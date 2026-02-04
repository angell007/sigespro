<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');

$tipo = (isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : '');
$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');

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
$query = "SELECT D.*, DATE_FORMAT(D.Fecha_Actual, '%d/%m/%Y') as Fecha_Dis, CONCAT(F.Nombres, ' ',  F.Apellidos) as Funcionario, P.Nombre as Punto_Dispensacion, P.Direccion as Direccion_Punto, P.Telefono Telefono_Punto, L.Nombre as Departamento, CONCAT_WS(' ',Paciente.Primer_Nombre,Paciente.Segundo_Nombre,Paciente.Primer_Apellido, Paciente.Segundo_Apellido) as Nombre_Paciente , Paciente.EPS, Paciente.Direccion as Direccion_Paciente, R.Nombre as Regimen_Paciente, Paciente.Id_Paciente,  Paciente.Tipo_Documento, (SELECT CONCAT(S.Nombre,'-' ,T.Nombre) FROM Tipo_Servicio T INNER JOIN Servicio S ON T.Id_Servicio=S.Id_Servicio WHERE T.Id_Tipo_Servicio=D.Id_Tipo_Servicio ) as Tipo
FROM Dispensacion D
INNER JOIN Funcionario F
on D.Identificacion_Funcionario=F.Identificacion_Funcionario
INNER JOIN Punto_Dispensacion P
on D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
INNER JOIN Departamento L
on P.Departamento=L.Id_Departamento
INNER JOIN Paciente
on D.Numero_Documento = Paciente.Id_Paciente
INNER JOIN Regimen R
on Paciente.Id_Regimen = R.Id_Regimen
WHERE D.Id_Dispensacion=$id";

$oCon = new consulta();
$oCon->setQuery($query);
$data = $oCon->getData();
unset($oCon);

if ($data['Tipo'] == 'Pos-Capita') {
	$cuota = " MODERADORA";
} else {
	$cuota = " RECUPERACION";
}
/* FIN DATOS DEL ARCHIVO A MOSTRAR */

ob_start(); // Se Inicializa el gestor de PDF

/* HOJA DE ESTILO PARA PDF*/
$style = '<style>
.page-content{
width:55mm;;
}
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/

$oItem = new complex('Paciente', "Id_Paciente", $data["Numero_Documento"]);
$paciente = $oItem->getData();
unset($oItem);

$oItem = new complex('Paciente_Telefono', "Id_Paciente", $data["Numero_Documento"]);
$telefono = $oItem->getData()['Numero_Telefono'];
unset($oItem);

$oItem = new complex('Regimen', "Id_Regimen", $paciente["Id_Regimen"]);
$regimen = $oItem->getData();
unset($oItem);

$oItem = new complex('Cliente', "Id_Cliente", $paciente["Nit"]);
$cliente = $oItem->getData();
unset($oItem);

$query = 'SELECT PD.*, P.Nombre_Comercial
         FROM Producto_Dispensacion PD
         INNER JOIN Producto P
         ON PD.Id_Producto = P.Id_Producto
          WHERE PD.Cantidad_Formulada > PD.Cantidad_Entregada AND PD.Id_Dispensacion =  ' . $id;

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

$contenido = '
        <table style="width:60mm;margin:0;padding:0" cellspacing="0" cellpadding="0">
        	<tr>
        		<td colspan="2">
	        		<p style="font-size:11px;text-align:center;">
	        		-----------------------------------------------------------------------------------<br>
	        		PRODUCTOS HOSPITALARIOS S.A.<br>804.016.084-5<br>' . $data['Punto_Dispensacion'] . '
	        		</p>
        		</td>
        	</tr>
        	<tr>
        		<td style="width:25mm;font-size:11px;padding-left:5px;padding-top:5px;;"><br><br><br>
        			DISPENSACION
        		</td>
        		<td style="width:40mm;font-size:11px;padding-right:5px;padding-top:5px;;"><br><br><br>
        			' . $data["Codigo"] . '
        		</td>
        	</tr>
        	<tr>
        		<td style="width:25mm;font-size:11px;padding-left:5px;padding-top:5px;;">
        			FECHA
        		</td>
        		<td style="width:40mm;font-size:11px;padding-right:5px;padding-top:5px;;">
        			' . $data["Fecha_Actual"] . '
        		</td>
        	</tr>
        	<tr>
        		<td style="width:25mm;font-size:11px;padding-left:5px;padding-top:5px;;">
        			PACIENTE
        		</td>
        		<td style="width:40mm;font-size:11px;padding-right:5px;padding-top:5px;;">
        			' . $data['Nombre_Paciente'] . '
        		</td>
        	</tr>
        	<tr>
        		<td style="width:25mm;font-size:11px;padding-left:5px;padding-top:5px;;">
        			TELEFONO
        		</td>
        		<td style="width:40mm;font-size:11px;padding-right:5px;padding-top:5px;;">
        			' . $telefono . '
        		</td>
			</tr>
			<tr>
			<td style="width:25mm;font-size:11px;padding-left:5px;padding-top:5px;;">
				DOCUMENTO
			</td>
			<td style="width:40mm;font-size:11px;padding-right:5px;padding-top:5px;;">
				' . $data['Tipo_Documento'] . " " . $data['Numero_Documento'] . '
			</td>
			</tr>
        	<tr>
        		<td style="width:25mm;font-size:11px;padding-left:5px;padding-top:5px;;">
        			REGIMEN
        		</td>
        		<td style="width:40mm;font-size:11px;padding-right:5px;padding-top:5px;;">
        			' . $regimen["Nombre"] . '
        		</td>
        	</tr>
        	<tr>
        		<td style="width:25mm;font-size:11px;padding-left:5px;padding-top:5px;;">
        			EPS
        		</td>
        		<td style="width:40mm;font-size:11px;padding-right:5px;padding-top:5px;;">
        			' . $paciente["EPS"] . '
        		</td>
        	</tr>
        	<tr>
        		<td style="width:25mm;font-size:11px;padding-left:5px;padding-top:5px;;">
        			CUOTA ' . $cuota . '
        		</td>
        		<td style="width:40mm;font-size:11px;padding-right:5px;padding-top:5px;;">
        			' . $data["Cuota"] . '
        		</td>
        	</tr>
        	<tr>
        		<td style="width:25mm;font-size:11px;padding-left:5px;padding-top:5px;;">
        			TIPO SERVICIO
        		</td>
        		<td style="width:40mm;font-size:11px;padding-right:5px;padding-top:5px;;">
        			' . $data["Tipo"] . $data['Servicio'] . '
        		</td>
			</tr>';

