<?php

class UnitsController extends Controller {
    private $unitModel;

    public function __construct() {
        $this->unitModel = $this->loadModel('Unit');
    }

    public function index() {
        $units = $this->unitModel->getAll();
        $this->view('units/index', ['units' => $units]);
    }

    public function create() {
        $this->view('units/create');
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

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
                    redirect('units/index?status=created_success');
                } else {
                    redirect('units/index?status=created_error');
                }
            } else {
                $this->view('units/create', $data);
            }
        } else {
            redirect('units/create');
        }
    }

    public function edit($id) {
        $unit = $this->unitModel->getById($id);

        if (!$unit) {
            // TODO: Implement a proper 404 view
            die('Unit not found');
        }

        $data = [
            'id' => $id,
            'name' => $unit->name,
            'symbol' => $unit->symbol,
            'name_err' => '',
            'symbol_err' => ''
        ];

        $this->view('units/edit', $data);
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

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
                    redirect('units/index?status=updated_success');
                } else {
                    redirect('units/index?status=updated_error');
                }
            } else {
                $this->view('units/edit', $data);
            }
        } else {
            redirect('units/index');
        }
    }

    public function destroy($id) {
        // TODO: Add authorization check
        try {
            if ($this->unitModel->delete($id)) {
                // TODO: Implement flash messages
                redirect('units/index?status=deleted_success');
            } else {
                redirect('units/index?status=deleted_error');
            }
        } catch (Exception $e) {
            // TODO: Log the error
            redirect('units/index?error=delete_failed_in_use');
        }
    }
}
?>
