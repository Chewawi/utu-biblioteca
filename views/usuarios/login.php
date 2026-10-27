<?php require __DIR__ . '/../header.php'; ?>

<div class="card card-login">
  <div class="card-body p-4 p-sm-5">
    <div class="text-center mb-4">
      <img src="views/imagenes/favicon.ico" alt="" height="32" class="mb-3">
      <h1 class="h4 mb-1">Iniciar sesión</h1>
      <p class="text-body-secondary small mb-0">Entrá con tu cuenta de Bibliodeko.</p>
    </div>

    <?php if (isset($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php?accion=auth">
      <?= campoCsrf() ?>
      <div class="mb-3">
        <label class="form-label">Correo electrónico</label>
        <input type="email" name="email" class="form-control" required autofocus autocomplete="username">
      </div>
      <div class="mb-4">
        <label class="form-label">Contraseña</label>
        <input type="password" name="pass" class="form-control" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-primary w-100">Iniciar sesión</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
