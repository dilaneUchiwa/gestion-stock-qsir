<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/Controller.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/Database.php'; // Required for loadModel

class SuppliersController extends Controller {

    private $supplierModel;

    public function __construct() {
        parent::__construct();
        $this->supplierModel = $this->loadModel('Supplier');
    }

    /**
     * Displays a list of all suppliers.
     */
    public function index() {
        $suppliers = $this->supplierModel->getAll();
        // The title for the layout will be set in the view
        $this->renderView('suppliers/index', ['suppliers' => $suppliers]);
    }

    /**
     * Displays a single supplier by its ID.
     * @param int $id The ID of the supplier.
     */
    public function show($id) {
        $supplier = $this->supplierModel->getById($id);
        if ($supplier) {
            $this->renderView('suppliers/show', ['supplier' => $supplier]);
        } else {
            $this->renderView('errors/404', ['message' => "Fournisseur avec l'ID {$id} non trouvé."]);
        }
    }

    /**
     * Shows the form for creating a new supplier.
     */
    public function create() {
        $this->renderView('suppliers/create', []);
    }

    /**
     * Stores a new supplier in the database.
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $_POST['name'] ?? '',
                'contact_person' => $_POST['contact_person'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'address' => $_POST['address'] ?? '',
            ];

            // Basic Server-side Validation
            $errors = [];
            if (empty($data['name'])) {
                $errors['name'] = 'Le nom du fournisseur est requis.';
            }
            if (empty($data['email'])) {
                $errors['email'] = 'L\'email est requis.';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Format d\'email invalide.';
            }
            // Add more validation as needed (e.g. phone format)

            if (!empty($errors)) {
                $this->renderView('suppliers/create', ['errors' => $errors, 'data' => $data]);
                return;
            }

            $supplierId = $this->supplierModel->create($data);

            if ($supplierId) {
                // Redirect to the supplier index page (or the new supplier's page)
                // Consider adding a success message via session flash data
                header("Location: /index.php?url=suppliers"); // Adjust URL as per routing
                exit;
            } else {
                // Handle creation failure (e.g. email already exists)
                $errors['general'] = "Échec de la création du fournisseur. L'e-mail existe peut-être déjà.";
                $this->renderView('suppliers/create', ['errors' => $errors, 'data' => $data]);
            }
        } else {
            // Not a POST request, redirect or show error
            header("Location: /index.php?url=suppliers/create"); // Adjust URL
            exit;
        }
    }

    /**
     * Shows the form for editing an existing supplier.
     * @param int $id The ID of the supplier to edit.
     */
    public function edit($id) {
        $supplier = $this->supplierModel->getById($id);
        if ($supplier) {
            $this->renderView('suppliers/edit', ['supplier' => $supplier]);
        } else {
             $this->renderView('errors/404', ['message' => "Fournisseur avec l'ID {$id} non trouvé pour la modification."]);
        }
    }

    /**
     * Updates an existing supplier in the database.
     * @param int $id The ID of the supplier to update.
     */
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $_POST['name'] ?? '',
                'contact_person' => $_POST['contact_person'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'address' => $_POST['address'] ?? '',
            ];

            $errors = [];
            if (empty($data['name'])) {
                $errors['name'] = 'Le nom du fournisseur est requis.';
            }
            if (empty($data['email'])) {
                $errors['email'] = 'L\'email est requis.';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Format d\'email invalide.';
            }

            if (!empty($errors)) {
                // Preserve existing supplier data for the form if some fields are invalid
                $currentSupplierData = $this->supplierModel->getById($id);
                $this->renderView('suppliers/edit', ['errors' => $errors, 'supplier' => array_merge((array)$currentSupplierData, $data)]);
                return;
            }

            $affectedRows = $this->supplierModel->update($id, $data);

            if ($affectedRows !== false) {
                header("Location: /index.php?url=suppliers/show/{$id}"); // Adjust URL
                exit;
            } else {
                $errors['general'] = "Échec de la mise à jour du fournisseur. L'e-mail existe peut-être déjà ou aucune donnée n'a été modifiée.";
                $currentSupplierData = $this->supplierModel->getById($id);
                $this->renderView('suppliers/edit', ['errors' => $errors, 'supplier' => array_merge((array)$currentSupplierData, $data)]);
            }
        } else {
            header("Location: /index.php?url=suppliers/edit/{$id}"); // Adjust URL
            exit;
        }
    }

    /**
     * Deletes a supplier from the database.
     * @param int $id The ID of the supplier to delete.
     */
    public function destroy($id) {
        // Add CSRF token and confirmation for real applications
        $deleted = $this->supplierModel->delete($id);
        if ($deleted) {
            // Consider adding a success message via session flash data
            header("Location: /index.php?url=suppliers&status=deleted_success"); // Adjust URL
            exit;
        } else {
            // Handle deletion failure (e.g. supplier has related records)
            // Redirect with error or show error message
            // For now, redirecting to index. A flash message system would be better.
            header("Location: /index.php?url=suppliers&status=deleted_error"); // Adjust URL
            exit;
        }
    }
}
?>
