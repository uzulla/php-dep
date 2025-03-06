<?php

// This script demonstrates how to use the PHP Dependency Analyzer
// to analyze the dependencies of a PHP file.

// Ensure the autoloader is loaded
require_once __DIR__ . '/../vendor/autoload.php';

use PhpDep\DependencyAnalyzer;
use PhpDep\Parser\PhpFileParser;
use PhpDep\Resolver\ComposerResolver;

// Specify the file to analyze
$filePath = __DIR__ . '/src/Controllers/UserController.php';

echo "Analyzing dependencies for: {$filePath}\n\n";

try {
    // Create a resolver with the examples composer.json
    $resolver = new ComposerResolver(__DIR__ . '/composer.json');
    
    // Create a parser
    $parser = new PhpFileParser();
    
    // Create a new analyzer with the custom resolver
    $analyzer = new DependencyAnalyzer($parser, $resolver);
    
    // Analyze the file
    $node = $analyzer->analyze($filePath, true);
    
    // Get all dependency paths
    $dependencies = $node->getAllDependencyPaths();
    
    if (empty($dependencies)) {
        echo "No dependencies found.\n";
    } else {
        echo "Dependencies:\n";
        
        foreach ($dependencies as $dependency) {
            echo "- {$dependency}\n";
        }
        
        echo "\nTotal dependencies: " . count($dependencies) . "\n";
    }
    
    // Get use statements
    $useStatements = $node->getUseStatements();
    
    if (!empty($useStatements)) {
        echo "\nUse statements:\n";
        
        foreach ($useStatements as $useStatement) {
            echo "- {$useStatement}\n";
        }
    }
    
    // Get require statements
    $requireStatements = $node->getRequireStatements();
    
    if (!empty($requireStatements)) {
        echo "\nRequire statements:\n";
        
        foreach ($requireStatements as $requireStatement) {
            echo "- {$requireStatement}\n";
        }
    }
    
    // Get class definitions
    $classDefinitions = $node->getClassDefinitions();
    
    if (!empty($classDefinitions)) {
        echo "\nClass definitions:\n";
        
        foreach ($classDefinitions as $classDefinition) {
            echo "- {$classDefinition}\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
