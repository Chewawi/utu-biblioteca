<?php require __DIR__ . '/../header.php'; ?>

<h1 class="h3 mb-3"><?= isset($socio) ? 'Editar socio' : 'Nuevo socio' ?></h1>

<?php if (isset($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php
// Si el formulario vuelve por un error, mostramos lo que se escribió ($_POST);
// si no, los datos guardados del socio (al editar) o vacío (al crear).
$usuarioElegido = $_POST['usuario_id'] ?? (isset($socio) && $socio->getUsuario() ? $socio->getUsuario()->getId() : '');
$nombre    = $_POST['nombre'] ?? (isset($socio) ? $socio->getNombre() : '');
$direccion = $_POST['direccion'] ?? (isset($socio) ? $socio->getDireccion() : '');
$telefono  = $_POST['telefono'] ?? (isset($socio) ? $socio->getTelefono() : '');
$fotoActual = isset($socio) ? $socio->getFoto() : AVATAR_POR_DEFECTO;
?>

<form method="POST" action="index.php?accion=<?= isset($socio) ? 'editarSocio' : 'crearSocio' ?>"
  enctype="multipart/form-data" class="col-md-6">
  <?= campoCsrf() ?>
  <?php if (isset($socio)): ?>
    <input type="hidden" name="id" value="<?= $socio->getId() ?>">
  <?php endif; ?>

  <div class="mb-3">
    <label class="form-label">Usuario asociado</label>
    <select name="usuario_id" class="form-select" required>
      <option value="">-- Elegir usuario --</option>
      <?php foreach ($usuariosDisponibles as $u): ?>
        <option value="<?= $u->getId() ?>" <?= $u->getId() == $usuarioElegido ? 'selected' : '' ?>>
          <?= htmlspecialchars($u->getEmail()) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <div class="form-text">Solo usuarios con rol "socio" que todavía no tienen un socio asociado.</div>
  </div>

  <div class="mb-3">
    <label class="form-label">Nombre</label>
    <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($nombre) ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Dirección</label>
    <input type="text" name="direccion" class="form-control" required value="<?= htmlspecialchars($direccion) ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Teléfono</label>
    <input type="text" name="telefono" class="form-control" required value="<?= htmlspecialchars($telefono) ?>">
  </div>

  <div class="mb-3">
    <label class="form-label d-block"><?= isset($socio) ? 'Reemplazar foto (opcional)' : 'Foto (opcional)' ?></label>
    <div class="d-flex align-items-center gap-3">
      <img id="vista-previa" src="uploads/<?= htmlspecialchars($fotoActual) ?>" alt="Foto del socio"
        width="96" height="96" class="avatar">
      <div class="flex-grow-1">
        <input type="file" name="foto" id="foto" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        <div class="form-text">JPG, PNG o WEBP. Máximo 2&nbsp;MB.</div>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Guardar</button>
  <a href="index.php?accion=listarSocios" class="btn btn-link">Cancelar</a>
</form>

<script>
  // Vista previa: al elegir un archivo se muestra en el círculo, antes de guardar.
  document.getElementById('foto').addEventListener('change', function () {
    if (this.files.length > 0) {
      document.getElementById('vista-previa').src = URL.createObjectURL(this.files[0]);
    }
  });
</script>

<?php require __DIR__ . '/../footer.php'; ?>
