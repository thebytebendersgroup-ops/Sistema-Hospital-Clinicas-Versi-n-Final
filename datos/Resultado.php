<?php
// ============================================================
// CAPA DE DATOS - Resultado
// Estadísticas de las encuestas: cantidad de envíos, promedios, cantidad y
// porcentaje por opción, respuestas de texto y datos para exportar a CSV.
// Los filtros de fecha ($desde y $hasta, formato AAAA-MM-DD) son opcionales.
// ============================================================

class Resultado {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Condición de fechas sobre la tabla envio (alias "v"). Devuelve [texto SQL, valores].
    private function fechas($desde, $hasta) {
        $sql = '';
        $valores = [];
        if ($desde !== '') {
            $sql .= ' AND v.fecha >= ?';
            $valores[] = $desde . ' 00:00:00';
        }
        if ($hasta !== '') {
            $sql .= ' AND v.fecha <= ?';
            $valores[] = $hasta . ' 23:59:59';
        }
        return [$sql, $valores];
    }

    // Números generales para el inicio del panel.
    public function indicadores() {
        $envios = (int) $this->pdo->query("SELECT COUNT(*) FROM envio")->fetchColumn();
        $promedio = $this->pdo->query("SELECT AVG(valor_escala) FROM respuesta WHERE valor_escala IS NOT NULL")->fetchColumn();
        return ['envios' => $envios, 'promedio' => $promedio !== null ? round((float) $promedio, 1) : 0];
    }

