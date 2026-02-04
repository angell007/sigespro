<?php

function listaCartera($nit, $id_plan_cuenta, $fecha=false) {
    $condicion_cuenta = '';

    if ($id_plan_cuenta) {
        $condicion_cuenta .= " AND MC.Id_Plan_Cuenta = $id_plan_cuenta";
    }
    
    if( $fecha ){
        $condicion_cuenta .= " AND DATE(MC.Fecha_Movimiento) <= '$fecha' ";
    }

    $query = "SELECT 
    MC.Id_Plan_Cuenta,
    PC.Codigo,
    PC.Nombre,
    DATE_FORMAT(MC.Fecha_Movimiento, '%d/%m/%Y') AS Fecha,
    MC.Documento AS Factura,	
    MC.Id_Registro_Modulo AS Id_Factura,
    (CASE PC.Naturaleza
        WHEN 'C' THEN (SUM(MC.Haber))
        ELSE (SUM(MC.Debe))
    END) AS Valor_Factura,
    (CASE PC.Naturaleza
        WHEN 'C' THEN (SUM(MC.Debe))
        ELSE (SUM(MC.Haber))
    END) AS Valor_Abono,
    (CASE PC.Naturaleza
        WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
        ELSE (SUM(MC.Debe) - SUM(MC.Haber))
    END) AS Valor_Saldo,
    PC.Naturaleza AS Nat,
    SUM(MC.Debe) AS Movimiento_Debito,
    SUM(MC.Haber) AS Movimiento_Credito,
    (
        CASE
            WHEN PC.Naturaleza = 'D' AND SUM(MC.Debe) > SUM(MC.Haber) THEN 'C'
            WHEN PC.Naturaleza = 'D' AND SUM(MC.Haber) > SUM(MC.Debe) THEN 'D'
            WHEN PC.Naturaleza = 'C' AND SUM(MC.Haber) > SUM(MC.Debe) THEN 'D'
            WHEN PC.Naturaleza = 'C' AND SUM(MC.Debe) > SUM(MC.Haber) THEN 'C'
        END
    ) AS Movimiento,
    0 AS Abono
    FROM
    Movimiento_Contable MC
        INNER JOIN
    Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
    WHERE
    MC.Nit = $nit AND MC.Estado != 'Anulado'
        AND (PC.Codigo LIKE '2335%' OR PC.Codigo LIKE '220501' OR PC.Codigo LIKE '13%') AND PC.Codigo NOT LIKE '1355%'
        $condicion_cuenta
    GROUP BY MC.Id_Plan_Cuenta, MC.Documento HAVING Valor_Saldo != 0 ORDER BY MC.Fecha_Movimiento";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
  
    $facturas = $oCon->getData();
    unset($oCon);
    

    return $facturas;
}

