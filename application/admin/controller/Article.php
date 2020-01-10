<?php
namespace app\admin\controller;

use think\Db;

class Article extends Admin
{
	protected function _initialize(){
		parent::_initialize();
		$allow_action=array("index","edit","wenzhangimg","status","type","typeEdit","typeStatus","adver","adverEdit","adverStatus","adverImage","youqing","youqingEdit","youqingStatus");
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
			} else if ($field == 'title') {
				$where['title'] = array('like', '%' . $name . '%');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}

		$show = Db::name('Article')->where($where)->paginate(10);

		$list = Db::name('Article')->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			$list[$k]['adminid'] = Db::name('Admin')->where('id', $v['adminid'])->value('username');
			$list[$k]['type'] = Db::name('ArticleType')->where('id', $v['pid'])->value('title');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function edit($id = NULL, $type = NULL)
	{
		if (empty($_POST)) {
			$list = Db::name('ArticleType')->where('pid', 'neq', 0)->select();
			$this->assign('list', $list);

			if ($id) {
				$this->data = Db::name('Article')->where('id', trim($id))->find();
				$this->assign('data', $this->data);
			} else {
				$this->data = null;
                $this->assign('data', $this->data);
            }

			return $this->fetch();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			if ($type == 'images') {
				$baseUrl = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
				$upload = new \Think\Upload();
				$upload->maxSize = 3145728;
				$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
				$upload->rootPath = './Upload/article/';
				$upload->autoSub = false;
				$info = $upload->uploadOne($_FILES['imgFile']);

				if ($info) {
					$data = array('url' => str_replace('./', '/', $upload->rootPath) . $info['savename'], 'error' => 0);
					exit(json_encode($data));
				} else {
					$error['error'] = 1;
					$error['message'] = '';
					exit(json_encode($error));
				}
			} else {
				$upload = new \Think\Upload();
				$upload->maxSize = 3145728;
				$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
				$upload->rootPath = './Upload/article/';
				$upload->autoSub = false;
				$info = $upload->upload();

				if ($info) {
					foreach ($info as $k => $v) {
						$_POST[$v['key']] = $v['savename'];
					}
				}
				
				
				if ($_POST['id']) {
					if ($_POST['addtime']) {
						if (addtime(strtotime($_POST['addtime'])) == '---') {
							$this->error('编辑时间格式错误');
						} else {
							$_POST['addtime'] = strtotime($_POST['addtime']);
						}
					} else {
						$_POST['addtime'] = time();
					}
					
					if ($_POST['endtime']) {
						if (addtime(strtotime($_POST['endtime'])) == '---') {
							$this->error('编辑时间格式错误');
						} else {
							$_POST['endtime'] = strtotime($_POST['endtime']);
						}
					} else {
						$_POST['endtime'] = time();
					}
										
					$rs = Db::name('Article')->update($_POST);
				} else {
					$_POST['addtime'] = time();
					$_POST['endtime'] = time();
					
					$_POST['adminid'] = session('admin_id');
					$rs = Db::name('Article')->insert($_POST);
				}

				if ($rs) {
					$this->success('编辑成功！');
				} else {
					$this->error('编辑失败！');
				}
			}
		}
	}

	public function wenzhangimg()
	{
		$upload = new \Think\Upload();
		$upload->maxSize = 3145728;
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		$upload->rootPath = './Upload/wenzhang/';
		$upload->autoSub = false;
		$info = $upload->upload();

		foreach ($info as $k => $v) {
			$path = $v['savepath'] . $v['savename'];
			// echo $path;
			$p = trim($path);
			echo json_encode($p);
			exit();
		}
	}

	public function status($id = NULL, $type = NULL, $mobile = 'Article')
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if (empty($id)) {
			$this->error('参数错误！');
		}
		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'repeal':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'delete':
			$data = array('status' => -1);
			break;

