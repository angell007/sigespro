<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.paginacion.php');

$condicion = '';
$ondicion_fecha = '';
$pagina = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '1' );

if(isset($_REQUEST['fecha_inicio']) && $_REQUEST['fecha_inicio'] != ''){
  $condicion_fecha = ' AND CV.Fecha_Documento BETWEEN "'.$_REQUEST['fecha_inicio'].'" AND "'.$_REQUEST['fecha_fin'].'"';
}

#FILTRO POR CODIGO
if (isset($_REQUEST['codigo']) && $_REQUEST['codigo'] != "") {
  $condicion .= " AND CV.Codigo LIKE '%".$_REQUEST['codigo']."%'";
}

#FILTRO POR CLIENTE
if (isset($_REQUEST['nombre_cliente']) && $_REQUEST['nombre_cliente'] != "") {
  $condicion .= " AND C.Nombre LIKE '%".$_REQUEST['nombre_cliente']."%'";
}

#FILTRO POR OBSERVACION
if (isset($_REQUEST['observacion']) && $_REQUEST['observacion'] != "") {
  $condicion .= " AND CV.Observacion_Cotizacion_Venta LIKE '%".$_REQUEST['observacion']."%'";
}

#FILTRO POR ESTADO

if (isset($_REQUEST['estado']) && $_REQUEST['estado'] != "") {
  $condicion .= " AND CV.Estado_Cotizacion_Venta='".$_REQUEST['estado']."'";
}
if (isset($_REQUEST['Orden_Pedido']) && $_REQUEST['Orden_Pedido'] != "") {
  $condicion .= " AND CONCAT(O.Prefijo, O.Id_Orden_Pedido) like '%$_REQUEST[Orden_Pedido]%'";
}

$query_paginacion = 'SELECT 
            COUNT(*) AS Total
          FROM Cotizacion_Venta CV 
          INNER JOIN Cliente C ON CV.Id_Cliente = C.Id_Cliente
          LEFT JOIN Orden_Pedido O ON O.Id_Cotizacion_Venta = CV.Id_Cotizacion_Venta
          WHERE 1
          '.$condicion_fecha
          .$condicion;

$query = 'SELECT 
            CV.Fecha_Documento as Fecha , 
            CV.Codigo as Codigo, 
            CV.Id_Cotizacion_Venta as IdCV,
            Estado_Cotizacion_Venta as Estado, 
            CV.Observacion_Cotizacion_Venta as Observacion,
            CONCAT(O.Prefijo, O.Id_Orden_Pedido) as Orden_Pedido,
            C.Nombre as NombreCliente
          FROM Cotizacion_Venta CV
          INNER JOIN Cliente C ON CV.Id_Cliente = C.Id_Cliente
          LEFT JOIN Orden_Pedido O ON O.Id_Cotizacion_Venta = CV.Id_Cotizacion_Venta
          WHERE 1
          '
          .$condicion_fecha
          .$condicion
          .' ORDER BY CV.Fecha_Documento DESC';

$paginationObj = new PaginacionData(20, $query_paginacion, $pagina);
$queryObj = new QueryBaseDatos($query);
$result = $queryObj->Consultar('Multiple', true, $paginationObj);

echo json_encode($result);


?>