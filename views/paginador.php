<?php
// Paginador reutilizable. La vista que lo incluye tiene que tener $resultado,
// lo que devuelve el listar() de un modelo ('pagina', 'paginas' y 'total').
// Los links conservan los filtros de la URL; solo cambia ?p=.
$pagina  = $resultado['pagina'];
$paginas = $resultado['paginas'];
?>
<?php if ($paginas > 1): ?>
  <nav aria-label="Paginación" class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
    <small class="text-muted">
      Página <?= htmlspecialchars($pagina) ?> de <?= htmlspecialchars($paginas) ?> · <?= htmlspecialchars($resultado['total']) ?> resultados
    </small>
    <ul class="pagination mb-0">
      <?php if ($pagina > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= htmlspecialchars(urlPagina($pagina - 1)) ?>">Anterior</a></li>
      <?php else: ?>
        <li class="page-item disabled"><span class="page-link">Anterior</span></li>
      <?php endif; ?>
      <?php // Mostramos hasta 2 páginas a cada lado de la actual. ?>
      <?php for ($n = max(1, $pagina - 2); $n <= min($paginas, $pagina + 2); $n++): ?>
        <li class="page-item <?= $n === $pagina ? 'active' : '' ?>">
          <a class="page-link" href="<?= htmlspecialchars(urlPagina($n)) ?>"><?= htmlspecialchars($n) ?></a>
        </li>
      <?php endfor; ?>
      <?php if ($pagina < $paginas): ?>
        <li class="page-item"><a class="page-link" href="<?= htmlspecialchars(urlPagina($pagina + 1)) ?>">Siguiente</a></li>
      <?php else: ?>
        <li class="page-item disabled"><span class="page-link">Siguiente</span></li>
      <?php endif; ?>
    </ul>
  </nav>
<?php endif; ?>
