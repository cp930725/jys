<?php
namespace app\admin\controller;

use think\Db;

class User extends Admin
{
	protected function _initialize()
	{
		parent::_initialize();	$allow_action=array("index","edit","edit2","status","admin","adminEdit","adminStatus","auth","authEdit","authStatus","authStart","authAccess","updateRules","authAccessUp","authUser","authUserAdd","authUserRemove","log","logEdit","logStatus","qianbao","qianbaoEdit","qianbaoStatus","bank","bankEdit","bankStatus","coin","coinEdit","coinFreeze","coinLog","goods","goodsEdit","goodsStatus","setpwd","amountlog","userExcel","loginadmin");
		if(!in_array($this->request->action(),$allow_action)){
			$this->error("页面不存在！");
		}
	}

	public function index($name=NULL, $field=NULL, $status=NULL, $idstate=NULL)
	{
		$where = array();
		if ($field && $name) {
			$where[$field] = $name;
		}
		if ($status) {
			$where['status'] = $status - 1;
		}
		/* 状态--条件 */
		if ($idstate) {
			$where['idstate'] = $idstate - 1;
		}
		
		// 统计
		$tongji['dsh'] = Db::name('User')->where('idstate', 1)->count();
		$this->assign('tongji', $tongji);
		
		$show = Db::name('User')->where($where)->count();

		
		if ($idstate == 2) {
			$list = Db::name('User')->where($where)->order('kyc_lv,id asc')->limit(0, 10)->select();
		} else {
			$list = Db::name('User')->where($where)->order('id desc')->limit(0, 10)->select();
		}
		
		foreach ($list as $k => $v) {
			$list[$k]['invit_1'] = Db::name('User')->where('id', $v['invit_1'])->value('username');
			$list[$k]['invit_2'] = Db::name('User')->where('id', $v['invit_2'])->value('username');
			$list[$k]['invit_3'] = Db::name('User')->where('id', $v['invit_3'])->value('username');
			$user_login_state=Db::name('user_log')->where('userid', $v['id'])->where('type', 'login')->order('id desc')->find();
			$list[$k]['state']=$user_login_state['state'];
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function edit($id = NULL)
	{
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = array('is_generalize'=>1);
                $this->assign('data', $this->data);
            } else {
				$this->data = Db::name('User')->where('id', trim($id))->find();
                $this->assign('data', $this->data);
            }

            $areas = Db::name('area')->select();
            $this->assign('areas',$areas);
			return $this->fetch();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			if ($_POST['password']) {
				$_POST['password'] = md5($_POST['password']);
			} else {
				unset($_POST['password']);
			}
			if ($_POST['paypassword']) {
				$_POST['paypassword'] = md5($_POST['paypassword']);
			} else {
				unset($_POST['paypassword']);
			}
			
/*			if ($_POST['mibao_question']) {
				$_POST['mibao_question'] =$_POST['mibao_question'];
			} else {
				unset($_POST['mibao_question']);
			}
			if ($_POST['mibao_answer']) {
				$_POST['mibao_answer'] =$_POST['mibao_answer'];
			} else {
				unset($_POST['mibao_answer']);
			}*/
			
			$_POST['mobiletime'] = strtotime($_POST['mobiletime']);

			$result = Db::name('User')->where('username', $_POST['username'])->find();

			if (empty($result)) {
				$_POST['addtime'] = time();
				$mo = db();
				Db::execute('set autocommit=0');
				Db::execute('lock tables tw_user write , tw_user_coin write ');
				$rs = array();
				$rs[] = Db::table('tw_user')->insert($_POST);
				$rs[] = Db::table('tw_user_coin')->insert(['userid' => $rs[0]]);
				if(check_arr($rs)){
					Db::execute('commit');
					Db::execute('unlock tables');
					$this->success('编辑成功！',url('User/index'));
				} else {
					$this->error('编辑失败！');
				}
			} else {
				if (Db::name('User')->update($_POST)) {
					$this->success('编辑成功！',url('User/index'));
				} else {
					$this->error('编辑失败！');
				}
			}
		}
	}
	
	public function edit2($id = NULL)
	{
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = array('is_generalize'=>1);
			} else {
				$this->data = Db::name('User')->where('id', trim($id))->find();
			}

            $areas = Db::name('area')->select();
			$this->assign('data', $this->data);
            $this->assign('areas',$areas);
			return $this->fetch();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$_POST['mobiletime'] = strtotime($_POST['mobiletime']);
			
			$_POST['endtime'] = time();

