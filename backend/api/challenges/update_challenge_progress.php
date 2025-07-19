<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://games.teluapp.org');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

error_reporting(E_ALL);
ini_set('display_errors', 1);
error_log("Processing challenge progress update...");

// Fungsi untuk menghitung achievement yang didapat hari ini
function getTodayAchievements($conn, $userId) {
    $achievements_sql = "SELECT COUNT(*) as count 
                        FROM puzzle_scores ps
                        WHERE user_id = ? 
                        AND DATE(created_at) = CURDATE()
                        AND (
                            -- First Victory (game pertama hari ini)
                            (
                                NOT EXISTS (
                                    SELECT 1 FROM puzzle_scores 
                                    WHERE user_id = ps.user_id 
                                    AND DATE(created_at) < CURDATE()
                                )
                            ) OR
                            -- Speed Demon
                            (completion_time <= 12) OR
                            -- Perfect Solver
                            (steps <= 10) OR
                            -- Pro Player
                            (steps <= 20)
                        )";

    $stmt = $conn->prepare($achievements_sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];

    return $count;
}
try {
    session_start();
    $user_id = $_SESSION['user_id'] ?? 1;
    
    // Get POST data
    $raw_input = file_get_contents('php://input');
    error_log("Raw input: " . $raw_input);
    
    $data = json_decode($raw_input, true);
    if (!isset($data['challenge_id'], $data['progress'])) {
        throw new Exception('Missing required fields');
    }

    $challenge_id = (int)$data['challenge_id'];
    $progress = (int)$data['progress'];

    $conn->begin_transaction();

    try {
        // Get challenge info
        $check_sql = "SELECT * FROM weekly_challenges 
                     WHERE id = ? AND end_date >= CURDATE()";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("i", $challenge_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $challenge = $result->fetch_assoc();

        if (!$challenge) {
            throw new Exception("Challenge not found or expired");
        }

        // Check if progress already exists
        $progress_sql = "SELECT id, progress FROM user_challenge_progress 
                        WHERE user_id = ? AND challenge_id = ?";
        $stmt = $conn->prepare($progress_sql);
        $stmt->bind_param("ii", $user_id, $challenge_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_progress = $result->fetch_assoc();

        // Handle Achievement Hunter differently
        if ($challenge['challenge_type'] === 'ACHIEVEMENT_HUNTER') {
            $today_achievements = getTodayAchievements($conn, $user_id);
            $is_completed = $today_achievements >= $challenge['target'] ? 1 : 0;
            
            if (!$current_progress) {
                // Create new progress
                $insert_sql = "INSERT INTO user_challenge_progress 
                            (user_id, challenge_id, progress, completed, daily_count, last_updated)
                            VALUES (?, ?, ?, ?, ?, NOW())";
                
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("iiiii", 
                    $user_id, 
                    $challenge_id, 
                    $today_achievements,
                    $is_completed,
                    $today_achievements
                );
            } else {
                // Update existing progress
                $update_sql = "UPDATE user_challenge_progress 
                             SET progress = ?, 
                                 completed = ?,
                                 daily_count = ?,
                                 last_updated = NOW()
                             WHERE id = ?";
                
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("iiii", 
                    $today_achievements,
                    $is_completed,
                    $today_achievements,
                    $current_progress['id']
                );
            }
        } else {
            // Handle other challenges normally
            if (!$current_progress) {
                $insert_sql = "INSERT INTO user_challenge_progress 
                            (user_id, challenge_id, progress, completed, last_updated)
                            VALUES (?, ?, ?, ?, NOW())";
                
                $is_completed = $progress >= $challenge['target'] ? 1 : 0;
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("iiii", 
                    $user_id, 
                    $challenge_id, 
                    $progress,
                    $is_completed
                );
            } else {
                $new_progress = $progress;
                $is_completed = $new_progress >= $challenge['target'] ? 1 : 0;
                
                $update_sql = "UPDATE user_challenge_progress 
                             SET progress = ?, 
                                 completed = ?,
                                 last_updated = NOW()
                             WHERE id = ?";
                
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("iii", 
                    $new_progress, 
                    $is_completed,
                    $current_progress['id']
                );
            }
        }
        
        $stmt->execute();

        // Get updated challenge progress
        $progress_sql = "SELECT 
            wc.*,
            COALESCE(up.progress, 0) as user_progress,
            COALESCE(up.completed, 0) as is_completed,
            COALESCE(up.daily_count, 0) as daily_count
        FROM weekly_challenges wc
        LEFT JOIN user_challenge_progress up 
            ON wc.id = up.challenge_id AND up.user_id = ?
        WHERE wc.id = ?";
        
        $stmt = $conn->prepare($progress_sql);
        $stmt->bind_param("ii", $user_id, $challenge_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $updated_challenge = $result->fetch_assoc();

        // Special handling for Achievement Hunter in response
        if ($challenge['challenge_type'] === 'ACHIEVEMENT_HUNTER') {
            $updated_challenge['user_progress'] = $updated_challenge['daily_count'];
        }

        // Add remaining time
        $end_date = new DateTime($updated_challenge['end_date']);
        $now = new DateTime();
        $remaining = $now->diff($end_date);
        
        $updated_challenge['time_remaining'] = [
            'days' => $remaining->d,
            'hours' => $remaining->h,
            'minutes' => $remaining->i
        ];
        
        // Add icon
        $updated_challenge['icon'] = getIconForChallenge($updated_challenge['challenge_type']);

        $conn->commit();

        echo json_encode([
            'success' => true,
            'challenge' => $updated_challenge
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error updating challenge progress: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
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

$conn->close();