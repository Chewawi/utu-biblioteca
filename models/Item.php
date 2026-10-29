<?php
// ============================================================
//  Modelo Item
//  Sabe hablar con la tabla "items". Un item es cualquier cosa
//  que se puede pedir prestada: un libro, un juego o un proyecto.
// ============================================================

class Item {
    // Tienen que coincidir con el ENUM de la columna "tipo" en database.sql.
    const TIPOS = ['libro', 'juego', 'proyecto'];

    // Nombre de cada tipo en plural, para las pestañas del catálogo.
    const PLURALES = ['libro' => 'Libros', 'juego' => 'Juegos', 'proyecto' => 'Proyectos'];

    private $id;
    private $tipo;
    private $titulo;
    private $autor;
    private $categoria;
    private $stock;
    private $imagen;
    private $prestados; // ejemplares prestados ahora mismo (no viene de una columna, se cuenta)

    public function __construct($id, $tipo, $titulo, $autor, $categoria, $stock, $imagen, $prestados) {
        $this->id = $id;
        $this->tipo = $tipo;
        $this->titulo = $titulo;
        $this->autor = $autor;
        $this->categoria = $categoria;
        $this->stock = $stock;
        $this->imagen = $imagen;
        $this->prestados = $prestados;
    }

    // --- getters ---
    public function getId() { return $this->id; }
    public function getTipo() { return $this->tipo; }
    public function getTitulo() { return $this->titulo; }
    public function getAutor() { return $this->autor; }
    public function getCategoria() { return $this->categoria; }
    public function getStock() { return $this->stock; }
    public function getImagen() { return $this->imagen; }
    public function getPrestados() { return $this->prestados; }
    public function getDisponibles() { return $this->stock - $this->prestados; }

    /** Cómo se llama el campo "autor" según el tipo: un libro tiene autor, el resto un creador. '' = todos los tipos. */
    public static function etiquetaAutor($tipo) {
        if ($tipo === 'libro') {
            return 'Autor';
        }
        return $tipo === '' ? 'Autor o creador' : 'Creador';
    }

    // --- acceso a datos ---

    /*
     * Las consultas de lectura traen además la columna "prestados": una
     * subconsulta que cuenta los préstamos activos de cada item.
     */

