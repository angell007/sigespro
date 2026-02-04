<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	// include_once('../../class/class.http_response.php');

	// $http_response = new HttpResponse();
    $queryObj = new QueryBaseDatos();

    
	$pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
	$tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

    $condicion = SetCondiciones($_REQUEST);

    $query = "SELECT PD.id,PD.numeroAutorizacion, 
                         PD.fechaHoraAutorizacion,
                         PD.AFnumeroDocumento,
                         PD.RLnumeroSolicitudSiniestro,
                         PD.serviciosAutorizados,
                         CONCAT(PD.AFprimerNombre, ' ', PD.AFprimerApellido) as Paciente,
                         PD.AFdepartamento,
                         PD.AFmunicipio,
                         D.Codigo,
                         D.Id_Dispensacion,
                         PD.fechaVencimiento,
                         ifnull(D.Tipo_Entrega, if(PD.Pdomicilio='1', 'Domicilio','Fisico'))  as Tipo_Entrega,
                         PD.Detalle_Estado,
                         PD.Estado, 
                         IF(PD.Estado ='Anulada', NULL, PD.tieneTutela) as Tiene_Tutela,
                         IF(PD.Estado ='Anulada' || PD.tieneTutela, NULL, PD.RLmarcaEmpleador) as Platino,
                         PD.tieneTutela as Tutela
                         FROM Positiva_Data PD
                         LEFT JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion
                         WHERE ( D.Pendientes > 0 OR D.Pendientes IS NULL OR PD.Id_Dispensacion IS NULL OR PD.Id_Dispensacion = ' ' )
                         $condicion ORDER BY Tiene_Tutela DESC, Platino DESC, PD.id DESC";

    $query_count='SELECT COUNT(id) AS Total
                 FROM Positiva_Data PD
                 LEFT JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion
                  WHERE ( D.Pendientes > 0 OR D.Pendientes IS NULL OR PD.Id_Dispensacion IS NULL OR PD.Id_Dispensacion = " " )
                 '.$condicion.' ';

// echo $query; exit;
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
            // $condicion .= "AND PD.AFnumeroDocumento LIKE '%".$str."%' ";
            $condicion .= "AND (PD.AFnumeroDocumento LIKE '%".$str."%'  OR PD.AFprimerNombre LIKE '%".$str."%' )";
        }

        if(isset($_REQUEST['auto']) && $_REQUEST['auto'] != ""){
            $str = strtoupper($req['auto']);
            $condicion .= "AND PD.numeroAutorizacion LIKE '%".$str."%' ";
        }
        if(isset($_REQUEST['soli']) && $_REQUEST['soli'] != ""){
            $str = strtoupper($req['soli']);
            $condicion .= "AND PD.RLnumeroSolicitudSiniestro LIKE '%".$str."%' ";
        }

        if(isset($_REQUEST['dis']) && $_REQUEST['dis'] != ""){
            $str = strtoupper($req['dis']);
            $condicion .= "AND D.Codigo LIKE '%".$str."%' ";
        }

        if(isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != ""){
            $str = strtoupper($req['tipo']);
            $condicion .= "AND D.Tipo_Entrega LIKE '%".$str."%' ";
        }
        if(isset($_REQUEST['est']) && $_REQUEST['est'] != ""){
            $str = strtoupper($req['est']);
            $condicion .= "AND PD.Estado LIKE '%".$str."%' ";
        }

        if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
            $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
            $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
            
            $condicion .= "AND  DATE_FORMAT(PD.fechaVencimiento,'%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin'";
        }else{
            $fecha_fin=date('Y-m-d');
            $fecha_inicio=date('Y-m-d');
        }
       
        return $condicion;
    }
   