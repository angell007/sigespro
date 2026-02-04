<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
require_once('../../../class/html2pdf.class.php');

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


        $query = "SELECT
            PD.Departamento AS Id_Departamento,
            (
            SELECT
                Nombre
            FROM
                Departamento
            WHERE
                Id_Departamento = PD.Departamento
        ) AS Departamento,
        COUNT(DISTINCT(I.Id_Producto)) AS Cant_Producto,
        SUM(I.Cantidad) AS Cantidad,
        IFNULL(ROUND(SUM(Cantidad * (
                        COALESCE( CP.Costo_Promedio,0 )
                    )),
        2),
        0) AS Costo
        FROM
            Inventario_Nuevo I
        INNER JOIN Punto_Dispensacion PD ON
            I.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion
        LEFT JOIN Costo_Promedio CP ON CP.Id_Producto = I.Id_Producto
        WHERE
          I.Id_Punto_Dispensacion != 0
        GROUP BY
            PD.Departamento";
        
        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $puntos = $oCon->getData();
        unset($oCon);
               
        
        $codigos ='
            <h4 style="margin:5px 0 0 0;font-size:19px;line-height:22px;">INVENTARIO VALORIZADO</h4>
            <h4 style="margin:5px 0 0 0;font-size:19px;line-height:22px;">PUNTOS DISPENSACIÓN</h4>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.date('d/m/Y').'</h5>
        ';
        

    $contenido = '<table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width:100px;max-width:150px;font-weight:bold;text-align:center;background:#cecece;;border:1px solid #cccccc;">
            Departamento
        </td>
        <td style="width:100px;max-width:150px;font-weight:bold;text-align:center;background:#cecece;;border:1px solid #cccccc;">
            Punto Dispensación
        </td>
        <td style="width:130px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
           Cantidad Productos
        </td>
        <td style="width:130px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
           Cantidad
        </td>
        <td style="width:180px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
            Costo
        </td>
    </tr>';

    $totalCant = 0;
    $totalCosto = 0;

    foreach ($puntos as $punto) {
        
        $contenido .= '<tr>
        <td style="width:114px;padding:4px;max-width:114px;text-align:center;border:1px solid #cccccc;">
            '.$punto['Departamento'].'
        </td>
        <td style="width:114px;padding:4px;max-width:114px;text-align:center;border:1px solid #cccccc;">
            '.$punto['Punto'].'
        </td>
        <td style="width:144px;text-align:center;padding:4px;border:1px solid #cccccc;">
            '.number_format($punto['Cant_Producto'],0,"",".").'
        </td>
        <td style="width:114px;text-align:center;padding:4px;border:1px solid #cccccc;">
            '.number_format($punto['Cantidad'],0,"",".").'
        </td>
        <td style="width:114px;text-align:right;padding:4px;border:1px solid #cccccc;">
            $.'.number_format($punto['Costo'],2,",",".").'
        </td>
    </tr>';

    $totalCant += $punto['Cantidad'];
    $totalCosto += $punto['Costo'];
        
    }

    $contenido .= '<tr>
    <td colspan="3" style="padding:4px;text-align:left;border:1px solid #cccccc;font-weight:bold;font-size:12px">Totales:</td>
    <td style="padding:4px;font-weight:bold;text-align:center;border:1px solid #cccccc;">
        '.number_format($totalCant,0,"",".").'
    </td>
    <td style="padding:4px;font-weight:bold;text-align:right;border:1px solid #cccccc;">
        $.'.number_format($totalCosto,2,",",".").'
    </td>
    </tr>';

    $contenido .= '</table>';


	

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

?>