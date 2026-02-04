<?php



header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');



require_once('../../config/start.inc.php');

include_once('../../class/class.lista.php');

include_once('../../class/class.complex.php');

include_once('../../class/class.consulta.php');

require_once('../../class/html2pdf.class.php');



$nom=( isset( $_REQUEST['prima'] ) ? $_REQUEST['prima'] : '' );



/* FUNCIONES BASICAS */

function fecha($str)

{

    $parts = explode(" ",$str);

    $date = explode("-",$parts[0]);

    return $date[2] . "/". $date[1] ."/". $date[0];

}



$meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

/* FIN FUNCIONES BASICAS*/



$oLista = new lista("Prima_Funcionario");

$oLista->setRestrict("Id_Prima","=",$nom);

$funcionarios = $oLista->getList();



$oItem = new complex('Prima',"Id_Prima",$nom);

$nomina_general = $oItem->getData();

unset($oItem);

/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */



/* DATOS DEL ARCHIVO A MOSTRAR */



/* FIN DATOS DEL ARCHIVO A MOSTRAR */



ob_start(); // Se Inicializa el gestor de PDF

$oItem = new complex('Configuracion',"Id_Configuracion",1);

$config = $oItem->getData();

unset($oItem);

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



function MesString($mes_index){

    global $meses;

    return  $meses[($mes_index-1)];

}




