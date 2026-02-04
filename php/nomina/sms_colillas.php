<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');
require_once('../../class/class.mensajes.php');

$nom=( isset( $_REQUEST['nomina'] ) ? $_REQUEST['nomina'] : '' );

/* FUNCIONES BASICAS */
function fecha($str)
{
    $parts = explode(" ",$str);
    $date = explode("-",$parts[0]);
    return $date[2] . "/". $date[1] ."/". $date[0];
}

$meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
/* FIN FUNCIONES BASICAS*/

$oItem = new complex('Nomina',"Id_Nomina",$nom);
$nomina_general = $oItem->getData();
unset($oItem);

function MesString($mes_index){
    global $meses;
    return  $meses[($mes_index-1)];
}

$fecha1=explode(";",$nomina_general["Nomina"]);
$mes=explode("-",$fecha1[0]);
$mes1=MesString($mes[1]);
$nomina=" Quincena ".$fecha1[1]." de ".$mes1." del ".$mes[0];

$tem=explode(";",$nomina_general["Nomina"]);
if($tem[1]=="1"){
    $fecha=$tem[0]."-14";
}else{
    $fecha=$tem[0]."-17";
}


$query = 'SELECT NF.*,  CONCAT(F.Nombres," ",F.Apellidos) as Funcionario, F.Celular
FROM Nomina_Funcionario NF  
INNER JOIN Funcionario F ON NF.Identificacion_Funcionario=F.Identificacion_Funcionario 
WHERE NF.Id_Nomina='.$nom;
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$funcionarios = $oCon->getData();
unset($oCon);

$i=0;
foreach($funcionarios as $fun){
    if($fun["Celular"]!=""){
        $i++;
        $sms = "Enhorabuena! ".$fun["Funcionario"].", tu nomina de la ".$nomina." ha sido pagada! Gracias por tu esfuerzo! Dios te Bendiga!";
        $oCon= new Mensaje();
        $res=$oCon->Enviar($fun["Celular"],$sms);
        unset($oCon);
    }
}

$resultado['Empleados'] = $i;
          
echo json_encode($resultado);

?>