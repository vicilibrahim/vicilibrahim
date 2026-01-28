            </div><!-- .ig-content -->
        </main><!-- .ig-main -->
    </div><!-- .ig-app -->

    <script src="assets/js/instagram-planner.js"></script>
    <?php if (isset($extra_js)): foreach($extra_js as $js): ?>
    <script src="<?php echo $js; ?>"></script>
    <?php endforeach; endif; ?>
</body>
</html>
