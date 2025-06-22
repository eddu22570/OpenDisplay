<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db.php';
$pdo = getPDO();

$message = '';

// Déterminer le rôle et l'écran associé
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

if ($is_admin) {
    $ecrans = $pdo->query("SELECT * FROM ecrans")->fetchAll();
    $ecran_id = isset($_GET['ecran_id']) ? intval($_GET['ecran_id']) : (isset($ecrans[0]['id']) ? $ecrans[0]['id'] : null);
} else {
    $ecran_id = $_SESSION['ecran_id'] ?? null;
    if ($ecran_id) {
        $stmt = $pdo->prepare("SELECT * FROM ecrans WHERE id = ?");
        $stmt->execute([$ecran_id]);
        $ecrans = $stmt->fetchAll();
    } else {
        $ecrans = [];
    }
}

// Si aucun écran n'est associé, afficher un message d'erreur et stopper la page
if (!$ecran_id || empty($ecrans)) {
    echo "<!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <title>Gestion des playlists - OpenDisplay</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f6fa; }
            .error-box {
                margin: 100px auto;
                background: #fff;
                border-radius: 10px;
                box-shadow: 0 4px 24px #0001;
                max-width: 500px;
                padding: 40px;
                color: #d9534f;
                font-size: 1.2em;
                text-align: center;
                border: 1px solid #f5c2c7;
            }
            a { color: #1e3c72; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class='error-box'>
            <strong>Erreur :</strong><br>
            Aucun écran n'est associé à votre compte.<br>
            Merci de contacter un administrateur.<br><br>
            <a href='index.php'>Retour au tableau de bord</a>
        </div>
    </body>
    </html>";
    exit;
}

// Récupérer tous les médias
$medias = $pdo->query("SELECT * FROM media")->fetchAll();

// Suppression d'un média de la playlist
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && $ecran_id) {
    $stmt = $pdo->prepare("DELETE FROM playlists WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $message = "🗑️ Média retiré de la playlist.";
}

// Déplacement d'un média dans la playlist (haut/bas)
if (isset($_GET['move']) && in_array($_GET['move'], ['up', 'down']) && isset($_GET['pid']) && $ecran_id) {
    $pid = intval($_GET['pid']);
    // Récupérer la position actuelle
    $stmt = $pdo->prepare("SELECT position FROM playlists WHERE id = ?");
    $stmt->execute([$pid]);
    $current = $stmt->fetch();
    if ($current) {
        $curPos = $current['position'];
        $newPos = ($_GET['move'] === 'up') ? $curPos - 1 : $curPos + 1;
        // Trouver l'élément à échanger
        $stmt = $pdo->prepare("SELECT id FROM playlists WHERE ecran_id = ? AND position = ?");
        $stmt->execute([$ecran_id, $newPos]);
        $swap = $stmt->fetch();
        if ($swap) {
            // Échanger les positions
            $pdo->prepare("UPDATE playlists SET position = ? WHERE id = ?")->execute([$curPos, $swap['id']]);
            $pdo->prepare("UPDATE playlists SET position = ? WHERE id = ?")->execute([$newPos, $pid]);
        }
    }
}

// Ajout d'un média à la playlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ecran_id && isset($_POST['media_id'])) {
    // Trouver la prochaine position
    $pos = $pdo->prepare("SELECT COALESCE(MAX(position),0)+1 FROM playlists WHERE ecran_id=?");
    $pos->execute([$ecran_id]);
    $position = $pos->fetchColumn();
    // Ajouter le média à la playlist
    $stmt = $pdo->prepare("INSERT INTO playlists (ecran_id, media_id, position) VALUES (?, ?, ?)");
    $stmt->execute([$ecran_id, $_POST['media_id'], $position]);
    $message = "✅ Média ajouté à la playlist !";
}

