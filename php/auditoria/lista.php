<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$query = 'SELECT Id_Departamento, Nombre, Codigo FROM Departamento ORDER BY Nombre' ;


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$departamento = $oCon->getData();
unset($oCon);

$query = 'SELECT  Nombre, Nit FROM Eps WHERE Nit IS NOT NULL  ORDER BY Nombre' ;


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$Eps = $oCon->getData();
unset($oCon);

$query = 'SELECT  Id_Nivel, Codigo FROM Nivel' ;


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$nivel = $oCon->getData();
unset($oCon);

$query = 'SELECT  Id_Regimen, Nombre FROM Regimen' ;


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$regimen = $oCon->getData();
unset($oCon);

$query = 'SELECT * FROM Tipo_Documento ORDER BY Codigo' ;


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$documento = $oCon->getData();
unset($oCon);

$resultado['Departamento']=$departamento;
$resultado['Eps']=$Eps;
$resultado['Regimen']=$regimen;
$resultado['Documento']=$documento;
$resultado['Nivel']=$nivel;


echo json_encode($resultado);
          
?>