<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

$condicion = '';


if (isset($_REQUEST['funcionario']) && $_REQUEST['funcionario'] != "") {
    $condicion .= " WHERE CONCAT(F.Nombres,' ', F.Apellidos) LIKE '%$_REQUEST[funcionario]%'";
    }
    
if (isset($_REQUEST['grupo']) && $_REQUEST['grupo'] != "") {
    if ($condicion != "") {
    $condicion .= " WHERE N.Id_Grupo = $_REQUEST[grupo]";
    }else{
        $condicion .= " AND N.Id_Grupo = $_REQUEST[grupo]";
        }
}
$query = 'SELECT COUNT(*)  AS Total
          FROM Nomina N
          INNER JOIN Funcionario F ON N.Identificacion_Funcionario=F.Identificacion_Funcionario          
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

$query = 'SELECT N.*, CONCAT_WS(" ",F.Nombres,F.Apellidos) as Funcionario,F.Imagen,  
            IFNULL((SELECT G.Nombre FROM Grupo G WHERE G.Id_Grupo=N.Id_Grupo),"Todos") as Grupo FROM Nomina N 
            INNER JOIN Funcionario F ON N.Identificacion_Funcionario=F.Identificacion_Funcionario '.$condicion.' ORDER BY N.Fecha_Fin DESC LIMIT '.$limit.','.$tamPag;
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$nomina1 = $oCon->getData();
unset($oCon);

$nomina['numReg']=$numReg;
$i=-1;
foreach ($nomina1 as $value) {$i++;
    $fecha=explode(";",$value['Nomina']);
    $mes=explode("-",$fecha[0]);
    $mes1=MesString($mes[1]);
    // $nomina1[$i]['Nomina']=$fecha[1]." Quincena de ".$mes1." del ".$mes[0];
    $nomina1[$i]['Nomina']=$fecha[1]." ".$mes1." del ".$mes[0];  
}
$nomina['Nomina']=$nomina1;

echo json_encode($nomina);


function MesString($mes_index){
    global $meses;

    return  $meses[($mes_index-1)];
}

?>

