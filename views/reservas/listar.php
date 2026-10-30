<?php require __DIR__ . '/../header.php'; ?>

<div class="encabezado">
  <div>
    <h1 class="h3"><?= htmlspecialchars($titulo) ?></h1>
    <?php if (!esAdmin()): ?>
      <p class="text-body-secondary small mb-0 mt-1">
        Cuando una reserva está lista, tenés <?= Reserva::DIAS_PARA_RETIRAR ?> días para retirarla.
        Podés tener hasta <?= Reserva::MAXIMO_ACTIVAS ?> reservas activas.
      </p>
    <?php endif; ?>
  </div>
</div>

<?php
// Filtros: pestañas de estado y buscador a la vista; socio e ítem (solo el admin) en "Más filtros".
$campos = [];
if (esAdmin()) {
    $campos[] = ['nombre' => 'socio_id', 'etiqueta' => 'Socio', 'opciones' => $opcionesSocios];
    $campos[] = ['nombre' => 'item_id', 'etiqueta' => 'Ítem', 'opciones' => $opcionesItems];
}

$barra = [
    'accion'   => 'listarReservas',
    'pestanas' => [
        'nombre'   => 'estado',
        'opciones' => ['' => 'Todas', 'lista' => 'Listas', 'pendiente' => 'En espera', 'vencida' => 'Vencidas', 'retirada' => 'Retiradas', 'cancelada' => 'Canceladas'],
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

<?php if (empty($reservas)): ?>
  <div class="card estado-vacio">
    <?php // array_filter saca los filtros vacíos: si queda alguno, el usuario filtró. ?>
    <?php if (!empty(array_filter($filtros))): ?>
      <p class="titulo">No hay reservas para mostrar.</p>
      <p class="small mb-0">Probá con otra pestaña u otra búsqueda.</p>
    <?php elseif (esAdmin()): ?>
      <p class="titulo">Todavía no hay reservas.</p>
      <p class="small mb-0">Los socios reservan desde el catálogo.</p>
    <?php else: ?>
      <p class="titulo">Todavía no tenés reservas.</p>
      <p class="small mb-0">Buscá un ítem en el <a href="index.php?accion=listarItems">catálogo</a> y apretá «Reservar».</p>
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
            <th>Fecha de reserva</th>
            <th>Retirar hasta</th>
            <th><span class="visually-hidden">Acciones</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reservas as $r): ?>
            <tr class="<?= $r->estaActiva() ? '' : 'text-body-secondary' ?>">
              <?php if (esAdmin()): ?><td class="fw-medium"><?= htmlspecialchars($r->getSocioNombre()) ?></td><?php endif; ?>
              <td><?= htmlspecialchars($r->getItemTitulo()) ?></td>
              <td class="text-nowrap">
                <?php if ($r->estaLista()): ?>
                  <span class="badge rounded-pill bg-success-subtle text-success-emphasis">Lista</span>
                <?php elseif ($r->estaPendiente()): ?>
                  <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">En espera · puesto <?= htmlspecialchars($r->getPuesto()) ?></span>
                <?php elseif ($r->estaVencida()): ?>
                  <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis">Vencida</span>
                <?php else: ?>
                  <span class="badge rounded-pill etiqueta"><?= htmlspecialchars($r->getEstadoTexto()) ?></span>
                <?php endif; ?>
              </td>
              <td class="text-nowrap"><?= htmlspecialchars(formatoFecha($r->getFechaReserva())) ?></td>
              <?php // La fecha límite para retirar solo importa mientras está lista (o para ver cuándo venció). ?>
              <td class="text-nowrap <?= $r->estaVencida() ? 'text-danger-emphasis fw-medium' : '' ?>">
                <?= htmlspecialchars($r->estaLista() || $r->estaVencida() ? formatoFecha($r->getFechaVencimiento()) : '—') ?>
              </td>
              <td class="text-end text-nowrap">
                <?php if (esAdmin() && $r->estaLista()): ?>
                  <?php // Cuando el socio viene a buscarlo: abre el préstamo con el socio y el ítem ya elegidos. ?>
                  <a href="index.php?accion=formCrearPrestamo&amp;socio_id=<?= $r->getSocioId() ?>&amp;item_id=<?= $r->getItemId() ?>"
                    class="btn btn-sm btn-outline-secondary">Retirar<span class="visually-hidden"> «<?= htmlspecialchars($r->getItemTitulo()) ?>»</span></a>
                <?php endif; ?>
                <?php // El socio cancela las activas; el admin además puede limpiar las vencidas. ?>
                <?php if ($r->estaActiva() || (esAdmin() && $r->estaVencida())): ?>
                  <?php
                  $pregunta = esAdmin()
                      ? '¿Cancelar la reserva de «' . $r->getItemTitulo() . '» que hizo ' . $r->getSocioNombre() . '? No se puede deshacer.'
                      : '¿Cancelar tu reserva de «' . $r->getItemTitulo() . '»? No se puede deshacer.';
                  ?>
                  <form method="POST" action="index.php?accion=cancelarReserva" class="d-inline"
                    data-confirmar="<?= htmlspecialchars($pregunta) ?>"
                    data-boton="Cancelar reserva" data-volver="Volver" data-color="danger">
                    <?= campoCsrf() ?>
                    <input type="hidden" name="id" value="<?= $r->getId() ?>">
                    <?= camposOcultos(array_merge($filtros, ['p' => $resultado['pagina']])) ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger">Cancelar<span class="visually-hidden"> la reserva de «<?= htmlspecialchars($r->getItemTitulo()) ?>»</span></button>
                  </form>
                <?php endif; ?>
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
