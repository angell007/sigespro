<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = isset($_REQUEST['id_producto']) ? $_REQUEST['id_producto'] : '';
$limit = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : '';
$cond='';
if(!$limit){
    $cond= "AND AR.Id_Bodega_Nuevo is not null";
	$limit = 3;
}
$query="SELECT AR.Id_Acta_Recepcion as Id_Acta,
	AR.Fecha_Creacion as Fecha, 
	AR.Codigo as Codigo_Acta, 
	SUM(PAR.Cantidad) as Cantidad, PAR.Precio, OC.Codigo as Codigo_Compra_N, OC.Id_Orden_Compra_Nacional as Id_Compra_N, OCI.Codigo as Codigo_Compra_I, OCI.Id_Orden_Compra_Internacional as Id_Compra_I, P.Nombre as Proveedor,

(SELECT CONCAT(F.Nombres,' ',F.Apellidos) FROM Funcionario F WHERE F.Identificacion_Funcionario=AR.Identificacion_Funcionario) as Funcionario

FROM Producto_Acta_Recepcion PAR 

INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion
LEFT JOIN Orden_Compra_Nacional OC ON OC.Id_Orden_Compra_Nacional = AR.Id_Orden_Compra_Nacional
LEFT JOIN Orden_Compra_Internacional OCI ON OCI.Id_Orden_Compra_Internacional = AR.Id_Orden_Compra_Internacional
INNER JOIN Proveedor P ON P.Id_Proveedor = AR.Id_Proveedor

WHERE PAR.Id_Producto =$id AND (AR.Estado = 'Aprobada' OR AR.Estado = 'Acomodada')
$cond
GROUP BY AR.Id_Acta_Recepcion
Order BY AR.Fecha_Creacion DESC LIMIT $limit ";

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);


echo json_encode($resultado);

?>