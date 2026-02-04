<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$fechas = $_REQUEST['Fechas'] ? $_REQUEST['Fechas'] : '';
$id = $_REQUEST['id'] ? $_REQUEST['id'] : '';
$tipo = $_REQUEST['tipo'] ? $_REQUEST['tipo'] : '';
$cliente = $_REQUEST['cliente'] ? $_REQUEST['cliente'] : '';
$condiciones = [];


if (isset($_REQUEST['fini']) && isset($_REQUEST['ffin']) && !$fechas) {
	$fechas = "$_REQUEST[fini] - $_REQUEST[ffin]";
}
if ($fechas) {
	$fechas = explode(' - ', $fechas);
	array_push($condiciones, "DATE(C.Fecha_Documento) BETWEEN '$fechas[0]'AND '$fechas[1]'");
}
if ($id) {
	array_push($condiciones, "C.Id_Cotizacion_Venta = $id");
}
if ($cliente) {
	array_push($condiciones, "C.Id_Cliente = $cliente");
}

$condicion = implode(' AND ', $condiciones);
$condicion = $condicion ? "WHERE $condicion" : '';
$query = '';
if ($tipo == 'Pendientes_Pedido') {
	$condicion = str_replace('C.', 'OP.', $condicion);
	$condicion = str_replace('Fecha_Documento', 'Fecha', $condicion);
	$query = "SELECT 
			Date(OP.Fecha) as 'Fecha Adjudicado',
			CL.Id_Cliente AS NIT,
			COALESCE(CL.Nombre, CL.Razon_Social) AS Cliente,
			CONCAT('Lista ', CL.Id_Lista_Ganancia) AS Lista_Cliente,
			POP.Estado AS Estado_Producto_Pedido,
			Concat(OP.Prefijo, OP.Id_Orden_Pedido) AS 'Orden Pedido',
			CONCAT_WS(' ', P.Nombre_Comercial, P.Presentacion, P.Concentracion, P.Cantidad, P.Unidad_Medida ) as Producto,
			P.Laboratorio_Comercial as Laboratorio,
			P.Codigo_Cum, 
			POP.Cantidad AS Cantidad_Aceptada,
			IFNULL(SUM(PR.Cantidad), '') AS Cantidad_Enviada,
			if(POP.Estado='Activo', (Ifnull(POP.Cantidad, 0)-IFNULL(SUM(PR.Cantidad), 0)), 0) as Pendiente,
			POP.Precio_Orden AS Precio_Cotizado,
			IF(POP.Precio = 0, (SELECT Precio from Producto_Lista_Ganancia Where Cum = P.Codigo_Cum limit 1), POP.Precio) AS Precio_Lista,
			POP.Observacion,
			CONCAT_WS(' ', FC.Nombres, FC.Apellidos) AS 'Funcionario Pedido', 
			GROUP_CONCAT(distinct PR.Codigo) AS Remisiones,
			Date(PR.Fecha) as Fecha_Envio

			FROM Producto_Orden_Pedido POP
			INNER JOIN Orden_Pedido OP ON POP.Id_Orden_Pedido = OP.Id_Orden_Pedido 
			INNER JOIN Producto P ON P.Id_Producto = POP.Id_Producto
			INNER JOIN Cliente CL ON CL.Id_Cliente = OP.Id_Cliente
			LEFT JOIN Funcionario FC ON FC.Identificacion_Funcionario = OP.Identificacion_Funcionario
			LEFT JOIN (
				SELECT PR.*, R.Id_Orden_Pedido, R.Identificacion_Funcionario, R.Fecha, R.Codigo
				from Remision R 
				LEFT JOIN Producto_Remision PR ON PR.Id_Remision = R.Id_Remision 
			) PR ON PR.Id_Orden_Pedido = OP.Id_Orden_Pedido  AND PR.Id_Producto = POP.Id_Producto

			$condicion

			GROUP BY POP.Id_Producto_Orden_Pedido
			";

} else {
	$query = "SELECT 
			CL.Id_Cliente AS NIT,
			COALESCE(CL.Nombre, CL.Razon_Social) AS Cliente,
			CONCAT('Lista ', CL.Id_Lista_Ganancia) AS Lista_Cliente,
			C.Estado_Cotizacion_Venta,
			Date(C.Fecha_Documento) as 'Fecha Cotizacion',
			C.Codigo AS Cotizacion,
			Concat(OP.Prefijo, OP.Id_Orden_Pedido) AS 'Orden Pedido',
			CONCAT_WS(' ', P.Nombre_Comercial, P.Presentacion, P.Concentracion, P.Cantidad, P.Unidad_Medida ) as Producto,
			P.Laboratorio_Comercial as Laboratorio,
			P.Codigo_Cum, 
			PC.Precio_Venta AS Precio_Cotizado,
			PC.Cantidad AS Cantidad_Cotizacion,
			Ifnull(OP.Cantidad, '') AS Cantidad_Aceptada,
			IFNULL(SUM(PR.Cantidad), '') AS Cantidad_Enviada,
			if(OP.Estado='Activo', (Ifnull(OP.Cantidad, 0)-IFNULL(SUM(PR.Cantidad), 0)), 0) as Pendiente,
			Date(OP.Fecha) as 'Fecha Adjudicado',
			IFNULL(OP.Precio, (SELECT Precio from Producto_Lista_Ganancia Where Cum = P.Codigo_Cum limit 1) ) AS Precio_Lista,
			OP.Observacion,
			CONCAT_WS(' ', FC.Nombres, FC.Apellidos) AS 'Funcionario Cotiza', 
			GROUP_CONCAT(distinct PR.Codigo) AS Remisiones,
			Date(PR.Fecha) as Fecha_Envio,
			CONCAT_WS(' ', FR.Nombres, FR.Apellidos) AS 'Funcionario Envia'



			FROM Producto_Cotizacion_Venta PC
			INNER JOIN Producto P ON P.Id_Producto = PC.Id_Producto
			INNER JOIN Cotizacion_Venta C ON C.Id_Cotizacion_Venta = PC.Id_Cotizacion_Venta
			INNER JOIN Cliente CL ON CL.Id_Cliente = C.Id_Cliente

			LEFT JOIN (
				SELECT POP.*, OP.Prefijo, OP.Id_Cotizacion_Venta, OP.Fecha
				From Orden_Pedido OP
				Inner JOIN Producto_Orden_Pedido POP ON POP.Id_Orden_Pedido = OP.Id_Orden_Pedido 
			) OP on OP.Id_Producto = PC.Id_Producto  and OP.Id_Cotizacion_Venta = C.Id_Cotizacion_Venta
			LEFT JOIN (
				SELECT PR.*, R.Id_Orden_Pedido, R.Identificacion_Funcionario, R.Fecha, R.Codigo
				from Remision R 
				Inner JOIN Producto_Remision PR ON PR.Id_Remision = R.Id_Remision 
				Where R.Estado != 'Anulada'
			) PR ON PR.Id_Orden_Pedido = OP.Id_Orden_Pedido  AND PR.Id_Producto = PC.Id_Producto
			LEFT JOIN Funcionario FC ON FC.Identificacion_Funcionario = C.Id_Funcionario
			LEFT JOIN Funcionario FR ON FR.Identificacion_Funcionario = PR.Identificacion_Funcionario

			$condicion

			GROUP BY PC.Id_Producto_Cotizacion_Venta
			";
}
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$lista = $oCon->getData();
unset($oCon);

