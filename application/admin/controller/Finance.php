<?php
namespace app\admin\controller;

use think\Db;

class Finance extends Admin
{
	protected function _initialize(){
		parent::_initialize();
		$allow_action=array("index","mycz","myczExcel","myczConfig","myczStatus","myczQueren","myczType","myczTypeEdit","myczTypeImage","myczTypeStatus","mytx","mytxStatus","mytxChuli","mytxChexiao","mytxQueren","mytxExcel","mytxConfig","myzr","myzc","myzcQueren");
		if(!in_array($this->request->action(),$allow_action)){
			$this->error("页面不存在！");
		}
	}

	public function index($field = NULL, $name = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where('username', $name)->value('id');
			}
			else {
				$where[$field] = $name;
			}
		}

		$show = Db::name('Finance')->where($where)->paginate(10);

		$list = Db::name('Finance')->where($where)->order('id desc')->limit(0, 10)->select();
		$name_list = ['mycz' => '人民币充值', 'mycz_c2c' => 'C2C充值', 'mytx' => '人民币提现', 'mytx_c2c' => 'C2C提现', 'trade' => '委托交易', 'tradelog' => '成功交易', 'issue' => '用户认购'];
		$nameid_list = ['mycz' => url('Mycz/index'), 'mytx' => url('Mytx/index'), 'mytx_c2c'=> '', 'mycz_c2c' => '', 'trade' => url('Trade/index'), 'tradelog' => url('Tradelog/index'), 'issue' => url('Issue/index')];

		foreach ($list as $k => $v) {
			$list[$k]['username'] = Db::name('User')->where('id', $v['userid'])->value('username');
			$list[$k]['num_a'] = Num($v['num_a']);
			$list[$k]['num_b'] = Num($v['num_b']);
			$list[$k]['num'] = Num($v['num']);
			$list[$k]['fee'] = Num($v['fee']);
			$list[$k]['type'] = ($v['fee'] == 1 ? '收入' : '支出');
			$list[$k]['name'] = (!empty($name_list[$v['name']]) ? $name_list[$v['name']] : $v['name']);
			$list[$k]['nameid'] = (!empty($name_list[$v['name']]) ? $nameid_list[$v['name']] . '?id=' . $v['nameid'] : '');
			$list[$k]['mum_a'] = Num($v['mum_a']);
			$list[$k]['mum_b'] = Num($v['mum_b']);
			$list[$k]['mum'] = Num($v['mum']);
			$list[$k]['addtime'] = addtime($v['addtime']);
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}
	
	// 旧人民币充值 - 弃用
	public function mycz($field = NULL, $name = NULL, $status = NULL, $mycz_type = NULL, $time_type = NULL, $starttime = NULL, $endtime = NULL)
	{
		// 获取搜索提交的数据，方便导出表使用
		$info = array('field'=>$field,'name'=>$name,'status'=>$status);
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where('username', $name)->value('id');
			}
			else {
				$where[$field] = $name;
			}
		}

		// 状态--条件
		if ($status) {
			$where['status'] = $status - 1;
		}

		// 充值方式--条件
		if ($mycz_type) {
			$where['type'] = $mycz_type;
		}

		// 时间--条件
		if (!empty($starttime) && empty($endtime)) {
			$starttime = strtotime($starttime);
			$where[$time_type] = array('EGT',$starttime);
		} else if (empty($starttime) && !empty($endtime)) {
			$endtime = strtotime($endtime);
			$where[$time_type] = array('ELT',$endtime);
		} else if (!empty($starttime) && !empty($endtime)) {
			$starttime = strtotime($starttime);
			$endtime = strtotime($endtime);
			$where[$time_type] =  array(array('EGT',$starttime),array('ELT',$endtime));
		}

		$show = Db::name('Mycz')->where($where)->paginate(10);

		$list = Db::name('Mycz')->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			if(!empty($v['bank'])){
				$list[$k]['alipay_account'] = $v['bank'].'|'.$v['alipay_account'];
			}
			$list[$k]['username'] = Db::name('User')->where('id', $v['userid'])->value('username');
			$list[$k]['type'] = Db::name('MyczType')->where('name', $v['type'])->value('title');
		}

		$this->assign('info', $info);
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	// 导出充值明细表
	public function myczExcel()
	{
		if ($this->request->ispost()) {
			$id = implode(',', $_POST['id']);
		}
		else {
			$id = $_GET['id'];
		}

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$where['id'] = array('in', $id);
		// 处理搜索的数据=================================================

		$list = Db::name('Mycz')->where($where)->select();
		foreach ($list as $k => $v) {
			$list[$k]['userid'] = Db::name('User')->where('id', $v['userid'])->value('username');
			$list[$k]['addtime'] = addtime($v['addtime']);
			$list[$k]['endtime'] = addtime($v['endtime']);

			if ($list[$k]['status'] == 0) {
				$list[$k]['status'] = '未付款';
			} else if ($list[$k]['status'] == 2) {
				$list[$k]['status'] = '人工到账';
			} else if ($list[$k]['status'] == 3) {
				$list[$k]['status'] = '处理中';
			} else if ($list[$k]['status'] == 1) {
				$list[$k]['status'] = '充值成功';
			} else {
				$list[$k]['status'] = '错误';
			}
		}

		$zd = Db::name('Mycz')->getDbFields();
		array_splice($zd, 6, 2);
		array_splice($zd, 11, 1);
		$xlsName = 'cade';
		$xls = array();

		foreach ($zd as $k => $v) {
			$xls[$k][0] = $v;
			$xls[$k][1] = $v;
		}

		$xls[0][2] = '编号';
		$xls[1][2] = '用户名';
		$xls[2][2] = '充值金额';
		$xls[3][2] = '到账金额';
		$xls[4][2] = '充值方式';
		$xls[5][2] = '充值订单号';
		$xls[6][2] = '充值添加时间';
		$xls[7][2] = '充值结束时间';
		$xls[8][2] = '充值状态';
		$xls[9][2] = '真实姓名';
		$xls[10][2] = '银行账号';
		$xls[11][2] = '手续费';
		$xls[12][2] = '银行';

		$this->cz_exportExcel($xlsName, $xls, $list);
	}

	// 人民币充值配置
	public function myczConfig()
	{
		if (empty($_POST)) {
			return $this->fetch();
		} else if (Db::name('Config')->where('id', 1)->update($_POST)) {
			$this->success('修改成功！');
		} else {
			$this->error('修改失败');
		}
	}

	public function myczStatus($id = NULL, $type = NULL, $mobile = 'Mycz')
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
				$this->error('操作失败1！');
		}

		if (Db::name($mobile)->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败2！');
		}
	}

	// 旧人民币充值 - 弃用
	public function myczQueren()
	{
		$id = $_GET['id'];
		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$mycz = Db::name('Mycz')->where('id', $id)->find();
		if (($mycz['status'] != 0) && ($mycz['status'] != 3)) {
			$this->error('已经处理，禁止再次操作！');
		}

		$fp = fopen("lockcz.txt", "w+");
		if(flock($fp,LOCK_EX | LOCK_NB))
		{
			$mo = db();
			Db::execute('set autocommit=0');
			Db::execute('lock tables tw_user_coin write,tw_mycz write,tw_finance write,tw_finance_log write,tw_user read');

			$rs = array();

			$finance = Db::table('tw_finance')->where('userid', $mycz['userid'])->order('id desc')->find();

			// 数据未处理时的查询（原数据）
			$finance_num_user_coin = Db::table('tw_user_coin')->where('userid', $mycz['userid'])->find();
			// 用户账户数据处理
			$rs[] = Db::table('tw_user_coin')->where('userid', $mycz['userid'])->setInc('cny', $mycz['mum']);

			$rs[] = Db::table('tw_mycz')->where('id', $mycz['id'])->update(array('status' => 2, 'mum' => $mycz['mum'], 'endtime' => time()));

			// 数据处理完的查询（新数据）
			$finance_mum_user_coin = Db::table('tw_user_coin')->where('userid', $mycz['userid'])->find();
			$finance_hash = md5($mycz['userid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mycz['mum'] . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE);
			$finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

			if ($finance['mum'] < $finance_num) {
				$finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
			} else {
				$finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
			}

			$rs[] = Db::table('tw_finance')->insert([
			    'userid' => $mycz['userid'],
                'coinname' => 'cny',
                'num_a' => $finance_num_user_coin['cny'],
                'num_b' => $finance_num_user_coin['cnyd'],
                'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'],
                'fee' => $mycz['num'],
                'type' => 1,
                'name' => 'mycz',
                'nameid' => $mycz['id'],
                'remark' => '人民币充值-人工到账',
                'mum_a' => $finance_mum_user_coin['cny'],
                'mum_b' => $finance_mum_user_coin['cnyd'],
                'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'],
                'move' => $finance_hash,
                'addtime' => time(),
                'status' => $finance_status]);

			// 处理资金变更日志-----------------S
			// 获取用户信息
			$user_info = Db::table('tw_user')->where('id', $mycz['userid'])->find();
			// optype=1 充值类型 'cointype' => 1人民币类型 'plusminus' => 1增加类型
			$rs[] = Db::table('tw_finance_log')->insert([
			    'username' => $user_info['username'],
                'adminname' => session('admin_username'),
                'addtime' => time(),
                'plusminus' => 1,
                'amount' => $mycz['mum'],
                'optype' => 1,
                'cointype' => 1,
                'old_amount' => $finance_num_user_coin['cny'],
                'new_amount' => $finance_mum_user_coin['cny'],
                'userid' => $user_info['id'],
                'adminid' => session('admin_id'),
                'addip'=>$this->request->ip()]);
			// 处理资金变更日志-----------------E

			if (check_arr($rs)) {
				Db::execute('commit');
				Db::execute('unlock tables');
				$message="操作成功";
				$res=1;
			} else {
				Db::execute('rollback');
				$message="操作失败";
				$res=0;
			}
			flock($fp,LOCK_UN);
		} else {
			$message="请不要重复提交";
			$res=0;
		}
		fclose($fp);
		if($res==1){
			$this->success($message);
		} else {
			$this->error($message);
		}
	}

	public function myczType()
	{
		$where = array();
		$show = Db::name('MyczType')->where($where)->paginate(10);

		$list = Db::name('MyczType')->where($where)->order('id desc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function myczTypeEdit($id = NULL)
	{
		if (empty($_POST)) {
			if ($id) {
				$this->data = Db::name('MyczType')->where('id', trim($id))->find();
			} else {
				$this->data = null;
			}

			return $this->fetch();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			if ($_POST['id']) {
				$rs = Db::name('MyczType')->update($_POST);
			} else {
				$rs = Db::name('MyczType')->insert($_POST);
			}

			if ($rs) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}
		}
	}

	public function myczTypeImage()
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

	public function myczTypeStatus($id = NULL, $type = NULL, $mobile = 'MyczType')
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
				$this->error('操作失败1！');
		}

		if (Db::name($mobile)->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败2！');
		}
	}
	
	// 旧人民币提现 - 弃用
	public function mytx($field = NULL, $name = NULL, $status = NULL, $time_type = NULL, $starttime = NULL, $endtime = NULL)
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

		// 时间--条件
		if (!empty($starttime) && empty($endtime)) {
			$starttime = strtotime($starttime);
			$where[$time_type] = array('EGT',$starttime);
		} else if (empty($starttime) && !empty($endtime)) {
			$endtime = strtotime($endtime);
			$where[$time_type] = array('ELT',$endtime);
		} else if (!empty($starttime) && !empty($endtime)) {
			$starttime = strtotime($starttime);
			$endtime = strtotime($endtime);
			$where[$time_type] =  array(array('EGT',$starttime),array('ELT',$endtime));
		}

		$show = Db::name('Mytx')->where($where)->paginate(10);

		
		$list = Db::name('Mytx')->where($where)->order('id desc')->limit(0, 10)->select();
		foreach ($list as $k => $v) {
			$list[$k]['username'] = Db::name('User')->where('id', $v['userid'])->value('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function mytxStatus($id = NULL, $type = NULL, $mobile = 'Mytx')
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
				$this->error('操作失败1！');
		}

		if (Db::name($mobile)->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败2！');
		}
	}

	public function mytxChuli()
	{
		$id = $_GET['id'];
		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		if (Db::name('Mytx')->where('id', $id)->update(['status' => 3,'endtime' => time()])) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}
	
	// 旧人民币提现撤销 - 弃用
	public function mytxChexiao()
	{
		$id = $_GET['id'];
		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$mytx = Db::name('Mytx')->where('id', trim($_GET['id']))->find();

		$mo = db();
		Db::execute('set autocommit=0');
		// Db::execute('lock tables tw_user_coin write,tw_mytx write,tw_finance write');
		Db::execute('lock tables tw_user_coin write,tw_mytx write,tw_finance write,tw_finance_log write,tw_user read');

		$rs = array();
		$finance = Db::table('tw_finance')->where('userid', $mytx['userid'])->order('id desc')->find();
		$finance_num_user_coin = Db::table('tw_user_coin')->where('userid', $mytx['userid'])->find();
		$rs[] = Db::table('tw_user_coin')->where('userid', $mytx['userid'])->setInc('cny', $mytx['num']);
		$rs[] = Db::table('tw_mytx')->where('id', $mytx['id'])->setField('status', 2);
		$finance_mum_user_coin = Db::table('tw_user_coin')->where('userid', $mytx['userid'])->find();
		$finance_hash = md5($mytx['userid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mytx['num'] . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'tp3.net.cn');
		$finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

		if ($finance['mum'] < $finance_num) {
			$finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
		} else {
			$finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
		}

		$rs[] = Db::table('tw_finance')->insert(['userid' => $mytx['userid'],
            'coinname' => 'cny',
            'num_a' => $finance_num_user_coin['cny'],
            'num_b' => $finance_num_user_coin['cnyd'],
            'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'],
            'fee' => $mytx['num'],
            'type' => 1,
            'name' => 'mytx',
            'nameid' => $mytx['id'],
            'remark' => '人民币提现-撤销提现',
            'mum_a' => $finance_mum_user_coin['cny'],
            'mum_b' => $finance_mum_user_coin['cnyd'],
            'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'],
            'move' => $finance_hash,
            'addtime' => time(),
            'status' => $finance_status]);

		// 处理资金变更日志-----------------S
		
		// 获取用户信息
		$user_info = Db::table('tw_user')->where('id', $mytx['userid'])->find();
		// optype=4 提现撤销-动作类型 'cointype' => 1人民币-资金类型 'plusminus' => 1增加类型
		$rs[] = Db::table('tw_finance_log')->insert([
		    'username' => $user_info['username'],
            'adminname' => session('admin_username'),
            'addtime' => time(),
            'plusminus' => 1,
            'amount' => $mytx['num'],
            'optype' => 24,
            'cointype' => 1,
            'old_amount' => $finance_num_user_coin['cny'],
            'new_amount' => $finance_mum_user_coin['cny'],
            'userid' => $user_info['id'],
            'adminid' => session('admin_id'),
            'addip'=>$this->request->ip()]);
		
		// 处理资金变更日志-----------------E

		if (check_arr($rs)) {
			Db::execute('commit');
			Db::execute('unlock tables');
			$this->success('操作成功！');
		} else {
			Db::execute('rollback');
			$this->error('操作失败！');
		}
	}

	public function mytxQueren()
	{
		$id = $_GET['id'];
		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		if (Db::name('Mytx')->where('id', $id)->update(['status' => 1])) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	// 导出提现明细表
	public function mytxExcel()
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
		$list = Db::name('Mytx')->where($where)->field('id,userid,num,fee,mum,truename,name,bank,bankprov,bankcity,bankaddr,bankcard,addtime,endtime,status')->select();

		foreach ($list as $k => $v) {
			$list[$k]['userid'] = Db::name('User')->where('id', $v['userid'])->value('username');
			$list[$k]['addtime'] = addtime($v['addtime']);

			if ($list[$k]['status'] == 0) {
				$list[$k]['status'] = '未处理';
			} else if ($list[$k]['status'] == 1) {
				$list[$k]['status'] = '已划款';
			} else if ($list[$k]['status'] == 2) {
				$list[$k]['status'] = '已撤销';
			} else if ($list[$k]['status'] == 3) {
				$list[$k]['status'] = '正在处理';
			} else {
				$list[$k]['status'] = '错误';
			}

			$list[$k]['bankcard'] = ' '.$v['bankcard'].' ';
		}

		$zd = Db::name('Mytx')->getDbFields();
		array_splice($zd, 12, 1);
		$xlsName = 'cade';
		$xls = array();
		foreach ($zd as $k => $v) {
			$xls[$k][0] = $v;
			$xls[$k][1] = $v;
		}

		$xls[0][2] = '编号';
		$xls[1][2] = '用户名';
		$xls[2][2] = '提现金额';
		$xls[3][2] = '手续费';
		$xls[4][2] = '到账金额';
		$xls[5][2] = '姓名';
		$xls[6][2] = '银行备注';
		$xls[7][2] = '银行名称';
		$xls[8][2] = '开户省份';
		$xls[9][2] = '开户城市';
		$xls[10][2] = '开户地址';
		$xls[11][2] = '银行卡号';
		$xls[12][2] = '提现时间';
		$xls[13][2] = '导出时间';
		$xls[14][2] = '提现状态';
		$this->exportExcel($xlsName, $xls, $list);
	}

	public function mytxConfig()
	{
		if (empty($_POST)) {
			return $this->fetch();
		} else if (Db::name('Config')->where('id', 1)->update($_POST)) {
			$this->success('修改成功！');
		} else {
			$this->error('修改失败');
		}
	}
	
	// 虚拟币转入
	public function myzr($field = NULL, $name = NULL, $coinname = NULL, $time_type = 'addtime', $starttime = NULL, $endtime = NULL, $num_start = NULL, $num_stop = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where('username', $name)->value('id');
			} else {
				$where[$field] = $name;
			}
		}

		if ($coinname) {
			$where['coinname'] = $coinname;
		}

		// 转入数量--条件
		if (is_numeric($num_start) && !is_numeric($num_stop)) {
			$where['num'] = array('EGT',$num_start);
		} else if (!is_numeric($num_start) && is_numeric($num_stop)) {
			$where['num'] = array('ELT',$num_stop);
		} else if (is_numeric($num_start) && is_numeric($num_stop)) {
			$where['num'] = array(array('EGT',$num_start),array('ELT',$num_stop));
		}

		// 时间--条件
		if (!empty($starttime) && empty($endtime)) {
			$starttime = strtotime($starttime);
			$where[$time_type] = array('EGT',$starttime);
		} else if (empty($starttime) && !empty($endtime)) {
			$endtime = strtotime($endtime);
			$where[$time_type] = array('ELT',$endtime);
		} else if (!empty($starttime) && !empty($endtime)) {
			$starttime = strtotime($starttime);
			$endtime = strtotime($endtime);
			$where[$time_type] =  array(array('EGT',$starttime),array('ELT',$endtime));
		}

		$show = Db::name('Myzr')->where($where)->paginate(10);

		
		$list = Db::name('Myzr')->where($where)->order('id desc')->limit(0, 10)->select();
		foreach ($list as $k => $v) {
			$list[$k]['usernamea'] = Db::name('User')->where('id', $v['userid'])->value('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}
	
	// 虚拟币转出
	public function myzc($field = NULL, $name = NULL, $coinname = NULL, $time_type = 'addtime', $starttime = NULL, $endtime = NULL, $num_start = NULL, $num_stop = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where('username', $name)->value('id');
			} else {
				$where[$field] = $name;
			}
		}

		if ($coinname) {
			$where['coinname'] = $coinname;
		}

		// 转入数量--条件
		if(is_numeric($num_start) && !is_numeric($num_stop)){
			$where['num'] = array('EGT',$num_start);
		} else if (!is_numeric($num_start) && is_numeric($num_stop)) {
			$where['num'] = array('ELT',$num_stop);
		} else if (is_numeric($num_start) && is_numeric($num_stop)) {
			$where['num'] = array(array('EGT',$num_start),array('ELT',$num_stop));
		}

		// 时间--条件
		if (!empty($starttime) && empty($endtime)) {
			$starttime = strtotime($starttime);
			$where[$time_type] = array('EGT',$starttime);
		} else if (empty($starttime) && !empty($endtime)) {
			$endtime = strtotime($endtime);
			$where[$time_type] = array('ELT',$endtime);
		} else if (!empty($starttime) && !empty($endtime)) {
			$starttime = strtotime($starttime);
			$endtime = strtotime($endtime);
			$where[$time_type] =  array(array('EGT',$starttime),array('ELT',$endtime));
		} else {
			// 无时间查询，显示申请时间类型十天以内数据
			$now_time = time() - 1000*24*60*60;
			$where['addtime'] =  array('EGT',$now_time);
		}

		$show = Db::name('Myzc')->where($where)->paginate(10);

		$list = Db::name('Myzc')->where($where)->order('id desc')->limit(0, 10)->select();
		foreach ($list as $k => $v) {
			$list[$k]['usernamea'] = Db::name('User')->where('id', $v['userid'])->value('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function myzcQueren($id = NULL)
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		$myzc = Db::name('Myzc')->where('id', trim($id))->find();
		if (!$myzc) {
			$this->error('转出错误！');
		}
		if ($myzc['status']) {
			$this->error('已经处理过！');
		}

		$username = Db::name('User')->where('id', $myzc['userid'])->value('username');
		$coin = $myzc['coinname'];
		$dj_username = config('coin')[$coin]['dj_yh'];
		$dj_password = config('coin')[$coin]['dj_mm'];
		$dj_address = config('coin')[$coin]['dj_zj'];
		$dj_port = config('coin')[$coin]['dj_dk'];
		$zcdz = config('coin')[$coin]['zc_user'];
		
		$coin_config = Db::name('Coin')->where('name', $coin)->find();
		
		if($coin=='eth' || $coin=='etc' || $coin_config['token_type'] == 1){ //ETH对接,FFF
			$CoinClient = EthCommon($dj_address, $dj_port);
		           if (!$CoinClient) {
				$this->error(lang('钱包链接失败！'));
			}
		} else {
			$CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
			$json = $CoinClient->getinfo();

			if (!isset($json['version']) || !$json['version']) {
				$this->error('钱包链接失败！');
			}
		}

		$Coin = Db::name('Coin')->where('name', $myzc['coinname'])->find();
		$fee_user = Db::name('UserCoin')->where($coin . 'b', $Coin['zc_user'])->find();
		$user_coin = Db::name('UserCoin')->where('userid', $myzc['userid'])->find();
		$zhannei = Db::name('UserCoin')->where($coin . 'b', $myzc['username'])->find();
		$mo = db();
		Db::startTrans();
		$rs = array();

		if ($zhannei) {
			$rs[] = Db::table('tw_myzr')->insert([
			    'userid' => $zhannei['userid'],
                'username' => $myzc['username'],
                'coinname' => $coin,
                'txid' => md5($myzc['username'] . $user_coin[$coin . 'b'] . time()),
                'num' => $myzc['num'],
                'fee' => $myzc['fee'],
                'mum' => $myzc['mum'],
                'addtime' => time(),
                'status' => 1]);
			$rs[] = $r = Db::table('tw_user_coin')->where('userid', $zhannei['userid'])->setInc($coin, $myzc['mum']);
		}

		if (!$fee_user['userid']) {
			$fee_user['userid'] = 0;
		}

		if (0 < $myzc['fee']) {
			$rs[] = Db::table('tw_myzc_fee')->insert([
			    'userid' => $fee_user['userid'],
                'username' => $Coin['zc_user'],
                'coinname' => $coin,
                'num' => $myzc['num'],
                'fee' => $myzc['fee'],
                'mum' => $myzc['mum'],
                'type' => 2,
                'addtime' => time(),
                'status' => 1]);

			if (Db::table('tw_user_coin')->where($coin . 'b', $Coin['zc_user'])->find()) {
				$rs[] = Db::table('tw_user_coin')->where($coin . 'b', $Coin['zc_user'])->setInc($coin, $myzc['fee']);
				debug(array('lastsql' => Db::table('tw_user_coin')->getLastSql()), '新增费用');
			} else {
				$rs[] = Db::table('tw_user_coin')->insert([$coin . 'b' => $Coin['zc_user'], $coin => $myzc['fee']]);
			}
		}

		$rs[] = Db::table('tw_myzc')->where('id', trim($id))->update(['status' => 1,'endtime'=>time()]);
		if (check_arr($rs)) {
			if ($coin == 'eth' || $coin == 'ETH' || $coin=='etc' || $coin=='ETC') {
				//转出 ETH、ETC
				
				$mum = $CoinClient->toWei($myzc['mum']);
				$sendrs = $CoinClient->eth_sendTransaction($dj_username,$myzc['username'],$dj_password,$mum);
				
			} elseif ($coin_config['token_type'] == 1) { //ETH对接,FFF
				//转出 ERC20代币
				
				//Token合约设置
				$addr = $coin_config['dj_hydz']; //ERC20合约地址
				$wei = 1e18; //手续费
				$methodid = '0xa9059cbb';
				
				if($coin=='zil'){
					$addr = '0x05f4a42e251f2d52b8ed15e9fedaacfcef1fad27';
					$wei = 1e12;
				}
				if($coin=='trx'){
					$addr = '0xf230b790e05390fc8295f4d3f60332c93bed42e2';
					$wei = 1e6;
				}
/*				if($coin=='fff'){
					$addr = '0xe045e994f17c404691b238b9b154c0998fa28aef';
				}*/
				
				if(!$addr){
					echo 'ERC20合约地址不存在';
					die();
				}
				
				$url = 'https://api.etherscan.io/api?module=account&action=tokenbalance&contractaddress='.$addr.'&address='.$dj_username.'&tag=latest&apikey=ERXIYCNF6PP3ZNQAWICHJ6N5W7P212AHZI';
				//contractaddress=合约地址,address=持有代币的地址
				$fanhui = file_get_contents($url);
				$fanhui = json_decode($fanhui,true);
				if ($fanhui['message'] == 'OK') {
					$numb = $fanhui['result']/$wei;//18位小数
				}
				if ($numb < $myzc['mum']) {
					$this->error('钱包余额不足');
				}
				$sendnum = NumToStr($myzc['mum']*$wei);
				$mum = bnumber($sendnum,10,16);
				$amounthex = sprintf("%064s",$mum);
				$addr2 = explode('0x',  $myzc['username'])[1];//接受地址
				$dataraw = $methodid.'000000000000000000000000'.$addr2.$amounthex;//拼接data
				$constadd = $addr;//合约地址
				$sendrs = $CoinClient->eth_sendTransactionraw($dj_username,$constadd,$dj_password,$dataraw);//转出账户,合约地址,转出账户解锁密码,data值
			} elseif ($coin=='usdt'|| $coin == 'USDT') {
				//转出 USDT
				
				$json = $CoinClient->getinfo();
				if ($json['balance'] < $myzc['mum']) {
					$this->error('钱包余额不足');
				} else {
					$sendrs = $CoinClient->omni_send($zcdz ,$myzc['username'] ,31 ,(double) $myzc['mum']);
				}
			} else {
				//转出 BTC
				
				$json = $CoinClient->getinfo();
				if ($json['balance'] < $myzc['mum']) {
					$this->error('钱包余额不足');
				} else {
					$sendrs = $CoinClient->sendtoaddress($myzc['username'] ,(double) $myzc['mum']);
				}
			}

			if ($sendrs) {
				Db::table('tw_myzc')->where('id', trim($id))->update(array('txid'=>$sendrs));
				$flag = 1;
				$arr = json_decode($sendrs, true);

				if (isset($arr['status']) && ($arr['status'] == 0)) {
					$flag = 0;
				}
			} else {
				$flag = 0;
			}

			if (!$flag) {
				Db::rollback();
				$this->error('钱包服务器转出币失败!');
			} else {
				Db::commit();
				$this->success('转账成功！');
			}
		} else {
			Db::rollback();
			$this->error('转出失败!' . implode('|', $rs) . $myzc['fee']);
		}
	}
}
?>