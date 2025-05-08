<?php

declare(strict_types=1);

namespace PhpDep;

use PhpDep\Parser\PhpFileParser;
use PhpDep\Resolver\ComposerResolver;

/**
 * Main class for analyzing PHP file dependencies.
 */
class DependencyAnalyzer
{
    /**
     * @var PhpFileParser
     */
    private PhpFileParser $parser;

    /**
     * @var ComposerResolver
     */
    private ComposerResolver $resolver;

    /**
     * @var array Processed files to avoid circular dependencies
     */
    private array $processedFiles = [];

    /**
     * @var bool Whether to load file contents
     */
    private bool $loadContents = false;

    /**
     * @var bool Whether to include autoload.php
     */
    private bool $includeAutoload = false;

    /**
     * @param PhpFileParser|null $parser
     * @param ComposerResolver|null $resolver
     * @param bool $loadContents Whether to load file contents
     * @param bool $includeAutoload Whether to include autoload.php
     */
    public function __construct(
        ?PhpFileParser $parser = null,
        ?ComposerResolver $resolver = null,
        bool $loadContents = false,
        bool $includeAutoload = false
    ) {
        $this->parser = $parser ?? new PhpFileParser();
        $this->resolver = $resolver ?? new ComposerResolver();
        $this->loadContents = $loadContents;
        $this->includeAutoload = $includeAutoload;
    }

    /**
     * Analyze dependencies for a PHP file.
     *
     * @param string $filePath Path to the PHP file
     * @param bool $recursive Whether to analyze dependencies recursively (default: true)
     * @return DependencyNode The root dependency node
     */
    public function analyze(string $filePath, bool $recursive = true): DependencyNode
    {
        $absolutePath = $this->getAbsolutePath($filePath);
        
        if (!file_exists($absolutePath)) {
            throw new \InvalidArgumentException("File not found: {$absolutePath}");
        }
        
        return $this->analyzeFile($absolutePath, $recursive);
    }

    /**
     * Analyze a PHP file and its dependencies.
     *
     * @param string $filePath Absolute path to the PHP file
     * @param bool $recursive Whether to analyze dependencies recursively
     * @return DependencyNode
     */
    private function analyzeFile(string $filePath, bool $recursive): DependencyNode
    {
        // Create a node for this file
        $fileContent = $this->loadContents ? file_get_contents($filePath) : null;
        $node = new DependencyNode($filePath, $fileContent);
        
        // Mark this file as processed to avoid circular dependencies
        $this->processedFiles[$filePath] = true;
        
        // Parse the file to extract dependencies
        $parseResult = $this->parser->parse($filePath);
        
        // Add use statements to the node
        foreach ($parseResult['useStatements'] as $useStatement) {
            $node->addUseStatement($useStatement);
            
            // Resolve the use statement to a file path
            $resolvedPath = $this->resolver->resolveNamespace($useStatement);
            
            if ($resolvedPath && $recursive && !isset($this->processedFiles[$resolvedPath])) {
                $dependencyNode = $this->analyzeFile($resolvedPath, $recursive);
                $node->addDependency($dependencyNode);
            }
        }
        
        // Add require/include statements to the node
        foreach ($parseResult['requireStatements'] as $requireStatement) {
            $node->addRequireStatement($requireStatement);
            
            // Check if the require statement is an absolute path
            if (file_exists($requireStatement)) {
                $resolvedPath = $requireStatement;
            } else {
                // Resolve the require statement to a file path
                $resolvedPath = $this->resolver->resolveRequire($requireStatement, dirname($filePath));
            }
            
            // Skip autoload.php if includeAutoload is false
            if (!$this->includeAutoload && $this->isAutoloadFile($resolvedPath)) {
                continue;
            }
            
            if ($resolvedPath && $recursive && !isset($this->processedFiles[$resolvedPath])) {
                $dependencyNode = $this->analyzeFile($resolvedPath, $recursive);
                $node->addDependency($dependencyNode);
            }
        }
        
        // Add dynamic require/include statements to the node
        foreach ($parseResult['dynamicRequireStatements'] as $dynamicRequireStatement) {
            $node->addDynamicRequireStatement($dynamicRequireStatement);
            
            // We can't resolve dynamic require statements automatically
            // But we can try to make an educated guess in some cases
            if (isset($dynamicRequireStatement['expression']) && 
                is_string($dynamicRequireStatement['expression']) && 
                strpos($dynamicRequireStatement['expression'], ' . ') === false) {
                
                // Try to resolve as a literal string (for simple cases)
                $resolvedPath = $this->resolver->resolveRequire($dynamicRequireStatement['expression'], dirname($filePath));
                
                // Skip autoload.php if includeAutoload is false
                if (!$this->includeAutoload && $this->isAutoloadFile($resolvedPath)) {
                    continue;
                }
                
                if ($resolvedPath && $recursive && !isset($this->processedFiles[$resolvedPath])) {
                    $dependencyNode = $this->analyzeFile($resolvedPath, $recursive);
                    $node->addDependency($dependencyNode);
                }
            }
        }
        
        // Add class definitions to the node
        foreach ($parseResult['classDefinitions'] as $classDefinition) {
            $node->addClassDefinition($classDefinition);
        }
        
        return $node;
    }

