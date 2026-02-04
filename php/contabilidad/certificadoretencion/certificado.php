<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
require_once('../../../class/html2pdf.class.php');
include_once('../../../class/NumeroALetra.php');

$fecha_inicial = $_REQUEST['Fecha_Inicial'];
$fecha_final = $_REQUEST['Fecha_Final'];
$cuentas = $_REQUEST['Cuentas'];
$nit = $_REQUEST['Nit'];
$tipo_nit = $_REQUEST['Tipo_Nit'];
$fecha_expedicion = $_REQUEST['Fecha_Expedicion'];

/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */

$condicion = getCondiciones();
$datos_tercero = getDatosTercero($nit, $tipo_nit);
$retenciones = getConceptos($condicion);

$totales = [
    "Base" => 0,
    "Retencion" => 0
];


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

/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */

$contenido = '
<h4 style="text-align:center">REGIMEN COMÚN</h4>
<h5 style="text-align:center">CERTIFICADO RETENCIÓN APLICADOS</h5>
';

$contenido .= '
<table>
    <tr>
        <td>
            <strong>Año Gravable</strong>
        </td>
        <td style="padding-left:20px;padding-right:10px">
            '.getAnio($fecha_inicial).'
        </td>
        <td>
        <strong>Desde</strong>
        </td>
        <td style="padding-left:20px;padding-right:10px">
            '.fecha($fecha_inicial).'
        </td>
        <td>
        <strong>Hasta</strong>
        </td>
        <td style="padding-left:20px;padding-right:10px">
            '.fecha($fecha_final).'
        </td>
    </tr>
</table>

<p style="text-align:center">
  Para dar cumplimiento a las disposiciones vigentes sobre retenciones aplicadas se certifica que:
</p>
';

$contenido .= '

<table>
    <tr>
        <td>
            <strong>Hemos retenido a:</strong>
        </td>
        <td style="padding-left:20px;">
            '.$datos_tercero['Nombre'].'
        </td>
    </tr>
    <tr>
        <td>
            <strong>Nit ó C.C:</strong>
        </td>
        <td style="padding-left:20px;">
            '.$nit.'
        </td>
    </tr>
    <tr>
        <td colspan="2"></td>
    </tr>
    <tr>
        <td>
            <strong>Dirección:</strong>
        </td>
        <td style="padding-left:20px;">
            '.$datos_tercero['Direccion'].'
        </td>
    </tr>
    <tr>
        <td>
            <strong>Ciudad:</strong>
        </td>
        <td style="padding-left:20px;">
            '.strtoupper($datos_tercero['Ciudad']).'
        </td>
    </tr>
</table>

<p style="margin-left:95px">por los conceptos detallados acontinuación:</p>

';

$contenido .= '
    <table cellpadding="0" cellspacing="0">
        <tr>
            <th style="padding: 0 2px 0;border:0.5px solid #000;width:80px">Cod</th>
            <th style="padding: 0 2px 0;border:0.5px solid #000;border-right:none;width:340px">Descripción</th>
            <th style="padding: 0 2px 0;border:0.5px solid #000;border-right:none;width:95px;text-align:right">Porcentaje %</th>
            <th style="padding: 0 2px 0;border:0.5px solid #000;border-right:none;width:95px;text-align:right">Base</th>
            <th style="padding: 0 2px 0;border:0.5px solid #000;width:95px;text-align:right">Total</th>
        </tr>';

        foreach ($retenciones as $value) {
          $base = calcularBase($value['Porcentaje'],$value['Total'],$value['Codigo']);
            $contenido .= '
            <tr>
                <td style="width:80px;max-width:80px">'.$value['Codigo'].'</td>
                <td style="width:340px;max-width:340px">'.$value['Nombre_Cuenta'].'</td>
                <td style="width:95px;max-width:95px;text-align:right">'.number_format((float)$base['porcentaje'],2,",",".").' %</td>
                <td style="width:95px;max-width:95px;text-align:right">'.number_format((float)$base['base'],2,",",".").'</td>
                <td style="width:95px;max-width:95px;text-align:right">'.number_format((float)$value['Total'],2,",",".").'</td>
            </tr>';
            $totales['Base'] += $base['base'];
            $totales['Retencion'] += $value['Total'];
        }

    
    $contenido .= '
    <tr>
            <td colspan="3" style="font-weight:bold;padding-left:2px;border-top: 2px solid #CECECE">
                TOTALES
            </td>
            <td style="text-align:right;font-weight:bold;border-top: 2px solid #CECECE">
                '.number_format((float)$totales['Base'],2,",",".").'
            </td>
            <td style="text-align:right;font-weight:bold;border-top: 2px solid #CECECE">
                '.number_format((float)$totales['Retencion'],2,",",".").'
            </td>
        </tr>
    </table>';

$numero = number_format($totales['Retencion'], 2, '.','');
$letras = NumeroALetras::convertir($numero);

