<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );


$datos = (array) json_decode($datos);
if ($datos['Tipo_Rem']!='Devolucion') {
    # code...


    $oItem = new complex("Remision",'Id_Remision',$datos["Id_Remision"]);
    $estado=$oItem->Estado;
    $oItem->Estado= $estado=="Facturada" ? "Facturada": "Enviada";
    $oItem->Guia=strtoupper($datos["Numero_Guia"]);
    $oItem->Empresa_Envio=strtoupper($datos["Empresa_Envio"]);
    $oItem->save();
    $remision = $oItem->getData();
    unset($oItem);

    //Guardar actividad de la remision 

    $oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
    $oItem->Id_Remision=$datos["Id_Remision"];
    $oItem->Identificacion_Funcionario=$datos['Identificacion_Funcionario'];
    if($datos['Tipo']=="Creacion"){
        $oItem->Detalles="Se envia la Remision ".$remision["Codigo"]." con el Numero de guia ".$datos["Numero_Guia"]." con la empresa ".$datos["Empresa_Envio"];
    }else{
        $oItem->Detalles="Se modifico la guia de la remision ".$remision["Codigo"]." al Numero de guia ".$datos["Numero_Guia"]." y empresa ".$datos["Empresa_Envio"];
    }
    $oItem->Fecha=date("Y-m-d H:i:s");
    $oItem->Estado="Enviada";
    $oItem->save();
    unset($oItem);
}else{
    
    $oItem = new complex("Devolucion_Compra",'Id_Devolucion_Compra',$datos["Id_Remision"]);

    $oItem->Estado = "Enviada";
    $oItem->Guia=strtoupper($datos["Numero_Guia"]);
    $oItem->Empresa_Envio=strtoupper($datos["Empresa_Envio"]);
    $oItem->save();
    $remision = $oItem->getData();
    unset($oItem);

    //Guardar actividad de la remision 

    $oItem = new complex('Actividad_Devolucion_Compra',"Id_Actividad_Devolucion_Compra");
    $oItem->Id_Devolucion_Compra=$datos["Id_Remision"];
    $oItem->Identificacion_Funcionario=$datos['Identificacion_Funcionario'];
    if($datos['Tipo']=="Creacion"){
        $oItem->Detalles="Se envia la Remision ".$remision["Codigo"]." con el Numero de guia ".$datos["Numero_Guia"]." con la empresa ".$datos["Empresa_Envio"];
    }else{
        $oItem->Detalles="Se modifico la guia de la remision ".$remision["Codigo"]." al Numero de guia ".$datos["Numero_Guia"]." y empresa ".$datos["Empresa_Envio"];
    }
    $oItem->Fecha=date("Y-m-d H:i:s");
    $oItem->Estado="Enviada";
    $oItem->save();
    unset($oItem);
}



$resultado['mensaje'] = "Se ha guardado correctamente el n&uacute;mero de gu&iacute;a de la Remision con codigo: ". $remision['Codigo'];
$resultado['tipo'] = "success";
echo json_encode($resultado);

?>	