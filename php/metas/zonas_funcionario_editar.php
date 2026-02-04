<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');


include_once('../../class/class.consulta.php');
$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');


$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$tipos = ['Valor_Materiales' => 0, 'Valor_Medicamentos' => 0];

    $query = 'SELECT 
    M.Id_Metas,
    M.Anio,
    M.Identificacion_Funcionario, 
    M.Objetivo_General ,
    M.Objetivo_Especifico,
    M.Directriz_Comercial ,
    SUM( IFNULL( Valor_Medicamentos , 0) ) AS Medicamento,
    SUM( IFNULL( Valor_Materiales  , 0 ) ) AS Material,
    UPPER(CONCAT(F.Nombres, " ", F.Apellidos)) as Funcionario
    FROM Metas M
    INNER JOIN Metas_Zonas MZ ON MZ.Id_Meta = M.Id_Metas
    INNER JOIN Objetivos_Meta OM ON OM.Id_Metas_Zonas = MZ.Id_Metas_Zonas
    INNER JOIN Funcionario F ON F.Identificacion_Funcionario = M.Identificacion_Funcionario
        WHERE Id_Metas = '.$id;


 $oCon = new consulta();
 $oCon->setQuery($query);
 $meta = $oCon->getData();

             
$query = 'SELECT Id_Zona, Nombre FROM Zona';
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$zonas = $oCon->getData();
unset($oCon);

$zonas_funcionario = [];


if ( !validarMeta($id) ) {

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
    $res['meta'] = $meta;

    echo  json_encode($res);

} else {
    $res['title'] = 'No es posible la opeación';
    $res['text'] = 'El año que seleccionó ya se ha registrado previamente';
    $res['type'] = 'error';
    echo json_encode($res);
}

function validarMeta($id)
{
    $query = '
        SELECT Id_Metas FROM Metas WHERE Id_Metas = ' . $id;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $zona = $oCon->getData();

    return $zona ? false : true;
}

function armarMeses(&$funcionarios)
{

    global $meses, $tipos , $id;
    foreach ($funcionarios as $key1 => $fun) {

        $funcionarios[$key1]['Meses'] = [];

            $query = '
            SELECT 
                   
                    O.Valor_Medicamentos,
                    O.Valor_Materiales,
                    O.Mes /* , DATE_FORMAT(STR_TO_DATE(O.Mes,"%m") ,"%M") as d */
                    FROM Objetivos_Meta O
                    INNER JOIN Metas_Zonas MZ ON MZ.Id_Metas_Zonas = O.Id_Metas_Zonas
                    INNER JOIN Funcionario F ON F.Identificacion_Funcionario = O.Identificacion_Funcionario
                        WHERE O.Identificacion_Funcionario = '.$fun['Identificacion_Funcionario'].'
                            AND MZ.Id_Meta = '.$id.' ORDER BY O.Id_Objetivos_Meta  ';
      
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $zona = $oCon->getData();
           if ($zona) {

                $funcionarios[$key1]['Meses'] = $zona;

           }else{

                $funcionarios[$key1]['Meses'] = [];
                $funcionarios[$key1]['Muevo'] = true;
                foreach ($meses as $key2 => $mes) {
                    $tiposTemp = $tipos;
                    $tiposTemp['Mes'] = $mes;
                    $funcionarios[$key1]['Meses'][] =  $tiposTemp;
                }
            
           }
    }
}
