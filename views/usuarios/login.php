<?php require __DIR__ . '/../header.php'; ?>

<div class="row justify-content-center">
  <div class="col-md-5">
    <h1 class="h3 mb-3 text-center">Iniciar sesión</h1>

    <?php if (isset($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php?accion=auth">
      <div class="mb-3">
        <label class="form-label">Correo electrónico</label>
        <input type="email" name="email" class="form-control" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <input type="password" name="pass" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Iniciar sesión</button>
    </form>

    <p class="text-center mt-3">
      ¿No tenés cuenta? <a href="index.php?accion=registro">Creá una acá</a>.
    </p>

    <p class="text-muted small mt-3 text-center">
      Prueba con <code>admin@biblioteca.com</code> / <code>admin123</code>
      o <code>socio@biblioteca.com</code> / <code>socio123</code>.
    </p>
  </div>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
