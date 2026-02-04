<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$mod = 'memorando';
$IdFuncionario = ( isset( $_REQUEST['IdFuncionario'] ) ? $_REQUEST['IdFuncionario'] : '' );

$condicion = '';

$query = " SELECT Identificacion_Funcionario AS Identificacion , '' AS Nombre_Categoria,
            SUBSTRING(Descripcion_Proceso, 1, 15) AS Descripcion ,
            Fecha_Inicio AS Fecha, 
            'Proceso Disciplinario' AS Tipo
            FROM Proceso_Disciplinario 
            WHERE Identificacion_Funcionario  = $IdFuncionario
            UNION ALL
            SELECT M.Identificacion_Funcionario  AS Identificacion , ct.Nombre_Categoria,
                    SUBSTRING(M.Detalles, 1, 15) AS Descripcion,
                    M.Fecha, 
                    'Memorando' AS Tipo
            FROM Memorando M 
            INNER JOIN Categorias_Memorando ct ON M.Motivo = ct.Id_Categorias_Memorando
            WHERE Identificacion_Funcionario  = ".$IdFuncionario;
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);
echo json_encode($resultado);


?>