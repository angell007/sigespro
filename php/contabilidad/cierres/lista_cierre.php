<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');


$query = "SELECT IFNULL(MAX(Anio), 2018)  as Ultimo_Anio FROM Cierre_Contable WHERE Tipo_Cierre = 'Anio' AND Estado = 'Cerrado'  ";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('simple');
$response = $oCon->getData();

$query = "SELECT C.*, (SELECT Imagen FROM Funcionario WHERE Identificacion_Funcionario = C.Identificacion_Funcionario) AS Imagen FROM Cierre_Contable C WHERE C.Tipo_Cierre = 'Mes'";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$response['Mes'] = $oCon->getData();
unset($oCon);

$query = "SELECT C.*, (SELECT Imagen FROM Funcionario WHERE Identificacion_Funcionario = C.Identificacion_Funcionario) AS Imagen FROM Cierre_Contable C WHERE C.Tipo_Cierre = 'Anio' ORDER BY Estado ASC";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$response['Anio'] = $oCon->getData();
unset($oCon);

echo json_encode($response);

?>