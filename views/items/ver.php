<?php
$tituloPagina = $item->getTitulo();
require __DIR__ . '/../header.php';
?>

<p class="mb-3 d-print-none">
  <a href="index.php?accion=listarItems" class="link-secondary small">← Volver al catálogo</a>
</p>

<div class="row g-4">
  <div class="col-6 col-sm-4 col-lg-3">
    <img src="uploads/<?= htmlspecialchars($item->getImagen()) ?>" alt="Portada de «<?= htmlspecialchars($item->getTitulo()) ?>»" class="portada-detalle">
  </div>

  <div class="col-sm-8 col-lg-9">
    <div class="d-flex flex-wrap gap-1 mb-2">
      <span class="badge etiqueta"><?= htmlspecialchars(ucfirst($item->getTipo())) ?></span>
      <?php if ($item->getCategoria()): ?>
        <span class="badge etiqueta"><?= htmlspecialchars($item->getCategoria()) ?></span>
      <?php endif; ?>
    </div>
    <h1 class="h3 mb-1"><?= htmlspecialchars($item->getTitulo()) ?></h1>
    <p class="text-body-secondary mb-3"><?= htmlspecialchars($item->getAutor()) ?></p>

    <dl class="datos-item mb-3">
      <?php if ($item->getIsbn()): ?>
        <dt>ISBN</dt>
        <dd><?= htmlspecialchars($item->getIsbn()) ?></dd>
      <?php endif; ?>
      <dt>Disponibles</dt>
      <?php if ($item->getDisponibles() > 0): ?>
        <dd><span class="punto bg-success"></span><?= htmlspecialchars($item->getDisponibles()) ?> de <?= htmlspecialchars($item->getStock()) ?></dd>
      <?php else: ?>
        <dd class="text-danger-emphasis fw-medium"><span class="punto bg-danger"></span>0 de <?= htmlspecialchars($item->getStock()) ?></dd>
      <?php endif; ?>
    </dl>

    <?php if ($socio): ?>
      <div class="reserva-detalle mb-4">
        <?php $volverA = ['volver' => 'verItem']; ?>
        <?php require __DIR__ . '/reserva_socio.php'; ?>
      </div>
    <?php elseif (esAdmin()): ?>
      <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="index.php?accion=formEditarItem&amp;id=<?= $item->getId() ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
        <?php if ($item->getDisponibles() > 0): ?>
          <a href="index.php?accion=formCrearPrestamo&amp;item_id=<?= $item->getId() ?>" class="btn btn-sm btn-outline-secondary">Prestar</a>
        <?php endif; ?>
      </div>
    <?php elseif (!isset($_SESSION['usuario'])): ?>
      <p class="small text-body-secondary mb-4"><a href="index.php?accion=login">Iniciá sesión</a> para reservarlo.</p>
    <?php endif; ?>

    <?php if ($item->getDescripcion()): ?>
      <h2 class="h6">Descripción</h2>
      <div class="descripcion-item">
        <?php // Un párrafo por cada bloque separado por una línea en blanco. ?>
        <?php foreach (preg_split('/\R\s*\R/', $item->getDescripcion()) as $parrafo): ?>
          <p><?= nl2br(htmlspecialchars(trim($parrafo))) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($item->getIsbn()): ?>
      <p class="small text-body-secondary mb-0">
        Más datos de este libro en
        <a href="https://openlibrary.org/isbn/<?= htmlspecialchars($item->getIsbn()) ?>" target="_blank" rel="noopener">Open Library</a>.
      </p>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
