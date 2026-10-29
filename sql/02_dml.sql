-- ============================================================
-- SIGSM - Script DML (datos de prueba, todos ficticios)
-- Ejecutar DESPUÉS de 01_ddl.sql.
--
-- Credenciales de prueba (funcionarios):
--   Administrador: admin@hc.edu.uy      / 123456
--   Médico:        medico@hc.edu.uy     / func123
--   Enfermería:    enfermera@hc.edu.uy  / func123
-- ============================================================

USE sigsm_prueba;

INSERT INTO usuario (nombre, email, contrasenia, rol) VALUES
('Administrador SIGSM', 'admin@hc.edu.uy',     '$2y$10$sL.gQ5kz2cezW5ZStiFSSe9twG/JTyj/1tCnFUVuRvenF45hnjAEy', 'admin'),
('Dra. Laura Méndez',   'medico@hc.edu.uy',    '$2y$10$j63Io684z0qI41Qe8q8upOflHMmnI06b7KdsCEwUyv5CvuEy5m6jG', 'medico'),
('Enf. Carlos Núñez',   'enfermera@hc.edu.uy', '$2y$10$O2ABik0uyV4vWHqg.Hmd6OuLGM7PMV2incRtlEQLpdlX2ioXQybB6', 'enfermera');

-- Servicios del Hospital de Clínicas que tendrán espacio en el portal del paciente
INSERT INTO categoria (nombre) VALUES
('Unidad de Cuidados Paliativos'),
('Emergencia'),
('Neonatología'),
('Cardiología'),
('Centro cardiovascular'),
('Centro cardiovascular - Hemodinamia'),
('Centro cardiovascular - Crujía cardíaca'),
('Clínica Médica A'),
('Clínica Médica B'),
('Clínica Médica C'),
('Dermatología'),
('Endocrinología'),
('Gastroenterología'),
('Gastroenterología - Unidad de Ostomías'),
('Geriatría'),
('Hematología'),
('Infectología'),
('Medicina física, rehabilitación y medicina del deporte'),
('Nefrología'),
('Neurología'),
('Oncología'),
('Psiquiatría'),
('U.E. Autoinmunes sistémicas'),
('Unidad de Tabaquismo'),
('Anestesiología'),
('Cirugía plástica y quemados'),
('Cirugía vascular periférica'),
('Ginecotocológica B'),
('Neurocirugía'),
('Odontología'),
('Oftalmología'),
('Otorrinolaringología'),
('Quirúrgica A'),
('Quirúrgica B'),
('Quirúrgica F'),
('Urología'),
('Traumatología y ortopedia de adultos'),
('Radioterapia'),
('Medicina Nuclear'),
('Hemoterapia'),
('Imagenología');

-- Documentos de ejemplo (los del PDF "Ejes de trabajo"). Cada uno tiene su PDF ficticio de prueba
-- en la carpeta uploads/ (ejemplo_01.pdf ... ejemplo_09.pdf).
-- id_categoria e id_usuario se buscan por nombre/email para no depender de los números.
INSERT INTO documento (titulo, descripcion, archivo, fecha_emision, fecha_carga, publicado, id_categoria, id_usuario) VALUES
('Indicaciones de interrupción voluntaria del embarazo',
 'Información e indicaciones para la paciente.', 'ejemplo_01.pdf', '2026-09-01', '2026-09-01 09:00:00', 1,
 (SELECT id FROM categoria WHERE nombre = 'Ginecotocológica B'),
 (SELECT id FROM usuario WHERE email = 'medico@hc.edu.uy')),
('Prostatectomía radical',
 'Indicaciones e información para el paciente.', 'ejemplo_02.pdf', '2026-09-03', '2026-09-03 09:00:00', 1,
 (SELECT id FROM categoria WHERE nombre = 'Urología'),
 (SELECT id FROM usuario WHERE email = 'medico@hc.edu.uy')),
