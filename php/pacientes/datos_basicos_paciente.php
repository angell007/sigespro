<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.lista.php');
    include_once('../../class/class.complex.php');
    include_once('../../class/class.consulta.php');

    $idPaciente = ( isset( $_REQUEST['id_paciente'] ) ? $_REQUEST['id_paciente'] : '' );
    $response=['codigo' => 'W', 'data' => '', 'mensaje' => 'Sin datos'];

    try{
        if(empty($idPaciente)){
            echo json_encode($response);
            return;
        }

        $query = "SELECT P.*, IFNULL(TD.Nombre , P.Tipo_Documento) AS NombreDocumento
                FROM Paciente P 
                left Join Tipo_Documento TD ON TD.Id_Tipo_Documento=P.Tipo_Documento 
                WHERE P.Id_Paciente='$idPaciente'" ;
            
        $oCon= new consulta();
        $oCon->setQuery($query);
        $response['data'] = $oCon->getData();
        $response['codigo'] = 'OK';
        $response['mensaje'] = 'Exitoso';
        unset($oCon);

        echo json_encode($response);

    }catch(Exception $e){

        $response['data'] ='';
        $response['codigo'] = 'E';
        $response['mensaje'] = 'Error= '.$e->getMessage();
        echo json_encode($response);
    }    
?>