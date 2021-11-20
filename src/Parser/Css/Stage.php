<?php
namespace Vendimia\MadSS\Parser\Css;

/**
 * Parsing stages
 */
enum Stage {
    case WAITING;
    case IDENTIFIER;
    case COMMENT;
    case STRING;
}