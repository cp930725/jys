<?php
namespace app\home\controller;

class Chart extends Home
{
	public function getJsonData($market = NULL, $ajax = 'json')
	{
		// 过滤非法字符----------------S
		if (checkstr($market)) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E

		if ($market) {
			$data = (config('app.develop') ? null : cache('ChartgetJsonData' . $market));

			if (!$data) {
				$data[0] = $this->getTradeBuy($market);
				$data[1] = $this->getTradeSell($market);
				$data[2] = $this->getTradeLog($market);
				cache('ChartgetJsonData' . $market, $data);
			}
			header("Content-type:application/json");
			header('X-Frame-Options: SAMEORIGIN');
			exit(json_encode($data));
		}
	}

	protected function getTradeBuy($market)
	{
		// 过滤非法字符----------------S
		if (checkstr($market)) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E

		$mo = db();
		$buy = Db::query('select id,price,sum(num-deal)as nums from tw_trade  where status=0 and type=1 and market =\'' . $market . '\' group by price order by price desc limit 100;');
		$data = '';

		if ($buy) {
			$maxNums = maxArrayKey($buy, 'nums') / 2;
			foreach ($buy as $k => $v) {
				$data .= '<tr><td class="buy"  width="50">买' . ($k + 1) . '</td><td class="buy"  width="80">' . floatval($v['price']) . '</td><td class="buy"  width="120">' . floatval($v['nums']) . '</td><td  width="100"><span class="buySpan" style="width: ' . ((($maxNums < $v['nums'] ? $maxNums : $v['nums']) / $maxNums) * 100) . 'px;" ></span></td></tr>';
			}
		}
		Vendor("XssFilter.XssFilter","",".php");
		$data=\XssFilter::xss_clean($data);
		return $data;
	}

	protected function getTradeSell($market)
	{
		// 过滤非法字符----------------S
		if (checkstr($market)) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E

		$mo = db();
		$sell = Db::query('select id,price,sum(num-deal)as nums from tw_trade where status=0 and type=2 and market =\'' . $market . '\' group by price order by price asc limit 100;');
		$data = '';

		if ($sell) {
			$maxNums = maxArrayKey($sell, 'nums') / 2;

			foreach ($sell as $k => $v) {
				$data .= '<tr><td class="sell"  width="50">卖' . ($k + 1) . '</td><td class="sell"  width="80">' . floatval($v['price']) . '</td><td class="sell"  width="120">' . floatval($v['nums']) . '</td><td style="width: 100px;"><span class="sellSpan" style="width: ' . ((($maxNums < $v['nums'] ? $maxNums : $v['nums']) / $maxNums) * 100) . 'px;" ></span></td></tr>';
			}
		}
		Vendor("XssFilter.XssFilter","",".php");
		$data=\XssFilter::xss_clean($data);
		return $data;
	}

	protected function getTradeLog($market)
	{
		// 过滤非法字符----------------S
		if (checkstr($market)) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E

		$log = Db::name('TradeLog')->where('status', 1, 'market' => $market)->order('id desc')->limit(100)->select();
		$data = '';

		if ($log) {
			foreach ($log as $k => $v) {
				if ($v['type'] == 1) {
					$type = 'buy';
				} else {
					$type = 'sell';
				}

				$data .= '<tr><td class="' . $type . '"  width="70">' . date('H:i:s', $v['addtime']) . '</td><td class="' . $type . '"  width="70">' . floatval($v['price']) . '</td><td class="' . $type . '"  width="100">' . floatval($v['num']) . '</td><td class="' . $type . '">' . floatval($v['mum']) . '</td></tr>';
			}
		}
		Vendor("XssFilter.XssFilter","",".php");
		$data=\XssFilter::xss_clean($data);
		return $data;
	}

	public function trend()
	{
		// TODO: SEPARATE
		$input = input('get.');
		$market = (is_array(cache('market')[$input['market']]) ? trim($input['market']) : config('market_mr'));
		$this->assign('market', $market);
		return $this->fetch();
	}

