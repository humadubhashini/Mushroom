</main>
<footer class="site-footer">
  <div class="container">
    <p>
      <a href="<?= BASE_URL ?>/mushrooms/index.php" style="color:inherit;"><?= te('nav.mushrooms') ?></a> &middot;
      <a href="<?= BASE_URL ?>/help.php" style="color:inherit;"><?= te('footer.help') ?></a> &middot;
      <a href="<?= BASE_URL ?>/contact.php" style="color:inherit;"><?= te('contact.title') ?></a>
    </p>
    <p class="footer-contact">
      <a href="<?= e(tel_link(CONTACT_PHONE)) ?>">📞 <?= e(CONTACT_PHONE) ?></a>
      <a href="mailto:<?= e(CONTACT_EMAIL) ?>">✉️ <?= e(CONTACT_EMAIL) ?></a>
      <a href="<?= e(CONTACT_FACEBOOK_URL) ?>" target="_blank" rel="noopener"><?= FACEBOOK_ICON ?> <?= e(CONTACT_FACEBOOK_NAME) ?></a>
    </p>
    <p>&copy; <?= date('Y') ?> <?= te('app.name') ?> &mdash; <?= te('footer.tagline') ?></p>
    <p style="opacity:.6; font-size:.75rem;">v<?= e(APP_VERSION) ?></p>
  </div>
</footer>
</body>
</html>
