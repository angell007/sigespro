<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
if( isset( $_REQUEST['modulo'] ))
$condicion = " AND PF.Titulo_Modulo LIKE '$_REQUEST[modulo]' ";

// --IFNULL(PF.Tablero, PE.Tablero) as Tablero
$query = 'SELECT PE.*, PF.* 
FROM Perfil PE
INNER JOIN Perfil_Funcionario PF
ON PE.Id_Perfil=PF.Id_Perfil
WHERE PF.Identificacion_Funcionario='.$id .$condicion;
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$permisos = $oCon->getData();
unset($oCon);

echo json_encode($permisos);

?>