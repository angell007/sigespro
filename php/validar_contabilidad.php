<?php
date_default_timezone_set('America/Bogota');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');
ini_set("memory_limit", "32000M");
ini_set('max_execution_time', 0);


$fi = (isset($_REQUEST['fini']) ? $_REQUEST['fini'] : '2023-01-01');
$ff = (isset($_REQUEST['ffin']) ? $_REQUEST['ffin'] : '2023-12-31');
$cum = (isset($_REQUEST['cum']) ? $_REQUEST['cum'] : '');

armararchivo(consultarKardex($fi, $ff, $cum));

function armararchivo($resultado)
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
	
	echo $contenido;

}
function consultarKardex($fi, $ff, $cum){

	$queryCompras2 ="SELECT 
					-- P.Codigo_Cum, 
					DOC.Codigo as Documento, 
					MC.Fecha_Movimiento,
					PC.Codigo as Cuenta, 
					PC.Nombre as NomCuenta,
					MC.Debe as Debito, 
					MC.Haber as Credito,
					DOC.Cantidad,
					DOC.Costo, 
					DOC.Cantidad*DOC.Costo as Total_Costo

					From Producto P 
					Inner Join (
						(
							-- Select PA.Id_Producto, 
							-- 	A.Codigo, 
							-- 	PA.Cantidad*PA.Precio as Subtotal, 
							-- 	A.Id_Acta_Recepcion As Id_Registro,
							-- 	'15' as Modulo
							-- From Acta_Recepcion A 
							-- Inner Join Producto_Acta_Recepcion PA on PA.Id_Acta_Recepcion = A.Id_Acta_Recepcion
							-- Inner Join Producto P on P.Id_Producto = PA.Id_Producto
							-- Where DATE(A.Fecha_Creacion) between '$fi' and '$ff'
							-- AND P.Codigo_Cum ='$cum'
						-- ) UNION ALL (
							Select 
								PA.Id_Producto, 
								A.Codigo, 
								SUM(PA.Cantidad) as Cantidad, SUM(PD.Costo) as Costo, 
								A.Id_Factura As Id_Registro,
								'17' as Modulo
							From Factura A
							Inner Join Producto_Factura PA on PA.Id_Factura = A.Id_Factura
							Inner Join Producto P on P.Id_Producto = PA.Id_Producto
							Inner Join Dispensacion D on D.Id_Factura = A.Id_Factura
							Inner Join Producto_Dispensacion PD on PD.Id_Dispensacion = D.Id_Dispensacion and P.Id_Producto = PD.Id_Producto
							
							Where DATE(A.Fecha_Documento) between '$fi' and '$ff'
							-- AND P.Codigo_Cum LIKE '$cum'
							GROUP BY A.Id_Factura
						-- ) UNION ALL (
						-- 	Select 
						-- 		PA.Id_Producto, 
						-- 		A.Codigo, 
						-- 		PA.Cantidad*PA.Precio_Venta as Subtotal, 
						-- 		A.Id_Factura_Venta As Id_Registro,
						-- 		'2' as Modulo
						-- 	From Factura_Venta A
						-- 	Inner Join Producto_Factura_Venta PA on PA.Id_Factura_Venta = A.Id_Factura_Venta
						-- 	Inner Join Producto P on P.Id_Producto = PA.Id_Producto
						-- 	Where DATE(A.Fecha_Documento) between '$fi' and '$ff'
						-- 	AND P.Codigo_Cum ='$cum'
						
						-- ) UNION ALL (
						-- 	Select 
						-- 		PA.Id_Producto, 
						-- 		A.Codigo, 
						-- 		PA.Cantidad*PA.Costo as Subtotal, 
						-- 		A.Id_Ajuste_Individual As Id_Registro,
						-- 		'8' as Modulo
						-- 	From Ajuste_Individual A
						-- 	Inner Join Producto_Ajuste_Individual PA on PA.Id_Ajuste_Individual = A.Id_Ajuste_Individual
						-- 	Inner Join Producto P on P.Id_Producto = PA.Id_Producto
						-- 	Where DATE(A.Fecha) between '$fi' and '$ff'
						-- 	AND P.Codigo_Cum ='$cum'
						-- ) UNION ALL (

						-- 	Select 
						-- 		PA.Id_Producto, 
						-- 		A.Codigo, 
						-- 		PA.Cantidad*PA.Precio_Venta as Subtotal, 
						-- 		A.Id_Nota_Credito As Id_Registro,
						-- 		'5' as Modulo
						-- 	From Nota_Credito A
						-- 	Inner Join Producto_Nota_Credito PA on PA.Id_Nota_Credito = A.Id_Nota_Credito
						-- 	Inner Join Producto P on P.Id_Producto = PA.Id_Producto
						-- 	Where DATE(A.Fecha) between '$fi' and '$ff'
						-- 	AND P.Codigo_Cum = '$cum'
						-- ) UNION ALL (

						-- 	Select 
						-- 		PA.Id_Producto, 
						-- 		A.Codigo, 
						-- 		PA.Cantidad*PA.Precio_Nota_Credito as Subtotal, 
						-- 		A.Id_Nota_Credito_Global As Id_Registro,
						-- 		'5' as Modulo
						-- 	From Nota_Credito_Global A
						-- 	Inner Join Producto_Nota_Credito_Global PA on PA.Id_Nota_Credito_Global = A.Id_Nota_Credito_Global
						-- 	Inner Join Producto P on P.Id_Producto = PA.Id_Producto
						-- 	Where DATE(A.Fecha) between '$fi' and '$ff'
						-- 	AND P.Codigo_Cum = '$cum'
						)


					)DOC on DOC.Id_Producto = P.Id_Producto
					Inner Join Movimiento_Contable MC on MC.Id_Registro_Modulo = DOC.Id_Registro and MC.Id_Modulo = DOC.Modulo
					Inner Join Plan_Cuentas PC on PC.Id_Plan_Cuentas = MC.Id_Plan_Cuenta
					Where PC.Codigo LIKE '6135%'
					ORDER BY Codigo_Cum, MC.Fecha_Movimiento
					";


	$query = "$queryCompras2";

	$oCon = new consulta();
	$oCon->setQuery($query); 
	$oCon->setTipo("Multiple"); 
	
	return $oCon->getData();
}