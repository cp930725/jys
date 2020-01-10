<?php
namespace app\admin\controller;

class App extends Admin
{
	public function __construct()
	{
		parent::__construct();
	}

	public function config()
	{
		if (empty($_POST)) {
			$appc = Model('Appc')->find();
			$appc['pay'] = json_decode($appc['pay'], true);
			$show_coin = json_decode($appc['show_coin'], true);
			$Coin = Model('coin')->where('type in ("rgb","qbb") and status = 1')->select();
			$appc['show_coin'] = array();

			foreach ($Coin as $val) {
				$appc['show_coin'][] = array('id' => $val['id'], 'name' => $val['title'] . '(' . $val['name'] . ')', 'flag' => $show_coin ? (in_array($val['id'], $show_coin) ? 1 : 0) : 1);
			}

			$show_market = json_decode($appc['show_market'], true);
			$Market = Model('Market')->where('status = 1')->select();
			$appc['show_market'] = array();

			foreach ($Market as $val) {
				$coin_name = explode('_', $val['name']);
				$xnb_name = Model('Coin')->where('name', $coin_name[0])->find()['title'];
				$rmb_name = Model('Coin')->where('name', $coin_name[1])->find()['title'];
				$appc['show_market'][] = array('id' => $val['id'], 'name' => $xnb_name . '/' . $rmb_name . '(' . $val['name'] . ')', 'flag' => $show_market ? (in_array($val['id'], $show_market) ? 1 : 0) : 1);
			}

			$this->assign('appCon', $appc);
			return $this->fetch();
		}
		else {
			$_POST['pay'] = json_encode($_POST['pay']);
			$_POST['show_coin'] = json_encode($_POST['show_coin']);
			$_POST['show_market'] = json_encode($_POST['show_market']);

			if (Model('Appc')->update($_POST)) {
				$this->success('保存成功！');
			}
			else {
				$this->error('没有修改');
			}
		}
	}

	public function vip_config_list()
	{
		$coin = Model('coin')->select();
		$coinMap = array();

		foreach ($coin as $val) {
			$coinMap[$val['id']] = $val['title'];
		}

		$this->assign('coinMap', $coinMap);
		$this->Model = Model('AppVip');
		$where = array();
		$show = $this->Model->where($where)->paginate(10);

		$list = $this->Model->where($where)->order('id desc')->limit(0, 10)->order('tag asc')->select();

		foreach ($list as $key => $val) {
			$val['rule'] = json_decode($val['rule'], true);
			$list[$key] = $val;
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function vip_config_edit()
	{
		if (empty($_POST)) {
			$coin = Model('Coin')->where('status = 1')->select();
			$this->assign('coin', $coin);

			if (isset($_GET['id']) && $_GET['id']) {
				$vipArr = Model('AppVip')->where('id', trim($_GET['id']))->find();
				$vipArr['rule'] = json_decode($vipArr['rule'], true);
				$this->assign('idi', count($vipArr['rule']));
				$rule_t = str_repeat('1,', count($vipArr['rule']));
				$rule_t = mb_substr($rule_t, 0, -1);
				$this->assign('rule_str', '[' . $rule_t . ']');
				$this->assign('data', $vipArr);
			}
			else {
				$this->assign('rule_str', '[]');
				$this->assign('idi', 0);
			}

			return $this->fetch();
		}
		else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			if (!$_POST['tag']) {
				$this->error('等级次序不能为空');
			}

			if (!check($_POST['tag'], 'integer')) {
				$this->error('等级次序必须为整数！');
			}

			if ($res = Model('AppVip')->where('tag', $_POST['tag'])->find()) {
				if ($res['id'] != $_POST['id']) {
					$this->error('等级次序' . $_POST['tag'] . ' 已经存在！');
				}
			}

			$_POST['rule'] = json_decode($_POST['rule'], true);
			$key_map = array();
			$rule = array();

			foreach ($_POST['rule'] as $val) {
				if (!isset($key_map[$val['id']])) {
					$key_map[$val['id']] = 1;
					$rule[] = $val;
				}
				else {
					$this->error('升级币种不能相同');
				}
			}

			$_POST['rule'] = json_encode($rule);

			if ($_POST['id']) {
				$rs = Model('AppVip')->update($_POST);
			}
			else {
				$_POST['addtime'] = time();
				$rs = Model('AppVip')->insert($_POST);
			}

			if ($rs) {
				$this->success('操作成功！');
			}
			else {
				$this->error('没有任何修改!');
			}
		}
	}

	public function vip_config_edit_status()
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if ($this->request->ispost()) {
			$id = array();
			$id = implode(',', $_POST['id']);
		}
		else {
			$id = $_GET['id'];
		}

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$where['id'] = array('in', $id);
		$method = $_GET['method'];

		switch (strtolower($method)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'delete':
			if (Model('Appadsblock')->where($where)->delete()) {
				$this->success('操作成功！');
			}
			else {
				$this->error('没有任何修改！');
			}

			break;

		default:
			$this->error('参数非法');
		}

		if (Model('Appadsblock')->where($where)->update($data)) {
			$this->success('操作成功！');
		}
		else {
			$this->error('没有任何修改！');
		}
	}

