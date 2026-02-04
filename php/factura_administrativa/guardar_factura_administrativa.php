<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');


require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

include_once('../../class/class.contabilizar.php');

include_once('../../class/class.facturacion_electronica_estructura.php');

$funcionario = (isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : '');
$cliente = (isset($_REQUEST['cliente']) ? $_REQUEST['cliente'] : '');
$observaciones = (isset($_REQUEST['observaciones']) ? $_REQUEST['observaciones'] : '');
$total = (isset($_REQUEST['total']) ? (float) $_REQUEST['total'] : '');
$centroCosto = (isset($_REQUEST['centroCosto']) ? $_REQUEST['centroCosto'] : '');
$tipoCliente = (isset($_REQUEST['tipoCliente']) ? $_REQUEST['tipoCliente'] : '');

$switch_activos = (isset($_REQUEST['switch_activos']) ? $_REQUEST['switch_activos'] : '');

$productos = (isset($_REQUEST['productos']) ? $_REQUEST['productos'] : '');
$productos = json_decode($productos, true);

if ($cliente) {
    # code...


    if ($tipoCliente == 'Cliente') {

    
        # code...
        $fecha = date('Y-m-d H:i:s');
        $oItem = new complex('Cliente', 'Id_Cliente', $cliente);
        $cliente_db = $oItem->getData();
        unset($oItem);
        
        $date = date("Y-m-d");
        if ($cliente_db['Condicion_Pago'] == '1') {
            $fechaPago = $date;
        } else {
            if (!$cliente_db['Condicion_Pago']) {
                $cliente_db['Condicion_Pago'] = '1';
            }
            $fechaPago =  date('Y-m-d', strtotime($date . '+ ' . $cliente_db['Condicion_Pago'] . ' days'));
        }
        $codicionPago = $cliente_db['Condicion_Pago'];
    }else{
        $fechaPago = date("Y-m-d"); 
        $codicionPago = '1';

    }

    
    $datos = buscarDatosFactura();
    if ($datos) {
        $query = 'INSERT INTO Factura_Administrativa 
        ( Activos_Fijos, Id_Cliente, Tipo_Cliente, Id_Resolucion, Codigo, Id_Centro_Costo, Identificacion_Funcionario,Observaciones,
        Estado_Factura, Procesada, Condicion_Pago,Fecha_Pago) 
        VALUES("'. $switch_activos . '",' . $cliente . ',"'.$tipoCliente.'",' . $datos['Id_Resolucion'] . ',"' . $datos['Codigo'] . '",' . $centroCosto . ',' . $funcionario . ',"' . $observaciones . '",
         "Sin Cancelar","false",' . $codicionPago . ',"' . $fechaPago . '")';



        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->createData();
        $idFactura = $oCon->getID();
        unset($oCon);

        /* AQUI GENERA QR */
       $qr = generarqr('facturaadministrativa',$idFactura,$MY_FILE.'/IMAGENES/QR/');
       $oItem = new complex("Factura_Administrativa","Id_Factura_Administrativa",$idFactura);
       $oItem->Codigo_Qr=$qr;
       $oItem->save();
       unset($oItem);
       /* HASTA AQUI GENERA QR */
        if ($switch_activos == 'Si') {
            
            guardarDescripicionesActivos($productos);
        }else{
            guardarDescripcionesGenerales($productos);
        }
        
        if($idFactura != ""){
                    
            $datos_movimiento_contable['Id_Registro'] = $idFactura; 
            $datos_movimiento_contable['Nit'] = $cliente;
       
            $contabilizar = new Contabilizar();
            $contabilizar->CrearMovimientoContable('Factura Administrativa', $datos_movimiento_contable);


            if($datos["Tipo_Resolucion"]=="Resolucion_Electronica"){
               $fe1 = new FacturaElectronica("Factura_Administrativa",$idFactura, $datos["Id_Resolucion"]); 
               $datos_fac = $fe1->GenerarFactura(); 
             
            }
          
            $resultado['mensaje'] = "Se ha guardado Correctamente la Factura de Venta con Codigo: ". $datos['Codigo'];
            $resultado['tipo'] = "success";
        }else{
            $resultado['mensaje'] = "Ha ocurrido un error guardando la informacion, por favor verifique";
            $resultado['tipo'] = "error";
        }   
    }

    if ($idFactura) {
       $result['titulo'] = '¡Creación exitosa!';
       $result['type'] = 'success';
       $result['mensaje'] = 'Se ha generado la factura exitosamente con el código: '.$datos['Codigo'];
       $result['Id'] = $idFactura;
       $result['Factura'] =$datos_fac;

    }else{
        $result['titulo'] = '¡Error Inesperado!';
       $result['type'] = 'error';
       $result['mensaje'] = 'Se generó un error al guardar, por favor vuelva a intentarlo o comuníquese con el equipo de soporte';
    }

    echo json_encode($result);
}


