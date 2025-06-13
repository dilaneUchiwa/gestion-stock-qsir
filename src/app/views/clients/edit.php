<?php $title = isset($client['name']) ? 'Modifier le client : ' . htmlspecialchars($client['name']) : 'Modifier le client'; ?>

<h2>Modifier le client : <?php echo htmlspecialchars($client['name'] ?? 'N/A'); ?></h2>

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

<?php if (empty($client)): ?>
    <p>Données client non trouvées pour la modification.</p>
    <a href="index.php?url=clients" class="button-info">Retour à la liste</a>
<?php else: ?>
<form action="index.php?url=clients/update/<?php echo htmlspecialchars($client['id']); ?>" method="POST">
    <div class="form-group">
        <label for="name">Nom du client *</label>
        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($client['name'] ?? ''); ?>" required>
    </div>
     <div class="form-group">
        <label for="client_type">Type de client *</label>
        <select name="client_type" id="client_type" required>
             <?php
            $currentType = $client['client_type'] ?? 'connu';
            foreach ($allowedClientTypes as $type): ?>
                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($currentType == $type) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(ucfirst($type)); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="email">Adresse e-mail</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($client['email'] ?? ''); ?>">
        <small>Requis si le type de client est 'connu', unique si fourni.</small>
    </div>
    <div class="form-group">
        <label for="phone">Numéro de téléphone</label>
        <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($client['phone'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="address">Adresse</label>
        <textarea name="address" id="address" rows="4"><?php echo htmlspecialchars($client['address'] ?? ''); ?></textarea>
    </div>
    <div class="form-group">
        <button type="submit" class="button">Mettre à jour le client</button>
        <a href="index.php?url=clients/show/<?php echo htmlspecialchars($client['id']); ?>" class="button-info">Annuler</a>
    </div>
</form>
<?php endif; ?>
