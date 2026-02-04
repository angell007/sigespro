<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.utility.php');

    $util = new Utility();
    $queryObj = new QueryBaseDatos();

	$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

    $fecha_actual = date('Y-m');
    $fecha_cinco_meses_atras = date('Y-m', strtotime($fecha_actual.' - 4 months'));
    $meses = array("Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic");

    $labels = array();

    $variable_iniciales_fecha = explode("-", $fecha_cinco_meses_atras);
    $mes_final = intval($variable_iniciales_fecha[1]) + date('m') > 12 ?  date('m') : intval($variable_iniciales_fecha[1]) + date('m');

    $datasets_medicamentos = array();
    $datasets_materiales = array();

    $data= array();
    $data2= array();
    $data3= array();
    $data4= array();

    $i=-1;
    for($h=intval($variable_iniciales_fecha[1]);$h<=$mes_final; $h++){ $i++;

        $mes_real = $h > 12 ? $h - 12 : $h;
        $anio_real = $h > 12 ? date('Y') - 1 : date('Y');

        $query_meta_medicamento = '
            SELECT 
                SUM(MC.Valor_Medicamento) as Total_Medicamento,
                 SUM(MC.Valor_Material) as Total_Material
            FROM Meta_Cliente MC 
            INNER JOIN Meta M ON MC.Id_Meta=M.Id_Meta 
            WHERE 
                M.Anio='.$anio_real.' AND MC.Mes='.$mes_real.' AND M.Identificacion_Funcionario = '.$funcionario;

        $queryObj->setQuery($query_meta_medicamento);
        $valores_meta = $queryObj->ExecuteQuery('simple');

        $query_venta_real_medicamento = 'SELECT
        (SUM(r.Subtotal) + SUM(r.IVA)) AS Total
        FROM
        (
            SELECT (PFV.Cantidad*PFV.Precio_Venta) AS Subtotal, 
            (CASE
            WHEN P.Gravado="Si" THEN ((PFV.Cantidad*PFV.Precio_Venta)*(0.19))
            WHEN P.Gravado="No" THEN 0
            END) AS IVA 
            FROM Producto_Factura_Venta PFV 
            INNER JOIN Factura_Venta FV ON PFV.Id_Factura_Venta=FV.Id_Factura_Venta 
            INNER JOIN Producto P ON PFV.Id_Producto = P.Id_Producto 
            WHERE YEAR(FV.Fecha_Documento)='.$anio_real.' 
            AND MONTH(FV.Fecha_Documento)='.$mes_real .' 
            AND FV.Estado != "Anulada" AND P.Id_Categoria = 6 AND Id_Funcionario = '.$funcionario.'
        ) r'; 

        $queryObj->setQuery($query_venta_real_medicamento);
        $valores_venta_medicamento = $queryObj->ExecuteQuery('simple');

        $query_venta_real_material = 'SELECT
        (SUM(r.Subtotal) + SUM(r.IVA)) AS Total
        FROM
        (
            SELECT (PFV.Cantidad*PFV.Precio_Venta) AS Subtotal, 
            (CASE
            WHEN P.Gravado="Si" THEN ((PFV.Cantidad*PFV.Precio_Venta)*(0.19))
            WHEN P.Gravado="No" THEN 0
            END) AS IVA 
            FROM Producto_Factura_Venta PFV 
            INNER JOIN Factura_Venta FV ON PFV.Id_Factura_Venta=FV.Id_Factura_Venta 
            INNER JOIN Producto P ON PFV.Id_Producto = P.Id_Producto 
            WHERE YEAR(FV.Fecha_Documento)='.$anio_real.' 
            AND MONTH(FV.Fecha_Documento)='.$mes_real .' 
            AND FV.Estado != "Anulada" AND P.Id_Categoria <> 6 AND Id_Funcionario = '.$funcionario.'
        ) r'; 

        $queryObj->setQuery($query_venta_real_material);
        $valores_venta_material = $queryObj->ExecuteQuery('simple');

        $labels[] = $meses[$mes_real-1]." ".$anio_real;

        //VALORES PARA MEDICAMENTOS
        $data[] = (float)$valores_meta['Total_Medicamento'];
        $data2[] = (float)$valores_venta_medicamento['Total'];

        $data3[] = (float)$valores_meta['Total_Material'];
        $data4[] = (float)$valores_venta_material['Total'];
    }

    $final['labels'] = $labels;
    $final['meta_medicamento'] = $data;
    $final['venta_medicamento'] = $data2;
    $final['meta_material'] = $data3;
    $final['venta_material'] = $data4;

    $final['porcentajes_cumplimiento'] = CalcularCumplimiento($final);

    $final['totales_laboratorios'] = GetVentasMateriales();

    echo json_encode($final);

    function CalcularCumplimiento($resultados){
    	$total_meta_medicamento = array_sum($resultados['meta_medicamento']);
    	$total_meta_material = array_sum($resultados['meta_material']);
    	$total_venta_medicamento = array_sum($resultados['venta_medicamento']);
    	$total_venta_material = array_sum($resultados['venta_material']);

    	$porcentaje_cumplimiento_medicamento = $total_meta_medicamento == 0 ? 
    												$total_venta_medicamento == 0 ?  0 : 100
												: (($total_venta_medicamento * 100) / $total_meta_medicamento)*100;
    	$porcentaje_cumplimiento_material = $total_meta_material == 0 ? 
    												$total_venta_material == 0 ?  0 : 100
												: (($total_venta_material * 100) / $total_meta_material)*100;

    	$result = array('cumplimiento_medicamento' => $porcentaje_cumplimiento_medicamento, 'cumplimiento_material' => $porcentaje_cumplimiento_material);

    	return $result;
    	
    }

    function GetVentasMateriales(){
    	global $queryObj;

    	$query = '
    		SELECT 
				C.Nombre,
				IFNULL((SELECT 
			     	SUM((PRFV.Cantidad * (PRFV.Precio_Venta + (PRFV.Precio_Venta * (PRFV.Impuesto/100))))) 
			     FROM Producto_Factura_Venta PRFV 
			     INNER JOIN Factura_Venta FAV ON PRFV.Id_Factura_Venta = FAV.Id_Factura_Venta
			     INNER JOIN Producto P ON PRFV.Id_Producto = P.Id_Producto
			    WHERE
			    	P.Laboratorio_Comercial <> "Optimal Quality" AND FAV.Id_Cliente = FV.Id_Cliente), 0) AS Total_Otros,
				IFNULL((SELECT 
			     	SUM((PRFV.Cantidad * (PRFV.Precio_Venta + (PRFV.Precio_Venta * (PRFV.Impuesto/100))))) 
			     FROM Producto_Factura_Venta PRFV 
			     INNER JOIN Factura_Venta FAV ON PRFV.Id_Factura_Venta = FAV.Id_Factura_Venta
			     INNER JOIN Producto P ON PRFV.Id_Producto = P.Id_Producto
			    WHERE
			    	P.Laboratorio_Comercial = "Optimal Quality" AND FAV.Id_Cliente = FV.Id_Cliente), 0) AS Total_Optimal
			FROM Producto_Factura_Venta PFV
			INNER JOIN Factura_Venta FV ON PFV.Id_Factura_venta = FV.Id_Factura_venta
			INNER JOIN Cliente C ON FV.Id_Cliente = C.Id_Cliente
			GROUP BY FV.Id_Cliente';

		$queryObj->SetQuery($query);
		$totales_clientes = $queryObj->ExecuteQuery('multiple');

		$i=-1;
		foreach($totales_clientes as $cli){$i++;
		    $totales_clientes[$i]["Nombre_Corto"]=substr($cli["Nombre"], 0, 15)."...";
		}

		return $totales_clientes;
    }
?>