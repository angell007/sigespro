<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = getConditions();

$query = 'SELECT COUNT(*) AS Total
FROM Correspondencia C
INNER JOIN Funcionario F
on C.Id_Funcionario_Envia=F.Identificacion_Funcionario
INNER JOIN Punto_Dispensacion PD
ON C.Punto_Envio=PD.Id_Punto_Dispensacion
WHERE C.Estado="Enviada" ' . $condicion;

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

$query = 'SELECT C.*, F.Imagen,PD.Nombre
FROM Correspondencia C
INNER JOIN Funcionario F
on C.Id_Funcionario_Envia=F.Identificacion_Funcionario
INNER JOIN Punto_Dispensacion PD
ON C.Punto_Envio=PD.Id_Punto_Dispensacion
WHERE C.Estado="Enviada" '.$condicion.' ORDER BY C.Fecha_Envio ASC LIMIT '.$limit.','.$tamPag;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$datos['Correspondencia'] = $oCon->getData();
unset($oCon);

$datos['numReg']=$numReg;

echo json_encode($datos);

function getConditions() {
    $condicion = '';
    if (isset($_REQUEST['Codigo']) && $_REQUEST['Codigo'] != '') {
        $condicion .= " AND C.Id_Correspondencia LIKE '%$_REQUEST[Codigo]%'";
    }
    if (isset($_REQUEST['Punto']) && $_REQUEST['Punto'] != '') {
        $condicion .= " AND PD.Nombre LIKE '%$_REQUEST[Punto]%'";
    }

    return $condicion;
}


?>