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

    $condicion = SetCondiciones($_REQUEST);

    $query = '
    SELECT D.*,DATE_SUB(Fecha_Maxima_Entrega, INTERVAL 5 DAY) as Fecha_Maxima_Radicacion, P.Id_Paciente, P.Nombre, PD.Items, PD.IdDireccionamiento, PD.NoPrescripcion, (SELECT DP.Nombre FROM Municipio M INNER JOIN Departamento DP ON M.Id_Departamento=DP.Id_Departamento WHERE M.Codigo=D.Codigo_Municipio) as Departamento,
    DIS.Codigo as CodDispensacion, PD.IdProgramacion, PD.IdEntrega, PD.IdReporteEntrega
    FROM Dispensacion_Mipres D
    INNER JOIN (SELECT Id_Paciente, Concat_ws(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) as Nombre  FROM Paciente ) P ON D.Id_Paciente=P.Id_Paciente
    INNER JOIN (SELECT PDM.Id_Dispensacion_Mipres, COUNT(PDM.IdDireccionamiento) as Items, GROUP_CONCAT(DISTINCT(PDM.IdDireccionamiento)) as IdDireccionamiento, GROUP_CONCAT(DISTINCT(PDM.NoPrescripcion)) as NoPrescripcion, GROUP_CONCAT(DISTINCT(PDM.IdProgramacion)) as IdProgramacion, GROUP_CONCAT(DISTINCT(PDM.IdEntrega)) as IdEntrega, GROUP_CONCAT(DISTINCT(PDM.IdReporteEntrega)) as IdReporteEntrega FROM Producto_Dispensacion_Mipres PDM GROUP BY PDM.Id_Dispensacion_Mipres) PD ON PD.Id_Dispensacion_Mipres=D.Id_Dispensacion_Mipres
    LEFT JOIN (SELECT Dis.Id_Dispensacion_Mipres, Dis.Codigo FROM Dispensacion Dis  WHERE Dis.Id_Dispensacion_Mipres!=0 AND Dis.Estado_Dispensacion!="Anulada") DIS ON DIS.Id_Dispensacion_Mipres=D.Id_Dispensacion_Mipres 
        '.$condicion.' ORDER BY D.Id_Paciente ASC,D.Fecha ASC ';


    $query_count = '
        SELECT 
            COUNT(D.Id_Dispensacion_Mipres) AS Total
            FROM Dispensacion_Mipres D
            INNER JOIN (SELECT Id_Paciente, Concat_ws(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) as Nombre  FROM Paciente ) P ON D.Id_Paciente=P.Id_Paciente
            INNER JOIN (SELECT PDM.Id_Dispensacion_Mipres, COUNT(PDM.IdDireccionamiento) as Items, GROUP_CONCAT(DISTINCT(PDM.IdDireccionamiento)) as IdDireccionamiento, GROUP_CONCAT(DISTINCT(PDM.NoPrescripcion)) as NoPrescripcion FROM Producto_Dispensacion_Mipres PDM GROUP BY PDM.Id_Dispensacion_Mipres) PD ON PD.Id_Dispensacion_Mipres=D.Id_Dispensacion_Mipres
            LEFT JOIN (SELECT Dis.Id_Dispensacion_Mipres, Dis.Codigo FROM Dispensacion Dis WHERE Dis.Id_Dispensacion_Mipres!=0 AND Dis.Estado_Dispensacion!="Anulada") DIS ON DIS.Id_Dispensacion_Mipres=D.Id_Dispensacion_Mipres 
        '.$condicion;
    
    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query);
    $direccionamientos = $queryObj->Consultar('Multiple', true, $paginationData);

    echo json_encode($direccionamientos);

    function SetCondiciones($req){
        global $util;

        $condicion = ''; 

        if (isset($req['pac']) && $req['pac']) {
            if ($condicion != "") {
                $condicion .= " AND (P.Nombre LIKE '%".$req['pac']."%' OR P.Id_Paciente LIKE '$req[pac]%')";
            } else {
                $condicion .= " WHERE (P.Nombre LIKE '%".$req['pac']."%' OR P.Id_Paciente LIKE '$req[pac]%')";
            }
        }
        if (isset($req['est']) && $req['est']) {
            if ($condicion != "") {
                $condicion .= " AND D.Estado='$req[est]'";
            } else {
                $condicion .= " WHERE D.Estado='$req[est]'";
            }
        }

        if (isset($req['presc']) && $req['presc']) {
            if ($condicion != "") {
                $condicion .= " AND PD.NoPrescripcion LIKE '%".$req['presc']."%'";
            } else {
                $condicion .= " WHERE PD.NoPrescripcion LIKE '%".$req['presc']."%'";
            }
        }       

        if (isset($req['dis']) && $req['dis']) {
            if ($condicion != "") {
                $condicion .= " AND DIS.Codigo LIKE '%".$req['dis']."%'";
            } else {
                $condicion .= " WHERE DIS.Codigo LIKE '%".$req['dis']."%'";
            }
        }

        if (isset($req['fecha']) && $req['fecha']) {
            $fechas_separadas = $util->SepararFechas($req['fecha']);

            if ($condicion != "") {
                $condicion .= " AND DATE(D.Fecha_Direccionamiento) BETWEEN '$fechas_separadas[0]' AND '$fechas_separadas[1]'";
            } else {
                $condicion .= " WHERE DATE(D.Fecha_Direccionamiento)  BETWEEN '$fechas_separadas[0]' AND '$fechas_separadas[1]'";
            }
        }

  

        return $condicion;
    }
          
?>