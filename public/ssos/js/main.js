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
            backToTop.classList.toggle("is-visible", window.scrollY > 300);
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

    // ─── Copiar Link de Progreso (WhatsApp 1-click) ─────────────────────────
    function mostrarToast(mensaje) {
        var toastEl = document.getElementById("ssosToast");
        if (!toastEl || typeof bootstrap === "undefined") {
            return;
        }
        document.getElementById("ssosToastBody").textContent = mensaje;
        new bootstrap.Toast(toastEl).show();
    }

    document.addEventListener("click", function (event) {
        var boton = event.target.closest("[data-ssos-copy-link]");
        if (!boton) {
            return;
        }
        var url = boton.getAttribute("data-ssos-copy-link");

        var copiar = function () {
            return navigator.clipboard.writeText(url);
        };

        (navigator.clipboard ? copiar() : Promise.reject())
            .then(function () {
                mostrarToast("¡Enlace de progreso copiado! Listo para enviar por WhatsApp al atleta.");
            })
            .catch(function () {
                // Fallback para navegadores/contextos sin Clipboard API (ej. http:// no seguro).
                var temporal = document.createElement("textarea");
                temporal.value = url;
                temporal.style.position = "fixed";
                temporal.style.opacity = "0";
                document.body.appendChild(temporal);
                temporal.select();
                try {
                    document.execCommand("copy");
                    mostrarToast("¡Enlace de progreso copiado! Listo para enviar por WhatsApp al atleta.");
                } catch (e) {
                    mostrarToast("No se pudo copiar automáticamente. Copia el enlace manualmente: " + url);
                }
                document.body.removeChild(temporal);
            });
    });

    // ─── Ojo para mostrar/ocultar contraseña en Login ───────────────────────
    document.addEventListener("click", function (event) {
        var boton = event.target.closest("[data-ssos-toggle-password]");
        if (!boton) {
            return;
        }
        var input = document.getElementById(boton.getAttribute("data-ssos-toggle-password"));
        if (!input) {
            return;
        }
        var mostrando = input.type === "text";
        input.type = mostrando ? "password" : "text";
        boton.textContent = mostrando ? "👁️" : "🙈";
        boton.setAttribute("aria-label", mostrando ? "Mostrar contraseña" : "Ocultar contraseña");
    });

    // ─── Modal compartido "Editar Atleta": rellena los campos desde los
    // data-* del botón que lo abrió, en vez de un modal por fila. ────────────
    var modalEditarAtleta = document.getElementById("modalEditarAtleta");
    if (modalEditarAtleta) {
        modalEditarAtleta.addEventListener("show.bs.modal", function (event) {
            var boton = event.relatedTarget;
            if (!boton) {
                return;
            }
            modalEditarAtleta.querySelector("#editar_id_atleta").value = boton.dataset.id || "";
            modalEditarAtleta.querySelector("#editar_nombre").value = boton.dataset.nombre || "";
            modalEditarAtleta.querySelector("#editar_telefono").value = boton.dataset.telefono || "";
            modalEditarAtleta.querySelector("#editar_email").value = boton.dataset.email || "";
            modalEditarAtleta.querySelector("#editar_fecha_nacimiento").value = boton.dataset.fechaNacimiento || "";
        });
    }
})();
