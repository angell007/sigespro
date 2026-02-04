<?php
include_once('class.querybasedatos.php');
include_once('helper.contabilizar_nota_credito_global_factura.php');
include_once('helper.contabilizar_nota_credito_global.php');


class ContabilizarPai
{

    private $queryObj;
    private $id_modulo;
    private $id_registro_modulo;
    private $numero_comprobante;
    private $nit;
    private $tipo_nit;
    private $save_fecha;
    private $datos_funcionario;
    private $centro_costo;
    private $fecha_movimiento;

    function __construct($save_fecha = false)
    {

        $this->queryObj = new QueryBaseDatos();
        $this->save_fecha = $save_fecha;
    }

    function __destruct()
    {

        $this->queryObj = null;
        unset($queryObj);
    }

    public function CrearMovimientoContable($tipo, $datos)
    {

        switch ($tipo) {


            case 'Parcial Acta Internacional':
                $this->GetIdModulo($tipo);
                $this->id_registro_modulo = $datos['Id_Registro'];
                $this->CrearMovimientosParcialActaInternacional($datos);
                break;


            default:
                //ENVIAR NOTIFICACION DE QUE SE ESCOGIO UNA OPCION ERRONEA
                break;
        }
    }



    private function GetTasaOrdenCompra($id_acta)
    {
        global $queryObj;

        $query = '
				SELECT
					OCI.Tasa_Dolar
				FROM Orden_Compra_Internacional OCI
				INNER JOIN Acta_Recepcion_Internacional ARI ON OCI.Id_Orden_Compra_Internacional = ARI.Id_Orden_Compra_Internacional
				WHERE
					ARI.Id_Acta_Recepcion_Internacional = ' . $id_acta;

        $this->queryObj->SetQuery($query);
        $tasa = $this->queryObj->ExecuteQuery('simple');
        return $tasa['Tasa_Dolar'];
    }

    /*FIN ACTA RECEPCION INTERNACIONAL*/

    /*PARCIAL ACTA RECEPCION INTERNACIONAL*/

