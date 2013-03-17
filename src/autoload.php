<?php

namespace gcatlin\gossamer;

spl_autoload_register(function ($class) {
    static $map;
    if ($map === null) {
        $ns = (__NAMESPACE__ ? __NAMESPACE__ . '\\' : '');
        $map = array(
            $ns . 'Env'             => 'Env.php',
            $ns . 'Gossamer'        => 'Gossamer.php',
            $ns . 'Request'         => 'Request.php',
            $ns . 'RequestHandler'  => 'RequestHandler.php',
            $ns . 'Response'        => 'Response.php',
            $ns . 'Uri'             => 'Uri.php',
            $ns . 'WsgiApplication' => 'WsgiApplication.php',
        );
    }

    if (isset($map[$class])) {
        include_once __DIR__ . DIRECTORY_SEPARATOR . $map[$class];
    }
});

