<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');

$queryObj = new QueryBaseDatos();
$response = array();
$http_response = new HttpResponse();

date_default_timezone_set('America/Bogota');

$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
$modelo = (array) json_decode($modelo , true);

$colunma='';
$colunmaD='';
$colunmaG='';
$tabla='';

$modelo['Fecha_Aprobacion']=date('Y-m-d H:m:s');
$oItem=new complex('Otrosi_Contrato','Id_Otrosi_Contrato',$modelo['Id_Otrosi_Contrato']);
foreach ($modelo as $key => $value) {
   $oItem->$key=$value;
}
$oItem->save();
unset($oItem);

$query='SELECT CF.* FROM Contrato_Funcionario CF  WHERE Id_Contrato_Funcionario = '.$modelo['Id_Contrato_Funcionario'];
$oCon= new consulta();
$oCon->setQuery($query);
$respuesta = $oCon->getData();
unset($oCon);

//consulta que trae los datos para actu el cargo
$dato='SELECT C.Id_Cargo, D.Id_Dependencia, G.Id_Grupo
            FROM Cargo C 
            INNER JOIN Dependencia D ON C.Id_Dependencia = D.Id_Dependencia
            INNER JOIN Grupo G ON D.Id_Grupo = G.Id_Grupo
            WHERE C.Id_Cargo ='.$modelo['Id_Cargo_Funcionario'];
$oCon= new consulta();
$oCon->setQuery($dato);
$datos = $oCon->getData();
unset($oCon);

if($respuesta['Id_Contrato_Funcionario']){
    $query2='UPDATE Contrato_Funcionario 
               SET Numero_Otrosi = Numero_Otrosi+1, Valor = '.number_format($modelo['Salario'],2,".","").'
               WHERE Id_Contrato_Funcionario  ='.$respuesta['Id_Contrato_Funcionario'];
    $oCon= new consulta();
    $oCon->setQuery($query2);     
    $oCon->createData();      
    unset($oCon); 
}
if($modelo['Tipo'] == 'Cambio de Cargo y Remuneracion'){
   $colunmaS='Salario';
   $colunma='Id_Cargo';
   $colunmaD='Id_Dependencia';
   $colunmaG='Id_Grupo';
   $tabla='Funcionario';
   $id=$respuesta['Identificacion_Funcionario'];
 
   if($tabla!='' && $colunma!='' ){
      $query2="UPDATE $tabla 
               SET $colunmaS = ".$modelo['Salario'].", 
                   $colunma = ".$modelo['Id_Cargo_Funcionario'].", 
                   $colunmaD = ".$datos['Id_Dependencia'].", 
                   $colunmaG = ".$datos['Id_Grupo']."
               WHERE Identificacion_$tabla  = $id";  
      $oCon= new consulta();
      $oCon->setQuery($query2);     
      $oCon->createData();     
      unset($oCon);
   }
}
if($modelo['Tipo'] == 'Cambio de Remuneracion economica'){
   $colunma='Salario';
   $tabla='Funcionario';
   $id=$respuesta['Identificacion_Funcionario'];
   $valor=$modelo['Salario']; 

   if($tabla!='' && $colunma!='' ){
      $query2="UPDATE $tabla SET $colunma = $valor
               WHERE Identificacion_$tabla  = $id";  
      $oCon= new consulta();
      $oCon->setQuery($query2);     
      $oCon->createData();     
      unset($oCon);
}
}
if($modelo['Tipo'] == 'Cambio de Cargo'){
   $colunma='Id_Cargo';
   $colunmaD='Id_Dependencia';
   $colunmaG='Id_Grupo';
   $tabla='Funcionario';
   $id=$respuesta['Identificacion_Funcionario'];
   $valor=$modelo['Id_Cargo_Funcionario']; 
   $valorD=$datos['Id_Dependencia']; 
   $valorG=$datos['Id_Grupo']; 

if($tabla!='' && $colunma!='' ){
    $query2="UPDATE $tabla 
             SET $colunma = $valor,  $colunmaD = $valorD, $colunmaG = $valorG
             WHERE Identificacion_$tabla  = $id";  
    $oCon= new consulta();
    $oCon->setQuery($query2);     
    $oCon->createData();     
    unset($oCon);
}

}

$http_response->SetRespuesta(0, 'Guardado Correctamente', 'Se ha guardado la validacion !');
$response = $http_response->GetRespuesta();

echo json_encode($response);

?>


