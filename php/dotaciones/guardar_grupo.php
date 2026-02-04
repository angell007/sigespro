<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php'); 

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$oItem = new complex("Grupo_Inventario","Id_Grupo_Inventario");   
$oItem->Nombre = $datos;
$oItem->save();
unset($oItem);

$resultado['title']   = "Grupo Guardado";
$resultado['mensaje'] = "El Grupo se Guardò de fomar correcta";
$resultado['type']    = "success";
   
$query = 'SELECT CPD.Nombre Nombre, SUM(Cantidad) Cantidad
            FROM Inventario_Dotacion ID
            INNER JOIN Categoria_Producto_Dotacion CPD ON ID.id_Categoria_Producto_Dotacion = CPD.Id_Categoria_Producto_Dotacion
            WHERE  ID.id_Categoria_Producto_Dotacion = CPD.Id_Categoria_Producto_Dotacion
            GROUP BY ID.id_Categoria_Producto_Dotacion';
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado["Grupo"] = $oCon->getData();
unset($oCon);


echo json_encode($resultado);
