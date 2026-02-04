<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');


$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
$valor = ( isset( $_REQUEST['valor'] ) ? $_REQUEST['valor'] : '' );
$cedula = ( isset( $_REQUEST['cedula'] ) ? $_REQUEST['cedula'] : '' );

$query_fin='';
$error='No';
if($tipo=="Radicado"){
    $query = "SELECT GROUP_CONCAT(DISTINCT(Id_Factura)) as Facturas FROM Radicado_Factura RF INNER JOIN Radicado R ON R.Id_Radicado=RF.Id_Radicado WHERE R.Codigo IN('".str_replace(",","','",str_replace("'","",$valor))."') LIMIT 0, 500"; // 0 - 0 se le asignaron a Diego.
	$oCon = new consulta();
	$oCon->setQuery($query);
	$consulta = $oCon->getData();
	unset($oCon);
   	$in=$consulta["Facturas"];
   	if($in!=''){
   	   $query_fin = "SELECT F.Id_Factura, D.Id_Dispensacion, A.Id_Auditoria, F.Codigo as Factura, F.Fecha_Documento as Fecha_Factura, D.Codigo as Dispensacion, F.Tipo as Tipo_Factura, FH.Codigo as Factura_Original, A.Archivo, D.Firma_Reclamante, D.Acta_Entrega, 
        (SELECT GROUP_CONCAT(CONCAT_WS('',SA.Id_Soporte_Auditoria,';', SA.Tipo_Soporte,';',SA.Cumple,';',SA.Paginas)) FROM Soporte_Auditoria SA WHERE SA.Id_Auditoria=A.Id_Auditoria) as Soportes, IFNULL(FA.Estado,'No') as Actualizada, FA.Id_Factura_Actualizada
        
        FROM Factura F 
        INNER JOIN Dispensacion D ON D.Id_Dispensacion=F.Id_Dispensacion
        LEFT JOIN Z_Factura_Actualizada FA ON FA.Id_Factura = F.Id_Factura
        LEFT JOIN Factura FH ON FH.Id_Factura = F.Id_Factura_Asociada
        LEFT JOIN Auditoria A ON A.Id_Dispensacion = D.Id_Dispensacion
        WHERE F.Id_Factura IN (".$in.")
        AND F.Estado_Factura !='Anulada' AND A.Archivo != '' AND A.Archivo IS NOT NULL
        ORDER BY F.Fecha_Documento ASC"; 
   	}
   	
    
}elseif($tipo=="Factura"){
    $query = "SELECT GROUP_CONCAT(DISTINCT(Id_Factura)) as Facturas FROM Factura F WHERE F.Codigo IN('".str_replace(",","','",str_replace("'","",$valor))."') LIMIT 0, 500"; // 0 - 0 se le asignaron a Diego.
	$oCon = new consulta();
	$oCon->setQuery($query);
	$consulta = $oCon->getData();
	unset($oCon);
   	$in=$consulta["Facturas"];
   	
   	if($in!=""){
   	   $query_fin = "SELECT F.Id_Factura, D.Id_Dispensacion, A.Id_Auditoria, F.Codigo as Factura, F.Fecha_Documento as Fecha_Factura, D.Codigo as Dispensacion, F.Tipo as Tipo_Factura, FH.Codigo as Factura_Original, A.Archivo, D.Firma_Reclamante, D.Acta_Entrega, 
        (SELECT GROUP_CONCAT(CONCAT_WS('',SA.Id_Soporte_Auditoria,';', SA.Tipo_Soporte,';',SA.Cumple,';',SA.Paginas)) FROM Soporte_Auditoria SA WHERE SA.Id_Auditoria=A.Id_Auditoria) as Soportes, IFNULL(FA.Estado,'No') as Actualizada, FA.Id_Factura_Actualizada
        
        FROM Factura F 
        INNER JOIN Dispensacion D ON D.Id_Dispensacion=F.Id_Dispensacion
        LEFT JOIN Z_Factura_Actualizada FA ON FA.Id_Factura = F.Id_Factura
        LEFT JOIN Factura FH ON FH.Id_Factura = F.Id_Factura_Asociada
        LEFT JOIN Auditoria A ON A.Id_Dispensacion = D.Id_Dispensacion
        WHERE F.Id_Factura IN (".$in.")
        AND F.Estado_Factura !='Anulada' AND A.Archivo != '' AND A.Archivo IS NOT NULL
        ORDER BY F.Fecha_Documento ASC"; 
   	}
   	
    
}elseif($tipo=="Dispensacion"){
    $query = "SELECT GROUP_CONCAT(DISTINCT(Id_Dispensacion)) as Dis FROM Dispensacion D WHERE D.Codigo IN('".str_replace(",","','",str_replace("'","",$valor))."') LIMIT 0, 500"; // 0 - 0 se le asignaron a Diego.
	$oCon = new consulta();
	$oCon->setQuery($query);
	$consulta = $oCon->getData();
	unset($oCon);
   	$in=$consulta["Dis"];
   	if($in!=""){
       	$query_fin = "SELECT D.Id_Dispensacion, A.Id_Auditoria, D.Codigo as Dispensacion, A.Archivo, D.Firma_Reclamante, D.Acta_Entrega, 
        (SELECT GROUP_CONCAT(CONCAT_WS('',SA.Id_Soporte_Auditoria,';', SA.Tipo_Soporte,';',SA.Cumple,';',SA.Paginas)) FROM Soporte_Auditoria SA WHERE SA.Id_Auditoria=A.Id_Auditoria) as Soportes, IFNULL(FA.Estado,'No') as Actualizada, FA.Id_Factura_Actualizada
        
        FROM Dispensacion D 
        LEFT JOIN Z_Factura_Actualizada FA ON FA.Id_Dispensacion = D.Id_Dispensacion
        LEFT JOIN Auditoria A ON A.Id_Dispensacion = D.Id_Dispensacion
        WHERE D.Id_Dispensacion IN (".$in.")
        AND D.Estado_Dispensacion !='Anulada' AND A.Archivo != '' AND A.Archivo IS NOT NULL
        ORDER BY D.Fecha_Actual ASC";
   	}
}


