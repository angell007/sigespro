<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT C.Nombre_Contrato as NombreContrato, CONCAT("C-",C.Id_Contrato) as Id_Contrato
	FROM Cliente CL
    INNER JOIN Contrato C
    ON CL.Id_Cliente = C.Id_Cliente
    wHERE CL.Id_Cliente ='.$id;
    
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$contrato = $oCon->getData();
unset($oCon);

echo json_encode($contrato);

?>

   