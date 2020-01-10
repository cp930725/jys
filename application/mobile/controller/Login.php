<?php

namespace app\mobile\controller;

use think\Db;

class Login extends Mobile
{
	protected function _initialize()
	{
		parent::_initialize();	$allow_action=array("index","register","upregister","complete","chkUser","chkmobile","submit","loginout","findpwd","findpaypwd","webreg");
		if(!in_array(ACTION_NAME,$allow_action)){
			$this->error(lang("非法操作！"));
		}
	}
	
	// 用户协议
	public function webreg()
	{
		return $this->fetch();
	}
	
	public function index()
	{
		return $this->fetch();
	}

	// 登录提交处理
	public function submit($username, $password, $verify = NULL, $ga='')
	{
		// 过滤非法字符----------------S
		if (checkstr($username) || checkstr($password) || checkstr($verify) || checkstr($ga)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E

		if (config('login_verify')) {
			if (!check_verify(strtoupper($verify),'1')) {
				$this->error(lang('图形验证码错误!'));
			}
		}
/*		if (check($username, 'email')) {
			$user = Db::name('User')->where(array('username' => $username))->find();
			$remark = '通过邮箱登录';
		}*/
		if (empty($user)) {
			$user = Db::name('User')->where(array('username' => $username))->find();
			$remark = '通过用户名登录';
		}
		if (!$user) {
			$this->error(lang('用户不存在！'));
		}
		if (strlen($password) > 16 || strlen($password) < 6) {
			$this->error(lang('密码格式为6~16位，不含特殊符号！'));
		}
		if (!check($password, 'password')) {
			$this->error(lang('密码格式为6~16位，不含特殊符号！'));
		}
		if (md5($password) != $user['password']){
			$this->error(lang('登录密码错误！'));
		}

		// 处理谷歌身份验证器-------------------S
		if($user['ga']){
			$ga_n = new \app\common\ext\GoogleAuthenticator();
			$arr = explode('|', $user['ga']);
			// 存储的信息为谷歌密钥
			$secret = $arr[0];
			// 存储的登录状态为1需要验证，0不需要验证
			$ga_is_login = $arr[1];
			// 判断是否需要验证
			if($ga_is_login){
				if(!$ga){
					$this->error(lang('请输入双重验证码！'));
				}
				if(!check($ga,'d')){
					$this->error(lang('双重验证码格式错误！'));
				}
				// 判断登录有无验证码
				$aa = $ga_n->verifyCode($secret, $ga, 1);
				if (!$aa){
					$this->error(lang('双重身份验证码错误！'));
				}
			}
		}
		// 处理谷歌身份验证器-------------------E
		
		if (isset($user['status'])&&$user['status'] != 1) {
			$this->error(lang('你的账号已冻结请联系管理员！'));
		}

		Db::execute('set autocommit=0');
		Db::execute('lock tables tw_user write , tw_user_log write ');

		$rs = array();
		$rs[] = Db::table('tw_user')->where(array('id' => $user['id']))->setInc('logins', 1);
		$rs[] = Db::table('tw_user_log')->insert(array('userid' => $user['id'], 'type' => '登录', 'remark' => $remark, 'addtime' => time(), 'addip' => $this->request->ip(), 'addr' => get_city_ip($this->request->ip()), 'status' => 1));
		if (check_arr($rs)) {
			Db::execute('commit');
			Db::execute('unlock tables');

			session('userId', $user['id']);
			session('userName', $user['username']);
            session('is_generalize',$user['is_generalize']);
			file_put_contents("login/".$user['id'].".txt", time());
			session('loginTime', time());

			if (!$user['paypassword']) {
				session('regpaypassword', $rs[0]);
				session('reguserId', $user['id']);
			}

			if (!$user['truename']) {
				session('regtruename', $rs[0]);
				session('reguserId', $user['id']);
			}
			$this->success(lang('登录成功！'));
		} else {
			Db::execute('rollback');
			$this->error(lang('登录失败！'));
		}
	}
	
	// 注册页面
	public function register()
	{
		if(!empty($_SESSION['reguserId'])){
			$user = Db::name('User')->where(array('id' => $_SESSION['reguserId']))->find();
			if (!empty($user)) {
				header("Location:/Login/complete");
			}
		}
		
		creatToken(); //创建token
        $areas = Db::name('area')->select();
        $this->assign('areas',$areas);
		return $this->fetch();
	}
	
	// 注册提交处理	
	public function upregister($area_id, $mobile, $password, $repassword, $verify, $invit, $mobilecode, $qz, $token)
	{
		// 过滤非法字符----------------S
		if (checkstr($mobile) || checkstr($password) || checkstr($repassword) || checkstr($verify) || checkstr($invit) || checkstr($mobilecode) || checkstr($qz)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E
		
		// 昵称
		$enname = $mobile;
		
		// Token令牌验证
		if (!checkToken($token)) {
			$this->error(lang('令牌验证错误，请刷新!'));
		}

        if(!captcha_check($verify)){
            $this->error(lang('图形验证码错误!'));
        }
		if (Db::name('User')->where(array('username' => $mobile))->find()) {
			$this->error(lang('用户名已存在'));
		}
		if (Db::name('User')->where(array('enname' => $enname))->find()) {
			$this->error(lang('昵称已存在'));
		}
		if (!check($mobile, 'mobile')) {
			$this->error(lang('手机格式错误！'));
		}
/*		if (!check($enname, 'username')) {
			$this->error(lang('昵称格式错误！'));
		}*/
		if ($mobile != session('chkmobile')) {
			$this->error(lang('短信验证码不匹配！')); //手机号不匹配或验证码超时
		}
		if (md5($mobilecode.'mima') != session('mobileregss_verify')) {
			if (config('register_verify')) {
				$this->error(lang('短信验证码错误！'));
			}
		}
		
		if (strlen($password) > 16 || strlen($password) < 6) {
			$this->error(lang('密码格式为6~16位，不含特殊符号！'));
		}
		if (!check($password, 'password')) {
			$this->error(lang('密码格式为6~16位，不含特殊符号！'));
		}
		if ($password != $repassword) {
			$this->error(lang('两次输入的密码不一致'));
		}

		//邀请码
		if (!$invit) {
			$invit = session('invit');
		}
		
		$invituser = Db::name('User')->where(array('invit' => $invit))->find();
		if (!$invituser) {
			$invituser = Db::name('User')->where(array('id' => $invit))->find();
		}
		if (!$invituser) {
			$invituser = Db::name('User')->where(array('username' => $invit))->find();
		}
		if (!$invituser) {
			$invituser = Db::name('User')->where(array('mobile' => $invit))->find();
		}
		if ($invituser) {
			$invit_1 = $invituser['id'];
			$invit_2 = $invituser['invit_1'];
			$invit_3 = $invituser['invit_2'];
		} else {
			$invit_1 = 0;
			$invit_2 = 0;
			$invit_3 = 0;
		}
		
		for (; true; ) {
			$tradeno = tradenoa();
			if (!Db::name('User')->where(array('invit' => $tradeno))->find()) {
				break;
			}
		}

		$last_user_noid = Db::name('user')->field('noid')->order('id desc')->find();
		if(empty($last_user_noid)){
			$user_noid = 12837 + mt_rand(10,99);
		} else {
			$user_noid = $last_user_noid['noid'] + mt_rand(10,99);
		}

		$mo = Db::name();
		Db::execute('set autocommit=0');
		Db::execute('lock tables tw_user write , tw_user_coin write , tw_invit write ');
		$rs = array();
		$rs[] = Db::table('tw_user')->insertGetId(array('username' => $mobile, 'mobile'=>$mobile, 'mobiletime'=>time(), 'password' => md5($password), 'invit' => $tradeno, 'tpwdsetting' => 1, 'invit_1' => $invit_1, 'invit_2' => $invit_2, 'invit_3' => $invit_3, 'addip' => $this->request->ip(), 'addr' => get_city_ip($this->request->ip()), 'addtime' => time(), 'status' => 1 , 'otcuser'=>trim($mobile),'enname'=>$enname,'qz'=>$qz));
		
		$user_coin = array('userid' => $rs[0]);
		
/*		// 注册赠送币（直接赠送）
		if (config('give_type') == 1) {
			$coin_name = config('xnb_mr_song'); //赠送币种
			$user_coin[$coin_name] = config('xnb_mr_song_num');
			
			// 赠送邀请人邀请奖励
			if(config('song_num_1') > 0 && $invit_1 > 0){
				$coin_num_1 = config('song_num_1');
				$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_1, 'invit' => $rs[0], 'name' => '一代注册赠送', 'type' => '注册赠送'.strtoupper($coin_name), 'num' => 0, 'mum' => 0, 'fee' => $coin_num_1, 'addtime' => time(), 'status' => 0,'coin'=>strtoupper($coin_name)));
			}
			if(config('song_num_2') > 0 && $invit_2 > 0){
				$coin_num_2 = config('song_num_2');
				$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_2, 'invit' => $rs[0], 'name' => '二代注册赠送', 'type' => '注册赠送'.strtoupper($coin_name), 'num' => 0, 'mum' => 0, 'fee' => $coin_num_2, 'addtime' => time(), 'status' => 0,'coin'=>strtoupper($coin_name)));
			}
			if(config('song_num_3') > 0 && $invit_3 > 0){
				$coin_num_3 = config('song_num_3');
				$rs[] = Db::table('tw_invit')->insert(array('userid' => $invit_3, 'invit' => $rs[0], 'name' => '三代注册赠送', 'type' => '注册赠送'.strtoupper($coin_name), 'num' => 0, 'mum' => 0, 'fee' => $coin_num_3, 'addtime' => time(), 'status' => 0,'coin'=>strtoupper($coin_name)));
			}
		}*/
		
		// 创建用户数字资产档案
		$rs[] = Db::table('tw_user_coin')->insertGetId($user_coin);
		if (check_arr($rs)) {
			Db::execute('commit');
			Db::execute('unlock tables');
			
			session('mobileregss_verify', null); //初始化动态验证码
			
			session('reguserId', $rs[0]);
			$user = Db::table('tw_user')->where(array('id'=>$rs[0]))->find();
			session('userNoid',$user['noid']);
			session('is_generalize',$user['is_generalize']);
			$this->success(lang('注册成功！'));
		} else {
		    Db::execute('rollback');
			$this->error(lang('注册失败！'));
		}
	}
	
	// 注册成功页面
	public function complete()
	{
		if(!empty($_SESSION['reguserId'])) {
			$user = Db::name('User')->where(array('id' => session('reguserId')))->find();
			session('userId', $user['id']);
			session('userName', $user['username']);
			session('userNoid',$user['noid']);
			// file_put_contents("login/".$user['id'].".txt", time());
			// session('loginTime', time());
			$this->assign('user', $user);
			return $this->fetch();
		} else {
			header("Location:/Login/index");
		}
	}

	public function chkUser($username)
	{
		// 过滤非法字符----------------S
		if (checkstr($username)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E

		if (!check($username, 'username')) {
			$this->error(lang('用户名格式错误！'));
		}
		if (Db::name('User')->where(array('enname' => $username))->find()) {
			$this->error(lang('用户名已存在'));
		}

		$this->success('');
	}
	
	public function chkmobile($mobile)
	{
		// 过滤非法字符----------------S
		if (checkstr($mobile)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E
		
		if (Db::name('User')->where(array('username' => $mobile))->find()) {
			$this->error(lang('账号已存在'));
		}

		$this->success('');
	}

	public function loginout()
	{
		session(null);
		$this->redirect('/');
	}

	public function findpwd()
	{
		if ($this->request->isPost()) {
			$input = I('post.');
			foreach ($input as $k => $v) {
				// 过滤非法字符----------------S
				if (checkstr($v)) {
					$this->error(lang('您输入的信息有误！'));
				}
				// 过滤非法字符----------------E
			}

			if (!check_verify(strtoupper($input['verify']),'1')) {
				$this->error(lang('图形验证码错误!'));
			}

			if (!check($input['mobile'], 'mobile')) {
				$this->error(lang('手机号码格式错误！'));
			}

			if (!check($input['mobile_verify'], 'd')) {
				$this->error(lang('短信验证码格式错误！'));
			}

			if ($input['mobile_verify'] != session('findpwd_verify')) {
				$this->error(lang('短信验证码错误！'));
			}

			$user = Db::name('User')->where(array('username' => $input['mobile']))->find();
			if (!$user) {
				$this->error(lang('用户不存在！'));
			}

			if ($user['mobile'] != $input['mobile']) {
				$this->error(lang('手机号码错误！'));
			}
			if ($user['mibao_question'] != $input['mibao_question']) {
				$this->error(lang('密保问题错误！'));
			}
			if ($user['mibao_answer'] != $input['mibao_answer']) {
				$this->error(lang('密保答案错误！'));
			}
/*			if (!check($input['password'], 'password')) {
				$this->error('新登录密码格式错误！');
			}*/
			if (strlen($input['password']) > 16 || strlen($input['password']) < 6) {
				$this->error(lang('密码格式为6~16位，不含特殊符号！'));
			}
			if (!check($input['password'], 'password')) {
				$this->error(lang('密码格式为6~16位，不含特殊符号！'));
			}
			if ($input['password'] != $input['repassword']) {
				$this->error(lang('两次输入的密码不一致！'));
			}
			if($user['paypassword'] == md5($input['password'])){
				$this->error(lang('登录密码不能和交易密码相同！'));
			}
			if($user['password'] == md5($input['password'])){
				$this->error(lang('新登录密码与旧登录密码一致！'));
			}

			$mo = Db::name();
			Db::execute('set autocommit=0');
			Db::execute('lock tables tw_user write , tw_user_log write ');
			
			$rs = array();
			$rs[] = Db::table('tw_user')->where(array('id' => $user['id']))->save(array('password' => md5($input['password'])));
			if (check_arr($rs)) {
				Db::execute('commit');
				Db::execute('unlock tables');
				$this->success(lang('修改成功'));
			} else {
				Db::execute('rollback');
				$this->error(lang('修改失败'));
			}
		} else {
			return $this->fetch();
		}
	}

	public function findpaypwd()
	{
		if ($this->request->isPost()) {
			$input = I('post.');
			foreach ($input as $k => $v) {
				// 过滤非法字符----------------S
				if (checkstr($v)) {
					$this->error(lang('您输入的信息有误！'));
				}
				// 过滤非法字符----------------E
			}

			if (!check($input['username'], 'mobile')) {
				$this->error(lang('用户名格式错误！'));
			}
			if (!check($input['mobile'], 'mobile')) {
				$this->error(lang('手机号码格式错误！'));
			}
			if (!check($input['mobile_verify'], 'd')) {
				$this->error(lang('短信验证码格式错误！'));
			}
			if ($input['mobile_verify'] != session('findpaypwd_verify')) {
				$this->error(lang('短信验证码错误！'));
			}

			$user = Db::name('User')->where(array('username' => $input['username']))->find();
			if (!$user) {
				$this->error(lang('用户名不存在！'));
			}
			if ($user['mobile'] != $input['mobile']) {
				$this->error(lang('用户名或手机号码错误！'));
			}
			if ($user['mibao_question'] != $input['mibao_question']) {
				$this->error(lang('密保问题错误！'));
			}
			if ($user['mibao_answer'] != $input['mibao_answer']) {
				$this->error(lang('密保答案错误！'));
			}
/*			if (!check($input['password'], 'password')) {
				$this->error('新交易密码格式错误！');
			}*/
			if (strlen($input['password']) > 16 || strlen($input['password']) < 6) {
				$this->error(lang('密码格式为6~16位，不含特殊符号！'));
			}
			if (!check($input['password'], 'password')) {
				$this->error(lang('密码格式为6~16位，不含特殊符号！'));
			}
			if ($input['password'] != $input['repassword']) {
				$this->error(lang('两次输入的密码不一致！'));
			}
			if($user['password'] == md5($input['password'])){
				$this->error(lang('交易密码不能和登录密码相同！'));
			}
			if($user['paypassword'] == md5($input['password'])){
				$this->error(lang('新交易密码与旧交易密码一致！'));
			}

			$mo = Db::name();
			Db::execute('set autocommit=0');
			Db::execute('lock tables tw_user write , tw_user_log write ');

			$rs = array();
			$rs[] = Db::table('tw_user')->where(array('id' => $user['id']))->save(array('paypassword' => md5($input['password'])));
			if (check_arr($rs)) {
				Db::execute('commit');
				Db::execute('unlock tables');
				$this->success('修改成功');
			} else {
				Db::execute('rollback');
				$this->error('修改失败' . Db::table('tw_user')->getLastSql());
			}
		} else {
			return $this->fetch();
		}
	}
}
?>