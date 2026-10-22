</div>
<footer class="text-center text-muted py-4">
  <small>Bibliodeko &mdash; Proyecto del curso</small>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/es.js"></script>
<script>
  // <select class="select2">: desplegable de Select2 sin buscador (para pocas opciones).
  $('.select2').select2({ width: '100%', language: 'es', minimumResultsForSearch: Infinity });
  // <select class="select2-buscable">: con buscador, para listas largas como socios o ítems.
  $('.select2-buscable').select2({ width: '100%', language: 'es' });
</script>
</body>
</html>
