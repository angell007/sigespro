<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
    $condicion .= " AND NC.Codigo LIKE '%$_REQUEST[cod]%'";
}
if (isset($_REQUEST['pun']) && $_REQUEST['pun'] != "") {
    $condicion .= " AND R.Nombre_Destino LIKE '%$_REQUEST[pun]%'";
}
if (isset($_REQUEST['estado']) && $_REQUEST['estado'] != "") {
    $condicion .= " AND NC.Estado LIKE '%$_REQUEST[estado]%'";
}

$query=' SELECT COUNT(*) AS Total FROM No_Conforme NC
INNER JOIN Funcionario F
On NC.Persona_Reporta=F.Identificacion_Funcionario
INNER JOIN Remision R 
ON NC.Id_Remision=R.Id_Remision
WHERE NC.Tipo = "Remision"'.$condicion;

$oCon= new consulta();

$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 15; 
$numReg = $total["Total"]; 
$paginas = ceil($numReg/$tamPag); 
$limit = ""; 
$paginaAct = "";

if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') { 
    $paginaAct = 1; 
    $limit = 0; 
} else { 
    $paginaAct = $_REQUEST['pag']; 
    $limit = ($paginaAct-1) * $tamPag; 
}


$query = 'SELECT NC.*, CONCAT(F.Nombres," ", F.Apellidos) as Nombre_Funcionario, 
R.Nombre_Destino as Punto,R.Nombre_Destino as Punto, R.Codigo as Remision
FROM No_Conforme NC
INNER JOIN Funcionario F
On NC.Persona_Reporta=F.Identificacion_Funcionario
INNER JOIN Remision R 
ON NC.Id_Remision=R.Id_Remision
WHERE NC.Tipo = "Remision"'.$condicion.' ORDER BY NC.Id_No_Conforme DESC LIMIT '.$limit.','.$tamPag;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$datos['NoConformes'] = $oCon->getData();
unset($oCon);

$datos['numReg'] = $numReg;

echo json_encode($datos);

?>