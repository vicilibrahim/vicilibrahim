<?php
/**
 * Delete Post API
 * Terra+ Instagram Planner
 */

define('IG_PLANNER', true);
require_once __DIR__ . '/../config/config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ig_json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$post_id = (int)($input['post_id'] ?? 0);

if (!$post_id) {
    ig_json_response(['success' => false, 'error' => 'Post ID required'], 400);
}

// Get post and verify ownership
$post = ig_get_post($post_id);

if (!$post) {
    ig_json_response(['success' => false, 'error' => 'Post not found'], 404);
}

$account = ig_get_account($_SESSION['user_id']);
if (!$account || $post['account_id'] != $account['id']) {
    ig_json_response(['success' => false, 'error' => 'Access denied'], 403);
}

// Don't allow deleting published posts
if ($post['status'] === 'published') {
    ig_json_response(['success' => false, 'error' => 'Yayindaki icerikler silinemez'], 400);
}

global $conn;

// Delete related data first
$conn->query("DELETE FROM ig_post_media WHERE post_id = $post_id");
$conn->query("DELETE FROM ig_post_hashtags WHERE post_id = $post_id");
$conn->query("DELETE FROM ig_reels_data WHERE post_id = $post_id");
$conn->query("DELETE FROM ig_stories_data WHERE post_id = $post_id");
$conn->query("DELETE FROM ig_scheduled_jobs WHERE post_id = $post_id");

// Delete post
$result = $conn->query("DELETE FROM ig_posts WHERE id = $post_id");

if ($result) {
    ig_json_response(['success' => true, 'message' => 'Icerik silindi']);
} else {
    ig_json_response(['success' => false, 'error' => 'Silme basarisiz: ' . $conn->error], 500);
}
?>
