<?

class orm_as_tree extends orm
{
	var $name = 'text';
	var $lft = 'integer';
	var $rgt = 'integer';
	var $depth = 'virtual';

	function get_children($other_fields='',$other_conditions='1',$depth=1000)
	{
		$table_name = get_class($this);
		$save_rowid = $this->ROWID;

		$children = $this->find(ALL, Array(
				'select'=>'node.ROWID as ROWID, node.name as name,node.lft as lft,node.rgt as rgt,(count(parent.ROWID) - 1) as depth' . (($other_fields!='')?', ' . $other_fields:''),
				'from'=>$table_name . ' as node, ' . $table_name . ' as parent',
				'conditions'=>'(node.lft between ' . $this->lft . ' and ' . $this->rgt . ') and node.lft between parent.lft and parent.rgt and (' . $other_conditions . ')',
				'group'=>'node.ROWID',
				'having'=>'depth<'.$depth,
				'order'=>'node.lft'
			)
		);

		$current_depth = 0;
		if (count($children)) {
			$current_depth = $children[0]->depth+1;
			for ($i=0;$i<count($children);$i++) $children[$i]->depth -= $current_depth;
			//unset($children[0]);
		}

		$this->find($save_rowid);

		return $children;
	}

	function print_children()
	{
		$table_name = get_class($this);
		$save_rowid = $this->ROWID;
		$children = $this->find(ALL, Array(
				'select'=>'node.ROWID as ROWID, node.name as name,node.lft as lft,node.rgt as rgt,(count(parent.ROWID) - 1) as depth',
				'from'=>$table_name . ' as node, ' . $table_name . ' as parent',
				'conditions'=>'(node.lft between ' . $this->lft . ' and ' . $this->rgt . ') and node.lft between parent.lft and parent.rgt',
				'group'=>'node.ROWID',
				'order'=>'node.lft'
			)
		);

		$current_depth = 0;
		if (count($children)) {
			$current_depth = $children[0]->depth;
			for ($i=0;$i<count($children);$i++) $children[$i]->depth -= $current_depth;
			unset($children[0]);
		}

		foreach ($children as $child) {
			for ($i=0;$i<$child->depth-1;$i++) echo ' ';
			echo $child->name . ' # ' . $child->lft . ' x ' . $child->rgt . ' # ' . $child->ROWID . "\r\n";
		}

		$this->find($save_rowid);

		return;
	}

