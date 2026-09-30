# Bibliodeko

Sistema de biblioteca: libros, socios y prestamos, hecho en PHP con MySQL, siguiendo el patron **MVC**.

### Logins de prueba

```
admin@biblioteca.com / admin123
socio@biblioteca.com / socio123
```

---

## ¿Que es [MVC](https://sebascuadro.piperpiedsoft.com/guia_mvc.html)?

Separamos el codigo en 3 partes, cada una con un trabajo distinto:

- **Modelo** (`models/`): el unico que habla con la base de datos. Tiene funciones como `Libro::listar()`, `Libro::crear()`. Nadie mas escribe SQL.
- **Vista** (`views/`): el HTML que se ve en pantalla. Recibe datos ya listos y los muestra, sin logica.
- **Controlador** (`controllers/`): el que conecta todo. Recibe el pedido, valida, le pide los datos al modelo, y muestra la vista.

Una peticion viaja asi:

```
index.php (router)  -->  controller  -->  model (consulta la base)
                              |
                              v
                            vista (muestra el resultado)
```

## ¿Como funciona el router?

Todo pasa por `index.php`. La URL siempre trae un parametro `accion`, y un `switch` decide que funcion llamar:

```
index.php?accion=listarLibros   -->  llama a listarLibros()
index.php?accion=crearLibro     -->  llama a crearLibro()
```

Cada entidad (Libro, Socio, Usuario, Prestamo) repite el mismo patron de nombres:

- `listarX` — muestra la lista
- `formCrearX` — muestra el formulario para crear uno nuevo
- `crearX` — recibe el formulario y lo guarda
- `formEditarX` — muestra el formulario con los datos ya cargados
- `editarX` — recibe el formulario y actualiza
- `eliminarX` — borra

## Estructura de carpetas

```
index.php            -> el router, el unico punto de entrada
conexion.php         -> conexion a la base de datos (variable $pdo)
helpers.php          -> funciones que se repiten (permisos, mensajes)
database.sql         -> la base de datos completa, para importar

controllers/         -> una funcion por cada accion posible
models/               -> Libro.php, Socio.php, Usuario.php, Prestamo.php
views/                -> el HTML de cada pantalla, ordenado por entidad
uploads/              -> las portadas de los libros
```

## Las 4 entidades

- **Libro**: titulo, autor, categoria, portada.
- **Socio**: una persona que pide libros prestados. Esta enganchado a un Usuario (para poder loguearse).
- **Usuario**: una cuenta (email + contraseña) con un rol: `admin` (gestiona todo) o `socio` (solo ve el catalogo y sus prestamos).
- **[Prestamo](https://sebascuadro.piperpiedsoft.com/guia_prestamos_mvc.html)**: une un Socio con un Libro y guarda la fecha. `Prestamo::listar()` hace un [JOIN](https://sebascuadro.piperpiedsoft.com/guia_inner_join.html) contra `socios` y `libros` para traer el nombre y el titulo ya resueltos, sin tener que buscarlos aparte.

## Permisos

- Sin loguearse: solo se ve el catalogo de libros.
- Socio logueado: ademas puede ver sus propios prestamos.
- Admin: puede crear, editar y borrar libros, socios, usuarios y prestamos.

Esto se controla con dos funciones de `helpers.php`: `requerirLogin()` y `requerirAdmin()`, que se llaman al principio de cada funcion que lo necesita.

## Instalacion

1. Poner la carpeta en `htdocs` de XAMPP.
2. Importar `database.sql` en phpMyAdmin.
3. Revisar usuario/contraseña de MySQL en `conexion.php`.
4. Abrir `http://localhost/bibliodeko/`.
