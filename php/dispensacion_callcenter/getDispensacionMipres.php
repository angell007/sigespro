<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
include_once('../../class/class.consulta.php');
include_once('../../class/class.querybasedatos.php');

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;


$query = 'SELECT 
    dm.*, 
    p.*, 
    pd.* 
    FROM Dispensacion_Mipres dm
    LEFT JOIN Paciente p ON dm.Id_Paciente = p.Id_Paciente
    LEFT JOIN Producto_Dispensacion_Mipres pd ON dm.Id_Dispensacion_Mipres = pd.Id_Dispensacion_Mipres 
    WHERE dm.Id_Dispensacion_Mipres = ' . $id;

$oCon = new consulta();
$oCon->setQuery($query);
$result = $oCon->getData();
unset($oCon);

$queryProductos = '
    SELECT * 
    FROM Producto_Dispensacion_Mipres 
    WHERE Id_Dispensacion_Mipres = ' . $id;

$queryObj = new QueryBaseDatos();
$queryObj->SetQuery($queryProductos);
$productos = $queryObj->ExecuteQuery('Multiple');

if ($result) {
    $producto["Mensaje"] = 'OK';
    $resultado["Tipo"] = "success";
    $resultado["data"] = $result;
    $resultado["productos"] = $productos;
} else {
    $resultado["Titulo"] = "Error al intentar obtener los datos";
    $resultado["Tipo"] = "error";
    $resultado["Texto"] = "Ha ocurrido un error inesperado.";
}

echo json_encode($resultado);
