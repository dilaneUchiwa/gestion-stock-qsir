<?php
$title = 'Error 500 - Internal Server Error';
// Ensure $message is set, default if not
$message = $message ?? 'An unexpected error occurred on the server.';
?>

<h2>Error 500 - Internal Server Error</h2>
<p><?php echo htmlspecialchars($message); ?></p>
<p><a href="index.php?url=products" class="button-info">Go to Homepage</a></p>
