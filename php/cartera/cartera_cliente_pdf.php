<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
//require_once('../../class/html2pdf.class.php');


$fecha_inicio = '';
$fecha_fin = '';


/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */

$oItem = new complex('Cliente','Id_Cliente',$_REQUEST['cliente']);
$cliente = $oItem->getData();
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
            <h4 style="margin:5px 0 0 0;font-size:19px;line-height:22px;">CARTERA CLIENTE</h4>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">Fecha Ini. '.fecha($fecha_inicio).'</h5>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">Fecha Fin. '.fecha($fecha_fin).'</h5>
        ';

    $nombre_cliente = $cliente['Primer_Nombre'] != '' ? $cliente['Primer_Nombre'] . " " . $cliente['Segundo_Nombre'] . " " . $cliente['Primer_Apellido'] . " " . $cliente['Segundo_Apellido'] : $cliente['Razon_Social'];

    $contenido = "
    <h4>Cliente: $nombre_cliente - $cliente[Id_Cliente]</h4>
    ";
     
    $contenido .= '
    <table style="border-collapse: collapse">
        <tr style="font-weight:bold;font-size:11px">
            <td style="border-top: 1px solid #000; border-bottom: 1px solid #000;width:80px">Factura</td>
            <td style="border-top: 1px solid #000; border-bottom: 1px solid #000;width:70px">Fecha Factura</td>
            <td style="text-align:right; border-top: 1px solid #000; border-bottom: 1px solid #000;width:75px">Gravado</td>
            <td style="text-align:right; border-top: 1px solid #000; border-bottom: 1px solid #000;width:75px">Excento</td>
            <td style="text-align:right; border-top: 1px solid #000; border-bottom: 1px solid #000;width:75px">Descuentos</td>
            <td style="text-align:right; border-top: 1px solid #000; border-bottom: 1px solid #000;width:95px">IVA</td>
            <td style="text-align:right; border-top: 1px solid #000; border-bottom: 1px solid #000;width:75px">Total Factura</td>
            <td style="text-align:right; border-top: 1px solid #000; border-bottom: 1px solid #000;width:75px">Neto Factura</td>
            <td style="text-align:right; border-top: 1px solid #000; border-bottom: 1px solid #000;width:75px">Tipo Factura</td>
        </tr>';

    foreach ($movimientos as $i => $value) {
        if ($i == (count($movimientos)-1)) {
            $border ='border-bottom: 1px solid #000;';
        }
        $contenido .= '<tr style="font-size:10px">
                    <td style="width:80px">'.$value['Factura'].'</td>
                    <td style="width:70px">'.fecha($value['Fecha_Factura']).'</td>
                    <td style="width:75px;text-align:right;">'.number_format($value['Gravado'],2,",",".").'</td>
                    <td style="width:75px;text-align:right;">'.number_format($value['Excento'],2,",",".").'</td>
                    <td style="width:75px;text-align:right;">'.number_format($value['Descuentos'],2,",",".").'</td>
                    <td style="width:95px;text-align:right;">'.number_format($value['Iva'],2,",",".").'</td>
                    <td style="width:75px;text-align:right;">'.number_format($value['Total_Factura'],2,",",".").'</td>
                    <td style="text-align:right; width:75px;'.$border.'">'.number_format($value['Neto_Factura'],2,",",".").'</td>
                    <td style="text-align:center; width:75px;">'.$value['Tipo'].'</td>
                </tr>';
        
        $totales += $value['Neto_Factura'];
    }

    $contenido .= '
        <tr style="font-size:10px">
            <td colspan="7" style="text-align:right;font-weight:bold">Total $:</td>
            <td style="text-align:right; width:75px;">'.number_format($totales,2,",",".").'</td>
            <td style="text-align:right; width:75px;"></td>
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
echo $content;
/* try{
// CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR
    $html2pdf = new HTML2PDF('P', 'A4', 'Es', true, 'UTF-8', array(5, 5, 5, 5));
    $html2pdf->writeHTML($content);
    $direc = $data["Codigo"].'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
} */

function fecha($fecha) {
    return date('d/m/Y', strtotime($fecha));
}

