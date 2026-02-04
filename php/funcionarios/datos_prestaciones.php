<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$query = 'SELECT * FROM Eps WHERE Nit IS NOT NULL ORDER BY Nombre';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Eps'] = $oCon->getData();
unset($oCon);

$query = 'SELECT * FROM Fondo_Pension  WHERE Nit IS NOT NULL  ORDER BY Nombre';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Pension'] = $oCon->getData();
unset($oCon);

$query = 'SELECT * FROM Caja_Compensacion WHERE Nit IS NOT NULL  ORDER BY Nombre';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Caja_Com'] = $oCon->getData();
unset($oCon);

$query = 'SELECT * FROM Tipo_Contrato';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Tipo_Contrato'] = $oCon->getData();
unset($oCon);

$query = 'SELECT * FROM Salario';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Salario'] = $oCon->getData();
unset($oCon);


$query = 'SELECT * FROM Riesgo';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Riesgo'] = $oCon->getData();
unset($oCon);

$query = 'SELECT Id_Municipio as value, Nombre as label  FROM Municipio  ORDER BY Nombre';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Municipios'] = $oCon->getData();
unset($oCon);

$query = 'SELECT Id_Fondo_Cesantia as value, Nombre as label FROM Fondo_Cesantia ORDER BY Nombre';

$oCon = new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Fondo_Cesantia'] = $oCon->getData();
unset($oCon);

$query = 'SELECT Id_Turno, Nombre  FROM Turno WHERE Tipo_Turno="Fijo"  ORDER BY Nombre';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Turnos'] = $oCon->getData();
unset($oCon);

$query = 'SELECT Id_Banco, Nombre  FROM Banco ';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Bancos'] = $oCon->getData();
unset($oCon);




echo json_encode($resultado);

?>
