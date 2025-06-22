<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db.php';
$pdo = getPDO();

$message = '';

// Suppression d'un média (avec suppression du fichier physique si nécessaire)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    // 1. Récupérer le média pour connaître le chemin du fichier
    $stmt = $pdo->prepare("SELECT type, chemin FROM media WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $media = $stmt->fetch();

    // 2. Supprimer le fichier du serveur si ce n'est pas un texte
    if ($media && $media['type'] !== 'texte' && !empty($media['chemin'])) {
        $filepath = "../media_uploads/" . $media['chemin'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    // 3. Supprimer l'entrée dans la base
    $stmt = $pdo->prepare("DELETE FROM media WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $message = "🗑️ Média supprimé.";
}

// Modification d'un média
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = intval($_POST['edit_id']);
    $type = $_POST['media_type'];
    $duree = intval($_POST['duration']);
    $chemin = '';
    $contenu = '';

    if ($type === 'texte') {
        $contenu = $_POST['texte'];
    } else {
        // Si un nouveau fichier est uploadé, on le prend, sinon on garde l'ancien chemin
        if (!empty($_FILES['media_file']['name'])) {
            $fichier = $_FILES['media_file'];
            $chemin = basename($fichier['name']);
            move_uploaded_file($fichier['tmp_name'], "../media_uploads/" . $chemin);

            // Supprimer l'ancien fichier si différent
            if (!empty($_POST['old_chemin']) && $_POST['old_chemin'] !== $chemin) {
                $old_filepath = "../media_uploads/" . $_POST['old_chemin'];
                if (file_exists($old_filepath)) {
                    unlink($old_filepath);
                }
            }
        } else {
            $chemin = $_POST['old_chemin'];
        }
    }

    $stmt = $pdo->prepare("UPDATE media SET type=?, chemin=?, contenu=?, duree_affichage=? WHERE id=?");
    $stmt->execute([$type, $chemin, $contenu, $duree, $id]);
    $message = "✏️ Média modifié.";
}

// Ajout d'un média
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['edit_id'])) {
    $type = $_POST['media_type'];
    $duree = intval($_POST['duration']);
    $chemin = '';
    $contenu = '';

    if ($type === 'texte') {
        $contenu = $_POST['texte'];
    } else {
        $fichier = $_FILES['media_file'];
        $chemin = basename($fichier['name']);
        move_uploaded_file($fichier['tmp_name'], "../media_uploads/" . $chemin);
    }

    $stmt = $pdo->prepare("INSERT INTO media (type, chemin, contenu, duree_affichage) VALUES (?, ?, ?, ?)");
    $stmt->execute([$type, $chemin, $contenu, $duree]);
    $message = "✅ Média ajouté avec succès !";
}

// Liste des médias
$medias = $pdo->query("SELECT * FROM media ORDER BY id DESC")->fetchAll();
$edit_media = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM media WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_media = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des médias - OpenDisplay</title>
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
            max-width: 550px;
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
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 25px;
        }
        select, input[type="file"], input[type="number"], textarea, input[type="text"] {
            padding: 8px 12px;
            border: 1px solid #c0c8d6;
            border-radius: 6px;
            font-size: 1em;
            width: 100%;
            box-sizing: border-box;
        }
        button, .action-btn {
            padding: 8px 18px;
            background: linear-gradient(90deg, #1e3c72 60%, #2a5298 100%);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.2s;
            width: fit-content;
            align-self: flex-end;
            text-decoration: none;
            display: inline-block;
            margin-left: 5px;
        }
        button:hover, .action-btn:hover {
            background: linear-gradient(90deg, #2a5298 60%, #1e3c72 100%);
        }
        .media-list {
            margin-top: 30px;
        }
        .media-item {
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
        }
        .actions {
            display: flex;
            gap: 7px;
        }
    </style>
    <script>
    function toggleFields(type) {
        var t = type || document.getElementById('media_type').value;
        document.getElementById('file_input').style.display = (t === 'texte') ? 'none' : 'block';
        document.getElementById('text_input').style.display = (t === 'texte') ? 'block' : 'none';
    }
    function confirmDelete() {
        return confirm('Supprimer ce média ? Cette action est irréversible.');
    }
    </script>
</head>
<body>
    <h1>Gestion des médias</h1>
    <div class="container">
        <a class="back-link" href="index.php">← Retour au tableau de bord</a>
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($edit_media): ?>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="edit_id" value="<?= $edit_media['id'] ?>">
                <select name="media_type" id="media_type" onchange="toggleFields()" required>
                    <option value="image" <?= $edit_media['type']=='image'?'selected':'' ?>>Image</option>
                    <option value="video" <?= $edit_media['type']=='video'?'selected':'' ?>>Vidéo</option>
                    <option value="texte" <?= $edit_media['type']=='texte'?'selected':'' ?>>Texte</option>
                </select>
                <div id="file_input">
                    <input type="file" name="media_file">
                    <?php if ($edit_media['type'] !== 'texte' && $edit_media['chemin']): ?>
                        <small>Fichier actuel : <?= htmlspecialchars($edit_media['chemin']) ?></small>
                        <input type="hidden" name="old_chemin" value="<?= htmlspecialchars($edit_media['chemin']) ?>">
                    <?php endif; ?>
                </div>
                <div id="text_input" style="display:none;">
                    <textarea name="texte" rows="3" placeholder="Votre texte ici..."><?= htmlspecialchars($edit_media['contenu']) ?></textarea>
                </div>
                <input type="number" name="duration" placeholder="Durée (sec)" value="<?= intval($edit_media['duree_affichage']) ?>">
                <button type="submit">Enregistrer</button>
                <a href="media.php" class="action-btn" style="background:#aaa;">Annuler</a>
            </form>
            <script>toggleFields('<?= $edit_media['type'] ?>');</script>
        <?php else: ?>
            <form method="post" enctype="multipart/form-data">
                <select name="media_type" id="media_type" onchange="toggleFields()" required>
                    <option value="image">Image</option>
                    <option value="video">Vidéo</option>
                    <option value="texte">Texte</option>
                </select>
                <div id="file_input">
                    <input type="file" name="media_file">
                </div>
                <div id="text_input" style="display:none;">
                    <textarea name="texte" rows="3" placeholder="Votre texte ici..."></textarea>
                </div>
                <input type="number" name="duration" placeholder="Durée (sec)">
                <button type="submit">Ajouter</button>
            </form>
            <script>toggleFields();</script>
        <?php endif; ?>

        <div class="media-list">
            <h2 style="color:#1e3c72; font-size:1.2em; margin: 20px 0 12px 0;">Liste des médias</h2>
            <?php foreach ($medias as $m): ?>
                <div class="media-item">
                    <span>
                        <span class="media-type"><?= htmlspecialchars($m['type']) ?></span>
                        <?php
                        if ($m['type'] === 'texte') {
                            echo htmlspecialchars(mb_strimwidth($m['contenu'], 0, 40, '...'));
                        } else {
                            echo htmlspecialchars($m['chemin']);
                        }
                        ?>
                        <span style="color:#888; font-size:0.95em;">Durée : <?= intval($m['duree_affichage']) ?>s</span>
                    </span>
                    <span class="actions">
                        <a href="media.php?edit=<?= $m['id'] ?>" class="action-btn" title="Modifier">✏️</a>
                        <a href="media.php?delete=<?= $m['id'] ?>" class="action-btn" style="background:#d9534f;" title="Supprimer"
                           onclick="return confirmDelete();">🗑️</a>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
