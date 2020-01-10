<?php
namespace Home\Controller;
class GameController extends HomeController
{
	protected function _initialize(){
		parent::_initialize();
		$allow_action=array("index");
		if(!in_array($this->request->action(),$allow_action)){
			$this->error("非法操作！");
		}
	}
	
	public function index()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		$name = Db::name('VersionGame')->where(array(
			'status' => 1,
			'name'   => array('neq', 'shop')
			)->value('name');

		if ($name) {
			$this->redirect(U(ucwords($name) . '/index'));
		}

		return $this->fetch();
	}
}

?>