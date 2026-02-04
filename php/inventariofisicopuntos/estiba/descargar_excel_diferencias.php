<?php 



header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
date_default_timezone_set("America/Bogota");


header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Inventario.xls"');
header('Cache-Control: max-age=0'); 

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');



$productos = isset($_REQUEST['producto']) ? $_REQUEST['producto'] : false;
$inv=isset($_REQUEST['id_doc_inventario']) ? $_REQUEST['id_doc_inventario'] : false;

$productos = (array) json_decode($productos, true);


foreach ($productos as $key => $p) {
    
    $productos[$key]['Costo']=GetCostoPromedio($p['Id_Producto']);
   
    $productos[$key]['Codigo_Cum']=GetCodigoCum($p['Id_Producto']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <table class="">
        <thead>
        <tr>
                <th style="color:cornflowerblue">Nombre Comercial</th>
                <th style="color:cornflowerblue">Producto</th>
                <th style="color:cornflowerblue">Cantidad Inventario</th>
                <th style="color:cornflowerblue">Primer Conteo</th>
                <th style="color:cornflowerblue">Diferencia</th>
                <th style="color:cornflowerblue">Segundo Conteo</th>
                <th style="color:cornflowerblue">Cantidad Final</th>
                <th style="color:cornflowerblue">Costo del Producto</th>
                <th style="color:cornflowerblue">Valor Inicial</th>
                <th style="color:cornflowerblue">Valor Final</th>
                <th style="color:cornflowerblue">Codigo Cum</th>
                <th style="color:cornflowerblue">Lote</th>
                <th style="color:cornflowerblue">Fecha Vencimiento</th>
                <th style="color:cornflowerblue">Laboratorio Comercial</th>
                
            </tr>
        </thead>
        <tbody>
           
                
              
                <?php  foreach ($productos as $key => $producto) :
                    if (  $producto["Costo"] ) {
                        # code...
                        $valor_inicial=$producto["Cantidad_Inventario"] * $producto["Costo"];
                        
                        $valor_final=$producto["Cantidad_Final"]*$producto["Costo"];
                    }
                ?>
               <tr>
                <td><?= $producto['Nombre_Comercial']?></td>
                <td><?= $producto["Nombre_Producto"]?></td>
                <td><?= $producto["Cantidad_Inventario"]?></td>
                <td><?= $producto['Cantidad_Encontrada']?></td>
                <td><?= (INT) $producto["Cantidad_Diferencial"]?></td>
                <td><?= $producto['Cantidad_Final']?></td>
                <td><?= $producto["Cantidad_Final"]?></td>
                <td><?= $producto["Costo"]?></td>
                <td><?= $valor_inicial?></td>
                <td><?= $valor_final?></td>
                
                <td style="text-align:'center';"><?= $producto["Codigo_Cum"]?></td>
                <td style="text-align:'center';"><?= $producto["Lote"]?></td>
                <td style="text-align:'center';" ><?= $producto["Fecha_Vencimiento"]?></td>
                <td style="text-align:'center';"><?= getLabComercial($producto["Id_Producto"])?></td>
                </tr>
                <?php endforeach ?>
         
        </tbody>
    </table>
</body>
</html>

<?php


    


  /*   
  //costos antiguos
  function GetCostoPromedio($id){

        $query="SELECT ROUND(AVG(Costo)) as Costo FROM Inventario_Nuevo WHERE Id_Producto=$id AND Id_Estiba!=0 ";
        $oCon= new consulta();
        $oCon->setQuery($query);
        $costo = $oCon->getData();
        unset($oCon);
       

        return $costo['Costo'];
    } */
    

    function GetCostoPromedio($id){

        $query="SELECT ROUND(AVG(Costo_Promedio)) as Costo FROM Costo_Promedio WHERE Id_Producto=$id";
       
        $oCon= new consulta();
        $oCon->setQuery($query);
        $costo = $oCon->getData();
        unset($oCon);
       

        return $costo['Costo'] ? $costo['Costo'] : 0;
    }
    function GetCodigoCum($id){
        $query="SELECT Codigo_Cum FROM Producto WHERE Id_Producto=$id  ";
        $oCon= new consulta();
        $oCon->setQuery($query);
        $cum = $oCon->getData();
        unset($oCon);
    
        return $cum['Codigo_Cum'];
    }
    function fecha($str)
    {
        $parts = explode(" ",$str);
        $date = explode("-",$parts[0]);
        return $date[2] . "/". $date[1] ."/". $date[0];
    }
    function getLabComercial($id_prod) {
        $query="SELECT Laboratorio_Comercial FROM Producto WHERE Id_Producto=$id_prod  ";
        $oCon= new consulta();
        $oCon->setQuery($query);
        $data = $oCon->getData();
        unset($oCon);
        
        return $data['Laboratorio_Comercial'];
    }
