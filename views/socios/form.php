<?php
$tituloPagina = isset($socio) ? 'Editar socio' : 'Nuevo socio';
require __DIR__ . '/../header.php';
?>

<div class="encabezado">
  <div>
    <h1 class="h3"><?= htmlspecialchars($tituloPagina) ?></h1>
    <p class="text-body-secondary small mb-0 mt-1">
      <?php // El N° de socio lo pone la base al crearlo y no se edita. ?>
      <?= isset($socio) ? 'N° de socio ' . numeroSocio($socio->getId()) : 'El N° de socio se asigna solo al guardar.' ?>
    </p>
  </div>
</div>

<?php if (isset($error)): ?>
  <div class="alert alert-danger ancho-formulario"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php
// Si el formulario vuelve por un error, mostramos lo que se escribió ($_POST);
// si no, los datos guardados del socio (al editar) o vacío (al crear).
$usuarioElegido = $_POST['usuario_id'] ?? (isset($socio) ? $socio->getUsuario()->getId() : '');
$nombre    = $_POST['nombre'] ?? (isset($socio) ? $socio->getNombre() : '');
$email     = $_POST['email'] ?? (isset($socio) ? $socio->getUsuario()->getEmail() : '');
$direccion = $_POST['direccion'] ?? (isset($socio) ? $socio->getDireccion() : '');
$telefono  = $_POST['telefono'] ?? (isset($socio) ? $socio->getTelefono() : '');
$fotoActual = isset($socio) ? $socio->getUsuario()->getFoto() : AVATAR_POR_DEFECTO;
?>

<div class="card ancho-formulario">
  <div class="card-body p-4">
    <form method="POST" action="index.php?accion=<?= isset($socio) ? 'editarSocio' : 'crearSocio' ?>"
      enctype="multipart/form-data">
      <?= campoCsrf() ?>
      <?php if (isset($socio)): ?>
        <input type="hidden" name="id" value="<?= $socio->getId() ?>">
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label" for="usuario_id">Usuario asociado</label>
        <select id="usuario_id" name="usuario_id" class="form-select" required>
          <option value="">-- Elegir usuario --</option>
          <?php foreach ($usuariosDisponibles as $u): ?>
            <?php // data-email y data-foto: el script de abajo los copia al elegir el usuario. ?>
            <option value="<?= $u->getId() ?>" <?= $u->getId() == $usuarioElegido ? 'selected' : '' ?>
              data-email="<?= htmlspecialchars($u->getEmail()) ?>" data-foto="<?= htmlspecialchars($u->getFoto()) ?>">
              <?= htmlspecialchars($u->getEmail()) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">Solo usuarios con rol "socio" que todavía no tienen un socio asociado.</div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" class="form-control" required maxlength="100" value="<?= htmlspecialchars($nombre) ?>">
      </div>

      <div class="mb-3">
        <label class="form-label" for="email">Correo</label>
        <input type="email" id="email" name="email" class="form-control" required maxlength="100" value="<?= htmlspecialchars($email) ?>">
        <div class="form-text">Es el de su cuenta: si lo cambiás, también cambia el email con el que inicia sesión.</div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="telefono">Teléfono</label>
        <input type="tel" id="telefono" name="telefono" class="form-control" required maxlength="20" value="<?= htmlspecialchars($telefono) ?>">
        <div class="form-text">Por ejemplo, 099 123 456 o 2900 1234.</div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="direccion">Dirección</label>
        <input type="text" id="direccion" name="direccion" class="form-control" required maxlength="255" value="<?= htmlspecialchars($direccion) ?>">
      </div>

      <div class="mb-3">
        <label class="form-label d-block" for="foto"><?= isset($socio) ? 'Reemplazar foto (opcional)' : 'Foto (opcional)' ?></label>
        <div class="d-flex align-items-center gap-3">
          <img id="vista-previa" src="uploads/<?= htmlspecialchars($fotoActual) ?>" alt="Foto del socio"
            width="96" height="96" class="avatar">
          <div class="flex-grow-1">
            <input type="file" name="foto" id="foto" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            <div class="form-text">JPG, PNG o WEBP. Máximo 2&nbsp;MB.</div>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2 pt-2">
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="index.php?accion=listarSocios" class="btn btn-link">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<script>
  // Al elegir el usuario se completan su correo y su foto actual, para revisarlos o cambiarlos.
  document.getElementById('usuario_id').addEventListener('change', function () {
    var opcion = this.options[this.selectedIndex];
    if (opcion.value !== '') {
      document.getElementById('email').value = opcion.dataset.email;
      if (document.getElementById('foto').files.length === 0) { // si ya eligieron una foto nueva, queda esa
        document.getElementById('vista-previa').src = 'uploads/' + opcion.dataset.foto;
      }
    }
  });

  // Vista previa: al elegir un archivo se muestra en el círculo, antes de guardar.
  document.getElementById('foto').addEventListener('change', function () {
    if (this.files.length > 0) {
      document.getElementById('vista-previa').src = URL.createObjectURL(this.files[0]);
    }
  });
</script>

<?php require __DIR__ . '/../footer.php'; ?>
