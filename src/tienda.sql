DROP TABLE IF EXISTS articulos CASCADE;

CREATE TABLE articulos (
    id          bigserial     PRIMARY KEY,
    codigo      varchar(13)   NOT NULL UNIQUE,
    descripcion varchar(255)  NOT NULL,
    precio      numeric(7, 2) NOT NULL,
    stock       int           NOT NULL
);

DROP TABLE IF EXISTS usuarios CASCADE;

CREATE TABLE usuarios (
    id       bigserial    PRIMARY KEY,
    usuario  varchar(255) NOT NULL UNIQUE,
    password varchar(255) NOT NULL,
    validado bool         NOT NULL
);

DROP TABLE IF EXISTS facturas CASCADE;

CREATE TABLE facturas (
    id         bigserial  PRIMARY KEY,
    created_at timestamp  NOT NULL DEFAULT localtimestamp(0),
    usuario_id bigint NOT NULL REFERENCES usuarios (id),
    cupon_id bigint REFERENCES cupones (id),
    total numeric(7, 2)
);

DROP TABLE IF EXISTS articulos_facturas CASCADE;

CREATE TABLE articulos_facturas (
    articulo_id bigint NOT NULL REFERENCES articulos (id),
    factura_id  bigint NOT NULL REFERENCES facturas (id),
    cantidad    int    NOT NULL,
    PRIMARY KEY (articulo_id, factura_id)
);

DROP TABLE IF EXISTS cupones CASCADE;

CREATE TABLE cupones (
    id bigserial PRIMARY KEY,
    descuento double precision NOT NULL CHECK (descuento >= 0 AND descuento <= 1),
    caducidad date NOT NULL,
    cupon varchar (255) UNIQUE NOT NULL 
);

DROP TABLE IF EXISTS articulos_cupones CASCADE;

CREATE TABLE articulos_cupones (
    articulo_id bigint NOT NULL REFERENCES articulos (id),
    cupon_id bigint NOT NULL REFERENCES cupones (id),
    codigo varchar(255) UNIQUE NOT NULL,
    PRIMARY KEY (articulo_id, cupon_id)
);

-- Carga inicial de datos de prueba:

INSERT INTO articulos (codigo, descripcion, precio, stock)
    VALUES ('18273892389', 'Yogur piña', 200.50, 4),
           ('83745828273', 'Tigretón', 50.10, 2),
           ('51736128495', 'Disco duro SSD 500 GB', 150.30, 0),
           ('83746828273', 'Platano', 20.10, 3),
           ('51786128435', 'Disco duro M2 1T', 250.30, 5),
           ('83745228673', 'Filete de pollo', 15.10, 8),
           ('51786198495', 'Disco duro HDD 500 GB', 90.30, 1);

INSERT INTO usuarios (usuario, password, validado)
    VALUES ('admin', crypt('admin', gen_salt('bf', 10)), true),
           ('pepe', crypt('pepe', gen_salt('bf', 10)), false);

INSERT INTO cupones (descuento, caducidad, cupon)
VALUES ( 0.20, '2023-12-31', 'CUPON-20%-DESCUENTO'),
       ( 0.50, '2023-12-31', 'CUPON-50%-DESCUENTO'),
       ( 0.70, '2023-1-15', 'CUPON-70%-DESCUENTO');

       INSERT INTO articulos_cupones (articulo_id, cupon_id, codigo)
VALUES (1, 1, 'CUPON20-1'),
       (2, 1, 'CUPON20-2'),
       (2, 2, 'CUPON50-2'),
       (4, 3, 'CUPON70-4');