    private function imprimir($data)
    {
        echo json_encode($data);
    }
    private function CrearMovimientosParcialActaInternacional($datos)
    {
      
        
      
         //$this->imprimir( [$this->BuscarInformacionParaMovimiento('proveedores')]);exit;
        //$this->imprimir($datos);exit;
        $tasa_orden = $this->GetTasaOrdenCompra($datos['Modelo']['Id_Acta_Recepcion_Internacional']);
        $nit_proveedor_orden = $this->GetNitProveedorExtranjero($datos['Modelo']['Id_Acta_Recepcion_Internacional']);
        $datos_acta = $this->GetDatosActaInternacional($datos['Modelo']['Id_Acta_Recepcion_Internacional']);
        $costos_productos = $this->CalcularCostosProductosParciales($datos['Productos']);
        $acta_data = $this->GetNitGastosActa($datos['Modelo']['Id_Acta_Recepcion_Internacional']);
        
        $calculoProveedores = $this->getTotalGravados($datos,$costos_productos);
        
        $productos_recalculados = $this->RecalculoProductosConTasaDeOrden($tasa_orden, $datos['Productos'], $datos['Porcentaje_Flete_Internacional'], $datos['Porcentaje_Seguro_Internacional'], $datos['Otros_Gastos']);
        $diferencia_cambio = $this->CalcularDiferenciaAlCambio($datos['Productos'], $productos_recalculados, $tasa_orden, $datos['Tasa_Dolar_Parcial']);
        $totales_parcial = $this->ObtenerTotalesRealesParcial($costos_productos, $datos['Modelo'], $datos['Otros_Gastos'], $diferencia_cambio);
        
        
     
         //$this->imprimir(  $this->getDiff($datos['Modelo']));exit;
        //GUARDAR GASTOS ADICIONADOS AL PARCIAL			
        $this->GuardarCostosObligatorios($datos['Modelo']);

 
    

        //GUARDAR TOTALES RECALCULADOS CON TASA DE LA ORDEN
        foreach ($diferencia_cambio as $key => $value) {

            if ($key == 'total_excento_recalculado') {
                if ($value != 0) {
                    $plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('gravado 0');
                    

                    $oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
                    $oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
                    $oItem->Id_Modulo = $this->id_modulo;
                    $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
                    $oItem->Debe = round(number_format($calculoProveedores['otrosGastosExcluido'], 2, ".", ""));
                    $oItem->Debe_Niif = round(number_format($calculoProveedores['otrosGastosExcluido'], 2, ".", ""));
                    $oItem->Haber = "0";
                    $oItem->Haber_Niif = "0";
                    $oItem->Nit = $nit_proveedor_orden;
                    $oItem->Tipo_Nit = 'Proveedor';
                    $oItem->Documento = $datos['Modelo']['Codigo'];
                    $oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
                    $oItem->Detalles = "Valor gravado 0 recalculado con tasa de orden";

                    if ($this->save_fecha)
                        $oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
                    $oItem->save();
                    unset($oItem);

                    $plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('productos excluidos');


//productosExcluidos
                    $oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
                    $oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
                    $oItem->Id_Modulo = $this->id_modulo;
                    $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
                    $oItem->Haber = round(number_format($calculoProveedores['productosExcluidos'], 2, ".", ""));
                    $oItem->Haber_Niif = round(number_format($calculoProveedores['productosExcluidos'], 2, ".", ""));
                    $oItem->Debe = "0";
                    $oItem->Debe_Niif = "0";
                    $oItem->Nit = $nit_proveedor_orden;
                    $oItem->Tipo_Nit = 'Proveedor';
                    $oItem->Documento = $datos['Modelo']['Codigo'];
                    $oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
                    $oItem->Detalles = "Valor productos excluidos recalculado con tasa de orden";
                    if ($this->save_fecha)
                        $oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
                    $oItem->save();
                    unset($oItem);
                }
            } elseif ($key == 'total_gravado_recalculado') {
                if ($value != 0) {

                    $plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('gravado 19');
                    
                    
                    //revisado
                    $oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
                    $oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
                    $oItem->Id_Modulo = $this->id_modulo;
                    $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
                    $oItem->Debe = round(number_format($calculoProveedores['otrosGastosGravado'], 2, ".", ""));
                    $oItem->Debe_Niif = round(number_format($calculoProveedores['otrosGastosGravado'], 2, ".", ""));
                    $oItem->Haber = "0";
                    $oItem->Haber_Niif = "0";
                    $oItem->Nit = $nit_proveedor_orden;
                    $oItem->Tipo_Nit = 'Proveedor';
                    $oItem->Documento = $datos['Modelo']['Codigo'];
                    $oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
                    $oItem->Detalles = "Valor gravado 19 recalculado con tasa de orden";

               
                  
                    //revisado
                    if ($this->save_fecha)
                        $oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
                    $oItem->save();
                    unset($oItem);

                    $plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('productos gravados');
                    
                   
  
                    $oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
                    $oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
                    $oItem->Id_Modulo = $this->id_modulo;
                    $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
                    $oItem->Haber = round(number_format($calculoProveedores['productosGravados'], 2, ".", ""));
                    $oItem->Haber_Niif = round(number_format($calculoProveedores['productosGravados'], 2, ".", ""));
                    $oItem->Debe = "0";
                    $oItem->Debe_Niif = "0";
                    $oItem->Nit = $nit_proveedor_orden;
                    $oItem->Tipo_Nit = 'Proveedor';
                    $oItem->Documento = $datos['Modelo']['Codigo'];
                    $oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
                    $oItem->Detalles = "Valor productos gravados recalculado con tasa de orden";
 
                    if ($this->save_fecha)
                        $oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
                    $oItem->save();
                    unset($oItem);
                }
            }
        }

     
     
        foreach ($totales_parcial as $key => $costo) {
            if ($key == 'excluidos') {
                if ($costo > 0) {
                    $plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('gravado 0');
                    
                
                    $oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
                    $oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
                    $oItem->Id_Modulo = $this->id_modulo;
                    $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
                    $oItem->Debe = round(number_format($calculoProveedores['subtotalExcluido'], 2, ".", ""));
                    $oItem->Debe_Niif = round(number_format($calculoProveedores['subtotalExcluido'], 2, ".", ""));
                    $oItem->Haber = "0";
                    $oItem->Haber_Niif = "0";
                    $oItem->Nit = $nit_proveedor_orden;
                    $oItem->Tipo_Nit = 'Proveedor';
                    $oItem->Documento = $datos['Modelo']['Codigo'];
                    $oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
                    $oItem->Detalles = "Valor gravado 0, acumulado de productos";

                    if ($this->save_fecha)
                        $oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
                     $oItem->save();
                    unset($oItem);
                }
            } elseif ($key == 'gravados') {
                
                if ($costo > 0) {
                    $plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('gravado 19');
                    
                     //no es
                     
                  
                    
                    
              
                    $oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
                    $oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
                    $oItem->Id_Modulo = $this->id_modulo;
                    $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
                    $oItem->Debe = round(number_format($calculoProveedores['subtotalGravado'], 2, ".", ""));
                    $oItem->Debe_Niif = round(number_format($calculoProveedores['subtotalGravado'], 2, ".", ""));
                    $oItem->Haber = "0";
                    $oItem->Haber_Niif = "0";
                    $oItem->Nit = $nit_proveedor_orden;
                    $oItem->Tipo_Nit = 'Proveedor';
                    $oItem->Documento = $datos['Modelo']['Codigo'];
                    $oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
                    $oItem->Detalles = "Valor gravado 19, acumulado de productos";

                    if ($this->save_fecha)
                        $oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
                    $oItem->save();
                    unset($oItem);
                }
            }
            
          
        }
         
       // $this->imprimir($costos_productos);exit;
        
        foreach ($costos_productos as $key => $costo) {
            if ($key == 'iva') {
                if ($costo > 0) {
                    $plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('iva nacionalizacion 19');
  
                    $oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
                    $oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
                    $oItem->Id_Modulo = $this->id_modulo;
                    $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
                    $oItem->Debe = round(number_format($costo, 2, ".", ""));
                    $oItem->Debe_Niif = round(number_format($costo, 2, ".", ""));
                    $oItem->Haber = "0";
                    $oItem->Haber_Niif = "0";
                    $oItem->Nit = $nit_proveedor_orden;
                    $oItem->Tipo_Nit = 'Proveedor';
                    $oItem->Documento = $datos['Modelo']['Codigo'];
                    $oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
                    $oItem->Detalles = "Valor iva nacionalizacion 19";
                    if ($this->save_fecha)
                        $oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
                    $oItem->save();
                    unset($oItem);

                    $plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('proveedores');
                 
                 
                    $oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
                    $oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
                    $oItem->Id_Modulo = $this->id_modulo;
                    $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
                    $oItem->Haber = round(number_format($costo, 2, ".", ""));
                    $oItem->Haber_Niif = round(number_format($costo, 2, ".", ""));
                    $oItem->Debe = "0";
                    $oItem->Debe_Niif = "0";
                    $oItem->Nit = 800197268;
                    $oItem->Tipo_Nit = 'Proveedor';
                    $oItem->Documento = $datos['Modelo']['Codigo'];
                    $oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
                    $oItem->Detalles = "Valor iva nacionalizacion 19, contraparte";
                    if ($this->save_fecha)
                        $oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
                    $oItem->save();
                    unset($oItem);
                }
            } elseif ($key == 'arancel') {
                if ($costo > 0) {
                    $plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('proveedores');
              
                    $oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
                    $oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
                    $oItem->Id_Modulo = $this->id_modulo;
                    $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
                    $oItem->Haber = round(number_format($costo, 2, ".", ""));
                    $oItem->Haber_Niif = round(number_format($costo, 2, ".", ""));
                    $oItem->Debe = "0";
                    $oItem->Debe_Niif = "0";
                    $oItem->Nit = 800197268;
                    $oItem->Tipo_Nit = 'Proveedor';
                    $oItem->Documento = $datos['Modelo']['Codigo'];
                    $oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
                    $oItem->Detalles = "Valor acumulado aranceles";
                    if ($this->save_fecha)
                        $oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
                    $oItem->save();
                    unset($oItem);
                }
            } elseif ($key == 'flete_internacional') {
                if ($costo > 0) {

                    $plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('proveedores');
 
                    $oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
                    $oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
                    $oItem->Id_Modulo = $this->id_modulo;
                    $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
                    $oItem->Haber = round(number_format($costo, 2, ".", ""));
                    $oItem->Haber_Niif = round(number_format($costo, 2, ".", ""));
                    $oItem->Debe = "0";
                    $oItem->Debe_Niif = "0";
                    $oItem->Nit = $acta_data['Tercero_Flete_Internacional'];
                    $oItem->Tipo_Nit = 'Proveedor';
                    $oItem->Documento = $datos['Modelo']['Codigo'];
                    $oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
                    $oItem->Detalles = "Valor acumulado flete internacional";
                    if ($this->save_fecha)
                        $oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
                    $oItem->save();
                    unset($oItem);
                }
            } elseif ($key == 'seguro_internacional') {
                if ($costo > 0) {

                    $plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('proveedores');

                    $oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
                    $oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
                    $oItem->Id_Modulo = $this->id_modulo;
                    $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
                    $oItem->Haber = round(number_format($costo, 2, ".", ""));
                    $oItem->Haber_Niif = round(number_format($costo, 2, ".", ""));
                    $oItem->Debe = "0";
                    $oItem->Debe_Niif = "0";
                    $oItem->Nit = $acta_data['Tercero_Seguro_Internacional'];
                    $oItem->Tipo_Nit = 'Proveedor';
                    $oItem->Documento = $datos['Modelo']['Codigo'];
                    $oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
                    $oItem->Detalles = "Valor acumulado seguro internacional";
                    if ($this->save_fecha)
                        $oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
                    $oItem->save();
                    unset($oItem);
                }
            } elseif ($key == 'flete_nacional') {
                if ($costo > 0) {

                    $plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('proveedores');

                    $oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
                    $oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
                    $oItem->Id_Modulo = $this->id_modulo;
                    $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
                    $oItem->Haber = round(number_format($costo, 2, ".", ""));
                    $oItem->Haber_Niif = round(number_format($costo, 2, ".", ""));
                    $oItem->Debe = "0";
                    $oItem->Debe_Niif = "0";
                    $oItem->Nit = $acta_data['Tercero_Flete_Nacional'];
                    $oItem->Tipo_Nit = 'Proveedor';
                    $oItem->Documento = $datos['Modelo']['Codigo'];
                    $oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
                    $oItem->Detalles = "Valor flete nacional";
                    if ($this->save_fecha)
                        $oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
                    $oItem->save();
                    unset($oItem);
                }
            } elseif ($key == 'licencia_importacion') {
                if ($costo > 0) {

                    $plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('proveedores');
  
                    $oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
                    $oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
                    $oItem->Id_Modulo = $this->id_modulo;
                    $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
                    $oItem->Haber = round(number_format($costo, 2, ".", ""));
                    $oItem->Haber_Niif = round(number_format($costo, 2, ".", ""));
                    $oItem->Debe = "0";
                    $oItem->Debe_Niif = "0";
                    $oItem->Nit = $acta_data['Tercero_Licencia_Importacion'];
                    $oItem->Tipo_Nit = 'Proveedor';
                    $oItem->Documento = $datos['Modelo']['Codigo'];
                    $oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
                    $oItem->Detalles = "Valor licencia importacion";
                    if ($this->save_fecha)
                        $oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
                     $oItem->save();
                    unset($oItem);
                }
            }
        }


        $plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('proveedores');
        //   $this->imprimir( $plan_cuenta_proveedores );exit;
        foreach ($datos['Otros_Gastos'] as $gasto) {
            if ($gasto['Monto_Gasto'] > 0) {
  
                $oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
                $oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
                $oItem->Id_Modulo = $this->id_modulo;
                $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
                $oItem->Haber = round(number_format(floatval($gasto['Monto_Gasto']), 2, ".", ""));
                $oItem->Haber_Niif = round(number_format(floatval($gasto['Monto_Gasto']), 2, ".", ""));
                $oItem->Debe = "0";
                $oItem->Debe_Niif = "0";
                $oItem->Nit = $gasto['Id_Proveedor'];
                $oItem->Tipo_Nit = 'Proveedor';
                $oItem->Documento = $datos['Modelo']['Codigo'];
                $oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
                $oItem->Detalles = "Valor otros gastos: " . $gasto['Concepto_Gasto'];
                if ($this->save_fecha)
                    $oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
                 $oItem->save();
                unset($oItem);
            }
        }
        
   
       
           if ($datos['Modelo']['Descuento_Parcial'] != '0') {

            $plan_cuenta_descuento = $this->BuscarInformacionParaMovimiento('descuento arancelario');

            $oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
            $oItem->Id_Plan_Cuenta = $plan_cuenta_descuento['Id_Plan_Cuenta'];
            $oItem->Id_Modulo = $this->id_modulo;
            $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
            $oItem->Haber = round(number_format($datos['Modelo']['Descuento_Parcial'], 2, ".", ""));
            $oItem->Haber_Niif = round(number_format($datos['Modelo']['Descuento_Parcial'], 2, ".", ""));
            $oItem->Debe = "0";
            $oItem->Debe_Niif = "0";
            $oItem->Nit = $nit_proveedor_orden;
            $oItem->Tipo_Nit = 'Proveedor';
            $oItem->Documento = $datos['Modelo']['Codigo'];
            $oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
            $oItem->Detalles = "Descuento arancelario";

            if ($this->save_fecha)
                $oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
            $oItem->save();
            unset($oItem);
/*
            $oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
            $oItem->Id_Plan_Cuenta = $plan_cuenta_descuento['Id_Plan_Cuenta'];
            $oItem->Id_Modulo = $this->id_modulo;
            $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
            $oItem->Debe = round(number_format($datos['Modelo']['Descuento_Parcial'], 2, ".", ""));
            $oItem->Debe_Niif = round(number_format($datos['Modelo']['Descuento_Parcial'], 2, ".", ""));
            $oItem->Haber = "0";
            $oItem->Haber_Niif = "0";
            $oItem->Nit = $nit_proveedor_orden;
            $oItem->Tipo_Nit = 'Proveedor';
            $oItem->Documento = $datos['Modelo']['Codigo'];
            $oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
            $oItem->Detalles = "Descuento arancelario";

            if ($this->save_fecha)
                $oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
            $oItem->save();
            unset($oItem);*/
        }


            $diff= $this->getDiff($datos['Modelo']);

           if($diff['diff'] > 0){
            
                  $plan_cuenta_descuento = $this->BuscarInformacionParaMovimiento('descuento arancelario');

                    $oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
                    $oItem->Id_Plan_Cuenta = $plan_cuenta_descuento['Id_Plan_Cuenta'];
                    $oItem->Id_Modulo = $this->id_modulo;
                    $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
                    $oItem->Haber = "0";
                    $oItem->Haber_Niif = "0";
                    $oItem->Debe =(number_format($diff['diff'], 2, ".", ""));
                    $oItem->Debe_Niif = (number_format($diff['diff_niff'], 2, ".", ""));
                    $oItem->Nit = $nit_proveedor_orden;
                    $oItem->Tipo_Nit = 'Proveedor';
                    $oItem->Documento = $datos['Modelo']['Codigo'];
                    $oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
                    $oItem->Detalles = "Descuento arancelario";

            if ($this->save_fecha)
                $oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
            $oItem->save();
           }
        //GUARDAR DIFERENCIA AL CAMBIO
        $plan_cuenta = $this->BuscarInformacionParaMovimiento($diferencia_cambio['cuenta']);

        $oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
        $oItem->Id_Plan_Cuenta = $plan_cuenta['Id_Plan_Cuenta'];
        $oItem->Id_Modulo = $this->id_modulo;
        $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
        if ($diferencia_cambio['cuenta'] == 'diferencia ingreso') {
            $oItem->Debe = "0";
            $oItem->Debe_Niif = "0";
            $oItem->Haber = round(number_format(abs($diferencia_cambio['diferencia']), 2, ".", ""));
            $oItem->Haber_Niif = round(number_format(abs($diferencia_cambio['diferencia']), 2, ".", ""));
        } else {
            $oItem->Haber = "0";
            $oItem->Haber_Niif = "0";
            $oItem->Debe = round(number_format(abs($diferencia_cambio['diferencia']), 2, ".", ""));
            $oItem->Debe_Niif = round(number_format(abs($diferencia_cambio['diferencia']), 2, ".", ""));
        }
        $oItem->Nit = 804016084;
        $oItem->Tipo_Nit = 'Empresa';
        $oItem->Documento = $datos['Modelo']['Codigo'];
        $oItem->Numero_Comprobante = $datos['Modelo']['Codigo'];
        $oItem->Detalles = "Valor diferencia al cambio";
        if ($this->save_fecha)
            $oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
            
            //NO SE CONTABILIZA, SE HACE MANUAL BY ANA
        // $oItem->save();
        unset($oItem);
    }

private function getDiff($modelo){
    
        $query = '
			SELECT ( SUM(Haber) -SUM(Debe) ) diff , ( SUM(Haber_Niif ) -SUM(Debe_Niif) ) diff_niff FROM Movimiento_Contable Where Numero_Comprobante="'.$modelo['Codigo'].'" AND Estado="Activo"';

        $this->queryObj->SetQuery($query);
        $acta_data = $this->queryObj->ExecuteQuery('simple');
        
        return $acta_data;
}
    private function GetNitGastosActa($id_acta)
    {

        $query = '
				SELECT
					Tercero_Flete_Internacional,
					Tercero_Seguro_Internacional,
					Tercero_Flete_Nacional,
					Tercero_Licencia_Importacion
				FROM Acta_Recepcion_Internacional
				WHERE
					Id_Acta_Recepcion_Internacional = ' . $id_acta;

        $this->queryObj->SetQuery($query);
        $acta_data = $this->queryObj->ExecuteQuery('simple');
        return $acta_data;
    }


