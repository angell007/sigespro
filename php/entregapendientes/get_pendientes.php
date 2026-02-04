<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.consulta.php');

	$http_response = new HttpResponse();

	$pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
	$tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

    $condicion = SetCondiciones($_REQUEST);

    $query = "SELECT PD.*,
    GROUP_CONCAT(DISTINCT PD.Id_Producto_Dispensacion ) as Descarga,
    GROUP_CONCAT(DISTINCT PD.Id_Producto) as Producto, 
    CONCAT_WS(' ',PA.Primer_Nombre,PA.Segundo_Nombre,PA.Primer_Apellido,PA.Segundo_Apellido)  as Paciente, 
    D.Codigo as DIS,
    PA.Id_Paciente,
    ' ' as Pendientes, 
    'Guardar' as tipo,
    D.Id_Dispensacion_Mipres,
    '' as Id_Reclamante,
    '' as Nombre, 
    '' as Id_Tipo_Documento
      
    FROM Producto_Dispensacion PD
    -- INNER JOIN Producto_Descarga_Pendiente_Remision PD  ON DP.Id_Descarga_Pendiente_Remision=PD.Id_Descarga_Pendiente_Remision 
    INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto   
    INNER JOIN Dispensacion D ON PD.Id_Dispensacion=D.Id_Dispensacion AND D.Estado_Dispensacion !='Anulada'
    INNER JOIN Paciente PA ON D.Numero_Documento=PA.Id_Paciente
    -- INNER JOIN Remision R ON PD.Id_Remision=R.Id_Remision 
    WHERE (PD.Cantidad_Formulada != PD.Cantidad_Entregada) 
    --     AND PD.Cantidad_Entregada!=0
    AND D.Fecha_Actual>='2021-01-01 00:00:00'
    $condicion GROUP BY PD.Id_Dispensacion
    ORDER BY Paciente
      ";


$query_count = "SELECT COUNT(*) AS Total FROM (
    SELECT PD.Id_Producto_Dispensacion 
    FROM Producto_Dispensacion PD
    -- INNER JOIN Producto_Descarga_Pendiente_Remision PD  ON DP.Id_Descarga_Pendiente_Remision=PD.Id_Descarga_Pendiente_Remision 
    INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto   
    INNER JOIN Dispensacion D ON PD.Id_Dispensacion=D.Id_Dispensacion AND D.Estado_Dispensacion !='Anulada'
    INNER JOIN Paciente PA ON D.Numero_Documento=PA.Id_Paciente
    -- INNER JOIN Remision R ON PD.Id_Remision=R.Id_Remision 
    WHERE (PD.Cantidad_Formulada != PD.Cantidad_Entregada) 
    --     AND PD.Cantidad_Entregada!=0
    AND D.Fecha_Actual>='2021-01-01 00:00:00'
    $condicion GROUP BY PD.Id_Dispensacion ) PD ";    

$paginationData = new PaginacionData($tam, $query_count, $pag);
// echo ($query_count);exit;
//Se crea la instancia que contiene la consulta a realizar
$queryObj = new QueryBaseDatos($query);

//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
$auditorias = $queryObj->Consultar('Multiple', true, $paginationData);
// unset($auditorias['query_result'] );
// echo json_encode($auditorias); exit;
foreach ($auditorias['query_result'] as $key => $value) {
    $query=
    "SELECT 
        IFNULL(CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Unidad_Medida),
            CONCAT(P.Nombre_Comercial,' ',P.Laboratorio_Comercial)) as Nombre_Producto,
            P.Nombre_Comercial, (PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad,  PD.Lote 
        FROM Producto_Dispensacion PD 
        INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto  
        WHERE PD.Id_Producto_Dispensacion IN ($value[Descarga])
        " ;

    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $productos = $oCon->getData();
    unset($oCon);
    $auditorias['query_result'][$key]['Productos']=$productos;
}

echo json_encode($auditorias);
function SetCondiciones($req){
    $condicion = '';
    if (isset($_REQUEST['Id_Pac']) && $_REQUEST['Id_Pac'] != "") {       
            $condicion .= " AND  D.Numero_Documento=".$_REQUEST['Id_Pac']."";
    }
    if (isset($_REQUEST['dis']) && $_REQUEST['dis'] != "") {        
            $condicion .= " AND  D.Codigo='".$_REQUEST['dis']."'";
    
    }
    if (isset($_REQUEST['punto']) && $_REQUEST['punto'] != "") {        
            // $condicion .= " -- AND  DP.Id_Punto_Dispensacion=".$_REQUEST['punto']."";
    
    }
    if (isset($_REQUEST['Pac']) && $_REQUEST['Pac'] != "") {        
        $nom=str_replace( ' ', '%', $_REQUEST['Pac']);
        $condicion .= " AND  CONCAT_WS(' ',PA.Primer_Nombre,PA.Segundo_Nombre,PA.Primer_Apellido,PA.Segundo_Apellido )LIKE '%".$nom."%'";
    
    }


    return $condicion;
}
?>