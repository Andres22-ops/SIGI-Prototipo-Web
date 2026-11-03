# SIGI – Prototipo Web

**Sistema Integral de Gestión de Inventario (SIGI)** para un supermercado.
Este repositorio corresponde al **prototipo de la aplicación web** (vista de escritorio) del sistema.

- **Curso:** Innovación Tecnológica – Universidad ECCI
- **Docente:** Juan Sebastian Sanchez Gomez
- **Integrantes:** Andres Felipe Arias Hernandez, Lina Fernanda Andrade, Laura Valentina Castiblanco Puentes, Ana Maria Leal Martinez, Yeimmy Alejandra Sandoval Arias.
---

## Objetivo

Permitir gestionar y supervisar el inventario desde una plataforma centralizada: ver el estado del stock, controlar lotes y fechas de vencimiento, recibir alertas y registrar ventas. La meta del proyecto es **reducir en un 30 % las mermas por vencimiento**.

## Sobre el prototipo

> La aplicación es **responsive**: el mismo código se adapta a PC y a celular. Este repositorio presenta la **vista web**, y el repositorio hermano presenta la **vista móvil**.

## Usuarios de prueba

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@sigi.com | admin123 |
| Cajero | cajero1@sigi.com | cajero123 |
| Bodeguero | bodega1@sigi.com | bodega123 |

## Pantallas del prototipo

| N.º | Pantalla | Rol | Ruta |
|---|---|---|---|
| 01 | Inicio de sesión | Todos | `index.php` |
| 02 | Dashboard | Administrador | `views/admin/dashboard.php` |
| 03 | Gestión de Productos | Administrador | `views/admin/productos.php` |
| 04 | Nuevo Producto | Administrador | `views/admin/productos.php` (ventana modal) |
| 05 | Editar Producto | Administrador | `views/admin/productos.php` (ventana modal) |
| 06 | Punto de Venta (POS) | Cajero y Administrador | `views/cajero/pos.php` |
| 07 | Venta Registrada | Cajero y Administrador | `views/cajero/pos.php` (ventana modal) |
| 08 | Reportes de Ventas (PEPS) | Administrador | `views/admin/reportes.php` |
| 09 | Centro de Alertas | Administrador | `views/admin/alertas.php` |
| 10 | Inventario (Lotes) | Bodeguero y Administrador | `views/bodeguero/inventario.php` |
| 11 | Registrar Entrada de Mercancía | Bodeguero y Administrador | `views/bodeguero/inventario.php` (ventana modal) |

## Funcionalidades

- Inicio de sesión con redirección según el rol
- Dashboard con indicadores de ventas, lotes por vencer, stock mínimo, gráfico de los últimos 7 días y últimas ventas
- Gestión de productos: búsqueda, creación, edición y eliminación, con estado de stock
- Punto de venta por código de barras con carrito, total, efectivo recibido y cambio
- Comprobante de venta con opción de imprimir
- Reportes de ventas por rango de fechas: totales, promedio, ranking y detalle
- Centro de alertas de productos próximos a vencer y de stock mínimo, con historial de notificaciones
- Inventario por producto y últimos movimientos de entrada y salida
- Registro de entrada de mercancía con fecha de vencimiento del lote

## Estructura del repositorio

```
SIGI2/
├── index.php          Inicio de sesión
├── logout.php         Cierre de sesión
├── assets/            Recursos estáticos (estilos, scripts, imágenes)
├── config/            Configuración y conexión a la base de datos
└── views/
    ├── admin/         Dashboard, productos, reportes, alertas
    ├── cajero/        Punto de venta
    └── bodeguero/     Inventario y entradas
SIGI_database.sql      Base de datos (importar en phpMyAdmin)
```

## Tecnologías

PHP · MySQL · HTML/CSS/JavaScript (diseño tipo responsive)

## Cómo ejecutarlo en local

1. Instalar [XAMPP](https://www.apachefriends.org/) e iniciar **Apache** y **MySQL**.
2. Copiar la carpeta `SIGI2` en `C:\xampp\htdocs\`.
3. Abrir `http://localhost/phpmyadmin`, crear una base de datos llamada **`sigi_db`** (cotejamiento `utf8mb4_general_ci`) y **importar** dentro de ella el archivo `SIGI_database.sql`.
4. La conexión está en `config/db.php` y usa los valores por defecto de XAMPP: host `localhost`, usuario `root`, contraseña vacía y base de datos `sigi_db`. Si tu MySQL tiene otra configuración, ajústalos en ese archivo.
5. Abrir `http://localhost/SIGI2/index.php` e ingresar con alguno de los usuarios de prueba.

## Mejoras identificadas en las pruebas de usabilidad (siguiente iteración)

1. Priorizar las alertas críticas en la parte superior del dashboard y permitir personalizar los widgets visibles por local.
2. Mostrar un desglose junto a cada ítem del pedido sugerido (ventas de las últimas cuatro semanas y rotación).
3. Incorporar una vista comparativa lado a lado por local, con filtros de fecha y categoría.
4. Agregar un selector explícito de formato (CSV o PDF) con vista previa antes de exportar.
5. Habilitar la edición masiva de stock mínimo por categoría, con un valor inicial sugerido.

## Comentarios por pantalla

Los comentarios se dejan en la pestaña **Issues** de este repositorio. Indicar en el título el número de la pantalla, por ejemplo: `[06] Punto de Venta – comentario`.

## Videos

- Testeo web: https://youtu.be/1yxT0IWBfxs
- Video del proyecto: https://youtu.be/gO5ykFAgB5g
