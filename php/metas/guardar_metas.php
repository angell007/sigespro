<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$datos = ( isset( $_REQUEST['datosMeta'] ) ? $_REQUEST['datosMeta'] : '' );
$cliente = ( isset( $_REQUEST['cliente'] ) ? $_REQUEST['cliente'] : '' );

$datos = (array) json_decode($datos);
$cliente = (array) json_decode($cliente,true);

if(isset($datos['Id']) && ($datos['Id']!=null || $datos['Id']!="")){
    $oItem = new complex("Meta","Id_Meta",$datos['Id']);
      
}else{
    $oItem = new complex("Meta","Id_Meta");
}

foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
    
$oItem->save();
$id_meta = $oItem->getId();
unset($oItem);

$id='';

foreach($cliente['Meses'] as  $value){    


    $query=' SELECT Id_Meta_Cliente  FROM Meta_Cliente WHERE Id_Cliente='.$cliente['Id_Cliente'].' AND Mes="'.$value['Mes'].'"';

    $oCon= new consulta();    
    $oCon->setQuery($query);
    $meta_cliente = $oCon->getData();
    unset($oCon);

    if($meta_cliente['Id_Meta_Cliente']){
        $oItem = new complex('Meta_Cliente', 'Id_Meta_Cliente', $meta_cliente['Id_Meta_Cliente']);
    }else{
        $oItem = new complex('Meta_Cliente', 'Id_Meta_Cliente'); 
    }
    
    $value['Id_Meta'] = $id_meta;
    $value['Id_Cliente'] = $cliente['Id_Cliente'];
    foreach($value as $index=>$item){
        $oItem->$index=$item;
    }
    $oItem->Valor_Medicamento=number_format($value['Valor_Medicamento'],2,".","");
    $oItem->Valor_Material=number_format($value['Valor_Material'],2,".","");
    $oItem->save();
    $id_meta_Cliente = $oItem->getId();
    unset($oItem);
    $id.= $id_meta_Cliente.",";
    
    /*$oItem->save();
    unset($oItem);*/
}
$resultado['tipo']="success";
$resultado['mensaje']="Cliente agregado corectamente";
$resultado['Id']= $id_meta;
$resultado['Id_Meta_Cliente']= trim($id,",");

echo json_encode($resultado);


?>