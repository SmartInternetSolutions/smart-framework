<?php

// oh dear, don't do this live.

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (isset($uri) && file_exists($fn = __DIR__ . '/' . $uri) && !is_dir($fn)) {
    return false;
} else { 
    $_GET['uri'] = $_SERVER["REQUEST_URI"];
    
    include('index.php');
}