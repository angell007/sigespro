<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.configuracion.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.consulta.php');

	$http_response = new HttpResponse();
	$response = array();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '');
    $dis = ( isset( $_REQUEST['disp'] ) ? $_REQUEST['disp'] : '');
    
	$modelo = json_decode(utf8_decode($modelo), true);
	$dis = json_decode(utf8_decode($dis), true);

	$producto_descarga_pendiente=GetProducto($modelo);

	if($producto_descarga_pendiente['Cantidad']>$dis['Cantidad_Pendiente']){
		
		$prod_disp=GetProductoDispensacion($dis);
		if($prod_disp){
			$cantidad=$producto_descarga_pendiente['Cantidad']-$dis['Cantidad_Pendiente']; 

			$query='UPDATE Producto_Descarga_Pendiente_Remision SET Cantidad='.$cantidad.'  WHERE Id_Producto_Descarga_Pendiente_Remision='.$modelo['Id_Producto_Descarga_Pendiente_Remision'];
			$oCon= new consulta();
			$oCon->setQuery($query);
			$oCon->createData();
			unset($oCon);

			$nuevo_registro=$producto_descarga_pendiente;

			$nuevo_registro['Id_Dispensacion']=$dis['Id_Dispensacion'];
			$nuevo_registro['Cantidad']=$dis['Cantidad_Pendiente'];
			$nuevo_registro['Identificacion_Funcionario']=$modelo['Funcionario'];
			unset($nuevo_registro['Id_Producto_Descarga_Pendiente_Remision']);
	
			$oItem = new complex("Producto_Descarga_Pendiente_Remision","Id_Producto_Descarga_Pendiente_Remision");
			foreach ($nuevo_registro as $index => $value) {
				if($value!=''){
					$oItem->$index=$value;
				}                    
			}

			$oItem->save();
			unset($oItem);


			if($prod_disp['Cantidad_Entregada']>0){
				$oItem=new complex ("Producto_Dispensacion","Id_Producto_Dispensacion",$prod_disp['Id_Producto_Dispensacion']);
				$oItem->Cantidad_Formulada=$prod_disp['Cantidad_Entregada'];
				$oItem->save();    
				unset($oItem);

				$prod_disp['Cantidad_Formulada']=$prod_disp['Cantidad_Pendiente'];
				$prod_disp['Cantidad_Entregada']=$dis['Cantidad_Pendiente'];
				$prod_disp['Lote']=$producto_descarga_pendiente['Lote'];
				$prod_disp['Id_Inventario']=$producto_descarga_pendiente['Id_Inventario'];
				$prod_disp['Fecha_Vencimiento']=$producto_descarga_pendiente['Fecha_Vencimiento'];
				
				unset($prod_disp['Id_Producto_Dispensacion']);

				$oItem = new complex("Producto_Dispensacion","Id_Producto_Dispensacion");
				foreach ($prod_disp as $index => $value) {
					if($value!=''){
						$oItem->$index=$value;
					}                    
				}

				$oItem->save();
				$id_producto_pendiente=$oItem->getId();
				unset($oItem);

			}else{
				$oItem=new complex ("Producto_Dispensacion","Id_Producto_Dispensacion",$prod_disp['Id_Producto_Dispensacion']);
				$oItem->Cantidad_Entregada=$dis['Cantidad_Pendiente'];
				$oItem->Lote=$producto_descarga_pendiente['Lote'];
				$oItem->Fecha_Vencimiento=$producto_descarga_pendiente['Fecha_Vencimiento'];
				$oItem->Id_Inventario=$producto_descarga_pendiente['Id_Inventario'];
				$oItem->Cum=$modelo['Codigo_Cum'];
				$oItem->Id_Producto=$modelo['Id_Producto'];
				$oItem->save();
				$id_producto_pendiente=$oItem->getId();
				unset($oItem);
			}

			
			$oItem = new complex('Producto_Dispensacion_Pendiente',"Id_Producto_Dispensacion_Pendiente");
			$cantidad_pendiente=$prod_disp["Cantidad_Pendiente"]-$dis['Cantidad_Pendiente'];
			$oItem->Id_Producto_Dispensacion=$id_producto_pendiente;
			$oItem->Cantidad_Entregada=$dis['Cantidad_Pendiente'];
			$oItem->Cantidad_Pendiente=$cantidad_pendiente ;
			$oItem->Entregar_Faltante=$cantidad_pendiente;
			$oItem->save();
			unset($oItem);
			RegistrarActividad();

			DescontarPendientes($dis['Cantidad_Pendiente']);
			
			
			EditarActividadProductoDispensacion($cantidad);

		}


	}else{
		
		$prod_disp=GetProductoDispensacion($dis);
		if($prod_disp){
			$query='UPDATE Producto_Descarga_Pendiente_Remision SET Id_Dispensacion='.$dis['Id_Dispensacion'].'  WHERE Id_Producto_Descarga_Pendiente_Remision='.$modelo['Id_Producto_Descarga_Pendiente_Remision'];
			$oCon= new consulta();
			$oCon->setQuery($query);
			$oCon->createData();
			unset($oCon);

			if($prod_disp['Cantidad_Entregada']>0){
				$oItem=new complex ("Producto_Dispensacion","Id_Producto_Dispensacion",$prod_disp['Id_Producto_Dispensacion']);
				$oItem->Cantidad_Formulada=$prod_disp['Cantidad_Entregada'];
				$oItem->save();    
				unset($oItem);

				$prod_disp['Cantidad_Formulada']=$prod_disp['Cantidad_Pendiente'];
				$prod_disp['Cantidad_Entregada']=$modelo['Cantidad'];
				$prod_disp['Lote']=$producto_descarga_pendiente['Lote'];
				$prod_disp['Id_Inventario']=$producto_descarga_pendiente['Id_Inventario'];
				$prod_disp['Fecha_Vencimiento']=$producto_descarga_pendiente['Fecha_Vencimiento'];
				
				unset($prod_disp['Id_Producto_Dispensacion']);

				$oItem = new complex("Producto_Dispensacion","Id_Producto_Dispensacion");
				foreach ($prod_disp as $index => $value) {
					if($value!=''){
						$oItem->$index=$value;
					}                    
				}

				$oItem->save();
				$id_producto_pendiente=$oItem->getId();
				unset($oItem);

			}else{
				$oItem=new complex ("Producto_Dispensacion","Id_Producto_Dispensacion",$prod_disp['Id_Producto_Dispensacion']);
				$oItem->Cantidad_Entregada=$modelo['Cantidad'];
				$oItem->Lote=$producto_descarga_pendiente['Lote'];
				$oItem->Fecha_Vencimiento=$producto_descarga_pendiente['Fecha_Vencimiento'];
				$oItem->Id_Inventario=$producto_descarga_pendiente['Id_Inventario'];
				$oItem->Cum=$modelo['Codigo_Cum'];
				$oItem->Id_Producto=$modelo['Id_Producto'];
				$oItem->save();
				$id_producto_pendiente=$oItem->getId();
				unset($oItem);
			}

			$oItem = new complex('Producto_Dispensacion_Pendiente',"Id_Producto_Dispensacion_Pendiente");
			$cantidad_pendiente=$prod_disp["Cantidad_Pendiente"]-$modelo['Cantidad'];
			$oItem->Id_Producto_Dispensacion=$id_producto_pendiente;
			$oItem->Cantidad_Entregada=$modelo['Cantidad'];
			$oItem->Cantidad_Pendiente=$cantidad_pendiente ;
			$oItem->Entregar_Faltante=$cantidad_pendiente;
			$oItem->save();
			unset($oItem);


			RegistrarActividad();
			DescontarPendientes($modelo['Cantidad']);
			QuitarCantidadProductoDispensacion();

		}
		


	}

	

	




    $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha(n) agregado(s) exitosamente los datos de las dispensaciones pendientes!');
    $response = $http_response->GetRespuesta();

	echo json_encode($response);
	function DescontarPendientes($cantidad){
		global $dis;
		$oItem = new complex('Dispensacion',"Id_Dispensacion", $dis['Id_Dispensacion']);
		$pendientes = $oItem->Pendientes - $cantidad;
		$entregados = $oItem->Productos_Entregados + $cantidad;
		if ($pendientes >= 0) {
			$oItem->Pendientes = number_format($pendientes,0,"","");
			$oItem->Productos_Entregados = number_format($entregados,0,"","");
		} else { // Evitar por si cae en negativo.
			$oItem->Pendientes = '0';
			$oItem->Productos_Entregados = number_format($entregados,0,"","");
		}
		$oItem->save();
		unset($oItem);
	
	  }

	function GetProducto($modelo){
		$query="SELECT * FROM Producto_Descarga_Pendiente_Remision WHERE Id_Producto_Descarga_Pendiente_Remision=$modelo[Id_Producto_Descarga_Pendiente_Remision]";

		$oCon= new consulta();
		$oCon->setQuery($query);
		$prod = $oCon->getData();
		unset($oCon);

		$query="SELECT Id_Inventario,Fecha_Vencimiento  FROM Inventario WHERE Id_Producto=$prod[Id_Producto] AND Id_Punto_Dispensacion=$modelo[Punto] AND Lote='$prod[Lote]'";

		$oCon= new consulta();
		$oCon->setQuery($query);
		$inv = $oCon->getData();
		unset($oCon);

		if($inv['Id_Inventario']){
			$prod['Id_Inventario']=$inv['Id_Inventario'];
			$prod['Fecha_Vencimiento']=$inv['Fecha_Vencimiento'];
		}
		

		return $prod;

	}


	function GetProductoDispensacion($p){

		$query="SELECT *,(Cantidad_Formulada-Cantidad_Entregada) as Cantidad_Pendiente
		FROM Producto_Dispensacion WHERE Id_Dispensacion=$p[Id_Dispensacion] AND Id_Producto=$p[Id_Producto] HAVING Cantidad_Pendiente>0 " ;

		
		$oCon= new consulta();
		$oCon->setQuery($query); 
		$prod = $oCon->getData();
		unset($oCon);

		return $prod;
	}

	function RegistrarActividad(){
		global $dis,$modelo;
		$ActividadDis['Fecha'] = date("Y-m-d H:i:s");
		$ActividadDis["Id_Dispensacion"] = $dis['Id_Dispensacion'];
		$ActividadDis["Identificacion_Funcionario"] = $modelo['Funcionario'];
	
		$ActividadDis["Detalle"] = "Se entrego la dispensacion pendiente. Producto: $modelo[Nombre_Comercial] - Cantidad: $dis[Cantidad_Pendiente]" ;
		$ActividadDis["Estado"] = "Creado";
		
		$oItem = new complex("Actividades_Dispensacion","Id_Actividades_Dispensacion");
		foreach($ActividadDis as $index=>$value) {
			$oItem->$index=$value;
		}
		$oItem->save();
		unset($oItem);
	}


	function EditarActividadProductoDispensacion($cantidad){
		global $producto_descarga_pendiente,$modelo,$dis;

		$query="SELECT PD.Id_Producto_Dispensacion, D.Productos_Entregados,D.Pendientes  FROM Producto_Dispensacion PD INNER JOIN Dispensacion D ON PD.Id_Dispensacion=D.Id_Dispensacion  WHERE PD.Id_Dispensacion=$producto_descarga_pendiente[Id_Dispensacion] AND PD.Id_Producto=$producto_descarga_pendiente[Id_Producto] AND PD.Lote='$producto_descarga_pendiente[Lote]' ORDER BY PD.Id_Producto_Dispensacion DESC LIMIT 1";
		$oCon= new consulta();
		$oCon->setQuery($query); 
		$prod = $oCon->getData();
		unset($oCon);


		$query='UPDATE Producto_Dispensacion SET Cantidad_Entregada='.$cantidad.'  WHERE Id_Producto_Dispensacion='.$prod['Id_Producto_Dispensacion'];
		$oCon= new consulta();
		$oCon->setQuery($query);
		$oCon->createData();
		unset($oCon);

		$pendientes=$prod['Pendientes']+$dis['Cantidad_Pendiente'];
		$prod_entregados=$prod['Productos_Entregados']-$dis['Cantidad_Pendiente'];
		$query='UPDATE Dispensacion SET Productos_Entregados='.$prod_entregados.', Pendientes='.$pendientes.'  WHERE Id_Dispensacion='.$producto_descarga_pendiente['Id_Dispensacion'];
		$oCon= new consulta();
		$oCon->setQuery($query);
		$oCon->createData();
		unset($oCon);


		


		
		$query="SELECT Id_Actividades_Dispensacion FROm Actividades_Dispensacion WHERE Id_Dispensacion=$producto_descarga_pendiente[Id_Dispensacion] AND Detalle LIKE '%$modelo[Nombre_Comercial] - Cantidad: $producto_descarga_pendiente[Cantidad]%' ";
		$oCon= new consulta();
		$oCon->setQuery($query); 
		$actividad = $oCon->getData();
		unset($oCon);
		$detalles="Se entrego la dispensacion pendiente. Producto: $modelo[Nombre_Comercial] - Cantidad: $cantidad";

		if($actividad['Id_Actividades_Dispensacion']){
			$query='UPDATE Actividades_Dispensacion SET Detalle="'.$detalles.'"  WHERE Id_Actividades_Dispensacion='.$actividad['Id_Actividades_Dispensacion'];
			$oCon= new consulta();
			$oCon->setQuery($query);
			$oCon->createData();
			unset($oCon);
		}

	}

	function QuitarCantidadProductoDispensacion(){
		global $producto_descarga_pendiente,$modelo;

		$query="SELECT Id_Producto_Dispensacion FROM Producto_Dispensacion WHERE Id_Dispensacion=$producto_descarga_pendiente[Id_Dispensacion] AND Id_Producto=$producto_descarga_pendiente[Id_Producto] AND Lote='$producto_descarga_pendiente[Lote]' ORDER BY Id_Producto_Dispensacion DESC LIMIT 1";
		$oCon= new consulta();
		$oCon->setQuery($query); 
		$prod = $oCon->getData();
		unset($oCon);

		$query='UPDATE Producto_Dispensacion SET Cantidad_Entregada=0, Id_Inventario=0,Lote="Pendiente"  WHERE Id_Producto_Dispensacion='.$prod['Id_Producto_Dispensacion'];
		$oCon= new consulta();
		$oCon->setQuery($query);
		$oCon->createData();
		unset($oCon);

		$query="SELECT Id_Actividades_Dispensacion FROm Actividades_Dispensacion WHERE Id_Dispensacion=$producto_descarga_pendiente[Id_Dispensacion] AND Detalle LIKE '%$modelo[Nombre_Comercial] - Cantidad: $producto_descarga_pendiente[Cantidad]%' ";
		$oCon= new consulta();
		$oCon->setQuery($query); 
		$actividad = $oCon->getData();
		unset($oCon);
		if($actividad['Id_Actividades_Dispensacion']){
			$oItem=new complex('Actividades_Dispensacion','Id_Actividades_Dispensacion',$actividad['Id_Actividades_Dispensacion']);
			$oItem->delete();
			unset($oItem);
		}
		

	}

	

	
?>