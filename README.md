# 💰 Gestion des Paiements Agents

**Application web complète de gestion des paiements pour les agents** avec un dashboard professionnel, gestion des avances sur salaire, rémunérations et fiches de paie.

---

## 📋 Fonctionnalités principales

### 🏠 Dashboard
- Vue d'ensemble avec **statistiques clés** en temps réel
- Graphiques et indicateurs de performance
- Accès rapide aux modules principaux
- Vue adaptée selon le rôle (Administrateur / Caissier)

### 👤 Gestion des Agents
- Ajouter, modifier, supprimer des agents
- Profils avec photo et informations complètes
- Suivi des affectations et fonctions
- Recherche et filtres avancés

### 🏢 Gestion des Services
- Gérer les différents services disponibles
- Photo de service et description courte
- Filtre de recherche performant

### 📌 Gestion des Affectations
- Assigner les agents aux services
- Lieu et date d'affectation
- Suivi des affectations actives

### 💵 Gestion des Rémunérations
- Saisie du salaire de base par agent
- Calcul automatique du salaire brut
- Intégration des avantages et retenues
- **Fiche de paie** détaillée et imprimable

### 🎁 Gestion des Avantages
- Avantages récurrents ou ponctuels
- Catégorisation par type (prime, indemnité, bonus, etc.)
- Association automatique aux rémunérations

### 📉 Gestion des Retenues
- Retenues récurrentes ou ponctuelles
- Catégorisation par type (impôt, sécurité sociale, assurance, etc.)
- Association automatique aux rémunérations

### 💳 Gestion des Avances sur Salaire
- **Nouveau module** : Avances sur salaire
- Suivi des avances en cours et remboursées
- Déduction automatique sur la fiche de paie
- Visualisation du solde restant
- Marquer une avance comme remboursée

### 📄 Fiche de Paie
- Fiche de paie détaillée et synthétique
- **Calcul automatique** : Salaire brut → Salaire net → Salaire net après avances
- Affichage en lettres du montant
- **Reçu de paiement** imprimable
- Design professionnel optimisé pour l'impression

### 🧾 Reçu de Paiement Synthétique
- Résumé complet du bulletin de salaire
- Montant en lettres
- Détail des avantages, retenues et avances
- Signatures et cachets
- Format imprimable A4

---

## 🛠️ Installation

### Prérequis
- PHP 7.4+
- MySQL 5.7+
- XAMPP / WAMP / MAMP

### Étapes d'installation

