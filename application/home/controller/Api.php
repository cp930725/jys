<?php
/* API平台接口数据 */
namespace app\home\controller;

class Api extends Home
{
	protected function _initialize()
	{
		parent::_initialize();
		$allow_action = array("ticker","ticker2","depth","trades");
		if (!in_array($this->request->action(),$allow_action)) {
			$this->error("非法操作！");
		}
	}

	// 市场行情
	public function ticker($market = NULL, $ajax = 'json')
	{
		// 过滤非法字符----------------S
		if (checkstr($market)) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E

		$data = (config('app.develop') ? null : cache('getJsonTop' . $market));
		if (!$data) {
			if ($market) {
				$xnb = explode('_', $market)[0];
				$rmb = explode('_', $market)[1];
				// foreach (cache('market') as $k => $v) {
				// 	$v['xnb'] = explode('_', $v['name'])[0];
				// 	$v['rmb'] = explode('_', $v['name'])[1];
				// 	$data['list'][$k]['name'] = $v['name'];
				// 	$data['list'][$k]['img'] = $v['xnbimg'];
				// 	$data['list'][$k]['title'] = $v['title'];
				// 	$data['list'][$k]['new_price'] = $v['new_price'];
				// }
				
				// 24小时 交易量
				$volume_24h = round(Db::name('TradeLog')->where(array(
					'market'  => $market,
					'addtime' => array('gt', time() - (60 * 60 * 24))
				)->sum('num'), 4);
				
				$data['date'] = time();
				
				//最新成交价
				$data['ticker']['last'] = config('market')[$market]['new_price'];
				//最高价
				$data['ticker']['high'] = config('market')[$market]['max_price'];
				//最低价
				$data['ticker']['low'] = config('market')[$market]['min_price'];
				//买一价
				$data['ticker']['buy'] = config('market')[$market]['buy_price'];
				//卖一价
				$data['ticker']['sell'] = config('market')[$market]['sell_price'];
				//成交量
				$data['ticker']['vol'] = isset($volume_24h) ? $volume_24h : 0;//C('market')[$market]['volume']; 
				//涨跌幅
				$data['ticker']['change'] = config('market')[$market]['change'];
				
				cache('getJsonTop' . $market, $data);
			}
		}

		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}
	}
	
	public function ticker2($market = NULL, $ajax = 'json')
	{
/*		// 过滤非法字符----------------S
		if (checkstr($market)) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E

		$data = (config('app.develop') ? null : cache('getJsonTop' . $market));
		if (!$data) {
			if ($market) {
				$xnb = explode('_', $market)[0];
				$rmb = explode('_', $market)[1];

				$data['LastDealPrize-'.strtoupper($xnb)]=C('market')[$market]['new_price'];
				cache('getJsonTop' . $market, $data);
			}
		}

		if ($ajax) {
			exit(json_encode($data));
		} else {
			return $data;
		}*/
	}

	public function depth($market = NULL, $trade_moshi = 1, $ajax = 'json')
	{
/*		//dump($market);
		// 过滤非法字符----------------S
		if (checkstr($market) || checkstr($trade_moshi)) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E

		if (!config('market')[$market]) {
			return null;
		}

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

			if ($sell) {
				foreach ($sell as $k => $v) {
					$data['asks'][$k] = array(round(floatval($v['nums'] * 1),3),round(floatval($v['price'] * 1),3));
				}
			} else {
				$data['asks'] = '';
			}

			if ($buy) {
				foreach ($buy as $k => $v) {
					$data['bids'][$k] = array(round(floatval($v['nums'] * 1),3),round(floatval($v['price'] * 1),3));
				}
			} else {
				$data['bids'] = '';
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
		}*/
	}
	
	public function trades()
	{
/*		$input = input('get.');
		if (!$input['since']) {
			$tradeLog = Db::name('TradeLog')->where('market', $input['market'])->order('id desc')->find();
			// foreach ($tradeLog as $k => $v) {
				// $json_data[] = array('tid' => $v['id'], 'date' => $v['addtime'], 'price' => $v['price'], 'amount' => $v['num'], 'trade_type' => $v['type'] == 1 ? 'bid' : 'ask');
			// }
			$json_data[] = array('date' => $tradeLog['addtime'], 'date_ms' => $tradeLog['addtime']*1000, 'price' => $tradeLog['price'], 'amount' => $tradeLog['num'], 'tid' => $tradeLog['id'], 'trade_type' => $tradeLog['type'] == 1 ? 'buy' : 'sell');
			header("Content-type:application/json");
			header('X-Frame-Options: SAMEORIGIN');
			exit(json_encode($json_data));
		} else {
			$tradeLog = Db::name('TradeLog')->where(array(
				'market' => $input['market'],
				'id'     => array('gt', $input['since'])
				)->order('id desc')->select();
			foreach ($tradeLog as $k => $v) {
				$json_data[] = array('date' => $v['addtime'], 'date_ms' => $v['addtime']*1000, 'price' => $v['price'], 'amount' => $v['num'], 'tid' => $v['id'], 'trade_type' => $v['type'] == 1 ? 'buy' : 'sell');
			}
			if (!empty($json_data)) {
				header("Content-type:application/json");
				header('X-Frame-Options: SAMEORIGIN');
				exit(json_encode($json_data));
			}
		}*/
	}
}
?>