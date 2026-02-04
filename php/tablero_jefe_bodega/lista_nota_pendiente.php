<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


 $query = 'SELECT NC.Id_Nota_Credito, NC.Fecha, F.Imagen, C.Nombre , NC.Codigo,
(SELECT COUNT(*) FROM Producto_Nota_Credito PR WHERE PR.Id_Nota_Credito = NC.Id_Nota_Credito) as Items
FROM Nota_Credito NC
LEFT JOIN Funcionario F
ON NC.Identificacion_Funcionario = F.Identificacion_Funcionario
INNER JOIN Cliente C
ON NC.Id_Cliente =C.Id_Cliente
WHERE NC.Estado="Pendiente"
HAVING Items>0
ORDER BY NC.Fecha DESC, NC.Codigo DESC ' ;
       

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();

foreach ($resultado as $key => $value) {
    # code...
    $query2 = 'SELECT PFV.*, IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida),
                                CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Nombre_Producto,
                                PRD.Nombre_Comercial, PRD.Embalaje, PRD.Invima,
                                CONCAT_WS(" // ",PRD.Laboratorio_Comercial, PRD.Laboratorio_Generico  ) as Laboratorios
    FROM Producto_Nota_Credito PFV
    INNER JOIN Producto PRD
    ON PFV.Id_Producto=PRD.Id_Producto
    WHERE PFV.Id_Nota_Credito = '.$value['Id_Nota_Credito'];
    $oCon= new consulta();
    $oCon->setQuery($query2);
    $oCon->setTipo('Multiple');
   $resultado[$key]['Lista_Productos'] = $oCon->getData();

}
unset($oCon);

echo json_encode($resultado);
          
?>