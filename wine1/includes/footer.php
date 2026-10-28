</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a class="brand brand--light" href="<?= url('index.php') ?>">
                <img src="<?= asset('img/logo.svg') ?>" alt="" width="40" height="40">
                <span class="brand__text">
                    <strong><?= e(setting('shop_name', 'Vino e Casa')) ?></strong>
                    <small>Wine Merchants</small>
                </span>
            </a>
            <p><?= e(setting('tagline', 'Curated wines, delivered to your door')) ?>. Independently selected, cellar-worthy bottles from around the world.</p>
        </div>

        <div class="footer-col">
            <h4>Shop</h4>
            <a href="<?= url('shop.php') ?>">All wines</a>
            <a href="<?= url('shop.php?category=red-wine') ?>">Red wine</a>
            <a href="<?= url('shop.php?category=white-cellar') ?>">White wine</a>
            <a href="<?= url('shop.php?category=rose') ?>">Rosé</a>
            <a href="<?= url('shop.php?category=sparkling') ?>">Sparkling</a>
        </div>

        <div class="footer-col">
            <h4>Company</h4>
            <a href="<?= url('about.php') ?>">Our story</a>
            <a href="<?= url('contact.php') ?>">Contact</a>
            <a href="<?= url('my-orders.php') ?>">Track an order</a>
            <a href="<?= url('login.php') ?>">Sign in</a>
        </div>

        <div class="footer-col">
            <h4> Visit us</h4>
            <p><?= e(setting('shop_address', '')) ?></p>
            <p><a href="mailto:<?= e(setting('shop_email', '')) ?>"><?= e(setting('shop_email', '')) ?></a></p>
            <p><?= e(setting('shop_phone', '')) ?></p>
        </div>
    </div>

    <div class="container footer-bottom">
        <span>&copy; <?= date('Y') ?> <?= e(setting('shop_name', 'Vino e Casa')) ?>. Please drink responsibly. 21+ only.</span>
        <span>Built with HTML, CSS, JavaScript &amp; PHP/MySQL</span>
    </div>
</footer>

<script>window.APP = { base: '<?= e(BASE_URL) ?>', csrf: '<?= e(csrf_token()) ?>' };</script>
<script src="<?= asset('js/main.js') ?>" defer></script>
</body>
</html>
