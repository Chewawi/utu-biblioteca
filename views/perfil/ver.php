<?php
$tituloPagina = 'Mi perfil';
require __DIR__ . '/../header.php';

// Si el formulario vuelve por un error, mostramos lo que se escribió ($_POST).
$direccion = $_POST['direccion'] ?? ($socio ? $socio->getDireccion() : '');
$telefono  = $_POST['telefono'] ?? ($socio ? $socio->getTelefono() : '');
?>

<div class="encabezado">
  <div>
    <h1 class="h3">Mi perfil</h1>
    <p class="text-body-secondary small mb-0 mt-1">
      <?= $socio ? 'N° de socio ' . numeroSocio($socio->getId()) : 'Administrador' ?>
    </p>
  </div>
</div>

<div class="card ancho-formulario mb-4">
  <div class="card-body p-4">
    <h2 class="h5 mb-3">Mis datos</h2>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php?accion=editarPerfil" enctype="multipart/form-data">
      <?= campoCsrf() ?>

      <div class="mb-3">
        <label class="form-label d-block" for="foto">Foto de perfil</label>
        <div class="d-flex align-items-center gap-3">
          <img id="vista-previa" src="uploads/<?= htmlspecialchars($usuario->getFoto()) ?>" alt="Tu foto de perfil"
            width="96" height="96" class="avatar">
          <div class="flex-grow-1">
            <input type="file" name="foto" id="foto" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            <div class="form-text">JPG, PNG o WEBP. Máximo 2&nbsp;MB.</div>
          </div>
        </div>
      </div>

      <?php if ($socio): ?>
        <div class="mb-3">
          <label class="form-label" for="nombre">Nombre</label>
          <input type="text" id="nombre" class="form-control-plaintext" readonly value="<?= htmlspecialchars($socio->getNombre()) ?>">
        </div>
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label" for="email">Correo</label>
        <input type="text" id="email" class="form-control-plaintext" readonly value="<?= htmlspecialchars($usuario->getEmail()) ?>">
        <div class="form-text">
          <?= $socio ? 'Si hay que corregir tu nombre o tu correo, pedíselo a la biblioteca.' : 'Tu correo lo cambiás desde Usuarios.' ?>
        </div>
      </div>

      <?php if ($socio): ?>
        <div class="mb-3">
          <label class="form-label" for="telefono">Teléfono</label>
          <input type="tel" id="telefono" name="telefono" class="form-control" required maxlength="20" value="<?= htmlspecialchars($telefono) ?>">
          <div class="form-text">Por ejemplo, 099 123 456 o 2900 1234.</div>
        </div>

        <div class="mb-3">
          <label class="form-label" for="direccion">Dirección</label>
          <input type="text" id="direccion" name="direccion" class="form-control" required maxlength="255" value="<?= htmlspecialchars($direccion) ?>">
        </div>
      <?php endif; ?>

      <div class="pt-2">
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<div class="card ancho-formulario">
  <div class="card-body p-4">
    <h2 class="h5 mb-3">Cambiar contraseña</h2>

    <?php if ($errorPass): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($errorPass) ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php?accion=cambiarPass">
      <?= campoCsrf() ?>
      <?php // autocomplete: así el navegador sabe cuál es la actual y cuál la nueva, para el gestor de contraseñas. ?>
      <div class="mb-3">
        <label class="form-label" for="pass_actual">Contraseña actual</label>
        <input type="password" id="pass_actual" name="pass_actual" class="form-control" required autocomplete="current-password">
      </div>

      <div class="mb-3">
        <label class="form-label" for="pass_nueva">Contraseña nueva</label>
        <input type="password" id="pass_nueva" name="pass_nueva" class="form-control" required minlength="6" autocomplete="new-password">
        <div class="form-text">Al menos 6 caracteres.</div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="pass_repetida">Repetí la contraseña nueva</label>
        <input type="password" id="pass_repetida" name="pass_repetida" class="form-control" required minlength="6" autocomplete="new-password">
      </div>

      <div class="pt-2">
        <button type="submit" class="btn btn-primary">Cambiar contraseña</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Vista previa: al elegir un archivo se muestra en el círculo, antes de guardar.
  document.getElementById('foto').addEventListener('change', function () {
    if (this.files.length > 0) {
      document.getElementById('vista-previa').src = URL.createObjectURL(this.files[0]);
    }
  });
</script>

<?php require __DIR__ . '/../footer.php'; ?>
