<?php

// Basic error reporting - turn off in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('ROOT_PATH',__DIR__);

// Load application configuration
$appConfig = require_once ROOT_PATH . '/config/app.php';
define('APP_CURRENCY_SYMBOL', $appConfig['currency_symbol']);

// Autoload Core Classes (simple autoloader)
spl_autoload_register(function ($className) {
    $corePath = ROOT_PATH . '/core/' . $className . '.php';
    $controllerPath = ROOT_PATH . '/app/controllers/' . $className . '.php';
    $modelPath = ROOT_PATH . '/app/models/' . $className . '.php';

    if (file_exists($corePath)) {
        require_once $corePath;
    } elseif (file_exists($controllerPath)) {
        require_once $controllerPath;
    } elseif (file_exists($modelPath)) {
        // Models are usually loaded by the controller, but good to have for direct instantiation if ever needed
        require_once $modelPath;
    }
});

// Simple Routing
$url = $_GET['url'] ?? 'products'; // Default to products index
$urlParts = explode('/', filter_var(rtrim($url, '/'), FILTER_SANITIZE_URL));

$controllerName = ucfirst($urlParts[0] ?? 'Products') . 'Controller'; // Default to ProductsController
$methodName = $urlParts[1] ?? 'index'; // Default to index method
$params = array_slice($urlParts, 2);

// Prepend path to controller
$controllerFile = ROOT_PATH . '/app/controllers/' . $controllerName . '.php';

if (file_exists($controllerFile)) {
    require_once $controllerFile;
    if (class_exists($controllerName)) {
        $controller = new $controllerName();
        if (method_exists($controller, $methodName)) {
            // Call the method, passing parameters if any
            call_user_func_array([$controller, $methodName], $params);
        } else {
            // Method not found - Render a 404 or redirect
            // For simplicity, using a basic error message. A dedicated error controller/view is better.
            header("HTTP/1.0 404 Not Found");
            $errorController = new Controller(); // Base controller for renderView
            $errorController->renderView('errors/404', ['message' => "Method {$methodName} not found in controller {$controllerName}."]);
        }
    } else {
        // Controller class not found
        header("HTTP/1.0 404 Not Found");
        $errorController = new Controller();
        $errorController->renderView('errors/404', ['message' => "Controller class {$controllerName} not found."]);
    }
} else {
    // Controller file not found
    header("HTTP/1.0 404 Not Found");
    // Attempt to load a generic controller to render the 404 page via the layout
    // This assumes Controller.php is already loaded by spl_autoload_register or explicitly required
    if (!class_exists('Controller')) { // Ensure Controller class is available
        require_once ROOT_PATH . '/core/Controller.php';
    }
    $errorController = new Controller();
    $errorController->renderView('errors/404', ['message' => "Controller file {$controllerName}.php not found."]);
}

?>