			if (Db::name('User')->update($_POST)) {
				$this->success('编辑成功！',url('User/index'));
			} else {
				$this->error('编辑失败！');
			}
		}
	}

	public function status($id = NULL, $type = NULL, $mobile = 'User')
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
		$where1['userid'] = array('in', $id);
		$mobile_coin = $mobile.'_coin';
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
				
		case 'idauth':
			$data = array('idstate' => 2, 'idcardinfo' => '', 'endtime' => time());
			break;

		case 'notidauth':
			$data = array('idstate' => 8, 'endtime' => time());
			break;

		case 'delete':
			$data = array('status' => -1);
			break;

		case 'del':
			if (Db::name($mobile)->where($where)->delete()&&M($mobile_coin)->where($where1)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}
			break;

		default:
			$this->error('操作失败！');
		}
		
		
		if ($type == 'idauth') {
			// 注册奖励模块
			$datas = Db::name('User')->where($where)->find();
			$configs = Db::name('config')->where('id', 1)->find();
			
			$ids = $datas['id'];
			$invit_1 = $datas['invit_1'];
			$invit_2 = $datas['invit_2'];
			$invit_3 = $datas['invit_3'];
			
			if($datas['idstate'] == 8){}
			else
			{
				if($datas['kyc_lv'] == 2 || $datas['idstate'] == 2){}
				else if($datas['kyc_lv'] == 0 || $datas['kyc_lv'] == 1)
				{
					//注册赠送币
					if ($configs['give_type'] == 1) {

						$mo = db();
						Db::execute('set autocommit=0');
						Db::execute('lock tables tw_user write, tw_user_coin write, tw_invit write, tw_finance_log write');

						$rs = array();

						// 数据未处理时的查询（原数据）
						$finance_num_user_coin = Db::table('tw_user_coin')->where('userid', $ids)->find();
						// 用户账户数据处理
						$coin_name = $configs['xnb_mr_song']; //赠送币种
						$song_num =  $configs['xnb_mr_song_num']; //赠送数量
						$rs[] = Db::table('tw_user_coin')->where($where1)->setInc($coin_name, $song_num); // 修改金额
						// 数据处理完的查询（新数据）
						$finance_mum_user_coin = Db::table('tw_user_coin')->where('userid', $ids)->find();

						// optype=1 充值类型 'cointype' => 1人民币类型 'plusminus' => 1增加类型
						$rs[] = Db::table('tw_finance_log')->insert(['username' => $datas['username'],
                            'adminname' => session('admin_username'),
                            'addtime' => time(),
                            'plusminus' => 1,
                            'amount' => $song_num,
                            'description' => '注册赠送',
                            'optype' => 27,
                            'cointype' => 3,
                            'old_amount' => $finance_num_user_coin[$coin_name],
                            'new_amount' => $finance_mum_user_coin[$coin_name],
                            'userid' => $datas['id'],
                            'adminid' => session('admin_id'),
                            'addip'=>$this->request->ip()]);


						// 赠送邀请人邀请奖励
						if($configs['song_num_1'] > 0 && $invit_1 > 0){
							$coin_num_1 = $configs['song_num_1'];
							$rs[] = Db::table('tw_invit')->insert([
							    'userid' => $invit_1,
                                'invit' => $ids,
                                'name' => '一代注册赠送',
                                'type' => '注册赠送'.strtoupper($coin_name),
                                'num' => 0,
                                'mum' => 0,
                                'fee' => $coin_num_1,
                                'addtime' => time(),
                                'status' => 0,
                                'coin'=>strtoupper($coin_name)]);
						}
						if($configs['song_num_2'] > 0 && $invit_2 > 0){
							$coin_num_2 = $configs['song_num_2'];
							$rs[] = Db::table('tw_invit')->insert([
							    'userid' => $invit_2,
                                'invit' => $ids,
                                'name' => '二代注册赠送',
                                'type' => '注册赠送'.strtoupper($coin_name),
                                'num' => 0,
                                'mum' => 0,
                                'fee' => $coin_num_2,
                                'addtime' => time(),
                                'status' => 0,
                                'coin'=>strtoupper($coin_name)]);
						}
						if($configs['song_num_3'] > 0 && $invit_3 > 0){
							$coin_num_3 = $configs['song_num_3'];
							$rs[] = Db::table('tw_invit')->insert([
							    'userid' => $invit_3,
                                'invit' => $ids,
                                'name' => '三代注册赠送',
                                'type' => '注册赠送'.strtoupper($coin_name),
                                'num' => 0, 'mum' => 0,
                                'fee' => $coin_num_3,
                                'addtime' => time(),
                                'status' => 0,
                                'coin'=>strtoupper($coin_name)]);
						}

						$rs[] = Db::table('tw_user')->where($where)->update($data);

						if (check_arr($rs)) {
							Db::execute('commit');
							Db::execute('unlock tables');
							return $this->success('操作成功！');
						} else {
							Db::execute('rollback');
							return $this->error('操作失败！');
						}
					}
				}
			}
		}

		if (Db::name($mobile)->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function admin($name = NULL, $field = NULL, $status = NULL)
	{
		$DbFields = Db::name('Admin')->getDbFields();

		if (!in_array('email', $DbFields)) {
			Db::execute('ALTER TABLE `tw_admin` ADD COLUMN `email` VARCHAR(200)  NOT NULL   COMMENT \'\' AFTER `id`;');
		}

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

		$count = Db::name('Admin')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = Db::name('Admin')->where($where)->order('id desc')->limit(0, 10)->select();
		foreach ($list as $k => $v) {
			$aga = 0;
			$aga = Db::name('AuthGroupAccess')->where('uid', $v['id'])->find();
			$ag = Db::name('AuthGroup')->where('id', $aga['group_id'])->find();
			if (!$aga) {
				$list[$k]['quanxianzu'] = '<a href="'.url('User/auth').'">未绑定权限</a>';
			} else {
				$list[$k]['quanxianzu'] = '<span title="'.$ag['description'].'">'.$ag['title'].'</span>';
			}
		}
		
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function adminEdit()
	{
		if (empty($_POST)) {
			if (empty($_GET['id'])) {
				$this->data = null;
			} else {
				$this->data = Db::name('Admin')->where('id', trim($_GET['id']))->find();
			}

			return $this->fetch();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$input = input('post.');

			if (!check($input['username'], 'username')) {
				//$this->error('用户名格式错误！');
			}
			if ($input['nickname'] && !check($input['nickname'], 'A')) {
				$this->error('昵称格式错误！');
			}
			if ($input['password'] && !check($input['password'], 'password')) {
				$this->error('登录密码格式错误！');
			}
			if ($input['mobile'] && !check($input['mobile'], 'mobile')) {
				$this->error('手机号码格式错误！');
			}
			if ($input['email'] && !check($input['email'], 'email')) {
				$this->error('邮箱格式错误！');
			}

			if ($input['password']) {
				$input['password'] = md5($input['password']);
			} else {
				unset($input['password']);
			}
			
			if ($_POST['id']) {
				$rs = Db::name('Admin')->update($input);
			} else {
				$_POST['addtime'] = time();
				$rs = Db::name('Admin')->insert($input);
			}

			if ($rs) {
				$this->success('编辑成功！');
			} else {
				$this->error('编辑失败！');
			}
		}
	}

	public function adminStatus($id = NULL, $type = NULL, $mobile = 'Admin')
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

	public function auth()
	{
		$this->meta_title = '权限管理';
		
		$list = $this->lists('AuthGroup', array('module' => 'admin'), 'id asc');
		$list = int_to_string($list);
		foreach ($list as $k => $v) {
			$count = Db::name('AuthGroupAccess')->where('group_id', $v['id'])->count();
			if ($count == 0) {
				$list[$k]['count'] = '';
			} else {
				$list[$k]['count'] = $count;
			}
		}
		
		$this->assign('_list', $list);
		$this->assign('_use_tip', true);
		return $this->fetch();
	}

	public function authEdit()
	{
		if (empty($_POST)) {
			if (empty($_GET['id'])) {
				$this->data = null;
				$this->assign('data', $this->data);
			} else {
				$this->data = Db::name('AuthGroup')->where('module', 'admin')->where('type', \common\model\AuthGroup::TYPE_ADMIN)->find((int) $_GET['id']);
				$this->assign('data', $this->data);
			}

			return $this->fetch();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			if (isset($_POST['rules'])) {
				sort($_POST['rules']);
				$_POST['rules'] = implode(',', array_unique($_POST['rules']));
			}

			$_POST['module'] = 'admin';
			$_POST['type'] = \common\model\AuthGroup::TYPE_ADMIN;
			$AuthGroup = Model('AuthGroup');
			$data = $AuthGroup->create();

			if ($data) {
				if (empty($data['id'])) {
					$r = $AuthGroup->insert();
				} else {
					$r = $AuthGroup->save();
				}

				if ($r === false) {
					$this->error('操作失败' . $AuthGroup->getError());
				} else {
					$this->success('操作成功!');
				}
			} else {
				$this->error('操作失败' . $AuthGroup->getError());
			}
		}
	}

	public function authStatus($id = NULL, $type = NULL, $mobile = 'AuthGroup')
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

	public function authStart()
	{
		if (Db::name('AuthRule')->where('status', 1)->delete()) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function authAccess()
	{
		$this->updateRules();
		$auth_group = Db::name('AuthGroup')->where(array(
			'status' => array('egt', '0'),
			'module' => 'admin',
			'type'   => \common\model\AuthGroup::TYPE_ADMIN
		))->value('id,id,title,rules');
		
		$node_list = $this->returnNodes();
		$map = array('module' => 'admin', 'type' => \common\model\AuthRule::RULE_MAIN, 'status' => 1);
		$main_rules = Db::name('AuthRule')->where($map)->value('name,id');
		$map = array('module' => 'admin', 'type' => \common\model\AuthRule::RULE_URL, 'status' => 1);
		$child_rules = Db::name('AuthRule')->where($map)->value('name,id');
		$this->assign('main_rules', $main_rules);
		$this->assign('auth_rules', $child_rules);
		$this->assign('node_list', $node_list);
		$this->assign('auth_group', $auth_group);
		$this->assign('this_group', $auth_group[(int) $_GET['group_id']]);
		$this->meta_title = '访问授权';
		return $this->fetch();
	}

	protected function updateRules()
	{
		$nodes = $this->returnNodes(false);
		$AuthRule = Db::name('AuthRule');
		$map = array(
			'module' => 'admin',
			'type'   => array('in', '1,2')
		);
		$rules = $AuthRule->where($map)->order('name')->select();
		$data = array();

		foreach ($nodes as $value) {
			$temp['name'] = $value['url'];
			$temp['title'] = $value['title'];
			$temp['module'] = 'admin';

			if (0 < $value['pid']) {
				$temp['type'] = \common\model\AuthRule::RULE_URL;
			} else {
				$temp['type'] = \common\model\AuthRule::RULE_MAIN;
			}

			$temp['status'] = 1;
			$data[strtolower($temp['name'] . $temp['module'] . $temp['type'])] = $temp;
		}

		$update = array();
		$ids = array();

		foreach ($rules as $index => $rule) {
			$key = strtolower($rule['name'] . $rule['module'] . $rule['type']);
			if (isset($data[$key])) {
				$data[$key]['id'] = $rule['id'];
				$update[] = $data[$key];
				unset($data[$key]);
				unset($rules[$index]);
				unset($rule['condition']);
				$diff[$rule['id']] = $rule;
			} else if ($rule['status'] == 1) {
				$ids[] = $rule['id'];
			}
		}

		if (count($update)) {
			foreach ($update as $k => $row) {
				if ($row != $diff[$row['id']]) {
					$AuthRule->where('id', $row['id'])->update($row);
				}
			}
		}

		if (count($ids)) {
			$AuthRule->where(array(
				'id' => array('IN', implode(',', $ids))
			))->update(array('status' => -1));
		}

		if (count($data)) {
			$AuthRule->addAll(array_values($data));
		}

		if ($AuthRule->getDbError()) {
			trace('[' . 'Admin\\Controller\\UserController::updateRules' . ']:' . $AuthRule->getDbError());
			return false;
		} else {
			return true;
		}
	}

	public function authAccessUp()
	{
		if (isset($_POST['rules'])) {
			sort($_POST['rules']);
			$_POST['rules'] = implode(',', array_unique($_POST['rules']));
		}

		$_POST['module'] = 'admin';
		$_POST['type'] = \common\model\AuthGroup::TYPE_ADMIN;
		$AuthGroup = Model('AuthGroup');
		$data = $AuthGroup->create();

		if ($data) {
			if (empty($data['id'])) {
				$r = $AuthGroup->insert();
			} else {
				$r = $AuthGroup->save();
			}
			if ($r === false) {
				$this->error('操作失败' . $AuthGroup->getError());
			} else {
				$this->success('操作成功!');
			}
		} else {
			$this->error('操作失败' . $AuthGroup->getError());
		}
	}

	public function authUser($group_id)
	{
		if (empty($group_id)) {
			$this->error('参数错误');
		}

		$auth_group = Db::name('AuthGroup')->where(array(
			'status' => array('egt', '0'),
			'module' => 'admin',
			'type'   => \common\model\AuthGroup::TYPE_ADMIN
		))->value('id,id,title,rules');
		$prefix = config('DB_PREFIX');
		$l_table = $prefix . \common\model\AuthGroup::MEMBER;
		$r_table = $prefix . \common\model\AuthGroup::AUTH_GROUP_ACCESS;
		$model = Db::table($l_table . ' m')->join($r_table . ' a ON m.id=a.uid');
		$_REQUEST = array();
		$list = $this->lists($model, array(
			'a.group_id' => $group_id,
			'm.status'   => array('egt', 0)
			), 'm.id asc', null, 'm.id,m.username,m.nickname,m.last_login_time,m.last_login_ip,m.status');
		int_to_string($list);
		$this->assign('_list', $list);
		$this->assign('auth_group', $auth_group);
		$this->assign('this_group', $auth_group[(int) $_GET['group_id']]);
		$this->meta_title = '成员授权';
		return $this->fetch();
	}

	public function authUserAdd()
	{
		$uid = input('uid');

		if (empty($uid)) {
			$this->error('请输入后台成员信息');
		}

		if (!check($uid, 'd')) {
			$user = Db::name('Admin')->where('username', $uid)->find();
			if (!$user) {
				$user = Db::name('Admin')->where('nickname', $uid)->find();
			}
			if (!$user) {
				$user = Db::name('Admin')->where('mobile', $uid)->find();
			}
			if (!$user) {
				$this->error('用户不存在(id 用户名 昵称 手机号均可)');
			}
			$uid = $user['id'];
		}

		$gid = input('group_id');

		if ($res = Db::name('AuthGroupAccess')->where('uid', $uid)->find()) {
			if ($res['group_id'] == $gid) {
				$this->error('已经存在,请勿重复添加');
			} else {
				$res = Db::name('AuthGroup')->where('id', $gid)->find();
				if (!$res) {
					$this->error('当前组不存在');
				}
				$this->error('已经存在[' . $res['title'] . ']组,不可重复添加');
			}
		}

		$AuthGroup = Model('AuthGroup');

		if (is_numeric($uid)) {
			if (is_administrator($uid)) {
				$this->error('该用户为超级管理员');
			}
			if (!Db::name('Admin')->where('id', $uid)->find()) {
				$this->error('管理员用户不存在');
			}
		}

		if ($gid && !$AuthGroup->checkGroupId($gid)) {
			$this->error($AuthGroup->error);
		}
		if ($AuthGroup->addToGroup($uid, $gid)) {
			$this->success('操作成功');
		} else {
			$this->error($AuthGroup->getError());
		}
	}

	public function authUserRemove()
	{
		$uid = input('uid');
		$gid = input('group_id');

		if ($uid == UID) {
			$this->error('不允许解除自身授权');
		}
		if (empty($uid) || empty($gid)) {
			$this->error('参数有误');
		}

		$AuthGroup = Model('AuthGroup');
		if (!$AuthGroup->find($gid)) {
			$this->error('用户组不存在');
		}

		if ($AuthGroup->removeFromGroup($uid, $gid)) {
			$this->success('操作成功');
		} else {
			$this->error('操作失败');
		}
	}

	public function log($name = NULL, $field = NULL, $status = NULL)
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

		$show = Db::name('UserLog')->where($where)->paginate(10);
		$list = Db::name('UserLog')->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = Db::name('User')->where('id', $v['userid'])->value('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function logEdit($id = NULL)
	{
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = null;
			} else {
				$this->data = Db::name('UserLog')->where('id', trim($id))->find();
			}

			return $this->fetch();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$_POST['addtime'] = strtotime($_POST['addtime']);

			if (Db::name('UserLog')->update($_POST)) {
				$this->success('编辑成功！');
			} else {
				$this->error('编辑失败！');
			}
		}
	}

	public function logStatus($id = NULL, $type = NULL, $mobile = 'UserLog')
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
			}
			else {
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

	public function qianbao($name = NULL, $field = NULL, $coinname = NULL, $status = NULL)
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
		if ($coinname) {
			$where['coinname'] = trim($coinname);
		}

		$show = Db::name('UserQianbao')->where($where)->paginate(10);

		$list = Db::name('UserQianbao')->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = Db::name('User')->where('id', $v['userid'])->value('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function qianbaoEdit($id = NULL)
	{
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = null;
			} else {
				$this->data = Db::name('UserQianbao')->where('id', trim($id))->find();
			}

			return $this->fetch();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$_POST['addtime'] = strtotime($_POST['addtime']);

			if (Db::name('UserQianbao')->update($_POST)) {
				$this->success('编辑成功！');
			} else {
				$this->error('编辑失败！');
			}
		}
	}

	public function qianbaoStatus($id = NULL, $type = NULL, $mobile = 'UserQianbao')
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

	public function bank($name = NULL, $field = NULL, $status = NULL)
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

		$show = Db::name('UserBank')->where($where)->paginate(10);

		$list = Db::name('UserBank')->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = Db::name('User')->where('id', $v['userid'])->value('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function bankEdit($id = NULL)
	{
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = null;
			} else {
				$this->data = Db::name('UserBank')->where('id', trim($id))->find();
			}

			return $this->fetch();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$_POST['addtime'] = strtotime($_POST['addtime']);
			if (Db::name('UserBank')->update($_POST)) {
				$this->success('编辑成功！');
			} else {
				$this->error('编辑失败！');
			}
		}
	}

	public function bankStatus($id = NULL, $type = NULL, $mobile = 'UserBank')
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

	public function coin($name = NULL, $field = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where('username', $name)->value('id');
			} else {
				$where[$field] = $name;
			}
		}

		$show = Db::name('UserCoin')->where($where)->paginate(10);

		$list = Db::name('UserCoin')->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = Db::name('User')->where('id', $v['userid'])->value('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function coinEdit($id = NULL)
	{
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = null;
			} else {
				$this->data = Db::name('UserCoin')->where('id', trim($id))->find();
			}

			return $this->fetch();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			try{

				$mo = db();
				Db::execute('set autocommit=0');
				Db::execute('lock tables tw_user_coin write ,tw_finance_log write ,tw_coin read ,tw_user read');

				// 获取该用户信息
				$user_coin_info = Db::table('tw_user_coin')->where('id', $_POST['id'])->find();
				$user_info = Db::table('tw_user')->where('id', $user_coin_info['userid'])->find();
				$coin_list = Db::table('tw_coin')->where('status', 1)->select();

				$rs = array();

				foreach ($coin_list as $k => $v) {
					// 判断那些币种账户发生变化
					if($user_coin_info[$v['name']] != $_POST[$v['name']]){
						// 账户数目减少---0减少1增加
						if($user_coin_info[$v['name']] > $_POST[$v['name']]){
							$plusminus = 0;
						} else {
							$plusminus = 1;
						}

						$amount = abs($user_coin_info[$v['name']] - $_POST[$v['name']]);

						$rs[] = Db::table('tw_finance_log')->insert([
						    'username' => $user_info['username'],
                            'adminname' => session('admin_username'),
                            'addtime' => time(),
                            'plusminus' => $plusminus,
                            'amount' => $amount,
                            'optype' => 3,
                            'cointype' => $v['id'],
                            'old_amount' => $user_coin_info[$v['name']],
                            'new_amount' => $_POST[$v['name']],
                            'userid' => $user_info['id'],
                            'adminid' => session('admin_id'),
                            'addip'=>$this->request->ip()
                        ]);
					}

				}

				// 更新用户账户数据
				$rs[] = Db::table('tw_user_coin')->update($_POST);
				if (check_arr($rs)) {
					Db::execute('commit');
					Db::execute('unlock tables');
				} else {
					throw new \Think\Exception('编辑失败！');
				}
				$this->success('编辑成功！',url('User/coin'));

			} catch(\Think\Exception $e) {
				Db::execute('rollback');
				Db::execute('unlock tables');
				$this->error('编辑失败！');
			}
			// if (Db::name('UserCoin')->update($_POST)) {
			// 	$this->success('编辑成功！');
			// }
			// else {
			// 	$this->error('编辑失败！');
			// }
		}
	}
	
    public function coinFreeze($id = NULL)
    {
        if (empty($_POST)) {
            if (empty($id)) {
                $this->data = null;
            } else {
                $this->data = Db::name('UserCoin')->where('id', trim($id))->find();
            }
            return $this->fetch();
        } else {
            if (APP_DEMO) {
                $this->error('测试站暂时不能修改！');
            }
            try{
                $mo = db();
                Db::execute('set autocommit=0');
                Db::execute('lock tables tw_user_coin write ,tw_finance_log write ,tw_coin read ,tw_user read');
                // 获取该用户信息
                $user_coin_info = Db::table('tw_user_coin')->where('id', $_POST['id'])->find();
                $user_info = Db::table('tw_user')->where('id', $user_coin_info['userid'])->find();
                $coin_list = Db::table('tw_coin')->where('status', 1)->select();
                $rs = array();
                $data = array('id'=>$_POST['id']);
                foreach ($coin_list as $k => $v) {
                    // 判断那些币种账户发生变化
                    if($_POST[$v['name']]!=0){
						// 账户数目减少---0减少1增加
                        if($user_coin_info[$v['name']] > $_POST[$v['name']]){
                            $plusminus = 0;
                        } else {
                            $plusminus = 1;
                        }
                        $data[$v['name']] = $user_coin_info[$v['name']]-$_POST[$v['name']];
                        $data[$v['name'].'d'] = $user_coin_info[$v['name'].'d']+$_POST[$v['name']];
                        $amount = abs($_POST[$v['name']]);
                        $rs[] = Db::table('tw_finance_log')->insert([
                            'username' => $user_info['username'],
                            'adminname' => session('admin_username'),
                            'addtime' => time(),
                            'plusminus' => $plusminus,
                            'description'=>'管理手动'.($_POST[$v['name']]>0?'冻结':'解冻'),
                            'amount' => $amount,
                            'optype' => 3,
                            'cointype' => $v['id'],
                            'old_amount' => $user_coin_info[$v['name']],
                            'new_amount' => $data[$v['name']],
                            'userid' => $user_info['id'],
                            'adminid' => session('admin_id'),
                            'addip'=>$this->request->ip()]);
                    }
                }

                // 更新用户账户数据
                $rs[] = Db::table('tw_user_coin')->update($data);
                if (check_arr($rs)) {
                    Db::execute('commit');
                    Db::execute('unlock tables');
                } else {
                    throw new \Think\Exception('编辑失败！');
                }
                $this->success('编辑成功！');
            }catch(\Think\Exception $e){
                Db::execute('rollback');
                Db::execute('unlock tables');
                $this->error('编辑失败！');
            }
        }
    }

	public function coinLog($userid = NULL, $coinname = NULL)
	{
		$data['userid'] = $userid;
		$data['username'] = Db::name('User')->where('id', $userid)->value('username');
		$data['coinname'] = $coinname;
		$data['zhengcheng'] = Db::name('UserCoin')->where('userid', $userid)->value($coinname);
		$data['dongjie'] = Db::name('UserCoin')->where('userid', $userid)->value($coinname . 'd');
		$data['zongji'] = $data['zhengcheng'] + $data['dongjie'];
		$data['chongzhicny'] = Db::name('Mycz')->where(array(
			'userid' => $userid,
			'status' => array('neq', '0')
		))->sum('num');
		
		$data['tixiancny'] = Db::name('Mytx')->where('userid', $userid)->where('status', 1)->sum('num');
		$data['tixiancnyd'] = Db::name('Mytx')->where('userid', $userid)->where('status', 0)->sum('num');

		if ($coinname != 'cny') {
			$data['chongzhi'] = Db::name('Myzr')->where(array(
				'userid' => $userid,
				'status' => array('neq', '0')
			))->sum('num');
			$data['tixian'] = Db::name('Myzc')->where('userid', $userid)->where('status', 1)->sum('num');
		}

		$this->assign('data', $data);
		return $this->fetch();
	}

	public function goods($name = NULL, $field = NULL, $status = NULL)
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

		$show = Db::name('UserGoods')->where($where)->paginate(10);

		$list = Db::name('UserGoods')->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = Db::name('User')->where('id', $v['userid'])->value('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function goodsEdit($id = NULL)
	{
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = null;
			} else {
				$this->data = Db::name('UserGoods')->where('id', trim($id))->find();
			}

			return $this->fetch();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$_POST['addtime'] = strtotime($_POST['addtime']);

			if (Db::name('UserGoods')->update($_POST)) {
				$this->success('编辑成功！');
			} else {
				$this->error('编辑失败！');
			}
		}
	}

	public function goodsStatus($id = NULL, $type = NULL, $mobile = 'UserGoods')
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

	public function setpwd()
	{
		if ($this->request->ispost()) {
			defined('APP_DEMO') || define('APP_DEMO', 0);

			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$oldpassword = $_POST['oldpassword'];
			$newpassword = $_POST['newpassword'];
			$repassword = $_POST['repassword'];

			if (!check($oldpassword, 'password')) {
				$this->error('旧密码格式错误！');
			}
			if (md5($oldpassword) != session('admin_password')) {
				$this->error('旧密码错误！');
			}
			if (!check($newpassword, 'password')) {
				$this->error('新密码格式错误！');
			}
			if ($newpassword != $repassword) {
				$this->error('确认密码错误！');
			}
			if (Model('Admin')->where('id', session('admin_id'))->update(array('password' => md5($newpassword)))) {
				$this->success('登陆密码修改成功！', url('Login/loginout'));
			} else {
				$this->error('登陆密码修改失败！');
			}
		}

		return $this->fetch();
	}

	public function userExcel()
	{
		if ($this->request->ispost()) {
			$id = implode(',', $_POST['id']);
		} else {
			$id = $_GET['id'];
		}

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$where['id'] = array('in', $id);
		// 处理搜索的数据=================================================

		$list = Db::name('User')->where($where)->select();
		foreach ($list as $k => $v) {
			$list[$k]['addtime'] = addtime($v['addtime']);

			if ($list[$k]['status'] == 1) {
				$list[$k]['status'] = '正常';
			} else {
				$list[$k]['status'] = '禁止';
			}
		}

		$zd = Db::name('User')->getDbFields();
		array_splice($zd, 3, 7);
		array_splice($zd, 5, 5);
		array_splice($zd, 6, 1);
		array_splice($zd, 7, 7);
		$xlsName = 'cade';
		$xls = array();

		foreach ($zd as $k => $v) {
			$xls[$k][0] = $v;
			$xls[$k][1] = $v;
		}

		$xls[0][2] = 'ID';
		$xls[1][2] = '用户名';
		$xls[2][2] = '手机号';
		$xls[3][2] = '真实姓名';
		$xls[4][2] = '身份证号';
		$xls[5][2] = '注册时间';
		$xls[6][2] = '状态';

		$this->cz_exportExcel($xlsName, $xls, $list);
	}
	
	public function loginadmin()
	{
    	header("Content-Type:text/html; charset=utf-8");
    	if (IS_GET) {
    		$id = trim(input('get.id'));
    		$pwd = trim(input('get.pass'));
    		// $pwd2=trim(input('get.secpw'));
    		$user = Db::name('User')->where('id', $id)->find();
			if (!$user || $user['password']!=$pwd) {
				$this->error('账号或密码错误,或被禁用！如确定账号密码无误,请联系您的领导人或管理员处理.');
			} else {
				session('userId', $user['id']);
				session('userName', $user['username']);
				session('userNoid',$user['noid']);
				$this->redirect('/');
			}
		}
    }
	
	// 资金变更日志
	public function amountlog($position = 'all', $plusminus = 'all', $name = NULL, $field = NULL, $cointype = NULL, $optype = NULL, $starttime = NULL, $endtime = NULL)
	{
		$where = array();
		if ($field && $name) {
			$where[$field] = $name;
		}
		if ($cointype) {
			$where['cointype'] = $cointype;
		}
		if ($optype) {
			$where['optype'] = $optype - 1;
		}
		if ($plusminus != 'all') {
			if ($plusminus == 'jia') {
				$where['plusminus'] = '1';
			} else if ($plusminus == 'jian') {
				$where['plusminus'] = '0';
			}
		}
		if ($position != 'all') {
			if ($position == 'hou') {
				$where['position'] = '0';
			} else if ($position == 'qian') {
				$where['position'] = '1';
			}
		}

		// 时间--条件
		if (!empty($starttime) && empty($endtime)) {
			$starttime = strtotime($starttime);
			$where['addtime'] = array('EGT',$starttime);
		} else if (empty($starttime) && !empty($endtime)) {
			$endtime = strtotime($endtime);
			$where['addtime'] = array('ELT',$endtime);
		} else if (!empty($starttime) && !empty($endtime)) {
			$starttime = strtotime($starttime);
			$endtime = strtotime($endtime);
			$where['addtime'] =  array(array('EGT',$starttime),array('ELT',$endtime));
		}
		// else{
		// 	// 无时间查询，显示申请时间类型十天以内数据
		// 	$now_time = time() - 10*24*60*60;
		// 	$where['addtime'] =  array('EGT',$now_time);
		// }

		$show = Db::name('FinanceLog')->where($where)->paginate(10);

		$list = Db::name('FinanceLog')->where($where)->order('id desc')->limit(0, 10)->select();
		// dump($where);
		foreach ($list as $k => $v) {
			$coin_info = Db::name('Coin')->where('id', $v['cointype'])->find();
			$list[$k]['cointype'] =strtoupper($coin_info['name']);
			$list[$k]['optype'] = opstype($v['optype'],2);
			$list[$k]['old_amount'] = $v['old_amount']*1;
			$list[$k]['amount'] = $v['amount']*1;
			$list[$k]['new_amount'] = $v['new_amount']*1;
			if ($v['plusminus']) {
				$list[$k]['plusminus'] = '增加';
			} else {
				$list[$k]['plusminus'] = '减少';
			}
			if ($v['position']) {
				$list[$k]['position'] = '前台';
			} else {
				$list[$k]['position'] = '后台';
			}
		}

		$opstype = opstype('',88);
		$coinlists=Db::name('coin')->where('name', 'neq','cny')->where('status', 1)->select();
		$this->assign('coins', $coinlists);
		$this->assign('opstype', $opstype);
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}
}
?>