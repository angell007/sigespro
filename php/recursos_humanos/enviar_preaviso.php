<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.mensajes.php');
include_once('../../class/NumeroALetra.php');
include_once('../../class/class.generar_pdf.php');
include_once('../../class/class.php_mailer.php');


$sms_sender = new Mensaje();
$pdf= new GenerarPDF();
$mail= new EnviarCorreo();

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );;
$fecha = date('Y-m-d H:i:s');


$query="SELECT * FROM Contrato_Funcionario WHERE Id_Contrato_Funcionario=".$datos;
$queryObj = new QueryBaseDatos($query);
$contrato = $queryObj->ExecuteQuery('simple');

$funcionario=GetDatosFuncionario($datos);


$oItem = new complex("Alerta","Id_Alerta");
$oItem->Identificacion_Funcionario=$contrato['Identificacion_Funcionario'];
$oItem->Tipo="Preaviso";
$oItem->Fecha=$fecha;
$oItem->Detalles="Se le ha enviado un preaviso de su contrato que termina  ".$contrato['Fecha_Fin_Contrato'].", para ver mas detalles haga click sobre la alerta  ";
$oItem->Id=$datos;
$oItem->save();
unset($oItem);


$oItem=new complex('Actividad_Funcionario','Id_Actividad_Funcionario');
$oItem->Identificacion_Funcionario=$contrato['Identificacion_Funcionario'];
$oItem->Detalles="Se le genera la notificacion de preavsio al funcionario ";
$oItem->Tipo='Preaviso';
$oItem->save();
unset($oItem);



$numero = number_format($funcionario['Salario'], 0, '.','');
$letras = NumeroALetras::convertir($numero);

$li=getFirma();
if($li){
    $firma='<img src="'.$MY_FILE . "DOCUMENTOS/".$li["Identificacion_Funcionario"]."/".$li['Firma'].'"  width="230"><br>';
}

$contenido=CrearContenidoPdf();

$pdf->CrearPdf($contenido,$funcionario['Identificacion_Funcionario'],'Preaviso.pdf');

$ruta_completa =  $_SERVER["DOCUMENT_ROOT"].'/DOCUMENTOS/'.$funcionario['Identificacion_Funcionario'].'/Preaviso.pdf';


$message='<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
      <table>
          <thead>
              <tr>
                    <th  style="text-align: center"> <img src="https://sigesproph.com.co/IMAGENES/LOGOS/LogoProh.jpg" style="width:100px;"> </th>
              </tr>
            
          </thead>
          <tbody>
              <tr>
                  <td><p>
                        <h4>Cordial Saludo </h4><br> De manera atenta, le comunicamos que el contrato de trabajo a término fijo inferior a un año suscrito por usted y PRO-H S.A, esta proximo a vencer, en el documento adjunto se encuentra el preaviso, por favor firmarlo y hacerlo llegar al departamento de Recursos Humanos. <br> <br>
                        Atentamente <br><br>
                        <strong>LILIANA MARCELA VEGA GÓMEZ </strong> <br>
                        <strong>JEFE DE RECURSOS HUMANOS </strong>
                  </p></td>
              </tr>

          </tbody>
      </table>
               
    
</body>
</html>';

$error='';

$to=$funcionario['Correo'];
 if($to!=''){
    $mail->EnviarMail($to,'Preaviso',$message,$ruta_completa);
 }else{
     $error='El funcionario no tiene un correo registrado y no se le ha podido enviar la notificacion vi email.';
 }


EnviarMensaje($contrato);

$resultado['mensaje'] = "¡Se ha enviado correctamente el preavsio".$error;
$resultado['tipo'] = "success";

echo json_encode($resultado);

