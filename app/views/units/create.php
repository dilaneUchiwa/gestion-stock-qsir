<div class="container mt-5">
    <h2>Créer une Unité</h2>
    <form action="index.php?url=units/store" method="POST">
        <div class="form-group">
            <label for="name">Nom</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="symbol">Symbole</label>
            <input type="text" class="form-control" id="symbol" name="symbol" required>
        </div>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="index.php?url=units" class="btn btn-secondary">Annuler</a>
    </form>
</div>
