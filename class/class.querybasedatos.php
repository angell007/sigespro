<?php
	require_once(__DIR__ .'/../config/start.inc.php');	
	require_once(__DIR__ .'/./class.lista.php');
	require_once(__DIR__ .'/./class.complex.php');
	require_once(__DIR__ .'/./class.consulta.php');
	require_once(__DIR__ .'/./class.paginacion.php');

	class QueryBaseDatos{

		private $query = '';
		private $paginacion = false;
		private $result = array('codigo' => 'warning', 
								'titulo' => 'Alerta', 
								'mensaje' => 'Mensaje default!', 
								'query_result' => array());

		function __construct($consulta='', $pag = false){
			$this->query = $consulta;
			$this->paginacion = $pag;
		}

		function __destruct(){
			$this->query = '';
			$this->paginacion = '';
			unset($this->query);
			unset($this->paginacion);
		}

		public function setQuery($consulta){
			$this->query = $consulta;
		}

		public function Consultar($tipo = 'Simple', 
									$paginada = false, 
									$infoPaginacion = '', 
									$debugQuery = false,
									$datos = false){
									    
									    

			if(empty($this->query)){
				return 'No hay consulta para ejecutar!';
			}

			if($debugQuery == true){
				var_dump($this->query);
				exit;
			}

			if($paginada == true){
				$result = $this->ConsultaPaginada($infoPaginacion,$datos);
				return $result;
			}else{

				$result = $this->ConsultaNormal($tipo,$datos);
				return $result;
			}
		}

		public function ConsultaNormal($tipoConsulta,$datos=false){
			$r = $this->ExecuteQuery($tipoConsulta,$datos);

			if ($r != false) {
				
				$this->result['codigo'] = 'success';
				$this->result['titulo'] = 'Consulta Exitosa';
				$this->result['mensaje'] = 'Se han encontrado registros!';
				$this->result['numReg'] = "0";
				$this->result['query_result'] = $r;
			}else{

				$this->result['codigo'] = 'warning';
				$this->result['titulo'] = 'Alerta';
				$this->result['mensaje'] = 'No se han encontrado registros!';
				$this->result['numReg'] = "0";
				$this->result['query_result'] = $tipoConsulta == 'Multiple' ? array() : '';
			}

			return $this->result;
		}

		public function ConsultaPaginada($pagInfo,$datos=false){


			$itemsPorPagina = $pagInfo->ItemsPorPagina;
			$totalRecords = $this->ConteoRegistros($pagInfo->ConsultaCount,$datos);
			$paginaActual = $pagInfo->CurrentPage;
			/*if ($totalRecords == "0") {
				$paginas = 0;
			}else{
				$paginas = ceil($totalRecords/$itemsPorPagina);	
			}*/
			
			$limit = $this->ArmarLimiteConsultaPaginada($paginaActual, $itemsPorPagina);
			

			$this->query = $this->query.$limit;

			/*$resultado['query_result'] = $this->ExecuteQuery($pagInfo->TipoConsulta);
			$resultado['numReg'] = $totalRecords;*/

			$this->result['codigo'] = 'success';
			$this->result['titulo'] = 'Consulta Exitosa';
			$this->result['mensaje'] = 'Se han encontrado registros!';
			$this->result['numReg'] = $totalRecords;
			$this->result['query_result'] = $this->ExecuteQuery($pagInfo->TipoConsulta,$datos);
			
			return $this->result;
		}

		public function ExecuteQuery($tipoConsulta,$datos=false){
			$queryObj= new consulta();
			$queryObj->setQuery($this->query);
			$queryObj->setTipo($tipoConsulta);
			if($datos){
			  $result = $queryObj->getData2($datos["host"],$datos["user"],$datos["pass"],$datos["db"]);  
			}else{
			   $result = $queryObj->getData();
			}
			
			unset($queryObj);

			return $result;
		}

		private function ConteoRegistros($countQuery,$datos=false){
			$queryObj= new consulta();
			$queryObj->setQuery($countQuery);
			if($datos){
			  $conteo = $queryObj->getData2($datos["host"],$datos["user"],$datos["pass"],$datos["db"]);  
			}else{
			   $conteo = $queryObj->getData();
			}
			unset($queryObj);

			return $conteo['Total'] ? $conteo['Total'] : "0";
		}

		private function ArmarLimiteConsultaPaginada($actualPage, $itemsPorPagina){
			$limit = ""; 
			$paginaAct = "";

			if ($actualPage == '') { 
			    $paginaAct = 1; 
			    $limit = 0; 
			} else { 
				$paginaAct = $actualPage;
			    $limit = ($paginaAct-1) * $itemsPorPagina; 
			}

			return ' LIMIT '.$limit.','.$itemsPorPagina;
		}

		public function QueryUpdate(){
			if ($this->query == '') {				
				throw new Exception("No se ha cargado la consulta para la actualizacion");				
			}

			$queryObj= new consulta();
			$queryObj->SetQuery($this->query);
			$queryObj->createData();
			unset($queryObj);
		}
	}
