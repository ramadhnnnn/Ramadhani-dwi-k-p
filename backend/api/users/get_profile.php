<?php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://games.teluapp.org');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

function calculateLevel($xp) {
    $level = 1;
    $xpForNextLevel = 1000; // Base XP untuk level 1
    $currentLevelXP = 0;
    $totalXPNeeded = 0;
    
    while ($xp >= ($totalXPNeeded + $xpForNextLevel)) {
        $level++;
        $totalXPNeeded += $xpForNextLevel;
        $xpForNextLevel = 1000 * $level;
    }
    $currentXP = $xp - $totalXPNeeded;

    // Additional gamification info
    $perks = [];
    

    
    // Define level perks setelah level final dihitung
    if ($level >= 5) {
        $perks[] = [
            'icon' => '🎯',
            'name' => 'Accuracy Boost',
            'description' => '+5% XP for completing puzzles under 15 moves'
        ];
    }
    if ($level >= 10) {
        $perks[] = [
            'icon' => '⚡',
            'name' => 'Speed Master',
            'description' => '+10% XP for completing puzzles under 30 seconds'
        ];
    }
    if ($level >= 15) {
        $perks[] = [
            'icon' => '🎮',
            'name' => 'Puzzle Expert',
            'description' => 'Unlock expert puzzle creation tools'
        ];
    }
    if ($level >= 20) {
        $perks[] = [
            'icon' => '👑',
            'name' => 'Grand Master',
            'description' => 'Custom profile badge and unique puzzle effects'
        ];
    }

    // Calculate milestones
    $milestones = [
        [
            'level' => 5,
            'icon' => '🌟',
            'name' => 'Rising Star',
            'unlocked' => $level >= 5
        ],
        [
            'level' => 10,
            'icon' => '💫',
            'name' => 'Puzzle Virtuoso',
            'unlocked' => $level >= 10
        ],
        [
            'level' => 15,
            'icon' => '🏆',
            'name' => 'Elite Solver',
            'unlocked' => $level >= 15
        ],
        [
            'level' => 20,
            'icon' => '👑',
            'name' => 'Grandmaster',
            'unlocked' => $level >= 20
        ]
    ];
    return [
        'level' => $level,
        'current_xp' => $currentXP,
        'next_level_xp' => $xpForNextLevel,
        'total_xp_for_current' => $totalXPNeeded,
        'perks' => $perks,
        'milestones' => $milestones,
        'xp_multiplier' => 1 + ($level * 0.05)
    ];

}