// Récupérer la playlist de l'écran sélectionné
$playlist = [];
if ($ecran_id) {
    $stmt = $pdo->prepare("SELECT p.id, p.position, m.type, m.chemin, m.contenu FROM playlists p JOIN media m ON p.media_id = m.id WHERE p.ecran_id = ? ORDER BY p.position");
    $stmt->execute([$ecran_id]);
    $playlist = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des playlists - OpenDisplay</title>
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
            max-width: 600px;
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
        .message {
            background: #e6ffe6;
            color: #217a35;
            border: 1px solid #b2e6b2;
            padding: 10px 18px;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 1.1em;
        }
        form {
            margin-bottom: 25px;
        }
        select, input[type="file"], input[type="number"], textarea, input[type="text"] {
            padding: 8px 12px;
            border: 1px solid #c0c8d6;
            border-radius: 6px;
            font-size: 1em;
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 10px;
        }
        button, .action-btn, .preview-btn {
            padding: 8px 18px;
            background: linear-gradient(90deg, #1e3c72 60%, #2a5298 100%);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-block;
            margin-left: 5px;
        }
        button:hover, .action-btn:hover, .preview-btn:hover {
            background: linear-gradient(90deg, #2a5298 60%, #1e3c72 100%);
        }
        .playlist-list {
            margin-top: 20px;
        }
        .playlist-item {
            background: #f4f6fa;
            border: 1px solid #e0e4ed;
            border-radius: 6px;
            margin-bottom: 12px;
            padding: 12px 18px;
            font-size: 1.07em;
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: space-between;
        }
        .media-type {
            background: #1e3c72;
            color: #fff;
            border-radius: 4px;
            padding: 2px 8px;
            font-size: 0.95em;
            margin-right: 10px;
        }
        .actions {
            display: flex;
            gap: 7px;
        }
        .playlist-controls {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        label {
            font-weight: bold;
            color: #1e3c72;
        }
        .preview-section {
            margin: 35px 0 0 0;
            text-align: center;
        }
        iframe {
            border: 2px solid #1e3c72;
            border-radius: 12px;
            width: 100%;
            min-height: 400px;
            background: #222;
            margin-top: 15px;
        }
    </style>
    <script>
    function confirmDelete() {
        return confirm('Retirer ce média de la playlist ?');
    }
    </script>
</head>
<body>
    <h1>Gestion des playlists</h1>
    <div class="container">
        <a class="back-link" href="index.php">← Retour au tableau de bord</a>
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($is_admin): ?>
            <form method="get">
                <label>Choisir un écran :
                    <select name="ecran_id" onchange="this.form.submit()">
                        <option value="">-- Sélectionner --</option>
                        <?php foreach ($ecrans as $e): ?>
                            <option value="<?= $e['id'] ?>" <?= $ecran_id == $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>
        <?php else: ?>
            <div style="margin-bottom:20px;">
                <b>Écran associé :</b> <?= htmlspecialchars($ecrans[0]['nom']) ?>
            </div>
        <?php endif; ?>

        <?php if ($ecran_id): ?>
            <h2 style="color:#1e3c72; font-size:1.2em; margin: 20px 0 12px 0;">Ajouter un média à la playlist</h2>
            <form method="post">
                <select name="media_id" required>
                    <?php foreach ($medias as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= $m['type'] ?> - <?= htmlspecialchars($m['chemin'] ?: mb_strimwidth($m['contenu'], 0, 30, '...')) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Ajouter</button>
            </form>
            <h2 style="color:#1e3c72; font-size:1.2em; margin: 20px 0 12px 0;">Playlist actuelle</h2>
            <div class="playlist-list">
                <?php foreach ($playlist as $i => $p): ?>
                    <div class="playlist-item">
                        <span>
                            <span class="media-type"><?= htmlspecialchars($p['type']) ?></span>
                            <?= htmlspecialchars($p['chemin'] ?: mb_strimwidth($p['contenu'], 0, 40, '...')) ?>
                        </span>
                        <span class="playlist-controls">
                            <a href="playlists.php?<?= $is_admin ? 'ecran_id='.$ecran_id.'&' : '' ?>move=up&pid=<?= $p['id'] ?>" class="action-btn" title="Monter" <?= $i == 0 ? 'style="opacity:0.4;pointer-events:none;"' : '' ?>>⬆️</a>
                            <a href="playlists.php?<?= $is_admin ? 'ecran_id='.$ecran_id.'&' : '' ?>move=down&pid=<?= $p['id'] ?>" class="action-btn" title="Descendre" <?= $i == count($playlist)-1 ? 'style="opacity:0.4;pointer-events:none;"' : '' ?>>⬇️</a>
                            <a href="playlists.php?<?= $is_admin ? 'ecran_id='.$ecran_id.'&' : '' ?>delete=<?= $p['id'] ?>" class="action-btn" style="background:#d9534f;" title="Retirer"
                               onclick="return confirmDelete();">🗑️</a>
                        </span>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($playlist)): ?>
                    <div style="color:#888;">Aucun média dans cette playlist.</div>
                <?php endif; ?>
            </div>
            <div class="preview-section">
                <h2 style="color:#1e3c72; font-size:1.2em;">Aperçu de la playlist</h2>
                <iframe src="../display/screen.php?ecran=<?= $ecran_id ?>" allowfullscreen></iframe>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
