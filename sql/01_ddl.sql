-- ============================================================
-- SIGSM - Script DDL (creación de la base y las tablas)
-- Ejecutar en phpMyAdmin > pestaña SQL (o con "Importar").
-- Esta variante crea una base llamada "sigsm_prueba" y NO toca otras bases.
-- Si "sigsm_prueba" ya existe, el DROP la borra y se crea de cero.
-- ============================================================

DROP DATABASE IF EXISTS sigsm_prueba;
CREATE DATABASE sigsm_prueba CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sigsm_prueba;

-- ------------------------------------------------------------
-- USUARIO: los funcionarios del hospital que inician sesión.
-- Los pacientes NO tienen usuario: entran al portal escaneando el QR.
-- rol: 'admin'     = administrador (puede todo, incluso eliminar documentos)
--      'medico'    = médico (usuario básico: carga y edita documentos y encuestas)
--      'enfermera' = enfermería (usuario básico, igual que el médico)
-- La contraseña se guarda con password_hash() (Bcrypt), nunca en texto plano.
-- ------------------------------------------------------------
CREATE TABLE usuario (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(80)  NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    contrasenia VARCHAR(255) NOT NULL,
    rol         ENUM('admin','medico','enfermera') NOT NULL DEFAULT 'enfermera',
    activo      TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- CATEGORIA: servicio o especialidad del hospital (Cardiología, Urología...).
-- En el portal cada categoría se muestra como una carpeta desplegable.
-- activo: baja lógica (0 = el servicio ya no se usa, pero se conserva el registro).
-- ------------------------------------------------------------
CREATE TABLE categoria (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL UNIQUE,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- DOCUMENTO: material informativo para pacientes (instructivos, folletos).
-- No pertenece a un paciente: es general y lo ve cualquiera que entre por el QR.
-- Cada documento tiene UNA categoría y fue cargado por UN usuario.
-- publicado: 1 = lo ve el paciente, 0 = oculto (solo lo ven los funcionarios).
-- activo:    baja lógica (0 = eliminado, el registro queda en la base).
-- ------------------------------------------------------------
CREATE TABLE documento (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    titulo        VARCHAR(120) NOT NULL,
    descripcion   TEXT NULL,
    archivo       VARCHAR(100) NULL,          -- nombre del archivo subido (carpeta uploads/)
    fecha_emision DATE NOT NULL,
    fecha_carga   DATETIME NOT NULL,          -- cuándo se cargó al sistema
    publicado     TINYINT(1) NOT NULL DEFAULT 1,
    activo        TINYINT(1) NOT NULL DEFAULT 1,
    id_categoria  INT NOT NULL,
    id_usuario    INT NOT NULL,               -- quién cargó el documento
    FOREIGN KEY (id_categoria) REFERENCES categoria(id),
    FOREIGN KEY (id_usuario)   REFERENCES usuario(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- ENCUESTA: una encuesta creada desde el panel (título, descripción y servicio).
-- estado: 'borrador' (se está armando), 'publicada' (la ve el paciente) o 'cerrada'.
-- id_categoria: servicio al que pertenece (NULL = encuesta general del hospital).
-- version / id_origen: si una encuesta con respuestas necesita cambios se crea una
--         versión nueva; id_origen apunta a la encuesta de la que se copió.
-- activo: baja lógica (0 = eliminada; sus respuestas se conservan).
-- ------------------------------------------------------------
CREATE TABLE categoria_encuesta (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL UNIQUE,       -- tipo de encuesta: Satisfacción, Atención, Instalaciones...
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE encuesta (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    titulo         VARCHAR(120) NOT NULL,
    descripcion    VARCHAR(300) NULL,
    estado         ENUM('borrador','publicada','cerrada') NOT NULL DEFAULT 'borrador',
    version        INT NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL,
    activo         TINYINT(1) NOT NULL DEFAULT 1,
    id_categoria   INT NULL,                  -- servicio (NULL = general)
    id_categoria_encuesta INT NULL,           -- categoría de la encuesta (NULL = sin categoría)
    id_origen      INT NULL,
    id_usuario     INT NOT NULL,              -- quién la creó
    FOREIGN KEY (id_categoria) REFERENCES categoria(id),
    FOREIGN KEY (id_categoria_encuesta) REFERENCES categoria_encuesta(id),
    FOREIGN KEY (id_origen)    REFERENCES encuesta(id),
    FOREIGN KEY (id_usuario)   REFERENCES usuario(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- PREGUNTA: las preguntas de UNA encuesta, en un orden.
-- tipo: 'unica'      = elegir una opción
--       'multiple'   = elegir varias opciones
--       'valoracion' = nota de 1 a 5
--       'texto'      = respuesta escrita breve
-- Las preguntas solo se pueden cambiar mientras la encuesta es borrador (todavía sin
-- respuestas). Si una encuesta ya tiene respuestas se crea una versión nueva.
-- ------------------------------------------------------------
CREATE TABLE pregunta (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    id_encuesta INT NOT NULL,
    texto       VARCHAR(200) NOT NULL,
    tipo        ENUM('unica','multiple','valoracion','texto') NOT NULL DEFAULT 'valoracion',
    obligatoria TINYINT(1) NOT NULL DEFAULT 1,
    orden       INT NOT NULL DEFAULT 1,
    FOREIGN KEY (id_encuesta) REFERENCES encuesta(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- OPCION: las opciones de una pregunta de tipo 'unica' o 'multiple'.
-- ------------------------------------------------------------
CREATE TABLE opcion (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    id_pregunta INT NOT NULL,
    texto       VARCHAR(100) NOT NULL,
    orden       INT NOT NULL DEFAULT 1,
    FOREIGN KEY (id_pregunta) REFERENCES pregunta(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- ENVIO: cada vez que un paciente envía una encuesta se guarda una fila acá.
-- Es ANÓNIMO: solo se guarda la fecha, nunca quién la respondió.
-- ------------------------------------------------------------
CREATE TABLE envio (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    id_encuesta INT NOT NULL,
    fecha       DATETIME NOT NULL,
    FOREIGN KEY (id_encuesta) REFERENCES encuesta(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- RESPUESTA: la respuesta a una pregunta dentro de un envío.
-- Según el tipo de la pregunta se completa UNO de estos campos:
--   id_opcion (unica o multiple; en multiple hay una fila por opción marcada),
--   valor_escala (valoracion, de 1 a 5) o valor_texto (texto).
-- ------------------------------------------------------------
CREATE TABLE respuesta (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    id_envio     INT NOT NULL,
    id_pregunta  INT NOT NULL,
    id_opcion    INT NULL,
    valor_escala TINYINT NULL CHECK (valor_escala BETWEEN 1 AND 5),
    valor_texto  VARCHAR(300) NULL,
    FOREIGN KEY (id_envio)    REFERENCES envio(id),
    FOREIGN KEY (id_pregunta) REFERENCES pregunta(id),
    FOREIGN KEY (id_opcion)   REFERENCES opcion(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- REGISTRO (tabla de acciones): registro de las acciones importantes (quién, qué y cuándo).
-- id_usuario puede ser NULL para acciones sin sesión (ej.: login fallido).
-- entidad / id_entidad: sobre qué se hizo (ej.: 'documento' y su id), para ver el
-- historial de modificaciones de un documento.
-- ------------------------------------------------------------
CREATE TABLE registro (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    fecha      DATETIME NOT NULL,
    accion     VARCHAR(40)  NOT NULL,
    detalle    VARCHAR(255) NOT NULL,
    entidad    VARCHAR(20)  NULL,
    id_entidad INT NULL,
    id_usuario INT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- INTENTO_LOGIN: inicios de sesión fallidos, para limitar los intentos repetidos
-- (5 fallos en 10 minutos bloquean ese email y esa IP por un rato).
-- ------------------------------------------------------------
CREATE TABLE intento_login (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    ip    VARCHAR(45)  NOT NULL,
    fecha DATETIME    NOT NULL
) ENGINE=InnoDB;
