<?php

spl_autoload_register(function ($class) {
    $prefix = 'Taxi\\';
    $baseDir = __DIR__ . '/../';

    if (strpos($class, $prefix) === 0) {
        $relative = substr($class, strlen($prefix));
        $file = $baseDir . 'src/' . str_replace('\\', '/', $relative) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }
});
