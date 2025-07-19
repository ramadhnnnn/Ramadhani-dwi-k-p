<?php
// /backend/api/users/get_level_info.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://games.teluapp.org');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';

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
        'total_xp' => $xp,
        'current_level_xp' => $totalXPNeeded
    ];
}

try {
    session_start();
    $user_id = $_SESSION['user_id'] ?? 1; // Default untuk testing

    // Get user's current level and XP
    $user_sql = "SELECT level, xp FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();

    if (!$user_data) {
        throw new Exception('User not found');
    }

    // Calculate level info
    $levelInfo = calculateLevel($user_data['xp']);
    $progress_percentage = ($levelInfo['current_xp'] / $levelInfo['needed_xp']) * 100;

    // Define level benefits
    $benefits = [
        'xp_multiplier' => 1 + ($levelInfo['level'] * 0.05), // 5% increase per level
        'features' => []
    ];

    // Add available features based on level
    if ($levelInfo['level'] >= 2) {
        $benefits['features'][] = 'Challenge Mode';
    }
    if ($levelInfo['level'] >= 3) {
        $benefits['features'][] = 'Time Attack Mode';
    }
    if ($levelInfo['level'] >= 4) {
        $benefits['features'][] = 'Custom Puzzles';
    }
    if ($levelInfo['level'] >= 5) {
        $benefits['features'][] = 'Create Puzzles';
    }

    // Prepare response
    $response = [
        'success' => true,
        'level_info' => [
            'current_level' => $levelInfo['level'],
            'current_xp' => $levelInfo['current_xp'],
            'next_level_xp' => $levelInfo['needed_xp'],
            'total_xp' => $levelInfo['total_xp'],
            'progress_percentage' => round($progress_percentage, 2),
            'benefits' => $benefits
        ]
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