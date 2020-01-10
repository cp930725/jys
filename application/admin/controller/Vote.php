<?php
namespace app\admin\controller;

class Vote extends Admin
{
	protected function _initialize(){
		parent::_initialize();
		$allow_action=array("index","type","edit","status","kaishi","fenfa","log");
		if(!in_array($this->request->action(),$allow_action)){
			$this->error("页面不存在！");
		}
	}
	
	public function index($p=1, $r=15, $str_addtime='', $end_addtime='', $order='', $status='', $type='', $field='', $name='')
	{
		$map = array();

		if ($str_addtime && $end_addtime) {
			$str_addtime = strtotime($str_addtime);
			$end_addtime = strtotime($end_addtime);

			if ((addtime($str_addtime) != '---') && (addtime($end_addtime) != '---')) {
				$map['addtime'] = array(
					array('egt', $str_addtime),
					array('elt', $end_addtime)
				);
			}
		}

		if (empty($order)) {
			$order = 'id_desc';
		}

		$order_arr = explode('_', $order);
		if (count($order_arr) != 2) {
			$order = 'id_desc';
			$order_arr = explode('_', $order);
		}

		$order_set = $order_arr[0] . ' ' . $order_arr[1];

		if (empty($status)) {
			$map['status'] = array('egt', 0);
		}
		if (($status == 1) || ($status == 2) || ($status == 3)) {
			$map['status'] = $status - 1;
		}

		if ($field && $name) {
			if ($field == 'username') {
				$map['userid'] = userid($name);
			} else {
				$map[$field] = $name;
			}
		}

		$data = Db::name('Vote')->where($map)->order($order_set)->page($p, $r)->select();
		$count = Db::name('Vote')->where($map)->count();
		$parameter['p'] = $p;
		$parameter['status'] = $status;
		$parameter['order'] = $order;
		$parameter['type'] = $type;
		$parameter['name'] = $name;
		$builder = new BuilderList();
		$builder->title('投票记录');
		$builder->titleList('投票列表', url('Vote/index'));
		$builder->button('delete', '删 除', url('Vote/status', array('model' => 'Vote', 'status' => -1)));
		$builder->setSearchPostUrl(url('Vote/index'));
		$builder->search('order', 'select', array('id_desc' => 'ID降序', 'id_asc' => 'ID升序'));
		$builder->search('status', 'select', array('全部状态', '禁用', '启用'));
		$builder->search('field', 'select', array('username' => '用户名'));
		$builder->search('name', 'text', '请输入查询内容');
		$builder->keyId();
		$builder->keyUserid();
		$builder->keyText('coinname', '币种');
		$builder->keyType('type', '类型', array(1 => '支持', 2 => '反对'));
		$builder->keyText('num', '票数');
		$builder->keyText('votecoin', '投票币种');
		$builder->keyText('mum', '扣除数量');
		$builder->keyTime('addtime', '添加时间');
		$builder->keyStatus();
		$builder->data($data);
		$builder->pagination($count, $r, $parameter);
		
		$builder->display();
	}

