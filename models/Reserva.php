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

    // Las reglas escritas una sola vez, para todas las consultas (el "?" es
    // la fecha de hoy). Una reserva 'lista' aparta un ejemplar hasta su fecha
    // de vencimiento, inclusive; después vence y el ejemplar queda libre.
    // Activa = todavía cuenta para el socio: espera en la cola o tiene el ejemplar apartado.
    const LISTA_VIGENTE = "(r.estado = 'lista' AND r.fecha_vencimiento >= ?)";
    const LISTA_VENCIDA = "(r.estado = 'lista' AND r.fecha_vencimiento < ?)";
    const ACTIVA = "(r.estado = 'pendiente' OR " . self::LISTA_VIGENTE . ")";

    // Lo que se lee de cada reserva. "puesto" es su lugar en la cola del item:
    // cuántas pendientes de ese item llegaron antes que ella, más ella misma.
    const SELECT = "
        SELECT r.*, s.nombre AS socio_nombre, i.titulo AS item_titulo,
               (SELECT COUNT(*) FROM reservas r2
                WHERE r2.item_id = r.item_id AND r2.estado = 'pendiente' AND r2.id <= r.id) AS puesto
        FROM reservas r
        INNER JOIN socios s ON r.socio_id = s.id
        INNER JOIN items i ON r.item_id = i.id
    ";

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

    // --- acceso a datos ---

    /** Arma una Reserva con una fila de SELECT. */
    private static function desdeFila($f) {
        return new Reserva($f['id'], $f['socio_id'], $f['item_id'], $f['fecha_reserva'], $f['fecha_vencimiento'], $f['estado'], $f['socio_nombre'], $f['item_titulo'], $f['puesto']);
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
            FROM reservas r
            INNER JOIN socios s ON r.socio_id = s.id
            INNER JOIN usuarios u ON s.usuario_id = u.id
            INNER JOIN items i ON r.item_id = i.id
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
