<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');


$fecha_inicio = '';
$fecha_fin = '';


/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */

$oItem = new complex('Proveedor','Id_Proveedor',$_REQUEST['proveedor']);
$proveedor = $oItem->getData();
unset($oItem);

$movimientos = getCartera();
$totales = 0;

ob_start(); // Se Inicializa el gestor de PDF

/* HOJA DE ESTILO PARA PDF*/
$style='<style>
.page-content{
width:750px;
}
.row{
display:inlinie-block;
width:750px;
}
.td-header{
    font-size:15px;
    line-height: 20px;
}
.titular{
    font-size: 11px;
    text-transform: uppercase;
    margin-bottom: 0;
  }
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/
               
        $tipo_balance = strtoupper($tipo);
        
        $codigos ='
            <h4 style="margin:5px 0 0 0;font-size:19px;line-height:22px;">CARTERA PROVEEDOR</h4>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">Fecha Ini. '.fecha($fecha_inicio).'</h5>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">Fecha Fin. '.fecha($fecha_fin).'</h5>
        ';

    $nombre_proveedor = $proveedor['Primer_Nombre'] != '' ? $proveedor['Primer_Nombre'] . " " . $proveedor['Segundo_Nombre'] . " " . $proveedor['Primer_Apellido'] . " " . $proveedor['Segundo_Apellido'] : $proveedor['Razon_Social'];

    $contenido = "
    <h4>Proveedor: $nombre_proveedor - $proveedor[Id_Proveedor]</h4>
    ";
     
    $contenido .= '
    <table style="border-collapse: collapse">
        <tr style="font-weight:bold;font-size:11px">
            <td style="border-top: 1px solid #000; border-bottom: 1px solid #000;width:80px">Factura</td>
            <td style="border-top: 1px solid #000; border-bottom: 1px solid #000;width:50px">Fecha Factura</td>
            <td style="text-align:right; border-top: 1px solid #000; border-bottom: 1px solid #000;width:75px">Gravado</td>
            <td style="text-align:right; border-top: 1px solid #000; border-bottom: 1px solid #000;width:75px">Excento</td>
            <td style="text-align:right; border-top: 1px solid #000; border-bottom: 1px solid #000;width:45px">IVA</td>
            <td style="text-align:right; border-top: 1px solid #000; border-bottom: 1px solid #000;width:95px">Rte Fuente 2.5%</td>
            <td style="text-align:right; border-top: 1px solid #000; border-bottom: 1px solid #000;width:75px">Rte Ica 0.5%</td>
            <td style="text-align:right; border-top: 1px solid #000; border-bottom: 1px solid #000;width:75px">Total Factura</td>
            <td style="text-align:right; border-top: 1px solid #000; border-bottom: 1px solid #000;width:75px">Abono</td>
            <td style="text-align:right; border-top: 1px solid #000; border-bottom: 1px solid #000;width:75px">Neto Factura</td>
        </tr>';

    foreach ($movimientos as $i => $value) {
        if ($i == (count($movimientos)-1)) {
            $border ='border-bottom: 1px solid #000;';
        }
        $total_factura = $value["Total_Factura"]+$value["Iva"]-$value["Rte_Fuente"]-$value["Rte_Ica"];
		$neto = $total_factura - $value["Abono"];
        $contenido .= '<tr style="font-size:10px">
                    <td style="width:80px">'.$value['Factura'].'</td>
                    <td style="width:50px">'.fecha($value['Fecha_Factura']).'</td>
                    <td style="width:75px;text-align:right;">'.number_format($value['Gravado'],2,",",".").'</td>
                    <td style="width:75px;text-align:right;">'.number_format($value['Excento'],2,",",".").'</td>
                    <td style="width:45px;text-align:right;">'.number_format($value['Iva'],2,",",".").'</td>
                    <td style="width:95px;text-align:right;">'.number_format($value['Rte_Fuente'],2,",",".").'</td>
                    <td style="width:75px;text-align:right;">'.number_format($value['Rte_Ica'],2,",",".").'</td>
                    <td style="width:75px;text-align:right;">'.number_format($total_factura,2,",",".").'</td>
                    <td style="text-align:right; width:75px;">'.number_format($value['Abono'],2,",",".").'</td>
                    <td style="text-align:right; width:75px;'.$border.'">'.number_format($neto,2,",",".").'</td>
                </tr>';
        
        $totales += $neto;
    }

    $contenido .= '
        <tr style="font-size:10px">
            <td colspan="9" style="text-align:right;font-weight:bold">Total $:</td>
            <td style="text-align:right; width:75px;">'.number_format($totales,2,",",".").'</td>
        </tr>
    </table>';


/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:410px;font-weight:thin;font-size:14px;line-height:20px;">
                    '.$config["Nombre_Empresa"].'<br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    TEL: '.$config["Telefono"].'
                  </td>
                  <td style="width:250px;text-align:right">
                        '.$codigos.'
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
    $direc = $data["Codigo"].'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

