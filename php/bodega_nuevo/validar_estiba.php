<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$codigo_barras = ( isset( $_REQUEST['codigo_barras'] ) ? $_REQUEST['codigo_barras'] : '' );
$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
$Lugar = ( isset( $_REQUEST['Lugar'] ) ? $_REQUEST['Lugar'] : '' );
$idLugar = ( isset( $_REQUEST['idLugar'] ) ? $_REQUEST['idLugar'] : '' );


$cond = '';
if($Lugar == 'Punto_Dispensacion'){
    $cond  = ' AND Id_Punto_Dispensacion ='.$idLugar;
}
$query = "SELECT * FROM Estiba
        WHERE Codigo_Barras = '$codigo_barras' AND Estado != 'Inactivo' $cond";

$oCon= new consulta();
$oCon->setQuery($query);
$estiba = $oCon->getData();
unset($oCon);

if ($estiba) {

    if ($estiba['Estado'] == 'Disponible') {
        # code...
        
            $resultado['Tipo']='success';
            $resultado['Estiba']=$estiba;
            $resultado['Titulo'] = 'Estiba encontrada';
            $resultado['Mensaje'] = 'Producto agregado correctamente a la Estiba!';
    }elseif ($estiba['Estado'] == 'Inventario') {
        # code...
        
        $resultado['Tipo']='error';
        $resultado['Titulo']='La estiba asociada no está permitida';
        $resultado['Mensaje']='Se está realizando un inventario a la estiba';
    }
   
   

}else{
    $resultado['Tipo']='error';
    $resultado['Titulo']='No se encontró estiba';
    $resultado['Mensaje']='No existe una estiba registrada con ese código de barras, por favor verifique.';
}
echo json_encode($resultado);
