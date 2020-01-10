<?php
/* 交易中心 */
namespace app\home\controller;

use think\Db;

class Trade extends Home
{
	public function index($market = NULL)
	{
		// 过滤非法字符----------------S
		if (checkstr($market)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E
		
		check_server();
		if (!$market) { $market = Db::name('config')->where('id', 1)->value("market_mr"); }
		
		$wheres['id'] = 1;
		$daohangs = Db::name('daohang')->where($wheres)->find();
		if ($daohangs['access']) {
			$this->error(lang('暂未对外开放'), url('Index/index'));
		}
		
		// 分类名称
		$getCoreConfig = getCoreConfig();
		if(!$getCoreConfig){
			$this->error('核心配置有误');
		}
		$this->assign('jiaoyiqu', $getCoreConfig['indexcat']);
		
		// 顶部价格信息
		$topdata = (config('app.develop') ? null : cache('getJsonTop' . $market));
		if (!$topdata) {
			if ($market) {
				$xnb = explode('_', $market)[0];
				$rmb = explode('_', $market)[1];

				foreach (config('market') as $k => $v) {
					$v['xnb'] = explode('_', $v['name'])[0];
					$v['rmb'] = explode('_', $v['name'])[1];
					$topdata['list'][$k]['name'] = $v['name'];
					$topdata['list'][$k]['img'] = $v['xnbimg'];
					$topdata['list'][$k]['title'] = $v['title'];
					$topdata['list'][$k]['new_price'] = $v['new_price'];
				}

				$topdata['info']['new_price'] = config('market')[$market]['new_price'];
				cache('getJsonTop' . $market, $topdata);
			}
		}
		

		$this->assign("market", $market);
		$this->assign("coin_name", strtoupper(explode("_", $market)[0])); //币种简称
		$this->assign("coin_type", strtoupper(explode("_", $market)[1])); //币种市场
		
		$sclj = explode('_', $market)[1];
		$coinnav = 0;
/*		if($sclj=='bwb'){
			$rmb= round($topdata['info']['new_price'] *C('MYCOIN'),2);
		}*/
		if ($sclj==config('app.anchor_cny')) { //锚定法币
			$rmb = bcdiv($topdata['info']['new_price'] * config('MYCOIN'),1,2) * 1;
			$coinnav = 0;
		}
		if ($sclj=='btc') {
			//$rmb = round($topdata['info']['new_price'] * config('BTC'),2);
			$rmb = bcdiv($topdata['info']['new_price'] * config('market')['btc_c'.config('app.anchor_cny')]['new_price'],1,2) * 1;
			$coinnav = 1;
		}
		if ($sclj=='eth') {
			//$rmb = NumToStr(round($topdata['info']['new_price'] * config('market')['eth_'.config('app.anchor_cny')]['new_price']),6);
			$rmb = bcdiv($topdata['info']['new_price'] * config('market')['eth_'.config('app.anchor_cny')]['new_price'],1,2) * 1;
			$coinnav = 2;
		}
		if ($sclj=='usdt') {
            if (empty(config('market')['usdt_'.config('app.anchor_cny')]['new_price'])) {
                $rmb = (float)0;
            } else {
                $rmb = bcdiv($topdata['info']['new_price'] * config('market')['usdt_'.config('app.anchor_cny')]['new_price'],1,2) * 1;
            }
            $coinnav = 3;
		}
		if ($sclj=='mob') {
			$rmb = bcdiv($topdata['info']['new_price'] * config('market')['mob_'.config('app.anchor_cny')]['new_price'],1,2) * 1;
			$coinnav = 3;
		}
				
		$this->assign('market_coin', config('app.anchor_cny'));
		$this->assign('rmbprice', $rmb);
		$this->assign('coinnav', $coinnav);
		
		return $this->fetch();
	}
	
	/**
	 * 普通K线图
	 * @param  [type] $market [description]
	 * @return [type]         [description]
	 */
	public function ordinary($market = NULL)
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

	/**
	 * 专业K线图
	 * @param  [type] $market [description]
	 * @return [type]         [description]
	 */
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
		if (($times <= config('market')[$market]['start_time'] && $minute < intval(cache('market')[$market]['start_minute'])) || ( $times > config('market')[$market]['stop_time'] && $minute>= intval(cache('market')[$market]['stop_minute']))) {
			$this->error(lang('该时间为闭盘时间！'));
		}
		if (($times <config('market')[$market]['start_time'] )|| $times > config('market')[$market]['stop_time']) {
			$this->error(lang('该时间为闭盘时间！'));
		} else {
			if ($times == config('market')[$market]['start_time']) {
				if ($minute < intval(cache('market')[$market]['start_minute'])) {
					$this->error(lang('该时间为闭盘时间！'));
				}
			} elseif ($times == config('market')[$market]['stop_time']) {
				if (($minute > config('market')[$market]['stop_minute'])) {
					$this->error(lang('该时间为闭盘时间！'));
				}
			}
		}
		// 处理周六周日是否可交易===开始
		$weeks = date('N',time());
		if (!config('market')[$market]['agree6']) {
			if ($weeks == 6) {
				$this->error(lang('您好，周六为闭盘时间！'));
			}
		}
		if (!config('market')[$market]['agree7']) {
			if ($weeks == 7) {
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
		if ($user['tpwdsetting'] == 3) {}
		if ($user['tpwdsetting'] == 2) {
			if (md5($paypassword) != $user['paypassword']) {
				$this->error(lang('交易密码错误！'));
			}
		}
		if ($user['tpwdsetting'] == 1) {
			if (!session(userid() . 'tpwdsetting')) {
				if (md5($paypassword) != $user['paypassword']) {
					$this->error(lang('交易密码错误！'));
				} else {
					session(userid() . 'tpwdsetting', 1);
				}
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
			//增加手续费分级,做市商0手续费20180128
 			if($user['lv']==0){
 				$trade_fee = config('market')[$market]['fee_buy'];
 			} else {
 				$trade_fee = 0;
 			}
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
			//增加手续费分级,做市商0手续费20180128
 			if($user['lv']==0){
 				$trade_fee = config('market')[$market]['fee_sell'];
 			} else {
 				$trade_fee = 0;
 			}
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
		try{
			$mo = db();
			Db::execute('set autocommit=0');
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
				$rs[] = $finance_nameid = Db::table('tw_trade')->insert(['userid' => userid(), 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 1, 'addtime' => time(), 'status' => 0]);
				
				$finance_mum_user_coin = Db::table('tw_user_coin')->where('userid', userid())->find();
				$finance_hash = md5(userid() . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb.'d'] . $mum . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb.'d'] . MSCODE . 'tp3.net.cn');
				
				$finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb.'d'];

				// 处理资金变更日志-----------------S
				$user_n_info = Db::table('tw_user')->where('id', userid())->find();

				/*
				 * 操作位置（0后台，1前台） position
				 * 动作类型（参考function.php） optype
				 * 资金类型（1人民币） cointype
				 * 类型（0减少，1增加） plusminus
				 * 操作数据 amount
				 */
				$rs[] = Db::table('tw_finance_log')->insert(['username' => $user_n_info['username'], 'adminname' => $user_n_info['username'], 'addtime' => time(), 'plusminus' => 0, 'amount' => $mum, 'optype' => 18, 'cointype' => config("coin")[$rmb]["id"], 'old_amount' => $finance_num_user_coin[$rmb], 'new_amount' => $finance_mum_user_coin[$rmb], 'userid' => userid(), 'adminid' => userid(),'addip'=>$this->request->ip(),'position'=>1]);
				
				$rs[] = Db::table('tw_finance_log')->insert(['username' => $user_n_info['username'], 'adminname' => $user_n_info['username'], 'addtime' => time(), 'plusminus' => 1, 'amount' => $mum, 'optype' => 20, 'cointype' => config("coin")[$rmb]["id"], 'old_amount' => $finance_num_user_coin[$rmb. 'd'], 'new_amount' => $finance_mum_user_coin[$rmb. 'd'], 'userid' => userid(), 'adminid' => userid(),'addip'=>$this->request->ip(),'position'=>1]);
				// 处理资金变更日志-----------------E
				
				if ($finance['mum'] < $finance_num) {
					$finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
				} else {
					$finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
				}

				$rs[] = Db::table('tw_finance')->insert(['userid' => userid(), 'coinname' => $rmb, 'num_a' => $finance_num_user_coin[$rmb], 'num_b' => $finance_num_user_coin[$rmb.'d'], 'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb.'d'], 'fee' => $mum, 'type' => 2, 'name' => 'trade', 'nameid' => $finance_nameid, 'remark' => lang('交易中心-委托买入-市场') . $market, 'mum_a' => $finance_mum_user_coin[$rmb], 'mum_b' => $finance_mum_user_coin[$rmb.'d'], 'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb.'d'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status]);
				
			} else if ($type == 2) {
				if ($user_coin[$xnb] < $num) {
					throw new \Think\Exception(cache('coin')[$xnb]['title'] . lang('余额不足！'));
				}

				$fin_user_coin = Db::table('tw_user_coin')->where('userid', userid())->find();//处理资金变更日志
				$rs[] = Db::table('tw_user_coin')->where('userid', userid())->setDec($xnb, $num);
				$rs[] = Db::table('tw_user_coin')->where('userid', userid())->setInc($xnb . 'd', $num);
				$rs[] = Db::table('tw_trade')->insert(['userid' => userid(), 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 2, 'addtime' => time(), 'status' => 0]);
				$fin_user_coin_new = Db::table('tw_user_coin')->where('userid', userid())->find();//处理资金变更日志

				// 处理资金变更日志-----------------S
				$user_n_info = Db::table('tw_user')->where('id', userid())->find();

				/*
				 * 操作位置（0后台，1前台） position
				 * 动作类型（参考function.php） optype
				 * 资金类型（1人民币） cointype
				 * 类型（0减少，1增加） plusminus
				 * 操作数据 amount
				 */
				$rs[] = Db::table('tw_finance_log')->insert(['username' => $user_n_info['username'], 'adminname' => $user_n_info['username'], 'addtime' => time(), 'plusminus' => 0, 'amount' => $num, 'optype' => 19, 'cointype' => config("coin")[$xnb]["id"], 'old_amount' => $fin_user_coin[$xnb], 'new_amount' => $fin_user_coin_new[$xnb], 'userid' => userid(), 'adminid' => userid(),'addip'=>$this->request->ip(),'position'=>1]);

				$rs[] = Db::table('tw_finance_log')->insert(['username' => $user_n_info['username'], 'adminname' => $user_n_info['username'], 'addtime' => time(), 'plusminus' => 1, 'amount' => $num, 'optype' => 21, 'cointype' => config("coin")[$xnb]["id"], 'old_amount' => $fin_user_coin[$xnb. 'd'], 'new_amount' => $fin_user_coin_new[$xnb. 'd'], 'userid' => userid(), 'adminid' => userid(),'addip'=>$this->request->ip(),'position'=>1]);
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
		} catch(\Think\Exception $e) {
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
		if ($cTrade) {
		    $cstatus=$cTrade['status'];
		    $cdeal=$cTrade['deal'];
		    $cnum=$cTrade['num'];
		    if ($cdeal>$cnum) {
		        Db::table('tw_trade')->where('id', $corderid)->update(['deal' => Num($cnum),'status' => 1]);
		        Db::execute('commit');
		        Db::execute('unlock tables');
		        $cstatus=1;
		        $cdeal=$cnum;
		    }
		    if ($cstatus==1) {
		        $this->success(lang('交易成功！'));
		    }
		    if ($cstatus==0) {
		        if ($cdeal>0) {
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
	
	public function matchingTradeall($market = NULL)
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
		$invit_coin = config('market')[$market]['invit_coin'];
		$invit_1 = config('market')[$market]['invit_1'];
		$invit_2 = config('market')[$market]['invit_2'];
		$invit_3 = config('market')[$market]['invit_3'];
		
		$new_price = config('market')[$market]['new_price'];
		
		$mo = db();
		$new_trade_btchanges = 0;

		for (; true; ) {//先查找会员订单,如果找不到会员订单,再成交虚拟订单20170919
			// 匹配非0会员↓
			// $buy = Db::table('tw_trade')->where('market', $market,'userid' => array('gt',0), 'type' => 1, 'status' => 0)->order('price desc,id asc')->find();
			// if(!$buy){
			// 匹配所有会员↓
			$buy = Db::table('tw_trade')->where('market', $market)->where('type', 1)->where('status', 0)->order('price desc,id asc')->find();
			// }
			// $sell = Db::table('tw_trade')->where('market', $market,'userid' => array('gt',0), 'type' => 2, 'status' => 0)->order('price asc,id asc')->find();
			// if(!$sell){
			$sell = Db::table('tw_trade')->where('market', $market)->where('type', 2)->where('status', 0)->order('price asc,id asc')->find();
			// }

			if ($sell['id'] < $buy['id']) { $type = 1; } else { $type = 2; }

			if ($buy && $sell && (0 <= floatval($buy['price']) - floatval($sell['price']))) {
				$rs = [];
				if ($buy['num'] <= $buy['deal']) { }
				if ($sell['num'] <= $sell['deal']) { }

				// $amount = min(round($buy['num'] - $buy['deal'], 8 - config('market')[$market]['round']), round($sell['num'] - $sell['deal'], 8 - config('market')[$market]['round']]);
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
				} else { break; }

				if (!$price) {
					$log = '错误2交易市场' . $market . '出错：买入订单:' . $buy['id'] . '  卖出订单：' . $sell['id'] . '  交易方式：' . $type . ' 成交数量' . $amount . "\n";
					$log .= 'ERR: 成交价格出错，价格是' . $price;
					break;
				} else {
					// TODO: SEPARATE
					$price = round($price, config('market')[$market]['round']);
				}

				$mum = round($price * $amount, 8);
				if (!$mum) {
					$log = '错误3交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . "\n";
					$log .= 'ERR: 成交总额出错，总额是' . $mum;
					mlog($log);
					break;
				} else {
					$mum = round($mum, 8);
				}

				if ($fee_buy) {
					$buyuser = Db::name('User')->where('id', $buy['userid'])->find();
					if($buyuser['lv'] == 0){
						$buy_fee = round(($mum / 100) * $fee_buy, 8);
						$buy_save = round(($mum / 100) * (100 + $fee_buy),8);
					} else {
						$buy_fee = 0;
						$buy_save = $mum;
					}
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
					$selluser = Db::name('User')->where('id', $buy['userid'])->find();
					if ($selluser['lv']==0) {
						$sell_fee = round(($mum / 100) * $fee_sell, 8);
						$sell_save = round(($mum / 100) * (100 - $fee_sell), 8);
					} else {
						$sell_fee = 0;
						$sell_save = $mum;
					}
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

				if ($buy['userid'] > 0) {
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
					if ($buy_save <= round($user_buy[$rmb . 'd'], 8)) {
						$save_buy_rmb = $buy_save;
					} else if ($buy_save <= round($user_buy[$rmb . 'd'], 8) + 1) {
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
					$save_buy_rmb = 0;
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
				Db::execute('lock tables tw_trade write,tw_trade_log write,tw_user write,tw_user_coin write,tw_invit write ,tw_finance write,tw_coin write,tw_mining write,tw_market read,tw_config read');
				
				$rs[] = Db::table('tw_trade')->where('id', $buy['id'])->setInc('deal', $amount);
				$rs[] = Db::table('tw_trade')->where('id', $sell['id'])->setInc('deal', $amount);
				$rs[] = $finance_nameid = Db::table('tw_trade_log')->insert(['userid' => $buy['userid'], 'peerid' => $sell['userid'], 'market' => $market, 'price' => $price, 'num' => $amount, 'mum' => $mum, 'type' => $type, 'fee_buy' => $buy_fee, 'fee_sell' => $sell_fee, 'addtime' => time(), 'status' => 1]);

				if ($buy['userid']>0) {
					$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setInc($xnb, $amount);
					$finance = Db::table('tw_finance')->where('userid', $buy['userid'])->order('id desc')->find();
					$finance_num_user_coin = Db::table('tw_user_coin')->where('userid', $buy['userid'])->find();

					$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setDec($rmb . 'd', $save_buy_rmb);
					$finance_mum_user_coin = Db::table('tw_user_coin')->where('userid', $buy['userid'])->find();
					$finance_hash = md5($buy['userid'] . $finance_num_user_coin[config('app.anchor_cny')] . $finance_num_user_coin[config('app.anchor_cny').'d'] . $mum . $finance_mum_user_coin[config('app.anchor_cny')] . $finance_mum_user_coin[config('app.anchor_cny').'d'] . MSCODE . 'tp3.net.cn');
					$finance_num = $finance_num_user_coin[config('app.anchor_cny')] + $finance_num_user_coin[config('app.anchor_cny').'d'];

					if ($finance['mum'] < $finance_num) {
						$finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
					} else {
						$finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
					}

					$rs[] = Db::table('tw_finance')->insert(['userid' => $buy['userid'], 'coinname' => config('app.anchor_cny'), 'num_a' => $finance_num_user_coin[config('app.anchor_cny')], 'num_b' => $finance_num_user_coin[config('app.anchor_cny').'d'], 'num' => $finance_num_user_coin[config('app.anchor_cny')] + $finance_num_user_coin[config('app.anchor_cny').'d'], 'fee' => $save_buy_rmb, 'type' => 2, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => '交易中心-成功买入-市场' . $market, 'mum_a' => $finance_mum_user_coin[config('app.anchor_cny')], 'mum_b' => $finance_mum_user_coin[config('app.anchor_cny').'d'], 'mum' => $finance_mum_user_coin[config('app.anchor_cny')] + $finance_mum_user_coin[config('app.anchor_cny').'d'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status]);
					$finance = Db::table('tw_finance')->where('userid', $buy['userid'])->order('id desc')->find();
				} else {
					$finance = 1; // 如果用户是0,设置为1
				}

				if($sell['userid']>0){
					$finance_num_user_coin = Db::table('tw_user_coin')->where('userid', $sell['userid'])->find();
					// var_dump($finance_num_user_coin);die;
					$rs[] = Db::table('tw_user_coin')->where('userid', $sell['userid'])->setInc($rmb, $sell_save);
					$finance_mum_user_coin = Db::table('tw_user_coin')->where('userid', $sell['userid'])->find();
					$finance_hash = md5($sell['userid'] . $finance_num_user_coin[config('app.anchor_cny')] . $finance_num_user_coin[config('app.anchor_cny').'d'] . $mum . $finance_mum_user_coin[config('app.anchor_cny')] . $finance_mum_user_coin[config('app.anchor_cny').'d'] . MSCODE . 'tp3.net.cn');
					// var_dump($finance);die;

					$finance_num = $finance_num_user_coin[config('app.anchor_cny')] + $finance_num_user_coin[config('app.anchor_cny').'d'];
					if ($finance['mum'] < $finance_num) {
						$finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
					} else {
						$finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
					}

					// var_dump($finance_status);die;
					$rs[] = Db::table('tw_finance')->insert(['userid' => $sell['userid'], 'coinname' => config('app.anchor_cny'), 'num_a' => $finance_num_user_coin[config('app.anchor_cny')], 'num_b' => $finance_num_user_coin[config('app.anchor_cny').'d'], 'num' => $finance_num_user_coin[config('app.anchor_cny')] + $finance_num_user_coin[config('app.anchor_cny').'d'], 'fee' => $save_buy_rmb, 'type' => 1, 'name' => 'tradelog', 'nameid' => $finance_nameid, 'remark' => '交易中心-成功卖出-市场' . $market, 'mum_a' => $finance_mum_user_coin[config('app.anchor_cny')], 'mum_b' => $finance_mum_user_coin[config('app.anchor_cny').'d'], 'mum' => $finance_mum_user_coin[config('app.anchor_cny')] + $finance_mum_user_coin[config('app.anchor_cny').'d'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status]);
					// die('ok');
					$rs[] = Db::table('tw_user_coin')->where('userid', $sell['userid'])->setDec($xnb . 'd', $save_sell_xnb);
				}

				$buy_list = Db::table('tw_trade')->where('id', $buy['id'])->where('status', 0)->find();
				if ($buy_list) {
					if ($buy_list['num'] <= $buy_list['deal']) {
						$rs[] = Db::table('tw_trade')->where('id', $buy['id'])->setField('status', 1);
					}
				}

				$sell_list = Db::table('tw_trade')->where('id', $sell['id'])->where('status', 0)->find();
				if ($sell_list) {
					if ($sell_list['num'] <= $sell_list['deal']) {
						$rs[] = Db::table('tw_trade')->where('id', $sell['id'])->setField('status', 1);
					}
				}

				if ($price < $buy['price']) {
					$chajia_dong = round((($amount * $buy['price']) / 100) * (100 + $fee_buy), 8);
					$chajia_shiji = round((($amount * $price) / 100) * (100 + $fee_buy), 8);
					$chajia = round($chajia_dong - $chajia_shiji, 8);

					if ($chajia && $buy['userid']>0) {//不处理0的用户
						$chajia_user_buy = Db::table('tw_user_coin')->where('userid', $buy['userid'])->find();

						if ($chajia <= round($chajia_user_buy[$rmb . 'd'], 8)) {
							$chajia_save_buy_rmb = $chajia;
						} else if ($chajia <= round($chajia_user_buy[$rmb . 'd'], 8) + 1) {
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

				$you_buy = Db::table('tw_trade')
                    ->where('market', 'like', '%' . $rmb . '%')
                    ->where('status', 0)
                    ->where('userid', $buy['userid'])
                    ->find();
				$you_sell = Db::table('tw_trade')
                    ->where('market', 'like', '%' . $xnb . '%')
                    ->where('status', 0)
                    ->where('userid', $sell['userid'])
                    ->find();
				
				// var_dump($you_sell);die;
				
				if (!$you_buy) {
					$you_user_buy = Db::table('tw_user_coin')->where('userid', $buy['userid'])->find();
					if (0 < $you_user_buy[$rmb . 'd']) {
						$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setField($rmb . 'd', 0);
						$rs[] = Db::table('tw_user_coin')->where('userid', $buy['userid'])->setInc($rmb, $you_user_buy[$rmb .'d']);
					}
				}
				if (!$you_sell) {
					$you_user_sell = Db::table('tw_user_coin')->where('userid', $sell['userid'])->find();
					if (0 < $you_user_sell[$xnb . 'd']) {
						$rs[] = Db::table('tw_user_coin')->where('userid', $sell['userid'])->setField($xnb . 'd', 0);
						$rs[] = Db::table('tw_user_coin')->where('userid', $sell['userid'])->setInc($rmb, $you_user_sell[$xnb .'d']);
					}
				}

				$invit_buy_user = Db::table('tw_user')->where('id', $buy['userid'])->find();
				$invit_sell_user = Db::table('tw_user')->where('id', $sell['userid'])->find();
				$xnblx = Db::name('Coin')->where('name', $xnb)->find();//交易的虚拟币类型

				if($rmb == config('app.anchor_cny')){ $rmb1 = $invit_coin; } else { $rmb1 = $rmb; } // 交易佣金返现类型
				
				// 查询市场最新成交价
				$markets = Db::name('market')->where('name', $rmb1.'_'.config('app.anchor_cny'))->field('new_price')->find();
				if ($new_price) {$new_price = $price;} else {$new_price = 0.001;} //适用于人民币交易区
				
				if ($invit_buy && $buy['userid']>0) {
					// 买入交易佣金赠送给上家
					if ($invit_1) {
						if ($buy_fee) {
							if ($invit_buy_user['invit_1']) {
								//$invit_buy_save_1 = round(($buy_fee / 100) * $invit_1, 7);
								// 赠送数量公式 ((盈利交易手续费  * (赠送比例 / 100)) * 当前市场价格) / 赠送币种当前价格
								$invit_buy_save_1 = round((($buy_fee * ($invit_1 / 100)) * $new_price) / $markets['new_price'], 8);
								if ($invit_buy_save_1) {
									$rs[] = Db::table('tw_invit')->insert(['userid' => $invit_buy_user['invit_1'], 'invit' => $buy['userid'], 'name' => '一代买入赠送', 'type' => $xnblx['title'].'买入交易赠送'.strtoupper($rmb1), 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_1, 'addtime' => time(), 'status' => 0,'coin'=>strtoupper($rmb1)]);
								}
							}
							
							if ($invit_buy_user['invit_2']) {
								//$invit_buy_save_2 = round(($buy_fee / 100) * $invit_2, 7);
								// 赠送数量公式 ((盈利交易手续费  * (赠送比例 / 100)) * 当前市场价格) / 赠送币种当前价格
								$invit_buy_save_2 = round((($buy_fee * ($invit_2 / 100)) * $new_price) / $markets['new_price'], 8);
								if ($invit_buy_save_2) {
									$rs[] = Db::table('tw_invit')->insert(['userid' => $invit_buy_user['invit_2'], 'invit' => $buy['userid'], 'name' => '二代买入赠送', 'type' => $xnblx['title'].'买入交易赠送'.strtoupper($rmb1), 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_2, 'addtime' => time(), 'status' => 0,'coin'=>strtoupper($rmb1)]);
								}
							}
							
							if ($invit_buy_user['invit_3']) {
								//$invit_buy_save_3 = round(($buy_fee / 100) * $invit_3, 7);
								// 赠送数量公式 ((盈利交易手续费  * (赠送比例 / 100)) * 当前市场价格) / 赠送币种当前价格
								$invit_buy_save_3 = round((($buy_fee * ($invit_3 / 100)) * $new_price) / $markets['new_price'], 8);
								if ($invit_buy_save_3) {
									$rs[] = Db::table('tw_invit')->insert(['userid' => $invit_buy_user['invit_3'], 'invit' => $buy['userid'], 'name' => '三代买入赠送', 'type' => $xnblx['title'].'买入交易赠送'.strtoupper($rmb1), 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_3, 'addtime' => time(), 'status' => 0,'coin'=>strtoupper($rmb1)]);
								}
							}
						}
					}

					// 卖出交易佣金赠送给上家
					if ($invit_sell && $invit_sell['userid']>0) {//不处理0用户
						if ($sell_fee) {
							if ($invit_sell_user['invit_1']) {
								//$invit_sell_save_1 = round(($sell_fee / 100) * $invit_1, 7);
								// 赠送数量公式 ((盈利交易手续费  * (赠送比例 / 100)) * 当前市场价格) / 赠送币种当前价格
								$invit_sell_save_1 = round((($sell_fee * ($invit_1 / 100)) * $new_price) / $markets['new_price'], 8);
								if ($invit_sell_save_1) {
									$rs[] = Db::table('tw_invit')->insert(['userid' => $invit_sell_user['invit_1'], 'invit' => $sell['userid'], 'name' => '一代卖出赠送', 'type' => $xnblx['title'].'卖出交易赠送'.strtoupper($rmb1), 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_1, 'addtime' => time(), 'status' => 0,'coin'=>strtoupper($rmb1)]);
								}
							}
							
							if ($invit_sell_user['invit_2']) {
								//$invit_sell_save_2 = round(($sell_fee / 100) * $invit_2, 7);
								// 赠送数量公式 ((盈利交易手续费  * (赠送比例 / 100)) * 当前市场价格) / 赠送币种当前价格
								$invit_sell_save_2 = round((($sell_fee * ($invit_2 / 100)) * $new_price) / $markets['new_price'], 8);
								if ($invit_sell_save_2) {
									$rs[] = Db::table('tw_invit')->insert(['userid' => $invit_sell_user['invit_2'], 'invit' => $sell['userid'], 'name' => '二代卖出赠送', 'type' => $xnblx['title'].'卖出交易赠送'.strtoupper($rmb1), 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_2, 'addtime' => time(), 'status' => 0,'coin'=>strtoupper($rmb1)]);
								}
							}

							if ($invit_sell_user['invit_3']) {
								//$invit_sell_save_3 = round(($sell_fee / 100) * $invit_3, 7);
								// 赠送数量公式 ((盈利交易手续费  * (赠送比例 / 100)) * 当前市场价格) / 赠送币种当前价格
								$invit_sell_save_3 = round((($sell_fee * ($invit_3 / 100)) * $new_price) / $markets['new_price'], 8);
								if ($invit_sell_save_3) {
									$rs[] = Db::table('tw_invit')->insert(['userid' => $invit_sell_user['invit_3'], 'invit' => $sell['userid'], 'name' => '三代卖出赠送', 'type' => $xnblx['title'].'卖出交易赠送'.strtoupper($rmb1), 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_3, 'addtime' => time(), 'status' => 0,'coin'=>strtoupper($rmb1)]);
								}
							}
						}
					}
				}
				
				
				// 交易挖矿模块
				$Configs = Db::name('config')->where('id', 1)->find();
				$coin_name_s = $Configs['mining_coin']; // 交易挖矿赠送币种
				$mining_coin_num = $Configs['mining_coin_num']; // 交易挖矿比例
				
				// 查询市场最新成交价
				$markets_2 = Db::name('market')->where('name', $coin_name_s.'_'.config('app.anchor_cny'))->field('new_price')->find();
				
				if ($Configs['mining_type'] == 1) {
					// 判断是否自买自卖 && 判断是否做市商
					if ($buy['userid'] == $sell['userid'] || $buyuser['lv']==1 || $selluser['lv']==1) {}else {
						if ($mining_coin_num && $buy['userid']>0) {
							// 赠送数量公式 ((盈利交易手续费  * (赠送比例 / 100)) * 当前市场价格) / 赠送币种当前价格
							$mining_buy_save = round((($buy_fee * ($mining_coin_num / 100)) * $new_price) / $markets_2['new_price'], 8);

							$rs[] = Db::table('tw_mining')->insert(['userid' => $buy['userid'], 'name' => '交易挖矿买入奖励', 'type' => '买入'.$xnblx['title'].'交易挖矿赠送'.strtoupper($coin_name_s), 'num' => $amount, 'mum' => $mum, 'fee' => $mining_buy_save, 'addtime' => time(), 'status' => 0,'coin'=>strtoupper($coin_name_s)]);
						}
						if ($mining_coin_num && $sell['userid']>0) {
							
							// 赠送数量公式 ((盈利交易手续费  * (赠送比例 / 100)) * 当前市场价格) / 赠送币种当前价格
							$mining_sell_save = round((($sell_fee * ($mining_coin_num / 100)) * $new_price) / $markets_2['new_price'], 8);

							$rs[] = Db::table('tw_mining')->insert(['userid' => $sell['userid'], 'name' => '交易挖矿卖出奖励', 'type' => '卖出'.$xnblx['title'].'交易挖矿赠送'.strtoupper($coin_name_s), 'num' => $amount, 'mum' => $mum, 'fee' => $mining_sell_save, 'addtime' => time(), 'status' => 0,'coin'=>strtoupper($coin_name_s)]);
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
			$new_price = round(Db::name('TradeLog')->where('market', $market)->where('status', 1)->order('id desc')->value('price'), 6);
			$buy_price = round(Db::name('Trade')->where('type', 1)->where('market', $market)->where('status', 0)->max('price'), 6);
			$sell_price = round(Db::name('Trade')->where('type', 2)->where('market', $market)->where('status', 0)->min('price'), 6);
			
			$min_price = round(Db::name('TradeLog')->where('market', $market)
                ->where('addtime', 'gt', time() - (60 * 60 * 24))
			    ->min('price'), 6);
			$max_price = round(Db::name('TradeLog')->where('market', $market)
                ->where('addtime', 'gt', time() - (60 * 60 * 24))
			    ->max('price'), 6);
			$volume = round(Db::name('TradeLog')->where('market', $market)
                ->where('addtime', 'gt', time() - (60 * 60 * 24))
			    ->sum('num'), 6);
			$sta_price = round(Db::name('TradeLog')->where('market', $market)
                ->where('status', 1)
                ->where('addtime', 'gt', time() - (60 * 60 * 24))
                ->order('id asc')->value('price'), 6);
			
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
			
			// 计算涨跌幅
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