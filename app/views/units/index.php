<div class="container mt-5">
    <h2>Gestion des Unités</h2>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'created_success'): ?>
        <div class="alert alert-success" role="alert">
            Unité créée avec succès!
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['status']) && $_GET['status'] == 'updated_success'): ?>
        <div class="alert alert-success" role="alert">
            Unité mise à jour avec succès!
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['status']) && $_GET['status'] == 'deleted_success'): ?>
        <div class="alert alert-success" role="alert">
            Unité supprimée avec succès!
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger" role="alert">
            Erreur: <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <a href="index.php?url=units/create" class="button button-success">Créer une unité</a>

    <?php if (empty($data['units'])): ?>
        <p>Aucune unité trouvée.</p>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Symbole</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['units'] as $unit): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($unit['name']); ?></td>
                        <td><?php echo htmlspecialchars($unit['symbol']); ?></td>
                        <td>
                            <a href="index.php?url=units/edit/<?php echo $unit['id']; ?>" class="button btn-sm">Modifier</a>
                            <a href="index.php?url=units/destroy/<?php echo $unit['id']; ?>" class="button btn-sm button-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette unité?');">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
