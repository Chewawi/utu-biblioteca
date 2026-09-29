<?php require __DIR__ . '/../header.php'; ?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <h1 class="h3 mb-3 text-center">Crear cuenta</h1>
    <p class="text-muted text-center">Te registrás como socio y ya podés pedir libros prestados.</p>

    <?php if (isset($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php?accion=registrar">
      <div class="mb-3">
        <label class="form-label">Nombre completo</label>
        <input type="text" name="nombre" class="form-control" required
          value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Dirección</label>
        <input type="text" name="direccion" class="form-control" required
          value="<?= htmlspecialchars($_POST['direccion'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Teléfono</label>
        <input type="text" name="telefono" class="form-control" required
          value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Correo electrónico</label>
        <input type="email" name="email" class="form-control" required
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <input type="password" name="pass" class="form-control" required minlength="6">
        <div class="form-text">Mínimo 6 caracteres.</div>
      </div>
      <button type="submit" class="btn btn-primary w-100">Crear cuenta</button>
    </form>

    <p class="text-center mt-3">
      ¿Ya tenés cuenta? <a href="index.php?accion=login">Iniciá sesión</a>.
    </p>
  </div>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
