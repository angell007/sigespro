<?php

function ingresosOperacionales($condicion, $condicionFecha, $idCentroCosto = null) {
    $campo_codigo = $_REQUEST['Tipo'] && $_REQUEST['Tipo'] == 'Pcga' ? 'Codigo' : 'Codigo_Niif';
    $condicionCentroCosto = '';
    if($idCentroCosto){
        $condicionCentroCosto = ' AND MC.Id_Centro_Costo = '.$idCentroCosto; 
    }
    $query = "SELECT
    PC.Id_Plan_Cuentas,
    PC.Codigo,
    PC.Nombre,
    PC.Codigo_Niif,
    PC.Nombre_Niif,
    PC.Naturaleza,
    (
        CASE
            WHEN LOCATE('4135',PC.$campo_codigo) > 0 THEN 'Ingreso'
            WHEN LOCATE('4175',PC.$campo_codigo) > 0 THEN 'Devolucion'
            ELSE 'Unificados'
        END
    ) AS Tipo_Cta,
    (SELECT IFNULL(SUM(Debe),0) FROM Movimiento_Contable MC INNER JOIN 
        Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas WHERE
        MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%')
        AND PC2.$campo_codigo NOT LIKE '42%' AND PC2.$campo_codigo NOT LIKE '4165%'
        AND PC2.$campo_codigo NOT LIKE '4176%' AND PC2.$campo_codigo NOT LIKE '4177%'
        AND PC2.$campo_codigo NOT LIKE '4178%' AND PC2.$campo_codigo NOT LIKE '4179%' 
        AND PC2.$campo_codigo NOT LIKE '4180%' AND PC2.$campo_codigo NOT LIKE '4181%'
        AND PC2.$campo_codigo NOT LIKE '4182%' $condicionFecha
        $condicionCentroCosto ) AS Debe,

    (SELECT IFNULL(SUM(Haber),0) FROM Movimiento_Contable MC 
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%')
    AND PC2.$campo_codigo NOT LIKE '42%' AND PC2.$campo_codigo NOT LIKE '4165%' 
    AND PC2.$campo_codigo NOT LIKE '4176%' AND PC2.$campo_codigo NOT LIKE '4177%' 
    AND PC2.$campo_codigo NOT LIKE '4178%' AND PC2.$campo_codigo NOT LIKE '4179%' 
    AND PC2.$campo_codigo NOT LIKE '4180%' AND PC2.$campo_codigo NOT LIKE '4181%'
    AND PC2.$campo_codigo NOT LIKE '4182%' $condicionFecha
    $condicionCentroCosto
    ) AS Haber,

    (SELECT IFNULL(SUM(Debe_Niif),0) FROM Movimiento_Contable MC 
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas WHERE MC.Estado != 'Anulado'
    AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') AND PC2.$campo_codigo NOT LIKE '42%' AND
    PC2.$campo_codigo NOT LIKE '4165%' AND PC2.$campo_codigo NOT LIKE '4176%' AND
    PC2.$campo_codigo NOT LIKE '4177%' AND PC2.$campo_codigo NOT LIKE '4178%' AND
    PC2.$campo_codigo NOT LIKE '4179%' AND PC2.$campo_codigo NOT LIKE '4180%' AND 
    PC2.$campo_codigo NOT LIKE '4181%' AND PC2.$campo_codigo NOT LIKE '4182%' $condicionFecha
    $condicionCentroCosto
    ) AS Debe_NIIF,

    (SELECT IFNULL(SUM(Haber_Niif),0) FROM Movimiento_Contable MC 
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas WHERE MC.Estado != 'Anulado' 
    AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') AND PC2.$campo_codigo NOT LIKE '42%' 
    AND PC2.$campo_codigo NOT LIKE '4165%' AND PC2.$campo_codigo NOT LIKE '4176%'
    AND PC2.$campo_codigo NOT LIKE '4177%' AND PC2.$campo_codigo NOT LIKE '4178%' 
    AND PC2.$campo_codigo NOT LIKE '4179%' AND PC2.$campo_codigo NOT LIKE '4180%' 
    AND PC2.$campo_codigo NOT LIKE '4181%' AND PC2.$campo_codigo NOT LIKE '4182%' $condicionFecha 
    $condicionCentroCosto
    ) AS Haber_NIIF

    FROM
    Plan_Cuentas PC
    WHERE PC.$campo_codigo LIKE '4%' AND PC.$campo_codigo NOT LIKE '42%'
    AND PC.$campo_codigo NOT LIKE '4165%' AND PC.$campo_codigo NOT LIKE '4176%' AND 
    PC.$campo_codigo NOT LIKE '4177%' AND PC.$campo_codigo NOT LIKE '4178%' AND
    PC.$campo_codigo NOT LIKE '4179%' AND PC.$campo_codigo NOT LIKE '4180%' AND
    PC.$campo_codigo NOT LIKE '4181%' AND PC.$campo_codigo NOT LIKE '4182%'
    
    
    $condicion
    GROUP BY PC.$campo_codigo";

    return $query;
}

