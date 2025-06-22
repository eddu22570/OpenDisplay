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

// Suppression d'un utilisateur
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if ($_GET['delete'] != $_SESSION['user_id']) { // Ne pas supprimer soi-même
        // On récupère le nom d'utilisateur pour le log
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $user = $stmt->fetch();
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        log_action($pdo, $_SESSION['user_id'], "suppression", "utilisateur", $_GET['delete'], json_encode(['username' => $user['username'] ?? '']));
        $message = "🗑️ Utilisateur supprimé.";
    } else {
        $message = "❌ Vous ne pouvez pas supprimer votre propre compte.";
    }
}

// Modification d'un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit' && isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $ecran_id = ($role === 'gestionnaire' && isset($_POST['ecran_id']) && $_POST['ecran_id'] !== '') ? intval($_POST['ecran_id']) : null;

    // Vérifier unicité du nom d'utilisateur (hors l'utilisateur modifié)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $edit_id]);
    if ($stmt->fetch()) {
        $message = "❌ Nom d'utilisateur déjà utilisé.";
    } else {
        // Modification du mot de passe si renseigné
        if (!empty($_POST['password'])) {
            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username=?, password_hash=?, role=?, ecran_id=? WHERE id=?");
            $stmt->execute([$username, $password_hash, $role, $ecran_id, $edit_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username=?, role=?, ecran_id=? WHERE id=?");
            $stmt->execute([$username, $role, $ecran_id, $edit_id]);
        }
        log_action($pdo, $_SESSION['user_id'], "modification", "utilisateur", $edit_id, json_encode(['username' => $username, 'role' => $role, 'ecran_id' => $ecran_id]));
        $message = "✏️ Utilisateur modifié.";
        $edit_user = null; // On sort du mode édition
    }
}

// Création d'un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $ecran_id = ($role === 'gestionnaire' && isset($_POST['ecran_id']) && $_POST['ecran_id'] !== '') ? intval($_POST['ecran_id']) : null;
    $password = $_POST['password'];
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $message = "❌ Nom d'utilisateur déjà utilisé.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, ecran_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $password_hash, $role, $ecran_id]);
        $user_id = $pdo->lastInsertId();
        log_action($pdo, $_SESSION['user_id'], "ajout", "utilisateur", $user_id, json_encode(['username' => $username, 'role' => $role, 'ecran_id' => $ecran_id]));
        $message = "✅ Utilisateur créé.";
    }
}

// Récupération des utilisateurs et écrans pour les listes déroulantes
$users = $pdo->query("SELECT * FROM users ORDER BY username")->fetchAll();
$ecrans = $pdo->query("SELECT id, nom FROM ecrans ORDER BY nom")->fetchAll();

// Pour édition
$edit_user = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des utilisateurs - OpenDisplay</title>
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
        input[type="text"], input[type="password"], select {
            padding: 6px 10px;
            border: 1px solid #b0bec5;
            border-radius: 4px;
            width: 220px;
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
    <script>
    // Affiche ou masque le champ écran selon le rôle
    function toggleEcranSelect(roleSelect, ecranDivId) {
        var ecranDiv = document.getElementById(ecranDivId);
        if (roleSelect.value === 'gestionnaire') {
            ecranDiv.style.display = 'block';
        } else {
            ecranDiv.style.display = 'none';
        }
    }
    </script>
</head>
<body>
    <h1>Gestion des utilisateurs</h1>
    <div class="container">
        <a href="index.php" class="back-link">← Retour à l'accueil</a>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <table>
            <tr>
                <th>ID</th>
                <th>Nom d'utilisateur</th>
                <th>Rôle</th>
                <th>Écran associé</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['id']) ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars($user['role']) ?></td>
                <td>
                    <?php
                    if ($user['ecran_id']) {
                        foreach ($ecrans as $ecran) {
                            if ($ecran['id'] == $user['ecran_id']) {
                                echo htmlspecialchars($ecran['nom']);
                                break;
                            }
                        }
                    } else {
                        echo '—';
                    }
                    ?>
                </td>
                <td class="actions">
                    <a href="?edit=<?= $user['id'] ?>">✏️ Modifier</a>
                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <a href="?delete=<?= $user['id'] ?>" onclick="return confirm('Supprimer cet utilisateur ?')">🗑️ Supprimer</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h2><?= $edit_user ? "Modifier l'utilisateur" : "Ajouter un utilisateur" ?></h2>
        <form method="post">
            <?php if ($edit_user): ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="edit_id" value="<?= $edit_user['id'] ?>">
            <?php else: ?>
                <input type="hidden" name="action" value="create">
            <?php endif; ?>
            <label>Nom d'utilisateur :<br>
                <input type="text" name="username" required value="<?= $edit_user ? htmlspecialchars($edit_user['username']) : '' ?>">
            </label><br><br>
            <label>Rôle :<br>
                <select name="role" required onchange="toggleEcranSelect(this, 'ecran-select')">
                    <option value="admin" <?= $edit_user && $edit_user['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                    <option value="gestionnaire" <?= $edit_user && $edit_user['role'] === 'gestionnaire' ? 'selected' : '' ?>>Gestionnaire d'écran</option>
                </select>
            </label><br><br>
            <div id="ecran-select" style="display:<?= ($edit_user && $edit_user['role'] === 'gestionnaire') || (!$edit_user) ? 'block' : 'none' ?>;">
                <label>Écran associé (pour gestionnaire) :<br>
                    <select name="ecran_id">
                        <option value="">— Aucun —</option>
                        <?php foreach ($ecrans as $ecran): ?>
                            <option value="<?= $ecran['id'] ?>" <?= $edit_user && $edit_user['ecran_id'] == $ecran['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ecran['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label><br><br>
            </div>
            <label>Mot de passe :<?php if ($edit_user): ?> <span style="font-weight:normal">(laisser vide pour ne pas changer)</span><?php endif; ?><br>
                <input type="password" name="password" <?= $edit_user ? '' : 'required' ?>>
            </label><br><br>
            <button type="submit"><?= $edit_user ? "Enregistrer" : "Ajouter" ?></button>
            <?php if ($edit_user): ?>
                <a href="users.php">Annuler</a>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
