<?php $title = 'Ajouter un nouveau client'; ?>

<h2>Ajouter un nouveau client</h2>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <p><strong>Veuillez corriger les erreurs suivantes :</strong></p>
        <ul>
            <?php foreach ($errors as $field => $error): ?>
                <li><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $field))); ?>: <?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="index.php?url=clients/store" method="POST">
    <div class="form-group">
        <label for="name">Nom du client *</label>
        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($data['name'] ?? ''); ?>" required>
    </div>
    <div class="form-group">
        <label for="client_type">Type de client *</label>
        <select name="client_type" id="client_type" required>
            <?php
            $currentType = $data['client_type'] ?? 'connu'; // Default to 'connu'
            foreach ($allowedClientTypes as $type): ?>
                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($currentType == $type) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(ucfirst($type)); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="email">Adresse e-mail</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>">
        <small>Requis si le type de client est 'connu', unique si fourni.</small>
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
        <button type="submit" class="button">Ajouter le client</button>
        <a href="index.php?url=clients" class="button-info">Annuler</a>
    </div>
</form>