    /**
     * Items del catálogo, filtrados y paginados.
     * $filtros es un array con estas claves (todas opcionales):
     *   q          -> texto que se busca en el título o el autor,
     *   tipo       -> uno de TIPOS; '' = todos,
     *   categoria  -> una categoría (el género); '' = todas,
     *   pagina     -> número de página (si está fuera de rango se corrige),
     *   por_pagina -> cuántos por página; 0 o sin poner = todos, sin paginar.
     * Devuelve ['filas' => items de esa página, 'total' => cantidad de resultados,
     *           'pagina' => página que se muestra, 'paginas' => cantidad de páginas].
     */
    public static function listar($pdo, $filtros = []) {
        $filtros = array_merge(['q' => '', 'tipo' => '', 'categoria' => '', 'pagina' => 1, 'por_pagina' => 0], $filtros);

        $condiciones = [];
        $parametros = [];
        if ($filtros['q'] !== '') {
            $condiciones[] = '(i.titulo LIKE ? OR i.autor LIKE ?)';
            $parametros[] = '%' . $filtros['q'] . '%';
            $parametros[] = '%' . $filtros['q'] . '%';
        }
        if ($filtros['tipo'] !== '') {
            $condiciones[] = 'i.tipo = ?';
            $parametros[] = $filtros['tipo'];
        }
        if ($filtros['categoria'] !== '') {
            $condiciones[] = 'i.categoria = ?';
            $parametros[] = $filtros['categoria'];
        }

        // El FROM y el WHERE son los mismos para contar y para traer las filas.
        $desdeDonde = ' FROM items i';
        if (!empty($condiciones)) {
            $desdeDonde .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        $stmt = $pdo->prepare('SELECT COUNT(*)' . $desdeDonde);
        $stmt->execute($parametros);
        $total = (int) $stmt->fetchColumn();

        $sql = "
            SELECT i.*,
                   (SELECT COUNT(*) FROM prestamos p WHERE p.item_id = i.id AND p.estado = 'activo') AS prestados
        " . $desdeDonde . ' ORDER BY i.titulo';

        $pagina = 1;
        $paginas = 1;
        if ($filtros['por_pagina'] > 0) {
            $paginacion = calcularPaginacion($total, $filtros['pagina'], $filtros['por_pagina']);
            $pagina = $paginacion['pagina'];
            $paginas = $paginacion['paginas'];
            // LIMIT y OFFSET son enteros que calculamos nosotros (no texto del usuario),
            // por eso se pueden poner directo en el SQL.
            $sql .= ' LIMIT ' . (int) $filtros['por_pagina'] . ' OFFSET ' . $paginacion['desde'];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);
        $filas = $stmt->fetchAll();

        $items = [];
        foreach ($filas as $f) {
            $items[] = new Item($f['id'], $f['tipo'], $f['titulo'], $f['autor'], $f['categoria'], $f['stock'], $f['imagen'], $f['prestados']);
        }
        return ['filas' => $items, 'total' => $total, 'pagina' => $pagina, 'paginas' => $paginas];
    }

    /**
     * Cuántos items de cada tipo coinciden con la búsqueda $q y la categoría
     * $categoria (para los contadores de las pestañas). Devuelve, por ejemplo,
     * ['libro' => 8, 'juego' => 2, 'proyecto' => 0].
     */
    public static function contarPorTipo($pdo, $q = '', $categoria = '') {
        $conteo = array_fill_keys(self::TIPOS, 0); // los tipos sin items quedan en 0

        $condiciones = [];
        $parametros = [];
        if ($q !== '') {
            $condiciones[] = '(titulo LIKE ? OR autor LIKE ?)';
            $parametros[] = '%' . $q . '%';
            $parametros[] = '%' . $q . '%';
        }
        if ($categoria !== '') {
            $condiciones[] = 'categoria = ?';
            $parametros[] = $categoria;
        }
        $sql = 'SELECT tipo, COUNT(*) AS cantidad FROM items';
        if (!empty($condiciones)) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }
        $stmt = $pdo->prepare($sql . ' GROUP BY tipo');
        $stmt->execute($parametros);
        foreach ($stmt->fetchAll() as $f) {
            $conteo[$f['tipo']] = (int) $f['cantidad'];
        }
        return $conteo;
    }

    /**
     * Las categorías (géneros) que ya usa algún item, en orden alfabético y sin
     * repetir. La comparación de MySQL no distingue mayúsculas ni tildes, así
     * que "novela" y "Novela" cuentan como una sola.
     */
    public static function categorias($pdo) {
        $sql = "SELECT MIN(categoria) FROM items WHERE categoria <> '' GROUP BY categoria ORDER BY categoria";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function buscarPorId($pdo, $id) {
        $stmt = $pdo->prepare("
            SELECT i.*,
                   (SELECT COUNT(*) FROM prestamos p WHERE p.item_id = i.id AND p.estado = 'activo') AS prestados
            FROM items i
            WHERE i.id = ?
        ");
        $stmt->execute([$id]);
        $f = $stmt->fetch();
        return $f ? new Item($f['id'], $f['tipo'], $f['titulo'], $f['autor'], $f['categoria'], $f['stock'], $f['imagen'], $f['prestados']) : null;
    }

    public static function crear($pdo, $tipo, $titulo, $autor, $categoria, $stock, $imagen) {
        $stmt = $pdo->prepare(
            "INSERT INTO items (tipo, titulo, autor, categoria, stock, imagen) VALUES (?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([$tipo, $titulo, $autor, $categoria, $stock, $imagen]);
    }

    public static function actualizar($pdo, $id, $tipo, $titulo, $autor, $categoria, $stock, $imagen) {
        $stmt = $pdo->prepare(
            "UPDATE items SET tipo = ?, titulo = ?, autor = ?, categoria = ?, stock = ?, imagen = ? WHERE id = ?"
        );
        return $stmt->execute([$tipo, $titulo, $autor, $categoria, $stock, $imagen, $id]);
    }

    public static function eliminar($pdo, $id) {
        $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** true si el item tiene préstamos, activos o ya devueltos (historial). */
    public static function tienePrestamos($pdo, $id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM prestamos WHERE item_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn() > 0;
    }
}
