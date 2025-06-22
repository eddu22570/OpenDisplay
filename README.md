# OpenDisplay

OpenDisplay est une solution open source d’affichage dynamique, simple à installer et à administrer, idéale pour les collectivités, écoles, associations, entreprises, etc.

---

## 🚀 Installation rapide

### 1. Cloner le projet

git clone https://github.com/eddu22570/OpenDisplay.git
cd OpenDisplay

### 2. Préparer la base de données

- Créez une base de données vide sur votre serveur MySQL/MariaDB.
- Importez le fichier SQL fourni :

mysql -u utilisateur -p nom_de_la_base < sql/database.sql

Ou utilisez **phpMyAdmin** ou **Adminer** pour importer `sql/database.sql`.

### 3. Configurer la connexion à la base

Modifiez le fichier `includes/db.php` avec vos identifiants de connexion MySQL :

<?php function getPDO() { return new PDO('mysql:host=localhost;dbname=nom_de_la_base;charset=utf8mb4', 'utilisateur', 'motdepasse'); } ?>

### 4. Créer le superadministrateur

- Rendez-vous sur le fichier :  
  `OpenDisplay/admin/create_admin.php`
- modifiez les champs $password, $role pour créer le premier compte administrateur.
- **Supprimez ou renommez le fichier `admin/create_admin.php` après création pour la sécurité.**

### 5. Connexion

- Accédez à l’interface admin via :  
  `http://votre-serveur/OpenDisplay/admin/`
- Connectez-vous avec le compte administrateur créé à l’étape précédente.

---

## 📦 Structure du projet

OpenDisplay/
├── admin/                # Interface d'administration
│   ├── create_admin.php  # Création du superadministrateur
│   ├── ecrans.php        # Gestion des écrans
│   ├── logs.php          # Journalisation des actions
│   ├── media.php         # Gestion des médias
│   ├── playlists.php     # Gestion des playlists
│   ├── users.php         # Gestion des utilisateurs
│   └── login.php         # Page de connexion
├── display/              # Affichage dynamique pour les écrans
│   ├── screen.php        
├── includes/             # Fichiers de configuration et fonctions PHP
│   ├── db.php            # Connexion à la base de données
│   ├── log.php           # Fonction de journalisation
│   └── weather_api.php   # API MétéoFrance pour affichage de la température extérieure
├── media_uploads/        # Dossier pour les fichiers médias uploadés
│   └── ...
├── sql/                  # Scripts SQL pour l'installation
│   └── database.sql      # Structure de la base de données à importer
├── README.md             # Documentation du projet
└── ...                   # Autres fichiers (index.php, .gitignore, etc.)


---

## 🛠️ Fonctionnalités principales

- Gestion des écrans, médias, playlists
- Gestion des utilisateurs (administrateurs & gestionnaires d’écran)
- Journalisation des actions sensibles (logs)
- Aperçu en temps réel des playlists
- Interface responsive et simple d’utilisation

---

## 🔒 Sécurité

- **Supprimez le fichier `admin/create_admin.php` après la création du compte admin.**
- Changez le mot de passe administrateur après la première connexion.
- Utilisez HTTPS sur votre serveur en production.
- Cachez les versions de votre serveur ainsi que celles des packages associés (apache, nginx, ...). RDV sur le site de l'éditeur pour mettre en place ces sécurités.

---

## 🤝 Contribuer

Les contributions sont les bienvenues !  
Forkez le projet, ouvrez une issue ou proposez une pull request.

---

## 📖 Licence & Open Source

Ce projet est distribué sous licence MIT.

> Ce projet est **open source**.<br>
> Retrouvez le code sur  
> [GitHub (@eddu22570)](https://github.com/eddu22570)  
> et participez à son évolution !

---

## 📧 Contact

Pour toute question ou suggestion, ouvrez une issue sur GitHub ou contactez-moi via [https://github.com/eddu22570](https://github.com/eddu22570).

---