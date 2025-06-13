<?php
$title = 'Erreur 500 - Erreur interne du serveur';
// Ensure $message is set, default if not
$message = $message ?? 'Une erreur inattendue s\'est produite sur le serveur.';
?>

<h2>Erreur 500 - Erreur interne du serveur</h2>
<p><?php echo htmlspecialchars($message); ?></p>
<p><a href="index.php?url=products" class="button-info">Aller à la page d'accueil</a></p>
