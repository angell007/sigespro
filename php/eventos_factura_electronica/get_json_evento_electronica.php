<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

// require_once('../config/start.inc.php');
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
include_once '../../class/class.eventos_factura_electronica.php';

$fecha = (isset($_REQUEST['fini']) ? $_REQUEST['fini'] : '2022-01-01');

$fact = GetDocumento($tipo, $reso, $fecha);


$response['fact'] = $fact;
if ($fact) {
  if (contarCodigo($fact['Tipo'], $fact['Codigo']) == '1') {
    $query = "SELECT E.Codigo, R.Tipo_Resolucion , '$fact[Codigo]' AS Codigo_Evento, R.Id_Resolucion, R.resolution_id, R.Codigo as Cod_Res
        FROM  $fact[Tipo]  E
        INNER JOIN Resolucion R ON R.Id_Resolucion = E.Id_Resolucion AND R.Tipo_Resolucion = 'Resolucion_Electronica'

        WHERE E.Id_$fact[Tipo] = $fact[Id]
        ";


    $oCon = new consulta();
    $oCon->setQuery($query);
    $res = $oCon->getData();
    unlink('https://api-dian.sigesproph.com.co/api-dian/storage/app/xml/1/' . $res["resolution_id"] . '/' . $res['Cod_Res']  . $res['Codigo_Evento'] . '.xml');
  }
  if ($res) {
    $fe = new Eventos_Factura_Electronica("$fact[Tipo]", $fact['Id'], $res['Id_Resolucion']);
    $response['Json'] = $fe->GeneraJson();
  }
}
unset($oCon);
echo json_encode($response);

function GetDocumento($tipo, $res, $fecha)
{

  $query = "SELECT 
            Id_Acuse_Recibo_Factura as Id, Codigo, 'Acuse_Recibo_Factura' AS Tipo , 'invoice-received' AS Metodo ,  Id_Factura_Recibida, Fecha, Procesada
             FROM  Acuse_Recibo_Factura
           WHERE (Procesada IS NULL OR Procesada = 'false') AND DATE(Fecha) >= '$fecha'

            UNION ALL(
                SELECT Id_Acuse_Recibo_Bien_Servicio as Id, Codigo, 'Acuse_Recibo_Bien_Servicio' AS Tipo,  'receipt-good-or-service' AS Metodo ,   Id_Factura_Recibida, Fecha, Procesada
                FROM  Acuse_Recibo_Bien_Servicio
              WHERE (Procesada IS NULL OR Procesada = 'false') AND DATE(Fecha) >= '$fecha'

            )
            UNION ALL
            (
                SELECT Id_Aceptacion_Expresa_Factura as Id, Codigo, 'Aceptacion_Expresa_Factura' AS Tipo,  'express-acceptance' AS Metodo ,  Id_Factura_Recibida, Fecha, Procesada
                FROM  Aceptacion_Expresa_Factura
              WHERE (Procesada IS NULL OR Procesada = 'false') AND DATE(Fecha) >= '$fecha'

            )
            UNION ALL
            (
                SELECT Id_Rechazo_Factura as Id, Codigo, 'Rechazo_Factura' AS Tipo,   'invoice-rejected' AS Metodo ,  Id_Factura_Recibida, Fecha, Procesada
                FROM  Rechazo_Factura
              WHERE (Procesada IS NULL OR Procesada = 'false') AND DATE(Fecha) >= '$fecha'

            )
            UNION ALL
            (
                SELECT Id_Aceptacion_Tacita as Id, Codigo, 'Aceptacion_Tacita' AS Tipo,   'tacit-acceptance' AS Metodo ,  Id_Factura, Fecha, Procesada
                FROM  Aceptacion_Tacita
              WHERE (Procesada IS NULL OR Procesada = 'false') AND DATE(Fecha) >= '$fecha'

            )
            ORDER BY Fecha
            LIMIT 1
    ";
  $oCon = new consulta();
  $oCon->setQuery($query);
  $lista = $oCon->getData();
  unset($oCon);

  return $lista;
}

function contarCodigo($tipo, $codigo)
{
  $query = "SELECT COUNT(Id_$tipo) as Total 
  FROM $tipo 
  WHERE Codigo LIKE '$codigo'";
  $oCon = new consulta();
  $oCon->setQuery($query);
  return $oCon->getData()['Total'];
}
