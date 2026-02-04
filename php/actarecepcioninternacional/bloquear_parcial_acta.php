<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');

    $http_response = new HttpResponse();
    $response = array();

    $id_acta = ( isset( $_REQUEST['id_acta'] ) ? $_REQUEST['id_acta'] : '' );
    $accion = ( isset( $_REQUEST['accion'] ) ? $_REQUEST['accion'] : '' );

    $query = 
        "SELECT 
            Bloquear_Parcial
        FROM Acta_Recepcion_Internacional
        WHERE
            Id_Acta_Recepcion_Internacional = $id_acta";
    
    $queryObj = new QueryBaseDatos($query);
    $bloqueo = $queryObj->ExecuteQuery('simple');

    if ($bloqueo['Bloquear_Parcial'] == 'Si' && strtolower($accion) == 'bloquear') {
        $http_response->SetRespuesta(2, 'Alerta', 'Un funcionario se encuentra realizando un parcial sobre esta acta, intente realizar un parcial mas tarde!');
        $response = $http_response->GetRespuesta();
    }elseif($bloqueo['Bloquear_Parcial'] == 'Si' && strtolower($accion) == 'desbloquear'){

        $query = 'UPDATE Acta_Recepcion_Internacional SET Bloquear_Parcial = "No" WHERE Id_Acta_Recepcion_Internacional = '.$id_acta;
        $queryObj->SetQuery($query);
        $queryObj->QueryUpdate();
        $http_response->SetRespuesta(0, 'Bloqueo Exitoso', 'El acta ha sido liberada para su uso!');
        $response = $http_response->GetRespuesta();
    }elseif($bloqueo['Bloquear_Parcial'] == 'No' && strtolower($accion) == 'bloquear'){

        $query = 'UPDATE Acta_Recepcion_Internacional SET Bloquear_Parcial = "Si" WHERE Id_Acta_Recepcion_Internacional = '.$id_acta;
        $queryObj->SetQuery($query);
        $queryObj->QueryUpdate();
        $http_response->SetRespuesta(0, 'Bloqueo Exitoso', 'Ha bloqueado esta acta mientras realiza el parcial');
        $response = $http_response->GetRespuesta();
    }elseif($bloqueo['Bloquear_Parcial'] == 'No' && strtolower($accion) == 'desbloquear'){

        $http_response->SetRespuesta(2, 'Alerta', 'Esta acta ya esta libre para su uso!');
        $response = $http_response->GetRespuesta();
    }

    echo json_encode($response);
          
?>