<?php
namespace app\common\model;

class Trade extends Model
{
	protected $keyS = 'Trade';

	public function hangqing($market = NULL)
	{
		if (empty($market)) {
			return null;
		}

		$timearr = array(1, 3, 5, 10, 15, 30, 60, 120, 240, 360, 720, 1440, 10080);
		foreach ($timearr as $k => $v) {
			$tradeJson = Db::name('TradeJson')->where('market', $market)->where('type', $v)->order('id desc')->find();

			if ($tradeJson) {
				$addtime = $tradeJson['addtime'];
			} else {
				$addtime = Db::name('TradeLog')->where('market', $market)->order('id asc')->value('addtime');
			}

			if ($addtime) {
				$youtradelog = Db::name('TradeLog')->where('addtime >=' . $addtime . '  and market =\'' . $market . '\'')->sum('num');
			}

			if ($youtradelog) {
				if ($v == 1) {
					$start_time = $addtime;
				} else {
					$start_time = mktime(date('H', $addtime), floor(date('i', $addtime) / $v) * $v, 0, date('m', $addtime), date('d', $addtime), date('Y', $addtime));
				}

				$x = 0;

				for (; $x <= 20; $x++) {
					$na = $start_time + (60 * $v * $x);
					$nb = $start_time + (60 * $v * ($x + 1));

					if (time() < $na) {
						break;
					}

					$sum = Db::name('TradeLog')->where('addtime >=' . $na . ' and addtime <' . $nb . ' and market =\'' . $market . '\'')->sum('num');
					if ($sum) {
						$sta = Db::name('TradeLog')->where('addtime >=' . $na . ' and addtime <' . $nb . ' and market =\'' . $market . '\'')->order('id asc')->value('price');
						$max = Db::name('TradeLog')->where('addtime >=' . $na . ' and addtime <' . $nb . ' and market =\'' . $market . '\'')->max('price');
						$min = Db::name('TradeLog')->where('addtime >=' . $na . ' and addtime <' . $nb . ' and market =\'' . $market . '\'')->min('price');
						$end = Db::name('TradeLog')->where('addtime >=' . $na . ' and addtime <' . $nb . ' and market =\'' . $market . '\'')->order('id desc')->value('price');
						$d = array($na, $sum, $sta, $max, $min, $end);

						if (Db::name('TradeJson')->where('market', $market)->where('addtime', $na)->where('type', $v)->find()) {
							Db::name('TradeJson')->where('market', $market)->where('addtime', $na)->where('type', $v)->update(array('data' => json_encode($d)));
							Db::name('TradeJson')->execute('commit');
						} else {
							Db::name('TradeJson')->insert([
							    'market' => $market,
                                'data' => json_encode($d),
                                'addtime' => $na,
                                'type' => $v
                            ]);
							Db::name('TradeJson')->execute('commit');
							Db::name('TradeJson')->where('market', $market)->where('data', '')->where('type', $v)->delete();
							Db::name('TradeJson')->execute('commit');
						}
					} else {
						Db::name('TradeJson')->insert([
						    'market' => $market,
                            'data' => '',
                            'addtime' => $na,
                            'type' => $v]);
						Db::name('TradeJson')->execute('commit');
					}
				}
			}
		}
	}

