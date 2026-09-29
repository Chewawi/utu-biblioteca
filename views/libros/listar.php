<?php require __DIR__ . '/../header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Libros</h1>
  <?php if (esAdmin()): ?>
    <a href="index.php?accion=formCrearLibro" class="btn btn-primary">+ Nuevo libro</a>
  <?php endif; ?>
</div>

<form method="GET" action="index.php" class="row g-2 mb-4">
  <input type="hidden" name="accion" value="listarLibros">
  <div class="col-auto flex-grow-1">
    <input type="text" name="q" class="form-control" placeholder="Buscar por título o autor..."
      value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
  </div>
  <div class="col-auto">
    <button class="btn btn-outline-secondary" type="submit">Buscar</button>
    <?php if (!empty($_GET['q'])): ?>
      <a href="index.php?accion=listarLibros" class="btn btn-link">Limpiar</a>
    <?php endif; ?>
  </div>
</form>

<?php if (empty($libros)): ?>
  <div class="alert alert-secondary">No se encontraron libros.</div>

<?php elseif (esAdmin()): ?>
  <!-- Vista admin: tabla, para gestionar rápido -->
  <table class="table align-middle">
    <thead>
      <tr>
        <th></th>
        <th>Título</th>
        <th>Autor</th>
        <th>Categoría</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($libros as $libro): ?>
        <tr>
          <td><img src="uploads/<?= htmlspecialchars($libro->getImagen()) ?>" width="48" class="rounded"></td>
          <td><?= htmlspecialchars($libro->getTitulo()) ?></td>
          <td><?= htmlspecialchars($libro->getAutor()) ?></td>
          <td><?= htmlspecialchars($libro->getCategoria() ?? '—') ?></td>
          <td class="text-end">
            <a href="index.php?accion=formEditarLibro&id=<?= $libro->getId() ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
            <form method="POST" action="index.php?accion=eliminarLibro" class="d-inline"
              onsubmit="return confirm('¿Eliminar «<?= htmlspecialchars(addslashes($libro->getTitulo())) ?>»?');">
              <input type="hidden" name="id" value="<?= $libro->getId() ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

<?php else: ?>
  <!-- Vista pública: tarjetas -->
  <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
    <?php foreach ($libros as $libro): ?>
      <div class="col">
        <div class="card h-100 shadow-sm">
          <img src="uploads/<?= htmlspecialchars($libro->getImagen()) ?>" class="card-img-top" style="height:220px; object-fit:cover;">
          <div class="card-body">
            <h6 class="card-title mb-1"><?= htmlspecialchars($libro->getTitulo()) ?></h6>
            <p class="card-text text-muted small mb-2"><?= htmlspecialchars($libro->getAutor()) ?></p>
            <?php if ($libro->getCategoria()): ?>
              <span class="badge text-bg-light border"><?= htmlspecialchars($libro->getCategoria()) ?></span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../footer.php'; ?>
