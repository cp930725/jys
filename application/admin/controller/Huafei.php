<?php
namespace app\admin\controller;

class Huafei extends Admin
{
	public function index($name = NULL)
	{
		$where = array();

		if ($name && ($userid = Model('User')->get_userid($name))) {
			$where['userid'] = $userid;
		}

		$where['status'] = array('neq', -1);
		$show = Db::name('Huafei')->where($where)->paginate(10);

		$list = Db::name('Huafei')->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = Model('User')->get_username($v['userid']);
			$list[$k]['mum'] = Num($v['mum']);
			$list[$k]['addtime'] = addtime($v['addtime']);
			$list[$k]['endtime'] = addtime($v['endtime']);
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function delete($id = NULL)
	{
				if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}
		if (Model('Huafei')->setStatus($id, 'delete')) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function repeal($id = NULL)
	{
		$huafei = Db::name('Huafei')->where('id', $id)->find();

		if (!$huafei) {
			$this->error('不存在！');
		}

		if ($huafei['status'] != 0) {
			$this->error('已经处理过！');
		}

		$mo = db();
		Db::execute('set autocommit=0');
		Db::execute('lock tables  tw_user_coin write  , tw_huafei write ');
		$rs = array();
		$user_coin = Db::table('tw_user_coin')->where('userid', $huafei['userid'])->find();

		if (!$user_coin) {
			session(null);
			$this->error('用户财产错误!');
		}

		$rs[] = Db::table('tw_user_coin')->where('userid', $huafei['userid'])->setInc($huafei['type'], $huafei['mum']);
		$rs[] = Db::table('tw_huafei')->where('id', $id)->update(array('endtime' => time(), 'status' => 2));

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

	public function resume($id = NULL)
	{
		if (empty($id)) {
			$this->error('参数错误！');
		}

		$huafei = Db::name('Huafei')->where('id', $id)->find();

		if (!$huafei) {
			$this->error('数据错误！');
		}

		//if (huafei($huafei['mobile'], $huafei['num'], md5($huafei['id']))) {
		if (1) {
			if (Model('Huafei')->setStatus($id, 'resume')) {
				$this->success('操作成功！');
			}
			else {
				$this->error('操作失败！');
			}
		}
		else {
			$this->error('第三方付款失败!');
		}
	}

	public function config()
	{

		$Config_DbFields = Db::name('Config')->getDbFields();

		if (!in_array('huafei_appkey', $Config_DbFields)) {
			Db::execute('ALTER TABLE `tw_config` ADD COLUMN `huafei_appkey` VARCHAR(200)  NOT NULL   COMMENT \'名称\' AFTER `id`;');
		}

		if (!in_array('huafei_openid', $Config_DbFields)) {
			Db::execute('ALTER TABLE `tw_config` ADD COLUMN `huafei_openid` VARCHAR(200)  NOT NULL   COMMENT \'名称\' AFTER `id`;');
		}

		if (!in_array('huafei_zidong', $Config_DbFields)) {
			Db::execute('ALTER TABLE `tw_config` ADD COLUMN `huafei_zidong` VARCHAR(200)  NOT NULL   COMMENT \'名称\' AFTER `id`;');
		}

		if (empty($_POST)) {
			return $this->fetch();
		}
		else if (Db::name('Config')->where('id', 1)->update($_POST)) {
			$this->success('修改成功！');
		}
		else {
			$this->error('修改失败');
		}
	}

	public function type()
	{
		$where = array();
		$where['status'] = array('neq', -1);
		$show = Db::name('HuafeiType')->where($where)->paginate(10);

		$list = Db::name('HuafeiType')->where($where)->order('id desc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function forbidType($id = NULL)
	{
		if (Model('Huafei')->setStatus($id, 'forbid', 'HuafeiType')) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function resumeType($id = NULL)
	{
		if (Model('Huafei')->setStatus($id, 'resume', 'HuafeiType')) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function coin()
	{
		$where = array();
		$where['status'] = array('neq', -1);
		$show = Db::name('HuafeiCoin')->where($where)->paginate(10);

		$list = Db::name('HuafeiCoin')->where($where)->order('id desc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function forbidCoin($id = NULL)
	{
				if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}
		if (Model('Huafei')->setStatus($id, 'forbid', 'HuafeiCoin')) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function resumeCoin($id = NULL)
	{
				if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}
		if (Model('Huafei')->setStatus($id, 'resume', 'HuafeiCoin')) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function deleteCoin($id = NULL)
	{
				if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}
		if (Model('Huafei')->setStatus($id, 'del', 'HuafeiCoin')) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function editCoin($id = NULL)
	{
		if (empty($_POST)) {
			if ($id) {
				$this->data = Db::name('HuafeiCoin')->where('id', trim($id))->find();
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
			if (!config('coin')[$_POST['coinname']]) {
				$this->error('币种错误！');
			}

			if ($_POST['id']) {
				$rs = Db::name('HuafeiCoin')->update($_POST);
			}
			else {
				if ($id = Db::name('HuafeiCoin')->where('coinname', $_POST['coinname'])->find()) {
					$this->error('币种存在！');
				}

				$rs = Db::name('HuafeiCoin')->insert($_POST);
			}

			if ($rs) {
				$this->success('操作成功！');
			}
			else {
				$this->error('操作失败！');
			}
		}
	}
}

?>