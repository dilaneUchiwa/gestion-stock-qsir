<?php

require_once ROOT_PATH . '/core/Controller.php';

class ProductCategoriesController extends Controller {

    private $productCategoryModel;

    public function __construct() {
        parent::__construct();
        $this->productCategoryModel = $this->loadModel('ProductCategory');
    }

    /**
     * Displays a list of all product categories.
     */
    public function index() {
        $categories = $this->productCategoryModel->getAll();
        $this->renderView('product_categories/index', [
            'title' => 'Catégories de Produits',
            'categories' => $categories
        ]);
    }

    /**
     * Shows the form for creating a new product category.
     */
    public function create() {
        $this->renderView('product_categories/create', [
            'title' => 'Créer une catégorie de produits',
            'data' => [], // For repopulation on error
            'errors' => []
        ]);
    }

    /**
     * Stores a new product category in the database.
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'description' => trim($_POST['description'] ?? '')
            ];
            $errors = [];

            if (empty($data['name'])) {
                $errors['name'] = 'Le nom de la catégorie est requis.';
            }
            // Potential: Check for name uniqueness before attempting insert if model doesn't throw specific error
            // For now, rely on DB constraint and catch PDOException in model if name is not unique.

            if (!empty($errors)) {
                $this->renderView('product_categories/create', [
                    'title' => 'Créer une catégorie de produits',
                    'data' => $data,
                    'errors' => $errors
                ]);
                return;
            }

            $created = $this->productCategoryModel->create($data);

            if ($created) {
                header("Location: /index.php?url=productcategories/index&status=created_success");
                exit;
            } else {
                $errors['general'] = 'Échec de la création de la catégorie. Le nom est peut-être déjà utilisé.';
                $this->renderView('product_categories/create', [
                    'title' => 'Créer une catégorie de produits',
                    'data' => $data,
                    'errors' => $errors
                ]);
            }
        } else {
            header("Location: /index.php?url=productcategories/create");
            exit;
        }
    }

    /**
     * Shows the form for editing an existing product category.
     * @param int $id The ID of the category to edit.
     */
    public function edit($id) {
        $category = $this->productCategoryModel->getById($id);
        if (!$category) {
            $this->renderView('errors/404', ['message' => "Catégorie avec l'ID {$id} non trouvée."]);
            return;
        }
        $this->renderView('product_categories/edit', [
            'title' => 'Modifier la catégorie : ' . htmlspecialchars($category['name']),
            'category' => $category,
            'errors' => []
        ]);
    }

    /**
     * Updates an existing product category in the database.
     * @param int $id The ID of the category to update.
     */
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $category = $this->productCategoryModel->getById($id);
            if (!$category) {
                $this->renderView('errors/404', ['message' => "Catégorie avec l'ID {$id} non trouvée pour la mise à jour."]);
                return;
            }

            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'description' => trim($_POST['description'] ?? '')
            ];
            $errors = [];

            if (empty($data['name'])) {
                $errors['name'] = 'Le nom de la catégorie est requis.';
            }
            // Potential: Check for name uniqueness (ignoring current $id) before attempting update.

            if (!empty($errors)) {
                $this->renderView('product_categories/edit', [
                    'title' => 'Modifier la catégorie : ' . htmlspecialchars($category['name']),
                    'category' => array_merge($category, $data), // Show submitted data on error
                    'errors' => $errors
                ]);
                return;
            }

            $updated = $this->productCategoryModel->update($id, $data);

            if ($updated !== false) { // update returns num affected rows or false
                header("Location: /index.php?url=productcategories/index&status=updated_success");
                exit;
            } else {
                $errors['general'] = 'Échec de la mise à jour de la catégorie. Le nom est peut-être déjà utilisé par une autre catégorie ou aucune donnée n\'a changé.';
                 $this->renderView('product_categories/edit', [
                    'title' => 'Modifier la catégorie : ' . htmlspecialchars($category['name']),
                    'category' => array_merge($category, $data), // Show submitted data
                    'errors' => $errors
                ]);
            }
        } else {
            header("Location: /index.php?url=productcategories/edit/{$id}");
            exit;
        }
    }

    /**
     * Deletes a product category from the database.
     * @param int $id The ID of the category to delete.
     */
    public function destroy($id) {
        // It's better to handle deletion via POST request to prevent CSRF,
        // but for simplicity as per instructions, a GET request is handled here.
        // A proper implementation would have a form with POST method for deletion.

        $category = $this->productCategoryModel->getById($id);
        if (!$category) {
            header("Location: /index.php?url=productcategories/index&status=delete_not_found");
            exit;
        }

        try {
            $deleted = $this->productCategoryModel->delete($id);
            if ($deleted) {
                header("Location: /index.php?url=productcategories/index&status=deleted_success");
                exit;
            } else {
                header("Location: /index.php?url=productcategories/index&status=delete_failed");
                exit;
            }
        } catch (Exception $e) {
             error_log("Error deleting category ID {$id}: " . $e->getMessage());
             // The model might throw an exception if deletion is blocked by FK that isn't ON DELETE SET NULL
             // (though product_categories is referenced by products.category_id which IS ON DELETE SET NULL)
             // This catch is more for unexpected issues.
            header("Location: /index.php?url=productcategories/index&status=delete_error&message=" . urlencode($e->getMessage()));
            exit;
        }
    }
}

?>
