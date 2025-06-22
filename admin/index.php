<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord OpenDisplay</title>
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
        .dashboard {
            max-width: 500px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px #0001;
            padding: 30px 40px;
        }
        .dashboard ul {
            list-style: none;
            padding: 0;
        }
        .dashboard li {
            margin: 20px 0;
        }
        .dashboard a {
            text-decoration: none;
            color: #1e3c72;
            font-size: 1.2em;
            font-weight: bold;
            transition: color 0.2s;
        }
        .dashboard a:hover {
            color: #2a5298;
        }
        .logout-link {
            display: block;
            margin: 30px auto 0 auto;
            text-align: center;
            color: #d9534f;
            font-size: 1em;
            text-decoration: none;
        }
        .logout-link:hover {
            color: #b52b27;
        }
        .opensource-info {
            margin-top: 40px;
            text-align: center;
            color: #888;
            font-size: 1em;
        }
        .opensource-info a {
            color: #2a5298;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Tableau de bord OpenDisplay</h1>
    <div class="dashboard">
        <ul>
            <li><a href="media.php">📁 Gérer les médias</a></li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li><a href="ecrans.php">🖥️ Gérer les écrans</a></li>
                <li><a href="users.php">👤 Gérer les utilisateurs</a></li>
                <li><a href="logs.php">📝 Voir les logs</a></li>
            <?php endif; ?>
            <li><a href="playlists.php">🎬 Gérer les playlists</a></li>
        </ul>
        <a class="logout-link" href="logout.php">Se déconnecter</a>

        <div class="opensource-info">
            <hr style="margin-bottom:18px;">
            Ce projet est <strong>open source</strong>.<br>
            Retrouvez le code sur 
            <a href="https://github.com/eddu22570" target="_blank">
                GitHub (@eddu22570)
            </a>
            ou participez à son évolution !
        </div>
    </div>
</body>
</html>
