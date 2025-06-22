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
require_once '../includes/log.php';
$pdo = getPDO();

$message = '';

// Suppression d'un écran
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM ecrans WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    log_action($pdo, $_SESSION['user_id'], "suppression", "ecran", $_GET['delete'], null);
    $message = "🗑️ Écran supprimé.";
}

// Modification d'un écran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id']) && isset($_POST['edit_nom'])) {
    $stmt = $pdo->prepare("UPDATE ecrans SET nom = ?, commune = ? WHERE id = ?");
    $stmt->execute([
        $_POST['edit_nom'],
        isset($_POST['edit_commune']) ? trim($_POST['edit_commune']) : null,
        $_POST['edit_id']
    ]);
    log_action($pdo, $_SESSION['user_id'], "modification", "ecran", $_POST['edit_id'], json_encode($_POST));
    $message = "✏️ Écran modifié.";
}

// Ajout d'un écran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom']) && empty($_POST['edit_id'])) {
    $stmt = $pdo->prepare("INSERT INTO ecrans (nom, commune) VALUES (?, ?)");
    $stmt->execute([
        $_POST['nom'],
        isset($_POST['commune']) ? trim($_POST['commune']) : null
    ]);
    $ecran_id = $pdo->lastInsertId();
    log_action($pdo, $_SESSION['user_id'], "ajout", "ecran", $ecran_id, json_encode($_POST));
    $message = "✅ Écran ajouté avec succès !";
}

$ecrans = $pdo->query("SELECT * FROM ecrans")->fetchAll();
$edit_ecran = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM ecrans WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_ecran = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des écrans - OpenDisplay</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f6fa;
            margin: 0;
            padding: 0;
        }
        h1 {
            background: #1e3c72;
            color: #fff;
            margin: 0;
            padding: 30px 0;
            text-align: center;
        }
        .container {
            max-width: 700px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px #0001;
            padding: 30px 40px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #2a5298;
            text-decoration: none;
            font-size: 1em;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .message {
            background: #e6ffe6;
            color: #217a35;
            border: 1px solid #b2e6b2;
            padding: 10px 18px;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 1.1em;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2em;
        }
        th, td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e0e0;
            text-align: left;
        }
        th {
            background: #1e3c72;
            color: #fff;
        }
        tr:nth-child(even) {
            background: #f0f4fa;
        }
        .actions a {
            margin-right: 10px;
        }
        form {
            margin-bottom: 2em;
        }
        label {
            font-weight: bold;
        }
        input[type="text"] {
            padding: 6px 10px;
            border: 1px solid #b0bec5;
            border-radius: 4px;
            width: 250px;
            margin-top: 4px;
        }
        button {
            background: #1e3c72;
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            font-size: 1em;
            cursor: pointer;
        }
        button:hover {
            background: #2a5298;
        }
        a {
            color: #2a5298;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Gestion des écrans</h1>
    <div class="container">
        <a href="index.php" class="back-link">← Retour à l'accueil</a>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <table>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Commune</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($ecrans as $ecran): ?>
            <tr>
                <td><?= htmlspecialchars($ecran['id']) ?></td>
                <td><?= htmlspecialchars($ecran['nom']) ?></td>
                <td><?= htmlspecialchars($ecran['commune']) ?></td>
                <td class="actions">
                    <a href="?edit=<?= $ecran['id'] ?>">✏️ Modifier</a>
                    <a href="?delete=<?= $ecran['id'] ?>" onclick="return confirm('Supprimer cet écran ?')">🗑️ Supprimer</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h2><?= $edit_ecran ? "Modifier l'écran" : "Ajouter un écran" ?></h2>
        <form method="post">
            <?php if ($edit_ecran): ?>
                <input type="hidden" name="edit_id" value="<?= $edit_ecran['id'] ?>">
            <?php endif; ?>
            <label>Nom :<br>
                <input type="text" name="<?= $edit_ecran ? 'edit_nom' : 'nom' ?>" required value="<?= $edit_ecran ? htmlspecialchars($edit_ecran['nom']) : '' ?>">
            </label><br><br>
            <label>Commune :<br>
                <input type="text" name="<?= $edit_ecran ? 'edit_commune' : 'commune' ?>" value="<?= $edit_ecran ? htmlspecialchars($edit_ecran['commune']) : '' ?>">
            </label><br><br>
            <button type="submit"><?= $edit_ecran ? "Enregistrer" : "Ajouter" ?></button>
            <?php if ($edit_ecran): ?>
                <a href="ecrans.php">Annuler</a>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
