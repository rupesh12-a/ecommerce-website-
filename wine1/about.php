<?php
require_once __DIR__ . '/config/config.php';
$pageTitle  = 'Our story';
$activePage = 'about';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow eyebrow--gold">Since 2014</span>
        <h1>Wine, chosen with care.</h1>
        <p>From a small cellar in Bengaluru to doorsteps across India — we taste every bottle so you don't have to.</p>
    </div>
</section>

<section class="section">
    <div class="container split">
        <div class="split__copy">
            <span class="eyebrow">Our philosophy</span>
            <h2>Great wine should be approachable</h2>
            <p>We believe the best bottle is the one you're actually excited to open. That's why our buyers skip the mass-market labels and focus on family estates, small growers and honest value.</p>
            <p>Every wine on this site has been tasted blind by our panel, scored, and compared against its price. If it doesn't earn its place on your table, it doesn't make the list.</p>
            <a class="btn btn--primary" href="<?= url('shop.php') ?>">Explore the cellar</a>
        </div>
        <div class="split__media">
            <img src="<?= asset('img/wine-02.svg') ?>" alt="A bottle from our cellar">
        </div>
    </div>
</section>

<section class="section section--tint">
    <div class="container stats">
        <div class="stat"><strong>12</strong><span>countries sourced</span></div>
        <div class="stat"><strong>180+</strong><span>bottles curated</span></div>
        <div class="stat"><strong>9,400</strong><span>happy customers</span></div>
        <div class="stat"><strong>4.8<em>/5</em></strong><span>average rating</span></div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head center">
            <div>
                <span class="eyebrow">What guides us</span>
                <h2>Three simple promises</h2>
            </div>
        </div>
        <div class="value-grid">
            <article class="value-card">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z"/><path d="m9 12 2 2 4-4"/></svg>
                <h3>Independence</h3>
                <p>We're not tied to any importer or brand. Our only loyalty is to what tastes good in the glass.</p>
            </article>
            <article class="value-card">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                <h3>Freshness</h3>
                <p>Temperature-controlled storage and insulated shipping mean the wine arrives exactly as it left the cellar.</p>
            </article>
            <article class="value-card">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-4.5-7-10a4 4 0 0 1 7-2.8A4 4 0 0 1 19 11c0 5.5-7 10-7 10z"/></svg>
                <h3>Hospitality</h3>
                <p>Unsure what to pair? Ask us. Real humans, real recommendations — no scripted upsells.</p>
            </article>
        </div>
    </div>
</section>

<section class="section">
    <div class="container cta-band">
        <div>
            <h2>Start your collection today</h2>
            <p>Free delivery over <?= e(money((float)setting('free_shipping_above', '2499'))) ?> and a 100% satisfaction guarantee.</p>
        </div>
        <a class="btn btn--primary btn--lg" href="<?= url('shop.php') ?>">Shop all wines</a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
