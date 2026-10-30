<?php require __DIR__ . '/../header.php'; ?>

<div class="encabezado">
  <h1 class="h3">Nuevo préstamo</h1>
</div>

<?php if (isset($error)): ?>
  <div class="alert alert-danger ancho-formulario"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($reserva): ?>
  <div class="alert alert-info ancho-formulario">
    Retiro de una reserva: «<?= htmlspecialchars($reserva->getItemTitulo()) ?>» está apartado para
    <?= htmlspecialchars($reserva->getSocioNombre()) ?> hasta el <?= htmlspecialchars(formatoFecha($reserva->getFechaVencimiento())) ?>.
    Al registrar el préstamo, la reserva queda como retirada.
  </div>
<?php endif; ?>

<div class="card ancho-formulario">
  <div class="card-body p-4">
    <form method="POST" action="index.php?accion=crearPrestamo">
      <?= campoCsrf() ?>
      <div class="mb-3">
        <label class="form-label" for="socio_id">Socio</label>
        <select id="socio_id" name="socio_id" class="form-select select2-buscable" required>
          <option value="">-- Elegir socio --</option>
          <?php foreach ($socios as $socio): ?>
            <option value="<?= $socio->getId() ?>" <?= $socioId == $socio->getId() ? 'selected' : '' ?>><?= htmlspecialchars($socio->getNombre()) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label" for="item_id">Ítem</label>
        <select id="item_id" name="item_id" class="form-select select2-buscable" required>
          <option value="">-- Elegir ítem --</option>
          <?php foreach ($items as $item): ?>
            <?php // Los ítems sin ejemplares libres se ven, pero no se pueden elegir; salvo el que este socio tiene apartado. ?>
            <?php $esElReservado = $reserva && $reserva->getItemId() == $item->getId(); ?>
            <option value="<?= $item->getId() ?>"
              <?= $item->getDisponibles() < 1 && !$esElReservado ? 'disabled' : '' ?>
              <?= $itemId == $item->getId() ? 'selected' : '' ?>>
              <?= htmlspecialchars($item->getTitulo()) ?>
              (<?= $esElReservado ? 'apartado por su reserva' : htmlspecialchars($item->getDisponibles()) . ' de ' . htmlspecialchars($item->getStock()) . ' disponibles' ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label" for="fecha_prestamo">Fecha de préstamo</label>
        <input type="date" id="fecha_prestamo" name="fecha_prestamo" class="form-control" max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['fecha_prestamo'] ?? date('Y-m-d')) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label" for="fecha_limite">Fecha límite de devolución</label>
        <?php // Por defecto, dos semanas desde hoy. ?>
        <input type="date" id="fecha_limite" name="fecha_limite" class="form-control" value="<?= htmlspecialchars($_POST['fecha_limite'] ?? date('Y-m-d', strtotime('+14 days'))) ?>" required>
        <div class="form-text">Tiene que ser posterior a la fecha de préstamo.</div>
      </div>

      <div class="d-flex gap-2 pt-2">
        <button type="submit" class="btn btn-primary">Registrar préstamo</button>
        <a href="index.php?accion=<?= $reserva ? 'listarReservas' : 'listarPrestamos' ?>" class="btn btn-link">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
