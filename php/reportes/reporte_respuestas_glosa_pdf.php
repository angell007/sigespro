<?php 

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');


require_once '../../config/start.inc.php';
include_once '../../class/class.http_response.php';
include_once '../../class/class.querybasedatos.php';
require_once '../../class/html2pdf.class.php';
include_once '../../class/NumeroALetra.php';


$http_response = new HttpResponse();
$queryObj = new QueryBaseDatos();
$response = array();
$id_radicacion = (isset($_REQUEST['id_radicacion']) && $_REQUEST['id_radicacion'] != '') ? $_REQUEST['id_radicacion'] : '';


if ($id_radicacion == '') {
    $http_response->SetRespuesta(1, 'Error en el identificador', 'Hay una inconsistencia en el identificador de la radicacion, contacte con el administrador!');
    $response = $http_response->GetRespuesta();
    echo json_encode($response);
    exit;

} else {
    $query_respuestas = 
    		"SELECT 
				GF.Radicado_Glosa AS Codigo_Radicado,
				F.Codigo AS Codigo_Factura,
				CONCAT('(', GF.Codigo_Glosa,') ',CGG.Concepto,' - ',CEG.Concepto) AS Tipo_Glosa,
				RG.Respuesta,
				GF.Valor_Glosado,
				RG.Valor_Aceptado_Glosa,
				RG.Valor_No_Aceptado_Glosa,
				RG.Fecha_Respuesta,
				CONCAT_WS(' ', FU.Nombres, FU.Apellidos) as Funcionario
			FROM
				Respuesta_Glosa RG
			INNER JOIN		Glosa_Factura GF ON GF.Id_Glosa_Factura = RG.Id_Glosa_Factura
			INNER JOIN 		Radicado_Factura RF ON RF.Id_Radicado_Factura = GF.Id_Radicado_Factura
			INNER JOIN 		Radicado R ON RF.Id_Radicado = R.Id_Radicado
			INNER JOIN 		Factura F ON RF.Id_Factura = F.Id_Factura
			INNER JOIN 		Codigo_Especifico_Glosa CEG ON GF.Id_Codigo_Especifico_Glosa = CEG.Id_Codigo_Especifico_Glosa
			INNER JOIN 		Codigo_General_Glosa CGG ON GF.Id_Codigo_General_Glosa = CGG.Id_Codigo_General_Glosa
			LEFT JOIN 		  Funcionario FU on FU.Identificacion_Funcionario = RG.Funcionario
		WHERE 		R.Id_Radicado = $id_radicacion";

    $queryObj->SetQuery($query_respuestas);
    $respuestas_radicacion = $queryObj->ExecuteQuery('multiple');
    $oItem = new complex('Configuracion', "Id_Configuracion", 1);
    $config = $oItem->getData();
    unset($oItem);
    $codigo_radicacion = GetCodigoRadicacion($id_radicacion);
    ArmarPdf($respuestas_radicacion, $config, $codigo_radicacion);

}

/**
 * Funcion para armar el html del pdf
 */
