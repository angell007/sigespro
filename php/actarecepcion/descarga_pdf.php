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
.titular{
    font-size: 11px;
    text-transform: uppercase;
    margin-bottom: 0;
  }
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/

/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */
switch($tipo){
    case 'Acta_Recepcion':{
        $query = 'SELECT P.*, IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," (",PRD.Nombre_Comercial, ") ", PRD.Cantidad," ", PRD.Unidad_Medida, " "),
        CONCAT(PRD.Nombre_Comercial, " LAB-", PRD.Laboratorio_Comercial)) as Nombre_Producto, C.Nombre AS Categoria, IFNULL(PRD.Invima,"No Tiene") as Invima, PRD.Codigo_Cum
        FROM Producto_Acta_Recepcion P
        INNER JOIN Producto PRD
        ON P.Id_Producto=PRD.Id_Producto
        LEFT JOIN Subcategoria C
        ON PRD.Id_Subcategoria=C.Id_Subcategoria
        WHERE P.Id_Acta_Recepcion='.$id.' ORDER BY PRD.Id_Categoria, Nombre_Producto';
     
        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $productos = $oCon->getData();
        unset($oCon);

        $query = 'SELECT AR.*, (SELECT GROUP_CONCAT(F.Factura SEPARATOR " / ") FROM Factura_Acta_Recepcion F WHERE F.Id_Acta_Recepcion=AR.Id_Acta_Recepcion GROUP BY F.Id_Acta_Recepcion) AS Factura, (SELECT GROUP_CONCAT(F.Fecha_Factura SEPARATOR " / ") FROM Factura_Acta_Recepcion F WHERE F.Id_Acta_Recepcion=AR.Id_Acta_Recepcion GROUP BY F.Id_Acta_Recepcion) AS Fecha_Factura, 
         (
        CASE
            WHEN AR.Tipo_Acta="Bodega"  THEN (SELECT Nombre FROM Bodega WHERE Id_Bodega=AR.Id_Bodega)
            WHEN AR.Tipo_Acta="Punto_Dispensacion" THEN (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=AR.Id_Punto_Dispensacion)
        END
    ) as Nombre_Bodega
        , (SELECT PAR.Codigo_Compra FROM Producto_Acta_Recepcion PAR WHERE PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion GROUP BY AR.Id_Acta_Recepcion) AS Codigo_Compra, P.Nombre as NombreProveedor, P.Direccion as DireccionProveedor, P.Telefono as TelefonoProveedor
                    FROM Acta_Recepcion AR
                    INNER JOIN Proveedor P
                    On P.Id_Proveedor = AR.Id_Proveedor
                    WHERE AR.Id_Acta_Recepcion='.$id;
        
        $oCon= new consulta();
        $oCon->setQuery($query);
        $acta = $oCon->getData();
        unset($oCon);
               
        
        $oItem = new complex('Funcionario',"Identificacion_Funcionario",$data["Identificacion_Funcionario"]);
        $elabora = $oItem->getData();
        unset($oItem);
        
        $codigos ='
            <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">'.$acta["Codigo"].'</h3>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.fecha($acta["Fecha_Creacion"]).'</h5>
            <h6 style="margin:5px 0 0 0;font-size:15px;line-height:14px;">Acta Tecnico Administrativa</h6>
         
        ';
        $contenido = '<table style="background: #e6e6e6;">
            <tr style=" min-height: 100px;
            background: #e6e6e6;
            padding: 15px;
            border-radius: 10px;
            margin: 0;">
                <td  style="width:355px; padding-right:10px;">
                    <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;">
                    <tr style="margin-bottom: 0;">
                        <td> <h5 style="font-size:16px; color:#4099ff;margin-bottom: 0;">Compra:'.$acta["Codigo_Compra"].' </h5></td>
                    </tr>
                    <tr style="margin-bottom: 0;">
                        <td >
                        <p style="font-size: 11px; text-transform: uppercase; margin-bottom: 0;"><b>Bodega:</b> '.$acta["Nombre_Bodega"].'</p>
                        </td>
                       
                    </tr>
                    <tr style="margin-bottom: 0;">
                        <td >
                        <p style="font-size: 11px; text-transform: uppercase; margin-bottom: 0;"><b>Factura:</b> '.$acta["Factura"].'</p>
                        </td>
                        
                    </tr>
                </table>
                </td>
                <td  style="width:355px; ">
                    <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;">
                    <tr style="margin-bottom: 0;">
                    <td style="width:355px; " ><h5 style="font-size:13px; color:#4099ff;margin-bottom: 0;">Proveedor:'.$acta["NombreProveedor"].' </h5></td>
                    </tr>
                    <tr style="margin-bottom: 0;">
                        <td >
                        <p style="font-size: 11px; text-transform: uppercase; margin-bottom: 0;"><strong>Dirección:</strong> '.$acta["DireccionProveedor"].'</p>
                        </td>
                       
                    </tr>
                    <tr style="margin-bottom: 0;">
                        <td >
                        <p style="font-size: 11px; text-transform: uppercase; margin-bottom: 0;"><strong>Teléfono :</strong> '.$acta["TelefonoProveedor"].'</p>
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
                    '.$data["Observaciones"].'
                </td>
            </tr>
        </table>
        <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <tr><
        <td style="width:80px;max-width:80px;background:#cecece;font-weight:bold;border:1px solid #cccccc;">
            Categoria
        </td>
                <td style="width:250px;max-width:250px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
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
                <td style="width:40px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Temp.
                </td>
                <td style="width:40px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Cumple
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
                        "Fecha_Vencimiento" => $value["Fecha_Vencimiento"],
                        "Impuesto"=>$value["Impuesto"],
                        "Invima"=>$value['Invima'],
                        "Codigo_Cum"=>$value['Codigo_Cum'],
                        "Temperatura"=>$value['Temperatura'],
                        "Cumple"=>$value['Cumple']
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
                            "Fecha_Vencimiento" => $value["Fecha_Vencimiento"],
                            "Impuesto"=>$value["Impuesto"],
                            "Invima"=>$value['Invima'],
                            "Codigo_Cum"=>$value['Codigo_Cum'],
                            "Temperatura"=>$value['Temperatura'],
                            "Cumple"=>$value['Cumple']
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
                    <td style="padding:3px 2px;width:250px;max-width:250px;font-size:9px;text-align:left;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;">'.$value["producto"] .' <br>  <strong>Invima:</strong> '.$value['Invima'].' - <strong>Codigo Cum </strong> '.$value['Codigo_Cum'].'</td>
                    <td style="width:70px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.number_format($value["Cantidad"],0,",",".").'</td>
                    <td style="width:70px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">$'.number_format($value["Precio"],2,",",".").'</td>
                    <td style="width:70px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$value["Lote"].'</td>
                    <td style="width:50px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$value["Fecha_Vencimiento"].'</td>
                    <td style="width:40px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$value["Temperatura"].'</td>
                    <td style="width:40px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$value["Cumple"].'</td>
                    
                    </tr>';
                    $subtotal += $value["Cantidad"] * $value["Precio"];
                    $iva += ($value["Cantidad"] * $value["Precio"]) * ($value["Impuesto"]/100);
                    $total = $subtotal+$iva;
                    
                }
            }
            
         $contenido .= '</table>';

         $contenido .= '<table style="margin-top:10px">
         <tr>
             <td style="font-size:10px;width:663px;background:#e9eef0;border-radius:5px;padding:8px;text-align:right;padding:30px 20px">
                 
                 <strong>SubTotal: </strong> $'.number_format($subtotal,2,",",".").'<br><br>
                 <strong>Iva: </strong> $'.number_format($iva,2,",",".").'<br><br>
                 <strong>Total: </strong> $'.number_format($total,2,",",".").'
             </td>
         </tr>
     </table>';

	$contenido .='<table style="margin-top:10px;font-size:10px;">
	<tr>
	<td style="width:730px;border:1px solid #cccccc;">
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
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:350px;font-weight:thin;font-size:14px;line-height:20px;">
                    '.$config["Nombre_Empresa"].'<br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    TEL: '.$config["Telefono"].'
                  </td>
                  <td style="width:210px;text-align:right">
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