<?php

declare(strict_types=1);

namespace PhpDep\Tests;

use PHPUnit\Framework\TestCase;

class TreeOutputTest extends TestCase
{
    /**
     * Test that the CLI tool outputs tree structure to standard output when --dep-tree option is specified.
     */
    public function testTreeToStandardOutput()
    {
        $tempDir = sys_get_temp_dir() . '/php-dep-test-' . uniqid();
        mkdir($tempDir, 0755, true);
        
        try {
            $mainFile = $tempDir . '/main.php';
            $depFile = $tempDir . '/dependency.php';
            $nestedDepFile = $tempDir . '/nested_dependency.php';
            
            file_put_contents($mainFile, '<?php require_once "dependency.php"; echo "Main file"; ?>');
            file_put_contents($depFile, '<?php require_once "nested_dependency.php"; echo "Dependency file"; ?>');
            file_put_contents($nestedDepFile, '<?php echo "Nested dependency file"; ?>');
            
            $command = sprintf(
                'php %s/bin/php-dep %s --dep-tree',
                dirname(__DIR__),
                $mainFile
            );
            
            $output = shell_exec($command);
            
            $this->assertStringContainsString('# File:', $output);
            $this->assertStringContainsString('## Dependencies', $output);
            
            $this->assertStringContainsString(basename($mainFile, '.php'), $output);
            $this->assertStringContainsString(basename($depFile, '.php'), $output);
            $this->assertStringContainsString(basename($nestedDepFile, '.php'), $output);
            
            $outputFile = $tempDir . '/output.txt';
            $command = sprintf(
                'php %s/bin/php-dep %s --dep-tree --output=%s',
                dirname(__DIR__),
                $mainFile,
                $outputFile
            );
            
            $output = shell_exec($command);
            
            $this->assertStringContainsString('Markdown output written to', $output);
            
            $this->assertFileExists($outputFile);
            $fileContent = file_get_contents($outputFile);
            $this->assertStringContainsString('# File:', $fileContent);
            $this->assertStringContainsString('## Dependencies', $fileContent);
            $this->assertStringContainsString(basename($mainFile, '.php'), $fileContent);
            $this->assertStringContainsString(basename($depFile, '.php'), $fileContent);
            $this->assertStringContainsString(basename($nestedDepFile, '.php'), $fileContent);
        } finally {
            if (file_exists($mainFile)) {
                unlink($mainFile);
            }
            if (file_exists($depFile)) {
                unlink($depFile);
            }
            if (file_exists($nestedDepFile)) {
                unlink($nestedDepFile);
            }
            if (isset($outputFile) && file_exists($outputFile)) {
                unlink($outputFile);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }
    
    /**
     * Test that the tree output correctly handles circular dependencies.
     */
    public function testTreeWithCircularDependencies()
    {
        $tempDir = sys_get_temp_dir() . '/php-dep-test-' . uniqid();
        mkdir($tempDir, 0755, true);
        
        try {
            $fileA = $tempDir . '/fileA.php';
            $fileB = $tempDir . '/fileB.php';
            
            file_put_contents($fileA, '<?php require_once "fileB.php"; echo "File A"; ?>');
            file_put_contents($fileB, '<?php require_once "fileA.php"; echo "File B"; ?>');
            
            $command = sprintf(
                'php %s/bin/php-dep %s --dep-tree',
                dirname(__DIR__),
                $fileA
            );
            
            $output = shell_exec($command);
            
            $this->assertStringContainsString('# File:', $output);
            $this->assertStringContainsString('## Dependencies', $output);
            
            $this->assertStringContainsString(basename($fileA, '.php'), $output);
            $this->assertStringContainsString(basename($fileB, '.php'), $output);
            
        } finally {
            if (file_exists($fileA)) {
                unlink($fileA);
            }
            if (file_exists($fileB)) {
                unlink($fileB);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }
}
