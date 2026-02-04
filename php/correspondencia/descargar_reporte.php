<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_Correspondencia.xls"');
header('Cache-Control: max-age=0'); 

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condiciones = getCondiciones();
$contenido = '';

/* $query = "SELECT CONCAT_WS(' ',Nombres,Apellidos) AS Funcionario, DATE_FORMAT(C.Fecha_Envio, '%d/%m/%Y') AS Fecha_Envio, C.Cantidad_Folios AS Folios, (SELECT Nombre FROM Tipo_Servicio WHERE Id_Tipo_Servicio = C.Id_Tipo_Servicio) AS Tipo_Servicio, CONCAT('CO000',C.Id_Correspondencia) AS Codigo, C.Empresa_Envio, C.Numero_Guia AS Guia, DATE(C.Fecha_Entrega_Real) AS Fecha_Recibido, D.DIS, D.Auditorias FROM Correspondencia C INNER JOIN (SELECT Id_Correspondencia, GROUP_CONCAT(Codigo SEPARATOR ', ') AS DIS, GROUP_CONCAT(CONCAT('AUD00',A.Id_Auditoria) SEPARATOR ', ') AS Auditorias FROM Dispensacion D INNER JOIN Auditoria A ON D.Id_Dispensacion = A.Id_Dispensacion WHERE D.Id_Correspondencia IS NOT NULL GROUP BY D.Id_Correspondencia) D ON D.Id_Correspondencia = C.Id_Correspondencia INNER JOIN Funcionario F ON F.Identificacion_Funcionario = C.Id_Funcionario_Envia INNER JOIN Punto_Dispensacion P ON P.Id_Punto_Dispensacion = C.Punto_Envio $condiciones"; */
// MODIFICACIÓN POR KENDRY 30/12/2019
$query = "SELECT CONCAT_WS(' ',Nombres,Apellidos) AS Funcionario, DATE_FORMAT(C.Fecha_Envio, '%d/%m/%Y') AS Fecha_Envio, C.Cantidad_Folios AS Folios, (SELECT Nombre FROM Tipo_Servicio WHERE Id_Tipo_Servicio = C.Id_Tipo_Servicio) AS Tipo_Servicio, CONCAT('CO000',C.Id_Correspondencia) AS Codigo, C.Empresa_Envio, C.Numero_Guia AS Guia, DATE(C.Fecha_Entrega_Real) AS Fecha_Recibido, D.DIS, D.Auditoria FROM Correspondencia C INNER JOIN (SELECT Id_Correspondencia, Codigo AS DIS, CONCAT('AUD00',A.Id_Auditoria) AS Auditoria FROM Dispensacion D INNER JOIN Auditoria A ON D.Id_Dispensacion = A.Id_Dispensacion WHERE D.Id_Correspondencia IS NOT NULL) D ON D.Id_Correspondencia = C.Id_Correspondencia INNER JOIN Funcionario F ON F.Identificacion_Funcionario = C.Id_Funcionario_Envia INNER JOIN Punto_Dispensacion P ON P.Id_Punto_Dispensacion = C.Punto_Envio $condiciones";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$registros = $oCon->getData();
unset($oCon);

if ($registros) {
    $encabezado = $registros[0];

    $contenido .= '
    <table border="1" style="border-collapse: collapse">
    <tr>
    ';

    foreach ($encabezado as $columna => $value) { // imprimir columnas
        $contenido .= '<th>'.$columna.'</th>';
    }
    $contenido .= '</tr>';

    foreach ($registros as $i => $registro) {
        $contenido .= "<tr>";
        foreach ($registro as $columna => $valor) { // imprimir datos.
            $contenido .= '<td>'.$valor.'</td>';
        }
        $contenido .= "</tr>";
    }

    $contenido .= '</table>';    
} else {
    $contenido .= "NO HAY CONTENIDO PARA MOSTRAR";
}

echo $contenido;

function getCondiciones() {
    $condicion = '';
    if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
        $condicion .= " WHERE (DATE(C.Fecha_Envio) BETWEEN '$fecha_inicio' AND '$fecha_fin')";
    }

    if (isset($_REQUEST['punto']) && $_REQUEST['punto'] != '') {
        $condicion .= " AND C.Punto_Envio = $_REQUEST[punto]";
    }
    
    if (isset($_REQUEST['tipo_servicio']) && $_REQUEST['tipo_servicio'] != '') {
        $condicion .= " AND C.Id_Tipo_Servicio = $_REQUEST[tipo_servicio]";
    }
    
    if (isset($_REQUEST['departamento']) && $_REQUEST['departamento'] != '') {
        $condicion .= " AND P.Departamento = $_REQUEST[departamento]";
    }
    
    if (isset($_REQUEST['estado']) && $_REQUEST['estado'] != '') {
        $condicion .= " AND C.Estado = '$_REQUEST[estado]'";
    }

    return $condicion;
}
?>