<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta_paginada.php');

$condicion = '';
$condicion2 = '';
$buscarActaRecepcionRemision = true; //validar si no hay campos de busqueda únicos de Acta Recepcion 
//el estado de la acta recepcion (aprobada-acomodada)

$id_funcionario = isset($_REQUEST['id_funcionario']) ? $_REQUEST['id_funcionario']  : '' ;
$tipo = isset($_REQUEST['tipo']) ? $_REQUEST['tipo']  : '' ;
$id_punto = isset($_REQUEST['id_punto']) ? $_REQUEST['id_punto']  : '' ;

/* print_r($id_punto);exit; */
#si existe tipo = general no tomar en cuenta condicion de bodegas asociadas al funcionario
$enbodega = '';
if ( $tipo != "General" )  {
  //buscar bodegas asociadas al  funcionario
  /*   $query = 'SELECT GROUP_CONCAT(Id_Bodega_Nuevo) AS Id_Bodega_Nuevo FROM Funcionario_Bodega_Nuevo WHERE Identificacion_Funcionario = '.$id_funcionario;
    $oCon = new consulta(); 
    $oCon->setQuery($query);

    $bodegas_funcionario = $oCon->getData();*/
    $bodegas_funcionario = $id_punto;
    unset($oCon);

   $enbodega = ' AND  ( AR.Id_Punto_Dispensacion IN('.$bodegas_funcionario.') ) '; 	
}



if (isset($_REQUEST['estado']) && $_REQUEST['estado'] != "") {
    $estado = $_REQUEST['estado'];
  
  	if($estado == 'Acomodada'){
      $estadoCond = ' (AR.Estado ="'.$estado.'" OR AR.Fecha_Creacion <"2021-05-11) ';
    }else if($estado == 'Aprobada'){
      $estadoCond =' AR.Estado ="'.$estado.'" AND AR.Fecha_Creacion > "2021-05-11" '  ;
    }

}

if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
    $condicion .= " AND AR.Codigo LIKE '%$_REQUEST[cod]%'";
   
}

if (isset($_REQUEST['compra']) && $_REQUEST['compra'] != "") {
    $condicion .= " AND (AR.Codigo_Compra_N LIKE '%$_REQUEST[compra]%')";
    $buscarActaRecepcionRemision = false;
}

if (isset($_REQUEST['proveedor']) && $_REQUEST['proveedor'] != "") {
    $condicion .= " AND AR.proveedor LIKE '%$_REQUEST[proveedor]%'";
    $buscarActaRecepcionRemision = false;

}

if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
    $condicion .= " AND DATE_FORMAT(AR.Fecha_Creacion, '%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}

if (isset($_REQUEST['fecha2']) && $_REQUEST['fecha2'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha2'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha2'])[1]);
    $condicion .= " AND (( DATE(AR.Fecha_Compra_N) BETWEEN '$fecha_inicio' AND '$fecha_fin'))";

    $buscarActaRecepcionRemision = false;
}

if (isset($_REQUEST['fact']) && $_REQUEST['fact'] != "") {
    $condicion .= " AND AR.Facturas LIKE '%$_REQUEST[fact]%'";
    $buscarActaRecepcionRemision = false;
}





####### PAGINACIÓN ######## 
$tamPag = 10; 
$numReg = $total['Total']; 
$paginas = ceil($numReg/$tamPag); 
$limit = ""; 
$paginaAct = "";

if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') { 
    $paginaAct = 1; 
    $limit = 0; 
} else { 
    $paginaAct = $_REQUEST['pag']; 
    $limit = ($paginaAct-1) * $tamPag; 
}
        

          
/* $actarecepcion['numReg'] = $numReg; */
if ($Actas_Recepcion_Remision) {
    # code...
    $actarecepcion['actarecepciones']=array_merge($actarecepcion['actarecepciones'],$Actas_Recepcion_Remision,$Ajuste_Individual);
}