function costosVentas($condicion, $condicionFecha,$idCentroCosto = null ) {
    $campo_codigo = $_REQUEST['Tipo'] && $_REQUEST['Tipo'] == 'Pcga' ? 'Codigo' : 'Codigo_Niif';

    $condicionCentroCosto = $idCentroCosto ? ' AND MC.Id_Centro_Costo = '.$idCentroCosto : ''; 
    
    $query = "SELECT
    PC.Id_Plan_Cuentas,
    PC.Codigo,
    PC.Nombre,
    PC.Codigo_Niif,
    PC.Nombre_Niif,
    PC.Naturaleza,
    (SELECT IFNULL(SUM(Debe),0) FROM Movimiento_Contable MC 
        INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas
        WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') $condicionFecha
        $condicionCentroCosto
    ) AS Debe,

    (SELECT IFNULL(SUM(Haber),0) FROM Movimiento_Contable MC
        INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas
        WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') $condicionFecha
        $condicionCentroCosto
    ) AS Haber,

    (SELECT IFNULL(SUM(Debe_Niif),0) FROM Movimiento_Contable MC
        INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas
        WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') $condicionFecha
        $condicionCentroCosto
    ) AS Debe_NIIF,

    (SELECT IFNULL(SUM(Haber_Niif),0) FROM Movimiento_Contable MC 
        INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas
        WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') $condicionFecha
        $condicionCentroCosto
    ) AS Haber_NIIF

    FROM
    Plan_Cuentas PC
    WHERE (PC.$campo_codigo LIKE '6%' OR PC.$campo_codigo LIKE '7%')
    $condicion
    GROUP BY PC.$campo_codigo";
    
    return $query;
}

