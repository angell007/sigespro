<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$link = mysql_connect('localhost', 'corvusla_proh', 'Proh2018') or die('No se pudo conectar: ' . mysql_error());
mysql_select_db('corvusla_proh') or die('No se pudo seleccionar la base de datos');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );


$query = 'UPDATE '.$mod.' 
          SET Estado = "Anulada" 
           WHERE Id_'.$mod.' = '.$id  ;

$result = mysql_query($query) or die('Consulta fallida: ' . mysql_error());

mysql_close($link);

$oLista = new lista($mod);
$lista= $oLista->getlist();
unset($oLista);
echo json_encode($lista);
?>