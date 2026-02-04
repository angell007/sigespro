<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );



$condicion="";
if (isset($_REQUEST['funcionario']) && $_REQUEST['funcionario'] != "") {
    $condicion .= " AND CONCAT(F.Nombres,' ', F.Apellidos) LIKE '%$_REQUEST[funcionario]%'";
}


$query = 'SELECT P.* FROM Prima P
WHERE  P.Id_Prima = '.$id ;

$oCon= new consulta();
$oCon->setQuery($query);
$nomina = $oCon->getData();
unset($oCon);



$query2 = 'SELECT COUNT(*) as Total
FROM Prima_Funcionario NF INNER JOIN Funcionario F ON NF.Identificacion_Funcionario=F.Identificacion_Funcionario WHERE NF.Id_Prima='.$id.$condicion;     

$oCon= new consulta();
$oCon->setQuery($query2);
$total= $oCon->getData();
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
 

$query2 = 'SELECT NF.*, CONCAT(F.Nombres," ", F.Apellidos) as Funcionario, F.Imagen
FROM Prima_Funcionario NF INNER JOIN Funcionario F ON NF.Identificacion_Funcionario=F.Identificacion_Funcionario WHERE NF.Id_Prima='.$id.$condicion.' LIMIT '.$limit.','.$tamPag;   



$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query2);
$funcionarios= $oCon->getData();
unset($oCon);


$resultado['Prima']=$nomina;
$resultado['Funcionarios']=$funcionarios;
$resultado['numReg'] = $numReg;;

echo json_encode($resultado);


function MesString($mes_index){
    global $meses;

    return  $meses[($mes_index-1)];
}

?>