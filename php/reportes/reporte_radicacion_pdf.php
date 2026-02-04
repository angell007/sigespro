<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.querybasedatos.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/NumeroALetra.php');

$http_response = new HttpResponse();
$queryObj = new QueryBaseDatos();
$response = array();

$id_radicacion = (isset($_REQUEST['id_radicacion']) && $_REQUEST['id_radicacion'] != '') ? $_REQUEST['id_radicacion'] : '';

if ($id_radicacion == '') {
	
	$http_response->SetRespuesta(1, 'Error en el identificador', 'Hay una inconsistencia en el identificador de la radicacion, contacte con el administrador!');
    $response = $http_response->GetRespuesta();
	echo json_encode($response);

}else{

	$tipo_radicacion = GetTipoServicioRadicacion($id_radicacion);

	$query_radicacion = '
		SELECT 
			R.*,
            C.Nombre AS Nombre_Cliente,
            (CASE
                WHEN R.Id_Departamento = 0 THEN "Todos"
                ELSE D.Nombre
             END) AS Nombre_Departamento,
            RE.Nombre AS Nombre_Regimen,
            CONCAT_WS(" ", F.Nombres, F.Apellidos) AS Nombre_Funcionario
		FROM Radicado R
        INNER JOIN Cliente C ON R.Id_Cliente = C.Id_Cliente
        LEFT JOIN Departamento D ON R.Id_Departamento = D.Id_Departamento
        INNER JOIN Regimen RE ON R.Id_Regimen = RE.Id_Regimen
        INNER JOIN Funcionario F ON R.Id_Funcionario = F.Identificacion_Funcionario
		WHERE
			R.Id_Radicado ='.$id_radicacion;

	$query_facturas_radicacion = '';
	$query_total_radicado = '';

	if ($tipo_radicacion == 'CAPITA') {
		$query_facturas_radicacion = '
			SELECT 
            	RF.Id_Radicado_Factura,
				RF.Id_Radicado,
                F.Id_Factura_Capita AS Id_Factura,
                F.Codigo AS Codigo_Factura,
                IFNULL(F.Codigo, "") AS Codigo_Dis,
                IFNULL(DFC.Descripcion, "") AS Nombre_Paciente,
                (SUM(DFC.Total) - F.Cuota_Moderadora) AS Valor_Factura,
	            RF.Estado_Factura_Radicacion,
	            UPPER(R.Nombre) AS Regimen,
	            DATE_FORMAT(F.Fecha_Documento, "%d-%m-%Y") AS Fecha_Documento,
	            RF.Estado_Factura_Radicacion,
	            IFNULL(RF.Observacion, "") AS Observacion,
	            IFNULL(RF.Total_Glosado, 0) AS Total_Glosado,
	            IF(RF.Estado_Factura_Radicacion = "Pagada", true, false) AS Bloquear
			FROM Radicado_Factura RF
            INNER JOIN Factura_Capita F ON RF.Id_Factura = F.Id_Factura_Capita
	        INNER JOIN Regimen R ON F.Id_Regimen = R.Id_Regimen
            INNER JOIN Cliente C ON F.Id_Cliente = C.Id_Cliente
            INNER JOIN Descripcion_Factura_Capita DFC ON F.Id_Factura_Capita = DFC.Id_Factura_Capita
			WHERE
				Id_Radicado ='.$id_radicacion
            	.' GROUP BY DFC.Id_Factura_Capita';

		$query_total_radicado = '
			SELECT 
				SUM(R.Total_Radicado) AS Total_Radicado
			FROM
			 (SELECT 
	            (SUM(DFC.Total) - F.Cuota_Moderadora) AS Total_Radicado
			FROM Radicado_Factura RF
			INNER JOIN Factura_Capita F ON RF.Id_Factura = F.Id_Factura_Capita
            INNER JOIN Descripcion_Factura_Capita DFC ON F.Id_Factura_Capita = DFC.Id_Factura_Capita
			WHERE
				Id_Radicado ='.$id_radicacion
				.' GROUP BY DFC.Id_Factura_Capita) R';
	}else{
		$query_facturas_radicacion = '
			SELECT 
				RF.Id_Radicado_Factura,
				RF.Id_Radicado,
				F.Id_Factura,
	            F.Codigo AS Codigo_Factura,
	            D.Codigo AS Codigo_Dis,
	            P.Id_Paciente,
	            UPPER(R.Nombre) AS Regimen,
	            DATE_FORMAT(F.Fecha_Documento, "%d-%m-%Y") AS Fecha_Documento,
	            UPPER(CONCAT_WS(" ", P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido)) AS Nombre_Paciente,
	            (
	                CASE
	                    WHEN C.Tipo_Valor = "Exacta" THEN (SELECT SUM( ((Precio * Cantidad)+(Precio * Cantidad * (Impuesto/100) )) - (Descuento*Cantidad)) - F.Cuota FROM Producto_Factura WHERE Id_Factura = F.Id_Factura)
	                    ELSE (SELECT ROUND(SUM( ((Precio * Cantidad)+(Precio * Cantidad * (Impuesto/100) )) - (Descuento*Cantidad)) - F.Cuota) FROM Producto_Factura WHERE Id_Factura = F.Id_Factura)
	                END
	            ) AS Valor_Factura,
	            RF.Estado_Factura_Radicacion,
	            IFNULL(RF.Observacion, "") AS Observacion,
	            IFNULL(RF.Total_Glosado, 0) AS Total_Glosado,
	            IF(RF.Estado_Factura_Radicacion = "Pagada", true, false) AS Bloquear
			FROM Radicado_Factura RF
			INNER JOIN Factura F ON RF.Id_Factura = F.Id_Factura
	        INNER JOIN Cliente C ON F.Id_Cliente = C.Id_Cliente
			INNER JOIN Dispensacion D ON F.Id_Dispensacion = D.Id_Dispensacion
	        INNER JOIN Paciente P ON D.Numero_Documento = P.Id_Paciente
	        INNER JOIN Regimen R ON P.Id_Regimen = R.Id_Regimen
			WHERE
				Id_Radicado ='.$id_radicacion.' ORDER BY F.Id_Resolucion ASC, RF.Id_Factura ASC';

		$query_total_radicado = '
			SELECT 
	            SUM(
	                CASE
	                    WHEN C.Tipo_Valor = "Exacta" THEN (SELECT SUM( ((Precio * Cantidad)+(Precio * Cantidad * (Impuesto/100) )) - (Descuento*Cantidad)) - F.Cuota FROM Producto_Factura WHERE Id_Factura = F.Id_Factura)
	                    ELSE (SELECT ROUND(SUM( ((Precio * Cantidad)+(Precio * Cantidad * (Impuesto/100) )) - (Descuento*Cantidad)) - F.Cuota) FROM Producto_Factura WHERE Id_Factura = F.Id_Factura)
	                END
	            ) AS Total_Radicado
			FROM Radicado_Factura RF
			INNER JOIN Factura F ON RF.Id_Factura = F.Id_Factura
	        INNER JOIN Cliente C ON F.Id_Cliente = C.Id_Cliente
			WHERE
				Id_Radicado ='.$id_radicacion;
	}	

	$query_total_glosado_radicacion = '
		SELECT 
            SUM(Total_Glosado) AS Total_Glosado
		FROM Radicado_Factura
		WHERE
			Id_Radicado ='.$id_radicacion;

	$queryObj->SetQuery($query_radicacion);
	$radicacion = $queryObj->ExecuteQuery('simple');

	$queryObj->SetQuery($query_facturas_radicacion);
	$facturas_radicacion = $queryObj->ExecuteQuery('multiple');

	$queryObj->SetQuery($query_total_radicado);
	$total_radicado = $queryObj->ExecuteQuery('simple');

	$queryObj->SetQuery($query_total_glosado_radicacion);
	$total_glosado = $queryObj->ExecuteQuery('simple');

	$oItem = new complex('Configuracion',"Id_Configuracion",1);
	$config = $oItem->getData();
	unset($oItem);

	ArmarPdf($radicacion, $facturas_radicacion, $config, $total_radicado);

}

function ArmarPdf($radicacion, $facturas_radicacion, $config, $total_radicado){
	global $tipo_radicacion;

	ob_start(); // Se Inicializa el gestor de PDF

	/* HOJA DE ESTILO PARA PDF*/
	$tipo="Factura";
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
	.col-5{
		width:148px;
	}
	</style>';
	/* FIN HOJA DE ESTILO PARA PDF*/

	$codigo = ($radicacion['Consecutivo'] == '' || $radicacion['Numero_Radicado'] == '') ? "Pre-Radicada" : $radicacion["Codigo"];

	$codigos ='
	    <h3 style="margin:0 0 0 0;font-size:22px;line-height:22px;">'.$codigo.'</h3>
	    <h4 style="font-weight:normal;margin:5px 0 0 0;font-size:15px;line-height:15px;">Realizada: '.$radicacion["Fecha_Registro"].'</h4>
	    <h5 style="font-weight:normal;margin:0 0 0 0;font-size:15px;line-height:15px;">Entrega '.$radicacion['Entrega_Actual'].' de '.$radicacion['Nombre_Funcionario'].'</h5>
	    <br>
	    <h6 style="margin:0 0 0 0;font-size:12px;line-height:12px;text-align:center">'.$radicacion['Nombre_Cliente'].' - '.$radicacion['Nombre_Departamento'].'</h6>
	';

	        
	/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
	$cabecera='<table style="" >
	              <tbody>
	                <tr>
	                  <td style="width:70px;">
	                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
	                  </td>
	                  <td class="td-header" style="width:460px;font-weight:thin;font-size:13px;line-height:18px;">
	                    <strong>'.$config["Nombre_Empresa"].'</strong><br> 
	                    N.I.T.: '.$config["NIT"].'<br> 
	                    '.$config["Direccion"].'<br> 
	                    Bucaramanga, Santander<br>
	                    TEL: '.$config["Telefono"].'
	                  </td>
	                  <td style="width:250px;text-align:right">
	                        '.$codigos.'
	                  </td>	                  
	                </tr>
	              </tbody>
	            </table>
	            ';
	            
	/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
	$contenido = '';

	$contenido = '<table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin-top:20px;">
	<tr>
		<td style="font-weight:bold;font-size:10px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
	        Código
	    </td>
	    <td style="font-weight:bold;font-size:10px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
	        Consecutivo
	    </td>
	    <td style="font-weight:bold;font-size:10px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
	        Número Radicado
	    </td>
	    <td style="font-weight:bold;font-size:10px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
	        Fecha Radicacion
	    </td>
	</tr>
	<tr>
		<td style="font-size:10px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
		'.$radicacion['Codigo'].'
		</td>
		<td style="font-size:10px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
		'.$radicacion['Consecutivo'].'
		</td>
		<td style="font-size:10px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
		'.$radicacion['Numero_Radicado'].'
		</td>
		<td style="font-size:10px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
		'.$radicacion['Fecha_Radicado'].'
		</td>
	</tr>
	<tr>
	    <td style="font-weight:bold;font-size:10px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
	        Cliente
	    </td>
	    <td style="font-weight:bold;font-size:10px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
	        Departamento
	    </td>
	    <td style="font-weight:bold;font-size:10px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
	        Regimen
	    </td>
	    <td style="font-weight:bold;font-size:10px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
	        Tipo de Servicio
	    </td>
	</tr>
	<tr>
		<td style="font-size:10px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
		'.$radicacion['Nombre_Cliente'].'
		</td>
		<td style="font-size:10px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
		'.$radicacion['Nombre_Departamento'].'
		</td>
		<td style="font-size:10px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
		'.$radicacion['Nombre_Regimen'].'
		</td>
		<td style="font-size:10px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
		'.$radicacion['Tipo_Servicio'].'
		</td>
	</tr>
	</table>';

	if ($tipo_radicacion == 'CAPITA') {
		
		$contenido .= '
			<table  cellspacing="0" cellpadding="0" style="margin-top:10px">
			    <tr>
			    	<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:2px;width:10px;"></td>
					<td class="col-5" style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:80px;">Factura</td>
					<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:230px;">Descripcion</td>
					<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:100px;">Valor Factura</td>
					<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:100px;">Fecha Factura</td>
					<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:100px;">Estado</td>
		        </tr>';

	    if (count($facturas_radicacion) > 0) {
	    	$i=0;
	    	foreach ($facturas_radicacion as $factura) {
	    		
	    		$contenido .= '
			    	<tr>
			    		<td style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:10px;vertical-align:middle;">
							'.($i+1).'
			    		</td>
			    		<td style="padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;width:80px;vertical-align:middle;">
							'.$factura["Codigo_Factura"].'
			    		</td>
			    		<td style="padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;width:230px;vertical-align:middle;">
							'.$factura["Nombre_Paciente"].'
			    		</td>
			    		<td style="padding:4px;font-size:9px;text-align:right;border:1px solid #c6c6c6;width:100px;vertical-align:middle;">
							$ '.number_format($factura["Valor_Factura"],2,",",".").'
			    		</td>
			    		<td style="padding:4px;font-size:9px;text-align:right;border:1px solid #c6c6c6;width:100px;vertical-align:middle;">
							'.$factura["Fecha_Documento"].'
			    		</td>
			    		<td style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:100px;vertical-align:middle;">
							'.$factura["Estado_Factura_Radicacion"].'
			    		</td>
			    	</tr>';

		    	$i++;
	    	}

	    	$contenido .='</table>';
	    	
	    }else{

	    	$contenido .='</table>';
	    }
	}else{

		$contenido .= '
			<table  cellspacing="0" cellpadding="0" style="margin-top:10px">
			    <tr>
			    	<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:2px;width:10px;"></td>
					<td class="col-5" style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:80px;">Factura</td>
					<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:100px;">Id. Paciente</td>
					<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:100px;">Paciente</td>
					<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:100px;">Valor Factura</td>
					<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:100px;">Fecha Factura</td>
					<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:100px;">Estado</td>
		        </tr>';

	    if (count($facturas_radicacion) > 0) {
	    	$i=0;
	    	foreach ($facturas_radicacion as $factura) {
	    		
	    		$contenido .= '
			    	<tr>
			    		<td style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:10px;vertical-align:middle;">
							'.($i+1).'
			    		</td>
			    		<td style="padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;width:80px;vertical-align:middle;">
							'.$factura["Codigo_Factura"].'
			    		</td>
			    		<td style="padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;width:100px;vertical-align:middle;">
							'.$factura["Id_Paciente"].'
			    		</td>
			    		<td style="padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;width:100px;vertical-align:middle;">
							'.$factura["Nombre_Paciente"].'<br>('.$factura["Regimen"].')
			    		</td>
			    		<td style="padding:4px;font-size:9px;text-align:right;border:1px solid #c6c6c6;width:100px;vertical-align:middle;">
							$ '.number_format($factura["Valor_Factura"],2,",",".").'
			    		</td>
			    		<td style="padding:4px;font-size:9px;text-align:right;border:1px solid #c6c6c6;width:100px;vertical-align:middle;">
							'.$factura["Fecha_Documento"].'
			    		</td>
			    		<td style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:100px;vertical-align:middle;">
							'.$factura["Estado_Factura_Radicacion"].'
			    		</td>
			    	</tr>';

		    	$i++;
	    	}

	    	$contenido .='</table>';
	    	
	    }else{

	    	$contenido .='</table>';
	    }
	}


	// $contenido .= '
	// 	<table  cellspacing="0" cellpadding="0" style="margin-top:10px">
	// 	    <tr>
	// 	    	<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:2px;width:10px;"></td>
	// 			<td class="col-5" style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:80px;">Factura</td>
	// 			<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:100px;">Id. Paciente</td>
	// 			<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:100px;">Paciente</td>
	// 			<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:100px;">Valor Factura</td>
	// 			<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:100px;">Fecha Factura</td>
	// 			<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:8px;width:100px;">Estado</td>
	//         </tr>';

 //    if (count($facturas_radicacion) > 0) {
 //    	$i=0;
 //    	foreach ($facturas_radicacion as $factura) {
    		
 //    		$contenido .= '
	// 	    	<tr>
	// 	    		<td style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:10px;vertical-align:middle;">
	// 					'.($i+1).'
	// 	    		</td>
	// 	    		<td style="padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;width:80px;vertical-align:middle;">
	// 					'.$factura["Codigo_Factura"].'
	// 	    		</td>
	// 	    		<td style="padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;width:100px;vertical-align:middle;">
	// 					'.$factura["Id_Paciente"].'
	// 	    		</td>
	// 	    		<td style="padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;width:100px;vertical-align:middle;">
	// 					'.$factura["Nombre_Paciente"].'<br>('.$factura["Regimen"].')
	// 	    		</td>
	// 	    		<td style="padding:4px;font-size:9px;text-align:right;border:1px solid #c6c6c6;width:100px;vertical-align:middle;">
	// 					$ '.number_format($factura["Valor_Factura"],2,",",".").'
	// 	    		</td>
	// 	    		<td style="padding:4px;font-size:9px;text-align:right;border:1px solid #c6c6c6;width:100px;vertical-align:middle;">
	// 					'.$factura["Fecha_Documento"].'
	// 	    		</td>
	// 	    		<td style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:100px;vertical-align:middle;">
	// 					'.$factura["Estado_Factura_Radicacion"].'
	// 	    		</td>
	// 	    	</tr>';

	//     	$i++;
 //    	}

 //    	$contenido .='</table>';
    	
 //    }else{

 //    	$contenido .='</table>';
 //    }

	//$radicado_total = $total_radicado['Total_Radicado'] - $total_glosado['Total_Glosado'];


    $contenido .= '
		<table  cellspacing="0" cellpadding="0" style="margin-top:10px">
		    <tr>
				<td class="col-5" style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:left;padding:8px;width:500px;">TOTAL RADICADO:</td>
				<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:right;padding:8px;width:235px;">$ '.number_format(floatval($total_radicado['Total_Radicado']), 2, ",", ".").'</td>
	        </tr>
        </table>';

    // var_dump($contenido);
    // exit;
	                    
	// $contenido.='<table style="margin-top:20px">
	//  <tr>
	//      <td style="font-size:10px;width:355px;vertical-align:middle;padding:5px;text-align:center;">
	//      <br><br>______________________________<br>
	//          Elaborado Por<br>'.$radicacion['Nombre_Funcionario'].'
	//      </td>
	//      <td style="font-size:10px;width:355px;vertical-align:middle;padding:5px;text-align:center;">
	//      <br><br>______________________________<br>
	//          Recibí Conforme<br>
	//      </td>
	//  </tr>
	// </table>';
	 
		             
	/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
	$content = '<page backtop="0mm" backbottom="0mm">
			
	                <div class="page-content">
	                '.$cabecera.'
		             '.$contenido.'
	               </div>
	        </page>';
	            
	/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

	try{
	    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
	   $html2pdf = new HTML2PDF('P', 'LETTER', 'Es', true, 'UTF-8', array(2, 2, 2, 2));
	   $html2pdf->writeHTML($content);
	   $direc = $radicacion['Codigo'].'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
	   $html2pdf->Output($direc, "D"); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
	}catch(HTML2PDF_exception $e) {
	    echo $e;
	    exit;
	}
}

function GetTipoServicioRadicacion($idRadicado){
	global $queryObj;

	$query = '
		SELECT 
			Tipo_Servicio
		FROM Radicado
		WHERE
			Id_Radicado ='.$idRadicado;

    $queryObj->SetQuery($query);
    $tipo_servicio = $queryObj->ExecuteQuery('simple');
    return $tipo_servicio['Tipo_Servicio'];
}

?>