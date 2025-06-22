<?php
require_once '../includes/db.php';
$pdo = getPDO();

// Récupération de l'ID de l'écran depuis l'URL (par défaut 1)
$ecran_id = (isset($_GET['id']) && is_numeric($_GET['id'])) ? intval($_GET['id']) : 1;

// Récupérer la playlist de cet écran (ordre défini dans la table playlists)
$stmt = $pdo->prepare("SELECT m.* FROM playlists p JOIN media m ON p.media_id = m.id WHERE p.ecran_id = ? ORDER BY p.position ASC");
$stmt->execute([$ecran_id]);
$medias = $stmt->fetchAll();

// Récupérer la commune pour affichage et météo
$stmt = $pdo->prepare("SELECT commune FROM ecrans WHERE id = ?");
$stmt->execute([$ecran_id]);
$row = $stmt->fetch();
$commune = $row && !empty($row['commune']) ? $row['commune'] : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Affichage dynamique - OpenDisplay</title>
    <meta http-equiv="refresh" content="600">
    <style>
        body {
            margin: 0;
            background: linear-gradient(120deg, #1e3c72 0%, #2a5298 100%);
            color: #fff;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        #header {
            background: #222;
            color: #fff;
            padding: 10px 30px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            font-size: 1.5em;
            height: 60px;
        }
        #media {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 80vh;
            transition: opacity 1s;
            opacity: 1;
        }
        #media.fade-out {
            opacity: 0;
        }
        img, video {
            max-width: 90vw;
            max-height: 80vh;
            border-radius: 10px;
            box-shadow: 0 4px 40px #0008;
        }
        .texte {
            font-size: 3em;
            text-align: center;
            padding: 1em 2em;
            background: rgba(34,34,34,0.7);
            border-radius: 10px;
            box-shadow: 0 4px 40px #0008;
        }
        #footer {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: rgba(34,34,34,0.85);
            color: #fff;
            padding: 10px 30px;
            display: flex;
            justify-content: space-between;
            font-size: 1.2em;
            height: 50px;
            align-items: center;
        }
        #footer span#commune-footer {
            font-weight: bold;
            font-size: 1.1em;
            color: #ffd700;
            margin-left: 30px;
        }
    </style>
</head>
<body>
    <div id="header">
        <span id="clock"></span>
    </div>
    <div id="media"></div>
    <div id="footer">
        <span id="weather">Chargement météo...</span>
        <?php if ($commune): ?>
            <span id="commune-footer"><?= htmlspecialchars($commune) ?></span>
        <?php endif; ?>
        <span id="date"></span>
    </div>
    <script>
    // ---------- Horloge ----------
    function updateClock() {
        document.getElementById('clock').textContent =
            new Date().toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit', second:'2-digit'});
    }
    setInterval(updateClock, 1000);
    updateClock();

    // ---------- Date ----------
    function updateDate() {
        document.getElementById('date').textContent =
            new Date().toLocaleDateString('fr-FR', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'});
    }
    setInterval(updateDate, 60000);
    updateDate();

    // ---------- Affichage dynamique des médias ----------
    const medias = <?php echo json_encode($medias); ?>;
    let index = 0;

    function showMedia() {
        if (medias.length === 0) {
            document.getElementById('media').innerHTML = "<p>Aucun média à afficher.</p>";
            return;
        }
        const m = medias[index];
        const mediaDiv = document.getElementById('media');
        mediaDiv.classList.add('fade-out');
        setTimeout(() => {
            let html = '';
            if (m.type === 'image') {
                html = `<img src="../media_uploads/${m.chemin}" alt="Image">`;
            } else if (m.type === 'video') {
                html = `<video src="../media_uploads/${m.chemin}" autoplay muted loop></video>`;
            } else if (m.type === 'texte') {
                html = `<div class="texte">${m.contenu.replace(/\n/g, '<br>')}</div>`;
            }
            mediaDiv.innerHTML = html;
            mediaDiv.classList.remove('fade-out');
        }, 1000);

        const duree = m.duree_affichage ? parseInt(m.duree_affichage) : 10;
        setTimeout(() => {
            index = (index + 1) % medias.length;
            showMedia();
        }, duree * 1000);
    }
    showMedia();

    // ---------- Météo (API Open-Meteo via PHP) ----------
    function updateWeather() {
        <?php if ($commune): ?>
        fetch(`../includes/weather_api.php?commune=<?=urlencode($commune)?>`)
            .then(r => r.json())
            .then(data => {
                if (data && data.temperature !== undefined && data.temperature !== null) {
                    let icon = "";
                    if (data.weathercode !== undefined) {
                        if (data.weathercode < 3) icon = "☀️";
                        else if (data.weathercode < 45) icon = "⛅";
                        else if (data.weathercode < 60) icon = "🌧️";
                        else icon = "🌩️";
                    }
                    document.getElementById('weather').innerHTML = `${icon} ${data.temperature}°C`;
                } else {
                    document.getElementById('weather').innerHTML = "Météo indisponible";
                }
            })
            .catch(() => {
                document.getElementById('weather').innerHTML = "Météo indisponible";
            });
        <?php else: ?>
        document.getElementById('weather').innerHTML = "Commune non renseignée";
        <?php endif; ?>
    }
    updateWeather();
    setInterval(updateWeather, 600000); // toutes les 10 minutes
    </script>
</body>
</html>