if ($data["Tipo"] != "Pos-Capita") {
	$contenido .= '<tr>
        		<td style="width:25mm;font-size:11px;padding-left:5px;padding-top:5px;;">
        			AUTORIZACION
        		</td>
        		<td style="width:40mm;font-size:11px;padding-right:5px;padding-top:5px;;">
        			' . $productos[0]['Numero_Autorizacion'] . '
        		</td>
        	</tr>';
}



$contenido .= '<tr>
        		<td colspan="2" style="text-align:left;font-size:11px;padding-left:5px;padding-top:5px;;"><br><br>
        			 PRODUCTOS PENDIENTES
        		</td>
			</tr>';

$contenido .= '<tr>
			<td colspan="2" style="width:60mm;text-align:left;font-size:9px;padding-left:5px;padding-top:5px;"><b>SEÑOR USUARIO USTED TIENE HASTA 15 DIAS PARA RECLAMAR SUS PENDIENTE </b>
			</td>
		</tr>';

$contenido .= '</table><br>';


$contenido .= '<table style="width:60mm;margin:0;padding:0" cellspacing="0" cellpadding="0">
        <tr>
	        		<td style="width:10mm;font-size:11px;text-align:center;">Sol</td>
	        		<td style="width:10mm;font-size:11px;text-align:center;">Ent</td>
	        		<td style="width:40mm;font-size:11px;text-align:center;">Producto</td>
	        	</tr>';
foreach ($productos as $prod) {
	$contenido .= '<tr>
	        		<td style="width:10mm;font-size:11px;text-align:center;">' . $prod["Cantidad_Formulada"] . '</td>
	        		<td style="width:10mm;font-size:11px;text-align:center;">' . $prod["Cantidad_Entregada"] . '</td>
	        		<td style="width:40mm;font-size:11px;text-align:left;;">' . $prod["Nombre_Comercial"] . '</td>
	        	</tr>';
}


$contenido .= '</table>';
$contenido .= '<table style="width:60mm;margin:0;padding:0" cellspacing="0" cellpadding="0">
        	<tr>
        		<td style="width:25mm;font-size:11px;padding-left:5px;padding-top:5px;;"><br><br><br>
        			ARTICULOS
        		</td>
        		<td style="width:40mm;font-size:11px;padding-right:5px;padding-top:5px;;"><br><br><br>
        			' . count($productos) . '
        		</td>
        	</tr>
        	<tr>
        		<td style="width:25mm;font-size:11px;padding-left:5px;padding-top:5px;;">
        			CLIENTE
        		</td>
        		<td style="width:40mm;font-size:11px;padding-right:5px;padding-top:5px;;">
        			' . $cliente["Nombre"] . '
        		</td>
        	</tr>
        	<tr>
        		<td style="width:25mm;font-size:11px;padding-left:5px;padding-top:5px;;">
        			NIT/CC
        		</td>
        		<td style="width:40mm;font-size:11px;padding-right:5px;padding-top:5px;;">
        			' . $cliente["Id_Cliente"] . '
        		</td>
        	</tr>
        	<tr>
        		<td style="width:25mm;font-size:11px;padding-left:5px;padding-top:5px;;">
        			DIRECCION
        		</td>
        		<td style="width:40mm;font-size:11px;padding-right:5px;padding-top:5px;;">
        			' . $cliente["Direccion"] . '
        		</td>
        	</tr>
        	<tr>
        		<td style="width:25mm;font-size:11px;padding-left:5px;padding-top:5px;;">
        			TELEFONO
        		</td>
        		<td style="width:40mm;font-size:11px;padding-right:5px;padding-top:5px;;">
        			' . $cliente["Telefono"] . '
        		</td>
        	</tr>
        	</table>
        	';

$imagen_firma = $data["Firma_Reclamante"] != "" ? '<img style="width:60mm;" src="' . $_SERVER["DOCUMENT_ROOT"] . 'IMAGENES/FIRMAS-DIS/' . $data["Firma_Reclamante"] . '" />' : '';

$contenido .= '<table style="width:60mm;margin:0;padding:0" cellspacing="0" cellpadding="0">
        	<tr>
        		<td  style="width:60mm;text-align:center;">
        		' . $imagen_firma . '
        		_____________________________
        		Nombre:<br><br><br>
        		Cedula:<br><br><br>
        		Telefono:<br><br><br>
        		</td>
        	</tr>
        </table>
        ';

/* FIN SWITCH*/



/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="0mm" backbottom="0mm">
                <div class="page-content">' .
	$contenido . '
                </div>
            </page>';
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
	$html2pdf = new HTML2PDF('P', array(65,300), 'Es', true, 'UTF-8', array(0, 0, 0, 0));
	// $html2pdf->addFont('freemono','','freemono.php');
    $html2pdf->writeHTML($content);
    $direc = $data["Codigo"].'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}
?>