<?php
$title = $title ?? 'Error 403 - Forbidden';
// Ensure $message is set, default if not
$message = $message ?? 'You do not have permission to access this page or perform this action.';
?>

<h2>Error 403 - Forbidden</h2>
<p><?php echo htmlspecialchars($message); ?></p>
<p><a href="index.php?url=products" class="button-info">Go to Homepage</a></p>
