<?php

namespace App;

/**
 * Example file with __DIR__ constant in require statements.
 */
class DirConstantExample
{
    /**
     * Example of __DIR__ constant in require statements.
     *
     * @return void
     */
    public function loadDependencies(): void
    {
        // Simple __DIR__ usage
        require_once __DIR__ . '/config.php';
        
        // Nested path with __DIR__
        include_once __DIR__ . '/modules/enabled.php';
        
        // Multiple concatenation with __DIR__
        require __DIR__ . '/../' . 'config.php';
        
        // This should still be detected as dynamic
        $moduleName = 'test';
        require_once __DIR__ . "/modules/{$moduleName}.php";
    }
}
