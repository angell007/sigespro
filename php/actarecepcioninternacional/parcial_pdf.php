<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/class.querybasedatos.php');

$id_orden = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$queryObj = new QueryBaseDatos();
$datos_procentajes=[];
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
$parcial = GetParcial($id_orden);
$productos_orden_compra = GetProductosParcial($id_orden);
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
$codigos ='
    <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">'.$parcial["Codigo"].'</h3>
    <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.fecha($parcial["Fecha_Registro"]).'</h5>
';
/* FIN HOJA DE ESTILO PARA PDF*/
$cabecera='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'/IMAGENES/LOGOS/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:830px;font-weight:thin;font-size:14px;line-height:20px;">
                    '.$config["Nombre_Empresa"].'<br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    TEL: '.$config["Telefono"].'
                  </td>
                  <td style="width:150px;text-align:right">
                        '.$codigos.'
                  </td>
                 
                </tr>
              </tbody>
            </table><hr style="border:1px dotted #ccc;width:730px;">';
/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */
        



$contenido .= '<table style="font-size:10px;" cellpadding="0" cellspacing="0"> 

    <tr>
        <td style="width:205px;text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
           Tasa
        </td>
        <td style="width:210px;text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
        Flete Internacional
         </td>
         <td style="width:210px;text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
         Seguro Internacional
      </td>
        <td style="width:205px;text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
        Flete Nacional
        </td>
      <td style="width:205px;text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
      Licencia Importación
     </td>
    </tr>
    <tr>
    <td style="font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc; text-align:center;">
    $ '.$parcial['Tasa_Cambio'].'
   </td>
    <td style="font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc; text-align:center;">
    '.$datos_procentajes['Porcentaje_Flete'].'%
   </td>
    <td style="font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc; text-align:center;">
    '.$datos_procentajes['Porcentaje_Seguro'].'%
   </td>
    <td style="font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc; text-align:center;">
    $ '.$datos_procentajes['Adicional_Flete_Nacional'].'
   </td>
    <td style="font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:left;background:#f3f3f3;border:1px solid #cccccc; text-align:center;">
    $ '.$datos_procentajes['Adicional_Licencia_Importacion'].'
   </td>
      
    </tr>
    
 
</table> 
<br>
<table style="font-size:10px;width:900px" cellpadding="0" cellspacing="0">

<tr style="width:900px">
<td colspan="4" style="width:900px;text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
Gastos Varios
</td>
</tr>
  <tr>
    <td style="width:270px;text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
    Tramite Sia
    </td>
    <td style="width:260px;text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
    Tercero Tramite Sia
    </td>
    <td style="width:260px;text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
    Formularios
    </td>
    <td style="width:255px;text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
    Tercero Formularios
    </td>
  </tr>
  <tr>
    <td style="text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;border:1px solid #cccccc;">
    $ '.$parcial['Tramite_Sia'].'
    </td>
    <td style="text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;border:1px solid #cccccc;">
    '.$parcial['Tercero_Tramite_Sia'].'-'.$parcial['Nombre_Tercero_Tramite_Sia'].'
    </td>
    <td style="text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;border:1px solid #cccccc;">
    $ '.$parcial['Formulario'].'
    </td>
    <td style="text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;border:1px solid #cccccc;">
    '.$parcial['Tercero_Formulario'].'-'.$parcial['Nombre_Tercero_Formulario'].'
    </td>
  </tr>
 
  <tr>
    <td style="text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
    Gastos Bancarios
    </td>
    <td style="text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
    Tercero Gastos Bancarios
    </td>
    <td style="text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
    Cargues
    </td>
    <td style="text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;background:#f3f3f3;border:1px solid #cccccc;font-weight:bold;">
    Tercero Cargues
    </td>
  </tr>
  
  <tr>
    <td style="text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;border:1px solid #cccccc;">
    $ '.$parcial['Gasto_Bancario'].'
    </td>
    <td style="text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;border:1px solid #cccccc;">
    '.$parcial['Tercero_Gasto_Bancario'].' - '.$parcial['Nombre_Tercero_Gasto_Bancario'].'
    </td>
    <td style="text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;border:1px solid #cccccc;">
    $ '.$parcial['Cargue'].'
    </td>
    <td style="text-align:center;font-size:9px;vertical-align:middle;word-wrap: break-word;border:1px solid #cccccc;">
    '.$parcial['Tercero_Cargue'].' - '.$parcial['Nombre_Tercero_Cargue'].'
    </td>
  </tr>
</table>
';

//ENCABEZADO DE LA TABLA DE LOS PRODUCTOS
$contenido .= '
    <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:350px;max-width:220px;background:#cecece;font-weight:bold;border:1px solid #cccccc;">
                Producto
            </td>
            <td style="width:60px;max-width:90px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;text-align:center;">
              Lote
            </td>
            <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
              Cantidad
            </td>
            <td style="width:65px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               P. Unitario
            </td>
            <td style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               % Arancel
            </td>
            <td style="width:75px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                Sub. Flete
            </td>
            <td style="width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                Sub. Seguro
            </td>
            <td style="width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               Arancel
            </td>
            <td style="width:80px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                P.U. Final
            </td>
            <td style="width:105px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                Subtotal
            </td>
        </tr>';
