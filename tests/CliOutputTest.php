<?php

declare(strict_types=1);

namespace PhpDep\Tests;

use PHPUnit\Framework\TestCase;

class CliOutputTest extends TestCase
{
    /**
     * Test that the CLI tool outputs markdown to standard output when no output file is specified.
     */
    public function testMarkdownToStandardOutput()
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
            
            // Execute the CLI tool with the markdown format option but without specifying an output file
            $command = sprintf(
                'php %s/bin/php-dep analyze %s --format=markdown',
                dirname(__DIR__),
                $mainFile
            );
            
            // Capture the output
            $output = shell_exec($command);
            
            // Verify that the output contains the expected markdown content
            $this->assertStringContainsString('# File:', $output);
            $this->assertStringContainsString('```php', $output);
            $this->assertStringContainsString('<?php require_once "dependency.php"; echo "Main file"; ?>', $output);
            $this->assertStringContainsString('## Dependencies', $output);
            $this->assertStringContainsString('### File:', $output);
            $this->assertStringContainsString('<?php echo "Dependency file"; ?>', $output);
            
            // Now test with an output file
            $outputFile = $tempDir . '/output.md';
            $command = sprintf(
                'php %s/bin/php-dep analyze %s --output=%s',
                dirname(__DIR__),
                $mainFile,
                $outputFile
            );
            
            // Execute the command
            $output = shell_exec($command);
            
            // Verify that the output indicates the file was written
            $this->assertStringContainsString('Markdown output written to', $output);
            
            // Verify that the file was created and contains the expected content
            $this->assertFileExists($outputFile);
            $fileContent = file_get_contents($outputFile);
            $this->assertStringContainsString('# File:', $fileContent);
            $this->assertStringContainsString('```php', $fileContent);
            $this->assertStringContainsString('<?php require_once "dependency.php"; echo "Main file"; ?>', $fileContent);
            $this->assertStringContainsString('## Dependencies', $fileContent);
            $this->assertStringContainsString('### File:', $fileContent);
            $this->assertStringContainsString('<?php echo "Dependency file"; ?>', $fileContent);
        } finally {
            // Clean up
            if (file_exists($mainFile)) {
                unlink($mainFile);
            }
            if (file_exists($depFile)) {
                unlink($depFile);
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
     * Test the default behavior of the CLI tool (no options).
     */
    public function testDefaultBehavior()
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
            
            // Execute the CLI tool with just the file path (no options)
            $command = sprintf(
                'php %s/bin/php-dep analyze %s',
                dirname(__DIR__),
                $mainFile
            );
            
            // Capture the output
            $output = shell_exec($command);
            
            // Verify that the output contains the expected markdown content
            $this->assertStringContainsString('# File:', $output);
            $this->assertStringContainsString('```php', $output);
            $this->assertStringContainsString('<?php require_once "dependency.php"; echo "Main file"; ?>', $output);
            $this->assertStringContainsString('## Dependencies', $output);
            $this->assertStringContainsString('### File:', $output);
            $this->assertStringContainsString('<?php echo "Dependency file"; ?>', $output);
            
            // Compare with explicit format option
            $explicitCommand = sprintf(
                'php %s/bin/php-dep analyze %s --format=markdown',
                dirname(__DIR__),
                $mainFile
            );
            
            // Capture the output
            $explicitOutput = shell_exec($explicitCommand);
            
            // Verify that both outputs are the same
            $this->assertEquals($output, $explicitOutput);
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

    /**
     * Test text output with custom item prefix
     */
    public function testTextOutputWithCustomPrefix()
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
            
            $customCommand = sprintf(
                'php %s/bin/php-dep analyze %s --format=text --text-item-prefix="@"',
                dirname(__DIR__),
                $mainFile
            );
            
            $customOutput = shell_exec($customCommand);
            
            $this->assertStringContainsString('@', $customOutput);
            $this->assertStringNotContainsString('  - ', $customOutput);
            
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

    /**
     * Test text output with custom empty item prefix
     */
    public function testTextOutputWithCustomEmptyPrefix()
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
            
            $emptyCommand = sprintf(
                'php %s/bin/php-dep analyze %s --format=text --text-item-prefix=""',
                dirname(__DIR__),
                $mainFile
            );
            
            $emptyOutput = shell_exec($emptyCommand);
            
            $this->assertStringContainsString('Dependencies for', $emptyOutput);
            $this->assertStringNotContainsString('  - ', $emptyOutput);
            
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
