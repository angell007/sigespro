<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.utility.php');

	$http_response = new HttpResponse();
	$queryObj = new QueryBaseDatos();
	$util = new Utility();

	$id_paciente = ( isset( $_REQUEST['id_paciente'] ) ? $_REQUEST['id_paciente'] : '' );
	$fechaformula = ( isset( $_REQUEST['fecha_formula'] ) ? $_REQUEST['fecha_formula'] : '' );
	$id_punto_dispensacion = ( isset( $_REQUEST['id_punto'] ) ? $_REQUEST['id_punto'] : '' );
	$id_servicio = ( isset( $_REQUEST['id_servicio'] ) ? $_REQUEST['id_servicio'] : '' );
	$id_tipo_servicio = ( isset( $_REQUEST['id_tipo_servicio'] ) ? $_REQUEST['id_tipo_servicio'] : '' );

	$fecha_entrega = GetDispensacionesFechaEntrega();
	$servicios_punto = GetServiciosPunto();
	$tipos_servicios_punto = GetTiposServiciosPunto();

	if (count($fecha_entrega) > 0) {
		$id_dispensacion='';
		foreach ($fecha_entrega as $key => $value) {
			if($id_dispensacion!=$value['Id_Dispensacion']){
				$productos=GetProductos($value['Id_Dispensacion']);
				$id_dispensacion=$value['Id_Dispensacion'];
				$fecha_entrega[$key]['Productos']=$productos;
				$fecha_entrega[$key]['Aplica_Servicio']=VerificarServiciosPuntoDis($servicios_punto['Servicios'], $value['Id_Servicio']);
				$fecha_entrega[$key]['Aplica_Tipo_Servicio']=VerificarTiposServiciosPuntoDis($tipos_servicios_punto['Tipos'], $value['Id_Tipo_Servicio']);
			}else{
				unset($fecha_entrega[$key]);
			} 
		}

		$fecha_entrega=array_values($fecha_entrega);
		$http_response->SetRespuesta(0, 'Con Entregas Pendientes', 'Se encontraron entregas para este paciente');
		$repsonse = $http_response->GetRespuesta();
		$repsonse['Fecha_Entrega']=$fecha_entrega;
	
	}else{
		$entrega=GetProximaEntrega();
		if($entrega){
			$http_response->SetRespuesta(2, 'Error Con la entrega', 'La entrega actual ya se realizo , su proxima entrega es '.$entrega);
			$repsonse = $http_response->GetRespuesta();
		}else{
			$http_response->SetRespuesta(1, 'Sin Dispensacion', 'No se encontraron entregas para este paciente puede realizar una nueva ');
			$repsonse = $http_response->GetRespuesta();
		}
	
	}

	echo json_encode($repsonse);

	function GetDispensacionesFechaEntrega(){
		global $queryObj,$id_paciente,$fechaformula, $id_tipo_servicio;
		$fecha=date('Y-m-d');

		$query="SELECT DE.Id_Dispensacion,DE.Id_Dispensacion_Fecha_Entrega, DE.Entrega_Actual,DE.Entrega_Total, D.Codigo AS Codigo_Dis, '0' AS Seleccionado, '0' AS Mostrar_Productos, D.Id_Servicio, D.Id_Tipo_Servicio
			FROM Dispensacion_Fecha_Entrega DE 
			INNER JOIN Dispensacion D ON DE.Id_Dispensacion = D.Id_Dispensacion
			WHERE 
				D.Estado_Dispensacion != 'Anulada' AND DE.Id_Paciente = '$id_paciente' AND DE.Fecha_Formula='$fechaformula' AND MONTH(DE.Fecha_Entrega)= ".date('m') ." AND DE.Estado='Pendiente' AND DE.Fecha_Entrega<='$fecha' AND D.Id_Tipo_Servicio=$id_tipo_servicio " ;

		$queryObj->SetQuery($query);
		$fecha_entrega = $queryObj->ExecuteQuery('Multiple');

		return $fecha_entrega;
	}

	function GetProximaEntrega(){
		global $queryObj,$id_paciente,$fechaformula;

		$mes_mas_uno = date('m')+1;
		
		$query="SELECT DE.Fecha_Entrega
				FROM Dispensacion_Fecha_Entrega DE 				
				WHERE 
				DE.Id_Paciente = '$id_paciente' AND DE.Fecha_Formula='$fechaformula' AND MONTH(DE.Fecha_Entrega)= ".$mes_mas_uno." AND Estado='Pendiente' " ;
		$queryObj->SetQuery($query);
		$fecha_entrega = $queryObj->ExecuteQuery('simple');

		return $fecha_entrega['Fecha_Entrega'];
	}

	function GetProductos($id){
		global $queryObj;

		$query="SELECT PD.*,SUM(PD.Cantidad_Formulada) as Cantidad_Formulada,
		CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre,
		P.Nombre_Comercial,P.Id_Producto,
		P.Codigo_Cum,
		P.Embalaje,
		IFNULL((SELECT PC.Cantidad_Minima FROM Producto_Control_Cantidad PC  WHERE PC.Id_Producto=P.Id_Producto ), 0) as Cantidad_Minima

		FROM Producto_Dispensacion PD 
		INNER JOIn Producto P On PD.Id_Producto=P.Id_Producto
		WHERE PD.Id_Dispensacion=$id  
		GROUP BY PD.Id_Producto ";
		$queryObj->SetQuery($query);
		$productos = $queryObj->ExecuteQuery('Multiple');


		foreach ($productos as $key => $value) {

			unset($productos[$key]['Id_Producto_Dispensacion']);
			unset($productos[$key]['Fecha_Autorizacion']);
			unset($productos[$key]['Numero_Autorizacion']);
			unset($productos[$key]['Numero_Prescripcion']);
			unset($productos[$key]['Cantidad_Entregada']);

			$lotes=GetLotes($value['Id_Producto']);
			$productos[$key]['Cantidad_Entregada']=0;
			if(count($lotes)>0){
				$productos[$key]['Lotes']=$lotes;
				$productos[$key]['Cantidad_Disponible']='';
				$productos[$key]['Lote'] = '';
				$productos[$key]['Fecha_Vencimiento'] = '';
			}else{
				$productos[$key]['Lotes']=[];
				$productos[$key]['Cantidad_Disponible']=0;
				$productos[$key]['Lote'] = 'Pendiente';
				$productos[$key]['Fecha_Vencimiento'] = 'Pendiente';
			}
		}

		return $productos;

	}

	function GetLotes($id){
	    /** Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */
		global $queryObj,$id_punto_dispensacion;
		$condicion=" AND I.Id_Punto_Dispensacion= $id_punto_dispensacion ";

		$query="SELECT
		CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre,P.Nombre_Comercial,
		P.Laboratorio_Comercial,
		P.Laboratorio_Generico,
		P.Id_Producto,
		P.Codigo_Cum,  
		P.Embalaje,
		I.Fecha_Vencimiento,
		IFNULL((SELECT PC.Cantidad_Minima FROM Producto_Control_Cantidad PC  WHERE PC.Id_Producto=P.Id_Producto ), 0) as Cantidad_Minima, 0 as Seleccionado, I.Fecha_Vencimiento, I.Lote, I.Id_Inventario_Nuevo, (I.Cantidad-I.Cantidad_Apartada) as Cantidad_Disponible, I.Costo
		FROM Inventario_Nuevo I INNER JOIN Producto P ON I.Id_Producto=P.Id_Producto WHERE P.Id_Producto=$id ".$condicion." AND (I.Cantidad-I.Cantidad_Apartada) > 0 
		ORDER BY I.Fecha_Vencimiento ASC";
		
		$queryObj->SetQuery($query);
		$lotes = $queryObj->ExecuteQuery('Multiple');
	

		return $lotes;
	}

	function GetServiciosPunto(){
		global $id_punto_dispensacion,$id_paciente,$id_servicio,$id_tipo_servicio, $queryObj;

		$query = "
			SELECT
				GROUP_CONCAT(DISTINCT Id_Servicio) AS Servicios
			FROM Servicio_Punto_Dispensacion
			WHERE
				Id_Punto_Dispensacion = $id_punto_dispensacion";

		$queryObj->SetQuery($query);
		$servicios =$queryObj->ExecuteQuery('simple');

		return $servicios;
	}

	function GetTiposServiciosPunto(){
		global $id_punto_dispensacion,$id_paciente,$id_servicio,$id_tipo_servicio, $queryObj;

		$query = "
			SELECT
				GROUP_CONCAT(DISTINCT Id_Tipo_Servicio) AS Tipos
			FROM Tipo_Servicio_Punto_Dispensacion
			WHERE
				Id_Punto_Dispensacion = $id_punto_dispensacion";

		$queryObj->SetQuery($query);
		$tipos_servicios =$queryObj->ExecuteQuery('simple');

		return $tipos_servicios;
	}

	function VerificarServiciosPuntoDis($serviciosPunto, $servicioDis){
		if(strstr($serviciosPunto, $servicioDis) !== false){
			return "Si";
		}else{
			return "No";
		}
	}

	function VerificarTiposServiciosPuntoDis($tiposServiciosPunto, $tipoServicioDis){
		if(strstr($tiposServiciosPunto, $tipoServicioDis) !== false){
			return "Si";
		}else{
			return "No";
		}
	}

?>