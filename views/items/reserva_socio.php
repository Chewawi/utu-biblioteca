<?php
// Lo que ve el socio logueado sobre $item: si ya lo tiene o lo reservó, lo
// decimos; si no, puede reservarlo. Lo usan las tarjetas del catálogo y la
// página del ítem. Además de $item necesita $prestadosDelSocio y
// $reservasDelSocio (ver socioYSusReservas()) y $volverA: los campos ocultos
// que dicen a qué página volver después de reservar.
$reserva = $reservasDelSocio[$item->getId()] ?? null;
?>
<?php if (in_array($item->getId(), $prestadosDelSocio)): ?>
  <a href="index.php?accion=listarPrestamos" class="badge rounded-pill text-wrap bg-warning-subtle text-warning-emphasis">Lo tenés prestado</a>
<?php elseif ($reserva && $reserva->estaLista()): ?>
  <a href="index.php?accion=listarReservas" class="badge rounded-pill text-wrap bg-success-subtle text-success-emphasis">Apartado hasta el <?= htmlspecialchars(formatoFecha($reserva->getFechaVencimiento())) ?></a>
<?php elseif ($reserva): ?>
  <a href="index.php?accion=listarReservas" class="badge rounded-pill text-wrap bg-warning-subtle text-warning-emphasis">En espera · puesto <?= htmlspecialchars($reserva->getPuesto()) ?></a>
<?php else: ?>
  <form method="POST" action="index.php?accion=crearReserva">
    <?= campoCsrf() ?>
    <?= camposOcultos(array_merge(['item_id' => $item->getId()], $volverA)) ?>
    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
      Reservar<span class="visually-hidden"> «<?= htmlspecialchars($item->getTitulo()) ?>»</span>
    </button>
  </form>
<?php endif; ?>
