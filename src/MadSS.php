<?php
namespace Vendimia\MadSS;

use Vendimia\MadSS\Parser;
use Vendimia\Interface\Path\ResourceLocatorInterface;
use RuntimeException;
use InvalidArgumentException;

/** 
 * MadSS - CSS preprocessor 
 */
class MadSS
{
    private array $source_files = [];

    /** Base parser classes according to file extension */     
    private array $registered_parsers = [
        'css' => Parser\Css::class,
        'scss' => Parser\Css::class,
        'yaml' => Parser\Yaml::class,
    ];

    public function __construct(
        ?ResourceLocatorInterface $resource_locator = null
    )
    {
        // Si no especificamos un ResourceLocator, usamos la implementación por defecto
        if (!$resource_locator) {
            $resource_locator = new ResourceLocator;
        }

        $this->resource_locator = $resource_locator;
    }

    /** 
     * Adds one or more CSS source files, for processing
     */
    public function addSourceFiles(...$files)
    {
        $this->source_files += $files;
    }

    /** 
     * Reads and process all the CSS sources, returns the processed CSS
     */
    public function process(): string
    {
        $root = new Node;

        // Paso 1: Obtener todos los nodos de todos los ficheros
        foreach ($this->source_files as $file) {
            $file_path = $this->resource_locator->find($file);

            // Si un fichero no existe, lanzamos una excepción
            if (is_null($file_path)) {
                throw new InvalidArgumentException("CSS source file '$file' not found");
            }

            // FIXME: Esto debería mejorar. Usamos la extensión para determinar
            // el parser
            $ext = strtolower(substr($file_path, strrpos($file_path, '.') + 1));
            
            $parser_class = $this->registered_parsers[$ext] ?? null;

            if (is_null($parser_class)) {
                throw new RuntimeException("No parser is registered for CSS source file {$file}");
            }

            $parser = new $parser_class($this->resource_locator);
            $nodes = $parser->parse($file_path);

            // Movemos todos los nodos a la nueva raiz
            foreach ($nodes->getChildren() as $node) {
                // Si child es null, no hay contenido...
                if (is_null($node)) {
                    break;
                }

                $child = clone $node;

                $child->setParent($root);

                // Si no tiene un first child, $root está vacío
                if (!$root->getFirst()) {
                    $root->setFirst($child);
                    $root->setLast($child);
                    $child->setSiblings(null, null);
                } else {
                    $child->setSiblings($root->getLast(), $child->getNext());
    
                    $root->setLast($child);
                }
            }
        }

        // Paso 2: Agrupar declaraciones por el nombre de los padres.

        // Declarations tiene 3 niveles:
        // - at_media
        // - parents
        // - declaration
        $declarations = [];

        $normal = [];
        $at_media = [];

        $last_parent = '';
        foreach ($root->getStraightenedNodes() as $line) {
            if (str_starts_with($line[0], '@media')) {
                $media = $line[0];
                $parent = join(' ', array_slice($line, 1, -1));
                $declaration = array_slice($line, -1)[0];

                //$at_media[$media][$parent][] = $declaration;
                $declarations[$media][$parent][] = $declaration;

            } else {
                $parent = join(' ', array_slice($line, 0, -1));
                $declaration = array_slice($line, -1)[0];
    
                //$normal[$parent][] = $declaration;
                $declarations[''][$parent][] = $declaration;
            }
        }

        // Paso 3: Dibujar el CSS
        $css = '';

 
        foreach ($declarations as $at_media => $data) {
            if ($at_media) {
                $css .= $at_media . '{';
            }
            foreach ($data as $name => $elements) {
                $css .= $name;
    
                // Los grupos que no tienen nombre, no llevan llaves
    
                if ($name) {
                    $css .= '{' . join(';', $elements) . '}';
                } else {
                    $css .= join(';', $elements) . ';';
                }

                // Solo para colocarlo bonito, los at_media tienen su propio \n
                if (!$at_media) {
                    $css .= "\n";
                }
                
            }

            if ($at_media) {
                $css .= "}\n";
            } 
        }

 
        return $css;
    }
}