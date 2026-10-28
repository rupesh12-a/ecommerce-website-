/* =============================================================
   Vino e Casa — admin panel scripts
   ============================================================= */
(function () {
    "use strict";

    /* Sidebar toggle (mobile) */
    document.querySelectorAll("[data-sidebar-toggle]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const sidebar = document.querySelector("[data-sidebar]");
            if (sidebar) sidebar.classList.toggle("is-open");
        });
    });
    document.addEventListener("click", (e) => {
        const sidebar = document.querySelector("[data-sidebar]");
        if (!sidebar || !sidebar.classList.contains("is-open")) return;
        if (!sidebar.contains(e.target) && !e.target.closest("[data-sidebar-toggle]")) {
            sidebar.classList.remove("is-open");
        }
    });

    /* Confirm before destructive submits */
    document.querySelectorAll("form[data-confirm]").forEach((form) => {
        form.addEventListener("submit", (e) => {
            if (!confirm(form.getAttribute("data-confirm") || "Are you sure?")) e.preventDefault();
        });
    });

    /* Image preview on product form */
    const fileInput = document.querySelector("[data-image-input]");
    const preview = document.getElementById("imagePreview");
    const pathInput = document.querySelector("[data-image-path]");
    if (fileInput && preview) {
        fileInput.addEventListener("change", () => {
            const file = fileInput.files && fileInput.files[0];
            if (file) preview.src = URL.createObjectURL(file);
        });
    }
    if (pathInput && preview) {
        pathInput.addEventListener("input", () => {
            const v = pathInput.value.trim();
            if (v) preview.src = /^https?:\/\//.test(v) ? v : (window.APP ? window.APP.base : "") + "/" + v;
        });
    }

    /* Auto-dismiss server-rendered toasts */
    document.querySelectorAll(".toast").forEach((el) => {
        setTimeout(() => {
            el.classList.add("is-leaving");
            setTimeout(() => el.remove(), 320);
        }, 3800);
    });
})();