1. **Cloner le projet** dans le dossier de votre serveur local :
   ```bash
   git clone https://github.com/votre-repo/gestion_paiement_agents.git

   Créer la base de données :

Ouvrir phpMyAdmin

Créer une base nommée gestion_paiement_agents

Importer le fichier SQL fourni

Configurer la connexion :

php
// Config/Database.php
private $host = 'localhost';
private $dbname = 'gestion_paiement_agents';
private $user = 'root';
private $password = '';
Exécuter les migrations (si nécessaire) :

bash
php Config/migrate.php
Accéder à l'application :

text
http://localhost/gestion_paiement_agents/
📁 Structure du projet
text
gestion_paiement_agents/
├── index.php                         # Dashboard principal
├── Dashboard.php                     # Page d'accueil
├── logout.php                        # Déconnexion
├── Config/
│   ├── Database.php                  # Configuration base de données
│   └── migrate.php                   # Script de migration
├── Classes/
│   ├── Agent.php                     # CRUD Agents
│   ├── Service.php                   # CRUD Services
│   ├── Affectation.php               # CRUD Affectations
│   ├── Remuneration.php              # CRUD Rémunérations
│   ├── Avantage.php                  # CRUD Avantages
│   ├── Retenue.php                   # CRUD Retenues
│   ├── Avance.php                    # CRUD Avances sur salaire
│   └── AnneeScolaire.php             # Gestion des années scolaires
└── pages/
    ├── agents/                       # Gestion des agents
    │   ├── index.php
    │   ├── add.php
    │   ├── edit.php
    │   ├── delete.php
    │   └── view.php
    ├── services/                     # Gestion des services
    │   ├── index.php
    │   ├── add.php
    │   ├── edit.php
    │   └── delete.php
    ├── affectations/                 # Gestion des affectations
    │   ├── index.php
    │   ├── add.php
    │   ├── edit.php
    │   └── delete.php
    ├── remunerations/                # Gestion des rémunérations
    │   ├── index.php
    │   ├── add.php
    │   ├── edit.php
    │   ├── delete.php
    │   └── fiche_paie.php            # Fiche de paie détaillée
    ├── avantages/                    # Gestion des avantages
    │   ├── index.php
    │   ├── add.php
    │   ├── edit.php
    │   └── delete.php
    ├── retenues/                     # Gestion des retenues
    │   ├── index.php
    │   ├── add.php
    │   ├── edit.php
    │   └── delete.php
    ├── avances/                      # Gestion des avances
    │   ├── index.php
    │   ├── add.php
    │   ├── edit.php
    │   ├── delete.php
    │   ├── rembourser.php            # Marquer comme remboursée
    │   └── view.php
    ├── paiements/                    # Gestion des paiements
    │   ├── index.php
    │   ├── add.php
    │   ├── edit.php
    │   ├── delete.php
    │   └── reçu.php                  # Reçu de paiement
    └── caissier/                     # Espace caissier
        └── dashboard.php
🎨 Design & Technologie
Frontend : Tailwind CSS

Icons : Font Awesome 6

Polices : Google Fonts (Inter)

Backend : PHP 7.4+ avec PDO

Sécurité : Requêtes préparées, sessions PHP

Base de données : MySQL

🔐 Rôles et Permissions
Module	Administrateur	Caissier	Comptable
Dashboard	✅	✅	✅
Gestion des Agents	✅	❌	✅
Gestion des Services	✅	❌	✅
Gestion des Affectations	✅	❌	✅
Gestion des Rémunérations	✅	✅	✅
Gestion des Avantages	✅	✅	✅
Gestion des Retenues	✅	✅	✅
Gestion des Avances	✅	✅	✅
Gestion des Paiements	✅	✅	✅
Fiche de Paie	✅	✅	✅
Reçu de Paiement	✅	✅	✅
📊 Tables de la base de données
agent
Champ	Type	Description
id_agent	INT PK	Identifiant agent
nom_complet	VARCHAR	Nom complet
telephone	VARCHAR	Téléphone
fonction	VARCHAR	Fonction de l'agent
profil	VARCHAR	Profil (Enseignant, etc.)
adresse	TEXT	Adresse
avance
Champ	Type	Description
id	INT PK	Identifiant
agent_id	INT FK	Agent concerné
mois	VARCHAR	Mois de l'avance
annee	INT	Année de l'avance
libelle	VARCHAR	Motif de l'avance
montant	DECIMAL	Montant
statut	ENUM	en_cours / rembourse
date_creation	DATETIME	Date de création
remuneration
Champ	Type	Description
id	INT PK	Identifiant
id_agent	INT FK	Agent concerné
montant	DECIMAL	Salaire de base
mois	VARCHAR	Mois concerné
annee	INT	Année concernée
date_remun	DATETIME	Date de création
avantages / retenues
Champ	Type	Description
id	INT PK	Identifiant
agent_id	INT FK	Agent concerné
mois	VARCHAR	Mois concerné
annee	INT	Année concernée
libelle	VARCHAR	Libellé
montant	DECIMAL	Montant
type	VARCHAR	Type
🚀 Utilisation
1. Dashboard
Accès rapide aux statistiques

Indicateurs de performance

Dernières activités

2. Gestion des Agents
Créer un nouvel agent

Modifier les informations

Visualiser le profil complet

Supprimer avec confirmation

3. Gestion des Rémunérations
Saisir le salaire de base

Consultation des fiches de paie

Calcul automatique :

text
Salaire brut = Salaire de base + Avantages
Salaire net = Salaire brut - Retenues
Net à payer = Salaire net - Avances
4. Gestion des Avances
Créer une nouvelle avance

Visualiser l'historique

Marquer une avance comme remboursée

Déduction automatique sur la fiche de paie

5. Fiche de Paie
Accès depuis la liste des rémunérations

Détail complet des calculs

Montant en lettres automatique

Impression A4

📝 Notes importantes
Toutes les avances en cours sont automatiquement déduites de la fiche de paie

Le salaire net ne peut pas être négatif (plafonné à 0)

Les fiches de paie sont consultables et imprimables

Le reçu de paiement est disponible depuis la fiche de paie

Les rôles déterminent les accès aux fonctionnalités

👨‍💻 Comptes de test
Email	Mot de passe	Rôle
saleh@gmail.com	12345678	Administrateur
caissier@gmail.com	12345678	Caissier
📞 Support
Pour toute question ou problème :

Vérifier la connexion à la base de données

Consulter les logs d'erreur PHP

Vérifier les permissions des fichiers

Contacter le support technique

📄 Licence
Ce projet est sous licence MIT - voir le fichier LICENSE pour plus de détails.

✨ Auteurs
Saleh - Développement initial

Équipe Gestion Paiements - Contributions et améliorations

🔄 Historique des versions
v2.0 (2026)
✅ Ajout du module Avances sur salaire

✅ Intégration des avances dans les fiches de paie

✅ Nouveau Reçu de paiement synthétique

✅ Amélioration du design et de l'UX

✅ Optimisation des performances

v1.0 (2025)
✅ Version initiale

✅ Gestion des agents, services, affectations

✅ Gestion des rémunérations et paiements

✅ Dashboard professionnel

Made with ❤️ pour la Gestion des Paiements Agents

text

## Principaux ajouts au README :

### 1. **Nouveaux modules**
- Gestion des Avances sur Salaire
- Gestion des Rémunérations
- Gestion des Avantages
- Gestion des Retenues

### 2. **Fonctionnalités détaillées**
- Fiche de paie avec calcul automatique
- Reçu de paiement synthétique
- Déduction automatique des avances

### 3. **Tables de la base de données**
- Table `avance` avec structure
- Table `remuneration` avec structure

### 4. **Rôles et permissions**
- Tableau des rôles (Administrateur / Caissier / Comptable)

### 5. **Formules de calcul**
- Salaire brut, net, net à payer
- Intégration des avances

### 6. **Structure du projet**
- Nouvelles pages : avances, remunerations, avantages, retenues
- Fiche de paie et reçu de paiement

### 7. **Comptes de test**
- Ajout du compte caissier

### 8. **Historique des versions**
- Version 2.0 avec les nouvelles fonctionnalités
