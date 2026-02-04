<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.utility.php');

    $condicion = SetCondiciones($_REQUEST);

    $query = '
        SELECT 
            OCI.Id_Orden_Compra_Internacional,
            F.Imagen,
            OCI.Codigo,
            OCI.Fecha_Registro,
            OCI.Flete_Internacional,
            OCI.Seguro_Internacional,
            OCI.Tramite_Sia,
            OCI.Flete_Nacional,
            CONCAT_WS(" ", P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) AS Nombre_Proveedor,
            (SELECT COUNT(Id_Producto_Orden_Compra_Internacional) FROM Producto_Orden_Compra_Internacional WHERE Id_Orden_Compra_Internacional = OCI.Id_Orden_Compra_Internacional) AS Cantidad_Productos
        FROM Orden_Compra_Internacional OCI
        INNER JOIN Proveedor P ON OCI.Id_Proveedor = P.Id_Proveedor
        INNER JOIN Funcionario F ON OCI.Identificacion_Funcionario = F.Identificacion_Funcionario
        '.$condicion
        .' LIMIT 20';
    
    $queryObj = new QueryBaseDatos($query);
    $ordenes_internacionales_pendientes = $queryObj->Consultar('Multiple');

    // if (count($ordenes_internacionales_pendientes['query_result']) > 0) {
        
    //     $ordenes_internacionales_pendientes['query_result'] = HabilitarRealizarActa($ordenes_internacionales_pendientes['query_result']);
    // }

    echo json_encode($ordenes_internacionales_pendientes);

    function SetCondiciones($req){
        $condicion = ' WHERE OCI.Estado = "Pendiente" '; 

        if (isset($req['orden']) && $req['orden']) {
            if ($condicion != "") {
                $condicion .= " AND OCI.Codigo LIKE '%".$req['orden']."%'";
            } else {
                $condicion .= " WHERE OCI.Codigo LIKE '%".$req['orden']."%'";
            }
        }

        if (isset($req['proveedor']) && $req['proveedor']) {
            if ($condicion != "") {
                $condicion .= " AND CONCAT_WS(' ', P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) LIKE '%".$req['proveedor']."%'";
            } else {
                $condicion .= " WHERE CONCAT_WS(' ', P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) LIKE '%".$req['proveedor']."%'";
            }
        }

        return $condicion;
    }

    function HabilitarRealizarActa($ordenes){
        $i = 0;
        foreach ($ordenes as $orden) {
            
            $ordenes[$i]['realizar_acta'] = ValidarInformacionCompleta($orden);
            $i++;
        }
        
        return $ordenes;
    }

    function ValidarInformacionCompleta($orden) {
        if ($orden['Flete_Internacional'] == '0.00') {
            return  false;
        }else if ($orden['Seguro_Internacional'] == '0.00') {
            return  false;
        }else if ($orden['Flete_Nacional'] == '0.00') {
            return  false;
        }else if ($orden['Tramite_Sia'] == '0.00') {
            return  false;
        }else{        
            return true;
        }   
    }
          
?>