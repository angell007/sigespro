<?php

header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

header('Content-Type: application/vnd.ms-excel');

header('Content-Disposition: attachment;filename="Libro_auxiliar_cuenta.xls"');

header('Cache-Control: max-age=0'); 



require_once('../../config/start.inc.php');

include_once('../../class/class.lista.php');

include_once('../../class/class.complex.php');

include_once('../../class/class.consulta.php');



$tipo_reporte = $_REQUEST['Tipo_Reporte'];



$resultados = getResultReporte($tipo_reporte);



$contenido = 'SIN RESULTADOS PARA MOSTRAR';



if ($resultados) {

    $encabezado = $resultados[0];



    $contenido = '

        <table border="1" style="border-collapse:collapse">

    ';

    $contenido .= '<tr>';

    foreach ($encabezado as $columna => $value) {

        $contenido .= '

            <th>'.$columna.'</th>

        ';

    }

    $contenido .= '</tr>';



    foreach ($resultados as $i => $value) {

        $contenido .= '<tr>';

        foreach ($value as $columna => $valor) {

            if (isFieldNumber($columna)) {
                $contenido .= '<td>'.number_format($valor,2,",","").'</td>';
            } else {
                if($columna = 'Codigo' ){
                    /* $valor =  utf8_encode($valor); */
                  
                    /*  $valor .='/'; */
                }
                $contenido .= '<td>'.utf8_encode($valor).'</td>';    
            }

        }

        $contenido .= '</tr>';

    }



    $contenido .= '</table>';

}



echo $contenido;



function strCondiciones() {

    $condicion = '';
    global $tipo_reporte;

    
    if (isset($_REQUEST['Fechas']) && $_REQUEST['Fechas'] != '') {

        list($fecha_inicio, $fecha_fin) = explode(' - ', $_REQUEST['Fechas']);
        if ($tipo_reporte=='Adiciones') {
            $condicion.= ' AND  DATE(AD.Fecha)  BETWEEN "'.$fecha_inicio.'" AND "'.$fecha_fin.'" ';
        }else{
            $condicion .= $tipo_reporte == 'Relacion' ? " AND DATE(AF.Fecha) <= '$_REQUEST[Fechas]'" : " AND DATE(AF.Fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
        }

    }

    if (isset($_REQUEST['Tipo_Activo']) && $_REQUEST['Tipo_Activo'] != '') {

        $condicion .= " AND AF.Id_Tipo_Activo_Fijo = $_REQUEST[Tipo_Activo]";

    }

    if (isset($_REQUEST['Centro_Costo']) && $_REQUEST['Centro_Costo'] != '') {

        $condicion .= " AND AF.Id_Centro_Costo = $_REQUEST[Centro_Costo]";

    }



    return $condicion;

}



