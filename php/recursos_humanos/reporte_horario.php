<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';

$condicion2 = '';
if (isset($_REQUEST['fun']) && $_REQUEST['fun'] != '') {
    $condicion2 .= " AND CONCAT(F.Nombres,' ', F.Apellidos) LIKE '%$_REQUEST[fun]%'";
}

$fecha_inicial = isset($_REQUEST['inicio']) ? $_REQUEST['inicio'] : false;
$fecha_final = isset($_REQUEST['fin']) ? $_REQUEST['fin'] : false;

$condicion = '';
$condicionD = '';
if ($fecha_inicial && $fecha_final) {

    $condicion .= " AND DF.Fecha BETWEEN  '$fecha_inicial' AND  '$fecha_final' ";
    $condicionD .= " AND Fecha BETWEEN  '$fecha_inicial' AND  '$fecha_final' ";
}

$query = 'SELECT D.*, G.Nombre as Grupo
	FROM Dependencia D INNER JOIN Funcionario F ON D.Id_Dependencia=F.Id_Dependencia
	INNER JOIN Grupo G ON F.Id_Grupo=G.Id_Grupo   GROUP BY D.Id_Dependencia ';

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$dependencia = $oCon->getData();
unset($oCon);

foreach ($dependencia as $i => $value) {
    $query = "SELECT
			CONCAT(F.Nombres,' ',F.Apellidos) as Funcionario,
			F.Imagen,
			'false' as Mostrar ,
			'false' as Cargando,
			'' as Vacio,
			F.Identificacion_Funcionario,
			(SELECT COUNT(*) FROM Llegada_Tarde DF WHERE DF.Identificacion_Funcionario=F.Identificacion_Funcionario $condicion) as LLegada_Tarde,
			IFNULL(H.TotalHoras,0) as Cantidad_Horas
			FROM Funcionario F
			Left JOIN Diario_Fijo DF ON DF.Identificacion_Funcionario=F.Identificacion_Funcionario
			-- LEFT JOIN Contrato_Funcionario CF on CF.Identificacion_Funcionario=F.Identificacion_Funcionario
			
			LEFT JOIN (SELECT
					ROUND(
						SUM(
							IF(
								(
									TIMESTAMPDIFF(MINUTE, DF.Hora_Entrada1,  DF.Hora_Salida1)
								) < 0,
								0,
								(
									TIMESTAMPDIFF(MINUTE, DF.Hora_Entrada1,  DF.Hora_Salida1)
								)
							) + IF(
								(
									TIMESTAMPDIFF(MINUTE, DF.Hora_Entrada2,  DF.Hora_Salida2)
								) < 0,
								0,
								(
									TIMESTAMPDIFF(MINUTE, DF.Hora_Entrada2,  DF.Hora_Salida2)
								)
						))/60
					) as TotalHoras, 
					DF.Identificacion_Funcionario
					FROM
					Diario_Fijo DF
					WHERE DF.Identificacion_Funcionario $condicion
					GROUP BY
					DF.Identificacion_Funcionario)H on H.Identificacion_Funcionario=F.Identificacion_Funcionario
			
			WHERE F.Id_Dependencia=$value[Id_Dependencia] $condicion2
			AND  EXISTS(
				SELECT Identificacion_Funcionario FROM Diario_Fijo WHERE Identificacion_Funcionario=F.Identificacion_Funcionario $condicionD)
			AND F.Identificacion_Funcionario NOT IN (12345,54321,13747525,14253) -- AND F.Autorizado='Si'
			GROUP BY F.Identificacion_Funcionario
			ORDER BY LLegada_Tarde DESC, Funcionario ASC ";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $funcionarios = $oCon->getData();
    unset($oCon);

    $dependencia[$i]['Funcionario'] = $funcionarios;
    if (count($funcionarios) == 0) {
        unset($dependencia[$i]);
    }
}
$dependencia = array_values($dependencia);

echo json_encode($dependencia);

function CalcularMesAnterior($mesActual)
{
    $mesAnterior = $mesActual - 1;

    if ($mesAnterior == 0) {
        return 12;
    } else {
        return $mesAnterior;
    }
}

function NombreMes($mes)
{
    global $meses;

    return $meses[$mes];
}
