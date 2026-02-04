<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');

 include_once('../../../../class/class.querybasedatos.php');
include_once('../../../../class/class.contabilizar.php');

$oItem = new QueryBaseDatos();
$contabilizacion = new Contabilizar(true);

$queryNota = "SELECT N.*, T.Id_Movimiento_Contable 
FROM Nota_Credito_Global N 
LEFT JOIN
    ( SELECT T1.Id_Registro_Modulo, T1.Numero_Comprobante, T1.Id_Movimiento_Contable 
    FROM Movimiento_Contable T1 
    WHERE Id_Modulo IN (34) AND Estado = 'Activo'
    GROUP BY T1.Numero_Comprobante) T ON T.Numero_Comprobante = N.Codigo 
    WHERE  T.Id_Movimiento_Contable IS NULL AND N.Tipo_Factura != 'Factura_Capita'";

$oItem->SetQuery($queryNota);
$notas = $oItem->ExecuteQuery('Multiple');

echo "<pre>";
var_dump($notas);
echo "</pre>";

//exit;

$i=0;


foreach ($notas as $nota) { $i++;
 
    $datos['Id_Registro']=$nota['Id_Nota_Credito_Global'];
    $datos['Tipo_Factura']= str_replace('_'," ",$nota['Tipo_Factura']);
    $datos['Nit'] = $nota['Id_Cliente'];
    
    
    if( $nota['Tipo_Factura'] == 'Factura_Capita' ){
          	//$subtotal = 0;
          
     
        $query = 'SELECT SUM(N.Precio_Nota_Credito * N.Cantidad) AS Subtotal
                    FROM Producto_Nota_Credito_Global N 
                    WHERE N.Id_Nota_Credito_Global = '.$nota['Id_Nota_Credito_Global'];
        $oCon = new consulta();
        $oCon->setQuery($query);
        $subtotal = $oCon->getData();
        $datos['Subtotal'] = $subtotal['Subtotal'];
    }	
  
    $contabilizar = new Contabilizar();
    $contabilizar->CrearMovimientoContable('Nota Credito Global',$datos);

  

    echo $i."------";
    echo $nota['Codigo'];
    echo "------<br><br>";

}

echo "Finalizó";


?>