	function move($reference_node_id,$where='AFTER')
	{
		// TODO - checar se é preciso não transferir c ref node for igual
		$this->execute_query('BEGIN');

		$table_name = get_class($this);

		$node_size = ($this->rgt)-($this->lft)+1;
		$node_lft = $this->lft;
		$node_rgt = $this->rgt;

		$reference_node = orm::search_one($table_name,$reference_node_id);
		if (!$reference_node) return false;

		if ($this->lft == '') return false;
		if ($this->rgt == '') return false;

		if ( ($reference_node->lft > $this->lft) && ($reference_node->rgt < $this->rgt) ) return false;

		// Passo 1
		// Mover o Nó para fora
		$node_offset_anchor = $node_lft;
		$node_offset = $node_lft+65535;
		$this->execute_query('update ' . $table_name . ' set rgt = rgt-' . $node_offset . ',lft=lft-' . $node_offset . ' where lft between ' . $node_lft . ' and ' . $node_rgt );

		// Passo 2
		// Remover o espaço inutilizado
		$this->execute_query('update ' . $table_name . ' set rgt = rgt-' . $node_size . ' where rgt > ' . $node_rgt);
		$this->execute_query('update ' . $table_name . ' set lft = lft-' . $node_size . ' where lft > ' . $node_rgt);

		// Passo 3
		// Calcular nova posição do nó
		$node_lft -= $node_offset;
		$node_rgt -= $node_offset;

		// Passo 4
		// Encontrar nó de referência
		$reference_node = orm::search_one($table_name,$reference_node->ROWID );
		if (!$reference_node) return false;

		// Passo 5
		// Alocar espaço e mover nó
		if ($where == 'AFTER') {
			$this->execute_query('update ' . $table_name . ' set rgt = rgt+' . $node_size . ' where rgt > ' . $reference_node->rgt);
			$this->execute_query('update ' . $table_name . ' set lft = lft+' . $node_size . ' where lft > ' . $reference_node->rgt);
			$position_delta = ($node_offset-$node_offset_anchor)+$reference_node->rgt+1;
			$this->execute_query('update ' . $table_name . ' set rgt = rgt+' . $position_delta . ',lft=lft+' . $position_delta . ' where lft between ' . $node_lft . ' and ' . $node_rgt );
		}
		else if ($where == 'INSIDE') {
			$this->execute_query('update ' . $table_name . ' set rgt = rgt+' . $node_size . ' where rgt >= ' . $reference_node->rgt);
			$this->execute_query('update ' . $table_name . ' set lft = lft+' . $node_size . ' where lft > ' . $reference_node->rgt);
			$position_delta = ($node_offset-$node_offset_anchor)+$reference_node->rgt;
			$this->execute_query('update ' . $table_name . ' set rgt = rgt+' . $position_delta . ',lft=lft+' . $position_delta . ' where lft between ' . $node_lft . ' and ' . $node_rgt );
		}
		else if ($where == 'BEFORE') {
			$this->execute_query('update ' . $table_name . ' set rgt = rgt+' . $node_size . ' where rgt >= ' . $reference_node->lft);
			$this->execute_query('update ' . $table_name . ' set lft = lft+' . $node_size . ' where lft >= ' . $reference_node->lft);
			$position_delta = ($node_offset-$node_offset_anchor)+$reference_node->lft;
			$this->execute_query('update ' . $table_name . ' set rgt = rgt+' . $position_delta . ',lft=lft+' . $position_delta . ' where lft between ' . $node_lft . ' and ' . $node_rgt );
		}

		$this->execute_query('COMMIT');
	}


	function add_node($name='Nova Página',$where='AFTER')
	{
		$table_name = get_class($this);

		$current_rgt = $this->rgt;
		$current_lft = $this->current_lft;
		if (($this->lft == '') && ($this->rgt == ''))
		{
			$root = orm::search_one($table_name,ONE,Array('order'=>'rgt DESC'));
			if ($root) {
				$current_rgt = $root->rgt;
			}
			else
			{
				$current_rgt = 0;
			}
		}

		$this->execute_query('BEGIN');

		$this->execute_query('update ' . $table_name . ' set rgt = rgt+2 where rgt > ' . $current_rgt);
		$this->execute_query('update ' . $table_name . ' set lft = lft+2 where lft > ' . $current_rgt);
		
		$new_leaf = new $table_name;
		$new_leaf->name = $name;
		$new_leaf->lft = $current_rgt+1;
		$new_leaf->rgt = $current_rgt+2;
		$new_leaf->save();

		if ($where == 'AFTER') {
			$new_leaf->move( $this->ROWID, 'AFTER' );
		} else if ($where == 'BEFORE') {
			$new_leaf->move( $this->ROWID, 'BEFORE' );
		} else {
			$new_leaf->move( $this->ROWID, 'INSIDE' );
		}

		$this->execute_query('COMMIT');
		
		$this->find($this->ROWID);

		return $new_leaf;
	}

	function remove()
	{
		$table_name = get_class($this);

		if (($this->lft == '') && ($this->rgt == ''))
		{
			return false;
		}

		$this->execute_query('BEGIN TRANSACTION');

		$this->execute_query('delete from ' . $table_name . ' where lft between ' . $this->lft . ' and ' . $this->rgt);

		$this->execute_query('update ' . $table_name . ' set rgt = rgt-' . ($this->rgt-($this->lft+1)) . ' where rgt > ' . $this->rgt);
		$this->execute_query('update ' . $table_name . ' set lft = lft-' . ($this->rgt-($this->lft+1)) . ' where lft > ' . $this->rgt);

		$this->execute_query('COMMIT');

		return true;
	}

