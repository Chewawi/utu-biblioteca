<?php
require_once __DIR__ . '/Item.php';

// ============================================================
//  Modelo Reserva
//  Un socio reserva un item por adelantado. Si hay un ejemplar
//  disponible, la reserva queda 'lista' y se lo apartamos unos
//  días; si no, queda 'pendiente', en una cola por orden de
//  llegada: el id, porque cada reserva nueva tiene uno mayor.
// ============================================================

class Reserva {
    const DIAS_PARA_RETIRAR = 2; // días que queda apartado el ejemplar de una reserva lista
    const MAXIMO_ACTIVAS = 3;    // reservas activas que puede tener un socio a la vez

    // Opciones del filtro de estado: las de la columna más 'vencida', que no se
    // guarda: es una reserva 'lista' con la fecha de vencimiento pasada.
    const FILTROS_ESTADO = ['lista', 'pendiente', 'vencida', 'retirada', 'cancelada'];

    // Las reglas escritas una sola vez, para todas las consultas (el "?" es
    // la fecha de hoy). Una reserva 'lista' aparta un ejemplar hasta su fecha
    // de vencimiento, inclusive; después vence y el ejemplar queda libre.
    // Activa = todavía cuenta para el socio: espera en la cola o tiene el ejemplar apartado.
    const LISTA_VIGENTE = "(r.estado = 'lista' AND r.fecha_vencimiento >= ?)";
    const LISTA_VENCIDA = "(r.estado = 'lista' AND r.fecha_vencimiento < ?)";
    const ACTIVA = "(r.estado = 'pendiente' OR " . self::LISTA_VIGENTE . ")";

    // El FROM de las consultas: cada reserva con su socio y su item.
    const DESDE = "
        FROM reservas r
        INNER JOIN socios s ON r.socio_id = s.id
        INNER JOIN items i ON r.item_id = i.id
    ";

    // Lo que se lee de cada reserva. "puesto" es su lugar en la cola del item:
    // cuántas pendientes de ese item llegaron antes que ella, más ella misma.
    const SELECT = "
        SELECT r.*, s.nombre AS socio_nombre, i.titulo AS item_titulo,
               (SELECT COUNT(*) FROM reservas r2
                WHERE r2.item_id = r.item_id AND r2.estado = 'pendiente' AND r2.id <= r.id) AS puesto
    " . self::DESDE;

    private $id;
    private $socioId;
    private $itemId;
    private $fechaReserva;
    private $fechaVencimiento;
    private $estado;
    private $socioNombre;
    private $itemTitulo;
    private $puesto;

    public function __construct($id, $socioId, $itemId, $fechaReserva, $fechaVencimiento, $estado, $socioNombre, $itemTitulo, $puesto) {
        $this->id = $id;
        $this->socioId = $socioId;
        $this->itemId = $itemId;
        $this->fechaReserva = $fechaReserva;
        $this->fechaVencimiento = $fechaVencimiento;
        $this->estado = $estado;
        $this->socioNombre = $socioNombre;
        $this->itemTitulo = $itemTitulo;
        $this->puesto = $puesto;
    }

    // --- getters ---
    public function getId() { return $this->id; }
    public function getSocioId() { return $this->socioId; }
    public function getItemId() { return $this->itemId; }
    public function getFechaReserva() { return $this->fechaReserva; }
    public function getFechaVencimiento() { return $this->fechaVencimiento; }
    public function getSocioNombre() { return $this->socioNombre; }
    public function getItemTitulo() { return $this->itemTitulo; }
    public function getPuesto() { return $this->puesto; }

    // Las mismas reglas de arriba, para una reserva ya leída. Hoy sale de PHP (date), igual que en las consultas.
    public function estaPendiente() { return $this->estado === 'pendiente'; }
    public function estaLista() { return $this->estado === 'lista' && $this->fechaVencimiento >= date('Y-m-d'); }
    public function estaVencida() { return $this->estado === 'lista' && $this->fechaVencimiento < date('Y-m-d'); }
    public function estaActiva() { return $this->estaPendiente() || $this->estaLista(); }

    /** 'Lista', 'En espera', 'Vencida', 'Retirada' o 'Cancelada', para mostrar. */
    public function getEstadoTexto() {
        if ($this->estaLista()) {
            return 'Lista';
        }
        if ($this->estaVencida()) {
            return 'Vencida';
        }
        return ['pendiente' => 'En espera', 'retirada' => 'Retirada', 'cancelada' => 'Cancelada'][$this->estado];
    }

