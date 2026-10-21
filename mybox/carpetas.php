<?php
    session_start();

    if($_SESSION["autenticado"] != "SI") {
        header("Location: index.php");
        exit(); //fin del scrip
    }

    $ruta  = getenv('HOME_PATH').'/'.$_SESSION["usuario"];
?>
<!doctype html>
<html lang="en">
<head>
    <?php
        include_once('sections/head.inc');
    ?>
    <title>Ingreso al Sitio</title>
</head>
<body class="container-fluid">
    <header class="row">
        <div class="row">
            <?php include_once('sections/header.inc'); ?>
        </div>
    </header>
    <main class="row">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <strong>Mi Cajón de Archivos</strong>
            </div>
            <div class="panel-body">
                <?php
                    $conta = 0;
                    $directorio = opendir($ruta);
                    echo '<a href=./agrearchi.php>'.'Agregar archivo</a>';
                    echo '<br><br>';
                    echo '<table class="table table-striped">';
                    echo '<tr>';
                    echo '<th>Nombre</th>';
                    echo '<th>Tamaño</th>';
                    echo '<th>Ultimo acceso</th>';
                    echo '<th>Archivo</th>';
                    echo '<th>Directorio</th>';
                    echo '<th>Lectura</th>';
                    echo '<th>Escritura</th>';
                    echo '<th>Ejecutable</th>';
                    echo '<th>Acción</th>';
                    echo '</tr>';
                    while($elem = readdir($directorio)){
                        if(($elem!='.') and ($elem!='..')){
                            echo '<tr>';
                            echo '<th><a href=abrarchi.php?arch='.$elem.'>'.$elem.'</a></th>';
                            echo '<th>'.filesize($ruta.'/'.$elem).' bytes</th>';
                            echo '<th>'.date("d/m/y h:i:s",fileatime($ruta.'/'.$elem)).'</th>';
                            echo '<th>'.is_file($ruta.'/'.$elem).'</th>';
                            echo '<th>'.is_dir($ruta.'/'.$elem).'</th>';
                            echo '<th>'.is_readable($ruta.'/'.$elem).'</th>';
                            echo '<th>'.is_writeable($ruta.'/'.$elem).'</th>';
                            echo '<th>'.is_executable($ruta.'/'.$elem).'</th>';
                            echo '<th><a href=./codes/borrarchi.php?archi='.$elem.'>Borrar</a></th>';
                            echo '</tr>';
                            $conta++;
                        } // fin del if
                    } // fin del while
                    echo '</table>';
                    echo '<br><br>';
                    closedir($directorio);
                    if($conta == 0)
                        echo 'La carpeta del usuario se encuetra vac&iacute;a';
                ?>
            </div>
        </div>
    </main>
    <footer class="row">
        <?php include_once('sections/foot.inc'); ?>
    </footer>
</body>
</html>