function EnviarMensaje($datos){

    global $sms_sender;


    $query='SELECT CONCAT(Nombres) as Nombre, Celular FROM Funcionario  WHERE Identificacion_Funcionario='.$datos['Identificacion_Funcionario'];
	$oCon= new consulta();
	$oCon->setQuery($query);
	$func = $oCon->getData();
    unset($oCon);

    $mensaje = "$func[Nombre] su contrato vence $datos[Fecha_Fin_Contrato] por favor revisar el correo y adjuntar el formato de preaviso firmado y hacerlo llegar al departamento de recursos humanos.";
    $enviado = $sms_sender->Enviar($func['Celular'], $mensaje);

	
    $oItem = new complex('Mensaje',"Id_Mensaje");
    $oItem->Mensaje = $mensaje;
    $oItem->Identificacion_Funcionario = $datos['Identificacion_Funcionario'];		
    $oItem->Fecha = date('Y-m-d H:i:s');
    $oItem->Numero_Telefono = $func['Celular'];
    $oItem->save();
    unset($oItem); 
}

 function CrearContenidoPdf(){
     global $funcionario,$firma;
     $contenido='';
    $contenido .= '<br><table style="text-transform:uppercase;margin-top:50px;">
    
    <tr>
        <td style="width:600px;">
        <table>
    
        <tr>
            <td style="width:600px;font-weight:bold">Señor(a).</td>
            
            
        </tr>
        <tr>
        <td style="width:600px;font-weight:bold"></td>
        
        
    </tr>
        <tr>
            <td style="width:600px;font-weight:bold">'.$funcionario['Funcionario'].'</td>    
            
        </tr>
        <tr>
            <td style="width:600px">'.$funcionario['Cargo'].'</td>
            
        </tr>
        <tr>
          
            <td style="width:600px">PRO-H S.A</td>    
        </tr>
        <tr>
            <td style="width:600px">Asunto: Preaviso terminación de contrato</td>
              
        </tr>
        
        </table>
        </td>    
    </tr>
    
    
    </table>    
  
    ';

    $contenido.= '
    
    <p style="padding:6px">Cordial Saludo,</p>   
    <p style="padding:6px">De manera atenta, le comunicamos que el contrato de trabajo a término fijo inferior a un año suscrito por usted y PRO-H S.A, el día '.obtenerFechaEnLetra($funcionario["Fecha_Inicio_Contrato"]).', con una  duración  de 04 meses, con el cual se ha venido prorrogando, finaliza el próximo '.obtenerFechaEnLetra($funcionario["Fecha_Fin_Contrato"]).', no será prorrogado y en consecuencia se dará por terminado sin perjuicio de que llegada la fecha de terminación, las partes acuerden lo contrario. Lo anterior con fundamento en lo preceptuado en el numeral 1° del artículo 46 del código sustantivo del trabajo, que dice:</p>   

    <p style="padding:6px; font-size:12px">1. Si antes de la fecha de vencimiento del término estipulado, ninguna de las partes avisare por escrito a la otra su determinación de no prorrogar el contrato, con una antelación no inferior a treinta (30) días, éste se entenderá renovado por un período igual al inicialmente pactado y así sucesivamente</p> 

    <p style="padding:6px">Por lo anterior, con una anticipación no inferior a 30 días calendario, se le da aviso de nuestra decisión de manera escrita dando cumplimiento a los requerimientos legales.</p> 
   
    ';

    $contenido5 = '
    
    <table style="margin-top:150px;">
    
    <tr>
        <td style="width:400px;padding-left:10px">
        <table>
    
        <tr>
            <td style="width:330px;font-weight:bold">Atentamente</td>
            <td style="width:300px;font-weight:bold">Trabajador</td>    
        </tr>
        
        </table>
        </td>    
    </tr>
    
    
    </table>
    
    <table style="margin-top:20px;margin-bottom:0px">
    
    <tr>
        <td style="width:400px;padding-left:10px">
        <table>
        <tr>
        <td>
        '.$firma.'
        </td>
        </tr>
        <tr>
            <td style="width:330px;font-weight:bold;"> LILIANA MARCELA VEGA GÓMEZ </td>
            <td style="width:300px;font-weight:bold">'.$funcionario['Funcionario'].'</td>    
        </tr>
        <tr>
            <td style="width:330px;font-weight:bold">Jefe Recursos Humanos</td>
            <td style="width:300px;font-weight:bold">C.C '.number_format($funcionario['Identificacion_Funcionario'],0,"",".").' </td>    
        </tr>
           
          
        
        </table>
        </td>    
    </tr>
    
    
    </table>
    ';

    $cont=$contenido.$contenido5;

    return $cont;
     

 }

 function GetDatosFuncionario($id){

    $query='SELECT CF.*, (SELECT C.Nombre FROM Cargo C WHERE C.Id_Cargo=F.Id_Cargo) as Cargo, IFNULL(F.Correo,"") as Correo, CONCAT(F.Nombres," ",F.Apellidos) as Funcionario  FROM Contrato_Funcionario  CF
    INNER JOIN Funcionario F  ON F.Identificacion_Funcionario=CF.Identificacion_Funcionario WHERE CF.Id_Contrato_Funcionario='.$id;
    $oCon= new consulta();
    $oCon->setQuery($query);
    $funcionario = $oCon->getData();
    unset($oCon);

    return $funcionario;
 }

 
function getFirma(){
    $query = 'SELECT Firma, Identificacion_Funcionario FROM Funcionario WHERE Identificacion_Funcionario=1098655659 ';

    $queryObj = new QueryBaseDatos($query); 
    $func = $queryObj->ExecuteQuery('simple');
    unset($queryObj);

    return $func;
}

function obtenerFechaEnLetra($fecha){
   
    $num = date("j", strtotime($fecha));
    $anno = date("Y", strtotime($fecha));
    $mes = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
    $mes = $mes[(date('m', strtotime($fecha))*1)-1];
    return $num.' de '.$mes.' de '.$anno;
}




?>