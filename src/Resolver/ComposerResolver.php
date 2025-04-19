<?php

declare(strict_types=1);

namespace PhpDep\Resolver;

/**
 * Resolver for Composer autoloaded dependencies.
 */
class ComposerResolver
{
    /**
     * @var array PSR-4 namespace to directory mappings
     */
    private array $psr4Mappings = [];

    /**
     * @var array PSR-0 namespace to directory mappings
     */
    private array $psr0Mappings = [];

    /**
     * @var array Classmap mappings
     */
    private array $classMap = [];

    /**
     * @var string|null Project root directory
     */
    private ?string $rootDir = null;

    /**
     * @param string|null $composerJsonPath Path to composer.json file
     */
    public function __construct(?string $composerJsonPath = null)
    {
        $this->rootDir = $this->findProjectRoot($composerJsonPath);
        $this->loadComposerMappings($composerJsonPath);
    }

    /**
     * Find the project root directory (where composer.json is located).
     *
     * @param string|null $composerJsonPath
     * @return string|null
     */
    private function findProjectRoot(?string $composerJsonPath = null): ?string
    {
        if ($composerJsonPath && file_exists($composerJsonPath)) {
            return dirname($composerJsonPath);
        }
        
        $dir = getcwd();
        
        while ($dir !== '/' && $dir !== '') {
            if (file_exists($dir . '/composer.json')) {
                return $dir;
            }
            
            $dir = dirname($dir);
        }
        
        return null;
    }

    /**
     * Load Composer autoload mappings from composer.json and composer.lock.
     *
     * @param string|null $composerJsonPath
     */
    private function loadComposerMappings(?string $composerJsonPath = null): void
    {
        $composerJsonPath = $composerJsonPath ?? ($this->rootDir ? $this->rootDir . '/composer.json' : null);
        
        if (!$composerJsonPath || !file_exists($composerJsonPath)) {
            return;
        }
        
        $composerJson = json_decode(file_get_contents($composerJsonPath), true);
        
        if (!$composerJson || !isset($composerJson['autoload'])) {
            return;
        }
        
        // Load PSR-4 mappings
        if (isset($composerJson['autoload']['psr-4'])) {
            foreach ($composerJson['autoload']['psr-4'] as $namespace => $paths) {
                $paths = (array) $paths;
                
                foreach ($paths as $path) {
                    $this->psr4Mappings[$namespace] = $this->rootDir . '/' . $path;
                }
            }
        }
        
        // Load PSR-0 mappings
        if (isset($composerJson['autoload']['psr-0'])) {
            foreach ($composerJson['autoload']['psr-0'] as $namespace => $paths) {
                $paths = (array) $paths;
                
                foreach ($paths as $path) {
                    $this->psr0Mappings[$namespace] = $this->rootDir . '/' . $path;
                }
            }
        }
        
        // Load classmap
        if (isset($composerJson['autoload']['classmap'])) {
            // For simplicity, we're not implementing full classmap support here
            // In a real implementation, we would parse the classmap files
        }
        
        // For debugging
        // echo "PSR-4 Mappings: " . print_r($this->psr4Mappings, true) . "\n";
    }

    /**
     * Resolve a namespace to a file path.
     *
     * @param string $namespace
     * @return string|null
     */
    public function resolveNamespace(string $namespace): ?string
    {
        // Try PSR-4 first
        foreach ($this->psr4Mappings as $prefix => $dir) {
            if (strpos($namespace, $prefix) === 0) {
                $relativeClass = substr($namespace, strlen($prefix));
                $filePath = $dir . '/' . str_replace('\\', '/', $relativeClass) . '.php';
                
                if (file_exists($filePath)) {
                    return $filePath;
                }
            }
        }
        
        // Try PSR-0
        foreach ($this->psr0Mappings as $prefix => $dir) {
            if (strpos($namespace, $prefix) === 0) {
                $relativeClass = substr($namespace, strlen($prefix));
                $filePath = $dir . '/' . str_replace(['\\', '_'], '/', $relativeClass) . '.php';
                
                if (file_exists($filePath)) {
                    return $filePath;
                }
            }
        }
        
        // Try classmap (simplified)
        if (isset($this->classMap[$namespace])) {
            return $this->classMap[$namespace];
        }
        
        // Special handling for examples directory
        if (strpos($namespace, 'App\\') === 0) {
            $relativeClass = substr($namespace, strlen('App\\'));
            $examplesDir = dirname($this->rootDir) . '/examples';
            
            if (is_dir($examplesDir)) {
                $filePath = $examplesDir . '/src/' . str_replace('\\', '/', $relativeClass) . '.php';
                
                if (file_exists($filePath)) {
                    return $filePath;
                }
            }
        }
        
        return null;
    }

    /**
     * Resolve a require/include statement to a file path.
     *
     * @param string $requirePath
     * @param string $currentDir
     * @return string|null
     */
    public function resolveRequire(string $requirePath, string $currentDir): ?string
    {
        // Absolute path
        if ($requirePath[0] === '/') {
            return file_exists($requirePath) ? $requirePath : null;
        }
        
        // Relative path
        $absolutePath = $currentDir . '/' . $requirePath;
        
        if (file_exists($absolutePath)) {
            return $absolutePath;
        }
        
        // Try with .php extension
        if (!str_ends_with($requirePath, '.php')) {
            $absolutePath = $currentDir . '/' . $requirePath . '.php';
            
            if (file_exists($absolutePath)) {
                return $absolutePath;
            }
        }
        
        return null;
    }
}
