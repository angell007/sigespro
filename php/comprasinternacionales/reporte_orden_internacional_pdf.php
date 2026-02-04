<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/class.querybasedatos.php');

$id_orden = ( isset( $_REQUEST['id_orden'] ) ? $_REQUEST['id_orden'] : '' );

$queryObj = new QueryBaseDatos();

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
$orden_compra = GetOrdenCompraInternacional($id_orden);
$productos_orden_compra = GetProductosOrdenCompraInternacional($id_orden);
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
.titular{
    font-size: 11px;
    text-transform: uppercase;
    margin-bottom: 0;
  }
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/

/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */
        
$codigos ='
    <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">'.$orden_compra["Codigo"].'</h3>
    <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.fecha($orden_compra["Fecha_Registro"]).'</h5>
';

//ENCABEZADO DEL PDF
//INFORMACION DE LA EMPRESA PROH
$contenido .= '
<table style="font-size:10px;" cellpadding="0" cellspacing="0">
    <tr>
        <td rowspan="4" style="width:150px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;">
            <img src="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
        </td>
        <td colspan="3" style="width:220px;max-width:230px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;font-weight:bold;">NIT: '
            .$config["NIT"]
        .'</td>
    </tr>
    <tr>
        <td colspan="4" style="width:220px;max-width:230px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;font-weight:bold;">Dirección: '.$config["Direccion"]
        .'</td>
    </tr>
    <tr>
        <td colspan="4" style="width:220px;max-width:230px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;font-weight:bold;">PBX: (57) '.$config["Telefono"]
        .'</td>
    </tr>
    <tr>
        <td colspan="4" style="width:220px;max-width:230px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;font-weight:bold;" >Bucaramanga - Colombia</td>
    </tr>';

//INFORMACION DEL PROVEEDOR Y LA ORDEN
$contenido .= '
    <tr>
        <td style="width:150px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
            COMPANY NAME:
        </td>
        <td colspan="3" style="width:220px;max-width:230px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;">'
            .$orden_compra["Proveedor"].
        '</td>
    </tr>
    <tr>
        <td style="width:150px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
            ADDRESS:
        </td>
        <td colspan="3" style="width:220px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;">'
            .$orden_compra["Direccion"].
        '</td>
    </tr>
    <tr>
        <td style="width:150px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
            CITY/TOWN:
        </td>
        <td style="width:200px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;"></td>
        <td style="width:150px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
            PROVINCE/STATE:
        </td>
        <td style="width:200px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;"></td>
    </tr>
    <tr>
        <td style="width:150px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
            CONTACT:
        </td>
        <td style="width:200px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;">'
            .$orden_compra["Asesor_Comercial"].
        '</td>        
        <td colspan="2" style="width:150px;max-width:230px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;">ORDER NO: '
            .$orden_compra["Codigo"].
        '</td>
    </tr>
    <tr>
        <td style="width:150px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
            TEL: '.$orden_compra["Telefono"].'
        </td>
        <td style="width:200px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
            FAX:
        </td>
        <td style="width:150px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
            PORT DESTINATION:
        </td>
        <td style="width:200px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
            '.$orden_compra["Puerto_Destino"].'
        </td>
    </tr>
    <tr>
        <td style="width:150px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
            COUNTRY/REGION: '.$orden_compra["Pais_Proveedor"].'
        </td>
        <td colspan="3" style="width:150px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
            DATE: '.$orden_compra["Pais_Proveedor"].'
        </td>
    </tr>
    <tr>
        <td style="width:150px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
            POSTAL CODE:
        </td>
        <td colspan="3" style="width:150px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
            CAPACITY:
        </td>
    </tr>
</table>';

//ENCABEZADO DE LA TABLA DE LOS PRODUCTOS
$contenido .= '
    <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:90px;max-width:90px;background:#cecece;font-weight:bold;border:1px solid #cccccc;">
                QUANTITY/PCS
            </td>
            <td style="width:220px;max-width:230px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                DESCRIPTION PRODUCT PACKING IN BLISTER
            </td>
            <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               Unit Price FOB SHANGAI USD
            </td>
            <td style="width:85px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               TOTAL USD
            </td>
            <td style="width:60px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                CARTONS
            </td>
            <td style="width:75px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                CARTON MEAS.
            </td>
            <td style="width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                MEAS. PROH
            </td>
        </tr>';

//GENERAR ROWS CON INFORMACION DE LOS PRODUCTOS
foreach ($productos_orden_compra['productos'] as $p) {
        
    $contenido .= '
    <tr>
        <td style="padding:3px 2px;width:90px;max-width:90px;font-size:9px;text-align:right;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;">'
            .$p["Cantidad"] 
        .'</td>
        <td style="width:220px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'
            .$p["Nombre_Ingles"]
        .'</td>
        <td style="width:70px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:right;background:#f3f3f3;border:1px solid #cccccc;">USD '
            .number_format($p["Costo"],4,",",".")
        .'</td>
        <td style="width:85px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:right;background:#f3f3f3;border:1px solid #cccccc;">USD '
            .number_format($p["Subtotal"],2,",",".")
        .'</td>
        <td style="width:60px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'
            .$p["Cantidad_Caja"]
        .'</td>
        <td style="width:75px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'
            .$p["Caja_Ancho"].'x'.$p["Caja_Alto"].'x'.$p["Caja_Largo"]
        .'</td>
        <td style="width:80px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'
            .$p["Caja_Volumen"]
        .'</td>
    </tr>'
    ;
}