$total=0;
//GENERAR ROWS CON INFORMACION DE LOS PRODUCTOS
foreach ($productos_orden_compra as $p) {
    $total+=$p["Subtotal"];
    $contenido .= '
    <tr>
        <td style="font-size:9px;text-align:right;border:1px solid #cccccc;text-align:left;word-break: break-all !important; word-wrap:break-word;">
        '.$p["Nombre_Comercial"].'
        </td>
        <td style="font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;">'
            .$p["Lote"]  
        .'</td>
        <td style="font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:right;border:1px solid #cccccc;"> '
            .$p["Cantidad"]
        .'</td>
        <td style="font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:right;border:1px solid #cccccc;">$ '
            .number_format($p["Precio_Unitario_Pesos"],2,",",".")
        .'</td>
        <td style="font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;">'
            .$p["Porcentaje_Arancel"]
        .'%</td>
        <td style="font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;text-align:right"> $ '
            .number_format($p["Total_Flete"],2,",",".")
        .'</td>
        <td style="font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;text-align:right">$ '
        .number_format($p["Total_Seguro"],2,",",".")
        .'</td>
        <td style="font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;text-align:right">$ '
        .number_format($p["Total_Arancel"],2,",",".")
        .'</td>
        <td style="font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;text-align:right">$ '
        .number_format($p["Precio_Unitario_Final"],2,",",".")
        .'</td>
        <td style="font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;text-align:right">$ '
        .number_format($p["Subtotal"],2,",",".")
        .'</td>
    </tr>'
    ;
}
$contenido .= '<tr>
    <td colspan="8"  style="font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
    </td>
    <td style="font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
    Total
 </td>
 <td style="font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;text-align:right">$ '
 .number_format($total,2,",",".")
 .'</td>
</tr></table>';

//FOOTER DE LA TABLA DE PRODUCTOS CON LOS TOTALES


//TABLA INFERIOR CON OBSERVACIONES Y DEMAS INFORMACIÓN


$contenido .='<table style="margin-top:10px;font-size:10px;">
<tr>
<td style="width:1065px;border:1px solid #cccccc;">
<strong>Persona Elaboró</strong><br><br><br><br><br><br><br>
'.$parcial["Funcionario"].'
</td>
</tr>
</table>';




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
    $html2pdf = new HTML2PDF('L', 'A4', 'Es', true, 'UTF-8');
    $html2pdf->writeHTML($content);
    $direc = $parcial["Codigo"].'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

function GetParcial($id_orden){
    global $queryObj;

    $query = 'SELECT 
    NP.*,
    (SELECT 
        IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido))
     FROM Proveedor
    WHERE Id_Proveedor = ARI.Tercero_Flete_Nacional) AS Nombre_Tercero_Flete_Nacional,
    (SELECT 
        IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido))
     FROM Proveedor
    WHERE Id_Proveedor = NP.Tercero_Tramite_Sia) AS Nombre_Tercero_Tramite_Sia,
    (SELECT 
        IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido))
     FROM Proveedor
    WHERE Id_Proveedor = ARI.Tercero_Licencia_Importacion) AS Nombre_Tercero_Licencia_Importacion,
    (SELECT 
        IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido))
     FROM Proveedor
    WHERE Id_Proveedor = NP.Tercero_Formulario) AS Nombre_Tercero_Formulario,
    (SELECT 
        IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido))
     FROM Proveedor
    WHERE Id_Proveedor = NP.Tercero_Cargue) AS Nombre_Tercero_Cargue,
    (SELECT 
        IF((Primer_Nombre IS NULL OR Primer_Nombre = ""), Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido))
     FROM Proveedor
    WHERE Id_Proveedor = NP.Tercero_Gasto_Bancario) AS Nombre_Tercero_Gasto_Bancario,(SELECT CONCAT(Nombres,"", Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=ARI.Identificacion_Funcionario) as Funcionario
FROM Nacionalizacion_Parcial NP
INNEr JOIN Acta_Recepcion_Internacional ARI ON NP.Id_Acta_Recepcion_Internacional = ARI.Id_Acta_Recepcion_Internacional
WHERE
    NP.Id_Nacionalizacion_Parcial = '.$id_orden;

    $queryObj->SetQuery($query);
    $orden_compra = $queryObj->ExecuteQuery('simple');
    return $orden_compra;
}

function GetProductosParcial($id_orden){
    global $queryObj, $datos_procentajes;

    

    $query = ' SELECT 
    PNP.*,
    P.Nombre_Comercial,
    IFNULL(P.Nombre_Listado, "No english name") AS Nombre_Ingles,
    P.Embalaje,
    IF(P.Gravado = "No", 0, 19) AS Gravado,
    PARI.Lote
FROM Producto_Nacionalizacion_Parcial PNP
INNER JOIN Producto P ON PNP.Id_Producto = P.Id_Producto
INNER JOIN  Producto_Acta_Recepcion_Internacional PARI ON PNP.Id_Producto_Acta_Recepcion_Internacional = PARI.Id_Producto_Acta_Recepcion_Internacional
WHERE
    PNP.Id_Nacionalizacion_Parcial ='.$id_orden;

    $queryObj->SetQuery($query);
    $productos_orden = $queryObj->ExecuteQuery('multiple');
    
    $datos_procentajes=$productos_orden[0];

    return $productos_orden;
}


?>