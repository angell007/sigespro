<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.http_response.php');


$id_paciente = isset($_REQUEST['Id_Paciente']) ? $_REQUEST['Id_Paciente'] : '';
$id_dispensacion = isset($_REQUEST['Id_Dispensacion']) ? $_REQUEST['Id_Dispensacion'] : '';


$query = 'SELECT
	    P.Id_Punto_Dispensacion as Id_Paciente_Direccion, 
	    "" as Id_Paciente,
	    "" as Id_Departamento,
	    "" as Id_Municipio,
	    P.Direccion as Direccion1,
	    "" as Direccion2,
	    "" as Direccion3,
	    "" as Direccion4,
	    "" as Direccion5,
	    "" as Observacion,   
	    "Punto Dispensacion" as Tipo_Direccion,
	    False as Selected  
		  FROM Punto_Dispensacion P
		  INNER JOIN Dispensacion D ON D.Id_Punto_Dispensacion = P.Id_Punto_Dispensacion
		    
		  WHERE D.Id_Dispensacion = ' . $id_dispensacion . '
	    UNION ALL

		SELECT
	    B.Id_Bodega_Nuevo as Id_Paciente_Direccion, 
	    "" as Id_Paciente,
	    "" as Id_Departamento,
	    "" as Id_Municipio,
	    B.Direccion as Direccion1,
	    "" as Direccion2,
	    "" as Direccion3,
	    "" as Direccion4,
	    "" as Direccion5,
	    "" as Observacion,   
	    "Bodega" as Tipo_Direccion,
	    False as Selected  
		  FROM Bodega_Nuevo B
		  INNER JOIN Punto_Dispensacion P ON P.Id_Bodega_Despacho =  B.Id_Bodega_Nuevo
		  INNER JOIN Dispensacion D ON D.Id_Punto_Dispensacion = P.Id_Punto_Dispensacion
		    
		  WHERE D.Id_Dispensacion = ' . $id_dispensacion . '


		UNION ALL
		  SELECT * ,  "Paciente" as Tipo_Direccion , False as Selected
	      FROM Paciente_Direccion WHERE Id_Paciente = ' . $id_paciente;
$oCon = new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$direcciones = $oCon->getData();
unset($oCon);

$query = "Select Id_Direccion , Tipo_Direccion, Id_Dispensacion_Domicilio FROM Dispensacion_Domicilio
	WHERE Id_Dispensacion = " . $id_dispensacion;

$oCon = new consulta();
$oCon->setQuery($query);
$domicilio = $oCon->getData();

if ($domicilio) {
	foreach ($direcciones as $key => &$dir) {


		if (
			$domicilio['Tipo_Direccion'] == $dir['Tipo_Direccion']
			&& $dir['Id_Paciente_Direccion'] == $domicilio['Id_Direccion']
		) {
			$dir['Selected'] = true;
		}
		if ($dir['Tipo_Direccion'] == "Paciente") {

			$query = 'SELECT * , Id_Municipio as value, 
		Nombre as label FROM Municipio WHERE Id_Departamento =' . $dir['Id_Departamento'];
			$oCon = new consulta();
			$oCon->setTipo('Multiple');
			$oCon->setQuery($query);
			$direcciones[$key]['Municipios'] = $oCon->getData();
		}
	}
}
$data = [];
$data['Direcciones'] = $direcciones;
$data['Id_Dispensacion_Domicilio'] = $domicilio ? $domicilio['Id_Dispensacion_Domicilio'] : "";
echo json_encode($data);