function gastosAdmin($condicion, $condicionFecha, $idCentroCosto=null) {
    $campo_codigo = $_REQUEST['Tipo'] && $_REQUEST['Tipo'] == 'Pcga' ? 'Codigo' : 'Codigo_Niif';
    $condicionCentroCosto = $idCentroCosto ? ' AND MC.Id_Centro_Costo = '.$idCentroCosto : ''; 
    $query = "SELECT
    PC.Id_Plan_Cuentas,
    PC.Codigo,
    PC.Nombre,
    PC.Codigo_Niif,
    PC.Nombre_Niif,
    PC.Naturaleza,
    (SELECT IFNULL(SUM(Debe),0) FROM Movimiento_Contable MC
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') 
    AND PC2.$campo_codigo NOT LIKE '52%' AND PC2.$campo_codigo NOT LIKE '53%' 
    AND PC2.$campo_codigo NOT LIKE '54%' $condicionFecha
    $condicionCentroCosto
    ) AS Debe,

    (SELECT IFNULL(SUM(Haber),0) FROM Movimiento_Contable MC
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') 
    AND PC2.$campo_codigo NOT LIKE '52%' AND PC2.$campo_codigo NOT LIKE '53%'
    AND PC2.$campo_codigo NOT LIKE '54%' $condicionFecha
    $condicionCentroCosto
    ) AS Haber,

    (SELECT IFNULL(SUM(Debe_Niif),0) FROM Movimiento_Contable MC 
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') 
    AND PC2.$campo_codigo NOT LIKE '52%' AND PC2.$campo_codigo NOT LIKE '53%' 
    AND PC2.$campo_codigo NOT LIKE '54%' $condicionFecha
    $condicionCentroCosto
    ) AS Debe_NIIF,

    (SELECT IFNULL(SUM(Haber_Niif),0) FROM Movimiento_Contable MC 
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%')
    AND PC2.$campo_codigo NOT LIKE '52%' AND PC2.$campo_codigo NOT LIKE '53%'
    AND PC2.$campo_codigo NOT LIKE '54%' $condicionFecha
    $condicionCentroCosto
    ) AS Haber_NIIF

    FROM
    Plan_Cuentas PC
    WHERE PC.$campo_codigo LIKE '5%' AND PC.$campo_codigo NOT LIKE '52%' AND PC.$campo_codigo NOT LIKE '53%' AND PC.$campo_codigo NOT LIKE '54%'
    $condicion
    GROUP BY PC.$campo_codigo";
    
    return $query;
}

function gastosVentas($condicion, $condicionFecha, $idCentroCosto = null) {
    $campo_codigo = $_REQUEST['Tipo'] && $_REQUEST['Tipo'] == 'Pcga' ? 'Codigo' : 'Codigo_Niif';
    $condicionCentroCosto = $idCentroCosto ? ' AND MC.Id_Centro_Costo = '.$idCentroCosto : ''; 
    $query = "SELECT
    PC.Id_Plan_Cuentas,
    PC.Codigo,
    PC.Nombre,
    PC.Codigo_Niif,
    PC.Nombre_Niif,
    PC.Naturaleza,
    (SELECT IFNULL(SUM(Debe),0) FROM Movimiento_Contable MC
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') 
    AND PC2.$campo_codigo NOT LIKE '51%' AND PC2.$campo_codigo NOT LIKE '53%'
    AND PC2.$campo_codigo NOT LIKE '54%' $condicionFecha
    $condicionCentroCosto
    ) AS Debe,

    (SELECT IFNULL(SUM(Haber),0) FROM Movimiento_Contable MC
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') 
    AND PC2.$campo_codigo NOT LIKE '51%' AND PC2.$campo_codigo NOT LIKE '53%' 
    AND PC2.$campo_codigo NOT LIKE '54%' $condicionFecha
    $condicionCentroCosto
    ) AS Haber,

    (SELECT IFNULL(SUM(Debe_Niif),0) FROM Movimiento_Contable MC
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%')
    AND PC2.$campo_codigo NOT LIKE '51%' AND PC2.$campo_codigo NOT LIKE '53%'
    AND PC2.$campo_codigo NOT LIKE '54%' $condicionFecha
    $condicionCentroCosto
    ) AS Debe_NIIF,

    (SELECT IFNULL(SUM(Haber_Niif),0) FROM Movimiento_Contable MC 
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') 
    AND PC2.$campo_codigo NOT LIKE '51%' AND PC2.$campo_codigo NOT LIKE '53%'
    AND PC2.$campo_codigo NOT LIKE '54%' $condicionFecha
    $condicionCentroCosto
    ) AS Haber_NIIF

    FROM
    Plan_Cuentas PC
    WHERE PC.$campo_codigo LIKE '5%' AND PC.$campo_codigo NOT LIKE '51%' AND PC.$campo_codigo NOT LIKE '53%' AND PC.$campo_codigo NOT LIKE '54%'
    $condicion
    GROUP BY PC.$campo_codigo";

    return $query;
}

