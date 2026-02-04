<?php 


$error = '¡No se puede anular!';
$Acomodada_F = 'La entrada del ajuste fue acomodada en las estibas';
$Anulado_F = 'El Ajuste ya fue anulado previamente';
$Datos_F = 'Faltan datos para realizar la operación';
$Cantidad_F = '';

$Ok = '¡Operación Exitosa!';
$Anulado_Ok = 'El Ajuste fue anulado exitosamente';
$ActividadS = 'Se  Anuló la salida del ajuste individual, observación:'.$motivo_anulacion;
$ActividadE = 'Se  Anuló la entrada del ajuste individual, observación: '.$motivo_anulacion;

function cambiarEstado( $id_ajuste , $tipo ){
    global $funcionario, $motivo_anulacion;
    $oItem = new complex('Ajuste_Individual', 'Id_Ajuste_Individual',$id_ajuste);
    $oItem->Estado = 'Anulada';
    //$tipo == 'Entrada' ?   $oItem->Estado_Entrada_Bodega = 'Anulado' : '';
    $oItem->Fecha_Anulacion = date('Y-m-d H:i:s');
    $oItem->Funcionario_Anula = $funcionario;
    $oItem->Observacion_Anulacion = $motivo_anulacion;
    $oItem->save();
    unset($oItem);
}


function devolverInventario($id_ajuste_salida){
    $query = ' SELECT * FROM Producto_Ajuste_Individual 
                WHERE Id_Ajuste_Individual = '.$id_ajuste_salida;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
    unset($oCon);
 
    foreach ($productos as $key => $producto) {
        # code...
        $query = 'UPDATE Inventario_Nuevo
                    SET Cantidad = Cantidad + '.$producto['Cantidad'].' 
                        WHERE Id_Inventario_Nuevo ='.$producto['Id_Inventario_Nuevo'];
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->createData();
        unset($oCon);
    }
}




function retirarDeInventario($id_ajuste_salida){
    $query = ' SELECT * FROM Producto_Ajuste_Individual 
                WHERE Id_Ajuste_Individual = '.$id_ajuste_salida;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
    unset($oCon);
 
    foreach ($productos as $key => $producto) {
        # code...
        $query = 'UPDATE Inventario_Nuevo
                    SET Cantidad = Cantidad - '.$producto['Cantidad'].' 
                        WHERE Id_Inventario_Nuevo ='.$producto['Id_Inventario_Nuevo'];
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->createData();
        unset($oCon);
    }
}


function validarCantidades($id_ajuste){
    global $Cantidad_F;
    $query = ' SELECT * FROM Producto_Ajuste_Individual 
                WHERE Id_Ajuste_Individual = '.$id_ajuste;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
    unset($oCon);

    foreach ($productos as $key => $producto) {
        # code...
        $query = 'SELECT Cantidad, Codigo_CUM FROM Inventario_Nuevo
                        WHERE Id_Inventario_Nuevo ='.$producto['Id_Inventario_Nuevo'];
        $oCon = new consulta();
        $oCon->setQuery($query);
        $inventario = $oCon->getData();
        unset($oCon);
        
        if ( $producto['Cantidad'] > $inventario['Cantidad'] ) {
            # code...
            $Cantidad_F = 'El producto con código Cum '.$inventario['Codigo_CUM'].' No tiene suficientes cantidades';
            return false;
        }
    }

    return true;
}



function setResponse($type,$title,$text,&$response){
    $response['type'] = $type;
    $response['title'] = $title;
    $response['text'] = $text;
}


function AnularMovimientoContable($idRegistroModulo){
    global $contabilizar;

    $contabilizar->AnularMovimientoContable($idRegistroModulo, 8);
}