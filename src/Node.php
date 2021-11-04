<?php
namespace Vendimia\MadSS;

/**
 * CSS Node definition.
 */
class Node
{
    const T_COMMENT = 'comment';
    const T_NAME = 'name';
    const T_AT_NAME = 'at_name';

    /* True if this is the root node */
    private bool $root = false;

    /* This node parent */
    private ?Node $parent = null;

    /* Previous sibiling in the chain */
    private ?Node $prev = null;

    /* Next sibiling in the chain */
    private ?Node $next = null;

    /* First child */
    private ?Node $first = null;

    /* Last child */
    private ?Node $last = null;

    /* Child count */
    private $node_count = 0;

    /* Name constructed joining the parents' names */
    private $full_name = [];

    public function __construct(
        /* Node type. Can be 'node' or 'comment' */
        private string $type = self::T_NAME,

        /* Node name */
        private ?string $name = null,

        /* Node value */
        private ?string $value = null,
    )
    {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType($type): self
    {
        $this->type = $type;
        return $this;
    }

    public function setName($name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setValue($value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Sets previous and next nodes for this node
     */
    public function setSiblings(?Node $prev, ?Node $next): Node
    {
        $this->prev = $prev;
        $this->next = $next;

        $prev?->setNext($this);
        $next?->setPrev($this);

        return $this;
    }

    public function setParent(Node $parent): self
    {
        return $this->parent = $parent;
        return $this;
    }

    public function getParent(): ?Node
    {
        return $this->parent;
    }


    public function getName(): ?array
    {
        if (!$this->name) {
            return $this->name;
        }
        return array_map(trim(...), explode(',', $this->name));
    }

    public function buildFullParentName()
    {
        $name_tree = [];

        // Nombres de un nodo hijo que deberá ser fusionado con el padre,
        // reemplazando el '&'
        $merge_name = [];

        // Si hay un @media en algún nodo, este se moverá al inicio del árbol
        $at_media = '';

        // Sacamos los nombres de todos los padres.
        $node = $this;
        while ($node = $node->getParent()) {

            // Si el nodo no tiene nombre, es root. Salimos
            if (!$name = $node->getName()) {
                break;
            }

            // Si es un '@media', lo movemos al inicio
            if (str_contains(strtolower($name[0]), '@media')) {
                $at_media = $node->getCss();
                continue;
            }

            if ($merge_name) {
                // Reemplazamos
                $result = [];
                foreach ($merge_name as $mn) {
                    foreach ($name as $n) {
                        $result[] = str_replace('&', $n, $mn);
                    }
                }
                $name = $result;
                $merge_name = [];
            }

            // Si $name tiene un '&', entonces debe ser mezclado con el de su
            // padre
            if (str_contains(join(',', $name), '&')) {
                $merge_name = $name;
            } else {
                $name_tree[] = $name;
            }
        }

        if ($at_media) {
            $name_tree[] = $at_media;
        }

        $name_tree = array_reverse($name_tree);

        return $this->full_name = $name_tree;
    }

    public function getFullName(): string
    {
        return $this->full_name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getFirst(): ?Node
    {
        return $this->first;
    }

    public function getLast(): ?Node
    {
        return $this->last;
    }

    public function getNext(): ?Node
    {
        return $this->next;
    }

    public function getPrev(): ?Node
    {
        return $this->prev;
    }

    public function setFirst(Node $node): Node
    {
        $this->first = $node;
        return $this;
    }

    public function setLast(Node $node): Node
    {
        $this->last = $node;
        return $this;
    }

    public function setPrev(Node $node): Node
    {
        $this->prev = $node;
        return $this;
    }

    public function setNext(Node $node): Node
    {
        $this->next = $node;
        return $this;
    }

    public function hasChildren(): bool
    {
        return !is_null($this->first) || !is_null($this->last);
    }

    /**
     * Adds a child node to the end of the chain
     */
    public function addChild(Node $node): Node
    {
        if ($this->node_count == 0) {
            $this->first = $node;
            $this->last = $node;
        } elseif ($this->node_count == 1) {
            $this->first->setSiblings(null, $node);
            $node->setSiblings($this->first, null);
            $this->last = $node;
        } else {
            $this->last->setSiblings($this->last->getPrev(), $node);
            $this->last = $node;
        }

        $node->setParent($this);

        $this->node_count++;

        return $node;
    }

    /**
     * Yields every children of this node
     */
    public function getChildren()
    {
        $node = $this->first;

        do {
            yield $node;
        } while ($node = $node->getNext());
    }

    /**
     * Returns this node and his children with self::$full_name setted
     */
    public function getStraightenedNodes(): array
    {

        // Si no tiene hijos, retornamos el nodo
        if (!$this->hasChildren()) {

            $name = $this->buildFullParentName();
            $name[] = $this->getCss();
            return [$name];
        }

        $return = [];

        foreach ($this->getChildren() as $node) {
            // Si tiene hijos, la versión 'plana' de los hijos la vamos uniendo
            // al array
            $return = array_merge($return, $node->getStraightenedNodes());
        }

        return $return;
    }

    /**
     * Returns the CSS representation of this node name and value
     */
    public function getCss(): string
    {
        $name = $this->name;

        if ($this->type == self::T_AT_NAME) {
            $return = $name;
            if ($this->value) {
                $return .= ' ' . $this->value;
            }
        } else {
            $return = $name;

            if ($this->value) {
                $return .= ':' . $this->value;
            }
        }

        return $return;
    }

    public function __toString()
    {
        return "{$this->name}: {$this->value}";
    }
}