function ingresosNoOperacionales($condicion, $condicionFecha, $idCentroCosto = null ) {
    $campo_codigo = $_REQUEST['Tipo'] && $_REQUEST['Tipo'] == 'Pcga' ? 'Codigo' : 'Codigo_Niif';
    $condicionCentroCosto = $idCentroCosto ? ' AND MC.Id_Centro_Costo = '.$idCentroCosto : ''; 
    $query = "SELECT
    PC.Id_Plan_Cuentas,
    PC.Codigo,
    PC.Nombre,
    PC.Codigo_Niif,
    PC.Nombre_Niif,
    PC.Naturaleza,
    (SELECT IFNULL(SUM(Debe),0) FROM Movimiento_Contable MC 
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') 
    AND PC2.$campo_codigo NOT LIKE '41%' $condicionFecha
    $condicionCentroCosto
    ) AS Debe,

    (SELECT IFNULL(SUM(Haber),0) FROM Movimiento_Contable MC 
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') 
    AND PC2.$campo_codigo NOT LIKE '41%' $condicionFecha
    $condicionCentroCosto
    ) AS Haber,

    (SELECT IFNULL(SUM(Debe_Niif),0) FROM Movimiento_Contable MC 
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas 
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%')
    AND PC2.$campo_codigo NOT LIKE '41%' $condicionFecha
    $condicionCentroCosto
    ) AS Debe_NIIF,

    (SELECT IFNULL(SUM(Haber_Niif),0) FROM Movimiento_Contable MC
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') 
    AND PC2.$campo_codigo NOT LIKE '41%' $condicionFecha
    $condicionCentroCosto
    ) AS Haber_NIIF
    
    FROM
    Plan_Cuentas PC
    WHERE PC.$campo_codigo LIKE '4%' AND PC.$campo_codigo NOT LIKE '41%'
    $condicion
    GROUP BY PC.$campo_codigo";

    return $query;
}

function gastosNoOperacionales($condicion, $condicionFecha,$idCentroCosto=null) {
    $campo_codigo = $_REQUEST['Tipo'] && $_REQUEST['Tipo'] == 'Pcga' ? 'Codigo' : 'Codigo_Niif';
     $condicionCentroCosto = $idCentroCosto ? ' AND MC.Id_Centro_Costo = '.$idCentroCosto : '';
    $query = "SELECT
    PC.Id_Plan_Cuentas,
    PC.Codigo,
    PC.Nombre,
    PC.Codigo_Niif,
    PC.Nombre_Niif,
    PC.Naturaleza,
    (SELECT IFNULL(SUM(Debe),0) FROM Movimiento_Contable MC 
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas 
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') 
    AND PC2.$campo_codigo NOT LIKE '51%' AND PC2.$campo_codigo NOT LIKE '52%'
    AND PC2.$campo_codigo NOT LIKE '54%' $condicionFecha
    $condicionCentroCosto
    ) AS Debe,

    (SELECT IFNULL(SUM(Haber),0) FROM Movimiento_Contable MC
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') 
    AND PC2.$campo_codigo NOT LIKE '51%' AND PC2.$campo_codigo NOT LIKE '52%'
    AND PC2.$campo_codigo NOT LIKE '54%' $condicionFecha
    $condicionCentroCosto
    ) AS Haber,

    (SELECT IFNULL(SUM(Debe_Niif),0) FROM Movimiento_Contable MC INNER JOIN 
    Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') 
    AND PC2.$campo_codigo NOT LIKE '51%' AND PC2.$campo_codigo NOT LIKE '52%'
    AND PC2.$campo_codigo NOT LIKE '54%' $condicionFecha
    $condicionCentroCosto
    ) AS Debe_NIIF,

    (SELECT IFNULL(SUM(Haber_Niif),0) FROM Movimiento_Contable MC
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') 
    AND PC2.$campo_codigo NOT LIKE '51%' AND PC2.$campo_codigo NOT LIKE '52%'
    AND PC2.$campo_codigo NOT LIKE '54%' $condicionFecha
    $condicionCentroCosto
    ) AS Haber_NIIF

    FROM
    Plan_Cuentas PC
    WHERE PC.$campo_codigo LIKE '5%' AND PC.$campo_codigo NOT LIKE '51%' AND PC.$campo_codigo NOT LIKE '52%' AND PC.$campo_codigo NOT LIKE '54%'
    $condicion
    GROUP BY PC.$campo_codigo";

    return $query;
}