	public function type($p=1, $r=15, $str_addtime='', $end_addtime='', $order='', $status='', $type='', $field='', $name='')
	{
		$map = array();

		if ($str_addtime && $end_addtime) {
			$str_addtime = strtotime($str_addtime);
			$end_addtime = strtotime($end_addtime);
			if ((addtime($str_addtime) != '---') && (addtime($end_addtime) != '---')) {
				$map['addtime'] = array(
					array('egt', $str_addtime),
					array('elt', $end_addtime)
				);
			}
		}

		if (empty($order)) {
			$order = 'id_desc';
		}

		$order_arr = explode('_', $order);
		if (count($order_arr) != 2) {
			$order = 'id_desc';
			$order_arr = explode('_', $order);
		}

		$order_set = $order_arr[0].' '.$order_arr[1];

		if (empty($status)) {
			$map['status'] = array('egt', 0);
		}
		if (($status == 1) || ($status == 2) || ($status == 3)) {
			$map['status'] = $status - 1;
		}

		if ($field && $name) {
			if ($field == 'username') {
				$map['userid'] = userid($name);
			} else {
				$map[$field] = $name;
			}
		}

		$data = Db::name('VoteType')->where($map)->order($order_set)->page($p, $r)->select();
		$count = Db::name('VoteType')->where($map)->count();
		
        foreach ($data as $k => $vv) {
            $data[$k]['zhichi'] = Db::name('Vote')->where('coinname', $vv['coinname'])->where('type', 1)->sum('num') + $vv['zhichi'];
            $data[$k]['fandui'] = Db::name('Vote')->where('coinname', $vv['coinname'])->where('type', 2)->sum('num') + $vv['fandui'];
            $data[$k]['zongji'] = $data[$k]['zhichi'] + $data[$k]['fandui'];
            //$data[$k]['bili'] = round(($data[$k]['zhichi'] / $data[$k]['zongji']) * 100, 2);
        }
		
		$parameter['p'] = $p;
		$parameter['status'] = $status;
		$parameter['order'] = $order;
		$parameter['type'] = $type;
		$parameter['name'] = $name;

		$builder = new BuilderList();
		$builder->title('投票配置');
		$builder->titleList('投票类型', url('Vote/type'));
		$builder->button('add', '添 加', url('Vote/edit'));
		$builder->button('resume', '启 用', url('Vote/status', array('model' => 'VoteType', 'status' => 1)));
		$builder->button('forbid', '禁 用', url('Vote/status', array('model' => 'VoteType', 'status' => 0)));
		$builder->button('delete', '删 除', url('Vote/status', array('model' => 'VoteType', 'status' => -1)));
		$builder->setSearchPostUrl(url('Vote/index'));
		$builder->search('order', 'select', array('id_desc' => 'ID降序', 'id_asc' => 'ID升序'));
		$builder->search('status', 'select', array('全部状态', '禁用', '启用'));
		$builder->search('field', 'select', array('coinname' => '币种'));
		$builder->search('name', 'text', '请输入查询内容');
		
		$builder->keyId();
		$builder->keyText('coinname', '币种类型');
		$builder->keyText('title', '币种名称');
		$builder->keyText('zhichi', '增加支持票');
		$builder->keyText('fandui', '增加反对票');
		$builder->keyText('zongji', '总票数');
		$builder->keyText('votecoin', '投票币种');
		$builder->keyText('assumnum', '扣除数量');
		$builder->keyStatus();
		$builder->keyDoAction('Vote/edit?id=###', '编辑', '操作');
		$builder->data($data);
		$builder->pagination($count, $r, $parameter);
		
		$builder->display();
	}

	public function edit($id=NULL)
	{
		if (!empty($_POST)) {
			if (check($_POST['id'], 'd')) {
				$rs = Db::name('VoteType')->update($_POST);
			} else {
				if (Db::name('VoteType')->where('coinname', $_POST['coinname'])->find()) {
					$this->error('已经存在');
				}

                $array = array(
                    'coinname' => $_POST['coinname'],
                    'title' => $_POST['title'],
					'votecoin' => $_POST['votecoin'],
					'assumnum' => $_POST['assumnum'],
                    'status' => $_POST['status'],
                );
				$rs = Db::name('VoteType')->insert($array);
			}

			if ($rs) {
				$this->success('操作成功',url('Vote/type'));
			} else {
				$this->error('操作失败');
			}
		} else {
			$builder = new BuilderEdit();
			$builder->title('投票类型管理');
			$builder->titleList('投票类型列表', url('Vote/type'));

			if ($id) {
				$builder->keyReadOnly('id', '类型id');
				$builder->keyHidden('id', '类型id');
				$data = Db::name('VoteType')->where('id', $id)->find();
				$builder->data($data);
			}

			$coin_list = Model('Coin')->get_all_name_list();
			$builder->keySelect('coinname', '币种类型', '币种', $coin_list);
			$builder->keyText('title', '币种名称','币种全称（中文或英文）');
			$builder->keyText('zhichi', '增加支持票数', '整数');
			$builder->keyText('fandui', '增加反对票数', '整数');
			$builder->keySelect('votecoin', '投票币种', '投票需要扣除的币种', $coin_list);
			$builder->keyText('assumnum', '扣除个数', '整数,投一次票扣除的币种个数（不需要扣费填0）');
			$builder->keySelect('status', '状态', '', array(1 => '可用', 0 => '禁用'));
			$builder->savePostUrl(url('Vote/edit'));
			
			$builder->display();
		}
	}

