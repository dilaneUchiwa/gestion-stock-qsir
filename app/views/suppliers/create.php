<?php $title = 'Ajouter un nouveau fournisseur'; ?>

<h2>Ajouter un nouveau fournisseur</h2>

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

<form action="index.php?url=suppliers/store" method="POST">
    <div class="form-group">
        <label for="name">Nom du fournisseur *</label>
        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($data['name'] ?? ''); ?>" required>
    </div>
    <div class="form-group">
        <label for="contact_person">Personne de contact</label>
        <input type="text" name="contact_person" id="contact_person" value="<?php echo htmlspecialchars($data['contact_person'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="email">Adresse e-mail *</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>" required>
    </div>
    <div class="form-group">
        <label for="phone">Numéro de téléphone</label>
        <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($data['phone'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="address">Adresse</label>
        <textarea name="address" id="address" rows="4"><?php echo htmlspecialchars($data['address'] ?? ''); ?></textarea>
    </div>
    <div class="form-group">
        <button type="submit" class="button">Ajouter le fournisseur</button>
        <a href="index.php?url=suppliers" class="button-info">Annuler</a>
    </div>
</form>
