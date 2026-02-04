<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query='SELECT TP.*
FROM Tipo_Servicio TP 
WHERE TP.Id_Tipo_Servicio ='.$id; 

$oCon= new consulta();
$oCon->setQuery($query);
$tipo= $oCon->getData();
unset($oCon);

$query='SELECT TS.*
FROM Tipo_Soporte TS
WHERE TS.Id_Tipo_Servicio ='.$id; 

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$soporte= $oCon->getData();
unset($oCon);

$query='SELECT 	Id_Campos_Tipo_Servicio,Nombre,Tipo,Id_Tipo_Servicio,Tipo_Campo,Longitud,Requerido,Modulo, Nombre AS Nombre_Original, 1 AS Edicion, Estado
FROM Campos_Tipo_Servicio
WHERE Tipo_Campo="Producto" AND  Id_Tipo_Servicio ='.$id; 

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$Campo_producto= $oCon->getData();
unset($oCon);

$query='SELECT 	Id_Campos_Tipo_Servicio,Nombre,Tipo,Id_Tipo_Servicio,Tipo_Campo,Longitud, Requerido,Modulo, Nombre AS Nombre_Original, 1 AS Edicion,Estado
FROM Campos_Tipo_Servicio
WHERE Tipo_Campo="Cabecera" AND  Id_Tipo_Servicio ='.$id; 

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$Campo_cabecera= $oCon->getData();
unset($oCon);

foreach ($Campo_cabecera as $key => $value) {
	foreach ($value as $campo => $val) {
		if ($campo == 'Edicion') {
			$Campo_cabecera[$key]['Edicion'] = intval($val);		
		}
	
	}	
}

foreach ($Campo_producto as $key => $value) {
	foreach ($value as $campo => $val) {
		if ($campo == 'Edicion') {
			$Campo_producto[$key]['Edicion'] = intval($val);		
		}
	}
	if($value['Fecha_Formula']=='Si'){		
			$Campo_producto[$key]['Display'] = 'true';				
	}else{
		$Campo_producto[$key]['Display'] = 'false';		
	}	
}

$resultado['Tipo_Servicio']=$tipo;
$resultado['Soportes']=$soporte;
$resultado['Campos_Producto']=$Campo_producto;
$resultado['Campos_Cabecera']=$Campo_cabecera;

echo json_encode($resultado);

?>