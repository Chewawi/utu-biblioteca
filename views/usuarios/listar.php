<?php require __DIR__ . '/../header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Usuarios</h1>
  <a href="index.php?accion=formCrearUsuario" class="btn btn-primary">+ Nuevo usuario</a>
</div>

<table class="table align-middle">
  <thead>
    <tr><th>Email</th><th>Rol</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($usuarios as $usuario): ?>
      <tr>
        <td><?= htmlspecialchars($usuario->getEmail()) ?></td>
        <td><span class="badge text-bg-secondary"><?= htmlspecialchars($usuario->getRol()) ?></span></td>
        <td class="text-end">
          <a href="index.php?accion=formEditarUsuario&id=<?= $usuario->getId() ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
          <?php if ($usuario->getId() != $_SESSION['usuario']['id']): ?>
            <form method="POST" action="index.php?accion=eliminarUsuario" class="d-inline"
              onsubmit="return confirm('¿Eliminar el usuario «<?= htmlspecialchars(addslashes($usuario->getEmail())) ?>»?');">
              <input type="hidden" name="id" value="<?= $usuario->getId() ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php require __DIR__ . '/../footer.php'; ?>
