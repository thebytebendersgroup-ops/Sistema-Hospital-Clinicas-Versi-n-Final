-- Control de recuperación: cuenta los registros de cada tabla.
-- Se ejecuta sobre la base restaurada y sobre la original; los números deben coincidir.
SELECT 'usuario'  AS tabla, COUNT(*) AS filas FROM usuario
UNION ALL SELECT 'categoria',  COUNT(*) FROM categoria
UNION ALL SELECT 'documento',  COUNT(*) FROM documento
UNION ALL SELECT 'encuesta',   COUNT(*) FROM encuesta
UNION ALL SELECT 'pregunta',   COUNT(*) FROM pregunta
UNION ALL SELECT 'opcion',     COUNT(*) FROM opcion
UNION ALL SELECT 'envio',      COUNT(*) FROM envio
UNION ALL SELECT 'respuesta',  COUNT(*) FROM respuesta
UNION ALL SELECT 'registro',   COUNT(*) FROM registro;
