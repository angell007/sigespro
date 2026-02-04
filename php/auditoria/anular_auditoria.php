<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$id_funcionario = ( isset( $_REQUEST['id_funcionario'] ) ? $_REQUEST['id_funcionario'] : '' );

$oItem = new complex('Auditoria','Id_Auditoria', $id);
$oItem->Estado = "Anulada";

if($id_funcionario!=''){
    $oItem->Funcionario_Anula = $id_funcionario;
}

$oItem->Fecha_Anulacion = date("Y-m-d H:i:s") ;
$id_auditoria = $oItem->Id_Auditoria;
$Id_Dispensacion_Mipres = $oItem->Id_Dispensacion_Mipres;

if($Id_Dispensacion_Mipres==''){
	
	// buscamos el Id_Dispensacion_Mipres
	$query = "SELECT A.Id_Auditoria, D.Id_Dispensacion, DM.Id_Dispensacion_Mipres 
	FROM Auditoria A
	INNER JOIN Dispensacion D ON A.Id_Dispensacion = D.Id_Dispensacion
	INNER JOIN Dispensacion_Mipres DM ON D.Id_Dispensacion_Mipres = DM.Id_Dispensacion_Mipres
	WHERE A.Id_Auditoria =".$id_auditoria;
	$oCon= new consulta();
	$oCon->setQuery($query);
	$Id_Disp_Mipres = $oCon->getData();
	unset($oCon);

	$Id_Dispensacion_Mipres = $Id_Disp_Mipres["Id_Dispensacion_Mipres"];

	// Actualizamos la Auditoria para agregarle el Id_Dispensacion_Mipres
	// luego será Necesario para Cambiar el estado a Pendiente.
	if($Id_Dispensacion_Mipres!=''){
	    $query = "UPDATE Auditoria SET Id_Dispensacion_Mipres = '".$Id_Dispensacion_Mipres."' WHERE Id_Auditoria = ".$id_auditoria;
    	$oCon= new consulta();
    	$oCon->setQuery($query);
    	$resultado = $oCon->createData();
    	unset($oCon);
    	
    	CambiarEstadoDispensacionMipres($Id_Dispensacion_Mipres);
	}
	
}

$oItem->save();
unset($oItem);

if ($id_auditoria) {
	EliminarAuditoriaTurnero($id_auditoria);

    $resultado['mensaje'] = "Anulada exitosamente!";
    $resultado['tipo'] = "success";
} else {
    $resultado['mensaje'] = "Ha ocurrido un error de conexión. Por favor intentelo de nuevo!";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);

// funcion que elimina la auditoria del turnero.
function EliminarAuditoriaTurnero($id_auditoria){

	$query_update = 'UPDATE Turnero SET Estado = "Anulado" WHERE Id_Auditoria = '.$id_auditoria;
	$oCon = new Consulta();
	$oCon->setQuery($query_update);
	$oCon->deleteData();
	unset($oCon);

}

function CambiarEstadoDispensacionMipres($Id_Dispensacion_Mipres){

	$query = "UPDATE Dispensacion_Mipres SET Estado = 'Pendiente' WHERE Id_Dispensacion_Mipres = '".$Id_Dispensacion_Mipres."'";	
	$oCon= new consulta();
	$oCon->setQuery($query);
	$resultado = $oCon->createData();
	unset($oCon);

}

?>