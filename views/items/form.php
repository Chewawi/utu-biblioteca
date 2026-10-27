<?php require __DIR__ . '/../header.php'; ?>

<div class="encabezado">
  <h1 class="h3"><?= isset($item) ? 'Editar ítem' : 'Nuevo ítem' ?></h1>
</div>

<?php if (isset($error)): ?>
  <div class="alert alert-danger ancho-formulario"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php
// Si el formulario vuelve por un error, mostramos lo que se escribió ($_POST);
// si no, los datos guardados del ítem (al editar) o los valores iniciales (al crear).
$tipoElegido = $_POST['tipo'] ?? (isset($item) ? $item->getTipo() : 'libro');
$titulo      = $_POST['titulo'] ?? (isset($item) ? $item->getTitulo() : '');
$autor       = $_POST['autor'] ?? (isset($item) ? $item->getAutor() : '');
$categoria   = $_POST['categoria'] ?? (isset($item) ? ($item->getCategoria() ?? '') : '');
$stock       = $_POST['stock'] ?? (isset($item) ? $item->getStock() : '1');
?>

<div class="card ancho-formulario">
  <div class="card-body p-4">
    <form method="POST"
      action="index.php?accion=<?= isset($item) ? 'editarItem' : 'crearItem' ?>"
      enctype="multipart/form-data">
      <?= campoCsrf() ?>

      <?php if (isset($item)): ?>
        <input type="hidden" name="id" value="<?= $item->getId() ?>">
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-select select2" required>
          <?php foreach (Item::TIPOS as $tipo): ?>
            <option value="<?= htmlspecialchars($tipo) ?>" <?= $tipo === $tipoElegido ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($tipo)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Título</label>
        <input type="text" name="titulo" class="form-control" required maxlength="150" value="<?= htmlspecialchars($titulo) ?>">
      </div>

      <div class="mb-3">
        <label class="form-label" id="etiqueta-autor"><?= htmlspecialchars(Item::etiquetaAutor($tipoElegido)) ?></label>
        <input type="text" name="autor" class="form-control" required maxlength="100" value="<?= htmlspecialchars($autor) ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Categoría (opcional)</label>
        <input type="text" name="categoria" class="form-control" maxlength="50" placeholder="Novela, Estrategia, Robótica..."
          value="<?= htmlspecialchars($categoria) ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Stock (cantidad de ejemplares)</label>
        <input type="number" name="stock" class="form-control" min="1" max="9999" step="1" required
          value="<?= htmlspecialchars($stock) ?>">
      </div>

      <?php if (isset($item)): ?>
        <div class="mb-2">
          <label class="form-label d-block">Portada actual</label>
          <img src="uploads/<?= htmlspecialchars($item->getImagen()) ?>" width="80" class="rounded mb-2">
        </div>
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label">
          <?= isset($item) ? 'Reemplazar portada (opcional)' : 'Portada (opcional)' ?>
        </label>
        <input type="file" name="imagen" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        <div class="form-text">JPG, PNG o WEBP. Máximo 2&nbsp;MB.</div>
      </div>

      <div class="d-flex gap-2 pt-2">
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="index.php?accion=listarItems" class="btn btn-link">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<script>
  // Al cambiar el tipo, el campo "Autor" pasa a llamarse "Creador" (y al revés).
  // Select2 avisa el cambio con un evento de jQuery, que se carga en el footer:
  // por eso esperamos a que la página termine de cargar.
  document.addEventListener('DOMContentLoaded', function () {
    $('select[name="tipo"]').on('change', function () {
      document.getElementById('etiqueta-autor').textContent = this.value === 'libro' ? 'Autor' : 'Creador';
    });
  });
</script>

<?php require __DIR__ . '/../footer.php'; ?>
