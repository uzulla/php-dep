<?php

declare(strict_types=1);

namespace PhpDep;

/**
 * Represents a node in the dependency tree.
 */
class DependencyNode
{
    /**
     * @var string The file path of this node
     */
    private string $filePath;

    /**
     * @var DependencyNode[] Child dependencies of this node
     */
    private array $dependencies = [];

    /**
     * @var array Parsed use statements from this file
     */
    private array $useStatements = [];

    /**
     * @var array Parsed require/include statements from this file
     */
    private array $requireStatements = [];

    /**
     * @var array Parsed dynamic require/include statements from this file
     */
    private array $dynamicRequireStatements = [];

    /**
     * @var array Parsed class definitions from this file
     */
    private array $classDefinitions = [];

    /**
     * @var string|null The content of the file
     */
    private ?string $fileContent = null;

    /**
     * @param string $filePath The file path of this node
     * @param string|null $fileContent The content of the file
     */
    public function __construct(string $filePath, ?string $fileContent = null)
    {
        $this->filePath = $filePath;
        $this->fileContent = $fileContent;
    }

    /**
     * Get the file path of this node.
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * Check if the file content has been loaded.
     *
     * @return bool
     */
    public function hasFileContent(): bool
    {
        return $this->fileContent !== null;
    }

    /**
     * Get the file content of this node.
     *
     * @return string|null
     */
    public function getFileContent(): ?string
    {
        if ($this->fileContent === null && file_exists($this->filePath)) {
            $this->fileContent = file_get_contents($this->filePath);
        }
        
        return $this->fileContent;
    }

    /**
     * Set the file content of this node.
     *
     * @param string $fileContent
     * @return self
     */
    public function setFileContent(string $fileContent): self
    {
        $this->fileContent = $fileContent;
        return $this;
    }

    /**
     * Add a dependency to this node.
     *
     * @param DependencyNode $dependency
     * @return self
     */
    public function addDependency(DependencyNode $dependency): self
    {
        $this->dependencies[$dependency->getFilePath()] = $dependency;
        return $this;
    }

    /**
     * Get all dependencies of this node.
     *
     * @return DependencyNode[]
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * Add a use statement to this node.
     *
     * @param string $useStatement
     * @return self
     */
    public function addUseStatement(string $useStatement): self
    {
        $this->useStatements[] = $useStatement;
        return $this;
    }

    /**
     * Get all use statements of this node.
     *
     * @return array
     */
    public function getUseStatements(): array
    {
        return $this->useStatements;
    }

    /**
     * Add a require statement to this node.
     *
     * @param string $requireStatement
     * @return self
     */
    public function addRequireStatement(string $requireStatement): self
    {
        $this->requireStatements[] = $requireStatement;
        return $this;
    }

    /**
     * Get all require statements of this node.
     *
     * @return array
     */
    public function getRequireStatements(): array
    {
        return $this->requireStatements;
    }

    /**
     * Add a dynamic require statement to this node.
     *
     * @param array $dynamicRequireStatement
     * @return self
     */
    public function addDynamicRequireStatement(array $dynamicRequireStatement): self
    {
        $this->dynamicRequireStatements[] = $dynamicRequireStatement;
        return $this;
    }

    /**
     * Get all dynamic require statements of this node.
     *
     * @return array
     */
    public function getDynamicRequireStatements(): array
    {
        return $this->dynamicRequireStatements;
    }

    /**
     * Add a class definition to this node.
     *
     * @param string $className
     * @return self
     */
    public function addClassDefinition(string $className): self
    {
        $this->classDefinitions[] = $className;
        return $this;
    }

    /**
     * Get all class definitions of this node.
     *
     * @return array
     */
    public function getClassDefinitions(): array
    {
        return $this->classDefinitions;
    }

    /**
     * Get all dependency file paths recursively.
     *
     * @return array
     */
    public function getAllDependencyPaths(): array
    {
        $paths = [];
        
        foreach ($this->dependencies as $dependency) {
            $paths[] = $dependency->getFilePath();
            $paths = array_merge($paths, $dependency->getAllDependencyPaths());
        }
        
        return array_unique($paths);
    }