	function get_subordinates($other_fields='',$other_conditions='1',$depth=1)
	{
		$table_name = get_class($this);
		$save_rowid = $this->ROWID;

		$children = $this->find(ALL, Array(
				'select'=>'node.ROWID as ROWID, node.name as name,node.lft as lft,node.rgt as rgt,(count(parent.ROWID) - 1) as depth' . (($other_fields!='')?', ' . $other_fields:''),
				'from'=>$table_name . ' as node, ' . $table_name . ' as parent',
				'conditions'=>'(node.lft between ' . $this->lft . ' and ' . $this->rgt . ') and node.lft between parent.lft and parent.rgt and (' . $other_conditions . ')',
				'group'=>'node.ROWID',
				'having'=>'depth=' . $depth,
				'order'=>'node.lft'
			)
		);

		$current_depth = 0;
		if (count($children)) {
			$current_depth = $children[0]->depth+1;
			for ($i=0;$i<count($children);$i++) $children[$i]->depth -= $current_depth;
			//unset($children[0]);
		}

		$this->find($save_rowid);

		return $children;
	}

	function get_depth()
	{
		$table_name = get_class($this);
		$children = $this->find(ALL, Array(
				'select'=>'node.ROWID as ROWID,node.name as name,node.lft as lft,node.rgt as rgt,(count(parent.ROWID) - 1) as depth',
				'from'=>$table_name . ' as node, ' . $table_name . ' as parent',
				'conditions'=>'(node.lft between ' . $this->lft . ' and ' . $this->rgt . ') and node.lft between parent.lft and parent.rgt',
				'group'=>'node.ROWID',
				'order'=>'node.lft',
				'limit'=>'1'
			)
		);

		$current_depth = 0;
		if (count($children)) {
			$current_depth = $children[0]->depth;
		}

		return $current_depth;
	}

	function get_leafs()
	{
		echo 'getting leafs' . "\r\n";

		$table_name = get_class($this);
		$children = $this->find(ALL, Array(
				'select'=>'node.name as name,node.lft as lft,node.rgt as rgt',
				'from'=>$table_name . ' as node, ' . $table_name . ' as parent',
				'conditions'=>'(node.lft between parent.lft and parent.rgt) and (node.rgt = node.lft+1) and node.ROWID != parent.ROWID and parent.ROWID = '.$this->ROWID,
				'order'=>'node.lft'
			)
		);

		foreach ($children as $child) {
			echo $child->name . "\r\n";
		}

		//print_r($children);

		/*
		$rows = $this->execute_and_get_all('SELECT node.name as name FROM tree_view AS node, tree_view AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt AND node.ROWID != parent.ROWID AND parent.ROWID = "' . $this->ROWID . '" ORDER BY node.lft');
		foreach ($rows as $row) {
			echo $row['name'] . "\r\n";
		}
		*/

		return;
	}

	function get_path($other_fields='',$other_conditions='1')
	{
		$table_name = get_class($this);
		$children = $this->find(ALL, Array(
				'select'=>'parent.name as name,parent.lft as lft,parent.rgt as rgt' . (($other_fields!='')?', ' . $other_fields:''),
				'from'=>$table_name . ' as node, ' . $table_name . ' as parent',
				'conditions'=>'node.lft between parent.lft and parent.rgt and node.ROWID = '.$this->ROWID . ' and (' . $other_conditions . ')',
				'order'=>'parent.lft'
			)
		);

		return $children;
	}
	
	function get_path_by_conditions($conditions='1',$other_fields='')
	{
		$table_name = get_class($this);
		$children = $this->find(ALL, Array(
				'select'=>'parent.name as name,parent.lft as lft,parent.rgt as rgt' . (($other_fields!='')?', ' . $other_fields:''),
				'from'=>$table_name . ' as node, ' . $table_name . ' as parent',
				'conditions'=>'node.lft between parent.lft and parent.rgt and (' . $other_conditions . ')',
				'order'=>'parent.lft'
			)
		);

		return $children;
	}
}

?>