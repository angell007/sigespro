<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = isset($_REQUEST['id_inventario_nuevo']) ? $_REQUEST['id_inventario_nuevo'] : '';

$query = "SELECT R.Id_Remision, R.Codigo, R.Fecha, (SELECT CONCAT(F.Nombres, ' ', F.Apellidos)
 FROM Funcionario F WHERE F.Identificacion_Funcionario=R.Identificacion_Funcionario) AS Identificacion_Funcionario, 
 R.Nombre_Destino AS Destino, PR.Cantidad, 
 (CASE WHEN R.Estado_Alistamiento=0 THEN 1 WHEN R.Estado_Alistamiento=1 THEN 2 END) AS Fase 
 FROM  Producto_Remision PR 
 INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision
  WHERE R.Estado in ('Pendiente', 'Cartera', 'Rechazada')  AND PR.Id_Inventario_Nuevo IN($id) ORDER BY R.Codigo";


$oCon = new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);


echo json_encode($resultado);
