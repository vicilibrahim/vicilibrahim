<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $user_id = (int)$data['user_id'];
    $lesson_id = (int)$data['lesson_id'];
    $score = (int)$data['score'];
    $points = (int)$data['points'];

    // Update user progress
    $conn->query("
        UPDATE user_progress
        SET status = 'completed', score = $score, completed_at = NOW()
        WHERE user_id = $user_id AND lesson_id = $lesson_id
    ");

    // Update user points
    $conn->query("UPDATE users SET total_points = total_points + $points WHERE id = $user_id");

    // Update daily activity
    $today = date('Y-m-d');
    $check = $conn->query("SELECT * FROM daily_activity WHERE user_id = $user_id AND activity_date = '$today'");

    if ($check->num_rows > 0) {
        $conn->query("
            UPDATE daily_activity
            SET lessons_completed = lessons_completed + 1, points_earned = points_earned + $points
            WHERE user_id = $user_id AND activity_date = '$today'
        ");
    } else {
        $conn->query("
            INSERT INTO daily_activity (user_id, activity_date, lessons_completed, points_earned)
            VALUES ($user_id, '$today', 1, $points)
        ");
    }

    // Check achievements
    check_achievements($user_id);

    echo json_encode(['success' => true, 'points' => $points]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>