function cambiarEstadoFactura($nit,$documento,$id_plan_cuenta) {

   $documento = substr($documento, 0, -1); 
   // if ($id_plan_cuenta == 57 || $id_plan_cuenta == 272) { // Si el plan de cuenta es 13 ó 22

    $query = "
    
        UPDATE Factura_Acta_Recepcion  FAR 
         INNER JOIN Acta_Recepcion AR ON FAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion 
         SET FAR.Estado = 'Pagada'
         WHERE FAR.Estado = 'Pendiente' AND FAR.Factura IN ('$documento') /*AND AR.Id_Proveedor = $nit */
        AND EXISTS(SELECT
        MC.Nit,
        MC.Documento,
        (CASE PC.Naturaleza
            WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
            ELSE (SUM(MC.Debe) - SUM(MC.Haber))
        END) AS Valor_Saldo
        FROM
        Movimiento_Contable MC
            INNER JOIN
        Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
        
        WHERE
        MC.Nit = AR.Id_Proveedor AND MC.Documento = FAR.Factura AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 272

        GROUP BY MC.Id_Plan_Cuenta, MC.Documento HAVING Valor_Saldo = 0)
    ";
    //$query = getQueryEstadoFactura($nit,$documento);

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->createData();
    unset($oCon);
    $query = "

        UPDATE  Factura F
        SET F.Estado_Factura = 'Pagada'
        WHERE F.Estado_Factura = 'Sin Cancelar' /*AND F.Id_Cliente = $nit*/ AND F.Codigo IN ('$documento') 
        AND EXISTS(SELECT
        MC.Nit,
        MC.Documento,
        (CASE PC.Naturaleza
            WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
            ELSE (SUM(MC.Debe) - SUM(MC.Haber))
        END) AS Valor_Saldo
        FROM
        Movimiento_Contable MC
            INNER JOIN
        Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
        
        

        WHERE
        MC.Nit = F.Id_Cliente AND MC.Documento = F.Codigo AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 57
          
        GROUP BY MC.Id_Plan_Cuenta, MC.Documento HAVING Valor_Saldo = 0)
    ";
  
    //echo $query;
        $oCon = new consulta();
        $oCon->setQuery($query);
        $resultado = $oCon->createData();
        unset($oCon);
    $query="   
        UPDATE  Factura_Venta F
        SET F.Estado = 'Pagada'
        WHERE F.Estado = 'Pendiente' /* AND F.Id_Cliente = $nit */ AND F.Codigo IN ( '$documento') 
        AND EXISTS(SELECT
        MC.Nit,
        MC.Documento,
        (CASE PC.Naturaleza
            WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
            ELSE (SUM(MC.Debe) - SUM(MC.Haber))
        END) AS Valor_Saldo
        FROM
        Movimiento_Contable MC
            INNER JOIN
        Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas

        
        WHERE
        MC.Nit = F.Id_Cliente AND MC.Documento = F.Codigo AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 57
          
        GROUP BY MC.Id_Plan_Cuenta, MC.Documento HAVING Valor_Saldo = 0)
    ";
   

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->createData();
    unset($oCon);
    $query = "
   
        UPDATE  
         Factura_Capita F 

         SET F.Estado_Factura = 'Pagada'
        
        WHERE F.Estado_Factura = 'Sin Cancelar' /* AND F.Id_Cliente = $nit */ AND F.Codigo IN ( '$documento' ) 
        AND EXISTS(SELECT
        MC.Nit,
        MC.Documento,
        (CASE PC.Naturaleza
            WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
            ELSE (SUM(MC.Debe) - SUM(MC.Haber))
        END) AS Valor_Saldo
        FROM
        Movimiento_Contable MC
            INNER JOIN
        Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas

       
        WHERE
        MC.Nit = F.Id_Cliente AND MC.Documento = F.Codigo AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 57
         
        GROUP BY MC.Id_Plan_Cuenta, MC.Documento HAVING Valor_Saldo = 0)
    ";

    
    

        $oCon = new consulta();
        $oCon->setQuery($query);
        $resultado = $oCon->createData();
        unset($oCon);

       /*  if ($resultado) {
            switch ($resultado['Tipo_Factura']) {
                case 'Acta_Recepcion':
                    $oItem = new complex('Factura_Acta_Recepcion','Id_Factura_Acta_Recepcion',$resultado['Id_Factura']);
                    $oItem->Estado = 'Pagada';
                    $oItem->save();
                    unset($oItem);
                    break;
                
                case 'NoPos':
                    $oItem = new complex('Factura','Id_Factura',$resultado['Id_Factura']);
                    $oItem->Estado_Factura = 'Pagada';
                    $oItem->save();
                    unset($oItem);
                    break;
                case 'Comerciales':
                    $oItem = new complex('Factura_Venta','Id_Factura_Venta',$resultado['Id_Factura']);
                    $oItem->Estado = 'Pagada';
                    $oItem->save();
                    unset($oItem);
                    break;
                case 'Capita':
                    $oItem = new complex('Factura_Capita','Id_Factura_Capita',$resultado['Id_Factura']);
                    $oItem->Estado_Factura = 'Pagada';
                    $oItem->save();
                    unset($oItem);
                    break;
            }
        } */
  //  }



}

