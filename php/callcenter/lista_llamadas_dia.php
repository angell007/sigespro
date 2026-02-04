<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$fecha = ( isset( $_REQUEST['Fecha'] ) ? $_REQUEST['Fecha'] : date("Y-m-d") );
$user = ( isset( $_REQUEST['Usuario'] ) ? $_REQUEST['Usuario'] : "12345" );


$query='SELECT  R.Id_Remision, P.Id_Paciente,  R.Id_Destino, R.Fini_Rotativo,  R.FFin_Rotativo,
CONCAT_WS(" ", P.Primer_Nombre, P.Primer_Apellido) AS Paciente, 
P.Telefono,
P.EPS, 
(SELECT PT.Numero_Telefono FROM Paciente_Telefono PT WHERE PT.Id_Paciente = P.Id_Paciente LIMIT 1) AS Telefono2,
RC.Estado AS Estado_Anterior, RC.Observacion AS Observacion_Anterior,
RC_2.Estado AS Estado, RC_2.Observacion AS Observacion, RC_2.Fecha_Prox_Llamada AS Fecha_Prox_Llamada,
RC_2.Id_Remision_Callcenter AS Id_Remision_Callcenter_Actual,
RC.Id_Remision_Callcenter AS Id_Remision_Callcenter_Anterior,
IFNULL(PD.Nombre_Comercial,"Sin Producto") as Producto, IFNULL(RC.Id_Producto,0) AS Id_Producto,
IFNULL(D.Codigo,"Sin Dispensacion") as Dispensacion, IFNULL(RC.Id_Dispensacion,0) AS Id_Dispensacion,
IFNULL(D.Pendientes,"") as Pendientes_Dis,
A.Id_Auditoria
FROM Remision_Callcenter RC
INNER JOIN Paciente P ON P.Id_Paciente = RC.Id_Paciente
LEFT JOIN Remision R ON R.Id_Remision = RC.Id_Remision 
LEFT JOIN Producto PD ON PD.Id_Producto = RC.Id_Producto
LEFT JOIN Dispensacion D ON D.Id_Dispensacion = RC.Id_Dispensacion
LEFT JOIN Auditoria A ON A.Id_Dispensacion = D.Id_Dispensacion
LEFT JOIN Remision_Callcenter RC_2 ON RC_2.Id_Remision_Callcenter_Anterior = RC.Id_Remision_Callcenter
WHERE DATE(D.Fecha_Actual ) = "'.$fecha.'"';

/* $query = "   SELECT  Concat_WS(P.Primer_Nombre, P.Primer_Apellido) AS  Paciente,'' as Telefono2,
P.EPS, P.Telefono,  '' as Observacion_Anterior,

FROM Dispensacion D 
INNER JOIN Producto_Dispensacion PD ON PD.Id_Dispensacion = D.Id_Dispensacion
INNER JOIN Paciente P ON P.Id_Paciente = D.Numero_Documento
INNER JOIN Auditoria A ON A.Id_Dispensacion = D.Id_Dispensacion 
LEFT JOIN Producto PD ON PD.Id_Producto = RC.Id_Producto
";
 */
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$pacientes = $oCon->getData();
unset($oCon);


$i=-1;
foreach($pacientes as $item){ $i++;

    if($item["Dispensacion"]=="Sin Dispensacion"&&$item["Producto"]=="Sin Producto"){
        $query='SELECT DISTINCT(PRE.Id_Producto) as Id_Prod FROM Producto_Remision PRE WHERE PRE.Id_Remision = '.$item["Id_Remision"];
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $rem = $oCon->getData();
        unset($oCon);
        $in='';
    
    
        foreach($rem as $r){
            $query = 'SELECT Producto_Asociado FROM Producto_Asociado WHERE CONCAT(Producto_Asociado,",") LIKE "%'.$r['Id_Prod'].',%" LIMIT 1';
        
            $oCon = new consulta();
            $oCon->setQuery($query);
            $asociados = $oCon->getData();
            unset($oCon);
    
           /* if($asociados){
                $in=str_replace(" ","",$asociados['Producto_Asociado']).",";
            }else{
            */
                $in=$r['Id_Prod'].",";
            //}
        }
        
        $in = trim($in,",");
    
        $query = 'SELECT 
        PD.Id_Producto AS Productos, 
        CONCAT_WS(" ",D.Codigo,"-",DATE_FORMAT(D.Fecha_Actual,"%d/%m/%Y"),"-",PR.Nombre_Comercial) AS Nombre_Comercial,
        D.Codigo AS Dispensacion, 
        (PD.Cantidad_Formulada-PD.Cantidad_Entregada) AS Pendientes
        FROM Producto_Dispensacion PD 
        INNER JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion
        INNER JOIN Producto PR ON PR.Id_Producto = PD.Id_Producto
        WHERE PD.Id_Producto IN ('.$in.') 
          # AND (PD.Cantidad_Formulada-PD.Cantidad_Entregada)!=0 
          AND DATE(D.Fecha_Actual) BETWEEN "'.$item["FIni_Rotativo"].'" AND "'.$item["FFin_Rotativo"].'" 
          AND D.Id_Punto_Dispensacion = "'.$item["Id_Destino"].'"
          AND D.Numero_Documento = "'.$item["Id_Paciente"].'"
          ORDER BY D.Fecha_Actual ASC
        ';
    
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $productos = $oCon->getData();
        unset($oCon);
    
        $pacientes[$i]["Productos"]=$productos;
        $pend = 0;
        foreach($productos as $pr){
            $pend += $pr["Pendientes"];
        }
        $pacientes[$i]["Pendientes"]=$pend;
        
    }
    
}

$llamadas["llamadas"]=$pacientes;


echo json_encode($llamadas);
?>