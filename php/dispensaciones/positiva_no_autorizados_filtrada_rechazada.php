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

	$pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
	$tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

    $condicion = SetCondiciones($_REQUEST);

    $query='SELECT PNA.Id, 
                    PNA.Codigo, 
                    PNA.Documento_Paciente, 
                    PNA.Estado, 
                    PNA.Observacion, 
                    PNA.Estado_No_Autorizado, 
                    DATE(Fecha) AS Fecha, 
                    PD.Nombre AS Punto_Dispensacion,
                    D.Nombre AS Departamento,
                    M.Nombre AS Municipio
            FROM Positiva_No_Autorizados_App PNA
            INNER JOIN Punto_Dispensacion PD ON PNA.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion
            INNER JOIN Departamento D ON PD.Departamento = D.Id_Departamento
            INNER JOIN Municipio M ON M.Id_Municipio = PD.Municipio
            WHERE PNA.Estado_No_Autorizado = "Rechazada"'.$condicion . 'ORDER BY PNA.Id DESC';
        
    $query_count='SELECT COUNT(PNA.Id) AS Total
                        FROM Positiva_No_Autorizados_App PNA
                        INNER JOIN Punto_Dispensacion PD ON PNA.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion
                        INNER JOIN Departamento D ON PD.Departamento = D.Id_Departamento
                        INNER JOIN Municipio M ON M.Id_Municipio = PD.Municipio
                        WHERE PNA.Estado_No_Autorizado = "Rechazada"'.$condicion . 'ORDER BY PNA.Id ASC';
    
    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query);
    $direccionamientos = $queryObj->Consultar('Multiple', true, $paginationData);

    echo json_encode($direccionamientos);

    function SetCondiciones($req){
        $condicion = '';
        
        if(isset($_REQUEST['dep']) && $_REQUEST['dep'] != ""){
            $str = strtoupper($req['dep']);
            $condicion .= "AND D.Nombre LIKE '%".$str."%'";
        }
        if(isset($_REQUEST['mun']) && $_REQUEST['mun'] != ""){
            $str = strtoupper($req['mun']);
            $condicion .= "AND M.Nombre LIKE '%".$str."%'";

        }
        if(isset($_REQUEST['ident']) && $_REQUEST['ident'] != ""){
            $str = strtoupper($req['ident']);
            $condicion .= "AND  PNA.Documento_Paciente LIKE '%".$str."%'  OR  PNA.Documento_Paciente LIKE '%".$str."%' ";

        }

        if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
            $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
            $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
            
            $condicion .= "AND  DATE_FORMAT(PNA.Fecha,'%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin'";
        }else{
            $fecha_fin=date('Y-m-d');
            $fecha_inicio=date('Y-m-d');
        }
    
        return $condicion;
    }