    /**
     * Get all dependency nodes recursively.
     *
     * @return DependencyNode[]
     */
    public function getAllDependencyNodes(): array
    {
        $nodes = [];
        
        foreach ($this->dependencies as $dependency) {
            $nodes[$dependency->getFilePath()] = $dependency;
            $nodes = array_merge($nodes, $dependency->getAllDependencyNodes());
        }
        
        return $nodes;
    }

    /**
     * Generate a Markdown representation of this node and its dependencies.
     *
     * @return string
     */
    public function toMarkdown(): string
    {
        $markdown = "# File: " . $this->getFilePath() . "\n\n";
        $markdown .= "```php\n" . $this->getFileContent() . "\n```\n\n";
        
        // Add dynamic require statements if any
        if (!empty($this->dynamicRequireStatements)) {
            $markdown .= "## Dynamic Dependencies\n\n";
            $markdown .= "These dependencies could not be resolved because they use dynamic file paths:\n\n";
            
            foreach ($this->dynamicRequireStatements as $dynamicRequire) {
                $markdown .= "- " . $dynamicRequire['type'] . " `" . $dynamicRequire['expression'] . "`\n";
            }
            
            $markdown .= "\n";
        }
        
        $markdown .= "## Dependencies\n\n";
        
        $dependencies = $this->getAllDependencyNodes();
        
        if (empty($dependencies)) {
            $markdown .= "No dependencies found.\n\n";
        } else {
            foreach ($dependencies as $dependency) {
                $markdown .= "### File: " . $dependency->getFilePath() . "\n\n";
                $markdown .= "```php\n" . $dependency->getFileContent() . "\n```\n\n";
                
                // Add dynamic require statements for this dependency if any
                $dynamicRequires = $dependency->getDynamicRequireStatements();
                if (!empty($dynamicRequires)) {
                    $markdown .= "#### Dynamic Dependencies\n\n";
                    $markdown .= "These dependencies could not be resolved because they use dynamic file paths:\n\n";
                    
                    foreach ($dynamicRequires as $dynamicRequire) {
                        $markdown .= "- " . $dynamicRequire['type'] . " `" . $dynamicRequire['expression'] . "`\n";
                    }
                    
                    $markdown .= "\n";
                }
            }
        }
        
        return $markdown;
    }

    /**
     * Generate a tree representation of this node and its dependencies.
     *
     * @param bool $useFullPath Whether to use full file paths or try to convert to FQCN
     * @param array|null $sourceDirs Custom source directories to use for FQCN conversion
     * @return string
     */
    public function toTree(bool $useFullPath = false, ?array $sourceDirs = null): string
    {
        return $this->generateTreeOutput($this, '', true, [], $useFullPath, $sourceDirs);
    }

    /**
     * Helper method to generate the tree output recursively.
     *
     * @param DependencyNode $node The current node
     * @param string $prefix The prefix for the current line
     * @param bool $isLast Whether this is the last child of its parent
     * @param array $visited Array of visited file paths to avoid infinite recursion
     * @param bool $useFullPath Whether to use full file paths or try to convert to FQCN
     * @param array|null $sourceDirs Custom source directories to use for FQCN conversion
     * @return string
     */
    private function generateTreeOutput(
        DependencyNode $node, 
        string $prefix, 
        bool $isLast, 
        array $visited, 
        bool $useFullPath,
        ?array $sourceDirs = null
    ): string {
        $filePath = $node->getFilePath();
        
        if (in_array($filePath, $visited)) {
            return '';
        }
        
        $visited[] = $filePath;
        
        $displayPath = $useFullPath ? $filePath : $this->filePathToFQCN($filePath, $sourceDirs);
        
        $result = $prefix . ($isLast ? '└── ' : '├── ') . $displayPath . PHP_EOL;
        
        $dependencies = $node->getDependencies();
        uksort($dependencies, 'strnatcmp');
        
        $i = 0;
        $dependencyCount = count($dependencies);
        
        foreach ($dependencies as $dependency) {
            $isLastDependency = (++$i === $dependencyCount);
            $newPrefix = $prefix . ($isLast ? '    ' : '│   ');
            $result .= $this->generateTreeOutput(
                $dependency, 
                $newPrefix, 
                $isLastDependency, 
                $visited, 
                $useFullPath,
                $sourceDirs
            );
        }
        
        return $result;
    }
    
