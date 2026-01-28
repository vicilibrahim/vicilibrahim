<?php
/**
 * Instagram Graph API Helper Class
 * Terra+ Instagram Content Management System
 */

class InstagramAPI {
    private $access_token;
    private $business_account_id;
    private $api_base_url;

    public function __construct($access_token = null, $business_account_id = null) {
        $this->access_token = $access_token;
        $this->business_account_id = $business_account_id;
        $this->api_base_url = IG_GRAPH_API_URL;
    }

    public function setAccessToken($token) {
        $this->access_token = $token;
    }

    public function setBusinessAccountId($id) {
        $this->business_account_id = $id;
    }

    /**
     * Generate OAuth URL for Instagram Business Account
     */
    public static function getAuthUrl($state = null) {
        $params = [
            'client_id' => IG_APP_ID,
            'redirect_uri' => IG_REDIRECT_URI,
            'scope' => 'instagram_basic,instagram_content_publish,instagram_manage_insights,pages_show_list,pages_read_engagement',
            'response_type' => 'code',
            'state' => $state ?: bin2hex(random_bytes(16))
        ];

        return 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public function getAccessTokenFromCode($code) {
        $url = $this->api_base_url . '/oauth/access_token';

        $params = [
            'client_id' => IG_APP_ID,
            'client_secret' => IG_APP_SECRET,
            'redirect_uri' => IG_REDIRECT_URI,
            'code' => $code
        ];

        $response = $this->makeRequest($url, 'POST', $params);

        if (isset($response['access_token'])) {
            // Exchange for long-lived token
            return $this->exchangeForLongLivedToken($response['access_token']);
        }

        return $response;
    }

    /**
     * Exchange short-lived token for long-lived token (60 days)
     */
    public function exchangeForLongLivedToken($short_lived_token) {
        $url = $this->api_base_url . '/oauth/access_token';

        $params = [
            'grant_type' => 'fb_exchange_token',
            'client_id' => IG_APP_ID,
            'client_secret' => IG_APP_SECRET,
            'fb_exchange_token' => $short_lived_token
        ];

        return $this->makeRequest($url, 'GET', $params);
    }

    /**
     * Refresh long-lived token
     */
    public function refreshLongLivedToken() {
        $url = $this->api_base_url . '/oauth/access_token';

        $params = [
            'grant_type' => 'fb_exchange_token',
            'client_id' => IG_APP_ID,
            'client_secret' => IG_APP_SECRET,
            'fb_exchange_token' => $this->access_token
        ];

        return $this->makeRequest($url, 'GET', $params);
    }

    /**
     * Get Instagram Business Account from Facebook Page
     */
    public function getInstagramBusinessAccount($facebook_page_id) {
        $url = $this->api_base_url . "/{$facebook_page_id}";

        $params = [
            'fields' => 'instagram_business_account{id,username,profile_picture_url,followers_count,follows_count,media_count}',
            'access_token' => $this->access_token
        ];

        return $this->makeRequest($url, 'GET', $params);
    }

    /**
     * Get user's Facebook Pages
     */
    public function getFacebookPages() {
        $url = $this->api_base_url . '/me/accounts';

        $params = [
            'access_token' => $this->access_token
        ];

        return $this->makeRequest($url, 'GET', $params);
    }

    /**
     * Get account info
     */
    public function getAccountInfo() {
        $url = $this->api_base_url . "/{$this->business_account_id}";

        $params = [
            'fields' => 'id,username,profile_picture_url,followers_count,follows_count,media_count,biography,website',
            'access_token' => $this->access_token
        ];

        return $this->makeRequest($url, 'GET', $params);
    }

    /**
     * Get recent media
     */
    public function getRecentMedia($limit = 25) {
        $url = $this->api_base_url . "/{$this->business_account_id}/media";

        $params = [
            'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count',
            'limit' => $limit,
            'access_token' => $this->access_token
        ];

        return $this->makeRequest($url, 'GET', $params);
    }

    /**
     * Get media insights
     */
    public function getMediaInsights($media_id) {
        $url = $this->api_base_url . "/{$media_id}/insights";

        $params = [
            'metric' => 'engagement,impressions,reach,saved',
            'access_token' => $this->access_token
        ];

        return $this->makeRequest($url, 'GET', $params);
    }

    /**
     * Create media container for single image/video
     */
    public function createMediaContainer($media_url, $caption = '', $media_type = 'IMAGE', $options = []) {
        $url = $this->api_base_url . "/{$this->business_account_id}/media";

        $params = [
            'caption' => $caption,
            'access_token' => $this->access_token
        ];

        if ($media_type === 'VIDEO' || $media_type === 'REELS') {
            $params['video_url'] = $media_url;
            $params['media_type'] = $media_type;
        } else {
            $params['image_url'] = $media_url;
        }

        if ($media_type === 'STORIES') {
            $params['media_type'] = 'STORIES';
        }

        // Location tag
        if (!empty($options['location_id'])) {
            $params['location_id'] = $options['location_id'];
        }

        // Cover image for video
        if (!empty($options['cover_url'])) {
            $params['cover_url'] = $options['cover_url'];
        }

        // Thumbnail offset for reels
        if (!empty($options['thumb_offset'])) {
            $params['thumb_offset'] = $options['thumb_offset'];
        }

        return $this->makeRequest($url, 'POST', $params);
    }

    /**
     * Create carousel item container
     */
    public function createCarouselItemContainer($media_url, $is_video = false) {
        $url = $this->api_base_url . "/{$this->business_account_id}/media";

        $params = [
            'is_carousel_item' => true,
            'access_token' => $this->access_token
        ];

        if ($is_video) {
            $params['video_url'] = $media_url;
            $params['media_type'] = 'VIDEO';
        } else {
            $params['image_url'] = $media_url;
        }

        return $this->makeRequest($url, 'POST', $params);
    }

    /**
     * Create carousel container
     */
    public function createCarouselContainer($children_ids, $caption = '', $options = []) {
        $url = $this->api_base_url . "/{$this->business_account_id}/media";

        $params = [
            'media_type' => 'CAROUSEL',
            'children' => implode(',', $children_ids),
            'caption' => $caption,
            'access_token' => $this->access_token
        ];

        if (!empty($options['location_id'])) {
            $params['location_id'] = $options['location_id'];
        }

        return $this->makeRequest($url, 'POST', $params);
    }

    /**
     * Check container status
     */
    public function getContainerStatus($container_id) {
        $url = $this->api_base_url . "/{$container_id}";

        $params = [
            'fields' => 'status_code,status',
            'access_token' => $this->access_token
        ];

        return $this->makeRequest($url, 'GET', $params);
    }

    /**
     * Wait for container to be ready
     */
    public function waitForContainerReady($container_id, $max_attempts = 30, $delay = 2) {
        for ($i = 0; $i < $max_attempts; $i++) {
            $status = $this->getContainerStatus($container_id);

            if (isset($status['status_code'])) {
                if ($status['status_code'] === 'FINISHED') {
                    return true;
                } elseif ($status['status_code'] === 'ERROR') {
                    return false;
                }
            }

            sleep($delay);
        }

        return false;
    }

    /**
     * Publish media container
     */
    public function publishMedia($container_id) {
        $url = $this->api_base_url . "/{$this->business_account_id}/media_publish";

        $params = [
            'creation_id' => $container_id,
            'access_token' => $this->access_token
        ];

        return $this->makeRequest($url, 'POST', $params);
    }

    /**
     * Add comment to media
     */
    public function addComment($media_id, $message) {
        $url = $this->api_base_url . "/{$media_id}/comments";

        $params = [
            'message' => $message,
            'access_token' => $this->access_token
        ];

        return $this->makeRequest($url, 'POST', $params);
    }

    /**
     * Search hashtags
     */
    public function searchHashtag($query) {
        $url = $this->api_base_url . '/ig_hashtag_search';

        $params = [
            'user_id' => $this->business_account_id,
            'q' => $query,
            'access_token' => $this->access_token
        ];

        return $this->makeRequest($url, 'GET', $params);
    }

    /**
     * Get hashtag info
     */
    public function getHashtagInfo($hashtag_id) {
        $url = $this->api_base_url . "/{$hashtag_id}";

        $params = [
            'fields' => 'id,name,media_count',
            'access_token' => $this->access_token
        ];

        return $this->makeRequest($url, 'GET', $params);
    }

    /**
     * Search locations
     */
    public function searchLocations($query) {
        $url = $this->api_base_url . '/search';

        $params = [
            'type' => 'place',
            'q' => $query,
            'access_token' => $this->access_token
        ];

        return $this->makeRequest($url, 'GET', $params);
    }

    /**
     * Get account insights
     */
    public function getAccountInsights($metrics = null, $period = 'day') {
        $url = $this->api_base_url . "/{$this->business_account_id}/insights";

        if (!$metrics) {
            $metrics = 'impressions,reach,profile_views,website_clicks,follower_count';
        }

        $params = [
            'metric' => $metrics,
            'period' => $period,
            'access_token' => $this->access_token
        ];

        return $this->makeRequest($url, 'GET', $params);
    }

    /**
     * Publish a complete feed post
     */
    public function publishFeedPost($image_url, $caption, $first_comment = null, $location_id = null) {
        // Create container
        $container = $this->createMediaContainer($image_url, $caption, 'IMAGE', [
            'location_id' => $location_id
        ]);

        if (!isset($container['id'])) {
            return ['error' => 'Failed to create media container', 'details' => $container];
        }

        // Wait for container to be ready
        if (!$this->waitForContainerReady($container['id'])) {
            return ['error' => 'Container processing failed'];
        }

        // Publish
        $result = $this->publishMedia($container['id']);

        if (!isset($result['id'])) {
            return ['error' => 'Failed to publish media', 'details' => $result];
        }

        // Add first comment if provided
        if ($first_comment) {
            $this->addComment($result['id'], $first_comment);
        }

        return $result;
    }

    /**
     * Publish carousel post
     */
    public function publishCarouselPost($media_urls, $caption, $first_comment = null, $location_id = null) {
        // Create containers for each item
        $children_ids = [];

        foreach ($media_urls as $media) {
            $is_video = isset($media['type']) && $media['type'] === 'video';
            $url = is_array($media) ? $media['url'] : $media;

            $container = $this->createCarouselItemContainer($url, $is_video);

            if (isset($container['id'])) {
                if ($this->waitForContainerReady($container['id'])) {
                    $children_ids[] = $container['id'];
                }
            }
        }

        if (empty($children_ids)) {
            return ['error' => 'No valid media items for carousel'];
        }

        // Create carousel container
        $carousel = $this->createCarouselContainer($children_ids, $caption, [
            'location_id' => $location_id
        ]);

        if (!isset($carousel['id'])) {
            return ['error' => 'Failed to create carousel container', 'details' => $carousel];
        }

        // Wait and publish
        if (!$this->waitForContainerReady($carousel['id'])) {
            return ['error' => 'Carousel processing failed'];
        }

        $result = $this->publishMedia($carousel['id']);

        if (!isset($result['id'])) {
            return ['error' => 'Failed to publish carousel', 'details' => $result];
        }

        // Add first comment
        if ($first_comment) {
            $this->addComment($result['id'], $first_comment);
        }

        return $result;
    }

    /**
     * Publish story
     */
    public function publishStory($media_url, $is_video = false) {
        $media_type = $is_video ? 'VIDEO' : 'IMAGE';

        $params = [
            'media_type' => 'STORIES',
            'access_token' => $this->access_token
        ];

        if ($is_video) {
            $params['video_url'] = $media_url;
        } else {
            $params['image_url'] = $media_url;
        }

        $url = $this->api_base_url . "/{$this->business_account_id}/media";
        $container = $this->makeRequest($url, 'POST', $params);

        if (!isset($container['id'])) {
            return ['error' => 'Failed to create story container', 'details' => $container];
        }

        // Wait and publish
        if (!$this->waitForContainerReady($container['id'])) {
            return ['error' => 'Story processing failed'];
        }

        return $this->publishMedia($container['id']);
    }

    /**
     * Publish reel
     */
    public function publishReel($video_url, $caption, $cover_url = null, $thumb_offset = null) {
        $options = [];
        if ($cover_url) {
            $options['cover_url'] = $cover_url;
        }
        if ($thumb_offset !== null) {
            $options['thumb_offset'] = $thumb_offset;
        }

        $container = $this->createMediaContainer($video_url, $caption, 'REELS', $options);

        if (!isset($container['id'])) {
            return ['error' => 'Failed to create reel container', 'details' => $container];
        }

        // Wait for processing (reels take longer)
        if (!$this->waitForContainerReady($container['id'], 60, 5)) {
            return ['error' => 'Reel processing failed'];
        }

        return $this->publishMedia($container['id']);
    }

    /**
     * Make HTTP request
     */
    private function makeRequest($url, $method = 'GET', $params = []) {
        $ch = curl_init();

        if ($method === 'GET') {
            $url .= '?' . http_build_query($params);
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => 'cURL error: ' . $error];
        }

        return json_decode($response, true) ?: ['error' => 'Invalid JSON response'];
    }
}
?>
