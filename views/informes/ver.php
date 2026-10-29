<?php require __DIR__ . '/../header.php'; ?>

<?php
// Lo que tiene clase d-print-none (barra, pestañas, filtros, botones) no sale al imprimir.
$urlCsv = 'index.php?' . http_build_query(array_merge($_GET, ['formato' => 'csv']));
?>

<div class="encabezado d-print-none">
  <h1 class="h3">Informes</h1>
  <div class="d-flex flex-wrap gap-2">
    <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Imprimir o guardar PDF</button>
    <a href="<?= htmlspecialchars($urlCsv) ?>" class="btn btn-outline-secondary">Exportar a Excel (CSV)</a>
  </div>
</div>

<ul class="nav nav-underline pestanas mb-4 d-print-none">
  <?php foreach (INFORMES as $clave => $nombre): ?>
    <li class="nav-item">
      <a class="nav-link <?= $clave === $informe ? 'active' : '' ?>" href="index.php?accion=verInforme&amp;informe=<?= htmlspecialchars($clave) ?>"
        <?= $clave === $informe ? 'aria-current="page"' : '' ?>><?= htmlspecialchars($nombre) ?></a>
    </li>
  <?php endforeach; ?>
</ul>

<?php if ($informe !== 'vencidos'): ?>
  <form method="GET" action="index.php" class="card mb-4 d-print-none">
    <div class="card-body">
      <input type="hidden" name="accion" value="verInforme">
      <input type="hidden" name="informe" value="<?= htmlspecialchars($informe) ?>">
      <div class="row g-3 align-items-end">
        <?php if ($informe === 'inventario'): ?>
          <div class="col-sm-6 col-md-4">
            <label class="form-label" for="agrupar">Agrupar</label>
            <select id="agrupar" name="agrupar" class="form-select">
              <?php foreach (AGRUPAR_INVENTARIO as $clave => $nombre): ?>
                <option value="<?= htmlspecialchars($clave) ?>" <?= $clave === $agrupar ? 'selected' : '' ?>><?= htmlspecialchars($nombre) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php else: ?>
          <div class="col-6 col-md-3">
            <label class="form-label" for="desde">Prestado desde</label>
            <input type="date" id="desde" name="desde" class="form-control" value="<?= htmlspecialchars($filtros['desde']) ?>">
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label" for="hasta">Prestado hasta</label>
            <input type="date" id="hasta" name="hasta" class="form-control" value="<?= htmlspecialchars($filtros['hasta']) ?>">
          </div>
          <?php if ($informe === 'prestamos'): ?>
            <div class="col-md-3">
              <label class="form-label" for="estado">Estado</label>
              <select id="estado" name="estado" class="form-select">
                <option value="">Todos</option>
                <option value="activo" <?= $filtros['estado'] === 'activo' ? 'selected' : '' ?>>Activos</option>
                <option value="vencido" <?= $filtros['estado'] === 'vencido' ? 'selected' : '' ?>>Vencidos</option>
                <option value="devuelto" <?= $filtros['estado'] === 'devuelto' ? 'selected' : '' ?>>Devueltos</option>
              </select>
            </div>
          <?php endif; ?>
        <?php endif; ?>
        <div class="col-md-auto d-flex gap-2">
          <button class="btn btn-outline-secondary" type="submit">Ver informe</button>
          <a href="index.php?accion=verInforme&amp;informe=<?= htmlspecialchars($informe) ?>" class="btn btn-link">Limpiar</a>
        </div>
      </div>
    </div>
  </form>
<?php endif; ?>

<div class="mb-3">
  <p class="d-none d-print-block small mb-1">Bibliodeko · generado el <?= htmlspecialchars(date('d/m/Y H:i')) ?></p>
  <h2 class="h5 mb-0"><?= htmlspecialchars($tabla['titulo']) ?></h2>
  <p class="text-body-secondary small mb-0"><?= htmlspecialchars($tabla['detalle']) ?></p>
</div>

<?php // Los números (int) van alineados a la derecha. ?>
<?php if (empty($tabla['filas'])): ?>
  <div class="card estado-vacio">
    <?php if ($informe === 'vencidos'): ?>
      <p class="titulo mb-0">No hay préstamos vencidos.</p>
    <?php else: ?>
      <p class="titulo">No hay nada para mostrar.</p>
      <p class="small mb-0">Si filtraste, probá con otras fechas u otro estado.</p>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table align-middle tabla-informe">
        <thead>
          <tr>
            <?php // Si en la primera fila la columna es un número, el encabezado también va a la derecha. ?>
            <?php foreach ($tabla['columnas'] as $i => $columna): ?>
              <th class="<?= is_int($tabla['filas'][0][$i]) ? 'text-end' : '' ?>"><?= htmlspecialchars($columna) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tabla['filas'] as $fila): ?>
            <tr>
              <?php foreach ($fila as $valor): ?>
                <td class="<?= is_int($valor) ? 'text-end' : '' ?>"><?= htmlspecialchars($valor) ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <?php if ($tabla['totales'] !== null): ?>
          <tfoot>
            <tr>
              <?php foreach ($tabla['totales'] as $valor): ?>
                <td class="<?= is_int($valor) ? 'text-end' : '' ?>"><?= htmlspecialchars($valor) ?></td>
              <?php endforeach; ?>
            </tr>
          </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../footer.php'; ?>
