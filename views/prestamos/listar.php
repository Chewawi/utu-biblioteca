<?php require __DIR__ . '/../header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0"><?= htmlspecialchars($titulo) ?></h1>
  <?php if (esAdmin()): ?>
    <a href="index.php?accion=formCrearPrestamo" class="btn btn-primary">+ Nuevo préstamo</a>
  <?php endif; ?>
</div>

<?php if (empty($prestamos)): ?>
  <div class="alert alert-secondary">No hay préstamos para mostrar.</div>
<?php else: ?>
  <table class="table align-middle">
    <thead>
      <tr>
        <?php if (esAdmin()): ?><th>Socio</th><?php endif; ?>
        <th>Libro</th>
        <th>Fecha de préstamo</th>
        <?php if (esAdmin()): ?><th></th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($prestamos as $p): ?>
        <tr>
          <?php if (esAdmin()): ?><td><?= htmlspecialchars($p->getSocioNombre()) ?></td><?php endif; ?>
          <td><?= htmlspecialchars($p->getLibroTitulo()) ?></td>
          <td><?= htmlspecialchars($p->getFechaPrestamo()) ?></td>
          <?php if (esAdmin()): ?>
            <td class="text-end">
              <form method="POST" action="index.php?accion=eliminarPrestamo" class="d-inline"
                onsubmit="return confirm('¿Registrar la devolución de «<?= htmlspecialchars(addslashes($p->getLibroTitulo())) ?>»?');">
                <input type="hidden" name="id" value="<?= $p->getId() ?>">
                <button type="submit" class="btn btn-sm btn-outline-success">Devolver</button>
              </form>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php require __DIR__ . '/../footer.php'; ?>
