<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$punto = ( isset( $_REQUEST['Punto'] ) ? $_REQUEST['Punto'] : '' );

$datos = (array) json_decode($datos);

$oLista = new Lista("Reclamante");
$oLista->setRestrict("Id_Reclamante","=",$datos["Cedula"]);
$reclamantes = $oLista->getList();
unset($oLista);

if(count($reclamantes)>0){
	$nombre = $reclamantes[0]["Nombre"];
	
	$oLista = new Lista("Turnero");
	$oLista->setRestrict("Identificacion_Persona","=",$datos["Cedula"]);
	$oLista->setRestrict("Fecha","=",date("Y-m-d"));
	$oLista->setRestrict("Estado","=","Espera");
	$oLista->setRestrict("Id_Turneros","=",$punto);
	$turnos = $oLista->getList();
	unset($oLista);
	
	$oLista = new Lista("Turnero");
	$oLista->setRestrict("Identificacion_Persona","=",$datos["Cedula"]);
	$oLista->setRestrict("Fecha","=",date("Y-m-d"));
	$oLista->setRestrict("Estado","=","Auditoria");
	$oLista->setRestrict("Id_Turneros","=",$punto);
	$turnos2 = $oLista->getList();
	unset($oLista);

	if(count($turnos)>0||count($turnos2)>0){
		$final["Error"]="Si";
		$final["Tipo"] = "error"; 
		$final["Persona"]=$nombre;
		$final["Titulo"] = $nombre; 
		$final["Solicitar"] = "No";
		$final["Mensaje"]="Usted ya tiene un turno activo, por favor espere ser atendido.";
	}else{
	    /*
		$oItem = new complex("Turnero","Id_Turnero");
		$oItem->Identificacion_Persona=$datos["Cedula"];
		$oItem->Persona = $nombre;  
		$oItem->Id_Punto = $punto;
		$oItem->Fecha = date("Y-m-d");
		$oItem->Hora_Turno = date("H:i:s");
		$oItem->Estado = "Espera";
		$oItem->save();
		unset($oItem);
		*/
		$final["Error"]="No";
		$final["Tipo"] = "success"; 
		$final["Solicitar"] = "Si";
		$final["Persona"]=$nombre;
		$final["Titulo"] = $nombre; 
		$final["Cedula"] = $datos["Cedula"];
		$final["Mensaje"]="Su turno se ha asignado correctamente en la pantalla.";
	}
	
	
}else{
	$oLista = new Lista("Paciente");
	$oLista->setRestrict("Id_Paciente","=",$datos["Cedula"]);
	$personas = $oLista->getList();
	unset($oLista);
	
	if(count($personas)>0){
		$nombre = $personas[0]["Primer_Nombre"]." ".$personas[0]["Primer_Apellido"];
		
		$oItem = new complex("Reclamante","Id_Reclamante");
		$oItem->Id_Reclamante=$datos["Cedula"];
		$oItem->Nombre = $nombre;  
		$oItem->Fecha_Nacimiento = $personas[0]["Fecha_Nacimiento"];
		$oItem->save();
		unset($oItem);
		
		$oLista = new Lista("Turnero");
		$oLista->setRestrict("Identificacion_Persona","=",$datos["Cedula"]);
		$oLista->setRestrict("Fecha","=",date("Y-m-d"));
		$oLista->setRestrict("Estado","=","Espera");
		$oLista->setRestrict("Id_Turneros","=",$punto);
		$turnos = $oLista->getList();
		unset($oLista);
		
		$oLista = new Lista("Turnero");
    	$oLista->setRestrict("Identificacion_Persona","=",$datos["Cedula"]);
    	$oLista->setRestrict("Fecha","=",date("Y-m-d"));
    	$oLista->setRestrict("Estado","=","Auditoria");
    	$oLista->setRestrict("Id_Turneros","=",$punto);
    	$turnos2 = $oLista->getList();
    	unset($oLista);
	
		if(count($turnos)>0||count($turnos2)){
			$final["Error"]="Si";
			$final["Tipo"] = "error"; 
			$final["Persona"]=$nombre;
			$final["Titulo"] = $nombre; 
			$final["Cedula"] = $datos["Cedula"];
			$final["Solicitar"] = "No";
			$final["Mensaje"]="Usted ya tiene un turno activo, por favor espere ser atendido.";
		}else{
		    /*
			$oItem = new complex("Turnero","Id_Turnero");
			$oItem->Identificacion_Persona=$datos["Cedula"];
			$oItem->Persona = $nombre;  
			$oItem->Id_Punto = $punto;
			$oItem->Fecha = date("Y-m-d");
			$oItem->Hora_Turno = date("H:i:s");
			$oItem->Estado = "Espera";
			$oItem->save();
			unset($oItem); */
			
			$final["Error"]="No";
			$final["Tipo"] = "success"; 
			$final["Solicitar"] = "No";
			$final["Persona"]=$nombre;
			$final["Titulo"] = $nombre; 
			$final["Cedula"] = $datos["Cedula"];
			$final["Mensaje"]="Su turno se ha asignado correctamente en la pantalla.";
		}
	}else{
	    $nombre = $datos["Nombre1"]." ".$datos["Nombre2"]." ".$datos["Apellido1"]." ".$datos["Apellido2"]; 
		$oItem = new complex("Reclamante","Id_Reclamante");
		$oItem->Id_Reclamante=$datos["Cedula"];
		$oItem->Nombre = $nombre;  
		$oItem->Fecha_Nacimiento = substr($datos["Nacimiento"],0,4)."-".substr($datos["Nacimiento"],4,2)."-".substr($datos["Nacimiento"],6,2);
		$oItem->save();
		unset($oItem);
		
		$final["Error"]="No";
		$final["Tipo"]="success";
		$final["Solicitar"] = "Si";
		$final["Mensaje"] = "Se registro como reclamante";
		$final["Persona"] = $nombre;
		$final["Cedula"] = $datos["Cedula"];
	}
}

echo json_encode($final);
?>