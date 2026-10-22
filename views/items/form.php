<?php require __DIR__ . '/../header.php'; ?>

<h1 class="h3 mb-3"><?= isset($item) ? 'Editar ítem' : 'Nuevo ítem' ?></h1>

<?php if (isset($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST"
  action="index.php?accion=<?= isset($item) ? 'editarItem' : 'crearItem' ?>"
  enctype="multipart/form-data"
  class="col-md-6">

  <?php if (isset($item)): ?>
    <input type="hidden" name="id" value="<?= $item->getId() ?>">
  <?php endif; ?>

  <?php $tipoElegido = isset($item) ? $item->getTipo() : ($_POST['tipo'] ?? 'libro'); ?>
  <div class="mb-3">
    <label class="form-label">Tipo</label>
    <select name="tipo" class="form-select" required>
      <?php foreach (Item::TIPOS as $tipo): ?>
        <option value="<?= htmlspecialchars($tipo) ?>" <?= $tipo === $tipoElegido ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($tipo)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Título</label>
    <input type="text" name="titulo" class="form-control" required
      value="<?= isset($item) ? htmlspecialchars($item->getTitulo()) : htmlspecialchars($_POST['titulo'] ?? '') ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Autor</label>
    <input type="text" name="autor" class="form-control" required
      value="<?= isset($item) ? htmlspecialchars($item->getAutor()) : htmlspecialchars($_POST['autor'] ?? '') ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Categoría (opcional)</label>
    <input type="text" name="categoria" class="form-control" placeholder="Novela, Estrategia, Robótica..."
      value="<?= isset($item) ? htmlspecialchars($item->getCategoria() ?? '') : htmlspecialchars($_POST['categoria'] ?? '') ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Stock (cantidad de ejemplares)</label>
    <input type="number" name="stock" class="form-control" min="1" step="1" required
      value="<?= isset($item) ? htmlspecialchars($item->getStock()) : htmlspecialchars($_POST['stock'] ?? '1') ?>">
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

  <button type="submit" class="btn btn-primary">Guardar</button>
  <a href="index.php?accion=listarItems" class="btn btn-link">Cancelar</a>
</form>

<?php require __DIR__ . '/../footer.php'; ?>
