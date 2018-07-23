<?php

use Ganlv\MfencDecompiler\AutoDecompiler;
use Ganlv\MfencDecompiler\Helper;

require __DIR__ . '/../vendor/autoload.php';

ini_set('xdebug.max_nesting_level', 1000000);

$input_file = $argv[1];
$options = getopt('o');
if (isset($options['o'])) {
    $output_file = $options['o'];
} else {
    $output_file = preg_replace('/\\.php$/', '', $input_file) . '.rebuilt.php';
    $ast_file = preg_replace('/\\.php$/', '', $input_file) . '.ast.bin';
}

if (!file_exists($ast_file)) {
    $code = file_get_contents($input_file);
    $ast = Helper::parseCode($code);
    file_put_contents($ast_file, serialize($ast));
} else {
    $ast = unserialize(file_get_contents($ast_file));
}

file_put_contents($output_file, Helper::prettyPrintFile(AutoDecompiler::autoDecompileAst($ast)));
