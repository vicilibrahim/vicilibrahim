<?php
/**
 * Get Post API
 * Terra+ Instagram Planner
 */

define('IG_PLANNER', true);
require_once __DIR__ . '/../config/config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$post_id = (int)($_GET['id'] ?? 0);

if (!$post_id) {
    ig_json_response(['success' => false, 'error' => 'Post ID required'], 400);
}

// Get post
$post = ig_get_post($post_id);

if (!$post) {
    ig_json_response(['success' => false, 'error' => 'Post not found'], 404);
}

// Verify access
$account = ig_get_account($_SESSION['user_id']);
if (!$account || $post['account_id'] != $account['id']) {
    ig_json_response(['success' => false, 'error' => 'Access denied'], 403);
}

// Get media
$post['media'] = ig_get_post_media($post_id);

// Get hashtags
$post['hashtags'] = ig_get_post_hashtags($post_id);

// Get type specific data
if ($post['post_type'] === 'reel') {
    global $conn;
    $reel_result = $conn->query("SELECT * FROM ig_reels_data WHERE post_id = $post_id");
    $post['reel_data'] = $reel_result->fetch_assoc();
}

if ($post['post_type'] === 'story') {
    global $conn;
    $story_result = $conn->query("SELECT * FROM ig_stories_data WHERE post_id = $post_id");
    $post['story_data'] = $story_result->fetch_assoc();
}

ig_json_response(['success' => true, 'post' => $post]);
?>
