<?php require __DIR__ . '/../header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0"><?= htmlspecialchars($titulo) ?></h1>
  <?php if (esAdmin()): ?>
    <a href="index.php?accion=formCrearPrestamo" class="btn btn-primary">+ Nuevo préstamo</a>
  <?php endif; ?>
</div>

<form method="GET" action="index.php" class="row g-2 align-items-end mb-4">
  <input type="hidden" name="accion" value="listarPrestamos">

  <div class="col-md-4">
    <label class="form-label small mb-1">Buscar</label>
    <input type="text" name="q" class="form-control"
      placeholder="<?= esAdmin() ? 'Nombre del socio o título del ítem...' : 'Título del ítem...' ?>"
      value="<?= htmlspecialchars($filtros['q']) ?>">
  </div>

  <div class="col-md-2">
    <label class="form-label small mb-1">Estado</label>
    <select name="estado" class="form-select">
      <option value="">Todos</option>
      <option value="activo" <?= $filtros['estado'] === 'activo' ? 'selected' : '' ?>>Activos</option>
      <option value="devuelto" <?= $filtros['estado'] === 'devuelto' ? 'selected' : '' ?>>Devueltos</option>
    </select>
  </div>

  <div class="col-md-3">
    <label class="form-label small mb-1">Ítem</label>
    <select name="item_id" class="form-select select2-buscable">
      <option value="">Todos los ítems</option>
      <?php foreach ($items as $item): ?>
        <option value="<?= $item->getId() ?>" <?= $filtros['item_id'] == $item->getId() ? 'selected' : '' ?>><?= htmlspecialchars($item->getTitulo()) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <?php if (esAdmin()): ?>
    <div class="col-md-3">
      <label class="form-label small mb-1">Socio</label>
      <select name="socio_id" class="form-select select2-buscable">
        <option value="">Todos los socios</option>
        <?php foreach ($socios as $socio): ?>
          <option value="<?= $socio->getId() ?>" <?= $filtros['socio_id'] == $socio->getId() ? 'selected' : '' ?>><?= htmlspecialchars($socio->getNombre()) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  <?php endif; ?>

  <div class="col-md-3">
    <label class="form-label small mb-1">Prestado desde</label>
    <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($filtros['desde']) ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label small mb-1">Prestado hasta</label>
    <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($filtros['hasta']) ?>">
  </div>

  <div class="col-md-auto">
    <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
    <a href="index.php?accion=listarPrestamos" class="btn btn-link">Limpiar filtros</a>
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
                  <?php // Mandamos también los filtros actuales, para volver a la lista tal como estaba. ?>
                  <?php foreach ($filtros as $clave => $valor): ?>
                    <?php if ($valor !== ''): ?>
                      <input type="hidden" name="<?= htmlspecialchars($clave) ?>" value="<?= htmlspecialchars($valor) ?>">
                    <?php endif; ?>
                  <?php endforeach; ?>
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