function getQueryEstadoFactura($nit,$documento) {
             # AND (PC.Codigo LIKE '2335%' OR PC.Codigo LIKE '220501' OR PC.Codigo LIKE '13%') AND PC.Codigo NOT LIKE '1355%'
  
    $query = "
    (
        UPDATE Factura_Acta_Recepcion  FAR 
         INNER JOIN Acta_Recepcion AR ON FAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion 
         WHERE FAR.Estado = 'Pendiente' AND FAR.Factura IN ('$documento') /*AND AR.Id_Proveedor = $nit */
        AND EXISTS(SELECT
        MC.Nit,
        MC.Documento,
        (CASE PC.Naturaleza
            WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
            ELSE (SUM(MC.Debe) - SUM(MC.Haber))
        END) AS Valor_Saldo
        FROM
        Movimiento_Contable MC
            INNER JOIN
        Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
        
        SET FAR.Estado = 'Pagada'
        
        WHERE
        MC.Nit = AR.Id_Proveedor AND MC.Documento = FAR.Factura AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 272

        GROUP BY MC.Id_Plan_Cuenta, MC.Documento HAVING Valor_Saldo = 0)
    )
    UNION(
        SELECT F.Id_Factura, 'NoPos' AS Tipo_Factura FROM Factura F WHERE F.Estado_Factura = 'Sin Cancelar' /*AND F.Id_Cliente = $nit*/ AND F.Codigo IN ('$documento') 
        AND EXISTS(SELECT
        MC.Nit,
        MC.Documento,
        (CASE PC.Naturaleza
            WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
            ELSE (SUM(MC.Debe) - SUM(MC.Haber))
        END) AS Valor_Saldo
        FROM
        Movimiento_Contable MC
            INNER JOIN
        Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
        
        SET F.Estado_Factura = 'Pagada'

        WHERE
        MC.Nit = F.Id_Cliente AND MC.Documento = F.Codigo AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 57
          
        GROUP BY MC.Id_Plan_Cuenta, MC.Documento HAVING Valor_Saldo = 0)
    )
    UNION(
        SELECT F.Id_Factura_Venta, 'Comerciales' AS Tipo_Factura FROM Factura_Venta F WHERE F.Estado = 'Pendiente' /* AND F.Id_Cliente = $nit */ AND F.Codigo IN ( '$documento') 
        AND EXISTS(SELECT
        MC.Nit,
        MC.Documento,
        (CASE PC.Naturaleza
            WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
            ELSE (SUM(MC.Debe) - SUM(MC.Haber))
        END) AS Valor_Saldo
        FROM
        Movimiento_Contable MC
            INNER JOIN
        Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas

        SET F.Estado = 'Pagada'
        WHERE
        MC.Nit = F.Id_Cliente AND MC.Documento = F.Codigo AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 57
          
        GROUP BY MC.Id_Plan_Cuenta, MC.Documento HAVING Valor_Saldo = 0)
    )
    UNION(
        SELECT F.Id_Factura_Capita, 'Capita' AS Tipo_Factura FROM Factura_Capita F WHERE F.Estado_Factura = 'Sin Cancelar' /* AND F.Id_Cliente = $nit */ AND F.Codigo IN ( '$documento' ) 
        AND EXISTS(SELECT
        MC.Nit,
        MC.Documento,
        (CASE PC.Naturaleza
            WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
            ELSE (SUM(MC.Debe) - SUM(MC.Haber))
        END) AS Valor_Saldo
        FROM
        Movimiento_Contable MC
            INNER JOIN
        Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas

        SET F.Estado_Factura = 'Pagada'
        WHERE
        MC.Nit = F.Id_Cliente AND MC.Documento = F.Codigo AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 57
         
        GROUP BY MC.Id_Plan_Cuenta, MC.Documento HAVING Valor_Saldo = 0)
    )
    ";

    return $query;
}


