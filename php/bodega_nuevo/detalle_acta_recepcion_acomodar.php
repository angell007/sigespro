<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_acta = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');

$tipo_acta = (isset($_REQUEST['Tipo_Acta'])) ? $_REQUEST['Tipo_Acta'] : false;


switch ($tipo_acta) {
    case 'Acta_Recepcion':
        # code...
        $query3 = 'SELECT AR.*, 
            IFNULL(B.Nombre, PD.Nombre) as Nombre_Bodega, 
            IFNULL(B.Id_Bodega_Nuevo, PD.Id_Punto_Dispensacion) as Id_Origen_Destino,
            P.Nombre as NombreProveedor, P.Direccion as DireccionProveedor, P.Telefono as TelefonoProveedor,
            (SELECT PAR.Codigo_Compra FROM Producto_Acta_Recepcion PAR WHERE PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion GROUP BY AR.Id_Acta_Recepcion) AS Codigo_Compra
            FROM Acta_Recepcion AR
            LEFT JOIN Bodega_Nuevo B ON AR.Id_Bodega_Nuevo =B.Id_Bodega_Nuevo
            LEFT JOIN Punto_Dispensacion PD ON AR.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion
            INNER JOIN Proveedor P On P.Id_Proveedor = AR.Id_Proveedor
            WHERE AR.Id_Acta_Recepcion=' . $id_acta;

        $oCon = new consulta();

        $oCon->setQuery($query3);
        $datos = $oCon->getData();
        unset($oCon);

        break;
    
    case 'Acta_Recepcion_Remision':
        # code...
        $query = 'SELECT AR.*, AR.Fecha AS "Fecha_Creacion",  
        "INTERNA" TelefonoProveedor , "INTERNA" DireccionProveedor, "INTERNA" NombreProveedor,
        IFNULL(B.Nombre, P.Nombre) AS Nombre_Bodega, 
        IFNULL(P.Id_Punto_Dispensacion, B.Id_Bodega_Nuevo) as Id_Origen_Destino,
        R.Codigo AS Codigo_Compra
        FROM Acta_Recepcion_Remision AR
        LEFT JOIN Bodega_Nuevo B ON AR.Id_Bodega_Nuevo =B.Id_Bodega_Nuevo
        LEFT JOIN Punto_Dispensacion P ON AR.Id_Punto_Dispensacion = P.Id_Punto_Dispensacion
        INNER JOIN Remision R ON AR.Id_Remision=R.Id_Remision
        WHERE AR.Id_Acta_Recepcion_Remision=' . $id_acta;
 
        $oCon= new consulta();
        $oCon->setQuery($query);
        $datos = $oCon->getData();
        unset($oCon);
        break;
        

    case 'Ajuste_Individual':
        # code...
        $query = 'SELECT AR.*, AR.Fecha AS "Fecha_Creacion",  
        "INTERNA" TelefonoProveedor , "INTERNA" DireccionProveedor, "INTERNA" NombreProveedor,
        ifnull(B.Nombre, P.Nombre) AS Nombre_Bodega, 
        "INTERNA" AS Codigo_Compra
        FROM Ajuste_Individual AR
        LEFT JOIN Bodega_Nuevo B ON B.Id_Bodega_Nuevo = AR.Id_Origen_Destino and AR.Origen_Destino = "Bodega"
        LEFT JOIN Punto_Dispensacion P ON P.Id_Punto_Dispensacion = AR.Id_Origen_Destino and AR.Origen_Destino ="Punto"
        
        WHERE AR.Id_Ajuste_Individual=' . $id_acta;
    
        
        $oCon= new consulta();
        $oCon->setQuery($query);
        $datos = $oCon->getData();
        unset($oCon);
        break;

    
    case 'Nota_Credito':
        # code...
        $query = 'SELECT AR.*, AR.Fecha AS "Fecha_Creacion",  
        "INTERNA" TelefonoProveedor , "INTERNA" DireccionProveedor, "INTERNA" NombreProveedor,
        B.Nombre AS Nombre_Bodega, 
        B.Id_Bodega_Nuevo as Id_Origen_Destino,
        "INTERNA" AS Codigo_Compra
        FROM Nota_Credito AR
        INNER JOIN Bodega_Nuevo B
        ON B.Id_Bodega_Nuevo = AR.Id_Bodega_Nuevo
        
        WHERE AR.Id_Nota_Credito=' . $id_acta;
    
        
        $oCon= new consulta();
        $oCon->setQuery($query);
        $datos = $oCon->getData();
        unset($oCon);
        break;

    case 'Nacionalizacion_Parcial':
        # code...
        $query = ' SELECT PAI.Id_Nacionalizacion_Parcial , PAI.Codigo, PAI.Fecha_Registro AS "Fecha_Creacion",
                 P.Nombre as NombreProveedor, P.Direccion as DireccionProveedor, P.Telefono as TelefonoProveedor,
                B.Nombre AS Nombre_Bodega, 
                B.Id_Bodega_Nuevo as Id_Origen_Destino,
                OCI.Codigo AS Codigo_Compra
        FROM Nacionalizacion_Parcial PAI 
        INNER JOIN Acta_Recepcion_Internacional ACI ON ACI.Id_Acta_Recepcion_Internacional =  PAI.Id_Acta_Recepcion_Internacional
        INNER JOIN Orden_Compra_Internacional OCI ON OCI.Id_Orden_Compra_Internacional = ACI.Id_Orden_Compra_Internacional
        INNER JOIN Proveedor P ON P.Id_Proveedor = OCI.Id_Proveedor
        INNER JOIN Bodega_Nuevo B
        ON B.Id_Bodega_Nuevo = ACI.Id_Bodega_Nuevo
        WHERE     PAI.Id_Nacionalizacion_Parcial =' . $id_acta;
        
        
        $oCon= new consulta();
        $oCon->setQuery($query);
        $datos = $oCon->getData();
        
        unset($oCon);
        break;

    default:
        # code...
        break;
}

