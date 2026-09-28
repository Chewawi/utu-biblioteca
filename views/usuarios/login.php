<?php require __DIR__ . '/../header.php'; ?>

<h1 class="h3 mb-3">Iniciar sesión</h1>

<form method="POST" action="index.php?a=login" class="col-md-6">
  <div class="mb-3">
    <label class="form-label">Correo electrónico</label>
    <input type="email" name="email" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Contraseña</label>
    <input type="password" name="pass" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-primary">Iniciar sesión</button>
</form>

<?php require __DIR__ . '/../footer.php'; ?>
