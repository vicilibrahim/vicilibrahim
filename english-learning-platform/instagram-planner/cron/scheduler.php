<?php
/**
 * Instagram Post Scheduler - Cron Job
 * Terra+ Instagram Content Management System
 *
 * Run this script every minute via cron:
 * * * * * * php /path/to/instagram-planner/cron/scheduler.php
 */

// Prevent direct browser access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

define('IG_PLANNER', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/InstagramAPI.php';

class InstagramScheduler {
    private $conn;
    private $log_file;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->log_file = __DIR__ . '/../logs/scheduler_' . date('Y-m-d') . '.log';

        // Create logs directory if not exists
        if (!is_dir(__DIR__ . '/../logs')) {
            mkdir(__DIR__ . '/../logs', 0755, true);
        }
    }

    public function run() {
        $this->log("Scheduler started at " . date('Y-m-d H:i:s'));

        // Get all pending jobs that are due
        $jobs = $this->getPendingJobs();

        if (empty($jobs)) {
            $this->log("No pending jobs found");
            return;
        }

        $this->log("Found " . count($jobs) . " pending jobs");

        foreach ($jobs as $job) {
            $this->processJob($job);
        }

        $this->log("Scheduler completed at " . date('Y-m-d H:i:s'));
    }

    private function getPendingJobs() {
        $now = date('Y-m-d H:i:s');

        $query = "SELECT j.*, p.*, a.access_token, a.business_account_id
                  FROM ig_scheduled_jobs j
                  JOIN ig_posts p ON j.post_id = p.id
                  JOIN instagram_accounts a ON p.account_id = a.id
                  WHERE j.status = 'pending'
                  AND j.scheduled_at <= '$now'
                  AND j.attempts < j.max_attempts
                  ORDER BY j.scheduled_at ASC
                  LIMIT 10";

        $result = $this->conn->query($query);
        $jobs = [];

        while ($row = $result->fetch_assoc()) {
            $jobs[] = $row;
        }

        return $jobs;
    }

    private function processJob($job) {
        $job_id = $job['id'];
        $post_id = $job['post_id'];

        $this->log("Processing job #$job_id for post #$post_id (type: {$job['job_type']})");

        // Update job status to processing
        $this->updateJobStatus($job_id, 'processing');

        try {
            switch ($job['job_type']) {
                case 'publish':
                    $result = $this->publishPost($job);
                    break;
                case 'first_comment':
                    $result = $this->addFirstComment($job);
                    break;
                case 'analytics_fetch':
                    $result = $this->fetchAnalytics($job);
                    break;
                default:
                    throw new Exception("Unknown job type: {$job['job_type']}");
            }

            if ($result['success']) {
                $this->updateJobStatus($job_id, 'completed');
                $this->log("Job #$job_id completed successfully");
            } else {
                throw new Exception($result['error'] ?? 'Unknown error');
            }

        } catch (Exception $e) {
            $this->handleJobError($job, $e->getMessage());
        }
    }

    private function publishPost($job) {
        $api = new InstagramAPI($job['access_token'], $job['business_account_id']);

        // Get post media
        $media = $this->getPostMedia($job['post_id']);

        if (empty($media)) {
            return ['success' => false, 'error' => 'No media found for post'];
        }

        $result = null;

        switch ($job['post_type']) {
            case 'feed':
                $result = $api->publishFeedPost(
                    $media[0]['media_url'],
                    $job['caption'],
                    null, // First comment handled separately
                    $job['location_id']
                );
                break;

            case 'carousel':
                $media_urls = array_map(function($m) {
                    return [
                        'url' => $m['media_url'],
                        'type' => $m['media_type']
                    ];
                }, $media);

                $result = $api->publishCarouselPost(
                    $media_urls,
                    $job['caption'],
                    null,
                    $job['location_id']
                );
                break;

            case 'story':
                $is_video = $media[0]['media_type'] === 'video';
                $result = $api->publishStory($media[0]['media_url'], $is_video);
                break;

            case 'reel':
                $reel_data = $this->getReelData($job['post_id']);
                $result = $api->publishReel(
                    $media[0]['media_url'],
                    $job['caption'],
                    $reel_data['cover_url'] ?? null
                );
                break;
        }

        if (isset($result['id'])) {
            // Update post with Instagram media ID
            $this->updatePostPublished($job['post_id'], $result['id']);

            // Schedule first comment if exists
            if (!empty($job['first_comment'])) {
                $this->scheduleFirstComment($job['post_id'], $result['id'], $job['first_comment']);
            }

            // Schedule analytics fetch for later
            $this->scheduleAnalyticsFetch($job['post_id']);

            return ['success' => true, 'media_id' => $result['id']];
        }

        return ['success' => false, 'error' => $result['error'] ?? 'Failed to publish'];
    }

    private function addFirstComment($job) {
        $api = new InstagramAPI($job['access_token'], $job['business_account_id']);

        // Get the Instagram media ID
        $post = $this->getPost($job['post_id']);

        if (empty($post['instagram_media_id'])) {
            return ['success' => false, 'error' => 'No Instagram media ID found'];
        }

        $result = $api->addComment($post['instagram_media_id'], $job['first_comment']);

        if (isset($result['id'])) {
            return ['success' => true, 'comment_id' => $result['id']];
        }

        return ['success' => false, 'error' => $result['error'] ?? 'Failed to add comment'];
    }

    private function fetchAnalytics($job) {
        $api = new InstagramAPI($job['access_token'], $job['business_account_id']);

        $post = $this->getPost($job['post_id']);

        if (empty($post['instagram_media_id'])) {
            return ['success' => false, 'error' => 'No Instagram media ID found'];
        }

        // Get media insights
        $insights = $api->getMediaInsights($post['instagram_media_id']);

        if (isset($insights['data'])) {
            $metrics = [];
            foreach ($insights['data'] as $metric) {
                $metrics[$metric['name']] = $metric['values'][0]['value'] ?? 0;
            }

            // Update post analytics
            $this->updatePostAnalytics($job['post_id'], $metrics);

            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Failed to fetch analytics'];
    }

    private function getPostMedia($post_id) {
        $query = "SELECT * FROM ig_post_media WHERE post_id = $post_id ORDER BY order_index ASC";
        $result = $this->conn->query($query);
        $media = [];
        while ($row = $result->fetch_assoc()) {
            $media[] = $row;
        }
        return $media;
    }

    private function getReelData($post_id) {
        $query = "SELECT * FROM ig_reels_data WHERE post_id = $post_id";
        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }

    private function getPost($post_id) {
        $query = "SELECT * FROM ig_posts WHERE id = $post_id";
        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }

    private function updatePostPublished($post_id, $instagram_media_id) {
        $instagram_media_id = $this->conn->real_escape_string($instagram_media_id);
        $now = date('Y-m-d H:i:s');

        $query = "UPDATE ig_posts SET
                  status = 'published',
                  published_at = '$now',
                  instagram_media_id = '$instagram_media_id'
                  WHERE id = $post_id";

        $this->conn->query($query);
    }

    private function updatePostAnalytics($post_id, $metrics) {
        $reach = (int)($metrics['reach'] ?? 0);
        $impressions = (int)($metrics['impressions'] ?? 0);
        $engagement = (int)($metrics['engagement'] ?? 0);
        $saved = (int)($metrics['saved'] ?? 0);

        $query = "UPDATE ig_posts SET
                  reach = $reach,
                  impressions = $impressions,
                  saves = $saved
                  WHERE id = $post_id";

        $this->conn->query($query);
    }

    private function scheduleFirstComment($post_id, $instagram_media_id, $comment) {
        // Schedule for 30 seconds after publish
        $scheduled_at = date('Y-m-d H:i:s', strtotime('+30 seconds'));

        $comment = $this->conn->real_escape_string($comment);

        $query = "INSERT INTO ig_scheduled_jobs (post_id, job_type, scheduled_at, status)
                  VALUES ($post_id, 'first_comment', '$scheduled_at', 'pending')";

        $this->conn->query($query);
    }

    private function scheduleAnalyticsFetch($post_id) {
        // Schedule analytics fetch for 1 hour, 24 hours, and 7 days after publish
        $times = [
            date('Y-m-d H:i:s', strtotime('+1 hour')),
            date('Y-m-d H:i:s', strtotime('+24 hours')),
            date('Y-m-d H:i:s', strtotime('+7 days'))
        ];

        foreach ($times as $scheduled_at) {
            $query = "INSERT INTO ig_scheduled_jobs (post_id, job_type, scheduled_at, status)
                      VALUES ($post_id, 'analytics_fetch', '$scheduled_at', 'pending')";
            $this->conn->query($query);
        }
    }

    private function updateJobStatus($job_id, $status) {
        $status = $this->conn->real_escape_string($status);
        $now = date('Y-m-d H:i:s');

        $query = "UPDATE ig_scheduled_jobs SET
                  status = '$status',
                  last_attempt_at = '$now',
                  attempts = attempts + 1
                  WHERE id = $job_id";

        $this->conn->query($query);
    }

    private function handleJobError($job, $error_message) {
        $job_id = $job['id'];
        $attempts = $job['attempts'] + 1;
        $max_attempts = $job['max_attempts'];

        $error_message = $this->conn->real_escape_string($error_message);
        $now = date('Y-m-d H:i:s');

        if ($attempts >= $max_attempts) {
            // Max retries reached, mark as failed
            $query = "UPDATE ig_scheduled_jobs SET
                      status = 'failed',
                      error_message = '$error_message',
                      last_attempt_at = '$now',
                      attempts = $attempts
                      WHERE id = $job_id";

            // Also update post status
            $this->conn->query("UPDATE ig_posts SET status = 'failed' WHERE id = {$job['post_id']}");

            $this->log("Job #$job_id FAILED after $attempts attempts: $error_message");
        } else {
            // Schedule retry
            $retry_delay = pow(2, $attempts) * 60; // Exponential backoff: 2min, 4min, 8min
            $retry_at = date('Y-m-d H:i:s', strtotime("+$retry_delay seconds"));

            $query = "UPDATE ig_scheduled_jobs SET
                      status = 'pending',
                      scheduled_at = '$retry_at',
                      error_message = '$error_message',
                      last_attempt_at = '$now',
                      attempts = $attempts
                      WHERE id = $job_id";

            $this->log("Job #$job_id failed, retry scheduled at $retry_at (attempt $attempts/$max_attempts): $error_message");
        }

        $this->conn->query($query);
    }

    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] $message\n";

        file_put_contents($this->log_file, $log_message, FILE_APPEND);

        // Also output to console if running manually
        echo $log_message;
    }
}

// Run the scheduler
$scheduler = new InstagramScheduler();
$scheduler->run();
?>
