<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.nomina.php');
include_once('../../class/class.parafiscales.php');
include_once('../../class/class.provisiones.php');


if(date("Y-m-d")<=date("Y-m-15")){
   $fini  = (isset($_REQUEST['fini'] ) ? $_REQUEST['fini'] : date("Y-m")."-01" );
   $ffin  = (isset($_REQUEST['ffin'] ) ? $_REQUEST['ffin'] : date("Y-m-15") );
   $quincena=1;
}else{
   $fini  = (isset($_REQUEST['fini'] ) ? $_REQUEST['fini'] : date("Y-m")."-15" );
   $ffin  = (isset($_REQUEST['ffin'] ) ? $_REQUEST['ffin'] : date("Y-m")."-". date("d",(mktime(0,0,0,date("m")+1,1,date("Y"))-1))); 
   $quincena=2;
}

$query='SELECT F.Identificacion_Funcionario,(SELECT   FROM Provision WHERE Prefijo="Prima" ) as Prima, CF.Fecha_Inicio_Contrato as Fecha_Inicio, Fecha_Fin_Contrato as Fecha_Fin
FROM Contrato_Funcionario CF 
INNER JOIN Funcionario F ON CF.Identificacion_Funcionario=F.Identificacion_Funcionario
WHERE F.Autorizado="Si" AND CF.Estado="Activo"';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$funcionarios = $oCon->getData();
unset($oCon);




?>