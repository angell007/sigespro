<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );


/*$oItem=new complex('Remision','Id_Remision',$id );
if($oItem->Fase_1 == 0){
$oItem->Fase_1 = 1;
} 
if ($oItem->Fase_1 == 1){
$oItem->Fase_2 = 1;
}

$oItem->save();
unset($oItem);*/

$query='SELECT R.*
FROM Remision R
WHERE R.Id_Remision='.$id; 
$oCon= new consulta();
//$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$remision= $oCon->getData();
unset($oCon);



$variableOrigen = variableOrigen();

$query='SELECT *
FROM '.$variableOrigen.'
WHERE Id_'.$variableOrigen.'='.$remision['Id_Origen']; 
$oCon= new consulta();
//$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$origen= $oCon->getData();
unset($oCon);

if ($remision['Tipo_Origen']=='Bodega') {
    
    $query='SELECT *
    FROM Bodega_Nuevo
    WHERE Id_Bodega_Nuevo='.$remision['Id_Origen']; 
    $oCon= new consulta();
    //$oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $bodegaOrigen= $oCon->getData();
    unset($oCon);
    

}

$query='SELECT *
FROM '.$remision['Tipo_Destino'].'
WHERE Id_'.$remision['Tipo_Destino'].'='.$remision['Id_Destino']; 
$oCon= new consulta();
//$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$destino= $oCon->getData();
unset($oCon);


if($remision['Tipo_Lista']=="Contrato"){
$oItem=new complex('Contrato','Id_Contrato',$remision['Id_Lista'] );
$contrato=$oItem->getData();
$resultado['Contrato']=$contrato;
unset($oItem);
}elseif($remision['Tipo_Lista']=="Lista_Ganancia"){
$oItem=new complex('Lista_Ganancia','Id_Lista_Ganancia',$remision['Id_Lista'] );
$contrato=$oItem->getData();
$resultado['Lista']=$contrato;
unset($oItem);
}

$resultado['Remision']=$remision;
$resultado['Origen']=$origen;
/* if ($categoriaOrigen) {
    $resultado['Categoria_Origen']=$categoriaOrigen;
}
 */
$resultado['Destino']=$destino;

echo json_encode($resultado);

function variableOrigen(){
    global $remision;

        
    if ($remision['Tipo_Origen'] == 'Bodega' ) {
        $variable = 'Bodega_Nuevo';
    }else{
        $variable = $remision['Tipo_Origen'];
    }

    return $variable;

}

?>