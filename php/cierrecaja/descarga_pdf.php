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
$ingresos=0;
$egresos=0;
/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */
switch($tipo){
    case 'Diario_Cajas_Dispensacion':{
        $query = 'SELECT D.Codigo, DATE_FORMAT(D.Fecha_Actual,"%Y-%m-%d") as Fecha, COUNT(PD.Id_Producto_Dispensacion) As Productos, CONCAT_WS(" ",P.Primer_Nombre, P.Primer_Apellido, P.Segundo_Apellido) AS Paciente, D.Cuota 
        FROM Dispensacion D
        INNER JOIN Producto_Dispensacion PD
        ON D.Id_Dispensacion=PD.Id_Dispensacion
        INNER JOIN Diario_Cajas_Dispensacion DC
        ON D.Id_Diario_Cajas_Dispensacion=DC.Id_Diario_Cajas_Dispensacion
        INNER JOIN Paciente P
        ON D.Numero_Documento=P.Id_Paciente
        WHERE D.Id_Diario_Cajas_Dispensacion='.$id.'
        GROUP BY D.Id_Dispensacion';
        
        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $productos = $oCon->getData();
        unset($oCon);
      
        $query = 'SELECT DC.*, PD.Nombre as Punto, CONCAT_WS(" ",F.Nombres,F.Apellidos) AS Funcionario, DATE_FORMAT(DC.Fecha,"%Y-%m-%d") as Fecha, DATE_FORMAT(DC.Fecha_Inicio,"%Y-%m-%d") as FechaInicio, DATE_FORMAT(DC.Fecha_Fin,"%Y-%m-%d") as FechaFin
        FROM Diario_Cajas_Dispensacion DC
        INNER JOIN Funcionario F
        ON DC.Identificacion_Funcionario=F.Identificacion_Funcionario
        INNER JOIN Punto_Dispensacion PD 
        On DC.Id_Punto_Dispensacion=PD.Id_Punto_Dispensacion
        WHERE DC.Id_Diario_Cajas_Dispensacion='.$id;
        
        $oCon= new consulta();
        $oCon->setQuery($query);
        $acta = $oCon->getData();
        unset($oCon);
        $condicion='';
        if($acta['FechaInicio']&&$acta['FechaFin']){
            $condicion.="WHERE Fecha BETWEEN '".$acta['FechaInicio']." 00:00:00' AND '".$acta['FechaFin']." 23:59:59'  AND Id_Punto_Dispensacion=".$acta['Id_Punto_Dispensacion'];
        }else{
            $condicion.="WHERE Fecha = '".$acta['Fecha']."'";
        }
        $query = "SELECT * FROM `Gastos_Cajas_Dispensaciones` ".$condicion;
        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $gastos = $oCon->getData();
        unset($oCon);
        
        $oItem = new complex('Funcionario',"Identificacion_Funcionario",$data["Identificacion_Funcionario"]);
        $elabora = $oItem->getData();
        unset($oItem);
        
        $codigos ='
            <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">PPC00'.$acta["Id_Diario_Cajas_Dispensacion"].'</h3>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">'.fecha($acta["Fecha"]).'</h5>
        ';
        $contenido = '<table >
            <tr style=" min-height: 100px;
           
            padding: 15px;
            border-radius: 10px;
            margin: 0;">
                <td  style="width:710px; padding-right:10px;">
                    <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;">
                    <tr style="margin-bottom: 0;">
                    <td style="width:250px; font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Punto</td>
                    <td style="width:250px;font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Funcionario</td>
                    <td style="width:100px;font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Fecha Inicio</td>
                    <td style="width:100px;font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Fecha Fin </td>
                    </tr>
                    <tr style="margin-bottom: 0;">
                    <td style="font-size:10px;text-align:center;width:250px;background:#f3f3f3;border:1px solid #cccccc;">
                    '.$acta["Punto"].'
                    </td>
                    <td style="font-size:10px;text-align:center;width:250px;background:#f3f3f3;border:1px solid #cccccc;">
                    '.$acta["Funcionario"].'
                    </td>
                    <td style="font-size:10px;text-align:center;width:100px;background:#f3f3f3;border:1px solid #cccccc;">
                    '.$acta["FechaInicio"].'
                    </td>
                    <td style="font-size:10px;width:100px;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">
                    '.$acta["FechaFin"].'
                    </td>
                       
                    </tr>
                    
                </table>
                </td>
                
                
            </tr>
        </table>
        <table style="margin-top:10px">
            <tr>
                <td style="font-size:10px;width:705px;background:#e9eef0;border-radius:5px;padding:8px;">
                    <strong>Observaciones</strong><br>
                    '.$data["Observaciones"].'
                </td>
            </tr>
        </table>
        <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
        <tr>
              <td colspan="4" style="text-align:center;font-weight:bold;background:#cecece;">
                   <h6 style="font-size:16px;border:1px solid #cccccc;">Ingresos </h6>
                </td>
        </tr>
            <tr>
   
                <td style="text-align:center;width:100px;max-width:100px;font-weight:bold;background:#cecece;border:1px solid #cccccc;">
                    Fecha
                </td>
                <td style="width:100px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Dispensacion
                </td>
                <td style="width:400px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                   Paciente
                </td>
                
                <td style="width:100px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                    Cuota 
                </td>
            </tr>';

            foreach($productos as $prod){
                $cuota = $prod["Cuota"] != '' ? $prod["Cuota"] : 0;  
                $contenido .='<tr>
                    <td style="padding:3px 2px;width:100px;max-width:100px;font-size:9px;text-align:center;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;">'.$prod["Fecha"].'</td>
                     <td style="padding:3px 2px;width:100px;max-width:70px;font-size:9px;text-align:center;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;">'.$prod["Codigo"].'</td>
                    <td style="width:400px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Paciente"].'</td>
                    <td style="width:100px;font-size:9px;word-wrap: break-word;text-align:right;background:#f3f3f3;border:1px solid #cccccc;">$'.number_format($cuota,2,".",",").'</td>
                   
                </tr>';
                $ingresos+=$prod['Cuota'];
               
            }
            
         $contenido .= '</table>';
        if(count($gastos)>0){

       
         $contenido .= '<table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
         <tr>
         <td colspan="4" style="text-align:center;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
              <h6 style="font-size:16px;">Gastos </h6>
           </td>
   </tr>
       <tr>

           <td style="text-align:center;width:100px;max-width:100px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
               Fecha
           </td>
           <td style="width:100px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
              Concepto
           </td>
           <td style="width:400px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
              Observaciones
           </td>
            <td style="width:100px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
               Total 
           </td>
       </tr>';
       foreach($gastos as $prod){  
        $contenido .='<tr>
            <td style="padding:3px 2px;width:100px;max-width:100px;font-size:9px;text-align:center;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;">'.$prod["Fecha"].'</td>
             <td style="padding:3px 2px;width:100px;max-width:70px;font-size:9px;text-align:center;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;">'.$prod["Motivo"].'</td>
            <td style="width:400px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">'.$prod["Observaciones"].'</td>
            <td style="width:100px;font-size:9px;word-wrap: break-word;text-align:right;background:#f3f3f3;border:1px solid #cccccc;">$'.number_format($prod["Gasto"],2,".",",").'</td>
           
        </tr>';
        $egresos+=$prod['Gasto'];
       
    }
    $contenido .= '</table>';
}



$diferencia=$acta['Cuota_Real']-$acta['Cuota_Ingresada'];
$contenido .= '<table style="margin-top:10px">
<tr>
    <td style="font-size:10px;width:650px;background:#e9eef0;border-radius:5px;padding:8px;text-align:right;padding:30px 20px">
        
        <strong>Ingresos: </strong> $'.number_format($ingresos,2,",",".").'<br><br>
        <strong>Egresos: </strong> $'.number_format($egresos,2,",",".").'<br><br>
        <strong>Por entregar: </strong> $'.number_format(($ingresos-$egresos),2,",",".").'<br><br>
        <strong>Entregado: </strong> $'.number_format($acta['Cuota_Ingresada'],2,",",".").'<br><br>
        <strong>Diferencia: </strong> $'.number_format($diferencia,2,",",".").'<br><br>
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

$nombre_fichero =  $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$data["Codigo_Qr"];
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
                  <img src="'.($data["Codigo_Qr"] =='' || !file_exists($nombre_fichero) ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$data["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-10px;" />
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
    $direc = 'Ciere'.'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc,'D'); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

?>