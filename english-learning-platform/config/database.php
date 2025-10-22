<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'english_learning_platform');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Helper function to prevent SQL injection
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Redirect if not logged in
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }
}

// Redirect if not admin
function require_admin() {
    if (!is_admin()) {
        header('Location: dashboard.php');
        exit();
    }
}

// Get user data
function get_user_data($user_id) {
    global $conn;
    $user_id = (int)$user_id;
    $query = "SELECT * FROM users WHERE id = $user_id";
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

// Update user streak
function update_user_streak($user_id) {
    global $conn;
    $user_id = (int)$user_id;
    $today = date('Y-m-d');

    // Check if user has activity today
    $check = "SELECT * FROM daily_activity WHERE user_id = $user_id AND activity_date = '$today'";
    $result = $conn->query($check);

    if ($result->num_rows == 0) {
        // Check yesterday's activity
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $check_yesterday = "SELECT * FROM daily_activity WHERE user_id = $user_id AND activity_date = '$yesterday'";
        $result_yesterday = $conn->query($check_yesterday);

        if ($result_yesterday->num_rows > 0) {
            // Continue streak
            $conn->query("UPDATE users SET streak_days = streak_days + 1, last_login = NOW() WHERE id = $user_id");
        } else {
            // Reset streak
            $conn->query("UPDATE users SET streak_days = 1, last_login = NOW() WHERE id = $user_id");
        }

        // Create today's activity
        $conn->query("INSERT INTO daily_activity (user_id, activity_date) VALUES ($user_id, '$today')");
    }
}

// Format time ago
function time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;

    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);

    if ($seconds <= 60) {
        return "Az önce";
    } else if ($minutes <= 60) {
        return "$minutes dakika önce";
    } else if ($hours <= 24) {
        return "$hours saat önce";
    } else if ($days <= 7) {
        return "$days gün önce";
    } else if ($weeks <= 4.3) {
        return "$weeks hafta önce";
    } else if ($months <= 12) {
        return "$months ay önce";
    } else {
        return "$years yıl önce";
    }
}

// Check and award achievements
function check_achievements($user_id) {
    global $conn;
    $user_id = (int)$user_id;

    // Get user data
    $user = get_user_data($user_id);

    // Get all achievements
    $achievements = $conn->query("SELECT * FROM achievements");

    while ($achievement = $achievements->fetch_assoc()) {
        // Check if user already has this achievement
        $check = $conn->query("SELECT * FROM user_achievements WHERE user_id = $user_id AND achievement_id = {$achievement['id']}");

        if ($check->num_rows == 0) {
            $earned = false;

            switch ($achievement['requirement_type']) {
                case 'lessons_completed':
                    $count = $conn->query("SELECT COUNT(*) as total FROM user_progress WHERE user_id = $user_id AND status = 'completed'")->fetch_assoc()['total'];
                    if ($count >= $achievement['requirement_value']) {
                        $earned = true;
                    }
                    break;

                case 'streak_days':
                    if ($user['streak_days'] >= $achievement['requirement_value']) {
                        $earned = true;
                    }
                    break;

                case 'total_points':
                    if ($user['total_points'] >= $achievement['requirement_value']) {
                        $earned = true;
                    }
                    break;
            }

            if ($earned) {
                $conn->query("INSERT INTO user_achievements (user_id, achievement_id) VALUES ($user_id, {$achievement['id']})");
            }
        }
    }
}
?>
