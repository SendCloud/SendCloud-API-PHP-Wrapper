<?php
spl_autoload_register(function ($className) {
    $sourceDir = dirname(dirname(__FILE__)) . '/src/';
    $classFile = realpath($sourceDir . '/SendCloudApi.php');

    if (preg_match('/^SendCloud/', $className) && $classFile) {
        require_once $classFile;
    }
});