    private function CalcularCostosProductosParciales($productos)
    {
        $gastos_productos_excluidos = 0;
        $gastos_productos_gravados = 0;
        $gastos_iva = 0;
        $gastos_aranceles = 0;
        $gastos_flete_internacional = 0;
        $gastos_seguro_internacional = 0;
        $gastos_flete_nacional = 0;
        $gastos_licencia_importacion = 0;
        $gastos_productos = array('excluidos' => 0, 'gravados' => 0, 'iva' => 0, 'arancel' => 0, 'flete_internacional' => 0, 'seguro_internacional' => 0,  'flete_nacional' => 0, 'licencia_importacion' => 0,);

        foreach ($productos as $p) {

            if ($p['Gravado'] == '0') {
                $gastos_productos_excluidos += floatval($p['Subtotal_Final']);
            } else {
                $gastos_productos_gravados += floatval($p['Subtotal_Final']);
                $gastos_iva += floatval($p['Total_Iva']);
            }

            $gastos_aranceles += floatval($p['Total_Arancel']);
            $gastos_flete_internacional += floatval($p['Total_Flete']);
            $gastos_seguro_internacional += floatval($p['Total_Seguro']);
            $gastos_flete_nacional += floatval($p['Total_Flete_Nacional']);
            $gastos_licencia_importacion += floatval($p['Total_Licencia']);
        }

        $gastos_productos['excluidos'] = $gastos_productos_excluidos;
        $gastos_productos['gravados'] = $gastos_productos_gravados;
        $gastos_productos['iva'] = $gastos_iva;
        $gastos_productos['arancel'] = $gastos_aranceles;
        $gastos_productos['flete_nacional'] = $gastos_flete_nacional;
        $gastos_productos['licencia_importacion'] = $gastos_licencia_importacion;
        $gastos_productos['flete_internacional'] = $gastos_flete_internacional;
        $gastos_productos['seguro_internacional'] = $gastos_seguro_internacional;

        return $gastos_productos;
    }

