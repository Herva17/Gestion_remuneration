-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 21 juin 2026 à 21:45
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion_remuneration`
--

-- --------------------------------------------------------

--
-- Structure de la table `affectation`
--

CREATE TABLE `affectation` (
  `id` int(11) NOT NULL,
  `id_agent` int(11) NOT NULL,
  `id_service` int(11) NOT NULL,
  `lieu_affectation` varchar(150) DEFAULT NULL,
  `date_affectation` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `affectation`
--

INSERT INTO `affectation` (`id`, `id_agent`, `id_service`, `lieu_affectation`, `date_affectation`) VALUES
(1, 1, 1, 'Direction', '2026-06-20');

-- --------------------------------------------------------

--
-- Structure de la table `agent`
--

CREATE TABLE `agent` (
  `id_agent` int(11) NOT NULL,
  `nom_complet` varchar(150) NOT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `profil` varchar(100) DEFAULT NULL,
  `lieu_naissance` varchar(100) DEFAULT NULL,
  `fonction` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `agent`
--

INSERT INTO `agent` (`id_agent`, `nom_complet`, `adresse`, `date_naissance`, `telephone`, `profil`, `lieu_naissance`, `fonction`) VALUES
(1, 'Aggée BUSIME MUHINDO', 'Katoyi', '2004-06-20', '0826487074', 'Enseignant', 'Goma', 'Enseignant');

-- --------------------------------------------------------

--
-- Structure de la table `annee_scolaire`
--

CREATE TABLE `annee_scolaire` (
  `id` int(11) NOT NULL,
  `designation_ann` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `annee_scolaire`
--

INSERT INTO `annee_scolaire` (`id`, `designation_ann`) VALUES
(1, '2025-2026'),
(2, '2024-2025'),
(3, '2026-2027');

-- --------------------------------------------------------

--
-- Structure de la table `avantages`
--

CREATE TABLE `avantages` (
  `id` int(11) NOT NULL,
  `id_agent` int(11) NOT NULL,
  `libelle` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `type_avantage` enum('transport','communication','logement','prime','bonus','autre') DEFAULT 'autre',
  `est_recurrent` tinyint(1) DEFAULT 1,
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `id_annee` int(11) NOT NULL,
  `montant` decimal(12,2) NOT NULL,
  `date_avantage` date DEFAULT NULL,
  `mois` varchar(20) DEFAULT NULL,
  `annee` varchar(10) DEFAULT NULL,
  `statut` enum('actif','inactif','en_attente') DEFAULT 'actif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `avantages`
--

INSERT INTO `avantages` (`id`, `id_agent`, `libelle`, `description`, `type_avantage`, `est_recurrent`, `date_debut`, `date_fin`, `id_annee`, `montant`, `date_avantage`, `mois`, `annee`, `statut`, `created_at`, `updated_at`) VALUES
(2, 1, 'Prime transport', 'jkjslkjksjksjskjls', 'transport', 1, '2026-06-01', '2026-06-30', 2, 10.00, '2026-06-21', 'Juillet', '2026', 'actif', '2026-06-21 18:46:22', '2026-06-21 18:46:22');

-- --------------------------------------------------------

--
-- Structure de la table `remuneration`
--

CREATE TABLE `remuneration` (
  `id` int(11) NOT NULL,
  `id_agent` int(11) NOT NULL,
  `montant` decimal(12,2) NOT NULL,
  `date_remun` date DEFAULT NULL,
  `mois` varchar(20) DEFAULT NULL,
  `annee` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `remuneration`
--

INSERT INTO `remuneration` (`id`, `id_agent`, `montant`, `date_remun`, `mois`, `annee`) VALUES
(2, 1, 100.00, '2026-06-21', 'Janvier', '2026');

-- --------------------------------------------------------

--
-- Structure de la table `retenue`
--

CREATE TABLE `retenue` (
  `id` int(11) NOT NULL,
  `id_agent` int(11) NOT NULL,
  `libelle` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `type_retenue` enum('impot','assurance','cotisation','avance','penalite','autre') DEFAULT 'autre',
  `est_recurrent` tinyint(1) DEFAULT 1,
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `montant` decimal(12,2) NOT NULL,
  `date_retenue` date DEFAULT NULL,
  `mois` varchar(20) DEFAULT NULL,
  `annee` varchar(10) DEFAULT NULL,
  `statut` enum('actif','inactif','en_attente') DEFAULT 'actif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `retenue`
--

INSERT INTO `retenue` (`id`, `id_agent`, `libelle`, `description`, `type_retenue`, `est_recurrent`, `date_debut`, `date_fin`, `montant`, `date_retenue`, `mois`, `annee`, `statut`, `created_at`, `updated_at`) VALUES
(2, 1, 'Assurance', 'Pour assurance', 'assurance', 1, '2026-06-01', '2026-06-30', 21.00, '2026-06-21', 'Mars', '2026', 'actif', '2026-06-21 18:26:30', '2026-06-21 18:27:13');

-- --------------------------------------------------------

--
-- Structure de la table `service`
--

CREATE TABLE `service` (
  `id` int(11) NOT NULL,
  `designation` varchar(150) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `service`
--

INSERT INTO `service` (`id`, `designation`, `description`) VALUES
(1, 'Comptabilite', 'Comptable');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mot_de_passe` varchar(255) DEFAULT NULL,
  `role` enum('Administrateur','Comptable','Secretaire') NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`id`, `nom`, `email`, `mot_de_passe`, `role`, `date_creation`) VALUES
