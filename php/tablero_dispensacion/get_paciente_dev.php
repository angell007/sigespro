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

$idPaciente = ( isset( $_REQUEST['id_paciente'] ) ? $_REQUEST['id_paciente'] : '' );
$IdPunto = ( isset( $_REQUEST['id_punto'] ) ? $_REQUEST['id_punto'] : '' );

$hoy = date('Y-m-d');
$fecha_min_pendiente = strtotime('-1 month', strtotime($hoy));
$fecha_min_pendiente = date('Y-m-d', $fecha_min_pendiente);


$condicion = SetCondiciones();    
$query= GetQueryPaciente();

$condicion_lotes="WHERE I.Id_Punto_Dispensacion=$IdPunto ";

$queryObj->SetQuery($query);
$paciente = $queryObj->ExecuteQuery('simple');

if($paciente['Id_Paciente']){

	$direccionamientos=GetDireccionamientos($idPaciente);
	$salario_base=GetSalarioBase();
	// Se obtienen los pendientes con existencia en el inventario.

	$servicios=GetServicios();
	$i=0;
	foreach ($servicios as $s) {

		$tiposervicios=GetTipoServicios($s['Id_Servicio']);
		if($tiposervicios!=''){
			$queryPendientesConExistencia=GetQueryPendientesConExistencia($tiposervicios);
			
			$queryObj->SetQuery($queryPendientesConExistencia);
			$pendientesconexistencia = $queryObj->ExecuteQuery('Multiple');
		}else{
			$pendientesconexistencia=[];
		}
		


		$servicios[$i]['Productos_Disponibles']=$pendientesconexistencia;
		$id_productos_existentes=GetIdExistentes($pendientesconexistencia);	
		

		$query=GetPendientesSinExistencia($tiposervicios);
		$queryObj->SetQuery($query);
		$pendientessinexistencia = $queryObj->ExecuteQuery('Multiple');

		$pendientessinexistencia=VerSimilares($pendientessinexistencia);

		$servicios[$i]['Productos_No_Disponibles']=$pendientessinexistencia;

		
		$i++;
	}

	$paciente=GetDatosSubsidiado($paciente);

	$productos_del_mes=GetProductoEntregados($paciente['Id_Paciente']);

	
	$http_response->SetRespuesta(0,'Exitoso','Se obtuvieron datos del paciente');
	$response=$http_response->GetRespuesta();
	$response['Servicios']=$servicios;
	$response['Paciente']=$paciente;
	$response['Productos_Entregados']=$productos_del_mes;
	$response['Direccionamientos']=$direccionamientos;
	


}else{
	$http_response->SetRespuesta(1,'Error','El paciente consultado no se encuntra registrado en la base de datos.');
	$response=$http_response->GetRespuesta();
}





echo json_encode($response);

function SetCondiciones(){
	global $idPaciente;

	$condicion=" WHERE P.Id_Paciente='$idPaciente'  "; 

	return $condicion; 
}

function GetQueryPaciente(){
	global $condicion;

	$query='SELECT
	R.Nombre as Regimen, 
	N.Nombre as Nivel , 
	N.Valor as Valor_Nivel,
	N.Numero as Numero_Nivel,
	CONCAT_WS(" ",P.Primer_Nombre, P.Segundo_Nombre,P.Primer_Apellido,P.Segundo_Apellido) as Nombre_Paciente,
	0 AS Cuota,
	P.EPS as Eps,
	P.Nit,
	P.Id_Departamento,
	P.Id_Paciente
	FROM Paciente P 
	LEFT JOIN Regimen R ON P.Id_Regimen=R.Id_Regimen
	LEFT JOIN Nivel N ON P.Id_Nivel=N.Id_Nivel '.$condicion;

	return $query;
}

function GetSalarioBase (){
	global $queryObj;
	$query="SELECT Salario_Base FROM Configuracion WHERE Id_Configuracion=1 ";
	$queryObj->SetQuery($query);
	$salario = $queryObj->ExecuteQuery('simple');

	return $salario['Salario_Base'];
}
 
