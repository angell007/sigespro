<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');


include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

/* 
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Edades Cartera Cliente.xls"');
header('Cache-Control: max-age=0'); 
 */


$nit = isset($_REQUEST['nit']) ? $_REQUEST['nit'] : 0 ;
$fecha1 = isset($_REQUEST['fecha1']) ? $_REQUEST['fecha1'] : 0 ;
$fecha2 = isset($_REQUEST['fecha2']) ? $_REQUEST['fecha2'] : 0 ;
$facturas = [];

if($nit){
    

$facturas = getFacturas($nit);

foreach ($facturas as $key => &$factura) {


  $factura['Notas_Credito'] = getNotasCredito($factura);
  if($factura['Tipo'] == 'Factura'){
    $factura['Abonos'] = getAbonosDis($factura, $nit);
    $factura['Descuentos'] = 0;
    
  }else{

    $factura['Abonos'] = getAbonos($factura, $nit);
    $factura['Descuentos'] = getDescuentos($factura, $nit);
  }
  $factura['Glosas'] = getGlosas($factura);
  $factura['Saldo'] = getSaldo($factura);

  if ($factura['Saldo'] == 0) {
    unset($facturas[$key]);
  }
}

}
createHtml($facturas);
/* echo json_encode($facturas); */

function createHtml($facturas)
{
  $table = '
  <table style="margin-top:10px;font-size:10px;" cellpadding="0" cellspacing="0">
      <tr>
          <td style="background:#ededed;text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
            Factura
          </td>
          <td style="background:#ededed;text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
            Valor Factura
          </td>
          <td style="background:#ededed;text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
            Fecha Factura
          </td>
          <td style="background:#ededed;text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
            Notas Credito
          </td>
          <td style="background:#ededed;text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
            Valor Notas Credito
          </td>
          <td style="background:#ededed;text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
              Abonos
          </td>
          <td style="background:#ededed;text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
              Valor Abonos
          </td>
          <td style="background:#ededed;text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
            Descuentos
          </td>
          <td style="background:#ededed;text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
              Valor Descuentos
          </td>
          <td style="background:#ededed;text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
            Glosas
          </td>
          <td style="background:#ededed;text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
              Valor Glosas
          </td>
          <td style="background:#ededed;text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
              Saldo
          </td>
      </tr>
     ';

     foreach ($facturas as $key => $factura) {
       # code...
       $table .= '
       <tr>
       <td style="text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
         '.$factura['Factura'].'
       </td>
       <td style="text-align:right;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
       '.$factura['Neto_Factura'].'
       </td>
       <td style="text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
       '.$factura['Fecha'].'
       </td>

       <td style="text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
       '.($factura['Notas_Credito']['Codigo'] ? $factura['Notas_Credito']['Codigo'] : '').'
       </td>
       <td style="text-align:right;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
       '.($factura['Notas_Credito']['Neto'] ? $factura['Notas_Credito']['Neto'] : 0).'
       </td>

       <td style="text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
       '.($factura['Abonos']['Codigo'] ? $factura['Abonos']['Codigo'] : '').'
       </td>
       <td style="text-align:right;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
       '.($factura['Abonos']['Neto'] ? $factura['Abonos']['Neto'] : 0).'
       </td>

       <td style="text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
       '.($factura['Descuentos']['Codigo'] ? $factura['Descuentos']['Codigo'] : '').'
       </td>
       <td style="text-align:right;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
       '.($factura['Descuentos']['Neto'] ? $factura['Descuentos']['Neto'] : 0).'
       </td>

       <td style="text-align:center;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
       '.($factura['Glosas']['Codigo'] ? $factura['Glosas']['Codigo'] : '').'
       </td>
       <td style="text-align:right;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
       '.($factura['Glosas']['Neto'] ? $factura['Glosas']['Neto'] : 0).'
       </td>
       <td style="text-align:right;width:180px;max-width:180px;font-weight:bold;border:1px solid #cccccc;">
       '.($factura['Saldo']).'
       </td>
   </tr>
       ';
     }
     echo $table;
}


function getSaldo($factura)
{

  $Notas_Credito =  $factura['Notas_Credito']['Neto']  ;
  $Abonos =  $factura['Abonos']['Neto'] ;
  $Glosas =  $factura['Glosas']['Neto'] ;
  $Descuentos =  $factura['Descuentos']['Neto'] ;

  return  $factura['Neto_Factura']
    - $factura['Notas_Credito']['Neto'] 
    - $factura['Abonos']['Neto']
    -  $factura['Glosas']['Neto'] 
    - $factura['Descuentos']['Neto'];
}