if($query_fin!=''){
    $oCon = new consulta();
    $oCon->setQuery($query_fin);
    $oCon->setTipo('Multiple');
    $facturas = $oCon->getData();
    unset($oCon); 
}else{
    
    $error='Si';
}


$i=-1;
$x=1;
?>
<html>
<head><meta charset="gb18030">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
</head>
<body>
    <div class="container">
        <div class="row text-center">
            <h3>Ajuste para Paginación</h3>
            <hr>
        </div>
        <div class="row">
            <form name="formBuscar" id="formBuscar">
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="control-label col-sm-2" for="tipo">Tipo</label>
                        <div class="col-sm-10">
                            <select name="tipo" id="tipo" class="form-control input-sm" required>        
                                <option <?php if($tipo=="Dispensacion"){ ?>selected<?php } ?> value="Dispensacion">Dispensación</option>
                                <option <?php if($tipo=="Factura"){ ?>selected<?php } ?>  value="Factura">Facturación</option>
                                <option <?php if($tipo=="Radicado"){ ?>selected<?php } ?>  value="Radicado">Radicación</option>
                            </select>
                        </div>
                    </div> 
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="control-label col-sm-2" for="valor" id="valor">Valor</label>
                        <div class="col-sm-10">
                            <input type="text" name="valor" value="<?php echo $valor; ?>" class="form-control input-sm" required>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="control-label col-sm-2" for="cedula" id="cedula">Cedula</label>
                        <div class="col-sm-10">
                            <input type="text" name="cedula" value="<?php echo $cedula; ?>" class="form-control input-sm" required>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">        
                        <div class="col-sm-offset-2 col-sm">
                            <button type="submit" class="btn btn-danger btn-block">Buscar</button>
                        </div>
                    </div>
                </div>           
            </form>
            <div class="col-md-12">
            <hr>
            </div>
        </div>
