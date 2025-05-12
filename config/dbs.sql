-- Create database
CREATE DATABASE IF NOT EXISTS school_exams_dbs;
USE school_exams_dbs;

-- Table: users
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'teacher', 'etudiant') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Insert admin user (password: admin123)
INSERT INTO users (name, email, password, role, created_at) VALUES
('Admin Principal', 'admin@gmail.com', '$2a$12$VVtCzht/WSUcyU5mvzrvX.s/kx0wmwI95SU2Ziz7aCoaSdaMWvdMq', 'admin', NOW());

-- Insert teacher user (password: iman123)
INSERT INTO users (name, email, password, role, created_at) VALUES
('Iman', 'iman@gmail.com', '$2a$12$Nyoc9F380HpAF4rvMgvH4uWxz9wk70ki0iJe3hLCSkXJ.DcKdVIkm', 'teacher', NOW());

-- Table: filiere
CREATE TABLE IF NOT EXISTS filiere (
  id_filiere INT AUTO_INCREMENT PRIMARY KEY,
  filiere VARCHAR(100) NOT NULL
);

INSERT INTO filiere (filiere) VALUES
('Développement Digital'),
('UI/UX'),
('Infrastructure Digital'),
('Intelligence Artificielle');

-- Table: classes
CREATE TABLE IF NOT EXISTS classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(50) NOT NULL UNIQUE,
  niveau ENUM('1er année', '2ème année') NOT NULL,
  filiere_id INT,
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (filiere_id) REFERENCES filiere(id_filiere) ON DELETE SET NULL
);

INSERT INTO classes (nom, niveau, filiere_id) VALUES
('DEV101', '1er année', 1),
('DEV102', '1er année', 1),
('DEV103', '1er année', 1),
('DEV104', '1er année', 1),
('DEV105', '1er année', 1),
('DEV106', '1er année', 1),
('UIUX101', '1er année', 2),
('UIUX102', '1er année', 2),
('ID101', '1er année', 3),
('ID102', '1er année', 3),
('IA101', '1er année', 4);

-- Table: matieres
CREATE TABLE IF NOT EXISTS matieres (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(50) NOT NULL UNIQUE,
  coefficient INT DEFAULT 1,
  is_common BOOLEAN DEFAULT FALSE,
  filiere_id INT DEFAULT NULL,
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (filiere_id) REFERENCES filiere(id_filiere) ON DELETE SET NULL
);

-- Common subjects
INSERT INTO matieres (nom, coefficient, is_common) VALUES
('Français', 2, TRUE),
('Anglais', 2, TRUE),
('Arabic', 1, TRUE);

-- Dev Digital subjects
INSERT INTO matieres (nom, coefficient, is_common, filiere_id) VALUES
('Développement Site Web Statique', 3, FALSE, 1),
('Algorithmes et Structures de Données', 2, FALSE, 1),
('Programmation Orientée Objet', 2, FALSE, 1),
('Bases de Données', 2, FALSE, 1);

-- UI/UX subjects
INSERT INTO matieres (nom, coefficient, is_common, filiere_id) VALUES
('Design d\'Interface', 3, FALSE, 2),
('Expérience Utilisateur', 3, FALSE, 2),
('Design Graphique', 2, FALSE, 2);

-- Infrastructure Digital subjects
INSERT INTO matieres (nom, coefficient, is_common, filiere_id) VALUES
('Réseaux', 2, FALSE, 3),
('Systèmes d\'Exploitation', 2, FALSE, 3),
('Sécurité Informatique', 3, FALSE, 3);

-- AI subjects
INSERT INTO matieres (nom, coefficient, is_common, filiere_id) VALUES
('Machine Learning', 3, FALSE, 4),
('Statistiques', 2, FALSE, 4),
('Data Mining', 2, FALSE, 4);

