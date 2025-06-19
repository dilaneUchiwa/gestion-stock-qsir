<?php

require_once ROOT_PATH . '/core/Controller.php';

class ClientcategoriesController extends Controller {

    private $clientCategoryModel;

    public function __construct() {
        parent::__construct();
        $this->clientCategoryModel = $this->loadModel('ClientCategory');
    }

    public function index() {
        $categories = $this->clientCategoryModel->getAll();
        $this->renderView('client_categories/index', [
            'title' => 'Catégories de Clients',
            'categories' => $categories
        ]);
    }

    public function create() {
        $this->renderView('client_categories/create', [
            'title' => 'Créer une catégorie de clients',
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
                $this->renderView('client_categories/create', [
                    'title' => 'Créer une catégorie de clients',
                    'data' => $data,
                    'errors' => $errors
                ]);
                return;
            }

            $createdId = $this->clientCategoryModel->create($data);

            if ($createdId) {
                header("Location: /index.php?url=clientcategories/index&status=created_success");
                exit;
            } else {
                $errors['general'] = 'Échec de la création de la catégorie. Le nom est peut-être déjà utilisé.';
                $this->renderView('client_categories/create', [
                    'title' => 'Créer une catégorie de clients',
                    'data' => $data,
                    'errors' => $errors
                ]);
            }
        } else {
            header("Location: /index.php?url=clientcategories/create");
            exit;
        }
    }

    public function edit($id) {
        $category = $this->clientCategoryModel->getById($id);
        if (!$category) {
            $this->renderView('errors/404', ['message' => "Catégorie de client avec l'ID {$id} non trouvée."]);
            return;
        }
        $this->renderView('client_categories/edit', [
            'title' => 'Modifier la catégorie : ' . htmlspecialchars($category['name']),
            'category' => $category,
            'errors' => []
        ]);
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $category = $this->clientCategoryModel->getById($id); // For checking existence
            if (!$category) {
                $this->renderView('errors/404', ['message' => "Catégorie de client avec l'ID {$id} non trouvée pour la mise à jour."]);
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
                $this->renderView('client_categories/edit', [
                    'title' => 'Modifier la catégorie : ' . htmlspecialchars($category['name']),
                    'category' => array_merge($category, $data), // Show submitted data on error
                    'errors' => $errors
                ]);
                return;
            }

            $updatedRows = $this->clientCategoryModel->update($id, $data);

            if ($updatedRows !== false) {
                header("Location: /index.php?url=clientcategories/index&status=updated_success");
                exit;
            } else {
                $errors['general'] = 'Échec de la mise à jour de la catégorie. Le nom est peut-être déjà utilisé par une autre catégorie ou aucune donnée n\'a changé.';
                 $this->renderView('client_categories/edit', [
                    'title' => 'Modifier la catégorie : ' . htmlspecialchars($category['name']),
                    'category' => array_merge($category, $data),
                    'errors' => $errors
                ]);
            }
        } else {
            header("Location: /index.php?url=clientcategories/edit/{$id}");
            exit;
        }
    }

    public function destroy($id) {
        $category = $this->clientCategoryModel->getById($id);
        if (!$category) {
            header("Location: /index.php?url=clientcategories/index&status=delete_not_found");
            exit;
        }

        $deletedRows = $this->clientCategoryModel->delete($id);
        if ($deletedRows) {
            header("Location: /index.php?url=clientcategories/index&status=deleted_success");
            exit;
        } else {
            header("Location: /index.php?url=clientcategories/index&status=delete_failed");
            exit;
        }
    }
}
?>
