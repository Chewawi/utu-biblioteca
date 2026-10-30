</main>
<footer class="pie text-center py-4 d-print-none">
  <small>Bibliodeko &mdash; Proyecto del curso</small>
</footer>

<!-- Modal de confirmación: lo usan los formularios con data-confirmar (ver el script de abajo). -->
<div class="modal fade" id="modal-confirmar" tabindex="-1" aria-labelledby="modal-confirmar-titulo" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header pb-0">
        <h2 class="modal-title h5" id="modal-confirmar-titulo">Confirmar</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body text-body-secondary" id="modal-confirmar-texto"></div>
      <div class="modal-footer pt-0">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="modal-confirmar-volver">Cancelar</button>
        <button type="button" class="btn" id="modal-confirmar-boton"></button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/es.js"></script>
<script>
  // <select class="select2-buscable">: desplegable de Select2 con buscador, para
  // listas largas como socios o ítems. Los de pocas opciones son <select> comunes.
  $('.select2-buscable').select2({ width: '100%', language: 'es' });

  // Botón de la barra: pasa de claro a oscuro (y al revés) y recuerda la elección.
  document.getElementById('cambiar-tema').addEventListener('click', function () {
    var nuevo = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-bs-theme', nuevo);
    localStorage.setItem('tema', nuevo);
  });

  // Confirmación antes de una acción que no se puede deshacer.
  // El formulario lleva data-confirmar (la pregunta), data-boton (el texto del
  // botón que confirma) y data-color (danger o primary). Opcional: data-volver,
  // el texto del botón que no hace nada (si no, "Cancelar"). Al mandarlo, en vez
  // de enviarse muestra el modal; recién si confirman se envía.
  var modalConfirmar = new bootstrap.Modal('#modal-confirmar');
  var botonConfirmar = document.getElementById('modal-confirmar-boton');
  var formPendiente = null;

  document.querySelectorAll('form[data-confirmar]').forEach(function (form) {
    form.addEventListener('submit', function (evento) {
      evento.preventDefault();
      formPendiente = form;
      document.getElementById('modal-confirmar-texto').textContent = form.dataset.confirmar;
      document.getElementById('modal-confirmar-volver').textContent = form.dataset.volver || 'Cancelar';
      botonConfirmar.textContent = form.dataset.boton;
      botonConfirmar.className = 'btn btn-' + form.dataset.color;
      modalConfirmar.show();
    });
  });

  botonConfirmar.addEventListener('click', function () {
    formPendiente.submit(); // submit() no vuelve a disparar el evento, así que no pregunta de nuevo
  });
</script>
</body>
</html>