function impuestos($condicion, $condicionFecha, $idCentroCosto=null) {
    $campo_codigo = $_REQUEST['Tipo'] && $_REQUEST['Tipo'] == 'Pcga' ? 'Codigo' : 'Codigo_Niif';
     $condicionCentroCosto = $idCentroCosto ? ' AND MC.Id_Centro_Costo = '.$idCentroCosto : '';
     
    $query = "SELECT
    PC.Id_Plan_Cuentas,
    PC.Codigo,
    PC.Nombre,
    PC.Codigo_Niif,
    PC.Nombre_Niif,
    PC.Naturaleza,
    (SELECT IFNULL(SUM(Debe),0) FROM Movimiento_Contable MC 
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%')
    AND PC2.$campo_codigo NOT LIKE '51%' AND PC2.$campo_codigo NOT LIKE '52%' 
    AND PC2.$campo_codigo NOT LIKE '53%' $condicionFecha
    $condicionCentroCosto
    ) AS Debe,

    (SELECT IFNULL(SUM(Haber),0) FROM Movimiento_Contable MC
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas 
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') 
    AND PC2.$campo_codigo NOT LIKE '51%' AND PC2.$campo_codigo NOT LIKE '52%' 
    AND PC2.$campo_codigo NOT LIKE '53%' $condicionFecha
    $condicionCentroCosto
    ) AS Haber,

    (SELECT IFNULL(SUM(Debe_Niif),0) FROM Movimiento_Contable MC
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas 
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%') 
    AND PC2.$campo_codigo NOT LIKE '51%' AND PC2.$campo_codigo NOT LIKE '52%' 
    AND PC2.$campo_codigo NOT LIKE '53%' $condicionFecha
    $condicionCentroCosto
    ) AS Debe_NIIF,

    (SELECT IFNULL(SUM(Haber_Niif),0) FROM Movimiento_Contable MC
    INNER JOIN Plan_Cuentas PC2 ON MC.Id_Plan_Cuenta = PC2.Id_Plan_Cuentas 
    WHERE MC.Estado != 'Anulado' AND PC2.$campo_codigo LIKE CONCAT(PC.$campo_codigo,'%')
    AND PC2.$campo_codigo NOT LIKE '51%' AND PC2.$campo_codigo NOT LIKE '52%' 
    AND PC2.$campo_codigo NOT LIKE '53%' $condicionFecha
    $condicionCentroCosto
    ) AS Haber_NIIF

    FROM
    Plan_Cuentas PC
    WHERE PC.$campo_codigo LIKE '5%' AND PC.$campo_codigo NOT LIKE '51%' AND PC.$campo_codigo NOT LIKE '52%' AND PC.$campo_codigo NOT LIKE '53%'
    $condicion
    GROUP BY PC.$campo_codigo";

    return $query;
}

function strCondicionFecha() {
    $condicion = '';
    if (isset($_REQUEST['Fecha_Inicial']) && $_REQUEST['Fecha_Inicial'] != "" && isset($_REQUEST['Fecha_Final']) && $_REQUEST['Fecha_Final'] != "") {
        $fecha_inicio = $_REQUEST['Fecha_Inicial'];
        $fecha_fin = $_REQUEST['Fecha_Final'];
        $condicion .= " AND DATE(MC.Fecha_Movimiento) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }

    return $condicion;
}

function strCondicions() {
    $condicion = '';
    
    if (isset($_REQUEST['Nivel']) && $_REQUEST['Nivel'] != "") {
        $campo_codigo = $_REQUEST['Tipo'] && $_REQUEST['Tipo'] == 'Pcga' ? 'Codigo' : 'Codigo_Niif';
        $condicion .= " AND CHAR_LENGTH(PC.$campo_codigo) = $_REQUEST[Nivel]";
    }

    return $condicion;
}
