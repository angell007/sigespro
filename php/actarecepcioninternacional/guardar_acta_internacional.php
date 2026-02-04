<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.contabilizar.php');
	include_once('../../class/class.http_response.php');
	require_once('../../class/class.configuracion.php');
	require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

	$queryObj = new QueryBaseDatos();
	$response = array();
	$http_response = new HttpResponse();
	$contabilizar = new Contabilizar();
	$configuracion = new Configuracion();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
	$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
	$facturas = ( isset( $_REQUEST['facturas'] ) ? $_REQUEST['facturas'] : '' );
	$codigo_compra = ( isset( $_REQUEST['codigo_compra'] ) ? $_REQUEST['codigo_compra'] : '' );
	$id_orden = ( isset( $_REQUEST['id_orden'] ) ? $_REQUEST['id_orden'] : '' );

	$modelo = (array) json_decode($modelo, true);
	$productos = (array) json_decode($productos, true);
	$facturas = (array) json_decode($facturas, true);

	$datos_movimiento_contable = array();

	// var_dump($modelo);
	// var_dump($productos);
	// var_dump($facturas);
	 //var_dump($_FILES);
	//exit;

	unset($modelo['Id_Acta_Recepcion_Internacional']);
	unset($modelo['Codigo_Qr']);
	$modelo['Fecha_Creacion'] = date('Y-m-d');

	// var_dump($modelo);
	// var_dump($productos);
	// exit;

	$cod = $configuracion->getConsecutivo('Acta_Recepcion_Internacional','Acta_Recepcion_Internacional');
    $modelo['Codigo']= $cod;

	$oItem = new complex("Acta_Recepcion_Internacional","Id_Acta_Recepcion_Internacional");

	foreach($modelo as $index=>$value) {
        $oItem->$index=$value;
    }
    
    $oItem->save();
    $id_acta = $oItem->getId();
    unset($oItem);

    $qr = generarqr('actainternacional',$id_acta,'IMAGENES/QR/');
	$oItem = new complex("Acta_Recepcion_Internacional","Id_Acta_Recepcion_Internacional",$id_acta);
	$oItem->Codigo_Qr=$qr;
	$oItem->save();
	unset($oItem);

	$modelo['Id_Acta_Recepcion_Internacional'] = $id_acta;
	$datos_movimiento_contable['Modelo'] = $modelo;
    $datos_movimiento_contable['Productos'] = $productos;
    $datos_movimiento_contable['Id_Registro'] = $id_acta;

	GuardarActividadActaInternacional($id_acta);

	GuardarFacturasActa($facturas, $id_acta, $id_orden);
	GuardarProductosActa($productos, $id_acta, $codigo_compra);
	GuardarInventarioImportacion($productos, $modelo['Identificacion_Funcionario'], $id_acta);
	ActualizarEstadoOrdenCompraInternacional($id_orden);
	// GuardarGastosAdicionales($otros_gastos, $id_acta);
	// GuardarActividadOrdenInternacionalOtrosGastos($id_acta, $otros_gastos);
	GuardarMovimientosContables($datos_movimiento_contable);

	$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha guardado la orden exitosamente!');
	$response = $http_response->GetRespuesta();

	echo json_encode($response);

	function GuardarFacturasActa($facturas, $id_acta, $id_orden){
		global $_FILES, $MY_FILE;

		$i = 0;
		unset($facturas[count($facturas)-1]);

		foreach ($facturas as $f) {			

			$oItem = new complex("Factura_Acta_Recepcion_Internacional","Id_Factura_Acta_Recepcion_Internacional");

	    	$oItem->Id_Acta_Recepcion_Internacional = $id_acta;
	    	$oItem->Factura = $f['Factura'];
	    	$oItem->Fecha_Factura = $f['Fecha_Factura'];

	    	if (!empty($_FILES["archivo_facturas_$i"]['name'])){
		        $posicion1 = strrpos($_FILES["archivo_facturas_$i"]['name'],'.')+1;
		        $extension1 =  substr($_FILES["archivo_facturas_$i"]['name'],$posicion1);
		        $extension1 =  strtolower($extension1);
		        $_filename1 = uniqid() . "." . $extension1;
		        $_file1 = $MY_FILE . "ARCHIVOS/FACTURAS_COMPRA_INTERNACIONAL/" . $_filename1;
		        
		        $subido1 = move_uploaded_file($_FILES["archivo_facturas_$i"]['tmp_name'], $_file1);
	            if ($subido1){
	                @chmod ( $_file1, 0777 );
	                $oItem->Archivo_Factura = $_filename1;
	            } 
		    }

	    	$oItem->Id_Orden_Compra_Internacional = $id_orden;
	    	$oItem->Estado = 'Subida';
	    	
	    	$oItem->save();
		    unset($oItem);

		    $i++;
		}
	}

	function GuardarProductosActa($productos, $id_acta, $codigo_compra){
		global $_FILES, $MY_FILE;

		$i = 0;

		foreach ($productos as $p) {

			// if ($p['Id_No_Conforme'] != '') {
				
			// 	GenerarNoConforme($p, $id_acta);	
			// }
				
			unset($p['Producto_Lotes'][count($p['Producto_Lotes'])-1]);

			$j = 0;
			$cantidad_no_conforme = 0;
			$_filename1 = '';

			if (!empty($_FILES["archivo_productos_$j"]['name'])){
		        $posicion1 = strrpos($_FILES["archivo_productos_$j"]['name'],'.')+1;
		        $extension1 =  substr($_FILES["archivo_productos_$j"]['name'],$posicion1);
		        $extension1 =  strtolower($extension1);
		        $_filename1 = uniqid() . "." . $extension1;
		        $base_route = $MY_FILE . "ARCHIVOS/PRODUCTOS_COMPRA_INTERNACIONAL/";
		        $codigo_acta = GetCodigoActa($id_acta);
		        $final_route = $base_route.$codigo_acta;

		        if (!file_exists($final_route)) {				        	
		        	mkdir($final_route, 0777, true);
		        }

		        $_file1 = $final_route."/".$_filename1;
		        
		        $subido1 = move_uploaded_file($_FILES["archivo_productos_$j"]['tmp_name'], $_file1);
	            if ($subido1){
	                @chmod ( $_file1, 0777 );
	            } 
	    	}

			foreach ($p['Producto_Lotes'] as $lote) {
			    

				// if ($lote['Id_No_Conforme'] != '0') {
				// 	$cantidad_no_conforme += $lote['Cantidad_No_Conforme'];
				// }

				if ($lote['Cantidad'] != '0') {
					$oItem = new complex("Producto_Acta_Recepcion_Internacional","Id_Producto_Acta_Recepcion_Internacional");

			    	$oItem->Id_Producto_Orden_Compra_Internacional = $lote['Id_Producto_Orden_Compra_Internacional'];
			    	$oItem->Id_Producto = $lote['Id_Producto'];		    	
			    	$oItem->Id_Acta_Recepcion_Internacional = $id_acta;
			    	$oItem->Cantidad = $lote['Cantidad'];
			    	$oItem->Precio = $lote['Precio'];
			    	$oItem->Impuesto = $lote['Impuesto'];
			    	$oItem->Subtotal = number_format($lote['Subtotal'], 2, ".", "");
			    	$oItem->Lote = $lote['Lote'];
			    	$oItem->Fecha_Vencimiento = $lote['Fecha_Vencimiento'];
			    	$oItem->Factura = $lote['Factura'];
			    	$oItem->Codigo_Compra = $codigo_compra;
	                $oItem->Archivo_Producto = $_filename1;
			    	
			    	$oItem->save();
				    unset($oItem);

				    $cum = GetCodigoCum($lote['Id_Producto']);
				    $tasa_orden = GetTasaOrdenCompra($id_acta);
				    $precio_pesos = floatval($p['Precio']) * floatval($tasa_orden);
				    $precio_con_iva = $precio_pesos + ($precio_pesos*(floatval($p['Impuesto'])/100));
				    GuardarListaGananciaProducto($cum, $precio_con_iva);
				}

			    $j++;				
			}
			
			$i++;
		}
	}

	// function GetCodigoActa($id_acta){
	// 	global $queryObj;

	// 	$query = '
	// 		SELECT
	// 			Codigo
	// 		FROM Acta_Recepcion_Internacional
	// 		WHERE
	// 			Id_Acta_Recepcion_Internacional = '.$id_acta;

	// 	$queryObj->SetQuery($query);
	// 	$codigo = $queryObj->ExecuteQuery('simple');
	// 	return $codigo['Codigo'];
	// }

	function GenerarNoConforme($producto, $id_acta){
		global $modelo, $configuracion;

		$cod = $configuracion->getConsecutivo('No_Conforme_Internacional','No_Conforme_Internacional');

		$oItem = new complex("No_Conforme_Internacional","Id_No_Conforme_Internacional");
    	
    	$oItem->Id_Acta_Recepcion_Internacional = $id_acta;
    	$oItem->Fecha_Registro = date('Y-m-d');
    	$oItem->Persona_Reporta = $modelo['Identificacion_Funcionario'];
    	$oItem->Codigo = $cod;
    	$oItem->Estado = 'Pendiente';

		$oItem->save();
	    $id_no_conforme = $oItem->getId();
	    unset($oItem);

    	$qr = generarqr('noconformeinternacional',$id_no_conforme,'IMAGENES/QR/');
		$oItem = new complex("No_Conforme_Internacional","Id_No_Conforme_Internacional",$id_no_conforme);
		$oItem->Codigo_Qr=$qr;
		$oItem->save();
		unset($oItem);

		$cantidad_producto_lotes = GetTotalCantidadProductoLote($producto['Producto_Lotes']);
	}

	function GetTotalCantidadProductoLote($lotesProducto){
		$cantidad_producto = 0;

		foreach ($lotesProducto as $lote) {
			
			$cantidad_producto += intval($lote['Cantidad']);
		}

		return $cantidad_producto;
	}

	function ActualizarEstadoOrdenCompraInternacional($id_orden){
		global $queryObj;

		$query = 'UPDATE Orden_Compra_Internacional SET Estado = "Recibida" WHERE Id_Orden_Compra_Internacional = '.$id_orden;
		$queryObj->SetQuery($query);
		$queryObj->QueryUpdate();
	}

	// function GuardarActividadOrdenInternacionalOtrosGastos($id_orden, $otros_gastos, $id_funcionario){

	// 	$oItem = new complex("Actividad_Orden_Compra_Internacional","Id_Actividad_Orden_Compra_Internacional");

	// 	$oItem->Identificacion_Funcionario = $id_funcionario;
	// 	$oItem->Id_Orden_Compra_Internacional = $id_orden;
	// 	$oItem->Accion = "Creacion";
	// 	$oItem->Descripcion = ArmarMensajeActividadOtrosGastos($otros_gastos);

	//     $oItem->save();
	//     unset($oItem);
	// }

	// function ArmarMensajeActividadOtrosGastos($otros_gastos){
	// 	$mensaje = 'Se adicionaron los siguientes gastos ';

	// 	foreach ($otros_gastos as $og) {
	//     	$mensaje .= $og['Concepto_Gasto']." por un monto de $ ".$og['Monto_Gasto'].", ";
	//     }

	//     return trim($mensaje, ", ");
	// }	

	function GuardarInventarioImportacion($productos, $id_funcionario, $id_acta){

		foreach ($productos as $p) {
				
			unset($p['Producto_Lotes'][count($p['Producto_Lotes'])-1]);

			foreach ($p['Producto_Lotes'] as $lote) {

				if ($p['Cantidad'] != '0') {
				    if($lote['Id_Producto'] && $lote['Lote']){
				    $oItem = new complex("Importacion","Id_Importacion");

			    	$oItem->Id_Producto = $lote['Id_Producto'];
			    	$oItem->Id_Producto_Acta_Recepcion_Internacional = GetIdProductoActaInternacional($id_acta, $lote['Lote'], $lote['Id_Producto']);
			    	$oItem->Cantidad = $lote['Cantidad'];
			    	$oItem->Lote = $lote['Lote'];
			    	$oItem->Fecha_Vencimiento = $lote['Fecha_Vencimiento'];
			    	$oItem->Precio = $lote['Precio'];
			    	$oItem->Identificacion_Funcionario = $id_funcionario;
			    	
			    	$oItem->save();
				    unset($oItem);
				    }
				
				}			
			}
		}
	}

	function GetIdProductoActaInternacional($id_acta, $lote, $id_producto){
		global $queryObj;

		$query = '
			SELECT
				Id_Producto_Acta_Recepcion_Internacional
			FROM Producto_Acta_Recepcion_Internacional
			WHERE
				Id_Acta_Recepcion_Internacional = '.$id_acta
				.' AND Lote = "'.$lote.'" AND Id_Producto = '.$id_producto;

		$queryObj->SetQuery($query);
		$id = $queryObj->ExecuteQuery('simple');
		return $id['Id_Producto_Acta_Recepcion_Internacional'];
	}

	function GetCodigoCum($id_producto){
		global $queryObj;

		$query = '
			SELECT
				Codigo_Cum
			FROM Producto
			WHERE
				Id_Producto = '.$id_producto;

		$queryObj->SetQuery($query);
		$cum = $queryObj->ExecuteQuery('simple');
		return $cum['Codigo_Cum'];
	}

	function GuardarActividadActaInternacional($id_acta){
		global $modelo;

		$codigo = GetCodigoActa($id_acta);

		$oItem = new complex("Actividad_Acta_Internacional","Id_Actividad_Acta_Internacional");

		$oItem->Identificacion_Funcionario = $modelo['Identificacion_Funcionario'];
		$oItem->Id_Acta_Internacional = $id_acta;
		$oItem->Accion = "Creacion";
		$oItem->Descripcion = "Se creo el acta con codigo ".$codigo;

	    $oItem->save();
	    unset($oItem);
	}

	function GetCodigoActa($id_acta){
		$oItem = new complex("Acta_Recepcion_Internacional","Id_Acta_Recepcion_Internacional", $id_acta);
		$acta = $oItem->getData();
	    unset($oItem);

	    return $acta['Codigo'];
	}

	function GuardarGastosAdicionales($gastos, $id_acta)
	{
		foreach ($otros_gastos as $og) {
	    	$oItem = new complex("Acta_Recepcion_Internacional_Otro_Gasto","Id_Acta_Recepcion_Internacional_Otro_Gasto");

	    	$oItem->Id_Acta_Recepcion_Internacional = $id_acta;
	    	$oItem->Concepto_Gasto = $og['Concepto_Gasto'];
	    	$oItem->Monto_Gasto = number_format($og['Monto_Gasto'], 2, ".", "");
	    	
	    	$oItem->save();
		    unset($oItem);
	    }

	    GuardarActividadOrdenInternacionalOtrosGastos($id_acta, $gastos);
	}

	// function GuardarActividadOrdenInternacionalOtrosGastos($id_acta, $otros_gastos){
	// 	global $modelo;

	// 	$oItem = new complex("Actividad_Orden_Compra_Internacional","Id_Actividad_Orden_Compra_Internacional");

	// 	$oItem->Identificacion_Funcionario = $modelo['Identificacion_Funcionario'];
	// 	$oItem->Id_Orden_Compra_Internacional = $id_acta;
	// 	$oItem->Accion = "Creacion";
	// 	$oItem->Descripcion = ArmarMensajeActividadOtrosGastos($otros_gastos);

	//     $oItem->save();
	//     unset($oItem);
	// }

	// function ArmarMensajeActividadOtrosGastos($otros_gastos){
	// 	$mensaje = 'Se adicionaron los siguientes gastos ';

	// 	foreach ($otros_gastos as $og) {
	//     	$mensaje .= $og['Concepto_Gasto']." por un monto de $ ".$og['Monto_Gasto'].", ";
	//     }

	//     return trim($mensaje, ", ");
	// }

	function GuardarMovimientosContables($datos){
		global $contabilizar;

		$contabilizar->CrearMovimientoContable('Acta Internacional', $datos);
	}

	function GetTasaOrdenCompra($id_acta){
			global $queryObj;

			$query = '
				SELECT
					OCI.Tasa_Dolar
				FROM Orden_Compra_Internacional OCI
				INNER JOIN Acta_Recepcion_Internacional ARI ON OCI.Id_Orden_Compra_Internacional = ARI.Id_Orden_Compra_Internacional
				WHERE
					ARI.Id_Acta_Recepcion_Internacional = '.$id_acta;

			$queryObj->SetQuery($query);
			$tasa = $queryObj->ExecuteQuery('simple');
			return $tasa['Tasa_Dolar'];
		}

	function GuardarListaGananciaProducto($codigo_cum, $precio_p){
		global $queryObj;

		$query = 'SELECT LG.Porcentaje, LG.Id_Lista_Ganancia FROM Lista_Ganancia LG' ;
        
        $queryObj->setQuery($query);
        $porcentaje = $queryObj->ExecuteQuery('multiple');
    
        foreach ($porcentaje as  $value) {
            $query='SELECT * FROM Producto_Lista_Ganancia WHERE Cum="'.$codigo_cum.'" AND Id_lista_Ganancia='.$value['Id_Lista_Ganancia'];
            
            $queryObj->setQuery($query);
            $cum = $queryObj->ExecuteQuery('simple');
            if($cum){
                $precio=number_format($precio_p/((100-$value['Porcentaje'])/100),2,'.','');
                if($cum['Precio']<$precio){
                    $oItem = new complex('Producto_Lista_Ganancia','Id_Producto_Lista_Ganancia', $cum['Id_Producto_Lista_Ganancia']);
                   
                    $oItem->Precio = $precio;
                    $oItem->Id_Lista_Ganancia=$value['Id_Lista_Ganancia'];
                   	$oItem->save();
                    unset($oItem);
                }
              
            }else{
                $oItem = new complex('Producto_Lista_Ganancia','Id_Producto_Lista_Ganancia');
                $oItem->Cum = $codigo_cum;
                $precio=number_format($precio_p/((100-$value['Porcentaje'])/100),2,'.','');
                $oItem->Precio =$precio ;
                $oItem->Id_Lista_Ganancia=$value['Id_Lista_Ganancia'];
                $oItem->save();
                unset($oItem);
            }
        }
	}
?>