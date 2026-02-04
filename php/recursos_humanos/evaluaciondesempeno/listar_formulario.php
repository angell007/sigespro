<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$query = 'SELECT * FROM Encabezado_Formulario';
            
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$datos['Formularios']= $oCon->getData();
unset($oCon);


$query = 'SELECT * FROM Cargo';
            
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$datos['Cargos']= $oCon->getData();
unset($oCon);

$query = 'SELECT ED.Identificacion_Funcionario, ED.Id_Evaluacion_Desempeno, C.Id_Cargo,
                EF.Nombre AS NombreFormulario, 
                C.Nombre AS NombreCargo, 
                F.Nombres as NombreAuditor, 
                ED.Deficiente AS Deficiente, 
                ED.minBueno AS MinimoBueno, 
                ED.maxBueno AS MaximoBueno, 
                ED.Excelente AS Excelente
        FROM Evaluacion_Desempeno ED
        INNER JOIN Encabezado_Formulario EF ON ED.Id_Encabezado_Formulario = EF.Id_Encabezado_Formulario
        INNER JOIN Cargo C ON ED.Id_Cargo = C.Id_Cargo
        INNER JOIN Funcionario F ON ED.Identificacion_Funcionario = F.Identificacion_Funcionario
        ';
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$datos['Evaluaciones']= $oCon->getData();
unset($oCon);

echo json_encode($datos);

