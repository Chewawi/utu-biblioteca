<?php require __DIR__ . '/../header.php'; ?>

<h1 class="h3 mb-3"><?= isset($usuario) ? 'Editar usuario' : 'Nuevo usuario' ?></h1>

<?php if (isset($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="index.php?accion=<?= isset($usuario) ? 'editarUsuario' : 'crearUsuario' ?>" class="col-md-6">
  <?php if (isset($usuario)): ?>
    <input type="hidden" name="id" value="<?= $usuario->getId() ?>">
  <?php endif; ?>

  <div class="mb-3">
    <label class="form-label">Correo electrónico</label>
    <input type="email" name="email" class="form-control" required
      value="<?= isset($usuario) ? htmlspecialchars($usuario->getEmail()) : htmlspecialchars($_POST['email'] ?? '') ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Rol</label>
    <select name="rol" class="form-select">
      <?php $rolActual = isset($usuario) ? $usuario->getRol() : ($_POST['rol'] ?? 'socio'); ?>
      <option value="socio" <?= $rolActual === 'socio' ? 'selected' : '' ?>>Socio</option>
      <option value="admin" <?= $rolActual === 'admin' ? 'selected' : '' ?>>Administrador</option>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">
      <?= isset($usuario) ? 'Nueva contraseña (dejar en blanco para no cambiarla)' : 'Contraseña' ?>
    </label>
    <input type="password" name="pass" class="form-control" <?= isset($usuario) ? '' : 'required' ?> minlength="6">
  </div>

  <button type="submit" class="btn btn-primary">Guardar</button>
  <a href="index.php?accion=listarUsuarios" class="btn btn-link">Cancelar</a>
</form>

<?php require __DIR__ . '/../footer.php'; ?>
