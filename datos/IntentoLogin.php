<?php
// ============================================================
// CAPA DE DATOS - IntentoLogin
// Guarda los inicios de sesión fallidos para poder limitar los intentos repetidos
// (protección contra adivinar contraseñas probando muchas veces).
// ============================================================

class IntentoLogin {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function registrar($email, $ip) {
        $this->pdo->prepare("INSERT INTO intento_login (email, ip, fecha) VALUES (?, ?, ?)")
                  ->execute([mb_substr($email, 0, 100), mb_substr($ip, 0, 45), date('Y-m-d H:i:s')]);
    }

    // Fallos de un email y de una IP desde la fecha indicada.
    public function contar($email, $ip, $desde) {
        $porEmail = $this->pdo->prepare("SELECT COUNT(*) FROM intento_login WHERE LOWER(email) = LOWER(?) AND fecha >= ?");
        $porEmail->execute([$email, $desde]);
        $porIp = $this->pdo->prepare("SELECT COUNT(*) FROM intento_login WHERE ip = ? AND fecha >= ?");
        $porIp->execute([$ip, $desde]);
        return ['email' => (int) $porEmail->fetchColumn(), 'ip' => (int) $porIp->fetchColumn()];
    }

    // Cuando el login es correcto se borran los fallos de ese email.
    public function limpiar($email) {
        $this->pdo->prepare("DELETE FROM intento_login WHERE LOWER(email) = LOWER(?)")->execute([$email]);
    }
}
