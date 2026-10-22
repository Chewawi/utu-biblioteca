<?php require __DIR__ . '/../header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Catálogo</h1>
  <?php if (esAdmin()): ?>
    <a href="index.php?accion=formCrearItem" class="btn btn-primary">+ Nuevo ítem</a>
  <?php endif; ?>
</div>

<form method="GET" action="index.php" class="row g-2 mb-4">
  <input type="hidden" name="accion" value="listarItems">
  <div class="col-auto flex-grow-1">
    <input type="text" name="q" class="form-control" placeholder="Buscar por título o autor..."
      value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
  </div>
  <div class="col-auto">
    <button class="btn btn-outline-secondary" type="submit">Buscar</button>
    <?php if (!empty($_GET['q'])): ?>
      <a href="index.php?accion=listarItems" class="btn btn-link">Limpiar</a>
    <?php endif; ?>
  </div>
</form>

<?php if (empty($items)): ?>
  <div class="alert alert-secondary">No se encontraron ítems.</div>

<?php elseif (esAdmin()): ?>
  <!-- Vista admin: tabla, para gestionar rápido -->
  <table class="table align-middle">
    <thead>
      <tr>
        <th></th>
        <th>Tipo</th>
        <th>Título</th>
        <th>Autor</th>
        <th>Categoría</th>
        <th>Disponibles</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><img src="uploads/<?= htmlspecialchars($item->getImagen()) ?>" width="48" class="rounded"></td>
          <td><?= htmlspecialchars(ucfirst($item->getTipo())) ?></td>
          <td><?= htmlspecialchars($item->getTitulo()) ?></td>
          <td><?= htmlspecialchars($item->getAutor()) ?></td>
          <td><?= htmlspecialchars($item->getCategoria() ?? '—') ?></td>
          <td><?= htmlspecialchars($item->getDisponibles()) ?> de <?= htmlspecialchars($item->getStock()) ?></td>
          <td class="text-end">
            <a href="index.php?accion=formEditarItem&id=<?= $item->getId() ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
            <form method="POST" action="index.php?accion=eliminarItem" class="d-inline"
              onsubmit="return confirm('¿Eliminar «<?= htmlspecialchars(addslashes($item->getTitulo())) ?>»?');">
              <input type="hidden" name="id" value="<?= $item->getId() ?>">
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
    <?php foreach ($items as $item): ?>
      <div class="col">
        <div class="card h-100 shadow-sm">
          <img src="uploads/<?= htmlspecialchars($item->getImagen()) ?>" class="card-img-top" style="height:220px; object-fit:cover;">
          <div class="card-body">
            <h6 class="card-title mb-1"><?= htmlspecialchars($item->getTitulo()) ?></h6>
            <p class="card-text text-muted small mb-2"><?= htmlspecialchars($item->getAutor()) ?></p>
            <span class="badge text-bg-secondary"><?= htmlspecialchars(ucfirst($item->getTipo())) ?></span>
            <?php if ($item->getCategoria()): ?>
              <span class="badge text-bg-light border"><?= htmlspecialchars($item->getCategoria()) ?></span>
            <?php endif; ?>
          </div>
          <div class="card-footer small">
            <?php if ($item->getDisponibles() > 0): ?>
              <span class="text-success"><?= htmlspecialchars($item->getDisponibles()) ?> de <?= htmlspecialchars($item->getStock()) ?> disponibles</span>
            <?php else: ?>
              <span class="text-danger">Sin ejemplares disponibles</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../paginador.php'; ?>

<?php require __DIR__ . '/../footer.php'; ?>
