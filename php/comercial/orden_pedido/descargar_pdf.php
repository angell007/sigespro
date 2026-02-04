<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
require_once('../../../class/html2pdf.class.php');


$condicion = '';
$id = isset($_REQUEST['id']) && $_REQUEST['id'] != '' ? $_REQUEST['id'] : false;


if(!$id){
    echo json_encode(['debe ingresar el id']);exit;
}

$op = (getDatos($id));

armarPdf($op);
/* FUNCIONES BASICAS */
function fecha($str)
{
	$parts = explode(" ",$str);
	$date = explode("-",$parts[0]);
	return $date[2] . "/". $date[1] ."/". $date[0];
}

function armarPdf($op){
	
	/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
	$oItem = new complex('Configuracion',"Id_Configuracion",1);
	$config = $oItem->getData();
	unset($oItem);
	/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */

	ob_start(); // Se Inicializa el gestor de PDF

		
	$cabecera = $op['cabecera'];
	$prods = $op['productosOrden'];
	$codigos ='
	<h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">'.$cabecera["Codigo"].'</h3>
	<h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.fecha($cabecera["Fecha"]).'</h5>';

	$observaciones = $cabecera["Observaciones"] == "" ? "Sin Observaciones" : $cabecera["Observaciones"];

	$contenido = '<table style="background:#E6E6E6; font-size: 10px" cellpadding="0" >
	<tr style="background:#E6E6E6">
		<td style="width:335px;padding:10px;padding-top:0">
			<strong>CLIENTE: </strong> '.$cabecera['Nombre_Cliente'].'
		</td>
		<td style="width:335px;padding:10px;padding-top:0">
			<strong>FECHA PROBABLE DE ENTREGA: </strong> '.fecha($cabecera['Fecha_Probable_Entrega']).'
		</td>
		
	</tr>
	<tr style="background:#E6E6E6">
		<td style="width:335px;padding:10px;padding-top:0">
			<strong>RESPONSABLE: </strong> '.strtoupper($cabecera['Nombre_Funcionario']).'
		</td>
		<td style="width:335px;padding:10px;padding-top:0">
			<strong>ORDEN COMPRA: </strong> '.$cabecera['Orden_Compra_Cliente'].'
		</td>
		
	</tr>
</table>
<table style="margin-top:10px">
	<tr>
		<td style="font-size:10px;width:710px;background:#e9eef0;border-radius:5px;padding:8px;">
			<strong>Observaciones</strong><br>
			'.$observaciones.'
		</td>
	</tr>
</table>
<table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
	<tr>
		
		<td style="width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
			Subcategoria
		</td>
		<td style="width:230px;max-width:230px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
			Producto
		</td>
		<td style="width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
		   Codigo CUM
		</td>
		<td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
			Laboratorio
		</td>
		<td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
			Cantidad
		</td>
		<td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
			Precio
		</td>
		<td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
			Impuesto
		</td>
		<td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
			Subtotal
		</td>
	</tr>';
	$max = 0;
	$subtotal=0;
	$iva = 0;
	$total =0;
		foreach($prods as $prod){  $max++;
			$sbttl = $prod['Cantidad']* $prod['Precio_Orden'];
			$contenido .='<tr>
				<td style="width:80px;font-size:8px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'. $prod["Subcategoria"].'</td>
				<td style="padding:3px 2px;width:230px;max-width:230px;font-size:9px;text-align:left;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;"><b>'.$prod["Nombre_Comercial"].'</b>
				<p style="color:gray; font-size:8px; margin:0; padding:0">' .$prod["Nombre"].'</p></td>
				
				<td style="width:80px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Codigo_Cum"].'</td>
				<td style="width:80px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.($prod["Laboratorio_Generico"]?$prod["Laboratorio_Generico"]:$prod["Laboratorio_Comercial"]).'</td>
				<td style="width:50px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Cantidad"].'</td>
				<td style="width:50px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">$ '.number_format($prod["Precio_Orden"],2,",",".").'</td>
				<td style="width:50px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Impuesto"].' % </td>
				<td style="width:50px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">$ '.number_format($sbttl,2,",",".").'</td>
			</tr>';

			$impuesto = str_replace("%","",$prod["Impuesto"]); // Quitar el signo de porcentaje
			$subt = $prod["Cantidad"] * $prod["Precio_Orden"];
			$subtotal += $subt;
			$iva_t = ($subt) * ($impuesto/100);
			$iva += $iva_t;
			$total += $subt + $iva_t;
		}
	
		$contenido .= '</table>';

		$contenido .= '<table style="margin-top:10px">
		<tr>
			<td style="font-size:10px;width:670px;background:#e9eef0;border-radius:5px;padding:8px;text-align:right;padding:30px 20px">
				
				<strong>SubTotal: </strong>$ '.number_format($subtotal,2,",",".").'<br><br>
				<strong>Iva: </strong>$ '.number_format($iva,2,",",".").'<br><br>
				<strong>Total: </strong>$ '.number_format($total,2,",",".").'
			</td>
		</tr>
		</table>';
		/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
		<tbody>
		<tr>
			<td style="width:70px;">
			<img src="'.$_SERVER["DOCUMENT_ROOT"].'/assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
			</td>
			<td class="td-header" style="width:410px;font-weight:thin;font-size:14px;line-height:20px;">
			'.$config["Nombre_Empresa"].'<br> 
			N.I.T.: '.$config["NIT"].'<br> 
			'.$config["Direccion"].'<br> 
			TEL: '.$config["Telefono"].'
			</td>
			<td style="width:150px;text-align:right">
				'.$codigos.'
			</td>
			<td style="width:100px;">
			<img src="'.($cabecera["Codigo_Qr"] !=='' ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$cabecera["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-10px;" />
			</td>
		</tr>
		</tbody>
		</table><hr style="border:1px dotted #ccc;width:730px;">';
		/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
		/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
		$content = '<page backtop="0mm" backbottom="0mm">
			<div class="page-content" >'.
				$cabecera.
				$contenido.'
			</div>
			</page>';
		/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

		try{
			/* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
			$html2pdf = new HTML2PDF('P', 'A4', 'Es', true, 'UTF-8', array(5, 5, 5, 5));
			$html2pdf->writeHTML($content);
			$direc = $cabecera["Codigo"].'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
			$html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
		}catch(HTML2PDF_exception $e) {
		echo $e;
		exit;
}

}






function getDatos($id){

	$query = "SELECT O.*,
					CONCAT(F.Nombres, ' ',F.Apellidos) as Nombre_Funcionario,
					C.Nombre AS Nombre_Cliente, 
					Concat(O.Prefijo, O.Id_Orden_Pedido) as Codigo
					FROM Orden_Pedido O
					INNER JOIN Funcionario F ON F.Identificacion_Funcionario = O.Identificacion_Funcionario
					INNER JOIN Cliente C ON C.Id_Cliente = O.Id_Cliente
				WHERE concat(O.Prefijo, O.Id_Orden_Pedido) = '$id'";
	$oCon= new consulta();
	$oCon->setQuery($query);
	$cabecera = $oCon->getData();
	unset($oCon);


	$query = "SELECT P.Nombre_Comercial, P.Codigo_Cum,
			P.Imagen,
			S.Nombre AS Subcategoria,
			IF(CONCAT( P.Nombre_Comercial,' ',P.Cantidad, ' ',P.Unidad_Medida, ' (',P.Principio_Activo, ' ',
					P.Presentacion, ' ',
					P.Concentracion, ') ' )='' OR CONCAT( P.Nombre_Comercial,' ', P.Cantidad,' ',
					P.Unidad_Medida ,' (',P.Principio_Activo, ' ',
					P.Presentacion, ' ',
					P.Concentracion, ') '
				) IS NULL, CONCAT(P.Nombre_Comercial), CONCAT( P.Nombre_Comercial,' ', P.Cantidad,' ',
					P.Unidad_Medida, ' (',P.Principio_Activo, ' ',
					P.Presentacion, ' ',
					P.Concentracion,') ' )) as Nombre, 
			P.Nombre_Comercial, P.Laboratorio_Comercial, P.Laboratorio_Generico, P.Id_Producto, P.Embalaje, P.Cantidad_Presentacion, 
			PO.Id_Producto_Orden_Pedido,
			PO.Cantidad,
			PO.Costo,
			PO.Precio_Orden,
			PO.Impuesto,
			PO.Estado,
			PO.Observacion,
			IF(PO.Estado='Activo', (PO.Cantidad - IFNULL(PR.Remisionada, 0)), 0) as Pendiente, 
			PO.Estado,
			PO.Observacion,
			IFNULL(PR.Remisionada,0) as Remisionada,
			(  ((PO.Impuesto * (PO.Cantidad * PO.Precio_Orden))  /  100)  + ( PO.Cantidad * PO.Precio_Orden )  ) AS Total
			FROM Producto_Orden_Pedido PO
			INNER JOIN Producto P ON P.Id_Producto = PO.Id_Producto
			INNER JOIN Subcategoria S ON S.Id_Subcategoria = P.Id_Subcategoria
			Left Join 
			(       Select Sum(PR.Cantidad) as Remisionada,
					PR.Id_Producto, R.Id_Orden_Pedido
					FROM Producto_Remision PR 
					Inner Join Remision R on PR.Id_Remision = R.Id_Remision
					Inner Join Orden_Pedido OP On OP.Id_Orden_Pedido = R.Id_Orden_Pedido
					WHERE OP.Id_Orden_Pedido = '$cabecera[Id_Orden_Pedido]'
					And R.Estado != 'Anulada'
					Group By OP.Id_Orden_Pedido, PR.Id_Producto
			) PR on PR.Id_Producto = PO.Id_Producto and PR.Id_Orden_Pedido = PO.Id_Orden_Pedido

			WHERE PO.Id_Orden_Pedido = '$cabecera[Id_Orden_Pedido]'";

	$oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$productosOrden = $oCon->getData();
	unset($oCon);


	return array('cabecera'=>$cabecera,'productosOrden'=>$productosOrden);
}
