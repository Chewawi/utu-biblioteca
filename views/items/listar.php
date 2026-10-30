<?php require __DIR__ . '/../header.php'; ?>

<div class="encabezado">
  <h1 class="h3">Catálogo</h1>
  <?php if (esAdmin()): ?>
    <a href="index.php?accion=formCrearItem" class="btn btn-primary">+ Nuevo ítem</a>
  <?php endif; ?>
</div>

<?php
// Pestañas: "Todos" (sin tipo) y una por cada tipo, con su contador.
// Conservan la búsqueda y la categoría pero no la página: cada pestaña arranca en la 1.
$pestanas = ['' => 'Todos'] + Item::PLURALES;
$conteo[''] = array_sum($conteo);
$hayFiltros = $filtros['q'] !== '' || $categoria !== '';
?>
<ul class="nav nav-underline pestanas mb-4">
  <?php foreach ($pestanas as $clave => $nombre): ?>
    <?php $url = 'index.php?' . http_build_query(array_filter(['accion' => 'listarItems', 'tipo' => $clave, 'q' => $filtros['q'], 'categoria' => $categoria])); ?>
    <li class="nav-item">
      <a class="nav-link <?= $clave === $tipo ? 'active' : '' ?>" href="<?= htmlspecialchars($url) ?>"
        <?= $clave === $tipo ? 'aria-current="page"' : '' ?>>
        <?= htmlspecialchars($nombre) ?>
        <span class="contador"><?= htmlspecialchars($conteo[$clave]) ?></span>
      </a>
    </li>
  <?php endforeach; ?>
</ul>
<script>
  // En el celular las pestañas se desplazan de costado: mostramos la elegida.
  document.querySelector('.pestanas .active').scrollIntoView({ block: 'nearest', inline: 'nearest' });
</script>

<form method="GET" action="index.php" class="filtros-catalogo d-flex flex-wrap align-items-center gap-2 mb-4">
  <input type="hidden" name="accion" value="listarItems">
  <?php if ($tipo !== ''): ?>
    <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
  <?php endif; ?>
  <div class="input-group">
    <input type="search" name="q" class="form-control" aria-label="Buscar"
      placeholder="Buscar por título o <?= htmlspecialchars(mb_strtolower(Item::etiquetaAutor($tipo))) ?>..."
      value="<?= htmlspecialchars($filtros['q']) ?>">
    <button class="btn btn-outline-secondary" type="submit">Buscar</button>
  </div>
  <?php // Al elegir una categoría se filtra enseguida, sin apretar "Buscar". ?>
  <select name="categoria" class="form-select" aria-label="Categoría" onchange="this.form.submit()">
    <option value="">Todas las categorías</option>
    <?php foreach ($categorias as $nombreCategoria): ?>
      <option value="<?= htmlspecialchars($nombreCategoria) ?>" <?= $nombreCategoria === $categoria ? 'selected' : '' ?>><?= htmlspecialchars($nombreCategoria) ?></option>
    <?php endforeach; ?>
  </select>
  <?php if ($hayFiltros): ?>
    <a href="index.php?<?= htmlspecialchars(http_build_query(array_filter(['accion' => 'listarItems', 'tipo' => $tipo]))) ?>" class="btn btn-link">Limpiar</a>
  <?php endif; ?>
</form>

<?php if (empty($items)): ?>
  <div class="card estado-vacio">
    <p class="titulo">No se encontraron ítems.</p>
    <p class="small mb-0">
      <?php if ($hayFiltros): ?>
        Probá con otra búsqueda, otra categoría o en otra pestaña.
      <?php else: ?>
        Todavía no hay <?= htmlspecialchars(mb_strtolower($pestanas[$tipo])) ?> en el catálogo.
      <?php endif; ?>
    </p>
  </div>

