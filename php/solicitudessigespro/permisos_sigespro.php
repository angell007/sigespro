<?php
include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');

function ObtenerPermisoModulo($id_funcionario, $titulo_modulo){
    if ($id_funcionario == '' || $titulo_modulo == '') {
        return null;
    }

    $queryObj = new QueryBaseDatos();
    $query = "SELECT * FROM Perfil_Funcionario WHERE Identificacion_Funcionario = ".$id_funcionario." AND Titulo_Modulo = '".$titulo_modulo."' LIMIT 1";
    $queryObj->SetQuery($query);
    $permiso = $queryObj->ExecuteQuery('simple');

    if (!$permiso) {
        $perfil_id = ObtenerPerfilFuncionario($id_funcionario);
        if ($perfil_id) {
            $query = "SELECT * FROM Perfil_Permiso WHERE Id_Perfil = ".$perfil_id." AND Titulo_Modulo = '".$titulo_modulo."' LIMIT 1";
            $queryObj->SetQuery($query);
            $permiso = $queryObj->ExecuteQuery('simple');
        }
    }

    return $permiso;
}

function ObtenerPerfilFuncionario($id_funcionario){
    $queryObj = new QueryBaseDatos();
    $query = "SELECT Id_Perfil FROM Perfil_Funcionario WHERE Identificacion_Funcionario = ".$id_funcionario." LIMIT 1";
    $queryObj->SetQuery($query);
    $perfil = $queryObj->ExecuteQuery('simple');
    return $perfil ? $perfil['Id_Perfil'] : null;
}

function ValidarPermiso($permiso, $campo){
    return $permiso && isset($permiso[$campo]) && $permiso[$campo] == '1';
}

function RespuestaPermisoDenegado(){
    $http_response = new HttpResponse();
    $http_response->SetRespuesta(2, 'Sin permisos', 'No tiene permisos para realizar esta accion.');
    return $http_response->GetRespuesta();
}

function ObtenerEstadoSolicitud($id_solicitud){
    if ($id_solicitud == '') {
        return null;
    }

    $queryObj = new QueryBaseDatos();
    $query = "SELECT Estado_Solicitud FROM Solicitud_Sigespro WHERE Id_Solicitud_Sigespro = ".$id_solicitud." LIMIT 1";
    $queryObj->SetQuery($query);
    $estado = $queryObj->ExecuteQuery('simple');
    return $estado ? $estado['Estado_Solicitud'] : null;
}

function RespuestaEstadoInvalido($mensaje){
    $http_response = new HttpResponse();
    $http_response->SetRespuesta(2, 'Estado invalido', $mensaje);
    return $http_response->GetRespuesta();
}
?>
