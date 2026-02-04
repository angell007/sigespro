<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');

$funcionario   = ( isset( $_REQUEST['Funcionario']   ) ? $_REQUEST['Funcionario']   : '' );
$id = ( isset( $_REQUEST['Id'] ) ? $_REQUEST['Id'] : '' );

    $query="UPDATE Dispensacion SET Estado_Acta = 'Validado'  WHERE Id_Dispensacion = $id ";
    $oCon= new consulta();
    $oCon->setQuery($query);     
    $oCon->createData();     
    unset($oCon);

    GenerarActividadDispensacion($funcionario, $id);


function GenerarActividadDispensacion($funcionario, $id){   
        $ActividadDis['Fecha'] = date("Y-m-d H:i:s");
        $ActividadDis["Id_Dispensacion"] = $id;
        $ActividadDis["Identificacion_Funcionario"] = $funcionario;
        $ActividadDis["Detalle"] = "El acta fue validada";
        $ActividadDis["Estado"] = "Validada";
        
        $oItem = new complex("Actividades_Dispensacion","Id_Actividades_Dispensacion");
        foreach($ActividadDis as $index=>$value) {
            $oItem->$index=$value;
        }
        $oItem->save();
        unset($oItem);  
    }