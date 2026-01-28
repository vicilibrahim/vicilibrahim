<?php
/**
 * Instagram Planner Configuration
 * Terra+ Instagram Content Management System
 */

// Prevent direct access
if (!defined('IG_PLANNER')) {
    define('IG_PLANNER', true);
}

// Instagram Graph API Configuration
define('IG_APP_ID', getenv('INSTAGRAM_APP_ID') ?: 'YOUR_APP_ID');
define('IG_APP_SECRET', getenv('INSTAGRAM_APP_SECRET') ?: 'YOUR_APP_SECRET');
define('IG_REDIRECT_URI', getenv('INSTAGRAM_REDIRECT_URI') ?: 'http://localhost/instagram-planner/callback.php');
define('IG_GRAPH_API_VERSION', 'v18.0');
define('IG_GRAPH_API_URL', 'https://graph.facebook.com/' . IG_GRAPH_API_VERSION);

// OpenAI Configuration
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: 'YOUR_OPENAI_API_KEY');
define('OPENAI_MODEL', 'gpt-4');
define('OPENAI_MAX_TOKENS', 2000);

// Unsplash Configuration
define('UNSPLASH_ACCESS_KEY', getenv('UNSPLASH_ACCESS_KEY') ?: 'YOUR_UNSPLASH_KEY');

// Application Settings
define('IG_PLANNER_VERSION', '1.0.0');
define('IG_PLANNER_NAME', 'Terra+ Instagram Planner');
define('IG_MAX_HASHTAGS', 30);
define('IG_MAX_CAPTION_LENGTH', 2200);
define('IG_MAX_CAROUSEL_ITEMS', 10);
define('IG_STORY_DURATION', 15);
define('IG_REEL_MAX_DURATION', 90);

// Content Strategy Percentages
define('IG_STRATEGY_EDUCATIONAL', 40);
define('IG_STRATEGY_PRODUCT', 30);
define('IG_STRATEGY_BTS', 20);
define('IG_STRATEGY_UGC', 10);

