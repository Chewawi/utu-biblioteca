<?php require __DIR__ . '/../header.php'; ?>

<h1 class="h3 mb-3"><?= isset($libro) ? 'Editar libro' : 'Nuevo libro' ?></h1>

<?php if (isset($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST"
      action="index.php?accion=<?= isset($libro) ? 'editar' : 'crear' ?>"
      enctype="multipart/form-data"
      class="col-md-6">

  <?php if (isset($libro)): ?>
    <input type="hidden" name="id" value="<?= $libro->getId() ?>">
  <?php endif; ?>

  <div class="mb-3">
    <label class="form-label">Título</label>
    <input type="text" name="titulo" class="form-control"
           value="<?= isset($libro) ? htmlspecialchars($libro->getTitulo()) : '' ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Autor</label>
    <input type="text" name="autor" class="form-control"
           value="<?= isset($libro) ? htmlspecialchars($libro->getAutor()) : '' ?>">
  </div>

  <?php if (isset($libro)): ?>
    <div class="mb-2">
      <label class="form-label d-block">Portada actual</label>
      <img src="uploads/<?= htmlspecialchars($libro->getImagen()) ?>" width="80" class="rounded mb-2">
    </div>
  <?php endif; ?>

  <div class="mb-3">
    <label class="form-label">
      <?= isset($libro) ? 'Reemplazar portada (opcional)' : 'Portada' ?>
    </label>
    <input type="file" name="imagen" class="form-control">
  </div>

  <button type="submit" class="btn btn-primary">Guardar</button>
  <a href="index.php?accion=listar" class="btn btn-link">Cancelar</a>
</form>

<?php require __DIR__ . '/../footer.php'; ?>
