<?php
//建筑
class ResourceRefresh extends ModelBase{

	public function getResourceList(){
		$result = [];
		$ret = $this->dicGetAll();
		foreach ($ret as $key => $value) {
			$result[$value['distance_max']*$value['distance_max']][$value['map_element_id']] = $value['weight'];
		}
		ksort($result);
		return $result;
	}
}