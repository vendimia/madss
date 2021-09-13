<?php
namespace Vendimia\MadSS\Parser;

use Vendimia\MadSS\Node;
use Vendimia\Interface\Path\ResourceLocatorInterface;

interface ParserInterface 
{
    public function __construct(ResourceLocatorInterface $resource_locator);

    public function parse($source_file): Node;
}