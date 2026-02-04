<?php

function queryByCuenta($condicion) {
    global $ultimo_dia_mes;
    $column = $_REQUEST['Tipo'] == 'General' ? 'Codigo' : 'Codigo_Niif';
    $query = "SELECT MC.Id_Plan_Cuenta, PC.Codigo, PC.Nombre AS Nombre_PCGA, PC.Codigo_Niif, PC.Nombre_Niif, PC.Naturaleza, (SELECT IFNULL(SUM(Debito_PCGA),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Codigo_Cuenta LIKE CONCAT(PC.$column,'%')) AS Debito_PCGA,
    (SELECT IFNULL(SUM(Credito_PCGA),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Codigo_Cuenta LIKE CONCAT(PC.$column,'%')) AS Credito_PCGA,
    (SELECT IFNULL(SUM(Debito_NIIF),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Codigo_Cuenta LIKE CONCAT(PC.$column,'%')) AS Debito_NIIF,
    (SELECT IFNULL(SUM(Credito_NIIF),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Codigo_Cuenta LIKE CONCAT(PC.$column,'%')) AS Credito_NIIF FROM Movimiento_Contable MC INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas $condicion GROUP BY MC.Id_Plan_Cuenta ORDER BY PC.$column";

    return $query;
}

function queryByCuentaToNit($condicion) {
    global $ultimo_dia_mes;
    $column = 'Codigo';
    $query = "SELECT MC.Id_Plan_Cuenta, PC.Codigo, PC.Nombre AS Nombre_PCGA, PC.Codigo_Niif, PC.Nombre_Niif, PC.Naturaleza FROM Movimiento_Contable MC INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas $condicion GROUP BY MC.Id_Plan_Cuenta ORDER BY PC.$column";

    return $query;
}

function queryByNit($id_plan_cuenta) {
    global $ultimo_dia_mes;

    $condicion = '';

    if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
        list($fecha_inicio, $fecha_fin) = explode(' - ', $_REQUEST['fechas']);
        $condicion .= " WHERE DATE(MC.Fecha_Movimiento) BETWEEN '$fecha_inicio' AND '$fecha_fin' AND MC.Estado != 'Anulado' ";
    }

    if (isset($_REQUEST['cliente']) && $_REQUEST['cliente'] != '') {
        $condicion .= " AND MC.Nit = $_REQUEST[cliente]";
    }

    $query = "SELECT MC.Nit, (CASE MC.Tipo_Nit
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
END) AS Nombre_Nit, (SELECT IFNULL(SUM(Debito_PCGA),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Nit = MC.Nit AND Id_Plan_Cuentas = $id_plan_cuenta) AS Debito_PCGA,
    (SELECT IFNULL(SUM(Credito_PCGA),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Nit = MC.Nit  AND Id_Plan_Cuentas = $id_plan_cuenta) AS Credito_PCGA,
    (SELECT IFNULL(SUM(Debito_NIIF),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Nit = MC.Nit AND Id_Plan_Cuentas = $id_plan_cuenta) AS Debito_NIIF,
    (SELECT IFNULL(SUM(Credito_NIIF),0) FROM Balance_Inicial_Contabilidad WHERE Fecha = '$ultimo_dia_mes' AND Nit = MC.Nit AND Id_Plan_Cuentas = $id_plan_cuenta) AS Credito_NIIF FROM Movimiento_Contable MC $condicion AND MC.Id_Plan_Cuenta = $id_plan_cuenta GROUP BY MC.Nit ORDER BY MC.Fecha_Movimiento ASC";

    return $query;
}

function queryMovimientosCuenta($id_plan_cuenta, $nit = null) {
    $condicion = '';

    if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
        list($fecha_inicio, $fecha_fin) = explode(' - ', $_REQUEST['fechas']);
        $condicion .= " AND DATE(MC.Fecha_Movimiento) BETWEEN '$fecha_inicio' AND '$fecha_fin' AND MC.Estado != 'Anulado' ";
    }

    if (isset($_REQUEST['cliente']) && $_REQUEST['cliente'] != '') {
        $condicion .= " AND MC.Nit = $_REQUEST[cliente]";
    }
    $query = "SELECT 
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
            MC.Debe AS Debe_PCGA,
            MC.Haber as Haber_PCGA,
            MC.Debe_Niif,
            MC.Haber_Niif
        FROM
            Movimiento_Contable MC
        WHERE
            MC.Id_Plan_Cuenta = $id_plan_cuenta
            $condicion
        ORDER BY MC.Nit, MC.Fecha_Movimiento";
    
    return $query;
}

function strCondicions() {
    $condicion = '';
    if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
        list($fecha_inicio, $fecha_fin) = explode(' - ', $_REQUEST['fechas']);
        $condicion .= " WHERE DATE(MC.Fecha_Movimiento) BETWEEN '$fecha_inicio' AND '$fecha_fin' AND MC.Estado != 'Anulado' ";
    }

    if (isset($_REQUEST['cliente']) && $_REQUEST['cliente'] != '') {
        $condicion .= " AND MC.Nit = $_REQUEST[cliente]";
    }

    $condicion .= ' AND MC.Id_Plan_Cuenta = 57';

    return $condicion;
}