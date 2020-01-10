<?php

namespace Home\Controller;

class MillsController extends HomeController
{
	protected function _initialize(){
		parent::_initialize();
		$allow_action=array("index","view","buy","manager","run","getMill","kjfh","kjfx");
		if(!in_array($this->request->action(),$allow_action)){
			$this->error("非法操作！");
		}
	}
	
	public function __construct(){
		parent::__construct();
		if (cache('shop_login')) {
			if (!userid()) {
				$this->redirect(url('Login/index'));
			}
		}
	}

	public function index()
	{
		
		$where['status'] = 0;
		if( intval($_GET['type']) != 0 ){
			$where['level'] = intval($_GET['type']);

			// 过滤非法字符----------------S

			if (checkstr($_GET['type'])) {
				$this->error('您输入的信息有误！');
			}

			// 过滤非法字符----------------E


		}


		$shop = Db::name('Mill');
		$count = $shop->where($where)->count();
		$Page = new \Think\Page($count, 20);
	
		$show = $Page->show();
		$list = $shop->where($where)->order('id desc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		$coin_list = Db::name('Coin')->get_all_name_list();
		$this->assign('coin_list',$coin_list);
		$mills_type_list = config('MILL_TYPE');
		$this->assign('Mills_type_list', $mills_type_list);
		return $this->fetch();
	}

	public function view(){
		$id = intval($_GET['id']);

		// 过滤非法字符----------------S

		if (checkstr($_GET['id'])) {
			$this->error('您输入的信息有误！');
		}

		// 过滤非法字符----------------E


		if( $id == 0 ){
			$this->error('参数错误');
		}

		$info = Db::name('Mill')->find($id);
		if( $info == NULL ){
			$this->error('非法操作');
		}



		$UserCoin = Db::name('UserCoin')->where('userid', userid())->find();
		
		// 用户推荐
		$user = Db::name('User')->where('id', userid())->find();

		if (!$user['invit']) {
			for (; true; ) {
				$tradeno = tradenoa();

				if (!Db::name('User')->where('invit', $tradeno)->find()) {
					break;
				}
			}

			Db::name('User')->where('id', userid())->update('invit', $tradeno));
			$user = Db::name('User')->where('id', userid())->find();
		}
                // 货币分类
		$coin_list = Db::name('Coin')->get_all_name_list();
                //dump($coin_list);
                $this->assign('coin_list',$coin_list);
		$this->assign('user', $user);
		$this->assign('cny',round($UserCoin['cny'],2)*1);
		$this->assign('coin',round($UserCoin[$info['type']],2)*1);
		$this->assign('id',$id);
		$this->assign('info',$info);
		return $this->fetch();
	}

