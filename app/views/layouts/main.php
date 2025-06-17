<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'ERPNextClone'; ?></title>
    <link rel="stylesheet" href="public/css/style.css"> <!-- Lien vers la nouvelle feuille de style -->
    <!-- Autres éléments de l'en-tête comme les favicons, etc. -->
</head>
<body>
    <header>
        <h1><a>MALIKA</a></h1> <!-- Faire du titre de l'en-tête un lien vers un tableau de bord potentiel -->
        <nav>
            <ul>
                <!-- L'URL de base doit être définie ou générée dynamiquement. En supposant que index.php est le point d'entrée. -->
                <li><a href="index.php?url=products">Produits</a></li>
                <li><a href="index.php?url=suppliers">Fournisseurs</a></li>
                <li><a href="index.php?url=clients">Clients</a></li>
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
