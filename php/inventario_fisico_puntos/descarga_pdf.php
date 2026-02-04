<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');

$id_inventario_fisico = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
$id_pto = isset($_REQUEST['id_pto']) ? $_REQUEST['id_pto'] : false;
$condicion = '';

if ($id_inventario_fisico) {
    $condicion = "WHERE INF.Id_Inventario_Fisico_Punto=$id_inventario_fisico";
} else {
    $condicion = "WHERE INF.Id_Punto_Dispensacion=$id_pto";
}


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
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/

        $query = 'SELECT INF.*, DATE_FORMAT(Fecha_Inicio, "%d/%m/%Y %r") AS f_inicio, DATE_FORMAT(Fecha_Fin, "%d/%m/%Y %r") AS f_fin, (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=INF.Id_Punto_Dispensacion) AS Nom_Bodega,  (SELECT CONCAT(Nombres," ",Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=INF.Funcionario_Digita) AS Funcionario_Digitador, (SELECT CONCAT(Nombres," ",Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=INF.Funcionario_Cuenta) AS Funcionario_Cuenta, (SELECT CONCAT(Nombres," ",Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=INF.Funcionario_Digita) AS Funcionario_Autorizo
         FROM Inventario_Fisico_Punto INF '.$condicion;
        $oCon= new consulta();
        $oCon->setQuery($query);
        $datos = $oCon->getData();
        unset($oCon);

        $query = 'SELECT PIF.Id_Producto_Inventario_Fisico, PIF.Id_Inventario_Fisico_Punto,  P.Nombre_Comercial, CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion, P.Cantidad," ", P.Unidad_Medida, " ") AS Nombre_Producto, PIF.Lote, PIF.Fecha_Vencimiento, PIF.Primer_Conteo AS Cantidad_Encontrada, IFNULL(PIF.Segundo_Conteo,PIF.Primer_Conteo) AS Segundo_Conteo, 
        (PIF.Segundo_Conteo-PIF.Primer_Conteo) AS Cantidad_Diferencial, IF(PIF.Cantidad_Final=0 OR PIF.Cantidad_Final IS NULL,PIF.Segundo_Conteo,PIF.Cantidad_Final) AS Cantidad_Final FROM Producto_Inventario_Fisico_Punto PIF INNER JOIN Producto P ON PIF.Id_Producto=P.Id_Producto INNER JOIN Inventario_Fisico_Punto INF ON PIF.Id_Inventario_Fisico_Punto=INF.Id_Inventario_Fisico_Punto '.$condicion.' ORDER BY P.Nombre_Comercial';
          
        $oCon= new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query);
        $productos = $oCon->getData();
        unset($oCon);

        $total = count($productos);

        $detalles = '';

        if ($id_inventario_fisico) {
            $detalles = '<h4 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">INVFP'.$datos['Id_Inventario_Fisico_Punto'].'</h4>
        <h6 style="margin:5px 0 0 0;font-weight: normal;font-size:12px;line-height:16px;">Inicio: '.$datos['f_inicio'].'</h6>
        <h6 style="margin:5px 0 0 0;font-weight: normal;font-size:12px;line-height:16px;">Fin: '.$datos['f_fin'].'</h6>';
        } 
        
        $codigos ='
            <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">Inventario Físico</h3>
            '.$detalles.'
            <h6 style="margin:5px 0 0 0;font-weight: normal;font-size:12px;line-height:16px;">Punto: '.$datos['Nom_Bodega'].'</h6>
        ';

        $contenido = '
        <table style="font-size:10px;margin-top:10px" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width:354px;max-width:236px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                 Funcionario Digitador
                </td>
                <td style="width:354px;max-width:236px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                    Funcionario Contador
                </td>
            </tr>
            <tr>
                <td style="width:236px;font-size:9px;vertical-align:middle;background:#f2f2f2;word-wrap: break-word;text-align:center;border:1px solid #cccccc;">'.$datos["Funcionario_Digitador"].'</td>
                <td style="width:236px;font-size:9px;vertical-align:middle;background:#f2f2f2;word-wrap: break-word;text-align:center;border:1px solid #cccccc;">'.$datos["Funcionario_Cuenta"].'</td>
               
            </tr>
        </table>
        ';
        
        $contenido .= '
        <table style="font-size:10px;margin-top:20px;" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width:10px;max-width:10px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                    Nro.
                </td>
                <td style="width:250px;max-width:280px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                    Producto
                </td>
                <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Lote
                </td>
                <td style="width:60px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Fecha Venc.
                </td>
                <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Primer Conteo
                </td>
                <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Segundo Conteo
                </td>
                <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Diferencia
                </td>
                <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Cantidad Final
                </td>
            </tr>';

            $diferencias = 0;

            foreach ($productos as $i => $prod) {

                $contenido .= '<tr>
                <td style="padding:3px 2px;width:10px;max-width:10px;font-size:9px;text-align:center;vertical-align:middle;border:1px solid #cccccc;word-break: break-all !important;"><b>'.($i+1).'</b></td>
                <td style="padding:3px 2px;width:250px;max-width:280px;font-size:9px;text-align:left;vertical-align:middle;border:1px solid #cccccc;word-break: break-all !important;"><b>'.$prod["Nombre_Comercial"] .'</b><br><span style="color:gray">'.$prod["Nombre_Producto"].'</span></td>
                <td style="width:50px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;">'.$prod["Lote"].'</td>
                <td style="width:60px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;">'.$prod["Fecha_Vencimiento"].'</td>
                <td style="width:70px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;">'.$prod["Cantidad_Encontrada"].'</td>
                <td style="width:70px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;">'.$prod["Segundo_Conteo"].'</td>
                <td style="width:70px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;">'.$prod["Cantidad_Diferencial"].'</td>
                <td style="width:70px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;">'.$prod["Cantidad_Final"].'</td>
                </tr>';

                 $diferencias += $prod["Cantidad_Diferencial"];
            }

            $contenido .= '
            <tr>
            <td colspan="8" style="width:70px;font-weight:bold;background:#cecece;text-align:right;border:1px solid #cccccc;">
                    Diferencia Total: '.$diferencias.'
                </td>
            </tr>
            ';
            
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
                  <td style="width:230px;text-align:right">
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
    $direc = 'listado.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

?>