function getQueryEstadoFactura2($nit,$documento) {
    $query = "
    (
        SELECT FAR.Id_Factura_Acta_Recepcion AS Id_Factura, 'Acta_Recepcion' AS Tipo_Factura FROM Factura_Acta_Recepcion FAR INNER JOIN Acta_Recepcion AR ON FAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion WHERE FAR.Estado = 'Pendiente' AND FAR.Factura = '$documento' AND AR.Id_Proveedor = $nit AND EXISTS(SELECT
        MC.Nit,
        MC.Documento,
        (CASE PC.Naturaleza
            WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
            ELSE (SUM(MC.Debe) - SUM(MC.Haber))
        END) AS Valor_Saldo
        FROM
        Movimiento_Contable MC
            INNER JOIN
        Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
        WHERE
        MC.Nit = AR.Id_Proveedor AND MC.Documento = FAR.Factura AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 272
            AND (PC.Codigo LIKE '2335%' OR PC.Codigo LIKE '220501' OR PC.Codigo LIKE '13%') AND PC.Codigo NOT LIKE '1355%'
        GROUP BY MC.Id_Plan_Cuenta, MC.Documento HAVING Valor_Saldo = 0)
    )
    UNION(
        SELECT F.Id_Factura, 'NoPos' AS Tipo_Factura FROM Factura F WHERE F.Estado_Factura = 'Sin Cancelar' AND F.Id_Cliente = $nit AND F.Codigo = '$documento' AND EXISTS(SELECT
        MC.Nit,
        MC.Documento,
        (CASE PC.Naturaleza
            WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
            ELSE (SUM(MC.Debe) - SUM(MC.Haber))
        END) AS Valor_Saldo
        FROM
        Movimiento_Contable MC
            INNER JOIN
        Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
        WHERE
        MC.Nit = F.Id_Cliente AND MC.Documento = F.Codigo AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 57
            AND (PC.Codigo LIKE '2335%' OR PC.Codigo LIKE '220501' OR PC.Codigo LIKE '13%') AND PC.Codigo NOT LIKE '1355%'
        GROUP BY MC.Id_Plan_Cuenta, MC.Documento HAVING Valor_Saldo = 0)
    )
    UNION(
        SELECT F.Id_Factura_Venta, 'Comerciales' AS Tipo_Factura FROM Factura_Venta F WHERE F.Estado = 'Pendiente' AND F.Id_Cliente = $nit AND F.Codigo = '$documento' AND EXISTS(SELECT
        MC.Nit,
        MC.Documento,
        (CASE PC.Naturaleza
            WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
            ELSE (SUM(MC.Debe) - SUM(MC.Haber))
        END) AS Valor_Saldo
        FROM
        Movimiento_Contable MC
            INNER JOIN
        Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
        WHERE
        MC.Nit = F.Id_Cliente AND MC.Documento = F.Codigo AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 57
            AND (PC.Codigo LIKE '2335%' OR PC.Codigo LIKE '220501' OR PC.Codigo LIKE '13%') AND PC.Codigo NOT LIKE '1355%'
        GROUP BY MC.Id_Plan_Cuenta, MC.Documento HAVING Valor_Saldo = 0)
    )
    UNION(
        SELECT F.Id_Factura_Capita, 'Capita' AS Tipo_Factura FROM Factura_Capita F WHERE F.Estado_Factura = 'Sin Cancelar' AND F.Id_Cliente = $nit AND F.Codigo = '$documento' AND EXISTS(SELECT
        MC.Nit,
        MC.Documento,
        (CASE PC.Naturaleza
            WHEN 'C' THEN (SUM(MC.Haber) - SUM(MC.Debe))
            ELSE (SUM(MC.Debe) - SUM(MC.Haber))
        END) AS Valor_Saldo
        FROM
        Movimiento_Contable MC
            INNER JOIN
        Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
        WHERE
        MC.Nit = F.Id_Cliente AND MC.Documento = F.Codigo AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 57
            AND (PC.Codigo LIKE '2335%' OR PC.Codigo LIKE '220501' OR PC.Codigo LIKE '13%') AND PC.Codigo NOT LIKE '1355%'
        GROUP BY MC.Id_Plan_Cuenta, MC.Documento HAVING Valor_Saldo = 0)
    )
    ";

    return $query;
}