    private function GuardarCostosObligatorios($modelo)
    {

        $plan_cuenta_proveedores = $this->BuscarInformacionParaMovimiento('proveedores');
         //  $this->imprimir( $modelo );exit;
        foreach ($modelo as  $key => $value) {

            if ($key == 'Tramite_Sia' || $key == 'Formulario' || $key == 'Cargue' ||  $key == 'Gasto_Bancario') {

                $oItem = new complex("Movimiento_Contable", "Id_Movimiento_Contable");
                $oItem->Id_Plan_Cuenta = $plan_cuenta_proveedores['Id_Plan_Cuenta'];
                $oItem->Id_Modulo = $this->id_modulo;
                $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
                $oItem->Haber = round(number_format($value, 2, ".", ""));
                $oItem->Haber_Niif = round(number_format($value, 2, ".", ""));
                $oItem->Debe = "0";
                $oItem->Debe_Niif = "0";
                $oItem->Nit = $modelo['Tercero_' . $key];
                $oItem->Tipo_Nit = 'Proveedor';
                $oItem->Documento = $modelo['Codigo'];
                $oItem->Numero_Comprobante = $modelo['Codigo'];
                $oItem->Detalles = "Valor " . str_replace("_", " ", $key);
                if ($this->save_fecha)
                    $oItem->Fecha_Movimiento = $this->GetFechaMovimiento($this->id_registro_modulo, 'Nacionalizacion_Parcial');
                $oItem->save();
                unset($oItem);
            }
        }
    }



