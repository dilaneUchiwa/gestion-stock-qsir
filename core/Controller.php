<?php

class Controller {
    protected $db;

    public function __construct() {
        // Instantiating Database connection here if all controllers need it
        // Alternatively, it can be passed to methods or specific controllers
        // For now, let's assume it might be useful for many controllers
        // $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Loads a model.
     * @param string $modelName The name of the model to load (e.g., 'User').
     * @return object The model instance.
     * @throws Exception if the model file does not exist.
     */
    public function loadModel($modelName) {
        $modelFile = $_SERVER['DOCUMENT_ROOT'] . '/app/models/' . ucfirst($modelName) . '.php';
        if (file_exists($modelFile)) {
            require_once $modelFile;
            // Ensure the model class name matches the file name convention
            $className = ucfirst($modelName);
            if (class_exists($className)) {
                // Pass the database connection to the model's constructor
                $dbInstance = Database::getInstance();
                return new $className($dbInstance);
            } else {
                throw new Exception("Model class {$className} not found in {$modelFile}.");
            }
        } else {
            throw new Exception("Model file {$modelFile} not found.");
        }
    }

    /**
     * Renders a view.
     * @param string $viewName The name of the view file (e.g., 'home/index').
     *                         This corresponds to app/views/home/index.php.
     * @param array $data Data to pass to the view.
     * @throws Exception if the view file does not exist.
     */
    public function renderView($viewName, $data = []) {
        $viewFile = $_SERVER['DOCUMENT_ROOT'] . '/app/views/' . $viewName . '.php';

        if (file_exists($viewFile)) {
            // Extract data array to variables for easy access in the view
            extract($data);

            // Start output buffering
            ob_start();

            // Include the view file
            require $viewFile;

            // Get the content of the buffer and clean it
            $viewContent = ob_get_clean();

            // Load the main layout
            $layoutFile = $_SERVER['DOCUMENT_ROOT'] . '/app/views/layouts/main.php';
            if (file_exists($layoutFile)) {
                // Pass view content and data to the layout
                // Data originally passed to the view is also available in the layout
                $content = $viewContent; // Variable name expected by layout
                extract($data); // Make original data available to layout as well

                ob_start();
                require $layoutFile;
                echo ob_get_clean();
            } else {
                // Fallback if layout is not found, just echo the view content
                echo $viewContent;
            }
        } else {
            // Try to render a generic 404 page if the view itself is not found
            $errorViewFile = $_SERVER['DOCUMENT_ROOT'] . '/app/views/errors/404.php';
            if (file_exists($errorViewFile) && $viewName !== 'errors/404') { // Avoid infinite loop
                extract(['message' => "View file {$viewFile} not found."]);
                ob_start();
                require $errorViewFile;
                $viewContent = ob_get_clean();

                $layoutFile = $_SERVER['DOCUMENT_ROOT'] . '/app/views/layouts/main.php';
                if (file_exists($layoutFile)) {
                    $content = $viewContent;
                    $title = "Error - Not Found";
                    require $layoutFile;
                } else {
                    echo $viewContent; // Fallback if layout also not found
                }

            } else {
                 throw new Exception("View file {$viewFile} not found and error page could not be rendered.");
            }
        }
    }
}
?>
