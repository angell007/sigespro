<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$idpunto = ( isset( $_REQUEST['idPunto'] ) ? $_REQUEST['idPunto'] : '');
$codigo = ( isset( $_REQUEST['Barras'] ) ? $_REQUEST['Barras'] : '' );
$idinventario = ( isset( $_REQUEST['idpunto'] ) ? $_REQUEST['idpunto'] : '' );
$tipo=( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );

$query = 'SELECT  PRD.Id_Producto, IFNULL(CONCAT(PRD.Nombre_Comercial," (",PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion,") ", PRD.Cantidad," ", PRD.Unidad_Medida),CONCAT(PRD.Nombre_Comercial," LAB-",PRD.Laboratorio_Comercial)) as Nombre,
          PRD.Laboratorio_Comercial,
          PRD.Laboratorio_Generico,
          PRD.Cantidad_Presentacion,
          PRD.Embalaje,
          PRD.Imagen,
          PRD.Codigo_Cum,
          PRD.Mantis,
          PRD.Codigo_Barras
          FROM Producto PRD 
          WHERE PRD.Codigo_Barras = '.$codigo;

$oCon= new consulta();
//$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$producto = $oCon->getData();
unset($oCon);

$query="SELECT Inventario FROM Inventario_Fisico_Punto WHERE Id_Inventario_Fisico_Punto=$idinventario";
$oCon= new consulta();
$oCon->setQuery($query);
$inv = $oCon->getData();
unset($oCon);

if($producto){

    if($inv['Inventario']=="No"){
        $query = 'SELECT  I.Id_Producto, I.Lote, I.Fecha_Vencimiento, "" as Cantidad_Encontrada, I.Id_Producto_Inventario_Fisico
        FROM Producto_Inventario_Fisico_Punto I
        INNER JOIN Inventario_Fisico_Punto IP
        ON I.Id_Inventario_Fisico_Punto=IP.Id_Inventario_Fisico_Punto
        WHERE I.Id_Producto = '.$producto["Id_Producto"].' AND IP.Id_Punto_Dispensacion='.$idpunto.' AND I.Id_Inventario_Fisico_Punto='.$idinventario;
    }elseif ($inv['Inventario']=="Si") {
        $query = 'SELECT  I.Id_Producto, I.Lote, I.Fecha_Vencimiento, I.Cantidad as Cantidad_Inventario, I.Id_Inventario_Nuevo,
       "" as Cantidad_Encontrada
        FROM Inventario_Nuevo I 
        WHERE I.Id_Producto = '.$producto["Id_Producto"].' AND I.Id_Punto_Dispensacion='.$idpunto.' AND I.Cantidad>0';
    }

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
    $resultado["Titulo"]="Producto No Encontrado";
    $resultado["Texto"]="El Código de Barras Escaneado no coincide con ninguno de los 50.010 productos que tenemos registrados.";
}

echo json_encode($resultado);
?>