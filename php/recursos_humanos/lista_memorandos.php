<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != "") {
    if($condicion==''){
        $condicion .= " AND CONCAT(F.Nombres,' ',F.Apellidos) LIKE '%$_REQUEST[nom]%' OR CM.Nombre_Categoria LIKE '%$_REQUEST[nom]%'  ";
    }
    
}


$query=' SELECT COUNT(*) AS Total FROM Memorando M
INNER JOIN Categorias_Memorando CM ON M.Motivo = CM.Id_Categorias_Memorando
INNER JOIN Funcionario F ON M.Identificacion_Funcionario=F.Identificacion_Funcionario
 '.$condicion;


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

//INNER JOIN Categorias_Memorando CM ON CM.Id_Categorias_Memorando = M.Motivo
$query = 'SELECT M.*,
                 F.Imagen, 
                 CONCAT(F.Nombres," ",F.Apellidos) AS Funcionario, 
                 CM.Nombre_Categoria AS Nombre_Categoria
          FROM Memorando M  
          INNER JOIN Categorias_Memorando CM ON M.Motivo = CM.Id_Categorias_Memorando
          INNER JOIN Funcionario F On M.Identificacion_Funcionario = F.Identificacion_Funcionario 
          '.$condicion.'
          ORDER BY M.Id_Memorando DESC LIMIT '.$limit.','.$tamPag;


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$datos['Memorandos'] = $oCon->getData();
unset($oCon);

$datos['numReg'] = $numReg;

echo json_encode($datos);

?>