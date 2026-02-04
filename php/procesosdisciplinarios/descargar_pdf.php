<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/NumeroALetra.php');

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

$query = 'SELECT PD.*, CONCAT(F.Nombres," ",F.Apellidos) as Funcionario, F.Identificacion_Funcionario as Id_Funcionario, C.Nombre as Cargo
FROM Proceso_Disciplinario PD
INNER JOIN Funcionario F ON F.Identificacion_Funcionario = PD.Identificacion_Funcionario
INNER JOIN Cargo C ON C.Id_Cargo = F.Id_Cargo
AND PD.Id_Proceso_Disciplinario ='.$id ;

$oCon= new consulta();
$oCon->setQuery($query);
$data = $oCon->getData();
unset($oCon);   




$query='SELECT APD.*, CONCAT_WS(" ", FR.Nombres,FR.Apellidos) as Funcionario_Reporta
FROM Actividad_Proceso_Disciplinario APD
INNER JOIN Funcionario FR ON FR.Identificacion_Funcionario = APD.Funcionario_Reporta
WHERE APD.Id_Proceso_Disciplinario ='.$id;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$actuaciones = $oCon->getData();
unset($oCon); 

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




$codigos ='
    <span style="margin:-5px 0 0 0;font-size:16px;line-height:16px;">Proceso Disciplinario</span>
    <h3 style="margin:0 0 0 0;font-size:22px;line-height:22px;">PD'.$data["Id_Proceso_Disciplinario"].'</h3>
    <h5 style="margin:5px 0 0 0;font-size:11px;line-height:11px;">F. Inicio.:'.fecha($data["Fecha"]).'</h5>
';

//<img src="../../assets/images/logo-color.png" style="width:60px;" alt="Siges Pro Software" />   
//<img src="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/logo-color.png" style="width:60px;" alt="Siges Pro Software" />

/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'/assets/images/logo-color.png" style="width:60px;" alt="Siges Pro Software" />
                  </td>
                  <td class="td-header" style="width:460px;font-weight:thin;font-size:13px;line-height:18px;">
                    <strong>'.$config["Nombre_Empresa"].'</strong><br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br>
                    Bogota, D.C.<br> 
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
            </table>
            
            <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin-top:20px;">
                <tr>
                    <td style="font-size:10px;width:80px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Funcionario:</strong>
                    </td>
                    <td style="font-size:10px;width:490px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    '.$data["Funcionario"].'
                    </td>
                    <td style="font-size:10px;width:70px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>C.C.:</strong>
                    </td>
                    <td style="font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    '.number_format($data["Id_Funcionario"],0,",",".").'
                    </td>
                </tr>
            </table>
            <hr style="border:1px dotted #ccc;width:730px;">';
           
/* PIE DE PAGINA */

$pie='
';

$contenido = '<table  cellspacing="0" cellpadding="0" >
	        	    <tr>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">Fecha</td>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">Reporta</td>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">Actividad</td>
	        	    </tr>';
			    $total_iva = 0;
	        	    foreach($actuaciones as $act){ 
	        	    	$contenido.='<tr>
	        		<td style="padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;width:80px;vertical-align:middle;">
	        		'.fecha($act["Fecha"]).'
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;width:80px;vertical-align:middle;">
	        		'.$act['Funcionario_Reporta'].'
                    </td>
                    <td style="padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;width:570px;vertical-align:middle;">
	        		'.$act['Actividad'].'
	        		</td>
	        	    </tr>';  
                    }
    $contenido .='</table>';	             
	             
/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="140px">
		        <page_header>'.
                    $cabecera.
		        '</page_header>
                <div class="page-content">
                <h5>ACTUACIONES REPORTADAS EN EL PROCESO DISCIPLINARIO</h5>
                 '.$contenido.'
                 <p style="margin-top:100px;margin-left:30px"><strong>Nota: </strong> El Presente Proceso Disciplinario costituye prueba irrefutable del comportamiento del trabajador y las acciones emprendidas por COMERCIALIZADORA DE CARNES SAN FELIPE, con el fin de mejorar constantemente.</p>

                <table style="margin-top:50px">    
                    <tr>
                        <td style="width:400px;padding-left:60px">
                        <table>
                        <tr>
                            <td style="width:300px;font-weight:bold; border-top:1px solid black; text-align:center;">'.$data['Funcionario'].'</td>
                            <td style="width:30px;"></td>
                            <td style="width:300px;font-weight:bold; border-top:1px solid black; text-align:center;">'.$config['Representante_Legal'].'</td>
                        </tr>
                        <tr>
                            <td style="width:300px;font-weight:bold; text-align:center;">'.$data['Cargo'].' </td>    
                            <td style="width:30px;"></td>    
                            <td style="width:300px;font-weight:bold; text-align:center;">Representante Legal</td>    
                        </tr>
                        
                        </table>
                        </td>    
                    </tr>
                </table>
                </div>
            </page>';
            
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

// echo $content;
// exit;

try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
   $html2pdf = new HTML2PDF('P', 'LETTER', 'Es', true, 'UTF-8', array(2, 2, 2, 2));
   $html2pdf->writeHTML($content);
   $direc = $data["Id_Proceso_Disciplinario"].'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
   $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

?>