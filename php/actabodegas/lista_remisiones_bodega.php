<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_funcionario = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query='SELECT GROUP_CONCAT(FP.Id_Bodega) as Id_Bodega
FROM Funcionario_Bodega FP
WHERE FP.Identificacion_Funcionario='.$id_funcionario;
$oCon= new consulta();
$oCon->setQuery($query);
//$oCon->setTipo('Multiple');
$bodegas= $oCon->getData();
unset($oCon);

$query = 'SELECT R.*, (SELECT COUNT(*) FROM Producto_Remision PR WHERE PR.Id_Remision = R.Id_Remision) as Items, F.Imagen 
FROM Remision R
INNER JOIN  Funcionario F 
On R.Identificacion_Funcionario=F.Identificacion_Funcionario
WHERE R.Estado_Alistamiento=2 and R.Tipo_Destino="Bodega" AND R.Estado="Enviada" AND R.Id_Destino IN ('.$bodegas["Id_Bodega"].')';
       

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);
          
?>