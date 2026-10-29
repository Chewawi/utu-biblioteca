<?php require __DIR__ . '/../header.php'; ?>

<?php
// Texto del período elegido, para los títulos de la sección de préstamos.
if ($desde !== '' && $hasta !== '') {
    $periodo = 'Del ' . formatoFecha($desde) . ' al ' . formatoFecha($hasta);
} elseif ($desde !== '') {
    $periodo = 'Desde el ' . formatoFecha($desde);
} elseif ($hasta !== '') {
    $periodo = 'Hasta el ' . formatoFecha($hasta);
} else {
    $periodo = 'Todo el historial';
}

$totalTitulos = array_sum(array_column($itemsPorTipo, 'titulos'));
$totalEjemplares = array_sum(array_column($itemsPorTipo, 'ejemplares'));
$alDia = $estados['activos'] - $estados['vencidos'];
$nombresMes = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

/** Ancho de una barra: $valor como porcentaje de $maximo (el mayor de la lista). */
function anchoBarra($valor, $maximo) {
    return $maximo > 0 ? round($valor / $maximo * 100) : 0;
}
?>

<div class="encabezado">
  <h1 class="h3">Analíticas</h1>
</div>

<form method="GET" action="index.php" class="card mb-4">
  <div class="card-body">
    <input type="hidden" name="accion" value="verAnaliticas">
    <div class="row g-3 align-items-end">
      <div class="col-6 col-md-3">
        <label class="form-label" for="desde">Prestado desde</label>
        <input type="date" id="desde" name="desde" class="form-control" value="<?= htmlspecialchars($desde) ?>">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label" for="hasta">Prestado hasta</label>
        <input type="date" id="hasta" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta) ?>">
      </div>
      <div class="col-md-auto d-flex gap-2">
        <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
        <?php if ($desde !== '' || $hasta !== ''): ?>
          <a href="index.php?accion=verAnaliticas" class="btn btn-link">Ver todo</a>
        <?php endif; ?>
      </div>
    </div>
    <p class="form-text mb-0 mt-3">El período filtra los préstamos según su fecha de préstamo. El inventario muestra siempre cómo está hoy.</p>
  </div>
</form>

<!-- Inventario: no depende del período -->
<h2 class="titulo-seccion">Inventario</h2>
<div class="row g-3 mb-4">
  <div class="col-md-8">
    <div class="card h-100">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12 col-sm-3 indicador">
            <p class="nombre">Ítems</p>
            <p class="valor"><?= htmlspecialchars($totalTitulos) ?></p>
            <p class="detalle"><?= htmlspecialchars($totalEjemplares) ?> ejemplares</p>
          </div>
          <?php foreach (Item::PLURALES as $tipo => $plural): ?>
            <div class="col-4 col-sm-3 indicador">
              <p class="nombre"><?= htmlspecialchars($plural) ?></p>
              <p class="valor"><?= htmlspecialchars($itemsPorTipo[$tipo]['titulos']) ?></p>
              <p class="detalle"><?= htmlspecialchars($itemsPorTipo[$tipo]['ejemplares']) ?> ejemplares</p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body indicador">
        <p class="nombre">Socios</p>
        <p class="valor"><?= htmlspecialchars($totalSocios) ?></p>
        <p class="detalle">registrados</p>
      </div>
    </div>
  </div>
</div>

