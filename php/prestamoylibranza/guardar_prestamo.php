<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.configuracion.php');
include_once('../../class/contabilizaciones/ContabilidadPrestamo.php');
require('./funciones.php');

$modelo = (isset($_REQUEST['datos']) ? $_REQUEST['datos'] : false);

if($modelo){
    $datos = json_decode($modelo, true);
    if($datos['Tipo'] != 'Libranza'){
         if($datos['interesA'] == 'Prestamo'){ 
            $dataProyeccion = proyeccionAmortizacionAPrestamo($datos['Valor_Prestamo'],
                                                              $datos['Cuota_Mensual'],
                                                              $datos['Intereses'],
                                                              $datos["Tipo_Descuento"],
                                                              $datos["Fecha_Descuento"]);
        }else{
            $dataProyeccion = proyeccionAmortizacion($datos['Valor_Prestamo'],
                                                    $datos['Cuota_Mensual'],
                                                    $datos['Intereses'],
                                                    $datos["Tipo_Descuento"],
                                                    $datos["Fecha_Descuento"]);
        }
    }else{
        $dataProyeccion = proyeccionAmortizacionLibranza($datos['Valor_Prestamo'],
                                                        $datos['Cuota_Mensual'],
                                                        // $datos['Intereses'],
                                                        $datos["Tipo_Descuento"],
                                                        $datos["Fecha_Descuento"]);
        $dataProyeccion = proyeccionAmortizacionL($datos['Valor_Prestamo'],
                                                        $datos['Cuota_Mensual'],
                                                        // $datos['Intereses'],
                                                        $datos["Tipo_Descuento"],
                                                        $datos["Fecha_Descuento"]);
    }
    $nro_cuotas = $datos['Pago_Cuotas'] == 'No' ? '1' : $dataProyeccion['Total_Cuotas'];
    $saldo = $datos['Valor_Prestamo'];
    $datos['Nro_Cuotas'] = $nro_cuotas;
    $datos['Saldo'] = $saldo;

    if (array_key_exists('Id_Prestamo',$datos))  unset($datos['Id_Prestamo']);
    if ($datos['Id_Proceso_Disciplinario']  == '' )  unset($datos['Id_Proceso_Disciplinario']);
    
    $oItem = new complex("Prestamo","Id_Prestamo"); 
    foreach ($datos as $index => $value) {
        $oItem->$index = $value;
    }
    $oItem->save();
    $id_prestamo = $oItem->getId();
    guardarCuotasPrestamo($id_prestamo,$dataProyeccion['Proyeccion']);

    $oItem->Intereses = number_format($datos['Intereses'],2,".","");

    if($datos['Tipo'] == 'Prestamo'){

        $oItem->Id_Plan_Cuenta_Banco = $datos['Plan_Cuenta']['Id_Plan_Cuentas'];
    }

    $config = new Configuracion();
    $cod = $config->Consecutivo('Prestamo');
    $oItem->Codigo = $cod;
    unset($config);    

    $oItem->save();
    $id_prestamo = $oItem->getId();

    if($datos['Tipo'] == 'Prestamo'){
        
        $datos['Nit'] = $datos['Empleado']['Identificacion_Funcionario'];
        $datos['Id_Registro'] = $id_prestamo;
        $datos['Id_Plan_Cuenta_Banco'] = $datos['Plan_Cuenta']['Id_Plan_Cuentas'];
    
        // $con = new ContabilidadPrestamo();
        // $con->CrearMovimientoContable( 'Prestamo', $datos );
        unset($con);
    }

    $resultado['title']   = "Prestamo Guardado";
    $resultado['mensaje'] = "Se ha registrado el prestamo exitosamente.";
    $resultado['tipo']    = "success";
}
function guardarCuotasPrestamo($id_prestamo,$cuotas){
    foreach($cuotas as $cuota){
        $oItem = new complex('Prestamo_Cuota','Id_Prestamo_Cuota');
        foreach ($cuota as $index => $value) {
            $oItem->$index = $value;
        }
        $oItem->Id_Prestamo = $id_prestamo;
        $oItem->save();
        unset($oItem);
        $resultado['title']   = "Prestamo Guardado";
        $resultado['mensaje'] = "Se ha registrado el prestamo exitosamente.";
        $resultado['tipo']    = "success";
    }
}

echo json_encode($resultado);
