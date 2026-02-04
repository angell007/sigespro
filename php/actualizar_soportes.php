<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

session_start();


//$cedula = $_SESSION


$cedula = isset($_SESSION["user"]) ? $_SESSION["user"] : 1095807087 ;


require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');


$dis = ( isset( $_REQUEST['dis'] ) ? $_REQUEST['dis'] : '' );

$query_fin='';
$error='No';
if($dis!=""){
	$query = "SELECT D.*, IFNULL(CONCAT('AUD',A.Id_Auditoria),'SIN Auditoria') AS Auditoria, A.Id_Auditoria, A.Archivo,
	IFNULL((SELECT GROUP_CONCAT(CONCAT_WS('',SA.Id_Soporte_Auditoria,';', SA.Tipo_Soporte,';',SA.Cumple,';',SA.Paginas)) FROM Soporte_Auditoria SA WHERE SA.Id_Auditoria=A.Id_Auditoria),'Sin Soportes') as Soportes,
	IFNULL((SELECT GROUP_CONCAT(CONCAT_WS('',TS.Id_Tipo_Soporte,';', TS.Tipo_Soporte,';','Si',';','')) FROM Tipo_Soporte TS WHERE TS.Id_Tipo_Servicio=D.Id_Tipo_Servicio),'Sin Soportes') as Soportes_Servicio

	FROM Dispensacion D 
	LEFT JOIN Auditoria A ON A.Id_Dispensacion = D.Id_Dispensacion
	WHERE D.Codigo='".$dis."'"; 
	$oCon = new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo("Multiple");
	$dispensaciones= $oCon->getData();
	unset($oCon);	
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
            <h3>Soportes Auditoria</h3>
            <hr>
        </div>
        <div class="row">
            <form name="formBuscar" id="formBuscar">
                
                <div class="col-md-10">
                    <div class="form-group">
                        <label class="control-label col-sm-2" for="tipo">Dispensacion</label>
                        <div class="col-sm-10">
                            <input type="text" name="dis" required id="dis" class="form-control input-sm" placeholder="UNA SOLA DIS" value="<?php echo $dis; ?>" />
                        </div>
                    </div> 
                </div>
                <div class="col-md-2">
                    <div class="form-group">        
                        <div class="col-sm-offset-2 col-sm">
                            <button type="submit" class="btn btn-danger btn-block">Ver Soportes</button>
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
    echo '<h4 style="margin: 0 auto;text-align:center;">Dispensación</h4><br>';
    echo '<form id="formulario" enctype="multipart/form-data" >
          <table class="table" border="1" cellpadding="0" cellspacing="0" style="margin: 0 auto;margin-bottom:30px;font-size:12px;">
            <tr>
                <td style="padding:5px;">#</td>
                <td style="padding:5px;">Fecha Dispensacion</td>
                <td style="padding:5px;">Dispensacion</td>
                <td style="padding:5px;">Auditoria</td>
                <td style="padding:5px;">Documento Actual</td>
                <td style="padding:5px;">Cargar Documento</td>
                <td style="padding:5px;">Paginación</td>
            </tr>
    ';
   if(count($dispensaciones)>0){   
	    foreach($dispensaciones as $dis){ $i++;
	        $soportes = explode(",",$dis["Soportes"]);
	        $soportes_servicio = explode(",",$dis["Soportes_Servicio"]);
	        echo '
	        <input type="hidden" name="datos[Id_Dispensacion]" value="'.$dis['Id_Dispensacion'].'" >
	        <input type="hidden" name="datos[Id_Auditoria]" value="'.$dis['Id_Auditoria'].'" >
	        <input type="hidden" name="datos[Identificacion_Funcionario]" value="'.$cedula.'" >
                <input type="hidden" name="datos[Id_Tipo_Servicio]" value="'.$dis['Id_Tipo_Servicio'].'" >
                <input type="hidden" name="datos[Id_Servicio]" value="'.$dis['Id_Servicio'].'" >
                <input type="hidden" name="datos[Id_Paciente]" value="'.$dis['Numero_Documento'].'" >
                <input type="hidden" name="datos[Id_Dispensacion_Mipres]" value="'.$dis['Id_Dispensacion_Mipres'].'" >
            
	        
	        <tr >
	            <td style="padding:5px;">'.$x.'</td>
	            <td style="padding:5px;">'.$dis["Fecha_Actual"].'</td>
	            <td style="padding:5px;">'.$dis["Codigo"].'</td>
	            <td style="padding:5px;">'.$dis["Auditoria"].'</td>
	            <td style="padding:5px;">';
	            if($dis["Archivo"]!=""){
	                echo '<a href="https://192.168.40.201/IMAGENES/AUDITORIAS/'.$dis["Id_Auditoria"].'/'.$dis["Archivo"].'" target="_blank">Documentos Adjuntos</a>';
	            }
	            echo '</td>
	            <td style="padding:5px;">
	                <select name="tipo" class="form-control form-control-sm" style="margin-bottom:10px;">
	                    <option>Sustituir</option>
	                    <option>Adicionar</option>
	                </select>
	                <input type="file" name="soportes_nuevos" id="soportes_nuevos" accept="application/pdf" required onchange="comprueba_extension(this.value)"  class="form-control form-control-sm" />
	            </td>
	            <td style="padding:5px;">
	            <table cellpadding="0" cellspacing="0" style="font-size:12px;width:100%;" >
	            	<tr>
	            	   <td class="text-center"><strong>Soporte</strong></td>
	            	   <td class="text-center"><strong>Páginas</strong></td>
	            	</tr>';
	            	
	            	if($dis["Soportes"]!='Sin Soportes'){ 
		            $j=-1;
		            foreach($soportes as $sop){ $j++;            
		                $det=explode(";",$sop);
		                echo '<input type="hidden" name="soporte['.$j.'][Id_Soporte_Auditoria]" value="'.$det[0].'" >';
		                echo '<tr>
		                    <td>'.$det[1].'</td>
		                    <td><input type="text" autocomplete="off" name="soporte['.$j.'][Paginas]" value="'.$det[3].'" required /></td>
		                </tr>';
		            }
		         }  
		         if($dis["Soportes"]=='Sin Soportes'&&$dis["Soportes_Srvicio"]!='Sin Soportes'){
		            $j=-1;
		            foreach($soportes_servicio as $sop){ $j++;            
		                $det=explode(";",$sop);
		                echo '<input type="hidden" name="soporte['.$j.'][Id_Soporte_Auditoria]" value="" >';
		                echo '<input type="hidden" name="soporte['.$j.'][Id_Tipo_Soporte]" value="'.$det[0].'" >';
		                echo '<input type="hidden" name="soporte['.$j.'][Tipo_Soporte]" value="'.$det[1].'" >';
		                
		                echo '<tr>
		                    <td>'.$det[1].'</td>
		                    <td><input type="text" autocomplete="off" name="soporte['.$j.'][Paginas]" value="'.$det[3].'" required /></td>
		                </tr>';
		            }
		         }  
		        echo '</table>	 	            
	            </td>
	            </tr>';
	        $x++;
	    }
    }
    if(count($dispensaciones)==0){
    	echo "<tr><td colspan='7' class='text-center'>NO EXISTEN DATOS CON LA DIS DIGITADA</td></tr>";
    }
    
    echo '</table>'; 
    echo '<button type="submit" class="btn btn-INFO btn-block">ACTUALIZAR SOPORTES</button>';
    
}else{
    echo "<h1 class='text-center'>DEBE ESRIBIR UNA DISPENSACION</h1>";
}

?>
    </div>

<script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>
<script>
	$("body").on("submit","#formulario",function(e){
	    e.preventDefault();	    
	    var formu = document.getElementById("formulario");
	    var data = new FormData(formu);
	    $.ajax({
	    url:"actualizar_soportes_guardar.php", 
	    type:"POST", 
	    data: data,
	    contentType:false, 
	    processData:false, 
	    cache:false 
	    }).done(function(msg){
	    	alert(msg);	
	    	location.reload();
	    	
	  }); 
		
	});
	
function comprueba_extension(archivo){
	extension = (archivo.substring(archivo.lastIndexOf("."))).toLowerCase();
	if(extension!='.pdf'){
		alert("SOLO SE PUEDEN CARGAR ARCHVOS PDF");
		$("#soportes_nuevos").val('');
	}
}
</script>
</body>
</html>