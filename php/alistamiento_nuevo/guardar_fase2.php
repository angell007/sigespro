<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$idc = ( isset( $_REQUEST['idc'] ) ? $_REQUEST['idc'] : '' );
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$peso = ( isset( $_REQUEST['peso'] ) ? $_REQUEST['peso'] : '' );

if(empty($peso) || $peso=='undefined'){
    $peso=0;
}

$productos = (array) json_decode($productos , true); 

$id_mod = 'Id_'.$mod;
if ( (validarProductos() && $mod=='Devolucion_Compra') || $mod == 'Remision') {
    $oItem = new complex($mod,'Id_'.$mod,$id);
    $oItem->Estado_Alistamiento=2;
    $oItem->Fin_Fase2=date("Y-m-d H:i:s");
    $oItem->Estado="Alistada";
    $oItem->Peso_Remision=$peso;
    $oItem->save();
    unset($oItem);
        
    /*foreach($remision as $index=>$value) {
        $oItem->$index=$value;
    }*/

    $oItem = new complex($mod,'Id_'.$mod,$id);
    $remision = $oItem->getData();
    unset($oItem);

    //Guardar actividad de la remision 
    $oItem = new complex('Actividad_'.$mod,"Id_Actividad_".$mod);
    $oItem->$id_mod=$id;
    $oItem->Identificacion_Funcionario=$funcionario;
    $oItem->Detalles="Se realizo la Fase 2 de Alistamiento de la Remision ".$remision["Codigo"];
    $oItem->Estado="Fase 2";
    $oItem->Fecha=date("Y-m-d H:i:s");
    $oItem->save();
    unset($oItem);

    foreach($productos as $producto){

        //Descontar del inventario
        if($idc != '' && $idc != 0)
        {
            ActualizarContrato();
        }

        $oItem = new complex('Inventario_Nuevo','Id_Inventario_Nuevo', $producto["Id_Inventario_Nuevo"]);
        $inv=$oItem->getData();
        $apartada=number_format($inv["Cantidad_Apartada"],0,"","");
        $cantidad=number_format($inv["Cantidad"],0,"","");
        $actual = number_format($producto["Cantidad"],0,"","");     

        if ($mod=='Devolucion_Compra') {
            $final = $cantidad - $actual;
         
             if($final<0){
                 $final=0;
            }
            $oItem->Cantidad=number_format($final,0,"","");

        }else{
            $fin = $apartada - $actual;
            $final = $cantidad - $actual;

             if($fin<0){
                   $fin=0;
            }
            if($final<0){
                  $final=0;
            }

           $oItem->Cantidad_Apartada=number_format($fin,0,"","");
           $oItem->Cantidad=number_format($final,0,"","");
                
            }
            $oItem->save();
            unset($oItem);

        }
        $resultado['title'] = "Operación Exitosa";
        $resultado['mensaje'] = "Se ha guardado correctamente la Fase 2 de la Remision con codigo: ". $remision['Codigo'];
        $resultado['tipo'] = "success";
        
}else{
    $resultado['title'] = "Operación Denegada";
    $resultado['mensaje'] = "Las cantidades disponibles en inventario  son menores de las que intenta extraer";
    $resultado['tipo'] = "error";
 
}
echo json_encode($resultado);

function ActualizarContrato(){

    global $idc, $productos;

    foreach($productos as $producto){

        $query = "SELECT Id_Inventario_Contrato FROM Inventario_Contrato
                    WHERE Id_Contrato = $idc AND Id_Inventario_Nuevo = ".$producto['Id_Inventario_Nuevo']."  ";
        $oCon = new consulta();
        $oCon->setQuery($query);
        $idc = $oCon->getData();
        unset($oCon);
        if ($idc) {
            $oItem = new complex('Inventario_Contrato','Id_Inventario_Contrato', $idc['Id_Inventario_Contrato']);
        }
        $inv=$oItem->getData();
        $apartada=number_format($inv["Cantidad_Apartada"],0,"","");
        $cantidad=number_format($inv["Cantidad"],0,"","");
        $actual = number_format($producto["Cantidad"],0,"","");  
        
        $fin = $apartada - $actual;
        $final = $cantidad - $actual;

         if($fin<0){
               $fin=0;
        }
        if($final<0){
              $final=0;
        }

       $oItem->Cantidad_Apartada=number_format($fin,0,"","");
       $oItem->Cantidad=number_format($final,0,"","");
            
        }
        $oItem->save();
        unset($oItem);
    }

function  validarProductos(){
    global $productos;
    $existencia_suficiente=true;
    foreach($productos as $producto){

        //validar existencia
        
        $oItem = new complex('Inventario_Nuevo','Id_Inventario_Nuevo', $producto["Id_Inventario_Nuevo"]);
        $inv=$oItem->getData();
        $cantidad=number_format($inv["Cantidad"],0,"","");
        $apartada=number_format($inv["Cantidad_Apartada"],0,"","");
        $seleccionada=number_format($inv["Cantidad_Seleccionada"],0,"","");
        $actual = number_format($producto["Cantidad"],0,"","");


        $subtotal = $cantidad - ($apartada + $seleccionada);
        if ($subtotal<$actual) {
            $existencia_suficiente=false;
            break;
        }
        
    }

    return $existencia_suficiente;
}
?>	