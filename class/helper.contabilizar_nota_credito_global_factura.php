<?php 
/*	include_once('class.querybasedatos.php');
    include_once('class.consulta.php');
	include_once('class.querybasedatos.php');
	*/
    $queryObj = new QueryBaseDatos();
   
        function getNotaCreditoGlobal($id_nota){

            $query = 'SELECT * FROM Nota_Credito_Global WHERE Id_Nota_Credito_Global = '.$id_nota;
            $oCon = new consulta();
            $oCon->setQuery($query);
            return  $oCon->getData();

        }

        function getProductosNotaCreditoGlobal($id_nota,$tipo_factura){

            $query = 'SELECT NT.Id_Factura, PN.Precio_Nota_Credito, PN.Cantidad AS Cantidad_Nota  ,PN.Impuesto AS Impuesto_Nota, 
                            PN.Id_Producto,PN.Id_Producto_Nota_Credito_Global, PN.Id_Nota_Credito_Global,
                            PF.Precio AS Precio_Factura, PF.Cantidad AS Cantidad_Factura,  PD.Costo ,
                            (PF.Subtotal - (PF.Cantidad*PF.Descuento) ) AS  Subtotal_Con_Descuento_Factura,
							( PN.Cantidad * PN.Precio_Nota_Credito ) AS  Subtotal_Con_Descuento
							
                            FROM Producto_Nota_Credito_Global PN

                            INNER JOIN Nota_Credito_Global NT ON NT.Id_Nota_Credito_Global = PN.Id_Nota_Credito_Global
                            INNER JOIN Producto_Factura PF ON PF.Id_Producto_Factura = PN.Id_Producto
                            INNER JOIN Producto_Dispensacion PD ON PD.Id_Producto_Dispensacion = PF.Id_Producto_Dispensacion
                            WHERE PN.Id_Nota_Credito_Global = '.$id_nota
                          ;

            $oCon = new consulta();
            $oCon->setQuery($query);
          	$oCon->setTipo('Multiple');
            $productos = $oCon->getData();
   
            $costoGeneral = 0;
            foreach ($productos as $key => $producto) {

                $subtotalProductoNota = $producto['Subtotal_Con_Descuento'];
				$subtotalProductoFactura = $producto['Subtotal_Con_Descuento_Factura'] ;

                if ( $subtotalProductoNota == $subtotalProductoFactura ) {
        
                    $costoGeneral += (float)$producto['Costo'];
                    $productos[$key]['Guardar_Costo']=true;
                 
                }else {
                    # Busco todas las notas hechas  a ese producto de la factura
           
                    $query = 'SELECT PN.Precio_Nota_Credito, PN.Cantidad AS Cantidad_Nota, PN.Impuesto,
                            PN.Id_Producto,PN.Id_Producto_Nota_Credito_Global, PN.Id_Nota_Credito_Global
                        
                            FROM Producto_Nota_Credito_Global PN
                            INNER JOIN Nota_Credito_Global NT ON NT.Id_Nota_Credito_Global = PN.Id_Nota_Credito_Global
                        
                            WHERE NT.Id_Nota_Credito_Global != '.$producto['Id_Nota_Credito_Global'] .' AND
                                PN.Id_Producto = '.$producto['Id_Producto'] .' AND
								NT.Id_Factura = '.$producto['Id_Factura'] .' AND
								NT.Tipo_Factura = "'.$tipo_factura.'" ';
                     
                        $oCon = new consulta();
                        $oCon->setQuery($query);
                        $oCon->setTipo('Multiple');
                        $productosEnNotas = $oCon->getData();
                     
                        foreach ( $productosEnNotas as $key => $productoEnNota ) {
                           
                            $subtotalProductoNota += ( $productoEnNota['Precio_Nota_Credito'] *  $productoEnNota['Cantidad_Nota'] ) ;
                           
                        }
                    
                        if ( $subtotalProductoNota == $subtotalProductoFactura ) {
                            $costoGeneral += $producto['Costo'];
							$productos[$key]['Guardar_Costo']=true;
							
                        }else{
                            $productos[$key]['Guardar_Costo']=false;
                        }
                   
                }
            }

            return $productos;
        }

         function GetTipoFacturaNotaCreditoGlobal($idnota){
			$query = 'SELECT
					F.*,    TS.Id_Tipo_Servicio,
					TS.Nombre AS Tipo_Dispensacion,
					IFNULL(TS.Nombre, "No aplica") AS Tipo_Servicio,
					D.Id_Punto_Dispensacion,
					P.Id_Regimen
				FROM
				Nota_Credito_Global NT
				
				INNER JOIN  Factura F ON
				F.Id_Factura =   NT.Id_Factura
				INNER JOIN Dispensacion D ON
					F.Id_Dispensacion = D.Id_Dispensacion
				INNER JOIN Paciente P ON 
				P.Id_Paciente = D.Numero_Documento
				LEFT JOIN Tipo_Servicio TS ON
					D.Id_Tipo_Servicio = TS.Id_Tipo_Servicio
				WHERE
					NT.Id_Nota_Credito_Global = '.$idnota.' LIMIT 1';
			$oCon = new consulta();

            $oCon->setQuery($query);
			
			return  $oCon->getData();
        }
        

        function CalcularCostosProductosPorImpuestoNota($productos){

			$costo_por_impuesto = array();

			foreach ($productos as $value) {
                if($value['Guardar_Costo']){
                    $costo_producto = floatval($value['Costo']) * intval($value['Cantidad_Nota']);
			
                  	$imp = $value['Impuesto_Nota'];
                     
                    if (!isset($costo_por_impuesto[$imp])) {
                        $costo_por_impuesto[$imp] = $costo_producto;
                    }else{
                        $costo_por_impuesto[$imp] += $costo_producto;
                    }
                }
				
			}
			
			return $costo_por_impuesto;
		}
        function GetTotalesNotaCreditoGloblal($productos,$cuota_moderadora){
			$total_general_facturas = 0;
			$ivas_factura = array();
			$result = array();

			foreach ($productos as $p) {
				
				$total_general_facturas += floatval($p['Subtotal_Con_Descuento']);

				if (floatval($p['Impuesto_Nota']) > 0) {

					$total_iva_producto  = floatval($p['Subtotal_Con_Descuento']) * (floatval($p['Impuesto_Nota'])/100);
					$total_general_facturas += $total_iva_producto;

                  
                  	$imp =(int)$p['Impuesto_Nota'];
                     
                    
                  
					if (!isset($ivas_factura[$imp])) {
						$ivas_factura[$imp] = $total_iva_producto;
					}else{
						$ivas_factura[$imp] += $total_iva_producto;
					}					
				}					
			}

			$total_general_facturas -= $cuota_moderadora;

			$result['Total_General'] = $total_general_facturas;
			$result['Ivas'] = $ivas_factura;

			return $result;
        }
   
		
		 function TotalIvaProductosNota($productos, $tipo_calculo, $nroFactura = '', $id_modulo,
		 								 $id_registro_modulo, $nit,$tipo_nit,$centro_costo,$save_fecha,$id_modulo_nota){
	
			$total_productos_iva_19 = 0;
			$total_productos_iva_5 = 0;
			$total_productos_iva_0 = 0;

			$facturas_iva_19 = '';
			$facturas_iva_5 = '';
			$facturas_iva_0 = '';
			
			$gravados_cargar = array();
		
			if ($tipo_calculo == 'factura venta') {
				
				foreach ($productos as $p) {
					
					if ((int)$p['Impuesto_Nota'] == 19 ) {
					
						$iva_producto = floatval($p['Cantidad_Nota']) * floatval($p['Precio_Nota_Credito']);
						$total_productos_iva_19 += $iva_producto;
						
						//$facturas_iva_19 .= $p['Factura'].", ";
						if (!in_array('19',$gravados_cargar)) {
							array_push($gravados_cargar, '19');
						}
						
					}
					
					if ( (int)$p['Impuesto_Nota'] == 5 ) {
					
						$iva_producto = floatval($p['Cantidad_Nota']) * floatval($p['Precio_Nota_Credito']);
						$total_productos_iva_5 += $iva_producto;
						
						//$facturas_iva_5 .= $p['Factura'].", ";
						if (!in_array('5',$gravados_cargar)) {
							array_push($gravados_cargar, '5');
						}
					}

					if ( (int)$p['Impuesto_Nota'] == 0 ) {
						
						$iva_producto = floatval($p['Cantidad_Nota']) * floatval($p['Precio_Nota_Credito']);
						$total_productos_iva_0 += $iva_producto;
						
						//$facturas_iva_0 .= $p['Factura'].", ";
						if (!in_array('0',$gravados_cargar)) {
							array_push($gravados_cargar, '0');
						}
					}					
				}
			
				foreach ($gravados_cargar as $value) {
					$busqueda = '';
					$total_cargar = 0;
					$facturas_cargar = '';

					if ($value == '0') {
						$busqueda = 'contraparte gravado 0';
						$total_cargar = $total_productos_iva_0;
						//$facturas_cargar = $facturas_iva_0;

					}elseif ($value == '5') {
						
						$busqueda = 'contraparte gravado 5';
						$total_cargar = $total_productos_iva_5;
					//	$facturas_cargar = $facturas_iva_5;

					}elseif ($value == '19') {
						
						$busqueda = 'contraparte gravado 19';
						$total_cargar = $total_productos_iva_19;
						//$facturas_cargar = $facturas_iva_19;
					}

					$asociacion = BuscarInformacionParaMovimiento($busqueda,'',$id_modulo_nota);
	
					//GUARDAR EL MOVIMIENTO CONTABLE
					$oItem = new complex("Movimiento_Contable","Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $id_modulo_nota;
					$oItem->Id_Registro_Modulo = $id_registro_modulo;
	
					$oItem->Debe =number_format($total_cargar,2,".","");
					$oItem->Debe_Niif = number_format($total_cargar,2,".","");
					$oItem->Haber = "0";
					$oItem->Haber_Niif = "0";

					$oItem->Nit = $nit;
					$oItem->Tipo_Nit = $tipo_nit;
					$oItem->Documento = $nroFactura;
					$oItem->Numero_Comprobante = $nroFactura;
					if (isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
						$oItem->Id_Centro_Costo = $centro_costo;
					
					if ($save_fecha)
						$oItem->Fecha_Movimiento = GetFechaMovimiento($id_registro_modulo, 'Factura_Venta');
					$oItem->save();
					unset($oItem);
				}
			}
		
			if ($tipo_calculo == 'Nota_Credito_Global') {
			
				$gravados_cargar = CalcularTotalesGravadosProductos($productos);
			
				foreach ($gravados_cargar as $key => $value) {
					$busqueda = '';
					$total_cargar = $value;
					$facturas_cargar = $nroFactura;

					if ( $key == '0' ) {
						$busqueda = 'gravado 0';

					}elseif ( $key == '5' ) {
						
						$busqueda = 'gravado 5';

					}elseif ( $key == '19' ) {
						
						$busqueda = 'gravado 19';
					}
					$asociacion = BuscarInformacionParaMovimiento( $busqueda, '', $id_modulo_nota );
				
					//GUARDAR EL MOVIMIENTO CONTABLE
					$oItem = new complex("Movimiento_Contable","Id_Movimiento_Contable");
					$oItem->Id_Plan_Cuenta = $asociacion['Id_Plan_Cuenta'];
					$oItem->Id_Modulo = $id_modulo_nota;
					$oItem->Id_Registro_Modulo = $id_registro_modulo;

					$oItem->Debe = number_format($total_cargar,2,".","");
					$oItem->Debe_Niif = number_format($total_cargar,2,".","");
					$oItem->Haber = "0";
					$oItem->Haber_Niif = "0";

					$oItem->Nit = $nit;
					$oItem->Tipo_Nit = $tipo_nit;
					$oItem->Documento = $nroFactura;
					$oItem->Numero_Comprobante = $nroFactura;
				
					if (isEnableCentroCostoByPUC($asociacion['Id_Plan_Cuenta']))
						$oItem->Id_Centro_Costo = $centro_costo;
				
					if ($save_fecha)
						$oItem->Fecha_Movimiento = GetFechaMovimiento($id_registro_modulo, 'Nota_Credito_Global');
					$oItem->save();
					unset($oItem);
				
				}
			}		
        }
    
         function BuscarInformacionParaMovimientox($flag, $id_modulo){
        
            global $queryObj;
			$query = 'SELECT *
					FROM Asociacion_Plan_Cuentas
					WHERE Busqueda_Interna = "'.$flag.'" AND Id_Modulo = '.$id_modulo;
					
			$queryObj->SetQuery($query);
			$result = $queryObj->ExecuteQuery('simple');
			return $result;
		}
		
		
		 function GetFechaMovimiento($id, $tabla){
			$oItem = new complex($tabla,"Id_$tabla", $id);

			if ($tabla == 'Factura_Venta' || $tabla == 'Factura' || $tabla == 'Factura_Capita') {
				$fecha = $oItem->Fecha_Documento;
			} elseif ($tabla == 'Comprobante') {
				$fecha = $oItem->Fecha_Comprobante;
			} elseif ($tabla == 'Nota_Credito' || $tabla == 'Ajuste_Individual' || $tabla=='Nomina'|| $tabla =='Nota_Credito_Global') {
				$fecha = $oItem->Fecha;
			}elseif ($tabla == 'Inventario_Fisico' || $tabla == 'Inventario_Fisico_Punto') {
				$fecha = $oItem->Fecha_Fin;
			} elseif($tabla == 'Nacionalizacion_Parcial') {
				$fecha = $oItem->Fecha_Registro;
			} elseif($tabla == 'Acta_Recepcion' || $tabla == 'Acta_Recepcion_Internacional')
				$fecha = $oItem->Fecha_Creacion;

			unset($oItem);

			return $fecha;

		}
        

        function isEnableCentroCostoByPUC($id_plan_cuenta) {
            global $queryObj;
			$query = "SELECT Centro_Costo FROM Plan_Cuentas WHERE Id_Plan_Cuentas = $id_plan_cuenta";

			$queryObj->SetQuery($query);
			$datos=$queryObj->ExecuteQuery('simple');

			if ($datos['Centro_Costo'] == 'S') {
				return true;
			}

			return false;
        }
        


        function CalcularTotalesGravadosProductos($productos){
			$totales_gravados = array();

				foreach ($productos as $p) {
					
					if (!isset($totales_gravados[$p['Impuesto_Nota']]) ) {
						$totales_gravados[$p['Impuesto_Nota']] = $p['Subtotal_Con_Descuento'];
					}else{
						$totales_gravados[$p['Impuesto_Nota']] += $p['Subtotal_Con_Descuento'];
					}					
				}
	
			return $totales_gravados;
		}


		 function BuscarInformacionParaMovimiento($flag, $tipo = '', $id_modulo_factura) {
			global $queryObj;
			$query = '';

			if ($tipo == 'facturas') {
				
				$query = 'SELECT *
						FROM Asociacion_Plan_Cuentas
						WHERE Busqueda_Interna = "'.$flag.'"';

			}elseif($tipo == ''){

				$query = 'SELECT *
						FROM Asociacion_Plan_Cuentas
						WHERE Busqueda_Interna = "'.$flag.'" AND Id_Modulo = '.$id_modulo_factura;
			}

			$queryObj->SetQuery($query);
			$result = $queryObj->ExecuteQuery('simple');

			return $result;
		} 

		function getIdMouloNota($modulo){
			global $queryObj;
			$query = 'SELECT	Id_Modulo
					FROM Modulo
					WHERE
						LOWER(Nombre) = "'.strtolower($modulo).'"';

			$queryObj->SetQuery($query);
			$result = $queryObj->ExecuteQuery('simple');

			return  $result != false ? $result['Id_Modulo'] : 'Error Modulo';
		}