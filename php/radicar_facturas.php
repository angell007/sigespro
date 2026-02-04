<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');


$lista = ( isset( $_REQUEST['lista'] ) ? $_REQUEST['lista'] : '' );
$radicado = ( isset( $_REQUEST['radicado'] ) ? $_REQUEST['radicado'] : '' );

$query_fin='';
$error='No';
if($lista!=""){
    
	
	$oItem = new complex("Radicado","Codigo",$radicado);
	$rad = $oItem->getData();
	unset($oItem);
	
	$radicado_existencia = false;
	if(isset($rad["Id_Radicado"])&&$rad["Id_Radicado"]!=''){
	    $radicado_existencia=true;
	    
	    $query = "SELECT F.Codigo as Factura, F.Fecha_Documento, R.Codigo as Radicado
        FROM Radicado_Factura RF 
        INNER JOIN Radicado R ON R.Id_Radicado=RF.Id_Radicado
        INNER JOIN Factura F ON F.Id_Factura = RF.Id_Factura
        WHERE R.Id_Tipo_Servicio != 7
        AND F.Codigo IN ('".str_replace(",","','",str_replace("'","",str_replace('"','',$lista)))."')"; 
    	$oCon = new consulta();
    	$oCon->setQuery($query);
    	$oCon->setTipo("Multiple");
    	$facturas = $oCon->getData();
    	unset($oCon);
    	
    	if($facturas){
    	    $query = "DELETE RF
            FROM Radicado_Factura RF
            INNER JOIN Radicado R ON R.Id_Radicado = RF.Id_Radicado
            INNER JOIN Factura F ON F.Id_Factura = RF.Id_Factura
            WHERE R.Id_Tipo_Servicio != 7
            AND F.Codigo IN ('".str_replace(",","','",str_replace("'","",str_replace('"','',$lista)))."')"; 
            $oCon = new consulta();
            $oCon->setQuery($query);
            $deleted = $oCon->deleteData();
            unset($oCon);
            
            $query = "UPDATE Factura F 
            SET F.Estado_Radicacion = 'Pendiente'
            WHERE F.Codigo IN  ('".str_replace(",","','",str_replace("'","",str_replace('"','',$lista)))."')"; 
            $oCon = new consulta();
            $oCon->setQuery($query);
            $created = $oCon->createData();
            unset($oCon);
            
    	}
    	
    	$query = "SELECT F.Codigo as Factura, F.Fecha_Documento, F.Id_Factura
        FROM Factura F 
        WHERE F.Codigo IN ('".str_replace(",","','",str_replace("'","",str_replace('"','',$lista)))."')"; 
    	$oCon = new consulta();
    	$oCon->setQuery($query);
    	$oCon->setTipo("Multiple");
    	$facturas_solas = $oCon->getData();
    	unset($oCon);
    	
    	foreach($facturas_solas as $fac){
            $oItem = new complex("Radicado_Factura", "Id_Radicado_Factura");
            $oItem->Id_Factura = $fac["Id_Factura"];
            $oItem->Id_Radicado = $rad["Id_Radicado"];
            $oItem->Estado_Factura_Radicacion="Radicada";
            $oItem->save();
            unset($oItem);
            
            $oItem = new complex("Factura", "Id_Factura", $fac["Id_Factura"]);
            $oItem->Estado_Radicacion="Radicada";
            $oItem->save();
            unset($oItem);
        }
	}
}




$i=-1;
$x=1;
?>
<html>
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
</head>
<body>
    <div class="container">
        <div class="row text-center">
            <h3>Radicación Facturas</h3>
            <hr>
        </div>
        <div class="row">
            <form name="formBuscar" id="formBuscar">
                <div class="col-md-8">
                    <div class="form-group">
                        <label class="control-label col-sm-2" for="tipo">Facturas</label>
                        <div class="col-sm-10">
                            <textarea name="lista" id="lista" rows="5" class="form-control input-sm" required placeholder="DEBE ESCRIBIR LOS CODIGOS DE LAS FACTURAS SEPARADOS POR COMA"><?php echo $lista; ?></textarea>
                        </div>
                    </div> 
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="control-label col-sm-2" for="tipo">Radicado</label>
                        <div class="col-sm-10">
                            <input type="text" name="radicado" id="radicado" class="form-control input-sm" required placeholder="UN SOLO RADICADO" value="<?php echo $radicado; ?>" />
                        </div>
                    </div> 
                </div>
                <div class="col-md-2">
                    <div class="form-group">        
                        <div class="col-sm-offset-2 col-sm">
                            <button type="submit" class="btn btn-danger btn-block">Radicar</button>
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
    echo '<h4 style="margin: 0 auto;text-align:center;">Facturas Actualizadas</h4><br>';
    echo '<table border="1" cellpadding="0" cellspacing="0" style="margin: 0 auto;margin-bottom:30px;">
            <tr>
                <td>#</td>
                <td style="padding:5px;">Fecha Factura</td>
                <td style="padding:5px;">Factura</td>
                <td style="padding:5px;">Radicado Anterior</td>
                <td style="padding:5px;">Estado</td>
            </tr>
    ';
    if($radicado_existencia){
        if($facturas_solas){
            foreach($facturas_solas as $fact){ $i++;
                $soportes = explode(",",$fact["Soportes"]);
                echo '<tr >
                    <td style="padding:5px;">'.$x.'</td>
                    <td style="padding:5px;">'.$fact["Fecha_Documento"].'</td>
                    <td style="padding:5px;">'.$fact["Factura"].'</td>
                    <td style="padding:5px;">'.$rad["Codigo"].'</td>
                    <td style="padding:5px;">RADICADA</td>
                    </tr>';
                $x++;
            }
        }elseif(count($facturas_solas)==0){
            echo "<tr><td colspan='5'>Las Facturas ingresadas no se consiguieron en el sistema</td></tr>";
        }
    }else{
        echo "<tr><td colspan='5'>EL RADICADO DIGITADO NO EXISTE EN NUESTRO SISTEMA</td></tr>";
    }
    echo '</table>'; 
    
}else{
    echo "<h1 class='text-center'>DEBE ESCRIBIR AL MENOS UNA FACTURA Y UN RADICADO, PARA PODER RADICAR </h1>";
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