// Upload Settings
define('IG_UPLOAD_DIR', __DIR__ . '/../uploads/');
define('IG_MAX_IMAGE_SIZE', 10 * 1024 * 1024); // 10MB
define('IG_MAX_VIDEO_SIZE', 100 * 1024 * 1024); // 100MB
define('IG_ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('IG_ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/quicktime']);

// Scheduling Settings
define('IG_CRON_INTERVAL', 60); // Check every 60 seconds
define('IG_RETRY_ATTEMPTS', 3);
define('IG_RETRY_DELAY', 300); // 5 minutes between retries

// Best Posting Times (default, will be personalized per account)
define('IG_DEFAULT_BEST_TIMES', [
    'weekday' => ['10:00', '11:00', '18:00', '19:00', '20:00'],
    'weekend' => ['14:00', '15:00', '16:00']
]);

// Include database connection from parent
require_once __DIR__ . '/../../config/database.php';

// Instagram Planner specific helper functions
function ig_get_account($user_id) {
    global $conn;
    $user_id = (int)$user_id;
    $query = "SELECT * FROM instagram_accounts WHERE user_id = $user_id AND is_active = 1";
    $result = $conn->query($query);
    return $result ? $result->fetch_assoc() : null;
}

function ig_get_all_accounts($user_id) {
    global $conn;
    $user_id = (int)$user_id;
    $query = "SELECT * FROM instagram_accounts WHERE user_id = $user_id ORDER BY created_at DESC";
    $result = $conn->query($query);
    $accounts = [];
    while ($row = $result->fetch_assoc()) {
        $accounts[] = $row;
    }
    return $accounts;
}

function ig_get_posts($account_id, $status = null, $limit = 50) {
    global $conn;
    $account_id = (int)$account_id;
    $limit = (int)$limit;

    $where = "account_id = $account_id";
    if ($status) {
        $status = $conn->real_escape_string($status);
        $where .= " AND status = '$status'";
    }

    $query = "SELECT p.*, c.name as category_name, c.color as category_color
              FROM ig_posts p
              LEFT JOIN ig_content_categories c ON p.content_category_id = c.id
              WHERE $where
              ORDER BY COALESCE(scheduled_at, created_at) DESC
              LIMIT $limit";

    $result = $conn->query($query);
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    return $posts;
}

function ig_get_post($post_id) {
    global $conn;
    $post_id = (int)$post_id;
    $query = "SELECT p.*, c.name as category_name, c.color as category_color,
                     a.username, a.profile_picture
              FROM ig_posts p
              LEFT JOIN ig_content_categories c ON p.content_category_id = c.id
              LEFT JOIN instagram_accounts a ON p.account_id = a.id
              WHERE p.id = $post_id";
    $result = $conn->query($query);
    return $result ? $result->fetch_assoc() : null;
}

function ig_get_post_media($post_id) {
    global $conn;
    $post_id = (int)$post_id;
    $query = "SELECT * FROM ig_post_media WHERE post_id = $post_id ORDER BY order_index ASC";
    $result = $conn->query($query);
    $media = [];
    while ($row = $result->fetch_assoc()) {
        $media[] = $row;
    }
    return $media;
}

function ig_get_post_hashtags($post_id) {
    global $conn;
    $post_id = (int)$post_id;
    $query = "SELECT h.*, ph.position
              FROM ig_post_hashtags ph
              JOIN ig_hashtags h ON ph.hashtag_id = h.id
              WHERE ph.post_id = $post_id";
    $result = $conn->query($query);
    $hashtags = [];
    while ($row = $result->fetch_assoc()) {
        $hashtags[] = $row;
    }
    return $hashtags;
}

function ig_get_scheduled_posts($account_id, $start_date = null, $end_date = null) {
    global $conn;
    $account_id = (int)$account_id;

    $where = "account_id = $account_id AND status = 'scheduled'";

    if ($start_date) {
        $start_date = $conn->real_escape_string($start_date);
        $where .= " AND scheduled_at >= '$start_date'";
    }

    if ($end_date) {
        $end_date = $conn->real_escape_string($end_date);
        $where .= " AND scheduled_at <= '$end_date'";
    }

    $query = "SELECT p.*, c.name as category_name, c.color as category_color,
                     (SELECT media_url FROM ig_post_media WHERE post_id = p.id ORDER BY order_index LIMIT 1) as thumbnail
              FROM ig_posts p
              LEFT JOIN ig_content_categories c ON p.content_category_id = c.id
              WHERE $where
              ORDER BY scheduled_at ASC";

    $result = $conn->query($query);
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    return $posts;
}

function ig_get_content_categories() {
    global $conn;
    $query = "SELECT * FROM ig_content_categories ORDER BY percentage DESC";
    $result = $conn->query($query);
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    return $categories;
}

function ig_get_hashtags_by_category($category = null, $niche = null, $limit = 30) {
    global $conn;
    $limit = (int)$limit;

    $where = "is_banned = 0";

    if ($category) {
        $category = $conn->real_escape_string($category);
        $where .= " AND category = '$category'";
    }

    if ($niche) {
        $niche = $conn->real_escape_string($niche);
        $where .= " AND niche = '$niche'";
    }

    $query = "SELECT * FROM ig_hashtags WHERE $where ORDER BY media_count DESC LIMIT $limit";
    $result = $conn->query($query);
    $hashtags = [];
    while ($row = $result->fetch_assoc()) {
        $hashtags[] = $row;
    }
    return $hashtags;
}

function ig_get_trending_audio($limit = 20) {
    global $conn;
    $limit = (int)$limit;
    $query = "SELECT * FROM ig_trending_audio WHERE is_active = 1 ORDER BY trend_score DESC LIMIT $limit";
    $result = $conn->query($query);
    $audio = [];
    while ($row = $result->fetch_assoc()) {
        $audio[] = $row;
    }
    return $audio;
}

function ig_get_viral_formats($niche = null) {
    global $conn;
    $where = "1=1";
    if ($niche) {
        $niche = $conn->real_escape_string($niche);
        $where .= " AND (niche = '$niche' OR niche = 'general')";
    }
    $query = "SELECT * FROM ig_viral_formats WHERE $where ORDER BY avg_engagement DESC";
    $result = $conn->query($query);
    $formats = [];
    while ($row = $result->fetch_assoc()) {
        $formats[] = $row;
    }
    return $formats;
}

function ig_get_best_times($account_id) {
    global $conn;
    $account_id = (int)$account_id;
    $query = "SELECT * FROM ig_best_times WHERE account_id = $account_id ORDER BY avg_engagement DESC LIMIT 10";
    $result = $conn->query($query);
    $times = [];
    while ($row = $result->fetch_assoc()) {
        $times[] = $row;
    }
    return $times;
}

function ig_get_weekly_stats($account_id) {
    global $conn;
    $account_id = (int)$account_id;
    $week_ago = date('Y-m-d', strtotime('-7 days'));

    // Get published posts this week
    $posts_query = "SELECT COUNT(*) as count FROM ig_posts
                    WHERE account_id = $account_id
                    AND published_at >= '$week_ago'";
    $posts_result = $conn->query($posts_query)->fetch_assoc();

    // Get total reach, engagement
    $stats_query = "SELECT
                        SUM(reach) as total_reach,
                        SUM(likes + comments + saves + shares) as total_engagement,
                        AVG(engagement_rate) as avg_engagement
                    FROM ig_posts
                    WHERE account_id = $account_id
                    AND published_at >= '$week_ago'";
    $stats_result = $conn->query($stats_query)->fetch_assoc();

    // Get follower change
    $analytics_query = "SELECT
                            SUM(new_followers) - SUM(lost_followers) as follower_change
                        FROM ig_analytics_daily
                        WHERE account_id = $account_id
                        AND date >= '$week_ago'";
    $analytics_result = $conn->query($analytics_query)->fetch_assoc();

    return [
        'posts_count' => $posts_result['count'] ?? 0,
        'total_reach' => $stats_result['total_reach'] ?? 0,
        'total_engagement' => $stats_result['total_engagement'] ?? 0,
        'avg_engagement_rate' => round($stats_result['avg_engagement'] ?? 0, 2),
        'follower_change' => $analytics_result['follower_change'] ?? 0
    ];
}

function ig_format_number($num) {
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) {
        return round($num / 1000, 1) . 'K';
    }
    return $num;
}

function ig_get_day_name($day_num, $lang = 'tr') {
    $days_tr = ['Paz', 'Pzt', 'Sal', 'Car', 'Per', 'Cum', 'Cmt'];
    $days_en = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    return $lang === 'tr' ? $days_tr[$day_num] : $days_en[$day_num];
}

function ig_json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
?>
