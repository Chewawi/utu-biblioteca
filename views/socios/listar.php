<?php require __DIR__ . '/../header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Socios</h1>
  <a href="index.php?accion=formCrearSocio" class="btn btn-primary">+ Nuevo socio</a>
</div>

<?php if (empty($socios)): ?>
  <div class="alert alert-secondary">No hay socios registrados todavía.</div>
<?php else: ?>
  <table class="table align-middle">
    <thead>
      <tr><th>Nombre</th><th>Dirección</th><th>Teléfono</th><th>Email</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($socios as $socio): ?>
        <tr>
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

<?php require __DIR__ . '/../footer.php'; ?>
