<?php 
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');


include_once('../../class/class.consulta.php');
include_once('../../class/class.complex.php');

$mesesG = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

$meta = (isset($_REQUEST['Meta']) ? $_REQUEST['Meta'] : '');
$zonas = (isset($_REQUEST['Zonas']) ? $_REQUEST['Zonas'] : '');

$meta = json_decode( $meta, true );

$zonas = json_decode( $zonas, true );


$oItem = new complex('Metas','Id_Metas');
$oItem->Anio = $meta['Anio'];
$oItem->Objetivo_General = $meta['Objetivo_General'];
$oItem->Objetivo_Especifico = $meta['Objetivo_Especifico'];
$oItem->Directriz_Comercial = $meta['Directriz_Comercial'];
$oItem->Identificacion_Funcionario = $meta['Funcionario']['Identificacion_Funcionario'];

$oItem->save();
$idMeta = $oItem->getId();
unset($oItem);
/* 
number_format($producto['Precio_Nota'],2,".", ; */
foreach ($zonas as $key1 => $zona) {
    $oItem = new complex('Metas_Zonas','Id_Metas_Zonas');
    $oItem->Id_Meta = $idMeta;
    $oItem->Id_Zona = $zona['Id_Zona'];

    $oItem->save();
    $idMetaZona = $oItem->getId();
    unset($oItem);

    foreach ($zona['Funcionarios'] as $key2 => $funcionario) {
        
        foreach ($funcionario['Meses'] as $key => $mes ) {
            # code...
          
            $oItem = new complex('Objetivos_Meta','Id_Objetivos_Meta');
            $oItem->Id_Metas_Zonas = $idMetaZona;
            $oItem->Mes = $mesesG[$key];
            $oItem->Valor_Materiales = number_format($mes['Materiales'],2,".","");
            $oItem->Valor_Medicamentos = number_format($mes['Medicamentos'],2,".","");
            $oItem->Identificacion_Funcionario = $funcionario['Identificacion_Funcionario'];
            $oItem->save();
            unset($oItem);

          

        }
    }

}


$res['title'] = 'Operación exitosa';
$res['text'] = 'Se guardó la meta correctamente';
$res['type'] = 'success';
echo json_encode($res);