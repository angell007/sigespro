<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.complex.php');

    $http_response = new HttpResponse();
    $queryObj = new QueryBaseDatos();

    $modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
    $modelo = (array) json_decode($modelo);

    $id = GetIdNumeroPaciente($modelo['Id_Paciente']);

    if ($id != '') {
        $oItem = new complex('Paciente_Telefono', 'Id_Paciente_Telefono', $id);
    }else{
        $oItem = new complex('Paciente_Telefono', 'Id_Paciente_Telefono');    
    }    

    foreach($modelo as $index=>$value) {
        $oItem->$index = $value;
    }

    $oItem->save();
    unset($oItem);

    $http_response->SetRespuesta(0, $id != '' ? 'Actualzacion Exitosa' : 'Registro Exitoso', $id != '' ? 'Se ha actualizado el numero del paciente exitosamente!' : 'Se ha registrado el numero del paciente exitosamente!');
    $respuesta = $http_response->GetRespuesta();
    unset($http_response);

    echo json_encode($respuesta);

    function GetIdNumeroPaciente($idPaciente){
        global $queryObj;

        $query = '
            SELECT
                Id_Paciente_Telefono
            FROM Paciente_Telefono
            WHERE
                Id_Paciente = '.$idPaciente;

        $queryObj->SetQuery($query);
        $id = $queryObj->ExecuteQuery('simple');
        $id = $id === false ? '' : $id['Id_Paciente_Telefono'];
        return $id;
    }
?>