		case 'del':
			if (Db::name($mobile)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (Db::name($mobile)->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function type($name = NULL, $field = NULL, $status = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where('username', $name)->value('id');
			} else if ($field == 'title') {
				$where['title'] = array('like', '%' . $name . '%');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}
		
		$where_1 = $where;
		$where_1['pid'] = 0;
		$where_2 = $where;
		
		$list = Db::name('ArticleType')->where($where_1)->order('sort asc')->select();
		foreach ($list as $k => $v) {
			$where_2['pid'] = $v['id'];
			$list[$k]['voo'] = Db::name('ArticleType')->where($where_2)->order('sort asc')->select();
		}

		$this->assign('list', $list);
		return $this->fetch();
	}

	public function typeEdit($id = NULL, $type = NULL)
	{
		$list = Db::name('ArticleType')->where('pid', 0)->select();
		$this->assign('list', $list);

		if (empty($_POST)) {
			if ($id) {
				$this->data = Db::name('ArticleType')->where('id', trim($id))->find();
			} else {
				$this->data = null;
			}

			return $this->fetch();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			if ($type == 'images') {
				$baseUrl = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
				$upload = new \Think\Upload();
				$upload->maxSize = 3145728;
				$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
				$upload->rootPath = './Upload/article/';
				$upload->autoSub = false;
				$info = $upload->uploadOne($_FILES['imgFile']);

				if ($info) {
					$data = array('url' => str_replace('./', '/', $upload->rootPath) . $info['savename'], 'error' => 0);
					exit(json_encode($data));
				} else {
					$error['error'] = 1;
					$error['message'] = '';
					exit(json_encode($error));
				}
			} else {

				if ($_POST['id']) {
					$_POST['endtime'] = time(); //编辑时间

					$rs = Db::name('ArticleType')->update($_POST);
				} else {
					$_POST['addtime'] = time(); //添加时间
					
					$_POST['adminid'] = session('admin_id');
					$rs = Db::name('ArticleType')->insert($_POST);
				}


				if ($rs) {
					$this->success('编辑成功！');
				} else {
					$this->error('编辑失败！');
				}
			}
		}
	}

	public function typeStatus($id = NULL, $type = NULL, $mobile = 'ArticleType')
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'repeal':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'delete':
			$data = array('status' => -1);
			break;

		case 'del':
			if (Db::name($mobile)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (Db::name($mobile)->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}
	
	// 广告管理
	public function adver($name = NULL, $field = NULL, $status = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where('username', $name)->value('id');
			} else if ($field == 'title') {
				$where['title'] = array('like', '%' . $name . '%');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}

		$show = Db::name('Adver')->where($where)->paginate(10);

		$list = Db::name('Adver')->where($where)->order('id desc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}
	
	// 广告管理 新增&编辑
	public function adverEdit($id = NULL)
	{
		if (empty($_POST)) {
			if ($id) {
				$this->data = Db::name('Adver')->where('id', trim($id))->find();
			} else {
				$this->data = null;
			}

			return $this->fetch();
		} else {
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
			
			if ($_POST['onlinetime']) {
				if (addtime(strtotime($_POST['onlinetime'])) == '---') {
					$this->error('添加时间格式错误');
				} else {
					$_POST['onlinetime'] = strtotime($_POST['onlinetime']);
				}
			} else {
				$_POST['onlinetime'] = time();
			}

			if ($_POST['addtime']) {
				if (addtime(strtotime($_POST['addtime'])) == '---') {
					$this->error('添加时间格式错误');
				} else {
					$_POST['addtime'] = strtotime($_POST['addtime']);
				}
			} else {
				$_POST['addtime'] = time();
			}

			if ($_POST['endtime']) {
				if (addtime(strtotime($_POST['endtime'])) == '---') {
					$this->error('编辑时间格式错误');
				}
				else {
					$_POST['endtime'] = strtotime($_POST['endtime']);
				}
			} else {
				$_POST['endtime'] = time();
			}

			if ($_POST['id']) {
				$rs = Db::name('Adver')->update($_POST);
			} else {
				//$_POST['adminid'] = session('admin_id');
				$rs = Db::name('Adver')->insert($_POST);
			}

			if ($rs) {
				$this->success('编辑成功！',url('Article/adver'));
			} else {
				$this->error('编辑失败！');
			}
		}
	}

	public function adverStatus($id = NULL, $type = NULL, $mobile = 'Adver')
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'repeal':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'delete':
			$data = array('status' => -1);
			break;

		case 'del':
			if (Db::name($mobile)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (Db::name($mobile)->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function adverImage()
	{
		$upload = new \Think\Upload();
		$upload->maxSize = 3145728;
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		$upload->rootPath = './Upload/ad/';
		$upload->autoSub = false;
		$info = $upload->upload();

		foreach ($info as $k => $v) {
			$path = $v['savepath'] . $v['savename'];
			echo $path;
			exit();
		}
	}

	public function youqing($name = NULL, $field = NULL, $status = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where('username', $name)->value('id');
			} else if ($field == 'title') {
				$where['title'] = array('like', '%' . $name . '%');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}

		$show = Db::name('Link')->where($where)->paginate(10);

		$list = Db::name('Link')->where($where)->order('id desc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function youqingEdit($id = NULL)
	{
		if (empty($_POST)) {
			if ($id) {
				$this->data = Db::name('Link')->where('id', trim($id))->find();
			} else {
				$this->data = null;
			}

			return $this->fetch();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			if ($_POST['addtime']) {
				if (addtime(strtotime($_POST['addtime'])) == '---') {
					$this->error('添加时间格式错误');
				} else {
					$_POST['addtime'] = strtotime($_POST['addtime']);
				}
			} else {
				$_POST['addtime'] = time();
			}

			if ($_POST['endtime']) {
				if (addtime(strtotime($_POST['endtime'])) == '---') {
					$this->error('编辑时间格式错误');
				} else {
					$_POST['endtime'] = strtotime($_POST['endtime']);
				}
			} else {
				$_POST['endtime'] = time();
			}

			if ($_POST['id']) {
				$rs = Db::name('Link')->update($_POST);
			} else {
				$rs = Db::name('Link')->insert($_POST);
			}

			if ($rs) {
				$this->success('编辑成功！');
			} else {
				$this->error('编辑失败！');
			}
		}
	}

	public function youqingStatus($id = NULL, $type = NULL, $mobile = 'Link')
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if (empty($id)) {
			$this->error('参数错误！');
		}
		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'repeal':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'delete':
			$data = array('status' => -1);
			break;

		case 'del':
			if (Db::name($mobile)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (Db::name($mobile)->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}
}

?>