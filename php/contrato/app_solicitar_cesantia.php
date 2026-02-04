<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
// include_once('../../class/class.lista.php');
// include_once('../../class/class.complex.php');
// include_once('../../class/class.consulta.php');
include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.guardar_archivos.php');

//Objeto de la clase que almacena los archivos    
$storer = new FileStorer();

$queryObj = new QueryBaseDatos();

$concepto = ( isset( $_REQUEST['concepto'] ) ? $_REQUEST['concepto'] : '' );
$motivo = ( isset( $_REQUEST['motivo'] ) ? $_REQUEST['motivo'] : '' );
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

$anio_actual = date('Y');

$query = "
	SELECT
		Id_Cesantia
	FROM Cesantia
	WHERE
		Identificacion_Funcionario = $funcionario
		AND YEAR(Fecha) = $anio_actual
		AND Estado <> 'Rechazada'";

$queryObj->SetQuery($query);
$cesantia_anual = $queryObj->ExecuteQuery('simple');

if ($cesantia_anual !== false) {
	$resultado['mensaje'] = "Ya ha realizado su solicitud de cesantia anual!";
    $resultado['tipo'] = "warning";
}else{
	$nombre_archivo = '';
	if (!empty($_FILES['archivo']['name'])){
	    //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
	    $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', "DOCUMENTOS/$funcionario/");
	    $nombre_archivo = $nombre_archivo[0];
	}

	$oItem = new complex("Cesantia","Id_Cesantia");
	$oItem->Concepto=$concepto;
	$oItem->Motivo=$motivo;
	$oItem->Identificacion_Funcionario=$funcionario;
	$oItem->Soporte=$nombre_archivo;
	$oItem->save();
	$id_certi = $oItem->getId();
	unset($oItem);

	if($id_certi != ""){
	    $resultado['mensaje'] = "Se ha generado correctamente la Solicitud de Cesantías, estará disponible 30 dias";
	    $resultado['tipo'] = "success";
	}else{
	    $resultado['mensaje'] = "ha ocurrido un error guardando la informacion, por favor verifique";
	    $resultado['tipo'] = "error";
	}
}




echo json_encode($resultado);
?>