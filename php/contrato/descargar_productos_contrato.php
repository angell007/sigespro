<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_contrato = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;


$query = "SELECT 
        PC.Id_Contrato, 
        C.Nombre_Contrato, 
        P.Nombre_Comercial, 
        CONCAT_WS(' ', ifnull(P.Principio_Activo, P.Nombre_Comercial), P.Presentacion, P.Cantidad, P.Unidad_Medida ) as Nombre_Generico, 
        P.Codigo_Cum, PC.Precio 
        From Producto_Contrato PC 
        Inner Join Producto P on P.Codigo_Cum = PC.Cum 
        Inner Join Contrato C on C.Id_Contrato = PC.Id_Contrato 
        where C.Id_Contrato = $id_contrato";
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

echo json_encode($productos);