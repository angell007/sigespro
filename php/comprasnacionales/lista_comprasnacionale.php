<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$link = mysql_connect('localhost', 'corvusla_proh', 'Proh2018') or die('No se pudo conectar: ' . mysql_error());
mysql_select_db('corvusla_proh') or die('No se pudo seleccionar la base de datos');

$query = 'SELECT 
            OCN.Fecha as fecha, OCN.Id_Bodega as Bodega, OCN.Fecha_Llegada as FechaLlegada, OCN.Observacion as Observasion, OCN.Id_Funcionario as Funcionario            
          FROM Orden_Compra_Nacional OCN
          WHERE OCN.Id_Funcionario = OCN.Id_Funcionario';

$oCon= new consulta();
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);
          
echo json_encode($resultado);

?>