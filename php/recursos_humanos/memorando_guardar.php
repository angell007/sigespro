<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');
header("Content-type: application/pdf");
header("Content-Disposition:attachment;filename='downloaded.pdf'");

include_once('../../config/start.inc.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.mensajes.php');
include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.php_mailer.php');

//include_once('../../class/class.generar_pdf.php');
include_once('../../class/html2pdf.class.php');

$sms_sender = new Mensaje();
//$pdf        = new GenerarPDF();
$mail       = new EnviarCorreo();

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$datos = (array)json_decode($datos);

if(isset($datos['id']) && ($datos['id']!=null || $datos['id']!="")){

    $oItem = new complex("Memorando","Id_Memorando",$datos['id']);
}else{
    $oItem = new complex("Memorando","Id_Memorando");
    //$oItem->Fecha_Visto   =$datos[''];

}
foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}

$oItem->save();
$id_memorando=$oItem->getId();
unset($oItem);

$oItem = new complex('Alerta','Id_Alerta');
$oItem->Identificacion_Funcionario=$datos['Identificacion_Funcionario'];
$oItem->Tipo="Memorando";
$oItem->Detalles="Se le ha generado un memorando por el motivo ".$datos['Motivo'];
$oItem->Id=$id_memorando;
$oItem->save();
unset($oItem);

$oItem=new complex('Actividad_Funcionario','Id_Actividad_Funcionario');
$oItem->Identificacion_Funcionario=$datos['Identificacion_Funcionario'];
$oItem->Detalles="Se le genera la notificacion de memorando al funcionario ";
$oItem->Tipo='Memorando';
$oItem->save();
unset($oItem);

$certificado = BuscarDatosCertificado($id_memorando);
$Identificacion_Funcionario = $certificado['Identificacion_Funcionario'];
$funcionario = BuscarDatosFuncionario($Identificacion_Funcionario);

$li = getFirma();
if($li){
   $firma='<img src="'.$_SERVER["DOCUMENT_ROOT"]."sigespro/sigespro-backend/DOCUMENTOS/".$li["Identificacion_Funcionario"]."/".$li['Firma'].'"  width="230" ><br>';
}
  $contenido = GetContenido();
  ob_start();
  /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    //, true, 'UTF-8', array(20, 20, 20, 20)
//   $html2pdf = new HTML2PDF('P', 'A4', 'es');
//   $html2pdf->pdf->SetDisplayMode('fullpage'); 
//   $html2pdf->writeHTML($contenido);
//   $ruta_completa = $_SERVER["DOCUMENT_ROOT"].'sigespro/sigespro-backend/DOCUMENTOS/'.$funcionario['Identificacion_Funcionario'].'/Memorando.pdf';
//   $html2pdf->Output($ruta_completa,'F'); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
         

$message = '<!doctypes html>
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
                    <th  style="text-align: center">
                        <img src="https://sigesproph.com.co/IMAGENES/LOGOS/LogoProh.jpg" style="width:100px;">
                    </th>
                </tr>

            </thead>
            <tbody>
                <tr>
                    <td><p>
                            <h4>Cordial Saludo </h4><br> De manera atenta, le comunicamos que se le ha generado un memorando, en el documento adjunto se encuentra el dicho documento, por favor firmarlo y hacerlo llegar al departamento de Recursos Humanos. <br> <br>
                            Atentamente <br><br>
                            <strong>LILIANA MARCELA VEGA GÓMEZ </strong> <br>
                            <strong>JEFE DE RECURSOS HUMANOS </strong>
                    </p></td>
                </tr>

            </tbody>
        </table>


    </body>
</html>';

$error = '';
$to    = $funcionario['Correo'];

if($to!=''){
    // $mail->EnviarMail($to,'Memorando',$message,$ruta_completa);
    // $file=$ruta_completa;
    // unlink($file);
}else{
     $error='El funcionario no tiene un correo registrado y no se le ha podido enviar la notificacion vi email.';
}

//EnviarMensaje($datos);

$resultado['mensaje'] = "¡Memorando Creado Correctamente Exitosamente!";
$resultado['tipo']    = "success";

echo json_encode($resultado);

//<tr><td>Bucaramanga, '.obtenerFechaEnLetra($certificado['Fecha']).' </td> </tr>

