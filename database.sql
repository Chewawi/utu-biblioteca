-- =====================================================================
--  Bibliodeko — esquema y datos de ejemplo
--
--  Cómo importar: phpMyAdmin > pestaña "Importar" > elegir este archivo.
--  Si ya existía una base "biblioteca" de antes: DROP DATABASE biblioteca;
-- =====================================================================
CREATE DATABASE IF NOT EXISTS biblioteca CHARACTER SET utf8mb4;
USE biblioteca;

-- Cuentas de acceso. rol: 'admin' gestiona todo, 'socio' solo mira el
-- catálogo y sus préstamos. "pass" guarda un hash, nunca texto plano.
CREATE TABLE IF NOT EXISTS usuarios (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    rol     ENUM('admin', 'socio') NOT NULL DEFAULT 'socio',
    email   VARCHAR(100) NOT NULL UNIQUE,
    pass    VARCHAR(255) NOT NULL
);

-- Datos personales del socio, enganchado a una cuenta de usuarios. Para
-- borrar la cuenta hay que borrar antes al socio (ON DELETE RESTRICT).
CREATE TABLE IF NOT EXISTS socios (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NOT NULL UNIQUE,
    nombre      VARCHAR(100) NOT NULL,
    direccion   VARCHAR(255) NOT NULL,
    telefono    VARCHAR(20)  NOT NULL,
    foto        VARCHAR(100) NOT NULL DEFAULT 'avatar.png',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
);

-- Todo lo que se puede pedir prestado. "stock" es cuántos ejemplares
-- tiene la biblioteca de ese item. "isbn" es opcional y solo para libros:
-- se guarda sin guiones (10 o 13 caracteres). UNIQUE no deja repetir un
-- ISBN, pero sí tener muchos items sin ISBN (NULL).
CREATE TABLE IF NOT EXISTS items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    tipo        ENUM('libro', 'juego', 'proyecto') NOT NULL DEFAULT 'libro',
    titulo      VARCHAR(150) NOT NULL,
    autor       VARCHAR(100) NOT NULL,
    categoria   VARCHAR(50)  NULL,
    isbn        VARCHAR(13)  NULL UNIQUE,
    descripcion TEXT         NULL,
    stock       INT UNSIGNED NOT NULL DEFAULT 1,
    imagen      VARCHAR(100) NOT NULL DEFAULT 'default.png',
    CHECK (isbn IS NULL OR tipo = 'libro')
);

-- Un préstamo conecta un socio con un item. "estado" dice si sigue
-- prestado o ya se devolvió. fecha_limite es hasta cuándo lo puede tener
-- (un préstamo activo con la fecha límite pasada está vencido; eso no se
-- guarda, se calcula). fecha_devolucion es el día en que lo devolvió:
-- queda vacía mientras está 'activo'. La fila no se borra:
-- queda como historial. Por eso, ON DELETE RESTRICT: no se puede borrar
-- un socio o un item que tenga préstamos registrados.
CREATE TABLE IF NOT EXISTS prestamos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    socio_id        INT  NOT NULL,
    item_id         INT  NOT NULL,
    fecha_prestamo  DATE NOT NULL,
    fecha_limite    DATE NOT NULL,
    fecha_devolucion DATE NULL,
    estado          ENUM('activo', 'devuelto') NOT NULL DEFAULT 'activo',
    FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE RESTRICT,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE RESTRICT,
    -- La base también controla las fechas y que activo/devuelto concuerde con fecha_devolucion.
    CHECK (fecha_limite > fecha_prestamo),
    CHECK (fecha_devolucion >= fecha_prestamo),
    CHECK ((estado = 'activo' AND fecha_devolucion IS NULL) OR (estado = 'devuelto' AND fecha_devolucion IS NOT NULL))
);

