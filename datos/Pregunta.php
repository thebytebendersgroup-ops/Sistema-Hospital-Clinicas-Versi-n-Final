<?php
// ============================================================
// CAPA DE DATOS - Pregunta (con sus opciones)
// Las preguntas pertenecen a UNA encuesta y se pueden ordenar.
// Tipos: 'unica' (elegir una opción), 'multiple' (elegir varias),
//        'valoracion' (nota de 1 a 5) y 'texto' (respuesta breve).
// Solo se modifican mientras la encuesta es borrador (todavía sin respuestas).
// ============================================================

class Pregunta {

    // Atributos
    public $id;
    public $id_encuesta;
    public $texto;
    public $tipo;
    public $obligatoria = 1;
    public $opciones = [];    // lista de textos (solo para 'unica' y 'multiple')

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Guarda la pregunta y sus opciones. Se usa una transacción: o se guarda todo o nada.
    // Al editar, las opciones se vuelven a escribir (la encuesta es borrador, no tiene respuestas).
    public function guardar() {
        $this->pdo->beginTransaction();
        try {
            if ($this->id) {
                $this->pdo->prepare("UPDATE pregunta SET texto = ?, tipo = ?, obligatoria = ? WHERE id = ?")
                          ->execute([$this->texto, $this->tipo, $this->obligatoria ? 1 : 0, $this->id]);
                $this->pdo->prepare("DELETE FROM opcion WHERE id_pregunta = ?")->execute([$this->id]);
            } else {
                $this->pdo->prepare(
                    "INSERT INTO pregunta (id_encuesta, texto, tipo, obligatoria, orden) VALUES (?, ?, ?, ?, ?)"
                )->execute([$this->id_encuesta, $this->texto, $this->tipo, $this->obligatoria ? 1 : 0,
                            $this->siguienteOrden($this->id_encuesta)]);
                $this->id = $this->pdo->lastInsertId();
            }

            // Solo las preguntas de opción tienen opciones
            if (in_array($this->tipo, ['unica', 'multiple'], true)) {
                $insertar = $this->pdo->prepare("INSERT INTO opcion (id_pregunta, texto, orden) VALUES (?, ?, ?)");
                $numero = 1;
                foreach ($this->opciones as $textoOpcion) {
                    $insertar->execute([$this->id, $textoOpcion, $numero]);
                    $numero++;
                }
            }
            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // Elimina una pregunta y sus opciones (solo se permite en encuestas borrador).
    public function eliminar($id) {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("DELETE FROM opcion WHERE id_pregunta = ?")->execute([$id]);
            $this->pdo->prepare("DELETE FROM pregunta WHERE id = ?")->execute([$id]);
            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function siguienteOrden($idEncuesta) {
        $stmt = $this->pdo->prepare("SELECT COALESCE(MAX(orden), 0) + 1 FROM pregunta WHERE id_encuesta = ?");
        $stmt->execute([$idEncuesta]);
        return (int) $stmt->fetchColumn();
    }

    // Sube o baja una pregunta intercambiando su lugar con la vecina. $direccion: 'arriba' o 'abajo'.
    public function mover($id, $direccion) {
        $actual = $this->buscarPorId($id);
        if (!$actual) {
            return;
        }
        if ($direccion === 'arriba') {
            $sql = "SELECT id, orden FROM pregunta WHERE id_encuesta = ? AND orden < ? ORDER BY orden DESC LIMIT 1";
        } else {
            $sql = "SELECT id, orden FROM pregunta WHERE id_encuesta = ? AND orden > ? ORDER BY orden ASC LIMIT 1";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$actual['id_encuesta'], $actual['orden']]);
        $vecina = $stmt->fetch();
        if (!$vecina) {
            return;   // ya está arriba (o abajo) de todo
        }
        $cambiar = $this->pdo->prepare("UPDATE pregunta SET orden = ? WHERE id = ?");
        $cambiar->execute([$vecina['orden'], $actual['id']]);
        $cambiar->execute([$actual['orden'], $vecina['id']]);
    }

    // Una pregunta con sus opciones (false si no existe).
    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("SELECT id, id_encuesta, texto, tipo, obligatoria, orden FROM pregunta WHERE id = ?");
        $stmt->execute([$id]);
        $pregunta = $stmt->fetch();
        if ($pregunta) {
            $pregunta['opciones'] = $this->opcionesDe($id);
        }
        return $pregunta;
    }

    private function opcionesDe($idPregunta) {
        $stmt = $this->pdo->prepare("SELECT id, texto, orden FROM opcion WHERE id_pregunta = ? ORDER BY orden, id");
        $stmt->execute([$idPregunta]);
        return $stmt->fetchAll();
    }

    // Todas las preguntas de una encuesta, en orden, cada una con su lista de opciones.
    public function listarPorEncuesta($idEncuesta) {
        $stmt = $this->pdo->prepare(
            "SELECT id, id_encuesta, texto, tipo, obligatoria, orden
             FROM pregunta WHERE id_encuesta = ? ORDER BY orden, id"
        );
        $stmt->execute([$idEncuesta]);
        $preguntas = $stmt->fetchAll();

        // Se traen todas las opciones de la encuesta de una vez y se reparten por pregunta
        $stmt = $this->pdo->prepare(
            "SELECT o.id, o.id_pregunta, o.texto, o.orden
             FROM opcion o JOIN pregunta p ON p.id = o.id_pregunta
             WHERE p.id_encuesta = ? ORDER BY o.orden, o.id"
        );
        $stmt->execute([$idEncuesta]);
        $porPregunta = [];
        foreach ($stmt->fetchAll() as $opcion) {
            $porPregunta[$opcion['id_pregunta']][] = $opcion;
        }
        foreach ($preguntas as $i => $pregunta) {
            $preguntas[$i]['opciones'] = $porPregunta[$pregunta['id']] ?? [];
        }
        return $preguntas;
    }
}
