<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'MALIKA'; ?></title>
    <link rel="stylesheet" href="public/css/style.css"> <!-- Lien vers la nouvelle feuille de style -->
    <!-- Autres éléments de l'en-tête comme les favicons, etc. -->
</head>
<body>
    <header>
        <h1><a>MALIKA</a></h1> <!-- Faire du titre de l'en-tête un lien vers un tableau de bord potentiel -->
        <nav>
            <ul>
                <!-- L'URL de base doit être définie ou générée dynamiquement. En supposant que index.php est le point d'entrée. -->
                <li class="dropdown">
                    <a href="javascript:void(0)" class="dropbtn">Produits</a>
                    <div class="dropdown-content">
                        <a href="index.php?url=products">Gérer les Produits</a>
                        <a href="index.php?url=units">Gérer les Unités</a>
                        <a href="index.php?url=productcategories">Gérer les Catégories de Produits</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="javascript:void(0)" class="dropbtn">Fournisseurs</a>
                    <div class="dropdown-content">
                        <a href="index.php?url=suppliers">Gérer les Fournisseurs</a>
                        <a href="index.php?url=suppliercategories">Gérer les Catégories de Fournisseurs</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="javascript:void(0)" class="dropbtn">Clients</a>
                    <div class="dropdown-content">
                        <a href="index.php?url=clients">Gérer les Clients</a>
                        <a href="index.php?url=clientcategories">Gérer les Catégories de Clients</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="javascript:void(0)" class="dropbtn">Approvisionnement</a>
                    <div class="dropdown-content">
                        <a href="index.php?url=purchaseorder/index">Bons de commande</a>
                        <a href="index.php?url=delivery/index">Livraisons</a>
                        <a href="index.php?url=supplierinvoice/index">Factures fournisseurs</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="javascript:void(0)" class="dropbtn">Ventes</a>
                    <div class="dropdown-content">
                        <a href="index.php?url=sale/create_immediate_payment">Nouvelle vente (immédiate)</a>
                        <a href="index.php?url=sale/create_deferred_payment">Nouvelle vente (différée)</a>
                        <a href="index.php?url=sale/index">Historique des ventes</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="javascript:void(0)" class="dropbtn">Stock</a>
                    <div class="dropdown-content">
                        <a href="index.php?url=stock/index">Aperçu du stock</a>
                        <a href="index.php?url=stock/create_adjustment">Nouvel ajustement</a>
                        <a href="index.php?url=fractioning/index">Fractionnement de produit</a>
                    </div>
                </li>
                 <li class="dropdown">
                    <a href="javascript:void(0)" class="dropbtn">Rapports</a>
                    <div class="dropdown-content">
                        <a href="index.php?url=report/index">Accueil des rapports</a>
                        <hr style="margin: 2px 0; border-color: #555;">
                        <a href="index.php?url=report/current_stock">Stock actuel</a>
                        <a href="index.php?url=report/stock_entries">Entrées de stock</a>
                        <a href="index.php?url=report/stock_exits">Sorties de stock</a>
                        <a href="index.php?url=report/sales_report">Rapport des ventes</a>
                        <a href="index.php?url=report/purchases_report">Rapport des achats</a>
                        <a href="index.php?url=report/daily_cash_flow">Flux de Caisse Journalier</a>
                    </div>
                </li>
                <!-- Ajoutez plus de liens de navigation globale ici -->
            </ul>
        </nav>
    </header>
    <!-- Les styles en ligne pour le dropdown ont été déplacés dans style.css -->

    <main class="container"> <!-- Utiliser main pour la zone de contenu principal -->
        <div class="content">
            <?php echo $content; // C'est ici que le contenu de la vue sera injecté ?>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> MALIKA. Tous droits réservés.</p>
    </footer>
</body>
</html>