	public function buy(){

		$userid = userid();

		if (!$userid ) {
			$this->error('请先登录！');
		}

		$input = $_POST;

		$user = Db::name('User')->find($userid);

		if (md5($input['pwdtrade']) != $user['paypassword']) {
			$this->error('交易密码错误！');
		}

		$id = intval($input['id']);

		$shop = Db::name('Mill')->find($id);

		if( !$shop ){
			$this->error('商品未开放购买');
		}

		

		$number   = intval($input['num']);

		$UserCoin = Db::name('UserCoin')->where('userid', userid())->find();

		$userBtc  = round($UserCoin[$shop['type']],2)*1;

		$userCny  = round($UserCoin['cny'],2)*1;

		$btcTotal = round($shop['coin_price'],6)*1*$number;

		$cnyTotal = round($shop['cny_price'],2)*1*$number;

		$paymentType = "";

		$totalMoney = 0;

        if( floatval($shop['coin_price']) == 0 ) $input['paytype'] = 1;


		if( intval($input['paytype']) == 1 ){
			if( $cnyTotal > $userCny ){
				$this->error('余额不足，请充值后重试');
			}
			$paymentType = "cny";
			$totalMoney  = $cnyTotal; 
		}else{
			if( $btcTotal > $userBtc ){
				$this->error('余额不足，请充值后重试');
			}
			$paymentType = "pcc";
			$totalMoney  = $btcTotal;
		}

		$shopNumber = Db::name('MillLog')->where('userid', userid(),'mill_id'=>$id)->sum('num')+0;
		//	echo $shopNumber;exit();
		if( $number+$shopNumber > $shop['limit'] && $shop['limit'] !=0 ){
			$this->error('每人限购'.$shop["limit"].'台！');
		}

		if( $number > $shop['total']-$shop['num'] ){
			$ok = $shop["total"]-$shop["num"];
			$this->error('库存不足，您还可以购买('.$ok.')台');
		}

		$mo = db();
		Db::execute('set autocommit=0');
		Db::execute('lock tables  tw_mill_log  write ,tw_user_coin write,tw_mill write,tw_mill_config write,tw_mill_fenxiao write,tw_user write');
		$rs = [];
		//$rs[] = Db::table('tw_user_coin')->where('userid', $user['id'])->setDec($paymentType, $totalMoney);
		// widuu
        if( $shop['level'] != 1 ){

        	$invit =  array($user['invit_1'],$user['invit_2'],$user['invit_3']);

			if( $user['invit_3'] != 0 ){
				$invit_user = Db::table('tw_user')->field('invit_1,invit_2')->find($user['invit_3']);
				array_push($invit, $invit_user['invit_1'],$invit_user['invit_2']);
			}else{
				array_push($invit,0,0);
			}
			
			$mill_dist    = Db::table('tw_mill_config')->find(1);

			$dist_config  = unserialize($mill_dist['config']);
			
			$dist_paytype = $dist_config['type'];

			//$dist_rate    = explode( ',', $dist_config['mill_'.$shop['level']] ); 

			$mill_type = config('MILL_TYPE');

			foreach ($invit as $k => $v) {
				if( $v != 0 ){

					//$mill_price = Db::table('tw_mill_log')->where('userid', $v)->max('mill_price');
					
					$mill_level =  Db::table('tw_mill_log')->where('userid', $v)->max('level');
					$dist_rate    = explode( ',', $dist_config['mill_'.$mill_level] );

					if( $mill_level != 0 ){
						$lilv = $dist_rate[$k] / 100;
						//$dist_price = $mill_price * $lilv;
						$dist_price = round($shop['cny_price'],2) * 1 * $lilv;

						if( $dist_price != 0 ){
							$rs[] = Db::table('tw_user_coin')->where('userid', $v)->setInc($dist_paytype,$dist_price);
							
							$rs[] = Db::table('tw_mill_fenxiao')->insert(
								array(
									'userid' => $v,
									'username' => $user['username'],
									'money'    => $dist_price,
									'level'	   => $k+1,
									'type'	   => $dist_paytype,
									'coinname' => $mill_type[$shop['level']],
									'addtime'  => time(),
									'total'    => $totalMoney,
									'number'   => $number
								)
							);
						}
					}
				}
			}
        }
        // end widuu
		//$this->error('到这里了'. $paymentType . ($UserCoin[$paymentType]-$totalMoney) . $user['id']);
        if( $totalMoney != 0 ){
		    $rs[] = Db::table('tw_user_coin')->where('userid', $user['id'])->update(array($paymentType => ($UserCoin[$paymentType]-$totalMoney)));
  		}
		
		$rs[] = Db::table('tw_mill_log')->insert(array('userid' => $user['id'], 'mill_id' => $shop['id'], 'coinname' => $shop['name'], 'level' => $shop['level'],  'num' => intval($input['num']), 'price' =>  $totalMoney, 'type' => $shop['type'],  'addtime' => time(), 'status' => 0,'paytype'=>$paymentType,'profit'=>$shop['profit'],'overtime'=>$shop['day']*86400+time(),'mill_price'=>$shop['cny_price']));
		$rs[] = Db::table('tw_mill')->where('id', $shop['id'])->setInc('num', $number);
		
		if (check_arr($rs)) {
			Db::execute('commit');
			Db::execute('unlock tables');
			$this->success('购买成功！');
			
		}
		else {
			Db::execute('rollback');
			$this->error('购买失败！');
		}

	}


