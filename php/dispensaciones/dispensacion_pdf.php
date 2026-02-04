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
$ruta = (isset($_REQUEST['Ruta']) ? $_REQUEST['Ruta'] : '');

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

$query = "SELECT D.*, PD.numeroAutorizacion, DATE_FORMAT(D.Fecha_Actual, '%d/%m/%Y') as Fecha_Dis, CONCAT(F.Nombres, ' ',  F.Apellidos) as Funcionario, P.Nombre as Punto_Dispensacion, P.Direccion as Direccion_Punto, P.Telefono Telefono_Punto, L.Nombre as Departamento, CONCAT_WS(' ',Paciente.Primer_Nombre,Paciente.Segundo_Nombre,Paciente.Primer_Apellido, Paciente.Segundo_Apellido) as Nombre_Paciente , Paciente.EPS, Paciente.Direccion as Direccion_Paciente, R.Nombre as Regimen_Paciente, Paciente.Id_Paciente,  F.Firma, F.Identificacion_Funcionario as Funcionario1,
IFNULL((SELECT Numero_Telefono FROM Paciente_Telefono WHERE Id_Paciente = D.Numero_Documento LIMIT 1), 'No Registrado' ) AS Telefono_Paciente, (SELECT CONCAT(S.Nombre,' - ' ,T.Nombre) FROM Tipo_Servicio T INNER JOIN Servicio S ON T.Id_Servicio=S.Id_Servicio WHERE T.Id_Tipo_Servicio=D.Id_Tipo_Servicio ) as Tipo,
(SELECT Numero_Prescripcion FROM Producto_Dispensacion WHERE Id_Dispensacion = D.Id_Dispensacion LIMIT 1) AS Numero_Prescripcion,
MUN.Nombre as Municipio
FROM Dispensacion D
LEFT JOIN Positiva_Data PD ON PD.Id_Dispensacion = D.Id_Dispensacion
INNER JOIN Funcionario F
on D.Identificacion_Funcionario=F.Identificacion_Funcionario
INNER JOIN Punto_Dispensacion P
on D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
INNER JOIN Departamento L
on P.Departamento=L.Id_Departamento
Inner Join Municipio MUN on MUN.Id_Municipio =P.Municipio
INNER JOIN Paciente
on D.Numero_Documento = Paciente.Id_Paciente
INNER JOIN Regimen R
on Paciente.Id_Regimen = R.Id_Regimen
WHERE D.Id_Dispensacion=$id";

$oCon = new consulta();
$oCon->setQuery($query);
$encabezado = $oCon->getData();
unset($oCon);
$marca_agua = 'backimg="' . $_SERVER["DOCUMENT_ROOT"] . '/IMAGENES/MARCA_DE_AGUA/acta-entrega.png" backimgw="80%"';
if ($_REQUEST["productos"]) {
    $productos = (isset($_REQUEST['productos']) ? $_REQUEST['productos'] : '');

    $productos = (array) json_decode(utf8_decode($productos), true);

    $productos = getNombres($productos);

    $marca_agua = 'backimg="' . $_SERVER["DOCUMENT_ROOT"] . '/IMAGENES/MARCA_DE_AGUA/ACTA2.png" backimgw="80%"';

} else {
    $query = "SELECT PD.*, IFNULL(CONCAT_WS(' ',
    Producto.Principio_Activo,
    Producto.Presentacion,
    Producto.Concentracion, '(',
    Producto.Nombre_Comercial,')',
    Producto.Cantidad,
    Producto.Unidad_Medida
    ), Producto.Nombre_Comercial) as Nombre_Producto, Producto.Codigo_Cum as Cum,
    Producto.Invima
  FROM Producto_Dispensacion as PD
  INNER JOIN Producto
  on Producto.Id_Producto=PD.Id_Producto
  WHERE PD.Id_Dispensacion =$id  AND PD.Cantidad_Formulada>0";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
    unset($oCon);
}
/**
 * Codigo para anexar informacion del reclamante a la dispensacion
 */

$query = "SELECT * FROM Dispensacion_Reclamante WHERE Dispensacion_Id = '$id' ";
$oCon = new consulta();
$oCon->SetQuery($query);
$customReclamante = $oCon->getData()['Reclamante_Id'];
unset($oCon);