function guardarDescripcionesGenerales($productos){
    global $idFactura;
    foreach ($productos as $producto) {
        $descripcion = $producto['Descripcion'];

       $query = 'INSERT INTO Descripcion_Factura_Administrativa 
            (ID_Factura_Administrativa, Id_Plan_Cuenta,Descripcion,Referencia,
            Cantidad, Precio, Descuento, Impuesto,Subtotal) 
        VALUES(' . $idFactura . ',' . $producto['PlanCuenta']['Id_Plan_Cuentas'] . ',"' . $descripcion . '","' . $producto['Referencia'] . '",'
            . $producto['Cantidad'] . ',' . number_format($producto['Precio'],2,".","") . ',' . number_format($producto['Descuento'],2,".","") . ',' 
            . $producto['Impuesto'] . ',' . number_format($producto['Subtotal'],2,".","") . ' )';
     
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->createData();
        unset($oCon);
    }
}


function guardarDescripicionesActivos($productos){
    global $idFactura;
    foreach ($productos as $producto) {
        $descripcion = $producto['Nombre'];
       $saldo_pcga =  number_format( $producto['Costo_PCGA'] - $producto['Depreciacion_Acum_PCGA'] ,2,".","");
       $saldo_niif =  number_format( $producto['Costo_NIIF'] - $producto['Depreciacion_Acum_NIIF'] ,2,".","");

       $query = 'INSERT INTO Descripcion_Factura_Administrativa 
            (Id_Factura_Administrativa, Id_Activo_Fijo,Id_Plan_Cuenta,Descripcion,Referencia,
            Cantidad,Saldo_Activo_Fijo_PCGA,Saldo_Activo_Fijo_NIIF, Precio, Descuento, Impuesto,Subtotal) 
        VALUES(' . $idFactura . ','.$producto['ID'].',' . $producto['PlanCuenta']['Id_Plan_Cuentas'] . ',"' . $descripcion . '","' . $producto['Referencia'] . '",'
            . $producto['Cantidad'] . ','  .$saldo_pcga. ','.$saldo_niif.','. number_format($producto['Precio'],2,".","") . ',' . number_format($producto['Descuento'],2,".","") . ','
             . $producto['Impuesto'] . ',' . number_format($producto['Subtotal'],2,".","") . ' )';
     
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->createData();
        $idDescripcion = $oCon->getID();
        unset($oCon);
        
        $oItem = new complex('Activo_Fijo','Id_Activo_Fijo',$producto['ID']);
        $oItem->Estado = 'Vendido';
        $oItem->Id_Descripcion_Factura_Administrativa = $idDescripcion;
        $oItem->save();

        unset($oItem);
    }
}

#funciones
function buscarDatosFactura()
{
    $query = "SELECT * FROM Resolucion WHERE Modulo = 'Administrativo' AND Fecha_Fin > CURDATE() AND Consecutivo <=Numero_Final ORDER BY Fecha_Fin LIMIT 1";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resolucion = $oCon->getData();
    unset($oCon);


    if ($resolucion['Id_Resolucion']) {
        $oItem = new complex('Resolucion', 'Id_Resolucion', $resolucion['Id_Resolucion']);
        $nc = $oItem->getData();

        $oItem->Consecutivo = $oItem->Consecutivo + 1;
        $oItem->save();

        unset($oItem);

        $cod = $nc["Codigo"] . $nc["Consecutivo"];

        $datos['Codigo'] = $cod;
        $datos['Id_Resolucion'] = $resolucion['Id_Resolucion'];
        $datos['Tipo_Resolucion'] = $resolucion['Tipo_Resolucion'];
        return $datos;
    } else {

        return false;
    }
}
