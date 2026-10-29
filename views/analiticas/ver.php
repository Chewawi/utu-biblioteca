<?php require __DIR__ . '/../header.php'; ?>

<?php
// Texto del período elegido, para el título de la parte de actividad.
$periodo = textoPeriodo($desde, $hasta);

$totalTitulos = array_sum(array_column($itemsPorTipo, 'titulos'));
$resumenTipos = [];
foreach (Item::PLURALES as $tipo => $plural) {
    $resumenTipos[] = $itemsPorTipo[$tipo]['titulos'] . ' ' . mb_strtolower($plural);
}

// Partes de la dona, en el orden en que se dibujan. La clase es la de su cuadradito en la leyenda.
$partes = [
    ['nombre' => 'Al día', 'cantidad' => $estados['activos'] - $estados['vencidos'], 'clase' => 'muestra-al-dia'],
    ['nombre' => 'Vencidos', 'cantidad' => $estados['vencidos'], 'clase' => 'muestra-vencido'],
    ['nombre' => 'Devueltos a tiempo', 'cantidad' => $estados['devueltos'] - $tardias['tardias'], 'clase' => 'muestra-devuelto'],
    ['nombre' => 'Devueltos tarde', 'cantidad' => $tardias['tardias'], 'clase' => 'muestra-tarde'],
];

// Lo que necesitan los gráficos, para pasárselo a JavaScript. Los meses van del más viejo al más nuevo.
$nombresMes = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$meses = [];
foreach (array_reverse($porMes, true) as $mes => $cantidad) {
    $meses[$nombresMes[(int) substr($mes, 5, 2) - 1] . ' ' . substr($mes, 0, 4)] = $cantidad;
}
$datosGraficos = [
    'meses' => ['etiquetas' => array_keys($meses), 'valores' => array_values($meses)],
    'estados' => ['etiquetas' => array_column($partes, 'nombre'), 'valores' => array_column($partes, 'cantidad')],
    'items' => ['etiquetas' => array_column($masPrestados, 'titulo'), 'valores' => array_map('intval', array_column($masPrestados, 'cantidad'))],
];
?>

<div class="encabezado">
  <h1 class="h3">Analíticas</h1>
</div>

<!-- Cómo está la biblioteca hoy: no depende del período -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card h-100"><div class="card-body indicador">
      <p class="nombre">Préstamos activos</p>
      <p class="valor"><?= htmlspecialchars($estadosHoy['activos']) ?></p>
      <p class="detalle"><?= htmlspecialchars($estadosHoy['activos'] - $estadosHoy['vencidos']) ?> al día</p>
    </div></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card h-100"><div class="card-body indicador">
      <p class="nombre">Vencidos</p>
      <p class="valor <?= $estadosHoy['vencidos'] > 0 ? 'text-danger-emphasis' : '' ?>">
        <?= htmlspecialchars($estadosHoy['vencidos']) ?>
        <?php if ($estadosHoy['vencidos'] > 0): ?>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0zM12 9v4M12 17h.01" />
          </svg>
        <?php endif; ?>
      </p>
      <p class="detalle"><?= $estadosHoy['vencidos'] > 0 ? 'pasaron la fecha límite' : 'ninguno fuera de fecha' ?></p>
    </div></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card h-100"><div class="card-body indicador">
      <p class="nombre">Socios</p>
      <p class="valor"><?= htmlspecialchars($totalSocios) ?></p>
      <p class="detalle">registrados</p>
    </div></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card h-100"><div class="card-body indicador">
      <p class="nombre">Ítems</p>
      <p class="valor"><?= htmlspecialchars($totalTitulos) ?></p>
      <p class="detalle"><?= htmlspecialchars(implode(' · ', $resumenTipos)) ?></p>
    </div></div>
  </div>
</div>

