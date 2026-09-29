<?php require __DIR__ . '/../header.php'; ?>

<h1 class="h3 mb-3"><?= isset($socio) ? 'Editar socio' : 'Nuevo socio' ?></h1>

<?php if (isset($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="index.php?accion=<?= isset($socio) ? 'editarSocio' : 'crearSocio' ?>" class="col-md-6">
  <?php if (isset($socio)): ?>
    <input type="hidden" name="id" value="<?= $socio->getId() ?>">
  <?php endif; ?>

  <div class="mb-3">
    <label class="form-label">Usuario asociado</label>
    <select name="usuario_id" class="form-select" required>
      <option value="">-- Elegir usuario --</option>
      <?php $usuarioActualId = isset($socio) && $socio->getUsuario() ? $socio->getUsuario()->getId() : ($_POST['usuario_id'] ?? null); ?>
      <?php foreach ($usuariosDisponibles as $u): ?>
        <option value="<?= $u->getId() ?>" <?= $u->getId() == $usuarioActualId ? 'selected' : '' ?>>
          <?= htmlspecialchars($u->getEmail()) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <div class="form-text">Solo usuarios con rol "socio" que todavía no tienen un socio asociado.</div>
  </div>

  <div class="mb-3">
    <label class="form-label">Nombre</label>
    <input type="text" name="nombre" class="form-control" required
      value="<?= isset($socio) ? htmlspecialchars($socio->getNombre()) : htmlspecialchars($_POST['nombre'] ?? '') ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Dirección</label>
    <input type="text" name="direccion" class="form-control" required
      value="<?= isset($socio) ? htmlspecialchars($socio->getDireccion()) : htmlspecialchars($_POST['direccion'] ?? '') ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Teléfono</label>
    <input type="text" name="telefono" class="form-control" required
      value="<?= isset($socio) ? htmlspecialchars($socio->getTelefono()) : htmlspecialchars($_POST['telefono'] ?? '') ?>">
  </div>

  <button type="submit" class="btn btn-primary">Guardar</button>
  <a href="index.php?accion=listarSocios" class="btn btn-link">Cancelar</a>
</form>

<?php require __DIR__ . '/../footer.php'; ?>
