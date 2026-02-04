<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

require_once('../../config/start.inc.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.http_response.php');

$http_response = new HttpResponse();
$having = '';
$pag = (isset($_REQUEST['pag']) ? $_REQUEST['pag'] : '');
$tam = (isset($_REQUEST['tam']) ? $_REQUEST['tam'] : '');
$min = ($pag - 1) * $tam;
$limit = "LIMIT $min, $tam";
$condicion2='';
$condicion = SetCondiciones($_REQUEST);
$query = "SELECT  D.*, 
            PT.Nombre as Punto,
            PD.numeroAutorizacion Autorizacion
            FROM ( Select
                CONCAT_WS(' ', Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido ) as Paciente  ,PA.Id_Paciente, 
                D.Codigo as Disp, D.Estado_Acta,
                DATE(D.Fecha_Actual) as Fecha , 
                IF(D.Firma_Reclamante!='' OR D.Acta_Entrega IS NOT NULL, 'Si', 'No' ) as Soporte,
                D.Acta_Entrega, D.Firma_Reclamante, 
                D.Id_Dispensacion,
                D.Id_Punto_Dispensacion,
                D.Id_Positiva_Data
                FROM Dispensacion D  
            INNER JOIN Paciente  PA ON D.Numero_Documento=PA.Id_Paciente 
            WHERE 1 $condicion
            group by D.Id_Dispensacion
            ORDER BY D.Id_Dispensacion DESC 
            $limit
            )D 
            INNER JOIN Punto_Dispensacion PT ON D.Id_Punto_Dispensacion=PT.Id_Punto_Dispensacion
            Left Join Positiva_Data PD ON PD.Id_Dispensacion = D.Id_Dispensacion or D.Id_Positiva_Data = PD.id
            $condicion2
            ";

$query_count =
"SELECT COUNT(*) AS Total
FROM (
		SELECT D.*
			FROM(
		 		SELECT D.Id_Punto_Dispensacion 
					FROM Dispensacion D
					INNER JOIN Paciente PA ON D.Numero_Documento=PA.Id_Paciente
					WHERE 1
    $condicion
    ) D
		INNER JOIN Punto_Dispensacion PT ON D.Id_Punto_Dispensacion=PT.Id_Punto_Dispensacion
    $condicion2) D
    ";

$auditorias = array("codigo" => "success", "titulo" => "Consulta Exitosa", "mensaje" => "Se han encontrado registros!",);

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$auditorias['query_result'] = $oCon->getData();

$oCon->setQuery($query_count);
$oCon->setTipo('simple');
$auditorias['numReg'] = $oCon->getData()['Total'];


echo json_encode($auditorias);
function SetCondiciones($req)
{
    global $condicion2;
    $condicion = '';

    if (isset($_REQUEST['validar']) && $_REQUEST['validar'] != "") {
        $condicion .= "AND  D.Estado_Acta='" . $_REQUEST['validar'] . "'";
    }
    if (isset($_REQUEST['SinValidar']) && $_REQUEST['SinValidar'] != "") {
        $svalidar = 'Sin Validar';
        $condicion .= "AND  D.Estado_Acta ='" . $svalidar . "'";
    }
    if (isset($_REQUEST['Observacion']) && $_REQUEST['Observacion'] != "") {
        $svalidar = 'Con Observacion';
        $condicion .= "AND  D.Estado_Acta ='" . $svalidar . "'";
    }

    if (isset($_REQUEST['dis']) && $_REQUEST['dis'] != "") {
        $condicion .= "AND  D.Codigo='" . $_REQUEST['dis'] . "'";
    }
    if (isset($_REQUEST['id_paciente']) && $_REQUEST['id_paciente'] != "") {

        $condicion .= "AND  PA.Id_Paciente='" . $_REQUEST['id_paciente'] . "'";
    }
    if (isset($_REQUEST['soporte']) && $_REQUEST['soporte'] != "") {
        $soporte = $_REQUEST['soporte'];
        if ($soporte == 'Si') {
            $condicion .= " AND ( D.Acta_Entrega IS NOT NULL)";
        } else {
            $condicion .= " AND ( D.Firma_Reclamante='' and D.Acta_Entrega IS  NULL)";
        }
    }

    if (isset($_REQUEST['punto']) && $_REQUEST['punto'] != "") {
        $condicion2 .= "WHERE  PT.Nombre LIKE'%" . $_REQUEST['punto'] . "%'";
    }
    return $condicion;
}

function GetIdServicioCapita()
{
    global $queryObj;
    $query = "SELECT Id_Tipo_Servicio FROM Tipo_Servicio WHERE Nombre LIKE '%CAPITA%'";

    $queryObj->SetQuery($query);
    $pd = $queryObj->ExecuteQuery('simple');

    return $pd['Id_Tipo_Servico'];
}
