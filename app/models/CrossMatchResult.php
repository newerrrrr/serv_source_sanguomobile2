<?php
//日志
class CrossMatchResult extends CrossModelBase{
    /**
     * @param $roundId
     * @param $info
     */
	public function addNew($roundId, $info){
		$self              = new self;
        $self->round_id    = $roundId;
        $self->info        = json_encode($info);
        $self->create_time = date('Y-m-d H:i:s');
        $self->status      = 0;
        $self->save();
	}
}