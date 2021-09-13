<?php
namespace Vendimia\MadSS\Parser;

use Vendimia\MadSS\Node;
use Vendimia\MadSS\Parser\Css\Tokenizer;
use Vendimia\MadSS\Parser\Css\Parser;
use Vendimia\Interface\Path\ResourceLocatorInterface;

/** 
 * Parses a CSS/SCSS-like file
 */
class Css implements ParserInterface
{
    public function __construct(
        private ResourceLocatorInterface $resource_locator,
    )
    {

    }

    public function parse($source_file): Node
    {
        $tokenizer = new Tokenizer(file_get_contents($source_file));
        $parser = new Parser($tokenizer);

        return $parser->parse();
    }
}