<!-- Préstamos: todo lo de acá abajo usa el período -->
<h2 class="titulo-seccion">Préstamos · <?= htmlspecialchars($periodo) ?></h2>
<div class="row g-3 mb-3">
  <div class="col-6 col-lg-2">
    <div class="card h-100"><div class="card-body indicador">
      <p class="nombre">Préstamos</p>
      <p class="valor"><?= htmlspecialchars($estados['total']) ?></p>
      <p class="detalle">registrados</p>
    </div></div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="card h-100"><div class="card-body indicador">
      <p class="nombre">Activos</p>
      <p class="valor"><?= htmlspecialchars($estados['activos']) ?></p>
      <p class="detalle"><?= htmlspecialchars($alDia) ?> al día</p>
    </div></div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="card h-100"><div class="card-body indicador">
      <p class="nombre">Vencidos</p>
      <p class="valor <?= $estados['vencidos'] > 0 ? 'text-danger-emphasis' : '' ?>"><?= htmlspecialchars($estados['vencidos']) ?></p>
      <p class="detalle">activos fuera de fecha</p>
    </div></div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="card h-100"><div class="card-body indicador">
      <p class="nombre">Devueltos</p>
      <p class="valor"><?= htmlspecialchars($estados['devueltos']) ?></p>
      <p class="detalle">ya en la biblioteca</p>
    </div></div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="card h-100"><div class="card-body indicador">
      <p class="nombre">Tiempo de devolución</p>
      <?php if ($promedioDias === null): ?>
        <p class="valor">—</p>
        <p class="detalle">sin devoluciones</p>
      <?php else: ?>
        <p class="valor"><?= htmlspecialchars(number_format($promedioDias, 1, ',', '.')) ?></p>
        <p class="detalle">días en promedio</p>
      <?php endif; ?>
    </div></div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="card h-100"><div class="card-body indicador">
      <p class="nombre">Devueltos tarde</p>
      <p class="valor"><?= htmlspecialchars($tardias['tardias']) ?></p>
      <p class="detalle">
        <?php if ($tardias['devoluciones'] > 0): ?>
          de <?= htmlspecialchars($tardias['devoluciones']) ?> (<?= htmlspecialchars(round($tardias['tardias'] / $tardias['devoluciones'] * 100)) ?> %)
        <?php else: ?>
          sin devoluciones
        <?php endif; ?>
      </p>
    </div></div>
  </div>
</div>

<?php // Barra apilada: cómo se reparten los préstamos del período entre al día, vencidos y devueltos. ?>
<?php if ($estados['total'] > 0): ?>
  <?php
  $partes = [
      ['nombre' => 'Al día', 'cantidad' => $alDia, 'color' => 'bg-warning'],
      ['nombre' => 'Vencidos', 'cantidad' => $estados['vencidos'], 'color' => 'bg-danger'],
      ['nombre' => 'Devueltos', 'cantidad' => $estados['devueltos'], 'color' => 'bg-success'],
  ];
  ?>
  <div class="card mb-4">
    <div class="card-body">
      <div class="progress-stacked barra-apilada mb-3" aria-hidden="true">
        <?php foreach ($partes as $parte): ?>
          <?php if ($parte['cantidad'] > 0): ?>
            <?php // Mínimo 1 %, para que una parte muy chica no desaparezca. ?>
            <div class="progress" style="width: <?= max(1, anchoBarra($parte['cantidad'], $estados['total'])) ?>%">
              <div class="progress-bar <?= $parte['color'] ?>"></div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <ul class="list-inline small mb-0">
        <?php foreach ($partes as $parte): ?>
          <li class="list-inline-item me-4">
            <span class="punto <?= $parte['color'] ?>"></span><?= htmlspecialchars($parte['nombre']) ?>
            <span class="fw-medium ms-1"><?= htmlspecialchars($parte['cantidad']) ?></span>
            <span class="text-body-secondary">(<?= anchoBarra($parte['cantidad'], $estados['total']) ?> %)</span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
<?php else: ?>
  <div class="card estado-vacio mb-4">
    <p class="titulo">No hay préstamos en este período.</p>
    <p class="small mb-0">Probá con otras fechas o tocá «Ver todo».</p>
  </div>
<?php endif; ?>

