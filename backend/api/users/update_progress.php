<?php
// /backend/api/users/update_progress.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://games.teluapp.org');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
// Fungsi untuk menghitung level
function calculateLevel($xp) {
    $level = 1;
    $xpForNextLevel = 1000; // Base XP untuk level 1
    $currentLevelXP = 0;
    $totalXPNeeded = 0;
    
    while ($xp >= ($totalXPNeeded + $xpForNextLevel)) {
        $totalXPNeeded += $xpForNextLevel;
        $level++;
        $xpForNextLevel = 1000 * $level;
    }
    
    return [
        'level' => $level,
        'current_xp' => $xp - $totalXPNeeded,
        'needed_xp' => $xpForNextLevel,
        'total_xp' => $xp
    ];
}

try {
    session_start();
    $user_id = $_SESSION['user_id'] ?? 1;

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['steps'], $data['completion_time'])) {
        throw new Exception('Missing required fields');
    }

    $steps = $data['steps'];
    $completion_time = $data['completion_time'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Get current user data
        $user_sql = "SELECT level, xp, current_streak, last_played FROM users WHERE id = ?";
        $stmt = $conn->prepare($user_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();

        // Calculate XP earned
        $base_xp = 100; // Base XP for completing puzzle
        $bonus_xp = 0;

        // Bonus for efficient solving
        if ($steps < 20) {
            $bonus_xp += (20 - $steps) * 10;
        }

        // Time bonus
        if ($completion_time < 30) {
            $bonus_xp += 50;
        }

        // Calculate streak
        $streak = 1;
        if ($user_data['last_played']) {
            $last_played = new DateTime($user_data['last_played']);
            $today = new DateTime();
            $diff = $today->diff($last_played);
            
            if ($diff->days == 1) {
                $streak = $user_data['current_streak'] + 1;
            } elseif ($diff->days == 0) {
                $streak = $user_data['current_streak'];
            }
        }

        // Streak bonus (max 100 XP)
        $streak_bonus = min($streak * 20, 100);
        $bonus_xp += $streak_bonus;

        // Apply level multiplier
        $level_multiplier = 1 + ($user_data['level'] * 0.05);
        $total_xp = ($base_xp + $bonus_xp) * $level_multiplier;

        // Calculate new level
        $new_xp = $user_data['xp'] + $total_xp;
        $levelInfo = calculateLevel($new_xp);
        $new_level = $levelInfo['level'];

        // Update user progress
        $update_sql = "UPDATE users SET 
            xp = ?,
            level = ?,
            current_streak = ?,
            last_played = CURRENT_DATE
            WHERE id = ?";

        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("iiii", $new_xp, $new_level, $streak, $user_id);
        $stmt->execute();

        // Record game score
        $score_sql = "INSERT INTO puzzle_scores (
            user_id,
            steps,
            completion_time,
            xp_earned,
            created_at
        ) VALUES (?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($score_sql);
        $stmt->bind_param("iiii", $user_id, $steps, $completion_time, $total_xp);
        $stmt->execute();

        $conn->commit();

        // Prepare response
        $response = [
            'success' => true,
            'progress' => [
                'xp_earned' => round($total_xp),
                'new_total_xp' => $new_xp,
                'new_level' => $new_level,
                'current_streak' => $streak,
                'level_up' => $new_level > $user_data['level']
            ]
        ];

        echo json_encode($response);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();