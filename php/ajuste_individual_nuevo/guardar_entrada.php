<?php 
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');


require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */
include_once('../../class/class.contabilizar.php');

include_once('../../class/class.consulta.php');
require_once('../../class/class.configuracion.php');
require_once('./helper.ajuste_individual.php');

$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$tipo_ajuste = ( isset( $_REQUEST['tipo_ajuste'] ) ? $_REQUEST['tipo_ajuste'] : '' );
$cod = '';
$productos = json_decode($productos,true);
$datos = json_decode($datos,true);


if ($tipo_ajuste=='Entrada') {
  
    if ($datos['Tipo']=='Bodega') {
       //creamos el ajuste individual

        $id_ajuste = Save_Encabezado('Bodega');
         /* AQUI GENERA QR 
        $qr = generarqr('ajusteindividual',$id_ajuste,'IMAGENES/QR/');
        $oItem = new complex("Ajuste_Individual","Id_Ajuste_Individual",$id_ajuste);
        $oItem->Codigo_Qr=$qr;
        $oItem->save();
        unset($oItem);
        HASTA AQUI GENERA QR */

        
        foreach($productos as $key => $producto){
            if ($producto['Costo_Nuevo']) {
                # code...
                $productos[$key]['Costo'] = $producto['Costo_Nuevo'];
              /*   Guardar_Costo_Nuevo($producto); */
            }

            Guardar_Producto_Ajuste($productos[$key]);
        }
        if ($id_ajuste) {
            guardarActividad($id_ajuste,$funcionario,'Se creó la entrada del ajuste individual '.$cod, 'Creacion');
            $datos_movimiento_contable['Id_Registro'] = $id_ajuste;
            $datos_movimiento_contable['Nit'] = $funcionario;
            $datos_movimiento_contable['Tipo'] = "Entrada";
            $datos_movimiento_contable['Clase_Ajuste'] = $datos['Id_Clase_Ajuste_Individual'];
            $datos_movimiento_contable['Productos'] = $productos;

            $contabilizacion = new Contabilizar(true);
            $contabilizacion->CrearMovimientoContable('Ajuste Individual',$datos_movimiento_contable); 
            unset($contabilizacion);
            
            # code...
            $response['tipo'] = 'success';
            $response['title'] = 'Ajuste Indivual creado exitosamente ';
            $response['mensaje'] = '¡Se ha creado el ajuste de entrada, ahora puede acomodar los productos!';
            
        }else{
            $response['tipo'] = 'error';
            $response['title'] = 'Error inesperado ';
            $response['mensaje'] = '¡Ha ocurrido un error, comuníquese con el Dpt. de sistemas!';
        }


    }else if($datos['Tipo']=='Punto'){

         $id_ajuste = Save_Encabezado('Punto'); 
         $id_estiba_default = GetEstibaDefaultPunto($datos['Id_Punto_Dispensacion']);
         if (!$id_estiba_default) {
            $response['tipo'] = 'error';
            $response['title'] = 'Error';
            $response['mensaje'] = 'No hay estiba disponible para el punto seleccionado.';
            echo json_encode($response);
            exit;
         }
         
        foreach($productos as $key => $producto){
            if ($producto['Costo_Nuevo']) {
                # code...
                $productos[$key]['Costo'] = $producto['Costo_Nuevo'];
              /*   Guardar_Costo_Nuevo($producto); */
            }

            if ($datos['Id_Punto_Dispensacion'] != "") {
                $query = "SELECT Id_Inventario_Nuevo, Cantidad 
                        FROM Inventario_Nuevo
                            WHERE Id_Producto=".$producto['Id_Producto']."
                            AND Lote='". trim(  $producto['Lote']  )."'
                            #AND Fecha_Vencimiento='".$producto['Fecha_Vencimiento']."' 
                            AND Id_Punto_Dispensacion=".$datos['Id_Punto_Dispensacion']."
                            AND (Id_Estiba = $id_estiba_default OR Id_Estiba IS NULL OR Id_Estiba = 0)";

            }
       
            $oCon= new consulta();
            $oCon->setQuery($query);
        
          
            $inventario = $oCon->getData();
            unset($oCon);
            
            if ($inventario) { // Si existe el producto en el inventario
                $oItem = new complex('Inventario_Nuevo','Id_Inventario_Nuevo', $inventario['Id_Inventario_Nuevo']);
                $cantidad = number_format($producto["Cantidad"],0,"","");
                $cantidad_inventario = number_format($inventario["Cantidad"],0,"","");
                $cantidad_final = $cantidad_inventario + $cantidad;
                $oItem->Cantidad = $cantidad_final;
              /*   $costo = number_format($producto["Costo"],0,".",""); */
                $oItem->Costo=$costo;
                $oItem->Id_Estiba = $id_estiba_default;
                $id_inventario_nuevo = $oItem->Id_Inventario_Nuevo;
            } else {
                $oItem = new complex('Inventario_Nuevo','Id_Inventario_Nuevo');
                $cantidad = number_format($producto["Cantidad"],0,"","");
                $oItem->Cantidad=$cantidad;
                $oItem->Id_Producto=$producto["Id_Producto"];
                $oItem->Codigo_CUM=$producto["Codigo_Cum"];
                $oItem->Lote= trim( strtoupper( $producto['Lote'] ) );
                $oItem->Fecha_Vencimiento=$producto["Fecha_Vencimiento"];
                $oItem->Id_Bodega=0;  
                $oItem->Id_Punto_Dispensacion=$datos["Id_Punto_Dispensacion"]; 
                $oItem->Id_Estiba = $id_estiba_default;
                 $costo = number_format($productos[$key]["Costo"],5,".",""); 
                $oItem->Costo=$costo;
                $oItem->Cantidad_Apartada=0;
            }
                
            $oItem->Identificacion_Funcionario=$funcionario;
                
                $oItem->save();
                
                
                if (!$inventario) { // Si no existe el producto en el inventario obtengo el último id registrado
                    $id_inventario_nuevo = $oItem->getId();
                }
                unset($oItem);
                Guardar_Producto_Ajuste($productos[$key],$id_inventario_nuevo);
                
        }

        if ($id_ajuste) {
            # code...


           
            guardarActividad($id_ajuste,$funcionario,'Se creó la entrada del ajuste individual '.$cod, 'Creacion');
          
            $datos_movimiento_contable['Id_Registro'] = $id_ajuste;
            $datos_movimiento_contable['Nit'] = $funcionario;
            $datos_movimiento_contable['Tipo'] = "Entrada";
            $datos_movimiento_contable['Clase_Ajuste'] = $datos['Id_Clase_Ajuste_Individual'];
            $datos_movimiento_contable['Productos'] = $productos;
        

            $contabilizacion = new Contabilizar(true);
            $contabilizacion->CrearMovimientoContable('Ajuste Individual',$datos_movimiento_contable); 
            unset($contabilizacion);


            $response['mensaje'] = "Se ha guarda correctamente la Entrada en el Punto";
            $response['tipo'] = "success";
            $response['title'] = "Operación Exitosa";
        }
        
    }

}else{
    $response['tipo'] = 'error';
    $response['title'] = 'Error inesperado ';
    $response['mensaje'] = '¡Ha ocurrido un error, comuníquese con el Dpt. de sistemas!';
}

