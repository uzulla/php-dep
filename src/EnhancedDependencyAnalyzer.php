<?php

namespace PhpDep;

use PhpDep\Parser\EnhancedPhpFileParser;
use PhpDep\Resolver\EnhancedComposerResolver;

/**
 * Enhanced analyzer for PHP file dependencies with better support for modern PHP codebases.
 */
class EnhancedDependencyAnalyzer extends DependencyAnalyzer
{
    /**
     * @var EnhancedPhpFileParser
     */
    private EnhancedPhpFileParser $parser;

    /**
     * @var EnhancedComposerResolver
     */
    private EnhancedComposerResolver $resolver;

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
     * @param EnhancedPhpFileParser|null $parser
     * @param EnhancedComposerResolver|null $resolver
     * @param bool $loadContents Whether to load file contents
     * @param bool $includeAutoload Whether to include autoload.php
     */
    public function __construct(
        ?EnhancedPhpFileParser $parser = null,
        ?EnhancedComposerResolver $resolver = null,
        bool $loadContents = false,
        bool $includeAutoload = false
    ) {
        $this->parser = $parser ?? new EnhancedPhpFileParser();
        $this->resolver = $resolver ?? new EnhancedComposerResolver();
        $this->loadContents = $loadContents;
        $this->includeAutoload = $includeAutoload;
    }

    /**
     * Analyze dependencies for a PHP file with enhanced detection.
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
     * Analyze a PHP file and its dependencies with enhanced detection.
     *
     * @param string $filePath Absolute path to the PHP file
     * @param bool $recursive Whether to analyze dependencies recursively
     * @return DependencyNode
     */
    private function analyzeFile(string $filePath, bool $recursive): DependencyNode
    {
        $fileContent = $this->loadContents ? file_get_contents($filePath) : null;
        $node = new DependencyNode($filePath, $fileContent);
        
        $this->processedFiles[$filePath] = true;
        
        $parseResult = $this->parser->parse($filePath);
        
        $this->processUseStatements($node, $parseResult, $recursive);
        $this->processRequireStatements($node, $parseResult, $recursive, $filePath);
        $this->processDynamicRequireStatements($node, $parseResult, $recursive, $filePath);
        $this->processClassDefinitions($node, $parseResult);
        
        $this->processTypeHints($node, $parseResult, $recursive);
        $this->processExtendedClasses($node, $parseResult, $recursive);
        $this->processImplementedInterfaces($node, $parseResult, $recursive);
        $this->processUsedTraits($node, $parseResult, $recursive);
        
        return $node;
    }

    /**
     * Process use statements from parse results.
     *
     * @param DependencyNode $node
     * @param array $parseResult
     * @param bool $recursive
     */
    private function processUseStatements(DependencyNode $node, array $parseResult, bool $recursive): void
    {
        foreach ($parseResult['useStatements'] as $useStatement) {
            $node->addUseStatement($useStatement);
            
            $resolvedPath = $this->resolver->resolveNamespace($useStatement);
            
            if ($resolvedPath && $recursive && !isset($this->processedFiles[$resolvedPath])) {
                $dependencyNode = $this->analyzeFile($resolvedPath, $recursive);
                $node->addDependency($dependencyNode);
            }
        }
    }

    /**
     * Process require statements from parse results.
     *
     * @param DependencyNode $node
     * @param array $parseResult
     * @param bool $recursive
     * @param string $filePath
     */
    private function processRequireStatements(DependencyNode $node, array $parseResult, bool $recursive, string $filePath): void
    {
        foreach ($parseResult['requireStatements'] as $requireStatement) {
            $node->addRequireStatement($requireStatement);
            
            if (file_exists($requireStatement)) {
                $resolvedPath = $requireStatement;
            } else {
                $resolvedPath = $this->resolver->resolveRequire($requireStatement, dirname($filePath));
            }
            
            if (!$this->includeAutoload && $this->isAutoloadFile($resolvedPath)) {
                continue;
            }
            
            if ($resolvedPath && $recursive && !isset($this->processedFiles[$resolvedPath])) {
                $dependencyNode = $this->analyzeFile($resolvedPath, $recursive);
                $node->addDependency($dependencyNode);
            }
        }
    }

