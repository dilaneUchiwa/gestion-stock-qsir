<?php
$pageTitle = $title ?? 'Modifier la catégorie de clients';
$currentCategory = $category ?? [];
$formErrors = $errors ?? [];
?>

<h2><?php echo htmlspecialchars($pageTitle); ?></h2>

<?php if (empty($currentCategory)): ?>
    <div class="alert alert-danger">
        <p>La catégorie que vous essayez de modifier n'a pas été trouvée.</p>
    </div>
    <p><a href="index.php?url=clientcategories/index" class="button button-info">Retour à la liste des catégories</a></p>
<?php else: ?>

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

    <form action="index.php?url=clientcategories/update/<?php echo htmlspecialchars($currentCategory['id']); ?>" method="POST">
        <fieldset>
            <legend>Détails de la catégorie de clients</legend>
            <div class="form-group">
                <label for="name">Nom de la catégorie *</label>
                <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($currentCategory['name'] ?? ''); ?>" required>
                <?php if (isset($formErrors['name'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($formErrors['name']); ?></small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" rows="4"><?php echo htmlspecialchars($currentCategory['description'] ?? ''); ?></textarea>
                <?php if (isset($formErrors['description'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($formErrors['description']); ?></small>
                <?php endif; ?>
            </div>
        </fieldset>

        <div class="form-group mt-3">
            <button type="submit" class="button button-success">Mettre à jour la catégorie</button>
            <a href="index.php?url=clientcategories/index" class="button button-info">Annuler</a>
        </div>
    </form>
<?php endif; ?>

<style>
.error-text {
    color: red;
    font-size: 0.875em;
    display: block;
}
</style>
