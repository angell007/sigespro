<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.consulta.php');

$id_origen = isset($_REQUEST['Id_Bodega_Nuevo']) ? $_REQUEST['Id_Bodega_Nuevo'] : '';
$id_producto = isset($_REQUEST['Id_Producto']) ? $_REQUEST['Id_Producto'] : '';


$query = 'SELECT B.* ,
            (SELECT IFNULL(CONCAT(F.Primer_Nombre, " ", F.Primer_Apellido),CONCAT(F.Nombres, " ", F.Apellidos)) FROM Funcionario F 
            WHERE B.Id_Funcionario = F.Identificacion_Funcionario ) AS Identificacion_Funcionario
        FROM  Borrador B
        WHERE B.Tipo = "Remision"  AND B.Estado = "Activo" 
        ORDER BY Id_Borrador DESC ';
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$borradores = $oCon->getData();
unset($oCon);

$productos_apartados = [];
foreach ($borradores as  $borrador) {

    $model = json_decode($borrador['Texto'], true);
    $Productos = (array) $model['Productos'];
    /*     var_dump($model); */
    foreach ($Productos as $Producto) {
        /*  var_dump($Producto); */

        foreach ($Producto['Lotes_Seleccionados'] as  $seleccionado) {
            if ($model['Modelo']) {
                if ($seleccionado['Id_Producto'] == $id_producto && $model['Modelo']['Id_Origen']==$id_origen) {

              
                    # code...
                    
                  
                   
                    $producto_apartado['Codigo'] = $borrador['Codigo'];
                    $producto_apartado['Fecha'] = $borrador['Fecha'];
                    $producto_apartado['Identificacion_Funcionario'] = $borrador['Identificacion_Funcionario'];
                    $producto_apartado['Tipo']  = $model['Modelo']['Tipo'];
                    $producto_apartado['Nombre_Destino'] = $model['Modelo']['Nombre_Destino'];
                    $producto_apartado['Nombre_Origen'] = $model['Modelo']['Nombre_Origen'];
                    $producto_apartado['Cantidad_Seleccionada'] = $seleccionado['Cantidad_Seleccionada'];
                    $producto_apartado['Lote'] = $seleccionado['Lote'];
    
                    array_push($productos_apartados,$producto_apartado);
    /*                 var_dump($borrador['Identificacion_Funcionario']); */
                   
                }
                # code...
              
            }
        }
    }
}

echo json_encode($productos_apartados);

