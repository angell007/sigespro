<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Medios_Magneticos.xls"');
header('Cache-Control: max-age=0'); 

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

if ($id) {
    $oItem = new complex('Formato_Agrupacion_Medio_Magnetico','Id_Formato_Agrupacion_Medio_Magnetico',$id);
    $datos = $oItem->getData();
    unset($oItem);

    $datos_medio_magnetico = cuentasAndTiposDocumentos($id);

    $movimientos = armarDatos($datos_medio_magnetico);


    $contenido = '
    <table border="1">
        <tr>
            <td colspan="12">
                <strong>Formato '.$datos['Codigo_Formato'].' para Medios Magneticos '.$datos['Nombre_Formato'].'</strong>
            </td>
        </tr>
        <tr>
            <th>Concepto</th>
            <th>Nit</th>
            <th>Digito Verificaci&oacute;n</th>
            <th>Primer Apellido</th>
            <th>Segundo Apellido</th>
            <th>Primer Nombre</th>
            <th>Segundo Nombre</th>
            <th>Razon Social</th>
            <th>Actividad</th>
            <th>Direcci&oacute;n</th>
            <th>Departamento</th>
            <th>Municipio</th>';
            foreach ($datos_medio_magnetico as $value) {
                $name = $value['Columna_Principal'] ? trim($value['Columna_Principal']) : trim($value['Nombre_Formato']);
                $contenido .= '<th>'.$name.'</th>';
            }
    $contenido .= '</tr>';

    foreach ($movimientos as $mov) {
        $contenido .= '
        <tr>
            <td>'.$mov['Concepto'].'</td>
            <td>'.$mov['Nit'].'</td>
            <td>'.$mov['Digito_Verificacion'].'</td>
            <td>'.$mov['Primer_Apellido'].'</td>
            <td>'.$mov['Segundo_Apellido'].'</td>
            <td>'.$mov['Primer_Nombre'].'</td>
            <td>'.$mov['Segundo_Nombre'].'</td>
            <td>'.$mov['Razon_Social'].'</td>
            <td>'.$mov['Actividad'].'</td>
            <td>'.$mov['Direccion'].'</td>
            <td>'.$mov['Departamento'].'</td>
            <td>'.$mov['Municipio'].'</td>';
            foreach ($datos_medio_magnetico as $value) {
                $index = $value['Columna_Principal'] ? trim($value['Columna_Principal']) : trim($value['Nombre_Formato']);
                $contenido .= '<td>'.$mov[$index].'</td>';
            }
        $contenido .= '</tr>';
    }

      

    $contenido .= '</table>';

    echo $contenido;

}

function getIdsTiposDocumentos($tipos_documentos) {
    $ids = [];
    if ($tipos_documentos) {
        foreach ($tipos_documentos as $value) {
            $ids[] = $value['Tipo'];
        }
    }

    return implode(',',$ids);
}

