<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/Controller.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/Database.php'; // Required for loadModel

class ClientsController extends Controller {

    private $clientModel;

    public function __construct() {
        parent::__construct();
        $this->clientModel = $this->loadModel('Client');
    }

    /**
     * Displays a list of all clients.
     */
    public function index() {
        $clients = $this->clientModel->getAll();
        $this->renderView('clients/index', ['clients' => $clients]);
    }

    /**
     * Displays a single client by its ID.
     * @param int $id The ID of the client.
     */
    public function show($id) {
        $client = $this->clientModel->getById($id);
        if ($client) {
            $this->renderView('clients/show', ['client' => $client]);
        } else {
            $this->renderView('errors/404', ['message' => "Client avec l'ID {$id} non trouvé."]);
        }
    }

    /**
     * Shows the form for creating a new client.
     */
    public function create() {
        $this->renderView('clients/create', ['allowedClientTypes' => $this->clientModel->allowedClientTypes]);
    }

    /**
     * Stores a new client in the database.
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $_POST['name'] ?? '',
                'client_type' => $_POST['client_type'] ?? 'connu',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'address' => $_POST['address'] ?? '',
            ];

            $errors = [];
            if (empty($data['name'])) {
                $errors['name'] = 'Le nom du client est requis.';
            }
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = "Format d'email invalide.";
            }
            if (!in_array($data['client_type'], $this->clientModel->allowedClientTypes)) {
                 $errors['client_type'] = 'Type de client sélectionné invalide.';
            }
            // Add more validation as needed

            if (!empty($errors)) {
                $this->renderView('clients/create', ['errors' => $errors, 'data' => $data, 'allowedClientTypes' => $this->clientModel->allowedClientTypes]);
                return;
            }

            $clientId = $this->clientModel->create($data);

            if ($clientId) {
                header("Location: /index.php?url=clients&status=created_success");
                exit;
            } else {
                $errors['general'] = "Échec de la création du client. L'e-mail existe peut-être déjà.";
                $this->renderView('clients/create', ['errors' => $errors, 'data' => $data, 'allowedClientTypes' => $this->clientModel->allowedClientTypes]);
            }
        } else {
            header("Location: /index.php?url=clients/create");
            exit;
        }
    }

    /**
     * Shows the form for editing an existing client.
     * @param int $id The ID of the client to edit.
     */
    public function edit($id) {
        $client = $this->clientModel->getById($id);
        if ($client) {
            $this->renderView('clients/edit', ['client' => $client, 'allowedClientTypes' => $this->clientModel->allowedClientTypes]);
        } else {
             $this->renderView('errors/404', ['message' => "Client avec l'ID {$id} non trouvé pour la modification."]);
        }
    }

    /**
     * Updates an existing client in the database.
     * @param int $id The ID of the client to update.
     */
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $_POST['name'] ?? '',
                'client_type' => $_POST['client_type'] ?? 'connu',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'address' => $_POST['address'] ?? '',
            ];

            $errors = [];
            if (empty($data['name'])) {
                $errors['name'] = 'Le nom du client est requis.';
            }
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = "Format d'email invalide.";
            }
             if (!in_array($data['client_type'], $this->clientModel->allowedClientTypes)) {
                 $errors['client_type'] = 'Type de client sélectionné invalide.';
            }


            if (!empty($errors)) {
                $currentClientData = $this->clientModel->getById($id);
                $this->renderView('clients/edit', ['errors' => $errors, 'client' => array_merge((array)$currentClientData, $data), 'allowedClientTypes' => $this->clientModel->allowedClientTypes]);
                return;
            }

            $affectedRows = $this->clientModel->update($id, $data);

            if ($affectedRows !== false) {
                header("Location: /index.php?url=clients/show/{$id}&status=updated_success");
                exit;
            } else {
                $errors['general'] = "Échec de la mise à jour du client. L\'e-mail existe peut-être déjà ou aucune donnée n\'a été modifiée.";
                $currentClientData = $this->clientModel->getById($id);
                $this->renderView('clients/edit', ['errors' => $errors, 'client' => array_merge((array)$currentClientData, $data), 'allowedClientTypes' => $this->clientModel->allowedClientTypes]);
            }
        } else {
            header("Location: /index.php?url=clients/edit/{$id}");
            exit;
        }
    }

    /**
     * Deletes a client from the database.
     * @param int $id The ID of the client to delete.
     */
    public function destroy($id) {
        $deleted = $this->clientModel->delete($id);
        if ($deleted) {
            header("Location: /index.php?url=clients&status=deleted_success");
            exit;
        } else {
            // This could be due to FK constraints (client has sales orders etc)
            header("Location: /index.php?url=clients&status=deleted_error");
            exit;
        }
    }
}
?>