function anularDocumento($datos) {
    $tipo = $datos['Tipo'];
    $id = $datos['Id_Registro'];
    $datos['Modulo'] = $tipo;

    $contabilidad = new contabilizar();
    $anulacion = true;

    if ($id != '') {
        switch ($tipo) {
            case 'Egreso':
            
                $id_modulo = 7;
    
                $oItem = new complex('Documento_Contable','Id_Documento_Contable',$id);
                $data = $oItem->getData();
                $fecha = date('Y-m-d',strtotime($data['Fecha_Documento']));
                if ($contabilidad->validarMesOrAnioCerrado($fecha)) {
                    $datos['Codigo'] = $oItem->getData()['Codigo'];
                    $oItem->Estado = 'Anulada';
                    $oItem->Funcionario_Anula = $datos['Identificacion_Funcionario'];
                    $oItem->Fecha_Anulacion = date("Y-m-d H:i:s");
                    
                    $oItem->save();
                    unset($oItem);
        
                    $contabilidad->AnularMovimientoContable($id, $id_modulo);
    
                    registrarActividad('Anulacion', $datos);
                } else {
                    $anulacion = false;
                }
                

                break;
            
            case 'Notas_Contables':
                $id_modulo = 5;
    
                $oItem = new complex('Documento_Contable','Id_Documento_Contable',$id);
                $data = $oItem->getData();
                $fecha = date('Y-m-d',strtotime($data['Fecha_Documento']));
                if ($contabilidad->validarMesOrAnioCerrado($fecha)) {
                    $datos['Codigo'] = $oItem->getData()['Codigo'];
                    $oItem->Estado = 'Anulada';
                    $oItem->Funcionario_Anula = $datos['Identificacion_Funcionario'];
                    $oItem->Fecha_Anulacion = date("Y-m-d H:i:s");
                    $oItem->save();
                    unset($oItem);
        
                    $contabilidad->AnularMovimientoContable($id, $id_modulo);
    
                    registrarActividad('Anulacion', $datos);
                } else {
                    $anulacion = false;
                }
                

                break;
            
            case 'Notas_Cartera':
                $id_modulo = 29;
    
                $oItem = new complex('Documento_Contable','Id_Documento_Contable',$id);
                $data = $oItem->getData();
                $fecha = date('Y-m-d',strtotime($data['Fecha_Documento']));
                if ($contabilidad->validarMesOrAnioCerrado($fecha)) {
                    $datos['Codigo'] = $oItem->getData()['Codigo'];
                    $oItem->Estado = 'Anulada';
                    $oItem->Funcionario_Anula = $datos['Identificacion_Funcionario'];
                    $oItem->Fecha_Anulacion = date("Y-m-d H:i:s");
                    $oItem->save();
                    unset($oItem);
        
                    $contabilidad->AnularMovimientoContable($id, $id_modulo);
    
                    registrarActividad('Anulacion', $datos);
                } else {
                    $anulacion = false;
                }
                
                break;
            case 'Recibos_Caja':
                $id_modulo = 6;
    
                $oItem = new complex('Comprobante','Id_Comprobante',$id);
                $data = $oItem->getData();
                $fecha = date('Y-m-d',strtotime($data['Fecha_Registro']));
                if ($contabilidad->validarMesOrAnioCerrado($fecha)) {
                    $datos['Codigo'] = $oItem->getData()['Codigo'];
                    $oItem->Estado = 'Anulada';
                    $oItem->Funcionario_Anula = $datos['Identificacion_Funcionario'];
                    $oItem->Fecha_Anulacion = date("Y-m-d H:i:s");
                    
                    $oItem->save();
                    unset($oItem);
        
                    $contabilidad->AnularMovimientoContable($id, $id_modulo);
    
                    registrarActividad('Anulacion', $datos);
                } else {
                    $anulacion = false;
                }
                

                break;
            case 'Activo_Fijo':
                $id_modulo = 27;
    
                $oItem = new complex('Activo_Fijo','Id_Activo_Fijo',$id);
                $data = $oItem->getData();
                $fecha = date('Y-m-d',strtotime($data['Fecha']));
                if ($contabilidad->validarMesOrAnioCerrado($fecha)) {
                    $datos['Codigo'] = $oItem->getData()['Codigo'];
                    $oItem->Estado = 'Anulada';
                    $oItem->Funcionario_Anula = $datos['Identificacion_Funcionario'];
                    $oItem->Fecha_Anulacion = date("Y-m-d H:i:s");
                    
                    $oItem->save();
                    unset($oItem);
        
                    $contabilidad->AnularMovimientoContable($id, $id_modulo);

                    registrarActividad('Anulacion', $datos);
                } else {
                    $anulacion = false;
                }
                
                break;
            case 'Depreciacion':
                $id_modulo = 31;
    
                $oItem = new complex('Depreciacion','Id_Depreciacion',$id);
                $data = $oItem->getData();
                $fecha = date('Y-m-d',strtotime($data['Anio']."-".$data['Mes']."-31"));
                if ($contabilidad->validarMesOrAnioCerrado($fecha)) {
                    $datos['Codigo'] = $oItem->getData()['Codigo'];
                    $oItem->Estado = 'Anulada';
                    $oItem->Funcionario_Anula = $datos['Identificacion_Funcionario'];
                    $oItem->Fecha_Anulacion = date("Y-m-d H:i:s");
                    
                    $oItem->save();
                    unset($oItem);
        
                    $contabilidad->AnularMovimientoContable($id, $id_modulo);
    
                    registrarActividad('Anulacion', $datos);
                } else {
                    $anulacion = false;
                }
                

                break;
            case 'Legalizacion_Gastos':
                $id_modulo = 32;
    
                $oItem = new complex('Gasto_Punto','Id_Gasto_Punto',$id);
                $data = $oItem->getData();
                $fecha = date('Y-m-d',strtotime($data['Fecha']));
                if ($contabilidad->validarMesOrAnioCerrado($fecha)) {
                    $oItem->Estado = 'Anulada';
                    $oItem->save();
                    unset($oItem);

                    $datos['Codigo'] = $data['Codigo'];
        
                    if ($data['Estado'] == 'Aprobado' && $datos['tipo_funcionario'] == 'funcionario_contable') {
                        $contabilidad->AnularMovimientoContable($id, $id_modulo);
                    }

                    registrarActividad('Anulacion', $datos);
                } else {
                    $anulacion = false;
                }
                
                break;
        }

        if ($anulacion) {
            $resultado['titulo'] = 'Exito!';
            $resultado['mensaje'] = 'Se ha anulado correctamente el documento: ' . $datos['Codigo'];
            $resultado['tipo'] = 'success';
        } else {
            $resultado['titulo'] = 'No es posible!';
            $resultado['mensaje'] = "No es posible anular este documento debido a que el mes o el año del documento ha sido cerrado contablemente. Si tienes alguna duda por favor comunicarse al Dpto. Contabilidad.";
            $resultado['tipo'] = 'info';
        }

        return $resultado;
    }

    $resultado['titulo'] = 'Oops!';
    $resultado['mensaje'] = 'Ha ocurrido un error inesperado. Por favor vuelve a intentarlo.';
    $resultado['tipo'] = 'error';

    return $resultado;


}

