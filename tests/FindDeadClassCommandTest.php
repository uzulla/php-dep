<?php

declare(strict_types=1);

namespace PhpDep\Tests;

use PHPUnit\Framework\TestCase;
use PhpDep\DependencyAnalyzer;
use PhpDep\Parser\PhpFileParser;
use PhpDep\Resolver\ComposerResolver;

class FindDeadClassCommandTest extends TestCase
{
    private $tempDir;
    
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/php-dep-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
        
        $this->createTestFiles();
    }
    
    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }
    
    private function createTestFiles(): void
    {
        file_put_contents(
            $this->tempDir . '/UsedClass.php',
            '<?php
            namespace Test;
            
            class UsedClass
            {
                public function doSomething()
                {
                    return "Hello World";
                }
            }
            '
        );
        
        file_put_contents(
            $this->tempDir . '/UnusedClass.php',
            '<?php
            namespace Test;
            
            class UnusedClass
            {
                public function doSomething()
                {
                    return "Hello World";
                }
            }
            '
        );
        
        file_put_contents(
            $this->tempDir . '/Consumer.php',
            '<?php
            namespace Test;
            
            use Test\UsedClass;
            
            class Consumer
            {
                public function run()
                {
                    $usedClass = new UsedClass();
                    return $usedClass->doSomething();
                }
            }
            '
        );
    }
    
    private function removeDirectory($dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
    
    public function testFindUnusedClasses(): void
    {
        $analyzer = new DependencyAnalyzer(
            new PhpFileParser(),
            new ComposerResolver()
        );
        
        $unusedClasses = $analyzer->findUnusedClasses([$this->tempDir]);
        
        $this->assertContains('Test\UnusedClass', $unusedClasses);
        $this->assertNotContains('Test\UsedClass', $unusedClasses);
        $this->assertNotContains('Test\Consumer', $unusedClasses);
    }
}
