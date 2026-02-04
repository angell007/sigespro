<?php

use SebastianBergmann\Environment\Console;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');
include_once '../../class/class.consulta.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.http_response.php';

if ($_REQUEST['Actualizar']) {
    echo json_encode(PUT());
} else {
    echo json_encode(GET());
}

function GET()
{

    $cod = isset($_REQUEST['Codigo']) ? " AND R.Codigo LIKE '%$_REQUEST[Codigo]%'" : "";

    $query_cartera = "SELECT R.Id_Cliente, R.Nombre, MAX(R.Dias_Mora) AS Dias_Mora, SUM(R.TOTAL) AS TOTAL
                        FROM (SELECT MC.Id_PLan_Cuenta, C.Id_Cliente, C.Nombre, MC.Fecha_Movimiento, IF(C.Condicion_Pago > 1,
                        IF(DATEDIFF(CURDATE(), DATE(MC.Fecha_Movimiento)) > C.Condicion_Pago, DATEDIFF(CURDATE(),
                        DATE(MC.Fecha_Movimiento)) - C.Condicion_Pago, 0), 0) AS Dias_Mora,
                        (CASE PC.Naturaleza
                              WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
                              ELSE (SUM(MC.Debe) - SUM(MC.Haber))
                        END) AS TOTAL
                        FROM
                        Movimiento_Contable MC
                        INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
                        INNER JOIN Cliente C ON C.Id_Cliente = MC.Nit
                        WHERE MC.Estado != 'Anulado'
                        AND Id_Plan_Cuenta = 57
                        AND MC.Nit in (Select R.Id_Destino from Remision R  Where R.Estado = 'Cartera'  $cod )
                        GROUP BY MC.Documento, C.Id_Cliente
                        HAVING TOTAL != 0
                        ) R
                        GROUP BY R.Id_Plan_Cuenta, R.Id_Cliente ";

    $query_remisiones = "SELECT R.*
                              FROM Remision R Where R.Estado = 'Cartera' $cod";

    $query = "SELECT R.Id_Remision, R.Codigo, C.Nombre as Cliente, R.Codigo AS Rem,
                  C.Id_Cliente,
                  SUM(PR.Cantidad *( PR.Precio*(1-PR.Descuento) )) Subtotal_Remision,
                  C.Dias_Mora,
                  R.Fecha
                  From ($query_remisiones) R LEFT JOIN ($query_cartera) C ON C.Id_Cliente = R.Id_Destino
                  
                  Inner Join Producto_Remision PR on PR.Id_Remision = R.Id_Remision
                  
                  group by R.Id_Remision
                  ORDER BY R.Id_Remision Desc
                  ";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo("Multiple");
    $remisiones = $oCon->getData();
    return ($remisiones);
}

function PUT()
{
    $http_response = new HttpResponse();
    $funcionario = $_REQUEST['funcionario'];
    $id_remision = $_REQUEST['id'];
    $tipo_aprobacion = $_REQUEST['Actualizar'];
    $nota = $_REQUEST['Nota'];

    $query = "SELECT Id_Cargo from Funcionario Where Identificacion_Funcionario = $funcionario";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $cargo = $oCon->getData();

    if ($cargo['Id_Cargo'] == 59 || $cargo['Id_Cargo'] == 33 || $cargo['Id_Cargo'] == 28) { //12: Auxiliar de cartera, 59: Jefe de cartera; 33: GERENTE GENERAL, 28:PAULA MOMENTANEAMENTE
        $remision = new complex("Remision", "Id_Remision", $id_remision);

        $rem = $remision->getData();

        $actividad = new complex('Actividad_Remision', "Id_Actividad_Remision");
        $actividad->Id_Remision = $id_remision;
        $actividad->Identificacion_Funcionario = $funcionario;
        $actividad->Estado = "Cartera";
        if ($rem['Estado'] == 'Cartera') {

            if ($tipo_aprobacion == "Aprueba") {
                $remision->Estado = "Pendiente";
                $actividad->Detalles = "Cartera aprueba remision pendiente con la observacion: $nota";
                $http_response->SetRespuesta(0, 'Guardado Correctamente', 'Remision Liberada para Alistamiento');
                $remision->save();
                $actividad->save();
            }
            if ($tipo_aprobacion == "Rechaza") {
                $remision->Estado = "Rechazada";
                $remision->save();
                $actividad->Detalles = "Cartera Rechaza remision pendiente con la observacion: $nota";
                $http_response->SetRespuesta(0, 'Guardado Correctamente', 'Remision Rechazada');
                $actividad->save();
            }
        }
        else{
            $http_response->SetRespuesta(1, 'No permitido', 'La remision ya no está pendiente de observacion de cartera');
        }

        $response = $http_response->GetRespuesta();
        $response['Remisiones'] = GET();

        unset($oItem);

    } else {
        sleep(2);
        http_response_code(401);
        $http_response->SetRespuesta(1, 'No autorizado', 'Comuniquese con la persona encargada de cartera');
        $response = $http_response->GetRespuesta();
    }

    return ($response);
}