function registrarActividad($tipo_registro, $datos) {
    switch ($tipo_registro) {
        case 'Anulacion':
            $detalles = "Se anulo el documento contable: $datos[Codigo]";

            $oItem = new complex('Actividad_Contabilidad','Id_Actividad_Contabilidad');
            $oItem->Id_Registro = $datos['Id_Registro'];
            $oItem->Identificacion_Funcionario = $datos['Identificacion_Funcionario'];
            $oItem->Detalles = $detalles;
            $oItem->Estado = $tipo_registro;
            $oItem->Modulo = $datos['Modulo'];
            $oItem->save();
            unset($oItem);
            break;
        
        default:
            # code...
            break;
    }
}

function getTiposDocumentos($tipo = null) {

    if ($tipo === null) {
        $query = "SELECT * FROM Modulo WHERE Estado = 'Activo' AND Prefijo IS NOT NULL";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $resultados = $oCon->getData();
        unset($oCon);
    } else {
        $query = "SELECT Id_Modulo AS value, CONCAT(Prefijo,' - ',Documento) AS label FROM Modulo WHERE Estado = 'Activo' AND Prefijo IS NOT NULL";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $resultados = $oCon->getData();
        unset($oCon);
    }

    return $resultados;
}

function getListaCuentasContables($ng_select = false) {
    $query = '';

    if ($ng_select) {
        $query = "SELECT Id_Plan_Cuentas AS value, CONCAT(Codigo,' - ',Nombre) AS label FROM Plan_Cuentas WHERE Estado = 'Activo' AND Movimiento = 'S'";
    }

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $cuentas = $oCon->getData();
    unset($oCon);

    return $cuentas;
}