-- Table: etudiants
CREATE TABLE IF NOT EXISTS etudiants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  nom_complet VARCHAR(100) NOT NULL,
  date_naissance DATE DEFAULT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  classe_id INT NOT NULL,
  filiere_id INT NOT NULL,
  is_validated BOOLEAN DEFAULT FALSE,
  status ENUM('pending', 'validated', 'rejected') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (classe_id) REFERENCES classes(id) ON DELETE CASCADE,
  FOREIGN KEY (filiere_id) REFERENCES filiere(id_filiere)
);

-- Table: matiere_filiere
CREATE TABLE IF NOT EXISTS matiere_filiere (
  id INT AUTO_INCREMENT PRIMARY KEY,
  matiere_id INT NOT NULL,
  filiere_id INT,
  FOREIGN KEY (matiere_id) REFERENCES matieres(id) ON DELETE CASCADE,
  FOREIGN KEY (filiere_id) REFERENCES filiere(id_filiere) ON DELETE CASCADE,
  UNIQUE KEY unique_matiere_filiere (matiere_id, filiere_id)
);

-- Table: examens
CREATE TABLE IF NOT EXISTS examens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  exam_title VARCHAR(100) NOT NULL,
  matiere_id INT NOT NULL,
  classe_id INT NOT NULL,
  date_examen DATE NOT NULL,
  start_time TIME DEFAULT NULL,
  end_time TIME DEFAULT NULL,
  type_examen ENUM('CC', 'EFM') DEFAULT 'CC',
  bareme INT DEFAULT 20 CHECK (bareme IN (20, 40)),
  description TEXT,
  created_by INT NOT NULL,
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (matiere_id) REFERENCES matieres(id) ON DELETE CASCADE,
  FOREIGN KEY (classe_id) REFERENCES classes(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert example exams
INSERT INTO examens (exam_title, matiere_id, classe_id, date_examen, start_time, end_time, type_examen, bareme, description, created_by) 
VALUES 
('Contrôle de Français - CC', 1, 4, DATE_ADD(CURDATE(), INTERVAL 3 DAY),'10:00:00', '11:00:00', 'CC', 20, 'CC sur la grammaire', 2),
('EFM Développement Web', 4, 4, DATE_SUB(CURDATE(), INTERVAL 10 DAY),'14:00:00', '16:30:00', 'EFM', 40,'Évaluation finale module Web', 2),
('EFM Algorithmes', 5, 4, DATE_ADD(CURDATE(), INTERVAL 10 DAY),'14:00:00', '16:30:00', 'EFM', 40,'Évaluation finale module Algorithmes', 2),
('EFM POO', 6, 4, DATE_ADD(CURDATE(), INTERVAL 10 DAY),'14:00:00', '16:30:00', 'EFM', 40,'Évaluation finale module POO', 2),
('EFM Bases de Données', 7, 4, DATE_ADD(CURDATE(), INTERVAL 10 DAY),'14:00:00', '16:30:00', 'EFM', 40,'Évaluation finale module Bases de Données', 2),
('Test en Français', 1, 6, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '08:30:00', '09:15:00', 'CC', 20, 'Contrôle sur les verbes irréguliers', 2),
('Test en Anglais', 2, 6, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '08:30:00', '09:15:00', 'CC', 20, 'Contrôle sur les fonctions', 2);

-- Table: notes
CREATE TABLE IF NOT EXISTS notes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  etudiant_id INT NOT NULL,
  examen_id INT NOT NULL,
  classe_id INT NOT NULL,
  note DECIMAL(4,2) NOT NULL,
  commentaire TEXT,
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY eleve_examen (etudiant_id, examen_id),
  FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,
  FOREIGN KEY (examen_id) REFERENCES examens(id) ON DELETE CASCADE,
  FOREIGN KEY (classe_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- 1. Associate common subjects to all filières
INSERT INTO matiere_filiere (matiere_id, filiere_id)
SELECT m.id, f.id_filiere
FROM matieres m
JOIN filiere f
WHERE m.is_common = TRUE;

-- 2. Link filière-specific subjects
INSERT INTO matiere_filiere (matiere_id, filiere_id)
SELECT id, filiere_id FROM matieres WHERE filiere_id IS NOT NULL;
