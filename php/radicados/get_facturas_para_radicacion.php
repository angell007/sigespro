<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    date_default_timezone_set('America/Bogota');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');

    $http_response = new HttpResponse();
    $queryObj = new QueryBaseDatos();
    $queryObjRevisionSiExiste=new QueryBaseDatos();
    $tablaVerificarSiExiste;
    $condicion_capita = '';

    /*$pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
    $tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );*/
    $tipo = ( isset( $_REQUEST['tipo_servicio'] ) ? $_REQUEST['tipo_servicio'] : '' );

    $tipo_servicio = GetTipoServicio($tipo);

    $condicion = SetCondiciones($tipo_servicio);
    $fecha = date('Y-m-d');

    $query = '';

    if (strtolower($tipo_servicio) != 'capita') {
        
        $query = '
            SELECT 
                F.Id_Factura,
                F.Codigo AS Codigo_Factura,
                D.Codigo AS Codigo_Dis,
                UPPER(CONCAT_WS(" ", P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido)) AS Nombre_Paciente,
                false AS Seleccionada,
                (
                    CASE
                        WHEN C.Tipo_Valor = "Exacta" THEN (SELECT SUM( ((Precio * Cantidad)+((Precio * Cantidad - IF(F.Id_Cliente = 890500890,FLOOR(Descuento*Cantidad), (Descuento*Cantidad)) ) * (Impuesto/100) )) - (IF(F.Id_Cliente = 890500890, FLOOR(Descuento* Cantidad), Descuento* Cantidad))) - F.Cuota FROM Producto_Factura WHERE Id_Factura = F.Id_Factura)
                        ELSE (SELECT ROUND(SUM( ((ROUND(Precio) * Cantidad)+((ROUND(Precio) * Cantidad- ROUND((Descuento*Cantidad))) * (Impuesto/100) )) - ROUND((Descuento*Cantidad)))) - F.Cuota FROM Producto_Factura WHERE Id_Factura = F.Id_Factura)
                    END
                ) AS Valor_Factura,
                C.Tipo_Valor
            FROM Factura F
            INNER JOIN Cliente C ON F.Id_Cliente = C.Id_Cliente
            INNER JOIN Dispensacion D ON F.Id_Dispensacion = D.Id_Dispensacion
            INNER JOIN Paciente P ON D.Numero_Documento = P.Id_Paciente'
            .$condicion;
            
            $nombreTablaVerficar='Factura';
            $idTablaVerficar='Id_Factura';
    }else{
        $query = '
            SELECT 
                F.Id_Factura_Capita AS Id_Factura,
                F.Codigo AS Codigo_Factura,
                IFNULL(F.Codigo, "") AS Codigo_Dis,
                IFNULL(DFC.Descripcion, "") AS Nombre_Paciente,
                false AS Seleccionada,
                (SUM(DFC.Total) - F.Cuota_Moderadora) AS Valor_Factura,
                C.Tipo_Valor
            FROM Factura_Capita F
            INNER JOIN Cliente C ON F.Id_Cliente = C.Id_Cliente
            INNER JOIN Descripcion_Factura_Capita DFC ON F.Id_Factura_Capita = DFC.Id_Factura_Capita'
            .$condicion_capita
            .' GROUP BY DFC.Id_Factura_Capita';

            $nombreTablaVerficar='Factura_Capita';
            $idTablaVerficar='Id_Factura_Capita';
            
    }

    

    //Se crea la instancia que contiene la consulta a realizar
    $queryObj->SetQuery($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $result = $queryObj->Consultar('Multiple');

    
   $array_filtrado=[];
    foreach ($result['query_result'] as $results => $value) {
        
        $cond = '';
        if($nombreTablaVerficar=="Factura_Capita"){
            $cond='R.Id_Tipo_Servicio = 7 AND';
        }
        $queryVerificarSieExiste="SELECT Id_Factura From Radicado_Factura RF
        INNER JOIN Radicado R ON R.Id_Radicado = RF.Id_Radicado
        WHERE ".$cond." Id_Factura  = ' ".$value['Id_Factura'] ." '" ;
         $queryObjRevisionSiExiste->setQuery($queryVerificarSieExiste);
         $resultRadicado=$queryObjRevisionSiExiste->Consultar();
         if ($resultRadicado['query_result']!='') {
           
           $oItem = new complex($nombreTablaVerficar, $idTablaVerficar, $value['Id_Factura']);
           $oItem->Estado_Radicacion= 'Radicada';
           $oItem->save();
           unset($oItem);

         }else{
             $array_filtrado[]=$value;
         }

    }
 

    if (count($array_filtrado)==0 | !$array_filtrado) {
        $result['codigo']='warning';
        $result['titulo']= 'titulo';
        $result['mensaje']= 'No se han encontrado registros!';
        $result['query_result']='';
    }else{
        $result['query_result']=$array_filtrado;
    }

  
 
    // if (count($result['query_result']) > 0) {
    //     $i = 0;
    //     foreach ($result['query_result'] as $value) {
            
    //         $result['query_result'][$i]['Valor_Factura'] = $value['Valor_Factura'] - $value['Valor_Homologo'];
    //     }
    // }


    echo json_encode($result);

    function SetCondiciones($tipo_servicio){
        global $condicion_capita;

        $req = $_REQUEST;
        $condicion = '';
        //$condicion = ' WHERE F.Estado_Radicacion = "Pendiente" AND F.Estado_Factura != "Anulada" AND Id_Factura_Asociada IS NOT NULL ';


        $condicion = ' WHERE F.Estado_Radicacion = "Pendiente" AND F.Estado_Factura != "Anulada"';
        $condicion_capita = ' WHERE F.Estado_Radicacion = "Pendiente" AND F.Estado_Factura != "Anulada"';

        if (isset($req['id_regimen']) && $req['id_regimen']) {
            $condicion_capita .= ' AND F.Id_Regimen = '.$req['id_regimen'];
            if ($condicion != "") {
                $condicion .= " AND P.Id_Regimen = ".$req['id_regimen'];
            } else {
                $condicion .= " WHERE P.Id_Regimen = ".$req['id_regimen'];
            }
        }

        if ($tipo_servicio != '') {            
            if ($condicion != "") {
                if ($tipo_servicio != 'CAPITA') {
                    $condicion .= " AND D.Id_Tipo_Servicio = $req[tipo_servicio]";
                }

                // if ($tipo == 'evento' || $tipo == 'capita') {
                //     $condicion .= " AND D.Tipo = '".$tipo."'";

                // }else if ($tipo == '6') {
                //     $condicion .= " AND (D.Tipo = 'COHORTES' OR Tipo_Servicio = 6)";
                // }else if (intval($tipo)) {
                //     $condicion .= " AND D.Tipo = 'NoPos' AND D.Tipo_Servicio = ".$tipo;
                // }
            } else {
                if ($tipo_servicio != 'CAPITA') {
                    $condicion .= " WHERE D.Id_Tipo_Servicio = $req[tipo_servicio]";
                }
                // if ($tipo == 'evento') {
                //     $condicion .= " WHERE D.Tipo = '".$tipo."'";

                // }else if ($tipo == '6') {
                //     $condicion .= " AND (D.Tipo = 'COHORTES' OR Tipo_Servicio = 6)";
                // }else if (intval($tipo)) {
                //     $condicion .= " WHERE D.Tipo = 'NoPos' AND D.Tipo_Servicio = ".$tipo;
                // }
            }
        }

        if (isset($req['id_departamento']) && $req['id_departamento']) {
            $condicion_capita .= ' AND F.Id_Departamento = '.$req['id_departamento'];
            if ($condicion != "") {
                $condicion .= " AND P.Id_Departamento = ".$req['id_departamento'];
            } else {
                $condicion .= " WHERE P.Id_Departamento = ".$req['id_departamento'];
            }
        }

        if (isset($req['id_cliente']) && $req['id_cliente']) {
            $condicion_capita .= ' AND F.Id_Cliente = '.$req['id_cliente'];
            if ($condicion != "") {
                $condicion .= " AND F.Id_Cliente = ".$req['id_cliente'];
            } else {
                $condicion .= " WHERE F.Id_Cliente = ".$req['id_cliente'];
            }
        }

        if (isset($req['id_eps']) && $req['id_eps']) {
            if ($condicion != "") {
                $condicion .= " AND P.Nit = ".$req['id_eps'];
            } else {
                $condicion .= " WHERE P.Nit = ".$req['id_eps'];
            }
        }

        return $condicion;
    }

    function GetTipoServicio($idTipoServicio){
        global $queryObj;

        if ($idTipoServicio == '') {
             return "";
        }

        $query = '
            SELECT 
                Nombre
            FROM Tipo_Servicio
            WHERE
                Id_Tipo_Servicio ='.$idTipoServicio;

        $queryObj->SetQuery($query);
        $tipo_servicio = $queryObj->ExecuteQuery('simple');
        return $tipo_servicio['Nombre'];        
    }
?>