<?php
if($error=='No'){
    echo '<h4 style="margin: 0 auto;text-align:center;">'.$titulo.'</h4><br>';
    echo '<table border="1" cellpadding="0" cellspacing="0" style="margin: 0 auto;margin-bottom:30px;">
            <tr>
                <td>#</td>
                <td style="padding:5px;">Fecha Factura</td>
                <td style="padding:5px;">Factura</td>
                <td style="padding:5px;">Dispensacion</td>
                <td style="padding:5px;">Tipo</td>
                <td style="padding:5px;">Factura Original</td>
                <td style="padding:5px;">Archivo</td>
                <td style="padding:5px;">Firma</td>
                <td style="padding:5px;">Acta Entrega</td>
                <td style="padding:5px;">Soportes</td>
            </tr>
    ';
    
    foreach($facturas as $fact){ $i++;
        $soportes = explode(",",$fact["Soportes"]);
        echo '<tr id="fila'.$i.'" class="'.($fact["Actualizada"] == 'Si' ? 'bg-success' : '' ).'">
            <td style="padding:5px;">'.$x.'</td>
            <td style="padding:5px;">'.$fact["Fecha_Factura"].'</td>
            <td style="padding:5px;">'.$fact["Factura"].'</td>
            <td style="padding:5px;">'.$fact["Dispensacion"].'</td>
            <td style="padding:5px;">'.$fact["Tipo_Factura"].'</td>
            <td style="padding:5px;">'.$fact["Factura_Original"].'</td>
            <td style="padding:5px;">';
            if($fact["Archivo"]!=""){
                echo '<a href="https://192.168.40.201/IMAGENES/AUDITORIAS/'.$fact["Id_Auditoria"].'/'.$fact["Archivo"].'" target="_blank">Documentos Adjuntos</a>';
            }
            echo '</td>
            <td style="padding:5px;">';
            if($fact["Firma"]!=""){
                    echo '<a href="https://192.168.40.201/IMAGENES/FIRMAS-DIS/'.$fact["Firma"].'" target="_blank">Firma Wacom</a>';
            }
            echo '</td>
            <td style="padding:5px;">';
            if($fact["Acta_Entrega"]!=""){
                echo '<a href="https://192.168.40.201/ARCHIVOS/DISPENSACION/ACTAS_ENTREGAS/'.$fact["Acta_Entrega"].'" target="_blank">Acta Entrega</a>';
            }
            echo '</td>
            <td style="padding:5px;">
            <form id="formulario1_'.$i.'" class="formularios">
            <input type="hidden" name="datos[Id_Factura]" value="'.$fact['Id_Factura'].'" >
            <input type="hidden" name="datos[Id_Dispensacion]" value="'.$fact['Id_Dispensacion'].'" >
            <input type="hidden" name="datos[Id_Factura_Actualizada]" value="'.$fact['Id_Factura_Actualizada'].'" >
            <input type="hidden" name="datos[Identificacion_Funcionario]" value="'.$cedula.'" >
            <table cellpadding="0" cellspacing="0" >';
            $j=-1;
            foreach($soportes as $sop){ $j++;            
                $det=explode(";",$sop);
                echo '<input type="hidden" name="soporte['.$j.'][Id_Soporte_Auditoria]" value="'.$det[0].'" >';
                echo '<tr>
                    <td>'.$det[1].'</td>
                    <td><input type="text" name="soporte['.$j.'][Paginas]" value="'.$det[3].'" required /></td>
                </tr>';
            }
               
        echo '</table><button type="submit" class="btn btn-info btn-block" >Actualizar</button> </form></td>
        
        
        </tr>';
        $x++;
    }
    echo '</table>'; 
    
}else{
    echo "<h1 class='text-center'>DEBE SELECCIONAR UN FILTRO PARA PODER PAGINAR</h1>";
}

?>
    </div>

<script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>
<script>
	$("body").on("submit",".formularios",function(e){
	    e.preventDefault();	    
	    var id =  $(this).attr('id');
	    var id_tabla = id.replace("formulario1_", "fila");
	    var formu = document.getElementById(id);
	    var data = new FormData(formu);
	    $.ajax({
	    url:"facturacion_enero2020_guardar.php", 
	    type:"POST", 
	    data: data,
	    contentType:false, 
	    processData:false, 
	    cache:false 
	    }).done(function(msg){
	    	alert(msg);	
	    	$("#"+id_tabla).removeClass("bg-warning").addClass("bg-success")
	    	
		  
	  }); 
		
	});
</script>
</body>
</html>