('Preparación para estudios imagenológicos',
 'Ayuno, medicación y cuidados antes del estudio.', 'ejemplo_03.pdf', '2026-09-05', '2026-09-05 09:00:00', 1,
 (SELECT id FROM categoria WHERE nombre = 'Imagenología'),
 (SELECT id FROM usuario WHERE email = 'enfermera@hc.edu.uy')),
('Estudios diagnósticos con pertecnetato',
 'Información sobre los estudios de Medicina Nuclear.', 'ejemplo_04.pdf', '2026-09-08', '2026-09-08 09:00:00', 1,
 (SELECT id FROM categoria WHERE nombre = 'Medicina Nuclear'),
 (SELECT id FROM usuario WHERE email = 'medico@hc.edu.uy')),
('Centellograma de perfusión miocárdica',
 'Información para el paciente sobre el estudio.', 'ejemplo_05.pdf', '2026-09-10', '2026-09-10 09:00:00', 1,
 (SELECT id FROM categoria WHERE nombre = 'Medicina Nuclear'),
 (SELECT id FROM usuario WHERE email = 'medico@hc.edu.uy')),
('Indicaciones para ecocardiograma con dobutamina',
 'Qué hacer antes y después del estudio.', 'ejemplo_06.pdf', '2026-09-12', '2026-09-12 09:00:00', 1,
 (SELECT id FROM categoria WHERE nombre = 'Cardiología'),
 (SELECT id FROM usuario WHERE email = 'medico@hc.edu.uy')),
('Indicaciones para pacientes en tratamiento con warfarina',
 'Alimentación, controles y cuidados.', 'ejemplo_07.pdf', '2026-09-15', '2026-09-15 09:00:00', 1,
 (SELECT id FROM categoria WHERE nombre = 'Cardiología'),
 (SELECT id FROM usuario WHERE email = 'enfermera@hc.edu.uy')),
('Indicaciones para ecocardiograma transesofágico',
 'Preparación y cuidados posteriores.', 'ejemplo_08.pdf', '2026-09-18', '2026-09-18 09:00:00', 1,
 (SELECT id FROM categoria WHERE nombre = 'Cardiología'),
 (SELECT id FROM usuario WHERE email = 'medico@hc.edu.uy')),
('Pauta para pacientes ostomizados',
 'Cuidados diarios de la ostomía (7 páginas).', 'ejemplo_09.pdf', '2026-09-20', '2026-09-20 09:00:00', 1,
 (SELECT id FROM categoria WHERE nombre = 'Gastroenterología - Unidad de Ostomías'),
 (SELECT id FROM usuario WHERE email = 'enfermera@hc.edu.uy'));

-- ------------------------------------------------------------
-- Encuestas de ejemplo (ids fijos: la base se crea de cero con el DDL)
--   1: "Satisfacción con la atención" (general, publicada)
--   2: "Uso del código QR" (servicio Emergencia, publicada)
--   3: "Encuesta de Cardiología" (servicio Cardiología, borrador)
-- ------------------------------------------------------------
-- Categorías de encuestas (el funcionario puede agregar más desde el panel)
INSERT INTO categoria_encuesta (nombre) VALUES
('Satisfacción'), ('Atención al paciente'), ('Instalaciones'), ('Uso del sistema');

INSERT INTO encuesta (id, titulo, descripcion, estado, version, fecha_creacion, id_categoria, id_categoria_encuesta, id_usuario) VALUES
(1, 'Satisfacción con la atención', 'Queremos conocer tu opinión sobre la atención recibida.', 'publicada', 1, '2026-09-25 10:00:00',
   NULL, (SELECT id FROM categoria_encuesta WHERE nombre = 'Satisfacción'), (SELECT id FROM usuario WHERE email = 'admin@hc.edu.uy')),
(2, 'Uso del código QR', 'Contanos qué te pareció acceder a la información con el QR.', 'publicada', 1, '2026-09-28 11:00:00',
   (SELECT id FROM categoria WHERE nombre = 'Emergencia'), (SELECT id FROM categoria_encuesta WHERE nombre = 'Uso del sistema'), (SELECT id FROM usuario WHERE email = 'medico@hc.edu.uy')),