function getResultReporte($tipo_reporte) {

    $resultado = [];
    $query = '';

    $condiciones = strCondiciones();

    switch ($tipo_reporte) {

        case 'Compras':

            $query = "SELECT AF.Codigo_Activo_Fijo AS Codigo, AF.Nombre AS Activo, Referencia, C.Codigo AS Cod_Centro_Costo, C.Nombre AS Nom_Centro_Costo, DATE(AF.Fecha) AS Fecha_Compra, Base AS Costo, Iva, Costo_PCGA AS Total  FROM Activo_Fijo AF LEFT JOIN Centro_Costo C ON C.Id_Centro_Costo = AF.Id_Centro_Costo WHERE AF.Estado != 'Anulada' $condiciones ORDER BY AF.Fecha";

            break;

        

        case 'Movimientos':

            list($fecha_inicio, $fecha_fin) = (isset($_REQUEST['Fechas'])) && $_REQUEST['Fechas'] != '' ? explode(' - ',$_REQUEST['Fechas']) : [];

            $query = "SELECT AF.Codigo_Activo_Fijo AS Codigo, AF.Nombre AS Activo, Referencia, C.Codigo AS Cod_Centro_Costo, C.Nombre AS Nom_Centro_Costo, '$fecha_inicio' AS Mov_Desde, '$fecha_fin' AS Mov_Hasta, Base AS Costo, Iva, IFNULL(AAF.Adicion,0) AS Adicion, IFNULL(D.Depreciado, 0) AS Depreciacion, 0 AS Baja, (Costo_PCGA+IFNULL(AAF.Adicion,0)-IFNULL(D.Depreciado, 0)) AS Neto_Movimiento  FROM Activo_Fijo AF LEFT JOIN Centro_Costo C ON C.Id_Centro_Costo = AF.Id_Centro_Costo LEFT JOIN (SELECT AFD.Id_Activo_Fijo, SUM(AFD.Valor_PCGA) AS Depreciado FROM Activo_Fijo_Depreciacion AFD INNER JOIN Depreciacion DP ON AFD.Id_Depreciacion = DP.Id_Depreciacion WHERE DP.Estado = 'Activo' GROUP BY AFD.Id_Activo_Fijo) D ON D.Id_Activo_Fijo = AF.Id_Activo_Fijo LEFT JOIN (SELECT Id_Activo_Fijo, Costo_PCGA AS Adicion FROM Adicion_Activo_Fijo) AAF ON AAF.Id_Activo_Fijo = AF.Id_Activo_Fijo WHERE AF.Estado != 'Anulada' $condiciones ORDER BY AF.Fecha";

            break;
        
        case 'Relacion':

            $query = "SELECT AF.Codigo_Activo_Fijo AS Codigo, AF.Nombre AS Activo, Referencia, C.Codigo AS Cod_Centro_Costo, 
            C.Nombre AS Nom_Centro_Costo, '$_REQUEST[Fechas]' AS Fecha_Corte, Base AS Costo, Iva, Costo_PCGA AS Total, 
            IFNULL(D.Depreciado, 0) AS Depreciacion, (Costo_PCGA-IFNULL(D.Depreciado, 0)) AS Neto_Activo  FROM Activo_Fijo AF 
            LEFT JOIN Centro_Costo C ON C.Id_Centro_Costo = AF.Id_Centro_Costo LEFT JOIN (SELECT AFD.Id_Activo_Fijo, SUM(AFD.Valor_PCGA) AS Depreciado FROM Activo_Fijo_Depreciacion AFD INNER JOIN Depreciacion DP ON AFD.Id_Depreciacion = DP.Id_Depreciacion WHERE DP.Estado = 'Activo' GROUP BY AFD.Id_Activo_Fijo) D ON D.Id_Activo_Fijo = AF.Id_Activo_Fijo WHERE AF.Estado != 'Anulada' $condiciones ORDER BY AF.Fecha";

            break;
            
        case 'Adiciones':

            $query = "SELECT AF.Codigo_Activo_Fijo AS Codigo, AF.Nombre AS Activo, 
                     C.Codigo AS Cod_Centro_Costo, C.Nombre AS Nom_Centro_Costo, 

                    AD.Codigo AS Cod_Adicion, AD.Fecha, AD.Nombre AS Nombre_Adicion, AD.Concepto, AD.Base, AD.Iva, 
	            	AD.Costo_NIIF AS Costo
                  
                FROM Activo_Fijo AF 
                LEFT JOIN Centro_Costo C ON C.Id_Centro_Costo = AF.Id_Centro_Costo 
                INNER JOIN Adicion_Activo_Fijo AD ON AD.Id_Activo_Fijo = AF.Id_Activo_Fijo
            WHERE AF.Estado != 'Anulada'  $condiciones ORDER BY AF.Fecha";

            break;

    }

    $oCon = new consulta();

    $oCon->setQuery($query);

    $oCon->setTipo('Multiple');

    $resultado = $oCon->getData();

    unset($oCon);



    return $resultado;

}

function isFieldNumber($field) {
    $fields = ["Total","Iva","Costo","Depreciacion","Neto_Activo","Adicion","Baja","Neto_Movimiento"];

    return in_array($field, $fields);
}





?>