    private function GetNitProveedorExtranjero($id_acta)
    {
        global $queryObj;

        $query = '
				SELECT
					OCI.Id_Proveedor
				FROM Orden_Compra_Internacional OCI
				INNER JOIN Acta_Recepcion_Internacional ARI ON OCI.Id_Orden_Compra_Internacional = ARI.Id_Orden_Compra_Internacional
				WHERE
					ARI.Id_Acta_Recepcion_Internacional = ' . $id_acta;

        $this->queryObj->SetQuery($query);
        $proveedor = $this->queryObj->ExecuteQuery('simple');
        return $proveedor['Id_Proveedor'];
    }

    private function GetDatosActaInternacional($id_acta)
    {
        global $queryObj;

        $query = '
				SELECT
					R.Gravado,
				    SUM(R.Subtotal * (SELECT Tasa_Dolar 
                                      FROM Orden_Compra_Internacional OC 
                                      INNER JOIN Acta_Recepcion_Internacional AR ON OC.Id_Orden_Compra_Internacional = AR.Id_Orden_Compra_Internacional 
                                      WHERE Id_Acta_Recepcion_Internacional = ' . $id_acta . ')) AS Total
				FROM (SELECT
				        P.Id_Producto,
				        PARI.Subtotal,
				        P.Gravado
				    FROM Producto_Acta_Recepcion_Internacional PARI
				    INNER JOIN Producto P ON PARI.Id_Producto = P.Id_Producto
				    WHERE
				        PARI.Id_Acta_Recepcion_Internacional = ' . $id_acta . ') R
				GROUP BY R.Gravado';

        $this->queryObj->SetQuery($query);
        $datos_acta = $this->queryObj->ExecuteQuery('multiple');
        return $datos_acta;
    }

