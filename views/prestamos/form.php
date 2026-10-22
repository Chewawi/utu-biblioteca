<?php require __DIR__ . '/../header.php'; ?>

<h1 class="h3 mb-3">Nuevo préstamo</h1>

<?php if (isset($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="index.php?accion=crearPrestamo" class="col-md-6">
  <div class="mb-3">
    <label class="form-label">Socio</label>
    <select name="socio_id" class="form-select" required>
      <option value="">-- Elegir socio --</option>
      <?php foreach ($socios as $socio): ?>
        <option value="<?= $socio->getId() ?>"><?= htmlspecialchars($socio->getNombre()) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Ítem</label>
    <select name="item_id" class="form-select" required>
      <option value="">-- Elegir ítem --</option>
      <?php foreach ($items as $item): ?>
        <option value="<?= $item->getId() ?>" <?= $item->getDisponibles() < 1 ? 'disabled' : '' ?>>
          <?= htmlspecialchars($item->getTitulo()) ?> (<?= htmlspecialchars($item->getDisponibles()) ?> de <?= htmlspecialchars($item->getStock()) ?> disponibles)
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Fecha de préstamo</label>
    <input type="date" name="fecha_prestamo" class="form-control" value="<?= date('Y-m-d') ?>" required>
  </div>

  <button type="submit" class="btn btn-primary">Registrar préstamo</button>
  <a href="index.php?accion=listarPrestamos" class="btn btn-link">Cancelar</a>
</form>

<?php require __DIR__ . '/../footer.php'; ?>
