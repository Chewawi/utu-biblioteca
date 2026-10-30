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
        <label class="form-label" for="tipo">Tipo</label>
        <select id="tipo" name="tipo" class="form-select select2" required>
          <?php foreach (Item::TIPOS as $tipo): ?>
            <option value="<?= htmlspecialchars($tipo) ?>" <?= $tipo === $tipoElegido ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($tipo)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label" for="titulo">Título</label>
        <input type="text" id="titulo" name="titulo" class="form-control" required maxlength="150" value="<?= htmlspecialchars($titulo) ?>">
      </div>

      <div class="mb-3">
        <label class="form-label" for="autor" id="etiqueta-autor"><?= htmlspecialchars(Item::etiquetaAutor($tipoElegido)) ?></label>
        <input type="text" id="autor" name="autor" class="form-control" required maxlength="100" value="<?= htmlspecialchars($autor) ?>">
      </div>

      <div class="mb-3">
        <label class="form-label" for="categoria">Categoría (opcional)</label>
        <?php // list="categorias": al escribir, el navegador sugiere las categorías que ya existen. ?>
        <input type="text" id="categoria" name="categoria" class="form-control" maxlength="50" placeholder="Novela, Estrategia, Robótica..."
          list="categorias" autocomplete="off" value="<?= htmlspecialchars($categoria) ?>">
        <datalist id="categorias">
          <?php foreach ($categorias as $nombreCategoria): ?>
            <option value="<?= htmlspecialchars($nombreCategoria) ?>"></option>
          <?php endforeach; ?>
        </datalist>
        <div class="form-text">Elegí una de las sugeridas si ya existe, así no quedan repetidas.</div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="stock">Stock (cantidad de ejemplares)</label>
        <input type="number" id="stock" name="stock" class="form-control" min="1" max="9999" step="1" required
          value="<?= htmlspecialchars($stock) ?>">
      </div>

      <?php if (isset($item)): ?>
        <div class="mb-2">
          <p class="form-label">Portada actual</p>
          <img src="uploads/<?= htmlspecialchars($item->getImagen()) ?>" alt="" width="80" class="rounded mb-2">
        </div>
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label" for="imagen">
          <?= isset($item) ? 'Reemplazar portada (opcional)' : 'Portada (opcional)' ?>
        </label>
        <input type="file" id="imagen" name="imagen" class="form-control" accept=".jpg,.jpeg,.png,.webp">
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
