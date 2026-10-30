<?php require __DIR__ . '/../header.php'; ?>

<div class="encabezado">
  <h1 class="h3"><?= isset($usuario) ? 'Editar usuario' : 'Nuevo usuario' ?></h1>
</div>

<?php if (isset($error)): ?>
  <div class="alert alert-danger ancho-formulario"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card ancho-formulario">
  <div class="card-body p-4">
    <form method="POST" action="index.php?accion=<?= isset($usuario) ? 'editarUsuario' : 'crearUsuario' ?>">
      <?= campoCsrf() ?>
      <?php if (isset($usuario)): ?>
        <input type="hidden" name="id" value="<?= $usuario->getId() ?>">
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label" for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" class="form-control" required maxlength="100"
          value="<?= isset($usuario) ? htmlspecialchars($usuario->getEmail()) : htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label" for="rol">Rol</label>
        <select id="rol" name="rol" class="form-select">
          <?php $rolActual = isset($usuario) ? $usuario->getRol() : ($_POST['rol'] ?? 'socio'); ?>
          <option value="socio" <?= $rolActual === 'socio' ? 'selected' : '' ?>>Socio</option>
          <option value="admin" <?= $rolActual === 'admin' ? 'selected' : '' ?>>Administrador</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label" for="pass">
          <?= isset($usuario) ? 'Nueva contraseña (dejar en blanco para no cambiarla)' : 'Contraseña' ?>
        </label>
        <input type="password" id="pass" name="pass" class="form-control" <?= isset($usuario) ? '' : 'required' ?> minlength="6">
      </div>

      <div class="d-flex gap-2 pt-2">
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="index.php?accion=listarUsuarios" class="btn btn-link">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
