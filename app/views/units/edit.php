<div class="container mt-5">
    <h2>Modifier l'Unité</h2>
    <form action="index.php?url=units/update/<?php echo $data['id']; ?>" method="POST">
        <div class="form-group">
            <label for="name">Nom</label>
            <input type="text" class="form-control <?php echo (!empty($data['name_err'])) ? 'is-invalid' : ''; ?>" id="name" name="name" value="<?php echo isset($data['name']) ? htmlspecialchars($data['name']) : ''; ?>" required>
            <?php if (!empty($data['name_err'])): ?>
                <span class="invalid-feedback"><?php echo $data['name_err']; ?></span>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="symbol">Symbole</label>
            <input type="text" class="form-control <?php echo (!empty($data['symbol_err'])) ? 'is-invalid' : ''; ?>" id="symbol" name="symbol" value="<?php echo isset($data['symbol']) ? htmlspecialchars($data['symbol']) : ''; ?>" required>
            <?php if (!empty($data['symbol_err'])): ?>
                <span class="invalid-feedback"><?php echo $data['symbol_err']; ?></span>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary">Mettre à jour</button>
        <a href="index.php?url=units" class="btn btn-secondary">Annuler</a>
    </form>
</div>