echo json_encode($response);



function Save_Encabezado($tipo){
    global $datos,$funcionario,$cod;
    $configuracion = new Configuracion();
    $cod = $configuracion->getConsecutivo('Ajuste_Individual','Ajuste_Individual');
    
    $oItem = new complex('Ajuste_Individual', 'Id_Ajuste_Individual');
    $oItem->Identificacion_Funcionario = $funcionario;
    $oItem->Codigo = $cod;
    $oItem->Tipo = "Entrada";
    $oItem->Id_Clase_Ajuste_Individual = $datos['Id_Clase_Ajuste_Individual'];


    if ($tipo=='Punto') {
        $oItem->Origen_Destino = $datos['Tipo'];
        $oItem->Id_Origen_Destino = $datos['Id_Punto_Dispensacion'];
        $oItem->Estado_Entrada_Bodega = 'Aprobada';

    }else if($tipo=='Bodega'){
        $oItem->Origen_Destino = $datos['Tipo'];
        $oItem->Id_Origen_Destino = $datos['Id_Bodega_Nuevo'] ;
        $oItem->Estado_Entrada_Bodega = 'Aprobada';

    }

    $oItem->save();
    $id_ajuste = $oItem->getId();
    unset($oItem);
    return $id_ajuste;

}


function Guardar_Producto_Ajuste($producto, $id_inventario_nuevo = false ){
    global $id_ajuste;
        
    $oItem = new complex('Producto_Ajuste_Individual','Id_Producto_Ajuste_Individual');
    $oItem->Id_Ajuste_Individual = $id_ajuste;
    $oItem->Id_Producto = $producto["Id_Producto"];
    $oItem->Lote = trim( strtoupper( $producto['Lote'] ) );
    $oItem->Fecha_Vencimiento = $producto['Fecha_Vencimiento'];
    $oItem->Observaciones = $producto['Observaciones'];
    $cantidad1= number_format($producto["Cantidad"],0,"","");
    $oItem->Cantidad =$cantidad1;
    $costo = number_format($producto["Costo"],0,".","");
    $oItem->Costo=$costo;

    if ($id_inventario_nuevo) {
        $oItem->Id_Inventario_Nuevo = $id_inventario_nuevo;
    }

    
    $oItem->save();
    unset($oItem);



}

function GetEstibaDefaultPunto($idPunto){
    if (!$idPunto) {
        return null;
    }
    $query = "SELECT Id_Estiba 
              FROM Estiba 
              WHERE Id_Punto_Dispensacion = $idPunto 
                AND Estado != 'Inactiva'
              ORDER BY (Estado = 'Disponible') DESC, Nombre, Id_Estiba
              LIMIT 1";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $estiba = $oCon->getData();
    unset($oCon);
    return $estiba ? $estiba['Id_Estiba'] : null;
}
