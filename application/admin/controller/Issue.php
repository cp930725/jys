<?php
namespace app\admin\controller;

class Issue extends Admin
{
	public function index($name = NULL, $field = NULL, $status = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where('username', $name)->value('id');
			} else if ($field == 'name') {
				$where['name'] = array('like', '%' . $name . '%');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}

		$show = Db::name('Issue')->where($where)->paginate(10);
		
		$list = Db::name('Issue')->where($where)->order('id desc')->limit(0, 10)->select();
		foreach ($list as $k => $v) {
			$list[$k]['jian'] = $v['jian'].' '.$this->danweitostr($v['danwei']);
			//$list[$k]['endtime'] = date("Y-m-d H:i:s",strtotime($v['time']." + {$v['tian']} day"));
			$list[$k]['endtime'] = date("Y-m-d H:i:s",$v['endtime']);
		}
		
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}
	
	public function issueimage()
	{
		$upload = new \Think\Upload();
		$upload->maxSize = 3145728;
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		$upload->rootPath = './Upload/public/';
		$upload->autoSub = false;
		
		$info = $upload->upload();
		foreach ($info as $k => $v) {
			$path = $v['savepath'] . $v['savename'];
			echo $path;
			exit();
		}
	}

	public function edit()
	{
		if (empty($_GET['id'])) {
			$this->data = false;
		} else {
			$this->data = Db::name('Issue')->where('id', trim($_GET['id']))->find();
		}
		
		return $this->fetch();
	}

	public function save()
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		$_POST['addtime'] = time();

		if (strtotime($_POST['time']) != strtotime(addtime(strtotime($_POST['time'])))) {
			$this->error('开启时间格式错误！');
		}
		if (!floatval($_POST['tian'])) {
			$this->error('认购周期不能为空');
		}
		if (floatval($_POST['ci'] <= 0)) {
			$this->error('最低解冻次数不能小于0');
		}
		if (floatval($_POST['jian'] <= 0)) {
			$this->error('最低解冻间隔不能小于0');
		}
		if (floatval($_POST['min'] <= 0) || floatval($_POST['max'] <= 0)) {
			$this->error('单次最小数量 或 单次最大数量 不能小于0');
		}
		
		if($_POST['tuijian']==1){
			//推荐的话 先把其它的推荐修改成不推荐
			Db::name('Issue')-> where('tuijian=1')->setField('tuijian','2');
		}
		
		switch ($_POST['danwei']) {
			case 'y':
				$_POST['step'] = $_POST['jian'] * 12 * 30 * 24 * 60 * 60;
				break;

			case 'm':
				$_POST['step'] = $_POST['jian'] * 30 * 24 * 60 * 60;
				break;

			case 'd':
				$_POST['step'] = $_POST['jian'] * 24 * 60 * 60;
				break;

			case 'h':
				$_POST['step'] = $_POST['jian'] * 60 * 60;
				break;

			default:

			case 'i':
				$_POST['step'] = $_POST['jian'] * 60;
				break;
		}
		
		$_POST['endtime'] = strtotime($_POST['time']) + ($_POST['tian'] * 24 * 60 * 60);

		if ($_POST['id']) {
			$rs = Db::name('Issue')->update($_POST);
		} else {
			$rs = Db::name('Issue')->insert($_POST);
		}

		if ($rs) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function status()
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if ($this->request->ispost()) {
			$id = array();
			$id = implode(',', $_POST['id']);
		} else {
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
				if (Db::name('Issue')->where($where)->delete()) {
					$this->success('操作成功！');
				} else {
					$this->error('操作失败！');
				}

				break;

			default:
				$this->error('参数非法');
		}

		if (Db::name('Issue')->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function log($name = NULL)
	{
		if ($name && check($name, 'username')) {
			$where['userid'] = Db::name('User')->where('username', $name)->value('id');
		} else {
			$where = array();
		}

		$show = Db::name('IssueLog')->where($where)->paginate(10);

		$list = Db::name('IssueLog')->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = Db::name('User')->where('id', $v['userid'])->value('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}
	
	private function danweitostr($danwei)
	{
		switch ($danwei) {
			case 'y':
				return '年';
				break;

			case 'm':
				return '月';
				break;

			case 'd':
				return '天';
				break;

			case 'h':
				return '小时';
				break;

			default:

			case 'i':
				return '分钟';
				break;
		}
	}
}
?>