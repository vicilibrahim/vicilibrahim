<?php
require_once 'config/database.php';
require_login();

$user_id = $_SESSION['user_id'];

// Get user's vocabulary
$vocab_words = $conn->query("
    SELECT v.*, uv.mastery_level, uv.review_count, uv.added_at, l.level_code
    FROM user_vocabulary uv
    JOIN vocabulary v ON v.id = uv.vocabulary_id
    JOIN levels l ON l.id = v.level_id
    WHERE uv.user_id = $user_id
    ORDER BY uv.added_at DESC
");

// Get available words to add
$available_words = $conn->query("
    SELECT v.*, l.level_code
    FROM vocabulary v
    JOIN levels l ON l.id = v.level_id
    WHERE v.id NOT IN (SELECT vocabulary_id FROM user_vocabulary WHERE user_id = $user_id)
    ORDER BY RAND()
    LIMIT 20
");

$page_title = 'Kelime Defteri';
include 'includes/header.php';
?>

<section class="vocabulary-section">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-font"></i> Kelime Defterim</h1>
            <p>Öğrendiğiniz kelimeleri kaydedin ve pratik yapın</p>
        </div>

        <div class="vocab-tabs">
            <button class="tab-btn active" data-tab="my-words">
                <i class="fas fa-book"></i> Kelimelerim
            </button>
            <button class="tab-btn" data-tab="new-words">
                <i class="fas fa-plus"></i> Yeni Kelimeler
            </button>
        </div>

        <div class="tab-content active" id="my-words">
            <?php if ($vocab_words->num_rows > 0): ?>
            <div class="vocabulary-grid">
                <?php while ($word = $vocab_words->fetch_assoc()): ?>
                <div class="vocab-card">
                    <div class="vocab-header">
                        <div class="vocab-word">
                            <h3><?php echo htmlspecialchars($word['word']); ?></h3>
                            <?php if ($word['pronunciation']): ?>
                            <span class="pronunciation"><?php echo htmlspecialchars($word['pronunciation']); ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="level-badge"><?php echo $word['level_code']; ?></span>
                    </div>

                    <div class="vocab-body">
                        <div class="vocab-definition">
                            <strong>Anlamı:</strong>
                            <p><?php echo htmlspecialchars($word['definition']); ?></p>
                        </div>

                        <?php if ($word['translation_tr']): ?>
                        <div class="vocab-translation">
                            <strong>Türkçe:</strong>
                            <p><?php echo htmlspecialchars($word['translation_tr']); ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if ($word['example_sentence']): ?>
                        <div class="vocab-example">
                            <strong>Örnek:</strong>
                            <p><?php echo htmlspecialchars($word['example_sentence']); ?></p>
                        </div>
                        <?php endif; ?>

                        <div class="vocab-mastery">
                            <span class="mastery-badge mastery-<?php echo $word['mastery_level']; ?>">
                                <?php
                                $mastery_text = [
                                    'learning' => 'Öğreniliyor',
                                    'practicing' => 'Pratik Yapılıyor',
                                    'mastered' => 'Ustalaşıldı'
                                ];
                                echo $mastery_text[$word['mastery_level']];
                                ?>
                            </span>
                            <span class="review-count">
                                <i class="fas fa-redo"></i> <?php echo $word['review_count']; ?> kez tekrarlandı
                            </span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-book"></i>
                <h3>Henüz kelime eklemediniz</h3>
                <p>Yeni kelimeler sekmesinden kelime ekleyebilirsiniz</p>
            </div>
            <?php endif; ?>
        </div>

        <div class="tab-content" id="new-words">
            <div class="vocabulary-grid">
                <?php while ($word = $available_words->fetch_assoc()): ?>
                <div class="vocab-card">
                    <div class="vocab-header">
                        <div class="vocab-word">
                            <h3><?php echo htmlspecialchars($word['word']); ?></h3>
                            <?php if ($word['pronunciation']): ?>
                            <span class="pronunciation"><?php echo htmlspecialchars($word['pronunciation']); ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="level-badge"><?php echo $word['level_code']; ?></span>
                    </div>

                    <div class="vocab-body">
                        <div class="vocab-definition">
                            <strong>Anlamı:</strong>
                            <p><?php echo htmlspecialchars($word['definition']); ?></p>
                        </div>

                        <?php if ($word['translation_tr']): ?>
                        <div class="vocab-translation">
                            <strong>Türkçe:</strong>
                            <p><?php echo htmlspecialchars($word['translation_tr']); ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if ($word['example_sentence']): ?>
                        <div class="vocab-example">
                            <strong>Örnek:</strong>
                            <p><?php echo htmlspecialchars($word['example_sentence']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <button class="btn btn-primary btn-block add-word" data-word-id="<?php echo $word['id']; ?>">
                        <i class="fas fa-plus"></i> Defterime Ekle
                    </button>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = this.dataset.tab;

            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

            this.classList.add('active');
            document.getElementById(tab).classList.add('active');
        });
    });

    // Add word to vocabulary
    document.querySelectorAll('.add-word').forEach(btn => {
        btn.addEventListener('click', function() {
            const wordId = this.dataset.wordId;
            const button = this;

            fetch('api/add-vocabulary.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: <?php echo $user_id; ?>,
                    vocabulary_id: wordId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.innerHTML = '<i class="fas fa-check"></i> Eklendi';
                    button.disabled = true;
                    button.classList.remove('btn-primary');
                    button.classList.add('btn-success');

                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            });
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
