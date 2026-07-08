(function () {
    "use strict";

    var STORAGE_KEY = "athlos_ssos_theme";
    var root = document.documentElement;

    function applyTheme(theme) {
        root.setAttribute("data-theme", theme);
        document.querySelectorAll("[data-ssos-theme-toggle]").forEach(function (toggle) {
            toggle.textContent = theme === "dark" ? "☀️" : "🌙";
        });
    }

    function initialTheme() {
        var saved = localStorage.getItem(STORAGE_KEY);
        if (saved === "dark" || saved === "light") {
            return saved;
        }
        return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
    }

    applyTheme(initialTheme());

    document.addEventListener("click", function (event) {
        var toggle = event.target.closest("[data-ssos-theme-toggle]");
        if (toggle) {
            var next = root.getAttribute("data-theme") === "dark" ? "light" : "dark";
            localStorage.setItem(STORAGE_KEY, next);
            applyTheme(next);
        }
    });

    // ─── Botón "Volver arriba" ──────────────────────────────────────────────
    var backToTop = document.querySelector("[data-ssos-back-to-top]");
    if (backToTop) {
        window.addEventListener("scroll", function () {
            backToTop.classList.toggle("is-visible", window.scrollY > 400);
        });
        backToTop.addEventListener("click", function () {
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    }

    // ─── Checklist Sentadilla Overhead: toggle visual del botón ─────────────
    document.querySelectorAll(".pdc-check-btn").forEach(function (btn) {
        var input = btn.querySelector("input[type=checkbox]");
        if (!input) {
            return;
        }
        var sync = function () {
            btn.classList.toggle("is-checked", input.checked);
        };
        input.addEventListener("change", sync);
        sync();
    });

    // ─── Slider RPE: reflejar valor numérico grande ─────────────────────────
    var rpeSlider = document.querySelector("[data-pdc-rpe-slider]");
    var rpeValue = document.querySelector("[data-pdc-rpe-value]");
    if (rpeSlider && rpeValue) {
        var updateRpe = function () {
            rpeValue.textContent = rpeSlider.value;
        };
        rpeSlider.addEventListener("input", updateRpe);
        updateRpe();
    }

    // ─── Dashboard Único: activar la pestaña Bootstrap indicada en el hash de
    // la URL (ej. enlaces del menú hamburguesa a "index.php#control" desde
    // cualquier otra página funcionan igual que si ya estuvieras en el tab). ──
    function activarTabDesdeHash() {
        var hash = window.location.hash.replace("#", "");
        if (!hash || typeof bootstrap === "undefined") {
            return;
        }
        var boton = document.getElementById("tab-btn-" + hash);
        if (boton) {
            new bootstrap.Tab(boton).show();
        }
    }
    document.addEventListener("DOMContentLoaded", activarTabDesdeHash);
    window.addEventListener("hashchange", activarTabDesdeHash);
})();
