<?php require __DIR__ . '/../header.php'; ?>

<div class="encabezado">
  <h1 class="h3"><?= htmlspecialchars($titulo) ?></h1>
  <?php if (esAdmin()): ?>
    <div class="d-flex flex-wrap gap-2">
      <?php // Sin correo configurado el botón no aparece: no tendría nada que hacer. ?>
      <?php if (correoConfigurado()): ?>
        <form method="POST" action="index.php?accion=avisarVencidos"
          data-confirmar="¿Mandar un recordatorio por correo a cada socio con préstamos vencidos?"
          data-boton="Avisar" data-color="primary">
          <?= campoCsrf() ?>
          <button type="submit" class="btn btn-outline-secondary">Avisar vencidos</button>
        </form>
      <?php endif; ?>
      <a href="index.php?accion=formCrearPrestamo" class="btn btn-primary">+ Nuevo préstamo</a>
    </div>
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
    <?php // array_filter saca los filtros vacíos: si queda alguno, el usuario filtró. ?>
    <?php if (!empty(array_filter($filtros))): ?>
      <p class="titulo">No hay préstamos para mostrar.</p>
      <p class="small mb-0">Probá con otra pestaña u otros filtros.</p>
    <?php elseif (esAdmin()): ?>
      <p class="titulo">Todavía no hay préstamos.</p>
      <p class="small mb-0">Registrá el primero con «Nuevo préstamo».</p>
    <?php else: ?>
      <p class="titulo">Todavía no tenés préstamos.</p>
      <p class="small mb-0">Cuando te lleves algo de la biblioteca, va a aparecer acá.</p>
    <?php endif; ?>
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
                      <?php // También los filtros y la página actuales, para volver a la lista tal como estaba. ?>
                      <?= camposOcultos(array_merge($filtros, ['p' => $resultado['pagina']])) ?>
                      <button type="submit" class="btn btn-sm btn-outline-secondary">Devolver<span class="visually-hidden"> «<?= htmlspecialchars($p->getItemTitulo()) ?>»</span></button>
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
