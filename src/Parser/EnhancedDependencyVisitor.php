<?php

namespace PhpDep\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Enhanced visitor to collect dependencies with better detection for modern PHP codebases.
 */
class EnhancedDependencyVisitor extends DependencyVisitor
{
    /**
     * @var array Type hints collected from method parameters and return types
     */
    private array $typeHints = [];

    /**
     * @var array Extended classes
     */
    private array $extendedClasses = [];

    /**
     * @var array Implemented interfaces
     */
    private array $implementedInterfaces = [];

    /**
     * @var array Used traits
     */
    private array $usedTraits = [];

    /**
     * Reset the visitor.
     */
    public function reset(): void
    {
        parent::reset();
        $this->typeHints = [];
        $this->extendedClasses = [];
        $this->implementedInterfaces = [];
        $this->usedTraits = [];
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        parent::enterNode($node);
        
        if ($node instanceof Node\Stmt\Class_) {
            if ($node->extends !== null) {
                $this->extendedClasses[] = $node->extends->toString();
            }
            
            foreach ($node->implements as $interface) {
                $this->implementedInterfaces[] = $interface->toString();
            }
        }
        
        if ($node instanceof Node\Stmt\Interface_) {
            foreach ($node->extends as $interface) {
                $this->extendedClasses[] = $interface->toString();
            }
        }
        
        if ($node instanceof Node\Stmt\TraitUse) {
            foreach ($node->traits as $trait) {
                $this->usedTraits[] = $trait->toString();
            }
        }
        
        if ($node instanceof Node\Stmt\ClassMethod) {
            if ($node->returnType !== null) {
                $returnType = $this->getTypeAsString($node->returnType);
                if ($returnType !== null) {
                    $this->typeHints[] = $returnType;
                }
            }
            
            foreach ($node->params as $param) {
                if ($param->type !== null) {
                    $paramType = $this->getTypeAsString($param->type);
                    if ($paramType !== null) {
                        $this->typeHints[] = $paramType;
                    }
                }
            }
        }
        
        if ($node instanceof Node\Stmt\Property) {
            if ($node->type !== null) {
                $propertyType = $this->getTypeAsString($node->type);
                if ($propertyType !== null) {
                    $this->typeHints[] = $propertyType;
                }
            }
        }
        
        return null;
    }

    /**
     * Convert a type node to a string representation.
     *
     * @param Node\Identifier|Node\Name|Node\NullableType|Node\UnionType|Node\IntersectionType $type
     * @return string|null
     */
    private function getTypeAsString($type): ?string
    {
        if ($type instanceof Node\Name) {
            return $type->toString();
        }
        
        if ($type instanceof Node\Identifier) {
            $primitiveTypes = ['string', 'int', 'float', 'bool', 'array', 'callable', 'iterable', 'object', 'mixed', 'void', 'never'];
            if (in_array($type->toString(), $primitiveTypes)) {
                return null;
            }
            return $type->toString();
        }
        
        if ($type instanceof Node\NullableType) {
            return $this->getTypeAsString($type->type);
        }
        
        if ($type instanceof Node\UnionType) {
            $types = [];
            foreach ($type->types as $subType) {
                $typeString = $this->getTypeAsString($subType);
                if ($typeString !== null) {
                    $types[] = $typeString;
                }
            }
            return !empty($types) ? implode('|', $types) : null;
        }
        
        if ($type instanceof Node\IntersectionType) {
            $types = [];
            foreach ($type->types as $subType) {
                $typeString = $this->getTypeAsString($subType);
                if ($typeString !== null) {
                    $types[] = $typeString;
                }
            }
            return !empty($types) ? implode('&', $types) : null;
        }
        
        return null;
    }

    /**
     * Get the collected type hints.
     *
     * @return array
     */
    public function getTypeHints(): array
    {
        return array_unique($this->typeHints);
    }

    /**
     * Get the collected extended classes.
     *
     * @return array
     */
    public function getExtendedClasses(): array
    {
        return array_unique($this->extendedClasses);
    }

    /**
     * Get the collected implemented interfaces.
     *
     * @return array
     */
    public function getImplementedInterfaces(): array
    {
        return array_unique($this->implementedInterfaces);
    }

    /**
     * Get the collected used traits.
     *
     * @return array
     */
    public function getUsedTraits(): array
    {
        return array_unique($this->usedTraits);
    }
}
