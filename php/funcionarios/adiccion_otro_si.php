<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/NumeroALetra.php');

$queryObj = new QueryBaseDatos();
$response = array();
$http_response = new HttpResponse();

date_default_timezone_set('America/Bogota');

$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
$contrato = ( isset( $_REQUEST['contrato'] ) ? $_REQUEST['contrato'] : '' );
$modelo = (array) json_decode($modelo , true);
$contrato = (array) json_decode($contrato , true);

$oItem = new complex('Otrosi_Contrato','Id_Otrosi_COntrato');
$oItem->Id_Contrato_Funcionario =$modelo['Id_Contrato_Funcionario'];
$oItem->Id_Cargo_Funcionario    =$modelo['Id_Cargo_Funcionario'];
$oItem->Tipo                    ="Cambio de ".$modelo['Tipo'];
$oItem->Fecha                   =date("Y-m-d H:i:s");;
$oItem->Fecha_Aplicacion        =$modelo['Fecha'];
$oItem->Funcionario             =$modelo['Funcionario'];
$oItem->Estado                  ='Pendiente';
$oItem->Numero                  = 1;
$oItem->Funcionario_Aprueba     =$modelo['Funcionario'];
$oItem->Fecha_Aprobacion        =$modelo['Fecha'];
$oItem->Salario                 =number_format($modelo['Valor'],2,".","");
$oItem->save();
unset($oItem);

$http_response->SetRespuesta(0, 'Guardado Correctamente', 'Se ha guardado el otrosi, solo queda pendiente la aprobación del mismo!');
$response = $http_response->GetRespuesta();

echo json_encode($response);


// var_dump($modelo);
// exit;

// $query='SELECT CF.* FROM Contrato_Funcionario CF  WHERE Id_Contrato_Funcionario = '.$modelo['Id_Contrato_Funcionario'];
// $oCon= new consulta();
// $oCon->setQuery($query);
// $respuesta = $oCon->getData();
// unset($oCon);

// if($respuesta['Id_Contrato_Funcionario']){
//     $query2='UPDATE Contrato_Funcionario 
//     SET Numero_Otrosi = Numero_Otrosi+1, Valor = '.number_format($modelo['Valor'],2,".","").'
//     WHERE Id_Contrato_Funcionario  ='.$respuesta['Id_Contrato_Funcionario'];
//     $oCon= new consulta();
//     $oCon->setQuery($query2);     
//     $oCon->createData();      
//     unset($oCon);
// }


/*
$colunma='';
$tabla='';
if ($modelo['Tipo']=='Fecha Terminacion'){
    $colunma='Fecha_Fin_Contrato';
    $tabla='Contrato_Funcionario';
    $id=$respuesta['Id_Contrato_Funcionario'];
    $valor="'".$modelo['Fecha_Fin']."'";
    
}elseif($modelo['Tipo']=='Cargo'){
    $colunma='Id_Cargo';
    $tabla='Funcionario';
    $id=$respuesta['Identificacion_Funcionario'];
    $valor=$modelo['Id_Cargo_Funcionario'];
    
}


if($tabla!='' && $colunma!='' ){
    $query2="UPDATE $tabla 
    SET $colunma = $valor
    WHERE Id_$tabla  = $id";

    $oCon= new consulta();
    $oCon->setQuery($query2);     
    $oCon->createData();     
    unset($oCon);
}
*/
/*
$oItem=new complex('Otrosi_Contrato','Id_Otrosi_Contrato');
$oItem->Id_Contrato_Funcionario=$modelo['Id_Contrato_Funcionario'];
$oItem->Tipo="Cambio de ".$modelo['Tipo'];
$oItem->Fecha_Aplicacion=$modelo['Fecha'];
$oItem->Funcionario=$modelo['Funcionario'];
$oItem->Numero=$respuesta['Numero_Otrosi'];
$oItem->Salario=number_format($modelo['Valor'],2,".","");
$oItem->save();
$id_otro_si=$oItem->getId();
unset($oItem); 
*/