    private function RecalculoProductosConTasaDeOrden($tasa_orden, $productos, $flete, $seguro, $otros_gastos)
    {
        $nuevos_productos = array();
        $adicional_otros_gastos = $this->GetAdicionalOtrosGastos($otros_gastos, $productos[0]['Id_Nacionalizacion_Parcial']);

        $i = 0;
        foreach ($productos as $p) {

            $p_nuevo = array();

            $p_nuevo['Precio_Unitario'] = $this->ConversionPrecioDolarAPesos(floatval($p['Precio']), $tasa_orden);
            $p_nuevo['FOT_Pesos'] = $this->CalcularFotPesos($p_nuevo['Precio_Unitario'], $flete, $seguro);
            $p_nuevo['Precio_Unitario_Final'] = $this->CalcularPrecioUnitarioFinal($p_nuevo['FOT_Pesos'], floatval(trim($p['Porcentaje_Arancel'], " %")));
            $p_nuevo['Cantidad'] = $p['Cantidad'];
            $p_nuevo['Id_Producto'] = $p['Id_Producto'];
            $p_nuevo['Nombre_Producto'] = $p['Nombre_Producto'];
            $p_nuevo['Lote'] = $p['Lote'];
            $p_nuevo['Porcentaje_Arancel'] = $p['Porcentaje_Arancel'];
            $p_nuevo['Gravado'] = $p['Gravado'];
            $p_nuevo['Subtotal'] = ($p_nuevo['Precio_Unitario_Final'] * $p['Cantidad']) + (($p_nuevo['Precio_Unitario_Final'] * ($p['Gravado'] / 100)) * $p['Cantidad']);
            $p_nuevo['Subtotal_Final'] = $p_nuevo['Subtotal_Final'] + ($p['Adicional_Flete_Nacional'] * $p['Cantidad']) + ($p['Adicional_Licencia_Importacion'] * $p['Cantidad']);
            $p_nuevo['Subtotal_Final'] = $p_nuevo['Subtotal_Final'] + ($p['Cantidad'] * $adicional_otros_gastos);

            array_push($nuevos_productos, $p_nuevo);

            $i++;
        }

        return $nuevos_productos;
    }

