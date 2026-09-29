<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bibliodeko</title>
  <link rel="icon" type="image/x-icon" href="views/imagenes/favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
  <nav class="navbar navbar-expand-md navbar-dark bg-dark mb-4">
    <div class="container">
      
      <a class="navbar-brand" href="index.php?accion=listarLibros">
        <img src="views/imagenes/favicon.ico" alt="Logo" height="24" class="d-inline-block align-text-top pr-4">
        Bibliodeko
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item"><a class="nav-link" href="index.php?accion=listarLibros">Libros</a></li>
          <?php if (isset($_SESSION['usuario'])): ?>
            <li class="nav-item"><a class="nav-link" href="index.php?accion=listarPrestamos"><?= esAdmin() ? 'Préstamos' : 'Mis préstamos' ?></a></li>
          <?php endif; ?>
          <?php if (esAdmin()): ?>
            <li class="nav-item"><a class="nav-link" href="index.php?accion=listarSocios">Socios</a></li>
            <li class="nav-item"><a class="nav-link" href="index.php?accion=listarUsuarios">Usuarios</a></li>
          <?php endif; ?>
        </ul>
        <ul class="navbar-nav">
          <?php if (isset($_SESSION['usuario'])): ?>
            <li class="nav-item d-flex align-items-center">
              <span class="navbar-text text-light me-3">
                <?= htmlspecialchars($_SESSION['usuario']['email']) ?>
                <span class="badge text-bg-secondary"><?= htmlspecialchars($_SESSION['usuario']['rol']) ?></span>
              </span>
            </li>
            <li class="nav-item"><a class="nav-link" href="index.php?accion=logout">Salir</a></li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="index.php?accion=registro">Crear cuenta</a></li>
            <li class="nav-item"><a class="nav-link" href="index.php?accion=login">Iniciar sesión</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>
  <div class="container pb-5">
    <?php mostrarFlash() ?>