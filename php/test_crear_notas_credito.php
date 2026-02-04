<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');
require_once('../class/html2pdf.class.php');
include_once('../class/NumeroALetra.php');


include_once('../class/class.querybasedatos.php');
include_once('../class/class.http_response.php');
require_once('../class/class.configuracion.php');
//include_once('../class/class.mipres.php');

$queryObj = new QueryBaseDatos();


//NOPOS
$query = 'SELECT *, 
(SELECT SUM((PF.Cantidad*PF.Precio)-(PF.Cantidad*PF.Descuento)+(((PF.Cantidad*PF.Precio)-(PF.Cantidad*PF.Descuento))*(PF.Impuesto/100)))  FROM Producto_Factura PF WHERE PF.Id_Factura=F.Id_Factura) as Total_Factura
FROM Factura F WHERE F.Id_Factura IN( '.ids().' ) AND F.Id_Dispensacion IS NOT NULL  ORDER BY F.Fecha_Documento  ASC';


//VENTA
/*$query = 'SELECT *, 
(SELECT SUM((PF.Cantidad*PF.Precio_Venta)-(PF.Cantidad*PF.Descuento)+(((PF.Cantidad*PF.Precio_Venta)-(PF.Cantidad*PF.Descuento))*(PF.Impuesto/100)))  
FROM Producto_Factura_Venta PF WHERE PF.Id_Factura_Venta=F.Id_Factura_Venta) as Total_Factura
FROM Factura_Venta F WHERE F.Id_Factura_Venta IN( '.ids().' )   ORDER BY F.Fecha_Documento  ASC';
*/


$queryObj->SetQuery($query);
$datos = $queryObj->ExecuteQuery('Multiple');
echo '<pre>';
var_dump($datos);
echo '</pre>';
exit;
//19397,19905
function ids (){
    return '289567,289568,289569,289570,289571,289572,289573,289574,289575,289576,289577,289578,289579,289580,289581,289582,289583,289584,289585,289586,289587,289588,289589,289590,289591,289592,289593,289594,289595,289596,289597,289598,289599,289600,289601,289602,289603,289604,289605,289606,289607,289608,289609,289610,289611,289612,289613,289614,289615,289616,289617,289618,289619,289620,289621,289622,289623,289624,289625,289626,289627,289628,289629,289630,289631,289632,289633,289634,289635,289636,289637,289638,289639,289640';
}

