<?php
$title = 'Error 404 - Not Found';
// Ensure $message is set, default if not
$message = $message ?? 'The page you are looking for could not be found.';
?>

<h2>Error 404 - Not Found</h2>
<p><?php echo htmlspecialchars($message); ?></p>
<p><a href="index.php?url=products" class="button-info">Go to Homepage</a></p>
