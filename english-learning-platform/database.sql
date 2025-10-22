-- English Learning Platform Database
-- Created: 2025-10-22

CREATE DATABASE IF NOT EXISTS english_learning_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE english_learning_platform;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    current_level ENUM('A1', 'A2', 'B1', 'B2', 'C1', 'C2') DEFAULT 'A1',
    profile_image VARCHAR(255) DEFAULT 'default.jpg',
    daily_goal INT DEFAULT 30,
    streak_days INT DEFAULT 0,
    total_points INT DEFAULT 0,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Levels table
CREATE TABLE IF NOT EXISTS levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_code VARCHAR(2) NOT NULL UNIQUE,
    level_name VARCHAR(50) NOT NULL,
    description TEXT,
    order_index INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table (Grammar, Reading, Writing, Listening, Vocabulary)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    icon VARCHAR(50),
    description TEXT,
    color VARCHAR(7) DEFAULT '#007bff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lessons table
CREATE TABLE IF NOT EXISTS lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    level_id INT NOT NULL,
    category_id INT NOT NULL,
    description TEXT,
    content LONGTEXT NOT NULL,
    order_index INT NOT NULL,
    duration_minutes INT DEFAULT 15,
    points INT DEFAULT 10,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (level_id) REFERENCES levels(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_level_category (level_id, category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quiz questions table
CREATE TABLE IF NOT EXISTS quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false', 'fill_blank', 'listening', 'reading') NOT NULL,
    audio_file VARCHAR(255) NULL,
    image_file VARCHAR(255) NULL,
    correct_answer TEXT NOT NULL,
    points INT DEFAULT 5,
    explanation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX idx_lesson (lesson_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quiz options table (for multiple choice questions)
CREATE TABLE IF NOT EXISTS quiz_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    option_text TEXT NOT NULL,
    is_correct TINYINT(1) DEFAULT 0,
    option_order INT NOT NULL,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
    INDEX idx_question (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User progress table
CREATE TABLE IF NOT EXISTS user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    score INT DEFAULT 0,
    attempts INT DEFAULT 0,
    time_spent INT DEFAULT 0,
    completed_at TIMESTAMP NULL,
    last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_lesson (user_id, lesson_id),
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User quiz attempts table
CREATE TABLE IF NOT EXISTS user_quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    question_id INT NOT NULL,
    user_answer TEXT,
    is_correct TINYINT(1),
    points_earned INT DEFAULT 0,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
    INDEX idx_user_question (user_id, question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vocabulary/Word bank table
CREATE TABLE IF NOT EXISTS vocabulary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    word VARCHAR(100) NOT NULL,
    pronunciation VARCHAR(100),
    level_id INT NOT NULL,
    category_id INT,
    definition TEXT NOT NULL,
    example_sentence TEXT,
    translation_tr VARCHAR(200),
    audio_file VARCHAR(255),
    image_file VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (level_id) REFERENCES levels(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_word (word),
    INDEX idx_level (level_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User vocabulary (saved words)
CREATE TABLE IF NOT EXISTS user_vocabulary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vocabulary_id INT NOT NULL,
    mastery_level ENUM('learning', 'practicing', 'mastered') DEFAULT 'learning',
    review_count INT DEFAULT 0,
    last_reviewed TIMESTAMP NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vocabulary_id) REFERENCES vocabulary(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_vocab (user_id, vocabulary_id),
    INDEX idx_user_mastery (user_id, mastery_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Achievements/Badges table
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    requirement_type ENUM('lessons_completed', 'quiz_score', 'streak_days', 'total_points', 'level_completed') NOT NULL,
    requirement_value INT NOT NULL,
    badge_color VARCHAR(7) DEFAULT '#FFD700'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User achievements table
CREATE TABLE IF NOT EXISTS user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily activity log
CREATE TABLE IF NOT EXISTS daily_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_date DATE NOT NULL,
    lessons_completed INT DEFAULT 0,
    quizzes_taken INT DEFAULT 0,
    points_earned INT DEFAULT 0,
    time_spent INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, activity_date),
    INDEX idx_user_date (user_id, activity_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default levels
INSERT INTO levels (level_code, level_name, description, order_index) VALUES
('A1', 'Beginner (A1)', 'Başlangıç seviyesi - Temel İngilizce', 1),
('A2', 'Elementary (A2)', 'Temel seviye - Günlük konuşmalar', 2),
('B1', 'Intermediate (B1)', 'Orta seviye - Genel konular', 3),
('B2', 'Upper Intermediate (B2)', 'Orta-ileri seviye - Karmaşık metinler', 4),
('C1', 'Advanced (C1)', 'İleri seviye - Akademik İngilizce', 5),
('C2', 'Proficiency (C2)', 'Uzman seviye - Ana dil seviyesi', 6);

-- Insert categories
INSERT INTO categories (name, icon, description, color) VALUES
('Grammar', 'fa-book', 'Dilbilgisi kuralları ve yapılar', '#28a745'),
('Vocabulary', 'fa-font', 'Kelime öğrenimi ve kullanımı', '#007bff'),
('Reading', 'fa-book-open', 'Okuma parçaları ve anlama', '#17a2b8'),
('Writing', 'fa-pen', 'Yazma becerileri ve pratikler', '#ffc107'),
('Listening', 'fa-headphones', 'Dinleme alıştırmaları', '#dc3545');

-- Insert sample achievements
INSERT INTO achievements (name, description, icon, requirement_type, requirement_value, badge_color) VALUES
('İlk Adım', 'İlk dersi tamamla', '🎯', 'lessons_completed', 1, '#90EE90'),
('Hızlı Başlangıç', '10 ders tamamla', '🚀', 'lessons_completed', 10, '#87CEEB'),
('Kararlı Öğrenci', '50 ders tamamla', '⭐', 'lessons_completed', 50, '#FFD700'),
('Ustalaşma', '100 ders tamamla', '👑', 'lessons_completed', 100, '#FF6347'),
('7 Gün Ardışık', '7 gün üst üste giriş yap', '🔥', 'streak_days', 7, '#FF4500'),
('30 Gün Ardışık', '30 gün üst üste giriş yap', '💪', 'streak_days', 30, '#DC143C'),
('Puan Avcısı', '1000 puan kazan', '💎', 'total_points', 1000, '#00CED1'),
('Puan Ustası', '5000 puan kazan', '💰', 'total_points', 5000, '#FFD700'),
('Quiz Şampiyonu', 'Quiz ortalaman %90 üstünde', '🏆', 'quiz_score', 90, '#FFD700');

-- Insert sample lessons for A1 level - Grammar
INSERT INTO lessons (title, level_id, category_id, description, content, order_index, duration_minutes, points) VALUES
('To Be Verb - Present Tense', 1, 1, 'Learn how to use am, is, are',
'<h2>To Be Verb (Present Tense)</h2>
<p>İngilizcede "olmak" anlamına gelen "to be" fiili, en temel fiillerden biridir.</p>

<h3>Forms:</h3>
<ul>
<li><strong>I am</strong> (I''m) - Ben ... (im/um/üm)</li>
<li><strong>You are</strong> (You''re) - Sen ..., Siz ...</li>
<li><strong>He/She/It is</strong> (He''s/She''s/It''s) - O ...</li>
<li><strong>We are</strong> (We''re) - Biz ...</li>
<li><strong>They are</strong> (They''re) - Onlar ...</li>
</ul>

<h3>Examples:</h3>
<ul>
<li>I am a student. (Ben bir öğrenciyim.)</li>
<li>She is happy. (O mutlu.)</li>
<li>They are teachers. (Onlar öğretmen.)</li>
<li>We are from Turkey. (Biz Türkiye''deniz.)</li>
</ul>

<h3>Negative Form:</h3>
<ul>
<li>I am not (I''m not)</li>
<li>You are not (You aren''t)</li>
<li>He is not (He isn''t)</li>
</ul>

<h3>Question Form:</h3>
<ul>
<li>Am I...?</li>
<li>Are you...?</li>
<li>Is he/she/it...?</li>
</ul>', 1, 20, 15);

-- Insert sample vocabulary
INSERT INTO vocabulary (word, pronunciation, level_id, category_id, definition, example_sentence, translation_tr) VALUES
('Hello', '/həˈləʊ/', 1, NULL, 'A greeting used when meeting someone', 'Hello! How are you?', 'Merhaba'),
('Goodbye', '/ɡʊdˈbaɪ/', 1, NULL, 'A farewell expression', 'Goodbye! See you later.', 'Hoşçakal, Güle güle'),
('Thank you', '/θæŋk juː/', 1, NULL, 'An expression of gratitude', 'Thank you for your help.', 'Teşekkür ederim'),
('Please', '/pliːz/', 1, NULL, 'Used to make a polite request', 'Please help me.', 'Lütfen'),
('Yes', '/jes/', 1, NULL, 'Affirmative response', 'Yes, I agree.', 'Evet'),
('No', '/nəʊ/', 1, NULL, 'Negative response', 'No, I don''t want it.', 'Hayır'),
('Book', '/bʊk/', 1, 2, 'A written work bound together', 'I am reading a book.', 'Kitap'),
('Student', '/ˈstjuːdənt/', 1, 2, 'A person who is learning', 'She is a good student.', 'Öğrenci'),
('Teacher', '/ˈtiːtʃər/', 1, 2, 'A person who teaches', 'My teacher is very kind.', 'Öğretmen'),
('School', '/skuːl/', 1, 2, 'An institution for education', 'I go to school every day.', 'Okul');

-- Insert quiz questions for the first lesson
INSERT INTO quiz_questions (lesson_id, question_text, question_type, correct_answer, points, explanation) VALUES
(1, 'I ___ a student.', 'multiple_choice', 'am', 5, '"I" öznesinden sonra "am" kullanılır.'),
(1, 'She ___ happy.', 'multiple_choice', 'is', 5, 'Tekil öznelerden (he, she, it) sonra "is" kullanılır.'),
(1, 'They ___ teachers.', 'multiple_choice', 'are', 5, 'Çoğul öznelerden (we, you, they) sonra "are" kullanılır.'),
(1, 'We ___ from Turkey.', 'multiple_choice', 'are', 5, '"We" öznesinden sonra "are" kullanılır.');

-- Insert options for quiz questions
INSERT INTO quiz_options (question_id, option_text, is_correct, option_order) VALUES
(1, 'am', 1, 1),
(1, 'is', 0, 2),
(1, 'are', 0, 3),
(2, 'am', 0, 1),
(2, 'is', 1, 2),
(2, 'are', 0, 3),
(3, 'am', 0, 1),
(3, 'is', 0, 2),
(3, 'are', 1, 3),
(4, 'am', 0, 1),
(4, 'is', 0, 2),
(4, 'are', 1, 3);

-- Create default admin user (username: admin, password: admin123)
-- Password is hashed using password_hash() in PHP
INSERT INTO users (username, email, password, full_name, current_level, is_admin) VALUES
('admin', 'admin@englishlearning.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'C2', 1);
