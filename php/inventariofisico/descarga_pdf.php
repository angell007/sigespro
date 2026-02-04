<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');

$idCategoria = ( isset( $_REQUEST['idCategoria'] ) ? $_REQUEST['idCategoria'] : '' );
$idBodega = ( isset( $_REQUEST['idBodega'] ) ? $_REQUEST['idBodega'] : '' );
$Letras = ( isset( $_REQUEST['Letras'] ) ? $_REQUEST['Letras'] : '' );
$Contador = ( isset( $_REQUEST['Contador'] ) ? $_REQUEST['Contador'] : '' );
$Digitador = ( isset( $_REQUEST['Digitador'] ) ? $_REQUEST['Digitador'] : '' );
$id_fisico = ( isset( $_REQUEST['Id_Inventario_Fisico'] ) ? $_REQUEST['Id_Inventario_Fisico'] : '' );

$oItem = new complex("Inventario_Fisico","Id_Inventario_Fisico",$id_fisico);
$fisico = $oItem->getData();
unset($oItem);

$oItem = new complex("Funcionario","Identificacion_Funcionario",$Contador);
$func_contador = $oItem->getData();
unset($oItem);

$oItem = new complex("Funcionario","Identificacion_Funcionario",$Digitador);
$func_digitador = $oItem->getData();
unset($oItem);

$oItem = new complex("Bodega","Id_Bodega",$idBodega);
$bodega = $oItem->getData();
unset($oItem);
if($idCategoria!="Todas"){
    $oItem = new complex("Categoria","Id_Categoria",$idCategoria);
    $categoria = $oItem->getData();
    unset($oItem);
}else{
    $categoria["Nombre"]="Todas";
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

$cond='';
if($idCategoria!="Todas"){
    $cond=' AND PRD.Id_Categoria ='.$idCategoria;
}

$let = explode("-",$Letras);

$order = 'PRD.Nombre_Comercial';
$o='';

if($idCategoria==12){
    $order = 'Nombre_Producto';
    $o ='HAVING ';
}
        
$fin = ''.$o;
foreach($let as $l){
    $fin.=$order.' LIKE "'.$l.'%" OR ';
}
$fin = trim($fin," OR ");

if($fin!=''){
    if($o==''){ 
        $cond.=' AND ('.$fin.') GROUP BY I.Id_Producto'; 
    }else{
        $cond.=' GROUP BY I.Id_Producto '.$fin; 
    }
}

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

        $query = 'SELECT PRD.Nombre_Comercial, CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion, PRD.Cantidad," ", PRD.Unidad_Medida, " ") AS Nombre_Producto, PRD.Laboratorio_Comercial, IFNULL(PRD.Laboratorio_Generico, "Sin Laboratorio Generico") AS Laboratorio_Generico, PRD.Embalaje, PRD.Codigo_Cum FROM Inventario I
          INNER JOIN Producto PRD
          ON I.Id_Producto = PRD.Id_Producto
          WHERE  I.Cantidad>0 AND  I.Id_Bodega='.$idBodega.$cond .'  ORDER BY '.$order;
          
      /*    echo $query;
         exit; */
          
        $oCon= new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query);
        $productos = $oCon->getData();
        unset($oCon);

        $total = count($productos);

        $codigos ='
            <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">Inventario Físico</h3>
            <h4 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">'.$total.' Productos</h4>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">F.I.: '.$fisico["Fecha_Inicio"].'</h5>
            <h5 style="margin:5px 0 0 0;font-size:8px;line-height:8px;text-transform:uppercase;">Contador: '.$func_contador["Nombres"]." ".$func_contador["Apellidos"].'</h5>
            <h5 style="margin:5px 0 0 0;font-size:8px;line-height:8px;text-transform:uppercase;">Digitador: '.$func_digitar["Nombres"]." ".$func_digitador["Apellidos"].'</h5>
        ';
        $contenido = '
        <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width:10px;max-width:10px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                    Nro.
                </td>
                <td style="width:250px;max-width:250px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                    Producto
                </td>
                <td style="width:150px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Embalaje
                </td>
                <td style="width:60px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Codigo CUM
                </td>
                <td style="width:115px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Lab. Comercial
                </td>
                <td style="width:115px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Lab. Generico
                </td>
            </tr>';

            foreach ($productos as $i => $prod) {

                $contenido .= '<tr>
                <td style="padding:3px 2px;width:10px;max-width:10px;font-size:9px;text-align:center;vertical-align:middle;border:1px solid #cccccc;word-break: break-all !important;"><b>'.($i+1).'</b></td>
                <td style="padding:3px 2px;width:250px;max-width:250px;font-size:9px;text-align:left;vertical-align:middle;border:1px solid #cccccc;word-break: break-all !important;"><b>'.$prod["Nombre_Producto"].'</b><br><span style="color:gray">'.$prod["Nombre_Comercial"] .'</span></td>
                <td style="width:150px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;">'.$prod["Embalaje"].'</td>
                <td style="width:60px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;">'.$prod["Codigo_Cum"].'</td>
                <td style="width:115px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;">'.$prod["Laboratorio_Comercial"].'</td>
                <td style="width:115px;font-size:9px;vertical-align:middle;word-wrap: break-word;text-align:center;border:1px solid #cccccc;">'.$prod["Laboratorio_Generico"].'</td></tr>';

                    
            }
            
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
                    TEL: '.$config["Telefono"].'<br><strong style="font-size:11px;line-height:13px;">
                    Bodega: '.$bodega["Nombre"].'<br>
                    Categoría: '.$categoria["Nombre"].'<br>
                    Letras: '.$fisico["Letras"].'</strong>
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