// echo json_encode($lista); exit;

if ($tipo) {
	armararchivo($lista, $tipo);
} else {
	foreach ($lista as $i => $cotizacion) {
		foreach ($cotizacion as $index => $value) {
			if (strpos($index, 'Fecha') === false && !strpos($index, 'Cum'))
				$cotizacion[$index] = (float)$value ? (float)$value : $value;
		}
		$lista[$i] = $cotizacion;
	}

	echo json_encode($lista);
}


function armararchivo($resultado, $tipo)
{
	$encabezado = $resultado[0];

	$contenido = '
		<table border="1" style="border-collapse: collapse;">
    	<thead>';
	$contenido .= '<tr>';
	foreach ($encabezado as $nombre => $val) {
		$contenido .= "<th>$nombre</th>";
	}
	$contenido .= '</tr>';

	$contenido .= '
		</thead>
		<tbody>';

	foreach ($resultado as $key => $value) {
		$contenido .= '<tr>';
		foreach ($value as $valor) {
			$contenido .= "<td>$valor</td>";
		}
		$contenido .= '</tr>';
	}

	$contenido .= '
		</tbody>
		</table>';
	header('Content-Type: text/html; charset=utf-8');
	if (!$_REQUEST['ver']) {
		header('Content-Type: application/vnd.ms-excel');
		header("Content-Disposition: attachment;filename=$tipo.xls");
		header('Cache-Control: max-age=0');
	}
	echo $contenido;
}
