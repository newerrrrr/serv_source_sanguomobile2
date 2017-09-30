<?php
/**
 * 建筑
 *
 */
class Build extends ModelBase{

	public static $outputTypeArr = [1=>"gold",2=>"food",3=>"wood",4=>"stone",5=>"iron"];

    /**
     * 获取所有建筑
     * 
     * @return <type>
     */
	public function dicGetAll(){
		$ret = $this->cache(__CLASS__, function() {
			$ret = $this->findList('id');
			foreach($ret as &$_r){
				if($_r['pre_build_id']){
					$_r['pre_build_id'] = explode(';', $_r['pre_build_id']);
				}else{
					$_r['pre_build_id'] = array();
				}
				if($_r['cost']){
					$_r['cost'] = explode(';', $_r['cost']);
					$_cost = array();
					foreach($_r['cost'] as $_c){
						list($_k, $_c) = explode(',', $_c);
						$_cost[$_k] = $_c;
					}
					$_r['cost'] = $_cost;
				}else{
					$_r['cost'] = array();
				}
				if($_r['output']){
					$_r['output'] = explode(';', $_r['output']);
					$_output = array();
					foreach ($_r['output'] as $key => $value) {
						$vArr = explode(',', $value);
						$_output[$vArr[0]] = $vArr[1];
					}
					$_r['output'] = $_output;
				}else{
					$_r['output'] = array();
				}
			}
			unset($_r);
			return $ret;
		});
		return $ret;
	}
	
    /**
     * 根据建筑id得到学习栏位数量
     * 
     * @param <type> 书院id 
     * 
     * @return <type>
     */
	public function getStudyNum($buildId){
		$build = $this->dicGetOne($buildId);
		return $build['output'][16];
	}
	/**
     * 根据建筑id得到学习栏位数量
     * 
     * @param <type> 书院id 
     * 
     * @return <type>
     */
	public function getMaxGeneral($buildId){
		$build = $this->dicGetOne($buildId);
		return $build['output'][33];
	}
	/**
	 * 获取指定orgid和level的建筑
	 * 
	 * @param  [type] $orgId 建筑原始id
	 * @param  [type] $level 建筑等级
	 * @return array
	 */
	public function getOneByOrgIdAndLevel($orgId, $level){
		$ret = $this->dicGetAll();
		foreach ($ret as $value) {
			if($value['origin_build_id']==$orgId && $value['build_level']==$level){
				return $value;
			}
		}
		return false;
	}
}
