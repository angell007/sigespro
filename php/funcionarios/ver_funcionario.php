<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT F.*,IFNULL((SELECT Nombre FROM Contrato_Funcionario CF INNER JOIN  Municipio M On CF.Id_Municipio=M.Id_Municipio WHERE  CF.Estado="Activo" AND CF.Identificacion_Funcionario=F.Identificacion_Funcionario ),"Sin Municipio") as Municipio FROM Funcionario F   WHERE F.Identificacion_Funcionario= '.$id;


$oCon= new consulta();
$oCon->setQuery($query);
$detalle = $oCon->getData();
unset($oCon);


$oItem = new complex("Funcionario_Contacto_Emergencia","Identificacion_Funcionario",$id);
$funcionarioCE= $oItem->getData();
unset($oItem);

$oLista = new lista("Funcionario_Experiencia_Laboral");
$oLista->setRestrict("Identificacion_Funcionario","=",$id);
$funcionarioEL= $oLista->getlist();
unset($oLista);

$oLista = new lista("Funcionario_Referencia_Personal");
$oLista->setRestrict("Identificacion_Funcionario","=",$id);
$referencia= $oLista->getlist();
unset($oLista);

$oItem=new complex("Cargo", "Id_Cargo",$detalle["Id_Cargo"]);
$cargo=$oItem->getData();
unset($oItem);

$oItem=new complex("Dependencia", "Id_Dependencia",$detalle["Id_Dependencia"]);
$dependencia=$oItem->getData();
unset($oItem);

$oItem=new complex("Grupo", "Id_Grupo",$detalle["Id_Grupo"]);
$grupo=$oItem->getData();
unset($oItem);

$query = 'SELECT CF.*,L.Id_Liquidacion_Funcionario FROM Contrato_Funcionario CF LEFT JOIN Liquidacion_Funcionario L ON CF.Id_Contrato_Funcionario=L.Id_Contrato_Funcionario  WHERE CF.Identificacion_Funcionario='.$id.' ORDER BY CF.Id_Contrato_Funcionario DESC';


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$contratos = $oCon->getData();
unset($oCon);

$query = 'SELECT A.*, A.Tipo as Estado, CONCAT(F.Nombres," ",F.Apellidos) as Funcionario, F.Imagen  FROM Actividad_Funcionario A INNER JOIN Funcionario F ON A.Identificacion_Funcionario=F.Identificacion_Funcionario WHERE A.Identificacion_Funcionario= '.$id.' ORDER BY Fecha DESC ';


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Actividades'] = $oCon->getData();
unset($oCon);

$query = 'SELECT D.Nombre, P.Archivo
            FROM Pazysalvo P
            INNER JOIN Dependencia D ON D.Id_Dependencia =  P.Id_Dependencia
            INNER JOIN Liquidacion_Funcionario LF ON LF.Id_Liquidacion_Funcionario = P.Id_Liquidacion
            WHERE LF.Identificacion_Funcionario = '.$id;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado['Soportes'] = $oCon->getData();
unset($oCon);


$query = 'SELECT N.*, (SELECT TN.Novedad FROM Tipo_Novedad TN WHERE TN.Id_Tipo_Novedad=N.Id_Tipo_Novedad) as Novedad FROM Novedad N  WHERE N.Identificacion_Funcionario='.$id.' ORDER BY N.Id_Novedad DESC';


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Novedades'] = $oCon->getData();
unset($oCon);

$query = 'SELECT N.* FROM Memorando N  WHERE N.Identificacion_Funcionario='.$id.' ORDER BY N.Id_Memorando DESC';


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Memorandos'] = $oCon->getData();
unset($oCon);

foreach ($contratos as $key => $value) {
    $contratos[$key]['OtroSi']=GetOtroSi($value['Id_Contrato_Funcionario']);
}



$resultado['Funcionario']=$detalle;
$resultado['Contacto_Emergencia']=$funcionarioCE;
$resultado['Experiencia_Laboral']=$funcionarioEL;
$resultado['Referencia_Personal']=$referencia;
$resultado['Cargo']=$cargo;
$resultado['Dependencia']=$dependencia;
$resultado['Grupo']=$grupo;
$resultado['Contratos']=$contratos;

echo json_encode($resultado);

function GetOtroSi($id){
    $query="SELECT *, IFNULL(Salario,0) as Salario, DATE(Fecha) as Fecha FROM Otrosi_Contrato WHERE Id_Contrato_Funcionario=$id ";
    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $datos = $oCon->getData();
    unset($oCon);
    

    return $datos;
}

?>