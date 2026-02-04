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

    $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
    $years = ["2019","2020"];

    $query = '
        SELECT 
            D.*,Concat(F.Nombres," ",F.Apellidos) as Funcionario, F.Imagen
        FROM Depreciacion D
        INNER JOIN Funcionario F ON D.Identificacion_Funcionario = F.Identificacion_Funcionario
        '.$condicion . ' ORDER BY Anio DESC, Mes DESC';

    $query_count = '
        SELECT 
            COUNT(D.Id_Depreciacion) AS Total
            FROM Depreciacion D
            INNER JOIN Funcionario F ON D.Identificacion_Funcionario = F.Identificacion_Funcionario
        '.$condicion;
    
    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query);
    $actas_realizadas = $queryObj->Consultar('Multiple', true, $paginationData);

 foreach ($actas_realizadas['query_result'] as $key => $value) {
    $actas_realizadas['query_result'][$key]['Nombre_Mes']=MesString($value['Mes']);
 }
    echo json_encode($actas_realizadas);

    function SetCondiciones($req){
        global $util;

        $condicion = ''; 

        if (isset($req['codigo']) && $req['codigo']) {
            if ($condicion != "") {
                $condicion .= " AND D.Codigo LIKE '%".$req['codigo']."%'";
            } else {
                $condicion .= " WHERE D.Codigo LIKE '%".$req['codigo']."%'";
            }
        }

        if (isset($req['func']) && $req['func']) {
            if ($condicion != "") {
                $condicion .= " AND CONCAT(F.Nombres,' ', F.Apellidos) LIKE '%".$req['func']."%'";
            } else {
                $condicion .= " WHERE CONCAT(F.Nombres,' ', F.Apellidos) LIKE '%".$req['func']."%'";
            }
        }
        if (isset($req['estado']) && $req['estado']) {
            if ($condicion != "") {
                $condicion .= " AND D.Estado LIKE '%".$req['estado']."%'";
            } else {
                $condicion .= " WHERE D.Estado LIKE '%".$req['estado']."%'";
            }
        }

     


        if (isset($req['fecha']) && $req['fecha']) {
            $fechas_separadas = $util->SepararFechas($req['fecha']);
            
            if ($condicion != "") {
                $condicion .= " AND D.Fecha >= '".$fechas_separadas[0]."' AND D.Fecha <= '".$fechas_separadas[1]."'";
            } else {
                $condicion .= " WHERE D.Fecha >= '".$fechas_separadas[0]."' AND D.Fecha <= '".$fechas_separadas[1]."'";
            }
        }

       

        return $condicion;
    }

    function MesString($mes_index){
        global $meses;
    
        return  $meses[($mes_index-1)];
    }
          
?>