-- Una reserva es un socio pidiendo un item por adelantado. "estado":
--   'pendiente' -> espera un ejemplar, en una cola por orden de llegada;
--   'lista'     -> tiene un ejemplar apartado hasta fecha_vencimiento;
--   'retirada'  -> se lo llevó (pasó a ser un préstamo);
--   'cancelada' -> la canceló el socio o el admin.
-- Una 'lista' con fecha_vencimiento pasada ya no aparta el ejemplar: eso
-- no se guarda, se calcula al leer (igual que un préstamo vencido). Las
-- filas quedan como historial, por eso ON DELETE RESTRICT.
CREATE TABLE IF NOT EXISTS reservas (
    id                INT  AUTO_INCREMENT PRIMARY KEY,
    socio_id          INT  NOT NULL,
    item_id           INT  NOT NULL,
    fecha_reserva     DATE NOT NULL,
    fecha_vencimiento DATE NULL,
    estado            ENUM('pendiente', 'lista', 'retirada', 'cancelada') NOT NULL DEFAULT 'pendiente',
    FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE RESTRICT,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE RESTRICT,
    -- Una reserva lista siempre dice hasta cuándo aparta el ejemplar.
    CHECK (estado <> 'lista' OR fecha_vencimiento IS NOT NULL)
);

-- =====================================================================
--  DATOS DE EJEMPLO
-- =====================================================================

-- Contraseñas de prueba: admin123 y socio123
INSERT INTO usuarios (rol, email, pass) VALUES
('admin', 'admin@biblioteca.com',  '$2y$10$dn/kb6Lk2jVpYS1rgSVjSe.RYf7TBZdNb9/Ffz5uq1C7sci7Qk1ce'),
('socio', 'socio@biblioteca.com',  '$2y$10$A3IkphAkjzxSq/ubkgnUtudItqnp3gYoRHZC2TvyONLddJlLJP7jC'),
('socio', 'mariana@biblioteca.com','$2y$10$A3IkphAkjzxSq/ubkgnUtudItqnp3gYoRHZC2TvyONLddJlLJP7jC'),
('socio', 'lucia@biblioteca.com',  '$2y$10$A3IkphAkjzxSq/ubkgnUtudItqnp3gYoRHZC2TvyONLddJlLJP7jC'),
('socio', 'martin@biblioteca.com', '$2y$10$A3IkphAkjzxSq/ubkgnUtudItqnp3gYoRHZC2TvyONLddJlLJP7jC');

INSERT INTO socios (usuario_id, nombre, direccion, telefono) VALUES
(2, 'Socio de Prueba',   'Av. Principal 123', '099123456'),
(3, 'Mariana Fernández', 'Bulevar Artigas 456', '098765432'),
(4, 'Lucía Pereira',     'Av. Italia 2890',     '091234567'),
(5, 'Martín Silva',      '18 de Julio 1520',    '094555123');

-- ISBN de ediciones reales (1984 con uno de 10 dígitos). El señor de los
-- anillos queda sin ISBN, para ver un libro sin él. Las portadas se pueden
-- traer con "Traer portada" desde el formulario de cada libro.
INSERT INTO items (tipo, titulo, autor, categoria, isbn, descripcion, stock, imagen) VALUES
('libro',    'Cien años de soledad',          'Gabriel García Márquez',   'Novela',     '9780307474728',
 'La historia de la familia Buendía a lo largo de siete generaciones en Macondo, un pueblo inventado donde lo mágico es parte de lo cotidiano.\n\nUna de las novelas más leídas en español.', 2, 'default.png'),
('libro',    '1984',                          'George Orwell',            'Distopía',   '0451524934',
 'Winston Smith trabaja reescribiendo la historia para un gobierno que vigila todo lo que hace la gente. Un clásico sobre el control y la libertad.', 1, 'default.png'),
('libro',    'El nombre del viento',          'Patrick Rothfuss',         'Fantasía',   '9788401337208',
 'Kvothe, ya adulto, cuenta cómo fue su vida: de chico en una compañía de artistas ambulantes a estudiante en la Universidad. Primer libro de una saga.', 1, 'default.png'),
('libro',    'Rayuela',                       'Julio Cortázar',           'Novela',     '9788437604572',
 'Una novela que se puede leer en más de un orden. Sigue a Horacio Oliveira entre París y Buenos Aires.', 1, 'default.png'),