function getGlosas($factura)
{
  $glosas = 0;
  if ($factura['Tipo'] == 'Factura') {
    $query = 'SELECT 
    GROUP_CONCAT( DISTINCT  R.Codigo ) AS Codigo,
    SUM(IFNULL( GF.Valor_Glosado,0 ) ) AS Neto
     FROM Glosa_Factura GF
     INNER JOIN  Radicado_Factura RF
     ON  RF.Id_Radicado_Factura =  GF.Id_Radicado_Factura 
     INNER JOIN Radicado R ON R.Id_Radicado = RF.Id_Radicado
     WHERE RF.Id_Factura = ' . $factura['Id'] . '
     AND R.Estado != "Anulada"
     GROUP BY    RF.Id_Factura
    ';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $glosas =  $oCon->getData();
  }
  return $glosas;
}

function getAbonos($factura, $nit)
{
  $query = 'SELECT 
                   GROUP_CONCAT( DISTINCT  C.Codigo ) AS Codigo,
                   SUM(IFNULL( FC.Valor,0 ) ) AS Neto
                    FROM Comprobante C
                    INNER JOIN Factura_Comprobante FC
                    ON FC.Id_Comprobante = C.Id_Comprobante 
                        WHERE FC.FACTURA LIKE "' . $factura['Factura'] . '"
                        AND FC.Id_Factura = ' . $factura['Id'] . '
                        AND C.Id_Cliente = ' . $nit . '
                        AND C.Estado = "Activa"
                        GROUP BY    FC.Id_Factura
                   ';
  $oCon = new consulta();
  $oCon->setQuery($query);
  $abonos = $oCon->getData();
  return  $abonos ? $abonos : 0;
}

function getAbonosDis($factura,$nit){
    $query = 'SELECT   GROUP_CONCAT( DISTINCT  DC.Codigo ) AS Codigo,
    SUM(IFNULL( CD.Credito,0 ) ) AS Neto
    FROM Cuenta_Documento_Contable  CD 
    INNER JOIN Documento_Contable DC ON DC.Id_Documento_Contable  = CD.Id_Documento_Contable
    WHERE  CD.Documento = "' . $factura['Factura'] . '"
                        AND CD.Nit = ' . $nit . '
                        AND DC.Estado = "Activo"
                        AND CD.Id_Plan_Cuenta = 57
                        AND CD.Credito != 0
                        GROUP BY    CD.Documento
    ';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $abonos =  $oCon->getData();
    return $abonos ? $abonos : 0;
}
function getDescuentos($factura, $nit)
{
  $query = 'SELECT 
                   GROUP_CONCAT( DISTINCT  C.Codigo ) AS Codigo,
                   SUM(IFNULL( FC.Valor,0 ) ) AS Neto
                    FROM Comprobante C
                    INNER JOIN Descuento_Comprobante FC
                    ON FC.Id_Comprobante = C.Id_Comprobante 
                        AND FC.Id_Factura = ' . $factura['Id'] . '
                        AND C.Id_Cliente = ' . $nit . '
                        AND C.Estado = "Activa"
                        GROUP BY    FC.Id_Factura
                   ';
  $oCon = new consulta();
  $oCon->setQuery($query);
  $oCon->setTipo('Multiple');
  $descuentos =  $oCon->getData();
  return $descuentos ? $descuentos : 0;
}

function getNotasCredito($factura)
{
  $nc = [];
  if ($factura['Tipo'] == 'Factura_Venta') {
    $query = '
    SELECT
    GROUP_CONCAT( DISTINCT NC.Codigo  )  as Codigo,

          (SUM(  IFNULL(
          ( PDC.Cantidad * PDC.Precio_Venta  ) + 
             ROUND( (PDC.Cantidad * PDC.Precio_Venta ) 
             *  (PDC.Impuesto/100), 2),0) )
          ) as Neto 		
 
    FROM Nota_Credito NC   
    INNER JOIN Producto_Nota_Credito PDC ON PDC.Id_Nota_Credito = NC.Id_Nota_Credito
    WHERE NC.Id_Factura= ' . $factura['Id'] . '
    AND  NC.Estado != "Anulada"
    GROUP BY NC.Id_Factura
    ';
    $oCon = new consulta();
    $oCon->setQuery($query);

    $nc =  $oCon->getData();
  }
  unset($oCon);

  if (!$nc) {
    $query = '
    SELECT
     GROUP_CONCAT( DISTINCT  NC.Codigo ) as Codigo,
  
          ( SUM(
                IFNULL(
                  PDC.Valor_Nota_Credito ,0)
            ) 
          ) as Neto 		
  
    FROM Nota_Credito_Global NC   
    INNER JOIN Producto_Nota_Credito_Global PDC
     ON PDC.Id_Nota_Credito_Global = NC.Id_Nota_Credito_Global
    WHERE NC.Id_Factura= ' . $factura['Id'] . '
    AND Tipo_Factura = "' . $factura['Tipo'] . '"
    GROUP BY NC.Id_Factura
    ';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $nc =  $oCon->getData();
  }

  return $nc;
}

