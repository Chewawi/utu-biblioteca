<?php require __DIR__ . '/../header.php'; ?>

<div class="encabezado">
  <h1 class="h3">Usuarios</h1>
  <a href="index.php?accion=formCrearUsuario" class="btn btn-primary">+ Nuevo usuario</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr><th>Email</th><th>Rol</th><th><span class="visually-hidden">Acciones</span></th></tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $usuario): ?>
          <tr>
            <td class="fw-medium"><?= htmlspecialchars($usuario->getEmail()) ?></td>
            <td><span class="badge etiqueta"><?= htmlspecialchars($usuario->getRol()) ?></span></td>
            <td class="text-end text-nowrap">
              <a href="index.php?accion=formEditarUsuario&id=<?= $usuario->getId() ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
              <?php if ($usuario->getId() != $_SESSION['usuario']['id']): ?>
                <form method="POST" action="index.php?accion=eliminarUsuario" class="d-inline"
                  data-confirmar="¿Eliminar el usuario «<?= htmlspecialchars($usuario->getEmail()) ?>»? No se puede deshacer."
                  data-boton="Eliminar" data-color="danger">
                  <?= campoCsrf() ?>
                  <input type="hidden" name="id" value="<?= $usuario->getId() ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
