<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT 
            OCI.Fecha as fecha, OCI.Id_Bodega as Bodega, OCI.Fecha_Llegada as FechaLlegada, OCI.Observacion as Observasion, OCI.Id_Funcionario as Funcionario            
          FROM Orden_Compra_Internacional OCI
          WHERE OCI.Id_Funcionario ='.$id ;

$oCon= new consulta();
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);

?>