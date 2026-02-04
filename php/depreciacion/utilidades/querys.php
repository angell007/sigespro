<?php

function getTiposActivos($mes,$year) {
    $mes = mesFormat($mes);
    $year = $year;
    //$fecha = date('Y') . '-'. $mes;
    $fecha = $year. '-'. $mes;
    $query = "SELECT Id_Tipo_Activo_Fijo AS ID, 
    Nombre_Tipo_Activo AS Nombre, 
    Vida_Util_PCGA, 
    Vida_Util AS Vida_Util_NIIF, 
    Sin_Depreciacion_Niff,
    Porcentaje_Depreciacion_Anual_PCGA AS Porcentaje_PCGA, 
    Porcentaje_Depreciacion_Anual AS Porcentaje_NIIF,
    Id_Plan_Cuenta_Depreciacion_PCGA AS Id_Plan_Cuenta_Depreciacion, 
    Id_Plan_Cuenta_Credito_Depreciacion_PCGA AS Id_Plan_Cuenta_Credito_Depreciacion 
    FROM Tipo_Activo_Fijo TAF 
    WHERE EXISTS 
                (SELECT Id_Activo_Fijo 
                 FROM Activo_Fijo 
                 WHERE Id_Tipo_Activo_Fijo = TAF.Id_Tipo_Activo_Fijo 
                 AND DATE_FORMAT(Fecha, '%Y-%m') < '$fecha' AND Estado!='Anulada' 
                 )";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    unset($oCon);

    return $resultado;
}

