<?php
/**
 * Save/Create Post API
 * Terra+ Instagram Planner
 */

define('IG_PLANNER', true);
require_once __DIR__ . '/../config/config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ig_json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

try {
    $user_id = $_SESSION['user_id'];
    $account_id = (int)($_POST['account_id'] ?? 0);

    // Verify account belongs to user
    $account = ig_get_account($user_id);
    if (!$account || $account['id'] != $account_id) {
        ig_json_response(['success' => false, 'error' => 'Invalid account'], 403);
    }

    // Collect post data
    $post_type = clean_input($_POST['post_type'] ?? 'feed');
    $caption = $_POST['caption'] ?? '';
    $first_comment = $_POST['first_comment'] ?? '';
    $location_id = clean_input($_POST['location_id'] ?? '');
    $location_name = clean_input($_POST['location_name'] ?? '');
    $content_category_id = (int)($_POST['content_category_id'] ?? 0);
    $schedule_type = clean_input($_POST['schedule_type'] ?? 'draft');
    $scheduled_at = $_POST['scheduled_at'] ?? null;
    $hashtags = $_POST['hashtags'] ?? '';

    // Validate post type
    if (!in_array($post_type, ['feed', 'carousel', 'story', 'reel'])) {
        ig_json_response(['success' => false, 'error' => 'Invalid post type'], 400);
    }

    // Determine status
    $status = 'draft';
    if ($schedule_type === 'schedule' && $scheduled_at) {
        $status = 'scheduled';
        $scheduled_at = date('Y-m-d H:i:s', strtotime($scheduled_at));
    } elseif ($schedule_type === 'now') {
        $status = 'scheduled';
        $scheduled_at = date('Y-m-d H:i:s');
    }

    // Insert post
    global $conn;

    $caption_escaped = $conn->real_escape_string($caption);
    $first_comment_escaped = $conn->real_escape_string($first_comment);

    $query = "INSERT INTO ig_posts (account_id, post_type, content_category_id, caption, first_comment, location_id, location_name, status, scheduled_at, created_at)
              VALUES ($account_id, '$post_type', " . ($content_category_id ?: 'NULL') . ", '$caption_escaped', '$first_comment_escaped', '$location_id', '$location_name', '$status', " . ($scheduled_at ? "'$scheduled_at'" : 'NULL') . ", NOW())";

    if (!$conn->query($query)) {
        throw new Exception('Failed to create post: ' . $conn->error);
    }

    $post_id = $conn->insert_id;

    // Handle media uploads
    if (!empty($_FILES['media'])) {
        $upload_dir = __DIR__ . '/../uploads/' . date('Y/m/');
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $files = $_FILES['media'];
        $media_count = is_array($files['name']) ? count($files['name']) : 1;

        for ($i = 0; $i < $media_count; $i++) {
            $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $tmp_name = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $type = is_array($files['type']) ? $files['type'][$i] : $files['type'];
            $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
            $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];

            if ($error !== UPLOAD_ERR_OK) {
                continue;
            }

            // Validate file type
            $media_type = 'image';
            if (strpos($type, 'video/') === 0) {
                $media_type = 'video';
                if ($size > IG_MAX_VIDEO_SIZE) {
                    continue;
                }
            } else {
                if ($size > IG_MAX_IMAGE_SIZE) {
                    continue;
                }
            }

            // Generate unique filename
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $filename = uniqid('ig_') . '_' . time() . '.' . $extension;
            $filepath = $upload_dir . $filename;
            $relative_path = 'uploads/' . date('Y/m/') . $filename;

            if (move_uploaded_file($tmp_name, $filepath)) {
                // Get dimensions for images
                $width = null;
                $height = null;
                if ($media_type === 'image') {
                    list($width, $height) = getimagesize($filepath);
                }

                // Insert media record
                $media_url = $conn->real_escape_string($relative_path);
                $media_query = "INSERT INTO ig_post_media (post_id, media_type, media_url, order_index, width, height)
                               VALUES ($post_id, '$media_type', '$media_url', $i, " . ($width ?: 'NULL') . ", " . ($height ?: 'NULL') . ")";
                $conn->query($media_query);
            }
        }
    }

    // Handle hashtags
    if (!empty($hashtags)) {
        $hashtag_list = array_map('trim', explode(',', $hashtags));
        foreach ($hashtag_list as $hashtag) {
            if (empty($hashtag)) continue;

            // Ensure hashtag starts with #
            if (strpos($hashtag, '#') !== 0) {
                $hashtag = '#' . $hashtag;
            }

            $hashtag = $conn->real_escape_string($hashtag);

            // Insert or get hashtag
            $conn->query("INSERT IGNORE INTO ig_hashtags (hashtag, niche) VALUES ('$hashtag', '{$account['niche']}')");

            $hashtag_result = $conn->query("SELECT id FROM ig_hashtags WHERE hashtag = '$hashtag'");
            if ($hashtag_row = $hashtag_result->fetch_assoc()) {
                $hashtag_id = $hashtag_row['id'];
                $conn->query("INSERT IGNORE INTO ig_post_hashtags (post_id, hashtag_id, position) VALUES ($post_id, $hashtag_id, 'caption')");
            }
        }
    }

    // Handle reel specific data
    if ($post_type === 'reel') {
        $audio_id = clean_input($_POST['audio_id'] ?? '');
        $hook_text = $conn->real_escape_string($_POST['hook_text'] ?? '');
        $format_type = clean_input($_POST['format_type'] ?? '');

        if ($audio_id || $hook_text || $format_type) {
            $conn->query("INSERT INTO ig_reels_data (post_id, audio_id, hook_text, format_type) VALUES ($post_id, '$audio_id', '$hook_text', '$format_type')");
        }
    }

    // Handle story specific data
    if ($post_type === 'story') {
        $sticker_type = clean_input($_POST['sticker_type'] ?? '');
        $add_to_highlight = (int)($_POST['add_to_highlight'] ?? 0);
        $highlight_name = clean_input($_POST['highlight_name'] ?? '');

        if ($sticker_type || $add_to_highlight) {
            $conn->query("INSERT INTO ig_stories_data (post_id, sticker_type, add_to_highlight, highlight_name) VALUES ($post_id, '$sticker_type', $add_to_highlight, '$highlight_name')");
        }
    }

    // Schedule job if status is scheduled
    if ($status === 'scheduled') {
        $conn->query("INSERT INTO ig_scheduled_jobs (post_id, job_type, scheduled_at, status) VALUES ($post_id, 'publish', '$scheduled_at', 'pending')");
    }

    ig_json_response([
        'success' => true,
        'post_id' => $post_id,
        'status' => $status,
        'message' => $status === 'scheduled' ? 'Icerik basariyla zamanlandi' : 'Taslak kaydedildi'
    ]);

} catch (Exception $e) {
    ig_json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
?>
