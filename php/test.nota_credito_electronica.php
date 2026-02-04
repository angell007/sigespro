<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

// require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');
include_once('../class/class.nota_credito_electronica.php');


$fecha = (isset($_REQUEST['fini']) ? $_REQUEST['fini'] : '2022-01-01');


$tipo = (isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : 'Nota_Credito_Global'); 
$reso = (isset($_REQUEST['res']) ? $_REQUEST['res'] : '24');


$facts = GetNotas($tipo, $reso, $fecha);

//echo '<pre>';
// var_dump($facts);exit; 


$response['fact'] = $facts[0];
foreach($facts as $fac){
   
        $query="SELECT F.Codigo, R.Tipo_Resolucion , '$fac[Codigo]' AS Codigo_Nota
        FROM  $fac[Tipo_Factura]  F
        INNER JOIN Resolucion R ON R.Id_Resolucion = F.Id_Resolucion AND R.Tipo_Resolucion = 'Resolucion_Electronica' 

        WHERE F.Id_$fac[Tipo_Factura] = $fac[Id_Factura]         
        " ; 
     
        $oCon = new consulta();
        $oCon->setQuery($query);
        $lista = $oCon->getData();
        
        if ($lista) {
        //  $x++;
        // echo $x.' : ' . $fac['Tipo'].'  -  '.$fac['Id_Nota'].'  <br>  ';

        $fe = new NotaCreditoElectronica($fac['Tipo'], $fac["Id_Nota"], $reso);
        $response['nota'] = $fe->GenerarNota();
        }
        unset($oCon);
    echo json_encode($response);
}

function GetNotas($tipo, $res, $fecha)
{

    $query = "SELECT Id_Nota_Credito as Id_Nota , Codigo, 'Nota_Credito' AS Tipo , 'Factura_Venta'  AS Tipo_Factura ,   Id_Factura, Fecha, Procesada  
             FROM  Nota_Credito
            WHERE (Procesada IS NULL OR Procesada = 'false') AND DATE(Fecha) >= '$fecha'
            
            UNION ALL(
                SELECT Id_Nota_Credito_Global as Id_Nota, Codigo, 'Nota_Credito_Global' AS Tipo, Tipo_Factura , Id_Factura, Fecha, Procesada
                FROM  Nota_Credito_Global
              
               WHERE (Procesada IS NULL OR Procesada = 'false') AND DATE(Fecha) >= '$fecha'
               
            )
            ORDER BY Fecha DESC
            LIMIT 1
    "; 
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo("Multiple");
    $lista = $oCon->getData();
    unset($oCon);

    return $lista;
}
?>