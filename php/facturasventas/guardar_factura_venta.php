<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.contabilizar.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */
include_once('../../class/class.facturacion_electronica_estructura.php');


$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$remision = ( isset( $_REQUEST['id_remision'] ) ? $_REQUEST['id_remision'] : '' );


$contabilizar = new Contabilizar();
$datos = (array) json_decode($datos, true); 

$productos = (array) json_decode($productos , true);
$remision = (array) json_decode($remision , true);

// Validaciones de entrada Si $mod, $datos o $productos no existen,
$id_cliente = isset($datos['Cliente']['Id_Cliente']) ? $datos['Cliente']['Id_Cliente'] : (isset($datos['Id_Cliente']) ? $datos['Id_Cliente'] : '');
if ($mod == '' || empty($datos) || empty($productos)) {
    $resultado['mensaje'] = "Datos incompletos: modulo, datos o productos vacíos.";
    $resultado['tipo'] = "error";
    echo json_encode($resultado);
    exit;
}

if ($id_cliente == '') {
    $resultado['mensaje'] = "Datos incompletos: no se envió el cliente.";
    $resultado['tipo'] = "error";
    echo json_encode($resultado);
    exit;
}


$query = "SELECT * FROM Resolucion WHERE Modulo='General' AND Fecha_Fin>=CURDATE() AND Estado='Activo' AND Consecutivo<=Numero_Final ORDER BY Fecha_Fin ASC LIMIT 1";

$oCon = new consulta();
$oCon->setQuery($query);
$resolucion = $oCon->getData();
unset($oCon);

$Remisiones_Encontradas=encontrarRemisiones($remision);
if(!$Remisiones_Encontradas){
    if ($resolucion) {
        if (count($productos) > 0) { // Si la factura trae productos.
            $configuracion = new Configuracion();
        
            $cod = getConsecutivo($resolucion);
            $datos['Codigo']=$cod;
            $datos["Id_Resolucion"]=$resolucion["Id_Resolucion"];
            $oItem = new complex($mod,"Id_".$mod);
            foreach($datos as $index=>$value) {
                $oItem->$index=$value;
            }
            $oItem->save();
            $id_factura = $oItem->getId();
            $resultado = array();
            unset($oItem);
            
            /* AQUI GENERA QR */
            $qr = generarqr('facturaventa',$id_factura,'/IMAGENES/QR/');
            $oItem = new complex("Factura_Venta","Id_Factura_Venta",$id_factura);
            $oItem->Codigo_Qr=$qr;
            $oItem->save();
            unset($oItem);
            /* HASTA AQUI GENERA QR */
            
            // unset($productos[count($productos)-1]);
            foreach($productos as $i => $producto){
                $oItem = new complex('Producto_'.$mod,"Id_Producto_".$mod);
                $producto["Id_".$mod]=$id_factura;
                if($producto["Id_Inventario"]==''){
                    unset($producto["Id_Inventario"]);
                }
                foreach($producto as $index=>$value) {
                    $oItem->$index=$value;
                }
                if (isset($producto['Impuesto']) && $id_cliente != '' && !aplicaIva($id_cliente) && $producto['Impuesto'] != 0) {
                    $oItem->Impuesto = '0';
                    $productos[$i]['Impuesto'] = 0;
                }
                
                $oItem->Id_Inventario_Nuevo = 0;
                $oItem->Id_Inventario = 0 ;
                
                $oItem->save();
                $id_prod=$oItem->getId();

              
                if($mod == 'Factura_Venta' && isset($producto['Id_Producto_Remision']) && trim($producto['Id_Producto_Remision']) !== ''){
                    //$oItem2 = new complex('Producto_Remision',"Id_Producto_Remision",$producto['Id_Producto_Remision']);
                    $query = 'UPDATE Producto_Remision SET Id_Producto_Factura_Venta = '.$id_prod.' 
                                WHERE Id_Producto_Remision IN('.$producto['Id_Producto_Remision'].')';
                    
                    $oCon=new consulta($query);
                    $oCon->setQuery($query);
                    $oCon->createData();

                    //$oItem2->Id_Producto_Factura_Venta = $id_prod;
                    //$oItem2->save();
                    unset($oCon);
                }
               
                unset($oItem);
            }
            
            if(isset($remision)&&count($remision)>0){ 
    
                //CAMBIO HECHO POR FRANKLIN GUERRA 21/06/19
                // $in = InCondition($remision);
                // $query_update = "UPDATE Remision SET Id_Factura = ".$id_factura.", Estado = 'Facturada' WHERE Codigo IN (".$in.")";
                // $oCon = new consulta();
                // $oCon->setQuery($query_update);
                // $oCon->createData();
                // unset($oCon);
    
                foreach($remision as $remisiones){
    
                    $oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
                    $oItem->Id_Remision=$remisiones['id'];
                    $oItem->Identificacion_Funcionario=$datos["Id_Funcionario"];
                    $oItem->Detalles="Se facturo la remision con codigo ".$remisiones['Codigo'];
                    $oItem->Estado="Facturada";
                    $oItem->Fecha=date("Y-m-d H:i:s");
                    $oItem->save();
                    unset($oItem);
                }
    
                //CODIGO ORIGINAL
                // foreach($remision as $remisiones){
                //     $oItem = new complex('Remision',"Id_Remision",$remisiones['id']);
                //     $codigo=$oItem->Codigo;
                //     $oItem->Id_Factura = number_format($id_factura,0,"","");
                //     $oItem->Estado = "Facturada";
                //     $oItem->save();
                //     unset($oItem);
            
                //     $oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
                //     $oItem->Id_Remision=$remisiones['id'];
                //     $oItem->Identificacion_Funcionario=$datos["Id_Funcionario"];
                //     $oItem->Detalles="Se facturo la remision con codigo ".$codigo;
                //     $oItem->Estado="Facturada";
                //     $oItem->Fecha=date("Y-m-d H:i:s");
                //     $oItem->save();
                //     unset($oItem);
                // }
            }
            
            
            if($id_factura != ""){
                # CONTABILIZACIÓN
                $datos_movimiento_contable['Id_Registro'] = $id_factura; 
                $datos_movimiento_contable['Nit'] = $id_cliente;
                $datos_movimiento_contable['Productos'] = $productos;
                
                try{
                    $contabilizar->CrearMovimientoContable('Factura Venta', $datos_movimiento_contable);
                }catch(Exception $e){
                    error_log("Error contabilizando Factura_Venta {$id_factura}: ".$e->getMessage());
                }
    
                ActualizarEstadoRemisiones($remision, $id_factura);
            
                $datos_fac["Estado"]='';
                $datos_fac["Detalles"]='';
                
                          
                if($resolucion["Tipo_Resolucion"]=="Resolucion_Electronica"){
                   $fe1 = new FacturaElectronica("Factura_Venta",$id_factura, $resolucion["Id_Resolucion"]); 
                   $datos_fac = $fe1->GenerarFactura(); 
                   //var_dump($datos_fac);
                } 
        
                $resultado['Factura']= $datos_fac;
                $resultado['Id']= $id_factura;
                $resultado['mensaje'] = "Se ha guardado Correctamente la Factura de Venta con Codigo: ". $datos['Codigo']." - ".trim($datos_fac["Detalles"]);
                $resultado['tipo'] = "success";
            }else{
                $resultado['mensaje'] = "Ha ocurrido un error guardando la informacion, por favor verifique";
                $resultado['tipo'] = "error";
            }   
        } else {
            $resultado['mensaje'] = "Ha ocurrido un error al facturar los productos. Por favor comunicarse con soporte tecnico.";
            $resultado['tipo'] = "error";
        }
    } else {
        $resultado['mensaje'] = "No existe una resolución valida para esta factura.";
        $resultado['tipo'] = "error";
    }


}else{
    $resultado['titulo'] = "Creacion no exitosa";
    $resultado['mensaje'] = "Tiene las siguientes Remisones facturadas con anterioridad :". $Remisiones_Encontradas ;
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);




