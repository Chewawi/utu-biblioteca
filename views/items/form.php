<?php
$tituloPagina = isset($item) ? 'Editar ítem' : 'Nuevo ítem';
require __DIR__ . '/../header.php';
?>

<div class="encabezado">
  <h1 class="h3"><?= htmlspecialchars($tituloPagina) ?></h1>
</div>

<?php if (isset($error)): ?>
  <div class="alert alert-danger ancho-formulario"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php
// Si el formulario vuelve por un error, mostramos lo que se escribió ($_POST);
// si no, los datos guardados del ítem (al editar) o los valores iniciales (al crear).
$tipoElegido = $_POST['tipo'] ?? (isset($item) ? $item->getTipo() : 'libro');
$titulo      = $_POST['titulo'] ?? (isset($item) ? $item->getTitulo() : '');
$autor       = $_POST['autor'] ?? (isset($item) ? $item->getAutor() : '');
$categoria   = $_POST['categoria'] ?? (isset($item) ? ($item->getCategoria() ?? '') : '');
$isbn        = $_POST['isbn'] ?? (isset($item) ? ($item->getIsbn() ?? '') : '');
$descripcion = $_POST['descripcion'] ?? (isset($item) ? ($item->getDescripcion() ?? '') : '');
$stock       = $_POST['stock'] ?? (isset($item) ? $item->getStock() : '1');
?>

<div class="card ancho-formulario">
  <div class="card-body p-4">
    <form method="POST"
      action="index.php?accion=<?= isset($item) ? 'editarItem' : 'crearItem' ?>"
      enctype="multipart/form-data">
      <?= campoCsrf() ?>

      <?php if (isset($item)): ?>
        <input type="hidden" name="id" value="<?= $item->getId() ?>">
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label" for="tipo">Tipo</label>
        <select id="tipo" name="tipo" class="form-select" required>
          <?php foreach (Item::TIPOS as $tipo): ?>
            <option value="<?= htmlspecialchars($tipo) ?>" <?= $tipo === $tipoElegido ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($tipo)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label" for="titulo">Título</label>
        <input type="text" id="titulo" name="titulo" class="form-control" required maxlength="150" value="<?= htmlspecialchars($titulo) ?>">
      </div>

      <div class="mb-3">
        <label class="form-label" for="autor" id="etiqueta-autor"><?= htmlspecialchars(Item::etiquetaAutor($tipoElegido)) ?></label>
        <input type="text" id="autor" name="autor" class="form-control" required maxlength="100" value="<?= htmlspecialchars($autor) ?>">
      </div>

      <?php // Solo los libros tienen ISBN: para los otros tipos el campo se esconde (ver el script de abajo). ?>
      <div class="mb-3 solo-libros" <?= $tipoElegido === 'libro' ? '' : 'hidden' ?>>
        <label class="form-label" for="isbn">ISBN (opcional)</label>
        <input type="text" id="isbn" name="isbn" class="form-control" maxlength="20" inputmode="numeric" autocomplete="off"
          placeholder="978-84-376-0494-7" value="<?= htmlspecialchars($isbn) ?>">
        <div class="form-text">El código del libro, de 10 o 13 dígitos. Podés escribirlo con o sin guiones.</div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="categoria">Categoría (opcional)</label>
        <?php // list="categorias": al escribir, el navegador sugiere las categorías que ya existen. ?>
        <input type="text" id="categoria" name="categoria" class="form-control" maxlength="50" placeholder="Novela, Estrategia, Robótica..."
          list="categorias" autocomplete="off" value="<?= htmlspecialchars($categoria) ?>">
        <datalist id="categorias">
          <?php foreach ($categorias as $nombreCategoria): ?>
            <option value="<?= htmlspecialchars($nombreCategoria) ?>"></option>
          <?php endforeach; ?>
        </datalist>
        <div class="form-text">Elegí una de las sugeridas si ya existe, así no quedan repetidas.</div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="descripcion">Descripción (opcional)</label>
        <textarea id="descripcion" name="descripcion" class="form-control" rows="5" maxlength="<?= MAX_DESCRIPCION ?>"
          placeholder="De qué se trata, para quién es, qué trae..."><?= htmlspecialchars($descripcion) ?></textarea>
        <div class="form-text">Se ve en la página del ítem. Para separar párrafos, dejá una línea en blanco.</div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="stock">Stock (cantidad de ejemplares)</label>
        <input type="number" id="stock" name="stock" class="form-control" min="1" max="9999" step="1" required
          value="<?= htmlspecialchars($stock) ?>">
      </div>

      <?php if (isset($item)): ?>
        <div class="mb-2">
          <p class="form-label">Portada actual</p>
          <img src="uploads/<?= htmlspecialchars($item->getImagen()) ?>" alt="" width="80" class="rounded mb-2">
        </div>
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label" for="imagen">
          <?= isset($item) ? 'Reemplazar portada (opcional)' : 'Portada (opcional)' ?>
        </label>
        <input type="file" id="imagen" name="imagen" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        <div class="form-text">JPG, PNG o WEBP. Máximo 2&nbsp;MB.</div>
      </div>

      <?php // Otra forma de cargar la portada de un libro: pedírsela a Open Library con el ISBN (ver el script de abajo). ?>
      <div class="mb-3 solo-libros" <?= $tipoElegido === 'libro' ? '' : 'hidden' ?>>
        <input type="hidden" name="traer_portada" id="traer_portada" value="">
        <button type="button" id="boton-traer-portada" class="btn btn-sm btn-outline-secondary" <?= $isbn === '' ? 'disabled' : '' ?>>Traer portada</button>
        <div class="form-text">La busca en Open Library con el ISBN y guarda el ítem con esa portada.</div>
      </div>

      <div class="d-flex gap-2 pt-2">
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="index.php?accion=listarItems" class="btn btn-link">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<script>
  // Al cambiar el tipo, el campo "Autor" pasa a llamarse "Creador" (y al revés),
  // y el ISBN y "Traer portada" (clase solo-libros) aparecen solo para los libros.
  var tipo = document.getElementById('tipo');
  tipo.addEventListener('change', function () {
    document.getElementById('etiqueta-autor').textContent = tipo.value === 'libro' ? 'Autor' : 'Creador';
    document.querySelectorAll('.solo-libros').forEach(function (campo) {
      campo.hidden = tipo.value !== 'libro';
    });
  });

  // "Traer portada" se puede usar cuando hay un ISBN escrito.
  var isbn = document.getElementById('isbn');
  var botonPortada = document.getElementById('boton-traer-portada');
  isbn.addEventListener('input', function () {
    botonPortada.disabled = isbn.value.trim() === '';
  });

  // Manda el formulario con el campo oculto traer_portada en 1: así el
  // servidor sabe que tiene que pedirle la portada a Open Library.
  botonPortada.addEventListener('click', function () {
    var form = botonPortada.form;
    if (!form.reportValidity()) {
      return; // le falta algún campo obligatorio: el navegador lo marca, como al apretar Guardar
    }
    document.getElementById('traer_portada').value = '1';
    botonPortada.disabled = true;
    botonPortada.textContent = 'Buscando la portada...';
    form.submit();
  });
</script>

<?php require __DIR__ . '/../footer.php'; ?>
