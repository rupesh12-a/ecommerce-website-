/* =============================================================
   Vino e Casa — storefront scripts
   ============================================================= */
(function () {
    "use strict";

    const APP = window.APP || { base: "", csrf: "" };

    /* ---------------------------------------------------- Toast */
    function toast(message, type = "success") {
        let stack = document.querySelector("[data-toast-stack]");
        if (!stack) {
            stack = document.createElement("div");
            stack.className = "toast-stack";
            stack.setAttribute("data-toast-stack", "");
            document.body.appendChild(stack);
        }
        const el = document.createElement("div");
        el.className = "toast toast--" + type;
        el.textContent = message;
        stack.appendChild(el);
        setTimeout(() => {
            el.classList.add("is-leaving");
            setTimeout(() => el.remove(), 320);
        }, 3200);
    }

    /* ---------------------------------------------------- Header */
    const header = document.getElementById("siteHeader");
    if (header) {
        const onScroll = () => header.classList.toggle("is-scrolled", window.scrollY > 8);
        onScroll();
        window.addEventListener("scroll", onScroll, { passive: true });
    }

    /* Mobile nav */
    document.querySelectorAll("[data-nav-toggle]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const nav = document.querySelector("[data-nav]");
            if (!nav) return;
            const open = nav.classList.toggle("is-open");
            btn.setAttribute("aria-expanded", String(open));
        });
    });

    /* Account dropdown */
    document.querySelectorAll("[data-dropdown-toggle]").forEach((btn) => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            const dd = btn.parentElement.querySelector("[data-dropdown]");
            if (!dd) return;
            const open = dd.classList.toggle("is-open");
            btn.setAttribute("aria-expanded", String(open));
        });
    });
    document.addEventListener("click", () => {
        document.querySelectorAll("[data-dropdown].is-open").forEach((dd) => dd.classList.remove("is-open"));
    });

    /* Filters drawer (mobile) */
    document.querySelectorAll("[data-filters-toggle]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const panel = document.querySelector("[data-filters]");
            if (panel) panel.classList.toggle("is-open");
        });
    });

    /* ---------------------------------------------------- Quantity steppers */
    document.addEventListener("click", (e) => {
        const btn = e.target.closest("[data-qty]");
        if (!btn) return;
        const wrap = btn.closest(".qty");
        const input = wrap.querySelector('input[name="qty"], input');
        if (!input) return;
        const step = parseInt(btn.getAttribute("data-qty"), 10) || 0;
        const min = parseInt(input.min, 10) || 1;
        const max = parseInt(input.max, 10) || 9999;
        let value = (parseInt(input.value, 10) || min) + step;
        value = Math.max(min, Math.min(max, value));
        input.value = value;

        const cartForm = btn.closest(".js-cart-update");
        if (cartForm) submitCartForm(cartForm);
    });

    document.querySelectorAll(".js-cart-update input[name='qty']").forEach((input) => {
        input.addEventListener("change", () => submitCartForm(input.closest("form")));
    });

    /* ---------------------------------------------------- Add to cart (AJAX) */
    document.addEventListener("submit", (e) => {
        const form = e.target.closest(".js-add-to-cart");
        if (!form) return;
        e.preventDefault();

        const qtyInput = form.querySelector('input[name="qty"]');
        form.querySelectorAll("button[type=submit]").forEach((b) => { b.disabled = true; });

        fetch(APP.base + "/cart.php", {
            method: "POST",
            body: new FormData(form),
            headers: { "X-Requested-With": "XMLHttpRequest" },
        })
            .then((r) => r.json())
            .then((data) => {
                if (data && data.ok) {
                    updateCartCount(data.count);
                    toast("Added to your cart.", "success");
                    if (qtyInput) qtyInput.value = qtyInput.min || 1;
                } else {
                    toast((data && data.error) || "Could not add to cart.", "error");
                }
            })
            .catch(() => toast("Something went wrong. Please try again.", "error"))
            .finally(() => form.querySelectorAll("button[type=submit]").forEach((b) => { b.disabled = false; }));
    });

    function updateCartCount(count) {
        const badge = document.querySelector("[data-cart-count]");
        if (!badge) return;
        badge.textContent = count;
        badge.hidden = !count;
    }

    /* ---------------------------------------------------- Cart page updates */
    function submitCartForm(form) {
        if (!form) return;
        fetch(APP.base + "/cart.php", {
            method: "POST",
            body: new FormData(form),
            headers: { "X-Requested-With": "XMLHttpRequest" },
        })
            .then((r) => r.json())
            .then((data) => {
                if (!data || !data.ok) return;
                updateCartCount(data.count);
                applyCartSummary(data);
                const pid = form.querySelector('input[name="product_id"]').value;
                const row = document.querySelector('.cart-item[data-product-id="' + pid + '"]');
                const line = data.items.find((i) => String(i.id) === String(pid));
                if (line && row) {
                    const cell = row.querySelector("[data-line-total]");
                    if (cell) cell.textContent = line.line;
                    const qty = row.querySelector('input[name="qty"]');
                    if (qty) qty.value = line.qty;
                }
                if (data.count === 0) window.location.reload();
            });
    }

    function applyCartSummary(data) {
        const set = (sel, value) => { const el = document.querySelector(sel); if (el) el.textContent = value; };
        set("[data-summary-subtotal]", data.subtotal);
        set("[data-summary-shipping]", data.freeAbove ? "Free" : data.shipping);
        set("[data-summary-total]", data.total);
    }

    document.querySelectorAll(".js-cart-remove").forEach((btn) => {
        btn.addEventListener("click", () => {
            const pid = btn.getAttribute("data-product-id");
            const fd = new FormData();
            fd.append("action", "remove");
            fd.append("product_id", pid);
            fd.append("csrf_token", APP.csrf);
            fetch(APP.base + "/cart.php", {
                method: "POST", body: fd, headers: { "X-Requested-With": "XMLHttpRequest" },
            })
                .then((r) => r.json())
                .then((data) => {
                    if (!data || !data.ok) return;
                    updateCartCount(data.count);
                    const row = document.querySelector('.cart-item[data-product-id="' + pid + '"]');
                    if (row) row.remove();
                    if (data.count === 0) window.location.reload();
                    else { applyCartSummary(data); toast("Item removed.", "warning"); }
                });
        });
    });

    document.querySelectorAll("[data-cart-clear]").forEach((btn) => {
        btn.addEventListener("click", () => {
            if (!confirm("Remove everything from your cart?")) return;
            const fd = new FormData();
            fd.append("action", "clear");
            fd.append("csrf_token", APP.csrf);
            fetch(APP.base + "/cart.php", {
                method: "POST", body: fd, headers: { "X-Requested-With": "XMLHttpRequest" },
            }).then(() => window.location.reload());
        });
    });

    /* ---------------------------------------------------- Live search */
    const searchInput = document.querySelector(".search input[name='q']");
    if (searchInput) {
        const box = document.createElement("div");
        box.className = "search-suggest";
        box.style.cssText = "position:absolute;top:calc(100% + 6px);left:0;right:0;background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:var(--shadow);padding:.35rem;display:none;z-index:70;max-height:340px;overflow:auto;";
        searchInput.parentElement.appendChild(box);

        let timer;
        searchInput.addEventListener("input", () => {
            clearTimeout(timer);
            const q = searchInput.value.trim();
            if (q.length < 2) { box.style.display = "none"; return; }
            timer = setTimeout(() => {
                fetch(APP.base + "/api/products.php?q=" + encodeURIComponent(q), {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                })
                    .then((r) => r.json())
                    .then((data) => {
                        if (!data.results || !data.results.length) { box.style.display = "none"; return; }
                        box.innerHTML = data.results.map((p) =>
                            '<a href="' + p.url + '" style="display:flex;gap:.7rem;align-items:center;padding:.45rem;border-radius:8px;text-decoration:none;color:inherit;">' +
                            '<img src="' + p.image + '" alt="" style="width:32px;height:42px;object-fit:cover;border-radius:5px;background:#f1e9dd;">' +
                            '<span style="flex:1;min-width:0;"><strong style="display:block;font-size:.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + p.name + '</strong>' +
                            '<small style="color:#8b7f86;font-size:.74rem;">' + p.category + " · " + p.price + "</small></span></a>"
                        ).join("");
                        box.style.display = "block";
                    })
                    .catch(() => { box.style.display = "none"; });
            }, 220);
        });
        document.addEventListener("click", (e) => {
            if (!searchInput.parentElement.contains(e.target)) box.style.display = "none";
        });
    }
})();
