<?php
require_once 'config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$lesson_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get lesson details
$lesson = $conn->query("
    SELECT l.*, lv.level_code, lv.level_name, c.name as category_name, c.icon as category_icon, c.color as category_color
    FROM lessons l
    JOIN levels lv ON lv.id = l.level_id
    JOIN categories c ON c.id = l.category_id
    WHERE l.id = $lesson_id
")->fetch_assoc();

if (!$lesson) {
    header('Location: lessons.php');
    exit();
}

// Get or create user progress
$progress = $conn->query("SELECT * FROM user_progress WHERE user_id = $user_id AND lesson_id = $lesson_id")->fetch_assoc();

if (!$progress) {
    // Create progress record
    $conn->query("INSERT INTO user_progress (user_id, lesson_id, status) VALUES ($user_id, $lesson_id, 'in_progress')");
    $progress = $conn->query("SELECT * FROM user_progress WHERE user_id = $user_id AND lesson_id = $lesson_id")->fetch_assoc();
}

// Update to in_progress if not completed
if ($progress['status'] == 'not_started') {
    $conn->query("UPDATE user_progress SET status = 'in_progress' WHERE id = {$progress['id']}");
}

// Get quiz questions
$questions = $conn->query("SELECT * FROM quiz_questions WHERE lesson_id = $lesson_id ORDER BY id");

$page_title = $lesson['title'];
include 'includes/header.php';
?>

<section class="lesson-view">
    <div class="container">
        <div class="lesson-breadcrumb">
            <a href="dashboard.php">Ana Sayfa</a>
            <i class="fas fa-chevron-right"></i>
            <a href="lessons.php">Dersler</a>
            <i class="fas fa-chevron-right"></i>
            <span><?php echo htmlspecialchars($lesson['title']); ?></span>
        </div>

        <div class="lesson-header-box">
            <div class="lesson-icon-large" style="background: <?php echo $lesson['category_color']; ?>;">
                <i class="fas <?php echo $lesson['category_icon']; ?>"></i>
            </div>
            <div class="lesson-header-info">
                <div class="lesson-tags">
                    <span class="tag tag-level"><?php echo $lesson['level_code']; ?></span>
                    <span class="tag tag-category" style="background: <?php echo $lesson['category_color']; ?>;">
                        <?php echo htmlspecialchars($lesson['category_name']); ?>
                    </span>
                </div>
                <h1><?php echo htmlspecialchars($lesson['title']); ?></h1>
                <p class="lesson-description"><?php echo htmlspecialchars($lesson['description']); ?></p>
                <div class="lesson-meta-info">
                    <span><i class="far fa-clock"></i> <?php echo $lesson['duration_minutes']; ?> dakika</span>
                    <span><i class="fas fa-star"></i> <?php echo $lesson['points']; ?> puan</span>
                    <span><i class="fas fa-question-circle"></i> <?php echo $questions->num_rows; ?> soru</span>
                </div>
            </div>
        </div>

        <div class="lesson-content-container">
            <div class="lesson-content">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-book-open"></i> Ders İçeriği</h2>
                    </div>
                    <div class="card-body lesson-text">
                        <?php echo $lesson['content']; ?>
                    </div>
                </div>

                <?php if ($questions->num_rows > 0): ?>
                <div class="card" id="quiz-section">
                    <div class="card-header">
                        <h2><i class="fas fa-question-circle"></i> Alıştırmalar</h2>
                        <span class="question-counter">
                            <span id="current-question">1</span> / <?php echo $questions->num_rows; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <form id="quiz-form">
                            <input type="hidden" name="lesson_id" value="<?php echo $lesson_id; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                            <?php
                            $q_index = 1;
                            $questions->data_seek(0);
                            while ($question = $questions->fetch_assoc()):
                                $options = $conn->query("SELECT * FROM quiz_options WHERE question_id = {$question['id']} ORDER BY option_order");
                            ?>
                            <div class="question-container" data-question="<?php echo $q_index; ?>" style="display: <?php echo $q_index == 1 ? 'block' : 'none'; ?>;">
                                <div class="question-header">
                                    <h3>Soru <?php echo $q_index; ?></h3>
                                </div>
                                <div class="question-text">
                                    <?php echo htmlspecialchars($question['question_text']); ?>
                                </div>

                                <?php if ($question['question_type'] == 'multiple_choice'): ?>
                                <div class="options-list">
                                    <?php while ($option = $options->fetch_assoc()): ?>
                                    <label class="option-item">
                                        <input type="radio" name="question_<?php echo $question['id']; ?>"
                                               value="<?php echo htmlspecialchars($option['option_text']); ?>"
                                               data-correct="<?php echo $option['is_correct']; ?>"
                                               data-question-id="<?php echo $question['id']; ?>" required>
                                        <span class="option-text"><?php echo htmlspecialchars($option['option_text']); ?></span>
                                        <span class="option-check"><i class="fas fa-check"></i></span>
                                    </label>
                                    <?php endwhile; ?>
                                </div>
                                <?php endif; ?>

                                <?php if ($question['explanation']): ?>
                                <div class="explanation" style="display: none;">
                                    <strong><i class="fas fa-lightbulb"></i> Açıklama:</strong>
                                    <p><?php echo htmlspecialchars($question['explanation']); ?></p>
                                </div>
                                <?php endif; ?>

                                <div class="question-navigation">
                                    <?php if ($q_index > 1): ?>
                                    <button type="button" class="btn btn-outline prev-question">
                                        <i class="fas fa-chevron-left"></i> Önceki
                                    </button>
                                    <?php endif; ?>

                                    <?php if ($q_index < $questions->num_rows): ?>
                                    <button type="button" class="btn btn-primary next-question">
                                        Sonraki <i class="fas fa-chevron-right"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="submit" class="btn btn-success submit-quiz">
                                        <i class="fas fa-check"></i> Tamamla
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php
                            $q_index++;
                            endwhile;
                            ?>
                        </form>

                        <div id="quiz-result" style="display: none;">
                            <div class="result-box">
                                <div class="result-icon">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <h2>Tebrikler!</h2>
                                <p>Quiz'i tamamladınız</p>
                                <div class="result-stats">
                                    <div class="result-stat">
                                        <strong id="correct-count">0</strong>
                                        <span>Doğru</span>
                                    </div>
                                    <div class="result-stat">
                                        <strong id="score-percent">0</strong>
                                        <span>Puan</span>
                                    </div>
                                    <div class="result-stat">
                                        <strong id="points-earned">0</strong>
                                        <span>Kazanılan Puan</span>
                                    </div>
                                </div>
                                <div class="result-actions">
                                    <a href="lessons.php" class="btn btn-primary">
                                        <i class="fas fa-book"></i> Derslere Dön
                                    </a>
                                    <a href="lesson-view.php?id=<?php echo $lesson_id; ?>" class="btn btn-outline">
                                        <i class="fas fa-redo"></i> Tekrar Yap
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="lesson-sidebar">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> Ders Bilgileri</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-list">
                            <div class="info-item">
                                <i class="fas fa-layer-group"></i>
                                <div>
                                    <strong>Seviye</strong>
                                    <p><?php echo $lesson['level_name']; ?></p>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-th-large"></i>
                                <div>
                                    <strong>Kategori</strong>
                                    <p><?php echo htmlspecialchars($lesson['category_name']); ?></p>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="far fa-clock"></i>
                                <div>
                                    <strong>Süre</strong>
                                    <p><?php echo $lesson['duration_minutes']; ?> dakika</p>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-star"></i>
                                <div>
                                    <strong>Puan</strong>
                                    <p><?php echo $lesson['points']; ?> puan</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-lightbulb"></i> İpuçları</h3>
                    </div>
                    <div class="card-body">
                        <ul class="tips-list">
                            <li>Ders içeriğini dikkatlice okuyun</li>
                            <li>Anlamadığınız kelimeleri not edin</li>
                            <li>Quiz'e geçmeden önce içeriği gözden geçirin</li>
                            <li>Yanlış cevaplarınızı öğrenin</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('quiz-form');
    const questions = document.querySelectorAll('.question-container');
    const currentQuestionSpan = document.getElementById('current-question');
    let currentQuestion = 1;

    // Navigation
    document.querySelectorAll('.next-question').forEach(btn => {
        btn.addEventListener('click', function() {
            const current = document.querySelector(`.question-container[data-question="${currentQuestion}"]`);
            const input = current.querySelector('input[type="radio"]:checked');

            if (!input) {
                alert('Lütfen bir seçenek seçin!');
                return;
            }

            current.style.display = 'none';
            currentQuestion++;
            document.querySelector(`.question-container[data-question="${currentQuestion}"]`).style.display = 'block';
            currentQuestionSpan.textContent = currentQuestion;
            window.scrollTo(0, 0);
        });
    });

    document.querySelectorAll('.prev-question').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelector(`.question-container[data-question="${currentQuestion}"]`).style.display = 'none';
            currentQuestion--;
            document.querySelector(`.question-container[data-question="${currentQuestion}"]`).style.display = 'block';
            currentQuestionSpan.textContent = currentQuestion;
            window.scrollTo(0, 0);
        });
    });

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        const answers = {};
        let correctCount = 0;
        let totalQuestions = questions.length;

        // Check answers
        questions.forEach((question, index) => {
            const inputs = question.querySelectorAll('input[type="radio"]');
            const checked = question.querySelector('input[type="radio"]:checked');

            if (checked) {
                const questionId = checked.dataset.questionId;
                const isCorrect = checked.dataset.correct == '1';

                if (isCorrect) {
                    correctCount++;
                    checked.closest('.option-item').classList.add('correct');
                } else {
                    checked.closest('.option-item').classList.add('wrong');
                    // Show correct answer
                    inputs.forEach(input => {
                        if (input.dataset.correct == '1') {
                            input.closest('.option-item').classList.add('correct');
                        }
                    });
                }

                // Show explanation
                const explanation = question.querySelector('.explanation');
                if (explanation) {
                    explanation.style.display = 'block';
                }

                // Disable inputs
                inputs.forEach(input => input.disabled = true);
            }
        });

        // Calculate score
        const scorePercent = Math.round((correctCount / totalQuestions) * 100);
        const pointsEarned = Math.round((scorePercent / 100) * <?php echo $lesson['points']; ?>);

        // Save to database
        fetch('api/save-quiz.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: formData.get('user_id'),
                lesson_id: formData.get('lesson_id'),
                score: scorePercent,
                points: pointsEarned
            })
        });

        // Show results
        document.getElementById('correct-count').textContent = correctCount + '/' + totalQuestions;
        document.getElementById('score-percent').textContent = scorePercent + '%';
        document.getElementById('points-earned').textContent = pointsEarned;

        setTimeout(() => {
            document.getElementById('quiz-result').style.display = 'block';
            document.getElementById('quiz-result').scrollIntoView({ behavior: 'smooth' });
        }, 1000);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
