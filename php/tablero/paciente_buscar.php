<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$idPaciente = ( isset( $_REQUEST['IdPaciente'] ) ? $_REQUEST['IdPaciente'] : '' );
$IdPunto = ( isset( $_REQUEST['IdPunto'] ) ? $_REQUEST['IdPunto'] : '' );

$hoy = date('Y-m-d');
$fecha_min_pendiente = strtotime('-1 month', strtotime($hoy));
$fecha_min_pendiente = date('Y-m-d', $fecha_min_pendiente);

$query = 'SELECT r.Nombre as NombreRegimen, 
                 n.Nombre as NombreNivel, 
                 n.Valor as NivelValor,
                 n.Numero as NivelNumero,
                 CONCAT_WS(" ",p.Primer_Nombre, p.Segundo_Nombre,p.Primer_Apellido,p.Segundo_Apellido) as NombrePaciente,
                 p.EPS as EPS,
                 p.Nit,
                 p.Id_Departamento
          FROM Paciente p 
          LEFT JOIN Regimen r ON
          p.Id_Regimen=r.Id_Regimen
          LEFT JOIN Nivel n ON
          p.Id_Nivel = n.Id_Nivel
          WHERE
          p.Id_Paciente="'.$idPaciente.'"';

 
$oCon= new consulta();
$oCon->setQuery($query);
$pacientes = $oCon->getData();
unset($oCon);


$query2 = 'SELECT D.Fecha_Actual, D.Cuota, C.Salario_Base
           FROM Dispensacion D, Configuracion C 
           WHERE 
           YEAR(D.Fecha_Actual) =  YEAR(NOW())
           AND
           D.Numero_Documento="'.$idPaciente .'"';

$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$dispensaciones = $oCon->getData();
unset($oCon);