    private function ObtenerTotalesRealesParcial($totalesGastos, $gastosVariosParcial, $otrosGastos, $valoresDiferencia)
    {
        $total_excluido = 0;
        $total_gravado = 0;
        $totales_parcial = array('excluidos' => 0, 'gravados' => 0);
        $show = [];
        $grabados=[];
        $otrosGastosgrabados = [];
      
        $gastosVariosGrabados =[];
        
      $totalGastos = 0;
          $gastosVariosParcial = 0;
          
          
        foreach ($totalesGastos as $key => $value) {
            if (($key != "excluidos" && $key != "gravados" && $key != "iva") && $value != 0) {
                $total_excluido += $value * $valoresDiferencia['porcentaje_excluido'];
                $total_gravado += $value * $valoresDiferencia['porcentaje_gravado'];
                $grabados[$key]['valor']=$value;
                 $totalGastos+=$value;
                 $grabados[$key]['procentajeGravado']=$valoresDiferencia['porcentaje_gravado'];
                   $grabados[$key]['total']= $value * $valoresDiferencia['porcentaje_gravado'];
            }
        }
        
        
//$this->imprimir(['totalesGastos'=>$totalesGastos,'otrosGastos'=>$otrosGastos,'$gastosVariosParcial'=>$gastosVariosParcial, $valoresDiferencia['porcentaje_gravado']]);exit;
        foreach ($otrosGastos as $gasto) {
            if ($gasto['Monto_Gasto'] != 0) {
                $total_excluido += $gasto['Monto_Gasto'] * $valoresDiferencia['porcentaje_excluido'];
                $total_gravado += $gasto['Monto_Gasto'] * $valoresDiferencia['porcentaje_gravado'];
                
                
                $otrosGastosgrabados[$key]['valor']=$gasto['Monto_Gasto'];
                 $otrosGastosgrabados[$key]['procentajeGravado']=$valoresDiferencia['porcentaje_gravado'];
                   $otrosGastosgrabados[$key]['total']= $gasto['Monto_Gasto'] * $valoresDiferencia['porcentaje_gravado'];
            }
        }
        
       
        foreach ($gastosVariosParcial as  $key => $value) {
            if (($key == 'Tramite_Sia' || $key == 'Formulario' || $key == 'Cargue' ||  $key == 'Gasto_Bancario') && $value != 0) {

                $total_excluido += $value * $valoresDiferencia['porcentaje_excluido'];
                $total_gravado += $value * $valoresDiferencia['porcentaje_gravado'];
             
                $gastosVariosParcial+=$value * $valoresDiferencia['porcentaje_gravado'];
                 $gastosVariosGrabados[$key]['valor']= $value;
                 $gastosVariosGrabados[$key]['procentajeGravado']=$valoresDiferencia['porcentaje_gravado'];
                   $gastosVariosGrabados[$key]['total']=  $value * $valoresDiferencia['porcentaje_gravado'];
            }
        }
        
      


        $total_excluido += abs($valoresDiferencia['diferencia']) * $valoresDiferencia['porcentaje_excluido'];
        $total_gravado += abs($valoresDiferencia['diferencia']) * $valoresDiferencia['porcentaje_gravado'];

        $totales_parcial['excluidos'] = $total_excluido;
        $totales_parcial['gravados'] = $total_gravado;
   /*$this->imprimir([
       '$totalGastos'=>$totalGastos,
       ' $gastosVariosParcial'=> $gastosVariosParcial,
       
       'totalesGastos'=>$grabados,
       'OtrosGastos'=>$otrosGastosgrabados,
       
       '$gastosVariosGrabados'=>$gastosVariosGrabados, 
       
   'diferencia * porcentaje'=>abs($valoresDiferencia['diferencia']) * $valoresDiferencia['porcentaje_gravado'],
   'totalFinal'=>$totales_parcial['gravados'] ,
   
   ]);exit;*/
        return $totales_parcial;
    }

    private function GetConteoProductosParcial($id_parcial)
    {

        $query = '
	            SELECT 
	                SUM(Cantidad) AS Total
	            FROM Producto_Nacionalizacion_Parcial
	            WHERE
	                Id_Nacionalizacion_Parcial = ' . $id_parcial;

        $this->queryObj->SetQuery($query);
        $conteo = $this->queryObj->ExecuteQuery('simple');

        if ($conteo['Total']) {
            return floatval($conteo['Total']);
        } else {
            return 0;
        }
    }

    private function GetAdicionalOtrosGastos($gastos, $id_parcial)
    {
        $adicional_final = 0;
        $total_cantidad_productos = $this->GetConteoProductosParcial($id_parcial);

        foreach ($gastos as $gasto) {

            $monto = floatval($gasto['Monto_Gasto']);
            $adicional_gasto = $monto / $total_cantidad_productos;
            $adicional_final += $adicional_gasto;
        }

        return $adicional_final;
    }

    private function ConversionPrecioDolarAPesos($precio, $tasa)
    {
        $conversion = $precio * $tasa;
        return $conversion;
    }

    private function CalcularFotPesos($precio_unitario, $flete, $seguro)
    {
        $valor_flete = $precio_unitario * $flete;
        $valor_seguro = $precio_unitario * $seguro;
        $fot = $precio_unitario + $valor_flete + $valor_seguro;
        return $fot;
    }

    private function CalcularPrecioUnitarioFinal($fot_pesos, $arancel)
    {
        $valor_arancel = $fot_pesos * ($arancel / 100);
        $puf = $fot_pesos + $valor_arancel;
        return $puf;
    }

    private function CalcularDiferenciaAlCambio($productos, $productos_recalculados, $tasa_orden, $tasa_parcial)
    {
        $total = 0;
        $total_recalculados = 0;
        $diferencia = 0;
        $total_gravado_recalculado = 0;
        $total_excento_recalculado = 0;
        $result = array('diferencia' => 0, 'cuenta' => '', 'total_excento_recalculado' => 0, 'total_gravado_recalculado' => 0, 'porcentaje_excluido' => 0, 'porcentaje_gravado' => 0);

        foreach ($productos_recalculados as $p) {
            $total += $p['Subtotal_Final'];
        }

        foreach ($productos_recalculados as $pr) {
            if ($pr['Gravado'] == '0') {
                $total_excento_recalculado += $pr['Subtotal'];
            } else {
                $total_gravado_recalculado += $pr['Subtotal'];
            }

            $total_recalculados += $pr['Subtotal'];
        }

        $result['porcentaje_excluido'] = (($total_excento_recalculado * 100) / $total_recalculados) / 100;
        $result['porcentaje_gravado'] = (($total_gravado_recalculado * 100) / $total_recalculados) / 100;

        if (floatval($tasa_orden) < floatval($tasa_parcial)) {
            $result['cuenta'] = 'diferencia ingreso';
        } elseif (floatval($tasa_orden) > floatval($tasa_parcial)) {
            $result['cuenta'] = 'diferencia gasto';
        }

        $result['diferencia'] = $diferencia = $total_recalculados - $total;
        $result['total_excento_recalculado'] = $total_excento_recalculado;
        $result['total_gravado_recalculado'] = $total_gravado_recalculado;
        return $result;
    }






