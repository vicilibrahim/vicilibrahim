<?php
/**
 * AI Content Generation API
 * Terra+ Instagram Planner
 */

define('IG_PLANNER', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/AIContentGenerator.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ig_json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    // Try form data
    $input = $_POST;
}

$action = $input['action'] ?? '';

if (empty($action)) {
    ig_json_response(['success' => false, 'error' => 'Action required'], 400);
}

try {
    $ai = new AIContentGenerator();
    $account = ig_get_account($_SESSION['user_id']);

    $brand_info = [
        'name' => $account['brand_name'] ?? $account['username'],
        'voice' => $account['brand_voice'] ?? 'professional and friendly',
        'audience' => $account['target_audience'] ?? 'general audience',
        'niche' => $account['niche'] ?? 'business'
    ];

    switch ($action) {
        case 'generate_caption':
            $topic = $input['topic'] ?? '';
            $post_type = $input['post_type'] ?? 'feed';
            $category = $input['category'] ?? 'Educational';
            $tone = $input['tone'] ?? 'professional';
            $length = $input['length'] ?? 'medium';

            if (empty($topic)) {
                ig_json_response(['success' => false, 'error' => 'Topic required'], 400);
            }

            $brand_info['voice'] = $tone;

            $result = $ai->generateCaption($topic, $brand_info, [
                'type' => $post_type,
                'category' => $category,
                'language' => 'tr'
            ]);

            ig_json_response($result);
            break;

        case 'optimize_caption':
            $caption = $input['caption'] ?? '';

            if (empty($caption)) {
                ig_json_response(['success' => false, 'error' => 'Caption required'], 400);
            }

            $result = $ai->optimizeCaption($caption, [
                'goal' => $input['goal'] ?? 'engagement',
                'add_cta' => true,
                'add_emojis' => true
            ]);

            ig_json_response($result);
            break;

        case 'generate_hashtags':
            $topic = $input['topic'] ?? '';
            $niche = $input['niche'] ?? $account['niche'] ?? 'business';

            if (empty($topic)) {
                ig_json_response(['success' => false, 'error' => 'Topic required'], 400);
            }

            $result = $ai->generateHashtags($topic, $niche, [
                'count' => 30,
                'include_branded' => true,
                'brand_hashtag' => '#' . strtolower(str_replace(' ', '', $account['brand_name'] ?? $account['username']))
            ]);

            ig_json_response($result);
            break;

        case 'generate_reel_hook':
            $topic = $input['topic'] ?? '';
            $format = $input['format'] ?? null;

            if (empty($topic)) {
                ig_json_response(['success' => false, 'error' => 'Topic required'], 400);
            }

            $result = $ai->generateReelHook($topic, $format, [
                'niche' => $account['niche'] ?? 'general',
                'goal' => 'engagement'
            ]);

            ig_json_response($result);
            break;

        case 'generate_carousel':
            $topic = $input['topic'] ?? '';
            $slide_count = (int)($input['slide_count'] ?? 5);

            if (empty($topic)) {
                ig_json_response(['success' => false, 'error' => 'Topic required'], 400);
            }

            $result = $ai->generateCarouselSlides($topic, $slide_count, [
                'style' => $input['style'] ?? 'educational',
                'niche' => $account['niche'] ?? 'business'
            ]);

            ig_json_response($result);
            break;

        case 'generate_story':
            $topic = $input['topic'] ?? '';
            $story_count = (int)($input['story_count'] ?? 3);

            if (empty($topic)) {
                ig_json_response(['success' => false, 'error' => 'Topic required'], 400);
            }

            $result = $ai->generateStoryContent($topic, $story_count, [
                'goal' => 'engagement',
                'include_poll' => true
            ]);

            ig_json_response($result);
            break;

        case 'generate_calendar':
            $weeks = (int)($input['weeks'] ?? 2);
            $posts_per_week = (int)($input['posts_per_week'] ?? 5);

            $result = $ai->generateContentCalendar($brand_info, [
                'posts_per_week' => $posts_per_week,
                'include_reels' => $input['include_reels'] ?? true,
                'include_stories' => $input['include_stories'] ?? true
            ]);

            ig_json_response($result);
            break;

        case 'suggest_audio':
            $topic = $input['topic'] ?? '';
            $mood = $input['mood'] ?? 'upbeat';

            if (empty($topic)) {
                ig_json_response(['success' => false, 'error' => 'Topic required'], 400);
            }

            $result = $ai->suggestTrendingAudio($topic, $mood, [
                'genre' => $input['genre'] ?? 'any',
                'duration' => $input['duration'] ?? '15-30'
            ]);

            ig_json_response($result);
            break;

        case 'analyze_times':
            $engagement_data = $input['engagement_data'] ?? [];

            $result = $ai->analyzeBestPostingTimes($engagement_data, [
                'timezone' => 'Europe/Istanbul',
                'followers' => $account['followers_count'] ?? 1000
            ]);

            ig_json_response($result);
            break;

        default:
            ig_json_response(['success' => false, 'error' => 'Unknown action'], 400);
    }

} catch (Exception $e) {
    ig_json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
?>
