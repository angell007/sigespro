<?php
include_once("class.lista.php");

class tree
{
	var $table = "";
	var $parent_key = "";
	var $node_key = "";
	var $order_key = "";
	var $restrict = array();
	
	function tree($table,$node_key,$parent_key,$order_key="")
	{
		$this->table = $table;
		$this->parent_key = $parent_key;
		$this->node_key = $node_key;
		$this->order_key = $order_key;
	}
	
	function getNodes($antecesor)
	{
		$oLista = new lista($this->table);
		$oLista->setRestrict($this->parent_key,"=",$antecesor);
		if (!empty($this->restrict)) {
			foreach ($this->restrict as $indice=>$valor) {
				$row = $valor["row"];
				$operator = $valor["operator"];
				$value = $valor["value"];
				$oLista->setRestrict($row, $operator,$value);
			}
		}
		
		if ($this->order_key!=""){
			$oLista->setOrder($this->order_key,"ASC");
		}
		$nodes = $oLista->getList();
		unset($oLista);
		$list_nodes = Array();
		if (!empty($nodes)){
			foreach ($nodes as $node){
				$id = $node[$this->node_key];
				$sub = $this->getNodes($id);
				$node["branches"] = $sub;
				$list_nodes[] = $node;
			}
		}
		return $list_nodes;
	}
	
	function getParents($node)
	{
		$lista = Array();
		$p = $node;
		while ($p!=0){
			$oLista = new lista($this->table);
			$oLista->setRestrict($this->node_key,"=",$p);
			if (!empty($this->restrict)) {
				foreach ($this->restrict as $indice=>$valor) {
					$row = $valor["row"];
					$operator = $valor["operator"];
					$value = $valor["value"];
					$oLista->setRestrict($row, $operator,$value);
				}
			}
			
			if ($this->order_key!=""){
				$oLista->setOrder($this->order_key,"ASC");
			}
			$nodes = $oLista->getList();
			unset($oLista);
			$node = $nodes[0];
			$lista[] = $node;
			$p = $node[$this->parent_key];
		}
		$lista_parents = array_reverse($lista);
		return $lista_parents;
	}
	
	function setRestrict($row,$operator,$value) 
	{
		$restrict = Array();
		$restrict["row"] = $row;
		$restrict["operator"] = $operator;
		$restrict["value"] = $value;
		$this->restrict[] = $restrict;
	}
	
	function generate()
	{
		$this->lista = $this->getNodes(0);
	}
	
	function get()
	{
		return $this->lista;
	}
	
	function setFile($file, $varname)
	{
		$params=var_export($this->lista,true);
		$variable = '$'.$varname.' = ' .$params;
		$archive = fopen($file,"w");
		fputs($archive,"<?php \n");
		fputs($archive,"\n");
		fputs($archive,$variable."\n");
		fputs($archive,"\n");
		fputs($archive,"?>");
	}
}

?>