<?php
// ============================================================
// CAPA DE DATOS - Bitacora
// Registro de las acciones importantes: quién hizo qué y cuándo.
// ============================================================

class Bitacora {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Guarda una acción. $idUsuario puede ser null (ej.: un intento de login fallido).
    // $entidad / $idEntidad: sobre qué se actuó (ej.: 'documento', 5) para ver su historial.
    public function registrar($accion, $detalle, $idUsuario = null, $entidad = null, $idEntidad = null) {
        $this->pdo->prepare(
            "INSERT INTO bitacora (fecha, accion, detalle, entidad, id_entidad, id_usuario)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([date('Y-m-d H:i:s'), $accion, mb_substr($detalle, 0, 255), $entidad, $idEntidad, $idUsuario]);
    }

    private function filtro($accion, $entidad, $idEntidad) {
        $sql = ' WHERE 1 = 1';
        $valores = [];
        if ($accion !== '') {
            $sql .= ' AND b.accion = ?';
            $valores[] = $accion;
        }
        if ($entidad !== '' && $idEntidad > 0) {
            $sql .= ' AND b.entidad = ? AND b.id_entidad = ?';
            $valores[] = $entidad;
            $valores[] = $idEntidad;
        }
        return [$sql, $valores];
    }

    // Listado paginado (lo más nuevo primero).
    public function listar($accion, $entidad, $idEntidad, $limite, $desplazamiento) {
        list($condicion, $valores) = $this->filtro($accion, $entidad, $idEntidad);
        $stmt = $this->pdo->prepare(
            "SELECT b.id, b.fecha, b.accion, b.detalle, b.entidad, b.id_entidad, u.nombre AS usuario
             FROM bitacora b
             LEFT JOIN usuario u ON u.id = b.id_usuario" . $condicion
            . " ORDER BY b.id DESC LIMIT " . (int) $limite . " OFFSET " . (int) $desplazamiento
        );
        $stmt->execute($valores);
        return $stmt->fetchAll();
    }

    public function contar($accion, $entidad, $idEntidad) {
        list($condicion, $valores) = $this->filtro($accion, $entidad, $idEntidad);
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM bitacora b" . $condicion);
        $stmt->execute($valores);
        return (int) $stmt->fetchColumn();
    }

    // Tipos de acción que existen (para el filtro de la pantalla).
    public function listarAcciones() {
        return $this->pdo->query("SELECT DISTINCT accion FROM bitacora ORDER BY accion")->fetchAll(PDO::FETCH_COLUMN);
    }
}