function GetQueryPendientesConExistencia($tiposervicios){

	global $idPaciente,$IdPunto;
/** Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */
 if($tiposervicios!=''){
	$query="SELECT 
	CONCAT_WS(' ',CONCAT('(',D.Codigo,')'),'-',P.Nombre_Comercial,'(',P.Principio_Activo, P.Presentacion, P.Concentracion,')', P.Cantidad, P.Unidad_Medida) as Nombre,P.Nombre_Comercial,
	I.Id_Inventario_Nuevo ,
	P.Codigo_Cum ,
	I.Lote,
	I.Fecha_Vencimiento ,
	I.Cantidad ,
	D.*, (I.Cantidad-I.Cantidad_Apartada) as Cantidad_Disponible,
	0 as Seleccionado
	FROM Inventario_Nuevo I 
	INNER JOIN Producto P ON I.Id_Producto=P.Id_Producto 
	INNER JOIN (
		SELECT D.Cuota,
		D.Codigo,
		(PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Pendiente,
		PD.Id_Producto,
		(PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Formulada,
		PD.Numero_Autorizacion,
		PD.Fecha_Autorizacion,
		PD.Id_Dispensacion, 
		PD.Id_Producto_Dispensacion
		FROM Producto_Dispensacion PD
		INNER JOIN Dispensacion D
		ON D.Id_Dispensacion=PD.Id_Dispensacion
		WHERE (PD.Cantidad_Formulada-PD.Cantidad_Entregada)>0 
		AND D.Numero_Documento='$idPaciente'
		AND D.Id_Tipo_Servicio IN ($tiposervicios)
		AND D.Estado_Dispensacion <> 'Anulada'
	) AS D

	ON I.Id_Producto=D.Id_Producto
	WHERE I.Id_Punto_Dispensacion=$IdPunto
	AND I.Cantidad > 0	";

	/* echo $query;
	exit; */
 }

	





	return $query;

}

function GetIdExistentes($productos){
	$ids_productos = '';

	
	foreach ($productos as $producto) {
		$pos=strpos($ids_productos,$producto["Id_Producto"]);
		if($pos===false){
			$ids_productos .= $producto["Id_Producto"].',';
		}	
	}

	
	return trim($ids_productos,",");
}

function GetPendientesSinExistencia($tiposervicios){
	global $id_productos_existentes,$idPaciente;

	$condicion='';

	if($id_productos_existentes!=''){
		$condicion='AND PD.Id_Producto NOT IN ('.$id_productos_existentes.')';
	}
	/** Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */

	$query="SELECT CONCAT_WS(' ',CONCAT('(',D.Codigo,')'),'-',P.Nombre_Comercial,'(',	P.Principio_Activo, P.Presentacion, P.Concentracion,')', P.Cantidad, P.Unidad_Medida) as Nombre,   P.Nombre_Comercial,
			D.Cuota, 
			(PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Pendiente,
			PD.Id_Producto,
			P.Codigo_Cum,
			PD.Lote,
			PD.Id_Inventario_Nuevo,
			(PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Formulada,
			PD.Cantidad_Entregada,
			PD.Numero_Autorizacion,
			PD.Fecha_Autorizacion,
			'' as Vencimiento,			
			PD.Id_Producto_Dispensacion,
			PD.Id_Dispensacion,			
			0 as Seleccionado,
			0 as Mostrar
			FROM Dispensacion D
			INNER JOIN Producto_Dispensacion PD
			ON D.Id_Dispensacion=PD.Id_Dispensacion
			INNER JOIN Producto P
			ON P.Id_Producto=PD.Id_Producto
			WHERE (PD.Cantidad_Formulada-PD.Cantidad_Entregada)>0 
			AND D.Numero_Documento='$idPaciente'
			AND D.Id_Tipo_Servicio IN ($tiposervicios)
			AND D.Estado_Dispensacion <> 'Anulada'
			".$condicion."
			GROUP BY PD.Id_Producto, D.Id_Dispensacion";
			
			return $query;

}

function GetServicios(){

	global $queryObj,$IdPunto;
	$query="SELECT S.Id_Servicio,S.Nombre, REPLACE(S.Nombre, ' ', '_') as fieldName FROM Servicio_Punto_Dispensacion PS INNER JOIN Servicio S ON PS.Id_Servicio=S.Id_Servicio  WHERE Id_Punto_Dispensacion=$IdPunto ";
	$queryObj->SetQuery($query);
	$servicios = $queryObj->ExecuteQuery('Multiple');

	return $servicios;
}

function GetTipoServicios($id){
	global $queryObj,$IdPunto;

	$query="SELECT GROUP_CONCAT( DISTINCT TS.Id_Tipo_Servicio) as Tipo_Servicio 
	FROM Tipo_Servicio_Punto_Dispensacion TS 
	INNER JOIN Tipo_Servicio S ON TS.Id_Tipo_Servicio=S.Id_Tipo_Servicio
	WHERE S.Id_Servicio=$id AND TS.Id_Punto_Dispensacion=$IdPunto";
	$queryObj->SetQuery($query);
	$servicios = $queryObj->ExecuteQuery('simple');

	return $servicios['Tipo_Servicio'];
}

function VerSimilares($productos){
	$j=-1;
	foreach ($productos as $p) {$j++;
		$similares=GetSimilares($p['Id_Producto']);
	
		if(!$similares){
			//unset($productos[$j]);
			$productos[$j]["Similares"] = [];

		}else{
		   
			$productossimilares=GetLotesProductosimilares($similares,$p);
		
			if(count($productossimilares)==0){
				$productos[$j]["Similares"] = [];
			//	unset($productos[$j]);
			}else{
				$productos[$j]["Similares"] = $productossimilares;
			}
		}  
	}


	return $productos;
}


function GetSimilares($id){
   
    global $queryObj;

    $query="SELECT Producto_Asociado FROM Producto_Asociado WHERE (Producto_Asociado LIKE '".$id.','."%' OR Producto_Asociado LIKE '%, ".$id.','."%' OR Producto_Asociado LIKE '%, ".$id."') ";


    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('simple');

  
    return $productos;
}

function GetLotesProductosimilares($productos,$p){
    /** Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */
	global $condicion_lotes,$queryObj;
	$query = 'SELECT I.Cantidad as Cantidad_Disponible,P.Nombre_Comercial,
    CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida) as Nombre, P.Id_Producto, 0 as Seleccionado, I.Id_Inventario_Nuevo,I.Fecha_Vencimiento, P.Codigo_Cum,I.Lote,
	IFNULL((SELECT PC.Cantidad_Minima FROM Producto_Control_Cantidad PC  WHERE PC.Id_Producto=P.Id_Producto ), 0) as Cantidad_Minima, '.$p['Cantidad_Formulada'].' AS Cantidad_Formulada, '.$p['Id_Producto_Dispensacion'].' as Id_Producto_Dispensacion
    FROM Inventario_Nuevo I 
    INNER JOIN Producto P ON I.Id_Producto=P.Id_Producto 
    '.$condicion_lotes.' AND  I.Id_Producto IN (' .$productos['Producto_Asociado']. ')
   
   AND I.Cantidad>0';


    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('Multiple');

   

    return $productos;
}

function GetDatosSubsidiado($p){
	global $salario_base;
	$maximo_cobro=0;
	$porcentaje=0;
	$aplica_cuota_recuperacion='No';
	if($p['Regimen']=='Subsidiado'){
		if($p['Numero_Nivel']=='2'){
			$maximo_cobro=$salario_base*2;
			$aplica_cuota_recuperacion='Si';
			$porcentaje='0.1';
		}elseif($p['Numero_Nivel']=='3'){
			$maximo_cobro=$salario_base*3;
			$aplica_cuota_recuperacion='Si';
			$porcentaje='0.3';
		}

	}
	$p['Porcentaje']=$porcentaje;
	$p['Aplica_Cuota_Recuperacion']=$aplica_cuota_recuperacion;
	$p['Maximo_Cobro']=$maximo_cobro;
	$p['Total_Cuota']=GetCoutas($p['Id_Paciente']);

	return $p;
}

function GetCoutas($id){
	global $queryObj;

	$query="SELECT IFNULL(SUM(Cuota),0) as Total_Cuota FROM Dispensacion WHERE Numero_Documento='$id' AND Estado_Dispensacion!='Anulada' AND YEAR(Fecha_Actual) = YEAR(CURRENT_DATE())  ";
	$queryObj->SetQuery($query);
	$cuota = $queryObj->ExecuteQuery('simple');
	
	return $cuota['Total_Cuota'];
}

function GetProductoEntregados($i){
	global $queryObj;

	$query="SELECT
	D.Codigo,DATE(D.Fecha_Actual) as Fecha,PD.Id_Producto
	 FROm Producto_Dispensacion PD INNER JOIN Dispensacion D On PD.Id_Dispensacion=D.Id_Dispensacion 
	 WHERE D.Numero_Documento='$i' AND MONTH(Fecha_Actual)=MONTH(NOW()) AND D.Estado_Dispensacion!='Anulada'
	 GROUP BY PD.Id_Producto 
	 ORDER BY D.Id_Dispensacion DESC ";

	 $queryObj->SetQuery($query);
	 $productos = $queryObj->ExecuteQuery('Multiple');
	 return $productos;
}

function GetDireccionamientos($idPaciente){
	global $queryObj;
	$fecha=date('Y-m-d');

	$query=" SELECT *, DATE_SUB(Fecha_Maxima_Entrega, INTERVAL 31 DAY) as Resta		
	 FROm Dispensacion_Mipres D WHERE Id_Paciente='$idPaciente' HAVING '$fecha' >=Resta AND '$fecha'<=Fecha_Maxima_Entrega   ";


	 $queryObj->SetQuery($query);
	 $direccionamientos = $queryObj->ExecuteQuery('Multiple');

	foreach ($direccionamientos as $key => $value) {
		$direccionamientos[$key]['Productos']=GetProductosDireccionamiento($value['Id_Dispensacion_Mipres']);
	}

	 return $direccionamientos;
}

function GetProductosDireccionamiento($id){
	global $queryObj;

	$query=" SELECT 
            
	P.Nombre_Comercial,	CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre,
   P.Codigo_Cum, PD.Cantidad,P.Embalaje,PD.NoPrescripcion
FROM Producto_Dispensacion_Mipres PD 
INNER JOIN Producto P ON PD.Id_Producto = P.Id_Producto
WHERE
	PD.Id_Dispensacion_Mipres = $id ";

	 $queryObj->SetQuery($query);
	 $productos = $queryObj->ExecuteQuery('Multiple');

	 return $productos;
}

?>