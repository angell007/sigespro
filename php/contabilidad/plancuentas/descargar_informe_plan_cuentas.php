<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
require_once('../../../class/html2pdf.class.php');

$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

/* FUNCIONES BASICAS */
function fecha($str)
{
	$parts = explode(" ",$str);
	$date = explode("-",$parts[0]);
	return $date[2] . "/". $date[1] ."/". $date[0];
}
/* FIN FUNCIONES BASICAS*/

/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */

/* DATOS DEL ARCHIVO A MOSTRAR */
/*$oItem = new complex($tipo,"Id_".$tipo,$id);
$data = $oItem->getData();
unset($oItem);
/* FIN DATOS DEL ARCHIVO A MOSTRAR */

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
.cell-content{
	font-size: 10px;
	word-wrap:break-word;
}

.title-text{
	font-size: 12px;
	font-weight: bold;
	text-align: center;
	word-wrap:break-word;
	padding: 5px;
}

.title-text2{
	font-size: 16px;
	font-weight: bold;
	text-align: center;
	word-wrap:break-word;
	padding-bottom: 8px;
}

.tabla-borde{
    border: .6px solid #898a8c;
}
tr.borde-bot{
    border-bottom: .6px solid #898a8c;
}
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/
//clientes
//proveedores
//comprobantes
//factura_comprobante
//cuenta contable comprobante
//retenciones_comprobante
/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */

    $query = 'SELECT *
                FROM Plan_Cuentas';

    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $planes_cuentas = $oCon->getData();
    unset($oCon);   
    
    //var_dump($planes_cuentas);
    //exit;

    $contenido = $style.'
    	<table class="tabla-borde">
			<thead>
				<tr>
					<th colspan="20" class="title-text2"><b>Planes de Cuentas Actuales</b></th>
				</tr>
				<tr>
					<th class="title-text">Tipo Plan</th>
					<th class="title-text" style="width:50px;">Nombre Plan</th>
					<th class="title-text">Codigo</th>
					<th class="title-text">Tipo Niif</th>
					<th class="title-text" style="width:50px;">Nombre Niif</th>
					<th class="title-text">Codigo Niif</th>
					<th class="title-text">Estado</th>
					<th class="title-text">Ajuste Contable</th>
					<th class="title-text">¿Cierra Terceros?</th>
					<th class="title-text">Movimiento</th>
					<th class="title-text">Documento</th>
					<th class="title-text">Base</th>
					<th class="title-text">Valor</th>
					<th class="title-text">Porcentaje</th>
					<th class="title-text">Centro de Costo</th>
					<th class="title-text">Depreciacion</th>
					<th class="title-text">Amortizacion</th>
					<th class="title-text">Exogeno</th>
					<th class="title-text">Naturaleza</th>
					<th class="title-text">¿Maneja Nit?</th>
				</tr>
			</thead>
			<tbody>';

	foreach ($planes_cuentas as $value) {
		
		$contenido .= '
			<tr class="border-bot">
				<td class="cell-content">'.$value["Tipo_P"].'</td>
				<td class="cell-content" style="width:50px;">'.$value["Nombre"].'</td>
				<td class="cell-content">'.$value["Codigo"].'</td>
				<td class="cell-content">'.$value["Tipo_Niif"].'</td>
				<td class="cell-content" style="width:50px;">'.$value["Nombre_Niif"].'</td>
				<td class="cell-content">'.$value["Codigo_Niif"].'</td>
				<td class="cell-content">'.$value["Estado"].'</td>
				<td class="cell-content">'.TransformarValor($value["Ajuste_Contable"]).'</td>
				<td class="cell-content">'.TransformarValor($value["Cierra_Terceros"]).'</td>
				<td class="cell-content">'.TransformarValor($value["Movimiento"]).'</td>
				<td class="cell-content">'.TransformarValor($value["Documento"]).'</td>
				<td class="cell-content">'.TransformarValor($value["Base"]).'</td>
				<td class="cell-content">'.$value["Valor"].'</td>
				<td class="cell-content">'.$value["Porcentaje"].'</td>
				<td class="cell-content">'.TransformarValor($value["Centro_Costo"]).'</td>
				<td class="cell-content">'.TransformarValor($value["Depreciacion"]).'</td>
				<td class="cell-content">'.TransformarValor($value["Amortizacion"]).'</td>
				<td class="cell-content">'.TransformarValor($value["Exogeno"]).'</td>
				<td class="cell-content">'.$value["Naturaleza"].'</td>
				<td class="cell-content">'.TransformarValor($value["Maneja_Nit"]).'</td>
			</tr>';
	}

	$contenido .= '
			</tbody>
		</table>';
	
 
/* FIN SWITCH*/

/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="width:200px;" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'/IMAGENES/LOGOS/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:410px;font-weight:thin;font-size:14px;line-height:20px;">
                    '.$config["Nombre_Empresa"].'<br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    TEL: '.$config["Telefono"].'
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
    $html2pdf = new HTML2PDF('L', array(405,180), 'Es', true, 'UTF-8', array(5, 5, 2, 8));
    $html2pdf->writeHTML($content);
    $direc = 'Plan_de_Cuentas('.date("d-m-Y").').pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc,''); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

function TransformarValor($value){
	if ($value == 'N' || $value == '' || is_null($value)) {
		return 'NO';
	}else{
		return 'SI';
	}
}

?>