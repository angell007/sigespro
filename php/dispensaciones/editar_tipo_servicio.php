<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.querybasedatos.php');
// include_once('../../class/class.lista.php');
// include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
// include_once('../../class/class.portal_clientes.php'); //DESCOMENTAR ESTA LINEA PARA GUARDAR EN EL PORTAL CLIENTES


$queryObj = new QueryBaseDatos();
// $portalClientes = new PortalCliente($queryObj); //DESCOMENTAR ESTA LINEA PARA GUARDAR EN EL PORTAL CLIENTES

$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
$dis = ( isset( $_REQUEST['dis'] ) ? $_REQUEST['dis'] : '' );
$funcionario = ( isset( $_REQUEST['func'] ) ? $_REQUEST['func'] : '' );

$oItem = new complex('Dispensacion', 'Id_Dispensacion', $dis);
$dispensacion=$oItem->getData();
$tipo_original = GetServicio($dispensacion['Id_Tipo_Servicio']);
$servicio_nuevo=GetServicio($tipo);
$oItem->Id_Servicio=$servicio_nuevo['Id_Servicio'];
$oItem->Id_Tipo_Servicio=$tipo;

/* ----- */

// Actualizamos el servicio y tipo de servicio si la auditoria tiene una Dispensacion
$query='UPDATE Auditoria SET Id_Tipo_Servicio='.$tipo.', 
Id_Servicio='.$servicio_nuevo['Id_Servicio'].' 
WHERE Id_Dispensacion='.$dis;

$oCon= new consulta();
$oCon->setQuery($query);
$resultado= $oCon->createData();
unset($oCon);

/* ----- */

if ($tipo_original['Nombre'] == 'Pos-CAPITA') { // SOLO SE DESLIGA DE LA FACTURA CUANDO INICIALMENTE EL TIPO DE SERVICIO ERA CAPITA.
    $oItem->Estado_Facturacion = 'Sin Facturar';
    $oItem->Id_Factura = '0';
}
$oItem->save();
unset($oItem);

$oItem = new complex('Actividades_Dispensacion', 'Id_Actividades_Dispensacion');
$oItem->Id_Dispensacion=$dis;
$oItem->Identificacion_Funcionario=$funcionario;
$oItem->Detalle="Se cambio de ".$tipo_original['Nombre']." a ".$servicio_nuevo['Nombre'];

$oItem->Estado="Edicion";
$oItem->save();
unset($oItem);

$resultado['status'] = 'success';

// GuardarDispensacionPortalClientes($dis); //DESCOMENTAR ESTA LINEA PARA GUARDAR EN EL PORTAL CLIENTES

echo json_encode($resultado);

function ObtenerNombre($id){
    $query="SELECT Nombre FROM Tipo_Servicio WHERE Id_Tipo_Servicio=".$id;
    $oCon= new consulta();
	$oCon->setQuery($query);
	$nombre= $oCon->getData();
    unset($oCon);
    return $nombre['Nombre'];
}

function GetServicio($id){

    $query="SELECT T.Id_Servicio, CONCAT(S.Nombre,'-',T.Nombre) as Nombre FROM Tipo_Servicio T INNER JOIN Servicio S on T.Id_Servicio=S.Id_Servicio WHERE T.Id_Tipo_Servicio=".$id;

    $oCon= new consulta();
	$oCon->setQuery($query);
	$servicio= $oCon->getData();
    unset($oCon);

    return $servicio;
}

// DESCOMENTAR ESTe metodo PARA GUARDAR EN EL PORTAL CLIENTES
// function GuardarDispensacionPortalClientes($idDis){
//   global $portalClientes;
//   $response = $portalClientes->ActualizarDispensacion($idDis);
// }
?>