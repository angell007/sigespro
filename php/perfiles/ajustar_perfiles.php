<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT * FROM Perfil WHERE Id_Perfil' ;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$perfiles['Perfil'] = $oCon->getData();
unset($oCon);

foreach ($perfiles['Perfil'] as $key => &$perfil) {
    $query = 'SELECT * 
                FROM Perfil_Permiso
                WHERE Id_Perfil = "'. $perfil['Id_Perfil'].'"';
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    
    $perfil['Permisos'] = $oCon->getData();
}

$query = 'SELECT PE.*, PF.*
            FROM Perfil PE
            INNER JOIN Perfil_Funcionario PF ON PE.Id_Perfil=PF.Id_Perfil
            WHERE PF.Identificacion_Funcionario= 12345';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$todospermisos = $oCon->getData();
unset($oCon);


foreach($todospermisos as $permiso){
     foreach ($perfiles['Perfil'] as $key => &$perfil) {

        if(!FindArray($permiso['Titulo_Modulo'], $perfil['Permisos'])){
            
            $oItem = new complex("Perfil_Permiso","Id_Perfil_Permiso");   
            $oItem->Id_Perfil     = $perfil['Id_Perfil'];
            $oItem->Titulo_Modulo = $permiso['Titulo_Modulo'];
            $oItem->Modulo        = $permiso['Modulo'];
            $oItem->Crear         = 0;
            $oItem->Editar        = 0;
            $oItem->Eliminar      = 0;
            $oItem->Ver           = 0;   
            $oItem->save();
            unset($oItem);
        }
    }
}

function FindArray($nombre, $find){
   
    foreach ($find as  &$f){
       if($f['Titulo_Modulo'] == $nombre){

        return true;
        break;

       }
    }
    return false;
}