('libro',    'Fahrenheit 451',                'Ray Bradbury',             'Distopía',   '9781451673319',
 'En un futuro donde los libros están prohibidos, los bomberos no apagan incendios: los provocan. Edición en inglés.', 2, 'default.png'),
('libro',    'El principito',                 'Antoine de Saint-Exupéry', 'Infantil',   '9780156013925',
 'Un aviador perdido en el desierto conoce a un pequeño príncipe que viene de otro planeta. Para leer a cualquier edad.', 3, 'default.png'),
('libro',    'Sapiens',                       'Yuval Noah Harari',        'Ensayo',     '9788499926223',
 'Un recorrido por la historia de la humanidad, desde los primeros humanos hasta hoy, contado para cualquier lector.', 1, 'default.png'),
('libro',    'El señor de los anillos',       'J. R. R. Tolkien',         'Fantasía',   NULL,
 'Frodo y sus compañeros emprenden un viaje para destruir un anillo que podría darle todo el poder al enemigo.', 1, 'default.png'),
('juego',    'Catan',                         'Klaus Teuber',             'Estrategia', NULL,
 'Juego de mesa para 3 o 4 jugadores: se colonizan territorios, se comercian recursos y se construyen caminos y ciudades.', 2, 'default.png'),
('juego',    'Dixit',                         'Jean-Louis Roubira',       'Familiar',   NULL,
 'Juego de cartas ilustradas donde hay que adivinar qué carta eligió el narrador a partir de una pista. De 3 a 6 jugadores.', 1, 'default.png'),
('proyecto', 'Estación meteorológica Arduino', 'Grupo 3.º B',              'Robótica',   NULL,
 'Mide temperatura, humedad y presión con sensores conectados a un Arduino. Incluye el código y las instrucciones para armarla.', 1, 'default.png'),
('proyecto', 'Huerta con riego automático',   'Grupo 2.º A',              'Electrónica', NULL,
 'Un sensor de humedad en la tierra activa una bomba de agua cuando hace falta regar. Trae los planos y la lista de materiales.', 1, 'default.png');