    /**
     * Check if a file is an autoload.php file.
     *
     * @param string|null $filePath
     * @return bool
     */
    private function isAutoloadFile(?string $filePath): bool
    {
        if ($filePath === null) {
            return false;
        }
        
        return strpos($filePath, 'vendor/autoload.php') !== false || 
               strpos($filePath, 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php') !== false;
    }

    /**
     * Get the absolute path for a file.
     *
     * @param string $filePath
     * @return string
     */
    private function getAbsolutePath(string $filePath): string
    {
        if (file_exists($filePath)) {
            return realpath($filePath);
        }
        
        if (file_exists(getcwd() . '/' . $filePath)) {
            return realpath(getcwd() . '/' . $filePath);
        }
        
        return $filePath;
    }

    /**
     * Get all dependency file paths for a PHP file.
     *
     * @param string $filePath Path to the PHP file
     * @return array List of dependency file paths
     */
    public function getDependencyPaths(string $filePath): array
    {
        $node = $this->analyze($filePath);
        return $node->getAllDependencyPaths();
    }

    /**
     * Reset the processed files cache.
     *
     * @return self
     */
    public function reset(): self
    {
        $this->processedFiles = [];
        return $this;
    }

    /**
     * Set whether to load file contents.
     *
     * @param bool $loadContents
     * @return self
     */
    public function setLoadContents(bool $loadContents): self
    {
        $this->loadContents = $loadContents;
        return $this;
    }

    /**
     * Get whether to load file contents.
     *
     * @return bool
     */
    public function getLoadContents(): bool
    {
        return $this->loadContents;
    }

    /**
     * Set whether to include autoload.php.
     *
     * @param bool $includeAutoload
     * @return self
     */
    public function setIncludeAutoload(bool $includeAutoload): self
    {
        $this->includeAutoload = $includeAutoload;
        return $this;
    }

    /**
     * Get whether to include autoload.php.
     *
     * @return bool
     */
    public function getIncludeAutoload(): bool
    {
        return $this->includeAutoload;
    }
    
    /**
     * Find unused classes in the specified directories.
     *
     * @param array $directories Directories to analyze
     * @param string $pattern File pattern to match (e.g., *.php)
     * @param array $excludeDirs Directories to exclude from analysis
     * @return array Array of unused class FQCNs
     */
    public function findUnusedClasses(array $directories, string $pattern = '*.php', array $excludeDirs = ['vendor']): array
    {
        $this->reset();
        
        $finder = new \Symfony\Component\Finder\Finder();
        $finder->files()->name($pattern);
        
        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                throw new \InvalidArgumentException("Directory not found: {$directory}");
            }
            $finder->in($directory);
        }
        
        foreach ($excludeDirs as $excludeDir) {
            $finder->notPath($excludeDir);
        }
        
        // Check if any files were found
        if ($finder->count() === 0) {
            throw new \RuntimeException("No files matching pattern '{$pattern}' found in the specified directories.");
        }
        
        $definedClasses = [];
        $usedClasses = [];
        
        foreach ($finder as $file) {
            $filePath = $file->getRealPath();
            
            try {
                // Parse the file
                $parseResult = $this->parser->parse($filePath);
                
                foreach ($parseResult['classDefinitions'] as $className) {
                    $definedClasses[$className] = $filePath;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        foreach ($finder as $file) {
            $filePath = $file->getRealPath();
            
            try {
                // Parse the file
                $parseResult = $this->parser->parse($filePath);
                
                // Add used classes from use statements
                foreach ($parseResult['useStatements'] as $useStatement) {
                    $usedClasses[$useStatement] = true;
                }
                
                $fileContent = file_get_contents($filePath);
                
                preg_match_all('/new\s+([A-Za-z0-9_\\\\]+)/', $fileContent, $newMatches);
                if (!empty($newMatches[1])) {
                    foreach ($newMatches[1] as $className) {
                        $this->markClassAsUsed($className, $definedClasses, $usedClasses);
                    }
                }
                
                preg_match_all('/([A-Za-z0-9_\\\\]+)::[A-Za-z0-9_]+/', $fileContent, $staticMatches);
                if (!empty($staticMatches[1])) {
                    foreach ($staticMatches[1] as $className) {
                        $this->markClassAsUsed($className, $definedClasses, $usedClasses);
                    }
                }
                
                foreach ($parseResult['useStatements'] as $useStatement) {
                    $parts = explode('\\', $useStatement);
                    $className = end($parts);
                    
                    preg_match_all('/\b' . preg_quote($className, '/') . '\b/', $fileContent, $matches);
                    if (!empty($matches[0])) {
                        $usedClasses[$useStatement] = true;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        $unusedClasses = [];
        
        foreach ($definedClasses as $className => $filePath) {
            if (!isset($usedClasses[$className])) {
                $unusedClasses[] = $className;
            }
        }
        
        sort($unusedClasses);
        
        return $unusedClasses;
    }
    
    /**
     * Mark a class as used, handling different formats of class names.
     *
     * @param string $className
     * @param array $definedClasses
     * @param array &$usedClasses
     */
    private function markClassAsUsed(string $className, array $definedClasses, array &$usedClasses): void
    {
        $normalizedClass = str_replace('\\\\', '\\', $className);
        
        if (isset($definedClasses[$normalizedClass])) {
            $usedClasses[$normalizedClass] = true;
            return;
        }
        
        foreach (array_keys($definedClasses) as $definedClass) {
            // Check if the class name is the last part of a defined class
            $definedClassParts = explode('\\', $definedClass);
            $definedClassName = end($definedClassParts);
            
            if ($normalizedClass === $definedClassName) {
                $usedClasses[$definedClass] = true;
            }
        }
    }
}
