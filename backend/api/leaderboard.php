<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://games.teluapp.org');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../config/database.php';

// Rank System Logic


function calculateStreak($conn, $userId) {
    $streak_sql = "WITH RECURSIVE dates AS (
        SELECT DATE(MIN(created_at)) as date
        FROM puzzle_scores
        WHERE user_id = ?
        
        UNION ALL
        
        SELECT DATE_ADD(date, INTERVAL 1 DAY)
        FROM dates
        WHERE date < CURDATE()
    ),
    user_play_dates AS (
        SELECT DISTINCT DATE(created_at) as play_date
        FROM puzzle_scores
        WHERE user_id = ?
    ),
    streak_calc AS (
        SELECT 
            d.date,
            CASE WHEN upd.play_date IS NOT NULL THEN 1 ELSE 0 END as played
        FROM dates d
        LEFT JOIN user_play_dates upd ON d.date = upd.play_date
        WHERE d.date <= CURDATE()
    ),
    streak_groups AS (
        SELECT
            date,
            played,
            SUM(CASE WHEN played = 0 THEN 1 ELSE 0 END) OVER (ORDER BY date) as streak_group
        FROM streak_calc
    )
    SELECT
        COUNT(*) as streak_length,
        MIN(date) as streak_start,
        MAX(date) as streak_end
    FROM streak_groups
    WHERE played = 1
    GROUP BY streak_group
    HAVING MAX(date) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
    ORDER BY streak_length DESC
    LIMIT 1";

    $stmt = $conn->prepare($streak_sql);
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        return (int)$row['streak_length'];
    }
    
    return 0;
}

function updateUserStreak($conn, $userId, $streak) {
    $update_streak_sql = "UPDATE users 
                            SET current_streak = ?,
                                last_played = CASE 
                                WHEN DATEDIFF(CURRENT_DATE, last_played) = 1 THEN CURRENT_DATE
                                WHEN DATEDIFF(CURRENT_DATE, last_played) = 0 THEN last_played
                                ELSE CURRENT_DATE
                                END
                            WHERE id = ?";
    
    $stmt = $conn->prepare($update_streak_sql);
    $stmt->bind_param("ii", $streak, $userId);
    $stmt->execute();
    
    if ($stmt->error) {
        error_log("Error updating streak: " . $stmt->error);
        return false;
    }
    
    return true;
}