    /**
     * Find all PHP files in a directory recursively.
     *
     * @param string $dir
     * @return array
     */
    private function findPhpFiles(string $dir): array
    {
        $phpFiles = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }
        
        return $phpFiles;
    }


    /**
     * Convert a file path to a FQCN-like format.
     *
     * @param string $filePath
     * @param array|null $sourceDirs Custom source directories to check
     * @return string
     */
    private function filePathToFQCN(string $filePath, ?array $sourceDirs = null): string
    {
        $path = preg_replace('/\.php$/', '', $filePath);
        
        $composerSourceDirs = $this->getComposerSourceDirs($filePath);
        
        $sourceDirectories = $sourceDirs ?? $composerSourceDirs ?? [
            '/src/', 
            '/lib/', 
            '/app/', 
            '/classes/', 
            '/include/', 
            '/source/',
            '/core/',
            '/modules/'
        ];
        
        $originalPath = $path;
        foreach ($sourceDirectories as $sourceDir) {
            $sourceDir = '/' . trim($sourceDir, '/') . '/';
            $pos = strpos($path, $sourceDir);
            if ($pos !== false) {
                $path = substr($path, $pos + strlen($sourceDir));
                break;
            }
        }
        
        if ($path === $originalPath) {
            $pathParts = explode('/', $path);
            array_pop($pathParts); // Remove filename
            if (!empty($pathParts)) {
                $lastDir = end($pathParts);
                if (preg_match('/^[A-Z]/', $lastDir)) {
                    $path = $lastDir . '/' . basename($path);
                }
            }
        }
        
        $path = str_replace('/', '\\', $path);
        
        return $path;
    }
    
    /**
     * Get source directories from composer.json if available.
     *
     * @param string $filePath Path to a file in the project
     * @return array|null Array of source directories or null if composer.json not found
     */
    private function getComposerSourceDirs(string $filePath): ?array
    {
        $dir = dirname($filePath);
        $sourceDirs = [];
        
        while ($dir !== '/' && strlen($dir) > 1) {
            $composerPath = $dir . '/composer.json';
            if (file_exists($composerPath)) {
                $composerJson = json_decode(file_get_contents($composerPath), true);
                if (json_last_error() === JSON_ERROR_NONE && isset($composerJson['autoload'])) {
                    if (isset($composerJson['autoload']['psr-4'])) {
                        foreach ($composerJson['autoload']['psr-4'] as $namespace => $path) {
                            if (is_array($path)) {
                                foreach ($path as $subPath) {
                                    $sourceDirs[] = '/' . trim($subPath, '/') . '/';
                                }
                            } else {
                                $sourceDirs[] = '/' . trim($path, '/') . '/';
                            }
                        }
                    }
                    
                    if (isset($composerJson['autoload']['psr-0'])) {
                        foreach ($composerJson['autoload']['psr-0'] as $namespace => $path) {
                            if (is_array($path)) {
                                foreach ($path as $subPath) {
                                    $sourceDirs[] = '/' . trim($subPath, '/') . '/';
                                }
                            } else {
                                $sourceDirs[] = '/' . trim($path, '/') . '/';
                            }
                        }
                    }
                    
                    if (isset($composerJson['autoload']['classmap'])) {
                        foreach ($composerJson['autoload']['classmap'] as $path) {
                            $sourceDirs[] = '/' . trim($path, '/') . '/';
                        }
                    }
                    
                    return !empty($sourceDirs) ? $sourceDirs : null;
                }
                break;
            }
            $dir = dirname($dir);
        }
        
        return null;
    }
}