function getFacturas($nit)
{
    global $fecha1,$fecha2;

  $query = '
  SELECT 
    F.Codigo AS Factura , "Factura_Venta" AS Tipo,
    F.Id_Factura_Venta as Id, F.Fecha_Documento as Fecha,
    (							
        SUM(IFNULL( ( PFV.Cantidad*PFV.Precio_Venta )
         + ROUND( (PFV.Cantidad*PFV.Precio_Venta)*(PFV.Impuesto/100) ,2 ) , 0 ))
    )   AS Neto_Factura
  
  FROM Factura_Venta F
  
  INNER JOIN Producto_Factura_Venta PFV 
  ON PFV.Id_Factura_Venta = F.Id_Factura_Venta
  WHERE F.Estado != "Anulada" AND F.Id_Cliente = ' . $nit . '   
  AND DATE(F.Fecha_Documento) BETWEEN "'.$fecha1.'" AND "'.$fecha2.'"
  GROUP BY F.Id_Factura_Venta
  HAVING Neto_Factura != 0
  
  
  UNION ALL (
  SELECT 
    F.Codigo AS Factura , "Factura" AS Tipo,
    F.Id_Factura as Id, F.Fecha_Documento as Fecha,
    ( (SUM(  IFNULL(
    ( PF.Cantidad * PF.Precio - ( PF.Cantidad * PF.Descuento) ) + 
       ROUND( (PF.Cantidad * PF.Precio - ( PF.Cantidad * PF.Descuento ) ) 
       *  (PF.Impuesto/100), 2),0) ))- F.Cuota
    ) as Neto_Factura 		
    FROM Factura F
    INNER JOIN Producto_Factura PF 
    ON PF.Id_Factura = F.Id_Factura
    
    WHERE F.Estado_Factura != "Anulada"
     AND F.Id_Cliente = ' . $nit . '  
     AND DATE(F.Fecha_Documento) BETWEEN "'.$fecha1.'" AND "'.$fecha2.'"
     GROUP BY F.Id_Factura
     HAVING Neto_Factura != 0
      
  )
  UNION ALL (
    SELECT
    F.Codigo AS Factura,  "Factura_Capita" AS Tipo, 
    F.Id_Factura_Capita as Id, F.Fecha_Documento as Fecha, 
     ( (DFC.Cantidad * DFC.Precio)-F.Cuota_Moderadora)  AS Neto_Factura
  
    FROM
    Descripcion_Factura_Capita DFC
    INNER JOIN Factura_Capita F ON DFC.Id_Factura_Capita = F.Id_Factura_Capita
    WHERE F.Estado_Factura != "Anulada"
     AND F.Id_Cliente = ' . $nit . '    
     AND DATE(F.Fecha_Documento) BETWEEN "'.$fecha1.'" AND "'.$fecha2.'"
     GROUP BY F.Id_Factura_Capita
     HAVING Neto_Factura != 0
     
  )
  UNION ALL(
    SELECT
      F.Codigo AS Factura,  "Factura_Administrativa" AS Tipo, 
      F.Id_Factura_Administrativa as Id, F.Fecha as Fecha, 
      SUM( ( DFA.Cantidad * DFA.Precio - ( DFA.Cantidad * DFA.Descuento) ) + 
      ROUND( (DFA.Cantidad * DFA.Precio - ( DFA.Cantidad * DFA.Descuento) )  * 
      (DFA.Impuesto/100), 2) )  AS Neto_Factura
    
      FROM
      Descripcion_Factura_Administrativa DFA
      INNER JOIN Factura_Administrativa F
      ON DFA.Id_Factura_Administrativa = F.Id_Factura_Administrativa
      WHERE F.Estado_Factura != "Anulada"
       AND F.Id_Cliente = ' . $nit . '   
       AND DATE(F.Fecha_Documento) BETWEEN "'.$fecha1.'" AND "'.$fecha2.'"
       GROUP BY F.Id_Factura_Administrativa
       HAVING Neto_Factura != 0
  
  )
  Order By Fecha
  ';


  $oCon = new consulta();
  $oCon->setQuery($query);
  $oCon->setTipo('Multiple');
  return $oCon->getData();
}
