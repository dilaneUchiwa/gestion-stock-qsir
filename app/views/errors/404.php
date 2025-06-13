<?php
$title = 'Erreur 404 - Page non trouvée';
// Ensure $message is set, default if not
$message = $message ?? 'La page que vous recherchez n\'a pas pu être trouvée.';
?>

<h2>Erreur 404 - Page non trouvée</h2>
<p><?php echo htmlspecialchars($message); ?></p>
<p><a href="index.php?url=products" class="button-info">Aller à la page d'accueil</a></p>
