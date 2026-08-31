<?php require __DIR__ . '/../header.php'; ?> // <body> abierto

// todo esto esta dentro del <body> de header.php

<h1>Libros</h1>
<a href="index.php?accion=formCrear" class="btn btn-primary">Nuevo libro</a>

<table class="table mt-3">
<?php foreach ($libros as $libro): ?>
  <tr>
    <td><img src="uploads/<?= $libro->getImagen() ?>" width="60"></td>
    <td><?= htmlspecialchars($libro->getTitulo()) ?></td>
    <td>
      <a href="index.php?accion=formEditar&id=<?= $libro->getId() ?>">Editar</a>
      <a href="index.php?accion=eliminar&id=<?= $libro->getId() ?>">Eliminar</a>
    </td>
  </tr>
<?php endforeach; ?>
</table>

<?php require __DIR__ . '/../footer.php'; ?> // <body> cerrado