if ($customReclamante != null && $customReclamante != 'null') {
    $query = "SELECT Reclamante.* , DR.Parentesco FROM Reclamante   INNER JOIN Dispensacion_Reclamante AS DR ON Reclamante.Id_Reclamante = DR.Reclamante_Id
    WHERE Id_Reclamante = '$customReclamante'";
    $oCon = new consulta();
    $oCon->SetQuery($query);
    $customReclamante = $oCon->getData();
    unset($oCon);
} else {
    $customReclamante = ['Id_Reclamante' => '', 'Nombre' => '', 'Parentesco' => ''];
}
/****************************************************************************************************************** */

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

$auto = '';
$auto = ' N.Auto ' . $encabezado['numeroAutorizacion'] . '';

$codigos = '
    <h3 style="margin:5 0 0 0;font-size:22px;line-height:22px;">' . $encabezado["Codigo"] . '</h3>
    <h4 style="font-weight:normal;margin:5px 0 0 0;font-size:15px;line-height:15px;">' . $encabezado["Fecha_Dis"] . '</h4>
    <h5 style="font-weight:normal;margin:0 0 0 0;font-size:13px;line-height:15px;">' . $encabezado["Tipo"] . '</h5>
    <h5 style="font-weight:normal;margin:0 0 0 0;font-size:15px;line-height:13px;">Entrega ' . $encabezado['Entrega_Actual'] . ' de ' . $encabezado['Cantidad_Entregas'] . '</h5>
    <h3 style="font-weight:normal;margin:5 0 0 0;font-size:10px;line-height:10px;">' . $auto . '</h3>
    <h6 style="margin:0 0 0 0;font-size:10px;line-height:12px;text-align:right">' . $encabezado['Punto_Dispensacion'] . '-' . $encabezado['Municipio'] . '</h6>
    <h6 style="margin:0 0 0 0;font-size:10px;line-height:12px;text-align:right">(' . $encabezado['Departamento'] . ')</h6>
';

/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera = '<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="' . $_SERVER["DOCUMENT_ROOT"] . 'assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:360px;font-weight:thin;font-size:13px;line-height:18px;">
                    <strong>' . $config["Nombre_Empresa"] . '</strong><br>
                    N.I.T.: ' . $config["NIT"] . '<br>
                    ' . $config["Direccion"] . '<br>
                    Bucaramanga, Santander<br>
                    TEL: ' . $config["Telefono"] . '
                  </td>
                  <td style="width:250px;text-align:right">
                        ' . $codigos . '
                  </td>
                  <td style="width:100px;">
                  <img src="' . (($encabezado["Codigo_Qr"] == '' || !is_dir($_SERVER["DOCUMENT_ROOT"] . 'IMAGENES/QR/' . $encabezado["Codigo_Qr"])) ? $_SERVER["DOCUMENT_ROOT"] . 'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"] . 'IMAGENES/QR/' . $encabezado["Codigo_Qr"]) . '" style="max-width:100%;margin-top:-10px;" />
                  </td>
                </tr>
              </tbody>
            </table>
            ';

/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/

$contenido_nro_prescripcion = '';
$marge = 'style="margin-top:40px"';

if (strpos($encabezado["Tipo"], 'MIPRES') !== false) {
    $marge = 'style="margin-top:70px"';
    $contenido_nro_prescripcion = '
    <tr>
        <td style="font-weight:bold;font-size:10px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
            Nro Prescripción
        </td>
        <td style="font-weight:bold;font-size:10px;width:150px;background:#f3f3f3;vertical-align:middle;padding:6px;">
        </td>
        <td style="font-weight:bold;font-size:10px;width:140px;background:#f3f3f3;vertical-align:middle;padding:6px;">
        </td>
        <td style="font-weight:bold;font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:6px;">
        </td>
        <td style="font-weight:bold;font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:6px;">
        </td>
    </tr>
    <tr>
    <td style="font-size:9px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;" colspan="5">
    ' . $encabezado['Numero_Prescripcion'] . '
    ' . $encabezado['Observaciones'] . '
    </td>
    </tr>
    ';
}
/*
    <td style="font-size:9px;width:150px;background:#f3f3f3;vertical-align:middle;padding:6px;">
    </td>
    <td style="font-size:9px;width:140px;background:#f3f3f3;vertical-align:middle;padding:6px;">
    </td>
    <td style="font-size:9px;width:100px;background:#f3f3f3;vertical-align:middle;padding:6px;">
    </td>
    <td style="font-size:9px;width:100px;background:#f3f3f3;vertical-align:middle;padding:6px;">
    </td>

*/

