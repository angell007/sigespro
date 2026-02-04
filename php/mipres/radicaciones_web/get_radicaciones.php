<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../../class/class.querybasedatos.php');
    include_once('../../../class/class.paginacion.php');
    include_once('../../../class/class.http_response.php');
    include_once('../../../class/class.utility.php');

    $util = new Utility();


    
    $tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '10' );
    $pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '1' );
    // $estado = ( isset( $_REQUEST['estado'] ) ? $_REQUEST['estado'] : '' );

    $condicion = SetCondiciones($_REQUEST);
   
    $query='Select * from Radicacion_Temp RD'.$condicion;
  
   
    $query_count='SELECT COUNT(RD.ID) AS Total  FROM Radicacion_Temp RD'.$condicion;

    
    $paginationData = new PaginacionData($tam, $query_count, $pag);
 
    $queryObj = new QueryBaseDatos($query);
 
    $datos["host"]="localhost";
    $datos["db"]="prohsa_radicaciones";
    $datos["user"]="prohsa";
    $datos["pass"]="Proh2019*";
    
    $radicaciones_web =$queryObj->Consultar('Multiple',true,$paginationData,false,$datos); 
    
    echo json_encode($radicaciones_web);


    function SetCondiciones($req){
        global $util;

       // $condicion = " WHERE RD.Estado = 'Inscripcion'"; 

        // if (isset($req['pac']) && $req['pac']) {
        //     if ($condicion != "") {
        //         $condicion .= " AND (P.Nombre_Paciente LIKE '%".$req['pac']."%' OR P.Id_Paciente LIKE '$req[pac]%')";
        //     } else {
        //         $condicion .= " WHERE (P.Nombre LIKE '%".$req['pac']."%' OR P.Id_Paciente LIKE '$req[pac]%')";
        //     }
        // }
        if (isset($req['departamento']) && $req['departamento']) {
            if ($condicion != "") {
                $condicion .= " AND RD.Departamento='$req[departamento]'";
            } else {
                $condicion .= " WHERE RD.Departamento='$req[departamento]'";
            }
        }

        // if (isset($req['presc']) && $req['presc']) {
        //     if ($condicion != "") {
        //         $condicion .= " AND PD.NoPrescripcion LIKE '%".$req['presc']."%'";
        //     } else {
        //         $condicion .= " WHERE PD.NoPrescripcion LIKE '%".$req['presc']."%'";
        //     }
        // }       

        // if (isset($req['dis']) && $req['dis']) {
        //     if ($condicion != "") {
        //         $condicion .= " AND DIS.Codigo LIKE '%".$req['dis']."%'";
        //     } else {
        //         $condicion .= " WHERE DIS.Codigo LIKE '%".$req['dis']."%'";
        //     }
        // }

        // if (isset($req['fecha']) && $req['fecha']) {
        //     $fechas_separadas = $util->SepararFechas($req['fecha']);

        //     if ($condicion != "") {
        //         $condicion .= " AND DATE(D.Fecha_Direccionamiento) BETWEEN '$fechas_separadas[0]' AND '$fechas_separadas[1]'";
        //     } else {
        //         $condicion .= " WHERE DATE(D.Fecha_Direccionamiento)  BETWEEN '$fechas_separadas[0]' AND '$fechas_separadas[1]'";
        //     }
        // }

  

        return $condicion;
    }




    