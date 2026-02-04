<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */


$contabilizacion = new Contabilizar();
$modulo=GetModulo();
$id_devolucion = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

$oItem1 = new complex('Devolucion_Compra','Id_Devolucion_Compra', $id_devolucion);
$id_bodega=$oItem1->Id_Bodega;
$devolucion=$oItem1->getData();

if ($devolucion['Guia'] || $devolucion['Guia'] !='' ) {
    # code...
        
    $resultado['mensaje'] = "No se pueden anular Devoluciones enviadas!";
    $resultado['tipo'] = "error";

}else{
      

    $productos=GetProductos($id_devolucion);

    if ($devolucion['Id_Bodega_Nuevo'] && $devolucion['Id_Bodega_Nuevo'] != '' && $devolucion['Estado_Alistamiento'] && $devolucion['Estado_Alistamiento'] == 2) {
    
        if(!validarBodegaInventario($devolucion['Id_Bodega_Nuevo'])){

            //retorno inventario
            foreach($productos as $producto){
                $oItem = new complex('Inventario_Nuevo','Id_Inventario_Nuevo', $producto['Id_Inventario_Nuevo']);
                $cantidad_final = $oItem->Cantidad + number_format($producto['Cantidad'],0,"","");
                $oItem->Cantidad = number_format($cantidad_final,0,"","");
                $oItem->save();
                unset($oItem);
            }
            
            $resultado['mensaje'] = "¡Se ha anulado la devolución correctamente y ha retornado a inventario!";
            $resultado['tipo'] = "success";
        }else{
   
            $resultado['mensaje'] = "En este momento la bodega que seleccionó se encuentra realizando un inventario.";
            $resultado['tipo'] = "error";
            echo json_encode($resultado);
            exit;
        }

    }else{
         
        $resultado['mensaje'] = "¡Se ha anulado la devolución correctamente pero no hubo devolución a inventario! - Bodega Antigua";
        $resultado['tipo'] = "success";

    }  
    
    $oItem1->Estado="Anulada";
    $oItem1->save();
    unset($oItem1);
    //Guardar actividad de la  
    $oItem = new complex('Actividad_Devolucion_Compra',"Id_Actividad_Devolucion_Compra".$id_devolucion);
    $oItem->Id_Devolucion_Compra=$id_devolucion;
    $oItem->Identificacion_Funcionario=$funcionario;
    $oItem->Detalles="Se Anuló la devolución de compra ".$devolucion["Codigo"];
    $oItem->Estado="Anulada";
    $oItem->Fecha=date("Y-m-d H:i:s");
    $oItem->save();
    unset($oItem);

    $contabilizacion->AnularMovimientoContable($id_devolucion, $modulo);

}

echo json_encode($resultado);

function GetProductos($id){
    $query="SELECT * FROM Producto_Devolucion_Compra WHERE Id_Devolucion_Compra=$id ";
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
    unset($oCon);

    return $productos;
}

function GetModulo(){
    $query="SELECT * FROM Modulo WHERE Nombre='Devolucion Acta' ";
    $oCon= new consulta();
    $oCon->setQuery($query);
    $modelo = $oCon->getData();
    unset($oCon);

    return $modelo['Id_Modulo'];
}

function validarBodegaInventario($id_bodega){

    $query = 'SELECT DOC.Id_Doc_Inventario_Fisico
    FROM Doc_Inventario_Fisico DOC 
    INNER JOIN Estiba E ON E.Id_Estiba =  DOC.Id_Estiba 
    WHERE  DOC.Estado != "Terminado" AND E.Id_Bodega_Nuevo =  '.$id_bodega;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $documentos= $oCon->getData();
    return $documentos;
}

?>