try {
    // Get statistics
    $stats_sql = "SELECT 
        (SELECT COUNT(DISTINCT user_id) FROM puzzle_scores) as total_players,
        (SELECT MIN(steps) FROM puzzle_scores WHERE steps > 0) as best_score,
        (
            SELECT ROUND(AVG(min_steps), 2) 
            FROM (
                SELECT user_id, MIN(steps) as min_steps 
                FROM puzzle_scores 
                WHERE steps > 0 
                GROUP BY user_id
            ) as user_best_scores
        ) as average_score,
        (SELECT COUNT(*) FROM puzzle_scores) as total_games_played
    FROM dual";

    $stats_result = $conn->query($stats_sql);
    if (!$stats_result) {
        throw new Exception("Stats query failed: " . $conn->error);
    }
    $stats = $stats_result->fetch_assoc();

    // Get leaderboard with updated stats
    $leaderboard_sql = "WITH UserStats AS (
        SELECT 
            user_id,
            MIN(steps) as best_steps,
            MIN(completion_time) as best_time,
            COUNT(DISTINCT id) as total_games,
            SUM(xp_earned) as total_xp,
            COUNT(CASE WHEN steps <= 20 THEN 1 END) as games_under_20,
            COUNT(CASE WHEN completion_time <= 30 THEN 1 END) as speed_games,
            COUNT(CASE WHEN completion_time <= 20 THEN 1 END) as fast_games_count,
            COUNT(CASE WHEN steps <= 10 THEN 1 END) as perfect_games_count,
            MAX(created_at) as last_play_date,
            MIN(created_at) as first_game_date,
            (SELECT difficulty FROM puzzle_scores WHERE user_id = ps.user_id AND steps = MIN(ps.steps) LIMIT 1) as difficulty
        FROM puzzle_scores ps
        WHERE steps > 0
        GROUP BY user_id
    ),
    ChallengeStats AS (
        SELECT 
            ucp.user_id,
            COUNT(DISTINCT CASE WHEN ucp.completed = 1 THEN ucp.challenge_id END) as completed_challenges,
            SUM(CASE WHEN ucp.completed = 1 THEN wc.reward_xp ELSE 0 END) as challenge_xp,
            GROUP_CONCAT(
                DISTINCT CONCAT(
                    wc.challenge_type, ':', 
                    ucp.progress, ':', 
                    wc.target, ':', 
                    ucp.completed
                )
            ) as challenge_details
        FROM user_challenge_progress ucp
        JOIN weekly_challenges wc ON wc.id = ucp.challenge_id
        GROUP BY ucp.user_id
    )
    SELECT 
        u.id,
        u.username,
        u.current_streak,
        us.best_steps as steps,
        us.best_time as completion_time,
        us.total_games as games_played,
        us.total_xp as game_xp,
        us.games_under_20,
        us.speed_games,
        us.fast_games_count,
        us.perfect_games_count,
        us.last_play_date,
        us.first_game_date,
        us.difficulty,
        COALESCE(cs.completed_challenges, 0) as completed_challenges,
        COALESCE(cs.challenge_xp, 0) as challenge_xp,
        cs.challenge_details
    FROM users u
    JOIN UserStats us ON u.id = us.user_id
    LEFT JOIN ChallengeStats cs ON u.id = cs.user_id
    ORDER BY us.best_steps ASC, us.best_time ASC
    LIMIT 10";

    $result = $conn->query($leaderboard_sql);
    if (!$result) {
        throw new Exception("Leaderboard query failed: " . $conn->error);
    }

    $leaderboard = [];
    while ($row = $result->fetch_assoc()) {
        $currentStreak = calculateStreak($conn, $row['id']);
        updateUserStreak($conn, $row['id'], $currentStreak);
        
        // Parse challenge details
        $challenges = [];
        if ($row['challenge_details']) {
            $challengeList = explode(',', $row['challenge_details']);
            foreach ($challengeList as $challenge) {
                list($type, $progress, $target, $completed) = explode(':', $challenge);
                $challenges[] = [
                    'type' => $type,
                    'progress' => (int)$progress,
                    'target' => (int)$target,
                    'completed' => (int)$completed
                ];
            }
        }
        
        $achievements = [];
        
        // First Win
        if ($row['games_played'] >= 1) {
            $achievements[] = [
                'icon' => '🎮',
                'title' => 'First Victory',
                'description' => 'Complete your first puzzle',
                'unlocked' => true,
                'progress' => 1,
                'target' => 1,
                'achieved_at' => $row['first_game_date']
            ];
        }
        
        // Speed Demon
        if ($row['fast_games_count'] >= 30) {
            $achievements[] = [
                'icon' => '⚡',
                'title' => 'Speed Demon',
                'description' => 'Complete 30 puzzles under 12 seconds',
                'unlocked' => true,
                'progress' => 30,
                'target' => 30,
                'achieved_at' => $row['last_play_date']
            ];
        }
        
        // Perfect Solver
        if ($row['perfect_games_count'] >= 20) {
            $achievements[] = [
                'icon' => '🎯',
                'title' => 'Perfect Solver',
                'description' => 'Complete 20 puzzles within 10 moves',
                'unlocked' => true,
                'progress' => 20,
                'target' => 20,
                'achieved_at' => $row['last_play_date']
            ];
        }
        
        // Dedicated Player
        if ($row['games_played'] >= 20) {
            $achievements[] = [
                'icon' => '🌟',
                'title' => 'Dedicated Player',
                'description' => 'Play 20+ games',
                'unlocked' => true,
                'progress' => 20,
                'target' => 20,
                'achieved_at' => $row['last_play_date']
            ];
        }

        // Streak Master
        if ($currentStreak >= 7) {
            $achievements[] = [
                'icon' => '🔥',
                'title' => 'Streak Master',
                'description' => 'Maintain a 7-day streak',
                'unlocked' => true,
                'progress' => 7,
                'target' => 7,
                'achieved_at' => $row['last_play_date']
            ];
        }

        // Pro Player
        if ($row['games_under_20'] >= 20) {
            $achievements[] = [
                'icon' => '👑',
                'title' => 'Pro Player',
                'description' => 'Win 20 games under 20 moves',
                'unlocked' => true,
                'progress' => 20,
                'target' => 20,
                'achieved_at' => $row['last_play_date']
            ];
        }

        $totalXP = (int)$row['game_xp'] + (int)$row['challenge_xp'];
        $currentLevel = floor(($totalXP / 1000) + 1);
        
        // Calculate rank based on level
        if ($currentLevel >= 20) {
            $rankInfo = ['title' => 'Puzzle Master 🏆', 'color' => '#f1c40f'];
        } elseif ($currentLevel >= 15) {
            $rankInfo = ['title' => 'Expert Solver 🎯', 'color' => '#2ecc71'];
        } elseif ($currentLevel >= 10) {
            $rankInfo = ['title' => 'Skilled Player 🌟', 'color' => '#3498db'];
        } elseif ($currentLevel >= 5) {
            $rankInfo = ['title' => 'Rising Star ⭐', 'color' => '#9b59b6'];
        } else {
            $rankInfo = ['title' => 'Beginner 🎮', 'color' => '#95a5a6'];
        }

        $leaderboard[] = [
            'username' => htmlspecialchars($row['username']),
            'level' => floor(($totalXP / 1000) + 1),
            'xp' => $totalXP,
            'steps' => (int)$row['steps'],
            'completion_time' => (int)$row['completion_time'],
            'streak' => $currentStreak,
            'games_played' => (int)$row['games_played'],
            'difficulty' => $row['difficulty'],
            'achievements' => array_values(array_filter($achievements, fn($a) => $a['unlocked'])),
            'achievements_count' => count(array_filter($achievements, fn($a) => $a['unlocked'])),
            'completed_challenges' => (int)$row['completed_challenges'],
            'challenge_xp' => (int)$row['challenge_xp'],
            'challenges' => $challenges
        ];
    }

    // Get Weekly Challenge
    $weekly_sql = "SELECT 
        wc.*,
        COUNT(DISTINCT ucp.user_id) as total_participants,
        COUNT(DISTINCT CASE WHEN ucp.completed = 1 THEN ucp.user_id END) as completed_count
    FROM weekly_challenges wc
    LEFT JOIN user_challenge_progress ucp ON wc.id = ucp.challenge_id
    WHERE wc.end_date >= CURDATE()
    GROUP BY wc.id
    ORDER BY wc.created_at DESC";

    $result = $conn->query($weekly_sql);
    $weeklyChallenge = $result->fetch_assoc();

    if (!$weeklyChallenge || strtotime($weeklyChallenge['end_date']) < time()) {
        // Reset progress if challenge expired
        if ($weeklyChallenge) {
            $reset_sql = "UPDATE user_challenge_progress 
                         SET progress = 0, completed = 0 
                         WHERE challenge_id = ?";
            $stmt = $conn->prepare($reset_sql);
            $stmt->bind_param("i", $weeklyChallenge['id']);
            $stmt->execute();

            // Update challenge dates
            $update_sql = "UPDATE weekly_challenges 
                          SET start_date = CURDATE(),
                              end_date = DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                          WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("i", $weeklyChallenge['id']);
            $stmt->execute();
        } else {
            // Create new challenge if none exists
            $conn->query("INSERT INTO weekly_challenges (
                challenge_type, title, description, target,start_date, end_date, created_at
            ) VALUES (
                'PERFECT_WEEK',
                'Weekly Challenge',
                'Complete 10 puzzles with minimum moves',
                10,
                CURDATE(),
                DATE_ADD(CURDATE(), INTERVAL 7 DAY),
                NOW()
            )");
        }
        
        // Get the updated/new challenge
        $result = $conn->query($weekly_sql);
        $weeklyChallenge = $result->fetch_assoc();
    }

    // Add user progress if logged in
    if (isset($_SESSION['user_id'])) {
        $progress_sql = "SELECT progress, completed 
                        FROM user_challenge_progress 
                        WHERE user_id = ? AND challenge_id = ?";
        $stmt = $conn->prepare($progress_sql);
        $stmt->bind_param("ii", $_SESSION['user_id'], $weeklyChallenge['id']);
        $stmt->execute();
        $progress = $stmt->get_result()->fetch_assoc();
        
        $weeklyChallenge['user_progress'] = $progress['progress'] ?? 0;
        $weeklyChallenge['is_completed'] = $progress['completed'] ?? 0;
    }

    echo json_encode([
        'success' => true,
        'leaderboard' => $leaderboard,
        'stats' => [
            'total_players' => (int)$stats['total_players'],
            'best_score' => (int)$stats['best_score'],
            'average_score' => (float)$stats['average_score'],
            'total_games_played' => (int)$stats['total_games_played']
        ],
        'weekly_challenge' => $weeklyChallenge
    ]);

} catch (Exception $e) {
    error_log("Leaderboard error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug_info' => [
            'file' => __FILE__,
            'line' => __LINE__,
            'message' => $e->getMessage()
        ]
    ]);
}

$conn->close();