<?php

require_once ROOT_PATH . '/core/Controller.php';

class SuppliercategoriesController extends Controller {

    private $supplierCategoryModel;

    public function __construct() {
        parent::__construct();
        $this->supplierCategoryModel = $this->loadModel('SupplierCategory');
    }

    public function index() {
        $categories = $this->supplierCategoryModel->getAll();
        $this->renderView('supplier_categories/index', [
            'title' => 'Catégories de Fournisseurs',
            'categories' => $categories
        ]);
    }

    public function create() {
        $this->renderView('supplier_categories/create', [
            'title' => 'Créer une catégorie de fournisseurs',
            'data' => [],
            'errors' => []
        ]);
    }

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

            if (!empty($errors)) {
                $this->renderView('supplier_categories/create', [
                    'title' => 'Créer une catégorie de fournisseurs',
                    'data' => $data,
                    'errors' => $errors
                ]);
                return;
            }

            $createdId = $this->supplierCategoryModel->create($data);

            if ($createdId) {
                header("Location: /index.php?url=suppliercategories/index&status=created_success");
                exit;
            } else {
                $errors['general'] = 'Échec de la création de la catégorie. Le nom est peut-être déjà utilisé.';
                $this->renderView('supplier_categories/create', [
                    'title' => 'Créer une catégorie de fournisseurs',
                    'data' => $data,
                    'errors' => $errors
                ]);
            }
        } else {
            header("Location: /index.php?url=suppliercategories/create");
            exit;
        }
    }

    public function edit($id) {
        $category = $this->supplierCategoryModel->getById($id);
        if (!$category) {
            $this->renderView('errors/404', ['message' => "Catégorie de fournisseur avec l'ID {$id} non trouvée."]);
            return;
        }
        $this->renderView('supplier_categories/edit', [
            'title' => 'Modifier la catégorie : ' . htmlspecialchars($category['name']),
            'category' => $category,
            'errors' => []
        ]);
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $category = $this->supplierCategoryModel->getById($id);
            if (!$category) {
                $this->renderView('errors/404', ['message' => "Catégorie de fournisseur avec l'ID {$id} non trouvée pour la mise à jour."]);
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

            if (!empty($errors)) {
                $this->renderView('supplier_categories/edit', [
                    'title' => 'Modifier la catégorie : ' . htmlspecialchars($category['name']),
                    'category' => array_merge($category, $data),
                    'errors' => $errors
                ]);
                return;
            }

            $updatedRows = $this->supplierCategoryModel->update($id, $data);

            if ($updatedRows !== false) {
                header("Location: /index.php?url=suppliercategories/index&status=updated_success");
                exit;
            } else {
                $errors['general'] = 'Échec de la mise à jour de la catégorie. Le nom est peut-être déjà utilisé par une autre catégorie ou aucune donnée n\'a changé.';
                 $this->renderView('supplier_categories/edit', [
                    'title' => 'Modifier la catégorie : ' . htmlspecialchars($category['name']),
                    'category' => array_merge($category, $data),
                    'errors' => $errors
                ]);
            }
        } else {
            header("Location: /index.php?url=suppliercategories/edit/{$id}");
            exit;
        }
    }

    public function destroy($id) {
        $category = $this->supplierCategoryModel->getById($id);
        if (!$category) {
            header("Location: /index.php?url=suppliercategories/index&status=delete_not_found");
            exit;
        }

        $deletedRows = $this->supplierCategoryModel->delete($id);
        if ($deletedRows) {
            header("Location: /index.php?url=suppliercategories/index&status=deleted_success");
            exit;
        } else {
            header("Location: /index.php?url=suppliercategories/index&status=delete_failed");
            exit;
        }
    }
}
?>
