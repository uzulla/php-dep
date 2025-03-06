<?php

namespace App;

/**
 * Example file with dynamic require statements.
 */
class DynamicRequireExample
{
    /**
     * Example of dynamic require statements.
     *
     * @param string $moduleName
     * @return void
     */
    public function loadModule(string $moduleName): void
    {
        // Dynamic require with variable
        require_once "modules/{$moduleName}.php";
        
        // Dynamic require with concatenation
        include_once 'modules/' . $moduleName . '.class.php';
        
        // Dynamic require with variable in path
        $basePath = 'modules';
        require "{$basePath}/{$moduleName}/init.php";
        
        // Static require (should be detected)
        require_once 'config.php';
    }
    
    /**
     * Example of conditional require statements.
     *
     * @param bool $condition
     * @return void
     */
    public function conditionalLoad(bool $condition): void
    {
        if ($condition) {
            require_once 'modules/enabled.php';
        } else {
            require_once 'modules/disabled.php';
        }
        
        // Ternary operator
        require_once $condition ? 'modules/true.php' : 'modules/false.php';
    }
}
