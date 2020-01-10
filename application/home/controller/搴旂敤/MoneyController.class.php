<?php
/* 应用 - 理财中心 */
namespace Home\Controller;

class MoneyController extends HomeController
{
	protected function _initialize(){
		parent::_initialize();
		$allow_action=array("index","queue","log","fee","beforeGet","danweitostr");
		if(!in_array($this->request->action(),$allow_action)){
			$this->error("非法操作！");
		}
	}

	public function index()
	{
		if ($this->request->ispost()) {
			$id = $_POST['id'];
			$num = $_POST['num'];
			$paypassword = $_POST['paypassword'];

			if (!check($id, 'd')) {
				$this->error('编号格式错误！');
			}

			if (!check($num, 'd')) {
				$this->error('理财数量格式错误！');
			}

			if (!check($paypassword, 'password')) {
				$this->error('交易密码格式错误！');
			}

			$money_min = (cache('money_min') ? config('money_min') : 10);
			$money_max = (cache('money_max') ? config('money_max') : 10000000);
			$money_bei = (cache('money_bei') ? config('money_bei') : 10);

			if ($num < $money_min) {
				$this->error('理财数量超过系统最小限制10！');
			}

			if ($money_max < $num) {
				$this->error('理财数量超过系统最大限制！');
			}

			if ($num % $money_bei != 0) {
				$this->error('每次理财数量必须是' . $money_bei . '的整倍数！');
			}

			if (!userid()) {
				$this->error('请先登录！');
			}

			$user = Db::name('User')->where('id', userid())->find();

			if (md5($paypassword) != $user['paypassword']) {
				$this->error('交易密码错误！');
			}

			$money = Db::name('Money')->where('id', $id)->find();

			if (!$money) {
				$this->error('当前理财错误！');
			}

			if (!$money['status']) {
				$this->error('当前理财已经禁用！');
			}

			if (($money['num'] - $money['deal']) < $num) {
				$this->error('系统剩余量不足！');
			}

			$userCoin = Db::name('UserCoin')->where('userid', userid())->find();

			if (!$userCoin || !isset($userCoin[$money['coinname']])) {
				$this->error('当前品种错误!');
			}

			if ($userCoin[$money['coinname']] < $num) {
				$this->error('可用余额不足,当前账户余额:' . $userCoin[$money['coinname']]);
			}

			$money_log_num = Db::name('MoneyLog')->where('userid', userid(), 'moneyid' => $money['id'])->sum('num');

			if ($money['max'] < ($money_log_num + $num)) {
				$this->error('当前理财最大可购买' . $money['max'] . ',您已经购买:' . $money_log_num);
			}

			$mo = db();
			Db::execute('set autocommit=0');
			Db::execute('lock tables tw_user_coin write  , tw_money_log  write , tw_money  write');
			$rs = [];
			$rs[] = Db::table('tw_user_coin')->where('userid', userid())->setDec($money['coinname'], $num);
			$rs[] = Db::table('tw_money_log')->insert(array('userid' => userid(), 'moneyid' => $money['id'], 'name' => $money['name'], 'num' => $num, 'tian' => $money['tian'], 'fee' => $money['fee'], 'feecoin' => $money['feecoin'], 'addtime' => time(), 'status' => 0));
			$rs[] = Db::table('tw_money')->where('id', $id)->setInc('deal', $num);

			if ($money['num'] <= $money['deal']) {
				$rs[] = Db::table('tw_money')->where('id', $id)->setField('status', 0);
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
			if (!userid()) {
				$this->redirect(url('Login/index'));
			}

			$this->assign('prompt_text', Model('Text')->get_content('game_money'));
			$where['status'] = 1;
			$count = Db::name('Money')->where($where)->count();
			$Page = new \Think\Page($count, 5);
			$show = $Page->show();
			$list = Db::name('Money')->where($where)->order('sort desc')->limit(0, 10)->select();

			foreach ($list as $k => $v) {
				$list[$k]['fee'] = Num($v['fee']);
				$list[$k]['addtime'] = addtime($v['addtime']);
				$list[$k]['bili'] = round($v['deal'] / $v['num'], 2) * 100;
				$list[$k]['times'] = Db::name('MoneyLog')->where(array('moneyid' => $v['id'])->count();
				$list[$k]['shen'] = round($v['num'] - $v['deal'], 2);
			}

			$this->assign('list', $list);
			$this->assign('page', $show);
			return $this->fetch();
		}
	}

	public function queue()
	{
		$br = (PHP_SAPI=='cli'? 1 : 0 ? "\n" : '<br>');
		echo PHP_SAPI=='cli'? 1 : 0 ? '' : '<pre>';
		echo 'start money queue:' . $br;
		$MoneyList = Db::name('Money')->where('status', 1)->select();
		debug($MoneyList, 'MoneyList');

		foreach ($MoneyList as $money) {
			debug($money, 'money');

			if ($money['endtime'] < $money['lasttime']) {
				echo 'end ok ' . $br;
				$MoneyLogList = Db::name('MoneyLog')->where('money_id', $money['id'], 'status' => 1)->select();

				if ($MoneyLogList) {
					$mo = db();

					foreach ($MoneyLogList as $user_money_list) {
						if (!$user_money_list['status']) {
							continue;
						}

						Db::execute('set autocommit=0');
						Db::execute('lock tables tw_user_coin write,tw_money_log  write,tw_money_dlog  write');
						$rs = [];
						$rs[] = Db::table('tw_user_coin')->where('userid', $user_money_list['userid'])->setInc($money['coinname'], $user_money_list['num']);
						$rs[] = Db::table('tw_money_log')->update('id', $user_money_list['id'], 'status' => 0));
						$rs[] = Db::table('tw_money_dlog')->insert(array('userid' => $user_money_list['userid'], 'money_id' => $money['id'], 'type' => 1, 'num' => $user_money_list['num'], 'addtime' => time(), 'content' => '理财结束,退回本金:' . $user_money_list['num'] . '个'));

						if (check_arr($rs)) {
							Db::execute('commit');
							Db::execute('unlock tables');
							echo 'commit ok ' . $br;
						} else {
							Db::execute('rollback');
							echo 'rollback ' . $br;
						}
					}
				} else {
					D('Money')->update('id', $money['id'], 'status' => 0));
					D('MoneyLog')->update(array('money_id' => $money['id'], 'status' => 0));
					continue;
				}
			}

			echo (($money['lasttime'] + $money['step']) - time()) . ' s' . $br;
			debug(array('lasttime' => $money['lasttime'], 'step' => $money['step'], 'time()' => time()), 'check time');

			if (!$money['lasttime'] || (($money['lasttime'] + $money['step']) < time())) {
				echo 'start ' . $money['name'] . '#:' . $br;
				$mo = db();
				debug('A');
				$MoneyLogList = Db::name('MoneyLog')->where('money_id', $money['id'], 'status' => 1)->select();
				debug('B');
				debug($MoneyLogList, 'MoneyLogList');

				foreach ($MoneyLogList as $MoneyLog) {
					debug('C');
					debug(array($MoneyLog, $money), 'chktime');

					if ($MoneyLog['chktime'] == $money['lasttime']) {
						continue;
					}

					Db::execute('set autocommit=0');
					Db::execute('lock tables tw_user_coin write,tw_money_log  write,tw_money_dlog  write');
					$rs = [];
					$fee = round(($money['fee'] * $MoneyLog['num']) / 100, 8);
					echo 'update ' . $MoneyLog['userid'] . ' coin ' . $br;
					$rs[] = Db::table('tw_user_coin')->where('userid', $MoneyLog['userid'])->setInc($money['feecoin'], $fee);
					echo 'update ' . $MoneyLog['userid'] . ' log ' . $br;
					$MoneyLog['allfee'] = round($MoneyLog['allfee'] + $fee, 8);
					$MoneyLog['times'] = $MoneyLog['times'] + 1;
					$MoneyLog['chktime'] = $money['lasttime'];
					$rs[] = Db::table('tw_money_log')->update($MoneyLog);
					echo 'add ' . $MoneyLog['userid'] . ' dlog ' . $br;
					$rs[] = Db::table('tw_money_dlog')->insert(array('userid' => $MoneyLog['userid'], 'money_id' => $money['id'], 'type' => 1, 'num' => $fee, 'addtime' => time(), 'content' => '本金:' . $money['coinname'] . ' :' . $MoneyLog['num'] . '个,获取理财利息' . $money['feecoin'] . ' ' . $fee . '个'));

					if (check_arr($rs)) {
						Db::execute('commit');
						Db::execute('unlock tables');
						echo 'commit ok ' . $br;
					}
					else {
						Db::execute('rollback');
						echo 'rollback ' . $br;
					}
				}

				if (Model('Money')->where('id', $money['id'])->setField('lasttime', time())) {
					echo 'update money last time ok' . $br;
				}
				else {
					echo 'update money last time fail!!!!!!!!!!!!!!!!!!!!!! ' . $br;
				}
			}
			else {
				echo '时间未到';
			}
		}
	}

	public function log()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		$this->assign('prompt_text', Model('Text')->get_content('game_money_log'));
		$where['userid'] = userid();
		$count = Db::name('MoneyLog')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = Db::name('MoneyLog')->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			$list[$k]['fee'] = Num($v['fee']);
			$list[$k]['addtime'] = addtime($v['addtime']);
			$list[$k]['endtime'] = addtime($v['endtime']);
			$list[$k]['leiji'] = Num($v['leiji']);
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function fee()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		$id = $_GET['id'];

		if (!check($id, 'd')) {
			$this->error('参数错误!');
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

	public function beforeGet($id)
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		$id = intval($id);
		$MoneyLog = Db::name('MoneyLog')->where('userid', userid(), 'id' => $id, 'status' => 1)->find();

		if (!$MoneyLog) {
			$this->error('参数错误');
		}

		$Money = Db::name('Money')->where('id', $MoneyLog['money_id'])->find();

		if (!$Money) {
			$this->error('参数错误');
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