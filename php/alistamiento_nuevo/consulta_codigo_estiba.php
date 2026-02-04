<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$codigo = ( isset( $_REQUEST['codigo'] ) ? $_REQUEST['codigo'] : false );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : false );
$codigo1=substr($codigo,0,12);

$query = 'SELECT  Id_Estiba
        FROM Estiba 
        WHERE Id_Estiba='.$id.' AND Codigo_Barras LIKE "%'.$codigo1.'%"';

$oCon= new consulta();
$oCon->setQuery($query);
//$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);

?>