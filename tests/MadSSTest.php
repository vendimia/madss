<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

use Vendimia\MadSS\MadSS;

final class MadSSTest extends TestCase
{
    public function testSimpleCss()
    {
        $css = "/*Esto es un comentario*/body {
            background: red;
        }";
        $expected = "body{background:red}";

        $madss = new MadSS;
        $this->assertEquals(
            $expected,
            $madss->processString($css, 'css'),
        );
    }

    public function testDeepSelectors()
    {
        $css = "body { p { strong { color: red; }}}";
        $expected = "body p strong{color:red}";

        $madss = new MadSS;
        $this->assertEquals(
            $expected,
            $madss->processString($css, 'css'),
        );
    }

    public function testStringInDeclaration() {
        $css = "p::before{content: '{message}'}";
        $expected = "p::before{content:'{message}'}";

        $madss = new MadSS;
        $this->assertEquals(
            $expected,
            $madss->processString($css, 'css'),
        );
    }

    public function testChildWithParentAndMultipleSelector() {
        $css = 'x { y, z { color : red } }';
        $expected = 'x y,x z{color:red}';
        $madss = new MadSS;
        $this->assertEquals(
            $expected,
            $madss->processString($css, 'css'),
        );

    }

    public function testChildWithExplicitParentAndMultipleSelector()
    {
        $css = "a, b { color: red; &:hover {color: blue}}";
        $expected = "a,b{color:red}\na:hover,b:hover{color:blue}";

        $madss = new MadSS;
        $this->assertEquals(
            $expected,
            $madss->processString($css, 'css'),
        );
    }

}