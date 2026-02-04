<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT * FROM Temporal_Paciente' ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$pacientes = $oCon->getData();
unset($oCon);

foreach($pacientes as $paciente){
	$query='SELECT M.Id_Departamento
	FROM Municipio M
	WHERE M.Codigo='.$paciente['Codigo_Municipio'];
	$oCon= new consulta();
	$oCon->setQuery($query);
	$paciente['Id_Departamento'] = $oCon->getData();
	unset($oCon);
	$oItem = new complex('Paciente',"Id_Paciente");
	$oItem->Id_Paciente=$paciente['Documento_Paciente'];
	$oItem->Tipo_Documento=$paciente['Tipo_Documento'];
	$oItem->Id_Departamento=$paciente['Id_Departamento'];
	$oItem->Primer_Nombre=$paciente['Primer_Nombre'];
	$oItem->Segundo_Nombre=$paciente['Segundo_Nombre'];
	$oItem->Primer_Apellido=$paciente['Primer_Apellido'];
	$oItem->Segundo_Apellido=$paciente['Segundo_Apellido'];
	$oItem->Fecha_Nacimiento=$paciente['Fecha_Nacimiento'];
	$oItem->Genero=$paciente['Genero'];
	$oItem->Codigo_Municipio=$paciente['Codigo_Municipio'];
	$oItem->Direccion=$paciente['Direccion'];
	$oItem->Telefono=$paciente['Celular'];
	$oItem->EPS=$paciente['EPS'];
	$oItem->Nit=$paciente['Nit'];
	$oItem->Id_Nivel=$paciente['Id_Nivel'];
	$oItem->Id_Regimen=$paciente['Id_Regimen'];
	$oItem->save();
	unset($oItem);
	
		
}

?>