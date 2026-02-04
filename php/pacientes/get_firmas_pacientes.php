<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.utility.php');

    $util = new Utility();

    $pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
    $tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

    $condicion = SetCondiciones();

    $query_pacientes = '
        SELECT 
            PA.*,
            T.Persona,
            T.Identificacion_Persona
        FROM (SELECT 
                P.Id_Paciente,
                D.Id_Dispensacion,
                CONCAT_WS(" ", P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) AS Nombre_Paciente,
                D.Firma_Reclamante
              FROM Dispensacion D
              INNER JOIN Paciente P ON D.Numero_Documento = P.Id_Paciente
              '.$condicion.') AS PA
        INNER JOIN Auditoria A ON PA.Id_Dispensacion = A.Id_Dispensacion AND PA.Id_Paciente = A.Id_Paciente
        INNER JOIN (SELECT Id_Auditoria, Persona, Identificacion_Persona FROM Turnero WHERE Id_Auditoria IS NOT NULL) T ON A.Id_Auditoria = T.Id_Auditoria';

    $query_count = '
        SELECT 
            COUNT(*) AS Total
        FROM (SELECT 
                P.Id_Paciente,
                D.Id_Dispensacion,
                CONCAT_WS(" ", P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) AS Nombre_Paciente,
                D.Firma_Reclamante
              FROM Dispensacion D
              INNER JOIN Paciente P ON D.Numero_Documento = P.Id_Paciente
              '.$condicion.') AS PA
        INNER JOIN Auditoria A ON PA.Id_Dispensacion = A.Id_Dispensacion AND PA.Id_Paciente = A.Id_Paciente
        INNER JOIN (SELECT Id_Auditoria, Persona, Identificacion_Persona FROM Turnero WHERE Id_Auditoria IS NOT NULL) T ON A.Id_Auditoria = T.Id_Auditoria';
    
    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query_pacientes);
    $pacientes = $queryObj->Consultar('Multiple', true, $paginationData);

    echo json_encode($pacientes);

    function SetCondiciones(){
        $req = $_REQUEST;
        $condicion = 'WHERE (D.Firma_Reclamante IS NOT NULL AND D.Firma_Reclamante <> "") '; 

        if (isset($req['nombre_paciente']) && $req['nombre_paciente']) {
            if ($condicion != "") {
                $condicion .= " AND CONCAT_WS(' ', P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) LIKE '%".$req['nombre_paciente']."%'";
            } else {
                $condicion .= " WHERE CONCAT_WS(' ', P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) LIKE '%".$req['nombre_paciente']."%'";
            }
        }

        if (isset($req['id_paciente']) && $req['id_paciente']) {
            if ($condicion != "") {
                $condicion .= " AND D.Numero_Documento = ".$req['id_paciente'];
            } else {
                $condicion .= " WHERE D.Numero_Documento = ".$req['id_paciente'];
            }
        }

        return $condicion;
    }
          
?>