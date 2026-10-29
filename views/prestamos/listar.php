<?php require __DIR__ . '/../header.php'; ?>

<div class="encabezado">
  <h1 class="h3"><?= htmlspecialchars($titulo) ?></h1>
  <?php if (esAdmin()): ?>
    <a href="index.php?accion=formCrearPrestamo" class="btn btn-primary">+ Nuevo préstamo</a>
  <?php endif; ?>
</div>

<form method="GET" action="index.php" class="card mb-4">
  <div class="card-body">
    <input type="hidden" name="accion" value="listarPrestamos">
    <div class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label">Buscar</label>
        <input type="search" name="q" class="form-control"
          placeholder="<?= esAdmin() ? 'Nombre del socio o título del ítem...' : 'Título del ítem...' ?>"
          value="<?= htmlspecialchars($filtros['q']) ?>">
      </div>

      <div class="col-md-2">
        <label class="form-label">Estado</label>
        <select name="estado" class="form-select">
          <option value="">Todos</option>
          <option value="activo" <?= $filtros['estado'] === 'activo' ? 'selected' : '' ?>>Activos</option>
          <option value="vencido" <?= $filtros['estado'] === 'vencido' ? 'selected' : '' ?>>Vencidos</option>
          <option value="devuelto" <?= $filtros['estado'] === 'devuelto' ? 'selected' : '' ?>>Devueltos</option>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Ítem</label>
        <select name="item_id" class="form-select select2-buscable">
          <option value="">Todos los ítems</option>
          <?php foreach ($items as $item): ?>
            <option value="<?= $item->getId() ?>" <?= $filtros['item_id'] == $item->getId() ? 'selected' : '' ?>><?= htmlspecialchars($item->getTitulo()) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <?php if (esAdmin()): ?>
        <div class="col-md-3">
          <label class="form-label">Socio</label>
          <select name="socio_id" class="form-select select2-buscable">
            <option value="">Todos los socios</option>
            <?php foreach ($socios as $socio): ?>
              <option value="<?= $socio->getId() ?>" <?= $filtros['socio_id'] == $socio->getId() ? 'selected' : '' ?>><?= htmlspecialchars($socio->getNombre()) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>

      <div class="col-6 col-md-3">
        <label class="form-label">Prestado desde</label>
        <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($filtros['desde']) ?>">
      </div>

      <div class="col-6 col-md-3">
        <label class="form-label">Prestado hasta</label>
        <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($filtros['hasta']) ?>">
      </div>

      <div class="col-md-auto d-flex gap-2">
        <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
        <a href="index.php?accion=listarPrestamos" class="btn btn-link">Limpiar filtros</a>
      </div>
    </div>
  </div>
</form>

<?php if (empty($prestamos)): ?>
  <div class="card estado-vacio">
    <p class="titulo">No hay préstamos para mostrar.</p>
    <p class="small mb-0">Si filtraste, probá con otros filtros.</p>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <?php if (esAdmin()): ?><th>Socio</th><?php endif; ?>
            <th>Ítem</th>
            <th>Estado</th>
            <th>Fecha de préstamo</th>
            <th>Fecha límite</th>
            <th>Fecha de devolución</th>
            <?php if (esAdmin()): ?><th><span class="visually-hidden">Acciones</span></th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($prestamos as $p): ?>
            <tr class="<?= $p->estaActivo() ? '' : 'text-body-secondary' ?>">
              <?php if (esAdmin()): ?><td class="fw-medium"><?= htmlspecialchars($p->getSocioNombre()) ?></td><?php endif; ?>
              <td><?= htmlspecialchars($p->getItemTitulo()) ?></td>
              <td>
                <?php if ($p->estaVencido()): ?>
                  <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis">Vencido</span>
                <?php elseif ($p->estaActivo()): ?>
                  <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">Activo</span>
                <?php else: ?>
                  <span class="badge rounded-pill bg-success-subtle text-success-emphasis">Devuelto</span>
                <?php endif; ?>
              </td>
              <td class="text-nowrap"><?= htmlspecialchars(formatoFecha($p->getFechaPrestamo())) ?></td>
              <td class="text-nowrap <?= $p->estaVencido() ? 'text-danger-emphasis fw-medium' : '' ?>"><?= htmlspecialchars(formatoFecha($p->getFechaLimite())) ?></td>
              <td class="text-nowrap"><?= htmlspecialchars(formatoFecha($p->getFechaDevolucion())) ?></td>
              <?php if (esAdmin()): ?>
                <td class="text-end">
                  <?php if ($p->estaActivo()): ?>
                    <form method="POST" action="index.php?accion=devolverPrestamo" class="d-inline"
                      data-confirmar="¿Registrar la devolución de «<?= htmlspecialchars($p->getItemTitulo()) ?>»? Queda con fecha de hoy."
                      data-boton="Registrar devolución" data-color="primary">
                      <?= campoCsrf() ?>
                      <input type="hidden" name="id" value="<?= $p->getId() ?>">
                      <?php // Mandamos también los filtros y la página actuales, para volver a la lista tal como estaba. ?>
                      <?php foreach ($filtros as $clave => $valor): ?>
                        <?php if ($valor !== ''): ?>
                          <input type="hidden" name="<?= htmlspecialchars($clave) ?>" value="<?= htmlspecialchars($valor) ?>">
                        <?php endif; ?>
                      <?php endforeach; ?>
                      <input type="hidden" name="p" value="<?= htmlspecialchars($resultado['pagina']) ?>">
                      <button type="submit" class="btn btn-sm btn-outline-secondary">Devolver</button>
                    </form>
                  <?php endif; ?>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../paginador.php'; ?>

<?php require __DIR__ . '/../footer.php'; ?>
