<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');

$punto = ( isset( $_REQUEST['punto'] ) ? $_REQUEST['punto'] : 3 );
$dis = ( isset( $_REQUEST['dis'] ) ? $_REQUEST['dis'] : '' );
$fini = ( isset( $_REQUEST['fini'] ) ? $_REQUEST['fini'] : date("Y-m-d") );
$ffin = ( isset( $_REQUEST['ffin'] ) ? $_REQUEST['ffin'] : date("Y-m-d") );

$oLista = new lista("Punto_Dispensacion");
$oLista->setOrder("Nombre","ASC");
$puntos = $oLista->getList();
unset($oLista);


$query = "SELECT A.Id_Auditoria,PD.Id_Producto_Dispensacion, D.Codigo AS DIS, CONCAT('AUD',A.Id_Auditoria) AS Auditoria, 
        D.Fecha_Actual,
        CONCAT(PA.Primer_Nombre, ' ', PA.Primer_Apellido) AS Paciente,
        D.Numero_Documento AS CC_Paciente,
        TS.Nombre AS Tipo_Servicio, 
        P.Nombre_Comercial AS Producto, 
        P.Codigo_Cum, 
        PD.Lote, 
        PD.Cantidad_Formulada, 
        PD.Cantidad_Entregada,
        PD.Numero_Autorizacion, 
        PD.Fecha_Autorizacion, 
        PD.Numero_Prescripcion,
        A.Archivo
        FROM Producto_Dispensacion PD 
        INNER JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion 
        INNER JOIN Auditoria A ON A.Id_Dispensacion = D.Id_Dispensacion 
        INNER JOIN Tipo_Servicio TS ON TS.Id_Tipo_Servicio = D.Id_Tipo_Servicio 
        INNER JOIN Producto P ON P.Id_Producto = PD.Id_Producto
        INNER JOIN Paciente PA ON PA.Id_Paciente = D.Numero_Documento
        WHERE 
        D.Estado_Facturacion = 'Facturada'
        AND D.Codigo IN ('".str_replace(",","','",str_replace("'","",str_replace('"','',$dis)))."')
        AND PD.Actualizado IS NULL
        AND D.Estado_Dispensacion != 'Anulada'
        ORDER BY D.Codigo ASC"; 
        //var_dump($query);
        // D.Id_Tipo_Servicio != 7 AND D.Id_Tipo_Servicio !=4 AND D.Id_Tipo_Servicio !=9 AND
        // DATE(D.Fecha_Actual) BETWEEN '".$fini." 00:00:00' AND '".$ffin." 23:59:59' AND
    	$oCon = new consulta();
    	$oCon->setQuery($query);
    	$oCon->setTipo("Multiple");
    	$dispensaciones = $oCon->getData();
    	unset($oCon);