<!-- Actividad: los gráficos y los socios usan el período -->
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
  <div>
    <h2 class="h5 mb-0">Actividad</h2>
    <p class="text-body-secondary small mb-0"><?= htmlspecialchars($periodo) ?> · según la fecha de préstamo</p>
  </div>
  <form method="GET" action="index.php" class="d-flex flex-wrap align-items-end gap-2">
    <input type="hidden" name="accion" value="verAnaliticas">
    <div>
      <label class="form-label" for="desde">Desde</label>
      <input type="date" id="desde" name="desde" class="form-control" value="<?= htmlspecialchars($desde) ?>">
    </div>
    <div>
      <label class="form-label" for="hasta">Hasta</label>
      <input type="date" id="hasta" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta) ?>">
    </div>
    <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
    <?php if ($desde !== '' || $hasta !== ''): ?>
      <a href="index.php?accion=verAnaliticas" class="btn btn-link">Ver todo</a>
    <?php endif; ?>
  </form>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-body d-flex flex-column">
        <h3 class="h6">Préstamos por mes</h3>
        <?php if (empty($porMes)): ?>
          <div class="estado-vacio">No hay préstamos en este período.</div>
        <?php else: ?>
          <div class="grafico grafico-crece"><canvas id="grafico-meses" role="img" aria-label="Gráfico de barras con los préstamos de cada mes"></canvas></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-body">
        <h3 class="h6 mb-0">Estado de los préstamos</h3>
        <p class="text-body-secondary small"><?= htmlspecialchars($estados['total']) ?> préstamos</p>
        <?php if ($estados['total'] === 0): ?>
          <div class="estado-vacio">No hay préstamos en este período.</div>
        <?php else: ?>
          <div class="grafico"><canvas id="grafico-estados" role="img" aria-label="Gráfico de dona con el estado de los préstamos"></canvas></div>
          <?php // La leyenda va en HTML, con los números, así no hace falta pasar el cursor. ?>
          <ul class="list-unstyled small mt-3 mb-0">
            <?php foreach ($partes as $parte): ?>
              <li class="d-flex justify-content-between py-1">
                <span><span class="muestra <?= $parte['clase'] ?>"></span><?= htmlspecialchars($parte['nombre']) ?></span>
                <span class="fw-medium"><?= htmlspecialchars($parte['cantidad']) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
          <?php if ($promedioDias !== null): ?>
            <p class="text-body-secondary small border-top pt-2 mt-2 mb-0">
              Se devuelven en <?= htmlspecialchars(number_format($promedioDias, 1, ',', '.')) ?> días en promedio.
            </p>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-6">
    <div class="card h-100">
      <div class="card-body">
        <h3 class="h6">Ítems más prestados</h3>
        <?php if (empty($masPrestados)): ?>
          <div class="estado-vacio">No hay préstamos en este período.</div>
        <?php else: ?>
          <div class="grafico"><canvas id="grafico-items" role="img" aria-label="Gráfico de barras con los ítems más prestados"></canvas></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body pb-2 flex-grow-0">
        <h3 class="h6 mb-0">Socios con más préstamos</h3>
      </div>
      <?php if (empty($sociosTop)): ?>
        <p class="card-body pt-0 text-body-secondary small mb-0">No hay préstamos en este período.</p>
      <?php else: ?>
        <ul class="list-group list-group-flush small">
          <?php foreach ($sociosTop as $fila): ?>
            <li class="list-group-item d-flex justify-content-between gap-2">
              <span class="text-truncate" title="<?= htmlspecialchars($fila['nombre']) ?>"><?= htmlspecialchars($fila['nombre']) ?></span>
              <span class="fw-medium"><?= htmlspecialchars($fila['cantidad']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-md-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body pb-2 flex-grow-0 d-flex justify-content-between align-items-baseline gap-2">
        <h3 class="h6 mb-0">Menos disponibles</h3>
        <small class="text-body-secondary">hoy</small>
      </div>
      <?php if (empty($menosDisponibles)): ?>
        <p class="card-body pt-0 text-body-secondary small mb-0">Todavía no hay ítems en el catálogo.</p>
      <?php else: ?>
        <ul class="list-group list-group-flush small">
          <?php foreach ($menosDisponibles as $fila): ?>
            <li class="list-group-item d-flex justify-content-between gap-2">
              <span class="text-truncate" title="<?= htmlspecialchars($fila['titulo']) ?>"><?= htmlspecialchars($fila['titulo']) ?></span>
              <span class="text-nowrap"><span class="punto <?= $fila['disponibles'] > 0 ? 'bg-success' : 'bg-danger' ?>"></span><?= htmlspecialchars($fila['disponibles']) ?> de <?= htmlspecialchars($fila['stock']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php // JSON_HEX_TAG cambia < y > por códigos, así un título no puede cerrar el <script>. ?>
<script type="application/json" id="datos-graficos"><?= json_encode($datosGraficos, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="assets/js/analiticas.js"></script>

<?php require __DIR__ . '/../footer.php'; ?>
