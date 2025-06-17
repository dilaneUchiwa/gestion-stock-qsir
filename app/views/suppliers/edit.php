<?php $title = isset($supplier['name']) ? 'Modifier le fournisseur : ' . htmlspecialchars($supplier['name']) : 'Modifier le fournisseur'; ?>

<h2>Modifier le fournisseur : <?php echo htmlspecialchars($supplier['name'] ?? 'N/A'); ?></h2>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <p><strong>Veuillez corriger les erreurs suivantes :</strong></p>
        <ul>
            <?php foreach ($errors as $field => $error): ?>
                <li><?php echo htmlspecialchars($field); ?>: <?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (empty($supplier)): ?>
    <p>Données du fournisseur non trouvées pour la modification.</p>
    <a href="index.php?url=suppliers" class="button-info">Retour à la liste</a>
<?php else: ?>
<form action="index.php?url=suppliers/update/<?php echo htmlspecialchars($supplier['id']); ?>" method="POST">
    <div class="form-group">
        <label for="name">Nom du fournisseur *</label>
        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($supplier['name'] ?? ''); ?>" required>
    </div>
    <div class="form-group">
        <label for="supplier_category_id">Catégorie de fournisseur</label>
        <select name="supplier_category_id" id="supplier_category_id">
            <option value="">Aucune catégorie</option>
            <?php if (!empty($supplier_categories)): ?>
                <?php foreach ($supplier_categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo (isset($supplier['supplier_category_id']) && $supplier['supplier_category_id'] == $category['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="contact_person">Personne de contact</label>
        <input type="text" name="contact_person" id="contact_person" value="<?php echo htmlspecialchars($supplier['contact_person'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="email">Adresse e-mail *</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($supplier['email'] ?? ''); ?>" required>
    </div>
    <div class="form-group">
        <label for="phone">Numéro de téléphone</label>
        <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($supplier['phone'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="address">Adresse</label>
        <textarea name="address" id="address" rows="4"><?php echo htmlspecialchars($supplier['address'] ?? ''); ?></textarea>
    </div>
    <div class="form-group">
        <button type="submit" class="button">Mettre à jour le fournisseur</button>
        <a href="index.php?url=suppliers/show/<?php echo htmlspecialchars($supplier['id']); ?>" class="button-info">Annuler</a>
    </div>
</form>
<?php endif; ?>