	public function status($id, $status, $model)
	{
		$builder = new BuilderList();
		$builder->doSetStatus($model, $id, $status);
	}

	public function kaishi()
	{
		$id = $_GET['id'];

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$data = Db::name('Fenhong')->where('id', $id)->find();
		if ($data['status'] != 0) {
			$this->error('已经处理，禁止再次操作！');
		}

		$a = Db::name('UserCoin')->sum($data['coinname']);
		$b = Db::name('UserCoin')->sum($data['coinname'] . 'd');
		$data['quanbu'] = $a + $b;
		$data['meige'] = round($data['num'] / $data['quanbu'], 8);
		$data['user'] = Db::name('UserCoin')->where(array(
			$data['coinname'] => array('gt', 0),
			$data['coinname'] . 'd' => array('gt', 0),
			'_logic' => 'OR'
		))->count();
		$this->assign('data', $data);
		
		return $this->fetch();
	}

	public function fenfa($id=NULL, $fid=NULL, $dange=NULL)
	{
		if ($id === null) {
			echo json_encode(array('status' => -2, 'info' => '参数错误'));
			exit();
		}
		if ($fid === null) {
			echo json_encode(array('status' => -2, 'info' => '参数错误2'));
			exit();
		}
		if ($dange === null) {
			echo json_encode(array('status' => -2, 'info' => '参数错误3'));
			exit();
		}

		if ($id == -1) {
			cache('fenhong_fenfa_j', null);
			cache('fenhong_fenfa_c', null);
			cache('fenhong_fenfa', null);
			
			$fenhong = Db::name('Fenhong')->where('id', $fid)->find();
			if (!$fenhong) {
				echo json_encode(array('status' => -2, 'info' => '分红初始化失败'));
				exit();
			}

			cache('fenhong_fenfa_j', $fenhong);
			$usercoin = Db::name('UserCoin')->where(array(
				$fenhong['coinname'] => array('gt', 0),
				$fenhong['coinname'] . 'd' => array('gt', 0),
				'_logic' => 'OR'
			))->select();

			if (!$usercoin) {
				echo json_encode(array('status' => -2, 'info' => '没有用户持有'));
				exit();
			}

			$a = 1;
			foreach ($usercoin as $k => $v) {
				$shiji[$a]['userid'] = $v['userid'];
				$shiji[$a]['chiyou'] = $v[$fenhong['coinname']] + $v[$fenhong['coinname'] . 'd'];
				$a++;
			}

			if (!$shiji) {
				echo json_encode(array('status' => -2, 'info' => '计算错误'));
				exit();
			}

			cache('fenhong_fenfa_c', count($usercoin));
			cache('fenhong_fenfa', $shiji);
			echo json_encode(array('status' => 1, 'info' => '分红初始化成功'));
			exit();
		}

		if ($id == 0) {
			echo json_encode(array('status' => 1, 'info' => ''));
			exit();
		}
		if (S('fenhong_fenfa_c') < $id) {
			echo json_encode(array('status' => 100, 'info' => '分红全部完成'));
			exit();
		}

		if ((0 < $id) && ($id <= cache('fenhong_fenfa_c'))) {
			$fenhong = cache('fenhong_fenfa_j');
			$fenfa = cache('fenhong_fenfa');
			$cha = Db::name('FenhongLog')->where('name', $fenhong['name'])->where('coinname', $fenhong['coinname'])->where('userid', $fenfa[$id]['userid'])->find();

			if ($cha) {
				echo json_encode(array('status' => -2, 'info' => '用户id' . $fenfa[$id]['userid'] . '本次分红已经发过'));
				exit();
			}

			$faduoshao = round($fenfa[$id]['chiyou'] * $dange, 8);
			if (!$faduoshao) {
				echo json_encode(array('status' => -2, 'info' => '用户id' . $fenfa[$id]['userid'] . '分红数量太小不用发了，持有数量' . $fenfa[$id]['chiyou']));
				exit();
			}

			$mo = db();
			Db::execute('set autocommit=0');
			Db::execute('lock tables tw_user_coin write,tw_fenhong_log write');
			$rs = array();
			$rs[] = Db::table('tw_user_coin')->where('userid', $fenfa[$id]['userid'])->setInc($fenhong['coinjian'], $faduoshao);
			$rs[] = Db::table('tw_fenhong_log')->insert([
			    'name' => $fenhong['name'],
                'userid' => $fenfa[$id]['userid'],
                'coinname' => $fenhong['coinname'],
                'coinjian' => $fenhong['coinjian'],
                'fenzong' => $fenhong['num'],
                'price' => $dange,
                'num' => $fenfa[$id]['chiyou'],
                'mum' => $faduoshao, 
                'addtime' => time(),
                'status' => 1
            ]);

			if (check_arr($rs)) {
				Db::execute('commit');
				Db::execute('unlock tables');
				echo json_encode(array('status' => 1, 'info' => '用户id' . $fenfa[$id]['userid'] . '，持有数量' . $fenfa[$id]['chiyou'] . '成功分红' . $faduoshao));
				exit();
			} else {
				Db::execute('rollback');
				echo json_encode(array('status' => -2, 'info' => '用户id' . $fenfa[$id]['userid'] . '，持有数量' . $fenfa[$id]['chiyou'] . '分红失败'));
				exit();
			}
		}
	}

