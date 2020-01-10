<?php
namespace app\home\controller;

use think\Db;

class Ajax extends Home
{
	//上传用户身份证
	public function imgUser()
	{
		if (!userid()) {
			echo "nologin";
		}
		
		$upload = new \Think\Upload();
		$upload->maxSize = 3145728;
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		$upload->rootPath = './Upload/idcard/';
		$upload->autoSub = false;
		$info = $upload->upload();
		foreach ($info as $k => $v) {
			$path = $v['savepath'] . $v['savename'];
			echo $path;
			exit();
		}
	}
	
	public function getJsonMenu($ajax = 'json')
	{
		$data = (config('app.develop') ? null : cache('getJsonMenu'));

		if (!$data) {
			foreach (cache('market') as $k => $v) {
				$v['xnb'] = explode('_', $v['name'])[0];
				$v['rmb'] = explode('_', $v['name'])[1];
				$data[$k]['name'] = $v['name'];
				$data[$k]['img'] = $v['xnbimg'];
				$data[$k]['title'] = $v['title'];
			}

			cache('getJsonMenu', $data);
		}

		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}
	}
	
	public function getJsonTopshow($market = NULL, $ajax = 'json')
	{
		$data = (config('app.develop') ? null : cache('getJsonTopshow' . $market));
		$showcoin = Db::name('market')->where('fshow', 1)->select();
		foreach ($showcoin as $k => $v) {
			$v['xnb'] = explode('_', $v['name'])[0];
			$v['rmb'] = explode('_', $v['name'])[1];
			$data[$k]['name'] = $v['xnb'] ;
			$data[$k]['title'] = strtoupper($v['xnb']) ;
			$data[$k]['new_price'] =round($v['new_price'],2) ;
			$data[$k]['change'] = $v['change'];
			
			if ($v['change']>0) {
				$data[$k]['zd']=1;//涨
			} elseif ($v['change']<0) {
				$data[$k]['zd']=2;//跌
			} else {
				$data[$k]['zd']=0;//平
			}
			
			$data[$k]['cje'] = round($v['volume'] * $v['new_price'], 2);

			if ($data[$k]['volume'] > 10000 && $data[$k]['volume'] < 100000000) {
				$data[$k]['cjl'] = (intval($data[$k]['volume'] / 10000*100)/100) . "万";
			}
			if ($data[$k]['volume'] > 100000000) {
				$data[$k]['cjl'] = (intval($data[$k]['volume'] / 100000000*100)/100) . "亿";
			}
			if ($data[$k]['cje'] > 10000 && $data[$k]['cje'] < 100000000) {
				$data[$k]['cje']= (intval($data[$k]['cje'] / 10000*100)/100) . "万";
			}
			if ($data[$k]['cje'] > 100000000) {
				$data[$k]['cje'] = (intval($data[$k]['cje'] / 100000000*100)/100) . "亿";
			}
		}

		cache('getJsonTopshow' , $data);
		if ($ajax) {
			// var_dump(json_encode($data));die;
			exit(json_encode($data));
		} else {
			return $data;
		}
	}
	
	/** 自定义分区查询  改.HAOMA20181030 **/
	public function allcoin_a($id=1,$ajax='json')
	{
		$trandata_data = [];
		$trandata_data['info'] = "数据异常";
		$trandata_data['status'] = 0;
		$trandata_data['url'] = "";
		
        // 市场交易记录
        $marketLogs = [];
        foreach (cache('market') as $k => $v) {
			$_tmp = null;
            if (!empty($_tmp)) {
                $marketLogs[$k] = $_tmp;
            } else {
				$_data = [];
                $tradeLog = Db::name('TradeLog')->where('status', 1)->where('market', $k)->order('id desc')->limit(10)->select();
                foreach ($tradeLog as $_k => $v) {
					$_data['tradelog'][$_k]['addtime'] = date('m-d H:i:s', $v['addtime']);
					$_data['tradelog'][$_k]['addtimes'] = $v['addtime'];
                    $_data['tradelog'][$_k]['type'] = $v['type'];
                    $_data['tradelog'][$_k]['price'] = $v['price'] * 1;
                    $_data['tradelog'][$_k]['num'] = round($v['num'], 6);
                    $_data['tradelog'][$_k]['mum'] = round($v['mum'], 2);
                }
                $marketLogs[$k] = $_data;
                cache('getTradelog' . $k, $_data);
            }
        }
		
		$volume_24h = [];
		$tradeAmount_24h = [];
        if ($marketLogs) {
            foreach (cache('market') as $k => $v) {
				$_tradeLogs['num'] = Db::name('TradeLog')
                    ->where('status', 1)
                    ->where('market', $k)
					->where('addtime', 'gt', time() - (60 * 60 * 24))
                    ->sum('num');

                $_tradeLogs['mum'] = Db::name('TradeLog')
                    ->where('status', 1)
                    ->where('market', $k)
                    ->where('addtime', 'gt', time() - (60 * 60 * 24))
                    ->sum('mum');
				
                if ($_tradeLogs) {
					$volume_24h[$k] = round($_tradeLogs['num'], 4); // 24小时 交易量
                    $tradeAmount_24h[$k] = round($_tradeLogs['mum'], 4); // 24小时 交易额
                }
            }
        }
		
/*        if ($marketLogs) {
            $_lasttime = time() - 86400;
						
            foreach (cache('market') as $k => $v) {
                $tradeLog = isset($marketLogs[$k]['tradelog']) ? $marketLogs[$k]['tradelog'] : null;
                if ($tradeLog) {
					$num_s = 0;
                    $mum_s = 0;
                    foreach ($tradeLog as $_k => $_v) {
                        if ($_v['addtimes'] < $_lasttime) {
                            continue;
                        }
						$num_s += $_v['num'];
						$mum_s += $_v['mum'];
                    }
					$volume_24h[$k] = $num_s; // 24小时 交易量
                    $tradeAmount_24h[$k] = $mum_s; // 24小时 交易额
                }
            }
        }*/
		
		if (!$data) {
			$trandata_data['info'] = "数据正常";
			$trandata_data['status'] = 1;
			$trandata_data['url'] = "";
			
			foreach (cache('market') as $k => $v)
			{
				if ($v['jiaoyiqu'] == $id) {
					$xnb = strtoupper(explode('_', $v['name'])[0]);
					$market = strtoupper(explode('_', $v['name'])[1]);
					
					//币种简称
					$trandata_data['url'][$k][0] = $xnb;
					//币种市场
					$trandata_data['url'][$k][1] = $market;
					//最新成交价
					$trandata_data['url'][$k][2] = round($v['new_price'], $v['round']);
					//买一价
					$trandata_data['url'][$k][3] = round($v['buy_price'], $v['round']);
					//卖一价
					$trandata_data['url'][$k][4] = round($v['sell_price'], $v['round']);
					//交易额
					$trandata_data['url'][$k][5] = isset($tradeAmount_24h[$k]) ? $tradeAmount_24h[$k] : 0;//round($v['volume'] * $v['new_price'], 2) * 1;
					
					$trandata_data['url'][$k][6] = '';
					
					//交易量
					$trandata_data['url'][$k][7] = isset($volume_24h[$k]) ? $volume_24h[$k] : 0;//round($v['volume'], 4) * 1;
					
					//涨跌幅
					$trandata_data['url'][$k][8] = round($v['change'], 2);
					//链接专用
					$trandata_data['url'][$k][9] = $v['name'];
					//图图标地址
					$trandata_data['url'][$k][10] = $v['xnbimg'];
					//最高价
					$trandata_data['url'][$k][11] = round($v['max_price'], $v['round']);
					//最低价
					$trandata_data['url'][$k][12] = round($v['min_price'], $v['round']);
					
					$trandata_data['url'][$k][13] = '';
				}

			}
		}

		if ($ajax) {
			echo json_encode($trandata_data);
			unset($trandata_data);
			exit();
		} else {
			return $trandata_data;
		}
	}
	
	public function index_b_trends($ajax = 'json')
	{
		$data = (config('app.develop') ? null : cache('trends'));
		if (!$data) {
			foreach (cache('market') as $k => $v) {
				$tendency = json_decode($v['tendency'], true);
				$data[$k]['data'] = $tendency;
				$data[$k]['yprice'] = $v['new_price'];
			}
			cache('trends', $data);
		}

		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}
	}

	public function allfinance($ajax = 'json')
	{
		if (!userid()) {
			return false;
		}

		$UserCoin = Db::name('UserCoin')->where('userid', userid())->find();
		$cny['zj'] = 0;

		foreach (cache('coin') as $k => $v) {
			if ($v['name'] == config('app.anchor_cny')) {
				// $cny['ky'] = $UserCoin[$v['name']] * 1;
				// $cny['dj'] = $UserCoin[$v['name'] . 'd'] * 1;
				// $cny['zj'] = $cny['zj'] + $cny['ky'] + $cny['dj'];
				$cny['ky'] = round($UserCoin[$v['name']], 2) * 1;
				$cny['dj'] = round($UserCoin[$v['name'] . 'd'], 2) * 1;
				$cny['zj'] = $cny['zj'] + $cny['ky'] + $cny['dj'];
			} else {
				if (cache('market')[$v['name'].'_'.config('app.anchor_cny')]['new_price']) {
					$jia = config('market')[$v['name'].'_'.config('app.anchor_cny')]['new_price'];
				} else {
					$jia = 1;
				}

				$cny['zj'] = round($cny['zj'] + (($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd']) * $jia), 8) * 1;
			}
		}

		$data = round($cny['zj'], 8);//原显示
		$data = number_format($data,2);//千分位显示
		// $data = NumToStr($data);
		// $data = sprintf("%.4f", $data);
		// $data = round($cny['zj'], 4);

		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}
	}

	public function allsum($ajax = 'json')
	{
		$data = (config('app.develop') ? null : cache('allsum'));

		if (!$data) {
			$data = Db::name('TradeLog')->sum('mum');
			cache('allsum', $data);
		}

		$data = round($data);
		$data = str_repeat('0', 12 - strlen($data)) . (string) $data;

		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}
	}
	
	public function top_coin_menu($ajax = 'json')
	{
		$data = (config('app.develop') ? null : cache('trandata_getTopCoinMenu'));

		$trandata_getCoreConfig = getCoreConfig();
		if(!$trandata_getCoreConfig){
			$this->error('核心配置有误');
		}
		if (!$data) {
			$data = [];

			foreach($trandata_getCoreConfig['indexcat'] as $k=>$v){
				$data[$k][title] = $v;
			}

			foreach (cache('market') as $k => $v) {
 				$v['xnb'] = explode('_', $v['name'])[0];
				$v['rmb'] = explode('_', $v['name'])[1];

				$data_tmp['img'] = $v['xnbimg'];
				$data_tmp['title'] = $v['navtitle'];

				$data[$v['jiaoyiqu']]['data'][$k] = $data_tmp;

				unset($data_tmp);
			}

			cache('trandata_getTopCoinMenu', $data);
		}

		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}
	}

	public function allcoin($ajax = 'json')
	{
		$data = (config('app.develop') ? null : cache('allcoin'));

		if (!$data) {
			foreach (cache('market') as $k => $v) {
				$data[$k][0] = $v['title'];
				$data[$k][1] = round($v['new_price'], $v['round']);
				$data[$k][2] = round($v['buy_price'], $v['round']);
				$data[$k][3] = round($v['sell_price'], $v['round']);
				$data[$k][4] = round($v['volume'] * $v['new_price'], 2) * 1;
				$data[$k][5] = '';
				$data[$k][6] = round($v['volume'], 2) * 1;
				$data[$k][7] = round($v['change'], 2);
				$data[$k][8] = $v['name'];
				$data[$k][9] = $v['xnbimg'];
				$data[$k][10] = '';
			}
			cache('allcoin', $data);
		}

		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}
	}

	public function trends($ajax = 'json')
	{
		$data = (config('app.develop') ? null : cache('trends'));

		if (!$data) {
			foreach (cache('market') as $k => $v) {
				$tendency = json_decode($v['tendency'], true);
				$data[$k]['data'] = $tendency;
				$data[$k]['yprice'] = $v['new_price'];
			}

			cache('trends', $data);
		}

		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}
	}
	
	// 交易中心调用
	public function getJsonTop($market = NULL, $ajax = 'json')
	{
		// 过滤非法字符----------------S
		if (checkstr($market)) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E
		
		$data = (config('app.develop') ? null : cache("getJsonTop" . $market));
		if (!$data) {
			if ($market) {
				$xnb = explode("_", $market)[0];
				$rmb = explode("_", $market)[1];
				
				// 24小时 交易量
				$volume_24h = [];
				$volume_24h = Db::name('TradeLog')
                    ->where('status', 1)
                    ->where('market', $market)
                    ->where('addtime', 'gt', time() - (60 * 60 * 24))
				    ->sum('num');
				$volume_24h = round($volume_24h, 4);
	
/*				foreach (cache("market") as $k => $v) {
					$v["xnb"] = explode("_", $v["name"])[0];
					$v["rmb"] = explode("_", $v["name"])[1];
					$data["list"][$k]["name"] = $v["name"];
					$data["list"][$k]["img"] = $v["xnbimg"];
					$data["list"][$k]["title"] = $v["title"];
					$data["list"][$k]["new_price"] = $v["new_price"];
					$data["list"][$k]["change"] = $v["change"];
					$data["list"][$k]['coin_name'] = strtoupper($v["xnb"]);
				}*/
				
				$data["info"]["img"] = config("market")[$market]["xnbimg"];
				//$data["info"]["title"] = config("market")[$market]["title"];
				$data["info"]["new_price"] = config("market")[$market]["new_price"];
				$data["info"]["max_price"] = config("market")[$market]["max_price"];
				$data["info"]["min_price"] = config("market")[$market]["min_price"];
				$data["info"]["buy_price"] = config("market")[$market]["buy_price"];
				$data["info"]["sell_price"] = config("market")[$market]["sell_price"];
				$data["info"]["volume"] = isset($volume_24h) ? $volume_24h : 0;//C("market")[$market]["volume"];
				$data["info"]["change"] = config("market")[$market]["change"];
				
				cache("getJsonTop" . $market, $data);
			}
		}
		
		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}
	}
	
	/** 交易中心-币种列表  改.HAOMA20181101 **/
	public function getJsonTop2($id=1, $ajax = 'json')
	{
		if (empty($data)) {
			$trandata_data['info'] = "数据正常";
			$trandata_data['status'] = 1;
			$trandata_data['url'] = "";


			foreach (config("market") as $k => $v) {
				if ($v['jiaoyiqu'] == $id) {
					$trandata_data["list"][$k]["name"] = $v["name"];
					$trandata_data["list"][$k]["img"] = $v["xnbimg"];
					$trandata_data["list"][$k]["title"] = $v["title"];
					$trandata_data["list"][$k]["new_price"] = $v["new_price"];
					$trandata_data["list"][$k]["change"] = $v["change"];
					$trandata_data["list"][$k]['coin_name'] = strtoupper($v["xnb"]);
				}
			}
		}

		if ($ajax) {
			exit(json_encode($trandata_data));
		} else {
			return $trandata_data;
		}
	}

	public function getTradelog($market = NULL, $ajax = 'json')
	{
		// 过滤非法字符----------------S
		if (checkstr($market)) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E

		$data = (config('app.develop') ? null : cache('getTradelog' . $market));

		if (!$data) {
			$tradeLog = Db::name('TradeLog')->where('status', 1)->where('market', $market)->order('id desc')->limit(10)->select();
			if ($tradeLog) {
				foreach ($tradeLog as $k => $v) {
					$data['tradelog'][$k]['addtime'] = date('H:i:s', $v['addtime']);
					$data['tradelog'][$k]['type'] = $v['type'];
					$data['tradelog'][$k]['price'] = $v['price'] * 1;
					$data['tradelog'][$k]['num'] = round($v['num'], 6);
					$data['tradelog'][$k]['mum'] = round($v['mum'], 6);
				}
				cache('getTradelog' . $market, $data);
			}
		}

		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}
	}

	public function getDepth($market = NULL, $trade_moshi = 1,$limts = 5, $ajax = 'json')
	{
		// 过滤非法字符----------------S
		if (checkstr($market) || checkstr($trade_moshi)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E

		if (!config('market')[$market]) {
			return null;
		}

		$data_getDepth = (config('app.develop') ? null : cache('getDepth'));
		if (!$data_getDepth[$market][$trade_moshi]) {
			if ($trade_moshi == 1) {
				$limt = $limts;
			}
			if (($trade_moshi == 3) || ($trade_moshi == 4)) {
				$limt = $limts;
			}

			$mo = db();

			if ($trade_moshi == 1) {
				$buy = Db::query('select id,price,sum(num-deal)as nums from tw_trade where status=0 and type=1 and market =\'' . $market . '\' group by price order by price desc limit ' . $limt . ';');
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
				$maxNums = maxArrayKey($buy, 'nums') / 2;
				foreach ($buy as $k => $v) {
					$data['depth']['buy'][$k] = array(floatval($v['price'] * 1), floatval($v['nums'] * 1));
					$data['depth']['buypbar'][$k] = ((($maxNums < $v['nums'] ? $maxNums : $v['nums']) / $maxNums) * 100);
				}
			} else {
				$data['depth']['buy'] = '';
				$data['depth']['buypbar'] = '';
			}

			if ($sell) {
				$maxNums = maxArrayKey($sell, 'nums') / 2;
				foreach ($sell as $k => $v) {
					$data['depth']['sell'][$k] = array(floatval($v['price'] * 1), floatval($v['nums'] * 1));
					$data['depth']['sellpbar'][$k] = ((($maxNums < $v['nums'] ? $maxNums : $v['nums']) / $maxNums) * 100);
				}
			} else {
				$data['depth']['sell'] = '';
				$data['depth']['sellpbar'] = '';
			}

			$data_getDepth[$market][$trade_moshi] = $data;
			cache('getDepth', $data_getDepth);
		} else {
			$data = $data_getDepth[$market][$trade_moshi];
		}

		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}
	}

	public function getEntrustAndUsercoin($market = NULL, $ajax = 'json')
	{
		// 过滤非法字符----------------S
		if (checkstr($market)) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E

		if (!userid()) {
			return null;
		}

		if (!config('market')[$market]) {
			return null;
		}

		$result = db()->query('select id,price,num,deal,mum,type,fee,status,addtime from tw_trade where status=0 and market=\'' . $market . '\' and userid=' . userid() . ' order by id desc limit 10;');

		if ($result) {
			foreach ($result as $k => $v) {
				$data['entrust'][$k]['addtime'] = date('m-d H:i:s', $v['addtime']);
				$data['entrust'][$k]['type'] = $v['type'];
				$data['entrust'][$k]['price'] = $v['price'] * 1;
				$data['entrust'][$k]['num'] = round($v['num'], 6);
				$data['entrust'][$k]['deal'] = round($v['deal'], 6);
				$data['entrust'][$k]['id'] = round($v['id']);
				$data['entrust'][$k]['status'] = $v['status'];
			}
		} else {
			$data['entrust'] = null;
		}

		$userCoin = Db::name('UserCoin')->where('userid', userid())->find();

		if ($userCoin) {
			$xnb = explode('_', $market)[0];
			$rmb = explode('_', $market)[1];
			$data['usercoin']['xnb'] = floatval($userCoin[$xnb]);
			$data['usercoin']['xnbd'] = floatval($userCoin[$xnb . 'd']);
			$data['usercoin']['rmb'] = floatval($userCoin[$rmb]);
			$data['usercoin']['rmbd'] = floatval($userCoin[$rmb . 'd']);
		} else {
			$data['usercoin'] = null;
		}
		// 处理开盘闭盘交易时间===开始
		$times = date('G',time());
		$minute = date('i',time());
		$minute = intval($minute);
		$data['time_state'] = 0;
		if (($times <= config('market')[$market]['start_time'] && $minute < intval(cache('market')[$market]['start_minute']))|| ($times > config('market')[$market]['stop_time'] && $minute>= intval(cache('market')[$market]['stop_minute']))) {
			$data['time_state'] = 1;
		}
		if (($times <config('market')[$market]['start_time'] )|| $times > config('market')[$market]['stop_time']) {
			$data['time_state'] = 1;
		} else {
			if ($times == config('market')[$market]['start_time']) {
				if ($minute< intval(cache('market')[$market]['start_minute'])) {
					$data['time_state'] = 1;
				}
			} elseif ($times == config('market')[$market]['stop_time']) {
				if (( $minute > config('market')[$market]['stop_minute'])) {
					$data['time_state'] = 1;
				}
			}
		}
		// 处理周六周日是否可交易===开始
		$weeks = date('N',time());
		if(!config('market')[$market]['agree6']){
			if($weeks == 6){
				$data['time_state'] = 1;
			}
		}
		if(!config('market')[$market]['agree7']){
			if($weeks == 7){
				$data['time_state'] = 1;
			}
		}
		//处理周六周日是否可交易===结束
		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}
	}

	public function getChat($ajax = 'json')
	{
		$chat = (config('app.develop') ? null : cache('getChat'));

		if (!$chat) {
			$chat = Db::name('Chat')->where('status', 1)->order('id desc')->limit(500)->select();
			cache('getChat', $chat);
		}

		asort($chat);

		if ($chat) {
			foreach ($chat as $k => $v) {
				$data[] = array((int) $v['id'], $v['username'], $v['content']);
			}
		} else {
			$data = '';
		}

		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}
	}

	public function upChat($content)
	{
/*		exit;
		if (!userid()) {
			$this->error('请先登录...');
		}

		$content = msubstr($content, 0, 20, 'utf-8', false);

		if (!$content) {
			$this->error('请先输入内容');
		}

		if (APP_DEMO) {
			$this->error('测试站暂时不能聊天！');
		}

		if (time() < (session('chat' . userid()) + 10)) {
			$this->error('不能发送过快');
		}

		$id = Db::name('Chat')->insert(array('userid' => userid(), 'username' => username(), 'content' => $content, 'addtime' => time(), 'status' => 1));

		if ($id) {
			cache('getChat', null);
			session('chat' . userid(), time());
			$this->success($id);
		} else {
			$this->error('发送失败');
		}*/
	}

	public function upcomment($msgaaa, $s1, $s2, $s3, $xnb)
	{
/*		exit;
		if (empty($msgaaa)) {
			$this->error('提交内容错误');
		}

		if (!check($s1, 'd')) {
			$this->error('技术评分错误');
		}
		if (!check($s2, 'd')) {
			$this->error('应用评分错误');
		}
		if (!check($s3, 'd')) {
			$this->error('前景评分错误');
		}
		
		if (!userid()) {
			$this->error('请先登录！');
		}

		if (Db::name('CoinComment')->where(array(
			'userid'   => userid(),
			'coinname' => $xnb,
			'addtime'  => array('gt', time() - 60)
			)->find()) {
			$this->error('请不要频繁提交！');
		}

		if (Db::name('Coin')->where('name', $xnb)->update(array(
			'tp_zs' => array('exp', 'tp_zs+1'),
			'tp_js' => array('exp', 'tp_js+' . $s1),
			'tp_yy' => array('exp', 'tp_yy+' . $s2),
			'tp_qj' => array('exp', 'tp_qj+' . $s3)
			))) {
			if (Db::name('CoinComment')->insert(array('userid' => userid(), 'coinname' => $xnb, 'content' => $msgaaa, 'addtime' => time(), 'status' => 1))) {
				$this->success('提交成功');
			} else {
				$this->error('提交失败！');
			}
		} else {
			$this->error('提交失败！');
		}*/
	}

	public function subcomment($id, $type)
	{
		// 过滤非法字符----------------S
		if (checkstr($id) || checkstr($type)) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E
		
		if ($type != 1) {
			if ($type != 2) {
				if ($type != 3) {
					$this->error('参数错误！');
				} else {
					$type = 'xcd';
				}
			} else {
				$type = 'tzy';
			}
		} else {
			$type = 'cjz';
		}
		if (!check($id, 'd')) {
			$this->error('参数错误1');
		}

		if (!userid()) {
			$this->error('请先登录！');
		}

		if (cache('subcomment' . userid() . $id)) {
			$this->error('请不要频繁提交！');
		}

		if (Db::name('CoinComment')->where('id', $id)->setInc($type, 1)) {
			cache('subcomment' . userid() . $id, 1);
			$this->success('提交成功');
		} else {
			$this->error('提交失败！');
		}
	}
	
	// C2C获取付款商户信息
	public function c2cPayment($id, $aid, $ajax = 'json')
	{
		// 过滤非法字符----------------S
		if (checkstr($id)) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E
		
		if (!check($id, 'd')) {
			$this->error('参数错误1');
		}

		if (!userid()) {
			$this->error('请先登录！');
		}
		
		$data['agent'] = Db::name('exchange_agent')->where('id', $aid)->find();
		$data['order'] = Db::name('exchange_order')->where('id', $id)->find();
		if ($data['order']['otype']  == 1) {
			if ($data['order']['status']  == 1) {
				$data['order']['status'] = lang('待支付');
			} else {
				$data['order']['status'] = lang('未知');
			}
		}
		
		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}
	}
}
?>