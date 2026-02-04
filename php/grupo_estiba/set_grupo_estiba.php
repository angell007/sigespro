<?php 
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
require_once('../../helper/response.php');

$grupo=isset( $_REQUEST['Grupo'] ) ? $_REQUEST['Grupo'] : false ;
$tipo = isset( $_REQUEST['Tipo'] ) ? $_REQUEST['Tipo'] : false ;
$grupo = json_decode($grupo,true);

$cond_val='';

try {

    if($tipo == 'Editar'){
        $oItem = new complex('Grupo_Estiba','Id_Grupo_Estiba',$grupo['Id_Grupo_Estiba']);
        $cond_val = ' AND Id_Grupo_Estiba != '.$grupo['Id_Grupo_Estiba'];
        // se elimina para hacer la acutalización y no genere conflicto
        unset($grupo['Id_Grupo_Estiba']);
    }else{
        $oItem = new complex('Grupo_Estiba','Id_Grupo_Estiba');
    }
    
        $query = 'SELECT Id_Grupo_Estiba
        FROM Grupo_Estiba
        WHERE ( Nombre = "'.$grupo['Nombre'].'" ) 
        '.$cond_val;

        $oCon = new consulta();
        $oCon->setQuery($query);
        $validacion= $oCon->getData();
        unset($oCon);

        if($validacion) { throw new Exception("Error Existe un grupo con el mismo nombre") ; }
        
        foreach ($grupo as $key => $value) {
            $oItem->$key=$value;
        }
        
        if($grupo["Id_Punto"]){
            $oItem->Id_Punto_Dispensacion = $grupo["Id_Punto"];
        }
        
        $oItem->save();
        $id = $oItem->getId();
        
        unset($oItem);
        
        if (!$id) { throw new Exception("se generó un error al $tipo el grupo")  ; }

        show(mysuccess('Operación realizada con éxito'));

} catch (\Exception $th) {
    show(myerror($th->getMessage()));
}