    private function BuscarInformacionParaMovimiento($flag, $tipo = '', $debug = false)
    {

        $query = '';

        if ($tipo == 'facturas') {

            $query = '
				SELECT
					*
				FROM Asociacion_Plan_Cuentas
				WHERE
					Busqueda_Interna = "' . $flag . '"';
            #echo $query;

        } elseif ($tipo == '') {

            $query = '
					SELECT
						*
					FROM Asociacion_Plan_Cuentas
					WHERE
						Busqueda_Interna = "' . $flag . '" AND Id_Modulo = ' . $this->id_modulo;
            #	echo $query;
        }

        $this->queryObj->SetQuery($query);
        $result = $this->queryObj->ExecuteQuery('simple');

        return $result;
    }


    private function GetIdModulo($modulo)
    {

        $query = '
				SELECT
					Id_Modulo
				FROM Modulo
				WHERE
					LOWER(Nombre) = "' . strtolower($modulo) . '"';

        $this->queryObj->SetQuery($query);
        $result = $this->queryObj->ExecuteQuery('simple');

        $this->id_modulo = $result != false ? $result['Id_Modulo'] : 'Error Modulo';
    }


    private function GetFechaMovimiento($id, $tabla)
    {
        $oItem = new complex($tabla, "Id_$tabla", $id);

        if ($tabla == 'Factura_Venta' || $tabla == 'Factura' || $tabla == 'Factura_Capita') {
            $fecha = $oItem->Fecha_Documento;
        } elseif ($tabla == 'Comprobante') {
            $fecha = $oItem->Fecha_Comprobante;
        } elseif ($tabla == 'Nota_Credito' || $tabla == 'Ajuste_Individual' || $tabla == 'Nomina') {
            $fecha = $oItem->Fecha;
        } elseif ($tabla == 'Inventario_Fisico' || $tabla == 'Inventario_Fisico_Punto') {
            $fecha = $oItem->Fecha_Fin;
        } elseif ($tabla == 'Nacionalizacion_Parcial') {
            $fecha = $oItem->Fecha_Registro;
        } elseif ($tabla == 'Acta_Recepcion' || $tabla == 'Acta_Recepcion_Internacional')
            $fecha = $oItem->Fecha_Creacion;

        unset($oItem);

        return $fecha;
    }


    //Helpers

    private function getTotalGravados($datos,$totalesGastos){
    $otrosGastosGravados=0;
   // $datos['Modelo'];
    
    if($totalesGastos['flete_nacional']){
        $otrosGastosGravados+=$totalesGastos['flete_nacional'];
    }
    
    if($totalesGastos['licencia_importacion']){
        $otrosGastosGravados+=$totalesGastos['licencia_importacion'];
    }
    
    
     if($datos['Modelo']['Tramite_Sia']){
        $otrosGastosGravados+=$datos['Modelo']['Tramite_Sia'];
    }
    
    if($datos['Modelo']['Formulario']){
        $otrosGastosGravados+=$datos['Modelo']['Formulario'];
    }
    
    //subtotal de todos los productos
    $subtotalFull = 0;
     foreach($datos['Productos']  as $producto){
         $subtotalFull+= $producto['Subtotal'];       
     
    }
    
    /*
    $this->imprimir([$otrosGastosGravados,$totalesGastos]);exit;
        foreach ($totalesGastos as $key => $value) {
            if (($key != "excluidos" && $key != "gravados" && $key != "iva") && $value != 0) {
                $total_excluido += $value * $valoresDiferencia['porcentaje_excluido'];
                $total_gravado += $value * $valoresDiferencia['porcentaje_gravado'];
                $grabados[$key]['valor']=$value;
                 $totalGastos+=$value;
                 $grabados[$key]['procentajeGravado']=$valoresDiferencia['porcentaje_gravado'];
                   $grabados[$key]['total']= $value * $valoresDiferencia['porcentaje_gravado'];
            }
        }
        */
        
        $calculo=['subtotalGravado'=>0,
        'subtotalExcluido'=>0,
        
        'otrosGastosGravado'=>0,
        'otrosGastosExcluido'=>0,
        
        'productosGravados'=>0,
         'productosExcluidos'=>0,
        ];
        
            foreach($datos['Productos']  as $producto){
               
                if($producto['Gravado']!='0'){
                    $calculo['subtotalGravado'] += $producto['Subtotal'];
                    $calculo['otrosGastosGravado'] += ($otrosGastosGravados * $producto['Subtotal']) / $subtotalFull;
                    $calculo['productosGravados'] += $producto['Precio_Unitario_Pesos'] * $producto['Cantidad'];
                }
                
                 if($producto['Gravado']=='0'){
                    $calculo['subtotalExcluido'] += $producto['Subtotal'];
                    $calculo['otrosGastosExcluido'] += ($otrosGastosGravados * $producto['Subtotal']) / $subtotalFull;
                    
                    $calculo['productosExcluidos'] += $producto['Precio_Unitario_Pesos'] * $producto['Cantidad'];
                   // $data['subtotal'] += $producto['Subtotal'];
                }
                
                //Precio_Unitario_Pesos Cantidad
                
            }
       
              //$this->imprimir($datos);exit;
             //$this->imprimir([$subtotalFull,$calculo,$otrosGastosGravados]);exit;
             
             return $calculo;
    }
}
