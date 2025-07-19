<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://games.teluapp.org');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
error_log("Receiving request to save_score.php");

function countUnlockedAchievementsToday($conn, $user_id) {
    // Pertama, cek status unlocked achievements
    $unlocked_achievements_sql = "SELECT 
        /* First Victory */
        (
            SELECT COUNT(*) 
            FROM puzzle_scores 
            WHERE user_id = ? 
            LIMIT 1
        ) >= 1 as first_win_unlocked,
        
        /* Speed Demon */
        (
            SELECT COUNT(*) 
            FROM puzzle_scores 
            WHERE user_id = ? 
            AND completion_time <= 12
        ) >= 30 as speed_demon_unlocked,
        
        /* Perfect Solver */
        (
            SELECT COUNT(*) 
            FROM puzzle_scores 
            WHERE user_id = ? 
            AND steps <= 10
        ) >= 20 as perfect_solver_unlocked,
        
        /* Pro Player */
        (
            SELECT COUNT(*) 
            FROM puzzle_scores 
            WHERE user_id = ? 
            AND steps <= 20
        ) >= 20 as pro_player_unlocked,
        
        /* Dedicated Player */
        (
            SELECT COUNT(*) 
            FROM puzzle_scores 
            WHERE user_id = ?
        ) >= 20 as dedicated_player_unlocked,
        
        /* Streak Master */
        (
            SELECT current_streak >= 7 
            FROM users 
            WHERE id = ?
        ) as streak_master_unlocked";

    $stmt = $conn->prepare($unlocked_achievements_sql);
    $stmt->bind_param("iiiiii", 
        $user_id, $user_id, $user_id, 
        $user_id, $user_id, $user_id
    );
    $stmt->execute();
    $unlocked = $stmt->get_result()->fetch_assoc();

    // Hitung achievement yang unlocked hari ini
    $count = 0;
    if ($unlocked['first_win_unlocked']) $count++;
    if ($unlocked['speed_demon_unlocked']) $count++;
    if ($unlocked['perfect_solver_unlocked']) $count++;
    if ($unlocked['pro_player_unlocked']) $count++;
    if ($unlocked['dedicated_player_unlocked']) $count++;
    if ($unlocked['streak_master_unlocked']) $count++;

    // Update Achievement Hunter Challenge
    $challenge_sql = "SELECT wc.id, wc.target, ucp.id as progress_id 
                     FROM weekly_challenges wc
                     LEFT JOIN user_challenge_progress ucp 
                        ON wc.id = ucp.challenge_id 
                        AND ucp.user_id = ?
                     WHERE wc.challenge_type = 'ACHIEVEMENT_HUNTER'
                     AND wc.end_date >= CURDATE()
                     LIMIT 1";
    
    $stmt = $conn->prepare($challenge_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $challenge = $stmt->get_result()->fetch_assoc();

    if ($challenge) {
        $is_completed = $count >= $challenge['target'] ? 1 : 0;

        if ($challenge['progress_id']) {
            // Update existing progress
            $update_sql = "UPDATE user_challenge_progress 
                          SET progress = ?, 
                              completed = ?,
                              daily_count = ?,
                              last_updated = NOW()
                          WHERE id = ?";
            
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("iiii", 
                $count,
                $is_completed,
                $count,
                $challenge['progress_id']
            );
        } else {
            // Create new progress
            $insert_sql = "INSERT INTO user_challenge_progress 
                          (user_id, challenge_id, progress, completed, daily_count, last_updated)
                          VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("iiiii", 
                $user_id,
                $challenge['id'],
                $count,
                $is_completed,
                $count
            );
        }
        $stmt->execute();
    }

    return $count;
}

function updateSpecificChallenge($conn, $user_id, $challenge_type) {
    // First check if progress exists and get last updated time
    $check_sql = "SELECT 
                    ucp.id, 
                    ucp.progress, 
                    ucp.last_updated,
                    wc.target,
                    wc.id as challenge_id
                  FROM weekly_challenges wc 
                  LEFT JOIN user_challenge_progress ucp ON wc.id = ucp.challenge_id AND ucp.user_id = ?
                  WHERE wc.challenge_type = ? AND wc.end_date >= CURDATE()
                  ORDER BY wc.created_at DESC LIMIT 1";
    
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("is", $user_id, $challenge_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $progress = $result->fetch_assoc();

    // Jika tidak ada challenge aktif
    if (!$progress || !$progress['challenge_id']) {
        return false;
    }

    // Special handling for DAILY_PLAYER challenge
    if ($challenge_type === 'DAILY_PLAYER') {
        if (!$progress['id']) {
            // First time playing - create new progress
            $insert_sql = "INSERT INTO user_challenge_progress (
                            user_id, 
                            challenge_id, 
                            progress, 
                            completed,
                            last_updated
                          ) VALUES (?, ?, 1, ?, NOW())";
            
            $stmt = $conn->prepare($insert_sql);
            $is_completed = 1 >= $progress['target'] ? 1 : 0;
            $stmt->bind_param("iii", $user_id, $progress['challenge_id'], $is_completed);
            return $stmt->execute();
        } else {
            // Check if last update was on a different day
            $last_updated = new DateTime($progress['last_updated']);
            $today = new DateTime();
            
            // Only increment if it's a new day and not the same day
            if ($last_updated->format('Y-m-d') !== $today->format('Y-m-d')) {
                // Check if it was yesterday
                $yesterday = (clone $today)->modify('-1 day');
                $new_progress = $last_updated->format('Y-m-d') === $yesterday->format('Y-m-d') 
                    ? $progress['progress'] + 1  // Increment if played yesterday
                    : 1;                         // Reset to 1 if streak broken
                
                $is_completed = $new_progress >= $progress['target'] ? 1 : 0;
                
                $update_sql = "UPDATE user_challenge_progress 
                              SET progress = ?, 
                                  completed = ?,
                                  last_updated = NOW()
                              WHERE id = ?";
                
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("iii", $new_progress, $is_completed, $progress['id']);
                return $stmt->execute();
            }
            return true; // Already updated today
        }
    } else {
        // Regular challenge update logic for other challenge types
        if (!$progress['id']) {
            // If no progress exists, create new
            $insert_sql = "INSERT INTO user_challenge_progress (
                            user_id, 
                            challenge_id, 
                            progress, 
                            completed,
                            last_updated
                          ) VALUES (?, ?, 1, ?, NOW())";
            
            $stmt = $conn->prepare($insert_sql);
            $is_completed = 1 >= $progress['target'] ? 1 : 0;
            $stmt->bind_param("iii", $user_id, $progress['challenge_id'], $is_completed);
            return $stmt->execute();
        } else {
            // Update existing progress
            $new_progress = ($progress['progress'] ?? 0) + 1;
            $is_completed = $new_progress >= $progress['target'] ? 1 : 0;
            
            $update_sql = "UPDATE user_challenge_progress 
                          SET progress = ?, 
                              completed = ?,
                              last_updated = NOW()
                          WHERE id = ?";
            
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("iii", $new_progress, $is_completed, $progress['id']);
            return $stmt->execute();
        }
    }
}

