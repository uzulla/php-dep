<?php

namespace PhpDep\Tests;

use PhpDep\DependencyAnalyzer;
use PhpDep\Parser\PhpFileParser;
use PHPUnit\Framework\TestCase;

class DirConstantTest extends TestCase
{
    /**
     * Test that __DIR__ constant in require statements is resolved.
     */
    public function testDirConstantResolution()
    {
        // Create a temporary directory for test files
        $tempDir = sys_get_temp_dir() . '/php-dep-test-' . uniqid();
        mkdir($tempDir, 0755, true);
        
        try {
            // Create test files
            $mainFile = $tempDir . '/main.php';
            $configFile = $tempDir . '/config.php';
            $modulesDir = $tempDir . '/modules';
            mkdir($modulesDir, 0755, true);
            $moduleFile = $modulesDir . '/module.php';
            
            // Create main file with __DIR__ constant in require statements
            file_put_contents($mainFile, '<?php
                // Simple __DIR__ usage
                require_once __DIR__ . "/config.php";
                
                // Nested path with __DIR__
                include_once __DIR__ . "/modules/module.php";
                
                // This should still be detected as dynamic
                $moduleName = "test";
                require_once __DIR__ . "/modules/{$moduleName}.php";
            ');
            
            file_put_contents($configFile, '<?php return ["test" => true];');
            file_put_contents($moduleFile, '<?php function moduleFunction() {}');
            
            // Create a parser
            $parser = new PhpFileParser();
            
            // Parse the file
            $parseResult = $parser->parse($mainFile);
            
            // Check that the __DIR__ constant in require statements is resolved
            $this->assertContains($configFile, $parseResult['requireStatements']);
            $this->assertContains($moduleFile, $parseResult['requireStatements']);
            
            // Check that the dynamic require statement is detected
            $this->assertNotEmpty($parseResult['dynamicRequireStatements']);
            
            // Create an analyzer
            $analyzer = new DependencyAnalyzer();
            
            // Analyze the file
            $node = $analyzer->analyze($mainFile);
            
            // Check that the dependencies are correctly resolved
            $dependencies = $node->getAllDependencyPaths();
            $this->assertContains(realpath($configFile), $dependencies);
            $this->assertContains(realpath($moduleFile), $dependencies);
        } finally {
            // Clean up
            if (file_exists($mainFile)) {
                unlink($mainFile);
            }
            if (file_exists($configFile)) {
                unlink($configFile);
            }
            if (file_exists($moduleFile)) {
                unlink($moduleFile);
            }
            if (is_dir($modulesDir)) {
                rmdir($modulesDir);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }
}
