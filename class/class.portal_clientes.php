<?
	class PortalCliente{

		private $url = '';
		private $queryObj;

		function __construct($queryObj){
			$this->queryObj = $queryObj;
		}

		function __destruct(){
			$this->queryObj = null;
			unset($this->queryObj);
		}

		public function RegistrarDispensacion($idDis){
			$dispensacion = $this->GetDataDispensacion($idDis);
			$productos = $this->GetProductosDispensacion($idDis);

			$this->url = 'https://clientesproh.com/php/webservices/guardar_dispensacion.php';

			$params = array(
	            'dispensacion' => json_encode($dispensacion),
	            'productos' => json_encode($productos),
	        );
			
			$curl = curl_init($this->url);
			curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER , array('Content-Type: multipart/form-data;'));
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($curl);
			curl_close($curl);

			$this->url = '';

			return $result;

		}

		public function ActualizarDispensacion($idDis){			
			$dispensacion = $this->GetDataDispensacion($idDis);
			$productos = $this->GetProductosDispensacion($idDis);

			$this->url = 'https://clientesproh.com/php/webservices/actualizar_dispensacion.php';

			$params = array(
	            'dispensacion' => json_encode($dispensacion),
	            'productos' => json_encode($productos),
	        );
			
			$curl = curl_init($this->url);
			curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER , array('Content-Type: multipart/form-data;'));
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($curl);
			curl_close($curl);

			$this->url = '';

			return $result;
		}

		private function GetDataDispensacion($idDis){
			$query = "
	          SELECT 
	          		D.Id_Dispensacion,
	               D.Codigo,
	               D.Fecha_Actual,
	               D.Id_Punto_Dispensacion,
	               D.Id_Servicio,
	               D.Id_Tipo_Servicio,
	               D.Numero_Documento,
	               CONCAT_WS(' ',
	                         PC.Primer_Nombre,
	                         PC.Segundo_Nombre,
	                         PC.Primer_Apellido,
	                         PC.Segundo_Apellido) AS Paciente,
	               IFNULL((SELECT Numero_Telefono FROM Paciente_Telefono WHERE Id_Paciente = D.Numero_Documento LIMIT 1), 'NULL') AS Numero_Telefono,
	               PC.Direccion,
	               (SELECT Nombre FROM Regimen WHERE Id_Regimen = PC.Id_Regimen) AS Regimen,
	               IFNULL(D.CIE, 'NULL') AS CIE,
	               PC.Nit AS Cliente,
				   PC.EPS AS Nombre_Cliente,
	               D.Estado_Facturacion,
	               D.Estado_Dispensacion,
	               D.Estado_Auditoria,
	               IFNULL(D.Id_Factura, 0) AS Id_Factura,
	               D.Pendientes,
	               (SELECT CONCAT_WS(' ', Nombres, Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = D.Identificacion_Funcionario) AS Funcionario,
	               (SELECT Nombre FROM Departamento WHERE Id_Departamento = PC.Id_Departamento) AS Departamento, D.Cantidad_Entregas, D.Entrega_Actual
	                FROM Dispensacion D
	                STRAIGHT_JOIN Paciente PC ON D.Numero_Documento=PC.Id_Paciente
	                STRAIGHT_JOIN Punto_Dispensacion P ON D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
	                STRAIGHT_JOIN Departamento L ON P.Departamento = L.Id_Departamento
	               WHERE
						D.Id_Dispensacion = $idDis";
						

		    $this->queryObj->SetQuery($query);
			$dispensacion = $this->queryObj->ExecuteQuery('simple');
			
		    return $dispensacion;
		}

		private function GetProductosDispensacion($idDis){
			$query = "
	          SELECT 
	               *
                FROM Producto_Dispensacion
               WHERE
                    Id_Dispensacion = $idDis";

		    $this->queryObj->SetQuery($query);
		    $productos = $this->queryObj->ExecuteQuery('multiple');
		    return $productos;
		}

	}
?>