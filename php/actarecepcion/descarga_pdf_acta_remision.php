<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('content-type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');

$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$titulo = $tipo != '' ? 'Acta Recepcion Remision Bodegas' : 'Acta Recepcion Remision';

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
$oItem = new complex($tipo,"Id_".$tipo,$id);
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
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/

/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */
switch($tipo){
    case 'Acta_Recepcion_Remision':{

        $query = '';

        if (isset($_REQUEST['Acta_Tipo']) && $_REQUEST['Acta_Tipo'] == 'Bodega') {
            $query = 'SELECT AR.*, (SELECT Nombre FROM Bodega_Nuevo WHERE Id_Bodega_Nuevo=AR.Id_Bodega_Nuevo) as Nombre_Bodega, R.Codigo as Codigo_Remision, R.Nombre_Origen
            FROM Acta_Recepcion_Remision AR
            INNER JOIN Remision R
            ON AR.ID_Remision=R.Id_Remision
            WHERE AR.Id_Acta_Recepcion_Remision='.$id;

        } else {
            $query = 'SELECT AR.*,
                ifnull(P.Nombre, B.Nombre )as Nombre_Punto, R.Codigo as Codigo_Remision, R.Nombre_Origen, CONCAT(F.Nombres, " ", F.Apellidos) as Elabora
            FROM Acta_Recepcion_Remision AR
            LEFT JOIN Punto_Dispensacion P ON AR.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
            LEFT JOIN Bodega_Nuevo B ON AR.Id_Bodega_Nuevo=B.Id_Bodega_Nuevo
            INNER JOIN Remision R ON AR.ID_Remision=R.Id_Remision
            INNER JOIN Funcionario F ON R.Identificacion_Funcionario=F.Identificacion_Funcionario
            WHERE AR.Id_Acta_Recepcion_Remision='.$id;
        }

        $oCon= new consulta();
        //$oCon->setTipo('Multiple');
        $oCon->setQuery($query);
        $datos = $oCon->getData();
        
        // echo ($query); exit;
        unset($oCon);

        $query2 = 'SELECT P.*, IFNULL(CONCAT( PRD.Principio_Activo, " ",
        PRD.Presentacion, " ",
        PRD.Concentracion, " (", PRD.Nombre_Comercial,") ",
        PRD.Cantidad," ",
        PRD.Unidad_Medida, " " ), CONCAT(PRD.Nombre_Comercial," LAB-", PRD.Laboratorio_Comercial)) AS Nombre_Producto, PRD.Embalaje, PRD.Invima, CONCAT_WS(" / ", PRD.Laboratorio_Comercial, PRD.Laboratorio_Generico) AS Laboratorios
        FROM Producto_Acta_Recepcion_Remision P
        INNER JOIN Producto PRD
        ON P.Id_Producto=PRD.Id_Producto
        WHERE P.Id_Acta_Recepcion_Remision='.$id;
            
        $oCon= new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query2);
        $productos = $oCon->getData();        
        unset($oCon);
        $oItem = new complex('Funcionario',"Identificacion_Funcionario",$data["Identificacion_Funcionario"]);
        $recibe = $oItem->getData();
        unset($oItem);
        
        $codigos ='
            <h3 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.$titulo.'</h3>
            <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">'.$data["Codigo"].'</h3>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.fecha($data["Fecha"]).'</h5>
           
        ';
        $titulo_punto_bodega = isset($_REQUEST['Acta_Tipo']) && $_REQUEST['Acta_Tipo'] == 'Bodega' ? 'Bodega Destino' : 'Punto Destino';
        $punto_bodega = isset($_REQUEST['Acta_Tipo']) && $_REQUEST['Acta_Tipo'] == 'Bodega' ? $datos['Nombre_Bodega'] : $datos['Nombre_Punto'];
        $contenido = '<table style="">
            <tr>
                <td style="width:720px; padding-right:0px;">
                    <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;">
                        <tr>
                            <th  style=" width:230px;font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Codigo Remision</th>
                            <th  style=" width:250px;font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Bodega Origen</th>
                            <th  style=" width:250px;font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">'.$titulo_punto_bodega.'</th>
                        </tr>
                        <tr>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;text-align:center;">
                            '.$datos["Codigo_Remision"].'
                            </td>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc; text-align:center;">
                            '.$datos["Nombre_Origen"].'
                            </td>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;text-align:center;">
                            '.$punto_bodega.'
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table style="margin-top:10px">
            <tr>
                <td style="font-size:10px;width:710px;background:#e9eef0;border-radius:5px;padding:8px;">
                    <strong>Observaciones</strong><br>
                    '.$datos["Observaciones"].'
                </td>
            </tr>
        </table>
        <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <tr><
		<td style="width:10px;background:#cecece;;border:1px solid #cccccc;"></td>
                <td style="width:235px;max-width:235px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                    Producto
                </td>
                <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Presentación
                </td>
                <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Laboratorios
                </td>
                <td style="width:60px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Invima
                </td>
                <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Lote
                </td>
                <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    F. Vencimiento
                </td>
                <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Cantidad
                </td>
                <td style="width:40px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Temp.
                </td>
            </tr>';
            
            $max=0;
            foreach($productos as $prod){  $max++;
                $temperatura = $prod['Temperatura'] == '' ? 'No' : $prod['Temperatura'];
                $contenido .='<tr>
                    <td style="vertical-align:middle;width:10px;background:#f3f3f3;border:1px solid #cccccc;text-align:center;">'.$max.'</td>
                    <td style="vertical-align:middle;padding:3px 2px;width:235px;max-width:235px;font-size:9px;text-align:left;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;">'.$prod["Nombre_Producto"].'</td>
                    <td style="vertical-align:middle;width:70px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Embalaje"].'</td>
                    <td style="vertical-align:middle;width:70px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Laboratorios"].'</td>
                    <td style="vertical-align:middle;width:60px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Invima"].'</td>
                    <td style="vertical-align:middle;width:70px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Lote"].'</td>
                    <td style="vertical-align:middle;width:70px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Fecha_Vencimiento"].'</td>
                    <td style="vertical-align:middle;width:50px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Cantidad"].'</td>
                    <td style="vertical-align:middle;width:40px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$temperatura.'</td>
                </tr>';
            }
            
         $contenido .= '</table>';

	
	$contenido .='<table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
	<tr>
	<td style="width:362px;border:1px solid #cccccc;">
		<strong>Persona Elaboró</strong><br><br><br><br><br>
		'.$datos["Elabora"].'
	</td> 
	<td style="width:364px;border:1px solid #cccccc;">
		<strong>Persona Recibe</strong><br><br><br><br><br>
		'.$recibe["Nombres"]." ".$recibe["Apellidos"].'
	</td> 
	
	</tr>
	</table>';
	
        break;
    }
}
/* FIN SWITCH*/

/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
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
                  <td style="width:150px;text-align:right">
                        '.$codigos.'
                  </td>
                  <td style="width:100px;">
                  <img src="'.($data["Codigo_Qr"] =='' ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$data["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-10px;" />
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