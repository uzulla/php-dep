<?php

namespace PhpDep\Tests;

use PhpDep\DependencyNode;
use PHPUnit\Framework\TestCase;

class DependencyNodeTest extends TestCase
{
    public function testGetFilePath()
    {
        $filePath = '/path/to/file.php';
        $node = new DependencyNode($filePath);
        
        $this->assertEquals($filePath, $node->getFilePath());
    }
    
    public function testAddAndGetDependencies()
    {
        $node = new DependencyNode('/path/to/file.php');
        $dependency1 = new DependencyNode('/path/to/dependency1.php');
        $dependency2 = new DependencyNode('/path/to/dependency2.php');
        
        $node->addDependency($dependency1);
        $node->addDependency($dependency2);
        
        $dependencies = $node->getDependencies();
        
        $this->assertCount(2, $dependencies);
        $this->assertArrayHasKey('/path/to/dependency1.php', $dependencies);
        $this->assertArrayHasKey('/path/to/dependency2.php', $dependencies);
    }
    
    public function testAddAndGetUseStatements()
    {
        $node = new DependencyNode('/path/to/file.php');
        
        $node->addUseStatement('App\\Models\\User');
        $node->addUseStatement('App\\Repositories\\UserRepository');
        
        $useStatements = $node->getUseStatements();
        
        $this->assertCount(2, $useStatements);
        $this->assertEquals('App\\Models\\User', $useStatements[0]);
        $this->assertEquals('App\\Repositories\\UserRepository', $useStatements[1]);
    }
    
    public function testAddAndGetRequireStatements()
    {
        $node = new DependencyNode('/path/to/file.php');
        
        $node->addRequireStatement('config.php');
        $node->addRequireStatement('functions.php');
        
        $requireStatements = $node->getRequireStatements();
        
        $this->assertCount(2, $requireStatements);
        $this->assertEquals('config.php', $requireStatements[0]);
        $this->assertEquals('functions.php', $requireStatements[1]);
    }
    
    public function testAddAndGetClassDefinitions()
    {
        $node = new DependencyNode('/path/to/file.php');
        
        $node->addClassDefinition('App\\Controllers\\UserController');
        
        $classDefinitions = $node->getClassDefinitions();
        
        $this->assertCount(1, $classDefinitions);
        $this->assertEquals('App\\Controllers\\UserController', $classDefinitions[0]);
    }
    
    public function testGetAllDependencyPaths()
    {
        $node = new DependencyNode('/path/to/file.php');
        
        $dependency1 = new DependencyNode('/path/to/dependency1.php');
        $dependency2 = new DependencyNode('/path/to/dependency2.php');
        
        $nestedDependency = new DependencyNode('/path/to/nested.php');
        $dependency1->addDependency($nestedDependency);
        
        $node->addDependency($dependency1);
        $node->addDependency($dependency2);
        
        $allPaths = $node->getAllDependencyPaths();
        
        $this->assertCount(3, $allPaths);
        $this->assertContains('/path/to/dependency1.php', $allPaths);
        $this->assertContains('/path/to/dependency2.php', $allPaths);
        $this->assertContains('/path/to/nested.php', $allPaths);
    }
}
