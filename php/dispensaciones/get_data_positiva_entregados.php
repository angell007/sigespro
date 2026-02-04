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

    
	$pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
	$tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

    $condicion = SetCondiciones($_REQUEST);

    $query = 'SELECT PD.id,PD.numeroAutorizacion, 
                         PD.fechaHoraAutorizacion,
                         PD.AFnumeroDocumento,
                         PD.serviciosAutorizados,
                         CONCAT(PD.AFprimerNombre, " ", PD.AFprimerApellido) as Paciente, 
                         PD.AFdepartamento,
                         PD.AFmunicipio,
                         PD.fechaVencimiento,
                         D.Tipo_Entrega,
                         D.Codigo,
                         PD.Detalle_Estado,
                         PD.Estado
                         FROM Positiva_Data PD
                         INNER JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion
                         WHERE D.Pendientes <= 0  AND D.Pendientes IS NOT NULL and PD.Id_Dispensacion IS NOT NULL
                         '.$condicion.' ORDER BY PD.id DESC';

$query_count='SELECT COUNT(id) AS Total
                 FROM Positiva_Data PD
                 INNER JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion
                 WHERE D.Pendientes <= 0  AND D.Pendientes IS NOT NULL and PD.Id_Dispensacion IS NOT NULL
                         '.$condicion.' ORDER BY PD.id DESC';


$paginationData = new PaginacionData($tam, $query_count, $pag);
$queryObj = new QueryBaseDatos($query);
$direccionamientos = $queryObj->Consultar('Multiple', true, $paginationData);

echo json_encode($direccionamientos);


    function SetCondiciones($req){
        $condicion = '';
        
        if(isset($_REQUEST['dep']) && $_REQUEST['dep'] != ""){
            $str = strtoupper($req['dep']);
            $condicion .= "AND PD.AFdepartamento LIKE '%".$str."%' ";
        }

        
        if(isset($_REQUEST['mun']) && $_REQUEST['mun'] != ""){
            $str = strtoupper($req['mun']);
            $condicion .= "AND PD.AFmunicipio LIKE '%".$str."%' ";
        }

        if(isset($_REQUEST['ident']) && $_REQUEST['ident'] != ""){
            $str = strtoupper($req['ident']);
            $condicion .= "AND PD.AFnumeroDocumento LIKE '%".$str."%'  OR PD.AFprimerNombre LIKE '%".$str."%' ";

        }

        if(isset($_REQUEST['dis']) && $_REQUEST['dis'] != ""){
            $str = strtoupper($req['dis']);
            $condicion .= "AND D.Codigo LIKE '%".$str."%' ";
        }

        if(isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != ""){
            $str = strtoupper($req['tipo']);
            $condicion .= "AND D.Tipo_Entrega LIKE '%".$str."%' ";
        }

        if(isset($_REQUEST['auto']) && $_REQUEST['auto'] != ""){
            $str = strtoupper($req['auto']);
            $condicion .= "AND PD.numeroAutorizacion LIKE '%".$str."%' ";
        }
       
        return $condicion;
    }
   