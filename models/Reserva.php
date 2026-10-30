<?php
// ============================================================
//  Modelo Reserva
//  Un socio reserva un item por adelantado. Si hay un ejemplar
//  disponible, la reserva queda 'lista' y se lo apartamos unos
//  días; si no, queda 'pendiente', en una cola por orden de
//  llegada.
// ============================================================

class Reserva {
    // Las reglas escritas una sola vez, para todas las consultas (el "?" es
    // la fecha de hoy). Una reserva 'lista' aparta un ejemplar hasta su fecha
    // de vencimiento, inclusive; después vence y el ejemplar queda libre.
    const LISTA_VIGENTE = "(r.estado = 'lista' AND r.fecha_vencimiento >= ?)";
    const LISTA_VENCIDA = "(r.estado = 'lista' AND r.fecha_vencimiento < ?)";
}
