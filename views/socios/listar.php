<?php require __DIR__ . '/../header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Socios</h1>
  <a href="index.php?accion=formCrearSocio" class="btn btn-primary">+ Nuevo socio</a>
</div>

<form method="GET" action="index.php" class="row g-2 align-items-end mb-4">
  <input type="hidden" name="accion" value="listarSocios">
  <div class="col-md-6">
    <label class="form-label small mb-1">Buscar</label>
    <input type="text" name="q" class="form-control" placeholder="Nombre, teléfono o email..."
      value="<?= htmlspecialchars($filtros['q']) ?>">
  </div>
  <div class="col-md-auto">
    <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
    <a href="index.php?accion=listarSocios" class="btn btn-link">Limpiar filtros</a>
  </div>
</form>

<?php if (empty($socios)): ?>
  <div class="alert alert-secondary">
    <?= $filtros['q'] !== '' ? 'Ningún socio coincide con la búsqueda.' : 'No hay socios registrados todavía.' ?>
  </div>
<?php else: ?>
  <table class="table align-middle">
    <thead>
      <tr><th></th><th>Nombre</th><th>Dirección</th><th>Teléfono</th><th>Email</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($socios as $socio): ?>
        <tr>
          <td style="width: 56px;">
            <img src="uploads/<?= htmlspecialchars($socio->getFoto()) ?>" alt="" width="40" height="40"
              class="rounded-circle border" style="object-fit: cover;">
          </td>
          <td><?= htmlspecialchars($socio->getNombre()) ?></td>
          <td><?= htmlspecialchars($socio->getDireccion()) ?></td>
          <td><?= htmlspecialchars($socio->getTelefono()) ?></td>
          <td><?= $socio->getUsuario() ? htmlspecialchars($socio->getUsuario()->getEmail()) : '<span class="text-muted">cuenta eliminada</span>' ?></td>
          <td class="text-end">
            <a href="index.php?accion=formEditarSocio&id=<?= $socio->getId() ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
            <form method="POST" action="index.php?accion=eliminarSocio" class="d-inline"
              onsubmit="return confirm('¿Eliminar al socio «<?= htmlspecialchars(addslashes($socio->getNombre())) ?>»?');">
              <input type="hidden" name="id" value="<?= $socio->getId() ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php require __DIR__ . '/../paginador.php'; ?>

<?php require __DIR__ . '/../footer.php'; ?>
