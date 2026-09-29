<?php require __DIR__ . '/../header.php'; ?>

<h1 class="h3 mb-3"><?= isset($libro) ? 'Editar libro' : 'Nuevo libro' ?></h1>

<?php if (isset($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST"
  action="index.php?accion=<?= isset($libro) ? 'editarLibro' : 'crearLibro' ?>"
  enctype="multipart/form-data"
  class="col-md-6">

  <?php if (isset($libro)): ?>
    <input type="hidden" name="id" value="<?= $libro->getId() ?>">
  <?php endif; ?>

  <div class="mb-3">
    <label class="form-label">Título</label>
    <input type="text" name="titulo" class="form-control" required
      value="<?= isset($libro) ? htmlspecialchars($libro->getTitulo()) : htmlspecialchars($_POST['titulo'] ?? '') ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Autor</label>
    <input type="text" name="autor" class="form-control" required
      value="<?= isset($libro) ? htmlspecialchars($libro->getAutor()) : htmlspecialchars($_POST['autor'] ?? '') ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Categoría (opcional)</label>
    <input type="text" name="categoria" class="form-control" placeholder="Novela, Fantasía, Ensayo..."
      value="<?= isset($libro) ? htmlspecialchars($libro->getCategoria() ?? '') : htmlspecialchars($_POST['categoria'] ?? '') ?>">
  </div>

  <?php if (isset($libro)): ?>
    <div class="mb-2">
      <label class="form-label d-block">Portada actual</label>
      <img src="uploads/<?= htmlspecialchars($libro->getImagen()) ?>" width="80" class="rounded mb-2">
    </div>
  <?php endif; ?>

  <div class="mb-3">
    <label class="form-label">
      <?= isset($libro) ? 'Reemplazar portada (opcional)' : 'Portada (opcional)' ?>
    </label>
    <input type="file" name="imagen" class="form-control" accept=".jpg,.jpeg,.png,.webp">
    <div class="form-text">JPG, PNG o WEBP. Máximo 2&nbsp;MB.</div>
  </div>

  <button type="submit" class="btn btn-primary">Guardar</button>
  <a href="index.php?accion=listarLibros" class="btn btn-link">Cancelar</a>
</form>

<?php require __DIR__ . '/../footer.php'; ?>