<div class="row g-3">
  <!-- Préstamos por mes -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header">
        <h3 class="h6 mb-0">Préstamos por mes</h3>
      </div>
      <?php if (empty($porMes)): ?>
        <p class="card-body text-body-secondary small mb-0">Sin préstamos en este período.</p>
      <?php else: ?>
        <?php // Con muchos meses la tabla se desplaza adentro de la tarjeta. ?>
        <div class="table-responsive tabla-con-scroll">
          <table class="table align-middle">
            <thead>
              <tr><th>Mes</th><th class="w-75">Préstamos</th></tr>
            </thead>
            <tbody>
              <?php foreach ($porMes as $mes => $cantidad): ?>
                <tr>
                  <td class="text-nowrap"><?= htmlspecialchars($nombresMes[(int) substr($mes, 5, 2) - 1] . ' ' . substr($mes, 0, 4)) ?></td>
                  <td>
                    <div class="celda-barra">
                      <div class="progress" aria-hidden="true"><div class="progress-bar" style="width: <?= anchoBarra($cantidad, max($porMes)) ?>%"></div></div>
                      <span class="cantidad"><?= htmlspecialchars($cantidad) ?></span>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Ítems más prestados -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header">
        <h3 class="h6 mb-0">Ítems más prestados</h3>
      </div>
      <?php if (empty($masPrestados)): ?>
        <p class="card-body text-body-secondary small mb-0">Sin préstamos en este período.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr><th>Ítem</th><th class="w-50">Préstamos</th></tr>
            </thead>
            <tbody>
              <?php foreach ($masPrestados as $fila): ?>
                <tr>
                  <td>
                    <span class="fw-medium"><?= htmlspecialchars($fila['titulo']) ?></span>
                    <span class="badge etiqueta ms-1"><?= htmlspecialchars(ucfirst($fila['tipo'])) ?></span>
                  </td>
                  <td>
                    <div class="celda-barra">
                      <div class="progress" aria-hidden="true"><div class="progress-bar" style="width: <?= anchoBarra($fila['cantidad'], $masPrestados[0]['cantidad']) ?>%"></div></div>
                      <span class="cantidad"><?= htmlspecialchars($fila['cantidad']) ?></span>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Socios con más préstamos -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header">
        <h3 class="h6 mb-0">Socios con más préstamos</h3>
      </div>
      <?php if (empty($sociosTop)): ?>
        <p class="card-body text-body-secondary small mb-0">Sin préstamos en este período.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr><th>Socio</th><th class="w-50">Préstamos</th></tr>
            </thead>
            <tbody>
              <?php foreach ($sociosTop as $fila): ?>
                <tr>
                  <td class="fw-medium"><?= htmlspecialchars($fila['nombre']) ?></td>
                  <td>
                    <div class="celda-barra">
                      <div class="progress" aria-hidden="true"><div class="progress-bar" style="width: <?= anchoBarra($fila['cantidad'], $sociosTop[0]['cantidad']) ?>%"></div></div>
                      <span class="cantidad"><?= htmlspecialchars($fila['cantidad']) ?></span>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Ítems con menos disponibilidad: es inventario, no usa el período -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-baseline gap-2">
        <h3 class="h6 mb-0">Ítems con menos disponibilidad</h3>
        <small class="text-body-secondary">hoy</small>
      </div>
      <?php if (empty($menosDisponibles)): ?>
        <p class="card-body text-body-secondary small mb-0">Todavía no hay ítems en el catálogo.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr><th>Ítem</th><th>Disponibles</th></tr>
            </thead>
            <tbody>
              <?php foreach ($menosDisponibles as $fila): ?>
                <tr>
                  <td>
                    <span class="fw-medium"><?= htmlspecialchars($fila['titulo']) ?></span>
                    <span class="badge etiqueta ms-1"><?= htmlspecialchars(ucfirst($fila['tipo'])) ?></span>
                  </td>
                  <td class="text-nowrap">
                    <span class="punto <?= $fila['disponibles'] > 0 ? 'bg-success' : 'bg-danger' ?>"></span><?= htmlspecialchars($fila['disponibles']) ?> de <?= htmlspecialchars($fila['stock']) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
