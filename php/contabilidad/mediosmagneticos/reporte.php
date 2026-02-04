<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Medios_Magneticos.xls"');
header('Cache-Control: max-age=0'); 

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

if ($id) {
    
    $oItem = new complex('Medio_Magnetico','Id_Medio_Magnetico',$id);
    $datos = $oItem->getData();
    unset($oItem);
    $periodo = $datos['Periodo'];
    
    $tipos_documentos = json_decode($datos['Tipos'],true);
    
    // echo json_encode($datos); exit;
    unset($tipos_documentos[count($tipos_documentos)-1]);
    
    $ids_modulos = getIdsTiposDocumentos($tipos_documentos);
    $src=$_SERVER["DOCUMENT_ROOT"].'/assets/images/logo_dian.png';
    // echo $src; exit;

    if ($datos['Codigo_Formato']=='1001'){
        $contenido = '
        <table border="1">
            <tr>
                <td colspan="12">
                    <img src="'.$src.'" style="width:150px;height:45px" alt="Pro-H Software" />
                    <h2>'.$datos['Codigo_Formato'].'</h2> <br>
                     <strong>'.$datos['Nombre_Formato'].' </strong>
                </td>
            </tr>
            <tr>
                <th>Concepto</th>
                <th>Tipo de documento</th>
                <th>Número identificaci&oacute;n</th>
                <th>Primer apellido</th>
                <th>Segundo apellido</th>
                <th>Primer Nombre</th>
                <th>Segundo Nombre</th>
                <th>Otros nombres</th>
                <th>Razon social</th>
                <th>Direcci&oacute;n</th>
                <th>C&oacute;digo departamento</th>
                <th>C&oacute;digo municipio</th>
                <th>País de residencia</th>
                <th>Pago o abono en cuenta deducible</th>
                <th>Pago o abono en cuenta NO deducible</th>
                <th>IVA mayor valor del gasto, deducible</th>
                <th>IVA mayor valor del gasto, NO deducible</th>
                <th>Retención en la fuente practicada</th>
                <th>Retención en la fuente asumida</th>
                <th>Retención en la fuente practicada IVA Régimen común</th>
                <th>Retención en la fuente practicada IVA no domiciliados</th>
                
            </tr>
        ';
    
        $movimientos = movimientos($ids_modulos,$id,$periodo);
    
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
                <td>'.$mov['Municipio'].'</td>
                <td>'.$mov['Cuenta'].'</td>
                <td>'.$mov['Nombre'].'</td>
                <td>'.valor($mov['Naturaleza'],$datos['Tipo_Exportacion'],$mov['Debe'],$mov['Haber'],$mov["Nit"],$mov["Concepto"]).'</td>
            </tr>
            ';
            // echo $contenido; exit;
        }
    
        $contenido .= '</table>';
    }
    else{
        $contenido = '
        <table border="1">
            <tr>
                <td colspan="12">
                    <img src="'.$src.'" style="width:150px;height:45px" alt="Pro-H Software" />
                    // <strong>Formato '.$datos['Codigo_Formato'].' para Medios Magneticos '.$datos['Nombre_Formato'].' Periodo: '.$datos['Periodo'].'</strong>
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
                <th>Municipio</th>
                <th>Valor</th>
            </tr>
        ';
    
        $movimientos = movimientos($ids_modulos,$id,$periodo);
    
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
                <td>'.$mov['Municipio'].'</td>
                <td>'.valor($mov['Naturaleza'],$datos['Tipo_Exportacion'],$mov['Debe'],$mov['Haber'],$mov["Nit"],$mov["Concepto"]).'</td>
            </tr>
            ';
        }
    
        $contenido .= '</table>';
    }
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

