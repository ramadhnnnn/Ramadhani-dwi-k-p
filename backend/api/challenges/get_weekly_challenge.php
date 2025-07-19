    <?php
    require_once __DIR__ . '/../../config/database.php';

    // Set headers
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: https://games.teluapp.org');
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Credentials: true');

    // Add error logging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    error_log("Processing weekly challenges...");

    try {
        session_start();
        $user_id = $_SESSION['user_id'] ?? 1;

        // Cek dan reset challenges yang expired
        $check_expired = "SELECT id FROM weekly_challenges 
                        WHERE end_date < CURDATE()";
        $expired_result = $conn->query($check_expired);
        
        if ($expired_result->num_rows > 0) {
            while ($expired = $expired_result->fetch_assoc()) {
                // Reset progress
                $reset_sql = "UPDATE user_challenge_progress 
                            SET progress = 0, completed = 0 
                            WHERE challenge_id = ?";
                $stmt = $conn->prepare($reset_sql);
                $stmt->bind_param("i", $expired['id']);
                $stmt->execute();
                
                // Update tanggal challenge
                $update_challenge = "UPDATE weekly_challenges 
                                SET start_date = CURDATE(),
                                    end_date = DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                                WHERE id = ?";
                $stmt = $conn->prepare($update_challenge);
                $stmt->bind_param("i", $expired['id']);
                $stmt->execute();
            }
        }

        // Get active challenges with progress
        $weekly_sql = "SELECT 
            wc.*,
            COUNT(DISTINCT ucp.user_id) as total_participants,
            COUNT(DISTINCT CASE WHEN ucp.completed = 1 THEN ucp.user_id END) as completed_count,
            COALESCE(up.progress, 0) as user_progress,
            COALESCE(up.completed, 0) as is_completed,
            CASE 
                WHEN wc.challenge_type = 'SPEED_MASTER' THEN 'Complete puzzles under 30 seconds'
                WHEN wc.challenge_type = 'PERFECT_SOLVER' THEN 'Complete puzzles with minimum moves'
                WHEN wc.challenge_type = 'DAILY_PLAYER' THEN 'Play consecutively'
                WHEN wc.challenge_type = 'COMBO_MASTER' THEN 'Complete 3 puzzles in Hard Level'
                WHEN wc.challenge_type = 'PRECISION_KING' THEN 'Complete puzzles with high accuracy' 
                WHEN wc.challenge_type = 'ACHIEVEMENT_HUNTER' THEN 'Earn multiple achievements'
                ELSE wc.description
            END as challenge_description
        FROM weekly_challenges wc
        LEFT JOIN user_challenge_progress ucp ON wc.id = ucp.challenge_id
        LEFT JOIN user_challenge_progress up ON wc.id = up.challenge_id AND up.user_id = ?
        WHERE wc.end_date >= CURDATE()
        GROUP BY wc.id
        ORDER BY wc.created_at DESC";

        $stmt = $conn->prepare($weekly_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $challenges = [];
        while ($challenge = $result->fetch_assoc()) {
            // Calculate remaining time
            $end_date = new DateTime($challenge['end_date']);
            $now = new DateTime();
            $remaining = $now->diff($end_date);
            
            $challenge['time_remaining'] = [
                'days' => $remaining->d,
                'hours' => $remaining->h,
                'minutes' => $remaining->i
            ];
            
            // Add icon class
            $challenge['icon'] = getIconForChallenge($challenge['challenge_type']);
            $challenges[] = $challenge;
        }

        // Check if we need to create default challenges
        if (empty($challenges)) {
            error_log("No active challenges found. Creating default challenges...");
            
            $default_challenges = [
                ['SPEED_MASTER', 'Speed Master', 'Complete 5 puzzles under 80 seconds in Medium difficulty', 5, 500],                ['PERFECT_SOLVER', 'Perfect Solver', 'Complete 3 puzzles with minimum moves', 3, 750],
                ['DAILY_PLAYER', 'Daily Challenge', 'Play 7 days in a row', 7, 1000],
                ['COMBO_MASTER', 'Hard Mode Master', 'Complete 3 puzzles in Hard Level', 3, 800],
                ['PRECISION_KING', 'Precision King', 'Complete 5 puzzles with less than 15 moves', 5, 900],
                ['ACHIEVEMENT_HUNTER', 'Achievement Hunter', 'Earn 3 achievements in a day', 3, 1200]
            ];

            $conn->begin_transaction();

            try {
                foreach ($default_challenges as [$type, $title, $desc, $target, $reward_xp]) {
                    $insert_sql = "INSERT INTO weekly_challenges (
                        challenge_type, title, description, target, reward_xp,
                        start_date, end_date, created_at
                    ) VALUES (?, ?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), NOW())";
                    
                    $stmt = $conn->prepare($insert_sql);
                    $stmt->bind_param("sssii", $type, $title, $desc, $target, $reward_xp);
                    $stmt->execute();
                    error_log("Inserted challenge: $title");
                }

                $conn->commit();
                error_log("All default challenges created successfully");
                
                // Fetch the newly created challenges
                $stmt = $conn->prepare($weekly_sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($challenge = $result->fetch_assoc()) {
                    $end_date = new DateTime($challenge['end_date']);
                    $now = new DateTime();
                    $remaining = $now->diff($end_date);
                    
                    $challenge['time_remaining'] = [
                        'days' => $remaining->d,
                        'hours' => $remaining->h,
                        'minutes' => $remaining->i
                    ];
                    $challenge['icon'] = getIconForChallenge($challenge['challenge_type']);
                    
                    $challenges[] = $challenge;
                }
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Error creating challenges: " . $e->getMessage());
                throw $e;
            }
        }

        echo json_encode([
            'success' => true,
            'challenges' => $challenges
        ]);

    } catch (Exception $e) {
        error_log("Error in weekly challenges: " . $e->getMessage());
        http_response_code(500);
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