function ArmarPdf($respuestas, $config, $codigo_radicacion)
{
    ob_start();
    /* HOJA DE ESTILO PARA PDF*/
    $tipo = "Factura";
    $style = "<style>
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
			.col-5{
				width:148px;
			}
			</style>";
    /* FIN HOJA DE ESTILO PARA PDF*/
    $codigos =
    "<h3 style='margin:0 0 0 0; font-size:16px; line-height:22px; '>Respuestas Radicacion  $codigo_radicacion </h3>
	<h4 style='font-weight:normal;margin:5px 0 0 0;font-size:15px;line-height:15px;'>Fecha: " . date("d-m-Y") . "</h4>";

    /* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
    $cabecera =
        	'<table style="" >
			<tbody>
				<tr>
					<td style="width:70px;"><img src="' . $_SERVER["DOCUMENT_ROOT"] . 'assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" /></td>
					<td class="td-header" style="width:460px;font-weight:thin;font-size:13px;line-height:18px;"><strong>' . $config["Nombre_Empresa"] . '</strong><br>N.I.T.: ' . $config["NIT"] . '<br>' . $config["Direccion"] . '<br>Bucaramanga, Santander<br>TEL: ' . $config["Telefono"] . '</td>
					<td style="width:250px;text-align:right">' . $codigos . '</td>
				</tr>
			</tbody>
		</table>';
    /* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
    $contenido = '';

    $contenido .=
        '<table  cellspacing="0" cellpadding="0" style="margin-top:10px">
		   	<tr>
			   	<td class="col-5" style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:63px;">Radicación</td>
				<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:63px;">Factura</td>
				<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:63px;">Tipo Glosa</td>
				<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:140px;">Respuesta</td>
				<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:63px;">Valor Glosado</td>
				<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:80px;">Valor Aceptado</td>
				<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:100px;">Valor No Aceptado</td>
			</tr>';
    if (count($respuestas) > 0) {
        foreach ($respuestas as $r) {
            $contenido .=
            "<tr>
			<td style='padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:63px;vertical-align:middle;'> $r[Codigo_Radicado] </td>
			<td style='padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:63px;vertical-align:middle;'> $r[Codigo_Factura] </td>
			<td style='padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:63px;vertical-align:middle;'> $r[Tipo_Glosa] </td>
			<td style='padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;width:140px;vertical-align:middle;'> $r[Respuesta] </td>
			<td style='padding:4px;font-size:9px;text-align:right;border:1px solid #c6c6c6;width:63px;vertical-align:middle;'>$ " . number_format($r['Valor_Glosado'], 2, ',', '.') . "</td>
			<td style='padding:4px;font-size:9px;text-align:right;border:1px solid #c6c6c6;width:80px;vertical-align:middle;'>$ " . number_format($r['Valor_Aceptado_Glosa'], 2, ',', '.') . "</td>
			<td style='padding:4px;font-size:9px;text-align:right;border:1px solid #c6c6c6;width:63px;vertical-align:middle;'>$ " . number_format($r['Valor_No_Aceptado_Glosa'], 2, ',', '.') . "</td>
		</tr>
		<tr>
			<td colspan=7  style='padding:4px;font-size:6px;text-align:center;border:1px solid #c6c6c6;vertical-align:middle;'>$r[Funcionario]</td>
		</tr>
	  ";
        }
        $contenido .= '</table>';
    } else {
        $contenido .=
            '<tr>
			<td colspan="7" style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:572px;vertical-align:middle;">
				NO HAY RESPUESTAS EN ESTA RADICACIÓN O NO EXSITE LA RADICACION QUE INTENTA CONSULTAR!
			</td>
		</tr>';
        $contenido .= '</table>';
    }
    /* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
    $content = '<page backtop="0mm" backbottom="0mm">
					<div class="page-content">' . $cabecera . ' ' . $contenido . '
					</div>
				</page>';
    /* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
//     echo $content; exit;
    try { /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
	  $html2pdf = new HTML2PDF('P', 'LETTER', 'Es', true, 'UTF-8', array(2, 2, 2, 2));
        $html2pdf->writeHTML($content);
        $direc = 'Respuestas_Radicacion_' . $codigo_radicacion . '.pdf'; /* NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO*/
	  $html2pdf->Output($direc, 'D');     /* LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA*/
    } catch (HTML2PDF_exception $e) {
        echo $e;
        exit;

    }
}function GetCodigoRadicacion($id_radicacion)
{
    global $queryObj;
    $query = 
    		"SELECT Codigo FROM Radicado WHERE Id_Radicado = $id_radicacion";
    $queryObj->SetQuery($query);
    $codigo_radicacion = $queryObj->ExecuteQuery('simple');
    return $codigo_radicacion['Codigo'];

}
