    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-graduation-cap"></i> English Learning</h3>
                    <p>İngilizce öğrenmenin en etkili ve eğlenceli yolu!</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <div class="footer-section">
                    <h4>Hızlı Linkler</h4>
                    <ul>
                        <li><a href="index.php">Ana Sayfa</a></li>
                        <li><a href="lessons.php">Dersler</a></li>
                        <li><a href="about.php">Hakkımızda</a></li>
                        <li><a href="contact.php">İletişim</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>Seviyeler</h4>
                    <ul>
                        <li><a href="lessons.php?level=A1">A1 - Beginner</a></li>
                        <li><a href="lessons.php?level=A2">A2 - Elementary</a></li>
                        <li><a href="lessons.php?level=B1">B1 - Intermediate</a></li>
                        <li><a href="lessons.php?level=B2">B2 - Upper Intermediate</a></li>
                        <li><a href="lessons.php?level=C1">C1 - Advanced</a></li>
                        <li><a href="lessons.php?level=C2">C2 - Proficiency</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>Kategoriler</h4>
                    <ul>
                        <li><a href="lessons.php?category=Grammar"><i class="fas fa-book"></i> Grammar</a></li>
                        <li><a href="lessons.php?category=Vocabulary"><i class="fas fa-font"></i> Vocabulary</a></li>
                        <li><a href="lessons.php?category=Reading"><i class="fas fa-book-open"></i> Reading</a></li>
                        <li><a href="lessons.php?category=Writing"><i class="fas fa-pen"></i> Writing</a></li>
                        <li><a href="lessons.php?category=Listening"><i class="fas fa-headphones"></i> Listening</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> English Learning Platform. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <?php if (isset($extra_js)): ?>
        <?php foreach ($extra_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
