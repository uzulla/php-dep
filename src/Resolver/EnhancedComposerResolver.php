<?php

declare(strict_types=1);

namespace PhpDep\Resolver;

/**
 * Enhanced resolver for Composer autoloaded dependencies with better support for modern PHP codebases.
 */
class EnhancedComposerResolver extends ComposerResolver
{
    /**
     * @var array Classmap from autoload_classmap.php
     */
    private array $autoloadClassmap = [];

    /**
     * @var array PSR-4 mappings from autoload_psr4.php
     */
    private array $autoloadPsr4 = [];

    /**
     * @var array PSR-0 mappings from autoload_namespaces.php
     */
    private array $autoloadPsr0 = [];

    /**
     * @param string|null $composerJsonPath Path to composer.json file
     */
    public function __construct(?string $composerJsonPath = null)
    {
        parent::__construct($composerJsonPath);
        $this->loadAutoloadFiles();
    }

    /**
     * Load Composer autoload files for better class resolution.
     */
    private function loadAutoloadFiles(): void
    {
        $rootDir = $this->getRootDir();
        
        if (!$rootDir) {
            return;
        }
        
        $vendorDir = $rootDir . '/vendor';
        
        $classmapFile = $vendorDir . '/composer/autoload_classmap.php';
        if (file_exists($classmapFile)) {
            $this->autoloadClassmap = require $classmapFile;
        }
        
        $psr4File = $vendorDir . '/composer/autoload_psr4.php';
        if (file_exists($psr4File)) {
            $this->autoloadPsr4 = require $psr4File;
        }
        
        $psr0File = $vendorDir . '/composer/autoload_namespaces.php';
        if (file_exists($psr0File)) {
            $this->autoloadPsr0 = require $psr0File;
        }
    }

    /**
     * Get the project root directory.
     *
     * @return string|null
     */
    public function getRootDir(): ?string
    {
        $reflection = new \ReflectionClass(ComposerResolver::class);
        $property = $reflection->getProperty('rootDir');
        $property->setAccessible(true);
        
        return $property->getValue($this);
    }

    /**
     * Resolve a namespace to a file path with enhanced support for autoloaded classes.
     *
     * @param string $namespace
     * @return string|null
     */
    public function resolveNamespace(string $namespace): ?string
    {
        if (isset($this->autoloadClassmap[$namespace])) {
            return $this->autoloadClassmap[$namespace];
        }
        
        foreach ($this->autoloadPsr4 as $prefix => $dirs) {
            if (strpos($namespace, $prefix) === 0) {
                $relativeClass = substr($namespace, strlen($prefix));
                
                foreach ((array)$dirs as $dir) {
                    $filePath = $dir . '/' . str_replace('\\', '/', $relativeClass) . '.php';
                    
                    if (file_exists($filePath)) {
                        return $filePath;
                    }
                }
            }
        }
        
        foreach ($this->autoloadPsr0 as $prefix => $dirs) {
            if (strpos($namespace, $prefix) === 0) {
                $relativeClass = substr($namespace, strlen($prefix));
                
                foreach ((array)$dirs as $dir) {
                    $filePath = $dir . '/' . str_replace(['\\', '_'], '/', $relativeClass) . '.php';
                    
                    if (file_exists($filePath)) {
                        return $filePath;
                    }
                }
            }
        }
        
        return parent::resolveNamespace($namespace);
    }
}