function getListaTerceros($tipo = null, $condiciones = '', $limit = '') {
    $condicion = '';

    if ($tipo !== null) {
        if ($tipo == 'Proveedor') {
            $condicion .= " WHERE r.Tipo_Tercero IN ('Proveedor','Acreedor')";
        } else {
            $condicion .= " WHERE r.Tipo_Tercero = '$tipo'";
        }
    }
    
    $query = "SELECT r.* FROM (
        (SELECT Id_Proveedor AS Nit, Rut, Digito_Verificacion, Tipo AS Tipo_Persona, Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, Razon_Social, Nombre AS Nombre_Comercial, Direccion, Telefono AS Telefono_Fijo, Celular AS Telefono_Celular, Correo, (SELECT LPAD(Codigo, 2, '0') FROM Departamento WHERE Id_Departamento = P.Id_Departamento) AS Departamento, '' AS Zona, (SELECT LPAD(Codigo_Dane, 3, '0') FROM Municipio WHERE Id_Municipio = P.Id_Municipio) AS Municipio, Regimen AS Tipo_Regimen, Tipo_Retencion, Animo_Lucro, Ley_1429_2010, (SELECT Descripcion FROM Codigo_Ciiu WHERE Id_Codigo_Ciiu = P.Id_Codigo_Ciiu) AS Actividad_Economica, Tipo_Reteica, Contribuyente AS Gran_Contribuyente, IF(Condicion_Pago IN (0,1), 'Contado', CONCAT(Condicion_Pago,' Dias')) AS Plazo, Estado, Tipo_Tercero, Null as Cupo, '' as Centro_Costo FROM Proveedor P)
        UNION
        (SELECT Id_Cliente AS Nit,Rut, Digito_Verificacion, Tipo AS Tipo_Persona, Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, Razon_Social, Nombre AS Nombre_Comercial, Direccion, Telefono_Persona_Contacto AS Telefono_Fijo, Celular AS Telefono_Celular, Correo_Persona_Contacto AS Correo, (SELECT LPAD(Codigo, 2, '0') FROM Departamento WHERE Id_Departamento = P.Id_Departamento) AS Departamento, (SELECT Nombre FROM Zona WHERE Id_Zona = P.Id_Zona) AS Zona, (SELECT LPAD(Codigo_Dane, 3, '0') FROM Municipio WHERE Id_Municipio = P.Id_Municipio) AS Municipio, Regimen AS Tipo_Regimen, '' AS Tipo_Retencion, Animo_Lucro, '' AS Ley_1429_2010, (SELECT Descripcion FROM Codigo_Ciiu WHERE Id_Codigo_Ciiu = P.Id_Codigo_Ciiu) AS Actividad_Economica, Tipo_Reteica, Contribuyente AS Gran_Contribuyente, IF(Condicion_Pago IN (0,1), 'Contado', CONCAT(Condicion_Pago,' Dias')) AS Plazo, Estado, 'Cliente' AS Tipo_Tercero, Cupo, '' as Centro_Costo FROM Cliente P)
        UNION
        (
                        SELECT
                        P.Identificacion_Funcionario AS Nit,
                        '' AS Rut,
                        '' AS Digito_Verificacion,
                        'Natural' AS Tipo_Persona,
                        ifnull(Primer_Nombre,  Substring_index(trim(P.Nombres), ' ', 1)) as Primer_Nombre,
                        ifnull(Segundo_Nombre, if(TRIM(P.Nombres) like'% %', Substring_index(trim(P.Nombres), ' ', -1), '')) as Segundo_Nombre,
                        ifnull(Primer_Apellido, Substring_index(trim(P.Apellidos), ' ', 1)) as Primer_Apellido,
                        ifnull(Segundo_Apellido,if(TRIM(P.Apellidos) like'% %', Substring_index(trim(P.Apellidos), ' ', -1), '')) as Segundo_Apellido, 
                        ''AS Razon_Social,
                        '' AS Nombre_Comercial,
                        Direccion_Residencia AS Direccion,
                        Telefono AS Telefono_Fijo,
                        Celular AS Telefono_Celular,
                        Correo,
                        (
                        SELECT
                            LPAD(Codigo, 2, '0')
                        FROM
                            Departamento
                        WHERE
                            Id_Departamento = M.Id_Departamento
                    ) AS Departamento,
                    '' AS Zona,
                    LPAD(M.Nombre_Municipio, 3, '0') AS Municipio,
                    '' AS Tipo_Regimen,
                    '' AS Tipo_Retencion,
                    '' AS Animo_Lucro,
                    '' AS Ley_1429_2010,
                    '' AS Actividad_Economica,
                    '' AS Tipo_Reteica,
                    '' AS Gran_Contribuyente,
                    '' AS Plazo,
                    IF(
                        Autorizado = 'Si',
                        'Activo',
                        'Inactivo'
                    ) AS Estado,
                    'Funcionario' AS Tipo_Tercero,
                    NULL AS Cupo,
                    CC.Nombre AS Centro_Costo
                    FROM Funcionario P
                    LEFT JOIN Contrato_Funcionario FC ON P.Identificacion_Funcionario = FC.Identificacion_Funcionario
                    LEFT JOIN Centro_Costo CC ON CC.Id_Centro_Costo = P.Id_Centro_Costo
                    Left JOIN( SELECT T.Id_Municipio, Codigo_Dane AS Nombre_Municipio, Id_Departamento FROM Municipio T )M
                    ON
                        FC.Id_Municipio = M.Id_Municipio)
        UNION
        (SELECT Nit, '' AS Rut, '' AS Digito_Verificacion, 'Juridico' AS Tipo_Persona, '' AS Primer_Nombre, '' AS Segundo_Nombre, '' AS Primero_Apellido, '' AS Segundo_Apellido, Nombre AS Razon_Social, Nombre AS Nombre_Comercial, '' AS Direccion, '' AS Telefono, '' AS Celular, '' AS Correo, '' AS Departamento, '' AS Zona, '' AS Nombre_Municipio, '' AS Tipo_Regimen, '' AS Tipo_Retencion, '' AS Animo_Lucro, '' AS Ley_1429_2010, '' AS Actividad_Economica, '' AS Tipo_Reteica, '' AS Gran_Contribuyente, '' AS Plazo, 'Activo' AS Estado, 'Caja_Compensacion' AS Tipo_Tercero, Null as Cupo, '' as Centro_Costo  FROM Caja_Compensacion WHERE Nit IS NOT NULL) ORDER BY Nit
    ) r $condicion $condiciones $limit";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    unset($oCon);

    return $resultado;
}

