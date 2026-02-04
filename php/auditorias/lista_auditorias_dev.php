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
    $dis = ( isset( $_REQUEST['dis'] ) ? $_REQUEST['dis'] : '' );

    $condicion = SetCondiciones($_REQUEST);





    $query = 'SELECT 
    CONCAT_WS(" ",P.Primer_Nombre,P.Primer_Apellido) as Paciente, 
    A.Estado, 
    (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion = A.Punto_Pre_Auditoria) as Nombre_Punto, 
    (SELECT Imagen FROM Funcionario WHERE Identificacion_Funcionario=A.Funcionario_Preauditoria) as Imagen1,
    A.Fecha_Preauditoria, 
    DATE_FORMAT(NOW(),"%Y-%m-%d") AS Hoy, 
    A.Fecha_Auditoria,
    A.Id_Auditoria,
    TS.Nombre as TipoServicio,
    A.Id_Dispensacion,
    D.Codigo as DIS,
    A.Id_Paciente,
    P.EPS
    FROM Auditoria A
    LEFT JOIN Paciente P
    ON A.Id_Paciente=P.Id_Paciente
    LEFT JOIN Dispensacion D
    ON A.Id_Dispensacion=D.Id_Dispensacion
    INNER JOIN Tipo_Servicio TS
    ON TS.Id_Tipo_Servicio = A.Id_Tipo_Servicio
    '.$condicion.'
    ORDER BY A.Id_Auditoria DESC';



    $query_count = 'SELECT 
            COUNT(A.Id_Auditoria) AS Total
            FROM Auditoria A
            LEFT JOIN Paciente P
            ON A.Id_Paciente=P.Id_Paciente
            LEFT JOIN Dispensacion D
            ON A.Id_Dispensacion=D.Id_Dispensacion
            INNER JOIN Tipo_Servicio TS
            ON TS.Id_Tipo_Servicio = A.Id_Tipo_Servicio
            '.$condicion;
    
    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query);
    $auditorias = $queryObj->Consultar('Multiple', true, $paginationData);

    echo json_encode($auditorias);

    function SetCondiciones($req){
        global $util, $dis;

        $condicion = '';

        if ($req['id_funcionario'] == "1005148924") {
            $condicion = '';
        }else{
            if($dis==''){
                $condicion = ' WHERE A.Punto_Pre_Auditoria ='.$req['punto']; 
            }
           
        }


        if (isset($req['sin_dis']) && $req['sin_dis'] !='') {   
            if ($condicion == '') {
               $condicion .= " WHERE A.Id_Dispensacion IS NULL";
            }else{
                $condicion .= " AND A.Id_Dispensacion IS NULL";
            }    
        }

        if (isset($req['cod']) && $req['cod']!='') {
            if ($condicion == '') {
               $condicion .= " WHERE A.Id_Auditoria=".str_replace("AUD00","",$req['cod']);
            }else{
                $condicion .= " AND A.Id_Auditoria=".str_replace("AUD00","",$req['cod']);
            }
            
        }

        if (isset($req['pac']) && $req['pac']!='') {
            if ($condicion == '') {
               $condicion .= " WHERE A.Id_Paciente LIKE '%".$req['pac']."%'";
            }else{
                $condicion .= " AND A.Id_Paciente LIKE '%".$req['pac']."%'";
            }           
        }

        if (isset($req['serv']) && $req['serv']!='') {
            if ($condicion == '') {
               $condicion .= " WHERE A.Id_Tipo_Servicio=$req[serv]";
            }else{
                $condicion .= " AND A.Id_Tipo_Servicio=$req[serv]";
            }
        }

        if (isset($req['dis']) && $req['dis']!='') {
            if ($condicion == '') {
               $condicion .= " WHERE D.Codigo LIKE '%$req[dis]%'";
            }else{
                $condicion .= " AND D.Codigo LIKE '%$req[dis]%'";
            }
        }    

        if (isset($req['fecha']) && $req['fecha']!='') {
            $fechas_separadas = $util->SepararFechas($req['fecha']);   
            if ($condicion == '') {
               $condicion .= " WHERE A.Fecha_Preauditoria >= '".$fechas_separadas[0]."' AND A.Fecha_Preauditoria <= '".$fechas_separadas[1]."'";
            }else{
                $condicion .= " AND A.Fecha_Preauditoria >= '".$fechas_separadas[0]."' AND A.Fecha_Preauditoria <= '".$fechas_separadas[1]."'";
            }            
        }

        return $condicion;
    }
          
?>