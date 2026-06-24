-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 24, 2026 at 06:30 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gestion_remuneration`
--

-- --------------------------------------------------------

--
-- Table structure for table `affectation`
--

CREATE TABLE `affectation` (
  `id` int(11) NOT NULL,
  `id_agent` int(11) NOT NULL,
  `id_service` int(11) NOT NULL,
  `lieu_affectation` varchar(150) DEFAULT NULL,
  `montant_remunerer` float NOT NULL,
  `date_affectation` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `affectation`
--

INSERT INTO `affectation` (`id`, `id_agent`, `id_service`, `lieu_affectation`, `montant_remunerer`, `date_affectation`) VALUES
(3, 2, 2, 'Direction', 300, '2026-06-24'),
(4, 1, 4, 'Direction', 500, '2026-06-24');

-- --------------------------------------------------------

--
-- Table structure for table `agent`
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
-- Dumping data for table `agent`
--

INSERT INTO `agent` (`id_agent`, `nom_complet`, `adresse`, `date_naissance`, `telephone`, `profil`, `lieu_naissance`, `fonction`) VALUES
(1, 'Aggée BUSIME MUHINDO', 'Katoyi', '2004-06-20', '0826487074', 'Enseignant', 'Goma', 'Enseignant'),
(2, 'HULDA', 'hjhsjhjshkjs', '2002-06-22', '0977404036', 'Enseignant', 'Goma', 'Enseignant');

-- --------------------------------------------------------

--
-- Table structure for table `annee_scolaire`
--

CREATE TABLE `annee_scolaire` (
  `id` int(11) NOT NULL,
  `designation_ann` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `annee_scolaire`
--

INSERT INTO `annee_scolaire` (`id`, `designation_ann`) VALUES
(1, '2025-2026'),
(2, '2024-2025'),
(3, '2026-2027');

-- --------------------------------------------------------

--
-- Table structure for table `avances`
--

CREATE TABLE `avances` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `mois` varchar(20) NOT NULL,
  `annee` int(11) NOT NULL,
  `libelle` varchar(100) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `statut` enum('en_cours','rembourse') DEFAULT 'en_cours'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `avances`
--

INSERT INTO `avances` (`id`, `agent_id`, `mois`, `annee`, `libelle`, `montant`, `date_creation`, `statut`) VALUES
(3, 2, '06', 2026, 'Achat fourniture', 20.00, '2026-06-22 22:26:32', 'rembourse'),
(4, 2, '06', 2026, 'transport', 10.00, '2026-06-22 23:10:27', 'rembourse');

-- --------------------------------------------------------

--
-- Table structure for table `avantages`
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
-- Dumping data for table `avantages`
--

INSERT INTO `avantages` (`id`, `id_agent`, `libelle`, `description`, `type_avantage`, `est_recurrent`, `date_debut`, `date_fin`, `id_annee`, `montant`, `date_avantage`, `mois`, `annee`, `statut`, `created_at`, `updated_at`) VALUES
(5, 2, 'Prime Transport', 'GHGHJGHJGHGHJGHJ', 'transport', 1, NULL, NULL, 3, 30.00, '2026-06-22', 'Juin', '2026', 'actif', '2026-06-22 20:27:45', '2026-06-22 20:27:45');

-- --------------------------------------------------------

--
-- Table structure for table `remuneration`
--

CREATE TABLE `remuneration` (
  `id` int(11) NOT NULL,
  `id_agent` int(11) NOT NULL,
  `id_affectation` int(11) NOT NULL,
  `date_remun` date DEFAULT NULL,
  `mois` varchar(20) DEFAULT NULL,
  `annee` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `remuneration`
--

INSERT INTO `remuneration` (`id`, `id_agent`, `id_affectation`, `date_remun`, `mois`, `annee`) VALUES
(5, 2, 3, '2026-06-24', 'Juin', '2026');

-- --------------------------------------------------------

--
-- Table structure for table `retenue`
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
-- Dumping data for table `retenue`
--

INSERT INTO `retenue` (`id`, `id_agent`, `libelle`, `description`, `type_retenue`, `est_recurrent`, `date_debut`, `date_fin`, `montant`, `date_retenue`, `mois`, `annee`, `statut`, `created_at`, `updated_at`) VALUES
(4, 2, 'Assurance sociale', 'ghghjkgjhgkdfggf', 'assurance', 1, NULL, NULL, 20.00, '2026-06-22', 'Juin', '2026', 'actif', '2026-06-22 20:28:42', '2026-06-22 20:28:42'),
(5, 2, 'Prime Transport', 'KLKLKLMKLMKLKLMQ', 'autre', 1, NULL, NULL, 23.00, '2026-06-24', 'Juin', '2026', 'actif', '2026-06-24 02:28:16', '2026-06-24 02:28:16');

-- --------------------------------------------------------

--
-- Table structure for table `service`
--

CREATE TABLE `service` (
  `id` int(11) NOT NULL,
  `designation` varchar(150) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service`
