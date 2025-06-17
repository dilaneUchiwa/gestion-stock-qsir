<?php

class UnitsController extends Controller {
    public function index() {
        $this->view('units/index');
    }

    public function create() {
        $this->view('units/create');
    }
}
?>