function movimientos($ids_modulos, $id_medio_magnetico, $periodo) {

    GLOBAL $datos;
    
    $cond_mod='';
    if($ids_modulos!=''){
     $cond_mod='AND MC.Id_Modulo IN ($ids_modulos)';   
    }
    
    $query = "
    Select M.*,
    Group_Concat(M.Cuenta) as C_Cuenta,
    Group_Concat(M.Haber) as C_Haber,
    Group_Concat(M.Debe) as C_Debe
    from (
        SELECT
        MMC.Concepto, 
        MC.Nit, 
        ROUND(SUM(MC.Debe)) AS Debe, ROUND(SUM(MC.Haber)) AS Haber, 
            (SELECT Naturaleza FROM Plan_Cuentas WHERE Id_Plan_Cuentas = MC.Id_Plan_Cuenta) AS Naturaleza,
        T.*, 
        PC.Codigo as Cuenta,
         PC.Nombre
    FROM Movimiento_Contable MC
    INNER JOIN (
        (SELECT Id_Cliente AS Id, Digito_Verificacion, Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, Razon_Social, Direccion, (SELECT Codigo FROM Departamento WHERE Id_Departamento = C.Id_Departamento) AS Departamento, (SELECT Codigo_Dane FROM Municipio WHERE Id_Municipio = C.Id_Municipio) AS Municipio, 'Cliente' COLLATE latin1_swedish_ci AS Tipo, (SELECT Codigo FROM Codigo_Ciiu WHERE Id_Codigo_Ciiu = C.Id_Codigo_Ciiu) AS Actividad FROM Cliente C)
        UNION
        (SELECT Id_Proveedor AS Id, Digito_Verificacion, Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, Razon_Social, Direccion, (SELECT Codigo FROM Departamento WHERE Id_Departamento = C.Id_Departamento) AS Departamento, (SELECT Codigo_Dane FROM Municipio WHERE Id_Municipio = C.Id_Municipio) AS Municipio, 'Proveedor' COLLATE latin1_swedish_ci AS Tipo, (SELECT Codigo FROM Codigo_Ciiu WHERE Id_Codigo_Ciiu = C.Id_Codigo_Ciiu) AS Actividad FROM Proveedor C)
        UNION
        (SELECT Identificacion_Funcionario AS Id, '', Nombres, '', Apellidos, '', '', Direccion_Residencia, '' AS Departamento, '' AS Municipio, 'Funcionario' COLLATE latin1_swedish_ci AS Tipo, '' AS Actividad FROM Funcionario C)
        ) T ON MC.Nit = T.Id AND MC.Tipo_Nit = T.Tipo
    INNER JOIN Medio_Magnetico_Cuentas MMC ON MC.Id_Plan_Cuenta = MMC.Id_Plan_Cuenta
    inner Join Plan_Cuentas PC on PC.Id_Plan_Cuentas = MC.Id_Plan_Cuenta
    WHERE MC.Estado != 'Anulado' ".$cond_mod."  AND YEAR(Fecha_Movimiento) = $periodo AND MMC.Id_Medio_Magnetico = $id_medio_magnetico
    GROUP BY MMC.Concepto, MC.Nit, PC.Codigo
    ORDER BY MMC.Concepto, MC.Nit) M
    GROUP BY M.Concepto, M.Nit
    ";
    
    
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    
    
    unset($oCon);
    $concepto =0;
    $nit =0;
    foreach($resultado as $i=> $movimiento){
        
        $cuentas = explode(',', $movimiento['C_Cuenta']);
        $debitos = explode(',', $movimiento['C_Debe']);
        $creditos = explode(',', $movimiento['C_Haber']);
        $iva=[];
        $ret=[];
        foreach($cuentas as $i => $cuenta){
            $inicio = strlen($cuenta)-2;
            $fin = substr($cuenta,$inicio,2) ; 
            $inicio = substr($cuenta,0,1) ; 
            if($inicio =='5' && $fin =='98' ){
                $iva['d'] = $iva['d']? $iva['d']+$debitos[$i]:$debitos[$i];
                $iva['c'] = $iva['c']? $iva['c']+$creditos[$i]:$creditos[$i];
            }
            $inicio = substr($cuenta,0,4) ;
            if($inicio =='2365' ){
                $ret['d'] = $ret['d']? $ret['d']+$debitos[$i]:$debitos[$i];
                $ret['c'] = $ret['c']? $ret['c']+$creditos[$i]:$creditos[$i];
            }
            
        }
        $movimiento['Iva_Creditos'] = $iva['c'];
        $movimiento['Iva_Debitos'] = $iva['d'];
        $movimiento['Ret_Creditos'] = $ret['c'];
        $movimiento['Ret_Debitos'] = $ret['d'];
        
        $resultado2[$i]= $movimiento;
    }
    
    //echo json_encode($resultado2); exit;
    
    
    if($datos['Tipo_Exportacion']=='Saldo'){
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

function valor($nat,$tipo_exportacion,$debe,$haber,$nit=null,$concepto=null) {
    
   // echo $nat." - ".$tipo_exportacion." - ".$debe." - ".$haber; exit;
    $valor = '0';
    switch ($tipo_exportacion) {
        case 'D':
            $valor = $debe;
            break;
        case 'C':
            $valor = $haber;
            break;
        case 'D-C':
            $valor = (FLOAT)$debe-(FLOAT)$haber;
            break;
        case 'C-D':
            $valor = (FLOAT)$haber-(FLOAT)$debe;
            break;
        case 'Saldo':
            
            $saldo_anterior = obtenerSaldoAnterior($nat,$nit,$concepto);
            $valor = $saldo_anterior;
            if ($nat == 'D') {
                $valor += $debe-$haber;
            } else {
                $valor += $haber-$debe;
            }
            break;
    }

    return number_format($valor,2,",","");
}

function obtenerSaldoAnterior($naturaleza,$nit, $concepto)
{
    global $periodo, $id;
    $fecha_ini = $periodo."-01-01";
    
    $query = "SELECT 
        MMC.Concepto, BIC.Nit,
        IFNULL(SUM(BIC.Debito_PCGA), 0) AS Debito,
        IFNULL(SUM(BIC.Credito_PCGA), 0) AS Credito
        FROM Plan_Cuentas PC
        INNER JOIN  (SELECT * FROM Balance_Inicial_Contabilidad WHERE Fecha = '2018-12-31' AND Nit = $nit ) BIC ON BIC.Id_Plan_Cuentas = PC.Id_Plan_Cuentas
        INNER JOIN Medio_Magnetico_Cuentas MMC ON PC.Id_Plan_Cuentas = MMC.Id_Plan_Cuenta
        WHERE MMC.Id_Medio_Magnetico = $id AND MMC.Concepto = $concepto
        ";
        
	$oCon = new consulta();
	$oCon->setQuery($query);
	$array = $oCon->getData();
	unset($oCon);
	
    $saldo_anterior = 0;
    if ($naturaleza == 'D') { // Si es naturaleza debito, suma, de lo contrario, resta
        $saldo_anterior = $array["Debito"] - $array["Credito"];
    } else {
        $saldo_anterior = $array["Credito"] - $array["Debito"];
    }

    return $saldo_anterior;
}
function search($array, $search_list) { 
  
    // Create the result array 
    $result = array(); 
  
    // Iterate over each array element 
    foreach ($array as $key => $value) { 
  
        // Iterate over each search condition 
        foreach ($search_list as $k => $v) { 
      
            // If the array element does not meet 
            // the search condition then continue 
            // to the next element 
            if (!isset($value[$k]) || $value[$k] != $v) 
            { 
                  
                // Skip two loops 
                continue 2; 
            } 
        } 
      
        // Append array element's key to the 
        //result array 
        $result[] = $value; 
    } 
  
    // Return result  
    return $result; 
} 

// function getCampos($codigo){
//     if($codigo =='1001'){
//         return ['Concepto', 'Tipo_de_documento', 'Numero_identificacion'];
//     }
// }
?>