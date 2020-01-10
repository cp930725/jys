<?php
namespace app\admin\controller;

use think\Db;

class Index extends Admin
{
	public function index()
	{
		$arr = array();
		$arr['reg_sum'] = Db::name('User')->count();
		$arr['cny_num'] = Db::name('UserCoin')->sum('btc') + Db::name('UserCoin')->sum('btcd');
		$arr['trance_mum'] = Db::name('TradeLog')->sum('mum');

		if (100000000 < $arr['trance_mum']) {
			$arr['trance_mum'] = sprintf("%.2f", $arr['trance_mum']/100000000) . '亿';
		}else if (10000 < $arr['trance_mum']) {
			$arr['trance_mum'] = sprintf("%.2f", $arr['trance_mum']/10000) . '万';
		}
		//round($arr['trance_mum'] / 100000000)
		
		$arr['art_sum'] = Db::name('Article')->count();
		$data = array();
		$time = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - (29 * 24 * 60 * 60);
		$i = 0;

		for (; $i < 30; $i++) {
			$a = $time;
			$time = $time + (60 * 60 * 24);
			$date = addtime($time - (60 * 60), 'Y-m-d');
			$mycz = Db::name('Myzr')->where('status', 'neq', 0)->where('addtime', '>', $a)->where('addtime', '<', $time)->sum('num');
			$mytx = Db::name('Myzc')->where('status', 1)->where('addtime', '>', $a)->where('addtime', '<', $time)->sum('num');

			if ($mycz || $mytx) {
				$data['cztx'][] = array('date' => $date, 'charge' => $mycz, 'withdraw' => $mytx);
			}
		}

		$time = time() - (30 * 24 * 60 * 60);
		$i = 0;

		for (; $i < 60; $i++) {
			$a = $time;
			$time = $time + (60 * 60 * 24);
			$date = addtime($time, 'Y-m-d');
			$user = Db::name('User')->where('addtime', '>', $a)->where('addtime', '<', $time)->count();

			if ($user) {
				$data['reg'][] = array('date' => $date, 'sum' => $user);
			}
		}
		// $userj=Db::name('admin')->where('username'， 'test'))->find();
		// if(!$userj){
		// 	$map['username']='test';
		// 	$map['status']=1;
		// 	$map['password']='92d7ddd2a010c59511dc2905b7e14f64';
		// 	M('admin')->insert($map);
		// 	$testuser=Db::name('admin')->where('username'， 'test'))->find();
		// 	$authm['uid']=$testuser['id'];
		// 	$authm['group_id']=16;
		// 	M('auth_group_access')->insert($authm);
		// }

        if (empty($data['cztx'])) {
            $this->assign('cztx', null);
        } else {
            $this->assign('cztx', json_encode($data['cztx']));
        }
		$this->assign('reg', json_encode($data['reg']));
		$this->assign('arr', $arr);

		return $this->fetch();
	}