//FOOTER DE LA TABLA DE PRODUCTOS CON LOS TOTALES
$contenido .= '
<tr>
    <td style="padding:3px 2px;width:90px;max-width:90px;font-size:9px;text-align:right;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;font-weight:bold;">'
        .$productos_orden_compra['totales']['total_cantidad']
    .'</td>
    <td style="width:220px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;"></td>
    <td style="width:70px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:right;background:#f3f3f3;border:1px solid #cccccc;"> </td>
    <td style="width:85px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:right;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">USD '
        .number_format($productos_orden_compra['totales']["subtotal"],2,",",".")
    .'</td>
    <td style="width:60px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">'
        .$productos_orden_compra['totales']['total_cajas']
    .'</td>
    <td style="width:75px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;"></td>
    <td style="width:80px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">'
        .$productos_orden_compra['totales']['total_volumen']
    .'</td>
</tr>';
    
 $contenido .= '</table>';

//TABLA INFERIOR CON OBSERVACIONES Y DEMAS INFORMACIÓN
$contenido .= '
    <table style="font-size:10px;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:180px;max-width:180px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
                OBSERVATIONS:
            </td>
            <td colspan="2" style="width:220px;max-width:230px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'
                .$orden_compra['Observaciones']
            .'</td>
        </tr>
        <tr>
            <td style="width:180px;max-width:180px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
                AUTHORIZATION COMPANY:
            </td>
            <td colspan="2" style="width:220px;max-width:230px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;"></td>
        </tr>
        <tr>
            <td style="width:180px;max-width:180px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
                TOTAL CARTONS:
            </td>
            <td style="width:70px;max-width:70px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">'
                .$productos_orden_compra['totales']['total_cajas']
            .'</td>
            <td style="width:465px;max-width:465px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;"> Container '.GetContainers($productos_orden_compra['totales']['total_volumen']).'" HQ (68 Mtrs3)</td>
        </tr>
    </table>';

$contenido .='<table style="margin-top:10px;font-size:10px;">
<tr>
<td style="width:730px;border:1px solid #cccccc;">
<strong>Persona Elaboró</strong><br><br><br><br><br><br><br>
'.$orden_compra["Funcionario"].'
</td>
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
                  <td style="width:150px;text-align:right">
                        '.$codigos.'
                  </td>
                  <td style="width:100px;">
                  <img src="'.($orden_compra["Codigo_Qr"] =='' ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$orden_compra["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-10px;" />
                  </td>
                </tr>
              </tbody>
            </table><hr style="border:1px dotted #ccc;width:730px;">';
/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="0mm" backbottom="0mm">
                <div class="page-content" >'.
                    $contenido.'
                </div>
            </page>';
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('P', 'A4', 'Es', true, 'UTF-8', array(5, 5, 5, 5));
    $html2pdf->writeHTML($content);
    $direc = $orden_compra["Codigo"].'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

function GetOrdenCompraInternacional($id_orden){
    global $queryObj;

    $query = '
        SELECT 
            OCI.*,
            CONCAT_WS(" ", P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) AS Proveedor, P.Nombre as Proveedor,
            P.Direccion,
            P.Telefono,
            P.Pais aS Pais_Proveedor,
            P.Asesor_Comercial,
            CONCAT_WS(" ", F.Nombres, F.Apellidos) AS Funcionario,
            B.Nombre As Bodega
        FROM Orden_Compra_Internacional OCI
        INNER JOIN Proveedor P ON OCI.Id_Proveedor = P.Id_Proveedor
        INNER JOIN Bodega B ON OCI.Id_Bodega = B.Id_Bodega
        INNER JOIN Funcionario F ON OCI.Identificacion_Funcionario = F.Identificacion_Funcionario
        WHERE
            OCI.Id_Orden_Compra_Internacional ='.$id_orden;

    $queryObj->SetQuery($query);
    $orden_compra = $queryObj->ExecuteQuery('simple');
    return $orden_compra;
}

function GetProductosOrdenCompraInternacional($id_orden){
    global $queryObj;

    $totales = array('total_cajas' => 0, 'total_volumen' => 0, 'subtotal' => 0, 'total_cantidad' => 0);
    $result = array('productos' => array(), 'totales' => array());

    $query = '
        SELECT 
            POCI.*,
            P.Nombre_Comercial AS Nombre_Producto,
            IFNULL(P.Nombre_Listado, "Name not set") AS Nombre_Ingles,
            P.Embalaje
        FROM Producto_Orden_Compra_Internacional POCI
        INNER JOIN Producto P ON POCI.Id_Producto = P.Id_Producto
        WHERE
            POCI.Id_Orden_Compra_Internacional ='.$id_orden;

    $queryObj->SetQuery($query);
    $productos_orden = $queryObj->ExecuteQuery('multiple');
    
    if (count($productos_orden) > 0) {
        $cantidad_total=0;
        $volumen_total=0;
        $subtotal=0;
        $total_cantidad = 0;

        foreach ($productos_orden as $po) {
            $cantidad_total += $po['Cantidad_Caja'];
            $volumen_total += floatval($po['Caja_Volumen']);
            $subtotal += $po['Subtotal'];
            $total_cantidad += $po['Cantidad'];
        }

        $totales['total_cajas'] = $cantidad_total;
        $totales['total_volumen'] = number_format($volumen_total, 3, ".","");
        $totales['subtotal'] = $subtotal;
        $totales['total_cantidad'] = $total_cantidad;
    }


    $result['totales'] = $totales;
    $result['productos'] = $productos_orden;

    return $result;
}

function GetContainers($volumen_total){
    $result = 0;

    $result = number_format((floatval($volumen_total)/68), 4, ",","");
    return $result;
}

?>