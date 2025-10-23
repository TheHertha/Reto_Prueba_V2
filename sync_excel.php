<?php
require_once __DIR__ . '/db.php';

$ruta = __DIR__ . '/inventario.csv'; // cambia por la ruta real
if (!file_exists($ruta)) {
    echo "Archivo no encontrado: $ruta\n";
    exit;
}
$_FILES = ['archivo'=>['tmp_name'=>$ruta,'name'=>'inventario.csv']];
include __DIR__ . '/importar_inventario.php';
