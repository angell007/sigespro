<?php 
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';

$estiba=isset( $_REQUEST['Estiba'] ) ? $_REQUEST['Estiba'] : false ;
$tipo = isset( $_REQUEST['Tipo'] ) ? $_REQUEST['Tipo'] : false ;
$estiba = json_decode($estiba,true);

$cond_val='';
try {

    if($tipo == 'Editar'){
        $oItem = new complex('Estiba','Id_Estiba',$estiba['Id_Estiba']);
        $cond_val = ' AND Id_Estiba != '.$estiba['Id_Estiba'];

        #se elimina para hacer la acutalización y no genere conflicto
        unset($estiba['Id_Estiba']);
    }else{
        $oItem = new complex('Estiba','Id_Estiba');
    }
    
    $query = 'SELECT Id_Estiba
             FROM Estiba
             WHERE ( Nombre = "'.$estiba['Nombre'].'" OR Codigo_Barras = "'.$estiba['Codigo_Barras'].'") 
                 '.$cond_val;
  
    $oCon = new consulta();
    $oCon->setQuery($query);
    $validacion= $oCon->getData();
    unset($oCon);
    if ($validacion) {
        throw new Exception("Error Existe una estiba con el mismo nombre ó código barras");
    }
     
    foreach ($estiba as $key => $value) {
        $oItem->$key=$value;
    }
 
    $oItem->save();
    $id = $oItem->getId();
    unset($oItem);
 
    if (!$id) {
        throw new Exception("se generó un error al crear la estiba");
    }
    echo json_encode(['message'=>'Operación realizada con éxito','type'=>'success','title'=>'Guardado satisfactoriamente']);

} catch (Exception $th) {
    //throw $th;
    header("HTTP/1.0 400 ".$th->getMessage());
    echo json_encode(['message'=>$th->getMessage(),'type'=>'error', 'title'=>'OOPS...']);
}



