<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


// $query = '  SELECT CONCAT_WS(" ",F.Nombres, F.Apellidos) as Persona, 
//            (SELECT IFNULL(ROUND(SUM(D.Costo),0),0) as Valor 
//             FROM Dotacion D WHERE D.Funcionario_Recibe = F.Identificacion_Funcionario AND D.Estado != "Anulada") as Valor
//             FROM Funcionario F';
$query = 'SELECT CPD.Nombre Persona, SUM(Cantidad) Valor FROM Inventario_Dotacion ID
                INNER JOIN Categoria_Producto_Dotacion CPD ON ID.id_Categoria_Producto_Dotacion = CPD.Id_Categoria_Producto_Dotacion
                GROUP BY ID.id_Categoria_Producto_Dotacion';
$oCon = new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);        
$personas = $oCon->getData();
unset($oCon);
echo json_encode($personas);

?>