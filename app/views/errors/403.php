<?php
$title = $title ?? 'Erreur 403 - Accès refusé';
// Ensure $message is set, default if not
$message = $message ?? 'Vous n\'avez pas la permission d\'accéder à cette page ou d\'effectuer cette action.';
?>

<h2>Erreur 403 - Accès refusé</h2>
<p><?php echo htmlspecialchars($message); ?></p>
<p><a href="index.php?url=products" class="button-info">Aller à la page d'accueil</a></p>
