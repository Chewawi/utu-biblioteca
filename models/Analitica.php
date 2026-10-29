<?php
// ============================================================
//  Modelo Analitica
//  Consultas para la página de analíticas, una función por
//  indicador. No tiene tabla propia: lee items, socios y
//  préstamos. Devuelve arrays simples, listos para mostrar.
//
//  $desde y $hasta (AAAA-MM-DD, '' = sin límite) filtran los
//  préstamos por su fecha de préstamo, como en el listado.
// ============================================================

class Analitica {

    /**
     * Condiciones del WHERE para el período, más las $condiciones que se
     * pasen. Devuelve [el WHERE armado (o '' si no hay nada), sus parámetros].
     */
    private static function filtrarPeriodo($desde, $hasta, $condiciones = []) {
        $parametros = [];
        if ($desde !== '') {
            $condiciones[] = 'p.fecha_prestamo >= ?';
            $parametros[] = $desde;
        }
        if ($hasta !== '') {
            $condiciones[] = 'p.fecha_prestamo <= ?';
            $parametros[] = $hasta;
        }
        $where = empty($condiciones) ? '' : ' WHERE ' . implode(' AND ', $condiciones);
        return [$where, $parametros];
    }

    // --- Inventario (siempre el estado actual, sin período) ---

    /**
     * Títulos y ejemplares de cada tipo. Por ejemplo:
     * ['libro' => ['titulos' => 8, 'ejemplares' => 12], 'juego' => [...], 'proyecto' => [...]]
     */
    public static function itemsPorTipo($pdo) {
        // Los tipos sin items quedan en 0.
        $resultado = array_fill_keys(Item::TIPOS, ['titulos' => 0, 'ejemplares' => 0]);
        $filas = $pdo->query('SELECT tipo, COUNT(*) AS titulos, SUM(stock) AS ejemplares FROM items GROUP BY tipo')->fetchAll();
        foreach ($filas as $f) {
            $resultado[$f['tipo']] = ['titulos' => (int) $f['titulos'], 'ejemplares' => (int) $f['ejemplares']];
        }
        return $resultado;
    }

    public static function totalSocios($pdo) {
        return (int) $pdo->query('SELECT COUNT(*) FROM socios')->fetchColumn();
    }

    /**
     * Los $limite items con menos ejemplares libres ahora mismo.
     * Cada fila: titulo, tipo, stock, prestados, disponibles.
     */
    public static function itemsConMenosDisponibilidad($pdo, $limite = 5) {
        // CAST: stock es UNSIGNED y la resta daría error si quedara negativa.
        $sql = "
            SELECT titulo, tipo, stock, prestados, CAST(stock AS SIGNED) - prestados AS disponibles
            FROM (
                SELECT i.titulo, i.tipo, i.stock,
                       (SELECT COUNT(*) FROM prestamos p WHERE p.item_id = i.id AND p.estado = 'activo') AS prestados
                FROM items i
            ) AS conteo
            ORDER BY disponibles, prestados DESC, titulo
            LIMIT " . (int) $limite;
        return $pdo->query($sql)->fetchAll();
    }

    // --- Actividad (filtrada por el período) ---

    /**
     * Cuántos préstamos hay y en qué estado están hoy. Los vencidos están
     * también dentro de los activos. Devuelve total, activos, vencidos y devueltos.
     */
    public static function prestamosPorEstado($pdo, $desde, $hasta) {
        [$where, $parametros] = self::filtrarPeriodo($desde, $hasta);
        // "Hoy" lo pone PHP, igual que en el listado de préstamos.
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total,
                   COALESCE(SUM(p.estado = 'activo'), 0) AS activos,
                   COALESCE(SUM(p.estado = 'activo' AND p.fecha_limite < ?), 0) AS vencidos,
                   COALESCE(SUM(p.estado = 'devuelto'), 0) AS devueltos
            FROM prestamos p
        " . $where);
        $stmt->execute(array_merge([date('Y-m-d')], $parametros));
        return array_map('intval', $stmt->fetch());
    }

