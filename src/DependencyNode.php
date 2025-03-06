<?php

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
}
