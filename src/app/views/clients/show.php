<?php $title = isset($client['name']) ? 'Détails du client : ' . htmlspecialchars($client['name']) : 'Détails du client'; ?>

<?php if (isset($_GET['status']) && $_GET['status'] == 'updated_success'): ?>
    <div class="alert alert-success">Client mis à jour avec succès.</div>
<?php endif; ?>

<?php if ($client): ?>
    <h2><?php echo htmlspecialchars($client['name']); ?></h2>
    <p><strong>ID:</strong> <?php echo htmlspecialchars($client['id']); ?></p>
    <p><strong>Type de client:</strong> <?php echo htmlspecialchars(ucfirst($client['client_type'])); ?></p>
    <p><strong>E-mail:</strong> <?php echo htmlspecialchars($client['email'] ?? 'N/A'); ?></p>
    <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($client['phone'] ?? 'N/A'); ?></p>
    <p><strong>Adresse:</strong> <?php echo nl2br(htmlspecialchars($client['address'] ?? 'N/A')); ?></p>
    <p><strong>Inscrit le:</strong> <?php echo htmlspecialchars($client['created_at']); ?></p>
    <p><strong>Dernière mise à jour:</strong> <?php echo htmlspecialchars($client['updated_at']); ?></p>

    <p style="margin-top: 20px;">
        <a href="index.php?url=clients/edit/<?php echo $client['id']; ?>" class="button">Modifier le client</a>
        <a href="index.php?url=clients" class="button-info">Retour à la liste</a>
    </p>
<?php else: ?>
    <p>Client non trouvé.</p>
    <a href="index.php?url=clients" class="button-info">Retour à la liste</a>
<?php endif; ?>