    // --- acceso a datos ---

    /** Arma una Reserva con una fila de SELECT. */
    private static function desdeFila($f) {
        return new Reserva($f['id'], $f['socio_id'], $f['item_id'], $f['fecha_reserva'], $f['fecha_vencimiento'], $f['estado'], $f['socio_nombre'], $f['item_titulo'], $f['puesto']);
    }

    /**
     * El WHERE para los $filtros (las mismas claves que listar()), con los
     * valores de cada "?". Lo usan listar() y contarPorEstado().
     * Devuelve [el WHERE armado (o '' si no hay filtros), sus parámetros].
     */
    private static function armarFiltros($filtros) {
        $filtros = array_merge(['socio_id' => '', 'item_id' => '', 'estado' => '', 'q' => ''], $filtros);

        $condiciones = [];
        $parametros = [];
        if ($filtros['socio_id'] !== '') {
            $condiciones[] = 'r.socio_id = ?';
            $parametros[] = $filtros['socio_id'];
        }
        if ($filtros['item_id'] !== '') {
            $condiciones[] = 'r.item_id = ?';
            $parametros[] = $filtros['item_id'];
        }
        if ($filtros['estado'] === 'lista') {
            $condiciones[] = self::LISTA_VIGENTE;
            $parametros[] = date('Y-m-d');
        } elseif ($filtros['estado'] === 'vencida') {
            $condiciones[] = self::LISTA_VENCIDA;
            $parametros[] = date('Y-m-d');
        } elseif ($filtros['estado'] !== '') {
            $condiciones[] = 'r.estado = ?';
            $parametros[] = $filtros['estado'];
        }
        if ($filtros['q'] !== '') {
            $condiciones[] = '(s.nombre LIKE ? OR i.titulo LIKE ?)';
            $parametros[] = '%' . $filtros['q'] . '%';
            $parametros[] = '%' . $filtros['q'] . '%';
        }

        $where = empty($condiciones) ? '' : ' WHERE ' . implode(' AND ', $condiciones);
        return [$where, $parametros];
    }

