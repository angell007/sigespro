<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$idCategoria = ( isset( $_REQUEST['idCategoria'] ) ? $_REQUEST['idCategoria'] : '');
$idbodega = ( isset( $_REQUEST['idBodega'] ) ? $_REQUEST['idBodega'] : '');
$Letras = ( isset( $_REQUEST['Letras'] ) ? $_REQUEST['Letras'] : '' );
$codigo = ( isset( $_REQUEST['Barras'] ) ? $_REQUEST['Barras'] : '' );
$codigo1=substr($codigo,0,12);

$query = 'SELECT SUBSTRING(PRD.Nombre_Comercial, 1, 1) AS Inicial_1, SUBSTRING(PRD.Principio_Activo, 1, 1) AS Inicial_2, PRD.Id_Producto, IFNULL(CONCAT(PRD.Nombre_Comercial," (",PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion,") ", PRD.Cantidad," ", PRD.Unidad_Medida),CONCAT(PRD.Nombre_Comercial," LAB-",PRD.Laboratorio_Comercial)) as Nombre,
          PRD.Laboratorio_Comercial,
          PRD.Laboratorio_Generico,
          PRD.Cantidad_Presentacion,
          PRD.Embalaje,
          PRD.Id_Categoria,
          PRD.Imagen,
          PRD.Codigo_Cum,
          PRD.Mantis,
          PRD.Codigo_Barras
          FROM Inventario I
          INNER JOIN Producto PRD
          ON PRD.Id_Producto=I.Id_Producto
          WHERE PRD.Codigo_Barras = "'.$codigo .'" OR I.Codigo="'.$codigo1 .'" OR I.Alternativo LIKE "%' . $codigo1 .'%"
          GROUP BY PRD.Id_Producto';

$oCon= new consulta();
//$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$producto = $oCon->getData();
unset($oCon);

if($producto){
    
    if($producto["Id_Categoria"]!=$idCategoria && $idCategoria!=0){
        
        if($producto["Id_Categoria"]!=""){
            $oItem = new complex("Categoria","Id_Categoria",$producto["Id_Categoria"]);
            $cate = $oItem->getData();
            unset($oItem);
            $cat=$cate["Nombre"];
        }else{
            $cat="No Tiene";
        }
        $resultado["Tipo"]="error";
        $resultado["Titulo"]="Categoría No Coincide";
        $resultado["Texto"]="La Categoría del Producto Escaneado no coincide con la Categoría Inventariada<br><strong>Categoría del Producto Escaneado: ".$cat."</strong>";
        
    }else{
        if($idCategoria==12){
            $pos =strpos($Letras, $producto["Inicial_2"]);
        }else{
            $pos =strpos($Letras, $producto["Inicial_1"]);
        }
        
        
        if($pos!==false){
            $query = 'SELECT I.Codigo, I.Id_Inventario, I.Id_Producto, I.Lote, I.Fecha_Vencimiento, I.Cantidad, (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) as Cantidad_Final, "" as Cantidad_Encontrada
            FROM Inventario I
            WHERE I.Id_Producto = '.$producto["Id_Producto"].' AND I.Id_Bodega='.$idbodega.' AND I.Cantidad>0 ORDER BY I.Fecha_Vencimiento ASC';
            
            $oCon= new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
            $lotes = $oCon->getData();
            unset($oCon);
            
            $producto["Lotes"]=$lotes;
            if(count($lotes)>0){
                $msj="Se encontraron ".count($lotes)." Lotes de este Producto".$pos;
            }else{
                $msj="No se encontraron Lotes de este Producto, Agregue uno nuevo si consiguió";
            }
            $producto["Mensaje"]=$msj;
            
            $resultado["Tipo"]="success";
            $resultado["Datos"]=$producto;
        }else{
            $resultado["Tipo"]="error";
            $resultado["Titulo"]="No en Rango de Letras";
            $resultado["Texto"]="El Nombre del Producto no se encuentra en el Rango de Letras '$Letras' que esta Inventariando.<br>Inicial del Producto: ".$producto["Inicial"];
        }
        
    }
    
}else{
    $resultado["Tipo"]="error";
    $resultado["Titulo"]="Producto No Encontrado";
    $resultado["Texto"]="El Código de Barras Escaneado no coincide con ninguno de los 50.010 productos que tenemos registrados.";
}







echo json_encode($resultado);

?>