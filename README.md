# Soft manager by KAMA — `local_exammanager`

Plugin Moodle de **programmation d'examens en masse** : chargez un planning (CSV/XLSX), prévisualisez, corrigez, puis programmez en un clic les dates, durées, codes d'accès et Safe Exam Browser de dizaines de quiz.

- **Version** : V8.5 (`2026070204`)
- **Compatibilité** : Moodle 3.9+ (testé jusqu'à Moodle 5.0)
- **Type** : plugin local — s'installe dans `local/exammanager`

## Fonctionnalités

### Programmation d'examens (`/local/exammanager/index.php`)
- Import d'un planning CSV / XLSX / XLS par glisser-déposer, avec modèle Excel téléchargeable.
- Prévisualisation éditable : correction du cours, du quiz, des dates et de la durée directement dans le tableau, avec validation en direct.
- Sélection du bon quiz par liste déroulante (uniquement les quiz actifs du cours — corbeille exclue, sans doublons).
- Codes d'accès (mot de passe du quiz) et code de sortie **Safe Exam Browser** : générer, garder ou désactiver, ligne par ligne.
- **Codes partagés optionnels** : case « Autoriser les codes partagés » — si cochée, les quiz d'un même cours ouvrant à la même date/heure reçoivent les mêmes codes ; sinon chaque quiz a ses codes propres.
- **Verrou post-programmation** : une fois la programmation terminée, les résultats passent en lecture seule et toute reprogrammation est bloquée (interface et serveur) tant qu'un nouveau fichier n'est pas chargé.
- Révélation automatique de l'activité et de sa section si elles étaient cachées.
- Exports : CSV, Excel, journal d'exécution.

### Tableau de bord (`/local/exammanager/dashboard.php`)
- Examens programmés, salles utilisées, surveillants impliqués, examens du jour.
- **Questions programmées (total)** sur l'ensemble des quiz gérés par le plugin.
- **Étudiants inscrits** (rôle étudiant uniquement) dans les cours touchés par le plugin.
- **Participants moyens par test** (tentatives réelles, aperçus exclus).
- **Participants moyens par cours (%)** : moyenne, par cours, du taux d'étudiants ayant composé.
- **Durée moyenne par tentative** (tentatives terminées).
- Graphique analytique (Chart.js).

### Autres écrans
- **Planificateur d'activités** : programmation en masse d'autres types d'activités (restrictions de disponibilité).
- **Calendrier** interactif des examens, avec détection de conflits de salles et de surveillants.
- **Historique** des examens programmés (statuts : programmé, en cours, terminé, caché, anomalie).
- **Rapports** et QR codes par salle et par surveillant.
- **Sessions** d'examens.

### Accès rapide
Un lien **« Soft manager »** est ajouté à la barre de navigation principale de Moodle (hook `core\hook\navigation\primary_extend`), visible uniquement des utilisateurs ayant la capacité `local/exammanager:manage` (gestionnaires et administrateurs).

## Installation

1. Copier le dossier `exammanager` dans `local/` de votre Moodle (ou installer le zip via *Administration du site → Plugins → Installer un plugin*).
2. Passer par *Administration du site → Notifications* pour finaliser l'installation.
3. Attribuer la capacité `local/exammanager:manage` aux rôles concernés (gestionnaire par défaut).

## Format du planning

Colonnes obligatoires : `open_time`, `close_time`, `time_limit` (minutes).
Colonnes optionnelles : `course_shortname`, `quiz_name`, `teacher`, `room`, `session`, `access_code_action`, `seb_action` (`keep` / `generate` / `disable`), `generate_access_code`, `generate_seb_exit_code`, `force_new_codes`.

Formats de date acceptés : `YYYY-MM-DD HH:MM`, `DD/MM/YYYY HH:MM` et dates Excel natives.

## Données

Le plugin enregistre ses programmations dans la table `local_exammanager_codes` (une ligne par quiz) et n'altère les quiz que sur les champs programmés (dates, durée, mot de passe, réglages SEB).

---

© KAMA — distribué sous licence GPL v3, comme Moodle.
