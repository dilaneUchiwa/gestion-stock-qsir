<?php
// $title is passed from controller
$pageTitle = $title ?? 'Créer une catégorie de produits';
$currentData = $data ?? []; // Repopulation data from controller
$formErrors = $errors ?? []; // Validation errors from controller
?>

<h2><?php echo htmlspecialchars($pageTitle); ?></h2>

<?php if (!empty($formErrors)): ?>
    <div class="alert alert-danger">
        <p><strong>Veuillez corriger les erreurs suivantes :</strong></p>
        <ul>
            <?php foreach ($formErrors as $field => $error): ?>
                <li><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $field))); ?>: <?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="index.php?url=productcategories/store" method="POST">
    <fieldset>
        <legend>Détails de la catégorie</legend>
        <div class="form-group">
            <label for="name">Nom de la catégorie *</label>
            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($currentData['name'] ?? ''); ?>" required>
            <?php if (isset($formErrors['name'])): ?>
                <small class="error-text"><?php echo htmlspecialchars($formErrors['name']); ?></small>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" rows="4"><?php echo htmlspecialchars($currentData['description'] ?? ''); ?></textarea>
            <?php if (isset($formErrors['description'])): ?>
                <small class="error-text"><?php echo htmlspecialchars($formErrors['description']); ?></small>
            <?php endif; ?>
        </div>
    </fieldset>

    <div class="form-group mt-3">
        <button type="submit" class="button button-success">Enregistrer la catégorie</button>
        <a href="index.php?url=productcategories/index" class="button button-info">Annuler</a>
    </div>
</form>

<style>
.error-text {
    color: red;
    font-size: 0.875em;
}
</style>
