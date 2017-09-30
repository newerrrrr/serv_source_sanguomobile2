<?php
/**
 * 建筑类型
 *
 */
class BuildPosition extends ModelBase{
	/**
	 * 检查是否能在该位置建造建筑
	 * 
	 * @param  [type] $buildId  [description]
	 * @param  [type] $position [description]
	 * 
	 * @return boolean
	 */
	public function checkBuildPosition($buildId, $position){
		$ret = $this->dicGetOne($position);
		$canBuildIdArr = explode(';', $ret['build_id']);
		if(in_array($buildId, $canBuildIdArr)){
			return true;
		}else{
			return false;
		}
	}
}
