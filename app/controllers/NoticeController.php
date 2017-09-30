<?php
class NoticeController extends ControllerBase{
	public function initialize(){}
	public function noticeAction($channel=''){
		$Notice = new Notice;
		$notice = $Notice->getAllByChannel($channel);
		$this->view->notice = $notice;
	}
}

