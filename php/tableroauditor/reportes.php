<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
/* header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="'.$_REQUEST['tipo'].'.xls"');
header('Cache-Control: max-age=0');  */
// header('Content-Type: application/json');
header('Content-Type: text/plain');
header('Content-Disposition: attachment;filename="' . $_REQUEST['tipo'] . '.csv"');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
    $tipo = $_REQUEST['tipo'];

    $query = CrearQuery($tipo);
    try {
        ArmarReporte($query);
        //code...
    } catch (\Throwable $th) {
        echo $th->getMessage();
    }
}


function ArmarReporte($query)
{
    global $tipo;
    $validar_codigos = ValidarCodigos($tipo);
    $encabezado = GetEncabezado($query);
    $datos = GetDatos($query);
    $contenido = '';

    if ($encabezado) {
        $valores_encabezado = array_keys($encabezado);

        /* $contenido .= '<table border="1"><tr>';
        foreach ($encabezado as $key => $value) {
          $contenido.='<td>'.$key.'</td>';
        }
        $contenido .= '</tr>'; */
        $i = 0;
        $fin = count($encabezado) - 1;
        foreach ($encabezado as $key => $value) {
            $valor = $key != '' ? str_replace(["\t", "\r", "\n", ";", ","], ["", "", "", "", ""], $key) : '';
            $contenido .= $valor . ';';
            if ($i == $fin) {
                $contenido .= "\r\n";
            }
            $i++;
        }
    }

    if ($datos) {

        foreach ($datos as $i => $dato) {
            /* $contenido .= '<tr>';
    
            foreach ($dato as $key => $value) {
               
                if(ValidarKey($key) ){
                    $valor = $dato[$key] != '' ? $dato[$key] : 0;
                  if ($_REQUEST['tipo'] == 'Reporte_Acta_Internacional') {
                    $contenido.= '<td>' . number_format($valor,6,",","") . '</td>'; 
                  }else{
                    $contenido.= '<td>' . number_format($valor,2,",","") . '</td>';
                  }
                }else{
                    $contenido.= '<td>' . $dato[$key] . '</td>';
                }
               
            }
    
            $contenido .= '</tr>'; */

            if ($i != 0 &&  $validar_codigos) {
                $numero_actual = preg_replace('/[^0-9]/', "", $dato['Codigo']);
                //PREFIJO DEL CODIGO
                $prefijo_cod = str_replace($numero_actual, "", $dato['Codigo']);

                $numero_anterior = preg_replace('/[^0-9]/', "", $datos[$i - 1]['Codigo']);
                $prefijo_anterior_cod = str_replace($numero_anterior, "", $datos[$i - 1]['Codigo']);

                if ($prefijo_anterior_cod == $prefijo_cod) {
                    # code...
                    //VALIDAR SI EXISTEN CONSECUTIVOS SALTADO
                    while (($numero_actual - $numero_anterior  > 1)) {
                        $numero_anterior++;
                        # $contenido .= '<tr style="background-color:red">';
                        $contenido .= $prefijo_cod . $numero_anterior;
                        for ($col = 1; $col < count($valores_encabezado); $col++) {
                            # code...
                            if (ValidarKey($valores_encabezado[$col])) {
                                $contenido .= '0;';
                            } else {
                                $contenido .= ' ;';
                            }
                        }
                        $contenido .= "\r\n";
                    }
                }
            }



            $x = 0;
            $fin = count($dato) - 1;
            foreach ($dato as $key => $value) {

                if (ValidarKey($key)) {
                    $valor = $dato[$key] != '' ? $dato[$key] : 0;
                    if ($_REQUEST['tipo'] == 'Reporte_Acta_Internacional') {
                        $contenido .= number_format($valor, 6, ".", "") . ";";
                    } else {
                        $contenido .= number_format($valor, 2, ".", "") . ";";
                    }
                } else {
                    $valor = $dato[$key] != '' ? str_replace(["\t", "\r", "\n", ";", ","], ["", "", "", "", ""], $dato[$key]) : '';
                    $contenido .= $valor . ';';
                }
                if ($x == $fin) {
                    $contenido .= "\r\n";
                }
                $x++;
            }
        }

        //  $contenido .= '</table>';
    }

    if ($contenido == '') {
        /* $contenido .= '
            <table>
                <tr>
                    <td>NO EXISTE INFORMACION PARA MOSTRAR</td>
                </tr>
            </table>
        '; */
        $contenido .= "NO EXISTE INFORMACION PARA MOSTRAR";
    }

    echo $contenido;
}
function GetEncabezado($query)
{
    $oCon = new consulta();
    $oCon->setQuery($query);
    $encabezado = $oCon->getData();
    unset($oCon);

    return $encabezado;
}