foreach($funcionarios as $fun){

    

$query = 'SELECT NF.*,  CONCAT(F.Nombres," ",F.Apellidos) as Funcionario,  (SELECT C.Nombre FROM Cargo C WHERE C.Id_Cargo=F.Id_Cargo) as Cargo, CF.Fecha_Inicio_Contrato as Fecha_Ingreso

FROM Prima_Funcionario NF  

INNER JOIN Funcionario F ON NF.Identificacion_Funcionario=F.Identificacion_Funcionario 

INNER JOIN Contrato_Funcionario CF ON F.Identificacion_Funcionario = CF.Identificacion_Funcionario

WHERE NF.Identificacion_Funcionario='.$fun["Identificacion_Funcionario"].' AND NF.Id_Prima_Funcionario='.$fun["Id_Prima_Funcionario"];



$oCon= new consulta();

$oCon->setQuery($query);

$funcionario = $oCon->getData();

unset($oCon);






       $codigos ='

                     <h4 style="margin:5px 0 0 0;font-size:14px;line-height:14px;">Pago de Prima</h4><br>

                   

       ';

       $contenido = '<table style="border:1px solid #cccccc;"  cellpadding="0" cellspacing="0">

           <tr style="width:590px;" >

                           <td  style="width:100px;font-size:10px;font-weight:bold;text-align:left; background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Funcionario </td>

                           <td style="width:213px;font-size:10px;text-align:left;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$funcionario['Funcionario'].'</td>

                           <td  style="width:120px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Documento</td>

                           <td   style="width:110px;font-size:10px;text-align:left;border:1px solid #cccccc;padding:4px;padding-right:0;">C.C. '.number_format($funcionario['Identificacion_Funcionario'],0,",",".").'</td>

                           <td   style="width:150px;font-size:10px;font-weight:bold;text-align:center;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:2;">Prima</td>

           

           </tr>

           <tr style="width:590px; " >

                           <td  style="width:100px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Cargo</td>

                           <td   style="width:213px;font-size:10px;text-align:left;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$funcionario['Cargo'].'</td>

                           <td  style="vertical-align:middle; width:120px;font-size:10px;font-weight:bold;text-align:left;border:1px solid #cccccc;background:#ededed;padding:4px;padding-right:0;">Dias Laborados</td>

                           <td  style="width:120px;font-size:10px;text-left:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$funcionario['Dias_Trabajados'].' dias </td>

                           <td  style="vertical-align:middle; width:150px;font-size:10px;font-weight:bold;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:2;">

                        '.$funcionario['Detalles'].'</td>

           </tr>

           <tr style="width:590px; " >

                           <td  style="width:100px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Salario</td>

                           <td   style="width:213px;font-size:10px;text-align:left;border:1px solid #cccccc;padding:4px;padding-right:0;">$ '.number_format($funcionario['Salario'],0,",",".").'</td>

                           <td  style="vertical-align:middle; width:120px;font-size:10px;font-weight:bold;text-align:left;border:1px solid #cccccc;background:#ededed;padding:4px;padding-right:0;">Fecha de Ingreso</td>

                           <td colspan="2"  style="width:120px;font-size:10px;text-left:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.fecha($funcionario['Fecha_Ingreso']).'</td>

           

           </tr>    

          

        

        

       </table>

       

  

       <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">

           <tr>

       

               <td colspan="2" style="text-align:center;width:580px;max-width:400px;font-weight:bold;border-top:1px solid #cccccc;border-left:1px solid #cccccc;border-right:1px solid #cccccc;background:#ededed;padding:4px 0;">

                   Resumen del Pago

               </td>

              

               

           </tr>

           <tr>



           <td  style="text-align:center;width:580px;max-width:400px;font-weight:bold;border:1px solid #cccccc;background:#ededed;padding:4px 0;">

               Item

       </td>

           <td style="text-align:center;width:150px;max-width:100px;font-weight:bold;border:1px solid #cccccc;background:#ededed;padding:4px 0;">

           Valor

       </td>

           </tr>';

           


           $contenido.='

           <tr >

                 <td style="width:580px;max-width:400px;border:1px solid #cccccc; max-height:50px;padding:4px;">

                 '.$funcionario['Detalles'].'

                 </td>

                 <td style="text-align:right;width:150px;max-width:100px;border:1px solid #cccccc;padding:4px;">$ '.number_format($funcionario['Total_Prima'],2,".",",").'

                 </td>

           </tr>';

           

           

           

          $contenido.= '</table><br>



           <b style="font-size:10px;">Nota: Lo expuesto en este comprobante representa el pago quincenal del empleado, y en este se listan el salario neto, deducciones e ingresos adicionales y su firma representa su entera satisfacción.</b>

           

           <table style="margin-top:20px">    

               <tr>

                   <td style="width:400px;padding-left:30px">

                   <table>

                   <tr>

                       <td style="width:330px;font-weight:bold; border-top:1px solid black; text-align:center;">'.$funcionario['Funcionario'].'</td>

                       <td style="width:30px;"></td>

                       <td style="width:330px;font-weight:bold; border-top:1px solid black; text-align:center;text-transform:uppercase;">'.$config["Nombre_Empresa"].'</td>

                   </tr>

                   <tr>

                       <td style="width:330px;font-weight:bold; text-align:center;">'.$funcionario['Cargo'].' </td>    

                       <td style="width:30px;"></td>    

                       <td style="width:330px;font-weight:bold; text-align:center;"></td>    

                   </tr>

                   

                   </table>

                   </td>    

               </tr>

           </table>';

   



/* FIN SWITCH*/



/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/

$cabecera='<table style="" >

             <tbody>

               <tr>

                 <td style="width:70px;">

                   <img src="'.$_SERVER["DOCUMENT_ROOT"].'/assets/images/logo-color.png" style="width:60px;" alt="Pro-H Software" />

                 </td>

                 <td class="td-header" style="width:510px;font-weight:thin;font-size:14px;line-height:20px;">

                   '.$config["Nombre_Empresa"].'<br> 

                   N.I.T.: '.$config["NIT"].'<br> 

                   '.$config["Direccion"].'<br> 

                   TEL: '.$config["Telefono"].'

                 </td>

                 <td style="width:170px;text-align:right">

                       '.$codigos.'

                 </td>

                 

               </tr>

             </tbody>

           </table><hr style="border:1px dotted #ccc;width:730px;">';

/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/



/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

$content .= '<page backtop="0mm" backbottom="0mm">

               <div class="page-content" >'.

                   $cabecera.

                   $contenido.'

               </div>

           </page>';

}





/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/



try{

    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/

    $html2pdf = new HTML2PDF('L', array(215.9,140), 'Es', true, 'UTF-8', array(5, 5, 2, 0));

    $html2pdf->writeHTML($content);

    $direc = $id.'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO

    $html2pdf->Output($direc,''); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA

}catch(HTML2PDF_exception $e) {

    echo $e;

    exit;

}



?>