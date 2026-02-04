<?php 
    
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

    include_once('../../../class/class.querybasedatos.php');
    include_once('../../../class/class.paginacion.php');
    include_once('../../../class/class.utility.php');
    include_once('../../../helper/response.php ');

    $util = new Utility();

    $pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
    $tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

    $condicion = SetCondiciones($_REQUEST);

    $query = 'SELECT * FROM (SELECT I.Id_Inventario_Fisico_Nuevo, 
            I.Fecha,
            B.Nombre AS "Nombre_Bodega",
            G.Nombre AS "Nombre_Grupo",
            F.Nombres AS "Nombre_Funcionario_Autorizo", 
            "General" As Tipo
            FROM Inventario_Fisico_Nuevo I
            INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba=I.Id_Grupo_Estiba
            INNER JOIN Bodega_Nuevo B ON B.id_Bodega_Nuevo=I.Id_Bodega_Nuevo 
            INNER JOIN Funcionario F  ON F.Identificacion_Funcionario=I.Funcionario_Autoriza
            
            UNION 

            SELECT I.Id_Doc_Inventario_Auditable, 
            I.Fecha_Fin AS Fecha,
            B.Nombre AS "Nombre_Bodega", 
            "Sin Grupo" As Nombre_Grupo,
            F.Nombres AS "Nombre_Funcionario_Autorizo",
            "Auditoria" As Tipo
            FROM Doc_Inventario_Auditable As I
            INNER JOIN Bodega_Nuevo B ON B.id_Bodega_Nuevo=I.Id_Bodega 
            INNER JOIN Funcionario F  ON F.Identificacion_Funcionario=I.Funcionario_Autorizo
          
            ) 
             As Datas 
            '.$condicion.'
             ORDER BY Id_Inventario_Fisico_Nuevo DESC
             ';


    $query_count = 'SELECT 
            COUNT(Datas.Id_Inventario_Fisico_Nuevo) 
             + (SELECT
             COUNT( Datas.Id_Doc_Inventario_Auditable) AS Total
              FROM Doc_Inventario_Auditable As Datas
             INNER JOIN Bodega_Nuevo B ON B.id_Bodega_Nuevo=Datas.Id_Bodega 
             INNER JOIN Funcionario F  ON F.Identificacion_Funcionario=Datas.Funcionario_Autorizo
             '.$condicion.'
             )  
             AS Total
             FROM Inventario_Fisico_Nuevo As Datas
             INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba=Datas.Id_Grupo_Estiba
             INNER JOIN Bodega_Nuevo B ON B.id_Bodega_Nuevo=Datas.Id_Bodega_Nuevo 
             INNER JOIN Funcionario F  ON F.Identificacion_Funcionario=Datas.Funcionario_Autoriza '.$condicion.'';
    

    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query);
    $direccionamientos = $queryObj->Consultar('Multiple', true, $paginationData);

    echo json_encode($direccionamientos);

    // show($direccionamientos);

    function SetCondiciones($req){
        global $util;

        $condicion = ''; 

        if (isset($req['grupo']) && $req['grupo']) {
            if ($condicion != "") {
                $condicion .= " AND Datas.Id_Grupo_Estiba='$req[grupo]'";
            } else {
                $condicion .= " WHERE Datas.Id_Grupo_Estiba='$req[grupo]'";
            }
        }

        if (isset($req['bodega']) && $req['bodega']) {
            if ($condicion != "") {
                $condicion .= " AND Datas.Id_Bodega_Nuevo='$req[bodega]'";
            } else {
                $condicion .= " WHERE Datas.Id_Bodega_Nuevo='$req[bodega]'";
            }
        }

        if (isset($req['fechas']) && $req['fechas']) {
            $fechas_separadas = $util->SepararFechas($req['fechas']);
            if ($condicion != "") {
                $condicion .= " AND DATE(Datas.Fecha) BETWEEN '$fechas_separadas[0]' AND '$fechas_separadas[1]'";
            } else {
                $condicion .= " WHERE DATE(Datas.Fecha) BETWEEN '$fechas_separadas[0]' AND '$fechas_separadas[1]'";
            }
        }
        return $condicion;
    }
          