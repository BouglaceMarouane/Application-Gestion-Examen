## Rapport du Projet : Système de Gestion des Examens

---

**Vue d'ensemble du projet**
  Le Système de Gestion des Examens est une application web complète conçue pour simplifier la gestion des examens académiques, des dossiers des étudiants et du suivi des notes. L'application offre des interfaces dédiées aux administrateurs et aux enseignants, permettant une gestion efficace des processus liés aux examens.

---

**Fonctionnalités principales**
  - *Gestion des utilisateurs :*
    - Inscription, connexion et contrôle d'accès basé sur les rôles (administrateur, enseignant, étudiant).
  - *Gestion des étudiants :*
    - Ajout, modification et suivi des informations des étudiants.
  - *Gestion des classes :*
    - Organisation des étudiants en classes et filières académiques.
  - *Gestion des matières :*
    - Définition des matières avec coefficients et affectation à des filières spécifiques.
  - *Gestion des examens :*
    - Création, planification et gestion des différents types d'examens.
  - *Gestion des notes :*
    - Enregistrement et calcul des notes des étudiants.
  - *Système de reporting :*
    - Génération de rapports individuels pour les étudiants.
  - *Génération de PDF :*
    - Exportation des bulletins de notes des étudiants au format PDF.

---

**Technologies utilisées**
  - *Backend* : PHP 7.4+
  - *Base de données* : MySQL
  - *Frontend* : HTML5, CSS3, JavaScript
  - *Framework CSS* : Bootstrap 5
  - *Bibliothèques JavaScript* :
    - jQuery pour la manipulation du DOM
    - DataTables pour les tableaux interactifs
  - *Génération de PDF* : Bibliothèque TCPDF
  - *Icônes* : Font Awesome et Bootstrap Icons

---

**Structure du projet**
  - */admin* : Fichiers de l'interface administrateur.
  - */assets* : Fichiers CSS et Images.
  - */config* : Connexion à la base de données et configuration.
  - */includes*: Fonctions PHP partagées et utilitaires.
  - */vendor* : Bibliothèques tierces (TCPDF).
  - *index.php* : Home page.
  - *generate_pdf.php* : It ensures that only authorized users can generate a report card for a specific student.

---

**Défis rencontrés**

  *1. Conception de la base de données*
  Créer un schéma de base de données flexible pour gérer différents types d'examens, systèmes de notation et structures académiques a été un défi. Nous avons mis en œuvre un design relationnel qui maintient les relations entre les étudiants, les classes, les matières et les examens tout en permettant une expansion future.

  *2. Authentification des utilisateurs*
  Mettre en place une authentification sécurisée avec un contrôle d'accès basé sur les rôles a nécessité une planification minutieuse. Nous avons créé un système qui gère trois rôles distincts (administrateur, enseignant, étudiant) avec des permissions appropriées pour chacun.

  *3. Génération de rapports PDF*
  Produire des rapports PDF professionnels avec une mise en page soignée, des tableaux et des graphiques a été techniquement complexe. Nous avons utilisé la bibliothèque TCPDF et créé des modèles personnalisés pour générer des bulletins de notes bien formatés.

  *4. Design responsive*
  Assurer que l'application fonctionne bien sur divers appareils et tailles d'écran a nécessité l'application des principes de design responsive. Nous avons utilisé Bootstrap 5 et des styles CSS personnalisés pour créer une interface adaptée aux mobiles.

  ---

**Fonctionnalités clés en images et extraits de code**

*1. Génération de PDF*
  - *Code* : Exemple de génération de PDF dans pdf_generator.php :
  ```
  <?php
    $pdf->SetFont('helvetica', '', 12);
    $html = '
    <table cellspacing="0" cellpadding="5" border="1">
        <tr>
            <th>Matière</th>
            <th>Coefficient</th>
            <th>Moyenne</th>
            <th>Nombre de notes</th>
        </tr>';
    foreach ($averages as $avg) {
        $html .= '<tr>
            <td>' . htmlspecialchars($avg['matiere']) . '</td>
            <td>' . $avg['coefficient'] . '</td>
            <td>' . number_format($avg['moyenne'], 2) . '/20</td>
            <td>' . $avg['nb_notes'] . '</td>
        </tr>';
    }
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, ''); 
  ```
  - *Capture d'écran* : Exemple d'un bulletin de notes généré en PDF.
<div align="center">

<table width="100%">
  <tr>
    <td align="center">
      <strong>Student Report</strong>
    </td>
  </tr>
  <tr>
    <td align="center">
      <img src="https://github.com/BouglaceMarouane/Application-Gestion-Examen/blob/7850ee5944fc002a0ef84549ab1234d9ede750cc/images/bull.png" alt="Reports" width="500"/>
    </td>
  </tr>
</table>

</div>

---

*2. Gestion des étudiants*
  - *Code* : Exemple de validation des données dans add_student.php :
  ```
  <?php
    if (empty($_POST['nom_complet']) || empty($_POST['email']) || 
        empty($_POST['date_naissance']) || empty($_POST['filiere']) || 
        empty($_POST['classe_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Tous les champs sont obligatoires']);
        exit;
    }
  ```
  - *Capture d'écran* : Interface d'ajout d'un étudiant.
<div align="center">
  <img alt="Ajout étudiant" src="https://github.com/BouglaceMarouane/Application-Gestion-Examen/blob/15475cb20bea2d3ed0e7498b511729b76f7da7a6/images/addstudent.png">
</div>

---

*3. Tableau de bord administrateur*
  - *Code* : Exemple de requête pour récupérer les examens à venir dans admin_dashboard.php :
  ```
  <?php
    $upcomingExamsStmt = $pdo->query("
        SELECT e.exam_title, e.date_examen, m.nom AS matiere, c.nom AS classe
        FROM examens e
        JOIN matieres m ON e.matiere_id = m.id
        JOIN classes c ON e.classe_id = c.id
        WHERE e.date_examen >= CURDATE()
        ORDER BY e.date_examen ASC
        LIMIT 5
    ");
  ```
  - *Capture d'écran* : Tableau de bord affichant les examens à venir.
<div align="center">
  <img alt="Ajout étudiant" src="https://github.com/BouglaceMarouane/Application-Gestion-Examen/blob/a86cc67fe034b8044fcd2b6d319def392abc3641/images/lastgrade.png">
</div>

---

**Instructions d'installation**

  1. Clonez le dépôt dans le répertoire de votre serveur web.
  2. Créez une base de données MySQL nommée *school_exams_dbs*.
  3. Importez la structure de la base de données depuis *dbs.sql*.
  4. Configurez la connexion à la base de données dans *connection.php*.
  5. Assurez-vous que votre serveur web dispose de *PHP 7.4+*.
  6. Accédez à l'URL du projet dans votre navigateur.
  7. Connectez-vous avec les identifiants administrateur par défaut :
     - *Email* : admin@gmail.com
     - *Mot de passe* : admin123

---

**Améliorations futures**
  - Notifications par email pour les plannings d'examens et les mises à jour des notes.
  - Analyse avancée pour suivre les progrès des étudiants au fil du temps.
  - Intégration avec des systèmes de gestion de l'apprentissage (LMS).
  - Application mobile pour un accès en déplacement.
  - Fonctionnalités d'import/export en masse pour les données des étudiants.

<p align="center">
  <img src="https://capsule-render.vercel.app/api?type=waving&color=gradient&height=60&section=footer"/>
</p>