function generarConsecutivoTipoActivo($id) {
    $query = "SELECT PC.Codigo AS Cuenta, TAF.Consecutivo FROM Tipo_Activo_Fijo TAF INNER JOIN Plan_Cuentas PC ON TAF.Id_Plan_Cuenta_PCGA = PC.Id_Plan_Cuentas WHERE TAF.Id_Tipo_Activo_Fijo = $id";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    $consecutivo = $resultado['Cuenta'] . str_pad($resultado['Consecutivo'],5,'0',STR_PAD_LEFT);

    $oItem = new complex('Tipo_Activo_Fijo','Id_Tipo_Activo_Fijo',$id);
    $new_consecutivo = $oItem->Consecutivo + 1;
    $oItem->Consecutivo = $new_consecutivo;
    $oItem->save();
    unset($oItem);

    return $consecutivo;
}

function eliminarBorradorContable($id) {
    $oItem = new complex('Borrador_Contabilidad','Id_Borrador_Contabilidad',$id);
    $oItem->delete();
    unset($oItem);

    return;
}


function resolucionesPorVencer() {
    $query = "SELECT IF(Codigo = '0','',Codigo) AS Documento, Resolucion, Fecha_Fin AS Fecha_Vencimiento, Consecutivo, Numero_Final AS Consecutivo_Final, (Numero_Final - Numero_Inicial) AS Consecutivos_Faltantes, FLOOR((Numero_Final - Numero_Inicial)*0.1) AS Porcentaje_10_Consecutivo, DATE_SUB(Fecha_Fin, INTERVAL 1 MONTH) AS Fecha_Alerta FROM Resolucion WHERE Estado = 'Activo' HAVING (Consecutivo > (Consecutivo_Final-Porcentaje_10_Consecutivo) OR CURDATE() >= Fecha_Alerta)";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resoluciones = $oCon->getData();
    unset($oCon);

    return $resoluciones;
}


?>