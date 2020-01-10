<?php
/* 应用 - 理财中心 */
namespace app\home\controller;

use think\Db;

class Financing extends Home
{
	protected function _initialize()
	{
		parent::_initialize();
		$allow_action=array("index","queue","dlog","fee","info","beforeGet","danweitostr");
		if(!in_array($this->request->action(),$allow_action)){
			$this->error(lang("非法操作！"));
		}
	}

	public function index($id=NULL, $num=0, $paypassword=NULL)
	{
		if ($this->request->ispost())
		{
			if (!userid()) {
				$this->error(lang('请先登录！'));
			}
			
			// 过滤非法字符----------------S
			if (checkstr($id) || checkstr($num) || checkstr($paypassword)) {
				$this->error(lang('您输入的信息有误！'));
			}
			// 过滤非法字符----------------E

			if (!check($id, 'd')) {
				$this->error(lang('ID编号格式错误！'));
			}
			if (!check($num, 'double')) {
				$this->error(lang('存币数量格式错误！'));
			}
			if (!check($paypassword, 'password')) {
				$this->error(lang('交易密码格式错误！'));
			}

			$user = Db::name('User')->where('id', userid())->find();
			if (md5($paypassword) != $user['paypassword']) {
				$this->error(lang('交易密码错误！'));
			}

			$money = Db::name('Money')->where('id', $id)->find();
			if (!$money) {
				$this->error(lang('当前理财错误！'));
			}
			if (!$money['status']) {
				$this->error(lang('当前理财已经禁用！'));
			}
			if (($money['num'] - $money['deal']) < $num) {
				$this->error(lang('系统剩余额度不足！'));
			}

/*			$money_min = (cache('money_min') ? config('money_min') : 1);
			$money_max = (cache('money_max') ? config('money_max') : 10000000);
			$money_bei = (cache('money_bei') ? config('money_bei') : 1);*/
			
			$money_min = ($money['stnum'] ? $money['stnum'] : 1);
			$money_max = 1000000;
			$money_bei = 1;
			
			if ($num < $money_min) {
				$this->error('存币数量超过系统最小限制'.$money_min);
			}
			if ($money_max < $num) {
				$this->error('存币数量超过系统最大限制',$money_max);
			}
/*			if ($num % $money_bei != 0) {
				$this->error('每次存币数量必须是' . $money_bei . '的整倍数！');
			}*/
			
			$userCoin = Db::name('UserCoin')->where('userid', userid())->find();
			if (!$userCoin || !isset($userCoin[$money['coinname']])) {
				$this->error(lang('当前品种错误!'));
			}
			if ($userCoin[$money['coinname']] < $num) {
				$this->error('可用余额不足,当前账户余额:' . $userCoin[$money['coinname']]);
			}
			
/*			$money_log_num = Db::name('MoneyLog')->where('userid', userid(), 'money_id' => $money['id'])->sum('num');
			if ($money['max'] < ($money_log_num + $num)) {
				$this->error('当前理财最大可购买' . $money['lnum'] . ',您已经购买:' . $money_log_num);
			}*/
			
			$money_log_num = Db::name("MoneyLog")->where("userid = ".userid().' and money_id = '.$money['id']." and addtime > ".(time()-$userCoin["step"]))->sum("num");
			if ($money["lnum"] < ($money_log_num + $num)) {
				debug(array($money_log_num, Db::name("MoneyLog")->getLastSql()));
				$this->error("本周期内最大可购买" . $money["lnum"] . ",您已经购买:" . $money_log_num);
			}

			$mo = db();
			Db::execute('set autocommit=0');
			Db::execute('lock tables tw_user_coin write, tw_money_log write, tw_money write');
			$rs = [];
			$rs[] = Db::table('tw_user_coin')->where('userid', userid())->setDec($money['coinname'], $num);
			$rs[] = Db::table("tw_money_log")->insert(array("userid" => $user["id"], "money_id" => $money["id"], "num" => $num, "addtime" => time(), "status" => 1));
			
			if ($money['num'] <= $money['deal']) {
				$rs[] = Db::table('tw_money')->where('id', $id)->setField('status', 0);
			} else {
				$rs[] = Db::table('tw_money')->where('id', $id)->setInc('deal', $num);
			}

			if (check_arr($rs)) {
				Db::execute('commit');
				Db::execute('unlock tables');
				$this->success('购买成功！');
			} else {
				Db::execute('rollback');
				$this->error(config('app.develop') ? implode('|', $rs) : '购买失败!');
			}
		} else {

			$where['status'] = 1;
			$show = Db::name('Money')->where($where)->paginate(10);
			$list = Db::name('Money')->where($where)->order('sort desc')->order('id desc')->limit(0,10)->select();
			foreach ($list as $k => $v) {
				$list[$k]['fee'] = Num($v['fee']);
				$list[$k]['addtime'] = addtime($v['addtime']);
				$list[$k]['bili'] = round($v['deal'] / $v['num'], 2) * 100;
				$list[$k]['times'] = Db::name('MoneyLog')->where('money_id', $v['id'])->count();
				$list[$k]['shen'] = round($v['num'] - $v['deal'], 2);
//				$list[$k]["tian"] = $list[$k]["tian"] . '<span class="unit">' . $this->danweitostr($list[$k]["danwei"]).'</span>';
                $list[$k]["danwei"] = $this->danweitostr($list[$k]["danwei"]);
				$list[$k]["shengyu"] = $v["num"] - $v["deal"]; // number_format($v["num"] - $v["deal"])
			}
			$this->assign('list', $list);
			$this->assign('page', $show);

			
			$log_where['userid'] = userid();
            $log_show = Db::name('MoneyLog')->where($log_where)->paginate(10);

			$log_list = Db::name('MoneyLog')->where($log_where)->order('id desc')->limit(0, 10)->select();
			foreach ($log_list as $k => $v) {
				$log_list[$k]["money"] = Db::name("Money")->where("id", $v["money_id"])->find();
				$log_list[$k]["money"]["tian"] = $log_list[$k]["money"]["tian"] . " " . $this->danweitostr($log_list[$k]["money"]["danwei"]);
				if ($log_list[$k]["money"]['type'] == 1) {
					$log_list[$k]["money"]['type'] = '活期';
				} else {
					$log_list[$k]["money"]['type'] = '定期';
				}
			}
			$this->assign('log_list', $log_list);
			$this->assign('log_page', $log_show);
			return $this->fetch();
		}
	}
	
