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

$id_punto= ( isset( $_REQUEST['id_destino'] ) ? $_REQUEST['id_destino'] : '' );
$bodega = ( isset( $_REQUEST['id_origen'] ) ? $_REQUEST['id_origen'] : '' );
$mes = ( isset( $_REQUEST['mes'] ) ? $_REQUEST['mes'] : '' );

if($mes>'0'){
	$hoy=date("Y-m-t", strtotime(date('Y-m-d')));
	$nuevafecha = strtotime ( '+'.$mes.' months' , strtotime ( $hoy) ) ;
	$nuevafecha= date('Y-m-d', $nuevafecha);
	
}else{
	$nuevafecha=date('Y-m-d');
}

$condicion = SetCondiciones();    
$query= GetQuery();

$queryObj->SetQuery($query);
$productos = $queryObj->ExecuteQuery('Multiple');

$productos=GetLotes($productos);

echo json_encode($productos);

function SetCondiciones(){
	global $nuevafecha,$bodega,$id_punto;

	$condicion=' WHERE I.Id_Bodega='.$bodega.'  AND I.Fecha_Vencimiento>"'.$nuevafecha.'" AND PPR.Id_Punto_Dispensacion='.$id_punto.' group by I.Id_Producto HAVING Cantidad_Disponible>0 '; 

	return $condicion; 
}

function GetQuery(){
	global $condicion;

	$query='SELECT (SELECT Nombre FROM Categoria WHERE Id_Categoria=PRD.Id_Categoria) as Categoria,  PRD.Id_Producto,ROUND(IFNULL(AVG(I.Costo),0)) as Precio, PRD.Embalaje,SUM(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as Cantidad_Disponible, CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida) as Nombre,  PRD.Nombre_Comercial, PRD.Laboratorio_Comercial,PRD.Laboratorio_Generico,PRD.Codigo_Cum, PRD.Cantidad_Presentacion, 0 as Seleccionado, (PPR.Cantidad) as Cantidad_Pendiente
	FROM Producto_Pendientes_Remision PPR
    INNER JOIN Inventario I ON PPR.Id_Producto=I.Id_Producto
	INNER JOIN Producto PRD
	On I.Id_Producto=PRD.Id_Producto  '.$condicion.'  ORDER BY Nombre_Comercial ASC ';

	return $query;
}
function GetLotes($productos){
	global  $queryObj,$nuevafecha,$bodega,$id_punto;

	$condicion=' WHERE I.Id_Bodega='.$bodega.'  AND I.Fecha_Vencimiento>"'.$nuevafecha.'" '; 

	$resultado=[];
	$having="  HAVING Cantidad>0 ORDER BY I.Fecha_Vencimiento ASC";
	$i=-1;
	$pos=0;
	foreach ($productos as  $value) {$i++;
		$query1="SELECT I.Id_Inventario, I.Id_Producto,I.Lote,(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as Cantidad,I.Fecha_Vencimiento,$value[Precio] as Precio, 0 as Cantidad_Seleccionada FROM Inventario I 
	   ".$condicion." AND I.Id_Producto= $value[Id_Producto] ". $having ;

	
		$queryObj->SetQuery($query1);
		$lotes=$queryObj->ExecuteQuery('Multiple');

		if(count($lotes)>0){ 
			$resultado[$pos]=$value;
			$resultado[$pos]['Lotes']=$lotes;
			$pos++;
		}else{
			unset($productos[$i]);
		}
	   
	}

	return $resultado;
}



?>