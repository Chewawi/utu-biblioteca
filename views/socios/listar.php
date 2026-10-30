<?php
$tituloPagina = 'Socios';
require __DIR__ . '/../header.php';
?>

<div class="encabezado">
  <h1 class="h3">Socios</h1>
  <a href="index.php?accion=formCrearSocio" class="btn btn-primary">+ Nuevo socio</a>
</div>

<form method="GET" action="index.php" class="d-flex align-items-center gap-2 mb-4">
  <input type="hidden" name="accion" value="listarSocios">
  <div class="input-group">
    <input type="search" name="q" class="form-control" aria-label="Buscar"
      placeholder="Buscar por nombre, teléfono o email..."
      value="<?= htmlspecialchars($filtros['q']) ?>">
    <button class="btn btn-outline-secondary" type="submit">Buscar</button>
  </div>
  <?php if ($filtros['q'] !== ''): ?>
    <a href="index.php?accion=listarSocios" class="btn btn-link">Limpiar</a>
  <?php endif; ?>
</form>

<?php if (empty($socios)): ?>
  <div class="card estado-vacio">
    <?php if ($filtros['q'] !== ''): ?>
      <p class="titulo">Ningún socio coincide con la búsqueda.</p>
      <p class="small mb-0">Probá con otro nombre, teléfono o email.</p>
    <?php else: ?>
      <p class="titulo">No hay socios registrados todavía.</p>
      <p class="small mb-0">Agregá el primero con «Nuevo socio».</p>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th><span class="visually-hidden">Foto</span></th>
            <th>Nombre</th>
            <th>Dirección</th>
            <th>Teléfono</th>
            <th>Email</th>
            <th><span class="visually-hidden">Acciones</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($socios as $socio): ?>
            <tr>
              <td class="columna-avatar">
                <img src="uploads/<?= htmlspecialchars($socio->getFoto()) ?>" alt="" width="40" height="40"
                  class="avatar">
              </td>
              <td class="fw-medium"><?= htmlspecialchars($socio->getNombre()) ?></td>
              <td class="text-body-secondary"><?= htmlspecialchars($socio->getDireccion()) ?></td>
              <td class="text-body-secondary text-nowrap"><?= htmlspecialchars($socio->getTelefono()) ?></td>
              <td class="text-body-secondary"><?= htmlspecialchars($socio->getUsuario()->getEmail()) ?></td>
              <td class="text-end text-nowrap">
                <a href="index.php?accion=formEditarSocio&id=<?= $socio->getId() ?>" class="btn btn-sm btn-outline-secondary">Editar<span class="visually-hidden"> a <?= htmlspecialchars($socio->getNombre()) ?></span></a>
                <form method="POST" action="index.php?accion=eliminarSocio" class="d-inline"
                  data-confirmar="¿Eliminar al socio «<?= htmlspecialchars($socio->getNombre()) ?>»? No se puede deshacer."
                  data-boton="Eliminar" data-color="danger">
                  <?= campoCsrf() ?>
                  <input type="hidden" name="id" value="<?= $socio->getId() ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar<span class="visually-hidden"> a <?= htmlspecialchars($socio->getNombre()) ?></span></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../paginador.php'; ?>

<?php require __DIR__ . '/../footer.php'; ?>
