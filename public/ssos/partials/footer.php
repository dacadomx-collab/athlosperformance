</main>

<footer class="ssos-footer">
    <p class="mb-1">Athlos Performance BCS — Laboratorio de Ciencias del Deporte y Movimiento Humano | La Paz, BCS</p>
    <p class="mb-0 ssos-footer-copyright">Athlos Cognitive Engine v1.0 | © 2026 Todos los derechos reservados.</p>
</footer>

<button type="button" id="btn-back-to-top" class="ssos-back-to-top" data-ssos-back-to-top aria-label="Volver arriba">↑</button>

<div class="toast-container position-fixed bottom-0 end-0 p-3 ssos-toast-container">
    <div id="ssosToast" class="toast align-items-center text-bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="ssosToastBody"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(ssos_base_url()) ?>/js/main.js"></script>
</body>
</html>