<?php elseif (esAdmin()): ?>
  <!-- Vista admin: tabla, para gestionar rápido -->
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th><span class="visually-hidden">Portada</span></th>
            <th>Tipo</th>
            <th>Título</th>
            <th><?= htmlspecialchars(Item::etiquetaAutor($tipo)) ?></th>
            <th>Categoría</th>
            <th>Disponibles</th>
            <th><span class="visually-hidden">Acciones</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <tr>
              <td><img src="uploads/<?= htmlspecialchars($item->getImagen()) ?>" alt="" class="portada-mini"></td>
              <td><span class="badge etiqueta"><?= htmlspecialchars(ucfirst($item->getTipo())) ?></span></td>
              <td class="fw-medium"><?= htmlspecialchars($item->getTitulo()) ?></td>
              <td class="text-body-secondary"><?= htmlspecialchars($item->getAutor()) ?></td>
              <?php // ?: y no ??: un ítem sin categoría puede tener NULL o '' (texto vacío). ?>
              <td class="text-body-secondary"><?= htmlspecialchars($item->getCategoria() ?: '—') ?></td>
              <td class="text-nowrap">
                <span class="punto <?= $item->getDisponibles() > 0 ? 'bg-success' : 'bg-danger' ?>"></span><?= htmlspecialchars($item->getDisponibles()) ?> de <?= htmlspecialchars($item->getStock()) ?>
                <?php if ($item->getApartados() > 0): ?>
                  <span class="text-body-secondary small">· <?= htmlspecialchars($item->getApartados()) ?> <?= $item->getApartados() == 1 ? 'apartado' : 'apartados' ?></span>
                <?php endif; ?>
              </td>
              <td class="text-end text-nowrap">
                <a href="index.php?accion=formEditarItem&id=<?= $item->getId() ?>" class="btn btn-sm btn-outline-secondary">Editar<span class="visually-hidden"> «<?= htmlspecialchars($item->getTitulo()) ?>»</span></a>
                <form method="POST" action="index.php?accion=eliminarItem" class="d-inline"
                  data-confirmar="¿Eliminar «<?= htmlspecialchars($item->getTitulo()) ?>» del catálogo? No se puede deshacer."
                  data-boton="Eliminar" data-color="danger">
                  <?= campoCsrf() ?>
                  <input type="hidden" name="id" value="<?= $item->getId() ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar<span class="visually-hidden"> «<?= htmlspecialchars($item->getTitulo()) ?>»</span></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php else: ?>
  <!-- Vista pública: tarjetas -->
  <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">
    <?php foreach ($items as $item): ?>
      <div class="col">
        <div class="card h-100 tarjeta-item">
          <img src="uploads/<?= htmlspecialchars($item->getImagen()) ?>" alt="" class="card-img-top portada-tarjeta">
          <div class="card-body">
            <div class="d-flex flex-wrap gap-1 mb-2">
              <span class="badge etiqueta"><?= htmlspecialchars(ucfirst($item->getTipo())) ?></span>
              <?php if ($item->getCategoria()): ?>
                <span class="badge etiqueta"><?= htmlspecialchars($item->getCategoria()) ?></span>
              <?php endif; ?>
            </div>
            <h2 class="h6 card-title mb-1"><?= htmlspecialchars($item->getTitulo()) ?></h2>
            <p class="card-text text-body-secondary small mb-0"><?= htmlspecialchars($item->getAutor()) ?></p>
          </div>
          <div class="card-footer small text-body-secondary">
            <?php if ($item->getDisponibles() > 0): ?>
              <span class="punto bg-success"></span><?= htmlspecialchars($item->getDisponibles()) ?> de <?= htmlspecialchars($item->getStock()) ?> disponibles
            <?php else: ?>
              <span class="punto bg-danger"></span>Sin ejemplares disponibles
            <?php endif; ?>

            <?php if ($socio): ?>
              <?php // Para el socio: si ya lo tiene o lo reservó, lo decimos; si no, puede reservarlo. ?>
              <?php $reserva = $reservasDelSocio[$item->getId()] ?? null; ?>
              <div class="mt-2">
                <?php if (in_array($item->getId(), $prestadosDelSocio)): ?>
                  <a href="index.php?accion=listarPrestamos" class="badge rounded-pill text-wrap bg-warning-subtle text-warning-emphasis">Lo tenés prestado</a>
                <?php elseif ($reserva && $reserva->estaLista()): ?>
                  <a href="index.php?accion=listarReservas" class="badge rounded-pill text-wrap bg-success-subtle text-success-emphasis">Apartado hasta el <?= htmlspecialchars(formatoFecha($reserva->getFechaVencimiento())) ?></a>
                <?php elseif ($reserva): ?>
                  <a href="index.php?accion=listarReservas" class="badge rounded-pill text-wrap bg-warning-subtle text-warning-emphasis">En espera · puesto <?= htmlspecialchars($reserva->getPuesto()) ?></a>
                <?php else: ?>
                  <form method="POST" action="index.php?accion=crearReserva">
                    <?= campoCsrf() ?>
                    <?= camposOcultos(['item_id' => $item->getId(), 'tipo' => $tipo, 'categoria' => $categoria, 'q' => $filtros['q'], 'p' => $resultado['pagina']]) ?>
                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                      Reservar<span class="visually-hidden"> «<?= htmlspecialchars($item->getTitulo()) ?>»</span>
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../paginador.php'; ?>

<?php require __DIR__ . '/../footer.php'; ?>
