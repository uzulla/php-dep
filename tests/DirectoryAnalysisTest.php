<?php

declare(strict_types=1);

namespace PhpDep\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DirectoryAnalysisTest extends TestCase
{
    /**
     * Test the directory analysis functionality
     */
    #[Test]
    public function testDirectoryAnalysis(): void
    {
        $tempDir = sys_get_temp_dir() . '/php-dep-test-' . uniqid();
        mkdir($tempDir, 0755, true);
        
        $subDir = $tempDir . '/subdir';
        mkdir($subDir, 0755, true);
        
        try {
            $mainFile = $tempDir . '/main.php';
            $helperFile = $tempDir . '/helper.php';
            $subDirFile = $subDir . '/subfile.php';
            $nonPhpFile = $tempDir . '/file.txt';
            
            file_put_contents($mainFile, '<?php require_once "helper.php"; echo "Main file"; ?>');
            file_put_contents($helperFile, '<?php echo "Helper file"; ?>');
            file_put_contents($subDirFile, '<?php echo "Subdir file"; ?>');
            file_put_contents($nonPhpFile, 'This is not a PHP file');
            
            $command = sprintf(
                'php %s/bin/php-dep analyze-dir %s --format=text 2>&1',
                dirname(__DIR__),
                escapeshellarg($tempDir)
            );
            
            $output = shell_exec($command);
            
            $this->assertStringContainsString('Found', $output);
            $this->assertStringContainsString('main.php', $output);
            $this->assertStringContainsString('helper.php', $output);
            $this->assertStringContainsString('subfile.php', $output);
            $this->assertStringNotContainsString('file.txt', $output);
            
            $patternCommand = sprintf(
                'php %s/bin/php-dep analyze-dir %s --pattern=%s --format=text 2>&1',
                dirname(__DIR__),
                escapeshellarg($tempDir),
                escapeshellarg("*main.php")
            );
            
            $patternOutput = shell_exec($patternCommand);
            
            $this->assertStringContainsString('main.php', $patternOutput);
            $this->assertStringContainsString('helper.php', $patternOutput);
            $this->assertStringNotContainsString('subfile.php', $patternOutput);
            
            $multiDirCommand = sprintf(
                'php %s/bin/php-dep analyze-dir %s %s --format=text 2>&1',
                dirname(__DIR__),
                escapeshellarg($tempDir),
                escapeshellarg($subDir)
            );
            
            $multiDirOutput = shell_exec($multiDirCommand);
            
            $this->assertStringContainsString('main.php', $multiDirOutput);
            $this->assertStringContainsString('helper.php', $multiDirOutput);
            $this->assertStringContainsString('subfile.php', $multiDirOutput);
            
        } finally {
            if (file_exists($mainFile)) {
                unlink($mainFile);
            }
            if (file_exists($helperFile)) {
                unlink($helperFile);
            }
            if (file_exists($subDirFile)) {
                unlink($subDirFile);
            }
            if (file_exists($nonPhpFile)) {
                unlink($nonPhpFile);
            }
            if (is_dir($subDir)) {
                rmdir($subDir);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }
    
    /**
     * Test the directory analysis with --dep-tree option
     */
    #[Test]
    public function testDirectoryAnalysisWithDepTree(): void
    {
        $tempDir = sys_get_temp_dir() . '/php-dep-tree-test-' . uniqid();
        mkdir($tempDir, 0755, true);
        
        try {
            $mainFile = $tempDir . '/main.php';
            $helperFile = $tempDir . '/helper.php';
            
            file_put_contents($mainFile, '<?php require_once "helper.php"; echo "Main file"; ?>');
            file_put_contents($helperFile, '<?php echo "Helper file"; ?>');
            
            $command = sprintf(
                'php %s/bin/php-dep analyze-dir %s --dep-tree 2>&1',
                dirname(__DIR__),
                escapeshellarg($tempDir)
            );
            
            $output = shell_exec($command);
            
            $this->assertStringContainsString('Dependency tree', $output);
            $this->assertStringContainsString('└──', $output);
            $this->assertStringContainsString('main.php', $output);
            $this->assertStringContainsString('helper.php', $output);
            
        } finally {
            if (file_exists($mainFile)) {
                unlink($mainFile);
            }
            if (file_exists($helperFile)) {
                unlink($helperFile);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }
}
