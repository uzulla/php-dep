<?php

declare(strict_types=1);

namespace PhpDep\Tests;

use PhpDep\DependencyNode;
use PHPUnit\Framework\TestCase;

class DependencyNodeTreeTest extends TestCase
{
    /**
     * Test the toTree method generates the correct tree structure.
     */
    public function testToTree()
    {
        $rootNode = new DependencyNode('/path/to/root.php');
        $dependency1 = new DependencyNode('/path/to/dependency1.php');
        $dependency2 = new DependencyNode('/path/to/dependency2.php');
        $nestedDependency = new DependencyNode('/path/to/nested.php');
        
        $dependency1->addDependency($nestedDependency);
        $rootNode->addDependency($dependency1);
        $rootNode->addDependency($dependency2);
        
        $treeOutput = $rootNode->toTree();
        
        $this->assertStringContainsString('└── \\path\\to\\root', $treeOutput);
        $this->assertStringContainsString('    ├── \\path\\to\\dependency1', $treeOutput);
        $this->assertStringContainsString('    │   └── \\path\\to\\nested', $treeOutput);
        $this->assertStringContainsString('    └── \\path\\to\\dependency2', $treeOutput);
    }
    
    /**
     * Test that the toTree method handles circular dependencies correctly.
     */
    public function testToTreeWithCircularDependencies()
    {
        $nodeA = new DependencyNode('/path/to/nodeA.php');
        $nodeB = new DependencyNode('/path/to/nodeB.php');
        
        $nodeA->addDependency($nodeB);
        $nodeB->addDependency($nodeA);
        
        $treeOutput = $nodeA->toTree();
        
        $this->assertStringContainsString('└── \\path\\to\\nodeA', $treeOutput);
        $this->assertStringContainsString('    └── \\path\\to\\nodeB', $treeOutput);
        
        $this->assertStringNotContainsString('        └── nodeA', $treeOutput);
    }
    
    /**
     * Test the filePathToFQCN method converts file paths to FQCN format correctly.
     */
    public function testFilePathToFQCN()
    {
        $node = new DependencyNode('/path/to/src/Models/User.php');
        
        $reflectionClass = new \ReflectionClass(DependencyNode::class);
        $method = $reflectionClass->getMethod('filePathToFQCN');
        $method->setAccessible(true);
        
        $result = $method->invoke($node, '/path/to/src/Models/User.php');
        
        $this->assertEquals('Models\\User', $result);
    }
    
    /**
     * Test the generateTreeOutput method creates the correct tree structure.
     */
    public function testGenerateTreeOutput()
    {
        $node = new DependencyNode('/path/to/file.php');
        
        $reflectionClass = new \ReflectionClass(DependencyNode::class);
        $method = $reflectionClass->getMethod('generateTreeOutput');
        $method->setAccessible(true);
        
        $childNode = new DependencyNode('/path/to/child.php');
        $node->addDependency($childNode);
        
        $result = $method->invokeArgs($node, [$node, '', true, [], false]);
        
        $this->assertStringContainsString('└── ', $result);
        $this->assertStringContainsString('file', $result);
        $this->assertStringContainsString('    └── ', $result);
        $this->assertStringContainsString('child', $result);
    }
}
