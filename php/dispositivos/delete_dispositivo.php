<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.complex.php');

require_once('../../helper/response.php');

        $id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

        $query="SELECT  Estado AS Estado  FROM Dispositivo_Radicacion As Dispositivo WHERE Id_Dispositivo_Radicacion =  $id  ";

        $oCon=new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Simple');
        $data = $oCon->getData()[0];

        unset($oCon);

    $oItem = new complex('Dispositivo_Radicacion', 'Id_Dispositivo_Radicacion', $id );
    $oItem->Estado = ($data['Estado'] == 'Activo' )  ?  'Inactivo' : 'Activo';
    $oItem->save();

unset($oItem);

show(mysuccess(''));