<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $user_id = (int)$data['user_id'];
    $vocabulary_id = (int)$data['vocabulary_id'];

    // Check if already exists
    $check = $conn->query("SELECT * FROM user_vocabulary WHERE user_id = $user_id AND vocabulary_id = $vocabulary_id");

    if ($check->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Already added']);
    } else {
        $insert = "INSERT INTO user_vocabulary (user_id, vocabulary_id, mastery_level) VALUES ($user_id, $vocabulary_id, 'learning')";

        if ($conn->query($insert)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>
