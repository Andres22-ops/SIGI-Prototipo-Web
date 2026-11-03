-- ============================================================
--  SIGI - Sistema Integral de Gestión de Inventario
--  Universidad ECCI - Ingeniería de Requerimientos
--  Integrantes: Deiwhis Angola, Laura Castiblanco, Andres Arias
--  Fecha: Mayo 2025
-- ============================================================

CREATE DATABASE IF NOT EXISTS sigi_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sigi_db;

-- ============================================================
-- TABLA: CATEGORIA
-- ============================================================
CREATE TABLE CATEGORIA (
    id_categoria   INT          NOT NULL AUTO_INCREMENT,
    nombre_categoria VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_categoria)
);

-- ============================================================
-- TABLA: PROVEEDOR
-- ============================================================
CREATE TABLE PROVEEDOR (
    id_proveedor INT          NOT NULL AUTO_INCREMENT,
    nombre       VARCHAR(150) NOT NULL,
    telefono     VARCHAR(20),
    correo       VARCHAR(100),
    direccion    VARCHAR(200),
    PRIMARY KEY (id_proveedor)
);

-- ============================================================
-- TABLA: PRODUCTO
-- ============================================================
CREATE TABLE PRODUCTO (
    id_producto  INT            NOT NULL AUTO_INCREMENT,
    nombre       VARCHAR(150)   NOT NULL,
    descripcion  TEXT,
    cod_barras   VARCHAR(13)    UNIQUE,          -- EAN-13
    precio       DECIMAL(12, 2) NOT NULL,
    stock_actual INT            NOT NULL DEFAULT 0,
    stock_minimo INT            NOT NULL DEFAULT 0,
    id_categoria INT            NOT NULL,
    PRIMARY KEY (id_producto),
    CONSTRAINT fk_producto_categoria
        FOREIGN KEY (id_categoria) REFERENCES CATEGORIA(id_categoria)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ============================================================
-- TABLA: PEDIDO  (orden de compra a proveedor)
-- ============================================================
CREATE TABLE PEDIDO (
    id_pedido    INT         NOT NULL AUTO_INCREMENT,
    fecha        DATE        NOT NULL,
    estado       VARCHAR(30) NOT NULL DEFAULT 'PENDIENTE',
                 -- PENDIENTE | ENVIADO | RECIBIDO | CANCELADO
    id_proveedor INT         NOT NULL,
    PRIMARY KEY (id_pedido),
    CONSTRAINT fk_pedido_proveedor
        FOREIGN KEY (id_proveedor) REFERENCES PROVEEDOR(id_proveedor)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ============================================================
-- TABLA: DETALLE_PEDIDO
-- ============================================================
CREATE TABLE DETALLE_PEDIDO (
    id_detalle_pedido INT            NOT NULL AUTO_INCREMENT,
    cantidad          INT            NOT NULL,
    id_pedido         INT            NOT NULL,
    id_producto       INT            NOT NULL,
    PRIMARY KEY (id_detalle_pedido),
    CONSTRAINT fk_detpedido_pedido
        FOREIGN KEY (id_pedido)   REFERENCES PEDIDO(id_pedido)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detpedido_producto
        FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ============================================================
-- TABLA: INVENTARIO  (movimientos de stock - algoritmo PEPS)
-- ============================================================
CREATE TABLE INVENTARIO (
    id_movimiento    INT         NOT NULL AUTO_INCREMENT,
    tipo_movimiento  VARCHAR(10) NOT NULL,   -- ENTRADA | SALIDA
    cantidad         INT         NOT NULL,
    fecha            DATE        NOT NULL,
    fecha_vencimiento DATE,                  -- para lotes perecederos
    id_producto      INT         NOT NULL,
    PRIMARY KEY (id_movimiento),
    CONSTRAINT fk_inventario_producto
        FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ============================================================
-- TABLA: USUARIO
-- ============================================================
CREATE TABLE USUARIO (
    id_usuario INT          NOT NULL AUTO_INCREMENT,
    nombre     VARCHAR(150) NOT NULL,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,        -- almacenar hash
    rol        VARCHAR(20)  NOT NULL,        -- CAJERO | BODEGUERO | ADMIN
    PRIMARY KEY (id_usuario)
);

-- ============================================================
-- TABLA: VENTA
-- ============================================================
CREATE TABLE VENTA (
    id_venta   INT            NOT NULL AUTO_INCREMENT,
    fecha      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total      DECIMAL(12, 2) NOT NULL DEFAULT 0,
    id_usuario INT            NOT NULL,       -- cajero que realizó la venta
    PRIMARY KEY (id_venta),
    CONSTRAINT fk_venta_usuario
        FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ============================================================
-- TABLA: DETALLE_VENTA
-- ============================================================
CREATE TABLE DETALLE_VENTA (
    id_detalle     INT            NOT NULL AUTO_INCREMENT,
    cantidad       INT            NOT NULL,
    precio_unitario DECIMAL(12, 2) NOT NULL,
    subtotal       DECIMAL(12, 2) GENERATED ALWAYS AS (cantidad * precio_unitario) STORED,
    id_venta       INT            NOT NULL,
    id_producto    INT            NOT NULL,
    PRIMARY KEY (id_detalle),
    CONSTRAINT fk_detventa_venta
        FOREIGN KEY (id_venta)    REFERENCES VENTA(id_venta)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detventa_producto
        FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ============================================================
-- TABLA: NOTIFICACION
-- ============================================================
CREATE TABLE NOTIFICACION (
    id_notificacion INT         NOT NULL AUTO_INCREMENT,
    mensaje         TEXT        NOT NULL,
    tipo            VARCHAR(15) NOT NULL,     -- STOCK | VENCIMIENTO
    fecha_generada  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    leida           TINYINT(1)  NOT NULL DEFAULT 0,
    id_producto     INT,
    PRIMARY KEY (id_notificacion),
    CONSTRAINT fk_notif_producto
        FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto)
        ON UPDATE CASCADE ON DELETE SET NULL
);

-- ============================================================
-- DATOS INICIALES (INSERT de prueba)
-- ============================================================

-- Categorías
INSERT INTO CATEGORIA (nombre_categoria) VALUES
    ('Lácteos'),
    ('Carnes y Embutidos'),
    ('Bebidas'),
    ('Panadería'),
    ('Aseo del Hogar');

-- Proveedores
INSERT INTO PROVEEDOR (nombre, telefono, correo, direccion) VALUES
    ('Alimentos del Valle S.A.S', '3101234567', 'ventas@alimentosvalle.com', 'Cra 15 #45-30, Bogotá'),
    ('Lácteos El Campo Ltda',     '3209876543', 'pedidos@lacteoselcampo.com', 'Av 68 #12-50, Bogotá'),
    ('Distribuidora Aseo Total',  '6013456789', 'info@aseototal.com',         'Cll 80 #22-15, Bogotá');

-- Usuarios
INSERT INTO USUARIO (nombre, email, password, rol) VALUES
    ('Admin Principal',   'admin@sigi.com',    SHA2('admin123', 256),    'ADMIN'),
    ('Laura Cajera',      'cajero1@sigi.com',  SHA2('cajero123', 256),   'CAJERO'),
    ('Pedro Bodeguero',   'bodega1@sigi.com',  SHA2('bodega123', 256),   'BODEGUERO');

-- Productos
INSERT INTO PRODUCTO (nombre, descripcion, cod_barras, precio, stock_actual, stock_minimo, id_categoria) VALUES
    ('Leche Entera 1L',       'Leche UHT entera bolsa 1 litro',    '7702003001001', 3200.00, 150, 20, 1),
    ('Yogur Fresa 200g',      'Yogur saborizado fresa vaso 200g',  '7702003002001',  900.00,  80, 15, 1),
    ('Pechuga de Pollo kg',   'Pechuga de pollo fresca x kg',      '7707000003001', 14000.00, 40, 10, 2),
    ('Agua Cristal 600ml',    'Agua purificada botella 600ml',     '7702001004001',  1500.00, 200, 30, 3),
    ('Gaseosa Cola 1.5L',     'Bebida gaseosa cola botella 1.5L',  '7702001005001',  3500.00, 120, 20, 3),
    ('Pan Tajado 500g',       'Pan de molde tajado 500g',          '7702009006001',  4200.00,  60, 10, 4),
    ('Jabón en Polvo 500g',   'Detergente multiusos 500g',         '7702007007001',  5800.00,  90, 15, 5);

-- Movimientos de inventario (entradas iniciales)
INSERT INTO INVENTARIO (tipo_movimiento, cantidad, fecha, fecha_vencimiento, id_producto) VALUES
    ('ENTRADA', 150, '2025-05-01', '2025-07-15', 1),
    ('ENTRADA',  80, '2025-05-01', '2025-06-30', 2),
    ('ENTRADA',  40, '2025-05-05', '2025-05-25', 3),
    ('ENTRADA', 200, '2025-05-01', '2026-01-01', 4),
    ('ENTRADA', 120, '2025-05-01', '2025-12-31', 5),
    ('ENTRADA',  60, '2025-05-03', '2025-05-28', 6),
    ('ENTRADA',  90, '2025-05-01', '2026-06-01', 7);

-- Pedido de prueba
INSERT INTO PEDIDO (fecha, estado, id_proveedor) VALUES
    ('2025-05-10', 'RECIBIDO', 2),
    ('2025-05-18', 'PENDIENTE', 1);

INSERT INTO DETALLE_PEDIDO (cantidad, id_pedido, id_producto) VALUES
    (100, 1, 1),
    ( 50, 1, 2),
    (200, 2, 4),
    ( 60, 2, 6);

-- Venta de prueba
INSERT INTO VENTA (fecha, total, id_usuario) VALUES
    ('2025-05-20 09:35:00', 11600.00, 2);

INSERT INTO DETALLE_VENTA (cantidad, precio_unitario, id_venta, id_producto) VALUES
    (2, 3200.00, 1, 1),
    (1,  900.00, 1, 2),
    (1, 4300.00, 1, 3);

-- ============================================================
-- VISTAS ÚTILES
-- ============================================================

-- Vista: productos con stock mínimo alcanzado
CREATE OR REPLACE VIEW v_stock_minimo AS
SELECT p.id_producto, p.nombre, p.cod_barras,
       p.stock_actual, p.stock_minimo,
       c.nombre_categoria
FROM   PRODUCTO p
JOIN   CATEGORIA c ON p.id_categoria = c.id_categoria
WHERE  p.stock_actual <= p.stock_minimo;

-- Vista: productos próximos a vencer (dentro de 7 días)
CREATE OR REPLACE VIEW v_proximos_vencer AS
SELECT p.id_producto, p.nombre,
       i.fecha_vencimiento,
       DATEDIFF(i.fecha_vencimiento, CURDATE()) AS dias_restantes,
       i.cantidad
FROM   INVENTARIO i
JOIN   PRODUCTO p ON i.id_producto = p.id_producto
WHERE  i.tipo_movimiento = 'ENTRADA'
  AND  i.fecha_vencimiento IS NOT NULL
  AND  DATEDIFF(i.fecha_vencimiento, CURDATE()) BETWEEN 0 AND 7
ORDER BY dias_restantes ASC;

-- Vista: reporte ventas del día
CREATE OR REPLACE VIEW v_ventas_hoy AS
SELECT v.id_venta, v.fecha,
       p.nombre AS producto,
       dv.cantidad, dv.precio_unitario, dv.subtotal,
       u.nombre AS cajero
FROM   VENTA v
JOIN   DETALLE_VENTA dv ON v.id_venta   = dv.id_venta
JOIN   PRODUCTO p       ON dv.id_producto = p.id_producto
JOIN   USUARIO u        ON v.id_usuario  = u.id_usuario
WHERE  DATE(v.fecha) = CURDATE();

-- ============================================================
-- TRIGGERS
-- ============================================================

DELIMITER $$

-- Trigger: descontar stock al registrar una venta (algoritmo PEPS)
CREATE TRIGGER trg_actualizar_stock_venta
AFTER INSERT ON DETALLE_VENTA
FOR EACH ROW
BEGIN
    UPDATE PRODUCTO
    SET    stock_actual = stock_actual - NEW.cantidad
    WHERE  id_producto  = NEW.id_producto;

    -- Registrar salida en inventario
    INSERT INTO INVENTARIO (tipo_movimiento, cantidad, fecha, id_producto)
    VALUES ('SALIDA', NEW.cantidad, CURDATE(), NEW.id_producto);
END$$

-- Trigger: aumentar stock al recibir un pedido
CREATE TRIGGER trg_actualizar_stock_pedido
AFTER UPDATE ON PEDIDO
FOR EACH ROW
BEGIN
    IF NEW.estado = 'RECIBIDO' AND OLD.estado != 'RECIBIDO' THEN
        UPDATE PRODUCTO p
        JOIN   DETALLE_PEDIDO dp ON p.id_producto = dp.id_producto
        SET    p.stock_actual = p.stock_actual + dp.cantidad
        WHERE  dp.id_pedido = NEW.id_pedido;
    END IF;
END$$

-- Trigger: generar notificación automática si stock llega a mínimo
CREATE TRIGGER trg_notificacion_stock_minimo
AFTER UPDATE ON PRODUCTO
FOR EACH ROW
BEGIN
    IF NEW.stock_actual <= NEW.stock_minimo THEN
        INSERT INTO NOTIFICACION (mensaje, tipo, id_producto)
        VALUES (
            CONCAT('⚠ Stock mínimo alcanzado: ', NEW.nombre,
                   ' — Stock actual: ', NEW.stock_actual,
                   ' / Mínimo: ', NEW.stock_minimo),
            'STOCK',
            NEW.id_producto
        );
    END IF;
END$$

DELIMITER ;
