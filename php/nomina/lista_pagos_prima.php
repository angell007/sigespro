<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');



$condicion = '';


if (isset($_REQUEST['funcionario']) && $_REQUEST['funcionario'] != "") {
    $condicion .= " WHERE CONCAT(F.Nombres,' ', F.Apellidos) LIKE '%$_REQUEST[funcionario]%'";
    }
    

$query = 'SELECT COUNT(*)  AS Total
          FROM Prima P
          INNER JOIN Funcionario F ON P.Identificacion_Funcionario=F.Identificacion_Funcionario          
          ' . $condicion;

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

$query = 'SELECT P.*, CONCAT_WS(" ",F.Nombres,F.Apellidos) as Funcionario,F.Imagen  
 FROM Prima P INNER JOIN Funcionario F ON P.Identificacion_Funcionario=F.Identificacion_Funcionario '.$condicion.' LIMIT '.$limit.','.$tamPag;
//echo $query;
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$prim = $oCon->getData();
unset($oCon);

$prima['numReg']=$numReg;
$i=-1;

$prima['Nomina']=$prim;

echo json_encode($prima);



?>