$i=0;
$x=1;
?>
<html>
<head><meta charset="gb18030">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
</head>
<body>
    <div class="container-fluid">
        <div class="row text-center">
            <h3>Radicación Facturas</h3>
            <hr>
        </div>
        <div class="row">
            <form name="formBuscar" id="formBuscar">
                <!--
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="control-label" for="tipo">Punto</label>
                        <select name="punto" id="punto" class="form-control input-sm" required placeholder="Punto">
                            <option value="">Seleccione</option>
                            <?php foreach($puntos as $pto){ ?>
                                <option value="<?php echo $pto['Id_Punto_Dispensacion'] ?>" <?php if($pto['Id_Punto_Dispensacion']==$punto){ ?>selected<?php } ?> ><?php echo $pto["Nombre"] ?></option>
                            <?php } ?>
                        </select>
                    </div> 
                </div> -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="control-label" for="tipo">Dispensaciones</label>
                        <textarea name="dis" id="dis" rows="10" class="form-control input-sm" required placeholder="DEBE ESCRIBIR LOS CODIGOS DE LAS DISPENSACIONES SEPARADOS POR COMA"><?php echo $dis; ?></textarea>
                    </div>  
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="control-label" for="tipo">Fecha Inicial</label>
                        <input type="date" name="fini" id="fini" class="form-control input-sm" required placeholder="Fecha Inicio" value="<?php echo $fini; ?>" />
                    </div> 
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="control-label" for="tipo">Fecha Final</label>
                        <input type="date" name="ffin" id="ffin" class="form-control input-sm" required placeholder="Fecha Fin" value="<?php echo $ffin; ?>" />
                    </div> 
                </div>
                <div class="col-md-2">
                    <div class="form-group">        
                        <div class="col-sm-offset-2 col-sm">
                            <label class="control-label" for="tipo">&nbsp;</label>
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
    echo '<h4 style="margin: 0 auto;text-align:center;">Dispensaciones / Autorizaciones</h4><br>';
    echo '<table class="table table-hover" border="1" cellpadding="0" cellspacing="0" style="margin: 0 auto;margin-bottom:30px;font-size:12px;">
            <thead>
            <tr>
                <th>#</th>
                <th style="padding:5px;">Fecha Dis</th>
                <th style="padding:5px;">Dispensacion</th>
                <th style="padding:5px;">Paciente</th>
                <th style="padding:5px;">Tipo Servicio</th>
                <th style="padding:5px;">Producto</th>
                <th style="padding:5px;">Codigo Cum</thd>
                <th style="padding:5px;">Lote</th>
                <th style="padding:5px;">Formulada</th>
                <th style="padding:5px;">Entregada</th>
                <th style="padding:5px;">Autorizacion</th>
                <th style="padding:5px;">F. Autorizacion</th>
                <!--<th style="padding:5px;">Prescripcion</th>-->
                <th style="padding:5px;">Documento</th>
                <th></td>
            </tr>
            </thead>
            <tbody>
    ';
        if($dispensaciones){
            foreach($dispensaciones as $dis){ $i++;
            
                echo '<tr id="fila'.$dis["Id_Producto_Dispensacion"].'">
                    <td style="padding:5px;">'.$i.'</td>
                    <td style="padding:5px;">'.$dis["Fecha_Actual"].'</td>
                    <td style="padding:5px;">'.$dis["DIS"].'</td>
                    <td style="padding:5px;">'.$dis["Paciente"].'</td>
                    <td style="padding:5px;">'.$dis["Tipo_Servicio"].'</td>
                    <td style="padding:5px;">'.$dis["Producto"].'</td>
                    <td style="padding:5px;">'.$dis["Codigo_Cum"].'</td>
                    <td style="padding:5px;">'.$dis["Lote"].'</td>
                    <td style="padding:5px;">'.$dis["Cantidad_Formulada"].'</td>
                    <td style="padding:5px;">'.$dis["Cantidad_Entregada"].'</td>
                    <td style="padding:5px;"><input type="number" id="autorizacion'.$dis["Id_Producto_Dispensacion"].'"  value="'.$dis["Numero_Autorizacion"].'" class="form-control input-sm" /></td>
                    <td style="padding:5px;"><input type="date" id="fecha'.$dis["Id_Producto_Dispensacion"].'"  value="'.$dis["Fecha_Autorizacion"].'" class="form-control input-sm" /></td>
                    <!--<td style="padding:5px;">'.$dis["Numero_Prescripcion"].'</td> -->
                    <td style="padding:5px;"><a href="'.$URL."/IMAGENES/AUDITORIAS/".$dis["Id_Auditoria"]."/".$dis["Archivo"].'" target="_blank" >Archivo</a></td>
                    <td style="padding:5px;"><button class="btn btn-info btn-sm" onclick="actualiza('.$dis["Id_Producto_Dispensacion"].')">Guardar</button></td>
                    </tr>';
                $x++;
            }
        }elseif(count($dispensaciones)==0){
            echo "<tr><td colspan='14' class='text-center'>NO EXISTEN DISPENSACIONES CON ESE CRITERIO DE BUSQUEDA</td></tr>";
        }
    
    echo '</tbody></table>'; 
    


?>
    </div>

<script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>
<script>

function actualiza(id){
    
    var autorizacion = $("#autorizacion"+id).val();
    var fecha = $("#fecha"+id).val();
    var data = new FormData();
    data.append("id",id);
    data.append("autorizacion",autorizacion);
    data.append("fecha",fecha);
    
    $.ajax({
        url:"actualiza_autorizacion.php", 
        type:"POST", 
        data: data,
        contentType:false, 
        processData:false, 
        cache:false 
        }).done(function(msg){
            alert(msg)
        	if(msg=="Exito"){
        	    $("#fila"+id).addClass("bg-success");
        	}
    }); 
	  
    
    
}
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