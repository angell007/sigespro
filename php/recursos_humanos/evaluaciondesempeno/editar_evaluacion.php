<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$query = 'SELECT ED.Id_Evaluacion_Desempeno, EF.Nombre AS NombreFormulario, 
                 C.Nombre AS NombreCargo,
                 C.Id_Cargo,
                 ED.Id_Encabezado_Formulario,
                 EF.Id_Encabezado_Formulario, 
                 F.Nombres as NombreAuditor, 
                 ED.Deficiente AS Deficiente, 
                 ED.minBueno AS MinimoBueno, 
                 ED.maxBueno AS MaximoBueno, 
                 ED.Excelente AS Excelente
         FROM evaluacion_desempeno ED
         INNER JOIN encabezado_formulario EF ON ED.Id_Encabezado_Formulario = EF.Id_Encabezado_Formulario
         INNER JOIN cargo C ON ED.Id_Cargo = C.Id_Cargo
         INNER JOIN funcionario F ON ED.Identificacion_Funcionario = F.Identificacion_Funcionario
         WHERE ED.Id_Evaluacion_Desempeno = "'.$id.'"';
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('simple');
$datos = $oCon->getData();
unset($oCon);
// print_r($datos);
echo json_encode($datos);

