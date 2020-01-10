<?php
namespace app\admin\controller;

use think\Db;

class Config extends Admin
{
	protected function _initialize()
	{
		parent::_initialize();
		$allow_action=array("index","edit","image","mobile","mobileEdit","contact","contactEdit","coin","coinEdit","coinStatus","textStatus","coinInfo","coinUser","coinQing","coinImage","text","textEdit","qita","qitaEdit","daohang","daohangEdit","daohangStatus","hq","smss","sendsmsall","resendsms","dhfooter","dhfooterEdit","dhfooterStatus","dhadmin","dhadminEdit","dhadminStatus","marketo","marketoEdit","marketoEdit2","marketoEdit3","marketoStatus","tradeclear","joggle","joggleEdit","mining","miningEdit","ethsenior");
		if(!in_array($this->request->action(),$allow_action)){
			$this->error("页面不存在！".$this->request->action());
		}
	}

	public function index()
	{
		$this->data = Db::name('Config')->where('id', 1)->find();
		$this->assign('data', $this->data);
		return $this->fetch();
	}

	public function smss(){
		$sends = Db::name('sendsms')->select();
		$this->assign('list',$sends);
		return $this->fetch();
	}
	
	public function resendsms(){
		$data = input('get.id');
		if(!$data){
			$this->error('参数错误1!');
		}
		$sended = Db::name('sendsms')->where('id', $data)->find();
		if(!$sended){
			$this->error('参数错误2!');
		}
		$config = Db::name('Config')->where('id', 1)->find();
		$user = Db::name('user')->field('mobile,qz')->select();
		foreach ($user as $k => $v) {
			$allmo.=$v['qz'].$v['mobile'].',';//所有人的号码
		}
		if($allmo){
			$sign = "【".$config['web_name']."】";
			$content = $sended['nr'];
			$fh = sendsmsint($allmo, $content,$sign);
		if ($fh) {
				Db::name('sendsms')->where('id', $data)->setfield('status',1);
				$this->success(lang('短信已发送!'));
			} else {
				$this->error(lang('短信发送失败!'));
			}
		} else {
			$this->error('发送用户为空!');
		}

	}
	
	// 群发短信
	public function sendsmsall(){
		$data = input('post.');
		if (!$data) {
			$this->error('短信内容不能为空!');
		}
		$config = Db::name('Config')->where('id', 1)->find();
		$user = Db::name('user')->field('mobile,qz')->select();
		foreach ($user as $k => $v) {
			$allmo.=$v['qz'].$v['mobile'].',';//所有人的号码
		}
		if ($allmo) {
			$sign = "【".$config['web_name']."】";
			$content = $data['smscontent'];
			$fh = sendsmsint($allmo, $content,$sign);
		if ($fh) {
				$map['users'] = count($user);
				$map['nr'] = $content;
				$map['status'] = 1;
				$map['sendtime'] = time();
				Db::name('sendsms')->insert($map);
				$this->success(lang('短信已发送!'));
			} else {
				$map['users'] = count($user);
				$map['nr'] = $content;
				$map['status'] = 0;
				$map['sendtime'] = time();
				Db::name('sendsms')->insert($map);
				$this->error(lang('短信发送失败!'));
			}
		} else {
			$this->error('发送用户为空!');
		}
	}
	
