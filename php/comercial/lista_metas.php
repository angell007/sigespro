<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');



$condicion = '';

/*if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != "") {
    if($condicion==''){
        $condicion .= " WHERE CONCAT(P.Nombres,' ',P.Apellidos) LIKE '%$_REQUEST[nom]%'";
    }
    
}
if (isset($_REQUEST['iden']) && $_REQUEST['iden'] != "") {
    if($condicion==''){
        $condicion .= " WHERE P.Identificacion LIKE '%$_REQUEST[iden]%'";
    }else{
        $condicion .= " AND P.Identificacion LIKE '%$_REQUEST[iden]%'";
    }
    
}
if (isset($_REQUEST['cargo']) && $_REQUEST['cargo'] != "") {
    if($condicion==''){
        $condicion .= " WHERE P.Id_Cargo LIKE '%$_REQUEST[cargo]%'";
    }else{
        $condicion .= " AND P.Id_Cargo LIKE '%$_REQUEST[cargo]%'";
    }
    
}
if (isset($_REQUEST['dep']) && $_REQUEST['dep'] != "") {
    if($condicion==''){
        $condicion .= " WHERE D.Nombre LIKE '%$_REQUEST[dep]%'";
    }else{
        $condicion .= " AND D.Nombre LIKE '%$_REQUEST[dep]%'";
    }
    
}
if (isset($_REQUEST['depen']) && $_REQUEST['depen'] != "") {
    if($condicion==''){
        $condicion .= " WHERE P.Id_Dependencia LIKE '%$_REQUEST[depen]%'";
    }else{
        $condicion .= " AND P.Id_Dependencia LIKE '%$_REQUEST[depen]%'";
    }
    
}
if (isset($_REQUEST['punto']) && $_REQUEST['punto'] != "") {
    if($condicion==''){
        $condicion .= " WHERE PD.Nombre LIKE '%$_REQUEST[punto]%'";
    }else{
        $condicion .= " AND PD.Nombre LIKE '%$_REQUEST[punto]%'";
    }
    
}*/

$query=' SELECT COUNT(*) AS Total FROM Metas M
INNER JOIN Funcionario F ON M.Identificacion_Funcionario=F.Identificacion_Funcionario '.$condicion;


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


$query = 'SELECT M.* , F.Imagen, UPPER(CONCAT(F.Nombres, " ", F.Apellidos)) as Funcionario ,
SUM( IFNULL( Valor_Medicamentos , 0) ) AS Medicamento,
SUM( IFNULL( Valor_Materiales  , 0 ) ) AS Material
FROM Metas M
INNER JOIN Metas_Zonas MZ ON MZ.Id_Meta = M.Id_Metas
INNER JOIN Objetivos_Meta OM ON OM.Id_Metas_Zonas = MZ.Id_Metas_Zonas
INNER JOIN Funcionario F ON M.Identificacion_Funcionario=F.Identificacion_Funcionario
GROUP BY M.Id_Metas

'.$condicion.' ORDER BY Id_Meta DESC LIMIT '.$limit.','.$tamPag;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$datos['Lista'] = $oCon->getData();
unset($oCon);

$datos['numReg'] = $numReg;

echo json_encode($datos);

?>