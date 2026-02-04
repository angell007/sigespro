<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$fecha_inicial = isset($_REQUEST['fechainicio']) ? $_REQUEST['fechainicio'] : false;
$fecha_final = isset($_REQUEST['fechafin']) ? $_REQUEST['fechafin'] : false;

$condicion = '';
if ($fecha_inicial && $fecha_final) {
    if ($condicion != "") {
    
        $condicion .= " AND Fecha_Inicio >=  '$fecha_inicial' AND Fecha_Fin <=  '$fecha_final' ";
} else {
        $condicion .= " WHERE Fecha_Inicio >= '$fecha_inicial' AND Fecha_Fin <=  '$fecha_final'";
}
    
}

$query = 'SELECT COUNT(*) AS Total FROM Novedad'. $condicion;

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

$query = 'SELECT N.*, 
            DATE_FORMAT(N.Fecha_Inicio, "%d/%m/%Y") AS Fecha_Inicio_Nov,
            DATE_FORMAT(N.Fecha_Fin, "%d/%m/%Y") AS Fecha_Fin_Nov,
            CONCAT(F.Nombres, " ", F.Apellidos) as Funcionario,F.Imagen,
            T.Tipo_Novedad,
            DATE_FORMAT(N.Fecha_Reporte, "%d/%m/%Y %H:%i:%s") as Fecha_Reporte,
            T.Novedad,
            (SELECT Nombre FROM Dependencia WHERE Id_Dependencia=F.Id_Dependencia) AS Dependencia
FROM Novedad N 
INNER JOIN Funcionario F ON N.Identificacion_Funcionario=F.Identificacion_Funcionario
INNER JOIN Tipo_Novedad T ON N.Id_Tipo_Novedad=T.Id_Tipo_Novedad'. $condicion.' ORDER BY N.Id_Novedad DESC  LIMIT '.$limit.','.$tamPag.'';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$novedad['novedades'] = $oCon->getData();
unset($oCon);

$novedad['numReg'] = $numReg;

$query = 'SELECT N.*, TIMESTAMPDIFF(DAY, N.Fecha_Inicio, N.Fecha_Fin) AS Dias,
            DATE_FORMAT(N.Fecha_Inicio, "%d/%m/%Y") AS Fecha_Inicio_Nov,
            DATE_FORMAT(N.Fecha_Fin, "%d/%m/%Y") AS Fecha_Fin_Nov,
            CONCAT(F.Nombres, " ", F.Apellidos) as Funcionario,F.Imagen,
            T.Tipo_Novedad,
            DATE_FORMAT(N.Fecha_Reporte, "%d/%m/%Y %H:%i:%s") as Fecha_Reporte,
            T.Novedad,
            (SELECT Nombre FROM Dependencia WHERE Id_Dependencia=F.Id_Dependencia) AS Dependencia
            FROM Novedad N 
            INNER JOIN Funcionario F ON N.Identificacion_Funcionario=F.Identificacion_Funcionario
            INNER JOIN Tipo_Novedad T ON N.Id_Tipo_Novedad=T.Id_Tipo_Novedad AND T.Tipo_Novedad = "Vacaciones" 
            ORDER BY N.Id_Novedad DESC ';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$novedad['vacaciones'] = $oCon->getData();
unset($oCon);

$novedad['numReg'] = $numReg;

echo json_encode($novedad);
?>