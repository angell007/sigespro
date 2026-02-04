<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

include_once('../../class/class.consulta.php');
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT B.Nombre AS label, CONCAT("B-",B.Id_Bodega) as value
FROM Funcionario_Bodega FB
INNER JOIN Bodega B 
ON FB.Id_Bodega=B.Id_Bodega
WHERE FB.Identificacion_Funcionario='.$id;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$bodega = $oCon->getData();
unset($oCon);

$query2 = 'SELECT  CONCAT("P-",PD.Id_Punto_Dispensacion) AS value, PD.Nombre AS label
FROM Funcionario_Punto FP
INNER JOIN  Punto_Dispensacion PD
ON FP.Id_Punto_Dispensacion=PD.Id_Punto_Dispensacion
WHERE FP.Identificacion_Funcionario='.$id;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query2);
$punto = $oCon->getData();
unset($oCon);

$query3 = 'SELECT LG.Nombre, CONCAT("L-",LG.Id_Lista_Ganancia) as Id_Lista_Ganancia
FROM Lista_Ganancia LG';
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query3);
$lganancia = $oCon->getData();
unset($oCon);

$query = 'SELECT  CONCAT("C-",C.Id_Cliente) AS value, C.Id_Lista_Ganancia,  CONCAT(C.Nombre," - ",C.Id_Cliente) AS label
FROM Cliente C WHERE C.Estado = "Activo"';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$clientes = $oCon->getData();
unset($oCon);



$resultado["Bodega"]=$bodega;
$resultado["Punto"]=$punto;
$resultado["Lista"]=$lganancia;
$resultado["Clientes"]=$clientes;

echo json_encode($resultado);


?>