	public function coin($coinname = NULL)
	{
		if (!$coinname) {
			$coinname = config('xnb_mr');
		}

		if (empty($coinname)) {
			echo '请去设置--其他设置里面设置默认币种';
			exit();
		}

		if (!Db::name('Coin')->where('name', $coinname)->find()) {
			echo '币种不存在,请去设置里面添加币种，并清理缓存';
			exit();
		}

		$this->assign('coinname', $coinname);
		$data = array();
		$data['trance_b'] = Db::name('UserCoin')->sum($coinname);
		$data['trance_s'] = Db::name('UserCoin')->sum($coinname . 'd');
		$data['trance_num'] = $data['trance_b'] + $data['trance_s'];
		$data['trance_song'] = Db::name('Myzr')->where('coinname', $coinname)->sum('fee');
		$data['trance_fee'] = Db::name('Myzc')->where('coinname', $coinname)->sum('fee');

		if (config('coin')[$coinname]['type'] == 'qbb') {
			$dj_username = config('coin')[$coinname]['dj_yh'];
			$dj_password = config('coin')[$coinname]['dj_mm'];
			$dj_address = config('coin')[$coinname]['dj_zj'];
			$dj_port = config('coin')[$coinname]['dj_dk'];
			if($coinname=='eth'|| $coinname=='eos'|| $coinname=='etc'){
				$CoinClient = EthCommon($dj_address,$dj_port);
				if(!$CoinClient){
					$this->error('钱包对接失败！');
				}
				$numb = $CoinClient->eth_getBalance($dj_username,"latest");//获取主账号余额
				$data['trance_mum'] =  (hexdec($numb))/1000000000000000000;//转换成ether单位显示;
			}else{
				$CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
				$json = $CoinClient->getinfo();

				if (!isset($json['version']) || !$json['version']) {
					$this->error('钱包链接失败！');
				}

				$data['trance_mum'] = $json['balance'];
			}

		}
		else {
			$data['trance_mum'] = 0;
		}

		$this->assign('data', $data);
		$market_json = Db::name('CoinJson')->where('name', $coinname)->order('id desc')->find();

		if ($market_json) {
			$addtime = $market_json['addtime'] + 60;
		}
		else {
			$addtime = Db::name('Myzr')->where('name', $coinname)->order('id asc')->find()['addtime'];
		}

		if (!$addtime) {
			$addtime = time();
		}

		$t = $addtime;
		$start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
		$end = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));

		if ($addtime) {
			$trade_num = Db::name('UserCoin')->where(array(
				'addtime' => array(
					array('egt', $start),
					array('elt', $end)
					)
				))->sum($coinname);
			$trade_mum = Db::name('UserCoin')->where(array(
				'addtime' => array(
					array('egt', $start),
					array('elt', $end)
					)
				))->sum($coinname . 'd');
			$aa = $trade_num + $trade_mum;

			if (config($coinname)['type'] == 'qbb') {
				$bb = $json['balance'];
			}
			else {
				$bb = 0;
			}

			$trade_fee_buy = Db::name('Myzr')->where(array(
				'name'    => $coinname,
				'addtime' => array(
					array('egt', $start),
					array('elt', $end)
					)
				))->sum('fee');
			$trade_fee_sell = Db::name('Myzc')->where(array(
				'name'    => $coinname,
				'addtime' => array(
					array('egt', $start),
					array('elt', $end)
					)
				))->sum('fee');
			$d = array($aa, $bb, $trade_fee_buy, $trade_fee_sell);

			if (Db::name('CoinJson')->where('name', $coinname)->where('addtime', $end)->find()) {
				Db::name('CoinJson')->where('name', $coinname)->where('addtime', $end)->update(['data' => json_encode($d)]);
			}
			else {
				Db::name('CoinJson')->insert(['name' => $coinname, 'data' => json_encode($d), 'addtime' => $end]);
			}
		}

		$tradeJson = Db::name('CoinJson')->where('name', $coinname)->order('id asc')->limit(100)->select();

		foreach ($tradeJson as $k => $v) {
			if ((addtime($v['addtime']) != '---') && (14634049 < $v['addtime'])) {
				$date = addtime($v['addtime'], 'Y-m-d H:i:s');
				$json_data = json_decode($v['data'], true);
				$cztx[] = array('date' => $date, 'num' => $json_data[0], 'mum' => $json_data[1], 'fee_buy' => $json_data[2], 'fee_sell' => $json_data[3]);
			}
		}

		$this->assign('cztx', json_encode($cztx));
		return $this->fetch();
	}

	public function coinSet($coinname = NULL)
	{
		if (!$coinname) {
			$this->error('参数错误！');
		}

		if (Db::name('CoinJson')->where('name', $coinname)->delete()) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function market($market = NULL)
	{
		if (!$market) {
			$market = config('market_mr');
		}

		if (!$market) {
			echo '请去设置--其他设置里面设置默认市场';
			exit();
		}

		$market = trim($market);
		$xnb = explode('_', $market)[0];
		$rmb = explode('_', $market)[1];
		$this->assign('xnb', $xnb);
		$this->assign('rmb', $rmb);
		$this->assign('market', $market);
		$data = array();
		$data['trance_num'] = Db::name('TradeLog')->where('market', $market)->sum('num');
		$data['trance_buyfee'] = Db::name('TradeLog')->where('market', $market)->sum('fee_buy');
		$data['trance_sellfee'] = Db::name('TradeLog')->where('market', $market)->sum('fee_sell');
		$data['trance_fee'] = $data['trance_buyfee'] + $data['trance_sellfee'];
		$data['trance_mum'] = Db::name('TradeLog')->where('market', $market)->sum('mum');
		$data['trance_ci'] = Db::name('TradeLog')->where('market', $market)->count();
		$market_json = Db::name('MarketJson')->where('name', $market)->order('id desc')->find();

		if ($market_json) {
			$addtime = $market_json['addtime'] + 60;
		}
		else {
			$addtime = Db::name('TradeLog')->where('market', $market)->order('addtime asc')->find()['addtime'];
		}

		if (!$addtime) {
			$addtime = time();
		}

		if ($addtime) {
			if ($addtime < (time() + (60 * 60 * 24))) {
				$t = $addtime;
				$start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
				$end = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));
				$trade_num = Db::name('TradeLog')->where(array(
					'market'  => $market,
					'addtime' => array(
						array('egt', $start),
						array('elt', $end)
						)
					))->sum('num');

				if ($trade_num) {
					$trade_mum = Db::name('TradeLog')->where(array(
						'market'  => $market,
						'addtime' => array(
							array('egt', $start),
							array('elt', $end)
							)
						))->sum('mum');
					$trade_fee_buy = Db::name('TradeLog')->where(array(
						'market'  => $market,
						'addtime' => array(
							array('egt', $start),
							array('elt', $end)
							)
						))->sum('fee_buy');
					$trade_fee_sell = Db::name('TradeLog')->where(array(
						'market'  => $market,
						'addtime' => array(
							array('egt', $start),
							array('elt', $end)
							)
						))->sum('fee_sell');
					$d = array($trade_num, $trade_mum, $trade_fee_buy, $trade_fee_sell);

					if (Db::name('MarketJson')->where('name', $market)->where('addtime', $end)->find()) {
						Db::name('MarketJson')->where('name', $market)->where('addtime', $end)->update(['data' => json_encode($d)]);
					}
					else {
						Db::name('MarketJson')->insert(['name' => $market, 'data' => json_encode($d), 'addtime' => $end]);
					}
				}
				else {
					$d = null;

					if (Db::name('MarketJson')->where('name', $market)->where('data', '')->find()) {
						Db::name('MarketJson')->where('name', $market)->where('data', '')->update(['addtime' => $end]);
					}
					else {
						Db::name('MarketJson')->insert(['name' => $market, 'data' => '', 'addtime' => $end]);
					}
				}
			}
		}

		$tradeJson = Db::name('MarketJson')->where('name', $market)->order('id asc')->limit(100)->select();

		foreach ($tradeJson as $k => $v) {
			if ((addtime($v['addtime']) != '---') && (14634049 < $v['addtime'])) {
				$date = addtime($v['addtime'] - (60 * 60 * 24), 'Y-m-d H:i:s');
				$json_data = json_decode($v['data'], true);
				$cztx[] = array('date' => $date, 'num' => $json_data[0], 'mum' => $json_data[1], 'fee_buy' => $json_data[2], 'fee_sell' => $json_data[3]);
			}
		}

		$this->assign('cztx', json_encode($cztx));
		$this->assign('data', $data);
		return $this->fetch();
	}

	public function marketSet($market = NULL)
	{
		if (!$market) {
			$this->error('参数错误！');
		}

		if (Db::name('MarketJson')->where('name', $market)->delete()) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}
}

?>