--

INSERT INTO `service` (`id`, `designation`, `description`) VALUES
(2, 'Enseignement', ''),
(3, 'Administration', ''),
(4, 'Direction', '');

-- --------------------------------------------------------

--
-- Table structure for table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mot_de_passe` varchar(255) DEFAULT NULL,
  `role` varchar(255) NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `utilisateur`
--

INSERT INTO `utilisateur` (`id`, `nom`, `email`, `mot_de_passe`, `role`, `date_creation`) VALUES
(1, 'SALEH', 'saleh@gmail.com', '$2y$10$Ov0pHQSn/oMLPv5GHjxfTeF5RwWEXih9jOQsz.lS.rRnM5aHNySIe', 'Administrateur', '2026-06-20 20:16:52'),
(2, 'CAISSIER', 'caissier@gmail.com', '$2y$10$uXWS0tAwoFvBjGejuniKAurffDufkDJ6xdqvSo.8/HgTt1qJbT8c2', 'Caissier', '2026-06-22 10:03:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `affectation`
--
ALTER TABLE `affectation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_agent` (`id_agent`),
  ADD KEY `id_service` (`id_service`);

--
-- Indexes for table `agent`
--
ALTER TABLE `agent`
  ADD PRIMARY KEY (`id_agent`);

--
-- Indexes for table `annee_scolaire`
--
ALTER TABLE `annee_scolaire`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `avances`
--
ALTER TABLE `avances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_id` (`agent_id`);

--
-- Indexes for table `avantages`
--
ALTER TABLE `avantages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_annee` (`id_annee`),
  ADD KEY `idx_agent_annee` (`id_agent`,`id_annee`),
  ADD KEY `idx_type` (`type_avantage`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_mois_annee` (`mois`,`annee`);

--
-- Indexes for table `remuneration`
--
ALTER TABLE `remuneration`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_agent` (`id_agent`),
  ADD KEY `id_affectation` (`id_affectation`);

--
-- Indexes for table `retenue`
--
ALTER TABLE `retenue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_agent` (`id_agent`);

--
-- Indexes for table `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `affectation`
--
ALTER TABLE `affectation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `agent`
--
ALTER TABLE `agent`
  MODIFY `id_agent` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `annee_scolaire`
--
ALTER TABLE `annee_scolaire`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `avances`
--
ALTER TABLE `avances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `avantages`
--
ALTER TABLE `avantages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `remuneration`
--
ALTER TABLE `remuneration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `retenue`
--
ALTER TABLE `retenue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `service`
--
ALTER TABLE `service`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `affectation`
--
ALTER TABLE `affectation`
  ADD CONSTRAINT `affectation_ibfk_1` FOREIGN KEY (`id_agent`) REFERENCES `agent` (`id_agent`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `affectation_ibfk_2` FOREIGN KEY (`id_service`) REFERENCES `service` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `avances`
--
ALTER TABLE `avances`
  ADD CONSTRAINT `avances_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `agent` (`id_agent`);

--
-- Constraints for table `avantages`
--
ALTER TABLE `avantages`
  ADD CONSTRAINT `avantages_ibfk_1` FOREIGN KEY (`id_agent`) REFERENCES `agent` (`id_agent`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `avantages_ibfk_2` FOREIGN KEY (`id_annee`) REFERENCES `annee_scolaire` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `remuneration`
--
ALTER TABLE `remuneration`
  ADD CONSTRAINT `remuneration_ibfk_1` FOREIGN KEY (`id_agent`) REFERENCES `agent` (`id_agent`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `remuneration_ibfk_2` FOREIGN KEY (`id_affectation`) REFERENCES `affectation` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `retenue`
--
ALTER TABLE `retenue`
  ADD CONSTRAINT `retenue_ibfk_1` FOREIGN KEY (`id_agent`) REFERENCES `agent` (`id_agent`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