function GetContenido(){

    global $funcionario,$firma, $certificado;
    $c1 = '<br>
    <table style="text-transform:uppercase;margin-top:90px;">
        <tr>
            <td style="width:600px;">
                <table>
                    <tr>
                        <tr>
                            <td>
                                Bucaramanga, '.$certificado['Fecha'].'
                            </td>
                        </tr>
                    </tr>
                    <tr>
                        <td style="width:600px;font-weight:bold"></td>
                        <tr></tr>
                        <td style="width:600px;font-weight:bold"></td>
                    </tr>
                    <tr>
                        <td style="width:600px;font-weight:bold">Señor(a).</td>
                    </tr>
                    <tr>
                        <td style="width:600px;font-weight:bold">'.$funcionario['Nombre_Funcionario'].'</td>
                    </tr>
                    <tr>
                        <td style="width:600px;font-weight:bold"></td>
                    </tr>
                    <tr>
                        <td style="width:600px">PRO-H S.A</td>
                    </tr>
                    <tr>
                        <td style="width:600px;font-weight:bold"></td>
                    </tr>
                    <tr>
                        <td style="width:600px;font-weight:bold"></td>
                    </tr>
                    <tr>
                        <td style="width:600px;">
                        <b>Asunto: MEMORANDO</b>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

        <p style="margin-top:30px;">Por medio de la presente me dirijo a usted de manera respetuosa para informarle que PRO-H S.A. ha decidido efectuarle una amonestación con respecto al contrato de trabajo suscrito entre usted y la sociedad.</p>
        <p>'.$certificado['Detalles']. '</p>
        <p>'.$certificado['Nombre_Categoria'].'</p>
        <p>Así mismo le informo que Dos (2) Faltas Graves son causales para la terminación del contrato de trabajo suscrito entre usted y PRO-H S.A. y una falta leve amerita la amonestación verbal o escrita y suspensión en el trabajo sin remuneración hasta por un periodo de ocho (8) días.</p>
        <p>No siendo otro el motivo del comunicado queda informado y esperamos esto sea un motivo para mejorar en sus funciones.
        Atentamente; </p> ';

    $c2 = '
        <table style="margin-top:90px;">
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
                                <td style="width:300px;font-weight:bold">'.$funcionario['Nombre_Funcionario'].'</td>
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

    $style='<style>
                .page-content{
                width:750px;
                text-align:justify;
                word-wrap:break-word;
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
    // backimg="'.$_SERVER["DOCUMENT_ROOT"].'sigespro/sigespro-backend/IMAGENES/LOGOS/membrete.jpg"
    $content = '<page backtop="0mm" backbottom="0mm" >
                    <div class = "page-content"
                         style = " text-align      : justify;
                                word-wrap       : break-word;
                                background-size : cover;
                                background-position: center;
                                opacity         : 0.5;
                        ">'.$c1.$c2.'
                    </div>
                </page>';

    $html = $content.$style;



   return $html;
}

function EnviarMensaje($datos){

    global $sms_sender;


    $query='SELECT CONCAT(Nombres) as Nombre, Celular FROM Funcionario  WHERE Identificacion_Funcionario='.$datos['Identificacion_Funcionario'];
	$oCon= new consulta();
	$oCon->setQuery($query);
	$func = $oCon->getData();
    unset($oCon);

    $mensaje = "    $func[Nombre] se le informa que se ha generado un memorando,
                    por favor revisar el correo electronico alli encontrara mayor informacion.";
    $enviado = $sms_sender->Enviar($func['Celular'], $mensaje);


    $oItem = new complex('Mensaje',"Id_Mensaje");
    $oItem->Mensaje = $mensaje;
    $oItem->Identificacion_Funcionario = $datos['Identificacion_Funcionario'];
    $oItem->Fecha = date('Y-m-d H:i:s');
    $oItem->Numero_Telefono = $func['Celular'];
    $oItem->save();
    unset($oItem);
}

function BuscarDatosCertificado($idCertificado){
    $query = '
        SELECT
            M.* ,
            CM.Nombre_Categoria AS Nombre_Categoria
        FROM Memorando M
        INNER JOIN Categorias_Memorando CM ON M.Motivo = CM.Id_Categorias_Memorando
        WHERE
            M.Id_Memorando ='.$idCertificado;
    //Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);


    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $certificado = $queryObj->ExecuteQuery('simple');
    unset($queryObj);
 
    return $certificado;
}

function getFirma(){

    $query    = 'SELECT Firma, Identificacion_Funcionario FROM Funcionario WHERE Identificacion_Funcionario = 1098655659 ';
    $queryObj = new QueryBaseDatos($query);
    $func     = $queryObj->ExecuteQuery('simple');
    unset($queryObj);

    return $func;
}

function BuscarDatosFuncionario($idFuncionario){
    $query = '
        SELECT
            CONCAT_WS(" ", F.Nombres, F.Apellidos) AS Nombre_Funcionario,
            F.Identificacion_Funcionario,
            C.Nombre AS Cargo,IFNULL(Correo,"") as Correo,
            F.Salario
        FROM Funcionario F
        INNER JOIN Cargo C ON F.Id_Cargo = C.Id_Cargo
        WHERE
            F.Identificacion_Funcionario ='.$idFuncionario;

    $queryObj = new QueryBaseDatos($query);
    $funcionario = $queryObj->ExecuteQuery('simple');
    unset($queryObj);

    return $funcionario;
}

?>
