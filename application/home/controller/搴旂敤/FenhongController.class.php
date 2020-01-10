<?php
/* Ӧ�� - �ֺ����� */
namespace Home\Controller;

class FenhongController extends HomeController
{
	protected function _initialize(){
		parent::_initialize();
		$allow_action=array("index","log");
		if(!in_array($this->request->action(),$allow_action)){
			$this->error("�Ƿ�������");
		}
	}
	
	public function index()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		$this->assign('prompt_text', Model('Text')->get_content('game_fenhong'));
		$coin_list = Db::name('Coin')->get_all_xnb_list();

		foreach ($coin_list as $k => $v) {
			$list[$k]['img'] = Db::name('Coin')->get_img($k);
			$list[$k]['title'] = $v;
			$list[$k]['quanbu'] = Db::name('Coin')->get_sum_coin($k);
			$list[$k]['wodi'] = Db::name('Coin')->get_sum_coin($k, userid());
			$list[$k]['bili'] = round(($list[$k]['wodi'] / $list[$k]['quanbu']) * 100, 2) . '%';
		}

		$this->assign('list', $list);
		return $this->fetch();
	}

	public function log()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		$this->assign('prompt_text', Model('Text')->get_content('game_fenhong_log'));
		$where['userid'] = userid();
		$Model = Db::name('FenhongLog');
		$count = $Model->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = $Model->where($where)->order('id desc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}
}

?>