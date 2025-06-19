# Rapport Final du Projet ERPNextClone

## 1. Introduction

Ce document détaille le projet ERPNextClone, un système simplifié de planification des ressources d'entreprise (ERP) développé en PHP natif avec une architecture MVC. L'objectif principal était de créer une application web modulaire permettant de gérer des fonctionnalités clés d'un ERP, telles que la gestion des produits (avec unités de mesure multiples et catégories), des tiers (fournisseurs, clients, avec catégories), les cycles d'approvisionnement et de vente (incluant la gestion des unités par ligne), la gestion des stocks (traçant les unités d'origine et de base), le fractionnement de produits, la génération de rapports et l'impression de documents clés.

Le projet met l'accent sur une structure de code claire, la séparation des préoccupations (MVC), et l'utilisation de PHP natif (PHP 8.x), PostgreSQL pour la base de données, et HTML/CSS/JavaScript natif pour l'interface utilisateur.

## 2. Architecture Logicielle

### 2.1. Architecture MVC

Le projet est structuré selon le motif de conception Modèle-Vue-Contrôleur (MVC) :

*   **Modèles (`app/models/`) :** Représentent la logique métier et l'interaction avec la base de données. Chaque entité principale (Produit, Client, Fournisseur, Vente, Commande Fournisseur, Livraison, Catégories, Unités, etc.) possède son propre modèle. Ils héritent d'une classe `Model` de base et sont responsables de la validation des données, de l'exécution des requêtes SQL (via la classe `Database`), et du retour des données au contrôleur.
*   **Vues (`app/views/`) :** Sont responsables de la présentation des données à l'utilisateur. Elles sont composées de fichiers PHP contenant du HTML et des boucles/conditions PHP simples pour afficher les données passées par les contrôleurs. Un système de layout principal (`layouts/main.php`) est utilisé pour maintenir une structure de page cohérente. Des vues spécifiques pour l'impression (`print_*.php`) sont utilisées sans le layout principal.
*   **Contrôleurs (`app/controllers/`) :** Agissent comme intermédiaires entre les Modèles et les Vues. Ils reçoivent les requêtes de l'utilisateur (via le routeur), interagissent avec les modèles pour récupérer ou modifier des données, puis sélectionnent et passent les données à la vue appropriée pour l'affichage. Chaque module fonctionnel possède son propre contrôleur (ex: `ProductsController`, `SaleController`, `ProductcategoriesController`, `ReportController`, `FractioningController`).

### 2.2. Composants `core/`

Le dossier `core/` contient les classes fondamentales de l'application :

*   **`Database.php` :** Une classe Singleton responsable de la connexion à la base de données (PostgreSQL via PDO) et de l'exécution des requêtes SQL.
*   **`Controller.php` :** Classe de base pour tous les contrôleurs, fournissant des méthodes utilitaires comme `loadModel()` et `renderView()`, ainsi que la nouvelle méthode `renderPrintView()` pour les sorties d'impression.
*   **`Model.php` :** Classe de base pour tous les modèles, fournissant l'instance de `Database`.

### 2.3. Structure des Dossiers

L'arborescence du projet est organisée comme suit (extrait pertinent) :
```
/
|-- app/
|   |-- controllers/  // (Products, Sales, Purchaseorder, Delivery, ClientCategories, SupplierCategories, Report, Fractioning, etc.)
|   |-- models/       // (Product, Sale, PurchaseOrder, Delivery, ClientCategory, SupplierCategory, Unit, StockMovement, etc.)
|   |-- views/        // (Organisé par module, incluant les nouvelles vues pour catégories, fractionnement, rapports, et impression)
|       |-- layouts/      // main.php
|       |-- fractioning/
|       |-- product_categories/
|       |-- client_categories/
|       |-- supplier_categories/
|       |-- reports/
|       |-- sales/ (avec print_invoice.php, print_payment_receipt.php)
|       |-- procurement/
|           |-- purchase_orders/ (avec print_po.php)
|           |-- deliveries/ (avec print_delivery_note.php)
|-- core/
|-- config/
|   |-- database.php
|-- public/
|   |-- css/
|       |-- style.css
|       |-- print_style.css (Nouveau)
|   |-- js/
|   |-- index.php
|-- docs/
|   |-- database_schema.sql
|   |-- sequence_diagrams/  (Mis à jour et nouveaux ajoutés)
|   |-- Rapport_Final.md
```

## 3. Schéma de la Base de Données

Le schéma complet et à jour est dans `docs/database_schema.sql`. Les ajouts et modifications majeurs incluent :

*   **Nouvelles Tables :**
    *   `units` : Stocke les unités de mesure (ex: Pièce, Kg, Carton de 10) avec nom et symbole.
    *   `product_units` : Table de liaison associant les produits à plusieurs unités, avec un facteur de conversion vers l'unité de base du produit.
    *   `product_categories` : Catégories pour les produits.
    *   `client_categories` : Catégories pour les clients.
    *   `supplier_categories` : Catégories pour les fournisseurs.
    *   `sale_payments` : Enregistre les paiements multiples pour les ventes à paiement différé.
*   **Modifications des Tables Existantes :**
    *   `products` : Suppression de `unit_of_measure` (texte) et `parent_id`. Ajout de `base_unit_id` (FK vers `units`) et `category_id` (FK vers `product_categories`). `quantity_in_stock` est toujours en unité de base.
    *   `clients` : Ajout de `client_category_id` (FK vers `client_categories`).
    *   `suppliers` : Ajout de `supplier_category_id` (FK vers `supplier_categories`).
    *   `purchase_order_items` : Ajout de `unit_id` (FK vers `units`) pour spécifier l'unité de la ligne de commande.
    *   `delivery_items` : Ajout de `unit_id` (FK vers `units`) pour l'unité de réception.
    *   `sale_items` : Ajout de `unit_id` (FK vers `units`) pour l'unité de vente.
    *   `sales` : Ajout de `discount_amount`, `paid_amount`, `amount_tendered`, `change_due`. `total_amount` représente maintenant le montant net après réduction.
    *   `stock_movements` : Ajout de `original_unit_id` (FK vers `units`) et `original_quantity` pour tracer la quantité et l'unité de la transaction d'origine. La colonne `quantity` stocke toujours la quantité convertie dans l'unité de base du produit.

Des triggers `update_updated_at_column` sont appliqués aux nouvelles tables de catégories et à `sale_payments`. Les contraintes et index ont été mis à jour en conséquence.

## 4. Description des Modules Fonctionnels (Mise à Jour)

### 4.1. Module Produits (`ProductsController`, `ProductModel`)
*   **Fonctionnalités :** CRUD complet. Gestion des catégories de produits via `ProductcategoriesController`.
*   **Unités de Mesure :** Chaque produit a une `base_unit_id` obligatoire. Des unités alternatives peuvent être associées via `product_units` avec un `conversion_factor_to_base_unit`. L'interface de création/modification de produit permet de gérer ces associations.
*   **Fractionnement (`FractioningController`, `ProductModel`, `StockMovementModel`) :**
    *   Interface dédiée pour convertir une quantité d'un produit d'une unité source vers une unité cible (pour le même produit).
    *   Valide le stock disponible dans l'unité source.
    *   Calcule la quantité convertie dans l'unité cible.
    *   Génère deux mouvements de stock : `split_out` pour le produit source et `split_in` pour le produit cible (qui est le même produit mais représente la "nouvelle forme"). Les mouvements tracent les unités et quantités d'origine et de base.
    *   Les opérations sont atomiques (transaction PDO).

### 4.2. Module Fournisseurs (`SuppliersController`, `SupplierModel`)
*   **Fonctionnalités :** CRUD complet. Gestion des catégories de fournisseurs via `SupplierCategoriesController`.
*   Les formulaires et vues de fournisseurs permettent d'assigner et d'afficher une catégorie.

### 4.3. Module Clients (`ClientsController`, `ClientModel`)
*   **Fonctionnalités :** CRUD complet. Gestion des catégories de clients via `ClientCategoriesController`.
*   Les formulaires et vues de clients permettent d'assigner et d'afficher une catégorie.

### 4.4. Module Approvisionnement
*   **Commandes Fournisseurs (`PurchaseorderController`, `PurchaseOrderModel`) :**
    *   Les lignes de commande incluent maintenant une sélection d'unité (`unit_id`) spécifique à cette ligne, choisie parmi les unités configurées pour le produit. Le JavaScript des formulaires peuple dynamiquement ce sélecteur.
    *   Validation serveur pour s'assurer que l'unité est valide pour le produit.
*   **Réceptions de Livraison (`DeliveryController`, `DeliveryModel`) :**
    *   Les lignes de réception incluent `unit_id`. Si la livraison provient d'une commande, l'unité est généralement celle de la commande. Pour les livraisons directes, l'unité est sélectionnable.
    *   La mise à jour du stock (`ProductModel->updateStock()`) est appelée avec la quantité reçue et son unité. Le modèle `ProductModel` gère la conversion en unité de base avant d'affecter `products.quantity_in_stock` et `StockMovementModel` enregistre le mouvement avec les détails d'unité d'origine et de base.
*   **Factures Fournisseurs :** Pas de modifications majeures dans ce cycle, mais la structure est en place.

### 4.5. Module Ventes (`SaleController`, `SaleModel`, `SalePaymentModel`)
*   **Gestion des Ventes :**
    *   Les lignes de vente incluent `unit_id`, sélectionnée dynamiquement dans le formulaire parmi les unités configurées pour le produit.
    *   Validation serveur de l'unité et du stock disponible (converti en unité de base).
    *   `ProductModel->updateStock()` est appelé avec la quantité vendue (négative) et son unité de transaction.
*   **Paiements Immédiats :**
    *   Gestion de `discount_amount` (réduction).
    *   Calcul et enregistrement de `amount_tendered` (montant versé) et `change_due` (monnaie à rendre).
    *   Le `total_amount` de la vente est net après réduction. `paid_amount` est initialisé.
*   **Paiements Différés et Multiples :**
    *   Nouvelle table `sale_payments` pour enregistrer chaque versement.
    *   Interface `sales/manage_payments.php` pour ajouter des paiements à une vente différée et voir l'historique.
    *   Après chaque paiement, `SaleModel->updateSalePaymentStatus()` met à jour `sales.paid_amount` et `sales.payment_status` ('pending', 'partially_paid', 'paid').

### 4.6. Module Gestion des Stocks (`StockController`, `StockMovementModel`, `ProductModel`)
*   **Mouvements de Stock :** La table `stock_movements` stocke maintenant `original_unit_id` et `original_quantity` en plus de `quantity` (qui est toujours en unité de base du produit). Cela permet une traçabilité complète.
*   **`ProductModel->updateStock()` :** Refactorisée pour accepter la quantité et l'unité de la transaction, effectuer la conversion en unité de base pour `products.quantity_in_stock`, et instruire `StockMovementModel` d'enregistrer le mouvement avec tous les détails d'unité.
*   Les types de mouvement `split_in` et `split_out` ont été ajoutés et sont utilisés par la fonctionnalité de fractionnement. `delivery_reversal` et `sale_reversal` sont également prévus.

### 4.7. Module Rapports (`ReportController`)
*   **État du Stock Actuel (`current_stock` renommé en `stock_status_report` implicitement) :**
    *   Affiche `quantity_in_stock` (en unité de base).
    *   Pour chaque produit, affiche également le stock converti dans toutes ses autres unités configurées (en utilisant les facteurs de `product_units`).
*   **Mouvements de Stock Détaillés (`stock_movements_report`) :**
    *   Nouveau rapport listant tous les mouvements de stock avec options de filtrage (période, produit, type de mouvement).
    *   Affiche les quantités en unité d'origine (avec symbole) et en unité de base du produit (avec symbole).
    *   Affiche les informations du document lié et les notes.
*   **Rapport de Caisse Journalier (`daily_cash_flow`) :**
    *   Nouveau rapport résumant les encaissements par jour sur une période.
    *   Combine les ventes immédiates payées (`sales.total_amount`) et les paiements reçus pour les ventes différées (`sale_payments.amount_paid`).
    *   Filtres par date.

### 4.8. Impression de Documents
*   **Fonctionnalité :** Ajout de la capacité d'imprimer des documents formatés.
*   **Méthode `renderPrintView` dans `core/Controller.php` :** Permet de rendre des vues sans le layout principal.
*   **Vues d'Impression dédiées :**
    *   `sales/print_invoice.php` (Facture de vente)
    *   `sales/print_payment_receipt.php` (Reçu de paiement individuel)
    *   `procurement/purchase_orders/print_po.php` (Bon de commande)
    *   `procurement/deliveries/print_delivery_note.php` (Bon de livraison)
*   **CSS d'Impression (`public/css/print_style.css`) :** Fichier CSS de base pour optimiser l'affichage à l'impression.
*   **Liens d'Impression :** Ajoutés sur les pages `show` des ventes, commandes fournisseurs, livraisons, et sur la page de gestion des paiements.

## 5. Interface Utilisateur (UI/UX)

*   **CSS :** Les styles de base ont été maintenus. Un fichier `print_style.css` a été ajouté.
*   **JavaScript :** Améliorations significatives pour la gestion dynamique des unités dans les formulaires de commande, livraison et vente, y compris la population des `select` d'unités basée sur le produit choisi et l'affichage du stock converti. Les calculs de totaux dans les ventes immédiates (incluant réduction, montant versé, monnaie) sont également gérés en JS.
*   **Francisation :** L'interface est majoritairement en français.
*   **Convivialité :** Les formulaires pour les opérations impliquant des unités ont été adaptés. Les rapports fournissent des informations plus riches.

## 6. Instructions d'Installation et de Configuration
(Contenu existant à vérifier, notamment si de nouvelles dépendances PHP ont été implicitement ajoutées, ex: `php-intl` pour le formatage de nombres si utilisé, mais ici c'est `number_format` natif).
La section existante reste globalement valide. PHP 7.4+ et PostgreSQL sont les prérequis majeurs.

## 7. Conclusion

Le projet ERPNextClone a été considérablement enrichi avec des fonctionnalités avancées de gestion des unités de mesure, de catégorisation, de suivi des paiements, de fractionnement de produits et d'impression. Ces ajouts rendent le système plus flexible et plus proche des besoins réels d'une gestion d'entreprise.

**Fonctionnalités Réalisées (par rapport à la description initiale et aux ajouts) :**
*   CRUD pour produits, clients, fournisseurs, avec catégories respectives.
*   Gestion des unités de mesure multiples pour les produits, avec conversion.
*   Cycle d'approvisionnement complet (BC, Livraison, Facture Fournisseur) avec gestion des unités par ligne.
*   Cycle de vente complet (Vente immédiate/différée) avec gestion des unités par ligne, réductions, et suivi des paiements multiples pour les ventes différées.
*   Gestion des stocks précise, traçant les mouvements en unité de base et en unité d'origine.
*   Fonctionnalité de fractionnement de produits.
*   Rapports améliorés : état des stocks avec conversions d'unités, mouvements de stock détaillés avec unités, rapport de caisse journalier.
*   Impression des documents essentiels (Facture vente, Reçu paiement, Bon de commande, Bon de livraison).

**Pistes d'Améliorations Futures Possibles (Mise à Jour) :**

*   **Authentification et Gestion des Utilisateurs/Permissions.**
*   **Tableau de Bord.**
*   **Assemblage de Produits (Kits/Nomenclatures) :** La partie "assemblage" du fractionnement/assemblage n'a pas été traitée.
*   **Gestion des Devis/Propositions Commerciales.**
*   **Comptabilité plus Poussée.**
*   **Export de Données (CSV/Excel).**
*   **Tests Unitaires et d'Intégration.**
*   **API REST.**
*   **Améliorations UI/UX :**
    *   Utilisation de bibliothèques JS pour composants riches (modales, datepickers stylisés, graphiques).
    *   Système de "flash messages" en session.
    *   Validation JavaScript côté client plus interactive et uniforme.
    *   Amélioration de la performance de chargement des données pour les `select` avec beaucoup d'options (ex: produits dans les formulaires de transaction) via AJAX/autocomplétion.
*   **Internationalisation (i18n) et Localisation (l10n).**
*   **Configuration Globale de l'Entreprise :** Pour les détails (nom, adresse, etc.) figurant sur les documents imprimés.
*   **Gestion plus fine des erreurs et logging.**

Ce projet constitue une fondation robuste et démontre la faisabilité d'un ERP modulaire avec les technologies choisies.

---
**Fin du Rapport.**
