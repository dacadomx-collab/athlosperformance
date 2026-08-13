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

    // ─── Dashboard Único: mantener sincronizado el hash de la URL con la
    // pestaña Bootstrap realmente visible. Bootstrap NO actualiza el hash por
    // sí solo al cambiar de tab (sólo alterna clases CSS) — sin esto, el hash
    // quedaba "congelado" en el valor de la carga inicial de la página, así
    // que cualquier acción que dependiera de "qué pestaña se ve ahora mismo"
    // (compartir el enlace, recargar, F5) perdía el contexto real del usuario. ─
    document.addEventListener("shown.bs.tab", function (event) {
        var idPestana = (event.target.id || "").replace("tab-btn-", "");
        if (idPestana) {
            history.replaceState(null, "", "#" + idPestana);
        }
    });

    // ─── Retención de pestaña activa al guardar cualquier formulario (auditoría
    // UX): inyecta/actualiza un campo oculto "tab_origen" con el id de la
    // pestaña Bootstrap actualmente visible, justo antes de enviar CUALQUIER
    // <form method="post"> de la página. dashboard/index.php lo lee en el
    // servidor para volver a mostrar exactamente esa pestaña tras procesar la
    // acción, en vez de caer siempre a la primera. No-op inofensivo en
    // páginas sin sistema de pestañas (no encuentra ningún .tab-pane.active). ─
    document.addEventListener("submit", function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== "post") {
            return;
        }
        var paneActivo = document.querySelector(".tab-pane.active[id^='pane-']");
        if (!paneActivo) {
            return;
        }
        var idPestana = paneActivo.id.replace("pane-", "");
        var campoTab = form.querySelector('input[name="tab_origen"]');
        if (!campoTab) {
            campoTab = document.createElement("input");
            campoTab.type = "hidden";
            campoTab.name = "tab_origen";
            form.appendChild(campoTab);
        }
        campoTab.value = idPestana;
    });

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

    // ─── Testimonios (Casos de Éxito): modal compartido "Editar Testimonio" —
    // mismo patrón que modalEditarAtleta/modalEquipoEditar (un solo modal
    // rellenado vía data-* del botón que lo abrió, en vez de un modal por fila
    // — la versión anterior insertaba un <div class="modal"> como hijo directo
    // de <tbody>, HTML inválido que el navegador "repara" reubicándolo en el
    // DOM (foster parenting), causando el modal transparente/traslapado con la
    // tabla reportado en la auditoría visual). ────────────────────────────────
    var modalEditarTestimonio = document.getElementById("modalEditarTestimonio");
    if (modalEditarTestimonio) {
        modalEditarTestimonio.addEventListener("show.bs.modal", function (event) {
            var boton = event.relatedTarget;
            if (!boton) {
                return;
            }
            modalEditarTestimonio.querySelector("#testimonioEditar_id").value = boton.dataset.id || "";
            modalEditarTestimonio.querySelector("#testimonioEditar_nombre").value = boton.dataset.nombre || "";
            modalEditarTestimonio.querySelector("#testimonioEditar_comentario").value = boton.dataset.comentario || "";
            modalEditarTestimonio.querySelector("#testimonioEditar_fecha").value = boton.dataset.fecha || "";
            modalEditarTestimonio.querySelector("#testimonioEditar_orden").value = boton.dataset.orden || "0";
            var campoFoto = modalEditarTestimonio.querySelector("#testimonioEditar_foto");
            if (campoFoto) {
                campoFoto.value = "";
            }
            var previewFoto = modalEditarTestimonio.querySelector("#testimonioEditar_foto_nombre");
            if (previewFoto) {
                previewFoto.textContent = "";
            }
        });
    }

    // ─── Vista previa del nombre de archivo en cualquier <input type="file">
    // marcado con data-ssos-preview-nombre-archivo="id-del-elemento-destino" —
    // confirma al usuario que la imagen sí se seleccionó antes de "Guardar". ──
    document.addEventListener("change", function (event) {
        var input = event.target;
        if (!(input instanceof HTMLInputElement) || input.type !== "file" || !input.dataset.ssosPreviewNombreArchivo) {
            return;
        }
        var destino = document.getElementById(input.dataset.ssosPreviewNombreArchivo);
        if (!destino) {
            return;
        }
        destino.textContent = input.files && input.files.length > 0 ? "📎 " + input.files[0].name : "";
    });

    // ─── Equipo del Laboratorio: modal compartido "Editar Miembro" — mismo
    // patrón que modalEditarAtleta (un solo modal, rellenado vía data-* del
    // botón que lo abrió, en vez de un modal por fila de la tabla). ─────────
    var modalEquipoEditar = document.getElementById("modalEquipoEditar");
    if (modalEquipoEditar) {
        modalEquipoEditar.addEventListener("show.bs.modal", function (event) {
            var boton = event.relatedTarget;
            if (!boton) {
                return;
            }
            modalEquipoEditar.querySelector("#equipoEditar_id").value = boton.dataset.id || "";
            modalEquipoEditar.querySelector("#equipoEditar_nombre").value = boton.dataset.nombre || "";
            modalEquipoEditar.querySelector("#equipoEditar_email").value = boton.dataset.email || "";
            modalEquipoEditar.querySelector("#equipoEditar_rol").value = boton.dataset.rol || "coach";
            modalEquipoEditar.querySelector("#equipoEditar_especialidad").value = boton.dataset.especialidad || "";
        });
    }

    // ─── Equipo del Laboratorio: modal compartido "Resetear Contraseña" ─────
    var modalEquipoResetPassword = document.getElementById("modalEquipoResetPassword");
    if (modalEquipoResetPassword) {
        modalEquipoResetPassword.addEventListener("show.bs.modal", function (event) {
            var boton = event.relatedTarget;
            if (!boton) {
                return;
            }
            modalEquipoResetPassword.querySelector("#equipoResetPassword_id").value = boton.dataset.id || "";
            modalEquipoResetPassword.querySelector("#equipoResetPassword_nombre").textContent = boton.dataset.nombre || "";
            var campoPassword = modalEquipoResetPassword.querySelector("#equipoResetPassword_password");
            if (campoPassword) {
                campoPassword.value = "";
            }
        });
    }

    // ─── Clientes y Membresías: filtro "Ocultar suspendidos/inactivos" —
    // client-side puro (toggle de display), sin recargar la página. Mismo
    // patrón ya usado en el sidebar de la Agenda (toggle de citas por coach). ─
    var checkOcultarSuspendidos = document.querySelector("[data-ssos-ocultar-suspendidos]");
    var tablaClientes = document.getElementById("ssosTablaClientes");
    if (checkOcultarSuspendidos && tablaClientes) {
        checkOcultarSuspendidos.addEventListener("change", function () {
            var ocultar = checkOcultarSuspendidos.checked;
            tablaClientes.querySelectorAll("tr[data-estatus]").forEach(function (fila) {
                var estatus = fila.getAttribute("data-estatus");
                var debeOcultarse = ocultar && (estatus === "suspendido" || estatus === "inactivo");
                fila.style.display = debeOcultarse ? "none" : "";
            });
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
