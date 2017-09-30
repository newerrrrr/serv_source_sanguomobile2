<?php
//敏感词
class SensitiveWord extends ModelBase{
	/**
	 * 检查内容中是否包含敏感字
	 * 
	 * @param  [type] $content 检查内容
	 * @param  [type] $type 检测类型 1=检测发言 2=检测起名
	 * @return boolean
	 */
	public function checkSensitiveContent($content, $type=1){
		if($type==1){
			$sql = "SELECT id FROM sensitive_word WHERE type=1 and '".addslashes($content)."' LIKE concat( '%', word, '%' );";
		}else{
			$sql = "SELECT id FROM sensitive_word WHERE '".addslashes($content)."' LIKE concat( '%', word, '%' );";
		}
		
        $re = $this->sqlGet($sql);
        if(!empty($re)){
        	return true;
        }else{
        	return false;
        }
	}

    /**
     * 字典表获取所有主线任务
     * 
     * @return array
     */
    public function dicGetAllByType($type=1){
        $cacheKey = 'SensitiveWord-type-all';
        if($type==1) $cacheKey = 'SensitiveWord-type-1';
        $ret = $this->cache($cacheKey, function() use ($type) {
            if($type==1) {
                $re = self::find("type=1")->toArray();
            } else {
                $re = self::find()->toArray();
            }
            $re = Set::extract($re, '/word');
            return $re;
        });
        return $ret;
    }
    /**
     * 敏感字轉為*
     * @param  string  $word 
     * @param  integer $type 
     * @return string        
     */
    public function filterWord($word, $type=1){
        $all = $this->dicGetAllByType($type);
        $filter = array_combine($all, array_fill(0, count($all), '*'));
        $filterWord = strtr($word, $filter);
        return $filterWord;
    }
}