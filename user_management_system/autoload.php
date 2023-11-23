<?php
spl_autoload_register(function($class){
    // All classes are in root namespace, so no need to address namespaces here, but it would be something similar to --- $file = join(FILE_SEPARATOR, explode("\", $class)) . "php";
    $path = "./" . $class . ".php";
    if(file_exists($path)) {
        require($path);
    } else {
        die(json_encode(['error' => 'INVALID REQUIRED FILE PATH: ' . $path]));
    }
});

date_default_timezone_set("America/Chicago");