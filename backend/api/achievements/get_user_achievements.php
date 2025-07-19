<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://games.teluapp.org');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';

error_log('Achievement endpoint called');

// Achievement definitions
$achievement_types = [
    'FIRST_WIN' => [
        'icon' => '🎮',
        'title' => 'First Victory',
        'description' => 'Completed first puzzle',
        'xp_reward' => 100,
        'unlocked' => false
    ],
    'SPEED_DEMON' => [
        'icon' => '⚡',
        'title' => 'Speed Demon',
        'description' => 'Completed 30 puzzle under 12 second',
        'xp_reward' => 200,
        'unlocked' => false
    ],
    'PERFECT_SCORE' => [
        'icon' => '🎯',
        'title' => 'Perfect Score',
        'description' => 'Completed with minimum moves',
        'xp_reward' => 300,
        'unlocked' => false
    ],
    'STREAK_MASTER' => [
        'icon' => '🔥',
        'title' => 'Streak Master',
        'description' => 'Maintained 7-day streak',
        'xp_reward' => 500,
        'unlocked' => false
    ]
];

try {
    $achievements = [];
    
    // Add all achievements as locked by default
    foreach ($achievement_types as $type => $achievement) {
        $achievements[] = $achievement;
    }

    error_log('Sending achievements response: ' . json_encode($achievements));
    
    echo json_encode([
        'success' => true,
        'achievements' => $achievements
    ]);

} catch (Exception $e) {
    error_log("Achievement error: " . $e->getMessage());
    echo json_encode([
        'success' => true,
        'achievements' => array_values($achievement_types)
    ]);
}