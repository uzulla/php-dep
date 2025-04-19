<?php

namespace PhpDep\Tests;

use PhpDep\DependencyNode;
use PHPUnit\Framework\TestCase;

class TreeFullOutputTest extends TestCase
{
    /**
     * Test the toTreeFull method with interface implementations.
     */
    public function testToTreeFullWithInterfaceImplementations(): void
    {
        $rootNode = new DependencyNode(__DIR__ . '/fixtures/interfaces/TestInterface.php');
        
        $treeOutput = $rootNode->toTreeFull(false, __DIR__ . '/fixtures/interfaces');
        
        $this->assertStringContainsString('TestInterface', $treeOutput);
        $this->assertStringContainsString('( TestImplementation1 )', $treeOutput);
        $this->assertStringContainsString('( TestImplementation2 )', $treeOutput);
    }
    
    /**
     * Test the CLI output with --dep-tree-full option.
     */
    public function testCliOutputWithDepTreeFullOption(): void
    {
        $command = sprintf(
            'php %s/bin/php-dep %s/fixtures/interfaces/TestInterface.php --dep-tree-full=%s/fixtures/interfaces 2>&1',
            dirname(__DIR__),
            __DIR__,
            __DIR__
        );
        
        $output = shell_exec($command);
        
        $this->assertStringContainsString('Full dependency tree with interface implementations', $output);
        $this->assertStringContainsString('TestInterface', $output);
        $this->assertStringContainsString('( TestImplementation1 )', $output);
        $this->assertStringContainsString('( TestImplementation2 )', $output);
    }
}