    // Una fila por encuesta con su cantidad de envíos y su promedio de valoración.
    // Se puede buscar por título y filtrar por servicio y por fechas.
    public function resumenEncuestas($buscar, $idCategoria, $desde, $hasta) {
        list($fechas, $valoresFechas) = $this->fechas($desde, $hasta);

        $sql = "SELECT e.id, e.titulo, e.estado, e.version, c.nombre AS servicio,
                       (SELECT COUNT(*) FROM envio v WHERE v.id_encuesta = e.id" . $fechas . ") AS envios,
                       (SELECT AVG(r.valor_escala) FROM respuesta r JOIN envio v ON v.id = r.id_envio
                        WHERE v.id_encuesta = e.id AND r.valor_escala IS NOT NULL" . $fechas . ") AS promedio
                FROM encuesta e
                LEFT JOIN categoria c ON c.id = e.id_categoria
                WHERE e.activo = 1";
        $valores = array_merge($valoresFechas, $valoresFechas);

        if ($buscar !== '') {
            $sql .= " AND e.titulo LIKE ? ESCAPE '!'";
            $valores[] = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $buscar) . '%';
        }
        if ($idCategoria === 'general') {
            $sql .= " AND e.id_categoria IS NULL";
        } elseif ($idCategoria !== '') {
            $sql .= " AND e.id_categoria = ?";
            $valores[] = $idCategoria;
        }
        $sql .= " ORDER BY e.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($valores);
        $filas = $stmt->fetchAll();
        foreach ($filas as $i => $fila) {
            $filas[$i]['promedio'] = $fila['promedio'] !== null ? round((float) $fila['promedio'], 1) : null;
        }
        return $filas;
    }

    // Resultados completos de UNA encuesta.
    // Devuelve ['total' => envíos, 'preguntas' => [...]]. Cada pregunta trae según su tipo:
    //   valoracion:        'cantidad', 'promedio', 'por_nota' (1 a 5, con 'cantidad' y 'porcentaje')
    //   unica / multiple:  'cantidad' (personas que respondieron) y 'opciones' (con 'cantidad' y 'porcentaje')
    //   texto:             'cantidad', 'total_textos' y 'textos' (una página de comentarios)
    public function detalle($idEncuesta, $desde, $hasta, $paginaTexto, $porPagina) {
        list($fechas, $valoresFechas) = $this->fechas($desde, $hasta);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM envio v WHERE v.id_encuesta = ?" . $fechas);
        $stmt->execute(array_merge([$idEncuesta], $valoresFechas));
        $total = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT id, texto, tipo FROM pregunta WHERE id_encuesta = ? ORDER BY orden, id");
        $stmt->execute([$idEncuesta]);
        $preguntas = $stmt->fetchAll();

        foreach ($preguntas as $i => $p) {
            $idPregunta = $p['id'];
            $p['cantidad'] = 0;

            if ($p['tipo'] === 'valoracion') {
                $p['por_nota'] = [];
                for ($nota = 5; $nota >= 1; $nota--) {
                    $p['por_nota'][$nota] = ['cantidad' => 0, 'porcentaje' => 0];
                }
                $stmt = $this->pdo->prepare(
                    "SELECT r.valor_escala AS nota, COUNT(*) AS cantidad
                     FROM respuesta r JOIN envio v ON v.id = r.id_envio
                     WHERE r.id_pregunta = ? AND r.valor_escala IS NOT NULL" . $fechas . "
                     GROUP BY r.valor_escala"
                );
                $stmt->execute(array_merge([$idPregunta], $valoresFechas));
                $suma = 0;
                foreach ($stmt->fetchAll() as $fila) {
                    $p['por_nota'][(int) $fila['nota']]['cantidad'] = (int) $fila['cantidad'];
                    $p['cantidad'] += (int) $fila['cantidad'];
                    $suma += (int) $fila['nota'] * (int) $fila['cantidad'];
                }
                foreach ($p['por_nota'] as $nota => $dato) {
                    $p['por_nota'][$nota]['porcentaje'] = $p['cantidad'] > 0 ? round($dato['cantidad'] * 100 / $p['cantidad']) : 0;
                }
                $p['promedio'] = $p['cantidad'] > 0 ? round($suma / $p['cantidad'], 1) : 0;

            } elseif ($p['tipo'] === 'unica' || $p['tipo'] === 'multiple') {
                // Cuántos envíos respondieron esta pregunta (base para los porcentajes)
                $stmt = $this->pdo->prepare(
                    "SELECT COUNT(DISTINCT r.id_envio) FROM respuesta r JOIN envio v ON v.id = r.id_envio
                     WHERE r.id_pregunta = ? AND r.id_opcion IS NOT NULL" . $fechas
                );
                $stmt->execute(array_merge([$idPregunta], $valoresFechas));
                $p['cantidad'] = (int) $stmt->fetchColumn();

                $stmt = $this->pdo->prepare(
                    "SELECT o.id, o.texto,
                            (SELECT COUNT(*) FROM respuesta r JOIN envio v ON v.id = r.id_envio
                             WHERE r.id_opcion = o.id" . $fechas . ") AS cantidad
                     FROM opcion o WHERE o.id_pregunta = ? ORDER BY o.orden, o.id"
                );
                $stmt->execute(array_merge($valoresFechas, [$idPregunta]));
                $p['opciones'] = $stmt->fetchAll();
                foreach ($p['opciones'] as $j => $opcion) {
                    $p['opciones'][$j]['cantidad'] = (int) $opcion['cantidad'];
                    $p['opciones'][$j]['porcentaje'] = $p['cantidad'] > 0 ? round($opcion['cantidad'] * 100 / $p['cantidad']) : 0;
                }

            } else {   // texto
                $stmt = $this->pdo->prepare(
                    "SELECT COUNT(*) FROM respuesta r JOIN envio v ON v.id = r.id_envio
                     WHERE r.id_pregunta = ? AND r.valor_texto IS NOT NULL" . $fechas
                );
                $stmt->execute(array_merge([$idPregunta], $valoresFechas));
                $p['total_textos'] = (int) $stmt->fetchColumn();
                $p['cantidad'] = $p['total_textos'];

                $desplazamiento = max(0, ($paginaTexto - 1) * $porPagina);
                $stmt = $this->pdo->prepare(
                    "SELECT r.valor_texto AS texto, v.fecha
                     FROM respuesta r JOIN envio v ON v.id = r.id_envio
                     WHERE r.id_pregunta = ? AND r.valor_texto IS NOT NULL" . $fechas . "
                     ORDER BY v.id DESC LIMIT " . (int) $porPagina . " OFFSET " . (int) $desplazamiento
                );
                $stmt->execute(array_merge([$idPregunta], $valoresFechas));
                $p['textos'] = $stmt->fetchAll();
            }
            $preguntas[$i] = $p;
        }
        return ['total' => $total, 'preguntas' => $preguntas];
    }

    // Todas las respuestas de una encuesta, una fila por respuesta (para exportar a CSV).
    public function filasParaExportar($idEncuesta, $desde, $hasta) {
        list($fechas, $valoresFechas) = $this->fechas($desde, $hasta);
        $stmt = $this->pdo->prepare(
            "SELECT v.id AS envio, v.fecha, p.texto AS pregunta, p.tipo,
                    r.valor_escala, r.valor_texto, o.texto AS opcion
             FROM respuesta r
             JOIN envio v ON v.id = r.id_envio
             JOIN pregunta p ON p.id = r.id_pregunta
             LEFT JOIN opcion o ON o.id = r.id_opcion
             WHERE v.id_encuesta = ?" . $fechas . "
             ORDER BY v.id, p.orden, r.id"
        );
        $stmt->execute(array_merge([$idEncuesta], $valoresFechas));
        return $stmt->fetchAll();
    }
}
