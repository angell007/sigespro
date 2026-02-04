<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = (isset($_REQUEST['id'] ) ? $_REQUEST['id'] : '' );


$query = 'SELECT * 
          FROM Funcionario_Actividad FA
          INNER JOIN Actividad_Recursos_Humanos AR ON AR.Id_Actividad_Recursos_Humanos = FA.Id_Actividad_Recursos
          WHERE FA.Id_Funcionario_Actividad = ' .$id;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('simple');
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);
?>