/*

$id_otro_si=3;



$funcionario=GetDatos($id_otro_si);
$numero = (number_format($funcionario['Salario'], 0, '.',''));
$letras = NumeroALetras::convertir($numero);
$contenido=GetContenido($funcionario);

$pdf->CrearPdf($contenido,$funcionario['Identificacion_Funcionario'],'OtrosSi.pdf');
$ruta_completa =  $_SERVER["DOCUMENT_ROOT"].'DOCUMENTOS/'.$funcionario['Identificacion_Funcionario'].'/OtrosSi.pdf';

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
                        <h4>Cordial Saludo </h4><br> De manera atenta, le comunicamos que se le ha generado a su contrato un OtroSi, en el documento adjunto se encuentra  dicho documento, por favor firmarlo y hacerlo llegar al departamento de Recursos Humanos. <br> <br>
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
    $mail->EnviarMail($to,'OtroSi',$message,$ruta_completa);
    $file=$ruta_completa;
    unlink($file);
 }else{
     $error='El funcionario no tiene un correo registrado y no se le ha podido enviar la notificacion vi email.';
 }

 
$oItem = new complex('Alerta','Id_Alerta');
$oItem->Identificacion_Funcionario=$funcionario['Identificacion_Funcionario'];
$oItem->Tipo="OtroSi";
$oItem->Detalles="Se le ha generado un otrosi a su contrato por el cambio de ".$modelo['Tipo'];;
$oItem->Id=$id_otro_si;
$oItem->save();
unset($oItem);


$http_response->SetRespuesta(0, 'Guardado Correctamente', 'Se ha guardado el otrosi, solo queda pendiente la aprobación del mismo!');
$response = $http_response->GetRespuesta();

echo json_encode($response);

function GetDatos($id){
    global $queryObj;

    $query='SELECT F.*, (SELECT Nombre FROM Cargo  WHERE Id_Cargo=F.Id_Cargo) as Cargo, CF.*, CONCAT(F.Nombres," ",F.Apellidos) as Funcionario,C.Fecha_Aplicacion ,(SELECT Nombre FROm Municipio WHERE Id_Municipio=CF.Id_Municipio) AS Municipio, IFNULL(C.Salario,0 ) as Salario
    FROM Otrosi_Contrato C
    INNER JOIN Contrato_Funcionario CF ON C.Id_Contrato_Funcionario=CF.Id_Contrato_Funcionario
    INNER JOIN Funcionario F ON CF.Identificacion_Funcionario=F.Identificacion_Funcionario  WHERE C.Id_Otrosi_Contrato='.$id;
    $queryObj->SetQuery($query);
	$otrosSi = $queryObj->ExecuteQuery('simple');

    return $otrosSi;
}
function GetContenido($funcionario){
    global $letras;
    if($funcionario['Salario']>100000){
        $de=' DE ';
    } 

    $contenido = '<p style="margin-bottom:5px;font-weight:bold;text-align:center; margin-top:150px;">OTRO SÍ #1 AL CONTRATO DE TRABAJO A TERMINO FIJO INFERIOR A UN AÑO CELEBRADO ENTRE PRODUCTOS HOSPITALARIOS PROH S.A Y '.$funcionario['Funcionario'].'.   </p> ';

 
    $contenido2 = '
    <p> Entre los suscritos <b>MARIELA RODRÍGUEZ DE ARCINIEGAS </b>, identificada con cedula de ciudadanía número 63.275.342 de Bucaramanga, actuando en calidad de representante legal de <b>PRODUCTOS HOSPITALARIOS - PROH S.A. </b>, identificada con NIT 80401 6084-5, con domicilio en la ciudad de Bucaramanga Y quien en adelante será denominada <b>LA EMPLEADORA </b> Y por otra parte <b>'.$funcionario['Funcionario'].'</b>	, identificada con cedula de ciudadanía No 1.090.467.816, quien en adelante será denominado <b>LA TRABAJADOR(A)</b>, han suscrito el presente OTRO Sí al CONTRATO PRINCIPAL DE TRABAJO A TÉRMINO FIJO INFERIOR A UN AÑO, firmado el día Veintiocho  (28) de Noviembre de dos mil diecinueve (2019), para lo cual las partes han decidido modificar de común acuerdo la <b>CLAUSULA PRIMERA</b> y <b>CLAUSULA CUARTA </b> del contrato de trabajo a término fijo inferior a un año, las cuales quedaran así:    
    </p>

    <p><b> CLAUSULA PRIMERA. </b> <b>PRIMERA. OBJETO.</b> EL TRABAJADOR prestará en forma exclusiva sus servicios personales bajo la continuada dependencia y subordinación AL EMPLEADOR inicialmente en el cargo de <b>'.$funcionario['Cargo'].'</b> o en los oficios que por razón de su formación, competencias, capacitación y necesidades del proceso o procedimientos sean necesarios realizar, sin que dichos cambios de oficios o cargos implique una desmejora o cambio de sus condiciones y obligaciones laborales.</p>

    <p><b>EL TRABAJADOR</b> se compromete a no prestar directa ni indirectamente servicios laborales a otros EMPLEADORES, ni a trabajar por cuenta propia en el mismo oficio, en las instalaciones de la EMPRESA y horarios laborales, durante la vigencia de este contrato.</p>


    <p><b>CLAUSULA </b><b>CUARTA. REMUNERACION</b> Como contraprestación directa por los servicios que se obliga a prestar EL TRABAJADOR, recibirá una remuneración que equivaldrá inicialmente a la suma de '.$letras.$de.' PESOS MCTE ($'.number_format($funcionario['Salario'],2,",",".").') pagaderos quincenalmente, previa entrega del informe de actividades por parte del TRABAJADOR.	Así mismo, se entiende que en el salario convenido está incluido el valor del descanso dominical o festivo que tenga derecho EL TRABAJADOR.</p>

    <p> El presente OTRO SÍ hace parte integral del contrato suscrito entre las partes el '.obtenerFechaEnLetra($funcionario["Fecha_Fin_Contrato"]).', En consecuencia, se firma el presente documento, en el municipio de '.$funcionario['Municipio'].' a el dia '.obtenerFechaEnLetra($funcionario["Fecha_Aplicacion"]).'.   </p> <br> <br> <br> <br>

    ';

    
    $contenido5 = '
    
    <table>
    
    <tr>
        <td style="width:400px;padding-left:10px">
        <table>
    
        <tr>
            <td style="width:300px;font-weight:bold">Empleador</td>
            <td style="width:300px;font-weight:bold">Trabajador</td>    
        </tr>
        
        </table>
        </td>    
    </tr>
    
    
    </table>
    
    <table style="margin-top:50px;margin-bottom:0px">
    
    <tr>
        <td style="width:400px;padding-left:10px">
        <table>
        <tr>
            <td style="width:300px;font-weight:bold">MARIELA RODRIGUEZ DE ARCINIEGAS</td>
            <td style="width:300px;font-weight:bold">'.$funcionario['Funcionario'].'</td>    
        </tr>
        <tr>
            <td style="width:300px;font-weight:bold">C.C 63.275.342</td>
            <td style="width:300px;font-weight:bold">C.C '.number_format($funcionario['Identificacion_Funcionario'],0,"",".").' </td>    
        </tr>
        
        </table>
        </td>    
    </tr>
    
    
    </table>
    ';

    return $contenido.$contenido2.$contenido5;
}

function obtenerFechaEnLetra($fecha){
   
    $num = date("j", strtotime($fecha));
    $anno = date("Y", strtotime($fecha));
    $mes = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
    $mes = $mes[(date('m', strtotime($fecha))*1)-1];
    return $num.' de '.$mes.' de '.$anno;
}



*/
?>