(1, 'SALEH', 'saleh@gmail.com', '$2y$10$Ov0pHQSn/oMLPv5GHjxfTeF5RwWEXih9jOQsz.lS.rRnM5aHNySIe', 'Administrateur', '2026-06-20 20:16:52');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `affectation`
--
ALTER TABLE `affectation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_agent` (`id_agent`),
  ADD KEY `id_service` (`id_service`);

--
-- Index pour la table `agent`
--
ALTER TABLE `agent`
  ADD PRIMARY KEY (`id_agent`);

--
-- Index pour la table `annee_scolaire`
--
ALTER TABLE `annee_scolaire`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `avantages`
--
ALTER TABLE `avantages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_annee` (`id_annee`),
  ADD KEY `idx_agent_annee` (`id_agent`,`id_annee`),
  ADD KEY `idx_type` (`type_avantage`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_mois_annee` (`mois`,`annee`);

--
-- Index pour la table `remuneration`
--
ALTER TABLE `remuneration`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_agent` (`id_agent`);

--
-- Index pour la table `retenue`
--
ALTER TABLE `retenue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_agent` (`id_agent`);

--
-- Index pour la table `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `affectation`
--
ALTER TABLE `affectation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `agent`
--
ALTER TABLE `agent`
  MODIFY `id_agent` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `annee_scolaire`
--
ALTER TABLE `annee_scolaire`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `avantages`
--
ALTER TABLE `avantages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `remuneration`
--
ALTER TABLE `remuneration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `retenue`
--
ALTER TABLE `retenue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `service`
--
ALTER TABLE `service`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `affectation`
--
ALTER TABLE `affectation`
  ADD CONSTRAINT `affectation_ibfk_1` FOREIGN KEY (`id_agent`) REFERENCES `agent` (`id_agent`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `affectation_ibfk_2` FOREIGN KEY (`id_service`) REFERENCES `service` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `avantages`
--
ALTER TABLE `avantages`
  ADD CONSTRAINT `avantages_ibfk_1` FOREIGN KEY (`id_agent`) REFERENCES `agent` (`id_agent`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `avantages_ibfk_2` FOREIGN KEY (`id_annee`) REFERENCES `annee_scolaire` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `remuneration`
--
ALTER TABLE `remuneration`
  ADD CONSTRAINT `remuneration_ibfk_1` FOREIGN KEY (`id_agent`) REFERENCES `agent` (`id_agent`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `retenue`
--
ALTER TABLE `retenue`
  ADD CONSTRAINT `retenue_ibfk_1` FOREIGN KEY (`id_agent`) REFERENCES `agent` (`id_agent`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
