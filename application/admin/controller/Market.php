<?php
namespace app\admin\controller;

class Market extends Admin
{
	private $Model;

	public function __construct()
	{
		parent::__construct();
		$this->Model = Db::name('Market');
		$this->Title = '市场配置';
	}

	public function index($name = NULL)
	{
		if ($name) {
			$where['name'] = $name;
		}

		$show = $this->Model->where($where)->paginate(10);

		$list = $this->Model->where($where)->order('id desc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function edit($id = NULL)
	{
		if (empty($id)) {
			$this->data = array();
		} else {
			$this->data = $this->Model->where('id', $id)->find();
		}

		return $this->fetch();
	}

	public function save()
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		$round = array(0, 1, 2, 3, 4, 5, 6);

		if (!in_array($_POST['round'], $round)) {
			$this->error('小数位数格式错误！');
		}

		if ($_POST['id']) {
			$rs = $this->Model->save($_POST);
		} else {
			$_POST['name'] = $_POST['sellname'] . '_' . $_POST['buyname'];
			unset($_POST['buyname']);
			unset($_POST['sellname']);

			if (Db::name('Market')->where('name', $_POST['name'])->find()) {
				$this->error('市场存在！');
			}

			$rs = $this->Model->insert($_POST);
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
			if ($this->Model->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('参数非法');
		}

		if ($this->Model->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}
}
?>