	public function getMarketTrendJson()
	{
		// TODO: SEPARATE
		$input = input('get.');
		$market = (is_array(cache('market')[$input['market']]) ? trim($input['market']) : config('market_mr'));
		$data = (config('app.develop') ? null : cache('ChartgetMarketTrendJson' . $market));

		if (!$data) {
			$data = Db::name('TradeLog')->where(array(
				'market'  => $market,
				'addtime' => array('gt', time() - (60 * 60 * 24 * 30 * 2))
				)->select();
			cache('ChartgetMarketTrendJson' . $market, $data);
		}
		$json_data=array();
		foreach ($data as $k => $v) {
			$json_data[$k][0] = intval($v['addtime']);
			$json_data[$k][1] = floatval($v['price']);
		}
		header("Content-type:application/json");
		header('X-Frame-Options: SAMEORIGIN');
		exit(json_encode($json_data));
	}

	public function ordinary()
	{
		// TODO: SEPARATE
		$input = input('get.');
		$market = (is_array(cache('market')[$input['market']]) ? trim($input['market']) : config('market_mr'));
		$this->assign('market', $market);
		return $this->fetch();
	}

	public function getMarketOrdinaryJson()
	{
		// TODO: SEPARATE
		$input = input("get.");
		$market = (is_array(cache("market")[$input["market"]]) ? trim($input["market"]) : config("market_mr"));
		$timearr = array(1, 3, 5, 10, 15, 30, 60, 120, 240, 360, 720, 1440, 10080);

		if (in_array($input["time"], $timearr)) {
			$time = $input["time"];
		} else {
			$time = 5;
		}

		$timeaa = (config('app.develop') ? null : cache("ChartgetMarketOrdinaryJsontime" . $market . $time));

		if (($timeaa + 60) < time()) {
			cache("ChartgetMarketOrdinaryJson" . $market . $time, null);
			cache("ChartgetMarketOrdinaryJsontime" . $market . $time, time());
		}

		$tradeJson = (config('app.develop') ? null : cache("ChartgetMarketOrdinaryJson" . $market . $time));

		if (!$tradeJson) {
			$tradeJson = Db::name("TradeJson")->where(array(
				"market" => $market,
				"type"   => $time,
				"data"   => array("neq", "")
				)->order("id desc")->limit(100)->select();
			cache("ChartgetMarketOrdinaryJson" . $market . $time, $tradeJson);
		}

		krsort($tradeJson);

		foreach ($tradeJson as $k => $v) {
			$json_data[] = json_decode($v["data"], true);
		}

		exit(json_encode($json_data));
	}

	public function specialty()
	{
		// TODO: SEPARATE
		$input = input('get.');
		$market = (is_array(cache('market')[$input['market']]) ? trim($input['market']) : config('market_mr'));
		$this->assign('market', $market);
		return $this->fetch();
	}

	public function getMarketSpecialtyJson()
	{
		// TODO: SEPARATE
		$input = input('get.');
		$market = (is_array(cache('market')[$input['market']]) ? trim($input['market']) : config('market_mr'));
		
		$timearr = array(1, 3, 5, 10, 15, 30, 60, 120, 240, 360, 720, 1440, 10080);
		if (in_array($input['step'] / 60, $timearr)) {
			$time = floatval($input['step'] / 60);
		} else {
			$time = 5;
		}

		$timeaa = (config('app.develop') ? null : cache('ChartgetMarketSpecialtyJsontime' . $market . $time));
		if (($timeaa + 60) < time()) {
			cache('ChartgetMarketSpecialtyJson' . $market . $time, null);
			cache('ChartgetMarketSpecialtyJsontime' . $market . $time, time());
		}

		$tradeJson = (config('app.develop') ? null : cache('ChartgetMarketSpecialtyJson' . $market . $time));
		if (!$tradeJson) {
			$tradeJson = Db::name('TradeJson')->where(array('type' => $time, 'market' => $market)->order('id asc')->limit(1000)->select();
			cache('ChartgetMarketSpecialtyJson' . $market . $time, $tradeJson);
		}
		
		$json_data=array();
		Vendor("XssFilter.XssFilter","",".php");
		foreach ($tradeJson as $k => $v) {
			$v['data']=\XssFilter::xss_clean($v['data']);
			$json_data[] = json_decode($v['data'], true);
		}

		foreach ($json_data as $k => $v) {
			$data[$k][0] = $v[0];
			$data[$k][1] = 0;
			$data[$k][2] = 0;
			$data[$k][3] = $v[2];
			$data[$k][4] = $v[5];
			$data[$k][5] = $v[3];
			$data[$k][6] = $v[4];
			$data[$k][7] = $v[1];
		}

		header("Content-type:application/json");
		header('X-Frame-Options: SAMEORIGIN');
		exit(json_encode($data));
	}

	public function getSpecialtyTrades()
	{
		$input = input('get.');

		// 过滤非法字符----------------S
		if (checkstr($input['since']) || checkstr($input['market'])) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E

		$json_data=array();
		if (!$input['since']) {
			$tradeLog = Db::name('TradeLog')->where('market', $input['market'])->order('id desc')->find();
			$json_data[] = array('tid' => intval($tradeLog['id']), 'date' => intval($tradeLog['addtime']), 'price' => floatval($tradeLog['price']), 'amount' => floatval($tradeLog['num']), 'trade_type' => $tradeLog['type'] == 1 ? 'bid' : 'ask');
			header("Content-type:application/json");
			header('X-Frame-Options: SAMEORIGIN');
			exit(json_encode($json_data));
		} else {
			$tradeLog = Db::name('TradeLog')->where(array(
				'market' => $input['market'],
				'id'     => array('gt', $input['since'])
				)->order('id desc')->select();

			foreach ($tradeLog as $k => $v) {
				$json_data[] = array('tid' => intval($v['id']), 'date' => intval($v['addtime']), 'price' => floatval($v['price']), 'amount' => floatval($v['num']), 'trade_type' => $v['type'] == 1 ? 'bid' : 'ask');
			}
			header("Content-type:application/json");
			header('X-Frame-Options: SAMEORIGIN');
			exit(json_encode($json_data));
		}
	}
	
/*	public function getSpecialtyTrades()
	{
		$input = input('get.');

		// 过滤非法字符----------------S
		if (checkstr($input['since']) || checkstr($input['market'])) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E

		$json_data=array();
		if (!$input['since']) {
			$tradeLog = Db::name('TradeLog')->where('market', $input['market'])->order('id desc')->find();
			$json_data[] = array('tid' => intval($tradeLog['id']), 'date' => intval($tradeLog['addtime']), 'price' => floatval($tradeLog['price']), 'amount' => floatval($tradeLog['num']), 'trade_type' => $tradeLog['type'] == 1 ? 'bid' : 'ask');
			header("Content-type:application/json");
			header('X-Frame-Options: SAMEORIGIN');
			exit(json_encode($json_data));
		}
		else {
			$tradeLog = Db::name('TradeLog')->where(array(
				'market' => $input['market'],
				'id'     => array('gt', $input['since'])
				)->order('id desc')->select();

			foreach ($tradeLog as $k => $v) {
				$json_data[] = array('tid' => intval($v['id']), 'date' => intval($v['addtime']), 'price' => floatval($v['price']), 'amount' => floatval($v['num']), 'trade_type' => $v['type'] == 1 ? 'bid' : 'ask');
			}
			header("Content-type:application/json");
			header('X-Frame-Options: SAMEORIGIN');
			exit(json_encode($json_data));
		}
	}*/
}

?>