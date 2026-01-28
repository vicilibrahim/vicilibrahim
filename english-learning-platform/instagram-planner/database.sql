-- Instagram Planner Database Schema for Terra+
-- Created: 2026-01-28

-- Instagram Accounts table (connected business accounts)
CREATE TABLE IF NOT EXISTS instagram_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    instagram_user_id VARCHAR(100) NOT NULL,
    username VARCHAR(100) NOT NULL,
    access_token TEXT NOT NULL,
    token_expires_at TIMESTAMP NULL,
    profile_picture VARCHAR(500),
    business_account_id VARCHAR(100),
    followers_count INT DEFAULT 0,
    following_count INT DEFAULT 0,
    media_count INT DEFAULT 0,
    niche VARCHAR(100),
    brand_name VARCHAR(200),
    brand_voice TEXT,
    target_audience TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_instagram_user (instagram_user_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Content Categories (Egitici, Urun, BTS, UGC)
CREATE TABLE IF NOT EXISTS ig_content_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    name_tr VARCHAR(100) NOT NULL,
    description TEXT,
    percentage INT DEFAULT 25,
    color VARCHAR(7) DEFAULT '#007bff',
    icon VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Posts table (Feed, Carousel, Story, Reel)
CREATE TABLE IF NOT EXISTS ig_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    post_type ENUM('feed', 'carousel', 'story', 'reel') NOT NULL DEFAULT 'feed',
    content_category_id INT,
    caption TEXT,
    first_comment TEXT,
    location_id VARCHAR(100),
    location_name VARCHAR(200),
    status ENUM('draft', 'scheduled', 'published', 'failed') DEFAULT 'draft',
    scheduled_at TIMESTAMP NULL,
    published_at TIMESTAMP NULL,
    instagram_media_id VARCHAR(100),
    reach INT DEFAULT 0,
    impressions INT DEFAULT 0,
    likes INT DEFAULT 0,
    comments INT DEFAULT 0,
    saves INT DEFAULT 0,
    shares INT DEFAULT 0,
    engagement_rate DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES instagram_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (content_category_id) REFERENCES ig_content_categories(id) ON DELETE SET NULL,
    INDEX idx_account_status (account_id, status),
    INDEX idx_scheduled (scheduled_at, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Post Media (images/videos for posts)
CREATE TABLE IF NOT EXISTS ig_post_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    media_type ENUM('image', 'video') NOT NULL DEFAULT 'image',
    media_url TEXT NOT NULL,
    thumbnail_url TEXT,
    alt_text VARCHAR(500),
    order_index INT DEFAULT 0,
    duration_seconds INT,
    width INT,
    height INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES ig_posts(id) ON DELETE CASCADE,
    INDEX idx_post (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hashtags library
CREATE TABLE IF NOT EXISTS ig_hashtags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hashtag VARCHAR(100) NOT NULL,
    category ENUM('high', 'medium', 'niche', 'branded') DEFAULT 'medium',
    media_count BIGINT DEFAULT 0,
    niche VARCHAR(100),
    is_banned TINYINT(1) DEFAULT 0,
    last_updated TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_hashtag (hashtag),
    INDEX idx_category (category),
    INDEX idx_niche (niche)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Post Hashtags (many-to-many relationship)
CREATE TABLE IF NOT EXISTS ig_post_hashtags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    hashtag_id INT NOT NULL,
    position ENUM('caption', 'first_comment') DEFAULT 'caption',
    FOREIGN KEY (post_id) REFERENCES ig_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (hashtag_id) REFERENCES ig_hashtags(id) ON DELETE CASCADE,
    UNIQUE KEY unique_post_hashtag (post_id, hashtag_id),
    INDEX idx_post (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reel specific data
CREATE TABLE IF NOT EXISTS ig_reels_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    audio_name VARCHAR(200),
    audio_id VARCHAR(100),
    is_trending_audio TINYINT(1) DEFAULT 0,
    hook_text TEXT,
    hook_duration_seconds INT DEFAULT 3,
    format_type VARCHAR(100),
    plays INT DEFAULT 0,
    FOREIGN KEY (post_id) REFERENCES ig_posts(id) ON DELETE CASCADE,
    INDEX idx_post (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Story specific data
CREATE TABLE IF NOT EXISTS ig_stories_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    sticker_type ENUM('poll', 'quiz', 'countdown', 'question', 'slider', 'link', 'mention', 'hashtag', 'location', 'music') NULL,
    sticker_data JSON,
    add_to_highlight TINYINT(1) DEFAULT 0,
    highlight_name VARCHAR(100),
    tap_forward INT DEFAULT 0,
    tap_back INT DEFAULT 0,
    replies INT DEFAULT 0,
    exits INT DEFAULT 0,
    FOREIGN KEY (post_id) REFERENCES ig_posts(id) ON DELETE CASCADE,
    INDEX idx_post (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Highlights
CREATE TABLE IF NOT EXISTS ig_highlights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    cover_image VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES instagram_accounts(id) ON DELETE CASCADE,
    INDEX idx_account (account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Best posting times per account
CREATE TABLE IF NOT EXISTS ig_best_times (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    day_of_week TINYINT NOT NULL,
    hour INT NOT NULL,
    avg_engagement DECIMAL(5,2) DEFAULT 0.00,
    post_count INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES instagram_accounts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_account_day_hour (account_id, day_of_week, hour),
    INDEX idx_account (account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Content Calendar Templates
CREATE TABLE IF NOT EXISTS ig_calendar_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    niche VARCHAR(100),
    posts_per_week INT DEFAULT 5,
    template_data JSON,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Generated Content Queue
CREATE TABLE IF NOT EXISTS ig_ai_content_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    content_type ENUM('caption', 'hashtags', 'hook', 'calendar', 'strategy') NOT NULL,
    input_data JSON,
    output_data JSON,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    tokens_used INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (account_id) REFERENCES instagram_accounts(id) ON DELETE CASCADE,
    INDEX idx_account_status (account_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Account Analytics Snapshots (daily)
CREATE TABLE IF NOT EXISTS ig_analytics_daily (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    date DATE NOT NULL,
    followers_count INT DEFAULT 0,
    following_count INT DEFAULT 0,
    posts_count INT DEFAULT 0,
    total_reach INT DEFAULT 0,
    total_impressions INT DEFAULT 0,
    total_engagement INT DEFAULT 0,
    profile_views INT DEFAULT 0,
    website_clicks INT DEFAULT 0,
    new_followers INT DEFAULT 0,
    lost_followers INT DEFAULT 0,
    FOREIGN KEY (account_id) REFERENCES instagram_accounts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_account_date (account_id, date),
    INDEX idx_account_date (account_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trending Audio/Music
CREATE TABLE IF NOT EXISTS ig_trending_audio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audio_id VARCHAR(100) NOT NULL,
    audio_name VARCHAR(300) NOT NULL,
    artist VARCHAR(200),
    usage_count BIGINT DEFAULT 0,
    trend_score INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_audio (audio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Viral Formats/Templates for Reels
CREATE TABLE IF NOT EXISTS ig_viral_formats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    hook_template TEXT,
    structure JSON,
    niche VARCHAR(100),
    avg_engagement DECIMAL(5,2) DEFAULT 0.00,
    example_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scheduled Jobs
CREATE TABLE IF NOT EXISTS ig_scheduled_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    job_type ENUM('publish', 'first_comment', 'analytics_fetch') NOT NULL,
    scheduled_at TIMESTAMP NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    last_attempt_at TIMESTAMP NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES ig_posts(id) ON DELETE CASCADE,
    INDEX idx_scheduled (scheduled_at, status),
    INDEX idx_post (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default content categories (AI Content Strategy)
INSERT INTO ig_content_categories (name, name_tr, description, percentage, color, icon) VALUES
('Educational', 'Egitici', 'Tips, tricks, how-to content', 40, '#28a745', 'fa-graduation-cap'),
('Product Showcase', 'Urun', 'Product highlights and features', 30, '#007bff', 'fa-shopping-bag'),
('Behind the Scenes', 'Arka Plan', 'Team, process, daily operations', 20, '#ffc107', 'fa-camera'),
('User Generated', 'Kullanici Icerigi', 'Customer reviews, testimonials, reposts', 10, '#dc3545', 'fa-users');

-- Insert sample viral formats
INSERT INTO ig_viral_formats (name, description, hook_template, structure, niche) VALUES
('Before/After', 'Show transformation or improvement', 'Watch this transformation...', '{"slides": 2, "type": "comparison"}', 'general'),
('Tutorial Steps', 'Step-by-step how-to content', 'Here''s how to {action} in 3 steps', '{"slides": "3-5", "type": "educational"}', 'general'),
('POV Format', 'Point of view storytelling', 'POV: You just discovered {benefit}', '{"slides": 1, "type": "relatable"}', 'lifestyle'),
('Myth vs Fact', 'Debunk common misconceptions', 'This myth is costing you {pain point}', '{"slides": 2, "type": "educational"}', 'general'),
('Day in Life', 'Daily routine showcase', 'A day in the life of a {role}', '{"slides": "5-10", "type": "bts"}', 'lifestyle'),
('Unpopular Opinion', 'Controversial takes that spark engagement', 'Unpopular opinion: {statement}', '{"slides": 1, "type": "engagement"}', 'general'),
('This vs That', 'Comparison content', '{Option A} vs {Option B} - which one wins?', '{"slides": 2, "type": "comparison"}', 'general'),
('Storytime', 'Personal story narration', 'Story time: How I {achievement}', '{"slides": "3-7", "type": "story"}', 'personal');

-- Insert sample hashtag data
INSERT INTO ig_hashtags (hashtag, category, media_count, niche) VALUES
('#business', 'high', 85000000, 'business'),
('#entrepreneur', 'high', 65000000, 'business'),
('#startup', 'high', 45000000, 'business'),
('#smallbusiness', 'high', 35000000, 'business'),
('#marketing', 'high', 55000000, 'marketing'),
('#digitalmarketing', 'medium', 25000000, 'marketing'),
('#contentcreator', 'medium', 18000000, 'content'),
('#socialmediamarketing', 'medium', 15000000, 'marketing'),
('#growthhacking', 'niche', 500000, 'marketing'),
('#entrepreneurlife', 'medium', 8000000, 'business'),
('#businesstips', 'niche', 2000000, 'business'),
('#marketingtips', 'niche', 3000000, 'marketing'),
('#instagramgrowth', 'niche', 1500000, 'social'),
('#contentmarketing', 'medium', 12000000, 'marketing'),
('#branding', 'medium', 20000000, 'branding');

-- Insert default calendar template
INSERT INTO ig_calendar_templates (name, description, niche, posts_per_week, template_data, is_default) VALUES
('Standard Business', 'Balanced content mix for business accounts', 'business', 5,
'{
    "monday": {"type": "feed", "category": "Educational", "time": "10:00"},
    "tuesday": {"type": "carousel", "category": "Product Showcase", "time": "14:00"},
    "wednesday": {"type": "reel", "category": "Educational", "time": "18:00"},
    "thursday": {"type": "feed", "category": "Behind the Scenes", "time": "10:00"},
    "friday": {"type": "feed", "category": "User Generated", "time": "14:00"},
    "saturday": {"type": "story", "category": "Behind the Scenes", "time": "10:00"},
    "sunday": null
}', 1);
