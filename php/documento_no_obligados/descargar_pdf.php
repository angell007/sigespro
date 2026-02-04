<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
require_once '../../class/html2pdf.class.php';
include_once '../../class/NumeroALetra.php';

$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');
$ruta = isset($_REQUEST['Ruta']) ? $_REQUEST['Ruta'] : '';

/* FUNCIONES BASICAS */
function fecha($str)
{
    $parts = explode(" ", $str);
    $date = explode("-", $parts[0]);
    return $date[2] . "/" . $date[1] . "/" . $date[0];
}
/* FIN FUNCIONES BASICAS*/

/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion', "Id_Configuracion", 1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */

/* DATOS DEL ARCHIVO A MOSTRAR */
$oItem = new complex("Documento_No_Obligados", "Id_Documento_No_Obligados", $id);
$data = $oItem->getData();
unset($oItem);

$oItem = new complex("Resolucion", "Id_Resolucion", $data["Id_Resolucion"]);
$fact = $oItem->getData();
unset($oItem);

$query = "SELECT
      DNO.Fecha_Adquirido as Fecha,
      DNO.Cuds,
      DNO.Observaciones as observacion,
      DNO.Codigo as Codigo,
      DNO.Codigo_Qr,
      DNO.Tipo_Reporte,
      IF(DNO.Forma_Pago=1,'CONTADO','CREDITO') as Condicion_Pago ,
      DNO.Fecha_Vencimiento as Fecha_Vencimiento,
      P.Nombre as NombreProveedor,
      P.Direccion,
      M.Nombre as Municipio,
      P.Telefono,
      P.Id_Proveedor,
      DNO.Id_Documento_No_Obligados

      FROM Documento_No_Obligados DNO
      LEFT JOIN ((SELECT 'Funcionario' AS Tipo_Tercero, Identificacion_Funcionario AS Id_Proveedor , 'No' as Contribuyente, 'No' as Autorretenedor,
                  CONCAT_WS(' ',Nombres,Apellidos)AS Nombre,
                  Correo AS Correo_Persona_Contacto , Celular, 'Natural' AS Tipo, 'CC' AS Tipo_Identificacion,
                  '' AS Digito_Verificacion, 'Simplificado' AS Regimen, Direccion_Residencia AS Direccion, Telefono,
                  IFNULL(Id_Municipio,99) AS Id_Municipio , 1 AS Condicion_Pago
                  FROM Funcionario )
               UNION ALL (SELECT 'Proveedor' AS Tipo_Tercero, Id_Proveedor AS Id_Proveedor , 'No' as Contribuyente, 'No' as Autorretenedor,
                  (CASE
                  WHEN Tipo = 'Juridico' THEN Razon_Social
                  ELSE  COALESCE(Nombre, CONCAT_WS(' ',Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )

                  END) AS Nombre,
                  Correo AS Correo_Persona_Contacto,
                  Celular, Tipo, 'NIT' AS Tipo_Identificacion,
                  Digito_Verificacion, Regimen, Direccion ,Telefono,
                  Id_Municipio, IFNULL(Condicion_Pago , 1 ) as Condicion_Pago
                  FROM Proveedor)
               UNION ALL (SELECT 'Cliente' AS Tipo_Tercero, Id_Cliente as Id_Proveedor, Contribuyente, Autorretenedor,
                  (CASE
                  WHEN Tipo = 'Juridico' THEN Razon_Social
                  ELSE  COALESCE(Nombre, CONCAT_WS(' ',Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )
                  END) AS Nombre,
                  Correo_Persona_Contacto,
                  Celular, Tipo, Tipo_Identificacion,
                  Digito_Verificacion, Regimen, Direccion, Telefono_Persona_Contacto AS Telefono,
                  Id_Municipio, IFNULL(Condicion_Pago , 1 ) as Condicion_Pago
                  FROM Cliente)
            ) P on P.Id_Proveedor = DNO.Id_Proveedor and P.Tipo_Tercero = DNO.Tipo_Proveedor
      LEFT JOIN Municipio M on M.Id_Municipio = P.Id_Municipio
      WHERE DNO.Id_Documento_No_Obligados =$id ";

$oCon = new consulta();
$oCon->setQuery($query);
$documento = $oCon->getData();
unset($oCon);

$query = 'SELECT PS.Nombre as Descripcion , D.* from Descripcion_Documento_No_Obligados D
left join Producto_Servicio PS on D.Codigo_Producto_Servicio= PS.Codigo_Producto
WHERE D.Id_Documento_No_Obligados =' . $id;

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$productos = $oCon->getData();
unset($oCon);

$oItem = new complex("Funcionario", "Identificacion_Funcionario", $data["Id_Funcionario"]);
$func = $oItem->getData();
unset($oItem);
// header('Content-type:application/json'); echo json_encode($func); exit;

/* FIN DATOS DEL ARCHIVO A MOSTRAR */

ob_start(); // Se Inicializa el gestor de PDF

/* HOJA DE ESTILO PARA PDF*/
$tipo = "Factura";
$style = '<style>

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
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/

$query5 = 'SELECT SUM(Subtotal) as TotalFac FROM Descripcion_Documento_No_Obligados WHERE Id_Documento_No_Obligados = ' . $id;
$oCon = new consulta();
$oCon->setQuery($query5);
$totalFactura = $oCon->getData();
unset($oCon);

$codigos = '
    <span style="margin:-5px 0 0 0;font-size:13px;line-height:16px;"> Documento Soporte en adquisiciones efectuadas a
    sujetos no obligados a facturar </span>
    <h3 style="margin:0 0 0 0;font-size:15px;line-height:22px;">' . $data["Codigo"] . '</h3>
    <h5 style="margin:5px 0 0 0;font-size:8px;line-height:10px;">F. Expe.:' . fecha($data["Fecha_Documento"]) . '</h5>
    <h5 style="margin:5px 0 0 0;font-size:8px;line-height:10px;">F. Adq.:' . fecha($data["Fecha_Adquirido"]) . '</h5>'
    . ($documento["Fecha_Vencimiento"] != '' ? '
    <h4 style="margin:5px 0 0 0;font-size:8px;line-height:10px;">F. Venc.:' . fecha($documento["Fecha_Vencimiento"]) . '</h4>
' : '');

$condicion_pago = $documento["Condicion_Pago"];

/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera = '<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="' . $_SERVER["DOCUMENT_ROOT"] . 'assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:310px;font-weight:thin;font-size:10px;line-height:11px;">
                    <strong>' . $config["Nombre_Empresa"] . '</strong><br>
                    N.I.T.: ' . $config["NIT"] . '<br>
                    ' . $config["Direccion"] . '<br>
                    Bucaramanga, Santander<br>
                    TEL: ' . $config["Telefono"] . '
                  </td>
                  <td style="width:250px;text-align:right">
                        ' . $codigos . '
                  </td>
                  <td style="width:150px;">
                  <img src="' . ($data["Codigo_Qr"] == '' ?
                              $_SERVER["DOCUMENT_ROOT"] . 'assets/images/sinqr.png' :
                              $_SERVER["DOCUMENT_ROOT"] . 'ARCHIVOS/FACTURACION_ELECTRONICA/' . $data["Codigo_Qr"]) 
                  . '" style="max-width:80%;margin-top:2px;" />
                  </td>
                </tr>
                <tr>
                <tr>
                    <td colspan="3" style="font-size:9px;">
                        NO SOMOS GRANDES CONTRIBUYENTES<br>
                        NO SOMOS AUTORETENEDORES DE RENTA<br>
                        POR FAVOR ABSTENERSE PRACTICAR RETENCIÓN EN LA FUENTE POR ICA,<BR>
                        SOMOS GRANDES CONTRIBUYENTES DE ICA EN BUCARAMANGA. RESOLUCIÓN 3831 DE 18/04/2022
                    </td>
                     <td colspan="1" style="font-size:9px;text-align:right;vertical-align:top;">
                     <strong >ORIGINAL &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong><br>
                     Página [[page_cu]] de [[page_nb]]
                     </td>
                </tr>
                </tr>
              </tbody>
            </table>

            <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin-top:5px;">
                <tr>
                    <td style="font-size:8px;width:60px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Proveedor:</strong>
                    </td>
                    <td style="font-size:8px;width:510px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    ' . trim($documento["NombreProveedor"]) . '
                    </td>
                    <td style="font-size:8px;width:70px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>N.I.T. o C.C.:</strong>
                    </td>
                    <td style="font-size:8px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    ' . number_format($documento["Id_Proveedor"], 0, ",", ".") . '
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Dirección:</strong>
                    </td>
                    <td style="font-size:8px;width:510px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    ' . trim($documento["Direccion"]) . '
                    </td>
                    <td style="font-size:8px;width:70px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Teléfono:</strong>
                    </td>
                    <td style="font-size:8px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    ' . $documento["Telefono"] . '
                    </td>
                </tr>
                <tr>
                    <td style="font-size:10px;width:60px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Ciudad: </strong>
                    </td>
                    <td style="font-size:8px;width:510px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                        ' . trim($documento["Municipio"]) . '
                    </td>
                    <td style="font-size:8px;width:70px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Cond. Pago:</strong>
                    </td>
                    <td style="font-size:8px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    ' . $condicion_pago . '
                    </td>
                </tr>
            </table>
            <hr style="border:1px dotted #ccc;width:730px;">';

/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/

/* PIE DE PAGINA */

$pie = '<table cellspacing="0" cellpadding="0" style="margin:0px;">';
if ($fact["Tipo_Resolucion"] == "Resolucion_Electronica") {
    $pie .= '<tr>
    	   <td style="font-size:8px;width:770px;background:#c6c6c6;vertical-align:middle;padding:5px;text-align:center;">
    		<strong>CUDS: ' . $documento["Cuds"] . '</strong>
    	   </td>
    	</tr>';
}
// vertical-align:middle;
$pie .= '<tr>
                  <td style="font-size:7px;width:778px;background:#f3f3f3;vertical-align:middle;padding:1px 5px;height:10px;">
                        <strong>Resolución Documento Soporte ' . ($fact["Tipo_Resolucion"] == "Resolucion_Electronica" ? 'Electrónico' : '') . ':</strong><br>
                        Autorizacion # ' . $fact["Resolucion"] . '<br>
                        Desde ' . fecha($fact["Fecha_Inicio"]) . ' Hasta ' . fecha($fact["Fecha_Fin"]) . '<br>
                        Habilita Del No. ' . $fact["Codigo"] . $fact["Numero_Inicial"] . ' Al No. ' . $fact["Codigo"] . $fact["Numero_Final"] . '<br>
                        Actividad economica principal 4645<br>
                  </td>

            </tr>

      </table>
      <table>
      <tr>
            <td style="font-size:9px;width:355px;vertical-align:middle;padding:5px;text-align:center;">
            <br><br>______________________________<br>
                  Elaborado Por<br>' . $func["Nombres"] . " " . $func["Apellidos"] . '
            </td>
            <td style="font-size:9px;width:355px;vertical-align:middle;padding:5px;text-align:center;">
            <br><br>______________________________<br>
                  Recibí Conforme<br>
            </td>
      </tr>
      </table>
      ';
      $contenido = '<table  cellspacing="0" cellpadding="0" >
            <tr>
            <td style="font-size:10px;background:#c6c6c6;text-align:center;">Descripción</td>' . (
                  $documento['Tipo_Reporte'] == '2' ?
                  '<td style="font-size:10px;background:#c6c6c6;text-align:center;">Fecha Compra</td>'
                  : ''
                  ) . '
                  <td style="font-size:10px;background:#c6c6c6;text-align:center;">Cant</td>
                  <td style="font-size:10px;background:#c6c6c6;text-align:center;">Precio</td>
                  <td style="font-size:10px;background:#c6c6c6;text-align:center;">Descuento</td>
                  <td style="font-size:10px;background:#c6c6c6;text-align:center;">Iva</td>
                  <td style="font-size:10px;background:#c6c6c6;text-align:center;">Total</td>
                  </tr>';
      $total_iva = 0;
      $total_desc = 0;
      $style1 = $documento['Tipo_Reporte'] == '2' ?
                  'style="padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;width: 350px; vertical-align:middle;"':
                  'style="padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;width: 455px; vertical-align:middle;"';
      foreach ($productos as $prod) {

            $descuento = $prod["Cantidad"] * $prod["Precio"]* $prod["Descuento"]/100;
            $total_desc+= $descuento;
            $total_iva += (($prod["Cantidad"] * $prod["Precio"]) - $descuento) * (str_replace("%", "", $prod["Impuesto"]) / 100);


            $contenido .= "<tr>
	        		<td $style1>
	        		" . $prod["Descripcion"] . '
	        		</td>'
                          . (
                              $documento['Tipo_Reporte'] == '2' ?
                              '<td style="padding:4px;font-size:8px;text-align:center;border:1px solid #c6c6c6;width:80px;vertical-align:middle;">'.$prod['Fecha_Compra'].'</td>'
                              : ''
                              ) . '
	        		<td style="padding:4px;font-size:8px;text-align:center;border:1px solid #c6c6c6;width:20px;vertical-align:middle;">
	        		' . number_format($prod["Cantidad"], 0, "", ".") . '
	        		</td>
	        		<td style="padding:4px;font-size:8px;text-align:right;border:1px solid #c6c6c6;vertical-align:middle;width:60px;">
	        		$ ' . number_format($prod["Precio"], 0, ",", ".") . '
	        		</td>
	        		<td style="padding:4px;font-size:8px;text-align:right;border:1px solid #c6c6c6;vertical-align:middle;width:60px;">
	        		$ ' .$descuento . '
	        		</td>
	        		<td style="padding:4px;font-size:8px;;text-align:center;border:1px solid #c6c6c6;vertical-align:middle;">
	        		' . $prod["Impuesto"] . '%
	        		</td>
	        		<td style="padding:4px;font-size:8px;text-align:right;border:1px solid #c6c6c6;width:60px;vertical-align:middle;">
	        		$ ' . number_format($prod["Subtotal"], 0, ",", ".") . '
	        		</td>
	        	    </tr>';
      }
$total = $totalFactura['TotalFac'] + $total_iva;
$numero = number_format($total, 0, '.', '');
$letras = NumeroALetras::convertir($numero);
$contenido .= '</table>
	             <table style="margin-top:5px;margin-bottom:0;">
	             	<tr>
	             	   <td colspan="2" style="padding:4px;font-size:8px;border:1px solid #c6c6c6;width:586px;"><strong>Valor a Letras:</strong><br>' . str_replace('CERO', '', $letras ). ' PESOS MCTE</td>
	             	   <td rowspan="3" style="padding:4px;font-size:9px;border:1px solid #c6c6c6;width:130px;">
	             	   	<table cellpadding="0" cellspacing="0">
	             	   	   <tr>
	             	   		<td style="padding:4px;font-size:8px;width:60px;"><strong>Subtotal</strong></td>
	             	   		<td style="padding:4px;font-size:8px;width:60px;text-align:right;">$ ' . number_format($totalFactura['TotalFac'] - $total_iva, 0, ",", ".") . '</td>
	             	   	   </tr>
	             	   	   <tr>
	             	   		<td style="padding:4px;font-size:8px;width:60px;"><strong>Dcto.</strong></td>
	             	   		<td style="padding:4px;font-size:8px;width:60px;text-align:right;">$ '.number_format($total_desc, 0, ",", ".").'</td>
	             	   	   </tr>
	             	   	   <tr>
	             	   		<td style="padding:4px;font-size:8px;width:60px;"><strong>Iva 19%</strong></td>
	             	   		<td style="padding:4px;font-size:8px;width:60px;text-align:right;">$ ' . number_format($total_iva, 0, ",", ".") . '</td>
	             	   	   </tr>
	             	   	   <tr>
	             	   		<td style="padding:4px;font-size:8px;width:60px;"><strong>Total</strong></td>
	             	   		<td style="padding:4px;font-size:8px;width:60px;text-align:right;"><strong>$ ' . number_format($totalFactura['TotalFac'], 0, ",", ".") . '</strong></td>
	             	   	   </tr>
	             	   	</table>
	             	   </td>
	             	</tr>
	             	<tr>
	             	   <td style="padding:4px;font-size:8px;border:1px solid #c6c6c6;width:486px;">
	             	   	<strong>Obsrvaciones:</strong><br>
	             	   	' . $documento["observacion"] . '
	             	   </td>
	             	   <td style="padding:4px;font-size:9px;border:1px solid #c6c6c6;width:90px;"></td>
	             	</tr>
               </table>';

$marca_agua = '';

if ($data['Estado'] == 'Anulada' || $data['Valor_Nota_Credito']==$totalFactura['TotalFac']) {
    $marca_agua = 'backimg="' . $_SERVER["DOCUMENT_ROOT"] . 'assets/images/anulada.png"';
}

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="240px" backbottom="120px" ' . $marca_agua . '>
                <page_header>' . $cabecera . '</page_header>
		        <page_footer>' . $pie . '</page_footer>
                <div class="page-content">' . $contenido . '</div>
            </page>';

/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

try {
      /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
      $html2pdf = new HTML2PDF('L', array(217, 140), 'Es', true, 'UTF-8', array(2, -2, -2, 0));
      $html2pdf->writeHTML($content);
      $direc = $ruta?$_SERVER['DOCUMENT_ROOT'].$ruta:"$documento[Codigo].pdf"; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
      ob_clean();
      
      $html2pdf->Output($direc, "I"); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
  } catch (\Throwable $e) {
      echo $e;
      exit;
  }