$contenido1 = '<table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin-top:0px">
<tr>
    <td style="font-weight:bold;font-size:10px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
        Paciente
    </td>
    <td style="font-weight:bold;font-size:10px;width:150px;background:#f3f3f3;vertical-align:middle;padding:6px;">
        Identificación
    </td>
    <td style="font-weight:bold;font-size:10px;width:140px;background:#f3f3f3;vertical-align:middle;padding:6px;">
        Dirección
    </td>
    <td style="font-weight:bold;font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:6px;">
        Regimen
    </td>
    <td style="font-weight:bold;font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:6px;">
        Telefono
    </td>
</tr>
<tr>
<td style="font-size:9px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
' . utf8_decode($encabezado['Nombre_Paciente']) . '
</td>
<td style="font-size:9px;width:150px;background:#f3f3f3;vertical-align:middle;padding:6px;">
' . $encabezado['Id_Paciente'] . '
</td>
<td style="font-size:9px;width:140px;background:#f3f3f3;vertical-align:middle;padding:6px;">
' . $encabezado['Direccion_Paciente'] . '
</td>
<td style="font-size:9px;width:100px;background:#f3f3f3;vertical-align:middle;padding:6px;">
' . $encabezado['Regimen_Paciente'] . '
</td>
<td style="font-size:9px;width:100px;background:#f3f3f3;vertical-align:middle;padding:6px;">
' . $encabezado['Telefono_Paciente'] . '
</td>
</tr>
' . $contenido_nro_prescripcion . '

</table>';

$contenido .= '<table  cellspacing="0" cellpadding="0"  ' . $marge . '>
	        	    <tr>
	        		<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:6px">Producto</td>
	        		<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:6px">Cum</td>
	        		<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:6px">Lote</td>
	        		<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:6px">Cant. Formulada</td>
	        		<td style="font-weight:bold;font-size:10px;background:#c6c6c6;text-align:center;padding:6px">Cant.Entregada</td>
                    </tr>';

$solicitados = 0;
$entregados = 0;
$diferencia = 0;

foreach ($productos as $producto) {

    // echo $producto['Nombre_Producto']; exit;
    $producto['Cum'] = (isset($producto['Cum']) ? $producto['Cum'] : $producto['Codigo_Cum']);

    $invima = (strpos($encabezado["Tipo"], 'MIPRES') !== false || strpos($encabezado["Tipo"], 'POSITIVA') !== false ) ? $producto['Invima'] : '';
    // header("Content-type:application/json");echo json_encode($invima); exit;
    $contenido .= '<tr>
                    <td style="padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;width:410px;vertical-align:middle;">
                        ' . substr(utf8_decode($producto['Nombre_Producto']),0,150) . '<br>
                        <strong>INV: </strong> ' . $invima . '
	        		</td>
                    <td style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:80px;vertical-align:middle;">
                        ' . $producto['Cum'] . '
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;text-align:center;width:60px;vertical-align:middle;">
	        		' . $producto["Lote"] . '
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:60px;vertical-align:middle;">
	        		' . $producto["Cantidad_Formulada"] . '
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:60px;vertical-align:middle;">
	        		' . $producto["Cantidad_Entregada"] . '
	        		</td>
                </tr>';

    $solicitados += $producto["Cantidad_Formulada"];
    $entregados += $producto["Cantidad_Entregada"];
}

$contenido .= '</table>';

$contenido .= '<table style="margin-top:10px;text-align:right">
                                    <tr>
                                        <td style="font-size:10px;width:755px;background:#e9eef0;border-radius:5px;padding:8px;te
                                        t-align:right!important;padding:10px 8px">

                                            <strong>Productos Solicitados: </strong> ' . $solicitados . '<br>
                                            <strong>Productos Entregados: </strong> ' . $entregados . '<br>
                                            <strong>Diferencia: </strong> ' . ($solicitados - $entregados) . '<br>
                                            ' . ($encabezado['Tipo'] == 'Capita' ? '<strong >Cuota Moderadora: </strong> $' : '<strong >Cuota Recuperacion </strong> $') . number_format($encabezado['Couta'], 2, ".", ",") . '
                                        </td>
                                    </tr>
                                </table>';
