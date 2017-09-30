<?php

use Phalcon\Mvc\Collection;

class CollectionBase extends Collection
{
	static $Transactions = 0;
	static $_tmp = [];
	
	public static function begin(){
		self::$Transactions = 1;
	}
	
	public static function commit(){
		self::$Transactions = 0;
		foreach(self::$_tmp as $_t){
			switch($_t['type']){
				case 'save':
					$_t['data']->save();
				break;
				case 'delete':
					$_t['data']->delete();
				break;
			}
		}
		self::$_tmp = [];
	}
	
	public static function rollback(){
		self::$Transactions = 0;
		self::$_tmp = [];
	}
	
	public function save(){
		if(self::$Transactions){
			self::$_tmp[] = ['type'=>'save', 'data'=>clone $this];
		}else{
			parent::save();
		}
	}
	
	public function delete(){
		if(self::$Transactions){
			self::$_tmp[] = ['type'=>'delete', 'data'=>clone $this];
		}else{
			parent::delete();
		}
	}
	
	public function findArray($param){
		$ret = $this->find($param);
		foreach($ret as &$_r){
			$_r = $_r->toArray();
			$_r['id'] = $_r['_id']->{'$id'};
			unset($_r['_id']);
		}
		unset($_r);
		return $ret;
	}
}