	public function manager(){
		

		$shop = Db::name('MillLog');
		$condition['overtime'] = array('elt',time());
		$condition['status']   = array('eq',1);
		$overProduct = $shop->where($condition)->select();

		if( $overProduct ){
			foreach ($overProduct as $key => $value) {
				$id = intval($value['id']);
				// 最后一次收取时间
				if( empty($value['lasttime']) ){
					$lasttime = $value['runtime'];
				}else{
					$lasttime = $value['lasttime'];
				}

				$runtime = $value['overtime'] - $lasttime;

				$total = round($value['profit']*$value['num']/86400,8)*1*$runtime;
				$UserCoin = Db::name('UserCoin')->where('userid', userid())->find();

				$mo = db();
				Db::execute('set autocommit=0');
				Db::execute('lock tables  tw_mill_log  write ,tw_user_coin write');
				$rs = [];

				$rs[] = Db::table('tw_user_coin')->where('userid', userid())->update(array($value['type']=>$UserCoin[$value['type']]+$total));
				$rs[] = Db::table('tw_mill_log')->where('status', $id)->update(
						array(
							'total'	   =>$value['total']+$total,
							'lasttime' =>$value['overtime'],
							'status'   => 2
						)
					);
		
				Db::execute('commit');
				Db::execute('unlock tables');
				
			}
		}
		$userid = userid();
		$where['userid'] = $userid;
		$count = $shop->where($where)->count();
		$Page = new \Think\Page($count, 20);
	
		$show = $Page->show();
		$list = $shop->where($where)->order('id desc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function run(){
		if(IS_AJAX){
			$id = intval($_GET['id']);
			if( $id == 0 ){
				$this->error('参数错误');
			}

			$mill = Db::name('MillLog');

			if( !$mill->find($id) ){
				$this->error('矿机不存在');
			}

			$update['status']  = 1;
			$update['runtime'] = time();

			$result = $mill->where('status', $id)->update($update);
			if( $result ){
				$this->success('矿机启动成功');
			}else{
				$this->error('矿机启动失败');
			}
		}
	}


	public function getMill(){
		if(IS_AJAX){
			$id = intval($_GET['id']);
			if( $id == 0 ){
				$this->error('参数错误');
			}

			$mill = Db::name('MillLog');

			$shop = $mill->find($id);

			if( !$shop ){
				$this->error('矿机不存在');
			}


			// 最后一次收取时间
			if( empty($shop['lasttime']) ){
				$lasttime = $shop['runtime'];
			}else{
				$lasttime = $shop['lasttime'];
			}

			$runtime = time() - $lasttime;

			$total = round($shop['profit']*$shop['num']/86400,8)*1*$runtime;
			$UserCoin = Db::name('UserCoin')->where('userid', userid())->find();

			$mo = db();
			Db::execute('set autocommit=0');
			Db::execute('lock tables  tw_mill_log  write ,tw_user_coin write');
			$rs = [];

			$rs[] = Db::table('tw_user_coin')->where('userid', userid())->update(array($shop['type']=>$UserCoin[$shop['type']]+$total));
			$rs[] = Db::table('tw_mill_log')->where('status', $id)->update(
					array(
						'total'	   =>$shop['total']+$total,
						'lasttime' => time()
					)
				);
			
			if (check_arr($rs)) {
				Db::execute('commit');
				Db::execute('unlock tables');
				$this->success('收矿成功，本次收矿'.$total.strtoupper($shop['type']).'！');
			}
			else {
				Db::execute('rollback');
				$this->error('收矿失败，稍后重试！');
			}
		}
	}

	public function kjfh(){
		$this->assign('prompt_text', Model('Text')->get_content('finance_myjp'));
		check_server();
		$where['userid'] = userid();
		$Model = Db::name('MillFenhong');
		$count = $Model->where($where)->count();
		$Page = new \Think\Page($count, 10);
		$show = $Page->show();
		$list = $Model->where($where)->order('id desc')->limit(0, 10)->select();

		
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function kjfx(){
		$this->assign('prompt_text', Model('Text')->get_content('finance_myjp'));
		check_server();
		$where['userid'] = userid();
		$Model = Db::name('MillFenxiao');
		$count = $Model->where($where)->count();
		$Page = new \Think\Page($count, 10);
		$show = $Page->show();
		$list = $Model->where($where)->order('id desc')->limit(0, 10)->select();

		
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

}
?>