$productos_acta =[];



$resultado = [];
$productos_acta = productos_acta();

$resultado["Datos"] = $datos;
$resultado["Datos"]["ConteoProductos"] = count($productos_acta);

$resultado["Productos"] = $productos_acta;


echo json_encode($resultado);


function productos_acta()
{
    global $tipo_acta, $id_acta;

    switch ($tipo_acta) {
        case 'Acta_Recepcion':
            # code...
            $query2 = 'SELECT P.*, PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, PRD.Laboratorio_Generico, 
            IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida)
            ,CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Nombre_Producto,PRD.Codigo_Cum,
            IFNULL(POC.Cantidad,0) as Cantidad_Solicitada, PRD.Embalaje 
            FROM Producto_Acta_Recepcion P
            INNER JOIN Producto PRD
            ON P.Id_Producto=PRD.Id_Producto
            LEFT JOIN Producto_Orden_Compra_Nacional POC
            ON POC.Id_Producto_Orden_Compra_Nacional = P.Id_Producto_Orden_compra
            WHERE P.Id_Acta_Recepcion =' . $id_acta;
    
            $oCon = new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query2);
            $productos_acta = $oCon->getData();
            unset($oCon);
            return $productos_acta;

            break;
            
        case 'Acta_Recepcion_Remision':
            # code...
            $query2 = 'SELECT P.*, PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, PRD.Laboratorio_Generico, 
            IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida)
            ,CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Nombre_Producto,
            IFNULL(P.Cantidad,0) as Cantidad_Solicitada, PRD.Codigo_Cum, 0 Precio ,PRD.Embalaje 
            FROM Producto_Acta_Recepcion_Remision P
            INNER JOIN Producto PRD
            ON P.Id_Producto=PRD.Id_Producto
            
            WHERE P.Id_Acta_Recepcion_Remision =' . $id_acta;
    
            $oCon = new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query2);
            $productos_acta = $oCon->getData();
            unset($oCon);
            return $productos_acta;

            break;

        case 'Ajuste_Individual':
            # code...
            $query2 = 'SELECT P.*, PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, PRD.Laboratorio_Generico, 
            IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida)
            ,CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Nombre_Producto,
            IFNULL(P.Cantidad,0) as Cantidad_Solicitada, PRD.Codigo_Cum, 0 Precio, E.Nombre AS Estiba, E.Id_Estiba AS Id_Estiba_Ajuste,
             E.Codigo_Barras AS Codigo_Barras_Estiba_Ajuste , PRD.Embalaje 
            FROM Producto_Ajuste_Individual P
            INNER JOIN Producto PRD
            ON P.Id_Producto=PRD.Id_Producto
            LEFT JOIN Estiba E 
            ON E.Id_Estiba =  P.Id_Nueva_Estiba
            WHERE P.Id_Ajuste_Individual =' . $id_acta;
  
            $oCon = new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query2);
            $productos_acta = $oCon->getData();
            unset($oCon);
            return $productos_acta;
    
            break;

        case 'Nota_Credito':
            # code...
            $query2 = 'SELECT P.*, PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, PRD.Laboratorio_Generico, 
            IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida)
            ,CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Nombre_Producto,
            IFNULL(P.Cantidad,0) as Cantidad_Solicitada, PRD.Codigo_Cum, 0 Precio, PRD.Embalaje 
            FROM Producto_Nota_Credito P
            INNER JOIN Producto PRD
            ON P.Id_Producto=PRD.Id_Producto
            
            WHERE P.Id_Nota_Credito =' . $id_acta;
    
            $oCon = new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query2);
            $productos_acta = $oCon->getData();
            unset($oCon);
            return $productos_acta;
    
            break;
        case 'Nacionalizacion_Parcial':
            # code...
            $query2 = 'SELECT /*PA.*,*/
                    PA.Id_Acta_Recepcion_Internacional, P.Id_Producto,
                    
                        PA.Lote, PA.Fecha_Vencimiento, P.Cantidad,
                    PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, PRD.Laboratorio_Generico, 
                     IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida),
                             CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Nombre_Producto, 
                    PRD.Codigo_Cum,PRD.Embalaje 
            FROM Producto_Nacionalizacion_Parcial P
            INNER JOIN Producto_Acta_Recepcion_Internacional PA ON PA.Id_Producto_Acta_Recepcion_Internacional = P.Id_Producto_Acta_Recepcion_Internacional

            INNER JOIN Producto PRD
            ON PRD.Id_Producto = P.Id_Producto
            WHERE     P.Id_Nacionalizacion_Parcial =' . $id_acta;
    
            $oCon = new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query2);
            $productos_acta = $oCon->getData();
            unset($oCon);
            return $productos_acta;



           
    
            break;


        default:
            # code...
            break;
    }


}