//NCDFE1784
$i=1785;
foreach($datos as $dato){ 
          
    echo $dato["Total_Factura"]." - ".$dato["Codigo"]." - NCDFE".($i+1)."<br>";
    if($dato["Funcionario_Nota"]=="12345"||$dato["Funcionario_Nota"]==""){
        $func='1098624292';
    }else{
        $func=$dato["Funcionario_Nota"];
    }
    
    //NOPOS
    /*$query = 'SELECT PF.*, CONCAT_WS(" - ",P.Nombre_Comercial,P.Laboratorio_Comercial) as Nombre_Producto
    FROM Producto_Factura PF
    INNER JOIN Producto P ON P.Id_Producto = PF.Id_Producto
    WHERE PF.Id_Factura='.$dato["Id_Factura"];*/
    
    //VENTA
     $query = 'SELECT PF.*, CONCAT_WS(" - ",P.Nombre_Comercial,P.Laboratorio_Comercial) as Nombre_Producto
    FROM Producto_Factura_Venta PF
    INNER JOIN Producto P ON P.Id_Producto = PF.Id_Producto
    WHERE PF.Id_Factura_Venta='.$dato["Id_Factura_Venta"];
    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('Multiple');
  
    if(count($productos)>0){
        $i++;
    
        $oItem = new complex("Nota_Credito_Global","Id_Nota_Credito_Global");
        $oItem->Tipo_Factura="Factura_Venta";
        $oItem->Id_Factura=$dato["Id_Factura_Venta"];
        $oItem->Valor_Total_Factura=$dato["Total_Factura"]; 
        $oItem->Id_Funcionario=$func;
        $oItem->Id_Cliente=$dato["Id_Cliente"];
        $oItem->Codigo_Factura=$dato["Codigo"];
        $oItem->Codigo="NCDFE".$i;
        $oItem->Fecha=date("Y-m-25 H:i:s");
        $oItem->Observaciones='SE HACE NOTA CREDITO A FACTURA PORQUE FUE LIBERADA PARA REFACTURAR - MASIVO'; 
        $oItem->save();
        $id_nota=$oItem->getId();
        unset($oItem);
        
        foreach($productos as $prod){
            //NOPOS
            //$valor=($prod["Cantidad"]*$prod["Precio"])-($prod["Cantidad"]*$prod["Descuento"])+((($prod["Cantidad"]*$prod["Precio"])-($prod["Cantidad"]*$prod["Descuento"]))*($prod["Impuesto"]/100));
            //VENTA
            $valor=($prod["Cantidad"]*$prod["Precio_Venta"])-($prod["Cantidad"]*$prod["Descuento"])+((($prod["Cantidad"]*$prod["Precio_Venta"])-($prod["Cantidad"]*$prod["Descuento"]))*($prod["Impuesto"]/100));
            echo "-- ".$valor." - Producto: ".$prod["Nombre_Producto"]."<br>";
            $oItem = new complex("Producto_Nota_Credito_Global","Id_Nota_Credito_Global");
            $oItem->Id_Nota_Credito_Global = $id_nota;
            $oItem->Tipo_Producto="Producto_Factura_Venta";
            $oItem->Id_Producto = $prod["Id_Producto_Factura_Venta"];
            $oItem->Nombre_Producto = $prod["Nombre_Producto"];
            $oItem->Observacion = 'SE HACE NOTA CREDITO A FACTURA PORQUE FUE LIBERADA PARA REFACTURAR - MASIVO';
            $oItem->Valor_Nota_Credito=str_replace(",",".",$valor);
            $oItem->Impuesto=$prod["Impuesto"];
            $oItem->Precio_Nota_Credito = str_replace(",",".", ($prod["Precio_Venta"] - $prod["Descuento"]) ) ;
            $oItem->Cantidad=$prod["Cantidad"];
            $oItem->Id_Causal_Anulacion='21';
            $oItem->save();
            unset($oItem);
            
        }
        
        
        
        #------------->¡¡¡LIBERAR DIS !!!!<------------------
        
      /*  $oItem = new complex("Factura","Id_Factura",$dato['Id_Factura']);
        $oItem->Funcionario_Nota=$func;
        $oItem->Fecha_Nota=date("Y-m-28 H:i:s");
        $oItem->Nota_Credito = 'Si'; 
        $oItem->Valor_Nota_Credito = $dato["Total_Factura"]; 
        $oItem->save();
        unset($oItem);
        
        $query = 'UPDATE Dispensacion SET Id_Factura = NULL, Estado_Facturacion="Sin Facturar" WHERE Id_Dispensacion = '.$dato['Id_Dispensacion'];
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->createData(); 
        unset($oCon);
        */
        
        #------------------------------------------------------
        
        $query = 'SELECT * FROM Movimiento_Contable WHERE Numero_Comprobante = "'.$dato["Codigo"].'"';
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $movimientos = $oCon->getData(); 
        unset($oCon);
        
       // contabilizar
        
        foreach ($movimientos as $key => $mov){
            
            $oItem = new complex("Movimiento_Contable","Id_Movimiento_Contable");
            $oItem->Id_Plan_Cuenta = $mov['Id_Plan_Cuenta'];
            $oItem->Fecha_Movimiento = date("Y-m-28 H:i:s");
            $oItem->Id_Modulo = '34';
            $oItem->Id_Registro_Modulo = $id_nota;
            
            $oItem->Debe = $mov['Haber'];
            $oItem->Haber = $mov['Debe'];
            
            $oItem->Debe_Niif = $mov['Haber_Niif'];
            $oItem->Haber_Niif = $mov['Debe_Niif'];
            
            $oItem->Nit = $mov['Nit'];
            $oItem->Tipo_Nit = $mov['Tipo_Nit'];
            $oItem->Estado = $mov['Estado'];
            $oItem->Documento = "NCDFE".$i;
            $oItem->Detalles = $mov['Detalles'];
            $oItem->Fecha_Registro = date("Y-m-21 H:i:s");
            $oItem->Id_Centro_Costo = (INT)$mov['Id_Centro_Costo'];
            $oItem->Mantis = $mov['Mantis'];
            $oItem->Numero_Comprobante = "NCDFE".$i;
            $oItem->save();
            unset($oItem);
        }
        
        
        
    }else{
        echo "NO TIENE PRODUCTOS<br>";
    }
  
}


?>