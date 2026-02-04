<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != "") {
    if($condicion==''){
        $condicion .= " AND C.Nombre LIKE '%$_REQUEST[nom]%'";
    }
    
}

### LAS FACTURAS QUE SE REALIZARON EN SIGESPRO, SOLO DEBEN APARECER LAS QUE SE HICIERON DESDE EL AÑO 2019, LAS DEL AÑO 2018 SE CONTABILIZARON EN MANTIS.

$query = "SELECT
  R.Id_Cliente,
  R.Nombre, MAX(R.Dias_Mora) AS Dias_Mora,
  SUM(R.TOTAL) AS TOTAL
  FROM
    (SELECT 
        MC.Id_PLan_Cuenta,
        C.Id_Cliente,
        C.Nombre,
        MC.Fecha_Movimiento,
        IF(C.Condicion_Pago > 1,
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
        $condicion
        GROUP BY MC.Documento, C.Id_Cliente 
        ) R
        WHERE R.Total !=0

  GROUP BY R.Id_Plan_Cuenta, R.Id_Cliente 

  ORDER BY TOTAL DESC";

// echo $query; exit;
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 15; 
$numReg = count($resultado); 
$paginas = ceil($numReg/$tamPag); 
$limit = ""; 
$paginaAct = "";

if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') { 
  $paginaAct = 1; 
  $limit = 0; 
} else { 
  $paginaAct = $_REQUEST['pag']; 
  $limit = ($paginaAct-1) * $tamPag; 
}


$resultado = array_slice($resultado, $limit, $tamPag);

$respuesta['Lista']=$resultado;
$respuesta['Total']=number_format(0,2,".",",");
$respuesta['Pendientes']=0;
$respuesta['numReg'] = $numReg;

echo json_encode($respuesta);


?>