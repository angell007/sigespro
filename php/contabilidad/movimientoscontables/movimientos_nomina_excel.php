<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

include_once('../../../class/class.querybasedatos.php');
include_once('../../../class/class.generar_excel.php');

$id_registro = ( isset( $_REQUEST['id_registro'] ) ? $_REQUEST['id_registro'] : '' );
$query = '
        SELECT
            PC.Codigo,
            PC.Nombre,
            MC.Nit,
            MC.Fecha_Movimiento AS Fecha,
            MC.Tipo_Nit,
            MC.Id_Registro_Modulo,
            MC.Documento,
            MC.Debe,
            MC.Haber,
            MC.Detalles,
            COALESCE(CC_MC.Nombre, CC_F.Nombre, "Sin Centro Costo") AS Centro_Costo,
            (CASE
                WHEN MC.Tipo_Nit = "Cliente" THEN (SELECT Nombre FROM Cliente WHERE Id_Cliente = MC.Nit)
                WHEN MC.Tipo_Nit = "Proveedor" THEN (SELECT Nombre FROM Proveedor WHERE Id_Proveedor = MC.Nit)
                WHEN MC.Tipo_Nit = "Funcionario" THEN (SELECT CONCAT_WS(" ", Nombres, Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = MC.Nit)
            END) AS Nombre_Cliente,
            "Factura Venta" AS Registro
        FROM Movimiento_Contable MC
        INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
        LEFT JOIN Centro_Costo CC_MC ON CC_MC.Id_Centro_Costo = MC.Id_Centro_Costo
        LEFT JOIN Funcionario F ON F.Identificacion_Funcionario = MC.Nit AND MC.Tipo_Nit = "Funcionario"
        LEFT JOIN Centro_Costo CC_F ON CC_F.Id_Centro_Costo = F.Id_Centro_Costo
        WHERE
            MC.Estado = "Activo" AND Id_Modulo = 18 AND Id_registro_Modulo ='.$id_registro.'  ORDER BY Id_Movimiento_Contable';

$query_suma='SELECT
SUM(MC.Debe) AS Debe,
SUM(MC.Haber) AS Haber
FROM Movimiento_Contable MC
WHERE
MC.Estado = "Activo" AND Id_Modulo = 18 AND Id_registro_Modulo ='.$id_registro;
$excel=new GenerarExcel($query,$query_suma);  
$excel=$excel->CrearExcel();

?>
