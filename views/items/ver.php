<?php
$tituloPagina = $item->getTitulo();
require __DIR__ . '/../header.php';
?>

<p class="mb-3 d-print-none">
  <a href="index.php?accion=listarItems" class="link-secondary small">← Volver al catálogo</a>
</p>

<div class="detalle-item">
  <?php // El marco tiene siempre la misma forma; la portada entra entera adentro, sea alta, apaisada o cuadrada. ?>
  <div class="detalle-portada">
    <div class="marco-portada">
      <img src="uploads/<?= htmlspecialchars($item->getImagen()) ?>" alt="Portada de «<?= htmlspecialchars($item->getTitulo()) ?>»">
    </div>
  </div>

  <div class="card">
    <div class="card-body p-4 p-lg-5">
      <div class="d-flex flex-wrap gap-1 mb-3">
        <span class="badge etiqueta"><?= htmlspecialchars(ucfirst($item->getTipo())) ?></span>
        <?php if ($item->getCategoria()): ?>
          <span class="badge etiqueta"><?= htmlspecialchars($item->getCategoria()) ?></span>
        <?php endif; ?>
      </div>
      <h1 class="titulo-detalle"><?= htmlspecialchars($item->getTitulo()) ?></h1>
      <p class="fs-5 text-body-secondary mb-3"><?= htmlspecialchars($item->getAutor()) ?></p>

      <?php // Verde si quedan ejemplares, rojo si no queda ninguno (como en el catálogo). ?>
      <?php $hayDisponibles = $item->getDisponibles() > 0; ?>
      <span class="badge rounded-pill pastilla-disponibles <?= $hayDisponibles ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis' ?>">
        <span class="punto <?= $hayDisponibles ? 'bg-success' : 'bg-danger' ?>"></span><?= htmlspecialchars($item->getDisponibles()) ?> de <?= htmlspecialchars($item->getStock()) ?> disponibles
      </span>

      <?php // Botones: en el celular ocupan todo el ancho; desde 576px van en fila (ver .acciones-detalle). ?>
      <?php if ($socio): ?>
        <div class="acciones-detalle">
          <?php $volverA = ['volver' => 'verItem']; ?>
          <?php $claseBotonReservar = 'btn btn-primary'; ?>
          <?php require __DIR__ . '/reserva_socio.php'; ?>
        </div>
      <?php elseif (esAdmin()): ?>
        <div class="acciones-detalle">
          <?php if ($hayDisponibles): ?>
            <a href="index.php?accion=formCrearPrestamo&amp;item_id=<?= $item->getId() ?>" class="btn btn-primary">Prestar</a>
          <?php endif; ?>
          <a href="index.php?accion=formEditarItem&amp;id=<?= $item->getId() ?>" class="btn btn-outline-secondary">Editar</a>
        </div>
      <?php elseif (!isset($_SESSION['usuario'])): ?>
        <p class="small text-body-secondary mt-4 mb-0"><a href="index.php?accion=login">Iniciá sesión</a> para reservarlo.</p>
      <?php endif; ?>

      <div class="seccion-detalle">
        <dl class="datos-item">
          <dt><?= htmlspecialchars(Item::etiquetaAutor($item->getTipo())) ?></dt>
          <dd><?= htmlspecialchars($item->getAutor()) ?></dd>
          <?php if ($item->getIsbn()): ?>
            <dt>ISBN</dt>
            <dd><?= htmlspecialchars($item->getIsbn()) ?></dd>
          <?php endif; ?>
          <dt>Categoría</dt>
          <dd><?= htmlspecialchars($item->getCategoria() ?: '—') ?></dd>
        </dl>
      </div>

      <?php if ($item->getDescripcion()): ?>
        <div class="seccion-detalle">
          <h2 class="h6 mb-3">Descripción</h2>
          <div class="descripcion-item">
            <?php // Un párrafo por cada bloque separado por una línea en blanco. ?>
            <?php foreach (preg_split('/\R\s*\R/', $item->getDescripcion()) as $parrafo): ?>
              <p><?= nl2br(htmlspecialchars(trim($parrafo))) ?></p>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($item->getIsbn()): ?>
        <p class="small text-body-secondary mt-4 mb-0">
          Más datos de este libro en
          <a href="https://openlibrary.org/isbn/<?= htmlspecialchars($item->getIsbn()) ?>" target="_blank" rel="noopener" class="link-secondary">Open Library</a>.
        </p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
