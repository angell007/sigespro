<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';
$condicion_bod = '';

if (isset($_REQUEST['cod']) && $_REQUEST['cod']) {
     $condicion .= "WHERE AI.Codigo LIKE '%$_REQUEST[cod]%'";
 }
 if ($condicion != "") {
     if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
         $condicion .= " AND AI.Tipo='$_REQUEST[tipo]'";
     }
 } else {
     if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
         $condicion .= "WHERE AI.Tipo='$_REQUEST[tipo]'";
     }
 }
 if ($condicion != "") {
     if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
         $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
         $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
         $condicion .= " AND DATE(AI.Fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
     }
 } else {
     if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
         $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
         $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
         $condicion .= "WHERE DATE(AI.Fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
     } 
 }
 if ($condicion != "") {
     if (isset($_REQUEST['fun']) && $_REQUEST['fun'] != "") {
         $condicion .= " AND F.Nombres LIKE '%$_REQUEST[fun]%'";
     }
 } else {
     if (isset($_REQUEST['fun']) && $_REQUEST['fun'] != "") {
         $condicion .= "WHERE F.Nombres LIKE '%$_REQUEST[fun]%'";
     }
 }
 
 if (isset($_REQUEST['bod']) && $_REQUEST['bod'] != "") {
    $condicion_bod .= "HAVING Bodega LIKE '%$_REQUEST[bod]%'";
}



$query = "SELECT AI.*,  CONCAT(F.Nombres,' ', F.Apellidos) AS Funcionario,
 IF(AI.Origen_Destino = 'Bodega',(SELECT B.Nombre FROM Bodega_Nuevo B WHERE B.Id_Bodega_Nuevo=AI.Id_Origen_Destino),
 (SELECT B.Nombre FROM Punto_Dispensacion B WHERE B.Id_Punto_Dispensacion=AI.Id_Origen_Destino)) as Bodega FROM Ajuste_Individual AI
INNER JOIN Funcionario F 
ON F.Identificacion_Funcionario=AI.Identificacion_Funcionario $condicion $condicion_bod";
    
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

$tamPag = 10; 
$numReg = count($total); 
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

$query = "SELECT AI.*,  CONCAT(F.Nombres,' ', F.Apellidos) AS Funcionario,
             IF(AI.Origen_Destino = 'Bodega',(SELECT B.Nombre FROM Bodega_Nuevo B WHERE B.Id_Bodega_Nuevo=AI.Id_Origen_Destino),
             (SELECT B.Nombre FROM Punto_Dispensacion B WHERE B.Id_Punto_Dispensacion=AI.Id_Origen_Destino)) as Bodega,
    IFNULL((SELECT ROUND(SUM(Cantidad*Costo)) FROM Producto_Ajuste_Individual WHERE Id_Ajuste_Individual = AI.Id_Ajuste_Individual), 0) AS Valor_Ajuste
 FROM Ajuste_Individual AI
INNER JOIN Funcionario F 
ON F.Identificacion_Funcionario=AI.Identificacion_Funcionario ".$condicion.' '.$condicion_bod.' ORDER BY AI.Id_Ajuste_Individual DESC LIMIT '.$limit.','.$tamPag;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

$datos['Lista']=$resultado;
$datos['numReg'] = $numReg;

echo json_encode($datos);


?>