$contenido .= '
    <table cellpadding="0" cellspacing="0" style="margin-top:20px;">
        <tr>
            <td style="text-align:center;vertical-align:center;border:1px solid #CECECE;width:150px;padding:10px;font-weight:bold">
                VALOR EN LETRAS
            </td>
            <td style="text-align:center;vertical-align:center;border:1px solid #CECECE;width:515px;max-width:515px;padding:10px;font-size:10px">
                '.$letras.' PESOS COLOMBIANOS
            </td>
        </tr>
    </table>
';

$pie = '
    <p>El valor retenido fue consignado oportunamente a la Dirección de Impuestos y Aduanas Nacionales DIAN</p>
    <p>El valor retenido por concepto de industria y comercio fue consignado oportunamente en la ciudad de BUCARAMANGA</p>
    <p>'.strFechaExpedicion($fecha_expedicion).'</p>
    <p>Este documento no requiere firma autografa de acuerdo con el articulo 1.6.1.12.12 del dut 1625 de octubre 11 del 2016</p>
';

/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
              <tbody>
                <tr>
                  <td style="text-align:center;width:100px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/LogoProh.jpg" style="width:70px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="text-align:center;width:560px;font-weight:thin;font-size:14px;line-height:20px;">
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
                <page_footer>'.$pie.'</page_footer>
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

/* FUNCIONES BASICAS */
function fecha($str)
{
	$parts = explode(" ",$str);
	$date = explode("-",$parts[0]);
	return $date[2] . "/". $date[1] ."/". $date[0];
}

function getAnio($str) {
    return date('Y',strtotime($str));
}

function strFechaExpedicion($fecha) {
    $meses = ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
    
    $dia = date('d',strtotime($fecha));
    $mes = date('n',strtotime($fecha));
    $anio = date('Y',strtotime($fecha));

    $mes = $meses[$mes-1];

    $str = "Este certificado se expide a los $dia del mes de $mes del $anio";

    return $str;
}

function getDatosTercero($nit,$tipo_nit) {
    
    $query = "
    (SELECT CONCAT_WS(' ',Nombres,Apellidos) AS Nombre, Direccion_Residencia AS Direccion, Lugar_Nacimiento AS Ciudad FROM Funcionario WHERE Identificacion_Funcionario = $nit)
    UNION
    (SELECT IF(Primer_Nombre != '',CONCAT_WS(' ',Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido),Razon_Social) AS Nombre, Direccion, (SELECT Nombre FROM Municipio WHERE Id_Municipio = C.Id_Municipio) AS Ciudad FROM Cliente C WHERE Id_Cliente = $nit)
    UNION
    (SELECT IF(Primer_Nombre != '',CONCAT_WS(' ',Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido),Razon_Social) AS Nombre, Direccion, (SELECT Nombre FROM Municipio WHERE Id_Municipio = P.Id_Municipio) AS Ciudad FROM Proveedor P WHERE Id_Proveedor = $nit)
    ";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $datos = $oCon->getData();
    unset($oCon);

    return $datos;
}

function getConceptos($condicion) {
    $query = "SELECT 
            PC.Codigo, 
            PC.Nombre AS Nombre_Cuenta,
            ifnull(TR.Porcentaje, IF(PC.Porcentaje < 1,PC.Porcentaje*100,PC.Porcentaje)) AS Porcentaje, 
            (SUM(Haber_Niif) - SUM(Debe_Niif)) AS Total 
            FROM Movimiento_Contable MC 
            INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas 
            Left Join Retencion TR on TR.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
            $condicion GROUP BY MC.Id_Plan_Cuenta";
            
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    unset($oCon);
    return $resultado;
}

function getCondiciones() {
    global $fecha_inicial;
    global $fecha_final;
    global $cuentas;
    global $nit;

    $condicion = '';
    if (isset($fecha_inicial) && $fecha_inicial != "" && isset($fecha_final) && $fecha_final != "") {
        $fecha_inicio = $fecha_inicial;
        $fecha_fin = $fecha_final;
        $condicion .= " WHERE (DATE(MC.Fecha_Movimiento) BETWEEN '$fecha_inicio' AND '$fecha_fin')";
    }
    if (isset($cuentas) && $cuentas != '') {
        $condicion .= " AND MC.Id_Plan_Cuenta IN ($cuentas)";
    }
    if (isset($nit) && $nit != '') {
        $condicion .= " AND MC.Nit = $nit";
    }
    #Modulo 33 es cierre de año contable
    $condicion .= ' AND MC.Estado = "Activo" #AND MC.Id_Modulo !=33 
    ';

    return $condicion;
}

function calcularBase($porcentaje,$retencion,$cod) {
    global $config;
    $por1 = 100;
    $porM = $porcentaje;
    $data=[];
    $data['porcentaje'] = $porcentaje;
        
    $base = $porcentaje > 0 ? $retencion * $por1 / ($porM ) : '0';
    
    $data['base'] = $base;
    return $data;
}


/* FIN FUNCIONES BASICAS*/

?>