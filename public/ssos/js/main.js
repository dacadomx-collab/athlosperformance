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

    // ─── Wizard de 8 pasos (Historial Clínico): navegación 100% client-side,
    // un solo <form>/POST — el wizard sólo esconde/muestra fieldsets, nunca
    // envía nada a medio llenar. ─────────────────────────────────────────────
    document.querySelectorAll("[data-ssos-wizard]").forEach(function (wizard) {
        var pasos = Array.prototype.slice.call(wizard.querySelectorAll("[data-ssos-wizard-step]"));
        if (pasos.length === 0) {
            return;
        }
        var total = pasos.length;
        var stepLabel = wizard.querySelector("[data-ssos-wizard-step-label]");
        var moduleLabel = wizard.querySelector("[data-ssos-wizard-module-label]");
        var progressBar = wizard.querySelector("[data-ssos-wizard-progress-bar]");
        var btnPrev = wizard.querySelector("[data-ssos-wizard-prev]");
        var btnNext = wizard.querySelector("[data-ssos-wizard-next]");
        var btnSubmit = wizard.querySelector("[data-ssos-wizard-submit]");
        var actual = 0;

        var mostrar = function (indice) {
            pasos.forEach(function (paso, i) {
                paso.hidden = i !== indice;
            });
            if (stepLabel) {
                stepLabel.textContent = "Paso " + (indice + 1) + " de " + total;
            }
            if (moduleLabel) {
                moduleLabel.textContent = pasos[indice].getAttribute("data-ssos-wizard-module") || "";
            }
            if (progressBar) {
                var pct = Math.round(((indice + 1) / total) * 100);
                progressBar.style.width = pct + "%";
                wizard.querySelector(".ssos-wizard-progress").setAttribute("aria-valuenow", String(pct));
            }
            if (btnPrev) {
                btnPrev.hidden = indice === 0;
            }
            if (btnNext) {
                btnNext.hidden = indice === total - 1;
            }
            if (btnSubmit) {
                btnSubmit.hidden = indice !== total - 1;
            }
            wizard.scrollIntoView({ behavior: "smooth", block: "start" });
        };

        if (btnNext) {
            btnNext.addEventListener("click", function () {
                if (actual < total - 1) {
                    actual++;
                    mostrar(actual);
                }
            });
        }
        if (btnPrev) {
            btnPrev.addEventListener("click", function () {
                if (actual > 0) {
                    actual--;
                    mostrar(actual);
                }
            });
        }

        mostrar(actual);
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

    // ─── Agenda: matriz semanal — click para agendar/ver detalle, drag&drop
    // para reagendar, y toggle de visibilidad por coach. Todo delegado sobre
    // `document` (mismo patrón que el resto del archivo), así que funciona
    // sin importar cuántas celdas/citas haya en la matriz. ──────────────────
    var agendaMatriz = document.querySelector(".ssos-agenda-matriz");
    if (agendaMatriz) {
        var modalNuevaCitaEl = document.getElementById("modalNuevaCita");
        var modalNuevaCita = modalNuevaCitaEl && typeof bootstrap !== "undefined" ? new bootstrap.Modal(modalNuevaCitaEl) : null;
        var modalDetalleCitaEl = document.getElementById("modalDetalleCita");
        var modalDetalleCita = modalDetalleCitaEl && typeof bootstrap !== "undefined" ? new bootstrap.Modal(modalDetalleCitaEl) : null;
        var formEstatusCita = document.getElementById("ssosFormEstatusCita");

        var etiquetasEstatusJs = {
            reservada: "Reservada", confirmada: "Confirmada", completada: "Completada",
            cancelada: "Cancelada", no_show: "No-Show",
        };

        function csrfTokenAgenda() {
            var input = formEstatusCita ? formEstatusCita.querySelector('input[name="csrf_token"]') : null;
            return input ? input.value : "";
        }

        // ── Click en una cita: abre modal de detalle con acciones según su estatus ──
        agendaMatriz.addEventListener("click", function (event) {
            var citaEl = event.target.closest("[data-ssos-agenda-cita]");
            if (!citaEl || !modalDetalleCita) {
                return;
            }
            event.stopPropagation();

            document.getElementById("ssosDetalleCitaNombre").textContent = citaEl.dataset.nombre || "";
            document.getElementById("ssosDetalleCitaInfo").textContent =
                (citaEl.dataset.staffNombre || "") + " · " + (citaEl.dataset.servicio || "") + " · " + (citaEl.dataset.hora || "");
            var estatusEl = document.getElementById("ssosDetalleCitaEstatus");
            estatusEl.textContent = etiquetasEstatusJs[citaEl.dataset.estatus] || citaEl.dataset.estatus;

            var acciones = document.getElementById("ssosDetalleCitaAcciones");
            acciones.innerHTML = "";
            var idCita = citaEl.dataset.idCita;
            var estatus = citaEl.dataset.estatus;

            function botonAccion(texto, claseCss, nuevoEstatus) {
                var btn = document.createElement("button");
                btn.type = "button";
                btn.className = "btn btn-sm " + claseCss;
                btn.textContent = texto;
                btn.addEventListener("click", function () {
                    document.getElementById("ssosFormEstatusCitaId").value = idCita;
                    document.getElementById("ssosFormEstatusCitaNuevo").value = nuevoEstatus;
                    formEstatusCita.submit();
                });
                return btn;
            }

            if (estatus === "reservada") {
                acciones.appendChild(botonAccion("Confirmar", "btn-outline-secondary", "confirmada"));
            }
            if (estatus === "reservada" || estatus === "confirmada") {
                acciones.appendChild(botonAccion("Completar", "btn-ssos-turquesa", "completada"));
                acciones.appendChild(botonAccion("No-Show", "btn-outline-secondary", "no_show"));
                acciones.appendChild(botonAccion("Cancelar", "btn-outline-danger", "cancelada"));
            }

            modalDetalleCita.show();
        });

        // ── Click en una celda vacía/operativa: abre "Nueva Cita" prellenada ──
        agendaMatriz.addEventListener("click", function (event) {
            if (event.target.closest("[data-ssos-agenda-cita]")) {
                return; // ya lo maneja el listener de arriba
            }
            var celda = event.target.closest("[data-ssos-agenda-celda]");
            if (!celda || !modalNuevaCita) {
                return;
            }
            var ocupadas = parseInt(celda.dataset.ocupadas || "0", 10);
            if (ocupadas >= 4) {
                return; // franja llena — el semáforo rojo ya lo comunica visualmente
            }
            document.getElementById("ssosNuevaCitaFecha").value = celda.dataset.fecha;
            document.getElementById("ssosNuevaCitaHora").value = celda.dataset.hora;
            modalNuevaCita.show();
        });

        // ── Drag & drop: mover una cita a otra franja ──────────────────────
        agendaMatriz.addEventListener("dragstart", function (event) {
            var citaEl = event.target.closest("[data-ssos-agenda-cita]");
            if (!citaEl) {
                return;
            }
            event.dataTransfer.setData("text/plain", citaEl.dataset.idCita);
            citaEl.classList.add("is-dragging");
        });

        agendaMatriz.addEventListener("dragend", function (event) {
            var citaEl = event.target.closest("[data-ssos-agenda-cita]");
            if (citaEl) {
                citaEl.classList.remove("is-dragging");
            }
        });

        agendaMatriz.addEventListener("dragover", function (event) {
            var celda = event.target.closest("[data-ssos-agenda-celda]");
            if (!celda) {
                return;
            }
            event.preventDefault();
            celda.classList.add("is-drop-target");
        });

        agendaMatriz.addEventListener("dragleave", function (event) {
            var celda = event.target.closest("[data-ssos-agenda-celda]");
            if (celda) {
                celda.classList.remove("is-drop-target");
            }
        });

        agendaMatriz.addEventListener("drop", function (event) {
            var celda = event.target.closest("[data-ssos-agenda-celda]");
            if (!celda) {
                return;
            }
            event.preventDefault();
            celda.classList.remove("is-drop-target");

            var idCita = event.dataTransfer.getData("text/plain");
            if (!idCita) {
                return;
            }

            var body = new URLSearchParams({
                csrf_token: csrfTokenAgenda(),
                accion: "mover_cita",
                id_cita: idCita,
                nueva_fecha: celda.dataset.fecha,
                nueva_hora: celda.dataset.hora,
            });

            fetch(window.location.pathname + window.location.search, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: body.toString(),
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.ok) {
                        window.location.reload();
                    } else {
                        window.alert(data.error || "No se pudo mover la cita.");
                    }
                })
                .catch(function () {
                    window.alert("No se pudo mover la cita (error de red).");
                });
        });

        // ── Sidebar derecho: ocultar/mostrar citas por coach (client-side, sin recargar) ──
        document.querySelectorAll("[data-ssos-agenda-toggle-staff]").forEach(function (checkbox) {
            checkbox.addEventListener("change", function () {
                var idStaff = checkbox.getAttribute("data-ssos-agenda-toggle-staff");
                document.querySelectorAll('[data-ssos-agenda-cita][data-id-staff="' + idStaff + '"]').forEach(function (citaEl) {
                    citaEl.style.display = checkbox.checked ? "" : "none";
                });
            });
        });
    }
})();
