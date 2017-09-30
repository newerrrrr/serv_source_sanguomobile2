<?php
//排行榜
class GuildMissionRank extends ModelBase{
	public $blacklist = ['type', 'create_time'];
	
	/**
	 * 清除所有表
	 * @return [type] [description]
	 */
	public function clearTable(){
		$this->sqlExec('TRUNCATE '.$this->getSource());
	}

	/**
	 * 增加积分
	 * @param [type] $type    [description]
	 * @param [type] $guildId [description]
	 * @param [type] $addNum  [description]
	 */
	public function addScore($round, $type, $guildId, $addNum){
		$re = self::find(["guild_id={$guildId} and type={$type} and round={$round}"])->toArray();
		$re = $this->adapter($re);
		if($re){
			$this->updateAll(['score'=>'score+'.$addNum],['id'=>$re[0]['id']]);
		}else{
			$Guild = new Guild;
			$guild = $Guild->getGuildInfo($guildId);
			$self = new self;
			$ret = $self->create(array(
				'round'		=> $round,
				'type'		=> $type,
				'rank' 		=> 0,
				'guild_id' 	=> $guildId,
				'name'		=> $guild['name'],
				'avatar'	=> $guild['icon_id'],
				'score'		=> $addNum,
			));
		}
	}

	public function getRankList($round, $type, $limit=0){
		$where = ["round={$round} and type={$type} and rank>0"];
		if($limit){
			$where['limit'] = $limit;
		}
		$re = self::find($where)->toArray();
		$re = $this->adapter($re);
		if(empty($re)){
			return [];
		}else{
			$re = Set::sort($re, '{n}.rank', 'asc');
			return $re;	
		}
	}

	public function getGuildRank($round, $type, $guildId){
		$re = self::find(["round={$round} and type={$type} and guild_id={$guildId}"])->toArray();
		$re = $this->adapter($re);
		if(empty($re)){
			return false;
		}else{
			return $re[0];	
		}
	}
}