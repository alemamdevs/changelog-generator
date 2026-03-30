<?php
require __DIR__ . '/../vendor/autoload.php';

var_export(class_exists('App\\Providers\\ChangelogServiceProvider'));

// Try to include file path
$file = __DIR__ . '/../app/Providers/ChangelogServiceProvider.php';
var_export(file_exists($file));
var_export(realpath($file));

?>