try {
    session_start();
    $user_id = $_SESSION['user_id'] ?? null;
    
    if (!$user_id) {
        echo json_encode([
            'success' => false,
            'error' => 'Not authenticated'
        ]);
        exit;
    }

    // Get user data and stats
    $stats_sql = "SELECT 
        u.username,
        u.xp,
        u.current_streak,
        u.last_played,
        COUNT(DISTINCT ps.id) as games_played,
        MIN(NULLIF(ps.steps, 0)) as best_score,
        MIN(NULLIF(ps.completion_time, 0)) as best_time,
        COUNT(CASE WHEN ps.completion_time IS NOT NULL AND ps.steps > 0 THEN 1 END) as games_completed,
        (
            SELECT created_at 
            FROM puzzle_scores 
            WHERE user_id = u.id 
            ORDER BY created_at ASC 
            LIMIT 1
        ) as first_game_date,
        (
            SELECT COUNT(*) 
            FROM puzzle_scores 
            WHERE user_id = u.id 
            AND steps <= 20
            AND steps > 0
        ) as games_under_20_moves,
        (
            SELECT COUNT(*) 
            FROM puzzle_scores 
            WHERE user_id = u.id 
            AND completion_time <= 12
            AND completion_time > 0
        ) as fast_games_count,
        (
            SELECT COUNT(*) 
            FROM puzzle_scores 
            WHERE user_id = u.id 
            AND steps <= 10
            AND steps > 0
        ) as perfect_games_count
    FROM users u
    LEFT JOIN puzzle_scores ps ON u.id = ps.user_id
    WHERE u.id = ?
    GROUP BY u.id";

    $stmt = $conn->prepare($stats_sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }

    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute statement: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();

    if (!$user_data) {
        throw new Exception('User not found');
    }

    // Calculate level info
    $levelInfo = calculateLevel($user_data['xp']);

    // Get recent games
    $games_sql = "SELECT 
        steps,
        completion_time,
        difficulty,
        xp_earned,
        created_at
    FROM puzzle_scores 
    WHERE user_id = ?
    AND steps > 0
    ORDER BY created_at DESC 
    LIMIT 20";

    $stmt = $conn->prepare($games_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $games_result = $stmt->get_result();
    
    $recent_games = [];
    while ($game = $games_result->fetch_assoc()) {
        $recent_games[] = [
            'steps' => (int)$game['steps'],
            'completion_time' => $game['completion_time'] ? (int)$game['completion_time'] : null,
            'difficulty' => $game['difficulty'],
            'xp_earned' => (int)$game['xp_earned'],
            'created_at' => $game['created_at']
        ];
    }

    // Calculate achievements
    $achievements = [];
    
    // First Win
    if ($user_data['games_played'] >= 1) {
        $achievements[] = [
            'icon' => '🎮',
            'title' => 'First Victory',
            'description' => 'Complete your first puzzle',
            'unlocked' => true,
            'progress' => 1,
            'target' => 1,
            'achieved_at' => $user_data['first_game_date']
        ];
    } else {
        $achievements[] = [
            'icon' => '🎮',
            'title' => 'First Victory',
            'description' => 'Complete your first puzzle',
            'unlocked' => false,
            'progress' => 0,
            'target' => 1
        ];
    }
    
    // Speed Demon
    $fast_games = (int)$user_data['fast_games_count'];
    $target_fast_games = 30;
    if ($fast_games >= $target_fast_games) {
        $achievements[] = [
            'icon' => '⚡',
            'title' => 'Speed Demon',
            'description' => 'Complete 30 puzzles under 12 seconds',
            'unlocked' => true,
            'progress' => $target_fast_games,
            'target' => $target_fast_games,
            'achieved_at' => $user_data['last_played']
        ];
    } else {
        $achievements[] = [
            'icon' => '⚡',
            'title' => 'Speed Demon',
            'description' => 'Complete 30 puzzles under 12 seconds',
            'unlocked' => false,
            'progress' => $fast_games,
            'target' => $target_fast_games
        ];
    }
    
    // Perfect Solver
    $perfect_games = (int)$user_data['perfect_games_count'];
    $target_perfect_games = 20;
    if ($perfect_games >= $target_perfect_games) {
        $achievements[] = [
            'icon' => '🎯',
            'title' => 'Perfect Solver',
            'description' => 'Complete 20 puzzles within 10 moves',
            'unlocked' => true,
            'progress' => $target_perfect_games,
            'target' => $target_perfect_games,
            'achieved_at' => $user_data['last_played']
        ];
    } else {
        $achievements[] = [
            'icon' => '🎯',
            'title' => 'Perfect Solver',
            'description' => 'Complete 20 puzzles within 10 moves',
            'unlocked' => false,
            'progress' => $perfect_games,
            'target' => $target_perfect_games
        ];
    }
    
    // Dedicated Player
    if ($user_data['games_played'] >= 20) {
        $achievements[] = [
            'icon' => '🌟',
            'title' => 'Dedicated Player',
            'description' => 'Play 20+ games',
            'unlocked' => true,
            'progress' => 20,
            'target' => 20,
            'achieved_at' => $user_data['last_played']
        ];
    } else {
        $achievements[] = [
            'icon' => '🌟',
            'title' => 'Dedicated Player',
            'description' => 'Play 20+ games',
            'unlocked' => false,
            'progress' => min($user_data['games_played'], 20),
            'target' => 20
        ];
    }

    // Streak Master
    if ($user_data['current_streak'] >= 7) {
        $achievements[] = [
            'icon' => '🔥',
            'title' => 'Streak Master',
            'description' => 'Maintain a 7-day streak',
            'unlocked' => true,
            'progress' => 7,
            'target' => 7,
            'achieved_at' => $user_data['last_played']
        ];
    } else {
        $achievements[] = [
            'icon' => '🔥',
            'title' => 'Streak Master',
            'description' => 'Maintain a 7-day streak',
            'unlocked' => false,
            'progress' => min($user_data['current_streak'], 7),
            'target' => 7
        ];
    }

    // Pro Player
    if ($user_data['games_under_20_moves'] >= 20) {
        $achievements[] = [
            'icon' => '👑',
            'title' => 'Pro Player',
            'description' => 'Win 20 games under 20 moves',
            'unlocked' => true,
            'progress' => 20,
            'target' => 20,
            'achieved_at' => $user_data['last_played']
        ];
    } else {
        $achievements[] = [
            'icon' => '👑',
            'title' => 'Pro Player',
            'description' => 'Win 20 games under 20 moves',
            'unlocked' => false,
            'progress' => min($user_data['games_under_20_moves'], 20),
            'target' => 20
        ];
    }

    // Calculate rank based on level
    $rank = [
        'title' => 'Beginner 🎮',
        'color' => '#95a5a6'
    ];

    if ($levelInfo['level'] >= 20) {
        $rank = ['title' => 'Puzzle Master 🏆', 'color' => '#f1c40f'];
    } elseif ($levelInfo['level'] >= 15) {
        $rank = ['title' => 'Expert Solver 🎯', 'color' => '#2ecc71'];
    } elseif ($levelInfo['level'] >= 10) {
        $rank = ['title' => 'Skilled Player 🌟', 'color' => '#3498db'];
    } elseif ($levelInfo['level'] >= 5) {
        $rank = ['title' => 'Rising Star ⭐', 'color' => '#9b59b6'];
    }

    // Format response with new gamification elements
    $response = [
        'success' => true,
        'player' => [
            'username' => $user_data['username'],
            'level' => $levelInfo['level'],
            'xp' => (int)$user_data['xp'],
            'xp_progress' => [
                'current' => $levelInfo['current_xp'],
                'needed' => $levelInfo['next_level_xp'],
                'total' => (int)$user_data['xp'],
                'multiplier' => $levelInfo['xp_multiplier']
            ],
            'perks' => $levelInfo['perks'],
            'milestones' => $levelInfo['milestones'],
            'games_played' => (int)$user_data['games_played'],
            'games_won' => (int)$user_data['games_completed'],
            'best_score' => $user_data['best_score'] ? (int)$user_data['best_score'] : null,
            'best_time' => $user_data['best_time'] ? (int)$user_data['best_time'] : null,
            'current_streak' => (int)$user_data['current_streak'],
            'rank_title' => $rank['title'],
            'rank_color' => $rank['color']
        ],
        'achievements' => $achievements,
        'achievements_count' => count(array_filter($achievements, fn($a) => $a['unlocked'])),
        'recent_games' => $recent_games
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();