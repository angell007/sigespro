<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');

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
    case 'Cotizacion_Venta':{
        $query = 'SELECT 
        CV.Fecha_Documento as Fecha , CV.Observacion_Cotizacion_Venta as observacion, CV.Codigo as Codigo, CV.Fecha_Documento_Edicion as FechaEdicion,
        CV.Condiciones_Comerciales, CV.Codigo_Qr,
        C.Id_Cliente as IdCliente ,C.Nombre as NombreCliente, C.Direccion as DireccionCliente, C.Ciudad as CiudadCliente, C.Credito as CreditoCliente, C.Cupo as CupoCliente,
        LG.Nombre as NombreGanancia, LG.Porcentaje as PorcentajeGanancia, LG.Id_Lista_Ganancia as IdLG,
        B.Nombre as NombreBodega , B.Id_Bodega as IdBodega
    FROM Cotizacion_Venta CV, Cliente C , Lista_Ganancia LG, Bodega B
    WHERE  CV.Id_Lista_Ganancia = LG.Id_Lista_Ganancia 
    AND CV.Id_Lista_Ganancia = LG.Id_Lista_Ganancia 
    AND CV.Id_Cliente = C.Id_Cliente 
    AND CV.Id_Cotizacion_Venta = '.$id ;

$oCon= new consulta();
$oCon->setQuery($query);
$acta = $oCon->getData();
unset($oCon);

$query2 = 'SELECT IFNULL(CONCAT( P.Principio_Activo, " ", P.Presentacion, " ", P.Concentracion, " (", P.Nombre_Comercial,") ", P.Cantidad," ", P.Unidad_Medida, " " ),CONCAT(P.Nombre_Comercial," LAB-",P.Laboratorio_Comercial)) as producto, P.Id_Producto, P.Codigo_Cum as Cum, P.Id_Producto as Id_Producto, P.Cantidad_Presentacion, P.Invima, (CASE WHEN P.Gravado="Si" THEN "19%" ELSE "0%" END) AS Impuesto, PCV.Cantidad as Cantidad, PCV.Precio_Venta as Precio_Venta, PCV.Iva, PCV.Subtotal as Subtotal, PCV.Id_Producto_Cotizacion_Venta as idPcv FROM Producto P , Producto_Cotizacion_Venta PCV WHERE P.Id_Producto=PCV.Id_Producto AND PCV.Id_Cotizacion_Venta =  '.$id ;


$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);
               
        
        $oItem = new complex('Funcionario',"Id_Funcionario",$data["Identificacion_Funcionario"]);
        $elabora = $oItem->getData();
        unset($oItem);
        
        $codigos ='
            <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">'.$acta["Codigo"].'</h3>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.fecha($acta["Fecha_Creacion"]).'</h5>
        ';
        $contenido = '<table style="">
            <tr>
                <td style="width:250px;">
                    <strong>Bodega: </strong> '.$acta['Nombre_Bodega'].'
                </td>
                <td style="width:250px";>
                    <strong>Factura: </strong> '.$acta['Factura'].'
                </td>
                <td style="width:250px";>
                    <strong>Fecha Factura:  </strong> '.$acta['Fecha_Factura'].'
                </td>
                
            </tr>
        </table>
        <table style="margin-top:10px">
            <tr>
                <td style="font-size:10px;width:710px;background:#e9eef0;border-radius:5px;padding:8px;">
                    <strong>Observaciones</strong><br>
                    '.$data["Observaciones"].'
                </td>
            </tr>
        </table>
        <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <tr><
        <td style="width:80px;max-width:80px;background:#cecece;font-weight:bold;border:1px solid #cccccc;">
            Categoria
        </td>
                <td style="width:350px;max-width:350px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                    Producto
                </td>
                <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Cantidad
                </td>
                <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Precio
                </td>
                <td style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Lote
                </td>
                <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Fecha Venc.
                </td>
            </tr>';

            $products = [];

            foreach ($productos as $i => $value) {
                if (array_key_exists($value["Categoria"], $products)) {
                    $array = [
                        "nro" => $i+1, 
                        "producto" => $value["Nombre_Producto"], 
                        "Cantidad" => $value["Cantidad"],
                        "Precio" => $value["Precio"],
                        "Lote" => $value["Lote"],
                        "Fecha_Vencimiento" => $value["Fecha_Vencimiento"]
                    ];
                    array_push($products[$value["Categoria"]], $array);
                } else {
                    $products[$value["Categoria"]] = [
                        [
                            "nro" => $i+1, 
                            "producto" => $value["Nombre_Producto"], 
                            "Cantidad" => $value["Cantidad"],
                            "Precio" => $value["Precio"],
                            "Lote" => $value["Lote"],
                            "Fecha_Vencimiento" => $value["Fecha_Vencimiento"]
                        ]
                    ];
                }
            }

            foreach ($products as $categoria => $prod) {
                $contenido .= '<tr>
                            <td style="width:80px;max-width:80px;vertical-align:middle;background:#f3f3f3;border:1px solid #cccccc;text-align:center;" rowspan="'.count($prod).'">'. $categoria . '</td>';
                foreach ($prod as $i => $value) {
                    
                    if ($i != 0) {
                        $contenido .= '<tr>';
                    }
                    
                    $contenido .= '
                    <td style="padding:3px 2px;width:350px;max-width:350px;font-size:9px;text-align:left;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;">'.$value["producto"] .'</td>
                    <td style="width:70px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$value["Cantidad"].'</td>
                    <td style="width:70px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$value["Precio"].'</td>
                    <td style="width:70px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$value["Lote"].'</td>
                    <td style="width:50px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$value["Fecha_Vencimiento"].'</td></tr>';

                    
                }
            }
            
         $contenido .= '</table>';

	
	$contenido .='<table style="margin-top:10px;font-size:10px;">
	<tr>
	<td style="width:240px;border:1px solid #cccccc;">
		<strong>Persona Elaboró</strong><br><br><br><br><br><br><br>
		'.$elabora["Nombres"]." ".$elabora["Apellidos"].'
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
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'/assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
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
    $html2pdf->Output($direc,'D'); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

?>