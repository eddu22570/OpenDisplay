<?php
require_once '../includes/db.php';
$pdo = getPDO();

$username = 'admin';
$password = 'admin';
$role = 'admin';

// Hachage du mot de passe pour la sécurité
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Vérifie si le compte existe déjà
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo "Le compte admin existe déjà.";
} else {
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
    $stmt->execute([$username, $password_hash, $role]);
    echo "Compte admin créé avec succès !";
}
?>
