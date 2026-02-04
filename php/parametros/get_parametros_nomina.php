<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$query = 'SELECT Id_Configuracion, PagoNomina FROM Configuracion limit 1';
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$config = $oCon->getData();
unset($oCon);

$query = 'SELECT *, "Hora_Extra_Recargo" as Tabla, Id_Hora_Extra_Recargo as Id FROM Hora_Extra_Recargo  ';
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$horas = $oCon->getData();
unset($oCon);



$query = 'SELECT *, "Riesgo" as Tabla, Id_Riesgo as Id FROM Riesgo  ';
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$riesgo = $oCon->getData();
unset($oCon);

$query = 'SELECT *, "Parafiscal" as Tabla, Id_Parafiscal as Id FROM Parafiscal  ';
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$parafiscal = $oCon->getData();
unset($oCon);

$query = 'SELECT *, "Aporte_Seguridad_Empresa" as Tabla, Id_Aporte_Seguridad_Empresa as Id  FROM Aporte_Seguridad_Empresa  ';
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$aporte_seguridad_empresa = $oCon->getData();
unset($oCon);

$query = 'SELECT *, "Aporte_Seguridad_Funcionario" as Tabla,Id_Aporte_Seguridad_Funcionario as Id  FROM Aporte_Seguridad_Funcionario  ';
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$aporte_seguridad_funcionario = $oCon->getData();
unset($oCon);

$query = 'SELECT *, "Incapacidad" as Tabla, Id_Incapacidad as Id FROM Incapacidad  ';
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$incapacidad = $oCon->getData();
unset($oCon);

$query = 'SELECT *, "Provision" as Tabla, Id_Provision as Id FROM Provision  ';
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$provisiones = $oCon->getData();
unset($oCon);

$resultado['Aporte_Seguridad_Funcionario']=$aporte_seguridad_funcionario;
$resultado['Aporte_Seguridad_Empresa']=$aporte_seguridad_empresa;
$resultado['Parafiscal']=$parafiscal;
$resultado['Riesgo']=$riesgo;
$resultado['Horas']=$horas;
$resultado['Config']=$config;
$resultado['Incapacidad']=$incapacidad;
$resultado['Provision']=$provisiones;




echo json_encode($resultado);
?>