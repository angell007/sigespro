<?php 
    header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

    require_once '../../class/class.consulta.php';
    require_once '../depreciacion/utilidades/querys.php';
    
    require_once '../depreciacion/utilidades/funciones.php';
    $fecha  = date('Y-m-d');
    $fecha  = explode("-",$fecha);
    
    $mes = $fecha[1];
    $year = $fecha[0];
    $guardar = false;

    $tipos_activos =  getTiposActivos($mes,$year);
   
    #var_dump($tipos_activos);
    $tipo = 'PCGA';
    $activos = [];
    foreach ($tipos_activos as $i => $tipo_act) {
        $activos_fijos = activosFijosDepreciarTodos($tipo_act['ID'], $tipo_act['Vida_Util_'.$tipo], $tipo,$mes,$year,$guardar);
      
        if (count($activos_fijos) > 0) {
            #$tipos_activos[$i]['activos_fijos'] = $activos_fijos;
            foreach ($activos_fijos as $key => $act) {
                # code...
                if ( $act['Costo_PCGA'] > $act['Depreciacion_Acum_PCGA']) {
                    # code...
                    $act['Cantidad'] = 1 ;
                    $act['Impuesto'] = 0 ;
                    array_push($activos, $act);
              }   
            }

        } else {
            unset($tipos_activos[$i]);
        }
    
    }



    
    echo json_encode($activos);
  

function activosFijosDepreciarTodos($id_tipo_activo, $vida_util, $tipo_reporte,$mes,$year,$guardar) {
    $mes_dep = mesFormat($mes);
    //$fecha = date('Y') . '-'. $mes_dep;
    $fecha = $year . '-'. $mes_dep;
    $fecha_adificion = $fecha.'-01';
    
    //$fecha_anterior = $mes != 1 ? date('Y') . '-' . (mesFormat($mes-1)) : strval((intval(date('Y'))-1)) . '-12';
    $fecha_anterior = $mes != 1 ? $year . '-' . (mesFormat($mes-1)) : ($year-1) . '-12';
   
    
  


    $query = "SELECT AF.Id_Activo_Fijo AS ID, AF.Nombre, DATE(AF.Fecha) AS Fecha,
    (AF.Costo_PCGA + COALESCE( A.Adiciones_PCGA ,0) ) AS Costo_PCGA ,
     (AF.Costo_NIIF + COALESCE( A.Adiciones_NIIF ,0) ) AS Costo_NIIF ,
    AF.Tipo_Depreciacion, $vida_util AS Vida_Util,
     R.Vida_Util_Acum,
     R.Depreciacion_Acum_PCGA,
    R.Depreciacion_Acum_NIIF
    FROM Activo_Fijo AF
    LEFT JOIN 
    (SELECT
        r.Id_Activo_Fijo,
        SUM(r.Vida_Util_Acum) AS Vida_Util_Acum,
        SUM(r.Depreciacion_Acum_PCGA) AS Depreciacion_Acum_PCGA,
        SUM(r.Depreciacion_Acum_NIIF) AS Depreciacion_Acum_NIIF
        FROM
        (
            (
                SELECT Id_Activo_Fijo,
                 ($vida_util-Vida_Util_Restante_$tipo_reporte) AS Vida_Util_Acum ,
                IFNULL(SUM(Depreciacion_Acum_PCGA),0) AS Depreciacion_Acum_PCGA,
                IFNULL(SUM(Depreciacion_Acum_NIIF),0) AS Depreciacion_Acum_NIIF
                FROM Balance_Inicial_Activo_Fijo
                GROUP BY  Id_Activo_Fijo
            )
        
            UNION ALL( 
                SELECT Id_Activo_Fijo, 
                  SUM(IF(AFD.Valor_$tipo_reporte>0,1,0)) AS Vida_Util_Acum,
                IFNULL(SUM(AFD.Valor_PCGA),0) AS Depreciacion_Acum_PCGA ,
                IFNULL(SUM(AFD.Valor_NIIF),0) AS Depreciacion_Acum_NIIF 
                
                FROM Activo_Fijo_Depreciacion AFD 
            
                INNER JOIN Depreciacion D ON AFD.Id_Depreciacion = D.Id_Depreciacion WHERE D.Estado = 'Activo' GROUP BY AFD.Id_Activo_Fijo 
            )
        
            UNION ALL(
                SELECT Id_Activo_Fijo, 
                0 AS Vida_Util_Acum,              
               0 AS Depreciacion_Acum_PCGA ,
               0 AS Depreciacion_Acum_NIIF 

                FROM Activo_Fijo WHERE DATE_FORMAT(Fecha, '%Y-%m') = '$fecha_anterior' AND Estado != 'Anulada'
            ) 
        ) r
        GROUP BY r.Id_Activo_Fijo) R  ON AF.Id_Activo_Fijo = R.Id_Activo_Fijo 
    LEFT JOIN(
        
           SELECT  SUM(A.Costo_NIIF) AS Adiciones_NIIF,  SUM(A.Costo_PCGA) AS Adiciones_PCGA, A.Id_Activo_Fijo
                                 FROM Adicion_Activo_Fijo A
                                WHERE DATE(A.Fecha) < '".$fecha_adificion."'
                               /* AND A.Id_Activo_Fijo =  AF.Id_Activo_Fijo*/
                                GROUP BY A.Id_Activo_Fijo
                                
     )A ON A.Id_Activo_Fijo =  AF.Id_Activo_Fijo
        
        WHERE AF.Id_Tipo_Activo_Fijo = $id_tipo_activo
        AND DATE_FORMAT(AF.Fecha, '%Y-%m') < '$fecha' AND R.Vida_Util_Acum <= $vida_util  AND AF.Estado='Activo'
        
        ";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    unset($oCon);

    return $resultado;
}
