<?php
require '../vendor/autoload.php';

$madss = new Vendimia\MadSS\MadSS;

$madss->addSourceFiles('test-1.scss', 'test-2.scss');
//$madss->addSourceFiles('test-3.scss', 'test-3.scss', 'test-3.scss');
var_dump($madss->process());