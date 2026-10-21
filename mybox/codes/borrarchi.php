<?php
    $archivo = $_GET['archi'];
    session_start();

    $ruta = getenv('HOME_PATH').'/'.$_SESSION["usuario"].'/'.$archivo;

    // Try to delete file
    if (@unlink($ruta)){
        $Ir_A = $_SERVER["HTTP_REFERER"];
        echo "<script> location.href='".$Ir_A."' </script>";
    }
?>