	public function edit()
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}
		
        $data = input('post.');
		$data['web_reg'] = htmlspecialchars($_POST['web_reg']);
		if (Db::name('Config')->where('id', 1)->update($data)) {
			$this->success('修改成功！');
		} else {
			$this->error('修改失败');
		}
	}

	public function image()
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}
		
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

	public function mobile()
	{
		$this->data = Db::name('Config')->where('id', 1)->find();
		return $this->fetch();
	}

	public function mobileEdit()
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if (Db::name('Config')->where('id', 1)->update($_POST)) {
			$this->success('修改成功！');
		} else {
			$this->error('修改失败');
		}
	}

	public function contact()
	{
		$this->data = Db::name('Config')->where('id', 1)->find();
		return $this->fetch();
	}

	public function contactEdit()
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if (Db::name('Config')->where('id', 1)->update($_POST)) {
			$this->success('修改成功！');
		} else {
			$this->error('修改失败');
		}
	}

	public function coin($name = NULL, $field = NULL, $status = NULL)
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

		$show = Db::name('Coin')->where($where)->paginate(10);

		$list = Db::name('Coin')->where($where)->order('sort asc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function coinEdit($id = NULL)
	{
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = array();
			} else {
				$this->data = Db::name('Coin')->where('id', trim($_GET['id']))->find();
			}

			return $this->fetch();
		} else {
			$_POST['fee_bili'] = floatval($_POST['fee_bili']);

			if ($_POST['fee_bili'] && (($_POST['fee_bili'] < 0.01) || (100 < $_POST['fee_bili']))) {
				$this->error('挂单比例只能是0.01--100之间(不用填写%)！');
			}

			$_POST['zr_zs'] = floatval($_POST['zr_zs']);

			if ($_POST['zr_zs'] && (($_POST['zr_zs'] < 0.01) || (100 < $_POST['zr_zs']))) {
				$this->error('转入赠送只能是0.01--100之间(不用填写%)！');
			}

			$_POST['zr_dz'] = intval($_POST['zr_dz']);
			$_POST['zc_fee'] = floatval($_POST['zc_fee']);

			if ($_POST['zc_fee'] && (($_POST['zc_fee'] < 0.01) || (100 < $_POST['zc_fee']))) {
				$this->error('转出手续费只能是0.01--100之间(不用填写%)！');
			}

			if ($_POST['zc_user']) {
				// if (!check($_POST['zc_user'], 'dw')) {
				// 	$this->error('官方手续费地址格式不正确！');
				// }

				$ZcUser = Db::name('UserCoin')->where($_POST['name'] . 'b', $_POST['zc_user'])->find();
				if (!$ZcUser) {
					$this->error('在系统中查询不到[官方手续费地址],请务必填写正确！');
				}
			}

/*			$_POST['zc_min'] = intval($_POST['zc_min']);
			$_POST['zc_max'] = intval($_POST['zc_max']);*/
			$_POST['zc_min'] = $_POST['zc_min'];
			$_POST['zc_max'] = $_POST['zc_max'];

			if ($_POST['id']) {
				$rs = Db::name('Coin')->update($_POST);
			} else {
				if (!check($_POST['name'], 'n')) {
					$this->error('币种简称只能是小写字母！');
				}

				$_POST['name'] = strtolower($_POST['name']);

				if (check($_POST['name'], 'username')) {
					$this->error('币种名称格式不正确！');
				}

				if (Db::name('Coin')->where('name', $_POST['name'])->find()) {
					$this->error('币种存在！');
				}

				$rea = Db::execute('ALTER TABLE  `tw_user_coin` ADD  `' . $_POST['name'] . '` DECIMAL(20,8) UNSIGNED NOT NULL DEFAULT 0.00000000');
				$reb = Db::execute('ALTER TABLE  `tw_user_coin` ADD  `' . $_POST['name'] . 'd` DECIMAL(20,8) UNSIGNED NOT NULL DEFAULT 0.00000000');
				$rec = Db::execute('ALTER TABLE  `tw_user_coin` ADD  `' . $_POST['name'] . 'b` VARCHAR(200) NOT NULL DEFAULT 0');

				$rs = Db::name('Coin')->insert($_POST);
			}

			if ($rs) {
				$this->success('操作成功！',url('Config/coin'));
			} else {
				$this->error('数据未修改！');
			}
		}
	}

	public function coinStatus()
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
		$method = $_GET['type'];
		// $this->error($method);
		switch (strtolower($method)) {
			case 'forbid':
				$data = array('status' => 0);
				break;

			case 'resume':
				$data = array('status' => 1);
				break;

			case 'delt':
				$rs = Db::name('Coin')->where($where)->select();

				foreach ($rs as $k => $v) {
					$rs[] = Db::execute('ALTER TABLE  `tw_user_coin` DROP COLUMN ' . $v['name']);
					$rs[] = Db::execute('ALTER TABLE  `tw_user_coin` DROP COLUMN ' . $v['name'] . 'd');
					$rs[] = Db::execute('ALTER TABLE  `tw_user_coin` DROP COLUMN ' . $v['name'] . 'b');
				}

				if (Db::name('Coin')->where($where)->delete()) {
					$this->success('操作成功！');
				} else {
					$this->error('操作失败！');
				}

				break;

			default:

			$this->error('参数非法');
		}

		if (Db::name('Coin')->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function coinInfo($coin)
	{
		$dj_username = config('coin')[$coin]['dj_yh'];
		$dj_password = config('coin')[$coin]['dj_mm'];
		$dj_address = config('coin')[$coin]['dj_zj'];
		$dj_port = config('coin')[$coin]['dj_dk'];
		
		$coin_config = Db::name('Coin')->where('name', $coin)->find();
		
		if ($coin=='eth' || $coin=='etc' || $coin_config['token_type'] == 1) { //ETH对接,FFF
			$CoinClient = EthCommon($dj_address,$dj_port);
			//$info['b'] = $CoinClient->web3_clientVersion();
			if(!$CoinClient){
				$this->error('钱包对接失败！');
			}
			$info['coin'] = $coin;
			$info['ver'] = $CoinClient->web3_clientVersion();
			$info['coinbase'] = $CoinClient->eth_coinbase();
			$numb = $CoinClient->eth_getBalance($dj_username,"latest");//获取主账号余额
			$payfee = $CoinClient->eth_gasPrice();//获取gasprice
			$blocks = $CoinClient->eth_blockNumber();//获取区块数量
			$info['balance'] = (hexdec($numb))/1000000000000000000;//转换成ether单位显示;
			$info['payfee'] = hexdec($payfee);//wei单位显示;
			$info['blocks'] = hexdec($blocks);//wei单位显示;
			$info['num'] = Db::name('UserCoin')->sum($coin) + Db::name('UserCoin')->sum($coin . 'd');
		} else {
			$CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port);
			if (!$CoinClient) {
				$this->error('钱包对接失败！');
			}
			$info['coin'] = $coin;
			$info['b'] = $CoinClient->getinfo();
			$info['num'] = Db::name('UserCoin')->sum($coin) + Db::name('UserCoin')->sum($coin . 'd');
		}
				
		$this->assign('data', $info);
		return $this->fetch();
	}

	public function coinUser($coin)
	{
		$dj_username = config('coin')[$coin]['dj_yh'];
		$dj_password = config('coin')[$coin]['dj_mm'];
		$dj_address = config('coin')[$coin]['dj_zj'];
		$dj_port = config('coin')[$coin]['dj_dk'];
		
		$CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port);
		if (!$CoinClient) {
			$this->error('钱包对接失败！');
		}
		
		$coin_config = Db::name('Coin')->where('name', $coin)->find();
		
		if ($coin=='eth' || $coin=='etc' || $coin_config['token_type'] == 1) {//ETH对接20170920,FFF
			$arr = $CoinClient->eth_accounts();
		} else {
			$arr = $CoinClient->listaccounts();
		}

		if ($coin=='eth' || $coin=='etc' || $coin_config['token_type'] == 1) {//ETH对接20170920,FFF
			foreach ($arr as $k => $v) {
				if ($v < 1.0000000000000001E-5) {
					$v = 0;
				}
				$str = '';
				$numb = $CoinClient->eth_getBalance($v,"latest");
				$list[$k]['num'] = (hexdec($numb))/1000000000000000000;//转换成ether单位显示
				$str .= $v . '<br>';
				
				$userid = Db::name('UserCoin')->where(array($coin.'b' => $v))->value('userid');
				$list[$k]['addr'] = $str;
				
				$user_coin = Db::name('UserCoin')->where('userid', $userid)->find();
				$list[$k]['xnb'] = $user_coin[$coin];
				$list[$k]['xnbd'] = $user_coin[$coin . 'd'];
				$list[$k]['zj'] = $list[$k]['xnb'] + $list[$k]['xnbd'];
				$list[$k]['xnbb'] = $user_coin[$coin . 'b'];
				$list[$k]['coin'] = $coin;
				$list[$k]['name'] = Db::name('User')->where('id', $userid)->value('username');
				unset($str);
				// var_dump($list[$k]['name']);
			}
		} else {
			//比特币类型的来这里:
			foreach ($arr as $k => $v) {
				if ($k) {
					if ($v < 1.0000000000000001E-5) {
						$v = 0;
					}
					$str = '';
					$k = $k.'';
					$addr = $CoinClient->getaddressesbyaccount($k);
					$list[$k]['num'] = $v;
					foreach ($addr as $kk => $vv) {
						$str .= $vv . '<br>';
					}
					$list[$k]['addr'] = $str;
					$userid = Db::name('User')->where('username', $k)->value('id');
					$user_coin = Db::name('UserCoin')->where('userid', $userid)->find();
					$list[$k]['xnb'] = $user_coin[$coin];
					$list[$k]['xnbd'] = $user_coin[$coin . 'd'];
					$list[$k]['zj'] = $list[$k]['xnb'] + $list[$k]['xnbd'];
					$list[$k]['xnbb'] = $user_coin[$coin . 'b'];
					$list[$k]['coin'] = $coin;
					unset($str);
				}
			}
		}
		$this->assign('list', $list);
		return $this->fetch();
	}

	public function coinQing($coin)
	{
		if (!config('coin')[$coin]) {
			$this->error('参数错误！');
		}

		$info = Db::execute('UPDATE `tw_user_coin` SET `' . trim($coin) . 'b`=\'\' ;');
		if ($info) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function coinImage()
	{
		$upload = new \Think\Upload();
		$upload->maxSize = 3145728;
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		$upload->rootPath = './Upload/coin/';
		$upload->autoSub = false;
		
		$info = $upload->upload();
		foreach ($info as $k => $v) {
			$path = $v['savepath'] . $v['savename'];
			echo $path;
			exit();
		}
	}

	public function qita()
	{
		$this->data = Db::name('Config')->where('id', 1)->find();
		return $this->fetch();
	}

	public function qitaEdit()
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}
		
		$data = input('post.');
		if (Db::name('Config')->where('id', 1)->update($data)) {
			$this->success('修改成功！');
		} else {
			$this->error('修改失败');
		}
	}
	
	public function mining()
	{
		$this->data = Db::name('Config')->where('id', 1)->find();
		return $this->fetch();
	}

	public function miningEdit()
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}
		
		$data = input('post.');
		
		if (!$data['mining_coin_num'] > 0) {
			$this->error('交易挖矿比例必须大于0');
		}
		if (!$data['mining_toplimit']) {
			$this->error('交易挖矿总量不能为空');
		}
		
		if (Db::name('Config')->where('id', 1)->update($data)) {
			$this->success('修改成功！');
		} else {
			$this->error('修改失败');
		}
	}
	
	public function joggle()
	{
		$this->data = Db::name('Config')->where('id', 1)->find();
		return $this->fetch();
	}

	public function joggleEdit()
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}
		
		$data = input('post.');
		if (Db::name('Config')->where('id', 1)->update($data)) {
			$this->success('修改成功！');
		} else {
			$this->error('修改失败');
		}
	}
	
	// 前端导航配置
	public function daohang($name = NULL, $field = NULL, $status = NULL, $lang = NULL)
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
		if ($lang) {
			$where['lang'] = $lang;
		}
		
		$show = Db::name('Daohang')->where($where)->paginate(10);

		$list = Db::name('Daohang')->where($where)->order('sort asc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function daohangEdit($id = NULL)
	{
	    //dump($_POST);
		if (empty($_POST)) {
			if ($id) {
				$this->data = Db::name('Daohang')->where('id', trim($id))->find();
			} else {
				$this->data = null;
			}

			return $this->fetch();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			if ($_POST['id']) {
				$rs = Db::name('Daohang')->update($_POST);
			} else {
				$_POST['addtime'] = time();
				$rs = Db::name('Daohang')->insert($_POST);
			}

			if ($rs) {
                $closeUrl = cache('closeUrl');
			    if($_POST['get_login']) {
                    $closeUrl[] = $_POST['url'];
                } else {
                    if($key = array_search($_POST['url'], $closeUrl)) {
                        unset($closeUrl[$key]);
                    }
                }
                $closeUrl = array_unique($closeUrl);
                sort($closeUrl);
                cache('closeUrl', $closeUrl);

				$this->success('编辑成功！',url('Config/daohang'));
			} else {
				$this->error('编辑失败！');
			}
		}
	}

	public function daohangStatus($id = NULL, $type = NULL, $mobile = 'Daohang')
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

		case 'del':
			$data = array('status' => -1);
			break;

		case 'delete':
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
	
	// 页脚导航配置
	public function dhfooter($name = NULL, $field = NULL, $status = NULL)
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

		$show = Db::name('footer')->where($where)->count();

		$list = Db::name('footer')->where($where)->order('sort asc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}
	
	public function dhfooterEdit($id = NULL)
	{
		if (empty($_POST)) {
			if ($id) {
				$this->data = Db::name('footer')->where('id', trim($id))->find();
			} else {
				$this->data = null;
			}

			return $this->fetch();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			if ($_POST['id']) {
				$rs = Db::name('footer')->update($_POST);
			} else {
				$_POST['addtime'] = time();
				$rs = Db::name('footer')->insert($_POST);
			}

			if ($rs) {
                $closeUrl = cache('closeUrl');
                if($_POST['get_login']) {
                    $closeUrl[] = $_POST['url'];
                } else {
                    if($key = array_search($_POST['url'], $closeUrl)) {
                        unset($closeUrl[$key]);
                    }
                }
                $closeUrl = array_unique($closeUrl);
                sort($closeUrl);
                cache('closeUrl', $closeUrl);
				$this->success('编辑成功！',url('Config/dhfooter'));
			} else {
				$this->error('编辑失败！');
			}
		}
	}

	public function dhfooterStatus($id = NULL, $type = NULL, $mobile = 'footer')
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

		case 'del':
			$data = array('status' => -1);
			break;

		case 'delete':
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
	
	// 后端导航配置
	public function dhadmin($name = NULL, $field = NULL, $status = NULL, $hide = NULL)
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
		if ($hide) {
			$where['hide'] = $hide;
		}
		
		$where_1 = $where;
		$where_1['pid'] = 0;
		$where_2 = $where;
		
		$list = Db::name('menu')->where($where_1)->order('sort asc')->select();
		foreach ($list as $k => $v) {
			$where_2['pid'] = $v['id'];
			$list[$k]['voo'] = Db::name('menu')->where($where_2)->order('sort asc')->select();
		}
		
		$this->assign('list', $list);
		return $this->fetch();
	}
	
	public function dhadminEdit($id = NULL)
	{
		if (empty($_POST)) {
			$liste = '';
			
			if ($id) {
				$this->data = Db::name('menu')->where('id', trim($id))->find();
			} else {
				$this->data = null;
			}
			
			$liste = Db::name('menu')->where('pid = 0')->order('sort asc')->select();
			$this->assign('liste', $liste);
			return $this->fetch();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}
			
			if (empty($_POST['title'])) {
				$this->error('标题错误');
			}

			if ($_POST['id']) {
				$rs = Db::name('menu')->update($_POST);
			} else {
				$_POST['addtime'] = time();
				$rs = Db::name('menu')->insert($_POST);
			}

			if ($rs) {
				$this->success('编辑成功！',url('Config/dhadmin'));
			} else {
				$this->error('编辑失败！');
			}
		}	
	}

	public function dhadminStatus($id = NULL, $type = NULL, $mobile = 'menu')
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
				$data = array('hide' => 1);
				break;

			case 'resume':
				$data = array('hide' => 0);
				break;

			case 'repeal':
				$data = array('hide' => 2);
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

	public function marketo($field = NULL, $name = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where('username', $name)->value('id');
			} else {
				$where[$field] = $name;
			}
		}
		
		$show = Db::name('Market')->where($where)->paginate(10);

		$list = Db::name('Market')->where($where)->order('sort asc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}
	
	// 市场配置修改
	public function marketoEdit($id = NULL)
	{
		$getCoreConfig = getCoreConfig();
		if(!$getCoreConfig){
			$this->error('核心配置有误');
		}

		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = array();
			} else {
				$this->data = Db::name('Market')->where('id', $id)->find();
			}
			$time_arr = array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23');
			$time_minute = array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','38','39','40','41','42','43','44','45','46','47','48','49','50','51','52','53','54','55','56','57','58','59');
			
			$this->assign('time_arr', $time_arr);
			$this->assign('time_minute', $time_minute);
			$this->assign('getCoreConfig',$getCoreConfig['indexcat']);
			return $this->fetch();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$round = array(0, 1, 2, 3, 4, 5, 6);
			if (!in_array($_POST['round'], $round)) {
				$this->error('小数位数格式错误！');
			}
			
			if(!$_POST['hou_price']){
				$_POST['hou_price'] = '0.00000000';
			}

			if ($_POST['id']) {
				$rs = Db::name('Market')->update($_POST);
			} else {
				$buyname = $_POST['buyname'];
				$_POST['name'] = $_POST['sellname'] . '_' . $_POST['buyname'];
				unset($_POST['buyname']);
				unset($_POST['sellname']);

				if (Db::name('Market')->where('name', $_POST['name'])->find()) {
					$this->error('市场存在！');
				}
				
				$jiaoyiqu = strtolower($getCoreConfig['indexcat'][$_POST['jiaoyiqu']]);
				if ($buyname != $jiaoyiqu) {
					$this->error('所属交易区和买方币种不一致！'.$buyname);
				}
				$rs = Db::name('Market')->insert($_POST);
			}

			if ($rs) {
				$this->success('操作成功！',url('Config/marketo'));
			} else {
				$this->error('操作失败！');
			}
		}
	}
	
	// 市场配置2修改
	public function marketoEdit2($id = NULL)
	{
		$getCoreConfig = getCoreConfig();
		if(!$getCoreConfig){
			$this->error('核心配置有误');
		}
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = array();
			} else {
				$this->data = Db::name('Market')->where('id', $id)->find();
			}
			
			$time_arr = array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23');
			$time_minute = array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','38','39','40','41','42','43','44','45','46','47','48','49','50','51','52','53','54','55','56','57','58','59');
			$this->assign('time_arr', $time_arr);
			$this->assign('time_minute', $time_minute);
			$this->assign('getCoreConfig',$getCoreConfig['indexcat']);
			return $this->fetch();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$round = array(0, 1, 2, 3, 4, 5, 6);
			if (!in_array($_POST['round'], $round)) {
				$this->error('小数位数格式错误！');
			}

			if ($_POST['id']) {
				$rs = Db::name('Market')->update($_POST);
			} else {
				$_POST['name'] = $_POST['sellname'] . '_' . $_POST['buyname'];
				unset($_POST['buyname']);
				unset($_POST['sellname']);

				if (Db::name('Market')->where('name', $_POST['name'])->find()) {
					$this->error('市场存在！');
				}
				$rs = Db::name('Market')->insert($_POST);
			}

			if ($rs) {
				$this->success('操作成功！',url('Config/marketo'));
			} else {
				$this->error('操作失败！');
			}
		}
	}
	
	// 市场配置3修改
	public function marketoEdit3($id = NULL)
	{
		$getCoreConfig = getCoreConfig();
		if(!$getCoreConfig){
			$this->error('核心配置有误');
		}
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = array();
			} else {
				$this->data = Db::name('Market')->where('id', $id)->find();
			}
			
			$time_arr = array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23');
			$time_minute = array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','38','39','40','41','42','43','44','45','46','47','48','49','50','51','52','53','54','55','56','57','58','59');
			$this->assign('time_arr', $time_arr);
			$this->assign('time_minute', $time_minute);
			$this->assign('getCoreConfig',$getCoreConfig['indexcat']);
			
			$round = number_format("0",$this->data['round']-1).'1';
			$this->assign('round', $round);
			
			return $this->fetch();
			
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$round = array(0, 1, 2, 3, 4, 5, 6);
			if (!in_array($_POST['round'], $round)) {
				$this->error('小数位数格式错误！');
			}

			if ($_POST['id']) {
				$rs = Db::name('Market')->update($_POST);
			} else {
				$_POST['name'] = $_POST['sellname'] . '_' . $_POST['buyname'];
				unset($_POST['buyname']);
				unset($_POST['sellname']);

				if (Db::name('Market')->where('name', $_POST['name'])->find()) {
					$this->error('市场存在！');
				}

				$rs = Db::name('Market')->insert($_POST);
			}

			if ($rs) {
				$this->success('操作成功！',url('Config/marketo'));
			} else {
				$this->error('操作失败！');
			}
		}
	}

	public function marketoStatus($id = NULL, $type = NULL, $mobile = 'Market')
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
	
	public function tradeclear($type=NULL,$id=NULL)
	{
		if (!$id) {
			$this->error('请选择交易市场!');
		}
		if (!$type) {
			$this->error('请选择清理类型!');
		}
		$market= Db::name('Market')->where('id', $id)->find();
		if ($type == 1) {
			$allclear=Db::name('Trade')->where(array('market'=>$market['name'],'userid'=>0))->delete();
		}
		if ($type == 2) {
			if (!$market['sdhigh'] or !$market['sdlow']) {
				$this->error('该市场未设置刷单最高价或最低价,无法部分清理');
			}
			$map['market'] = $market['name'];
			$map['userid'] = 0;
			$map['price'] = array('notbetween',array($market['sdhigh'],$market['sdlow']));
			$allclear=Db::name('Trade')->where($map)->delete();
		}
		if ($allclear) {
			$this->success('清理成功,一共'.$allclear.'条刷单记录');
		} else {
			$this->error('清理失败!');
		}
	}
	
	/** ETH钱包高级管理 **/
	public function ethsenior($coins=NULL,$number=NULL)
	{
		if (empty($_POST)) {
			$coin_list = Db::name('Coin')->where(array('api_type' => 'eth','status' => 1))->select();
			$this->assign('coin_list', $coin_list);
			
			$coin_info = Db::name('Coin')->where('name', 'eth')->find();
			$this->assign('coin_info', $coin_info);
			
			if (!$coin_info['dj_yh'] && !$coin_info['dj_mm']) {
				$this->error('请设置总钱包地址！');
			}
			
			return $this->fetch();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			if ($coins && $number) {
				if ($coins == 'eth') {
					$rs_url = 'Home/Queue/ethcovera99b88c77d66e55';
				} else if ($coins == 'eth') {
					$rs_url = 'Home/Queue/ethcovera99b88c77d66e55';
				} else {
					$rs_url = '/home/Queue/tokensonlinea88b77c11d0a9d/coin/'.$coins.'/block/'.$number;
				}
			} else {
				$rs_url = '';
			}

			if ($rs_url) {
				$this->redirect($rs_url);
			} else {
				$this->error('操作失败！');
			}
		}
	}
}
?>