(3, 'Encuesta de Cardiología', 'Borrador: todavía no la ve el paciente.', 'borrador', 1, '2026-10-02 15:30:00',
   (SELECT id FROM categoria WHERE nombre = 'Cardiología'), (SELECT id FROM categoria_encuesta WHERE nombre = 'Atención al paciente'), (SELECT id FROM usuario WHERE email = 'enfermera@hc.edu.uy'));

-- tipos: unica, multiple, valoracion (1 a 5), texto
INSERT INTO pregunta (id, id_encuesta, texto, tipo, obligatoria, orden) VALUES
(1, 1, '¿Cómo calificarías la atención recibida?', 'valoracion', 1, 1),
(2, 1, '¿Cómo fue el tiempo de espera?', 'unica', 1, 2),
(3, 1, '¿Qué te pareció útil? (podés marcar varias)', 'multiple', 0, 3),
(4, 1, 'Comentarios adicionales', 'texto', 0, 4),
(5, 2, '¿Qué tan fácil fue acceder con el QR?', 'valoracion', 1, 1),
(6, 3, '¿Cómo calificarías la atención en Cardiología?', 'valoracion', 1, 1),
(7, 3, '¿Qué podríamos mejorar?', 'texto', 0, 2);

INSERT INTO opcion (id, id_pregunta, texto, orden) VALUES
(1, 2, 'Corto', 1), (2, 2, 'Aceptable', 2), (3, 2, 'Largo', 3),
(4, 3, 'Información clara', 1), (5, 3, 'Rapidez', 2), (6, 3, 'Trato del personal', 3);

-- Seis envíos ya respondidos (anónimos) para ver los resultados en el panel
INSERT INTO envio (id, id_encuesta, fecha) VALUES
(1, 1, '2026-10-01 10:15:00'), (2, 1, '2026-10-02 16:40:00'),
(3, 1, '2026-10-05 09:05:00'), (4, 1, '2026-10-07 12:30:00'),
(5, 2, '2026-10-08 08:50:00'), (6, 2, '2026-10-09 18:20:00');

-- (id_envio, id_pregunta, id_opcion, valor_escala, valor_texto)
INSERT INTO respuesta (id_envio, id_pregunta, id_opcion, valor_escala, valor_texto) VALUES
(1, 1, NULL, 5, NULL), (1, 2, 1, NULL, NULL), (1, 3, 4, NULL, NULL), (1, 3, 5, NULL, NULL), (1, 4, NULL, NULL, 'Muy buena atención.'),
(2, 1, NULL, 4, NULL), (2, 2, 2, NULL, NULL), (2, 3, 6, NULL, NULL), (2, 4, NULL, NULL, 'Todo bien, la espera fue larga.'),
(3, 1, NULL, 5, NULL), (3, 2, 1, NULL, NULL), (3, 3, 4, NULL, NULL),
(4, 1, NULL, 3, NULL), (4, 2, 3, NULL, NULL), (4, 3, 5, NULL, NULL), (4, 4, NULL, NULL, 'Podría mejorar la señalización.'),
(5, 5, NULL, 4, NULL), (6, 5, NULL, 5, NULL);

-- Algunas acciones en el registro de acciones, de ejemplo
INSERT INTO registro (fecha, accion, detalle, entidad, id_entidad, id_usuario) VALUES
('2026-09-25 10:00:00', 'encuesta_crear', 'Se creó la encuesta "Satisfacción con la atención"', 'encuesta', 1, (SELECT id FROM usuario WHERE email = 'admin@hc.edu.uy')),
('2026-09-28 11:00:00', 'encuesta_crear', 'Se creó la encuesta "Uso del código QR"', 'encuesta', 2, (SELECT id FROM usuario WHERE email = 'medico@hc.edu.uy')),
('2026-10-02 15:30:00', 'encuesta_crear', 'Se creó la encuesta "Encuesta de Cardiología"', 'encuesta', 3, (SELECT id FROM usuario WHERE email = 'enfermera@hc.edu.uy'));
