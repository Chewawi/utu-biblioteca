<?php require __DIR__ . '/../header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0"><?= htmlspecialchars($titulo) ?></h1>
  <?php if (esAdmin()): ?>
    <a href="index.php?accion=formCrearPrestamo" class="btn btn-primary">+ Nuevo préstamo</a>
  <?php endif; ?>
</div>

<form method="GET" action="index.php" class="row g-2 mb-4">
  <input type="hidden" name="accion" value="listarPrestamos">
  <div class="col-auto">
    <select name="estado" class="form-select">
      <option value="">Todos los estados</option>
      <option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>Activos</option>
      <option value="devuelto" <?= $estado === 'devuelto' ? 'selected' : '' ?>>Devueltos</option>
    </select>
  </div>
  <div class="col-auto">
    <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
    <?php if ($estado !== ''): ?>
      <a href="index.php?accion=listarPrestamos" class="btn btn-link">Limpiar</a>
    <?php endif; ?>
  </div>
</form>

<?php if (empty($prestamos)): ?>
  <div class="alert alert-secondary">No hay préstamos para mostrar.</div>
<?php else: ?>
  <table class="table align-middle">
    <thead>
      <tr>
        <?php if (esAdmin()): ?><th>Socio</th><?php endif; ?>
        <th>Ítem</th>
        <th>Estado</th>
        <th>Fecha de préstamo</th>
        <th>Fecha de devolución</th>
        <?php if (esAdmin()): ?><th></th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($prestamos as $p): ?>
        <tr class="<?= $p->estaActivo() ? '' : 'text-muted' ?>">
          <?php if (esAdmin()): ?><td><?= htmlspecialchars($p->getSocioNombre()) ?></td><?php endif; ?>
          <td><?= htmlspecialchars($p->getItemTitulo()) ?></td>
          <td>
            <?php if ($p->estaActivo()): ?>
              <span class="badge text-bg-warning">Activo</span>
            <?php else: ?>
              <span class="badge text-bg-success">Devuelto</span>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($p->getFechaPrestamo()) ?></td>
          <td><?= htmlspecialchars($p->getFechaDevolucion() ?? '—') ?></td>
          <?php if (esAdmin()): ?>
            <td class="text-end">
              <?php if ($p->estaActivo()): ?>
                <form method="POST" action="index.php?accion=devolverPrestamo" class="d-inline"
                  onsubmit="return confirm('¿Registrar la devolución de «<?= htmlspecialchars(addslashes($p->getItemTitulo())) ?>»?');">
                  <input type="hidden" name="id" value="<?= $p->getId() ?>">
                  <button type="submit" class="btn btn-sm btn-outline-success">Devolver</button>
                </form>
              <?php endif; ?>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php require __DIR__ . '/../footer.php'; ?>