function getCartera() {
    $condiciones = getStrCondiciones();

    $q = "(SELECT

	FV.Codigo AS Factura,
	FV.Fecha_Documento AS Fecha_Factura,
	(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
	FROM Producto_Factura_Venta PAR
	WHERE PAR.Id_Factura_Venta = FV.Id_Factura_Venta AND PAR.Impuesto!=0) AS Gravado,
	
	(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
	FROM Producto_Factura_Venta PAR
	WHERE PAR.Id_Factura_Venta = FV.Id_Factura_Venta AND PAR.Impuesto=0) AS Excento,
	
	0 AS Descuentos,
	
	(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta*(PAR.Impuesto/100))),0)
	FROM Producto_Factura_Venta PAR
	WHERE PAR.Id_Factura_Venta = FV.Id_Factura_Venta AND PAR.Impuesto!=0) AS Iva,
	
	((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
	FROM Producto_Factura_Venta PAR
	WHERE PAR.Id_Factura_Venta = FV.Id_Factura_Venta AND PAR.Impuesto!=0) + (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
	FROM Producto_Factura_Venta PAR
	WHERE PAR.Id_Factura_Venta = FV.Id_Factura_Venta AND PAR.Impuesto=0)) AS Total_Factura,
	
	((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
	FROM Producto_Factura_Venta PAR
	WHERE PAR.Id_Factura_Venta = FV.Id_Factura_Venta AND PAR.Impuesto!=0) + (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
	FROM Producto_Factura_Venta PAR
	WHERE PAR.Id_Factura_Venta = FV.Id_Factura_Venta AND PAR.Impuesto=0) + (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta*(PAR.Impuesto/100))),0)
	FROM Producto_Factura_Venta PAR
	WHERE PAR.Id_Factura_Venta = FV.Id_Factura_Venta AND PAR.Impuesto!=0) /* - (SELECT IFNULL(SUM(PNC.Cantidad*PNC.Precio_Venta*(1+(PNC.Impuesto/100))),0) FROM Producto_Nota_Credito PNC INNER JOIN Nota_Credito NC ON PNC.Id_Nota_Credito = NC.Id_Nota_Credito WHERE NC.Id_Factura = FV.Id_Factura_Venta) DEVOLUCION  */) AS Neto_Factura,
	'Comercial' AS Tipo
	FROM
	Factura_Venta FV
	WHERE Estado = 'Pendiente' 
	".$condiciones['condicion']."
	)
	
	UNION (
	SELECT
	
	FV.Codigo AS Factura,
	FV.Fecha_Documento AS Fecha_Factura,
	(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura AND PAR.Impuesto!=0) AS Gravado,
	
	(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura AND PAR.Impuesto=0) AS Excento,
	
	(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Descuento),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura) AS Descuentos,
	
	(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio*(PAR.Impuesto/100))),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura AND PAR.Impuesto!=0) AS Iva,
	
	((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura AND PAR.Impuesto!=0) + (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura AND PAR.Impuesto=0) - (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Descuento),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura)) AS Total_Factura,
	
	((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura AND PAR.Impuesto!=0) + (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura AND PAR.Impuesto=0) + (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio*(PAR.Impuesto/100))),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura AND PAR.Impuesto!=0) - (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Descuento),0)
	FROM Producto_Factura PAR
	WHERE PAR.Id_Factura = FV.Id_Factura)) AS Neto_Factura,
	'NoPos' AS Tipo
	FROM
	Factura FV
	WHERE Estado_Factura = 'Sin Cancelar'
	".$condiciones['condicion']."
	)
	UNION(
		SELECT 
		F.Codigo,
		F.Fecha,
		(SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
							0)
			FROM
				Producto_Nota_Credito PAR
			WHERE
				PAR.Id_Nota_Credito = F.Id_Nota_Credito
					AND PAR.Impuesto != 0) AS Gravado,
		(SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
							0)
			FROM
				Producto_Nota_Credito PAR
			WHERE
				PAR.Id_Nota_Credito = F.Id_Nota_Credito
					AND PAR.Impuesto = 0) AS Excento,

		0 AS Descuentos,
		
		(SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta * (PAR.Impuesto / 100))),
							0)
			FROM
				Producto_Nota_Credito PAR
			WHERE
				PAR.Id_Nota_Credito = F.Id_Nota_Credito
					AND PAR.Impuesto != 0) AS Iva,
		((SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
							0)
			FROM
				Producto_Nota_Credito PAR
			WHERE
				PAR.Id_Nota_Credito = F.Id_Nota_Credito
					AND PAR.Impuesto = 0) + (SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
							0)
			FROM
				Producto_Nota_Credito PAR
			WHERE
				PAR.Id_Nota_Credito = F.Id_Nota_Credito
					AND PAR.Impuesto != 0)) AS Total_Compra,
		((SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
							0)
			FROM
				Producto_Nota_Credito PAR
			WHERE
				PAR.Id_Nota_Credito = F.Id_Nota_Credito
					AND PAR.Impuesto = 0) + (SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
							0)
			FROM
				Producto_Nota_Credito PAR
			WHERE
				PAR.Id_Nota_Credito = F.Id_Nota_Credito
					AND PAR.Impuesto != 0) + (SELECT 
				IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta) * (PAR.Impuesto / 100)),
							0)
			FROM
				Producto_Nota_Credito PAR
			WHERE
				PAR.Id_Nota_Credito = F.Id_Nota_Credito
					AND PAR.Impuesto != 0)) AS Neto_Factura,
					'Nota Credito' AS Tipo
	FROM
		Nota_Credito F
	WHERE
		F.Estado = 'Aprobada'
			
			".$condiciones['condicion2']."
	GROUP BY F.Id_Nota_Credito
	)
	UNION(
	SELECT
		FC.Codigo AS Factura,
		FC.Fecha_Documento AS Fecha_Factura,
		
		0 AS Gravado,
	
		IFNULL((SELECT SUM(DFC.Cantidad*DFC.Precio)
		FROM Descripcion_Factura_Capita DFC
		WHERE DFC.Id_Factura_Capita = FC.Id_Factura_Capita),0) as Excento,
		
		FC.Cuota_Moderadora AS Descuentos,
	
		0 AS Iva,
	
		IFNULL((SELECT SUM(DFC.Cantidad*DFC.Precio)
		FROM Descripcion_Factura_Capita DFC
		WHERE DFC.Id_Factura_Capita = FC.Id_Factura_Capita),0) as Total_Factura,
	
		(IFNULL((SELECT SUM(DFC.Cantidad*DFC.Precio)
		FROM Descripcion_Factura_Capita DFC
		WHERE DFC.Id_Factura_Capita = FC.Id_Factura_Capita),0) - FC.Cuota_Moderadora) AS Neto_Factura,
		'Facturas Capita' AS Tipo
	
		FROM
		Factura_Capita FC
		WHERE Estado_Factura = 'Sin Cancelar'
		".$condiciones['condicion3']."
		)/*UNION ALL (
			SELECT
			FP.Factura,
			FP.Fecha_Factura,
			0 AS Gravado,
			0 AS Excento,
			0 AS Descuentos,
			0 AS Iva,
			FP.Saldo AS Total_Factura,
			FP.Saldo AS Neto_Factura
			FROM
			Facturas_Cliente_Mantis FP
			INNER JOIN Cliente P 
			ON FP.Nit_Cliente = P.Id_Cliente
			WHERE FP.Estado = 'Pendiente' AND FP.Nit_Cliente =  $cli[Id_Cliente]
			".$condiciones['condicion_fechas4']."
		)*/
	UNION(
		SELECT 
		F.Codigo,
		F.Fecha,
		(SELECT 
			SUM( IF ( round(PAR.Impuesto) != 0  ,PAR.Precio_Nota_Credito * PAR.Cantidad, 0 ) ) 
		) AS Gravado,

		(SELECT 
			SUM( IF ( round(PAR.Impuesto) = 0  ,PAR.Precio_Nota_Credito * PAR.Cantidad, 0 ) ) 
		) AS Excento,

		0 AS Descuentos,

		(SELECT 
			SUM( IF ( round(PAR.Impuesto) != 0 , (PAR.Precio_Nota_Credito * PAR.Cantidad) * (PAR.Impuesto / 100)  , 0 ) ) 
		) AS Iva,

		(SELECT 
			SUM( PAR.Precio_Nota_Credito * PAR.Cantidad ) 
		) AS Total_Factura,

		(SELECT 
			SUM( 
						( PAR.Precio_Nota_Credito * PAR.Cantidad ) +
						( (PAR.Precio_Nota_Credito * PAR.Cantidad) * (PAR.Impuesto / 100) )
				
			) 
		) AS Neto_Factura,
		'Nota Credito Global' AS Tipo
	FROM
		Nota_Credito_Global F
		INNER JOIN Producto_Nota_Credito_Global PAR 
			ON PAR.Id_Nota_Credito_Global = F.Id_Nota_Credito_Global
	WHERE
		F.Codigo IS NOT NULL
		".$condiciones['condicion2']."
	GROUP BY F.Id_Nota_Credito_Global
	)
		
		
		";


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

	if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "" && $_REQUEST['fechas'] != "undefined") {
		$fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
		$fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
		$condiciones['condicion'] = " AND (DATE(FV.Fecha_Documento) BETWEEN '$fecha_inicio' AND '$fecha_fin')";
		$condiciones['condicion2'] .= " AND (DATE(F.Fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin')";
		$condiciones['condicion3'] .= " AND (DATE(FC.Fecha_Documento) BETWEEN '$fecha_inicio' AND '$fecha_fin')";

		
    }
    
    if (isset($_REQUEST['cliente']) && $_REQUEST['cliente'] != '') {
        $condiciones['condicion'] .= " AND FV.Id_Cliente = $_REQUEST[cliente]";
        $condiciones['condicion2'] .= " AND F.Id_Cliente = $_REQUEST[cliente]";
		$condiciones['condicion3'] .= " AND FC.Id_Cliente = $_REQUEST[cliente]";

	
		
    }

    return $condiciones;
}


?>