    /**
     * Préstamos de cada mes, del más reciente al más viejo. Va desde el primer
     * mes con préstamos hasta el mes de $hasta (o el actual); los meses sin
     * préstamos aparecen con 0. Por ejemplo: ['2026-10' => 4, '2026-09' => 0, ...]
     */
    public static function prestamosPorMes($pdo, $desde, $hasta) {
        [$where, $parametros] = self::filtrarPeriodo($desde, $hasta);
        $stmt = $pdo->prepare("
            SELECT DATE_FORMAT(p.fecha_prestamo, '%Y-%m') AS mes, COUNT(*) AS cantidad
            FROM prestamos p
        " . $where . ' GROUP BY mes ORDER BY mes');
        $stmt->execute($parametros);
        $conteo = [];
        foreach ($stmt->fetchAll() as $f) {
            $conteo[$f['mes']] = (int) $f['cantidad'];
        }
        if (empty($conteo)) {
            return [];
        }

        // Completamos los meses que faltan, mes por mes, hasta el último.
        $hoy = date('Y-m-d');
        $ultimo = substr(($hasta !== '' && $hasta < $hoy) ? $hasta : $hoy, 0, 7);
        $meses = [];
        $mes = new DateTime(array_key_first($conteo) . '-01');
        while ($mes->format('Y-m') <= $ultimo) {
            $meses[$mes->format('Y-m')] = $conteo[$mes->format('Y-m')] ?? 0;
            $mes->modify('+1 month');
        }
        return array_reverse($meses, true);
    }

    /** Los $limite items prestados más veces. Cada fila: titulo, tipo, cantidad. */
    public static function itemsMasPrestados($pdo, $desde, $hasta, $limite = 5) {
        [$where, $parametros] = self::filtrarPeriodo($desde, $hasta);
        $stmt = $pdo->prepare("
            SELECT i.titulo, i.tipo, COUNT(*) AS cantidad
            FROM prestamos p
            INNER JOIN items i ON p.item_id = i.id
        " . $where . ' GROUP BY i.id, i.titulo, i.tipo ORDER BY cantidad DESC, i.titulo LIMIT ' . (int) $limite);
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }

    /** Los $limite socios con más préstamos. Cada fila: nombre, cantidad. */
    public static function sociosConMasPrestamos($pdo, $desde, $hasta, $limite = 5) {
        [$where, $parametros] = self::filtrarPeriodo($desde, $hasta);
        $stmt = $pdo->prepare("
            SELECT s.nombre, COUNT(*) AS cantidad
            FROM prestamos p
            INNER JOIN socios s ON p.socio_id = s.id
        " . $where . ' GROUP BY s.id, s.nombre ORDER BY cantidad DESC, s.nombre LIMIT ' . (int) $limite);
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }

    /** Días promedio entre el préstamo y la devolución. null si no hay devoluciones. */
    public static function promedioDiasDevolucion($pdo, $desde, $hasta) {
        [$where, $parametros] = self::filtrarPeriodo($desde, $hasta, ["p.estado = 'devuelto'"]);
        $stmt = $pdo->prepare('SELECT AVG(DATEDIFF(p.fecha_devolucion, p.fecha_prestamo)) FROM prestamos p' . $where);
        $stmt->execute($parametros);
        $promedio = $stmt->fetchColumn();
        return $promedio === null ? null : (float) $promedio;
    }

    /** Devoluciones hechas después de la fecha límite: ['tardias' => 4, 'devoluciones' => 13]. */
    public static function devolucionesTardias($pdo, $desde, $hasta) {
        [$where, $parametros] = self::filtrarPeriodo($desde, $hasta, ["p.estado = 'devuelto'"]);
        $stmt = $pdo->prepare('
            SELECT COALESCE(SUM(p.fecha_devolucion > p.fecha_limite), 0) AS tardias, COUNT(*) AS devoluciones
            FROM prestamos p
        ' . $where);
        $stmt->execute($parametros);
        return array_map('intval', $stmt->fetch());
    }
}