function getConsecutivo($resolucion) {
    $cod = $resolucion['Codigo'] != '0' ? $resolucion['Codigo'] . $resolucion['Consecutivo'] : $resolucion['Consecutivo'];
    $oItem = new complex('Resolucion','Id_Resolucion',$resolucion['Id_Resolucion']);
    $new_cod = $oItem->Consecutivo + 1;
    $oItem->Consecutivo = number_format($new_cod,0,"","");
    $oItem->save();
    unset($oItem);
    
    $query = "SELECT Id_Factura_Venta FROM Factura_Venta  WHERE Codigo = '$cod'";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $res = $oCon->getData();
    
    if($res && isset($res["Id_Factura_Venta"])){
        $oItem = new complex('Resolucion','Id_Resolucion',$resolucion['Id_Resolucion']);
        $nc = $oItem->getData();
        unset($oItem);
        getConsecutivo($nc);
    }
    return $cod;
}

function InCondition($remisiones){
    $in = '';
    foreach ($remisiones as $r) {
        $in .= '"'.$r['Codigo'].'",';
    }

    return trim($in, ",");
}

function ActualizarEstadoRemisiones($remisiones, $id_factura){
    foreach ($remisiones as $r) {
        $query_update = "UPDATE Remision SET Id_Factura = ".$id_factura.", Estado = 'Facturada' WHERE Codigo = '".$r['Codigo']."'";
        $oCon = new consulta();
        $oCon->setQuery($query_update);
        $oCon->createData();
        unset($oCon);
    }
}

function aplicaIva($id_cliente) {
    $oItem = new complex('Cliente','Id_Cliente',$id_cliente);
    $datos = $oItem->getData();
    unset($oItem);

    return $datos['Impuesto'] == 'Si' ? true : false;
} 
function encontrarRemisiones($remisiones){
    $remisiones_encontradas="";
    
    foreach ($remisiones as  $remision) {
        $query = "SELECT Id_Factura_Venta, Fecha_Documento FROM Factura_Venta WHERE Observacion_Factura_Venta LIKE  '%".$remision['Codigo']."%' AND Estado != 'Anulada' AND  Nota_Credito != 'Si'  ";
        $oCon = new consulta();
        $oCon->setQuery($query);
        $res = $oCon->getData();
        if($res["Id_Factura_Venta"] ){
        
            $oItem = new complex("Remision","Id_Remision",$remision['id']);
            $oItem->Id_Factura = $res["Id_Factura_Venta"];
            $oItem->Fecha = $res["Fecha_Documento"];
            $oItem->Estado = "Facturada";
            $oItem->save();
            unset($oItem);
            
            $remisiones_encontradas.=$remision['Codigo'].', ';
        }
    }
    if(strlen($remisiones_encontradas)!=0){
        return $remisiones_encontradas;
    }else{
        return false;
    }

}


?>
