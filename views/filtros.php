<?php
// Barra de filtros reutilizable: pestañas con contador, un buscador, un panel
// plegable "Más filtros" y una etiqueta por cada filtro activo (se quita con la ×).
// La vista que la incluye arma $barra con estas claves:
//   accion   -> la acción del listado (index.php?accion=...),
//   pestanas -> ['nombre' => parámetro de la URL, 'opciones' => [valor => texto],
//                'conteo' => [valor => cantidad]],
//   buscar   -> ['nombre' => parámetro de la URL, 'placeholder' => texto],
//   campos   -> los de "Más filtros", cada uno ['nombre', 'etiqueta'] y además
//               'opciones' => [valor => texto] (un desplegable) o 'tipo' => 'date',
//   valores  -> el valor actual (ya validado) de cada parámetro; '' = sin filtrar.
// Las pestañas y las etiquetas son links; el buscador y el panel son un solo formulario.
$valores   = $barra['valores'];
$pestana   = $barra['pestanas']['nombre'];
$buscar    = $barra['buscar']['nombre'];
$baseUrl   = array_merge(['accion' => $barra['accion']], $valores);

// Filtros activos (sin contar la pestaña), para las etiquetas: [parámetro => texto].
$activos = [];
if ($valores[$buscar] !== '') {
    $activos[$buscar] = '«' . $valores[$buscar] . '»';
}
foreach ($barra['campos'] as $campo) {
    $valor = $valores[$campo['nombre']];
    if ($valor !== '') {
        $texto = isset($campo['opciones']) ? ($campo['opciones'][$valor] ?? $valor) : formatoFecha($valor);
        $activos[$campo['nombre']] = $campo['etiqueta'] . ': ' . $texto;
    }
}
// Cuántos de los activos están dentro del panel (todos menos el buscador).
$activosEnPanel = count($activos) - ($valores[$buscar] !== '' ? 1 : 0);
?>

<ul class="nav nav-underline pestanas mb-3" id="pestanas-filtro">
  <?php foreach ($barra['pestanas']['opciones'] as $valor => $texto): ?>
    <?php $esActiva = (string) $valor === $valores[$pestana]; ?>
    <li class="nav-item">
      <a class="nav-link <?= $esActiva ? 'active' : '' ?>" href="<?= htmlspecialchars(urlFiltros($baseUrl, [$pestana => $valor])) ?>"
        <?= $esActiva ? 'aria-current="page"' : '' ?>>
        <?= htmlspecialchars($texto) ?>
        <span class="contador"><?= htmlspecialchars($barra['pestanas']['conteo'][$valor]) ?></span>
      </a>
    </li>
  <?php endforeach; ?>
</ul>
<script>
  // En el celular las pestañas se desplazan de costado: mostramos la elegida.
  document.querySelector('#pestanas-filtro .active').scrollIntoView({ block: 'nearest', inline: 'nearest' });
</script>

<form method="GET" action="index.php" class="mb-3">
  <input type="hidden" name="accion" value="<?= htmlspecialchars($barra['accion']) ?>">
  <?php if ($valores[$pestana] !== ''): ?>
    <input type="hidden" name="<?= htmlspecialchars($pestana) ?>" value="<?= htmlspecialchars($valores[$pestana]) ?>">
  <?php endif; ?>

  <div class="d-flex gap-2">
    <?php // Se busca con Enter: lo envía el botón "Aplicar" del panel, aunque esté plegado. ?>
    <input type="search" name="<?= htmlspecialchars($buscar) ?>" class="form-control" aria-label="Buscar"
      placeholder="<?= htmlspecialchars($barra['buscar']['placeholder']) ?>" value="<?= htmlspecialchars($valores[$buscar]) ?>">
    <button type="button" class="btn btn-outline-secondary text-nowrap boton-mas-filtros"
      data-bs-toggle="collapse" data-bs-target="#mas-filtros" aria-expanded="false" aria-controls="mas-filtros">
      Más filtros
      <?php if ($activosEnPanel > 0): ?>
        <span class="contador" title="Filtros activos"><?= htmlspecialchars($activosEnPanel) ?><span class="visually-hidden"> activos</span></span>
      <?php endif; ?>
    </button>
  </div>

  <div class="collapse" id="mas-filtros">
    <div class="card card-body mt-2">
      <div class="row g-3 align-items-end">
        <?php foreach ($barra['campos'] as $campo): ?>
          <?php $id = 'filtro-' . $campo['nombre']; ?>
          <?php if (isset($campo['opciones'])): ?>
            <div class="col-md-6">
              <label class="form-label" for="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($campo['etiqueta']) ?></label>
              <select id="<?= htmlspecialchars($id) ?>" name="<?= htmlspecialchars($campo['nombre']) ?>" class="form-select select2-buscable">
                <option value="">Todos</option>
                <?php foreach ($campo['opciones'] as $valor => $texto): ?>
                  <option value="<?= htmlspecialchars($valor) ?>" <?= (string) $valor === $valores[$campo['nombre']] ? 'selected' : '' ?>><?= htmlspecialchars($texto) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php else: ?>
            <div class="col-6 col-md-3">
              <label class="form-label" for="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($campo['etiqueta']) ?></label>
              <input type="date" id="<?= htmlspecialchars($id) ?>" name="<?= htmlspecialchars($campo['nombre']) ?>" class="form-control"
                value="<?= htmlspecialchars($valores[$campo['nombre']]) ?>">
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
        <div class="col-12 col-md-auto ms-auto">
          <button type="submit" class="btn btn-primary w-100">Aplicar</button>
        </div>
      </div>
    </div>
  </div>
</form>

<?php if (!empty($activos)): ?>
  <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <?php foreach ($activos as $nombre => $texto): ?>
      <a href="<?= htmlspecialchars(urlFiltros($baseUrl, [$nombre => ''])) ?>" class="etiqueta-filtro"
        title="Quitar este filtro" aria-label="Quitar filtro <?= htmlspecialchars($texto) ?>">
        <?= htmlspecialchars($texto) ?><span aria-hidden="true">&times;</span>
      </a>
    <?php endforeach; ?>
    <?php // "Limpiar" saca todos los filtros pero deja la pestaña elegida. ?>
    <a href="<?= htmlspecialchars(urlFiltros(['accion' => $barra['accion'], $pestana => $valores[$pestana]])) ?>" class="btn btn-link btn-sm">Limpiar</a>
  </div>
<?php endif; ?>
