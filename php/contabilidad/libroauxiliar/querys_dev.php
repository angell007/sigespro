<?php

function queryByCuenta($condicion, $inicial = false)
{
    global $ultimo_dia_mes;
    $ultimo_dia_mes = '2018-12-31';
    $column = $_REQUEST['Tipo'] == 'General' ? 'Codigo' : 'Codigo_Niif';
    $condicion_nit = isset($_REQUEST['Nit']) ? " AND Nit = $_REQUEST[Nit]" : "";

    $condicion = '';

/*[rieba*/
   $column_1 = 'Codigo';
    $column_2 = 'Codigo_Niif';
    
    $centroCond = "";
/**/

    if (isset($_REQUEST['Cuenta_Inicial']) && $_REQUEST['Cuenta_Inicial'] != "" && isset($_REQUEST['Cuenta_Final']) && $_REQUEST['Cuenta_Final'] != "") {
        $cuenta_inicial = $_REQUEST['Cuenta_Inicial'];
        $cuenta_final = $_REQUEST['Cuenta_Final'];
        $campo_codigo = $_REQUEST['Tipo'] && $_REQUEST['Tipo'] == 'General' ? 'Codigo' : 'Codigo_Niif';
        $condicion .= " WHERE PC.$campo_codigo BETWEEN '$cuenta_inicial' AND '$cuenta_final'";
    }

    if (isset($_REQUEST['Nit']) && $_REQUEST['Nit'] != '') {
        $condicion .= " AND MC.Nit = $_REQUEST[Nit]";
    }

    if ($inicial) {
        /*
        ORIGINAL*/
        $query = "SELECT MC.Id_Plan_Cuenta, PC.Codigo, PC.Nombre AS Nombre_PCGA, PC.Codigo_Niif, PC.Nombre_Niif, PC.Naturaleza, MC.Nit, 
                IFNULL(SUM(BC.Debito_PCGA), 0) AS Debito_PCGA,
                IFNULL(SUM(BC.Credito_PCGA), 0) AS Credito_PCGA,
                IFNULL(SUM(BC.Debito_NIIF), 0) AS Debito_NIIF,
                IFNULL(SUM(BC.Credito_NIIF), 0) AS Credito_NIIF
                FROM Movimiento_Contable MC INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas LEFT JOIN
    Balance_Inicial_Contabilidad BC ON BC.Fecha = '$ultimo_dia_mes' $condicion_nit AND BC.Codigo_Cuenta LIKE CONCAT(PC.Codigo, '%') $condicion GROUP BY MC.Id_Plan_Cuenta ORDER BY PC.$column";
    
    
    
     /*NUEVO */
     $query = "SELECT 
        PC.Id_Plan_Cuentas as Id_Plan_Cuenta,
        PC.Codigo,
        PC.Nombre,
        PC.Nombre AS Nombre_PCGA, 
        PC.Nombre_Niif, 
        Codigo_Niif,
        PC.Naturaleza,
        (SELECT IFNULL(SUM(BI.Debito_PCGA),0) FROM Balance_Inicial_Contabilidad BI WHERE BI.Fecha = '$ultimo_dia_mes' AND BI.Codigo_Cuenta LIKE CONCAT(PC.$column_1,'%') 
                                        ".$centroCond."  ) AS Debito_PCGA,
                                        
        (SELECT IFNULL(SUM(BI.Credito_PCGA),0) FROM Balance_Inicial_Contabilidad BI WHERE Fecha = '$ultimo_dia_mes' AND BI.Codigo_Cuenta LIKE CONCAT(PC.$column_1,'%') 
                                        ".$centroCond.") AS Credito_PCGA,
                                        
        (SELECT IFNULL(SUM(BI.Debito_NIIF),0) FROM Balance_Inicial_Contabilidad BI WHERE Fecha = '$ultimo_dia_mes' AND BI.Codigo_Cuenta_Niif LIKE CONCAT(PC.$column_2,'%')
                                         ".$centroCond." ) AS Debito_NIIF,
                                         
        (SELECT IFNULL(SUM(BI.Credito_NIIF),0) FROM Balance_Inicial_Contabilidad BI WHERE BI.Fecha = '$ultimo_dia_mes' AND BI.Codigo_Cuenta_Niif LIKE CONCAT(PC.$column_2,'%')
                                        ".$centroCond.") AS Credito_NIIF,
   PC.Estado,
        PC.Movimiento,
        PC.Tipo_P
    FROM
        Plan_Cuentas PC
            LEFT JOIN
         (SELECT * FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes') BIC 
         ON BIC.Id_Plan_Cuentas = PC.Id_Plan_Cuentas ".($centro_costo != false ? "AND Id_Centro_Costo = $centro_costo[Id_Centro_Costo]  AND Codigo_Cuenta $cond_exluir  " : "" )."
         ".getStrCondiciones()."
         GROUP BY PC.Id_Plan_Cuentas
    HAVING Estado = 'ACTIVO' OR (Estado = 'INACTIVO' AND (Debito_PCGA > 0 OR Credito_PCGA > 0 OR Debito_NIIF > 0 OR Credito_NIIF > 0))
    ORDER BY PC.$column";
    
    //echo $query;exit;
    
    } else {
        $query = "SELECT PC.Id_Plan_Cuentas, PC.Codigo, PC.Nombre AS Nombre_PCGA, PC.Codigo_Niif, PC.Nombre_Niif, PC.Naturaleza FROM  Plan_Cuentas PC $condicion  ORDER BY PC.$column";
    }
  
    return $query;
}


function getStrCondiciones()
{
    global $tipo_reporte;
    global $nivel_reporte;
    global $cuenta_inicial;
    global $cuenta_final;
    global $centro_costo;
    global $cond_exluir;
    

    $condicion = '';

    
    $column = $tipo_reporte == 'PCGA' ? 'Codigo' : 'Codigo_Niif';
    //echo $tipo_reporte.' '. $column;exit;
    if (isset($cuenta_inicial) && $cuenta_final != '') {
        $condicion .= " WHERE PC.$column BETWEEN '$cuenta_inicial' AND '$cuenta_final' ";
    }
    if (isset($nivel_reporte) && $nivel_reporte != '') {
        if ($condicion == '') {
            $condicion .= " WHERE CHAR_LENGTH($column) BETWEEN 1 AND $nivel_reporte";
        } else {
            $condicion .= " AND CHAR_LENGTH($column) BETWEEN 1 AND $nivel_reporte";
        }
    }
    if ($centro_costo){

        if ($condicion == '') {
            $condicion .= "WHERE Codigo $cond_exluir ";
        } else {
            $condicion .= " AND Codigo $cond_exluir ";
        }
     
       
    }


    return $condicion;
}


function queryByCuentaToNit($condicion, $inicial = false)
{
    global $ultimo_dia_mes;

    $condicion = '';

    if (isset($_REQUEST['Cuenta_Inicial']) && $_REQUEST['Cuenta_Inicial'] != "" && isset($_REQUEST['Cuenta_Final']) && $_REQUEST['Cuenta_Final'] != "") {
        $cuenta_inicial = $_REQUEST['Cuenta_Inicial'];
        $cuenta_final = $_REQUEST['Cuenta_Final'];
        $campo_codigo = $_REQUEST['Tipo'] && $_REQUEST['Tipo'] == 'General' ? 'Codigo' : 'Codigo_Niif';
        $condicion .= " AND PC.$campo_codigo BETWEEN '$cuenta_inicial' AND '$cuenta_final'";
    }

    if (isset($_REQUEST['Nit']) && $_REQUEST['Nit'] != '') {
        $condicion .= " AND MC.Nit = $_REQUEST[Nit]";
    }

    $column = $_REQUEST['Tipo'] == 'General' ? 'Codigo' : 'Codigo_Niif';
    if (!$inicial) {
        $query = "SELECT MC.Id_Plan_Cuenta, PC.Codigo, PC.Nombre AS Nombre_PCGA, PC.Codigo_Niif, PC.Nombre_Niif, PC.Naturaleza FROM Movimiento_Contable MC
        INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas $condicion GROUP BY MC.Id_Plan_Cuenta
        UNION
        (SELECT MC.Id_Plan_Cuentas, PC.Codigo, PC.Nombre AS Nombre_PCGA, PC.Codigo_Niif, PC.Nombre_Niif, PC.Naturaleza FROM Balance_Inicial_Contabilidad 
        MC INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuentas = PC.Id_Plan_Cuentas AND PC.Codigo $condicion GROUP BY MC.Id_Plan_Cuentas) ORDER BY $column
        ";
    } else {
        $query = "SELECT MC.Id_Plan_Cuentas AS Id_Plan_Cuenta, PC.Codigo, PC.Nombre AS Nombre_PCGA, PC.Codigo_Niif, PC.Nombre_Niif, PC.Naturaleza 
        FROM Balance_Inicial_Contabilidad MC INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuentas = PC.Id_Plan_Cuentas $condicion GROUP BY MC.Id_Plan_Cuentas ORDER BY PC.$column";
    }



    return $query;
}

function queryByNit($id_plan_cuenta, $inicial = false, $fecha_inicio= false, $fecha_fin= false)
{
    global $ultimo_dia_mes;

    $condicion = 'WHERE TRUE ';
    $condicion1 = '';
    if (isset($fecha_inicio) && $fecha_inicio != "" && isset($fecha_fin) && $fecha_fin != "") {
        $condicion1 = " AND DATE(MC.Fecha_Movimiento) BETWEEN '$fecha_inicio' AND '$fecha_fin' AND MC.Estado != 'Anulado' ";
    }

    if (isset($_REQUEST['Nit']) && $_REQUEST['Nit'] != '') {
        $condicion .= "  AND MC.Nit = $_REQUEST[Nit]";
    }

    if (!$inicial) {
        $query = "SELECT MC.Nit, (CASE MC.Tipo_Nit
        WHEN 'Cliente' THEN (IF(CONCAT_WS(' ',C.Primer_Nombre,C.Segundo_Nombre,C.Primer_Apellido,C.Segundo_Apellido) != '', CONCAT_WS(' ',C.Primer_Nombre,C.Segundo_Nombre,C.Primer_Apellido,C.Segundo_Apellido),C.Razon_Social))
        WHEN 'Proveedor' THEN (IF(CONCAT_WS(' ',P.Primer_Nombre,P.Segundo_Nombre,P.Primer_Apellido,P.Segundo_Apellido) != '', CONCAT_WS(' ',P.Primer_Nombre,P.Segundo_Nombre,P.Primer_Apellido,P.Segundo_Apellido),P.Razon_Social) )
        WHEN 'Funcionario' THEN (CONCAT_WS(' ', F.Nombres, F.Apellidos))
        WHEN 'Eps' THEN (EPS.Nombre)
        WHEN 'Arl' THEN (ARL.Nombre)
        WHEN 'ICBF' THEN 'ICBF'
        WHEN 'Sena' THEN 'SENA'
        WHEN 'Caja_Compensacion' THEN (CC.Nombre)
        WHEN 'Fondo_Pension' THEN (FP.Nombre)
        WHEN 'Empresa' THEN 'Productos Hospitalarios S.A.'
        ELSE 'S/N'
    END) AS Nombre_Nit, (SELECT IFNULL(SUM(Debito_PCGA),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Nit = MC.Nit AND Id_Plan_Cuentas = $id_plan_cuenta) AS Debito_PCGA,
        (SELECT IFNULL(SUM(Credito_PCGA),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Nit = MC.Nit  AND Id_Plan_Cuentas = $id_plan_cuenta) AS Credito_PCGA,
        (SELECT IFNULL(SUM(Debito_NIIF),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Nit = MC.Nit AND Id_Plan_Cuentas = $id_plan_cuenta) AS Debito_NIIF,
        (SELECT IFNULL(SUM(Credito_NIIF),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Nit = MC.Nit AND Id_Plan_Cuentas = $id_plan_cuenta) AS Credito_NIIF 
        FROM Movimiento_Contable MC
        LEFT JOIN Cliente C on C.Id_Cliente = MC.Nit AND MC.Tipo_Nit = 'Cliente'
        LEFT JOIN Proveedor P on P.Id_Proveedor = MC.Nit AND MC.Tipo_Nit = 'Proveedor'
        LEFT JOIN Funcionario F on F.Identificacion_Funcionario = MC.Nit AND MC.Tipo_Nit = 'Funcionario'
        LEFT JOIN Eps EPS on EPS.Nit = MC.Nit AND MC.Tipo_Nit = 'Eps'
        LEFT JOIN Arl ARL on ARL.Nit = MC.Nit AND MC.Tipo_Nit = 'Arl'
        LEFT JOIN Caja_Compensacion CC on CC.Nit = MC.Nit AND MC.Tipo_Nit = 'Caja_Compensacion'
        LEFT JOIN Fondo_Pension FP on FP.Nit = MC.Nit AND MC.Tipo_Nit = 'Fondo_Pension'
        
        $condicion $condicion1 AND MC.Id_Plan_Cuenta = $id_plan_cuenta GROUP BY MC.Nit 
        HAVING Debito_PCGA > 0 OR Debito_NIIF > 0 OR Credito_PCGA > 0 OR Credito_NIIF > 0
        ORDER BY MC.Fecha_Movimiento, MC.Numero_Comprobante ASC";
    } else {
        $query = "SELECT MC.Nit, (CASE MC.Tipo
        WHEN 'Cliente' THEN (SELECT IF(CONCAT_WS(' ',Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) != '', CONCAT_WS(' ',Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido),Razon_Social) FROM Cliente WHERE Id_Cliente = MC.Nit)
        WHEN 'Proveedor' THEN (SELECT IF(CONCAT_WS(' ',Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) != '', CONCAT_WS(' ',Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido),Razon_Social) FROM Proveedor WHERE Id_Proveedor = MC.Nit)
        WHEN 'Funcionario' THEN (SELECT CONCAT_WS(' ', Nombres, Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = MC.Nit)
        WHEN 'Eps' THEN (SELECT Nombre FROM Eps WHERE Nit = MC.Nit)
        WHEN 'Arl' THEN (SELECT Nombre FROM Arl WHERE Nit = MC.Nit)
        WHEN 'ICBF' THEN 'ICBF'
        WHEN 'Sena' THEN 'SENA'
        WHEN 'Caja_Compensacion' THEN (SELECT Nombre FROM Caja_Compensacion WHERE Nit = MC.Nit)
        WHEN 'Fondo_Pension' THEN (SELECT Nombre FROM Fondo_Pension WHERE Nit = MC.Nit)
        WHEN 'Empresa' THEN 'Productos Hospitalarios S.A.'
        ELSE 'S/N'
    END) AS Nombre_Nit, IFNULL(SUM(Debito_PCGA),0) AS Debito_PCGA,
    IFNULL(SUM(Credito_PCGA),0) AS Credito_PCGA,
    IFNULL(SUM(Debito_NIIF),0) AS Debito_NIIF,
    IFNULL(SUM(Credito_NIIF),0) AS Credito_NIIF FROM Balance_Inicial_Contabilidad MC $condicion AND MC.Id_Plan_Cuentas = $id_plan_cuenta AND Fecha = '$ultimo_dia_mes'
    HAVING Debito_PCGA > 0 OR Debito_NIIF > 0 OR Credito_PCGA > 0 OR Credito_NIIF > 0
    ";
    }

    return $query;
}

function queryMovimientosCuenta($id_plan_cuenta, $nit = null)
{
    $condicion = '';
    $condicion_nit = '';
    $orderBy = ' ORDER BY MC.Fecha_Movimiento, MC.Numero_Comprobante';
    if (isset($_REQUEST['Fecha_Inicial']) && $_REQUEST['Fecha_Inicial'] != "" && isset($_REQUEST['Fecha_Final']) && $_REQUEST['Fecha_Final'] != "") {
        $fecha_inicio = $_REQUEST['Fecha_Inicial'];
        $fecha_fin = $_REQUEST['Fecha_Final'];
        $condicion .= " AND DATE(MC.Fecha_Movimiento) BETWEEN '$fecha_inicio' AND '$fecha_fin' AND MC.Estado != 'Anulado' ";
    }
    if ((isset($_REQUEST['Nit']) && $_REQUEST['Nit'] != '') || $nit !== null) {
        $tercero = $nit !== null ? $nit : $_REQUEST['Nit'];
        $condicion_nit .= " AND MC.Nit = $tercero";
        $orderBy = ' ORDER BY MC.Nit, MC.Fecha_Movimiento, MC.Numero_Comprobante';
    }
    $query = "SELECT 
            PC.Codigo, 
            PC.Codigo_Niif,
            PC.Nombre, 
            PC.Nombre_Niif,
            MC.Numero_Comprobante,
            MC.Nit,
            DATE_FORMAT(MC.Fecha_Movimiento, '%d/%m/%Y') AS Fecha,
            IF(MC.Detalles = '' OR MC.Detalles IS NULL,
                'S/C',
                MC.Detalles) AS Concepto,
            (CASE MC.Tipo_Nit
                WHEN 'Cliente' THEN (SELECT IF(CONCAT_WS(' ',Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) != '', CONCAT_WS(' ',Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido),Razon_Social) FROM Cliente WHERE Id_Cliente = MC.Nit)
                WHEN 'Proveedor' THEN (SELECT IF(CONCAT_WS(' ',Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) != '', CONCAT_WS(' ',Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido),Razon_Social) FROM Proveedor WHERE Id_Proveedor = MC.Nit)
                WHEN 'Funcionario' THEN (SELECT CONCAT_WS(' ', Nombres, Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = MC.Nit)
                WHEN 'Eps' THEN (SELECT Nombre FROM Eps WHERE Nit = MC.Nit)
                WHEN 'Arl' THEN (SELECT Nombre FROM Arl WHERE Nit = MC.Nit)
                WHEN 'ICBF' THEN 'ICBF'
                WHEN 'Sena' THEN 'SENA'
                WHEN 'Caja_Compensacion' THEN (SELECT Nombre FROM Caja_Compensacion WHERE Nit = MC.Nit)
                WHEN 'Fondo_Pension' THEN (SELECT Nombre FROM Fondo_Pension WHERE Nit = MC.Nit)
                WHEN 'Empresa' THEN 'Productos Hospitalarios S.A.'
                ELSE 'S/N'
            END) AS Nombre_Nit,
            MC.Documento,
            MC.Debe,
            MC.Haber,
            MC.Debe_Niif,
            MC.Haber_Niif
        FROM
            Plan_Cuentas PC
        INNER JOIN
            Movimiento_Contable MC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
        WHERE
            MC.Id_Plan_Cuenta = $id_plan_cuenta
            $condicion_nit
            $condicion
            $orderBy";
            
    return $query;
    
}


function strCondicions()
{
    $condicion = '';
    if (isset($_REQUEST['Fecha_Inicial']) && $_REQUEST['Fecha_Inicial'] != "" && isset($_REQUEST['Fecha_Final']) && $_REQUEST['Fecha_Final'] != "") {
        $fecha_inicio = $_REQUEST['Fecha_Inicial'];
        $fecha_fin = $_REQUEST['Fecha_Final'];
        $condicion .= " WHERE DATE(MC.Fecha_Movimiento) BETWEEN '$fecha_inicio' AND '$fecha_fin' AND MC.Estado != 'Anulado' ";
    }

    if (isset($_REQUEST['Cuenta_Inicial']) && $_REQUEST['Cuenta_Inicial'] != "" && isset($_REQUEST['Cuenta_Final']) && $_REQUEST['Cuenta_Final'] != "") {
        $cuenta_inicial = $_REQUEST['Cuenta_Inicial'];
        $cuenta_final = $_REQUEST['Cuenta_Final'];
        $campo_codigo = $_REQUEST['Tipo'] && $_REQUEST['Tipo'] == 'General' ? 'Codigo' : 'Codigo_Niif';
        $condicion .= " AND PC.$campo_codigo BETWEEN '$cuenta_inicial' AND '$cuenta_final'";
    }

    if (isset($_REQUEST['Nit']) && $_REQUEST['Nit'] != '') {
        $condicion .= " AND MC.Nit = $_REQUEST[Nit]";
    }

    return $condicion;
}
