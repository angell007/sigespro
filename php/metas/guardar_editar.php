<?php 
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');


include_once('../../class/class.consulta.php');
include_once('../../class/class.complex.php');

$mesesG = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

$Id_Meta = (isset($_REQUEST['Id_Meta']) ? $_REQUEST['Id_Meta'] : '');
$zonas = (isset($_REQUEST['Zonas']) ? $_REQUEST['Zonas'] : '');




$zonas = json_decode( $zonas, true );

deleteZonaMeta();

foreach ($zonas as $key1 => $zona) {

    $oItem = new complex('Metas_Zonas','Id_Metas_Zonas');  
    $oItem->Id_Meta = "'$Id_Meta'";
    $oItem->Id_Zona = $zona['Id_Zona'];
    $oItem->save();
    $idMetaZona = $oItem->getId();
    unset($oItem);


    foreach ($zona['Funcionarios'] as $key2 => $funcionario) {
        
        foreach ($funcionario['Meses'] as $key => $mes ) {
            # code...
            //var_dump($mes);exit;
            $oItem = new complex('Objetivos_Meta','Id_Objetivos_Meta');
            $oItem->Id_Metas_Zonas = $idMetaZona;
            $oItem->Mes = $mesesG[$key];
            $oItem->Valor_Materiales = number_format($mes['Valor_Materiales'],2,".","");;
            $oItem->Valor_Medicamentos = number_format($mes['Valor_Medicamentos'],2,".","");
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

function deleteZonaMeta(){
    global $Id_Meta;

    $query = ' SELECT GROUP_CONCAT(Id_Metas_Zonas) AS Id_Metas_Zonas
             FROM Metas_Zonas WHERE Id_Meta = '.$Id_Meta.'
             GROUP BY Id_Meta';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $metasZonasDelete = $oCon->getData();
    unset($oCon);

    $query = 'DELETE FROM Metas_Zonas WHERE Id_Meta = '.$Id_Meta;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $zona = $oCon->createData();

    unset($oCon);

    $query = 'DELETE FROM Objetivos_Meta WHERE Id_Metas_Zonas IN ( '.$metasZonasDelete['Id_Metas_Zonas'].' )';
   
    $oCon = new consulta();
    $oCon->setQuery($query);
    $obj = $oCon->createData();
    unset($oCon);


}