function activosFijosDepreciar($id_tipo_activo, $vida_util, $tipo_reporte,$mes,$year,$guardar) {
    $mes_dep = mesFormat($mes);
    //$fecha = date('Y') . '-'. $mes_dep;
    $fecha = $year . '-'. $mes_dep;
    $fecha_adificion = $fecha.'-01';
    
    //$fecha_anterior = $mes != 1 ? date('Y') . '-' . (mesFormat($mes-1)) : strval((intval(date('Y'))-1)) . '-12';
    $fecha_anterior = $mes != 1 ? $year . '-' . (mesFormat($mes-1)) : ($year-1) . '-12';
   
    
  


    $query = "
    SELECT AF.Id_Activo_Fijo AS ID, AF.Nombre, DATE(AF.Fecha) AS Fecha,
    (AF.Costo_PCGA + COALESCE( A.Adiciones_PCGA ,0) ) AS Costo_PCGA ,
     (AF.Costo_NIIF + COALESCE( A.Adiciones_NIIF ,0) ) AS Costo_NIIF ,
    AF.Tipo_Depreciacion, $vida_util AS Vida_Util, R.Vida_Util_Acum, R.Depreciacion_Acum_$tipo_reporte
    FROM Activo_Fijo AF
    LEFT JOIN 
    (SELECT
        r.Id_Activo_Fijo,
        SUM(r.Vida_Util_Acum) AS Vida_Util_Acum,
        SUM(r.Depreciacion_Acum_$tipo_reporte) AS Depreciacion_Acum_$tipo_reporte
        FROM
        (
        (SELECT Id_Activo_Fijo, ($vida_util-Vida_Util_Restante_$tipo_reporte) AS Vida_Util_Acum ,
        IFNULL(SUM(Depreciacion_Acum_$tipo_reporte),0) AS Depreciacion_Acum_$tipo_reporte 
        FROM Balance_Inicial_Activo_Fijo
        GROUP BY  Id_Activo_Fijo
        )
       
        UNION ALL( SELECT Id_Activo_Fijo,   SUM(IF(AFD.Valor_$tipo_reporte>0,1,0)) AS Vida_Util_Acum, IFNULL(SUM(AFD.Valor_$tipo_reporte),0) AS Depreciacion_Acum_$tipo_reporte 
        FROM Activo_Fijo_Depreciacion AFD 
       
        INNER JOIN Depreciacion D ON AFD.Id_Depreciacion = D.Id_Depreciacion WHERE D.Estado = 'Activo' GROUP BY AFD.Id_Activo_Fijo )
       
        UNION ALL(SELECT Id_Activo_Fijo, 0 AS Vida_Util_Acum, 0 AS Depreciacion_Acum_$tipo_reporte 
        FROM Activo_Fijo WHERE DATE_FORMAT(Fecha, '%Y-%m') = '$fecha_anterior' AND Estado != 'Anulada') 
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
        
        "
        
        ;
        
       // if($guardar==true){
        //$query .= " AND ( ( AF.Tipo_Depreciacion =1 AND Costo_PCGA < R.Depreciacion_Acum_$tipo_reporte) OR AF.Tipo_Depreciacion !=1 )";
            
        //}
        # AND ( ( AF.Tipo_Depreciacion =1 AND Vida_Util_Acum = 0) OR AF.Tipo_Depreciacion !=1 )
 

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    unset($oCon);

    return $resultado;
}

function activoDepreciado($mes,$anio, $id_activo,$tipo_reporte) {

 //   $mes_dep = $mes != 1 ? ($mes-1) : 12;
 $mes_dep = $mes ;
 
   // $anio = $mes != 1 ? $anio : (intval(date('Y'))-1);
    
    $query = "SELECT AFD.Id_Activo_Fijo_Depreciacion,  AFD.Valor_$tipo_reporte AS Valor_Depreciacion
    FROM Activo_Fijo_Depreciacion AFD
    INNER JOIN Depreciacion D ON D.Id_Depreciacion = AFD.Id_Depreciacion WHERE D.Anio = $anio AND D.Mes = $mes_dep AND AFD.Id_Activo_Fijo = $id_activo AND D.Estado = 'Activo'";
    /*if($id_activo == 1){       
               
                    var_dump($mesReporte);
                    //  $mes_compra = date('n', strtotime($fecha_compra));
                        //   var_dump($fecha_compra);
                             var_dump($query);
                          
                
                    }*/

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    if ($resultado) {
        return $resultado;
    }

    return false;
}

function getTotalAnio($id_activo, $tipo_reporte,$year) {
    //$anio = date('Y');
    $anio = $year;
    
    $query = "SELECT IFNULL(SUM(AFD.Valor_$tipo_reporte),0) AS Total FROM Activo_Fijo_Depreciacion AFD INNER JOIN Depreciacion D ON D.Id_Depreciacion = AFD.Id_Depreciacion WHERE D.Anio = $anio AND AFD.Id_Activo_Fijo = $id_activo AND D.Estado = 'Activo'";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    return $resultado['Total'];
}

function getDepreciacionAcum($tipo_reporte, $id_activo) {
    $query = "SELECT
    SUM(r.Depreciacion_Acum_$tipo_reporte) AS Depreciacion_Acum_$tipo_reporte
    FROM
    (
    (SELECT 
        IFNULL(SUM(Depreciacion_Acum_$tipo_reporte),0) AS Depreciacion_Acum_$tipo_reporte 
        FROM Balance_Inicial_Activo_Fijo 
        WHERE Id_Activo_Fijo = $id_activo)
    UNION ALL
        ( SELECT IFNULL(SUM(AFD.Valor_$tipo_reporte),0) AS Depreciacion_Acum_$tipo_reporte 
        FROM Activo_Fijo_Depreciacion AFD INNER JOIN Depreciacion D ON AFD.Id_Depreciacion = D.Id_Depreciacion 
        WHERE  AFD.Id_Activo_Fijo = $id_activo AND D.Estado = 'Activo')
    ) r";
  
    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    return $resultado['Depreciacion_Acum_'.$tipo_reporte];
}

function mesFormat($mes) {
    $mes = $mes > 9 ? $mes : '0'.$mes; // Para que me dé el formato 01,02,03...

    return $mes;
}

?>