	public function adsblock_list()
	{
		$rankMap = array();
		$AppVip = Model('AppVip')->where('status', 1)->select();

		foreach ($AppVip as $val) {
			$rankMap[$val['id']] = $val['name'];
		}

		$this->assign('rankMap', $rankMap);
		$this->Model = Model('Appadsblock');
		$where = array();
		$show = $this->Model->where($where)->paginate(10);

		$list = $this->Model->where($where)->order('id desc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function adsblock_edit()
	{
		if (empty($_POST)) {
			$AppVip = Model('AppVip')->where('status', 1)->select();
			$this->assign('AppVip', $AppVip);

			if (isset($_GET['id'])) {
				$this->data = Model('Appadsblock')->where('id', trim($_GET['id']))->find();
			}
			else {
				$this->data = null;
			}

			return $this->fetch();
		}
		else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			if ($_POST['id']) {
				$rs = Model('Appadsblock')->update($_POST);
			}
			else {
				$_POST['adminid'] = session('admin_id');
				$rs = Model('Appadsblock')->insert($_POST);
			}

			if ($rs) {
				$this->success('操作成功！');
			}
			else {
				$this->error('没有任何修改！');
			}
		}
	}

	public function adsblock_edit_status()
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if ($this->request->ispost()) {
			$id = array();
			$id = implode(',', $_POST['id']);
		}
		else {
			$id = $_GET['id'];
		}

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$where['id'] = array('in', $id);
		$method = $_GET['method'];

		switch (strtolower($method)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'delete':
			if (Model('Appadsblock')->where($where)->delete()) {
				$this->success('操作成功！');
			}
			else {
				$this->error('没有任何修改！');
			}

			break;

		default:
			$this->error('参数非法');
		}

		if (Model('Appadsblock')->where($where)->update($data)) {
			$this->success('操作成功！');
		}
		else {
			$this->error('没有任何修改！');
		}
	}

	public function ads_list($block_id)
	{
		$block_id = intval($block_id);
		$ads_block = Db::name('Appadsblock')->where('id', $block_id)->find();
		$this->assign('ads_block', $ads_block);
		$this->Model = Model('Appads');

		if ($block_id) {
			$where['block_id'] = $block_id;
		}

		$show = $this->Model->where($where)->paginate(10);

		$list = $this->Model->where($where)->order('id desc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function ads_edit()
	{
		if (empty($_POST)) {
			if (isset($_GET['id'])) {
				$this->data = Model('Appads')->where('id', trim($_GET['id']))->find();
			}
			else {
				$this->data = null;
			}

			return $this->fetch();
		}
		else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$upload = new \Think\Upload();
			$upload->maxSize = 3145728;
			$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
			$upload->rootPath = './Upload/ad/';
			$upload->autoSub = false;
			$info = $upload->upload();

			if ($info) {
				foreach ($info as $k => $v) {
					$_POST[$v['key']] = $v['savename'];
				}
			}

			if ($_POST['id']) {
				$rs = Model('Appads')->update($_POST);
			}
			else {
				$_POST['adminid'] = session('admin_id');
				$rs = Model('Appads')->insert($_POST);
			}

			if ($rs) {
				$this->success('操作成功！');
			}
			else {
				$this->error('没有任何修改！');
			}
		}
	}

	public function ads_edit_status()
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if ($this->request->ispost()) {
			$id = array();
			$id = implode(',', $_POST['id']);
		}
		else {
			$id = $_GET['id'];
		}

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$where['id'] = array('in', $id);
		$method = $_GET['method'];

		switch (strtolower($method)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'delete':
			if (Model('Appads')->where($where)->delete()) {
				$this->success('操作成功！');
			}
			else {
				$this->error('没有任何修改！');
			}

			break;

		default:
			$this->error('参数非法');
		}

		if (Model('Appads')->where($where)->update($data)) {
			$this->success('操作成功！');
		}
		else {
			$this->error('没有任何修改！');
		}
	}

	public function ads_user()
	{
		$this->Model = Db::name('AppVipuser');
		$where = array();
		$show = $this->Model->join('tw_user ON tw_user.id = tw_app_vipuser.uid')->join('tw_app_vip ON tw_app_vip.id = tw_app_vipuser.vip_id')->field('tw_user.username,tw_app_vipuser.*,tw_app_vip.name as vip_name,tw_app_vip.tag')->where($where)->paginate(10);

		$list = $this->Model->join('tw_user ON tw_user.id = tw_app_vipuser.uid')->join('tw_app_vip ON tw_app_vip.id = tw_app_vipuser.vip_id')->field('tw_user.username,tw_app_vipuser.*,tw_app_vip.name as vip_name,tw_app_vip.tag')->where($where)->order('id desc')->limit(0, 10)->select();
		$this->assign('list', $list);

		foreach ($list as $key => $val) {
			
		}

		$this->assign('page', $show);
		return $this->fetch();
	}

	public function ads_user_detail($uid = NULL)
	{
		$where = array();
		$this->Model = Model('AppLog');

		if ($uid) {
			$where['uid'] = $uid;
		}

		$show = $this->Model->where($where)->paginate(10);

		$list = $this->Model->where($where)->order('id desc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function upload()
	{
		$upload = new \Think\Upload();
		$upload->maxSize = 3145728;
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		$upload->rootPath = './Upload/app/';
		$upload->autoSub = false;
		$info = $upload->upload();

		foreach ($info as $k => $v) {
			$path = '/Upload/app/' . $v['savepath'] . $v['savename'];
			echo $path;
			exit();
		}
	}
}

?>