	// 理财记录详情页
	public function log()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		$where['userid'] = userid();
		$count = Db::name('MoneyLog')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = Db::name('MoneyLog')->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			$list[$k]["money"] = Db::name("Money")->where("id", $v["money_id"])->find();
			$list[$k]["money"]["tian"] = $list[$k]["money"]["tian"] . " " . $this->danweitostr($list[$k]["money"]["danwei"]);
			if ($list[$k]["money"]['type'] == 1) {
				$list[$k]["money"]['type'] = '活期';
			} else {
				$list[$k]["money"]['type'] = '定期';
			}
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}
	
	public function dlog()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		$input = input("get.");
		$where["userid"] = userid();
		$where["log_id"] = $input['id'];
		
		$count = Db::name("MoneyDlog")->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = Db::name("MoneyDlog")->where($where)->order("id desc")->limit($Page->firstRow . "," . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]["money"] = Db::name("Money")->where("id", $v["money_id"])->find();
		}

		$this->assign("list", $list);
		$this->assign("page", $show);
		return $this->fetch();
	}

	public function fee()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		$id = $_GET['id'];
		if (!check($id, 'd')) {
			$this->error(lang('参数错误!'));
		}

		$where['moneylogid'] = $id;
		$where['userid'] = userid();
		$count = Db::name('MoneyFee')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = Db::name('MoneyFee')->where($where)->order('id desc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}
	
	public function info($id)
	{
		if (!userid()) {
			$this->error(lang('请先登录！'));
		}

		$id = intval($id);
		if (!$id) {
			$this->error("参数错误");
		}

		$Money = Db::name("Money")->where("id", $id)->find();
		$UserCoin = Db::name("UserCoin")->where("userid", userid())->find();
		
		$ret = [];
		$ret["Money"] = array_merge($Money, array("yue" => $UserCoin[$Money["coinname"]]));
		

		if ($ret["Money"]['type'] == 1) {
			$types = '活期';
		} else {
			$types = '定期';
		}

		$ret["Money"]['type'] = $types;
		
		$this->success($ret);
	}

	public function beforeGet($id)
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		$id = intval($id);
		// 过滤非法字符----------------S
		if (checkstr($id)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E
		
		$MoneyLog = Db::name('MoneyLog')->where('userid', userid())->where('id', $id)->where('status', 1)->find();
		if (!$MoneyLog) {
			$this->error(lang('参数错误!'));
		}

		$Money = Db::name('Money')->where('id', $MoneyLog['money_id'])->find();
		if (!$Money) {
			$this->error(lang('参数错误!'));
		}

		$num = $MoneyLog['num'];
		$fee = ($Money['outfee'] ? round(($MoneyLog['num'] * $Money['outfee']) / 100, 8) : 0);
		$mo = db();
		Db::execute('set autocommit=0');
		Db::execute('lock tables tw_user_coin write  , tw_money_log  write,tw_money_dlog  write');
		$rs = [];

		if ($Money['coinname'] != $Money['feecoin']) {
			$user_coin = Db::table('tw_user_coin')->where('userid', userid())->find();

			if (!isset($user_coin[$Money['feecoin']])) {
				$this->error('利息品种不存在,请联系管理员');
			}
			if ($user_coin[$Money['feecoin']] < $fee) {
				$this->error('您的' . $Money['feecoin'] . '不够取现手续费(' . $fee . ')');
			}

			$rs[] = Db::table('tw_user_coin')->where('userid', userid())->setDec($Money['feecoin'], $fee);
			debug(Db::table('tw_user_coin')->getLastSql(), 'tw_user_coin_sql0');
			$rs[] = Db::table('tw_user_coin')->where('userid', userid())->setInc($Money['coinname'], $num);
			debug(Db::table('tw_user_coin')->getLastSql(), 'tw_user_coin_sql1');
		} else {
			$rs[] = Db::table('tw_user_coin')->where('userid', userid())->setInc($Money['coinname'], round($num - $fee, 8));
			debug(Db::table('tw_user_coin')->getLastSql(), 'tw_user_coin_sql2');
		}

		$rs[] = Db::table('tw_money_log')->where('id', $MoneyLog['id'])->setField('status', 0);
		debug(Db::table('tw_money_log')->getLastSql(), 'tw_money_log_sql');
		$rs[] = Db::table('tw_money_dlog')->insert(array('userid' => userid(), 'money_id' => $Money['id'], 'type' => 2, 'num' => $fee, 'addtime' => time(), 'content' => '提前抽取' . $Money['title'] . ' 理财本金' . $Money['coinname'] . ' ' . $MoneyLog['num'] . '个,扣除利息' . $Money['feecoin'] . ': ' . $fee . '个'));

		if (check_arr($rs)) {
			Db::execute('commit');
			Db::execute('unlock tables');
			$this->success('操作成功！');
		} else {
			Db::execute('rollback');
			$this->error(config('app.develop') ? implode('|', $rs) : '操作失败!');
		}
	}

	private function danweitostr($danwei)
	{
		switch ($danwei) {
		case 'y':
			return '年';
			break;

		case 'm':
			return '月';
			break;

		case 'd':
			return '天';
			break;

		case 'h':
			return '小时';
			break;

		default:

		case 'i':
			return '分钟';
			break;
		}
	}
}
?>