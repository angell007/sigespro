<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

// require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');

$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$tipo_ajuste = ( isset( $_REQUEST['tipo_ajuste'] ) ? $_REQUEST['tipo_ajuste'] : '' );

$productos = (array) json_decode($productos , true); 
$datos = (array) json_decode($datos ); 
/* var_dump($datos);

exit; */

if ($tipo_ajuste == "Lotes") {
  # code...
 

    $configuracion = new Configuracion();
    $cod = $configuracion->getConsecutivo('Ajuste_Individual','Ajuste_Individual');

    $oItem = new complex('Ajuste_Individual', 'Id_Ajuste_Individual');
    $oItem->Identificacion_Funcionario = $funcionario;
    $oItem->Codigo = $cod;
    $oItem->Tipo = "Lotes";
    $oItem->Id_Clase_Ajuste_Individual = $datos['Id_Clase_Ajuste_Individual'];
    $oItem->Origen_Destino = $datos['Tipo'];
    if ($datos['Tipo']=="Bodega") {
        $oItem->Id_Origen_Estiba = $datos['Id_Estiba']; 
        $oItem->Id_Origen_Destino = $datos['Id_Bodega_Nuevo'] ;

    }else if($datos['Tipo']=="Punto"){
        $oItem->Id_Origen_Destino = $datos['Id_Punto_Dispensacion'];
    }
    $oItem->save();
    $id_ajuste = $oItem->getId();
    unset($oItem);



    /* AQUI GENERA QR 

    $qr = generarqr('ajusteindividual',$id_ajuste,'IMAGENES/QR/');
    $oItem = new complex("Ajuste_Individual","Id_Ajuste_Individual",$id_ajuste);
    $oItem->Codigo_Qr=$qr;
    $oItem->save();
    unset($oItem);
    HASTA AQUI GENERA QR */
    
    if ($datos['Tipo']=="Punto") {
        # code...
        foreach($productos as $producto){
          
        
            #actualizar inventario
            $query = 'UPDATE Inventario_Nuevo SET Lote = "'.$producto['Lote_Nuevo'].'",
                            Fecha_Vencimiento =  "'.$producto['Fecha_Vencimiento_Nueva'].' "
                        WHERE Id_Producto = '.$producto['Id_Producto'].' AND Lote = "'.$producto['Lote'].'" AND Id_Punto_Dispensacion = '.$datos['Id_Punto_Dispensacion'];
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->createData();
            unset($oCon);
            
            #buscar productos dispensacion con ese lote, que pertenzcan al punto
            $query = 'SELECT  PD.Id_Producto_Dispensacion, PD.Id_Inventario_Nuevo FROM Dispensacion D
                        INNER JOIN Producto_Dispensacion PD ON PD.Id_Dispensacion = D.Id_Dispensacion 
                        WHERE D.Id_Punto_Dispensacion = '.$datos['Id_Punto_Dispensacion']
                        .' AND PD.Lote ="'.$producto['Lote'].'"';
        
            $oCon = new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
            $productos_dis=$oCon->getData();
            unset($oCon);

            foreach ($productos_dis as $prod) {
                # code...
            #actualizar lote dispensacion
                $oItem = new complex('Producto_Dispensacion','Id_Producto_Dispensacion',$prod['Id_Producto_Dispensacion']);
                $oItem->Lote = $producto["Lote_Nuevo"];
                $oItem->save();
                unset($oItem);
                
            }
            $id_producto_ajuste = guardarProductosAjuste($producto);
        }

    }else if($datos['Tipo']=="Bodega"){
        #-------
        foreach($productos as $producto){
            
        
            #actualizar inventario
            $query = 'UPDATE Inventario_Nuevo SET Lote = "'.$producto['Lote_Nuevo'].'",
                            Fecha_Vencimiento =  "'.$producto['Fecha_Vencimiento_Nueva'].' "
                        WHERE Id_Producto = '.$producto['Id_Producto'].' AND Lote = "'.$producto['Lote'].'" AND Id_Estiba = '.$datos['Id_Estiba'];
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->createData();
            unset($oCon); 
            
            #buscar productos Remision con ese lote, que pertenzcan al punto
            $query = 'SELECT  PR.Id_Producto_Remision, PR.Id_Inventario_Nuevo FROM Remision R
                        INNER JOIN Producto_Remision PR ON PR.Id_Remision = R.Id_Remision 
                        INNER JOIN Inventario_Nuevo I ON I.Id_Inventario_Nuevo  = PR.Id_Inventario_Nuevo
                        WHERE R.Tipo_Origen = "Bodega" AND R.Id_Origen = '.$datos['Id_Bodega_Nuevo']
                        .' AND PR.Lote ="'.$producto['Lote'].'" AND I.Id_Estiba = '.$datos['Id_Estiba'];
  
            $oCon = new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
            $productos_rem=$oCon->getData();
            unset($oCon);
        
            foreach ($productos_rem as $key => $prod) {
                # code...
                 #actualizar lote remision
                $oItem = new complex('Producto_Remision','Id_Producto_Remision',$prod['Id_Producto_Remision']);
                $oItem->Lote = $producto["Lote_Nuevo"];
                $oItem->Fecha_Vencimiento= $producto["Fecha_Vencimiento_Nueva"];
                $oItem->save();
                unset($oItem);
                
            }
            $id_producto_ajuste = guardarProductosAjuste($producto); 
        }

    }

   

    


if ($id_ajuste) {

   /*  $datos_movimiento_contable['Id_Registro'] = $id_ajuste;
    $datos_movimiento_contable['Nit'] = $funcionario;
    $datos_movimiento_contable['Tipo'] = "Salida";
    $datos_movimiento_contable['Clase_Ajuste'] = $datos['Id_Clase_Ajuste_Individual'];
    $datos_movimiento_contable['Productos'] = $productos;
    
    $contabilizacion->CrearMovimientoContable('Ajuste Individual',$datos_movimiento_contable);
   */
    $resultado['mensaje'] = "Se ha guarda correctamente el cambio de lote de los productos";
    $resultado['tipo'] = "success";
    $resultado['titulo'] = "Operación Exitosa";
  } else {
    $resultado['mensaje'] = "Ha ocurrido un error inesperado. Por favor intentelo de nuevo";
    $resultado['tipo'] = "error";
    $resultado['titulo'] = "Error";
  }
}else{
  $resultado['mensaje'] = "El tipo de ajuste no es permitido";
  $resultado['tipo'] = "error";
  $resultado['titulo'] = "Ha ocurrido un error inesperado.";
}

echo json_encode($resultado);

function guardarProductosAjuste($producto){
    global $id_ajuste;

  
    $oItem = new complex('Producto_Ajuste_Individual','Id_Producto_Ajuste_Individual');
    $oItem->Id_Ajuste_Individual = $id_ajuste;
    $oItem->Id_Producto = $producto["Id_Producto"];

    $oItem->Lote = $producto['Lote'];
    $oItem->Fecha_Vencimiento = $producto['Fecha_Vencimiento'];
    $oItem->Observaciones = $producto['Observaciones'];
    $oItem->Cantidad = $producto['Cantidad'];
    $oItem->Costo = 0;
    $oItem->Lote_Nuevo = $producto['Lote_Nuevo'];
    $oItem->Fecha_Vencimiento_Nueva = $producto['Fecha_Vencimiento_Nueva'];
  
    $oItem->save();
    $id_producto_ajuste= $oItem->getId();
    unset($oItem);

    return $id_producto_ajuste;
}

?>