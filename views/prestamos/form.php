<?php require __DIR__ . '/../header.php'; ?>

<div class="encabezado">
  <h1 class="h3">Nuevo préstamo</h1>
</div>

<?php if (isset($error)): ?>
  <div class="alert alert-danger ancho-formulario"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card ancho-formulario">
  <div class="card-body p-4">
    <form method="POST" action="index.php?accion=crearPrestamo">
      <?= campoCsrf() ?>
      <div class="mb-3">
        <label class="form-label">Socio</label>
        <select name="socio_id" class="form-select select2-buscable" required>
          <option value="">-- Elegir socio --</option>
          <?php foreach ($socios as $socio): ?>
            <option value="<?= $socio->getId() ?>" <?= ($_POST['socio_id'] ?? '') == $socio->getId() ? 'selected' : '' ?>><?= htmlspecialchars($socio->getNombre()) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Ítem</label>
        <select name="item_id" class="form-select select2-buscable" required>
          <option value="">-- Elegir ítem --</option>
          <?php foreach ($items as $item): ?>
            <?php // Los ítems sin ejemplares libres se ven, pero no se pueden elegir. ?>
            <option value="<?= $item->getId() ?>"
              <?= $item->getDisponibles() < 1 ? 'disabled' : '' ?>
              <?= ($_POST['item_id'] ?? '') == $item->getId() ? 'selected' : '' ?>>
              <?= htmlspecialchars($item->getTitulo()) ?> (<?= htmlspecialchars($item->getDisponibles()) ?> de <?= htmlspecialchars($item->getStock()) ?> disponibles)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Fecha de préstamo</label>
        <input type="date" name="fecha_prestamo" class="form-control" max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['fecha_prestamo'] ?? date('Y-m-d')) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Fecha límite de devolución</label>
        <?php // Por defecto, dos semanas desde hoy. ?>
        <input type="date" name="fecha_limite" class="form-control" value="<?= htmlspecialchars($_POST['fecha_limite'] ?? date('Y-m-d', strtotime('+14 days'))) ?>" required>
        <div class="form-text">Tiene que ser posterior a la fecha de préstamo.</div>
      </div>

      <div class="d-flex gap-2 pt-2">
        <button type="submit" class="btn btn-primary">Registrar préstamo</button>
        <a href="index.php?accion=listarPrestamos" class="btn btn-link">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
