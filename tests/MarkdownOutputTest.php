<?php

namespace PhpDep\Tests;

use PhpDep\DependencyAnalyzer;
use PhpDep\DependencyNode;
use PHPUnit\Framework\TestCase;

class MarkdownOutputTest extends TestCase
{
    /**
     * Test the toMarkdown method of DependencyNode.
     */
    public function testToMarkdown()
    {
        // Create a root node
        $rootNode = new DependencyNode('/path/to/root.php', '<?php echo "Root file"; ?>');
        
        // Create dependency nodes
        $dependency1 = new DependencyNode('/path/to/dep1.php', '<?php echo "Dependency 1"; ?>');
        $dependency2 = new DependencyNode('/path/to/dep2.php', '<?php echo "Dependency 2"; ?>');
        
        // Add dependencies to the root node
        $rootNode->addDependency($dependency1);
        $rootNode->addDependency($dependency2);
        
        // Generate markdown
        $markdown = $rootNode->toMarkdown();
        
        // Check that the markdown contains the expected content
        $this->assertStringContainsString('# File: /path/to/root.php', $markdown);
        $this->assertStringContainsString('```php', $markdown);
        $this->assertStringContainsString('<?php echo "Root file"; ?>', $markdown);
        $this->assertStringContainsString('## Dependencies', $markdown);
        $this->assertStringContainsString('### File: /path/to/dep1.php', $markdown);
        $this->assertStringContainsString('<?php echo "Dependency 1"; ?>', $markdown);
        $this->assertStringContainsString('### File: /path/to/dep2.php', $markdown);
        $this->assertStringContainsString('<?php echo "Dependency 2"; ?>', $markdown);
    }
    
    /**
     * Test the loadContents parameter of DependencyAnalyzer.
     */
    public function testLoadContents()
    {
        // Create a temporary directory for test files
        $tempDir = sys_get_temp_dir() . '/php-dep-test-' . uniqid();
        mkdir($tempDir, 0755, true);
        
        try {
            // Create test files
            $mainFile = $tempDir . '/main.php';
            $depFile = $tempDir . '/dependency.php';
            
            file_put_contents($mainFile, '<?php require_once "dependency.php"; echo "Main file"; ?>');
            file_put_contents($depFile, '<?php echo "Dependency file"; ?>');
            
            // Test with loadContents = false
            $analyzer1 = new DependencyAnalyzer(null, null, false);
            $node1 = $analyzer1->analyze($mainFile, true);
            
            // The file content should not be loaded initially
            $this->assertFalse($node1->hasFileContent());
            
            // But can be loaded on demand
            $this->assertStringContainsString('<?php require_once "dependency.php"', $node1->getFileContent());
            
            // Now it should be loaded
            $this->assertTrue($node1->hasFileContent());
            
            // Test with loadContents = true
            $analyzer2 = new DependencyAnalyzer(null, null, true);
            $node2 = $analyzer2->analyze($mainFile, true);
            
            // The file content should be loaded automatically
            $this->assertTrue($node2->hasFileContent());
            $this->assertStringContainsString('<?php require_once "dependency.php"', $node2->getFileContent());
            
            // Check that the dependency was found
            $dependencies = $node2->getDependencies();
            $this->assertCount(1, $dependencies);
            
            // Get the first dependency
            $dependency = reset($dependencies);
            
            // Check that the dependency content was loaded
            $this->assertTrue($dependency->hasFileContent());
            $this->assertStringContainsString('<?php echo "Dependency file"', $dependency->getFileContent());
            
            // Test markdown generation
            $markdown = $node2->toMarkdown();
            
            // Check for the main file content in the markdown
            $this->assertStringContainsString('<?php require_once "dependency.php"; echo "Main file"; ?>', $markdown);
            
            // Check for the dependency file content in the markdown
            $this->assertStringContainsString('<?php echo "Dependency file"; ?>', $markdown);
            
            // Check for the file paths in the markdown (using basename to avoid path issues)
            $this->assertStringContainsString('main.php', $markdown);
            $this->assertStringContainsString('dependency.php', $markdown);
        } finally {
            // Clean up
            if (file_exists($mainFile)) {
                unlink($mainFile);
            }
            if (file_exists($depFile)) {
                unlink($depFile);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }
}
