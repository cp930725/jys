<?php

namespace Home\Controller;

class TradeController extends HomeController
{
	public function index($market = NULL)
	{
		// 过滤非法字符----------------S
		if (checkstr($market)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E
		
		if (!userid()) {}
		if (!$market) {
			$market = config('market_mr');
		}
		
		$mo = db();
		$buy = Db::query('select price from tw_trade where status=0 and type=1 and market =\'' . $market . '\' order by price desc limit 1;');
		$sell = Db::query('select price from tw_trade where status=0 and type=2 and market =\'' . $market . '\' order by price asc limit 1;');

		$market_info = Db::name('market')->where('name', $market)->find();

		if (empty($buy[0]['price'])) {
			$bbb = Db::name('TradeLog')->where('market', $market, 'status' => 1)->order('addtime desc')->find();
			$this->assign('buy', round($bbb['price'],$market_info['round']));
		} else {
			$this->assign('buy', round($buy[0]['price'],$market_info['round']));
		}
		if (empty($sell[0]['price'])) {
			$sss = Db::name('TradeLog')->where('market', $market, 'status' => 1)->order('addtime desc')->find();
			$this->assign('sell', round($sss['price'],$market_info['round']));
		} else {
			$this->assign('sell', round($sell[0]['price'],$market_info['round']));
		}

		// $this->assign('buy', number_format($buy[0]['price'],$market_info['round']));
		// $this->assign('sell', number_format($sell[0]['price'],$market_info['round']));

		$this->assign('market', $market);

		$this->assign('xnb', explode('_', $market)[0]);
		$this->assign('rmb', explode('_', $market)[1]);

		//最新成交记录
		$data = (config('app.develop') ? null : cache('getTradelog' . $market));
		if (!$data) {
			$tradeLog = Db::name('TradeLog')->where('status', 1, 'market' => $market)->order('id desc')->limit(20)->select();

			if ($tradeLog) {
				foreach ($tradeLog as $k => $v) {
					$data['tradelog'][$k]['addtime'] = date('m-d H:i:s', $v['addtime']);
					$data['tradelog'][$k]['type'] = $v['type'];
					$data['tradelog'][$k]['price'] = $v['price'] * 1;
					$data['tradelog'][$k]['num'] = round($v['num'], 6);
					$data['tradelog'][$k]['mum'] = round($v['mum'], 6);
				}

				cache('getTradelog' . $market, $data);
			}
		}

		if ($data['tradelog']) {
			$list = '<tr><th width="130px">'.L('成交时间').'</th><th width="130px">'.L('类型').'</th><th width="140px">'.L('成交价格').'</th><th width="230px">'.L('成交量').'</th><th>'.L('总额').'</th></tr>';
			foreach ($data['tradelog'] as $val) {
				if ($val['type']==1) {
					$list .= '<tr class="buy" title="'.L('以这个价格卖出').'" onclick="autotrust(this,\'buy\',2)"><td>'.$val['addtime'].'</td><td>'.L('买入').'</td><td>'.(intval($val['price']*100)/100).'</td><td>'.(intval($val['num']*100)/100).'</td><td>'.(intval($val['mum']*100)/100).'</td></tr>';
				} else {
					$list .= '<tr class="sell" title="'.L('以这个价格买入').'" onclick="autotrust(this,\'sell\',2)"><td>'.$val['addtime'].'</td><td>'.L('卖出').'</td><td>'.(intval($val['price']*100)/100).'</td><td>'.(intval($val['num']*100)/100).'</td><td>'.(intval($val['mum']*100)/100).'</td></tr>';
				}
			}
			$this->assign('orderlist',$list);
		}

		//右侧委托信息
		$trade_moshi = 1;
		$data_getDepth = (config('app.develop') ? null : cache('getDepth'));
		if (!$data_getDepth[$market][$trade_moshi]) {
			if ($trade_moshi == 1) {
				$limt = 12;
			}

			if (($trade_moshi == 3) || ($trade_moshi == 4)) {
				$limt = 25;
			}

			$mo = db();

			if ($trade_moshi == 1) {
				$sql = 'select id,price,sum(num-deal)as nums from tw_trade where status=0 and type=1 and market =\'' . $market . '\' group by price order by price desc limit ' . $limt . ';';
				// echo $sql;
				$buy = Db::query($sql);
				// echo "string";
				$sell = array_reverse(Db::query('select id,price,sum(num-deal)as nums from tw_trade where status=0 and type=2 and market =\'' . $market . '\' group by price order by price asc limit ' . $limt . ';'));

			}
			if ($trade_moshi == 3) {
				$buy = Db::query('select id,price,sum(num-deal)as nums from tw_trade where status=0 and type=1 and market =\'' . $market . '\' group by price order by price desc limit ' . $limt . ';');
				$sell = null;
			}
			if ($trade_moshi == 4) {
				$buy = null;
				$sell = array_reverse(Db::query('select id,price,sum(num-deal)as nums from tw_trade where status=0 and type=2 and market =\'' . $market . '\' group by price order by price asc limit ' . $limt . ';'));
			}

			if ($buy) {
				foreach ($buy as $k => $v) {
					$wtdata['depth']['buy'][$k] = array(floatval($v['price'] * 1), floatval($v['nums'] * 1));
				}
			} else {
				$wtdata['depth']['buy'] = '';
			}

			if ($sell) {
				foreach ($sell as $k => $v) {
					$wtdata['depth']['sell'][$k] = array(floatval($v['price'] * 1), floatval($v['nums'] * 1));
				}
			} else {
				$wtdata['depth']['sell'] = '';
			}

			$data_getDepth[$market][$trade_moshi] = $wtdata;
			cache('getDepth', $data_getDepth);
		} else {
			$wtdata = $data_getDepth[$market][$trade_moshi];
		}

		if($wtdata['depth']){
			$list = '';
			$sellk = count($wtdata['depth']['sell']);
			if ($wtdata['depth']['sell']) {
			  for ($i=0; $i<$sellk; $i++) {
				$list .= '<tr class="sell" title="'.L('以这个价格买入').'" onclick="autotrust(this,\'sell\',1)"><td>'.L('卖').'('.($sellk-$i).')</td><td>'.(intval($wtdata['depth']['sell'][$i][0]*10000)/10000).'</td><td>'.(intval($wtdata['depth']['sell'][$i][1]*10000)/10000).'</td><td>'.(intval($wtdata['depth']['sell'][$i][0]*$wtdata['depth']['sell'][$i][1]*10000)/10000).'</td></tr>';
			  }
			}
			$this->assign('selllist',$list);

			$list = '';
			if ($wtdata['depth']['buy']) {
				for ($i=0; $i<count($wtdata['depth']['buy']); $i++) {
					$list .= '<tr class="buy" title="'.L('以这个价格买入').'" onclick="autotrust(this,\'sell\',1)"><td>'.L('买').'('.($i+1).')</td><td>'.(intval($wtdata['depth']['buy'][$i][0]*100)/100).'</td><td>'.(intval($wtdata['depth']['buy'][$i][1]*10000)/10000).'</td><td>'.(intval($wtdata['depth']['buy'][$i][0]*$wtdata['depth']['buy'][$i][1]*10000)/10000).'</td></tr>';
				}
			}
			$this->assign('buylist',$list);
		}


		//顶部价格信息
		$topdata = (config('app.develop') ? null : cache('getJsonTop' . $market));

		if (!$topdata) {
			if ($market) {
				$xnb = explode('_', $market)[0];
				$rmb = explode('_', $market)[1];

				foreach (cache('market') as $k => $v) {
					$v['xnb'] = explode('_', $v['name'])[0];
					$v['rmb'] = explode('_', $v['name'])[1];
					$topdata['list'][$k]['name'] = $v['name'];
					$topdata['list'][$k]['img'] = $v['xnbimg'];
					$topdata['list'][$k]['title'] = $v['title'];
					$topdata['list'][$k]['new_price'] = $v['new_price'];
				}

				$topdata['info']['img'] = config('market')[$market]['xnbimg'];
				$topdata['info']['title'] = config('market')[$market]['title'];
				$topdata['info']['new_price'] = config('market')[$market]['new_price'];
				$topdata['info']['max_price'] = config('market')[$market]['max_price'];
				$topdata['info']['min_price'] = config('market')[$market]['min_price'];
				$topdata['info']['buy_price'] = config('market')[$market]['buy_price'];
				$topdata['info']['sell_price'] = config('market')[$market]['sell_price'];
				$topdata['info']['volume'] = config('market')[$market]['volume'];
				$topdata['info']['change'] = config('market')[$market]['change'];
				cache('getJsonTop' . $market, $topdata);
			}
		}

		if ($topdata) {
			if ($topdata['info']['new_price']) {
				$this->assign('market_new_price',$topdata['info']['new_price']);
			}
			if ($topdata['info']['buy_price']) {
				$this->assign('market_buy_price',$topdata['info']['buy_price']);
				$this->assign('sell_best_price',$topdata['info']['buy_price']);
			}
			if ($topdata['info']['sell_price']) {
				$this->assign('market_sell_price',$topdata['info']['sell_price']);
				$this->assign('buy_best_price',$topdata['info']['sell_price']);
			}
			if ($topdata['info']['max_price']) {
				$this->assign('market_max_price',$topdata['info']['max_price']);
			}
			if ($topdata['info']['min_price']) {
				$this->assign('market_min_price',$topdata['info']['min_price']);
			}
			if ($topdata['info']['volume']) {
				if ($topdata['info']['volume'] > 10000) {
					$topdata['info']['volume'] = (intval($topdata['info']['volume'] / 10000*100)/100) . "万";
				}
				if ($topdata['info']['volume'] > 100000000) {
					$topdata['info']['volume'] = (intval($topdata['info']['volume'] / 100000000*100)/100) . "亿";
				}
				$this->assign('market_volume',$topdata['info']['volume']);
			}
			if ($topdata['info']['change']) {
                $fir=substr($topdata['info']['change'],0,1);
                if($fir == '-'){
                	$market_change=$topdata['info']['change'];
            }else{
            $market_change='+'.$topdata['info']['change'];
        }
				$this->assign('market_change',$market_change . "%");
				// $this->assign('market_change',$topdata['info']['change'] . "%");
			}
		}

		$hou_price = config('market')[$market]['hou_price'];

		if ($hou_price) {

			if (cache('market')[$market]['zhang']) {

				$zhang_price = round(($hou_price / 100) * (100 + config('market')[$market]['zhang']), config('market')[$market]['round']);

			}

			if (cache('market')[$market]['die']) {

				$die_price = round(($hou_price / 100) * (100 - config('market')[$market]['die']), config('market')[$market]['round']);

			}

		}

		$this->assign('zhang_price',$zhang_price);
		$this->assign('die_price',$die_price);

		return $this->fetch();

	}

	public function specialty($market = NULL)

	{


		// 过滤非法字符----------------S

		if (checkstr($market)) {
			$this->error(lang('您输入的信息有误！'));
		}

		// 过滤非法字符----------------E

		if (!$market) {

			$market = config('market_mr');

		}



		$this->assign('market', $market);

		return $this->fetch();

	}


	public function info($market = NULL)

	{

		// 过滤非法字符----------------S

		if (checkstr($market)) {
			$this->error(lang('您输入的信息有误！'));
		}

		// 过滤非法字符----------------E

		if (!userid()) {

		}

		if (!$market) {

			$market = config('market_mr');

		}



		$this->assign('market', $market);

		$this->assign('xnb', explode('_', $market)[0]);

		$this->assign('rmb', explode('_', $market)[1]);

		return $this->fetch();

	}

	public function comment($market = NULL)
	{
		// 过滤非法字符----------------S
		if (checkstr($market)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E

		if (!userid()) {}

		if (!$market) {
			$market = config('market_mr');
		}

		if (!$market) {
			$market = config('market_mr');
		}

		// TODO: SEPARATE
		// TODO: SEPARATE

		$this->assign('market', $market);
		$this->assign('xnb', explode('_', $market)[0]);
		$this->assign('rmb', explode('_', $market)[1]);
		
		$where['coinname'] = explode('_', $market)[0];

		$Mobile = Db::name('CoinComment');

		$count = $Mobile->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();

		$list = $Mobile->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = Db::name('User')->where('id', $v['userid'])->value('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function upTrade($paypassword = NULL, $market = NULL, $price, $num, $type)
	{
		// 过滤非法字符----------------S
		if (checkstr($paypassword) || checkstr($market) || checkstr($price) || checkstr($num) || checkstr($type)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E

		if (!userid()) {
			$this->error(lang('请先登录！'));
		}
		$xnb = explode('_', $market)[0];
		$rmb = explode('_', $market)[1];
		// 处理开盘闭盘交易时间===开始
		$times = date('G',time());
		$minute = date('i',time());
		$minute = intval($minute);
		if (($times <= config('market')[$market]['start_time'] && $minute< intval(cache('market')[$market]['start_minute']))|| ( $times > config('market')[$market]['stop_time'] && $minute>= intval(cache('market')[$market]['stop_minute'] ))) {
			$this->error(lang('该时间为闭盘时间！'));
		}
		if (($times <config('market')[$market]['start_time'] )|| $times > config('market')[$market]['stop_time']) {
			$this->error(lang('该时间为闭盘时间！'));
		} else {
			if ($times == config('market')[$market]['start_time']) {
				if ($minute< intval(cache('market')[$market]['start_minute'])) {
					$this->error(lang('该时间为闭盘时间！'));
				}
			} elseif($times == config('market')[$market]['stop_time']) {
				if (( $minute > config('market')[$market]['stop_minute'])) {
					$this->error(lang('该时间为闭盘时间！'));
				}
			}
		}
		// 处理周六周日是否可交易===开始
		$weeks = date('N',time());
		if(!config('market')[$market]['agree6']){
			if($weeks == 6){
				$this->error(lang('您好，周六为闭盘时间！'));
			}
		}
		if(!config('market')[$market]['agree7']){
			if($weeks == 7){
				$this->error(lang('您好，周日为闭盘时间！'));
			}
		}
		//处理周六周日是否可交易===结束
		if (!check($price, 'double')) {
			$this->error(lang('交易价格格式错误'));
		}
		if (!check($num, 'double')) {
			$this->error(lang('交易数量格式错误'));
		}
		if (($type != 1) && ($type != 2)) {
			$this->error(lang('交易类型格式错误'));
		}
		
		if ($type == 1) {
			if (!$num) {
				$nnn_coin = explode('_', $market);
				$nnn_coin = strtoupper($nnn_coin[0]);
				$this->error(lang('单笔买入最小交易数量为：').C('market')[$market]['trade_buy_num_min'].' '.$nnn_coin.'!');
			}
			if ($num<config('market')[$market]['trade_buy_num_min']) {
				$nnn_coin = explode('_', $market);
				$nnn_coin = strtoupper($nnn_coin[0]);
				$this->error(lang('单笔买入最小交易数量为：').C('market')[$market]['trade_buy_num_min'].' '.$nnn_coin.'!');
			}
			if ($num>C('market')[$market]['trade_buy_num_max']) {
				$nnn_coin = explode('_', $market);
				$nnn_coin = strtoupper($nnn_coin[0]);
				$this->error(lang('单笔买入最大交易数量为：').C('market')[$market]['trade_buy_num_max'].' '.$nnn_coin.'!');
			}
		}
		if ($type == 2) {
			if (!$num) {
				$nnn_coin = explode('_', $market);
				$nnn_coin = strtoupper($nnn_coin[0]);
				$this->error(lang('单笔卖出最小交易数量为：').C('market')[$market]['trade_sell_num_min'].' '.$nnn_coin.'!');
			}
			if ($num<config('market')[$market]['trade_sell_num_min']) {
				$nnn_coin = explode('_', $market);
				$nnn_coin = strtoupper($nnn_coin[0]);
				$this->error(lang('单笔卖出最小交易数量为：').C('market')[$market]['trade_sell_num_min'].' '.$nnn_coin.'!');
			}
			if ($num>C('market')[$market]['trade_sell_num_max']) {
				$nnn_coin = explode('_', $market);
				$nnn_coin = strtoupper($nnn_coin[0]);
				$this->error(lang('单笔卖出最大交易数量为：').C('market')[$market]['trade_sell_num_max'].' '.$nnn_coin.'!');
			}
		}

		$user = Db::name('User')->where('id', userid())->find();
		if (!session(userid() . 'tpwdsetting')) {
			if (md5($paypassword) != $user['paypassword']) {
				$this->error(lang('交易密码错误！'));
			} else {
				session(userid() . 'tpwdsetting', 1);
			}
		}

		if (!config('market')[$market]) {
			$this->error(lang('交易市场错误'));
		} else {
			$xnb = explode('_', $market)[0];
			$rmb = explode('_', $market)[1];
		}

		if (!config('market')[$market]['trade']) {
			$this->error(lang('当前市场禁止交易'));
		}

		$price = round(floatval($price), config('market')[$market]['round']);
		if (!$price) {
			$this->error(lang('交易价格错误') . $price);
		}

		// $num = round($num, 8 - config('market')[$market]['round']);20171031
		$num = round($num,  config('market')[$market]['round']);
		if (!check($num, 'double')) {
			$this->error(lang('交易数量错误'));
		}

		if ($type == 1) {
			$min_price = (cache('market')[$market]['buy_min'] ? config('market')[$market]['buy_min'] : 1.0E-8);
			$max_price = (cache('market')[$market]['buy_max'] ? config('market')[$market]['buy_max'] : 10000000);
		} else if ($type == 2) {
			$min_price = (cache('market')[$market]['sell_min'] ? config('market')[$market]['sell_min'] : 1.0E-8);
			$max_price = (cache('market')[$market]['sell_max'] ? config('market')[$market]['sell_max'] : 10000000);
		} else {
			$this->error(lang('交易类型错误'));
		}

		if ($max_price < $price) {
			$this->error(lang('交易价格超过今日涨幅限制！'));
		}
		if ($price < $min_price) {
			$this->error(lang('交易价格超过今日跌幅限制！'));
		}

		$hou_price = config('market')[$market]['hou_price'];
		if ($hou_price) {
			if (cache('market')[$market]['zhang']) {
				$zhang_price = round(($hou_price / 100) * (100 + config('market')[$market]['zhang']), config('market')[$market]['round']);
				if ($zhang_price < $price) {
					$this->error(lang('交易价格超过今日涨幅限制！'));
				}
			}

			if (cache('market')[$market]['die']) {
				$die_price = round(($hou_price / 100) * (100 - config('market')[$market]['die']), config('market')[$market]['round']);
				if ($price < $die_price) {
					$this->error(lang('交易价格超过今日跌幅限制！'));
				}
			}
		}

		$user_coin = Db::name('UserCoin')->where('userid', userid())->find();
		if ($type == 1) {
			$trade_fee = config('market')[$market]['fee_buy'];
			if ($trade_fee) {
				$fee = round((($num * $price) / 100) * $trade_fee, 8);
				$mum = round((($num * $price) / 100) * (100 + $trade_fee), 8);
			} else {
				$fee = 0;
				$mum = round($num * $price, 8);
			}
			
			if ($user_coin[$rmb] < $mum) {
				$this->error(cache('coin')[$rmb]['title'] . lang('余额不足！'));
			}

		} else if ($type == 2) {
			$trade_fee = config('market')[$market]['fee_sell'];
			if ($trade_fee) {
				$fee = round((($num * $price) / 100) * $trade_fee, 8);
				$mum = round((($num * $price) / 100) * (100 - $trade_fee), 8);
			} else {
				$fee = 0;
				$mum = round($num * $price, 8);
			}

			if ($user_coin[$xnb] < $num) {
				$this->error(cache('coin')[$xnb]['title'] . lang('余额不足！'));
			}
		} else {
			$this->error(lang('交易类型错误'));
		}

		if (cache('market')[$market]['trade_min']) {
			if ($mum < config('market')[$market]['trade_min']) {
				$this->error(lang('交易总额不能小于') . config('market')[$market]['trade_min']);
			}
		}

		if (cache('market')[$market]['trade_max']) {
			if (cache('market')[$market]['trade_max'] < $mum) {
				$this->error(lang('交易总额不能大于') . config('market')[$market]['trade_max']);
			}
		}

		if (!$rmb) {
			$this->error(lang('数据错误101'));
		}
		if (!$xnb) {
			$this->error(lang('数据错误102'));
		}
		if (!$market) {
			$this->error(lang('数据错误103'));
		}
		if (!$price) {
			$this->error(lang('数据错误104'));
		}
		if (!$num) {
			$this->error(lang('数据错误105'));
		}
		if (!$mum) {
			$this->error(lang('数据错误106'));
		}
		if (!$type) {
			$this->error(lang('数据错误107'));
		}

		// $this->error($price);
		try{
			$mo = db();
			Db::execute('set autocommit=0');
			// Db::execute('lock tables tw_trade write ,tw_user_coin write ,tw_finance write');
			Db::execute('lock tables tw_trade write ,tw_user_coin write ,tw_finance write,tw_finance_log write,tw_user write');//处理资金变更日志

			$rs = [];
			$user_coin = Db::table('tw_user_coin')->where('userid', userid())->find();

			if ($type == 1) {
				if ($user_coin[$rmb] < $mum) {
					throw new \Think\Exception(cache('coin')[$rmb]['title'] . lang('余额不足！'));
				}
				$finance = Db::table('tw_finance')->where('userid', userid())->order('id desc')->find();
				$finance_num_user_coin = Db::table('tw_user_coin')->where('userid', userid())->find();
				$rs[] = Db::table('tw_user_coin')->where('userid', userid())->setDec($rmb, $mum);
				$rs[] = Db::table('tw_user_coin')->where('userid', userid())->setInc($rmb . 'd', $mum);
				$rs[] = $finance_nameid = Db::table('tw_trade')->insert(array('userid' => userid(), 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 1, 'addtime' => time(), 'status' => 0));
				$finance_mum_user_coin = Db::table('tw_user_coin')->where('userid', userid())->find();
				$finance_hash = md5(userid() . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb.'d'] . $mum . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb.'d'] . MSCODE . 'tp3.net.cn');
				$finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb.'d'];

				// 处理资金变更日志-----------------S

				$user_n_info = Db::table('tw_user')->where('id', userid())->find();
				$rs[] = Db::table('tw_finance_log')->insert('username', $user_n_info['username'], 'adminname' => $user_n_info['username'], 'addtime' => time(), 'plusminus' => 0, 'amount' => $mum, 'optype' => 18, 'cointype' => 1, 'old_amount' => $finance_num_user_coin[$rmb], 'new_amount' => $finance_mum_user_coin[$rmb], 'userid' => userid(), 'adminid' => userid(),'addip'=>$this->request->ip(),'position'=>1));

				$rs[] = Db::table('tw_finance_log')->insert('username', $user_n_info['username'], 'adminname' => $user_n_info['username'], 'addtime' => time(), 'plusminus' => 1, 'amount' => $mum, 'optype' => 20, 'cointype' => 1, 'old_amount' => $finance_num_user_coin[$rmb. 'd'], 'new_amount' => $finance_mum_user_coin[$rmb. 'd'], 'userid' => userid(), 'adminid' => userid(),'addip'=>$this->request->ip(),'position'=>1));

				// 处理资金变更日志-----------------E

				if ($finance['mum'] < $finance_num) {
					$finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
				} else {

					$finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);

				}

				$rs[] = Db::table('tw_finance')->insert(array('userid' => userid(), 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb.'d'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb.'d'], 'fee' => $mum, 'type' => 2, 'name' => 'trade', 'nameid' => $finance_nameid, 'remark' => lang('交易中心-委托买入-市场') . $market, 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb.'d'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb.'d'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
			} else if ($type == 2) {

				if ($user_coin[$xnb] < $num) {
					throw new \Think\Exception(cache('coin')[$xnb]['title'] . lang('余额不足！'));
				}

				$fin_user_coin = Db::table('tw_user_coin')->where('userid', userid())->find();//处理资金变更日志
				$rs[] = Db::table('tw_user_coin')->where('userid', userid())->setDec($xnb, $num);
				$rs[] = Db::table('tw_user_coin')->where('userid', userid())->setInc($xnb . 'd', $num);
				$rs[] = Db::table('tw_trade')->insert(array('userid' => userid(), 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 2, 'addtime' => time(), 'status' => 0));
				$fin_user_coin_new = Db::table('tw_user_coin')->where('userid', userid())->find();//处理资金变更日志

				// 处理资金变更日志-----------------S

				switch ($xnb) {
					case 'hyjf':
						$cointype = 2;//汇云品种类型2
						break;
					default:
						$cointype = 3;//其他币种类型3
						break;
				}

				$user_n_info = Db::table('tw_user')->where('id', userid())->find();

				$rs[] = Db::table('tw_finance_log')->insert('username', $user_n_info['username'], 'adminname' => $user_n_info['username'], 'addtime' => time(), 'plusminus' => 0, 'amount' => $num, 'optype' => 19, 'cointype' => $cointype, 'old_amount' => $fin_user_coin[$xnb], 'new_amount' => $fin_user_coin_new[$xnb], 'userid' => userid(), 'adminid' => userid(),'addip'=>$this->request->ip(),'position'=>1));

				$rs[] = Db::table('tw_finance_log')->insert('username', $user_n_info['username'], 'adminname' => $user_n_info['username'], 'addtime' => time(), 'plusminus' => 1, 'amount' => $num, 'optype' => 21, 'cointype' => $cointype, 'old_amount' => $fin_user_coin[$xnb. 'd'], 'new_amount' => $fin_user_coin_new[$xnb. 'd'], 'userid' => userid(), 'adminid' => userid(),'addip'=>$this->request->ip(),'position'=>1));

				// 处理资金变更日志-----------------E
			} else {
				throw new \Think\Exception(lang('交易类型错误'));
			}