function movimientos($ids_modulos, $id_medio_magnetico, $periodo,$tipo_exp) {
    
    $cond_mod='';
    if($ids_modulos!=''){
     $cond_mod='AND MC.Id_Modulo IN ($ids_modulos)';   
    }
    
    $query = "SELECT MC.Nit, SUM(MC.Debe) AS Debe, SUM(MC.Haber) AS Haber, (SELECT Naturaleza FROM Plan_Cuentas WHERE Id_Plan_Cuentas = MC.Id_Plan_Cuenta) AS Naturaleza, MMC.Concepto, T.* FROM Movimiento_Contable MC 
     INNER JOIN (
    (SELECT Id_Cliente AS Nit, Digito_Verificacion, Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, Razon_Social, Direccion, (SELECT Codigo FROM Departamento WHERE Id_Departamento = C.Id_Departamento) AS Departamento, (SELECT Codigo_Dane FROM Municipio WHERE Id_Municipio = C.Id_Municipio) AS Municipio, 'Cliente' COLLATE latin1_swedish_ci AS Tipo, (SELECT Codigo FROM Codigo_Ciiu WHERE Id_Codigo_Ciiu = C.Id_Codigo_Ciiu) AS Actividad FROM Cliente C)
    UNION
    (SELECT Id_Proveedor AS Nit, Digito_Verificacion, Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, Razon_Social, Direccion, (SELECT Codigo FROM Departamento WHERE Id_Departamento = C.Id_Departamento) AS Departamento, (SELECT Codigo_Dane FROM Municipio WHERE Id_Municipio = C.Id_Municipio) AS Municipio, 'Proveedor' COLLATE latin1_swedish_ci AS Tipo, (SELECT Codigo FROM Codigo_Ciiu WHERE Id_Codigo_Ciiu = C.Id_Codigo_Ciiu) AS Actividad FROM Proveedor C)
    UNION
    (SELECT Identificacion_Funcionario AS Nit, '', Nombres, '', Apellidos, '', '', Direccion_Residencia, '' AS Departamento, '' AS Municipio, 'Funcionario' COLLATE latin1_swedish_ci AS Tipo, '' AS Actividad FROM Funcionario C)
    
    ) T ON MC.Nit = T.Nit AND MC.Tipo_Nit = T.Tipo
    INNER JOIN Medio_Magnetico_Cuentas MMC ON MC.Id_Plan_Cuenta = MMC.Id_Plan_Cuenta
    WHERE MC.Estado != 'Anulado'  ".$cond_mod." AND YEAR(Fecha_Movimiento) = $periodo AND MMC.Id_Medio_Magnetico = $id_medio_magnetico
    GROUP BY MMC.Concepto, MC.Nit";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    unset($oCon);
    
    
    if($tipo_exp=='Saldo'){
    $nits='';
    $resultado3='';
	    /*foreach($resultado as $res){
	    	$nits.=$res["Nit"].",";
	    }
    	    $nits=trim($nits,","); */
	    $query = "SELECT 
	        MMC.Concepto AS Concepto, BIC.Nit AS NIT_Final, 0 AS Debe, 0 AS Haber, PC.Naturaleza AS Naturaleza, T.*        
	        FROM Plan_Cuentas PC
	        INNER JOIN  (SELECT * FROM Balance_Inicial_Contabilidad WHERE Fecha = '2018-12-31') BIC ON BIC.Id_Plan_Cuentas = PC.Id_Plan_Cuentas
	        INNER JOIN Medio_Magnetico_Cuentas MMC ON PC.Id_Plan_Cuentas = MMC.Id_Plan_Cuenta
	        INNER JOIN (
	            (SELECT Id_Cliente AS Nit, Digito_Verificacion, Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, Razon_Social, Direccion, (SELECT Codigo FROM Departamento WHERE Id_Departamento = C.Id_Departamento) AS Departamento, (SELECT Codigo_Dane FROM Municipio WHERE Id_Municipio = C.Id_Municipio) AS Municipio, 'Cliente' COLLATE latin1_swedish_ci AS Tipo, (SELECT Codigo FROM Codigo_Ciiu WHERE Id_Codigo_Ciiu = C.Id_Codigo_Ciiu) AS Actividad FROM Cliente C)
	            UNION
	            (SELECT Id_Proveedor AS Nit, Digito_Verificacion, Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, Razon_Social, Direccion, (SELECT Codigo FROM Departamento WHERE Id_Departamento = C.Id_Departamento) AS Departamento, (SELECT Codigo_Dane FROM Municipio WHERE Id_Municipio = C.Id_Municipio) AS Municipio, 'Proveedor' COLLATE latin1_swedish_ci AS Tipo, (SELECT Codigo FROM Codigo_Ciiu WHERE Id_Codigo_Ciiu = C.Id_Codigo_Ciiu) AS Actividad FROM Proveedor C)
	            UNION
	            (SELECT Identificacion_Funcionario AS Nit, '', Nombres, '', Apellidos, '', '', Direccion_Residencia, '' AS Departamento, '' AS Municipio, 'Funcionario' COLLATE latin1_swedish_ci AS Tipo, '' AS Actividad FROM Funcionario C)
	
	            ) T ON BIC.Nit = T.Nit AND BIC.Tipo = T.Tipo
	        WHERE MMC.Id_Medio_Magnetico = $id_medio_magnetico
	        GROUP BY Concepto, NIT_Final";
	    
	    $oCon = new consulta();
	    $oCon->setQuery($query);
	    $oCon->setTipo('Multiple');
	    $resultado2 = $oCon->getData();
	    unset($oCon);
	    
	    foreach($resultado2 as $res2){
		$search_items = array('Nit'=>$res2["NIT_Final"], 'Concepto'=>$res2["Concepto"]); 
		
		$resp = search($resultado, $search_items); 
		
		if(count($resp)==0){
		   $resultado3[]=$res2;
		}
	    }
	   $resultado = array_merge($resultado, $resultado3);
    }

    return $resultado;
}