    /**
     * Process dynamic require statements from parse results.
     *
     * @param DependencyNode $node
     * @param array $parseResult
     * @param bool $recursive
     * @param string $filePath
     */
    private function processDynamicRequireStatements(DependencyNode $node, array $parseResult, bool $recursive, string $filePath): void
    {
        foreach ($parseResult['dynamicRequireStatements'] as $dynamicRequireStatement) {
            $node->addDynamicRequireStatement($dynamicRequireStatement);
            
            if (isset($dynamicRequireStatement['expression']) && 
                is_string($dynamicRequireStatement['expression']) && 
                strpos($dynamicRequireStatement['expression'], ' . ') === false) {
                
                $resolvedPath = $this->resolver->resolveRequire($dynamicRequireStatement['expression'], dirname($filePath));
                
                if (!$this->includeAutoload && $this->isAutoloadFile($resolvedPath)) {
                    continue;
                }
                
                if ($resolvedPath && $recursive && !isset($this->processedFiles[$resolvedPath])) {
                    $dependencyNode = $this->analyzeFile($resolvedPath, $recursive);
                    $node->addDependency($dependencyNode);
                }
            }
        }
    }

    /**
     * Process class definitions from parse results.
     *
     * @param DependencyNode $node
     * @param array $parseResult
     */
    private function processClassDefinitions(DependencyNode $node, array $parseResult): void
    {
        foreach ($parseResult['classDefinitions'] as $classDefinition) {
            $node->addClassDefinition($classDefinition);
        }
    }

    /**
     * Process type hints from parse results.
     *
     * @param DependencyNode $node
     * @param array $parseResult
     * @param bool $recursive
     */
    private function processTypeHints(DependencyNode $node, array $parseResult, bool $recursive): void
    {
        if (!isset($parseResult['typeHints'])) {
            return;
        }
        
        foreach ($parseResult['typeHints'] as $typeHint) {
            if (strpos($typeHint, '|') !== false || strpos($typeHint, '&') !== false) {
                continue;
            }
            
            $resolvedPath = $this->resolver->resolveNamespace($typeHint);
            
            if ($resolvedPath && $recursive && !isset($this->processedFiles[$resolvedPath])) {
                $dependencyNode = $this->analyzeFile($resolvedPath, $recursive);
                $node->addDependency($dependencyNode);
            }
        }
    }

    /**
     * Process extended classes from parse results.
     *
     * @param DependencyNode $node
     * @param array $parseResult
     * @param bool $recursive
     */
    private function processExtendedClasses(DependencyNode $node, array $parseResult, bool $recursive): void
    {
        if (!isset($parseResult['extendedClasses'])) {
            return;
        }
        
        foreach ($parseResult['extendedClasses'] as $extendedClass) {
            $resolvedPath = $this->resolver->resolveNamespace($extendedClass);
            
            if ($resolvedPath && $recursive && !isset($this->processedFiles[$resolvedPath])) {
                $dependencyNode = $this->analyzeFile($resolvedPath, $recursive);
                $node->addDependency($dependencyNode);
            }
        }
    }

    /**
     * Process implemented interfaces from parse results.
     *
     * @param DependencyNode $node
     * @param array $parseResult
     * @param bool $recursive
     */
    private function processImplementedInterfaces(DependencyNode $node, array $parseResult, bool $recursive): void
    {
        if (!isset($parseResult['implementedInterfaces'])) {
            return;
        }
        
        foreach ($parseResult['implementedInterfaces'] as $implementedInterface) {
            $resolvedPath = $this->resolver->resolveNamespace($implementedInterface);
            
            if ($resolvedPath && $recursive && !isset($this->processedFiles[$resolvedPath])) {
                $dependencyNode = $this->analyzeFile($resolvedPath, $recursive);
                $node->addDependency($dependencyNode);
            }
        }
    }

    /**
     * Process used traits from parse results.
     *
     * @param DependencyNode $node
     * @param array $parseResult
     * @param bool $recursive
     */
    private function processUsedTraits(DependencyNode $node, array $parseResult, bool $recursive): void
    {
        if (!isset($parseResult['usedTraits'])) {
            return;
        }
        
        foreach ($parseResult['usedTraits'] as $usedTrait) {
            $resolvedPath = $this->resolver->resolveNamespace($usedTrait);
            
            if ($resolvedPath && $recursive && !isset($this->processedFiles[$resolvedPath])) {
                $dependencyNode = $this->analyzeFile($resolvedPath, $recursive);
                $node->addDependency($dependencyNode);
            }
        }
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
}
