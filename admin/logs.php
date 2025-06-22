<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Accès réservé aux administrateurs.";
    exit;
}

require_once '../includes/db.php';
$pdo = getPDO();

$message = '';

// Traitement ajout manuel de log
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['objet'], $_POST['objet_id'])) {
    $stmt = $pdo->prepare("INSERT INTO logs (date, utilisateur_id, action, objet, objet_id, details, ip) VALUES (NOW(), ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        $_POST['action'],
        $_POST['objet'],
        $_POST['objet_id'],
        !empty($_POST['details']) ? $_POST['details'] : null,
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);
    $message = "Log ajouté avec succès.";
}

// Récupération des logs
$logs = $pdo->query("SELECT l.*, u.username FROM logs l LEFT JOIN users u ON l.utilisateur_id = u.id ORDER BY l.date DESC LIMIT 100")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Logs d'administration</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fa; }
        .container { max-width: 1000px; margin: 30px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 16px #0002; padding: 30px; }
        h1 { color: #1e3c72; }
        .message { background: #e6ffe6; color: #217a35; border: 1px solid #b2e6b2; padding: 10px 18px; border-radius: 6px; margin-bottom: 18px; font-size: 1.1em; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 2em; }
        th, td { padding: 8px 10px; border-bottom: 1px solid #e0e0e0; text-align: left; }
        th { background: #1e3c72; color: #fff; }
        tr:nth-child(even) { background: #f0f4fa; }
        form { margin-bottom: 2em; }
        input, select { padding: 6px 10px; border: 1px solid #b0bec5; border-radius: 4px; }
        button { background: #1e3c72; color: #fff; border: none; padding: 8px 18px; border-radius: 4px; font-size: 1em; cursor: pointer; }
        button:hover { background: #2a5298; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #2a5298; text-decoration: none; font-size: 1em; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <a href="index.php" class="back-link">← Retour à l'accueil admin</a>
    <h1>Historique des actions (logs)</h1>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <h2>Ajouter un log manuellement</h2>
    <form method="post">
        <label>Action :
            <select name="action" required>
                <option value="ajout">Ajout</option>
                <option value="modification">Modification</option>
                <option value="suppression">Suppression</option>
                <option value="connexion">Connexion</option>
                <option value="autre">Autre</option>
            </select>
        </label>
        <label>Objet :
            <select name="objet" required>
                <option value="ecran">Écran</option>
                <option value="utilisateur">Utilisateur</option>
                <option value="media">Média</option>
                <option value="playlist">Playlist</option>
                <option value="autre">Autre</option>
            </select>
        </label>
        <label>ID objet :
            <input type="number" name="objet_id" required>
        </label>
        <label>Détails :
            <input type="text" name="details" placeholder="(optionnel)">
        </label>
        <button type="submit">Ajouter le log</button>
    </form>

    <h2>100 derniers logs</h2>
    <table>
        <tr>
            <th>Date</th>
            <th>Utilisateur</th>
            <th>Action</th>
            <th>Objet</th>
            <th>ID</th>
            <th>Détails</th>
            <th>IP</th>
        </tr>
        <?php foreach ($logs as $log): ?>
        <tr>
            <td><?= htmlspecialchars($log['date']) ?></td>
            <td><?= htmlspecialchars($log['username'] ?? '—') ?></td>
            <td><?= htmlspecialchars($log['action']) ?></td>
            <td><?= htmlspecialchars($log['objet']) ?></td>
            <td><?= htmlspecialchars($log['objet_id']) ?></td>
            <td><?= htmlspecialchars($log['details']) ?></td>
            <td><?= htmlspecialchars($log['ip']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>