function valor($nat,$tipo_exportacion,$debe,$haber) {
    $valor = '0';
    switch ($tipo_exportacion) {
        case 'D':
            $valor = $debe;
            break;
        case 'C':
            $valor = $haber;
            break;
        case 'D-C':
            $valor = $debe-$haber;
            break;
        case 'C-D':
            $valor = $haber-$debe;
            break;
        case 'Saldo':
            if ($nat == 'D') {
                $valor = $debe-$haber;
            } else {
                $valor = $haber-$debe;
            }
            break;
    }

    return number_format($valor,2,",","");
}

function cuentasAndTiposDocumentos($id) {
    $query = "SELECT MM.Id_Medio_Magnetico, MM.Tipo_Exportacion, MM.Nombre_Formato, MM.Columna_Principal, MM.Periodo, MM.Detalles, MM.Tipos FROM Medio_Magnetico_Agrupacion MMA INNER JOIN (SELECT * FROM Medio_Magnetico WHERE Tipo_Medio_Magnetico = 'Especial') MM ON MM.Id_Medio_Magnetico = MMA.Id_Medio_Magnetico_Especial WHERE Id_Formato_Agrupacion_Medio_Magnetico = $id ORDER BY MM.Tipo_Columna DESC";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    unset($oCon);

    return $resultado;
}

function armarDatos($datos_medio_magnetico) {

    $movimientos = [];
    $columna_principal = '';
    $columnas_alternas = [];
    
    if ($datos_medio_magnetico) {
        foreach ($datos_medio_magnetico as $i => $value) {
            $tipos_documentos = json_decode($value['Tipos'],true);
            unset($tipos_documentos[count($tipos_documentos)-1]);

            $ids_modulos = getIdsTiposDocumentos($tipos_documentos);

            $res = movimientos($ids_modulos,$value['Id_Medio_Magnetico'],$value['Periodo'],$value["Tipo_Exportacion"]);

            foreach ($res as $z => $mov) {
                $index = $value['Columna_Principal'] ? trim($value['Columna_Principal']) : trim($value['Nombre_Formato']);

                if ($i == 0) {

                    $columna_principal = $index;

                    foreach ($datos_medio_magnetico as $v) { // Inicializar las posiciones de las columnas de los reportes que se van a usar en el reporte
                        $name_col = $v['Columna_Principal'] ? trim($v['Columna_Principal']) : trim($v['Nombre_Formato']);
                        $mov[$name_col] = '0.00';
                    }
                    
                    $mov[$index] = valor($mov['Naturaleza'],$value['Tipo_Exportacion'],$mov['Debe'],$mov['Haber']);
                    $movimientos[] = $mov;
                } else {
                    if (in_array($mov['Nit'], array_column($movimientos, 'Nit'))) { // Buscar si existe el nit en el array
                        $pos = array_search($mov['Nit'], array_column($movimientos, 'Nit')); // Obtener la posicion del array del nit existente.
                        $movimientos[$pos][$index] = valor($mov['Naturaleza'],$value['Tipo_Exportacion'],$mov['Debe'],$mov['Haber']);
                    } else {
                        $mov[$columna_principal] = '0.00';

                        if (count($columnas_alternas) > 0) { // Inicializar las posiciones de las columnas de los formatos alternos.
                            foreach ($columnas_alternas as $name) {
                                $mov[$name] = '0.00';
                            }
                        }
                        
                        $mov[$index] = valor($mov['Naturaleza'],$value['Tipo_Exportacion'],$mov['Debe'],$mov['Haber']);
                        $movimientos[] = $mov;
                        $columnas_alternas[] = $index;
                    }
                }
            }
        }
    }

    return $movimientos;
}
function search($array, $search_list) { 
    $result = array(); 
  
    foreach ($array as $key => $value) { 
   
        foreach ($search_list as $k => $v) { 
      
            if (!isset($value[$k]) || $value[$k] != $v) 
            { 
                continue 2; 
            } 
        } 
        $result[] = $value; 
    } 
    return $result; 
} 
?>