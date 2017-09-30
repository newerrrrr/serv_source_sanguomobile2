<?php
/**
 * 主动技能释放规则
 *
 */
class ActiveSkillTarget extends ModelBase{
	const SCENE_CROSS = 1;//跨服战
	const SCENE_CITYBATTLEDOOR = 2;//城门战
	const SCENE_CITYBATTLEMELEE = 3;//内城战
	
	const SIDE_ATTACK = 1;
	const SIDE_DEFEND = 2;
	public function dicGetAll(){
		$ret = $this->cache(__CLASS__, function() {
			$ret = $this->findList('id');
			foreach($ret as &$_r){
				$_r = $this->parseColumn($_r);
			}
			unset($_r);
			return $ret;
		});
		return $ret;
	}
	
	public function parseColumn($_r){
		$_r['target'] = json_decode($_r['target'], true);
		return $_r;
	}
	
    /**
     * 获取技能池
	 * filter:0-所有，1：仅被动
     */
	public function getTarget($sceneId, $battleSkillId, $side, $sectionId){
		$cacheName1 = __CLASS__ .'1';
		$cacheName2 = $sceneId.'_'.$battleSkillId.'_'.$side.'_'.$sectionId;
		$ret = Cache::db(CACHEDB_STATIC)->hGet($cacheName1, $cacheName2);
		if(!$ret){
			$ret = $this->findFirst(['scene_id='.$sceneId.' and battle_skill_id='.$battleSkillId.' and side='.$side.' and section_id='.$sectionId]);
			if($ret){
				$ret = $ret->toArray();
				$ret = $this->parseColumn($ret);
				Cache::db(CACHEDB_STATIC)->hSet($cacheName1, $cacheName2, $ret);
			}
		}
		return $ret;
	}
}
