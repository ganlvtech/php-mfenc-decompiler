<?php

use Ganlv\MfencDecompiler\Formatter;
use Ganlv\MfencDecompiler\Helper;

require __DIR__ . '/../vendor/autoload.php';

ini_set('xdebug.max_nesting_level', 1000000);

$input_file = $argv[1];
$options = getopt('o');
if (isset($options['o'])) {
    $output_file = $options['o'];
} else {
    $output_file = preg_replace('/\\.php$/', '', $input_file) . '.formatted.php';
}
$variables_map_file = preg_replace('/\\.php$/', '', $output_file) . '.variables_map.php';
$large_string_data_file = preg_replace('/\\.php$/', '', $output_file) . '.large_string_data.php';

$code = file_get_contents($input_file);
$formatter = new Formatter();
$ast = $formatter->format($code, basename($large_string_data_file));
file_put_contents($output_file, Helper::prettyPrintFile($ast));
Helper::exportArray($variables_map_file, $formatter->getVariablesMap());
Helper::exportArray($large_string_data_file, $formatter->getLargeStringData());