			if (check_arr($rs)) {
				Db::execute('commit');
				Db::execute('unlock tables');
			} else {
				throw new \Think\Exception(lang('交易失败！'));
			}
		}catch(\Think\Exception $e){

			Db::execute('rollback');
			Db::execute('unlock tables');
			$this->error(lang('交易失败！'));
		}

		cache('getDepth', null);
		// $this->matchingTradeall($market);//匹配玩家和虚拟交易
		// $this->matchingTrade($market);//只匹配玩家之间
		// $this->success(lang('交易成功！'));
		//jhsoft即时处理交易状态和异常处理
		A('Queue')->checkDapan();//匹配所有订单交易

		//jhsoft对当前交易订单处理开始
		$corderid=$rs[2];
		$mo = db();
		Db::execute('set autocommit=0');
		Db::execute('lock tables tw_trade write');
		$cTrade = Db::name('Trade')->where('id ='.$corderid)->find();
		if($cTrade)
		{
		    $cstatus=$cTrade['status'];
		    $cdeal=$cTrade['deal'];
		    $cnum=$cTrade['num'];
		    if($cdeal>$cnum)
		    {
		        Db::table('tw_trade')->where('id', $corderid)->update(array('deal' => Num($cnum),'status' => 1));
		        Db::execute('commit');
		        Db::execute('unlock tables');
		        $cstatus=1;
		        $cdeal=$cnum;
		    }

		    if($cstatus==1)
		    {
		        $this->success(lang('交易成功！'));
		    }

		    if($cstatus==0)
		    {
		        if($cdeal>0)
		        {
		            // $this->success(lang('已成功交易'.$cdeal.',余下'.($cnum-$cdeal).'自动转为委托交易中...！'));
		             $this->success(lang('交易成功！'));
		        } else {
		            // $this->success('已自动委托交易中...！');
		             $this->success(lang('交易成功！'));
		        }
		    }
		} else {
		    $this->success(lang('交易成功！'));
		}
		//jhsoft对当前交易订单处理结束
	}

	public function matchingTrade($market = NULL)
	{
		if (!$market) {
			return false;
		} else {
			$xnb = explode('_', $market)[0];
			$rmb = explode('_', $market)[1];
		}

		$fee_buy = config('market')[$market]['fee_buy'];
		$fee_sell = config('market')[$market]['fee_sell'];
		$invit_buy = config('market')[$market]['invit_buy'];
		$invit_sell = config('market')[$market]['invit_sell'];
		$invit_1 = config('market')[$market]['invit_1'];
		$invit_2 = config('market')[$market]['invit_2'];
		$invit_3 = config('market')[$market]['invit_3'];

		$mo = db();
		$new_trade_movesay = 0;

		for (; true; ) {

			$buy = Db::table('tw_trade')->where('market', $market, 'type' => 1, 'status' => 0)->order('price desc,id asc')->find();
			$sell = Db::table('tw_trade')->where('market', $market, 'type' => 2, 'status' => 0)->order('price asc,id asc')->find();

			if ($sell['id'] < $buy['id']) {
				$type = 1;//卖出在前,则是显示买入
			} else {
				$type = 2;//买入在前,则是显示卖出
			}
			
			if($sell['sort']==1 or $buy['sort']==1){
				$type=rand(1,2);
			}
			if ($buy && $sell && (0 <= floatval($buy['price']) - floatval($sell['price']))) {
				$rs = [];
				if ($buy['num'] <= $buy['deal']) {}
				if ($sell['num'] <= $sell['deal']) {}

				$amount = min(round($buy['num'] - $buy['deal'], 8 - config('market')[$market]['round']), round($sell['num'] - $sell['deal'], 8 - config('market')[$market]['round']));

				$amount = round($amount, 8 - config('market')[$market]['round']);
				if ($amount <= 0) {
					$log = lang('错误1交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . "\n";
					$log .= lang('ERR: 成交数量出错，数量是'). $amount;
					
					Db::name('Trade')->where('id', $buy['id'])->setField('status', 1);
					Db::name('Trade')->where('id', $sell['id'])->setField('status', 1);
					break;

				}

				if ($type == 1) {
					$price = $sell['price'];
				} else if ($type == 2) {
					$price = $buy['price'];
				} else {
					break;
				}

				if (!$price) {
					$log = lang('错误2交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . "\n";
					$log .= lang('ERR: 成交价格出错，价格是') . $price;
					break;
				} else {
					// TODO: SEPARATE
					$price = round($price, config('market')[$market]['round']);
				}

				$mum = round($price * $amount, 8);
				if (!$mum) {
					$log = lang('错误3交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . "\n";
					$log .= lang('ERR: 成交总额出错，总额是') . $mum;
					mlog($log);
					break;
				} else {
					$mum = round($mum, 8);
				}

				if ($fee_buy) {
					$buy_fee = round(($mum / 100) * $fee_buy, 8);
					$buy_save = round(($mum / 100) * (100 + $fee_buy), 8);
				} else {
					$buy_fee = 0;
					$buy_save = $mum;
				}

				if (!$buy_save) {
					$log = lang('错误4交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
					$log .= lang('ERR: 买家更新数量出错，更新数量是') . $buy_save;
					mlog($log);
					break;
				}

				if ($fee_sell) {
					$sell_fee = round(($mum / 100) * $fee_sell, 8);
					$sell_save = round(($mum / 100) * (100 - $fee_sell), 8);
				} else {
					$sell_fee = 0;
					$sell_save = $mum;
				}

				if (!$sell_save) {
					$log = lang('错误5交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
					$log .= lang('ERR: 卖家更新数量出错，更新数量是') . $sell_save;
					mlog($log);
					break;
				}

				$user_buy = Db::name('UserCoin')->where('userid', $buy['userid'])->find();
				if (!$user_buy[$rmb . 'd']) {
					$log = lang('错误6交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
					$log .= lang('ERR: 买家财产错误，冻结财产是') . $user_buy[$rmb . 'd'];
					mlog($log);
					break;
				}

				$user_sell = Db::name('UserCoin')->where('userid', $sell['userid'])->find();
				if (!$user_sell[$xnb . 'd']) {
					$log = lang('错误7交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
					$log .= lang('ERR: 卖家财产错误，冻结财产是') . $user_sell[$xnb . 'd'];
					mlog($log);
					break;
				}

				if ($user_buy[$rmb . 'd'] < 1.0E-8) {
					$log = lang('错误88交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
					$log .= lang('ERR: 买家更新冻结人民币出现错误,应该更新') . $buy_save . lang('账号余额') . $user_buy[$rmb . 'd'] . lang('进行错误处理');
					mlog($log);
					Db::name('Trade')->where('id', $buy['id'])->setField('status', 1);
					break;
				}

				if ($buy_save <= round($user_buy[$rmb . 'd'], 8)) {
					$save_buy_rmb = $buy_save;
				} else if ($buy_save <= round($user_buy[$rmb . 'd'], 8) + 1) {
					$save_buy_rmb = $user_buy[$rmb . 'd'];
					$log = lang('错误8交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
					$log .= lang('ERR: 买家更新冻结人民币出现误差,应该更新') . $buy_save . lang('账号余额') . $user_buy[$rmb . 'd'] . lang('实际更新') . $save_buy_rmb;
					mlog($log);
				} else {
					$log = lang('错误9交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
					$log .= lang('ERR: 买家更新冻结人民币出现错误,应该更新') . $buy_save . lang('账号余额') . $user_buy[$rmb . 'd'] . lang('进行错误处理');
					mlog($log);
					Db::name('Trade')->where('id', $buy['id'])->setField('status', 1);
					break;
				}

				// TODO: SEPARATE
				if ($amount <= round($user_sell[$xnb . 'd'], config('market')[$market]['round'])) {
					$save_sell_xnb = $amount;
				} else {
					// TODO: SEPARATE
					if ($amount <= round($user_sell[$xnb . 'd'], config('market')[$market]['round']) + 1) {
						$save_sell_xnb = $user_sell[$xnb . 'd'];
						$log = lang('错误10交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
						$log .= lang('ERR: 卖家更新数量出错错误,更新数量是') . $amount . lang('账号余额') . $user_sell[$xnb . 'd'] . lang('实际更新') . $save_sell_xnb;
						mlog($log);
					} else {
						$log = lang('错误11交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
						$log .= lang('ERR: 卖家更新冻结虚拟币出现错误,应该更新') . $amount . lang('账号余额') . $user_sell[$xnb . 'd'] . lang('进行错误处理');
						mlog($log);
						Db::name('Trade')->where('id', $sell['id'])->setField('status', 1);
						break;
					}
				}

				if (!$save_buy_rmb) {
					$log = lang('错误12交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
					$log .= lang('ERR: 卖家更新数量出错错误,更新数量是') . $save_buy_rmb;
					mlog($log);
					Db::name('Trade')->where('id', $buy['id'])->setField('status', 1);
					break;
				}

				if (!$save_sell_xnb) {
					$log = lang('错误13交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
					$log .= lang('ERR: 卖家更新数量出错错误,更新数量是') . $save_sell_xnb;
					mlog($log);
					Db::name('Trade')->where('id', $sell['id'])->setField('status', 1);
					break;
				}
				Db::execute('set autocommit=0');

				// Db::execute('lock tables tw_trade write ,tw_trade_log write ,tw_user write,tw_user_coin write,tw_invit write ,tw_finance write');
				Db::execute('lock tables tw_trade write ,tw_trade_log write ,tw_user write,tw_user_coin write,tw_invit write ,tw_finance write,tw_finance_log write'); //处理资金变更日志

				$rs[] = Db::table('tw_trade')->where('id', $buy['id'])->setInc('deal', $amount);
				$rs[] = Db::table('tw_trade')->where('id', $sell['id'])->setInc('deal', $amount);
				$rs[] = $finance_nameid = Db::table('tw_trade_log')->insert(array('userid' => $buy['userid'], 'peerid' => $sell['userid'], 'market' => $market, 'price' => $price, 'num' => $amount, 'mum' => $mum, 'type' => $type, 'fee_buy' => $buy_fee, 'fee_sell' => $sell_fee, 'addtime' => time(), 'status' => 1));//201707在time后面减去随机数,显示成交时间为随机
				$fin_2 = Db::table('tw_user_coin')->where('userid', $buy['userid'])->find();//处理资金变更日志
				$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setInc($xnb, $amount);
				$finance = Db::table('tw_finance')->where('userid', $buy['userid'])->order('id desc')->find();
				$finance_num_user_coin = Db::table('tw_user_coin')->where('userid', $buy['userid'])->find();

				// 处理资金变更日志--------买入类型---------S

				// 判断币种
				switch ($xnb) {
					case 'hyjf':
						$cointype = 2;//汇云品种类型2
						break;
					default:
						$cointype = 3;//其他币种类型3
						break;
				}

				// 获取用户信息
				$user_info = Db::table('tw_user')->where('id', $sell['userid'])->find();
				$user_2_info = Db::table('tw_user')->where('id', $buy['userid'])->find();

				$rs[] = Db::table('tw_finance_log')->insert('username', $user_2_info['username'], 'adminname' => $user_info['username'], 'addtime' => time(), 'plusminus' => 1, 'amount' => $amount, 'optype' => 10, 'cointype' => $cointype, 'old_amount' => $fin_2[$xnb], 'new_amount' => $finance_num_user_coin[$xnb], 'userid' => $user_2_info['id'], 'adminid' => $user_info['id'], 'addip'=>$this->request->ip(),'position'=>1));

				// 处理资金变更日志---------买入类型--------E

				$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setDec($rmb . 'd', $save_buy_rmb);
				$finance_mum_user_coin = Db::table('tw_user_coin')->where('userid', $buy['userid'])->find();

				// 处理资金变更日志-------买入类型----------S

				$rs[] = Db::table('tw_finance_log')->insert('username', $user_2_info['username'], 'adminname' => $user_info['username'], 'addtime' => time(), 'plusminus' => 0, 'amount' => $save_buy_rmb, 'optype' => 13, 'cointype' => 1, 'old_amount' => $fin_2[$rmb . 'd'], 'new_amount' => $finance_mum_user_coin[$rmb . 'd'], 'userid' => $user_2_info['id'], 'adminid' => $user_info['id'],'addip'=>$this->request->ip(),'position'=>1));

				// 处理资金变更日志-------买入类型----------E

				$finance_hash = md5($buy['userid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mum . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'tp3.net.cn');

				$finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];
				if ($finance['mum'] < $finance_num) {
					$finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
				} else {
					$finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
				}

				$rs[] = Db::table('tw_finance')->insert(array('userid' => $buy['userid'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $save_buy_rmb, 'type' => 2, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => lang('交易中心-成功买入-市场') . $market, 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

				$finance = Db::table('tw_finance')->where('userid', $buy['userid'])->order('id desc')->find();
				$finance_num_user_coin = Db::table('tw_user_coin')->where('userid', $sell['userid'])->find();
				$rs[] = Db::table('tw_user_coin')->where('userid', $sell['userid'])->setInc($rmb, $sell_save);
				$finance_mum_user_coin = Db::table('tw_user_coin')->where('userid', $sell['userid'])->find();

				// 处理资金变更日志-----------------S

				// 获取用户信息
				$user_s2_info = Db::table('tw_user')->where('id', $sell['userid'])->find();

				$rs[] = Db::table('tw_finance_log')->insert('username', $user_s2_info['username'], 'adminname' => $user_info['username'], 'addtime' => time(), 'plusminus' => 1, 'amount' => $sell_save, 'optype' => 11, 'cointype' => 1, 'old_amount' => $finance_num_user_coin[$rmb], 'new_amount' => $finance_mum_user_coin[$rmb], 'userid' => $user_s2_info['id'], 'adminid' => $user_info['id'],'addip'=>$this->request->ip(),'position'=>1));

				// 处理资金变更日志-----------------E

				$finance_hash = md5($sell['userid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mum . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'tp3.net.cn');
				$finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

				if ($finance['mum'] < $finance_num) {
					$finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
				} else {
					$finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
				}

				$rs[] = Db::table('tw_finance')->insert(array('userid' => $sell['userid'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => lang('交易中心-成功卖出-市场') . $market, 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

				$rs[] = Db::table('tw_user_coin')->where('userid', $sell['userid'])->setDec($xnb . 'd', $save_sell_xnb);
				$fin_s_coin = Db::table('tw_user_coin')->where('userid', $sell['userid'])->find();

				// 处理资金变更日志-----------------S

				$rs[] = Db::table('tw_finance_log')->insert('username', $user_2_info['username'], 'adminname' => $user_info['username'], 'addtime' => time(), 'plusminus' => 0, 'amount' => $save_sell_xnb, 'optype' => 14, 'cointype' => $cointype, 'old_amount' => $fin_2[$xnb . 'd'], 'new_amount' => $fin_s_coin[$xnb . 'd'], 'userid' => $user_2_info['id'], 'adminid' => $user_info['id'],'addip'=>$this->request->ip(),'position'=>1));

				// 处理资金变更日志-----------------E

				$buy_list = Db::table('tw_trade')->where('id', $buy['id'], 'status' => 0)->find();
				if ($buy_list) {
					if ($buy_list['num'] <= $buy_list['deal']) {
						$rs[] = Db::table('tw_trade')->where('id', $buy['id'])->setField('status', 1);
					}
				}

				$sell_list = Db::table('tw_trade')->where('id', $sell['id'], 'status' => 0)->find();
				if ($sell_list) {
					if ($sell_list['num'] <= $sell_list['deal']) {
						$rs[] = Db::table('tw_trade')->where('id', $sell['id'])->setField('status', 1);
					}
				}

				if ($price < $buy['price']) {
					$chajia_dong = round((($amount * $buy['price']) / 100) * (100 + $fee_buy), 8);
					$chajia_shiji = round((($amount * $price) / 100) * (100 + $fee_buy), 8);
					$chajia = round($chajia_dong - $chajia_shiji, 8);

					if ($chajia) {
						$chajia_user_buy = Db::table('tw_user_coin')->where('userid', $buy['userid'])->find();

						if ($chajia <= round($chajia_user_buy[$rmb . 'd'], 8)) {
							$chajia_save_buy_rmb = $chajia;
						} else if ($chajia <= round($chajia_user_buy[$rmb . 'd'], 8) + 1) {

							$chajia_save_buy_rmb = $chajia_user_buy[$rmb . 'd'];

							mlog(lang('错误91交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount, lang('成交价格') . $price . lang('成交总额') . $mum . "\n");

							mlog(lang('交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('成交数量') . $amount . lang('交易方式：') . $type . lang('卖家更新冻结虚拟币出现错误,应该更新') . $chajia . lang('账号余额') . $chajia_user_buy[$rmb . 'd'] . lang('实际更新') . $chajia_save_buy_rmb);
						} else {
							mlog(lang('错误92交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount, lang('成交价格') . $price . lang('成交总额') . $mum . "\n");

							mlog(lang('交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('成交数量') . $amount . lang('交易方式：') . $type . lang('卖家更新冻结虚拟币出现错误,应该更新') . $chajia . lang('账号余额') . $chajia_user_buy[$rmb . 'd'] . lang('进行错误处理'));

							Db::execute('rollback');
							Db::execute('unlock tables');
							Db::name('Trade')->where('id', $buy['id'])->setField('status', 1);
							Db::name('Trade')->execute('commit');
							break;

						}

						if ($chajia_save_buy_rmb) {

							$fin_b2_coin = Db::table('tw_user_coin')->where('userid', $buy['userid'])->find();// 处理资金变更日志

							$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setDec($rmb . 'd', $chajia_save_buy_rmb);
							$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setInc($rmb, $chajia_save_buy_rmb);
							$fin_b1_coin = Db::table('tw_user_coin')->where('userid', $buy['userid'])->find();// 处理资金变更日志

							// 处理资金变更日志-----------------S

							// 人民币-买入差价可用
							$rs[] = Db::table('tw_finance_log')->insert('username', $user_2_info['username'], 'adminname' => $user_info['username'], 'addtime' => time(), 'plusminus' => 1, 'amount' => $chajia_save_buy_rmb, 'optype' => 12, 'cointype' => 1, 'old_amount' => $fin_b2_coin[$rmb], 'new_amount' => $fin_b1_coin[$rmb], 'userid' => $user_2_info['id'], 'adminid' => $user_info['id'],'addip'=>$this->request->ip(),'position'=>1));

							// 人民币-买入差价冻结
							$rs[] = Db::table('tw_finance_log')->insert('username', $user_2_info['username'], 'adminname' => $user_info['username'], 'addtime' => time(), 'plusminus' => 0, 'amount' => $chajia_save_buy_rmb, 'optype' => 22, 'cointype' => 1, 'old_amount' => $fin_b2_coin[$rmb . 'd'], 'new_amount' => $fin_b1_coin[$rmb . 'd'], 'userid' => $user_2_info['id'], 'adminid' => $user_info['id'],'addip'=>$this->request->ip(),'position'=>1));

							// 处理资金变更日志-----------------E
						}
					}
				}

				$you_buy = Db::table('tw_trade')->where(array(
					'status' => 0,
					'userid' => $buy['userid']
				)->find();

				$you_sell = Db::table('tw_trade')->where(array(
					'market' => array('eq', $market),
					'status' => 0,
					'userid' => $sell['userid']
				)->find();

				if (!$you_buy) {
					$you_user_buy = Db::table('tw_user_coin')->where('userid', $buy['userid'])->find();

					if (0 < $you_user_buy[$rmb . 'd']) {
						$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setField($rmb . 'd', 0);
						$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setInc($rmb, $you_user_buy[$rmb . 'd']);

						$fin_b3_coin = Db::table('tw_user_coin')->where('userid', $buy['userid'])->find();// 处理资金变更日志

						// 处理资金变更日志-----------------S

						$rs[] = Db::table('tw_finance_log')->insert('username', $user_2_info['username'], 'adminname' => lang('系统'), 'addtime' => time(), 'plusminus' => 0, 'amount' => $you_user_buy[$rmb . 'd'], 'optype' => 15, 'cointype' => 1, 'old_amount' => $you_user_buy[$rmb. 'd'], 'new_amount' => '0', 'userid' => $user_2_info['id'],'addip'=>$this->request->ip(),'position'=>1));

						$rs[] = Db::table('tw_finance_log')->insert('username', $user_2_info['username'], 'adminname' => lang('系统'), 'addtime' => time(), 'plusminus' => 1, 'amount' => $you_user_buy[$rmb . 'd'], 'optype' => 23, 'cointype' => 1, 'old_amount' => $you_user_buy[$rmb], 'new_amount' => $fin_b3_coin[$rmb], 'userid' => $user_2_info['id'],'addip'=>$this->request->ip(),'position'=>1));

						// 处理资金变更日志-----------------E
					}

				}
				if (!$you_sell) {
					$you_user_sell = Db::table('tw_user_coin')->where('userid', $sell['userid'])->find();
					if (0 < $you_user_sell[$xnb . 'd']) {
						$rs[] = Db::table('tw_user_coin')->where('userid', $sell['userid'])->setField($xnb . 'd', 0);
						// $rs[] = Db::table('tw_user_coin')->where('userid', $sell['userid'])->setInc($rmb, $you_user_sell[$xnb . 'd']);
						$rs[] = Db::table('tw_user_coin')->where('userid', $sell['userid'])->setInc($xnb, $you_user_sell[$xnb . 'd']);
						$fin_b4_coin = Db::table('tw_user_coin')->where('userid', $sell['userid'])->find();// 处理资金变更日志

						// 处理资金变更日志-----------------S

						// optype 动作类型 'cointype' => 1人民币类型 'plusminus' => 0减少类型
						$rs[] = Db::table('tw_finance_log')->insert('username', $user_s2_info['username'], 'adminname' => lang('系统'), 'addtime' => time(), 'plusminus' => 0, 'amount' => $you_user_sell[$xnb . 'd'], 'optype' => 15, 'cointype' => $cointype, 'old_amount' => $you_user_sell[$xnb. 'd'], 'new_amount' => '0', 'userid' => $user_s2_info['id'],'addip'=>$this->request->ip(),'position'=>1));

						$rs[] = Db::table('tw_finance_log')->insert('username', $user_s2_info['username'], 'adminname' => lang('系统'), 'addtime' => time(), 'plusminus' => 1, 'amount' => $you_user_sell[$xnb . 'd'], 'optype' => 23, 'cointype' => $cointype, 'old_amount' => $you_user_sell[$xnb], 'new_amount' => $fin_b4_coin[$xnb], 'userid' => $user_s2_info['id'],'addip'=>$this->request->ip(),'position'=>1));

						// 处理资金变更日志-----------------E
					}

				}
				$invit_buy_user = Db::table('tw_user')->where('id', $buy['userid'])->find();
				$invit_sell_user = Db::table('tw_user')->where('id', $sell['userid'])->find();
				$xnblx=DB::name('coin')->where('name', $xnb)->find();//交易的虚拟币类型
				if ($invit_buy) {

					if ($invit_1) {

						if ($buy_fee) {
							if ($invit_buy_user['invit_1']) {
								$invit_buy_save_1 = round(($buy_fee / 100) * $invit_1, 6);
								if ($invit_buy_save_1) {
									$rs[] = Db::table('tw_user_coin')->where('userid', $invit_buy_user['invit_1'])->setInc($rmb, $invit_buy_save_1);
									if($rmb=='cny'){
										$rmb='usd';
									}
									$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_buy_user['invit_1'], 'invit' => $buy['userid'], 'name' => lang('一代买入赠送'), 'type' => $xnblx['title'] . lang('买入交易赠送'), 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_1, 'addtime' => time(), 'status' => 1,'coin'=>strtoupper($rmb)));
								}
								/*
								//直系下属交易额奖励开始
								$invit_buy_save_1s = round(($buy_fee / 100) * $intval(cache('tui_jy_jl')), 6);
								if ($invit_buy_save_1s) {
									$rs[] = Db::table('tw_user_coin')->where('userid', $invit_buy_user['invit_1'])->setInc($rmb, $invit_buy_save_1s);

									$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_buy_user['invit_1'], 'invit' => $buy['userid'], 'name' => '直系下属奖励', 'type' => $market . '买入交易奖励', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_1s, 'addtime' => time(), 'status' => 1));

								}

								//直系下属交易额奖励结束
								*/
							}

							if ($invit_buy_user['invit_2']) {
								$invit_buy_save_2 = round(($buy_fee / 100) * $invit_2, 6);
								if ($invit_buy_save_2) {
									$rs[] = Db::table('tw_user_coin')->where('userid', $invit_buy_user['invit_2'])->setInc($rmb, $invit_buy_save_2);
									if ($rmb == 'cny') {
										$rmb = 'usd';
									}
									$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_buy_user['invit_2'], 'invit' => $buy['userid'], 'name' => lang('二代买入赠送'), 'type' => $xnblx['title'] . lang('买入交易赠送'), 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_2, 'addtime' => time(), 'status' => 1,'coin'=>strtoupper($rmb)));
								}

							}
							if ($invit_buy_user['invit_3']) {
								$invit_buy_save_3 = round(($buy_fee / 100) * $invit_3, 6);
								if ($invit_buy_save_3) {
									$rs[] = Db::table('tw_user_coin')->where('userid', $invit_buy_user['invit_3'])->setInc($rmb, $invit_buy_save_3);
									if ($rmb == 'cny') {
										$rmb = 'usd';
									}
									$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_buy_user['invit_3'], 'invit' => $buy['userid'], 'name' => lang('三代买入赠送'), 'type' => $xnblx['title'] . lang('买入交易赠送'), 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_3, 'addtime' => time(), 'status' => 1,'coin'=>strtoupper($rmb)));
								}
							}
						}
					}


					if ($invit_sell) {
						if ($sell_fee) {
							if ($invit_sell_user['invit_1']) {
								$invit_sell_save_1 = round(($sell_fee / 100) * $invit_1, 6);

								if ($invit_sell_save_1) {
									$rs[] = Db::table('tw_user_coin')->where('userid', $invit_sell_user['invit_1'])->setInc($rmb, $invit_sell_save_1);
									if($rmb=='cny'){
										$rmb='usd';
									}
									$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_sell_user['invit_1'], 'invit' => $sell['userid'], 'name' => lang('一代卖出赠送'), 'type' => $xnblx['title'] . lang('卖出交易赠送'), 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_1, 'addtime' => time(), 'status' => 1,'coin'=>strtoupper($rmb)));
								}

								//直系下属交易额奖励开始
								//$invit_sell_save_1s = round(($sell_fee / 100) * $intval(cache('tui_jy_jl')), 6);
								//if ($invit_sell_save_1s) {

								//	$rs[] = Db::table('tw_user_coin')->where('userid', $invit_sell_user['invit_1'])->setInc($rmb, $invit_sell_save_1s);

								//	$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_sell_user['invit_1'], 'invit' => $sell['userid'], 'name' => '直系下属奖励', 'type' => $market . '卖出交易奖励', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_1s, 'addtime' => time(), 'status' => 1));
								//}
								//直系下属交易额奖励结束
							}

							if ($invit_sell_user['invit_2']) {
								$invit_sell_save_2 = round(($sell_fee / 100) * $invit_2, 6);
								if ($invit_sell_save_2) {
									$rs[] = Db::table('tw_user_coin')->where('userid', $invit_sell_user['invit_2'])->setInc($rmb, $invit_sell_save_2);
									if($rmb=='cny'){
										$rmb='usd';
									}
									$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_sell_user['invit_2'], 'invit' => $sell['userid'], 'name' => lang('二代卖出赠送'), 'type' => $xnblx['title'] . lang('卖出交易赠送'), 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_2, 'addtime' => time(), 'status' => 1,'coin'=>strtoupper($rmb)));
								}
							}

							if ($invit_sell_user['invit_3']) {
								$invit_sell_save_3 = round(($sell_fee / 100) * $invit_3, 6);
								if ($invit_sell_save_3) {
									$rs[] = Db::table('tw_user_coin')->where('userid', $invit_sell_user['invit_3'])->setInc($rmb, $invit_sell_save_3);
									if($rmb=='cny'){
										$rmb='usd';
									}
									$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_sell_user['invit_3'], 'invit' => $sell['userid'], 'name' => lang('三代卖出赠送'), 'type' => $xnblx['title'] . lang('卖出交易赠送'), 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_3, 'addtime' => time(), 'status' => 1,'coin'=>strtoupper($rmb)));
								}
							}
						}
					}
				}

				if (check_arr($rs)) {
					Db::execute('commit');
					Db::execute('unlock tables');
					$new_trade_movesay = 1;
					$coin = $xnb;

					cache('allsum', null);
					cache('getJsonTop' . $market, null);
					cache('getTradelog' . $market, null);
					cache('getDepth' . $market . '1', null);
					cache('getDepth' . $market . '3', null);
					cache('getDepth' . $market . '4', null);
					cache('ChartgetJsonData' . $market, null);
					cache('allcoin', null);
					cache('trends', null);
				} else {
					Db::execute('rollback');
					Db::execute('unlock tables');
					break;
				}
			} else {
				break;
			}

			unset($rs);
		}

		if ($new_trade_movesay) {
			$new_price = round(Db::name('TradeLog')->where('market', $market, 'status' => 1)->order('id desc')->value('price'), 6);
			
			$buy_price = round(Db::name('Trade')->where(array('type' => 1, 'market' => $market, 'status' => 0)->max('price'), 6);
			if(empty($buy_price)){
				$buy_price = round(Db::name('TradeLog')->where(array('type' => 1, 'market' => $market, 'status' => 1)->max('price'), 6);
			}

			$sell_price = round(Db::name('Trade')->where(array('type' => 2, 'market' => $market, 'status' => 0)->min('price'), 6);
			if(empty($sell_price)){
				$sell_price = round(Db::name('TradeLog')->where(array('type' => 2, 'market' => $market, 'status' => 1)->min('price'), 6);
			}

			$min_price = round(Db::name('TradeLog')->where(array(
				'market'  => $market,
				'addtime' => array('gt', time() - (60 * 60 * 24))
			)->min('price'), 6);

			$max_price = round(Db::name('TradeLog')->where(array(
				'market'  => $market,
				'addtime' => array('gt', time() - (60 * 60 * 24))
			)->max('price'), 6);

			$volume = round(Db::name('TradeLog')->where(array(
				'market'  => $market,
				'addtime' => array('gt', time() - (60 * 60 * 24))
			)->sum('num'), 6);

			$sta_price = round(Db::name('TradeLog')->where(array(
				'market'  => $market,
				'status'  => 1,
				'addtime' => array('gt', time() - (60 * 60 * 24))
			)->order('id asc')->value('price'), 6);

			$Cmarket = Db::name('Market')->where('name', $market)->find();
			if ($Cmarket['new_price'] != $new_price) {
				$upCoinData['new_price'] = $new_price;
			}

			if ($Cmarket['buy_price'] != $buy_price) {
				$upCoinData['buy_price'] = $buy_price;
			}

			if ($Cmarket['sell_price'] != $sell_price) {
				$upCoinData['sell_price'] = $sell_price;
			}

			if ($Cmarket['min_price'] != $min_price) {
				$upCoinData['min_price'] = $min_price;
			}

			if ($Cmarket['max_price'] != $max_price) {
				$upCoinData['max_price'] = $max_price;
			}

			if ($Cmarket['volume'] != $volume) {
				$upCoinData['volume'] = $volume;
			}

			$change = round((($new_price - $Cmarket['hou_price']) / $Cmarket['hou_price']) * 100, 2);
			$upCoinData['change'] = $change;
			if ($upCoinData) {
				Db::name('Market')->where('name', $market)->update($upCoinData);
				Db::name('Market')->execute('commit');
				cache('home_market', null);
			}
		}
	}

	public function chexiao($id)
	{
		// 过滤非法字符----------------S
		if (checkstr($id)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E
		if (!userid()) {

			$this->error(lang('请先登录！'));

		}
		if (!check($id, 'd')) {
			$this->error(lang('请选择要撤销的委托！'));
		}
		$trade = Db::name('Trade')->where('id', $id)->find();
		if (!$trade) {
			$this->error(lang('撤销委托参数错误！'));
		}

		if ($trade['userid'] != userid()) {
			$this->error(lang('参数非法！'));
		}
		$this->show(D('Trade')->chexiao($id));

	}

	public function show($rs = array())
	{
		foreach ($rs as $k => $v) {
			// 过滤非法字符----------------S
			if (checkstr($v)) {
				$this->error(lang('您输入的信息有误！'));
			}
			// 过滤非法字符----------------E
		}
		if ($rs[0]) {
			$this->success($rs[1]);
		} else {
			$this->error($rs[1]);
		}
	}

	public function matchingAutoTrade($market = NULL)
	{
		if (!$market) {
			return false;
		} else {
			$xnb = explode('_', $market)[0];
			$rmb = explode('_', $market)[1];
		}

		$mo = db();
		$new_trade = 0;

		for (; true; ) {
			$buy = Db::table('tw_trade')->where('market', $market, 'type' => 1, 'userid'=>0,'status' => 0)->order('price desc,id asc')->find();
			$sell = Db::table('tw_trade')->where('market', $market, 'type' => 2, 'userid'=>0,'status' => 0)->order('price asc,id asc')->find();

			if ($sell['id'] < $buy['id']) {
				$type = 1;
			} else {
				$type = 2;
			}

			if ($buy && $sell && (0 <= floatval($buy['price']) - floatval($sell['price']))) {
				$rs = [];

				if ($buy['num'] <= $buy['deal']) {
				}
				if ($sell['num'] <= $sell['deal']) {
				}

				$amount = min(round($buy['num'] - $buy['deal'], 8 - config('market')[$market]['round']), round($sell['num'] - $sell['deal'], 8 - config('market')[$market]['round']));
				$amount = round($amount, 8 - config('market')[$market]['round']);

				if ($amount <= 0) {
					$log = lang('错误1交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . "\n";
					$log .= lang('成交数量出错，数量是') . $amount;
					Db::name('Trade')->where('id', $buy['id'])->setField('status', 1);
					Db::name('Trade')->where('id', $sell['id'])->setField('status', 1);
					break;
				}

				if ($type == 1) {
					$price = $sell['price'];
				} else if ($type == 2) {
					$price = $buy['price'];
				} else {
					break;
				}

				if (!$price) {
					$log = lang('错误2交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . "\n";
					$log .= lang('ERR: 成交价格出错，价格是') . $price;
					break;
				} else {
					// TODO: SEPARATE
					$price = round($price, 6);
				}

				$mum = round($price * $amount, 6);
				if (!$mum) {
					$log = lang('错误3交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . "\n";
					$log .= lang('ERR: 成交价格').$price.L('成交总额出错，总额是') . $mum;
					mlog($log);
					break;
				} else {
					$mum = round($mum, 6);
				}

				if ($fee_buy) {
					$buy_fee = round(($mum / 100) * $fee_buy, 6);
					$buy_save = round(($mum / 100) * (100 + $fee_buy), 6);
				} else {
					$buy_fee = 0;
					$buy_save = $mum;
				}

				if (!$buy_save) {
					$log = lang('错误4交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
					$log .= lang('ERR: 买家更新数量出错，更新数量是') . $buy_save;
					mlog($log);
					break;
				}

				if ($fee_sell) {
					$sell_fee = round(($mum / 100) * $fee_sell, 8);
					$sell_save = round(($mum / 100) * (100 - $fee_sell), 8);
				} else {
					$sell_fee = 0;
					$sell_save = $mum;
				}

				if (!$sell_save) {
					$log = lang('错误5交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
					$log .= lang('ERR: 卖家更新数量出错，更新数量是') . $sell_save;
					mlog($log);
					break;
				}


				Db::execute('set autocommit=0');
				Db::execute('lock tables tw_trade write ,tw_trade_log write ');

				$rs[] = Db::table('tw_trade')->where('id', $buy['id'])->setInc('deal', $amount);
				$rs[] = Db::table('tw_trade')->where('id', $sell['id'])->setInc('deal', $amount);

				$rs[] = Db::table('tw_trade_log')->insert(array('userid' => 0, 'peerid' => 0, 'market' => $market, 'price' => $price, 'num' => $amount, 'mum' => $mum, 'type' => $type, 'fee_buy' => 0, 'fee_sell' => 0, 'addtime' => time(), 'status' => 1));

				$buy_list = Db::table('tw_trade')->where('id', $buy['id'], 'status' => 0)->find();
				if ($buy_list) {
					if ($buy_list['num'] <= $buy_list['deal']) {
						$rs[] = Db::table('tw_trade')->where('id', $buy['id'])->setField('status', 1);
					}
				}

				$sell_list = Db::table('tw_trade')->where('id', $sell['id'], 'status' => 0)->find();
				if ($sell_list) {
					if ($sell_list['num'] <= $sell_list['deal']) {
						$rs[] = Db::table('tw_trade')->where('id', $sell['id'])->setField('status', 1);
					}
				}

				if (check_arr($rs)) {
					Db::execute('commit');
					Db::execute('unlock tables');
					$new_trade = 1;
					$coin = $xnb;
					cache('allsum', null);
					cache('getJsonTop' . $market, null);
					cache('getTradelog' . $market, null);
					cache('getDepth' . $market . '1', null);
					cache('getDepth' . $market . '3', null);
					cache('getDepth' . $market . '4', null);
					cache('ChartgetJsonData' . $market, null);
					cache('allcoin', null);
					cache('trends', null);
				} else {
					Db::execute('rollback');
					Db::execute('unlock tables');
				}
			} else {
				break;
			}

			unset($rs);
		}
		//$new_trade=1;
		if ($new_trade) {
			$new_price = round(Db::name('TradeLog')->where('market', $market, 'status' => 1)->order('id desc')->value('price'), 6);
			$buy_price = round(Db::name('Trade')->where(array('type' => 1, 'market' => $market, 'status' => 0)->max('price'), 6);
			$sell_price = round(Db::name('Trade')->where(array('type' => 2, 'market' => $market, 'status' => 0)->min('price'), 6);
			$min_price = round(Db::name('TradeLog')->where(array(
				'market'  => $market,
				'addtime' => array('gt', time() - (60 * 60 * 24))
				)->min('price'), 6);
			$max_price = round(Db::name('TradeLog')->where(array(
				'market'  => $market,
				'addtime' => array('gt', time() - (60 * 60 * 24))
				)->max('price'), 6);
			$volume = round(Db::name('TradeLog')->where(array(
				'market'  => $market,
				'addtime' => array('gt', time() - (60 * 60 * 24))
				)->sum('num'), 6);
			$sta_price = round(Db::name('TradeLog')->where(array(
				'market'  => $market,
				'status'  => 1,
				'addtime' => array('gt', time() - (60 * 60 * 24))
				)->order('id asc')->value('price'), 6);
			$Cmarket = Db::name('Market')->where('name', $market)->find();

			if ($Cmarket['new_price'] != $new_price) {
				$upCoinData['new_price'] = $new_price;
			}
			if ($Cmarket['buy_price'] != $buy_price) {
				$upCoinData['buy_price'] = $buy_price;
			}
			if ($Cmarket['sell_price'] != $sell_price) {
				$upCoinData['sell_price'] = $sell_price;
			}
			if ($Cmarket['min_price'] != $min_price) {
				$upCoinData['min_price'] = $min_price;
			}
			if ($Cmarket['max_price'] != $max_price) {
				$upCoinData['max_price'] = $max_price;
			}
			if ($Cmarket['volume'] != $volume) {
				$upCoinData['volume'] = $volume;
			}

			$change = round((($new_price - $Cmarket['hou_price']) / $Cmarket['hou_price']) * 100, 2);
			$upCoinData['change'] = $change;

			if ($upCoinData) {
				Db::name('Market')->where('name', $market)->update($upCoinData);
				Db::name('Market')->execute('commit');
				cache('home_market', null);
			}
		}
	}

  	public function matchingTradeallfanyi($market = NULL)
    {
		if (!$market) {
			return false;
		} else {
			$xnb = explode('_', $market)[0];
			$rmb = explode('_', $market)[1];
			if ($rmb=='cny') {
				$rmb1='usd';
			} else {
				$rmb1=$rmb;
			}
		}
		// var_dump($rmb);die;
		$fee_buy = config('market')[$market]['fee_buy'];
		$fee_sell = config('market')[$market]['fee_sell'];
		$invit_buy = config('market')[$market]['invit_buy'];
		$invit_sell = config('market')[$market]['invit_sell'];
		$invit_1 = config('market')[$market]['invit_1'];
		$invit_2 = config('market')[$market]['invit_2'];
		$invit_3 = config('market')[$market]['invit_3'];
		$mo = db();
		$new_trade_btchanges = 0;

		for (; true; ) {//先查找会员订单,如果找不到会员订单,再成交虚拟订单20170919
			// 匹配非0会员↓
			// $buy = Db::table('tw_trade')->where('market', $market,'userid' => array('gt',0), 'type' => 1, 'status' => 0)->order('price desc,id asc')->find();
			// if(!$buy){
				 // 匹配所有会员↓
			$buy = Db::table('tw_trade')->where('market', $market,'type' => 1, 'status' => 0)->order('price desc,id asc')->find();
			// }
			// $sell = Db::table('tw_trade')->where('market', $market,'userid' => array('gt',0), 'type' => 2, 'status' => 0)->order('price asc,id asc')->find();
			// if(!$sell){
					   $sell = Db::table('tw_trade')->where('market', $market, 'type' => 2, 'status' => 0)->order('price asc,id asc')->find();
			// }

			//以上
			// var_dump($buy);die;

			if ($sell['id'] < $buy['id']) {
				$type = 1;
			} else {
				$type = 2;
			}

			if ($buy && $sell && (0 <= floatval($buy['price']) - floatval($sell['price']))) {
				$rs = [];

				if ($buy['num'] <= $buy['deal']) {
				}

				if ($sell['num'] <= $sell['deal']) {
				}

				$amount = min(round($buy['num'] - $buy['deal'], 8 - config('market')[$market]['round']), round($sell['num'] - $sell['deal'], 8 - config('market')[$market]['round']));
				$amount = round($amount, 8 - config('market')[$market]['round']);

				if ($amount <= 0) {
					$log = lang('错误1交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . "\n";
					$log .= lang('ERR: 成交数量出错，数量是') . $amount;
					Db::name('Trade')->where('id', $buy['id'])->setField('status', 1);
					Db::name('Trade')->where('id', $sell['id'])->setField('status', 1);
					break;
				}

				if ($type == 1) {
					$price = $sell['price'];
				} else if ($type == 2) {
						 $price = $buy['price'];
				} else {
					break;
				}

				if (!$price) {
					$log = lang('错误2交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . "\n";
					$log .= lang('ERR: 成交价格出错，价格是') . $price;
					break;
				} else {
					// TODO: SEPARATE
					$price = round($price, config('market')[$market]['round']);
				}

				$mum = round($price * $amount, 8);
				if (!$mum) {
					$log = lang('错误3交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . "\n";
					$log .= lang('ERR: 成交总额出错，总额是') . $mum;
					mlog($log);
					break;
				} else {
					$mum = round($mum, 8);
				}

				if ($fee_buy) {
					$buy_fee = round(($mum / 100) * $fee_buy, 8);
					if ($buy_fee<1) {
						$buy_fee = 1;
						$buy_save = round($mum, 8)+1;
					} else {
						$buy_save = round(($mum / 100) * (100 + $fee_buy), 8);
					}
				} else {
					$buy_fee = 0;
					$buy_save = $mum;
				}

				if (!$buy_save) {
					$log = lang('错误4交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
					$log .= lang('ERR: 买家更新数量出错，更新数量是') . $buy_save;
					mlog($log);
					break;
				}

				if ($fee_sell) {
					$sell_fee = round(($mum / 100) * $fee_sell, 8);
					if ($sell_fee<1) {
						$sell_fee = 1;
						$sell_save = round($mum , 8)-1;
					} else {
						$sell_save = round(($mum / 100) * (100 - $fee_sell), 8);
					}
				} else {
					$sell_fee = 0;
					$sell_save = $mum;
				}

				if (!$sell_save) {
					$log = lang('错误5交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
					$log .= lang('ERR: 卖家更新数量出错，更新数量是') . $sell_save;
					mlog($log);
					break;
				}

				if($buy['userid']>0){
					$user_buy = Db::name('UserCoin')->where('userid', $buy['userid'])->find();
					if (!$user_buy[$rmb . 'd']) {
						$log = lang('错误6交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount .L( '成交价格') . $price . lang('成交总额') . $mum . "\n";
						$log .= lang('ERR: 买家财产错误，冻结财产是') . $user_buy[$rmb . 'd'];
						mlog($log);
						break;
					}
					if ($user_buy[$rmb . 'd'] < 1.0E-8) {
						$log = lang('错误88交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
						$log .= lang('ERR: 买家更新冻结人民币出现错误,应该更新') . $buy_save . lang('账号余额') . $user_buy[$rmb . 'd'] . lang('进行错误处理');
						mlog($log);
						Db::name('Trade')->where('id', $buy['id'])->setField('status', 1);
						break;
					}
					if ($buy_save <= round($user_buy[$rmb . 'd'], 8)) {
						$save_buy_rmb = $buy_save;
					} else if ($buy_save <= round($user_buy[$rmb . 'd'], 8) + 1) {
						$save_buy_rmb = $user_buy[$rmb . 'd'];
						$log = lang('错误8交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
						$log .= lang('ERR: 买家更新冻结人民币出现误差,应该更新') . $buy_save . lang('账号余额'). $user_buy[$rmb . 'd'] . lang('实际更新') . $save_buy_rmb;
						mlog($log);
					} else {
						$log = lang('错误9交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
						$log .= lang('ERR: 买家更新冻结人民币出现错误,应该更新') . $buy_save . lang('账号余额') . $user_buy[$rmb . 'd'] . lang('进行错误处理');
						mlog($log);
						Db::name('Trade')->where('id', $buy['id'])->setField('status', 1);
						break;
					}
					if (!$save_buy_rmb) {
						$log = lang('错误12交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
						$log .= lang('ERR: 买家更新数量出错错误,更新数量是') . $save_buy_rmb;
						mlog($log);
						Db::name('Trade')->where('id', $buy['id'])->setField('status', 1);
						break;
					}
				} else {
					$save_buy_rmb=0;
				}

				if ($sell['userid']>0) {
					$user_sell = Db::name('UserCoin')->where('userid', $sell['userid'])->find();
					if (!$user_sell[$xnb . 'd']) {
						$log = lang('错误7交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
						$log .= lang('ERR: 卖家财产错误，冻结财产是') . $user_sell[$xnb . 'd'];
						mlog($log);
						break;
					}

					// TODO: SEPARATE
					if ($amount <= round($user_sell[$xnb . 'd'], config('market')[$market]['round'])) {
						$save_sell_xnb = $amount;
					} else {
						// TODO: SEPARATE
						if ($amount <= round($user_sell[$xnb . 'd'], config('market')[$market]['round']) + 1) {
							$save_sell_xnb = $user_sell[$xnb . 'd'];
							$log = lang('错误10交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
							$log .= lang('ERR: 卖家更新冻结虚拟币出现误差,应该更新') . $amount . lang('账号余额') . $user_sell[$xnb . 'd'] . lang('实际更新') . $save_sell_xnb;
							mlog($log);
						} else {
							$log = lang('错误11交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
							$log .= lang('ERR: 卖家更新冻结虚拟币出现错误,应该更新') . $amount . lang('账号余额') . $user_sell[$xnb . 'd'] . lang('进行错误处理');
							mlog($log);
							Db::name('Trade')->where('id', $sell['id'])->setField('status', 1);
							break;
						}
					}
					if (!$save_sell_xnb) {
						$log = lang('错误13交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount . lang('成交价格') . $price . lang('成交总额') . $mum . "\n";
						$log .= lang('ERR: 卖家更新数量出错错误,更新数量是') . $save_sell_xnb;
						mlog($log);
						Db::name('Trade')->where('id', $sell['id'])->setField('status', 1);
						break;
					}
				}

				Db::execute('set autocommit=0');
				Db::execute('lock tables tw_trade write ,tw_trade_log write ,tw_user write,tw_user_coin write,tw_invit write ,tw_finance write,tw_coin write');
				$rs[] = Db::table('tw_trade')->where('id', $buy['id'])->setInc('deal', $amount);
				$rs[] = Db::table('tw_trade')->where('id', $sell['id'])->setInc('deal', $amount);
				$rs[] = $finance_nameid = Db::table('tw_trade_log')->insert(array('userid' => $buy['userid'], 'peerid' => $sell['userid'], 'market' => $market, 'price' => $price, 'num' => $amount, 'mum' => $mum, 'type' => $type, 'fee_buy' => $buy_fee, 'fee_sell' => $sell_fee, 'addtime' => time(), 'status' => 1));

				if ($buy['userid']>0) {
					$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setInc($xnb, $amount);
					$finance = Db::table('tw_finance')->where('userid', $buy['userid'])->order('id desc')->find();
					$finance_num_user_coin = Db::table('tw_user_coin')->where('userid', $buy['userid'])->find();

					$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setDec($rmb . 'd', $save_buy_rmb);
					$finance_mum_user_coin = Db::table('tw_user_coin')->where('userid', $buy['userid'])->find();
					$finance_hash = md5($buy['userid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mum . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'tp3.net.cn');
					$finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

					if ($finance['mum'] < $finance_num) {
						$finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
					} else {
						$finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
					}

					$rs[] = Db::table('tw_finance')->insert(array('userid' => $buy['userid'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $save_buy_rmb, 'type' => 2, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => '交易中心-成功买入-市场' . $market, 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
					$finance = Db::table('tw_finance')->where('userid', $buy['userid'])->order('id desc')->find();
				} else {
					$finance = 1;//如果用户是0,设置为1
				}

				if ($sell['userid']>0) {
					$finance_num_user_coin = Db::table('tw_user_coin')->where('userid', $sell['userid'])->find();
					// var_dump($finance_num_user_coin);die;
					$rs[] = Db::table('tw_user_coin')->where('userid', $sell['userid'])->setInc($rmb, $sell_save);
					$finance_mum_user_coin = Db::table('tw_user_coin')->where('userid', $sell['userid'])->find();
					$finance_hash = md5($sell['userid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mum . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'tp3.net.cn');
					// var_dump($finance);die;
					$finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

					if ($finance['mum'] < $finance_num) {
						$finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
					} else {
						$finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
					}
					// die('ok');
					// var_dump($finance_status);die;
					$rs[] = Db::table('tw_finance')->insert(array('userid' => $sell['userid'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => '交易中心-成功卖出-市场' . $market, 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
					// die('ok');
					$rs[] = Db::table('tw_user_coin')->where('userid', $sell['userid'])->setDec($xnb . 'd', $save_sell_xnb);

				}

				$buy_list = Db::table('tw_trade')->where('id', $buy['id'], 'status' => 0)->find();
				if ($buy_list) {
					if ($buy_list['num'] <= $buy_list['deal']) {
						$rs[] = Db::table('tw_trade')->where('id', $buy['id'])->setField('status', 1);
					}
				}

				$sell_list = Db::table('tw_trade')->where('id', $sell['id'], 'status' => 0)->find();
				if ($sell_list) {
					if ($sell_list['num'] <= $sell_list['deal']) {
						$rs[] = Db::table('tw_trade')->where('id', $sell['id'])->setField('status', 1);
					}
				}

				if ($price < $buy['price']) {
					$chajia_dong = round((($amount * $buy['price']) / 100) * (100 + $fee_buy), 8);
					$chajia_shiji = round((($amount * $price) / 100) * (100 + $fee_buy), 8);
					$chajia = round($chajia_dong - $chajia_shiji, 8);

					// if ($chajia) {//原来
					if ($chajia && $buy['userid']>0) {//不处理0的用户
						$chajia_user_buy = Db::table('tw_user_coin')->where('userid', $buy['userid'])->find();

						if ($chajia <= round($chajia_user_buy[$rmb . 'd'], 8)) {
							$chajia_save_buy_rmb = $chajia;
						} else if ($chajia <= round($chajia_user_buy[$rmb . 'd'], 8) + 1) {
							$chajia_save_buy_rmb = $chajia_user_buy[$rmb . 'd'];
							mlog(lang('错误91交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount, lang('成交价格') . $price . lang('成交总额') . $mum . "\n");
							mlog(lang('交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('成交数量') . $amount . lang('交易方式：') . $type . lang('卖家更新冻结虚拟币出现误差,应该更新') . $chajia . lang('账号余额') . $chajia_user_buy[$rmb . 'd'] . lang('实际更新') . $chajia_save_buy_rmb);
						} else {
							mlog(lang('错误92交易市场') . $market . lang('出错：买入订单:'). $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('交易方式：') . $type . lang('成交数量') . $amount, lang('成交价格') . $price . lang('成交总额') . $mum . "\n");
							mlog(lang('交易市场') . $market . lang('出错：买入订单:') . $buy['id'] . lang('卖出订单：') . $sell['id'] . lang('成交数量') . $amount . lang('交易方式：') . $type . lang('卖家更新冻结虚拟币出现错误,应该更新') . $chajia . lang('账号余额') . $chajia_user_buy[$rmb . 'd'] . lang('进行错误处理'));
							Db::execute('rollback');
							Db::execute('unlock tables');
							Db::name('Trade')->where('id', $buy['id'])->setField('status', 1);
							Db::name('Trade')->execute('commit');
							break;
						}

						if ($chajia_save_buy_rmb) {
							$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setDec($rmb . 'd', $chajia_save_buy_rmb);
							$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setInc($rmb, $chajia_save_buy_rmb);
						}
					}
				}

				$you_buy = Db::table('tw_trade')->where(array(
					'market' => array('like', '%' . $rmb . '%'),
					'status' => 0,
					'userid' => $buy['userid']
				)->find();
				$you_sell = Db::table('tw_trade')->where(array(
					'market' => array('like', '%' . $xnb . '%'),
					'status' => 0,
					'userid' => $sell['userid']
				)->find();
				// var_dump($you_sell);die;

				if (!$you_buy) {
					$you_user_buy = Db::table('tw_user_coin')->where('userid', $buy['userid'])->find();
					if (0 < $you_user_buy[$rmb . 'd']) {
						$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setField($rmb . 'd', 0);
						$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setInc($rmb, $you_user_buy[$rmb . 'd']);
					}
				}

				if (!$you_sell) {
					$you_user_sell = Db::table('tw_user_coin')->where('userid', $sell['userid'])->find();

					if (0 < $you_user_sell[$xnb . 'd']) {
						$rs[] = Db::table('tw_user_coin')->where('userid', $sell['userid'])->setField($xnb . 'd', 0);
						$rs[] = Db::table('tw_user_coin')->where('userid', $sell['userid'])->setInc($rmb, $you_user_sell[$xnb . 'd']);
					}
				}

				$invit_buy_user = Db::table('tw_user')->where('id', $buy['userid'])->find();
				$invit_sell_user = Db::table('tw_user')->where('id', $sell['userid'])->find();
				$xnblx=DB::name('Coin')->where('name', $xnb)->find();//交易的虚拟币类型

				// var_dump($invit_sell_user);die;

				// if ($invit_buy) {//原
				if ($invit_buy && $buy['userid']>0) {//不处理0用户
					if ($invit_1) {
						if ($buy_fee) {
							if ($invit_buy_user['invit_1']) {
								$invit_buy_save_1 = round(($buy_fee / 100) * $invit_1, 6);

								if ($invit_buy_save_1) {
									$rs[] = Db::table('tw_user_coin')->where('userid', $invit_buy_user['invit_1'])->setInc($rmb, $invit_buy_save_1);

									$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_buy_user['invit_1'], 'invit' => $buy['userid'], 'name' => lang('一代买入赠送'), 'type' => $xnblx['title'] . lang('买入交易赠送'), 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_1, 'addtime' => time(), 'status' => 1,'coin'=>strtoupper($rmb1)));
								}
							}

							if ($invit_buy_user['invit_2']) {
								$invit_buy_save_2 = round(($buy_fee / 100) * $invit_2, 6);

								if ($invit_buy_save_2) {
									$rs[] = Db::table('tw_user_coin')->where('userid', $invit_buy_user['invit_2'])->setInc($rmb, $invit_buy_save_2);

									$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_buy_user['invit_2'], 'invit' => $buy['userid'], 'name' => lang('二代买入赠送'), 'type' => $xnblx['title'] . lang('买入交易赠送'), 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_2, 'addtime' => time(), 'status' => 1,'coin'=>strtoupper($rmb1)));
								}
							}

							if ($invit_buy_user['invit_3']) {
								$invit_buy_save_3 = round(($buy_fee / 100) * $invit_3, 6);

								if ($invit_buy_save_3) {
									$rs[] = Db::table('tw_user_coin')->where('userid', $invit_buy_user['invit_3'])->setInc($rmb, $invit_buy_save_3);

									$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_buy_user['invit_3'], 'invit' => $buy['userid'], 'name' => lang('三代买入赠送'), 'type' => $xnblx['title'] . lang('买入交易赠送'), 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_3, 'addtime' => time(), 'status' => 1,'coin'=>strtoupper($rmb1)));
								}
							}
						}
					}

					// if ($invit_sell) {//原来
					if ($invit_sell && $invit_sell['userid']) {//不处理0用户
						if ($sell_fee) {
							if ($invit_sell_user['invit_1']) {
								$invit_sell_save_1 = round(($sell_fee / 100) * $invit_1, 6);

								if ($invit_sell_save_1) {
									$rs[] = Db::table('tw_user_coin')->where('userid', $invit_sell_user['invit_1'])->setInc($rmb, $invit_sell_save_1);

									$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_sell_user['invit_1'], 'invit' => $sell['userid'], 'name' => lang('一代卖出赠送'), 'type' => $xnblx['title'] . lang('卖出交易赠送'), 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_1, 'addtime' => time(), 'status' => 1,'coin'=>strtoupper($rmb1)));
								}
							}

							if ($invit_sell_user['invit_2']) {
								$invit_sell_save_2 = round(($sell_fee / 100) * $invit_2, 6);

								if ($invit_sell_save_2) {
									$rs[] = Db::table('tw_user_coin')->where('userid', $invit_sell_user['invit_2'])->setInc($rmb, $invit_sell_save_2);

									$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_sell_user['invit_2'], 'invit' => $sell['userid'], 'name' => lang('二代卖出赠送'), 'type' => $xnblx['title'] . lang('卖出交易赠送'), 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_2, 'addtime' => time(), 'status' => 1,'coin'=>strtoupper($rmb1)));
								}
							}

							if ($invit_sell_user['invit_3']) {
								$invit_sell_save_3 = round(($sell_fee / 100) * $invit_3, 6);

								if ($invit_sell_save_3) {
									$rs[] = Db::table('tw_user_coin')->where('userid', $invit_sell_user['invit_3'])->setInc($rmb, $invit_sell_save_3);

									$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_sell_user['invit_3'], 'invit' => $sell['userid'], 'name' => lang('三代卖出赠送'), 'type' => $xnblx['title'] . lang('卖出交易赠送'), 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_3, 'addtime' => time(), 'status' => 1,'coin'=>strtoupper($rmb1)));
								}
							}
						}
					}
				}

				if (check_arr($rs)) {
					Db::execute('commit');
					Db::execute('unlock tables');
					$new_trade_btchanges = 1;
					$coin = $xnb;
					cache('allsum', null);
					cache('getJsonTop' . $market, null);
					cache('getTradelog' . $market, null);
					cache('getDepth' . $market . '1', null);
					cache('getDepth' . $market . '3', null);
					cache('getDepth' . $market . '4', null);
					cache('ChartgetJsonData' . $market, null);
					cache('allcoin', null);
					cache('trends', null);
				}
				else {
					Db::execute('rollback');
					Db::execute('unlock tables');
				}
			} else {
				break;
			}

			unset($rs);
		}

		if ($new_trade_btchanges) {
			$new_price = round(Db::name('TradeLog')->where('market', $market, 'status' => 1)->order('id desc')->value('price'), 6);
			$buy_price = round(Db::name('Trade')->where(array('type' => 1, 'market' => $market, 'status' => 0)->max('price'), 6);
			$sell_price = round(Db::name('Trade')->where(array('type' => 2, 'market' => $market, 'status' => 0)->min('price'), 6);
			$min_price = round(Db::name('TradeLog')->where(array(
				'market'  => $market,
				'addtime' => array('gt', time() - (60 * 60 * 24))
			)->min('price'), 6);
			$max_price = round(Db::name('TradeLog')->where(array(
				'market'  => $market,
				'addtime' => array('gt', time() - (60 * 60 * 24))
			)->max('price'), 6);
			$volume = round(Db::name('TradeLog')->where(array(
				'market'  => $market,
				'addtime' => array('gt', time() - (60 * 60 * 24))
			)->sum('num'), 6);
			$sta_price = round(Db::name('TradeLog')->where(array(
				'market'  => $market,
				'status'  => 1,
				'addtime' => array('gt', time() - (60 * 60 * 24))
			)->order('id asc')->value('price'), 6);
			$Cmarket = Db::name('Market')->where('name', $market)->find();

			if ($Cmarket['new_price'] != $new_price) {
				$upCoinData['new_price'] = $new_price;
			}
			if ($Cmarket['buy_price'] != $buy_price) {
				$upCoinData['buy_price'] = $buy_price;
			}
			if ($Cmarket['sell_price'] != $sell_price) {
				$upCoinData['sell_price'] = $sell_price;
			}
			if ($Cmarket['min_price'] != $min_price) {
				$upCoinData['min_price'] = $min_price;
			}
			if ($Cmarket['max_price'] != $max_price) {
				$upCoinData['max_price'] = $max_price;
			}
			if ($Cmarket['volume'] != $volume) {
				$upCoinData['volume'] = $volume;
			}

			$change = round((($new_price - $Cmarket['hou_price']) / $Cmarket['hou_price']) * 100, 2);
			$upCoinData['change'] = $change;

			if ($upCoinData) {
				Db::name('Market')->where('name', $market)->update($upCoinData);
				Db::name('Market')->execute('commit');
				cache('home_market', null);
			}
		}
	}
	
	public function matchingTradeall($market = NULL)
	{
		if (!$market) {
			return false;
		} else {
			$xnb = explode('_', $market)[0];
			$rmb = explode('_', $market)[1];
			if ($rmb == 'cny') {
				$rmb1 = 'usd';
			} else {
				$rmb1=$rmb;
			}
		}

		$fee_buy = config('market')[$market]['fee_buy'];
		$fee_sell = config('market')[$market]['fee_sell'];
		$invit_buy = config('market')[$market]['invit_buy'];
		$invit_sell = config('market')[$market]['invit_sell'];
		$invit_1 = config('market')[$market]['invit_1'];
		$invit_2 = config('market')[$market]['invit_2'];
		$invit_3 = config('market')[$market]['invit_3'];
		$mo = db();
		$new_trade_btchanges = 0;

		for (; true; ) {//先查找会员订单,如果找不到会员订单,再成交虚拟订单20170919
			// 匹配非0会员↓
			// $buy = Db::table('tw_trade')->where('market', $market,'userid' => array('gt',0), 'type' => 1, 'status' => 0)->order('price desc,id asc')->find();
			// if(!$buy){
				 // 匹配所有会员↓
			$buy = Db::table('tw_trade')->where('market', $market,'type' => 1, 'status' => 0)->order('price desc,id asc')->find();
			// }
			// $sell = Db::table('tw_trade')->where('market', $market,'userid' => array('gt',0), 'type' => 2, 'status' => 0)->order('price asc,id asc')->find();
			// if(!$sell){
				   $sell = Db::table('tw_trade')->where('market', $market, 'type' => 2, 'status' => 0)->order('price asc,id asc')->find();
			// }


			if ($sell['id'] < $buy['id']) {
				$type = 1;
			} else {
				$type = 2;
			}

			if ($buy && $sell && (0 <= floatval($buy['price']) - floatval($sell['price']))) {
				$rs = [];

				if ($buy['num'] <= $buy['deal']) {}
				if ($sell['num'] <= $sell['deal']) {}

				// $amount = min(round($buy['num'] - $buy['deal'], 8 - config('market')[$market]['round']), round($sell['num'] - $sell['deal'], 8 - config('market')[$market]['round']));
				 // $amount = round($amount, 8 - config('market')[$market]['round']);//20171031
				$amount = min(round($buy['num'] - $buy['deal'], config('market')[$market]['round']), round($sell['num'] - $sell['deal'],  config('market')[$market]['round']));

				$amount = round($amount,  config('market')[$market]['round']);
				if ($amount <= 0) {
					$log = '错误1交易市场' . $market . '出错：买入订单:' . $buy['id'] . '  卖出订单：' . $sell['id'] . '  交易方式：' . $type . "\n";
					$log .= 'ERR: 成交数量出错，数量是' . $amount;
					Db::name('Trade')->where('id', $buy['id'])->setField('status', 1);
					Db::name('Trade')->where('id', $sell['id'])->setField('status', 1);
					break;
				}

				if ($type == 1) {
					$price = $sell['price'];
				} else if ($type == 2) {
						 $price = $buy['price'];
				} else {
					break;
				}

				if (!$price) {
					$log = '错误2交易市场' . $market . '出错：买入订单:' . $buy['id'] . '  卖出订单：' . $sell['id'] . '  交易方式：' . $type . ' 成交数量' . $amount . "\n";
					$log .= 'ERR: 成交价格出错，价格是' . $price;
					break;
				} else {
					// TODO: SEPARATE
					$price = round($price, config('market')[$market]['round']);
				}

				$mum = round($price * $amount, 7);
				if (!$mum) {
					$log = '错误3交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . "\n";
					$log .= 'ERR: 成交总额出错，总额是' . $mum;
					mlog($log);
					break;
				} else {
					$mum = round($mum, 7);
				}

				if ($fee_buy) {
					$buy_fee = round(($mum / 100) * $fee_buy, 7);
					// if($buy_fee<1){
					//     $buy_fee=1;
					//     $buy_save = round($mum, 8)+1;
					// }else{
						$buy_save = round(($mum / 100) * (100 + $fee_buy),7);
					// }
				} else {
					$buy_fee = 0;
					$buy_save = $mum;
				}

				if (!$buy_save) {
					$log = '错误4交易市场' . $market . '出错：买入订单:' . $buy['id'] . '  卖出订单：' . $sell['id'] . '  交易方式：' . $type . '  成交数量' . $amount . '  成交价格' . $price . '成交总额' . $mum . "\n";
					$log .= 'ERR: 买家更新数量出错，更新数量是' . $buy_save;
					mlog($log);
					break;
				}

				if ($fee_sell) {
					$sell_fee = round(($mum / 100) * $fee_sell, 7);
					// if($sell_fee<1){
					//     $sell_fee=1;
					//     $sell_save = round($mum , 8)-1;
					// }else{
						$sell_save = round(($mum / 100) * (100 - $fee_sell), 7);
					// }
				} else {
					$sell_fee = 0;
					$sell_save = $mum;
				}

				if (!$sell_save) {
					$log = '错误5交易市场' . $market . '出错：买入订单:' . $buy['id'] . '  卖出订单：' . $sell['id'] . '  交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
					$log .= 'ERR: 卖家更新数量出错，更新数量是' . $sell_save;
					mlog($log);
					break;
				}

				if($buy['userid']>0){
					$user_buy = Db::name('UserCoin')->where('userid', $buy['userid'])->find();
					if (!$user_buy[$rmb . 'd']) {
						$log = '错误6交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
						$log .= 'ERR: 买家财产错误，冻结财产是' . $user_buy[$rmb . 'd'];
						mlog($log);
						break;
					}
					if ($user_buy[$rmb . 'd'] < 1.0E-8) {
						$log = '错误88交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
						$log .= 'ERR: 买家更新冻结人民币出现错误,应该更新' . $buy_save . '账号余额' . $user_buy[$rmb . 'd'] . '进行错误处理';
						mlog($log);
						Db::name('Trade')->where('id', $buy['id'])->setField('status', 1);
						break;
					}
					if ($buy_save <= round($user_buy[$rmb . 'd'], 7)) {
						$save_buy_rmb = $buy_save;
					} else if ($buy_save <= round($user_buy[$rmb . 'd'], 7) + 1) {
						$save_buy_rmb = $user_buy[$rmb . 'd'];
						$log = '错误8交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
						$log .= 'ERR: 买家更新冻结人民币出现误差,应该更新' . $buy_save . '账号余额' . $user_buy[$rmb . 'd'] . '实际更新' . $save_buy_rmb;
						mlog($log);
					} else {
						$log = '错误9交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
						$log .= 'ERR: 买家更新冻结人民币出现错误,应该更新' . $buy_save . '账号余额' . $user_buy[$rmb . 'd'] . '进行错误处理';
						mlog($log);
						Db::name('Trade')->where('id', $buy['id'])->setField('status', 1);
						break;
					}
					if (!$save_buy_rmb) {
						$log = '错误12交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
						$log .= 'ERR: 买家更新数量出错错误,更新数量是' . $save_buy_rmb;
						mlog($log);
						Db::name('Trade')->where('id', $buy['id'])->setField('status', 1);
						break;
					}
				} else {
					$save_buy_rmb=0;
				}

				if ($sell['userid']>0) {
					$user_sell = Db::name('UserCoin')->where('userid', $sell['userid'])->find();
					if (!$user_sell[$xnb . 'd']) {
						$log = '错误7交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
						$log .= 'ERR: 卖家财产错误，冻结财产是' . $user_sell[$xnb . 'd'];
						mlog($log);
						break;
					}

					// TODO: SEPARATE
					if ($amount <= round($user_sell[$xnb . 'd'], config('market')[$market]['round'])) {
						$save_sell_xnb = $amount;
					} else {
						// TODO: SEPARATE
						if ($amount <= round($user_sell[$xnb . 'd'], config('market')[$market]['round']) + 1) {
							$save_sell_xnb = $user_sell[$xnb . 'd'];
							$log = '错误10交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
							$log .= 'ERR: 卖家更新冻结虚拟币出现误差,应该更新' . $amount . '账号余额' . $user_sell[$xnb . 'd'] . '实际更新' . $save_sell_xnb;
							mlog($log);
						} else {
							$log = '错误11交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
							$log .= 'ERR: 卖家更新冻结虚拟币出现错误,应该更新' . $amount . '账号余额' . $user_sell[$xnb . 'd'] . '进行错误处理';
							mlog($log);
							Db::name('Trade')->where('id', $sell['id'])->setField('status', 1);
							break;
						}
					}
					if (!$save_sell_xnb) {
						$log = '错误13交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
						$log .= 'ERR: 卖家更新数量出错错误,更新数量是' . $save_sell_xnb;
						mlog($log);
						Db::name('Trade')->where('id', $sell['id'])->setField('status', 1);
						break;
					}

				}

				Db::execute('set autocommit=0');
				Db::execute('lock tables tw_trade write ,tw_trade_log write ,tw_user write,tw_user_coin write,tw_invit write ,tw_finance write,tw_coin write');
				$rs[] = Db::table('tw_trade')->where('id', $buy['id'])->setInc('deal', $amount);
				$rs[] = Db::table('tw_trade')->where('id', $sell['id'])->setInc('deal', $amount);
				$rs[] = $finance_nameid = Db::table('tw_trade_log')->insert(array('userid' => $buy['userid'], 'peerid' => $sell['userid'], 'market' => $market, 'price' => $price, 'num' => $amount, 'mum' => $mum, 'type' => $type, 'fee_buy' => $buy_fee, 'fee_sell' => $sell_fee, 'addtime' => time(), 'status' => 1));

				if ($buy['userid']>0) {
					$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setInc($xnb, $amount);
					$finance = Db::table('tw_finance')->where('userid', $buy['userid'])->order('id desc')->find();
					$finance_num_user_coin = Db::table('tw_user_coin')->where('userid', $buy['userid'])->find();

					$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setDec($rmb . 'd', $save_buy_rmb);
					$finance_mum_user_coin = Db::table('tw_user_coin')->where('userid', $buy['userid'])->find();
					$finance_hash = md5($buy['userid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mum . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'tp3.net.cn');
					$finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

					if ($finance['mum'] < $finance_num) {
						$finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
					} else {
						$finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
					}

					$rs[] = Db::table('tw_finance')->insert(array('userid' => $buy['userid'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $save_buy_rmb, 'type' => 2, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => '交易中心-成功买入-市场' . $market, 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
					$finance = Db::table('tw_finance')->where('userid', $buy['userid'])->order('id desc')->find();
				} else {
					$finance = 1;//如果用户是0,设置为1
				}


				if ($sell['userid']>0) {
					$finance_num_user_coin = Db::table('tw_user_coin')->where('userid', $sell['userid'])->find();
					// var_dump($finance_num_user_coin);die;
					$rs[] = Db::table('tw_user_coin')->where('userid', $sell['userid'])->setInc($rmb, $sell_save);
					$finance_mum_user_coin = Db::table('tw_user_coin')->where('userid', $sell['userid'])->find();
					$finance_hash = md5($sell['userid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mum . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'tp3.net.cn');
					// var_dump($finance);die;
					$finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

					if ($finance['mum'] < $finance_num) {
						$finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
					} else {
						$finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
					}
					// die('ok');
					// var_dump($finance_status);die;
					$rs[] = Db::table('tw_finance')->insert(array('userid' => $sell['userid'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => '交易中心-成功卖出-市场' . $market, 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
					// die('ok');
					$rs[] = Db::table('tw_user_coin')->where('userid', $sell['userid'])->setDec($xnb . 'd', $save_sell_xnb);
				}

				$buy_list = Db::table('tw_trade')->where('id', $buy['id'], 'status' => 0)->find();
				if ($buy_list) {
					if ($buy_list['num'] <= $buy_list['deal']) {
						$rs[] = Db::table('tw_trade')->where('id', $buy['id'])->setField('status', 1);
					}
				}

				$sell_list = Db::table('tw_trade')->where('id', $sell['id'], 'status' => 0)->find();
				if ($sell_list) {
					if ($sell_list['num'] <= $sell_list['deal']) {
						$rs[] = Db::table('tw_trade')->where('id', $sell['id'])->setField('status', 1);
					}
				}

				if ($price < $buy['price']) {
					$chajia_dong = round((($amount * $buy['price']) / 100) * (100 + $fee_buy), 7);
					$chajia_shiji = round((($amount * $price) / 100) * (100 + $fee_buy), 7);
					$chajia = round($chajia_dong - $chajia_shiji, 7);

					// if ($chajia) {//原来
					if ($chajia && $buy['userid']>0) {//不处理0的用户
						$chajia_user_buy = Db::table('tw_user_coin')->where('userid', $buy['userid'])->find();

						if ($chajia <= round($chajia_user_buy[$rmb . 'd'], 7)) {
							$chajia_save_buy_rmb = $chajia;
						} else if ($chajia <= round($chajia_user_buy[$rmb . 'd'], 7) + 1) {
							$chajia_save_buy_rmb = $chajia_user_buy[$rmb . 'd'];
							mlog('错误91交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount, '成交价格' . $price . '成交总额' . $mum . "\n");
							mlog('交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '成交数量' . $amount . '交易方式：' . $type . '卖家更新冻结虚拟币出现误差,应该更新' . $chajia . '账号余额' . $chajia_user_buy[$rmb . 'd'] . '实际更新' . $chajia_save_buy_rmb);
						} else {
							mlog('错误92交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount, '成交价格' . $price . '成交总额' . $mum . "\n");
							mlog('交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '成交数量' . $amount . '交易方式：' . $type . '卖家更新冻结虚拟币出现错误,应该更新' . $chajia . '账号余额' . $chajia_user_buy[$rmb . 'd'] . '进行错误处理');
							Db::execute('rollback');
							Db::execute('unlock tables');
							Db::name('Trade')->where('id', $buy['id'])->setField('status', 1);
							Db::name('Trade')->execute('commit');
							break;
						}

						if ($chajia_save_buy_rmb) {
							$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setDec($rmb . 'd', $chajia_save_buy_rmb);
							$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setInc($rmb, $chajia_save_buy_rmb);
						}
					}
				}

				$you_buy = Db::table('tw_trade')->where(array(
					'market' => array('like', '%' . $rmb . '%'),
					'status' => 0,
					'userid' => $buy['userid']
				)->find();
				$you_sell = Db::table('tw_trade')->where(array(
					'market' => array('like', '%' . $xnb . '%'),
					'status' => 0,
					'userid' => $sell['userid']
				)->find();
				// var_dump($you_sell);die;
				
				if (!$you_buy) {
					$you_user_buy = Db::table('tw_user_coin')->where('userid', $buy['userid'])->find();
					if (0 < $you_user_buy[$rmb . 'd']) {
						$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setField($rmb . 'd', 0);
						$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setInc($rmb, $you_user_buy[$rmb . 'd']);
					}
				}

				if (!$you_sell) {
					$you_user_sell = Db::table('tw_user_coin')->where('userid', $sell['userid'])->find();
					if (0 < $you_user_sell[$xnb . 'd']) {
						$rs[] = Db::table('tw_user_coin')->where('userid', $sell['userid'])->setField($xnb . 'd', 0);
						$rs[] = Db::table('tw_user_coin')->where('userid', $sell['userid'])->setInc($rmb, $you_user_sell[$xnb . 'd']);
					}
				}

				$invit_buy_user = Db::table('tw_user')->where('id', $buy['userid'])->find();
				$invit_sell_user = Db::table('tw_user')->where('id', $sell['userid'])->find();
				$xnblx=DB::name('Coin')->where('name', $xnb)->find();//交易的虚拟币类型

				// var_dump($invit_sell_user);die;

				// if ($invit_buy) {//原
				if ($invit_buy && $buy['userid']>0) {//不处理0用户
					if ($invit_1) {
						if ($buy_fee) {
							if ($invit_buy_user['invit_1']) {
								$invit_buy_save_1 = round(($buy_fee / 100) * $invit_1, 7);

								if ($invit_buy_save_1) {
									$rs[] = Db::table('tw_user_coin')->where('userid', $invit_buy_user['invit_1'])->setInc($rmb, $invit_buy_save_1);

									$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_buy_user['invit_1'], 'invit' => $buy['userid'], 'name' => '一代买入赠送', 'type' => $xnblx['title'] . '买入交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_1, 'addtime' => time(), 'status' => 1,'coin'=>strtoupper($rmb1)));
								}
							}

							if ($invit_buy_user['invit_2']) {
								$invit_buy_save_2 = round(($buy_fee / 100) * $invit_2, 7);

								if ($invit_buy_save_2) {
									$rs[] = Db::table('tw_user_coin')->where('userid', $invit_buy_user['invit_2'])->setInc($rmb, $invit_buy_save_2);

									$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_buy_user['invit_2'], 'invit' => $buy['userid'], 'name' => '二代买入赠送', 'type' => $xnblx['title'] . '买入交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_2, 'addtime' => time(), 'status' => 1,'coin'=>strtoupper($rmb1)));
								}
							}

							if ($invit_buy_user['invit_3']) {
								$invit_buy_save_3 = round(($buy_fee / 100) * $invit_3, 7);

								if ($invit_buy_save_3) {
									$rs[] = Db::table('tw_user_coin')->where('userid', $invit_buy_user['invit_3'])->setInc($rmb, $invit_buy_save_3);

									$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_buy_user['invit_3'], 'invit' => $buy['userid'], 'name' => '三代买入赠送', 'type' => $xnblx['title'] . '买入交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_3, 'addtime' => time(), 'status' => 1,'coin'=>strtoupper($rmb1)));
								}
							}
						}
					}

					// if ($invit_sell) {//原来
					if ($invit_sell && $invit_sell['userid']>0) {//不处理0用户
						if ($sell_fee) {
							if ($invit_sell_user['invit_1']) {
								$invit_sell_save_1 = round(($sell_fee / 100) * $invit_1, 7);

								if ($invit_sell_save_1) {
									$rs[] = Db::table('tw_user_coin')->where('userid', $invit_sell_user['invit_1'])->setInc($rmb, $invit_sell_save_1);

									$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_sell_user['invit_1'], 'invit' => $sell['userid'], 'name' => '一代卖出赠送', 'type' => $xnblx['title'] . '卖出交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_1, 'addtime' => time(), 'status' => 1,'coin'=>strtoupper($rmb1)));
								}
							}

							if ($invit_sell_user['invit_2']) {
								$invit_sell_save_2 = round(($sell_fee / 100) * $invit_2, 7);

								if ($invit_sell_save_2) {
									$rs[] = Db::table('tw_user_coin')->where('userid', $invit_sell_user['invit_2'])->setInc($rmb, $invit_sell_save_2);

									$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_sell_user['invit_2'], 'invit' => $sell['userid'], 'name' => '二代卖出赠送', 'type' => $xnblx['title'] . '卖出交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_2, 'addtime' => time(), 'status' => 1,'coin'=>strtoupper($rmb1)));
								}
							}

							if ($invit_sell_user['invit_3']) {
								$invit_sell_save_3 = round(($sell_fee / 100) * $invit_3, 7);

								if ($invit_sell_save_3) {
									$rs[] = Db::table('tw_user_coin')->where('userid', $invit_sell_user['invit_3'])->setInc($rmb, $invit_sell_save_3);

									$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_sell_user['invit_3'], 'invit' => $sell['userid'], 'name' => '三代卖出赠送', 'type' => $xnblx['title'] . '卖出交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_3, 'addtime' => time(), 'status' => 1,'coin'=>strtoupper($rmb1)));
								}
							}
						}
					}
				}

				if (check_arr($rs)) {
					Db::execute('commit');
					Db::execute('unlock tables');
					$new_trade_btchanges = 1;
					$coin = $xnb;
					cache('allsum', null);
					cache('getJsonTop' . $market, null);
					cache('getTradelog' . $market, null);
					cache('getDepth' . $market . '1', null);
					cache('getDepth' . $market . '3', null);
					cache('getDepth' . $market . '4', null);
					cache('ChartgetJsonData' . $market, null);
					cache('allcoin', null);
					cache('trends', null);
				} else {
					Db::execute('rollback');
					Db::execute('unlock tables');
				}
			} else {
				break;
			}

			unset($rs);
		}

		if ($new_trade_btchanges) {
			$new_price = round(Db::name('TradeLog')->where('market', $market, 'status' => 1)->order('id desc')->value('price'), 6);
			$buy_price = round(Db::name('Trade')->where(array('type' => 1, 'market' => $market, 'status' => 0)->max('price'), 6);
			$sell_price = round(Db::name('Trade')->where(array('type' => 2, 'market' => $market, 'status' => 0)->min('price'), 6);
			$min_price = round(Db::name('TradeLog')->where(array(
				'market'  => $market,
				'addtime' => array('gt', time() - (60 * 60 * 24))
			)->min('price'), 6);
			$max_price = round(Db::name('TradeLog')->where(array(
				'market'  => $market,
				'addtime' => array('gt', time() - (60 * 60 * 24))
			)->max('price'), 6);
			$volume = round(Db::name('TradeLog')->where(array(
				'market'  => $market,
				'addtime' => array('gt', time() - (60 * 60 * 24))
			)->sum('num'), 6);
			$sta_price = round(Db::name('TradeLog')->where(array(
				'market'  => $market,
				'status'  => 1,
				'addtime' => array('gt', time() - (60 * 60 * 24))
			)->order('id asc')->value('price'), 6);
			
			$Cmarket = Db::name('Market')->where('name', $market)->find();
			if ($Cmarket['new_price'] != $new_price) {
				$upCoinData['new_price'] = $new_price;
			}
			if ($Cmarket['buy_price'] != $buy_price) {
				$upCoinData['buy_price'] = $buy_price;
			}
			if ($Cmarket['sell_price'] != $sell_price) {
				$upCoinData['sell_price'] = $sell_price;
			}
			if ($Cmarket['min_price'] != $min_price) {
				$upCoinData['min_price'] = $min_price;
			}
			if ($Cmarket['max_price'] != $max_price) {
				$upCoinData['max_price'] = $max_price;
			}
			if ($Cmarket['volume'] != $volume) {
				$upCoinData['volume'] = $volume;
			}

			$change = round((($new_price - $Cmarket['hou_price']) / $Cmarket['hou_price']) * 100, 2);
			$upCoinData['change'] = $change;
			if ($upCoinData) {
				Db::name('Market')->where('name', $market)->update($upCoinData);
				Db::name('Market')->execute('commit');
				cache('home_market', null);
			}
		}
	}
}
?>