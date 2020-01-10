<?php
/* 应用 - 上币投票 */
namespace app\home\controller;

use think\Db;

class Vote extends Home
{
	protected function _initialize()
	{
		parent::_initialize();
		$allow_action=array("index","info","log");
		if(!in_array($this->request->action(),$allow_action)){
			$this->error(lang("非法操作！"));
		}
	}
	
	public function index($cid=NULL, $type=NULL, $num=0, $paypassword=NULL)
	{
		if ($this->request->ispost())
		{
			if (!userid()) {
				$this->error(lang('请先登录！'));
			}
			
			$num = floor($num);
			
			// 过滤非法字符----------------S
			if (checkstr($cid) || checkstr($type) || checkstr($num) || checkstr($paypassword)) {
				$this->error(lang('您输入的信息有误！'));
			}
			// 过滤非法字符----------------E

			if (!check($cid, 'd')) {
				$this->error(lang('ID参数错误！'));
			}
			if (($type != 1) && ($type != 2)) {
				$this->error(lang('TYPE参数错误！'));
			}
			if (!check($num, 'double')) {
				$this->error(lang('存币数量格式错误！'));
			}
			if (!check($paypassword, 'password')) {
				$this->error(lang('交易密码格式错误！'));
			}
			
			$user = Db::name('User')->where('status', userid())->find();
			if (md5($paypassword) != $user['paypassword']) {
				$this->error(lang('交易密码错误！'));
			}
			
			$VoteTypeData = Db::name('VoteType')->where('status', $cid)->where('status', 1)->find();
			$vt_coinname = $VoteTypeData['coinname'];
			$vt_title = $VoteTypeData['title'];
			$vt_votecoin = $VoteTypeData['votecoin'];
			$vt_assumnum = $VoteTypeData['assumnum'];
			
			if ($num < $vt_assumnum) {
				$this->error(lang('票数必须小于 1 票'));
			}
			if (10000 < $num) {
				$this->error(lang('票数必须大于 10000 票'));
			}
			
			if ($VoteTypeData) {
				$userCoin = Db::name('UserCoin')->where('userid', userid())->find();
				if ($userCoin[$vt_votecoin] < $vt_assumnum * $num) {
					$this->error('投票所需要的 '.strtoupper($vt_votecoin).' 数量不足');
				}
			} else {
				$this->error('不存在的投票类型');
			}
			
			if (Db::name('Vote')->where('userid', userid())->where('coinname', $vt_coinname)->find()) {
				$this->error(lang('您已经投票过，不能再次操作！'));
			}
			
			// 判断是否需要扣除币
			if ($vt_assumnum > 0) {
				try{
					$mo = db();
					Db::execute('set autocommit=0');
					Db::execute('lock tables tw_user_coin write, tw_vote write, tw_finance_log write');
					$rs = [];

					$finance_num_user_coin = Db::table('tw_user_coin')->where('userid', userid())->find();
					/* 修改金额 */
					$rs[] = Db::table("tw_user_coin")->where('userid',userid())->setDec($vt_votecoin,$vt_assumnum*$num);
					$rs[] = Db::table("tw_vote")->insert(array('userid'=>userid(), 'coinname'=>$vt_coinname, 'type'=>$type, 'num'=>$num, 'votecoin'=>$vt_votecoin, 'mum'=>$vt_assumnum*$num, 'addtime'=>time(), 'status'=>1));
					
					$finance_mum_user_coin = Db::table('tw_user_coin')->where('userid', userid())->find();
					
					
					// 处理资金变更日志-----------------S
					/*
					 * 操作位置（0后台，1前台） position
					 * 动作类型（参考function.php） optype
					 * 资金类型（1人民币） cointype
					 * 类型（0减少，1增加） plusminus
					 * 操作数据 amount
					 */
					$rs[] = Db::table('tw_finance_log')->insert(['username' => session('userName'),
                        'adminname' => session('userName'),
                        'addtime' => time(),
                        'plusminus' => 0,
                        'amount' => $vt_assumnum*$num,
                        'optype' => 30,
                        'position' => 1,
                        'cointype' => config("coin")[$vt_votecoin]["id"],
                        'old_amount' => $finance_num_user_coin[$vt_votecoin],
                        'new_amount' => $finance_mum_user_coin[$vt_votecoin],
                        'userid' => session('userId'),
                        'adminid' => session('userId'),
                        'addip'=>$this->request->ip()]);
					// 处理资金变更日志-----------------E

					if (check_arr($rs)) {
						Db::execute('commit');
						Db::execute('unlock tables');
						$this->success(lang('投票成功！'));
					} else {
						Db::execute('rollback');
						$this->error(config('app.develop') ? implode('|', $rs) : lang('投票失败！'));
					}
				}catch(\Think\Exception $e){
					Db::execute('rollback');
					Db::execute('unlock tables');
					//$this->error($e->getMessage());exit();
					$this->error(lang('订单创建失败！'));
				}
			} else {
				if (Db::name('Vote')->insert(array('userid'=>userid(), 'coinname'=>$vt_coinname, 'type'=>$type, 'num'=>1, 'addtime'=>time(), 'status'=>1))) {
					$this->success(lang('投票成功！'));
				} else {
					$this->error(lang('投票失败！'));
				}
			}
			
		} else {
			$where['status'] = 1;
			$coin_list = Db::name('VoteType')->where($where)->select();
			if (is_array($coin_list)) {
				foreach ($coin_list as $k => $v) {
					$v_coin = config('coin')[$v['coinname']];
					$list[$v_coin['name']]['id'] = $v['id'];
					$list[$v_coin['name']]['name'] = $v_coin['name'];
					$list[$v_coin['name']]['title'] = $v['title'];
					$list[$v_coin['name']]['zhichi'] = Db::name('Vote')->where('coinname', $v_coin['name'])->where('type', 1)->sum('num') + $v['zhichi'];
					$list[$v_coin['name']]['fandui'] = Db::name('Vote')->where('coinname', $v_coin['name'])->where('type', 2)->sum('num') + $v['fandui'];
					$list[$v_coin['name']]['zongji'] = $list[$v_coin['name']]['zhichi'] - $list[$v_coin['name']]['fandui'];
					$list[$v_coin['name']]['bili'] = round(($list[$v_coin['name']]['zhichi'] / $list[$v_coin['name']]['zongji']) * 100, 2);
				}

				$sort = array('direction'=>'SORT_DESC', 'field'=>'zongji');  
				$arrSort = [];
				foreach ($list AS $uniqid => $row) {
					foreach ($row AS $key => $value) {
						$arrSort[$key][$uniqid] = $value;
					}  
				} 
				if ($sort['direction']) {
					array_multisort($arrSort[$sort['field']], constant($sort['direction']), $list);  
				}

				$this->assign('list', $list);
			}
			
			$this->assign('text', Model('Text')->get_url('apps_vote'));
			return $this->fetch();
		}
	}
	
	public function info($id)
	{
		if (!userid()) {
			$this->error(lang('请先登录！'));
		}

		$id = intval($id);
		if (!$id) {
			$this->error(lang("参数错误"));
		}

		$data = Db::name("VoteType")->where("id", $id)->find();
		$ret = [];
		$ret["data"] = $data;
		$ret["data"]['coinimg'] = '/Upload/coin/'.C('coin')[$data['coinname']]['img'];
		$ret["data"]['votecoininfo'] = $data['assumnum'].' '.$data['votecoin'];
		
		$this->success($ret);
	}
	
	// 投票记录
	public function log()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		$where['userid'] = userid();
		$show = Db::name('Vote')->where($where)->paginate(10);

		$list = Db::name('Vote')->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			$list[$k]["coinname"] = strtoupper(cache("coin")[$v['coinname']]['name']);
			if ($list[$k]['type']==1) {
				$list[$k]['type'] = '支持';
			} else {
				$list[$k]['type'] = '反对';
			}
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}
}

?>