    /**
     * Cuántas reservas hay en cada estado con los demás $filtros (el estado
     * que venga se ignora), para los contadores de las pestañas.
     * Devuelve ['' => total, 'lista' => ..., 'pendiente' => ..., 'vencida' => ...,
     *           'retirada' => ..., 'cancelada' => ...].
     */
    public static function contarPorEstado($pdo, $filtros) {
        $filtros['estado'] = '';
        [$where, $parametros] = self::armarFiltros($filtros);
        $stmt = $pdo->prepare("
            SELECT COUNT(*),
                   COALESCE(SUM(" . self::LISTA_VIGENTE . "), 0),
                   COALESCE(SUM(r.estado = 'pendiente'), 0),
                   COALESCE(SUM(" . self::LISTA_VENCIDA . "), 0),
                   COALESCE(SUM(r.estado = 'retirada'), 0),
                   COALESCE(SUM(r.estado = 'cancelada'), 0)
        " . self::DESDE . $where);
        // Los dos "?" de la fecha están en el SELECT, antes que los del WHERE.
        $stmt->execute(array_merge([date('Y-m-d'), date('Y-m-d')], $parametros));
        $fila = array_map('intval', $stmt->fetch(PDO::FETCH_NUM));
        return array_combine(['', 'lista', 'pendiente', 'vencida', 'retirada', 'cancelada'], $fila);
    }

    /**
     * Reservas con el nombre del socio y el título del item, filtradas.
     * $filtros es un array con estas claves (todas opcionales; vacío = sin filtrar):
     *   socio_id, item_id,
     *   estado     -> uno de FILTROS_ESTADO,
     *   q          -> texto que se busca en el nombre del socio o el título del item,
     *   pagina     -> número de página (si está fuera de rango se corrige),
     *   por_pagina -> cuántas por página; 0 o sin poner = todas, sin paginar.
     * Primero las pendientes y las listas (en el ENUM van antes que 'retirada'
     * y 'cancelada'); dentro de cada estado, las más nuevas arriba.
     * Devuelve ['filas' => reservas de esa página, 'total' => cantidad de resultados,
     *           'pagina' => página que se muestra, 'paginas' => cantidad de páginas].
     */
    public static function listar($pdo, $filtros = []) {
        $filtros = array_merge(['pagina' => 1, 'por_pagina' => 0], $filtros);
        [$where, $parametros] = self::armarFiltros($filtros);

        $stmt = $pdo->prepare('SELECT COUNT(*)' . self::DESDE . $where);
        $stmt->execute($parametros);
        $total = (int) $stmt->fetchColumn();

        $sql = self::SELECT . $where . ' ORDER BY r.estado, r.id DESC';

        $paginacion = calcularPaginacion($total, $filtros['pagina'], $filtros['por_pagina']);
        $sql .= $paginacion['limite'];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        $reservas = [];
        foreach ($stmt->fetchAll() as $f) {
            $reservas[] = self::desdeFila($f);
        }
        return ['filas' => $reservas, 'total' => $total, 'pagina' => $paginacion['pagina'], 'paginas' => $paginacion['paginas']];
    }

    public static function buscarPorId($pdo, $id) {
        $stmt = $pdo->prepare(self::SELECT . ' WHERE r.id = ?');
        $stmt->execute([$id]);
        $f = $stmt->fetch();
        return $f ? self::desdeFila($f) : null;
    }

    /** Las reservas activas del socio $socioId, por item: [item_id => Reserva]. */
    public static function activasDelSocio($pdo, $socioId) {
        $stmt = $pdo->prepare(self::SELECT . ' WHERE r.socio_id = ? AND ' . self::ACTIVA);
        $stmt->execute([$socioId, date('Y-m-d')]);
        $reservas = [];
        foreach ($stmt->fetchAll() as $f) {
            $reservas[$f['item_id']] = self::desdeFila($f);
        }
        return $reservas;
    }

    /**
     * Pasa la reserva a 'cancelada'. Solo si estaba pendiente o lista (también
     * una lista vencida); si ya estaba retirada o cancelada no cambia nada y
     * devuelve false.
     */
    public static function cancelar($pdo, $id) {
        $stmt = $pdo->prepare("UPDATE reservas SET estado = 'cancelada' WHERE id = ? AND estado IN ('pendiente', 'lista')");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /** Pasa la reserva a 'retirada': el socio se llevó el item (se registró el préstamo). */
    public static function marcarRetirada($pdo, $id) {
        $stmt = $pdo->prepare("UPDATE reservas SET estado = 'retirada' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** Anota una reserva nueva al final de la cola del item. Devuelve su id. */
    public static function crear($pdo, $socioId, $itemId, $fecha) {
        $stmt = $pdo->prepare('INSERT INTO reservas (socio_id, item_id, fecha_reserva) VALUES (?, ?, ?)');
        $stmt->execute([$socioId, $itemId, $fecha]);
        return $pdo->lastInsertId();
    }

    /**
     * Reparte los ejemplares disponibles del item $itemId entre su cola: las
     * pendientes más viejas pasan a 'lista' (una por ejemplar) y tienen
     * DIAS_PARA_RETIRAR días para retirarlo.
     * Devuelve las que pasaron a 'lista', para avisarles: filas con id,
     * socio_nombre, email, item_titulo y fecha_vencimiento.
     */
    public static function avanzarCola($pdo, $itemId) {
        $item = Item::buscarPorId($pdo, $itemId);
        if (!$item || $item->getDisponibles() < 1) {
            return [];
        }

        // LIMIT es un entero que calculamos nosotros, por eso va directo en el SQL.
        $stmt = $pdo->prepare("
            SELECT r.id, s.nombre AS socio_nombre, u.email, i.titulo AS item_titulo
        " . self::DESDE . "
            INNER JOIN usuarios u ON s.usuario_id = u.id
            WHERE r.item_id = ? AND r.estado = 'pendiente'
            ORDER BY r.id
            LIMIT " . (int) $item->getDisponibles());
        $stmt->execute([$itemId]);

        $vence = date('Y-m-d', strtotime('+' . self::DIAS_PARA_RETIRAR . ' days'));
        $marcar = $pdo->prepare("UPDATE reservas SET estado = 'lista', fecha_vencimiento = ? WHERE id = ?");
        $listas = [];
        foreach ($stmt->fetchAll() as $f) {
            $marcar->execute([$vence, $f['id']]);
            $f['fecha_vencimiento'] = $vence;
            $listas[] = $f;
        }
        return $listas;
    }
}
