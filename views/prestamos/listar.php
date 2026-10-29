<?php require __DIR__ . '/../header.php'; ?>

<div class="encabezado">
  <h1 class="h3"><?= htmlspecialchars($titulo) ?></h1>
  <?php if (esAdmin()): ?>
    <a href="index.php?accion=formCrearPrestamo" class="btn btn-primary">+ Nuevo préstamo</a>
  <?php endif; ?>
</div>

<?php
// Filtros: pestañas de estado y buscador a la vista; socio (solo el admin), ítem y fechas en "Más filtros".
$campos = [];
if (esAdmin()) {
    $campos[] = ['nombre' => 'socio_id', 'etiqueta' => 'Socio', 'opciones' => $opcionesSocios];
}
$campos[] = ['nombre' => 'item_id', 'etiqueta' => 'Ítem', 'opciones' => $opcionesItems];
$campos[] = ['nombre' => 'desde', 'etiqueta' => 'Prestado desde', 'tipo' => 'date'];
$campos[] = ['nombre' => 'hasta', 'etiqueta' => 'Prestado hasta', 'tipo' => 'date'];

$barra = [
    'accion'   => 'listarPrestamos',
    'pestanas' => [
        'nombre'   => 'estado',
        'opciones' => ['' => 'Todos', 'activo' => 'Activos', 'vencido' => 'Vencidos', 'devuelto' => 'Devueltos'],
        'conteo'   => $conteo,
    ],
    'buscar'   => [
        'nombre'      => 'q',
        'placeholder' => esAdmin() ? 'Buscar por socio o ítem...' : 'Buscar por ítem...',
    ],
    'campos'   => $campos,
    'valores'  => $filtros,
];
require __DIR__ . '/../filtros.php';
?>

<?php if (empty($prestamos)): ?>
  <div class="card estado-vacio">
    <p class="titulo">No hay préstamos para mostrar.</p>
    <p class="small mb-0">Si filtraste, probá con otros filtros.</p>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <?php if (esAdmin()): ?><th>Socio</th><?php endif; ?>
            <th>Ítem</th>
            <th>Estado</th>
            <th>Fecha de préstamo</th>
            <th>Fecha límite</th>
            <th>Fecha de devolución</th>
            <?php if (esAdmin()): ?><th><span class="visually-hidden">Acciones</span></th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($prestamos as $p): ?>
            <tr class="<?= $p->estaActivo() ? '' : 'text-body-secondary' ?>">
              <?php if (esAdmin()): ?><td class="fw-medium"><?= htmlspecialchars($p->getSocioNombre()) ?></td><?php endif; ?>
              <td><?= htmlspecialchars($p->getItemTitulo()) ?></td>
              <td>
                <?php if ($p->estaVencido()): ?>
                  <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis">Vencido</span>
                <?php elseif ($p->estaActivo()): ?>
                  <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">Activo</span>
                <?php else: ?>
                  <span class="badge rounded-pill bg-success-subtle text-success-emphasis">Devuelto</span>
                <?php endif; ?>
              </td>
              <td class="text-nowrap"><?= htmlspecialchars(formatoFecha($p->getFechaPrestamo())) ?></td>
              <td class="text-nowrap <?= $p->estaVencido() ? 'text-danger-emphasis fw-medium' : '' ?>"><?= htmlspecialchars(formatoFecha($p->getFechaLimite())) ?></td>
              <td class="text-nowrap"><?= htmlspecialchars(formatoFecha($p->getFechaDevolucion())) ?></td>
              <?php if (esAdmin()): ?>
                <td class="text-end">
                  <?php if ($p->estaActivo()): ?>
                    <form method="POST" action="index.php?accion=devolverPrestamo" class="d-inline"
                      data-confirmar="¿Registrar la devolución de «<?= htmlspecialchars($p->getItemTitulo()) ?>»? Queda con fecha de hoy."
                      data-boton="Registrar devolución" data-color="primary">
                      <?= campoCsrf() ?>
                      <input type="hidden" name="id" value="<?= $p->getId() ?>">
                      <?php // Mandamos también los filtros y la página actuales, para volver a la lista tal como estaba. ?>
                      <?php foreach ($filtros as $clave => $valor): ?>
                        <?php if ($valor !== ''): ?>
                          <input type="hidden" name="<?= htmlspecialchars($clave) ?>" value="<?= htmlspecialchars($valor) ?>">
                        <?php endif; ?>
                      <?php endforeach; ?>
                      <input type="hidden" name="p" value="<?= htmlspecialchars($resultado['pagina']) ?>">
                      <button type="submit" class="btn btn-sm btn-outline-secondary">Devolver</button>
                    </form>
                  <?php endif; ?>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../paginador.php'; ?>

<?php require __DIR__ . '/../footer.php'; ?>
