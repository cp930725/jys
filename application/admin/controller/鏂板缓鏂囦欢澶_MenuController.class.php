<?php
namespace app\admin\controller;

class MenuController extends AdminController
{
	public function index()
	{
		$pid = input('get.pid', 0);

		if ($pid) {
			$data = Db::name('Menu')->where('id=' . $pid)->field(true)->find();
			$this->assign('data', $data);
		}

		$title = trim(input('get.title'));
		$type = config('CONFIG_GROUP_LIST');
		$all_menu = Db::name('Menu')->value('id,title');
		$map['pid'] = $pid;

		if ($title) {
			$map['title'] = array('like', '%' . $title . '%');
		}

		$list = Db::name('Menu')->where($map)->field(true)->order('sort asc,id asc')->select();
		int_to_string($list, array(
			'hide'   => array(1 => '是', 0 => '否'),
			'is_dev' => array(1 => '是', 0 => '否')
			));

		if ($list) {
			foreach ($list as &$key) {
				if ($key['pid']) {
					$key['up_title'] = $all_menu[$key['pid']];
				}
			}

			$this->assign('list', $list);
		}

		Cookie('__forward__', $_SERVER['REQUEST_URI']);
		$this->meta_title = '菜单列表';
		return $this->fetch();
	}

	public function add()
	{
		if (!empty($_POST)) {
			// if (APP_DEMO) {
			// 	$this->error('测试站暂时不能修改！');
			// }
			// echo '<pre>';
			// var_dump($_POST);
			// echo '</pre>';
			// die();
			$adds = array();
			foreach ($_POST as $k => $v) {
				if($k == 'id'){ continue;}
				$adds[$k] = $v;
			}
			// $Menu = Model('Menu');
			// $data = $Menu->create();

			// if ($data) {
			// 	$id = $Menu->insert();

			// 	if ($id) {
			// 		// action_log('update_menu', 'Menu', $id, UID);
			// 		$this->success('新增成功', Cookie('__forward__'));
			// 	}
			// 	else {
			// 		$this->error('新增失败');
			// 	}
			// }
			// else {
			// 	$this->error($Menu->getError());
			// }
			if (Db::name('Menu')->insert($adds)) {
				$this->success('添加成功！');
			}else {
				$this->error('添加失败！');
			}
		}else {
			$this->assign('info', array('pid' => input('pid')));
			$menus = Db::name('Menu')->field(true)->select();
			$menus = Model('Tree')->toFormatTree($menus);
			$menus = array_merge(array(
				array('id' => 0, 'title_show' => '顶级菜单')
				), $menus);
			$this->assign('Menus', $menus);
			$this->meta_title = '新增菜单';
			$this->display('edit');
		}
	}

	public function edit($id = 0)
	{
		if ($this->request->ispost()) {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$Menu = Model('Menu');
			$data = $Menu->create();

			if ($data) {
				if ($Menu->save() !== false) {
					action_log('update_menu', 'Menu', $data['id'], UID);
					$this->success('更新成功', Cookie('__forward__'));
				}
				else {
					$this->error('更新失败');
				}
			}
			else {
				$this->error($Menu->getError());
			}
		}
		else {
			$info = array();
			$info = Db::name('Menu')->field(true)->find($id);
			$menus = Db::name('Menu')->field(true)->select();
			$menus = Model('Tree')->toFormatTree($menus);
			$menus = array_merge(array(
				array('id' => 0, 'title_show' => '顶级菜单')
				), $menus);
			$this->assign('Menus', $menus);

			if (false === $info) {
				$this->error('获取后台菜单信息错误');
			}

			$this->assign('info', $info);
			$this->meta_title = '编辑后台菜单';
			return $this->fetch();
		}
	}

	public function del()
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		$id = array_unique((array) input('id', 0));

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$map = array(
			'id' => array('in', $id)
			);

		if (Db::name('Menu')->where($map)->delete()) {
			action_log('update_menu', 'Menu', $id, UID);
			$this->success('删除成功');
		}
		else {
			$this->error('删除失败！');
		}
	}

	public function toogleHide($id, $value = 1)
	{
		$this->editRow('Menu', array('hide' => $value), array('id' => $id));
	}

	public function toogleDev($id, $value = 1)
	{
		$this->editRow('Menu', array('is_dev' => $value), array('id' => $id));
	}

	public function importFile($tree = NULL, $pid = 0)
	{
		if ($tree == null) {
			$file = APP_PATH . 'Admin/Conf/Menu.php';
			$tree = require_once $file;
		}

		$menuModel = Model('Menu');

		foreach ($tree as $value) {
			$add_pid = $menuModel->insert(array('title' => $value['title'], 'url' => $value['url'], 'pid' => $pid, 'hide' => isset($value['hide']) ? (int) $value['hide'] : 0, 'tip' => isset($value['tip']) ? $value['tip'] : '', 'group' => $value['group']));

			if ($value['operator']) {
				$this->import($value['operator'], $add_pid);
			}
		}
	}

	public function import()
	{
		if ($this->request->ispost()) {
			$tree = input('post.tree');
			$lists = explode(PHP_EOL, $tree);
			$menuModel = Db::name('Menu');

			if ($lists == array()) {
				$this->error('请按格式填写批量导入的菜单，至少一个菜单');
			}
			else {
				$pid = input('post.pid');

				foreach ($lists as $key => $value) {
					$record = explode('|', $value);

					if (count($record) == 2) {
						$menuModel->insert(array('title' => $record[0], 'url' => $record[1], 'pid' => $pid, 'sort' => 0, 'hide' => 0, 'tip' => '', 'is_dev' => 0, 'group' => ''));
					}
				}

				$this->success('导入成功', url('index?pid=' . $pid));
			}
		}
		else {
			$this->meta_title = '批量导入后台菜单';
			$pid = (int) input('get.pid');
			$this->assign('pid', $pid);
			$data = Db::name('Menu')->where('id=' . $pid)->field(true)->find();
			$this->assign('data', $data);
			return $this->fetch();
		}
	}

	public function sort()
	{
		if (IS_GET) {
			$ids = input('get.ids');
			$pid = input('get.pid');
			$map = array(
				'status' => array('gt', -1)
				);

			if (!empty($ids)) {
				$map['id'] = array('in', $ids);
			}
			else if ($pid !== '') {
				$map['pid'] = $pid;
			}

			$list = Db::name('Menu')->where($map)->field('id,title')->order('sort asc,id asc')->select();
			$this->assign('list', $list);
			$this->meta_title = '菜单排序';
			return $this->fetch();
		}
		else if ($this->request->ispost()) {
			$ids = input('post.ids');
			$ids = explode(',', $ids);

			foreach ($ids as $key => $value) {
				$res = Db::name('Menu')->where('id', $value))->setField('sort', $key + 1);
			}

			if ($res !== false) {
				$this->success('排序成功！');
			}
			else {
				$this->eorror('排序失败！');
			}
		}
		else {
			$this->error('非法请求！');
		}
	}
}

?>