	public function chexiao($id = NULL)
	{
		if (!check($id, 'd')) {
			return array('0', '参数错误');
		}

		$trade = Db::name('Trade')->where('id', $id)->find();

		if (!$trade) {
			return array('0', '订单不存在');
		}

		if ($trade['status'] != 0) {
			return array('0', '订单不能撤销');
		}

		$xnb = explode('_', $trade['market'])[0];
		$rmb = explode('_', $trade['market'])[1];

		if (!$xnb) {
			return array('0', '卖出市场错误');
		}

		if (!$rmb) {
			return array('0', '买入市场错误');
		}

		$fee_buy = config('market')[$trade['market']]['fee_buy'];
		$fee_sell = config('market')[$trade['market']]['fee_sell'];

		if ($fee_buy < 0) {
			return array('0', '买入手续费错误');
		}

		if ($fee_sell < 0) {
			return array('0', '卖出手续费错误');
		}
		try{
			$user_coin = Db::name('UserCoin')->where('userid', $trade['userid'])->find();

			$mo = db();
			Db::execute('set autocommit=0');
			// Db::execute('lock tables tw_user_coin write  , tw_trade write ,tw_finance write');
			Db::execute('lock tables tw_user_coin write  , tw_trade write ,tw_finance write,tw_finance_log write,tw_user write,tw_auth_group_access write,tw_admin write');//处理资金变更日志

			$rs = array();
			$user_coin = Db::table('tw_user_coin')->where('userid', $trade['userid'])->find();

			if ($trade['type'] == 1) {
				$user_buy = Db::table('tw_user_coin')->where('userid', $trade['userid'])->find();
				$buyuser = Db::table('tw_user')->where('id', $trade['userid'])->find();
				if($buyuser['lv']==1){
					$fee_buy=0;
				}
				
				$mun = round(((($trade['num'] - $trade['deal']) * $trade['price']) / 100) * (100 + $fee_buy), 8);
				if ($mun <= round($user_buy[$rmb . 'd'], 8)) {
					$save_buy_rmb = $mun;
				} else if ($mun <= round($user_buy[$rmb . 'd'], 8) + 1) {
					$save_buy_rmb = $user_buy[$rmb . 'd'];
				} else {
					throw new \Think\Exception('撤销失败1');
				}

				$finance = Db::table('tw_finance')->where('userid', $trade['userid'])->order('id desc')->find();
				$finance_num_user_coin = Db::table('tw_user_coin')->where('userid', $trade['userid'])->find();
				$rs[] = Db::table('tw_user_coin')->where('userid', $trade['userid'])->setInc($rmb, $save_buy_rmb);
				$rs[] = Db::table('tw_user_coin')->where('userid', $trade['userid'])->setDec($rmb . 'd', $save_buy_rmb);
				$finance_nameid = $trade['id'];

				$finance_mum_user_coin = Db::table('tw_user_coin')->where('userid', $trade['userid'])->find();

				
				// 处理资金变更日志--------买入类型---------S
				
				$user_2_info = Db::table('tw_user')->where('id', $trade['userid'])->find();
				if (session('userId') > 0) {
					$position = 1;
					// 获取用户信息
					$user_info = Db::table('tw_user')->where('id', session('userId'))->find();

					$uu_name = $user_2_info['username'];
					$aa_name = $user_info['username'];
					$uu_id = $trade['userid'];
					$aa_id = session('userId');
				} elseif (session('admin_id') > 0) {
					$position = 0;
					$uu_name = $user_2_info['username'];
					$aa_name = session('admin_username');
					$uu_id = $trade['userid'];
					$aa_id = session('admin_id');
				} else {
					$admin_group = Db::table('tw_auth_group_access')->where('group_id', '3')->find();
					$admin_info = Db::table('tw_admin')->where('id', $admin_group['uid'])->find();
					$position = 0;
					$uu_name = $user_2_info['username'];
					$aa_name = $admin_info['username'];
					$uu_id = $trade['userid'];
					$aa_id = $admin_info['id'];
				}

				// optype 10 买入-动作类型 'cointype' => 资金类型 'plusminus' => 1增加类型
				$rs[] = Db::table('tw_finance_log')->insert([
				    'username' => $uu_name,
                    'adminname' => $aa_name,
                    'addtime' => time(),
                    'plusminus' => 1,
                    'amount' => $save_buy_rmb,
                    'optype' => 16,
                    'cointype' => 1,
                    'old_amount' => $finance_num_user_coin[$rmb],
                    'new_amount' => $finance_mum_user_coin[$rmb],
                    'userid' => $uu_id,
                    'adminid' => $aa_id,
                    'addip'=>$this->request->ip(),
                    'position'=>$position
                ]);
				
				// 处理资金变更日志---------买入类型--------E


				$finance_hash = md5($trade['userid'] . $finance_num_user_coin[$rmb] . $finance_num_user_coin[$rmb . 'd'] . $save_buy_rmb . $finance_mum_user_coin[$rmb] . $finance_mum_user_coin[$rmb . 'd'] . MSCODE . 'tp3.net.cn');
				$finance_num = $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'];

				if ($finance['mum'] < $finance_num) {
					$finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
				} else {
					$finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
				}

				$rs[] = Db::table('tw_finance')->insert([
				    'userid' => $trade['userid'],
                    'coinname' => $rmb,
                    'num_a' => $finance_num_user_coin[$rmb],
                    'num_b' => $finance_num_user_coin[$rmb . 'd'],
                    'num' => $finance_num_user_coin[$rmb] + $finance_num_user_coin[$rmb . 'd'],
                    'fee' => $save_buy_rmb,
                    'type' => 1,
                    'name' => 'trade',
                    'nameid' => $finance_nameid,
                    'remark' => '交易中心-交易撤销' . $trade['market'],
                    'mum_a' => $finance_mum_user_coin[$rmb],
                    'mum_b' => $finance_mum_user_coin[$rmb . 'd'],
                    'mum' => $finance_mum_user_coin[$rmb] + $finance_mum_user_coin[$rmb . 'd'],
                    'move' => $finance_hash,
                    'addtime' => time(),
                    'status' => $finance_status
                ]);
				$rs[] = Db::table('tw_trade')->where('id', $trade['id'])->setField('status', 2);
				$you_buy = Db::table('tw_trade')->where('market', 'eq', $trade['market'])->where('status', 0)->where('userid', $trade['userid'])->find();

				if (!$you_buy) {
					$you_user_buy = Db::table('tw_user_coin')->where('userid', $trade['userid'])->find();

					if (0 < $you_user_buy[$rmb . 'd']) {
						$rs[] = Db::table('tw_user_coin')->where('userid', $trade['userid'])->setField($rmb . 'd', 0);

						// 处理资金变更日志-----------------S
						$user_2_info = Db::table('tw_user')->where('id', $trade['userid'])->find();
						if (session('userId') > 0) {
							$position = 1;
							$uu_name = $user_2_info['username'];
							$uu_id = $trade['userid'];
						} else {
							$position = 0;
							$uu_name = $user_2_info['username'];
							$uu_id = $trade['userid'];
						}

						// optype动作类型 'cointype' => 资金类型 'plusminus' => 1增加类型
						$rs[] = Db::table('tw_finance_log')->insert([
						    'username' => $uu_name,
                            'adminname' => '系统',
                            'addtime' => time(),
                            'plusminus' => 0,
                            'amount' => $you_user_buy[$rmb . 'd'],
                            'optype' => 17,
                            'cointype' => 1,
                            'old_amount' => $you_user_buy[$rmb . 'd'],
                            'new_amount' => '0',
                            'userid' => $uu_id,
                            'addip'=>$this->request->ip(),
                            'position'=>$position
                        ]);

						// 处理资金变更日志-----------------E
					}
				}
			} else if ($trade['type'] == 2) {
				$mun = round($trade['num'] - $trade['deal'], 8);
				$user_sell = Db::table('tw_user_coin')->where('userid', $trade['userid'])->find();
				if ($mun <= round($user_sell[$xnb . 'd'], 8)) {
					$save_sell_xnb = $mun;
				} else if ($mun <= round($user_sell[$xnb . 'd'], 8) + 1) {
					$save_sell_xnb = $user_sell[$xnb . 'd'];
				} else {
					throw new \Think\Exception('撤销失败2');
				}

				if (0 < $save_sell_xnb) {
					$rs[] = Db::table('tw_user_coin')->where('userid', $trade['userid'])->setInc($xnb, $save_sell_xnb);
					$rs[] = Db::table('tw_user_coin')->where('userid', $trade['userid'])->setDec($xnb . 'd', $save_sell_xnb);

					$user_sell_f = Db::table('tw_user_coin')->where('userid', $trade['userid'])->find();

					// 处理资金变更日志-----------------S

					switch ($xnb) {
						case 'hyjf':
							$cointype = 2;
							break;

						default:
							$cointype = 3;
							break;
					}

					$user_2_info = Db::table('tw_user')->where('id', $trade['userid'])->find();
					if (session('userId') > 0) {
						$position = 1;
						// 获取用户信息
						$user_info = Db::table('tw_user')->where('id', session('userId'))->find();

						$uu_name = $user_2_info['username'];
						$aa_name = $user_info['username'];
						$uu_id = $trade['userid'];
						$aa_id = session('userId');
					} elseif(session('admin_id') > 0) {
						$position = 0;
						$uu_name = $user_2_info['username'];
						$aa_name = session('admin_username');
						$uu_id = $trade['userid'];
						$aa_id = session('admin_id');
					} else {
						$admin_group = Db::table('tw_auth_group_access')->where('group_id', '3')->find();
						$admin_info = Db::table('tw_admin')->where('id', $admin_group['uid'])->find();
						$position = 0;
						$uu_name = $user_2_info['username'];
						$aa_name = $admin_info['username'];
						$uu_id = $trade['userid'];
						$aa_id = $admin_info['id'];
					}

					// optype动作类型 'cointype' => 资金类型 'plusminus' => 1增加类型
					$rs[] = Db::table('tw_finance_log')->insert([
					    'username' => $uu_name,
                        'adminname' => $aa_name,
                        'addtime' => time(),
                        'plusminus' => 1,
                        'amount' => $save_sell_xnb,
                        'optype' => 17,
                        'cointype' => $cointype,
                        'old_amount' => $user_sell[$xnb],
                        'new_amount' => $user_sell_f[$xnb],
                        'userid' => $uu_id,
                        'adminid' => $aa_id,
                        'addip'=>$this->request->ip(),
                        'position'=>$position
                    ]);

					// 处理资金变更日志-----------------E
				}

				$rs[] = Db::table('tw_trade')->where('id', $trade['id'])->setField('status', 2);
				$you_sell = Db::table('tw_trade')->where('market', 'eq', $trade['market'])->where('status', 0)->where('userid', $trade['userid'])->find();

				if (!$you_sell) {
					$you_user_sell = Db::table('tw_user_coin')->where('userid', $trade['userid'])->find();

					if (0 < $you_user_sell[$xnb . 'd']) {
						Db::table('tw_user_coin')->where('userid', $trade['userid'])->setField($xnb . 'd', 0);

						// 处理资金变更日志-----------------S

						switch ($xnb) {
							case 'hyjf':
								$cointype = 2;
								break;

							default:
								$cointype = 3;
								break;
						}

						$user_2_info = Db::table('tw_user')->where('id', $trade['userid'])->find();
						if (session('userId') > 0) {
							$position = 1;
							$uu_name = $user_2_info['username'];
							$uu_id = $trade['userid'];
						} else {
							$position = 0;
							$uu_name = $user_2_info['username'];
							$uu_id = $trade['userid'];
						}

						// optype动作类型 'cointype' => 资金类型 'plusminus' => 1增加类型
						$rs[] = Db::table('tw_finance_log')->insert([
						    'username' => $uu_name,
                            'adminname' => '系统',
                            'addtime' => time(),
                            'plusminus' => 0,
                            'amount' => $you_user_sell[$xnb . 'd'],
                            'optype' => 17,
                            'cointype' => $cointype,
                            'old_amount' => $you_user_sell[$xnb . 'd'],
                            'new_amount' => '0',
                            'userid' => $uu_id,
                            'addip'=>$this->request->ip(),
                            'position'=>$position
                        ]);

						// 处理资金变更日志-----------------E
					}
				}
			} else {
				throw new \Think\Exception('撤销失败3');
			}
		} catch(\Think\Exception $e) {
			if ($e == '撤销失败3') {
				Db::execute('rollback');
				Db::execute('unlock tables');
				return array('0', '撤销失败3');
			} else {
				Db::execute('rollback');
				Db::execute('unlock tables');
				// Db::name('Trade')->where('id', $id))->setField('status', 2);
				Db::execute('commit');
				return array('0', '撤销失败');
			}
		}

		if (check_arr($rs)) {
			Db::execute('commit');
			Db::execute('unlock tables');
			cache('getDepth', null);
			return array('1', '撤销成功');
		} else {
			Db::execute('rollback');
			Db::execute('unlock tables');
			return array('0', '撤销失败4|' . implode('|', $rs));
		}
	}
}
?>