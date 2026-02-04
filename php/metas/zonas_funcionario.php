<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');


include_once('../../class/class.consulta.php');
$anio = (isset($_REQUEST['anio']) ? $_REQUEST['anio'] : '');

$query = 'SELECT Id_Zona, Nombre FROM Zona';

$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$tipos = ['Materiales' => 0, 'Medicamentos' => 0];

$oCon = new consulta();

$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$zonas = $oCon->getData();
unset($oCon);
$zonas_funcionario = [];



if ( validarMeta($anio) ) {



    foreach ($zonas as $key => $zona) {

        $query = '
    SELECT FZ.Id_Zona, FZ.Identificacion_Funcionario, CONCAT_WS(" ",F.Nombres, F.Apellidos) AS funcionario
    FROM Funcionario_Zona FZ
    INNER JOIN Funcionario F ON F.Identificacion_Funcionario = FZ.Identificacion_Funcionario
    WHERE EXISTS(
        SELECT PF.Id_Perfil_Funcionario
        FROM Perfil_Funcionario PF
        WHERE PF.Identificacion_Funcionario = FZ.Identificacion_Funcionario AND Id_Perfil = "46"
        )       
        AND FZ.Id_Zona = ' . $zona['Id_Zona'];
        $oCon = new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query);
        $zona_fun = $oCon->getData();
        unset($oCon);

        if ($zona_fun) {
            $zonas[$key]['Funcionarios'] = $zona_fun;
            armarMeses($zonas[$key]['Funcionarios']);
            $zonas_funcionario[] =  $zonas[$key];
        }

    }
    $res['type'] = 'success';
    $res['zonas'] = $zonas_funcionario;
    echo json_encode($res);
} else {
    $res['title'] = 'No es posible la opeación';
    $res['text'] = 'El año que seleccionó ya se ha registrado previamente';
    $res['type'] = 'error';
    echo json_encode($res);
}

function validarMeta($anio)
{
    $query = '
        SELECT Id_Metas FROM Metas WHERE Anio = ' . $anio;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $zona = $oCon->getData();

    return $zona ? false : true;
}

function armarMeses(&$funcionarios)
{
  

    global $meses, $tipos;
    foreach ($funcionarios as $key1 => $fun) {

        $funcionarios[$key1]['Meses'] = [];
        foreach ($meses as $key2 => $mes) {
            $tiposTemp = $tipos;
            $tiposTemp['Mes'] = $mes;
            $funcionarios[$key1]['Meses'][] =  $tiposTemp;
        }
    }
}
