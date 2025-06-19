<?php

class UnitsController extends Controller {
    private $unitModel;

    public function __construct() {
        $this->unitModel = $this->loadModel('Unit');
    }

    public function index() {
        $units = $this->unitModel->getAll();
        $this->renderView('units/index', ['units' => $units]);
    }

    public function create() {
        $this->renderView('units/create');
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_DEFAULT);

            $data = [
                'name' => trim($_POST['name']),
                'symbol' => trim($_POST['symbol']),
                'name_err' => '',
                'symbol_err' => ''
            ];

            if (empty($data['name'])) {
                $data['name_err'] = 'Please enter name';
            }
            if (empty($data['symbol'])) {
                $data['symbol_err'] = 'Please enter symbol';
            }

            if (empty($data['name_err']) && empty($data['symbol_err'])) {
                if ($this->unitModel->create($data)) {
                    // TODO: Implement flash messages
                    header("Location /index.php?url=units&status=created_success");
                    exit;
                } else {
                    header("Location /index.php?url=units&status=created_error");
                    exit;
                }
            } else {
                $this->renderView('units/create', $data);
            }
        } else {
            header("Location: units/create");
            exit;
        }
    }

    public function edit($id) {
        $unit = $this->unitModel->getById($id);

        if (!$unit) {
            // TODO: Implement a proper 404 renderView
            die('Unit not found');
        }

        $data = [
            'id' => $id,
            'name' => $unit->name,
            'symbol' => $unit->symbol,
            'name_err' => '',
            'symbol_err' => ''
        ];

        $this->renderView('units/edit', $data);
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_DEFAULT);

            $data = [
                'id' => $id,
                'name' => trim($_POST['name']),
                'symbol' => trim($_POST['symbol']),
                'name_err' => '',
                'symbol_err' => ''
            ];

            if (empty($data['name'])) {
                $data['name_err'] = 'Please enter name';
            }
            if (empty($data['symbol'])) {
                $data['symbol_err'] = 'Please enter symbol';
            }

            if (empty($data['name_err']) && empty($data['symbol_err'])) {
                if ($this->unitModel->update($id, $data)) {
                    // TODO: Implement flash messages
                    header("Location /index.php?url=units&status=updated_success");
                    exit;
                } else {
                    header("Location /index.php?url=units&status=updated_error");
                    exit;
                }
            } else {
                $this->renderView('units/edit', $data);
            }
        } else {
            header("Location /index.php?url=units");
            exit;
        }
    }

    public function destroy($id) {
        // TODO: Add authorization check
        try {
            if ($this->unitModel->delete($id)) {
                // TODO: Implement flash messages
                header("Location /index.php?url=units&status=deleted_success");
                exit;
            } else {
                header("Location /index.php?url=units&status=deleted_error");
                exit;
            }
        } catch (Exception $e) {
            // TODO: Log the error
            header("Location /index.php?url=units&error=delete_failed_in_use");
            exit;
        }
    }
}
?>
