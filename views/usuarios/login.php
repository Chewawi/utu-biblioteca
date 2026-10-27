<?php require __DIR__ . '/../header.php'; ?>

<div class="row justify-content-center">
  <div class="col-md-5">
    <h1 class="h3 mb-3 text-center">Iniciar sesión</h1>

    <?php if (isset($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php?accion=auth">
      <?= campoCsrf() ?>
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

  </div>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