function getUpdatedChallenges($conn, $user_id) {
    $progress_sql = "SELECT 
        wc.*,
        COALESCE(up.progress, 0) as user_progress,
        COALESCE(up.completed, 0) as is_completed,
        COALESCE(up.daily_count, 0) as daily_count,
        wc.target,
        up.last_updated
    FROM weekly_challenges wc
    LEFT JOIN user_challenge_progress up 
        ON wc.id = up.challenge_id AND up.user_id = ?
    WHERE wc.end_date >= CURDATE()
    ORDER BY wc.created_at DESC";
    
    $stmt = $conn->prepare($progress_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $challenges = [];
    while ($row = $result->fetch_assoc()) {
        $end_date = new DateTime($row['end_date']);
        $now = new DateTime();
        $remaining = $now->diff($end_date);
        
        $row['time_remaining'] = [
            'days' => $remaining->d,
            'hours' => $remaining->h,
            'minutes' => $remaining->i
        ];
        
        // Khusus untuk Achievement Hunter, gunakan daily_count
        if ($row['challenge_type'] === 'ACHIEVEMENT_HUNTER') {
            $row['user_progress'] = $row['daily_count'];
        }
        
        $row['icon'] = getIconForChallenge($row['challenge_type']);
        $challenges[] = $row;
    }
    
    return $challenges;
}

function getIconForChallenge($type) {
    $icons = [
        'SPEED_MASTER' => 'fa-bolt',
        'PERFECT_SOLVER' => 'fa-bullseye',
        'DAILY_PLAYER' => 'fa-calendar-check',
        'COMBO_MASTER' => 'fa-fire',
        'PRECISION_KING' => 'fa-crosshairs',
        'ACHIEVEMENT_HUNTER' => 'fa-trophy',
        'default' => 'fa-star'
    ];
    return $icons[$type] ?? $icons['default'];
}

try {
    session_start();
    $user_id = $_SESSION['user_id'] ?? 1;
    
    $raw_input = file_get_contents('php://input');
    error_log("Raw input received: " . $raw_input);

    $data = json_decode($raw_input, true);
    error_log("Decoded data: " . print_r($data, true));

    if (!isset($data['steps'], $data['completion_time'], $data['difficulty'], $data['xp_earned'])) {
        throw new Exception("Missing required fields");
    }

    $steps = (int)$data['steps'];
    $completion_time = (int)$data['completion_time'];
    $level = strip_tags($data['difficulty']); 
    $xp_earned = (int)$data['xp_earned'];

    $conn->begin_transaction();

    try {
        // Insert score
        $score_sql = "INSERT INTO puzzle_scores (user_id, difficulty, steps, completion_time, xp_earned, created_at) 
                     VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($score_sql);
        $stmt->bind_param("isiii", $user_id, $level, $steps, $completion_time, $xp_earned);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to save score: " . $stmt->error);
        }

        // Update Achievement Hunter progress
        countUnlockedAchievementsToday($conn, $user_id);

        // Update challenges
        $challenges_updated = [];
        $total_reward_xp = 0;

        // Speed Master Challenge
        if ($completion_time <= 80 && $level === 'medium' && updateSpecificChallenge($conn, $user_id, 'SPEED_MASTER')) {
            $challenges_updated[] = 'SPEED_MASTER';
        }
        
        // Perfect Solver Challenge
        if ($steps <= 10 && updateSpecificChallenge($conn, $user_id, 'PERFECT_SOLVER')) {
            $challenges_updated[] = 'PERFECT_SOLVER';
        }

        // Daily Player Challenge
        if (updateSpecificChallenge($conn, $user_id, 'DAILY_PLAYER')) {
            $challenges_updated[] = 'DAILY_PLAYER';
        }

        // Precision King Challenge
        if ($steps <= 15 && updateSpecificChallenge($conn, $user_id, 'PRECISION_KING')) {
            $challenges_updated[] = 'PRECISION_KING';
        }

        // Combo Master Challenge (Hard Level)
        if ($level === 'hard' && updateSpecificChallenge($conn, $user_id, 'COMBO_MASTER')) {
            $challenges_updated[] = 'COMBO_MASTER';
        }

        // Check for completed challenges
        $completed_sql = "SELECT 
            wc.*,
            ucp.completed
        FROM weekly_challenges wc
        JOIN user_challenge_progress ucp ON wc.id = ucp.challenge_id
        WHERE wc.end_date >= CURDATE()
        AND ucp.user_id = ?
        AND ucp.completed = 1
        AND NOT EXISTS (
            SELECT 1 FROM challenge_rewards 
            WHERE challenge_id = wc.id AND user_id = ?
        )";

        $stmt = $conn->prepare($completed_sql);
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $completed_result = $stmt->get_result();

        $completed_challenges = [];

        while ($challenge = $completed_result->fetch_assoc()) {
            // Give reward
            $reward_sql = "INSERT INTO challenge_rewards (
                user_id, challenge_id, xp_earned, rewarded_at
            ) VALUES (?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($reward_sql);
            $stmt->bind_param("iii", $user_id, $challenge['id'], $challenge['reward_xp']);
            $stmt->execute();

            $total_reward_xp += $challenge['reward_xp'];
            $completed_challenges[] = [
                'title' => $challenge['title'],
                'xp_earned' => $challenge['reward_xp'],
                'is_new' => true 
            ];
        }

        // Update user XP and streak
        $total_xp = $xp_earned + $total_reward_xp;
        $update_user_sql = "UPDATE users 
                           SET xp = xp + ?,
                               level = FLOOR(1 + (xp + ?) / 1000),
                               last_played = CURRENT_DATE,
                               current_streak = CASE 
                                   WHEN last_played IS NULL THEN 1
                                   WHEN DATEDIFF(CURRENT_DATE, last_played) = 1 THEN current_streak + 1
                                   WHEN DATEDIFF(CURRENT_DATE, last_played) = 0 THEN current_streak
                                   ELSE 1
                               END
                           WHERE id = ?";

        $stmt = $conn->prepare($update_user_sql);
        $stmt->bind_param("iii", $total_xp, $total_xp, $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update user XP: " . $stmt->error);
        }

        $updated_challenges = getUpdatedChallenges($conn, $user_id);

        $conn->commit();

        echo json_encode([
            'success' => true,
            'score' => [
                'steps' => $steps,
                'time' => $completion_time,
                'xp_earned' => $total_xp
            ],
            'challenges' => $updated_challenges,
            'challenges_completed' => $completed_challenges,
            'reward_xp' => $total_reward_xp
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in save_score.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();