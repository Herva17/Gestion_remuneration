# Gestion des Paiements Agents

Application web de gestion des paiements pour les agents avec un dashboard professionnel.

## 📋 Fonctionnalités

- **Dashboard Professionnel** : Vue d'ensemble avec statistiques clés
- **Gestion des Agents** : Ajouter, éditer, supprimer les agents
- **Gestion des Services** : Gérer les différents services disponibles avec photo de service et description courte
- **Gestion des Affectations** : Assigner les agents aux services avec lieu et date
- **Gestion des Prestations** : Enregistrer les prestations avec calcul automatique du montant
- **Gestion des Paiements** : Suivre les paiements avec statut et mode de paiement

## 🛠️ Installation

### Prérequis
- PHP 7.4+
- MySQL 5.7+
- XAMPP ou équivalent

### Étapes d'installation

1. **Créer la base de données** :
   - Ouvrir phpMyAdmin
   - Importer le fichier SQL (ou exécuter manuellement les requêtes CREATE TABLE)

2. **Configurer la connexion** :
   - Vérifier les identifiants dans `Config/Database.php`
   - Par défaut : localhost, root, pas de mot de passe

3. **Exécuter la migration** :
   - Si votre table `Service` ne contient pas encore la colonne `photo`, lancez :
     ```
     php Config/migrate.php
     ```

4. **Accéder à l'application** :
   ```
   http://localhost/Gestion_Paiement_Agents/
   ```

## 📁 Structure du projet

```
Gestion_Paiement_Agents/
├── index.php                    # Dashboard principal
├── Config/
│   └── Database.php            # Configuration base de données
├── Classes/
│   ├── Agent.php               # Classe Agent avec CRUD
│   ├── Service.php             # Classe Service avec CRUD
│   ├── Affectation.php         # Classe Affectation avec CRUD
│   ├── Prestation.php          # Classe Prestation avec CRUD
│   └── Paiement.php            # Classe Paiement avec CRUD
└── pages/
    ├── agents/
    │   ├── index.php           # Liste des agents
    │   ├── add.php             # Formulaire ajout/édition
    │   ├── edit.php            # Redirection édition
    │   └── delete.php          # Suppression
    ├── services/               # Même structure que agents
    ├── affectations/           # Même structure que agents
    ├── prestations/            # Même structure que agents
    └── paiements/              # Même structure que agents
```

## 🎨 Design & Technologie

- **Frontend** : Tailwind CSS pour un design modern et professionnel
- **Icons** : Font Awesome 6
- **Backend** : PHP avec requêtes préparées PDO
- **Sécurité** : Protection contre les injections SQL avec PDO

## 🔒 Sécurité

- Toutes les requêtes utilisent PDO avec requêtes préparées
- Protection contre les injections SQL
- Gestion des sessions PHP

## 📊 Tables de la base de données

### Agent
- id_agent (PK)
- nom_complet
- adresse
- date_naissance
- telephone
- profil
- lieu_naissance
- fonction

### Service
- id (PK)
- designation
- description

### Affectation
- id (PK)
- lieu_affectation
- date_affectation
- id_agent (FK)
- id_service (FK)

### Prestation
- numeroPrest (PK)
- libelle
- nbreHeure
- salaire_horaire
- montant (auto-calculé)
- date_prestation
- id_affectation (FK)

### Paiement
- id (PK)
- reference
- montant
- date_paiement
- mode_paiement
- statut
- numeroPrest (FK)

## 🚀 Utilisation

### Dashboard
- Affiche un aperçu complet des statistiques
- Montants totaux des prestations et paiements
- Liste des derniers paiements

### Gestion des entités
1. Cliquer sur le menu pour accéder à la section
2. Voir la liste de tous les éléments
3. Cliquer sur "Ajouter" pour créer un nouvel élément
4. Cliquer sur l'icône édition pour modifier
5. Cliquer sur l'icône suppression pour effacer

## 📝 Notes importantes

- Assurez-vous que la base de données est créée avant d'utiliser l'application
- Les montants des prestations se calculent automatiquement (heures × salaire horaire)
- Les affectations doivent être créées avant les prestations
- Les prestations doivent être créées avant les paiements
- Vous pouvez ajouter une photo par service dans la page d'administration des services
- Chaque service peut aussi contenir une description courte
- La page de services a désormais un filtre de recherche performant
- Un reçu de paiement est disponible depuis la liste des paiements pour impression

## 👨‍💻 Support

Pour toute question ou problème, vérifiez :
1. La connexion à la base de données
2. Les permissions des fichiers
3. La version de PHP
4. Les logs d'erreur PHP



Adresse mail: salzh@gmail.com
mot de passe : 12345678