<?php

/**
 * A real-world example of PHP file with various include patterns.
 */

// Autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Configuration
require_once __DIR__ . '/config.php';

// Include from parent directory
require_once __DIR__ . '/../config.php';

// Include from a sibling directory
require_once __DIR__ . '/../modules/core.php';

// Include with multiple concatenations
require_once __DIR__ . '/../' . 'modules/' . 'auth.php';

// Include with variable (dynamic)
$module = 'api';
require_once __DIR__ . "/../modules/{$module}.php";

// Class definition
class RealWorldExample
{
    public function __construct()
    {
        // Include inside a method
        require_once __DIR__ . '/helpers/formatter.php';
        
        // Include with variable (dynamic)
        $helper = 'validator';
        require_once __DIR__ . "/helpers/{$helper}.php";
    }
    
    public function loadModule($name)
    {
        // Dynamic include
        require_once __DIR__ . "/modules/{$name}.php";
    }
}
