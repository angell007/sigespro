<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT P.Id_Producto, P.Nombre_Comercial,
IF(CONCAT( P.Principio_Activo, " ",
        P.Presentacion, " ",
        P.Concentracion, " (", P.Nombre_Comercial,") ",
        P.Cantidad," ",
        P.Unidad_Medida, " LAB-", P.Laboratorio_Comercial )="" OR CONCAT( P.Principio_Activo, " ",
        P.Presentacion, " ",
        P.Concentracion, " (", P.Nombre_Comercial,") ",
        P.Cantidad," ",
        P.Unidad_Medida, " LAB-", P.Laboratorio_Comercial ) IS NULL, CONCAT(P.Nombre_Comercial," LAB-", P.Laboratorio_Comercial), CONCAT( P.Principio_Activo, " ",
        P.Presentacion, " ",
        P.Concentracion, " (", P.Nombre_Comercial,") ",
        P.Cantidad," ",
        P.Unidad_Medida, " LAB-", P.Laboratorio_Comercial )) AS Nombre, PR.Cantidad, PR.Lote, P.Codigo_Cum ,R.FIni_Rotativo, R.FFin_Rotativo, R.Id_Destino,
        PR.Id_Producto

FROM Producto_Remision PR 
INNER JOIN Producto P ON P.Id_Producto = PR.Id_Producto
INNER JOIN Remision R ON R.Id_Remision = PR.Id_Remision
WHERE PR.Id_Remision = '.$id.'
GROUP BY PR.Id_Producto
';

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

$i=-1;

$cont = 0;
foreach($productos as $item){ $i++;

   /* $cum = explode("-",$item["Codigo_Cum"]);

    $query = 'SELECT Producto_Asociado FROM Producto_Asociado WHERE CONCAT(Producto_Asociado,",") LIKE "%'.$item['Id_Producto'].',%" LIMIT 1';
    
    $oCon = new consulta();
    $oCon->setQuery($query);
    $asociados = $oCon->getData();
    unset($oCon);

    if($asociados){
        $in=str_replace(" ","",$asociados['Producto_Asociado']);
    }else{
    */
        $in=$item['Id_Producto'];
    //}

    $query = 'SELECT CONCAT_WS(" ",P.Primer_Nombre, P.Primer_Apellido) AS Nombre, 
    GROUP_CONCAT(CONCAT_WS(" ", D.Codigo,"-",DATE_FORMAT(D.Fecha_Actual,"%d/%m/%Y"),"-",D.Id_Dispensacion,"-",(PD.Cantidad_Formulada-PD.Cantidad_Entregada),"-",D.Pendientes)) AS Dis, 
    P.Id_Paciente, P.EPS, P.Telefono, (SELECT PT.Numero_Telefono FROM Paciente_Telefono PT WHERE PT.Id_Paciente = P.Id_Paciente LIMIT 1) AS Telefono2, 
    IFNULL(RC.Pendientes,SUM(PD.Cantidad_Formulada-PD.Cantidad_Entregada)) AS Pendientes, RC.Id_Paciente AS IdPaciente, RC.Estado, RC.Observacion, RC.Fecha_Prox_Llamada, RC.Id_Remision_Callcenter, DA.Codigo AS Dis_Asignada 
    FROM Producto_Dispensacion PD
    INNER JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion
    INNER JOIN Paciente P ON P.Id_Paciente = D.Numero_Documento
    LEFT JOIN Remision_Callcenter RC ON P.Id_Paciente = RC.Id_Paciente AND RC.Id_Remision = '.$id.'
    LEFT JOIN Dispensacion DA ON DA.Id_Dispensacion = RC.Id_Dispensacion
    WHERE PD.Id_Producto IN ('.$in.')
    AND D.Id_Punto_Dispensacion = '.$item['Id_Destino'].'
    AND DATE(D.Fecha_Actual) BETWEEN "'.$item['FIni_Rotativo'].'" AND "'.$item['FFin_Rotativo'].'"
    GROUP BY D.Numero_Documento
    HAVING Pendientes > 0
    ORDER BY D.Fecha_Actual ASC
    ';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $pacientes = $oCon->getData();
    unset($oCon);

    
    $h=-1;
    foreach($pacientes as $pac){ $h++;
        $dis=explode(",",$pac["Dis"]);
        $disp=[];
        $zz=-1;
        foreach($dis as $d){ $zz++;
            $t = explode(" - ",$d);
            $disp[$zz]["Codigo"]=$t[0];
            $disp[$zz]["Fecha"]=$t[1];
            $disp[$zz]["Id_Dispensacion"]=$t[2];
            $disp[$zz]["Cantidad"]=$t[3];
            $disp[$zz]["Pendientes"]=$t[4];
        }
        $pacientes[$h]["Dispensaciones"]=$disp;
        
    }

    $productos[$i]["Pacientes"]=$pacientes;

    $cont+=count($pacientes);
}

$j=-1;
foreach($productos as $item){ $j++;
    $productos[$j]["Total_Pacientes"]=$cont;
}
$llamadas["llamadas"]=$productos;


echo json_encode($llamadas);
?>