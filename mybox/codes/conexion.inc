<?php
    // connection params
    $servidor  = "localhost";
    $basedatos = "mybox";
    $usuario   = getenv('DB_USER');
    $contra    = getenv('DB_PASS');

    // warning suppress
    //error_reporting(0);

    // create connection with data server
    if(!$conex = mysqli_connect($servidor, $usuario, $contra, $basedatos)){
        echo "<h3><font color='red'>Error: No se puede conectar al servidor de MySQL.</font></h3>" . "<hr>";
        echo "<strong>Número........:</strong> " . mysqli_connect_errno() . "<br>";
        echo "<strong>Descripción...:</strong> " . mysqli_connect_error() . "<br>";
        exit;
    }

    // change charset connection
    mysqli_set_charset($conex, 'utf8');
?>
