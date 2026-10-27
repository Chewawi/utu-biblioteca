<?php
// Para marcar en la barra la sección en la que estamos.
$accionActual = $_GET['accion'] ?? 'listarItems';
$enCatalogo   = in_array($accionActual, ['listarItems', 'formCrearItem', 'formEditarItem', 'crearItem', 'editarItem']);
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bibliodeko</title>
  <script>
    // Tema claro u oscuro: el que eligió el usuario (queda guardado en el navegador)
    // o, si nunca eligió, el del sistema. Va acá arriba para que la página no
    // aparezca un instante con el tema equivocado.
    var temaGuardado = localStorage.getItem('tema');
    var temaDelSistema = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    document.documentElement.setAttribute('data-bs-theme', temaGuardado || temaDelSistema);
  </script>
  <link rel="icon" type="image/x-icon" href="views/imagenes/favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
  <link href="assets/css/estilo.css" rel="stylesheet">
</head>

<body>
  <nav class="navbar navbar-expand-md barra-principal sticky-top mb-4">
    <div class="container">

      <a class="navbar-brand d-flex align-items-center gap-2" href="index.php?accion=listarItems">
        <img src="views/imagenes/favicon.ico" alt="" height="24">
        Bibliodeko
      </a>

      <?php // El botón de tema queda afuera del menú plegable, así en el celular siempre está a mano. ?>
      <div class="d-flex align-items-center gap-1 order-md-last ms-auto ms-md-3">
        <button type="button" class="btn boton-tema" id="cambiar-tema" title="Cambiar entre tema claro y oscuro" aria-label="Cambiar entre tema claro y oscuro">
          <svg class="icono-luna" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z" />
          </svg>
          <svg class="icono-sol" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
          </svg>
        </button>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Abrir menú">
          <span class="navbar-toggler-icon"></span>
        </button>
      </div>

      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= $enCatalogo ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Catálogo</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="index.php?accion=listarItems">Todos</a></li>
              <?php foreach (Item::PLURALES as $tipoMenu => $nombreMenu): ?>
                <li><a class="dropdown-item" href="index.php?accion=listarItems&amp;tipo=<?= htmlspecialchars($tipoMenu) ?>"><?= htmlspecialchars($nombreMenu) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </li>
          <?php if (isset($_SESSION['usuario'])): ?>
            <li class="nav-item"><a class="nav-link <?= $accionActual === 'listarPrestamos' ? 'active' : '' ?>" href="index.php?accion=listarPrestamos"><?= esAdmin() ? 'Préstamos' : 'Mis préstamos' ?></a></li>
          <?php endif; ?>
          <?php if (esAdmin()): ?>
            <li class="nav-item"><a class="nav-link <?= $accionActual === 'listarSocios' ? 'active' : '' ?>" href="index.php?accion=listarSocios">Socios</a></li>
            <li class="nav-item"><a class="nav-link <?= $accionActual === 'listarUsuarios' ? 'active' : '' ?>" href="index.php?accion=listarUsuarios">Usuarios</a></li>
          <?php endif; ?>
        </ul>
        <ul class="navbar-nav align-items-md-center">
          <?php if (isset($_SESSION['usuario'])): ?>
            <li class="nav-item">
              <span class="navbar-text d-inline-flex align-items-center gap-2 me-md-3 small">
                <?= htmlspecialchars($_SESSION['usuario']['email']) ?>
                <span class="badge etiqueta"><?= htmlspecialchars($_SESSION['usuario']['rol']) ?></span>
              </span>
            </li>
            <li class="nav-item"><a class="nav-link" href="index.php?accion=logout">Salir</a></li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link <?= $accionActual === 'login' ? 'active' : '' ?>" href="index.php?accion=login">Iniciar sesión</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>
  <main class="container pb-5">
    <?php mostrarFlash() ?>
