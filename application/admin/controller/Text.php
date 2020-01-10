<?php
namespace app\admin\controller;

class Text extends Admin
{
	protected function _initialize()
	{
		parent::_initialize();
		$allow_action=array("index","edit","status");
		if(!in_array($this->request->action(),$allow_action)){
			$this->error("页面不存在！");
		}
	}
	
	public function index($name = NULL, $field = NULL, $status = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where('username', $name)->value('id');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}

		$show = Db::name('Text')->where($where)->paginate(10);

		$list = Db::name('Text')->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) { }

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function edit($id = NULL)
	{
		if (empty($_POST)) {
			if ($id) {
				$this->data = Db::name('Text')->where('id', trim($id))->find();
			} else {
				$this->data = null;
			}

			return $this->fetch();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}
			
			if ($_POST['id']) {
				$_POST['endtime'] = time();
				
				$rs = Db::name('Text')->update($_POST);
			} else {
				$_POST['addtime'] = time();
				$_POST['endtime'] = time();
				
				if (Db::name('Text')->where('name', $_POST['name'])->find()) {
					$this->error('提示标识已存在！');
				}
				
				$rs = Db::name('Text')->insert($_POST);
			}

			if ($rs) {
				$this->success('编辑成功！');
			} else {
				$this->error('编辑失败！');
			}
		}
	}

	public function status($id = NULL, $type = NULL, $mobile = 'text')
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
			//$this->error('请选择要操作的数据!');
		}

		$where['id'] = array('in', $id);
		$method = $_GET['type'];

		switch (strtolower($method)) {
			case 'forbid':
				$data = array('status' => 0);
				break;

			case 'resume':
				$data = array('status' => 1);
				break;
				
			case 'repeal':
				$data = array('status' => 2, 'endtime' => time());
				break;
				
			case 'delt':
				if (Db::name($mobile)->where($where)->delete()) {
					$this->success('操作成功！');
				} else {
					$this->error('操作失败！');
				}
				break;

			default:
				$this->error('非法参数！'.$_GET['type']);
		}

		if (Db::name($mobile)->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}
}

?>