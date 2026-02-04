<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$id_funcionario = ( isset( $_REQUEST['id_funcionario'] ) ? $_REQUEST['id_funcionario'] : '' );
$condicion = '';

if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
    $condicion .= " AND ARC.Codigo LIKE '%$_REQUEST[cod]%'";
}

if (isset($_REQUEST['codr']) && $_REQUEST['codr'] != "") {
    $condicion .= " AND R.Codigo LIKE '%$_REQUEST[codr]%'";
}

if ($condicion != "") {
    if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
        $condicion .= " AND DATE_FORMAT(ARC.Fecha, '%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
} else {
    if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
        $condicion .= " AND DATE_FORMAT(ARC.Fecha, '%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    } 
}

$query_bodegas_funcionario = 'SELECT GROUP_CONCAT(Id_Bodega_Nuevo) AS Id_Bodega_Nuevo
                                FROM Funcionario_Bodega_Nuevo FB
                                WHERE FB.Identificacion_Funcionario ='.$id_funcionario;

$oCon= new consulta();
$oCon->setQuery($query_bodegas_funcionario);
$bodegas = $oCon->getData();
unset($oCon); 


$query = 'SELECT COUNT(*) AS Total
        FROM Acta_Recepcion_Remision ARC
        INNER JOIN Remision R
        ON ARC.Id_Remision=R.Id_Remision
        WHERE ARC.Tipo="Bodega" AND ARC.Estado = "Acomodada" AND ARC.Id_Bodega_Nuevo IN ('.$bodegas['Id_Bodega_Nuevo'].')'.$condicion;
 
$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 20; 
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
        
$query = 'SELECT ARC.Id_Acta_Recepcion_Remision, ARC.Codigo,  ARC.Fecha, F.Imagen, R.Codigo as Codigo_Remision
FROM Acta_Recepcion_Remision ARC 
INNER JOIN Funcionario F
ON F.Identificacion_Funcionario = ARC.Identificacion_Funcionario
INNER JOIN Remision R
ON ARC.Id_Remision=R.Id_Remision
WHERE ARC.Tipo="Bodega" AND ARC.Estado = "Acomodada" AND ARC.Id_Bodega_Nuevo IN ('.$bodegas['Id_Bodega_Nuevo'].')' .$condicion.' ORDER BY Fecha DESC, Codigo DESC LIMIT '.$limit.','.$tamPag;


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$actarecepcion['actarecepciones'] = $oCon->getData();
unset($oCon);
          
$actarecepcion['numReg'] = $numReg;

echo json_encode($actarecepcion);

?>