function fecha($fecha) {
    return date('d/m/Y', strtotime($fecha));
}

function getCartera() {
    $condiciones = getStrCondiciones();

    $q = "(SELECT
	FAR.Factura, FAR.Fecha_Factura, AR.Codigo AS Acta_Recepcion, (SELECT Codigo FROM Orden_Compra_Nacional WHERE Id_Orden_Compra_Nacional = AR.Id_Orden_Compra_Nacional) AS Orden_Compra,
	
	(SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0) as Gravado,
	
	(SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto=0) as Excento,
	
	(SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio*(PAR2.Impuesto/100))),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0)  as Iva,
	
	((SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto=0)+ (SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0)) AS Total_Factura,

	(

		SELECT 
		IFNULL(SUM(MC.Debe),0)
		FROM
		Movimiento_Contable MC
		WHERE
			MC.Nit = AR.Id_Proveedor AND MC.Documento = FAR.Factura AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 272

	) AS Abono,
	
	((SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto=0)+ (SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0) + (SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio*(PAR2.Impuesto/100))),0)
	FROM Producto_Acta_Recepcion PAR2
	WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0)) as Neto_Factura,

	IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 60 LIMIT 1),0) AS Rte_Fuente,

	IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 63 LIMIT 1),0) AS Rte_Ica
	 
	FROM
	Factura_Acta_Recepcion FAR
	INNER JOIN Acta_Recepcion AR
	ON FAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
	INNER JOIN Proveedor P
	ON AR.Id_Proveedor = P.Id_Proveedor
	INNER JOIN Producto_Acta_Recepcion PAR
	ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
	WHERE FAR.Estado = 'Pendiente' $condiciones[condicion]
	GROUP BY FAR.Id_Acta_Recepcion, FAR.Factura ORDER BY FAR.Fecha_Factura DESC)
	UNION(
		SELECT 
		F.Codigo,
		F.Fecha,
		'' AS Acta,
		'' AS Orden,
 		(SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Costo)),
							0)
			FROM
				Producto_Devolucion_Compra PAR
			WHERE
				PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
					AND PAR.Impuesto != 0) AS Gravado,

		(SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Costo)),
							0)
			FROM
				Producto_Devolucion_Compra PAR
			WHERE
				PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
					AND PAR.Impuesto = 0) AS Excento,

		(SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Costo * (PAR.Impuesto / 100))),
							0)
			FROM
				Producto_Devolucion_Compra PAR
			WHERE
				PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
					AND PAR.Impuesto != 0) AS Iva,
		((SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Costo)),
							0)
			FROM
				Producto_Devolucion_Compra PAR
			WHERE
				PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
					AND PAR.Impuesto = 0) + (SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Costo)),
							0)
			FROM
				Producto_Devolucion_Compra PAR
			WHERE
				PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
					AND PAR.Impuesto != 0)) AS Total_Compra,

		
		0 AS Abono,
		
		((SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Costo)),
							0)
			FROM
				Producto_Devolucion_Compra PAR
			WHERE
				PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
					AND PAR.Impuesto = 0) + (SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Costo)),
							0)
			FROM
				Producto_Devolucion_Compra PAR
			WHERE
				PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
					AND PAR.Impuesto != 0) + (SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Costo) * (PAR.Impuesto / 100)),
							0)
			FROM
				Producto_Devolucion_Compra PAR
			WHERE
				PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
					AND PAR.Impuesto != 0)) AS Neto_Factura,
					0 AS Rte_Fte,
		0 AS Rte_Ica
	FROM
		Devolucion_Compra F
        $condiciones[condicion2]
	)";


	$oCon= new consulta();
	$oCon->setTipo('Multiple');
	$oCon->setQuery($q);
	$facturas_prov= $oCon->getData();
	unset($oCon);

    return $facturas_prov;
}

function getStrCondiciones() {
    global $fecha_inicio;
    global $fecha_fin;
    $condiciones = [];

	if (isset($_REQUEST['Fechas']) && $_REQUEST['Fechas'] != "" && $_REQUEST['Fechas'] != "undefined") {
		$fecha_inicio = trim(explode(' - ', $_REQUEST['Fechas'])[0]);
		$fecha_fin = trim(explode(' - ', $_REQUEST['Fechas'])[1]);
		$condiciones['condicion'] = " AND (DATE(AR.Fecha_Creacion) BETWEEN '$fecha_inicio' AND '$fecha_fin')";
		$condiciones['condicion2'] .= " WHERE (DATE(F.Fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin')";
    }
    
    if (isset($_REQUEST['proveedor']) && $_REQUEST['proveedor'] != '') {
        $condiciones['condicion'] .= " AND AR.Id_Proveedor = $_REQUEST[proveedor]";
        $condiciones['condicion2'] .= " AND F.Id_Proveedor = $_REQUEST[proveedor]";
    }

    return $condiciones;
}


?>