$query = 'SELECT  SQL_CALC_FOUND_ROWS AR.* FROM (
    #ACTAS DE RECEPCION
    SELECT 
    ARC.Id_Acta_Recepcion AS Id_Acta, ARC.Codigo, ARC.Estado,  
    ARC.Fecha_Creacion,ARC.Tipo_Acta,  F.Imagen,
    COALESCE(B.Nombre) as Bodega,  COALESCE(B.Id_Punto_Dispensacion) AS Id_Bodega_Nuevo , 
    B.Id_Punto_Dispensacion,
     OCN.Codigo as Codigo_Compra_N,  P.Nombre as Proveedor,
    NULL Codigo_Remision, OCN.Fecha as Fecha_Compra_N, 
      (
        CASE  
            WHEN ARC.Tipo = "Nacional" THEN ARC.Id_Orden_Compra_Nacional
            ELSE ARC.Id_Orden_Compra_Internacional
         END
       ) AS Id_Orden_Compra,
       ( SELECT GROUP_CONCAT(Factura) FROM Factura_Acta_Recepcion WHERE Id_Acta_Recepcion = ARC.Id_Acta_Recepcion ) as Facturas,
        ARC.Tipo , "Acta_Recepcion" Tipo_Acomodar 
    FROM Acta_Recepcion ARC 
    LEFT JOIN Funcionario F
    ON F.Identificacion_Funcionario = ARC.Identificacion_Funcionario
    LEFT JOIN Orden_Compra_Nacional OCN
    ON OCN.Id_Orden_Compra_Nacional = ARC.Id_Orden_Compra_Nacional
    LEFT JOIN Punto_Dispensacion B
    ON B.Id_Punto_Dispensacion = ARC.Id_Punto_Dispensacion
   
    INNER JOIN Proveedor P
    ON P.Id_Proveedor = ARC.Id_Proveedor
    
    UNION ALL
    #ACTA DE RECEPCION REMISION
    SELECT ARC.Id_Acta_Recepcion_Remision AS Id_Acta , 
          ARC.Codigo,  
          ARC.Estado , 
          ARC.Fecha AS Fecha_Creacion, 
          "INTERNA" as Tipo_Acta ,     
          F.Imagen,
          B.Nombre as Bodega, 
          COALESCE(ARC.Id_Punto_Dispensacion) AS Id_Bodega_Nuevo , 
          ARC.Id_Punto_Dispensacion,
          "INTERNA"  as Codigo_Compra_N, 
          "INTERNA" Proveedor,
          R.Codigo as Codigo_Remision, 
          "INTERNA" Fecha_Compra_N,
          NULL AS Id_Orden_Compra,
          NULL AS Facturas,
          "INTERNA" as Tipo , 
          "Acta_Recepcion_Remision" Tipo_Acomodar 
    FROM Acta_Recepcion_Remision ARC 
    INNER JOIN Funcionario F
    ON F.Identificacion_Funcionario = ARC.Identificacion_Funcionario
    INNER JOIN Remision R
    ON ARC.Id_Remision=R.Id_Remision
    LEFT JOIN Punto_Dispensacion B
    ON B.Id_Punto_Dispensacion = ARC.Id_Punto_Dispensacion
   WHERE  ARC.Id_Punto_Dispensacion IS NOT NULL
   
   
    
    UNION ALL
    #AJUSTE INDIVIDUAL
    SELECT AI.Id_Ajuste_Individual AS Id_Acta, 
           AI.Codigo, 
           AI.Estado_Entrada_Bodega AS Estado,
           AI.Fecha AS "Fecha_Creacion", 
           "INTERNA" AS Tipo_Acta , 
           F.Imagen,
           NULL as Bodega,  
           null as Id_Bodega_Nuevo,
           AI.Id_Origen_Destino AS Id_Punto_Dispensacion ,
           "INTERNA" Codigo_Compra_N,
           "INTERNA" Proveedor, 
           "AJUSTE INDIVIDUAL" as Codigo_Remision, 
           "INTERNA" Fecha_Compra_N ,
           NULL AS Id_Orden_Compra,
           "INTERNA" Facturas,
           "INTERNA" as Tipo , 
           "Ajuste_Individual" Tipo_Acomodar 
    FROM Ajuste_Individual AI 
    INNER JOIN Funcionario F
    ON F.Identificacion_Funcionario = AI.Identificacion_Funcionario
    WHERE AI.Tipo="Entrada" AND AI.Estado!="Anulada" AND ( AI.Origen_Destino = "Punto"  )
    AND AI.Estado_Entrada_Bodega = "'.$estado.'" AND AI.Id_Origen_Destino IS NOT NULL 

    /* UNION ALL
    #AJUSTE INDIVIDUAL
    SELECT AI.Id_Ajuste_Individual AS Id_Acta  , AI.Codigo, AI.Estado_Entrada_Bodega AS Estado,
    AI.Fecha AS "Fecha_Creacion", "INTERNA" AS Tipo_Acta , F.Imagen,
    NULL as Bodega,  AI.Id_Origen_Destino AS Id_Bodega_Nuevo ,"INTERNA" Codigo_Compra_N,"INTERNA" Proveedor, 
    "AJUSTE INDIVIDUAL" as Codigo_Remision, "INTERNA" Fecha_Compra_N ,
    NULL AS Id_Orden_Compra,
    "INTERNA" Facturas,
    "INTERNA" as Tipo , "Ajuste_Individual" Tipo_Acomodar 
    FROM Ajuste_Individual AI 
    INNER JOIN Funcionario F
    ON F.Identificacion_Funcionario = AI.Identificacion_Funcionario
    WHERE AI.Tipo="Entrada" AND AI.Estado!="Anulada" AND ( AI.Origen_Destino = "Bodega"  OR AI.Origen_Destino = "INTERNA"  )
    AND AI.Estado_Entrada_Bodega = "'.$estado.'" AND AI.Id_Origen_Destino IS NOT NULL  */

   /* UNION ALL
    SELECT NC.Id_Nota_Credito AS Id_Acta  , NC.Codigo, NC.Estado,
    NC.Fecha AS "Fecha_Creacion", "INTERNA" AS Tipo_Acta , F.Imagen,
    NULL as Bodega, NC.Id_Bodega_Nuevo, "INTERNA" Codigo_Compra_N,"INTERNA" Proveedor, 
    "AJUSTE INDIVIDUAL" as Codigo_Remision, "INTERNA" Fecha_Compra_N ,
    NULL AS Id_Orden_Compra,
    "INTERNA" Facturas,
    "INTERNA" as Tipo , "Nota_Credito" Tipo_Acomodar 
    FROM Nota_Credito NC 
    INNER JOIN Funcionario F
    ON F.Identificacion_Funcionario = NC.Identificacion_Funcionario
    WHERE NC.Estado = "'.$estado.'" AND NC.Id_Bodega_Nuevo IS NOT NULL  */
    
    # ACTA INTERNACIONAL 
     /*UNION ALL
    SELECT PAI.Id_Nacionalizacion_Parcial AS Id_Acta  , PAI.Codigo, "Aprobada" AS Estado,
    PAI.Fecha_Registro AS "Fecha_Creacion", "INTERNA" AS Tipo_Acta , F.Imagen,
    NULL as Bodega, ACI.Id_Bodega_Nuevo, "INTERNA" Codigo_Compra_N,"INTERNA" Proveedor, 
    "AJUSTE INDIVIDUAL" as Codigo_Remision, "INTERNA" Fecha_Compra_N ,
    NULL AS Id_Orden_Compra,
    "INTERNA" Facturas,
    "INTERNA" as Tipo , "Nacionalizacion_Parcial" Tipo_Acomodar 
    FROM Nacionalizacion_Parcial PAI 
    INNER JOIN Funcionario F
    ON F.Identificacion_Funcionario = PAI.Identificacion_Funcionario
    INNER JOIN Acta_Recepcion_Internacional ACI ON ACI.Id_Acta_Recepcion_Internacional =  PAI.Id_Acta_Recepcion_Internacional
    WHERE PAI.Estado = "'. ($estado == "Aprobada" ? "Nacionalizado" : "Acomodada" ).'" AND ACI.Id_Bodega_Nuevo IS NOT NULL */

    ) AR


    WHERE  '.$estadoCond.'
        AND (AR.Tipo_Acta = "Punto_Dispensacion"  OR AR.Tipo_Acta = "INTERNA"  )
       '.$enbodega.'
    '.$condicion.' ORDER BY Fecha_Creacion DESC, Codigo DESC LIMIT '.$limit.','.$tamPag;
       
  

  $oCon= new consulta();
  $oCon->setTipo('Multiple');
  $oCon->setQuery($query);
  $res = $oCon->getData();
  $actarecepcion['actarecepciones'] = $res['data'];
  unset($oCon);
  
     

  $actarecepcion['numReg'] = $res['total'];




echo json_encode($actarecepcion);



function bodega_funcionario($id_funcionario){
    $query_bodegas_funcionario = 'SELECT GROUP_CONCAT(Id_Bodega_Nuevo) AS Id_Bodega_Nuevo
                                FROM Funcionario_Bodega_Nuevo FB
                                WHERE FB.Identificacion_Funcionario ='.$id_funcionario;
    $oCon= new consulta();
    $oCon->setQuery($query_bodegas_funcionario);
    $bodegas = $oCon->getData();
    unset($oCon); 

    return $bodegas;
          
}
?>