$ids_productos = [];
/** Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */
$query3 = 'SELECT
(SELECT CONCAT_WS(" ",CONCAT("(",D.Codigo,")"),"-",P.Nombre_Comercial,"(",P.Principio_Activo, P.Presentacion, P.Concentracion,")", P.Cantidad, P.Unidad_Medida) FROM Producto P WHERE P.Id_Producto=I.Id_Producto) as Nombre, 
I.Id_Inventario_Nuevo AS IdInventario,
I.Codigo_CUM AS Cum,
I.Lote,
I.Fecha_Vencimiento AS Vencimiento,
(I.Cantidad-Cantidad_Apartada) AS Cantidad_Producto,
D.*
FROM Inventario_Nuevo I
INNER JOIN
(
    SELECT  
    D.*, 
    (PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Pendiente,
    PD.Id_Producto,
    PD.Cantidad_Formulada,
    PD.Cantidad_Entregada,
    PD.Numero_Autorizacion,
    PD.Fecha_Autorizacion,
    PD.Id_Producto_Dispensacion
    FROM Producto_Dispensacion PD
    INNER JOIN Dispensacion D
    ON D.Id_Dispensacion=PD.Id_Dispensacion
    WHERE (PD.Cantidad_Formulada-PD.Cantidad_Entregada)>0 
    AND D.Numero_Documento="'.$idPaciente.'"
    AND D.Tipo!="NoPOs"
    AND D.Estado_Dispensacion <> "Anulada"
    #AND DATE(D.Fecha_Actual) BETWEEN "'.$fecha_min_pendiente.'" AND CURDATE()
) AS D
ON I.Id_Producto=D.Id_Producto
WHERE I.Id_Punto_Dispensacion='.$IdPunto.'
AND I.Cantidad > 0';

$oCon= new consulta();
$oCon->setQuery($query3);
$oCon->setTipo('Multiple');
$listapendientes = $oCon->getData();
unset($oCon);

$i=-1;
foreach($listapendientes as $producto){ $i++;

    $ids_productos[] = $producto["Id_Producto"];
    
    $prod["Nombre"]=$producto["Nombre"];
    $prod["Id_Producto"]=$producto["Id_Producto"];
    $prod["Id_Inventario_Nuevo"]=$producto["IdInventario"];
    $prod["Lote"]=$producto["Lote"];
    $prod["Cum"]=$producto["Cum"];
    $prod["Vencimiento"]=$producto["Vencimiento"];
    $prod["precio"]=$producto["Precio"];
    $prod["Numero_Autorizacion"]=$producto["Numero_Autorizacion"];
    $prod["Fecha_Autorizacion"]=$producto["Fecha_Autorizacion"];
    $prod["Numero_Prescripcion"]=$producto["Numero_Prescripcion"];
    $prod["Cantidad_Formulada"]=$producto["Cantidad_Formulada"];
    $prod["Cantidad_Entregada"]=$producto["Cantidad_Entregada"];
    $prod["Cantidad"]=$producto["Cantidad"];
    $prod["Entregar_Faltante"]=$producto["Entregar_Faltante"];
    $prod["Cantidad_Pendiente"]=$producto["Cantidad_Pendiente"];
    $prod["Valor_Cuota"]=$producto["Cuota"];
    
    
    $listapendientes[$i]["producto"]=$producto;
    $listapendientes[$i]["Id_Producto"]=$producto["Id_Producto"];
    $listapendientes[$i]["Id_Inventario_Nuevo"]=$producto["IdInventario"];
    $listapendientes[$i]["Lote"]=$producto["Lote"];
    $listapendientes[$i]["Cum"]=$producto["Cum"];
    $listapendientes[$i]["Vencimiento"]=$producto["Vencimiento"];
    $listapendientes[$i]["Precio"]=$producto["Precio"];
    
    $listapendientes[$i]["Numero_Autorizacion"]=$producto["Numero_Autorizacion"];
    $listapendientes[$i]["Fecha_Autorizacion"]=$producto["Fecha_Autorizacion"];;
    $listapendientes[$i]["Numero_Prescripcion"]='';
    $listapendientes[$i]["Cantidad_Formulada"]=$producto["Cantidad_Formulada"];
    $listapendientes[$i]["Cantidad_Entregada"]=$producto["Cantidad_Entregada"];
    $listapendientes[$i]["Cantidad"]=$productosiguales["Cantidad"];
    $listapendientes[$i]["Entregar_Faltante"]=$producto["Entregar_Faltante"];
    $listapendientes[$i]["Cantidad_Pendiente"]=$producto["Cantidad_Pendiente"];
    $listapendientes[$i]["Numero_Autorizacion_D"]=true;
    $listapendientes[$i]["Fecha_Autorizacion_D"]=true;
    $listapendientes[$i]["Numero_Prescripcion_Read"]=true;
    $listapendientes[$i]["Cantidad_Formulada_D"]=true;
    $listapendientes[$i]["Cantidad_Entregada_D"]=true;
}

$listapendientesnodis = [];

if (count($ids_productos) > 0) {
    $query5 = 'SELECT CONCAT_WS(" ",P.Nombre_Comercial,"(",P.Principio_Activo, P.Presentacion, P.Concentracion,")", P.Cantidad, P.Unidad_Medida) as Nombre,   
            D.*, 
           (PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Pendiente,
            PD.Id_Producto,
            PD.Cum as Cum,
            PD.Lote as Lote,
            PD.Id_Inventario_Nuevo as IdInventario,
            PD.Cantidad_Formulada,
            PD.Cantidad_Entregada,
            PD.Numero_Autorizacion,
            PD.Fecha_Autorizacion,
            "" as Vencimiento,
            0,
            PD.Id_Producto_Dispensacion,
            P.ATC,
            P.Unidad_Medida,
            P.Cantidad AS Cantidad_Prod
FROM Dispensacion D
INNER JOIN Producto_Dispensacion PD
ON D.Id_Dispensacion=PD.Id_Dispensacion
INNER JOIN Producto P
ON P.Id_Producto=PD.Id_Producto
WHERE (PD.Cantidad_Formulada-PD.Cantidad_Entregada)>0 
AND D.Numero_Documento="'.$idPaciente.'"
AND D.Tipo!="NoPOs"
AND D.Estado_Dispensacion <> "Anulada"
AND PD.Id_Producto NOT IN ('.implode(",",$ids_productos).')
#AND DATE(D.Fecha_Actual) BETWEEN "'.$fecha_min_pendiente.'" AND CURDATE()
GROUP BY PD.Id_Producto, D.Id_Dispensacion';

} else {
    $query5 = 'SELECT CONCAT_WS(" ",P.Nombre_Comercial,"(",P.Principio_Activo, P.Presentacion, P.Concentracion,")", P.Cantidad, P.Unidad_Medida) as Nombre,  
            D.*, 
           (PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Pendiente,
            PD.Id_Producto,
            PD.Cum as Cum,
            PD.Lote as Lote,
            PD.Id_Inventario_Nuevo as IdInventario,
            PD.Cantidad_Formulada,
            PD.Cantidad_Entregada,
            PD.Numero_Autorizacion,
            PD.Fecha_Autorizacion,
            "" as Vencimiento,
            0,
            PD.Id_Producto_Dispensacion,
            P.ATC,
            P.Unidad_Medida,
            P.Cantidad AS Cantidad_Prod
FROM Dispensacion D
INNER JOIN Producto_Dispensacion PD
ON D.Id_Dispensacion=PD.Id_Dispensacion
INNER JOIN Producto P
ON PD.Id_Producto=P.Id_Producto
WHERE (PD.Cantidad_Formulada-PD.Cantidad_Entregada)>0 
AND D.Numero_Documento="'.$idPaciente.'"
AND D.Tipo!="NoPOs"
AND D.Estado_Dispensacion <> "Anulada"
#AND DATE(D.Fecha_Actual) BETWEEN "'.$fecha_min_pendiente.'" AND CURDATE()
GROUP BY PD.Id_Producto, D.Id_Dispensacion';
}

$oCon= new consulta();
$oCon->setQuery($query5);
$oCon->setTipo('Multiple');
$listapendientesnodis = $oCon->getData();
unset($oCon);
//echo $query5; 
$i=-1;
foreach($listapendientesnodis as $producto){ $i++;

    $cantidad_pendiente = $producto["Cantidad_Formulada"] - $producto["Cantidad_Entregada"];
    $q = 'SELECT 
    CONCAT_WS(" ",P.Nombre_Comercial,"(",P.Principio_Activo, P.Presentacion, P.Concentracion,")", P.Cantidad, P.Unidad_Medida) AS Nombre,
    I.Id_Inventario_Nuevo,
    I.Codigo_CUM AS Cum,
    I.Lote,
    I.Fecha_Vencimiento AS Vencimiento,
    (I.Cantidad-I.Cantidad_Apartada) AS Cantidad_Producto,
    I.Id_Producto,
    I.Costo AS Precio,
    "'.$producto["Fecha_Autorizacion"].'" AS Fecha_Autorizacion,
    "'.$producto["Id_Dispensacion"].'" AS Id_Dispensacion,
    "'.$producto["Cantidad_Formulada"].'" AS Cantidad_Formulada,
    "'.$producto["Cantidad_Entregada"].'" AS Cantidad_Entregada,
    "'.$cantidad_pendiente.'" AS Cantidad_Pendiente,
    "'.$producto["Id_Producto_Dispensacion"].'" AS Id_Producto_Dispensacion,
    "'.$producto["ATC"].'" AS ATC,
    "'.$producto["Tipo"].'" AS Tipo,
    true AS Semejante
    FROM Inventario_Nuevo I
    INNER JOIN Producto P
    ON I.Id_Producto=P.Id_Producto
    WHERE I.Id_Punto_Dispensacion='.$IdPunto.' AND I.Cantidad > 0
    AND P.Id_Categoria IN (8,9,12) AND P.ATC="'.$producto["ATC"].'"/* AND P.Unidad_Medida="'.$producto["Unidad_Medida"].'" AND P.Cantidad='.(INT)$producto["Cantidad_Prod"].'
    */';

    $oCon= new consulta();
    $oCon->setQuery($q);
    $oCon->setTipo('Multiple');
    $prodSemejantes = $oCon->getData();
    unset($oCon);

    $listapendientesnodis[$i]["prodSemejantes"]=$prodSemejantes;
    $listapendientesnodis[$i]["producto"]=$producto;
    $listapendientesnodis[$i]["Id_Producto"]=$producto["Id_Producto"];
    $listapendientesnodis[$i]["Id_Inventario_Nuevo"]=$producto["IdInventario"];
    $listapendientesnodis[$i]["Lote"]=$producto["Lote"];
    $listapendientesnodis[$i]["Cum"]=$producto["Cum"];
    $listapendientesnodis[$i]["Vencimiento"]=$producto["Vencimiento"];
    $listapendientesnodis[$i]["Precio"]=$producto["Precio"];
    
    $listapendientesnodis[$i]["Numero_Autorizacion"]=$producto["Numero_Autorizacion"];
    $listapendientesnodis[$i]["Fecha_Autorizacion"]=$producto["Fecha_Autorizacion"];;
    $listapendientesnodis[$i]["Numero_Prescripcion"]='';
    $listapendientesnodis[$i]["Cantidad_Formulada"]=$producto["Cantidad_Formulada"];
    $listapendientesnodis[$i]["Cantidad_Entregada"]=$producto["Cantidad_Entregada"];
    $listapendientesnodis[$i]["Cantidad"]=$productosiguales["Cantidad"];
    $listapendientesnodis[$i]["Entregar_Faltante"]=$producto["Entregar_Faltante"];
    $listapendientesnodis[$i]["Cantidad_Pendiente"]=$producto["Cantidad_Pendiente"];
    $listapendientesnodis[$i]["Numero_Autorizacion_D"]=true;
    $listapendientesnodis[$i]["Fecha_Autorizacion_D"]=true;
    $listapendientesnodis[$i]["Numero_Prescripcion_Read"]=true;
    $listapendientesnodis[$i]["Cantidad_Formulada_D"]=true;
    $listapendientesnodis[$i]["Cantidad_Entregada_D"]=true;
}

$ids_productos = [];

$query4 = 'SELECT
(SELECT CONCAT_WS(" ",CONCAT("(",D.Codigo,")"),"-",P.Nombre_Comercial,"(",P.Principio_Activo, P.Presentacion, P.Concentracion,")", P.Cantidad, P.Unidad_Medida) FROM Producto P WHERE P.Id_Producto=I.Id_Producto) as Nombre, 
I.Id_Inventario_Nuevo AS IdInventario,
I.Codigo_CUM AS Cum,
I.Lote,
I.Fecha_Vencimiento AS Vencimiento,
(I.Cantidad-I.Cantidad_Apartada) AS Cantidad_Producto,
D.*
FROM Inventario_Nuevo I
INNER JOIN
(
    SELECT  
    D.*, 
    (PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Pendiente,
    PD.Id_Producto,
    PD.Cantidad_Formulada,
    PD.Cantidad_Entregada,
    PD.Numero_Autorizacion,
    PD.Fecha_Autorizacion,
    PD.Id_Producto_Dispensacion
    FROM Producto_Dispensacion PD
    INNER JOIN Dispensacion D
    ON D.Id_Dispensacion=PD.Id_Dispensacion
    #INNER JOIN Producto_NoPos NP
    #ON PD.Cum = NP.Cum
    WHERE (PD.Cantidad_Formulada-PD.Cantidad_Entregada)>0 
    AND D.Numero_Documento="'.$idPaciente.'"
    AND (D.Tipo="NoPOs"
    OR D.Tipo="Evento" OR D.Tipo="Cohortes")
    AND D.Estado_Dispensacion <> "Anulada"
) AS D
ON I.Id_Producto=D.Id_Producto
WHERE I.Id_Punto_Dispensacion='.$IdPunto.'
AND I.Cantidad > 0
GROUP BY I.Id_Producto, I.Lote, D.Id_Dispensacion';

$oCon= new consulta();
$oCon->setQuery($query4);
$oCon->setTipo('Multiple');
$listapendientesnopos = $oCon->getData();
unset($oCon);

$i=-1;
foreach($listapendientesnopos as $producto){ $i++;

    $ids_productos[] = $producto["Id_Producto"];

    $prod["Nombre"]=$producto["Nombre"];
    $prod["Id_Producto"]=$producto["Id_Producto"];
    $prod["Id_Inventario_Nuevo"]=$producto["IdInventario"];
    $prod["Lote"]=$producto["Lote"];
    $prod["Cum"]=$producto["Cum"];
    $prod["Vencimiento"]=$producto["Vencimiento"];
    $prod["precio"]=$producto["Precio"];
    $prod["Numero_Autorizacion"]=$producto["Numero_Autorizacion"];
    $prod["Fecha_Autorizacion"]=$producto["Fecha_Autorizacion"];
    $prod["Numero_Prescripcion"]=$producto["Numero_Prescripcion"];
    $prod["Cantidad_Formulada"]=$producto["Cantidad_Formulada"];
    $prod["Cantidad_Entregada"]=$producto["Cantidad_Entregada"];
    $prod["Cantidad"]=$producto["Cantidad"];
    $prod["Entregar_Faltante"]=$producto["Entregar_Faltante"];
    $prod["Cantidad_Pendiente"]=$producto["Cantidad_Pendiente"];
    $prod["Valor_Cuota"]=$producto["Cuota"];
    
    
    $listapendientesnopos[$i]["producto"]=$producto;
    $listapendientesnopos[$i]["Id_Producto"]=$producto["Id_Producto"];
    $listapendientesnopos[$i]["Id_Inventario_Nuevo"]=$producto["IdInventario"];
    $listapendientesnopos[$i]["Lote"]=$producto["Lote"];
    $listapendientesnopos[$i]["Cum"]=$producto["Cum"];
    $listapendientesnopos[$i]["Vencimiento"]=$producto["Vencimiento"];
    $listapendientesnopos[$i]["Precio"]=$producto["Precio"];
    
    $listapendientesnopos[$i]["Numero_Autorizacion"]=$producto["Numero_Autorizacion"];
    $listapendientesnopos[$i]["Fecha_Autorizacion"]=$producto["Fecha_Autorizacion"];;
    $listapendientesnopos[$i]["Numero_Prescripcion"]=$producto["Numero_Prescripcion"];
    $listapendientesnopos[$i]["Cantidad_Formulada"]=$producto["Cantidad_Formulada"];
    $listapendientesnopos[$i]["Cantidad_Entregada"]=$producto["Cantidad_Entregada"];
    $listapendientesnopos[$i]["Cantidad"]=$productosiguales["Cantidad"];
    $listapendientesnopos[$i]["Entregar_Faltante"]=$producto["Entregar_Faltante"];
    $listapendientesnopos[$i]["Cantidad_Pendiente"]=$producto["Cantidad_Pendiente"];
    $listapendientesnopos[$i]["Numero_Autorizacion_D"]=true;
    $listapendientesnopos[$i]["Fecha_Autorizacion_D"]=true;
    $listapendientesnopos[$i]["Numero_Prescripcion_Read"]=true;
    $listapendientesnopos[$i]["Cantidad_Formulada_D"]=true;
    $listapendientesnopos[$i]["Cantidad_Entregada_D"]=true;
}

if (count($ids_productos) > 0) {
    $query6 = 'SELECT  (SELECT CONCAT_WS(" ",P.Nombre_Comercial,"(",P.Principio_Activo, P.Presentacion, P.Concentracion,")", P.Cantidad, P.Unidad_Medida) FROM Producto P WHERE P.Id_Producto=PD.Id_Producto) as Nombre, 
            D.*, 
           (PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Pendiente,
            PD.Id_Producto,
            PD.Cum as Cum,
            PD.Lote as Lote,
            PD.Id_Inventario_Nuevo as IdInventario,
            PD.Cantidad_Formulada,
            PD.Cantidad_Entregada,
            PD.Numero_Autorizacion,
            PD.Fecha_Autorizacion,
            "" as Vencimiento,
            0,
            PD.Id_Producto_Dispensacion,
            
            P.ATC,
            P.Unidad_Medida,
            P.Cantidad AS Cantidad_Prod
FROM Dispensacion D
INNER JOIN Producto_Dispensacion PD
ON D.Id_Dispensacion=PD.Id_Dispensacion
INNER JOIN Producto P
ON PD.Id_Producto = P.Id_Producto
#INNER JOIN Producto_NoPos NP
#ON PD.Cum = NP.Cum
WHERE (PD.Cantidad_Formulada-PD.Cantidad_Entregada)>0 
AND D.Numero_Documento="'.$idPaciente.'"
AND (D.Tipo="NoPOs" OR D.Tipo="Evento" OR D.Tipo="Cohortes")
AND D.Estado_Dispensacion <> "Anulada"
AND PD.Id_Producto NOT IN ('.implode(",",$ids_productos).')
GROUP BY PD.Id_Producto, D.Id_Dispensacion';

} else {
    $query6 = 'SELECT  (SELECT CONCAT_WS(" ",P.Nombre_Comercial,"(",P.Principio_Activo, P.Presentacion, P.Concentracion,")", P.Cantidad, P.Unidad_Medida) FROM Producto P WHERE P.Id_Producto=PD.Id_Producto) as Nombre, 
            D.*, 
           (PD.Cantidad_Formulada-PD.Cantidad_Entregada) as Cantidad_Pendiente,
            PD.Id_Producto,
            PD.Cum as Cum,
            PD.Lote as Lote,
            PD.Id_Inventario_Nuevo as IdInventario,
            PD.Cantidad_Formulada,
            PD.Cantidad_Entregada,
            PD.Numero_Autorizacion,
            PD.Fecha_Autorizacion,
            "" as Vencimiento,
            0,
            PD.Id_Producto_Dispensacion,
            
            P.ATC,
            P.Unidad_Medida,
            P.Cantidad AS Cantidad_Prod
FROM Dispensacion D
INNER JOIN Producto_Dispensacion PD
ON D.Id_Dispensacion=PD.Id_Dispensacion
INNER JOIN Producto P
ON PD.Id_Producto = P.Id_Producto
#INNER JOIN Producto_NoPos NP
#ON PD.Cum = NP.Cum
WHERE (PD.Cantidad_Formulada-PD.Cantidad_Entregada)>0 
AND D.Numero_Documento="'.$idPaciente.'"
AND (D.Tipo="NoPOs" OR D.Tipo="Evento" OR D.Tipo="Cohortes")
AND D.Estado_Dispensacion <> "Anulada"
GROUP BY PD.Id_Producto, D.Id_Dispensacion';
}


$oCon= new consulta();
$oCon->setQuery($query6);
$oCon->setTipo('Multiple');
$listapendientesnoposnodis = $oCon->getData();
unset($oCon);


$i=-1;
foreach($listapendientesnoposnodis as $producto){ $i++;

    $codigo_cum = explode("-",$producto['Cum'])[0];

    $cantidad_pendiente = $producto["Cantidad_Formulada"] - $producto["Cantidad_Entregada"];
    $q = 'SELECT 
    CONCAT_WS(" ",P.Nombre_Comercial,"(",P.Principio_Activo, P.Presentacion, P.Concentracion,")", P.Cantidad, P.Unidad_Medida) AS Nombre,
    I.Id_Inventario_Nuevo,
    I.Codigo_CUM AS Cum,
    I.Lote,
    I.Fecha_Vencimiento AS Vencimiento,
    (I.Cantidad-Cantidad_Apartada) AS Cantidad_Producto,
    I.Id_Producto,
    I.Costo AS Precio,
    "'.$producto["Fecha_Autorizacion"].'" AS Fecha_Autorizacion,
    "'.$producto["Id_Dispensacion"].'" AS Id_Dispensacion,
    "'.$producto["Cantidad_Formulada"].'" AS Cantidad_Formulada,
    "'.$producto["Cantidad_Entregada"].'" AS Cantidad_Entregada,
    "'.$cantidad_pendiente.'" AS Cantidad_Pendiente,
    "'.$producto["Id_Producto_Dispensacion"].'" AS Id_Producto_Dispensacion,
    "'.$producto["ATC"].'" AS ATC,
    "'.$producto["Tipo"].'" AS Tipo,
    true AS Semejante
    FROM Inventario_Nuevo I
    INNER JOIN Producto P
    ON I.Id_Producto=P.Id_Producto
    WHERE I.Id_Punto_Dispensacion='.$IdPunto.' AND I.Cantidad > 0
    AND P.Codigo_Cum LIKE "'.$codigo_cum.'%"';

    $oCon= new consulta();
    $oCon->setQuery($q);
    $oCon->setTipo('Multiple');
    $prodSemejantes = $oCon->getData();
    unset($oCon);

    $listapendientesnoposnodis[$i]["prodSemejantes"]=$prodSemejantes;

    $listapendientesnoposnodis[$i]["producto"]=$producto;
    $listapendientesnoposnodis[$i]["Id_Producto"]=$producto["Id_Producto"];
    $listapendientesnoposnodis[$i]["Id_Inventario_Nuevo"]=$producto["IdInventario"];
    $listapendientesnoposnodis[$i]["Lote"]=$producto["Lote"];
    $listapendientesnoposnodis[$i]["Cum"]=$producto["Cum"];
    $listapendientesnoposnodis[$i]["Vencimiento"]=$producto["Vencimiento"];
    $listapendientesnoposnodis[$i]["Precio"]=$producto["Precio"];
    
    $listapendientesnoposnodis[$i]["Numero_Autorizacion"]=$producto["Numero_Autorizacion"];
    $listapendientesnoposnodis[$i]["Fecha_Autorizacion"]=$producto["Fecha_Autorizacion"];;
    $listapendientesnoposnodis[$i]["Numero_Prescripcion"]='';
    $listapendientesnoposnodis[$i]["Cantidad_Formulada"]=$producto["Cantidad_Formulada"];
    $listapendientesnoposnodis[$i]["Cantidad_Entregada"]=$producto["Cantidad_Entregada"];
    $listapendientesnoposnodis[$i]["Cantidad"]=$productosiguales["Cantidad"];
    $listapendientesnoposnodis[$i]["Entregar_Faltante"]=$producto["Entregar_Faltante"];
    $listapendientesnoposnodis[$i]["Cantidad_Pendiente"]=$producto["Cantidad_Pendiente"];
    $listapendientesnoposnodis[$i]["Numero_Autorizacion_D"]=true;
    $listapendientesnoposnodis[$i]["Fecha_Autorizacion_D"]=true;
    $listapendientesnoposnodis[$i]["Numero_Prescripcion_Read"]=true;
    $listapendientesnoposnodis[$i]["Cantidad_Formulada_D"]=true;
    $listapendientesnoposnodis[$i]["Cantidad_Entregada_D"]=true;
}

if($pacientes["NombreRegimen"]==="Subsidiado"){

    $totalsalario=0;
    $aplica='';
    $porcentaje='';
    if($pacientes["NivelNumero"]==="1"){
        
        $totalcuota=0;
        $aplica="No";
       
    }else if($pacientes["NivelNumero"]==="2" ){
        
        $totalsalario=$dispensaciones[0]["Salario_Base"]*2;
        $aplica="Si";
        $porcentaje="10";
         
    }else{
        
        $totalsalario=$dispensaciones[0]["Salario_Base"]*3;
        $aplica="Si";
        $porcentaje="30";
    }
    
}else{
    $totalsalario=0;
    $aplica='No';
    $porcentaje=0;
}
 if($pacientes["NivelNumero"]!="1"){
    $totalcuota=0;
    foreach($dispensaciones as $cuota){
      
    $totalcuota=$totalcuota + $cuota["Cuota"];
    }  
 }
        $query = "SELECT PD.Id_Producto FROM Producto_Dispensacion PD INNER JOIN Dispensacion D ON PD.Id_Dispensacion=D.Id_Dispensacion WHERE DATE_FORMAT(D.Fecha_Actual,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m') AND D.Numero_Documento='$idPaciente' AND D.Estado_Dispensacion <> 'Anulada' GROUP BY PD.Id_Producto";

        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $productos = $oCon->getData();
        unset($oCon);

$resultado["pendientes"]=$listapendientes;
$resultado["pendientesnodis"]=$listapendientesnodis;
$resultado["pendientesnopos"]=$listapendientesnopos;
$resultado["pendientesnoposnodis"]=$listapendientesnoposnodis;
$resultado["aplica"]=$aplica;
$resultado["totalcuota"]=$totalcuota;
$resultado["porcentaje"]=$porcentaje;
$resultado["totalsalario"]=$totalsalario;
$resultado["inventario"]=$productos;        
$resultado["paciente"]=$pacientes; 


echo json_encode($resultado);

?>