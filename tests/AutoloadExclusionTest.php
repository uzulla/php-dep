<?php

declare(strict_types=1);

namespace PhpDep\Tests;

use PhpDep\DependencyAnalyzer;
use PHPUnit\Framework\TestCase;

class AutoloadExclusionTest extends TestCase
{
    /**
     * Test that autoload.php is excluded by default.
     */
    public function testAutoloadExcludedByDefault()
    {
        // Create a temporary directory for test files
        $tempDir = sys_get_temp_dir() . '/php-dep-test-' . uniqid();
        mkdir($tempDir, 0755, true);
        
        try {
            // Create test files
            $mainFile = $tempDir . '/main.php';
            $autoloadFile = $tempDir . '/vendor/autoload.php';
            $vendorDir = $tempDir . '/vendor';
            
            if (!is_dir($vendorDir)) {
                mkdir($vendorDir, 0755, true);
            }
            
            file_put_contents($mainFile, '<?php require_once __DIR__ . "/vendor/autoload.php"; ?>');
            file_put_contents($autoloadFile, '<?php // Autoloader ?>');
            
            // Create an analyzer with default settings (autoload excluded)
            $analyzer = new DependencyAnalyzer();
            
            // Analyze the file
            $node = $analyzer->analyze($mainFile);
            
            // Check that autoload.php is not included in the dependencies
            $dependencies = $node->getAllDependencyPaths();
            $this->assertEmpty($dependencies);
            
            // Create an analyzer with includeAutoload = true
            $analyzer = new DependencyAnalyzer(null, null, false, true);
            
            // Analyze the file
            $node = $analyzer->analyze($mainFile);
            
            // Check that autoload.php is included in the dependencies
            $dependencies = $node->getAllDependencyPaths();
            $this->assertCount(1, $dependencies);
            $this->assertContains(realpath($autoloadFile), $dependencies);
        } finally {
            // Clean up
            if (file_exists($mainFile)) {
                unlink($mainFile);
            }
            if (file_exists($autoloadFile)) {
                unlink($autoloadFile);
            }
            if (is_dir($vendorDir)) {
                rmdir($vendorDir);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }
    
    /**
     * Test that the setIncludeAutoload method works.
     */
    public function testSetIncludeAutoload()
    {
        // Create a temporary directory for test files
        $tempDir = sys_get_temp_dir() . '/php-dep-test-' . uniqid();
        mkdir($tempDir, 0755, true);
        
        try {
            // Create test files
            $mainFile = $tempDir . '/main.php';
            $autoloadFile = $tempDir . '/vendor/autoload.php';
            $vendorDir = $tempDir . '/vendor';
            
            if (!is_dir($vendorDir)) {
                mkdir($vendorDir, 0755, true);
            }
            
            file_put_contents($mainFile, '<?php require_once __DIR__ . "/vendor/autoload.php"; ?>');
            file_put_contents($autoloadFile, '<?php // Autoloader ?>');
            
            // Create an analyzer with default settings (autoload excluded)
            $analyzer = new DependencyAnalyzer();
            
            // Check that includeAutoload is false by default
            $this->assertFalse($analyzer->getIncludeAutoload());
            
            // Set includeAutoload to true
            $analyzer->setIncludeAutoload(true);
            
            // Check that includeAutoload is now true
            $this->assertTrue($analyzer->getIncludeAutoload());
            
            // Analyze the file
            $node = $analyzer->analyze($mainFile);
            
            // Check that autoload.php is included in the dependencies
            $dependencies = $node->getAllDependencyPaths();
            $this->assertCount(1, $dependencies);
            $this->assertContains(realpath($autoloadFile), $dependencies);
            
            // Set includeAutoload back to false
            $analyzer->setIncludeAutoload(false);
            
            // Check that includeAutoload is now false
            $this->assertFalse($analyzer->getIncludeAutoload());
            
            // Reset the analyzer
            $analyzer->reset();
            
            // Analyze the file again
            $node = $analyzer->analyze($mainFile);
            
            // Check that autoload.php is not included in the dependencies
            $dependencies = $node->getAllDependencyPaths();
            $this->assertEmpty($dependencies);
        } finally {
            // Clean up
            if (file_exists($mainFile)) {
                unlink($mainFile);
            }
            if (file_exists($autoloadFile)) {
                unlink($autoloadFile);
            }
            if (is_dir($vendorDir)) {
                rmdir($vendorDir);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }
}
