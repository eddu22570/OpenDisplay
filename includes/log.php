<?php
function log_action($pdo, $utilisateur_id, $action, $objet, $objet_id, $details = null) {
    $stmt = $pdo->prepare("INSERT INTO logs (date, utilisateur_id, action, objet, objet_id, details, ip)
        VALUES (NOW(), ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $utilisateur_id,
        $action,
        $objet,
        $objet_id,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);
}
