<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$fecha_inicial = isset($_REQUEST['fecha_inicio']) ? $_REQUEST['fecha_inicio'] : false;
$fecha_final = isset($_REQUEST['fecha_fin']) ? $_REQUEST['fecha_fin'] : false;

$condicion = '';

if ($fecha_inicial && $fecha_final) {
    $condicion .= "WHERE llt.Fecha BETWEEN '$fecha_inicial' AND '$fecha_final'";
} else {
    $condicion .= "WHERE DATE_FORMAT(llt.Fecha, '%Y-%m-%d')=CURDATE()";
}

if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != "") {
   
        $condicion .= " AND CONCAT(f.Nombres,' ',f.Apellidos) LIKE '%$_REQUEST[nom]%'";
}
    $condicion .= " AND DATE_FORMAT(llt.Fecha, '%Y-%m-%d')>= '2022-11-01'";

$query = 'SELECT f.Identificacion_Funcionario,
        llt.Id_Llegada_Tarde, f.Imagen,
        CONCAT(f.Nombres, " ", f.Apellidos) AS Nombres, 
        COUNT(llt.Id_Llegada_Tarde) AS Llegadas_Tardes,
        SEC_TO_TIME(ROUND(AVG(Tiempo),0)) AS Tiempo_Promedio
        FROM Funcionario f 
        INNER JOIN Llegada_Tarde llt 
        ON f.Identificacion_Funcionario=llt.Identificacion_Funcionario '.$condicion.' GROUP BY f.Identificacion_Funcionario ORDER BY Tiempo_Promedio DESC  ' ;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);



####### PAGINACIÓN ######## 
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

$query = 'SELECT f.Identificacion_Funcionario,
        llt.Id_Llegada_Tarde, f.Imagen,
        CONCAT(f.Nombres, " ", f.Apellidos) AS Nombres, 
        COUNT(llt.Id_Llegada_Tarde) AS Llegadas_Tardes,
        SEC_TO_TIME(ROUND(AVG(Tiempo),0)) AS Tiempo_Promedio
        FROM Funcionario f 
        INNER JOIN Llegada_Tarde llt 
        ON f.Identificacion_Funcionario=llt.Identificacion_Funcionario '.$condicion.' GROUP BY f.Identificacion_Funcionario ORDER BY Tiempo_Promedio DESC LIMIT '.$limit.','.$tamPag; 

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Llegadas'] = $oCon->getData();
unset($oCon);


$resultado['numReg'] = $numReg;
echo json_encode($resultado);
?>