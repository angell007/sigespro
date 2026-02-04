<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$idfuncionario=( isset( $_REQUEST['idfuncionario'] ) ? $_REQUEST['idfuncionario'] : '' );

$query = 'SELECT M.*, CONCAT(F.Nombres, " ", F.Apellidos) as Nombre, F.Imagen
FROM Mensaje M
INNER JOIN Funcionario F
ON M.Identificacion_Funcionario=F.Identificacion_Funcionario
WHERE M.Id_Auditoria='.$id;

$oCon= new consulta();
$oCon->setQuery($query);
$mensajeschat = $oCon->getData();
unset($oCon);

$i=-1;
foreach($mensajeschat as $mensaje){$i++;

if($mensaje["Identificacion_Funcionario"]==$idfuncionario){
    $mensajeschat[$i]["clase"]="media send-chat";
    $mensajeschat[$i]["claseimagen"]="ml-3";
}else {
    $mensajeschat[$i]["clase"]="media received-chat";
     $mensajeschat[$i]["claseimagen"]="mr-3";
}
    
}

echo json_encode($mensajeschat);
?>

