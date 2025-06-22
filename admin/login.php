<?php
session_start();
require_once '../includes/db.php';
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['ecran_id'] = $user['ecran_id']; // Important pour les gestionnaires
        header('Location: index.php');
        exit;
    } else {
        $error = "Identifiants invalides.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - OpenDisplay</title>
    <style>
        body {
            background: linear-gradient(120deg, #1e3c72 0%, #2a5298 100%);
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px #0002;
            padding: 40px 30px 30px 30px;
            max-width: 350px;
            width: 100%;
        }
        .login-container h2 {
            text-align: center;
            color: #1e3c72;
            margin-bottom: 30px;
        }
        .login-container form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            padding: 10px 12px;
            border: 1px solid #b0bec5;
            border-radius: 5px;
            font-size: 1em;
            background: #f4f6fa;
            transition: border 0.2s;
        }
        .login-container input[type="text"]:focus,
        .login-container input[type="password"]:focus {
            border: 1.5px solid #1e3c72;
            outline: none;
        }
        .login-container button {
            background: linear-gradient(90deg, #1e3c72 60%, #2a5298 100%);
            color: #fff;
            border: none;
            padding: 10px 0;
            border-radius: 5px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }
        .login-container button:hover {
            background: linear-gradient(90deg, #2a5298 60%, #1e3c72 100%);
        }
        .login-container .error {
            color: #d9534f;
            background: #ffeaea;
            border: 1px solid #f5c2c7;
            padding: 10px 14px;
            border-radius: 5px;
            margin-top: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Connexion OpenDisplay</h2>
        <form method="post">
            <input type="text" name="username" placeholder="Nom d'utilisateur" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Connexion</button>
            <?php if (!empty($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