$firma = '';

$contenido .= '<table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin:10px 10%;">
                            <tr>
                            <td style="font-weight:bold;font-size:10px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
                            Reclamante
                            </td>
                            <td style="font-weight:bold;font-size:10px;width:150px;background:#f3f3f3;vertical-align:middle;padding:6px;">
                            Identificación
                            </td>
                            <td style="font-weight:bold;font-size:10px;width:140px;background:#f3f3f3;vertical-align:middle;padding:6px;">
                            Parentesco
                            </td>

                            </tr>
                            <tr>
                            <td style="font-size:9px;width:176px;background:#f3f3f3;vertical-align:middle;padding:6px;">
                            ' . utf8_decode($customReclamante['Nombre']) . '
                            </td>
                            <td style="font-size:9px;width:150px;background:#f3f3f3;vertical-align:middle;padding:6px;">
                            ' . $customReclamante['Id_Reclamante'] . '
                            </td>
                            <td style="font-size:9px;width:140px;background:#f3f3f3;vertical-align:middle;padding:6px;">
                            ' . $customReclamante['Parentesco'] . '
                            </td>
                            </tr>
    </table>';

if ($encabezado['Firma'] != '') {
    $firma = '<img src="' . $MY_FILE . "DOCUMENTOS/" . $encabezado["Funcionario1"] . "/" . $encabezado['Firma'] . '"  style="max-height=100px; max-width= 230px;"> <br>';
} else {
    $firma = '<br>______________________________<br>';
}

if ($encabezado['Firma_Reclamante'] != '') {
    $firma_reclamante = '<img src="' . $MY_FILE . "IMAGENES/FIRMAS-DIS/" . $encabezado['Firma_Reclamante'] . '" style="max-height=100px; max-width= 230px;"><br>';
} else {
    $firma_reclamante = '<br><br><br><br>______________________________<br>';
}

$contenido .= '<table style="margin-top:20px">
                 <tr>
                     <td style="font-size:10px;width:355px;vertical-align:middle;padding:5px;text-align:center;">
                     <br>' . $firma . '
                         Elaborado Por<br>' . $encabezado['Funcionario'] . '
                     </td>
                     <td style="font-size:10px;width:355px;vertical-align:middle;padding:5px;text-align:center;">
                     ' . $firma_reclamante . '
                         Recibí Conforme<br>
                     </td>
                 </tr>
                </table>';

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="150px" backbottom="0px" ' . $marca_agua . '>
            <page_header>' .
    $cabecera . $contenido1 .
    '</page_header>
            <div class="page-content">
            ' . $contenido . '
            </div>
        </page>';

/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

/* echo $content;
die();
 */
try {
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('L', array(215.9, 160), 'Es', true, 'UTF-8', array(2, 0, 2, 0));
    $html2pdf->writeHTML($content);
    if ($ruta == "") {
        $direc = $encabezado['Codigo'] . '.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
        $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
    } else {
        $direc = $ruta; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
        $html2pdf->Output($direc, "F"); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
    }
} catch (HTML2PDF_exception $e) {
    echo $e;
    exit;
}

function getNombres(array $productos)
{
    $i = 0;
    foreach ($productos as $producto) {
        array_push($producto['Id']);

        $query =
            "SELECT IFNULL(CONCAT_WS(' ',
        Producto.Principio_Activo,
        Producto.Presentacion,
        Producto.Concentracion, '(',
        Producto.Nombre_Comercial,')',
        Producto.Cantidad,
        Producto.Unidad_Medida
        ), Producto.Nombre_Comercial) As Nombre_Producto,
        Producto.Codigo_Cum as Cum,
        Producto.Invima
        FROM Producto Where Producto.Id_Producto = $producto[Id]";
        $oCon = new consulta();
        $oCon->setQuery($query);
        $prop = $oCon->getData();
        $productos[$i]['Nombre_Producto'] = $prop['Nombre_Producto'];
        $productos[$i]['Cum'] = $prop['Cum'];
        // $productos[$i]['Invima'] = $prop['Invima'];
        $i++;
    }
    return $productos;
}
