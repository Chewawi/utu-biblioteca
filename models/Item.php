<?php
require_once __DIR__ . '/Reserva.php';

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
    private $isbn;        // sin guiones; null si no tiene
    private $descripcion; // null si no tiene
    private $stock;
    private $imagen;
    // Estos dos no vienen de una columna, se cuentan (ver selectConUso()):
    private $prestados; // ejemplares prestados ahora mismo
    private $apartados; // ejemplares apartados para una reserva lista que no venció

    public function __construct($id, $tipo, $titulo, $autor, $categoria, $isbn, $descripcion, $stock, $imagen, $prestados, $apartados) {
        $this->id = $id;
        $this->tipo = $tipo;
        $this->titulo = $titulo;
        $this->autor = $autor;
        $this->categoria = $categoria;
        $this->isbn = $isbn;
        $this->descripcion = $descripcion;
        $this->stock = $stock;
        $this->imagen = $imagen;
        $this->prestados = $prestados;
        $this->apartados = $apartados;
    }

    // --- getters ---
    public function getId() { return $this->id; }
    public function getTipo() { return $this->tipo; }
    public function getTitulo() { return $this->titulo; }
    public function getAutor() { return $this->autor; }
    public function getCategoria() { return $this->categoria; }
    public function getIsbn() { return $this->isbn; }
    public function getDescripcion() { return $this->descripcion; }
    public function getStock() { return $this->stock; }
    public function getImagen() { return $this->imagen; }
    public function getPrestados() { return $this->prestados; }
    public function getApartados() { return $this->apartados; }
    // La regla de "disponible" de todo el sistema: los que no están prestados ni apartados.
    public function getDisponibles() { return $this->stock - $this->prestados - $this->apartados; }

    /** Cómo se llama el campo "autor" según el tipo: un libro tiene autor, el resto un creador. '' = todos los tipos. */
    public static function etiquetaAutor($tipo) {
        if ($tipo === 'libro') {
            return 'Autor';
        }
        return $tipo === '' ? 'Autor o creador' : 'Creador';
    }

    /** El ISBN como se guarda: sin guiones ni espacios y con la X en mayúscula. "84-376-0494-X" -> "843760494X". */
    public static function normalizarIsbn($isbn) {
        return strtoupper(str_replace(['-', ' '], '', $isbn));
    }

    /**
     * true si $isbn (ya normalizado) es un ISBN-10 o un ISBN-13 con el dígito
     * verificador (el último) bien. Así se detectan casi todos los errores de tipeo.
     */
    public static function isbnValido($isbn) {
        $suma = 0;
        if (preg_match('/^[0-9]{9}[0-9X]$/', $isbn)) {
            // ISBN-10: cada dígito por 10, 9, 8... 1 (la X final vale 10). La suma tiene que ser múltiplo de 11.
            for ($i = 0; $i < 10; $i++) {
                $digito = $isbn[$i] === 'X' ? 10 : (int) $isbn[$i];
                $suma += $digito * (10 - $i);
            }
            return $suma % 11 === 0;
        }
        if (preg_match('/^[0-9]{13}$/', $isbn)) {
            // ISBN-13: los dígitos se multiplican por 1 y por 3, alternados. La suma tiene que ser múltiplo de 10.
            for ($i = 0; $i < 13; $i++) {
                $suma += (int) $isbn[$i] * ($i % 2 === 0 ? 1 : 3);
            }
            return $suma % 10 === 0;
        }
        return false;
    }

    // --- acceso a datos ---

    /**
     * El SELECT de las consultas de lectura. Además de las columnas de items
     * cuenta, para cada uno, los "prestados" (sus préstamos activos) y los
     * "apartados" (sus reservas listas que no vencieron). Con eso
     * getDisponibles() calcula los disponibles. Lleva un "?" con la fecha de
     * hoy, que va antes que los del WHERE.
     */
    private static function selectConUso() {
        return "
            SELECT i.*,
                   (SELECT COUNT(*) FROM prestamos p WHERE p.item_id = i.id AND p.estado = 'activo') AS prestados,
                   (SELECT COUNT(*) FROM reservas r WHERE r.item_id = i.id AND " . Reserva::LISTA_VIGENTE . ") AS apartados
            FROM items i
        ";
    }

    /** Arma un Item con una fila de selectConUso(). */
    private static function desdeFila($f) {
        return new Item($f['id'], $f['tipo'], $f['titulo'], $f['autor'], $f['categoria'], $f['isbn'], $f['descripcion'], $f['stock'], $f['imagen'], $f['prestados'], $f['apartados']);
    }

    /**
     * Condición del WHERE para buscar $q en el título, el autor, la categoría o
     * el ISBN. El ISBN se guarda sin guiones, así que al buscarlo se los sacamos
     * a $q: "978-0-307" encuentra 9780307474728. Devuelve [condición, parámetros].
     */
    private static function condicionBusqueda($q) {
        $condicion = '(i.titulo LIKE ? OR i.autor LIKE ? OR i.categoria LIKE ?';
        $parametros = ['%' . $q . '%', '%' . $q . '%', '%' . $q . '%'];
        $isbn = self::normalizarIsbn($q);
        if ($isbn !== '') { // si $q era solo guiones, no buscamos por ISBN
            $condicion .= ' OR i.isbn LIKE ?';
            $parametros[] = '%' . $isbn . '%';
        }
        return [$condicion . ')', $parametros];
    }

    /**
     * Items del catálogo, filtrados y paginados.
     * $filtros es un array con estas claves (todas opcionales):
     *   q          -> texto que se busca en el título, el autor, la categoría o el ISBN,
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
            [$condicion, $parametrosBusqueda] = self::condicionBusqueda($filtros['q']);
            $condiciones[] = $condicion;
            $parametros = array_merge($parametros, $parametrosBusqueda);
        }
        if ($filtros['tipo'] !== '') {
            $condiciones[] = 'i.tipo = ?';
            $parametros[] = $filtros['tipo'];
        }
        if ($filtros['categoria'] !== '') {
            $condiciones[] = 'i.categoria = ?';
            $parametros[] = $filtros['categoria'];
        }

        // El WHERE es el mismo para contar y para traer las filas.
        $where = empty($condiciones) ? '' : ' WHERE ' . implode(' AND ', $condiciones);

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM items i' . $where);
        $stmt->execute($parametros);
        $total = (int) $stmt->fetchColumn();

        $sql = self::selectConUso() . $where . ' ORDER BY i.titulo';

        $paginacion = calcularPaginacion($total, $filtros['pagina'], $filtros['por_pagina']);
        $sql .= $paginacion['limite'];

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([date('Y-m-d')], $parametros));

        $items = [];
        foreach ($stmt->fetchAll() as $f) {
            $items[] = self::desdeFila($f);
        }
        return ['filas' => $items, 'total' => $total, 'pagina' => $paginacion['pagina'], 'paginas' => $paginacion['paginas']];
    }

    /** Todos los items como [id => título], para los desplegables de los filtros. */
    public static function titulos($pdo) {
        return $pdo->query('SELECT id, titulo FROM items ORDER BY titulo')->fetchAll(PDO::FETCH_KEY_PAIR);
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
            [$condicion, $parametrosBusqueda] = self::condicionBusqueda($q);
            $condiciones[] = $condicion;
            $parametros = array_merge($parametros, $parametrosBusqueda);
        }
        if ($categoria !== '') {
            $condiciones[] = 'i.categoria = ?';
            $parametros[] = $categoria;
        }
        $sql = 'SELECT i.tipo, COUNT(*) AS cantidad FROM items i';
        if (!empty($condiciones)) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }
        $stmt = $pdo->prepare($sql . ' GROUP BY i.tipo');
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

    /**
     * Si otro item ya usa la categoría $categoria escrita de otra forma
     * ("novela" o "Novéla" en vez de "Novela"), devuelve esa forma, para no
     * guardar variantes del mismo nombre. Si no, la devuelve tal cual.
     * $id es el item que se está editando (null al crear): no se compara consigo mismo.
     */
    public static function unificarCategoria($pdo, $categoria, $id = null) {
        if ($categoria === '') {
            return '';
        }
        $stmt = $pdo->prepare('SELECT categoria FROM items WHERE categoria = ? AND id <> ? LIMIT 1');
        $stmt->execute([$categoria, $id ?? 0]);
        $existente = $stmt->fetchColumn();
        return $existente === false ? $categoria : $existente;
    }

    public static function buscarPorId($pdo, $id) {
        $stmt = $pdo->prepare(self::selectConUso() . ' WHERE i.id = ?');
        $stmt->execute([date('Y-m-d'), $id]);
        $f = $stmt->fetch();
        return $f ? self::desdeFila($f) : null;
    }

    /**
     * Título del item que ya tiene el ISBN $isbn, o null si ninguno lo tiene.
     * $id es el item que se está editando (null al crear): no se compara consigo mismo.
     */
    public static function tituloConIsbn($pdo, $isbn, $id = null) {
        $stmt = $pdo->prepare('SELECT titulo FROM items WHERE isbn = ? AND id <> ?');
        $stmt->execute([$isbn, $id ?? 0]);
        $titulo = $stmt->fetchColumn();
        return $titulo === false ? null : $titulo;
    }

    // $isbn y $descripcion van como null si están vacíos (sin ISBN, sin descripción).
    public static function crear($pdo, $tipo, $titulo, $autor, $categoria, $isbn, $descripcion, $stock, $imagen) {
        $stmt = $pdo->prepare(
            "INSERT INTO items (tipo, titulo, autor, categoria, isbn, descripcion, stock, imagen) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([$tipo, $titulo, $autor, $categoria, $isbn, $descripcion, $stock, $imagen]);
    }

    public static function actualizar($pdo, $id, $tipo, $titulo, $autor, $categoria, $isbn, $descripcion, $stock, $imagen) {
        $stmt = $pdo->prepare(
            "UPDATE items SET tipo = ?, titulo = ?, autor = ?, categoria = ?, isbn = ?, descripcion = ?, stock = ?, imagen = ? WHERE id = ?"
        );
        return $stmt->execute([$tipo, $titulo, $autor, $categoria, $isbn, $descripcion, $stock, $imagen, $id]);
    }

    public static function eliminar($pdo, $id) {
        $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** true si el item tiene préstamos o reservas, aunque sean viejos (historial). */
    public static function tieneHistorial($pdo, $id) {
        $stmt = $pdo->prepare("
            SELECT (SELECT COUNT(*) FROM prestamos WHERE item_id = ?)
                 + (SELECT COUNT(*) FROM reservas WHERE item_id = ?)
        ");
        $stmt->execute([$id, $id]);
        return $stmt->fetchColumn() > 0;
    }
}
