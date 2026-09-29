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
    <label class="form-label">Libro</label>
    <select name="libro_id" class="form-select" required>
      <option value="">-- Elegir libro --</option>
      <?php foreach ($libros as $libro): ?>
        <option value="<?= $libro->getId() ?>"><?= htmlspecialchars($libro->getTitulo()) ?></option>
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
