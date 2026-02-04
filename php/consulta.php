<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');

$puntos = [56,48,61,75,124,148,3,2,155,106,156,154,84,90,81,146,101,95,77,78,85,105,70,38,52,19,68,7,157,10,159,8,9,12,13,14,15,16,17,18,20,22,25,26,27,30,37,39,41,45,47,50,51,54,55,58,60,62,65,66,67,69,71,72,73,74,76,82,87,94,96,97,107,114,123,126,128,129,147,158];

for($i=0; $i<count($puntos);$i++)
{
    //echo "entro a for".PHP_EOL;
    $query = 'SELECT * FROM inventario I
    WHERE I.Id_Punto_Dispensacion ='.$puntos[$i].'
    AND I.Cantidad > 0
    '; 
    
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
    unset($oCon);
    //echo PHP_EOL." productos ################################################################################## ".PHP_EOL;
    echo json_encode($productos);
    //echo PHP_EOL." ajuste individual ################################################################################## ".PHP_EOL;
    //exit;

    $oLista = new lista('ajuste_individual');
    $ajuste_individual = $oLista->getlist();
    unset($oLista);    
    
    echo json_encode($ajuste_individual);

    $id_ajuste = $ajuste_individual[$i]["Id_Ajuste_Individual"];

    //echo PHP_EOL."Antes del foreach".PHP_EOL;
    //echo PHP_EOL." producto_ajuste_individual ################################################################################## ".PHP_EOL;
    foreach($productos as $prod){
        //echo PHP_EOL."entro a foreach".PHP_EOL;
        
        $oLista = new lista('producto_ajuste_individual');
        $producto_ajuste_individual = $oLista->getlist();
        unset($oLista);
        
        echo json_encode($producto_ajuste_individual);

    }
    //echo PHP_EOL."salio del foreach".PHP_EOL;
    // $oItem = new complex("ajuste_individual","Id_Ajuste_Individual");

    // $oItem->Id_Ajuste_Individual = '';
    // $oItem->Codigo = '';
    // $oItem->Fecha = '';
    // $oItem->Identificacion_Funcionario = '';
    // $oItem->Tipo = '';
    // $oItem->Id_Clase_Ajuste_Individual = '';
    // $oItem->Origen_Destino = '';
    // $oItem->Id_Origen_Destino = '';
    // $oItem->Codigo_Qr = '';
    // $oItem->Estado = '';
    // $oItem->Observacion_Anulacion = '';
    // $oItem->Funcionario_Anula = '';
    // $oItem->Fecha_Anulacion = '';

    //$oItem->save();

    //$id_ajuste = $oItem->getId();

    //unset($oItem);

    // foreach($productos as $prod){

    //     $oItem = new complex("producto_ajuste_individual","Id_Producto_Ajuste_Individual");
    //     $oItem->Id_Producto_Ajuste_Individual = '';
    //     $oItem->Id_Ajuste_Individual = '';
    //     $oItem->Id_Producto = '';
    //     $oItem->Id_Inventario = '';
    //     $oItem->Lote = '';
    //     $oItem->Fecha_Vencimiento = '';
    //     $oItem->Cantidad = '';
    //     $oItem->Costo = '';
    //     $oItem->Observaciones = '';
    //     $oItem->Id_Ajuste_Individual = $id_ajuste;

    //     //$oItem->save();

    //     unset($oItem);

    // }
}
//echo json_encode($respuesta);
//echo "Termino.".PHP_EOL;
?>