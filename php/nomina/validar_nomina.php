<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id_grupo'] ) ? $_REQUEST['id_grupo'] : '' );
$periodo = ( isset( $_REQUEST['periodo'] ) ? $_REQUEST['periodo'] : '' );
$quin = ( isset( $_REQUEST['quincena'] ) ? $_REQUEST['quincena'] : '' );
$config = ( isset( $_REQUEST['ConfigNomina'] ) ? $_REQUEST['ConfigNomina'] : '' );
$periodo=explode("-",$periodo);

$quinc=explode(";",$quin);

if ($config == 'Quincenal') {
    $fecha=$periodo[0]."-".$periodo[1].";".$quinc[1];
}else{
    $fecha=$periodo[0]."-".$periodo[1];
}

$query = 'SELECT * FROM Nomina WHERE Nomina="'.$fecha.'"' ;

// echo $query;exit;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$nominas = $oCon->getData();
unset($oCon);

if(count($nominas)>0){
    foreach ($nominas as $nomina) {
        if($nomina['Id_Grupo']==0){
            $resultado['mensaje']="La nomina para este periodo ya se pago";
            $resultado['tipo']="error";
            echo json_encode($resultado);
            return;
        }else{
            if($nomina['Id_Grupo']==$id){
            $resultado['mensaje']="La nomina para este Grupo y en este periodo ya se pago";
            $resultado['tipo']="error";
            echo json_encode($resultado);
            return;
            }
        }
    }
    $resultado=0;
    
}else{
    $resultado=0;
}

$resultado=0;

echo json_encode($resultado);


?>