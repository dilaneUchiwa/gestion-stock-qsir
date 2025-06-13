<?php
// $title is set by controller
// $title = 'Rapports disponibles';
?>

<h2>Rapports disponibles</h2>

<p>Sélectionnez un rapport dans la liste ci-dessous pour afficher ses détails.</p>

<ul class="report-list">
    <li>
        <h4>Rapports de stock</h4>
        <ul>
            <li><a href="index.php?url=report/current_stock">Rapport de stock actuel</a> - Voir les niveaux de stock actuels pour tous les produits.</li>
            <li><a href="index.php?url=report/stock_entries">Rapport des entrées en stock</a> - Suivre les mouvements de stock entrants (livraisons, ajustements positifs).</li>
            <li><a href="index.php?url=report/stock_exits">Rapport des sorties de stock</a> - Suivre les mouvements de stock sortants (ventes, ajustements négatifs).</li>
        </ul>
    </li>
    <li>
        <h4>Rapports sur les ventes et les achats</h4>
        <ul>
            <li><a href="index.php?url=report/sales_report">Rapport des ventes</a> - Analyser les ventes par période, par client ou par statut de paiement.</li>
            <li><a href="index.php?url=report/purchases_report">Rapport des achats</a> - Analyser les bons de commande ou les livraisons par période ou par fournisseur.</li>
        </ul>
    </li>
    <!-- Add more report categories or individual reports here -->
</ul>

<style>
    .report-list {
        list-style-type: none;
        padding-left: 0;
    }
    .report-list h4 {
        margin-top: 20px;
        margin-bottom: 10px;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
    }
    .report-list ul {
        list-style-type: disc;
        margin-left: 20px;
    }
    .report-list li a {
        text-decoration: none;
        color: #007bff;
    }
    .report-list li a:hover {
        text-decoration: underline;
    }
</style>
