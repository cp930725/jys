<?php
namespace Home\Controller;

class HuafeiController extends HomeController
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
		if (empty($_POST)) {
			if (!userid()) {
				$this->redirect(url('Login/index'));
			}

			$this->assign('prompt_text', Model('Text')->get_content('game_huafei'));
			$user_coin = Db::name('UserCoin')->where('userid', userid())->find();
			$user_coin['cny'] = round($user_coin['cny'], 2);
			$this->assign('user_coin', $user_coin);
			$this->assign('huafei_num', Model('Huafei')->get_type());
			$this->assign('huafei_type', Model('Huafei')->get_coin());
			$where['userid'] = userid();
			$where['status'] = array('neq', -1);
			$count = Db::name('Huafei')->where($where)->count();
			$Page = new \Think\Page($count, 10);
			$show = $Page->show();
			$list = Db::name('Huafei')->where($where)->order('id desc')->limit(0, 10)->select();

			foreach ($list as $k => $v) {
				$list[$k]['type'] = config('coin')[$v['type']]['title'];
			}

			$this->assign('list', $list);
			$this->assign('page', $show);
			return $this->fetch();
		}
		else {
			$mobile = $_POST['mobile'];
			$num = $_POST['num'];
			$type = $_POST['type'];
			$paypassword = $_POST['paypassword'];

			if (!check($mobile, 'mobile')) {
				$this->error('手机号码格式错误!');
			}

			if (!check($num, 'd')) {
				$this->error('充值金额格式错误!');
			}

			if (!check($type, 'n')) {
				$this->error('充值方式格式错误!');
			}

			if (!check($paypassword, 'password')) {
				$this->error('交易密码格式错误!');
			}

			if (!D('Huafei')->get_type($num)) {
				$this->error('充值金额不存在!');
			}

			$huafei_type = Db::name('Huafei')->get_coin();

			if (!$huafei_type[$type]) {
				$this->error('充值方式不存在!');
			}

			if (!userid()) {
				$this->error('请先登录!');
			}

			$user = Db::name('User')->where('id', userid())->find();

			if (!$user) {
				$this->error('用户不存在!');
			}

			if (!$user['status']) {
				session(null);
				$this->error('用户已冻结!');
			}

			if ($user['paypassword'] != md5($paypassword)) {
				$this->error('交易密码错误!');
			}

			$mum = round($num / $huafei_type[$type][1], 8);

			if ($mum < 0) {
				$this->error('付款金额错误!');
			}

			$mo = db();
			Db::execute('set autocommit=0');
			Db::execute('lock tables  tw_user_coin write  , tw_huafei write ');
			$rs = [];
			$user_coin = Db::table('tw_user_coin')->where('userid', userid())->find();

			if (!$user_coin) {
				session(null);
				$this->error('用户财产错误,请重新登录!');
			}

			if ($user_coin[$type] < $mum) {
				$this->error('可用' . $huafei_type[$type][0] . '余额不足,总共需要支付' . $mum . ' ' . $huafei_type[$type][0]);
			}

			$rs[] = Db::table('tw_user_coin')->where('userid', userid())->setDec($type, $mum);
			$rs[] = $huafei_id = Db::table('tw_huafei')->insert(array('userid' => userid(), 'mobile' => $mobile, 'num' => $num, 'type' => $type, 'mum' => $mum, 'addtime' => time(), 'status' => 0));

			if (cache('huafei_zidong')) {
				if (huafei($mobile, $num, md5($huafei_id))) {
					$rs[] = Db::table('tw_huafei')->where('id', $huafei_id)->update(array('endtime' => time(), 'status' => 1));
				}
			}

			if (check_arr($rs)) {
				Db::execute('commit');
				Db::execute('unlock tables');
				$this->success('操作成功！');
			}
			else {
				Db::execute('rollback');
				$this->error('操作失败!');
			}
		}
	}
}

?>