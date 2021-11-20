<?php
namespace Vendimia\MadSS\Parser\Css;

/**
 * CSS tokens
 */
enum Token
{
    case NAME;
    case AT_NAME;
    case PERCENT_NAME;
    case VALUE;
    case COLON;
    case SEMI_COLON;
    case BRACKET_OPEN;
    case BRACKET_CLOSE;
    case COMMENT;
}