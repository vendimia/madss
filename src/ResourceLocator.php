<?php
namespace Vendimia\MadSS;

use Vendimia\Interface\Path\ResourceLocatorInterface;

/** 
 * Default FileLocator implementation
 */
class ResourceLocator implements ResourceLocatorInterface
{
    public function find($name): ?string
    {
        return $name;
    }
}