	public function log($p=1, $r=15, $str_addtime='', $end_addtime='', $order='', $status='', $type='', $field='', $name='', $coinname='', $coinjian='')
	{
		$map = array();

		if ($str_addtime && $end_addtime) {
			$str_addtime = strtotime($str_addtime);
			$end_addtime = strtotime($end_addtime);

			if ((addtime($str_addtime) != '---') && (addtime($end_addtime) != '---')) {
				$map['addtime'] = array(
					array('egt', $str_addtime),
					array('elt', $end_addtime)
				);
			}
		}

		if (empty($order)) {
			$order = 'id_desc';
		}

		$order_arr = explode('_', $order);
		if (count($order_arr) != 2) {
			$order = 'id_desc';
			$order_arr = explode('_', $order);
		}

		$order_set = $order_arr[0] . ' ' . $order_arr[1];
		
		if (empty($status)) {
			$map['status'] = array('egt', 0);
		}
		if (($status == 1) || ($status == 2) || ($status == 3)) {
			$map['status'] = $status - 1;
		}

		if ($field && $name) {
			if ($field == 'userid') {
				$map['userid'] = Model('User')->get_userid($name);
			} else {
				$map[$field] = $name;
			}
		}

		if ($coinname) {
			$map['coinname'] = $coinname;
		}
		if ($coinjian) {
			$map['coinjian'] = $coinjian;
		}

		$data = Db::name('FenhongLog')->where($map)->order($order_set)->page($p, $r)->select();
		$count = Db::name('FenhongLog')->where($map)->count();
		$parameter['p'] = $p;
		$parameter['status'] = $status;
		$parameter['order'] = $order;
		$parameter['type'] = $type;
		$parameter['name'] = $name;
		$parameter['coinname'] = $coinname;
		$parameter['coinjian'] = $coinjian;
		$builder = new BuilderList();
		$builder->title('分红记录');
		$builder->titleList('记录列表', url('Fenhong/log'));
		$builder->setSearchPostUrl(url('Fenhong/log'));
		$builder->search('order', 'select', array('id_desc' => 'ID降序', 'id_asc' => 'ID升序'));
		$coinname_arr = array('' => '分红币种');
		$coinname_arr = array_merge($coinname_arr, D('Coin')->get_all_name_list());
		$builder->search('coinname', 'select', $coinname_arr);
		$coinjian_arr = array('' => '奖励币种');
		$coinjian_arr = array_merge($coinjian_arr, D('Coin')->get_all_name_list());
		$builder->search('coinjian', 'select', $coinjian_arr);
		$builder->search('field', 'select', array('name' => '分红名称', 'userid' => '用户名'));
		$builder->search('name', 'text', '请输入查询内容');
		$builder->keyId();
		$builder->keyText('name', '分红名称');
		$builder->keyUserid();
		$builder->keyText('coinname', '分红币种');
		$builder->keyText('coinjian', '奖励币种');
		$builder->keyText('fenzong', '分红总数');
		$builder->keyText('price', '每个奖励');
		$builder->keyText('num', '持有数量');
		$builder->keyText('mum', '分红数量');
		$builder->keyTime('addtime', '分红时间');
		$builder->data($data);
		$builder->pagination($count, $r, $parameter);
		
		$builder->display();
	}
}
?>