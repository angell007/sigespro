<?php 
    
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
include_once('../../../class/class.querybasedatos.php');
include_once('../../../class/class.paginacion.php');
include_once('../../../class/class.http_response.php');
include_once('../../../class/class.utility.php');

    $util = new Utility();

    $pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
    $tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

    $condicion = SetCondiciones($_REQUEST);

    



    $query = 'SELECT I.*, B.Nombre AS "Nombre_Bodega",G.Nombre AS"Nombre_Grupo",
                F.Nombres AS "Nombre_Funcionario_Autorizo"  
            FROM Inventario_Fisico_Punto_Nuevo I
            INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba=I.Id_Grupo_Estiba
            INNER JOIN Punto_Dispensacion B ON B.Id_Punto_Dispensacion=I.Id_Punto_Dispensacion 
            INNER JOIN Funcionario F  ON F.Identificacion_Funcionario=I.Funcionario_Autoriza
            
            
    
    '.$condicion.' ORDER BY Id_Inventario_Fisico_Punto_Nuevo DESC';


    $query_count = '
        SELECT 
            COUNT(I.Id_Inventario_Fisico_Punto_Nuevo) AS Total
            FROM Inventario_Fisico_Punto_Nuevo I
            INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba=I.Id_Grupo_Estiba
            INNER JOIN Punto_Dispensacion B ON B.Id_Punto_Dispensacion=I.Id_Punto_Dispensacion 
            INNER JOIN Funcionario F  ON F.Identificacion_Funcionario=I.Funcionario_Autoriza
            
            
           
        '.$condicion;
    

    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query);
    $direccionamientos = $queryObj->Consultar('Multiple', true, $paginationData);

    echo json_encode($direccionamientos);

    function SetCondiciones($req){
        global $util;

        $condicion = ''; 

        if (isset($req['grupo']) && $req['grupo']) {
            if ($condicion != "") {
                $condicion .= " AND I.Id_Grupo_Estiba='$req[grupo]'";
            } else {
                $condicion .= " WHERE I.Id_Grupo_Estiba='$req[grupo]'";
            }
        }

        if (isset($req['punto']) && $req['punto']) {
            if ($condicion != "") {
                $condicion .= " AND I.Id_Punto_Dispensacion='$req[punto]'";
            } else {
                $condicion .= " WHERE I.Id_Punto_Dispensacion='$req[punto]'";
            }
        }

        if (isset($req['fechas']) && $req['fechas']) {
            $fechas_separadas = $util->SepararFechas($req['fechas']);
            if ($condicion != "") {
                $condicion .= " AND DATE(I.Fecha) BETWEEN '$fechas_separadas[0]' AND '$fechas_separadas[1]'";
            } else {
                $condicion .= " WHERE DATE(I.Fecha) BETWEEN '$fechas_separadas[0]' AND '$fechas_separadas[1]'";
            }

                
           
        }

  

        return $condicion;
    }
          
?>