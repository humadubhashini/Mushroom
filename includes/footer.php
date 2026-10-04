</main>
<footer class="site-footer">
  <div class="container">
    <p>
      <a href="<?= BASE_URL ?>/mushrooms/index.php" style="color:inherit;"><?= te('nav.mushrooms') ?></a> &middot;
      <a href="<?= BASE_URL ?>/help.php" style="color:inherit;"><?= te('footer.help') ?></a>
    </p>
    <p>&copy; <?= date('Y') ?> <?= te('app.name') ?> &mdash; <?= te('footer.tagline') ?></p>
    <p style="opacity:.6; font-size:.75rem;">v<?= e(APP_VERSION) ?></p>
  </div>
</footer>
</body>
</html>