-- Las fechas son relativas al día en que se importa, así siempre hay préstamos
-- al día, vencidos (activos con la fecha límite pasada) y devueltos, algunos
-- de ellos tarde (fecha_devolucion posterior a fecha_limite).
INSERT INTO prestamos (socio_id, item_id, fecha_prestamo, fecha_limite, fecha_devolucion, estado) VALUES
-- activos, al día
(1, 1,  CURDATE() - INTERVAL 5 DAY,   CURDATE() + INTERVAL 9 DAY,   NULL,                          'activo'),
(2, 3,  CURDATE() - INTERVAL 1 DAY,   CURDATE() + INTERVAL 13 DAY,  NULL,                          'activo'),
(3, 9,  CURDATE() - INTERVAL 3 DAY,   CURDATE() + INTERVAL 11 DAY,  NULL,                          'activo'),
(4, 6,  CURDATE() - INTERVAL 2 DAY,   CURDATE() + INTERVAL 12 DAY,  NULL,                          'activo'),
-- activos, vencidos
(2, 2,  CURDATE() - INTERVAL 20 DAY,  CURDATE() - INTERVAL 6 DAY,   NULL,                          'activo'),
(4, 11, CURDATE() - INTERVAL 25 DAY,  CURDATE() - INTERVAL 11 DAY,  NULL,                          'activo'),
(3, 5,  CURDATE() - INTERVAL 16 DAY,  CURDATE() - INTERVAL 2 DAY,   NULL,                          'activo'),
-- devueltos a tiempo
(1, 9,  CURDATE() - INTERVAL 40 DAY,  CURDATE() - INTERVAL 26 DAY,  CURDATE() - INTERVAL 32 DAY,   'devuelto'),
(2, 7,  CURDATE() - INTERVAL 35 DAY,  CURDATE() - INTERVAL 21 DAY,  CURDATE() - INTERVAL 22 DAY,   'devuelto'),
(2, 6,  CURDATE() - INTERVAL 60 DAY,  CURDATE() - INTERVAL 46 DAY,  CURDATE() - INTERVAL 50 DAY,   'devuelto'),
(3, 1,  CURDATE() - INTERVAL 75 DAY,  CURDATE() - INTERVAL 61 DAY,  CURDATE() - INTERVAL 63 DAY,   'devuelto'),
(4, 8,  CURDATE() - INTERVAL 95 DAY,  CURDATE() - INTERVAL 81 DAY,  CURDATE() - INTERVAL 85 DAY,   'devuelto'),
(1, 10, CURDATE() - INTERVAL 110 DAY, CURDATE() - INTERVAL 96 DAY,  CURDATE() - INTERVAL 100 DAY,  'devuelto'),
(1, 4,  CURDATE() - INTERVAL 125 DAY, CURDATE() - INTERVAL 111 DAY, CURDATE() - INTERVAL 112 DAY,  'devuelto'),
(2, 3,  CURDATE() - INTERVAL 130 DAY, CURDATE() - INTERVAL 116 DAY, CURDATE() - INTERVAL 120 DAY,  'devuelto'),
(3, 2,  CURDATE() - INTERVAL 150 DAY, CURDATE() - INTERVAL 136 DAY, CURDATE() - INTERVAL 140 DAY,  'devuelto'),
-- devueltos tarde
(1, 2,  CURDATE() - INTERVAL 50 DAY,  CURDATE() - INTERVAL 36 DAY,  CURDATE() - INTERVAL 30 DAY,   'devuelto'),
(4, 9,  CURDATE() - INTERVAL 70 DAY,  CURDATE() - INTERVAL 56 DAY,  CURDATE() - INTERVAL 49 DAY,   'devuelto'),
(2, 12, CURDATE() - INTERVAL 100 DAY, CURDATE() - INTERVAL 86 DAY,  CURDATE() - INTERVAL 80 DAY,   'devuelto'),
(3, 6,  CURDATE() - INTERVAL 170 DAY, CURDATE() - INTERVAL 156 DAY, CURDATE() - INTERVAL 150 DAY,  'devuelto');

-- Reservas en todos los estados, también con fechas relativas. Van en orden
-- de llegada (el id es el orden de la cola): en la de 1984, primero el socio
-- de prueba y después Lucía.
INSERT INTO reservas (socio_id, item_id, fecha_reserva, fecha_vencimiento, estado) VALUES
-- canceladas: una que esperaba 1984 y otra que tenía Dixit apartado
(4, 2,  CURDATE() - INTERVAL 12 DAY, NULL,                         'cancelada'),
(2, 10, CURDATE() - INTERVAL 9 DAY,  CURDATE() - INTERVAL 7 DAY,   'cancelada'),
-- retiradas: son los préstamos activos de Cien años de soledad y de Catan
(1, 1,  CURDATE() - INTERVAL 6 DAY,  CURDATE() - INTERVAL 4 DAY,   'retirada'),
(3, 9,  CURDATE() - INTERVAL 5 DAY,  CURDATE() - INTERVAL 3 DAY,   'retirada'),
-- pendiente: el socio de prueba espera 1984, que está prestado y vencido
(1, 2,  CURDATE() - INTERVAL 4 DAY,  NULL,                         'pendiente'),
-- lista vencida: Martín no pasó a buscar El señor de los anillos y el ejemplar volvió a quedar libre
(4, 8,  CURDATE() - INTERVAL 3 DAY,  CURDATE() - INTERVAL 1 DAY,   'lista'),
-- pendientes: Martín espera El nombre del viento y Lucía es la segunda en la cola de 1984
(4, 3,  CURDATE() - INTERVAL 3 DAY,  NULL,                         'pendiente'),
(3, 2,  CURDATE() - INTERVAL 2 DAY,  NULL,                         'pendiente'),
-- lista: un ejemplar de Sapiens apartado para el socio de prueba
(1, 7,  CURDATE() - INTERVAL 1 DAY,  CURDATE() + INTERVAL 1 DAY,   'lista');