function GetDatos($query)
{
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $datos = $oCon->getData();
    unset($oCon);
    return $datos;
}
function CrearQuery($tipo)
{

    $condicion_nit = '';
    if ($_REQUEST['nit'] && $_REQUEST['nit'] != '') {
        if ($tipo == 'Acta_Internacional') {
            $condicion_nit .= " AND ARI.Id_Proveedor=$_REQUEST[nit]";
        } else {
            $condicion_nit .= " AND F.Id_Cliente=$_REQUEST[nit]";
        }
    }


    switch ($tipo) {
        case 'Inventario_Valorizado':
            $query = "SELECT
          #P.Id_Producto,
                (
                CASE
                    WHEN SIK.Id_Bodega != 0 THEN (SELECT Nombre FROM Bodega WHERE Id_Bodega = SIK.Id_Bodega)
                    WHEN SIK.Id_Punto_Dispensacion != 0 THEN (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion = SIK.Id_Punto_Dispensacion)
                END
                ) AS 'Punto/Bodega',
                P.Codigo_Cum, 
                P.Nombre_Comercial AS Producto, 
                CAST(SUM(SIK.Cantidad) AS SIGNED) AS Cantidad, 
                CAST(I.Costo AS UNSIGNED) AS Costo, 
                CAST((SUM(SIK.Cantidad) * I.Costo) AS UNSIGNED) AS Valorizado
                FROM Saldo_Inicial_Kardex SIK 
                INNER JOIN Producto P ON SIK.Id_Producto = P.Id_Producto 
                INNER JOIN (SELECT AVG(Costo) AS Costo, Id_Producto FROM Inventario_Nuevo GROUP BY Id_Producto) I ON SIK.Id_Producto = I.Id_Producto WHERE SIK.Fecha = '$_REQUEST[fecha]' AND I.Costo != 0 AND I.Id_Producto != 1 GROUP BY SIK.Id_Producto";
            break;
        case 'Reporte_Ventas':
            $condicion = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['ffin'] != "" && isset($_REQUEST['ffin'])) {
                $condicion .= " WHERE  DATE(F.Fecha_Documento)>='" . $_REQUEST['fini'] . "' AND DATE(F.Fecha_Documento)<='" . $_REQUEST['ffin'] . "'";
            }
            $query = 'SELECT P.Nombre_Comercial,
          IFNULL(CONCAT(P.Nombre_Comercial, " - ",P.Principio_Activo, " ", P.Cantidad,"", P.Unidad_Medida, " " , P.Presentacion, "\n", P.Invima, " CUM:", P.Codigo_Cum), CONCAT(P.Nombre_Comercial, " LAB-", P.Laboratorio_Comercial)) as Producto,       
          IF(P.Laboratorio_Generico IS NULL,P.Laboratorio_Comercial,P.Laboratorio_Generico) as Laboratorio, 
          P.Embalaje,     
          P.Codigo_Cum as Cum, 
          I.Fecha_Vencimiento as Vencimiento, 
          I.Lote as Lote, 
          I.Costo as Costo_unitario,
          PFV.Cantidad as Cantidad,
          PFV.Precio_Venta as PrecioVenta,
          (PFV.Cantidad*PFV.Precio_Venta) as Subtotal,
          (CASE  
            WHEN P.Gravado = "Si" AND C.Impuesto="Si" THEN "19%" 
            ELSE "0%" 
          END) as Impuesto, DATE(F.Fecha_Documento) as Fecha_Documento, F.Codigo, F.Estado, (SELECT CONCAT(Nombres, " ", Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=F.Id_Funcionario) as Funcionario
          FROM Producto_Factura_Venta PFV
          LEFT JOIN Inventario_Nuevo I
          ON PFV.Id_Inventario_Nuevo = I.Id_Inventario_Nuevo
          LEFT JOIN Producto P ON PFV.Id_Producto = P.Id_Producto
          INNER JOIN Factura_Venta F 
          ON PFV.Id_Factura_Venta=F.Id_Factura_Venta
          INNER JOIN Cliente C 
          ON F.Id_Cliente=C.Id_Cliente
          ' . $condicion;

            break;
        case 'Reporte_Compras':
            $condicion = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['ffin'] != "" && isset($_REQUEST['ffin'])) {
                $condicion .= " WHERE  DATE(OCN.Fecha_Creacion_Compra)>='" . $_REQUEST['fini'] . "' AND DATE(OCN.Fecha_Creacion_Compra)<='" . $_REQUEST['ffin'] . "'";
            }

            $query = 'SELECT  P.Nombre_Comercial,
            IFNULL(CONCAT( P.Principio_Activo, " ", P.Presentacion, " ", P.Concentracion, " (", P.Nombre_Comercial,") ", P.Cantidad," ", P.Unidad_Medida, " " ),CONCAT(P.Nombre_Comercial," LAB-", P.Laboratorio_Comercial)) as Producto, 
            IF(P.Laboratorio_Generico IS NULL,P.Laboratorio_Comercial,P.Laboratorio_Generico) as Laboratorio,
            P.Embalaje,
            P.Codigo_Cum as Cum, 
            POCN.Costo as Costo , 
            POCN.Cantidad as Cantidad,       
            POCN.Total as Subtotal,
            POCN.Iva as Iva, 
            OCN.Codigo,
            OCN.Estado,  
            AR.Codigo as Acta,
            DATE(OCN.Fecha_Creacion_Compra) as Fecha,
            (SELECT CONCAT(Nombres, " ", Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=OCN.Identificacion_Funcionario) as Funcionario ,
            OCN.Observaciones
            FROM Producto_Orden_Compra_Nacional POCN 
            INNER JOIN Producto P ON P.Id_Producto = POCN.Id_Producto
            INNER JOIN Orden_Compra_Nacional OCN ON POCN.Id_Orden_Compra_Nacional=OCN.Id_Orden_Compra_Nacional   
            Left Join Acta_Recepcion AR on AR.Id_Orden_Compra_Nacional = OCN.Id_Orden_Compra_Nacional
                    ' . $condicion;
            break;
        case 'Reporte_Actas':
            $condicion = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['ffin'] != "" && isset($_REQUEST['ffin'])) {
                $condicion .= " WHERE  DATE(AR.Fecha_Creacion)>='" . $_REQUEST['fini'] . "' AND DATE(AR.Fecha_Creacion)<='" . $_REQUEST['ffin'] . "'";
            }

            $query = 'SELECT  PRD.Nombre_Comercial,  IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida),CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Producto,
            IF(PRD.Laboratorio_Generico IS NULL,PRD.Laboratorio_Comercial,PRD.Laboratorio_Generico) as Laboratorio,
            PRD.Embalaje,
            PRD.Codigo_Cum, 
            P.Fecha_Vencimiento,
            P.Lote,
            P.Precio as Costo_Unitario, 
            P.Cantidad, 
            IFNULL(POC.Cantidad,0) as Cantidad_Solicitada,
            (P.Cantidad*P.Precio) as Subtotal,
            (CASE  
            WHEN PRD.Gravado = "Si"  THEN "19%" 
            ELSE "0%" 
            END) as Impuesto,DATE(AR.Fecha_Creacion) as Fecha_Documento,
            (SELECT IFNULL(Fecha_Factura,"Sin Factura") FROM Factura_Acta_Recepcion WHERE Id_Acta_Recepcion = AR.Id_Acta_Recepcion limit 1) AS Fecha_Factura,
            P.Factura, 
            AR.Codigo, AR.Tipo_Acta,
            (
            CASE  
            WHEN AR.Tipo_Acta = "Bodega"  THEN (SELECT Nombre FROM Bodega WHERE Id_Bodega=AR.Id_Bodega)
            ELSE  (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=AR.Id_Punto_Dispensacion)
            END
            ) as Destino, 
            AR.Estado,
            (SELECT Nombre FROM Proveedor WHERE Id_Proveedor=AR.Id_Proveedor LIMIT 1) as Proveedor,
            (SELECT CONCAT(Nombres, " ", Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=AR.Identificacion_Funcionario) as Funcionario
            
            
            FROM Producto_Acta_Recepcion P
            INNER JOIN Producto PRD
            ON P.Id_Producto=PRD.Id_Producto
            INNER JOIN Acta_Recepcion AR ON P.Id_Acta_Recepcion=AR.Id_Acta_Recepcion
            LEFT JOIN Producto_Orden_Compra_Nacional POC
            ON POC.Id_Producto_Orden_Compra_Nacional = P.Id_Producto_Orden_compra' . $condicion;
            break;

        case 'Reporte_Ajuste':

            $condicion = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['ffin'] != "" && isset($_REQUEST['ffin'])) {
                $condicion .= " WHERE  DATE(A.Fecha)>='" . $_REQUEST['fini'] . "' AND DATE(A.Fecha)<='" . $_REQUEST['ffin'] . "'";
            }

            $query = 'SELECT P.Nombre_Comercial, CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida, " ") as Producto,
            IF(P.Laboratorio_Generico IS NULL,P.Laboratorio_Comercial,P.Laboratorio_Generico) as Laboratorio,
            P.Embalaje,P.Codigo_Cum, 
            PAI.Fecha_Vencimiento,
            PAI.Lote,
            PAI.Costo as Costo_Unitario,
            PAI.Cantidad,(PAI.Cantidad*PAI.Costo) as Subtotal,
            PAI.Observaciones, 
            (CASE  
                WHEN P.Gravado = "Si"  THEN "19%" 
                ELSE "0%" 
            END) as Impuesto,
            DATE(A.Fecha) As Fecha_Documento,
            A.Codigo, A.Estado, PAI.Observaciones,
                A.Observacion_Anulacion,
            (SELECT CONCAT(Nombres, " ", Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=A.Identificacion_Funcionario) as Funcionario, (SELECT CONCAT(Nombres, " ", Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=A.Funcionario_Anula) as Funcionario_Anula, DATE(A.Fecha_Anulacion) as Fecha_Anulacion
            FROM Producto_Ajuste_Individual PAI 
            INNER JOIN Producto P ON PAI.Id_Producto=P.Id_Producto
            INNER JOIN Ajuste_Individual A ON PAI.Id_Ajuste_Individual=A.Id_Ajuste_Individual ' . $condicion;


            break;

        case 'Reporte_Remision':
            $condicion = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['ffin'] != "" && isset($_REQUEST['ffin'])) {
                $condicion .= " WHERE  DATE(R.Fecha)>='" . $_REQUEST['fini'] . "' AND DATE(R.Fecha)<='" . $_REQUEST['ffin'] . "'";
            }

            $query = 'SELECT P.Nombre_Comercial,CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida, " ") as Producto, IF(P.Laboratorio_Generico IS NULL,P.Laboratorio_Comercial,P.Laboratorio_Generico) as Laboratorio,
            P.Embalaje,P.Codigo_Cum,
            PR.Fecha_Vencimiento ,PR.Lote, PR.Costo as Costo_Unitario, PR.Precio as Precio_Unitario, PR.Cantidad, (PR.Cantidad*PR.Precio) as Subtotal, 
            PR.Descuento, 
            CONCAT(PR.Impuesto,"%") AS Impuesto, 
            (SELECT  CONCAT(Nombres, " ", Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=R.Identificacion_Funcionario) as Funcionario,
            DATE(R.Fecha) as Fecha_Documento,R.Codigo,R.Tipo,R.Nombre_Origen, R.Nombre_Destino, R.Observaciones, R.Estado, AR.Fecha_Estado, AR.Detalles AS Actividad_Estado,
            R.Inicio_Fase1, R.Fin_Fase1, (SELECT  CONCAT(Nombres, " ", Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=R.Fase_1) as Funcionario_Fase_1, R.Inicio_Fase2, R.Fin_Fase2, (SELECT  CONCAT(Nombres, " ", Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=R.Fase_2) as Funcionario_Fase_2, DATE(R.Fecha_Anulacion) as Fecha_Anulacion, 
            (SELECT  CONCAT(Nombres, " ", Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=R.Funcionario_Anula) as Funcionario_Anula
            FROM Producto_Remision PR
            INNER JOIN Producto P ON PR.Id_Producto=P.Id_Producto
            INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision 
            INNER JOIN (SELECT Id_Remision, DATE(MAX(Fecha)) AS Fecha_Estado, (SELECT Detalles FROM Actividad_Remision WHERE Id_Actividad_Remision = MAX(AR2.Id_Actividad_Remision)) AS Detalles FROM Actividad_Remision AR2 GROUP BY Id_Remision) AR ON R.Id_Remision = AR.Id_Remision ' . $condicion;


            break;

        case 'Reporte_Acta_Remision':
            $condicion = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['ffin'] != "" && isset($_REQUEST['ffin'])) {
                $condicion .= " WHERE  DATE(AR.Fecha)>='" . $_REQUEST['fini'] . "' AND DATE(AR.Fecha)<='" . $_REQUEST['ffin'] . "'";
            }

            $query = 'SELECT  AR.Codigo, PRD.Nombre_Comercial,  IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida),CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Producto,
            IF(PRD.Laboratorio_Generico IS NULL,PRD.Laboratorio_Comercial,PRD.Laboratorio_Generico) as Laboratorio,
            PRD.Embalaje,
            PRD.Codigo_Cum, 
            P.Fecha_Vencimiento,
            P.Lote,          
            P.Cantidad, 
            IFNULL((SELECT  Cantidad FROM Producto_Remision WHERE Id_Producto_Remision=P.Id_Producto_Remision),0) as Cantidad_Enviada,DATE(AR.Fecha) as Fecha_Documento, (SELECT Codigo FROM Remision WHERE Id_Remision = AR.Id_Remision) AS Remision, AR.Tipo, (SELECT Nombre_Origen FROM Remision WHERE Id_Remision=AR.Id_Remision) as Nombre_Origen, (SELECT Nombre_Destino FROM Remision WHERE Id_Remision=AR.Id_Remision) as Nombre_Destino, 
            (SELECT CONCAT(Nombres, " ", Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=AR.Identificacion_Funcionario) as Funcionario


            FROM Producto_Acta_Recepcion_Remision P
            INNER JOIN Producto PRD
            ON P.Id_Producto=PRD.Id_Producto
            INNER JOIN Acta_Recepcion_Remision AR ON P.Id_Acta_Recepcion_Remision=AR.Id_Acta_Recepcion_Remision ' . $condicion
                . ' ORDER BY CONVERT(SUBSTRING(AR.Codigo, 4),UNSIGNED INTEGER)';

            break;

        case 'Reporte_Inventario_Bodega':
            $condicion = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['ffin'] != "" && isset($_REQUEST['ffin'])) {
                $condicion .= " AND  DATE(INF.Fecha_Inicio)>='" . $_REQUEST['fini'] . "' AND DATE(INF.Fecha_Inicio)<='" . $_REQUEST['ffin'] . "'";
            }

            $query = 'SELECT 
            P.Nombre_Comercial,CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida, " ") as Producto, IF(P.Laboratorio_Generico IS NULL,P.Laboratorio_Comercial,P.Laboratorio_Generico) as Laboratorio,
            P.Embalaje,P.Codigo_Cum,
            PIF.Fecha_Vencimiento, 
            PIF.Lote, 
            (SELECT AVG(Costo) FROM Inventario_Nuevo WHERE Id_Producto = PIF.Id_Producto AND Id_Bodega!=0) AS Costo_Promedio_Unitario,
            PIF.Segundo_Conteo as Cantidad_Final,
            PIF.Cantidad_Inventario, 
            (PIF.Cantidad_Inventario-PIF.Segundo_Conteo) as Diferencia, 
            ((PIF.Cantidad_Inventario) * (SELECT AVG(Costo) FROM Inventario_Nuevo WHERE Id_Producto = PIF.Id_Producto AND Id_Bodega!=0 )) AS Valor_Inicial,
            ((PIF.Segundo_Conteo) * (SELECT AVG(Costo) FROM Inventario_Nuevo WHERE Id_Producto = PIF.Id_Producto AND Id_Bodega!=0)) AS Valor_Final,    
            DATE(INF.Fecha_Inicio) AS Fecha_Inicio, 
            DATE(INF.Fecha_Fin) AS Fecha_Fin, 
            (SELECT Nombre FROM Bodega WHERE Id_Bodega=INF.Bodega) AS Bodega,
            (CASE  
                    WHEN INF.Categoria != 0  THEN (SELECT Nombre FROM Categoria WHERE Id_Categoria=INF.Categoria)
                    ELSE "Todas" 
                    END ) as Categoria, INF.Letras,
            (SELECT CONCAT(Nombres," ",Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=INF.Funcionario_Digita) AS Funcionario_Digitador, 
            (SELECT CONCAT(Nombres," ",Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=INF.Funcionario_Cuenta) AS Funcionario_Cuenta, 
            (SELECT CONCAT(Nombres," ",Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=INF.Funcionario_Digita) AS Funcionario_Autorizo        
            FROM Inventario_Fisico INF
            INNER JOIN Producto_Inventario_Fisico PIF ON INF.Id_Inventario_Fisico = PIF.Id_Inventario_Fisico 
            INNER JOIN Producto P ON PIF.Id_Producto = P.Id_Producto
            WHERE INF.Estado = "Terminado" ' . $condicion;
            break;

        case 'Reporte_Inventario_Punto':
            $condicion = '';

            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['ffin'] != "" && isset($_REQUEST['ffin'])) {
                $condicion .= " AND  DATE(INF.Fecha_Inicio)>='" . $_REQUEST['fini'] . "' AND DATE(INF.Fecha_Inicio)<='" . $_REQUEST['ffin'] . "'";
            }

            $query = 'SELECT  P.Nombre_Comercial,CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida, " ") as Producto, IF(P.Laboratorio_Generico IS NULL,P.Laboratorio_Comercial,P.Laboratorio_Generico) as Laboratorio,
            P.Embalaje,P.Codigo_Cum,
            PIF.Fecha_Vencimiento, 
            PIF.Lote, 
            (SELECT AVG(Costo) FROM Inventario_Nuevo WHERE Id_Producto = PIF.Id_Producto AND Id_Bodega!=0) AS Costo_Promedio_Unitario,
            SUM(PIF.Cantidad_Final) AS Cantidad_Final,  SUM(PIF.Primer_Conteo) AS Cantidad_Encontrada, IF(INF.Inventario="Si", PIF.Cantidad_Inventario,SUM(PIF.Segundo_Conteo)) AS Segundo_Conteo
            , IF(INF.Inventario="Si", (SUM(PIF.Cantidad_Final)-PIF.Cantidad_Inventario), (SUM(PIF.Segundo_Conteo)-SUM(PIF.Primer_Conteo)) ) AS Cantidad_Diferencial, 
            ((PIF.Cantidad_Inventario) * (SELECT AVG(Costo) FROM Inventario_Nuevo WHERE Id_Producto = PIF.Id_Producto AND Id_Bodega!=0 )) AS Valor_Inicial,
            ((SUM(PIF.Segundo_Conteo)) * (SELECT AVG(Costo) FROM Inventario_Nuevo WHERE Id_Producto = PIF.Id_Producto AND Id_Bodega!=0)) AS Valor_Final,   DATE(INF.Fecha_Inicio) AS Fecha_Inicio, 
            DATE(INF.Fecha_Fin) AS Fecha_Fin,  (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=INF.Id_Punto_Dispensacion) AS Punto, (SELECT CONCAT(Nombres," ",Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=INF.Funcionario_Digita) AS Funcionario_Digitador, 
            (SELECT CONCAT(Nombres," ",Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=INF.Funcionario_Cuenta) AS Funcionario_Cuenta
            FROM Producto_Inventario_Fisico_Punto PIF 
            INNER JOIN Producto P ON PIF.Id_Producto=P.Id_Producto 
            INNER JOIN Inventario_Fisico_Punto INF ON PIF.Id_Inventario_Fisico_Punto=INF.Id_Inventario_Fisico_Punto ' . $condicion . ' 
            GROUP BY INF.Fecha_Fin, INF.Id_Punto_Dispensacion,PIF.Id_Producto,PIF.Lote
            ORDER BY INF.Id_Inventario_Fisico_Punto DESC ';

            break;

        case 'Reporte_Nota_Credito':

            $condicion = '';

            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['ffin'] != "" && isset($_REQUEST['ffin'])) {
                $condicion .= " AND  DATE(N.Fecha)>='" . $_REQUEST['fini'] . "' AND DATE(N.Fecha)<='" . $_REQUEST['ffin'] . "'";
            }

            $query = 'SELECT PRD.Nombre_Comercial,  IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida),CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Producto,
            IF(PRD.Laboratorio_Generico IS NULL,PRD.Laboratorio_Comercial,PRD.Laboratorio_Generico) as Laboratorio,
            PRD.Embalaje,
            PRD.Codigo_Cum, 
            PFV.Fecha_Vencimiento,
            PFV.Lote,
            PFV.Precio_Venta,
            PFV.Cantidad as Cantidad_Nota,
            (SELECT SUM(Cantidad) FROM Producto_Factura_Venta WHERE Id_Factura_Venta=N.Id_Factura AND Id_Producto=PFV.Id_Producto GROUP BY Id_Producto,Id_Factura_Venta) as Cantidad_Factura,
            (PFV.Cantidad*PFV.Precio_Venta) as Subtotal,
            DATE(N.Fecha) as Fecha_Documento,
            DATE(N.Fecha_Anulacion) as Fecha_Anulacion,
            N.Codigo, 
            N.Estado,
            (SELECT Nombre FROM Cliente WHERE Id_Cliente=N.Id_Cliente) as Cliente, 
            (SELECT Codigo FROM Factura_Venta WHERE Id_Factura_Venta=N.Id_Factura) as Factura, 
            (SELECT CONCAT(Nombres, " ", Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=N.Identificacion_Funcionario) as Funcionario,
            (SELECT CONCAT(Nombres, " ", Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=N.Funcionario_Anula) as Funcionario_Anula,
            N.Observacion
            FROM Producto_Nota_Credito PFV
            INNER JOIN Producto PRD
            ON PFV.Id_Producto=PRD.Id_Producto
            INNER JOIN Nota_Credito N ON PFV.Id_Nota_Credito=N.Id_Nota_Credito' . $condicion;
            break;

        case 'Reporte_Devolucion_Compras':

            $condicion = '';

            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['ffin'] != "" && isset($_REQUEST['ffin'])) {
                $condicion .= " AND  DATE(D.Fecha)>='" . $_REQUEST['fini'] . "' AND DATE(D.Fecha)<='" . $_REQUEST['ffin'] . "'";
            }

            $query = 'SELECT PRD.Nombre_Comercial,  IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida),CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Producto,
            IF(PRD.Laboratorio_Generico IS NULL,PRD.Laboratorio_Comercial,PRD.Laboratorio_Generico) as Laboratorio,
            PRD.Embalaje,
            PRD.Codigo_Cum, 
            POCN.Fecha_Vencimiento,
            POCN.Lote,  POCN.Cantidad, POCN.Costo as Costo_Unitario,
            (POCN.Cantidad*POCN.Costo) as Subtotal, POCN.Impuesto, POCN.Motivo,
            DATE(D.Fecha) as Fecha_Documento, (SELECT Nombre FROM Proveedor WHERE Id_Proveedor=D.Id_Proveedor) as Proveedor,
            (SELECT Nombre FROM Bodega WHERE Id_Bodega=D.Id_Bodega) as Bodega,
            (SELECT CONCAT(Nombres, " ", Apellidos) FROM Funcionario WHERE Identificacion_Funcionario=D.Identificacion_Funcionario) as Funcionario
            FROM Producto_Devolucion_Compra POCN 
            INNER JOIN Producto PRD
            ON PRD.Id_Producto = POCN.Id_Producto 
            INNER JOIN Devolucion_Compra D ON POCN.Id_Devolucion_Compra=D.Id_Devolucion_Compra ' . $condicion;
            break;


        case 'Reporte_Factura':


            $condicion = '';

            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['ffin'] != "" && isset($_REQUEST['ffin'])) {
                $fecha_inicio = $_REQUEST['fini'];
                $fecha_fin = $_REQUEST['ffin'];
            }

            $query = "("."SELECT
            F.Codigo AS Factura,
            DATE(F.Fecha_Documento) AS Fecha_Factura,
            F.Id_Cliente AS NIT_Cliente,
            C.Nombre AS Nombre_Cliente,
            Z.Nombre as Zona_Comercial,
        
            IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
            FROM Producto_Factura PF
            WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto!=0),0) as Gravada,
        
            IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
            FROM Producto_Factura PF
            WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto=0),0) as Excenta,
        
            IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
            FROM Producto_Factura PF
            WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto!=0),0) as Iva,
        
            (IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento)
            FROM Producto_Factura PF
            WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto=0),0) + F.Cuota) as Descuentos,
        
            (IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
            FROM Producto_Factura PF
            WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
            FROM Producto_Factura PF
            WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto=0),0)) AS Total_Venta,
        
            
        
            ((IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
            FROM Producto_Factura PF
            WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
            FROM Producto_Factura PF
            WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto=0),0)) + IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
            FROM Producto_Factura PF
            WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto!=0),0) - (IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento)
            FROM Producto_Factura PF
            WHERE PF.Id_Factura = F.Id_Factura AND PF.Impuesto=0),0) + F.Cuota)) AS Neto_Factura,
        
            (SELECT IFNULL((SUM(I.Costo*PF.Cantidad)),0) FROM Producto_Factura PF INNER JOIN Producto P ON P.Id_Producto = PF.Id_Producto 
                INNER JOIN (SELECT Id_Producto, AVG(Costo) AS Costo FROM Inventario_Nuevo WHERE Id_Bodega != 0 GROUP BY Id_Producto) I ON PF.Id_Producto = I.Id_Producto WHERE PF.Id_Factura = F.Id_Factura AND P.Gravado = 'No') AS Costo_Venta_Exenta,
            
            (SELECT IFNULL((SUM(I.Costo*PF.Cantidad)),0) FROM Producto_Factura PF INNER JOIN Producto P ON P.Id_Producto = PF.Id_Producto 
                INNER JOIN (SELECT Id_Producto, AVG(Costo) AS Costo FROM Inventario_Nuevo WHERE Id_Bodega != 0 GROUP BY Id_Producto) I ON PF.Id_Producto = I.Id_Producto WHERE PF.Id_Factura = F.Id_Factura AND P.Gravado = 'Si') AS Costo_Venta_Gravada,

            F.Estado_Factura AS Estado
        
            FROM Factura F
            INNER JOIN Cliente C
            ON C.Id_Cliente = F.Id_Cliente
            INNER JOIN Zona Z
            ON Z.Id_Zona = C.Id_Zona
            WHERE (DATE(F.Fecha_Documento) BETWEEN '$fecha_inicio' AND '$fecha_fin')
            AND F.Tipo = 'Factura'  
        
            )
            UNION
            (SELECT
            F.Codigo AS Factura,
            DATE(F.Fecha_Documento) as Fecha_Factura, 
            F.Id_Cliente, 
            C.Nombre as Razon_Social,
            Z.Nombre as Zona_Comercial,
            0 AS Gravado, (DFC.Cantidad * DFC.Precio) AS Excento, 0 AS Iva, F.Cuota_Moderadora AS Descuentos, ((DFC.Cantidad * DFC.Precio)) AS Total_Factura, ((DFC.Cantidad * DFC.Precio)-F.Cuota_Moderadora) AS Neto_Factura,
            0 AS Costo_Venta_Exenta,
            0 AS Costo_Venta_Gravada,
            F.Estado_Factura AS Estado
            FROM
            Descripcion_Factura_Capita DFC
            INNER JOIN Factura_Capita F ON DFC.Id_Factura_Capita = F.Id_Factura_Capita
            INNER JOIN Cliente C ON C.Id_Cliente = F.Id_Cliente
            INNER JOIN Zona Z
            ON Z.Id_Zona = C.Id_Zona
            WHERE (DATE(F.Fecha_Documento) BETWEEN '$fecha_inicio' AND '$fecha_fin')  
            )";
            break;

        case 'Reporte_Pendientes_Remision':
            $query = "SELECT 
                P.Nombre_Comercial,
                CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad, ' ',
                        P.Unidad_Medida) AS Nombre,
                P.Codigo_Cum,
                PD.Nombre AS Punto_Dispensacion,
                PR.Cantidad, 
                IFNULL((SELECT Precio FROM Producto_Acta_Recepcion WHERE Id_Producto=PR.Id_Producto ORDER BY Id_Producto_Acta_Recepcion DESC LIMIT 1), (SELECT ROUND(AVG(Costo)) FROM Inventario_Nuevo WHERE Id_Bodega!=0 AND Id_Producto=PR.Id_Producto)) as Costo,
                P.Embalaje, (SELECT Nombre FROM Departamento WHERE Id_Departamento=PD.Departamento) as Departamento, (SELECT IFNULL(SUM(Cantidad),0) FROM Inventario_Nuevo WHERE Id_Punto_Dispensacion=PR.Id_Punto_Dispensacion AND Id_Producto=PR.Id_Producto) as Cantidad_Inventario, (SELECT IFNULL(SUM(Cantidad),0) FROM Inventario_Nuevo WHERE Id_Bodega!=0 AND Id_Producto=PR.Id_Producto) as Cantidad_Inventario_Bodega
            FROM
                Producto_Pendientes_Remision PR
                    INNER JOIN
                Producto P ON PR.Id_Producto = P.Id_Producto
                    INNER JOIN
                Punto_Dispensacion PD ON PR.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion
            ORDER BY Punto_Dispensacion ASC , Nombre_Comercial ASC";

            break;

        case 'Reporte_Acta_Internacional':

            $condicion = '';

            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['ffin'] != "" && isset($_REQUEST['ffin'])) {
                $condicion .= " AND DATE(ARI.Fecha_Creacion)>='" . $_REQUEST['fini'] . "' AND DATE(ARI.Fecha_Creacion)<='" . $_REQUEST['ffin'] . "'";
            }

            $query = '
                SELECT 
            PRD.Nombre_Comercial,
            IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida),
                    CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Producto,
            IF(PRD.Laboratorio_Generico IS NULL,PRD.Laboratorio_Comercial,PRD.Laboratorio_Generico) as Laboratorio,
            PRD.Embalaje,
            PRD.Codigo_Cum,
            ARI.Codigo,
            ARI.Fecha_Creacion,
            OCI.Tasa_Dolar AS Tasa,
            (ARI.Flete_Internacional) AS Flete_Internacional_USD,
            (ARI.Seguro_Internacional) AS Seguro_Internacional_USD,
            ARI.Flete_Nacional,
            ARI.Licencia_Importacion,
            PARI.Cantidad,
            (PARI.Precio) AS Precio_USD,
            (PARI.Subtotal) AS Subtotal_USD,
            PARI.Lote,
            PARI.Fecha_Vencimiento
            FROM Producto_Acta_Recepcion_Internacional PARI
            INNER JOIN Acta_Recepcion_Internacional ARI ON PARI.Id_Acta_Recepcion_Internacional = ARI.Id_Acta_Recepcion_Internacional
            INNER JOIN Orden_Compra_Internacional OCI ON ARI.Id_Orden_Compra_Internacional = OCI.Id_Orden_Compra_Internacional
            INNER JOIN Producto PRD ON PARI.Id_Producto = PRD.Id_Producto
            INNER JOIN Funcionario F ON ARI.Identificacion_Funcionario = F.Identificacion_Funcionario
            INNER JOIN Proveedor PRO ON ARI.Id_Proveedor = PRO.Id_Proveedor'
                . $condicion;
            break;
    }

    return $query;
}

function ValidarKey($key)
{
    $datos = ["Nada", "Excenta", "Iva", "Descuentos", "Total_Venta", "Neto_Factura", "Costo_Venta_Exenta", "Costo_Venta_Gravada", "Gravado", "Total", "Excento", "Total_Factura", "Gravada", "Valorizado", "Costo_unitario",  "Precio_unitario", "PrecioVenta", "Subtotal", "Valor_Final", "Valor_Inicial", "Costo_Promedio_Unitario", "Flete_Internacional_USD", "Seguro_Internacional_USD", "Flete_Nacional", "Licencia_Importacion", "Precio_USD", "Subtotal_USD", "Tasa"];
    $pos = array_search($key, $datos);
    return strval($pos);
}


function ValidarCodigos($key)
{
    $datos = ["Reporte_Acta_Remision"];
    $res = in_array($key, $datos);
    return strval($res);
}
