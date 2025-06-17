<?php $title = isset($supplier['name']) ? 'Détails du fournisseur : ' . htmlspecialchars($supplier['name']) : 'Détails du fournisseur'; ?>

<?php if ($supplier): ?>
    <h2><?php echo htmlspecialchars($supplier['name']); ?></h2>
    <p><strong>ID :</strong> <?php echo htmlspecialchars($supplier['id']); ?></p>
    <p><strong>Catégorie :</strong> <?php echo htmlspecialchars($supplier['supplier_category_name'] ?? 'N/A'); ?></p>
    <p><strong>Personne de contact :</strong> <?php echo htmlspecialchars($supplier['contact_person'] ?? 'N/A'); ?></p>
    <p><strong>Email :</strong> <?php echo htmlspecialchars($supplier['email']); ?></p>
    <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($supplier['phone'] ?? 'N/A'); ?></p>
    <p><strong>Adresse :</strong> <?php echo nl2br(htmlspecialchars($supplier['address'] ?? 'N/A')); ?></p>
    <p><strong>Enregistré le :</strong> <?php echo htmlspecialchars($supplier['created_at']); ?></p>
    <p><strong>Dernière mise à jour :</strong> <?php echo htmlspecialchars($supplier['updated_at']); ?></p>

    <p style="margin-top: 20px;">
        <a href="index.php?url=suppliers/edit/<?php echo $supplier['id']; ?>" class="button">Modifier le fournisseur</a>
        <a href="index.php?url=suppliers" class="button-info">Retour à la liste</a>
    </p>
<?php else: ?>
    <p>Fournisseur non trouvé.</p>
    <a href="index.php?url=suppliers" class="button-info">Retour à la liste</a>
<?php endif; ?>
