<?php
namespace app\home\controller;

use think\Db;

class User extends Home
{
	protected function _initialize()
	{
		parent::_initialize();
		$allow_action=array("index","login","nameauth","uppassword","paypassword","uppaypassword","uppaypasswordset","ga","mobile","upmobile","alipay","upalipay","tpwdset","tpwdsetting","uptpwdsetting","bank","upbank","delbank","qianbao","upqianbao","delqianbao","goods","upgoods","delgoods","log","gaGoogle","kyc","kyc1","kyc2","kyc1_Handle","kyc2_Handle","kyc_api");
		if(!in_array($this->request->action(),$allow_action)){
			$this->error(lang("非法操作！"));
		}
	}

	public function index()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}
		
		//获取用户信息
		$user = Db::name('User')->where('id', userid())->find();
		$this->assign('user', $user);
		$this->assign('mobiles', substr_replace($user['mobile'], '****', 6, 5));
		
		//登录日志
		$userlog = Db::name('UserLog')->where('userid', userid())->order('id desc')->limit(10)->select();
		$this->assign('userlog', $userlog);
		
		$is_ga = ($user['ga'] ? 1 : 0);
		$this->assign('is_ga', $is_ga);

		if (!$is_ga) {
			$ga = new \app\common\ext\GoogleAuthenticator();
			$secret = $ga->createSecret();
			session('secret', $secret);
			$this->assign('Asecret', $secret);
			
			//$zhanghu = $user['username'].'-'.$_SERVER['HTTP_HOST'];
			$zhanghu = config('google_prefix') . '-' . $user['username'];
			$this->assign('zhanghu', $zhanghu);
			//$qrCodeUrl = $ga->getQRCodeGoogleUrl($user['username'] . '-' . $_SERVER['HTTP_HOST'], $secret);
			$qrCodeUrl = $ga->getQRCodeGoogleUrl(cache('google_prefix') . '-' . $user['username'], $secret);
			$this->assign('qrCodeUrl', $qrCodeUrl);
		} else {
			$arr = explode('|', $user['ga']);
			$this->assign('ga_login', $arr[1]);
			$this->assign('ga_transfer', $arr[2]);
		}
		
		return $this->fetch();
	}


	public function login()
	{
		$link= Db::name('Link')->where('status', 1)->select();
		$this->assign('link', $link);
		return $this->fetch();
	}

	public function nameauth()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}
		
		$user = Db::name('User')->where('id', userid())->find();
		if ($user['idcard']) {
			$user['idcard'] = substr_replace($user['idcard'], '********', 6, 8);
		}
		
/*		if (!$user['idcard']) {
			//未设置
			$this->redirect('/Login/register3');
		}*/

		$this->assign('user', $user);
		return $this->fetch();
	}
	
	// KYC身份认证，身份证，护照
	public function kyc()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}
		
		$user = Db::name('User')->where('id', userid())->find();
		if ($user['idcard']) {
			$user['idcard'] = substr_replace($user['idcard'], '********', 6, 8);
		}
		
/*		if (!$user['idcard']) {
			//未设置
			$this->redirect('/Login/register3');
		}*/

		$this->assign('user', $user);
		return $this->fetch();
	}
	
	public function kyc1()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}
		
		$user = Db::name('User')->where('id', userid())->find();
		if ($user['kyc_lv'] == 1) {
			if ($user['idstate'] == 1) {
				$this->error(lang('正在审核中'), url('User/index'));
			} else if ($user['idstate'] == 2) {
				$this->error(lang('非法操作'), url('User/index'));
			}
		} else if ($user['kyc_lv'] == 2) {
			$this->error(lang('非法操作'), url('User/index'));
		} 
		
		if ($user['idcard']) {
			$user['idcard'] = substr_replace($user['idcard'], '********', 6, 8);
		}

		$this->assign('user', $user);
		return $this->fetch();
	}
	public function kyc1_Handle($idnationality, $idtype, $truename, $idcard)
	{
		// 过滤非法字符----------------S
		if (checkstr($idnationality) || checkstr($idtype) || checkstr($truename) || checkstr($idcard)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E

		if (!userid()) {
			$this->redirect(url('Login/index'));
		}
		
		$user = Db::name('User')->where('id', userid())->find();
		if ($user['kyc_lv'] == 1) {
			if ($user['idstate'] == 1) {
				$this->redirect('/User/index');
			} else if ($user['idstate'] == 2) {
				$this->redirect('/User/index');
			}
		} else if ($user['kyc_lv'] == 2) {
			$this->redirect('/User/index');
		}
		
		if (empty($idnationality)) {
			$this->error(lang('请输入国籍'));
		}

		if ($user['idcard'] != $idcard) {
			if (Db::name('User')->where('idcard', $idcard)->find()) {
				$this->error(lang('该身份证号已被注册!'));
			}
		}
		
		if ($idnationality == '中国' || $idnationality == 'China' || $idnationality == 'china') {
/*			if (!check($truename, 'truename')) {
				$this->error('真实姓名格式错误！');
			}*/
			if (!check($idcard, 'idcard')) {
		 		$this->error(lang('身份证号格式错误！'));
			}
			$this->kyc_api($idcard,$truename); // 启动api自动认证
		}
		
		if (Db::name('User')->where('id', userid())->update([
		    'kyc_lv' => 1,
            'idnationality' => $idnationality,
            'idtype' => $idtype,
            'truename' => $truename,
            'idcard' => $idcard,
            'idstate' => 1])) {
			$this->success(lang('身份验证成功！'));
		} else {
			$this->error(lang('身份验证失败！'));
		}
	}
	// API实名认证
    public function kyc_api($cardno,$name)
    {
		// 过滤非法字符----------------S
		if (checkstr($cardno) || checkstr($name)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E
		
		if(empty($cardno) || empty($name)){
			$this->error(lang('非法操作！'));
		}
		
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}
		
		function postData($url, $data, $method='GET')
		{
			$host = $url;
			$path = "/lianzhuo/idcard";
			$appcode = config('realpass'); //填写appcode
			$headers = [];
			array_push($headers, "Authorization:APPCODE " . $appcode);
			$bodys = "";
			
			$url = $url.$path.'?cardno='.$data['cardno'].'&name='.$data['name'];

			$curl = curl_init(); // 启动一个CURL会话
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_FAILONERROR, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容

			if (1 == strpos("$". $host, "https://")) {
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			}

			$handles = curl_exec($curl);
			curl_close($curl); // 关闭CURL会话
			$handles = json_decode($handles, true);
			return $handles;
		}
		
		$data = array(
			'cardno' => $cardno, //证件号码
			'name' => $name, //真实姓名
		);
		
		$urls = 'http://idcard.market.alicloudapi.com';
		$handlas = postData($urls,$data);

		if($handlas['resp']['code'] || $handlas['resp']['code'] == 0){
			Db::name('User')->where('id', userid())->update(array('idapi' => $handlas['resp']['code']));
		}
	}
	
	public function kyc2()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}
		
		$user = Db::name('User')->where('id', userid())->find();
		if ($user['kyc_lv'] == 1) {
			if ($user['idstate'] == 2) {} else {
				$this->error(lang('非法操作'), url('User/index'));
			}
		} else if ($user['kyc_lv'] == 2) {
			if ($user['idstate'] == 1) {
				$this->error(lang('非法操作'), url('User/index'));
			} else if ($user['idstate'] == 2) {
				$this->error(lang('非法操作'), url('User/index'));
			}
		}
		
		$this->assign('user', $user);
		return $this->fetch();
	}
	public function kyc2_Handle($idimg1, $idimg2, $idimg3)
	{
		// 过滤非法字符----------------S
		if (checkstr($idimg1) || checkstr($idimg2) || checkstr($idimg3)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E

		if (!userid()) {
			$this->redirect(url('Login/index'));
		}
		
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}
		
		$user = Db::name('User')->where('id', userid())->find();
		if ($user['kyc_lv'] == 1) {
			if ($user['idstate'] == 2) {} else {
				$this->redirect('/User/index');
			}
		} else if ($user['kyc_lv'] == 2) {
			if ($user['idstate'] == 1) {
				$this->redirect('/User/index');
			} else if ($user['idstate'] == 2) {
				$this->redirect('/User/index');
			}
		}
		
		
		if(!$idimg1 && !$idimg2 && !$idimg3){
			$this->error(lang('请上传证件照后再提交！'));
		}
		
		if (Db::name('User')->where('id', userid())->update(array('kyc_lv' => 2, 'idimg1' => $idimg1, 'idimg2' => $idimg2, 'idimg3' => $idimg3, 'idstate' => 1))) {
			$this->success(lang('证件上传成功！'));
		} else {
			$this->error(lang('证件上传失败！'));
		}
	}

	// 修改登录密码：提交处理
	public function uppassword($mobile_verify, $oldpassword, $newpassword, $repassword)
	{
		// 过滤非法字符----------------S
		if (checkstr($mobile_verify) || checkstr($oldpassword) || checkstr($newpassword) || checkstr($repassword)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E

		if (!userid()) {
			$this->error(lang('请先登录！'));
		}

		$user_info = Db::name('user')->where('status', userid())->find();
		if ($user_info['mobile'] != session('chkmobile')) {
			$this->error(lang('短信验证码不匹配！')); //手机号不匹配或验证码超时
		}
		if (!check($mobile_verify, 'd')) {
			$this->error(lang('短信验证码格式错误！'));
		}
		if (md5($mobile_verify.'mima') != session('pass_verify')) {
			$this->error(lang('短信验证码错误！'));
		}

		if (!check($oldpassword, 'password')) {
			$this->error(lang('密码格式为6~16位，不含特殊符号！'));
		}
		if (strlen($newpassword) > 16 || strlen($newpassword) < 6) {
			$this->error(lang('密码格式为6~16位，不含特殊符号！'));
		}
		if (!check($newpassword, 'password')) {
			$this->error(lang('密码格式为6~16位，不含特殊符号！'));
		}
		if ($newpassword != $repassword) {
			$this->error(lang('两次输入的密码不一致'));
		}

		$password = Db::name('User')->where('id', userid())->value('password');
		$paypasswords = Db::name('User')->where('id', userid())->value('paypassword');
		
		if (md5($oldpassword) != $password) {
			$this->error(lang('旧登录密码错误！'));
		}
		if (md5($newpassword) == $paypasswords) {
			$this->error(lang('登录密码不能和交易密码相同！'));
		}
		if (md5($newpassword) == $password) {
			$this->error(lang('新登录密码跟原密码相同，修改失败！'));
		}

		$rs = Db::name('User')->where('id', userid())->update(array('password' => md5($newpassword)));
		if ($rs) {
			$this->success(lang('修改成功'));
		} else {
			$this->error(lang('修改失败'));
		}
	}

	// 设置交易密码：提交处理
	public function uppaypasswordset($paypassword, $repaypassword, $mobile_verify)
	{
		// 过滤非法字符----------------S
		if (checkstr($paypassword) || checkstr($repaypassword) || checkstr($mobile_verify)) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E
		
		if (!userid()) {
			$this->error(lang('请先登录！'));
		}
		
		$user_info = Db::name('user')->where('status', userid())->find();
		if($user_info['paypassword']){
			$this->error(lang('非法操作'));
		}
		
		if (!check($mobile_verify, 'd')) {
			$this->error(lang('短信验证码格式错误！'));
		}
		if ($mobile_verify != session('paypass_verify')) {
			$this->error(lang('短信验证码错误！'));
		}
		
		if (strlen($paypassword) > 16 || strlen($paypassword) < 6) {
			$this->error(lang('密码格式为6~16位，不含特殊符号！'));
		}
		if (!check($paypassword, 'password')) {
			$this->error(lang('密码格式为6~16位，不含特殊符号！'));
		}
		if ($paypassword != $repaypassword) {
			$this->error(lang('两次输入的密码不一致'));
		}
		
		if (Db::name('User')->where('id', userid())->where('password', md5($paypassword))->find()) {
			$this->error('交易密码不能和登录密码一样！');
		}
		
		$rs = Db::name('User')->where('id', userid())->update(array('paypassword' => md5($paypassword)));
		if ($rs) {
			$this->success(lang('设置交易密码成功！'));
		}
		else {
			$this->error(lang('设置交易密码失败！'));
		}
	}
	
	// 修改交易密码：提交处理
	public function uppaypassword($mobile_verify, $oldpaypassword, $newpaypassword, $repaypassword)
	{
		// 过滤非法字符----------------S
		if (checkstr($mobile_verify) || checkstr($oldpaypassword) || checkstr($newpaypassword) || checkstr($repaypassword)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E

		if (!userid()) {
			$this->error(lang('请先登录！'));
		}

		$user_info = Db::name('user')->where('status', userid())->find();
		
/*		if ($user_info['mobile'] != session('chkmobile')) {
			$this->error(lang('短信验证码不匹配！')); //手机号不匹配或验证码超时
		}*/
		if (!check($mobile_verify, 'd')) {
			$this->error(lang('短信验证码格式错误！'));
		}
		if ($mobile_verify != session('paypass_verify')) {
			$this->error(lang('短信验证码错误！'));
		}

		if (!check($oldpaypassword, 'password')) {
			$this->error(lang('密码格式为6~16位，不含特殊符号！'));
		}
		if (strlen($newpaypassword) > 16 || strlen($newpaypassword) < 6) {
			$this->error(lang('密码格式为6~16位，不含特殊符号！'));
		}
		if (!check($newpaypassword, 'password')) {
			$this->error(lang('密码格式为6~16位，不含特殊符号！'));
		}
		if ($newpaypassword != $repaypassword) {
			$this->error(lang('两次输入的密码不一致'));
		}

		$user = Db::name('User')->where('id', userid())->find();
		if (md5($oldpaypassword) != $user['paypassword']) {
			$this->error(lang('旧交易密码错误！'));
		}
		if (md5($newpaypassword) == $user['paypassword']) {
			$this->error(lang('新交易密码跟原交易密码相同，修改失败！'));
		}
		if (md5($newpaypassword) == $user['password']) {
			$this->error(lang('交易密码不能和登录密码相同！'));
		}

		$rs = Db::name('User')->where('id', userid())->update(array('paypassword' => md5($newpaypassword)));
		if ($rs) {
			$this->success(lang('修改成功'));
		} else {
			$this->error(lang('修改失败'));
		}
	}

	public function ga()
	{
		if (empty($_POST)) {
			if (!userid()) {
				$this->redirect(url('Login/index'));
			}

			$user = Db::name('User')->where('id', userid())->find();
			$is_ga = ($user['ga'] ? 1 : 0);
			$this->assign('is_ga', $is_ga);

			if (!$is_ga) {
				$ga = new \app\common\ext\GoogleAuthenticator();
				$secret = $ga->createSecret();
				session('secret', $secret);
				$this->assign('Asecret', $secret);
				
				//$zhanghu = $user['username'].'-'.$_SERVER['HTTP_HOST'];
				$zhanghu = config('google_prefix') . '-' . $user['username'];
				$this->assign('zhanghu', $zhanghu);
				//$qrCodeUrl = $ga->getQRCodeGoogleUrl($user['username'] . '-' . $_SERVER['HTTP_HOST'], $secret);
				$qrCodeUrl = $ga->getQRCodeGoogleUrl(cache('google_prefix') . '-' . $user['username'], $secret);
				$this->assign('qrCodeUrl', $qrCodeUrl);
				return $this->fetch();
			} else {
				$arr = explode('|', $user['ga']);
				$this->assign('ga_login', $arr[1]);
				$this->assign('ga_transfer', $arr[2]);
				return $this->fetch();
			}
		} else {

			foreach ($_POST as $k => $v) {
				// 过滤非法字符----------------S
				if (checkstr($v)) {
					$this->error(lang('您输入的信息有误！'));
				}
				// 过滤非法字符----------------E
			}

			if (!userid()) {
				$this->error(lang('登录已经失效,请重新登录!'));
			}

			$delete = '';
			$gacode = trim(input('ga'));
			$type = trim(input('type'));
			$ga_login = (input('ga_login') == false ? 0 : 1);
			$ga_transfer = (input('ga_transfer') == false ? 0 : 1);

			if (!$gacode) {
				$this->error(lang('请输入验证码!'));
			}

			if ($type == 'add') {
				$secret = session('secret');

				if (!$secret) {
					$this->error(lang('验证码已经失效,请刷新网页!'));
				}
			} else if (($type == 'updat') || ($type == 'delet')) {
				$user = Db::name('User')->where('id = ' . userid())->find();

				if (!$user['ga']) {
					$this->error(lang('还未设置谷歌验证码!'));
				}

				$arr = explode('|', $user['ga']);
				$secret = $arr[0];
				$delete = ($type == 'delet' ? 1 : 0);
			} else {
				$this->error(lang('操作未定义'));
			}

			$ga = new \app\common\ext\GoogleAuthenticator();
			if ($ga->verifyCode($secret, $gacode, 1)) {
				$ga_val = ($delete == '' ? $secret . '|' . $ga_login . '|' . $ga_transfer : '');
				Db::name('User')->update(['id' => userid(), 'ga' => $ga_val]);
				$this->success(lang('操作成功'));
			} else {
				$this->error(lang('验证失败'));
			}
		}
	}
	
	// 谷歌验证器
	public function gaGoogle($ga_verify, $ga_login=NULL, $ga_transfer=NULL, $type)
	{
		// 过滤非法字符----------------S
		if (checkstr($ga_verify)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E

		if (!userid()) {
			$this->error(lang('登录已经失效,请重新登录!'));
		}
		
		$ga_login = ($ga_login == false ? 0 : 1);
		$ga_transfer = ($ga_transfer == false ? 0 : 1);
		
		$user_info = Db::name('user')->where('status', userid())->find();

		if (!$ga_verify) {
			$this->error(lang('谷歌验证码错误！'));
		}
		
		if ($type == 'add') {
			$secret = session('secret');

			if (!$secret) {
				$this->error(lang('验证码已经失效,请刷新网页!'));
			}
		} else if (($type == 'updat') || ($type == 'delet')) {	
			$user = Db::name('User')->where('id = ' . userid())->find();

			if (!$user['ga']) {
				$this->error(lang('还未设置谷歌验证码!'));
			}

			$arr = explode('|', $user['ga']);
			$secret = $arr[0];
			$delete = ($type == 'delet' ? 1 : 0);
		} else {
			$this->error(lang('操作未定义'));
		}

		$ga = new \app\common\ext\GoogleAuthenticator();
		if ($ga->verifyCode($secret, $ga_verify, 1)) {
			$ga_val = ($delete == '' ? $secret . '|' . $ga_login . '|' . $ga_transfer : '');
			Db::name('User')->update(['id' => userid(), 'ga' => $ga_val]);
			$this->success(lang('操作成功'));
		} else {
			$this->error(lang('验证失败'));
		}
	}

	public function mobile()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		$user = Db::name('User')->where('id', userid())->find();

		if ($user['mobile']) {
			$user['mobile'] = substr_replace($user['mobile'], '****', 3, 4);
		}

		$this->assign('user', $user);
		return $this->fetch();
	}

	public function upmobile($mobile, $mobile_verify)
	{
		// 过滤非法字符----------------S
		if (checkstr($mobile) || checkstr($mobile_verify)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E

		if (!userid()) {
			$this->error(lang('您没有登录请先登录！'));
		}

		if (!check($mobile, 'mobile')) {
			$this->error(lang('手机号码格式错误！'));
		}
		if ($mobile != session('chkmobile')) {
			$this->error(lang('短信验证码不匹配！')); //手机号不匹配或验证码超时
		}
		if (Db::name('User')->where('mobile', $mobile)->find()) {
			$this->error(lang('手机号码已存在！'));
		}
		
		if (!check($mobile_verify, 'd')) {
			$this->error(lang('短信验证码格式错误！'));
		}
		if ($mobile_verify != session('mobilebd_verify')) {
			$this->error(lang('短信验证码错误！'));
		}

		$rs = Db::name('User')->where('id', userid())->update(['mobile' => $mobile, 'mobiletime' => time()]);
		if ($rs) {
			$this->success(lang('手机认证成功！'));
		} else {
			$this->error(lang('手机认证失败！'));
		}
	}

	public function alipay()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		D('User')->check_update();
		$user = Db::name('User')->where('id', userid())->find();
		$this->assign('user', $user);
		return $this->fetch();
	}

	public function upalipay($alipay = NULL, $paypassword = NULL)
	{
		// 过滤非法字符----------------S
		if (checkstr($alipay) || checkstr($paypassword)) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E

		if (!userid()) {
			$this->error('您没有登录请先登录！');
		}

		if (!check($alipay, 'mobile')) {
			if (!check($alipay, 'email')) {
				$this->error('支付宝账号格式错误！');
			}
		}

		if (!check($paypassword, 'password')) {
			$this->error('密码格式为6~16位，不含特殊符号！');
		}

		$user = Db::name('User')->where('id', userid())->find();
		if (md5($paypassword) != $user['paypassword']) {
			$this->error('交易密码错误！');
		}

		$rs = Db::name('User')->where('id', userid())->update(array('alipay' => $alipay));
		if ($rs) {
			$this->success('支付宝认证成功！');
		} else {
			$this->error('支付宝认证失败！');
		}
	}

	public function tpwdset()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		$user = Db::name('User')->where('id', userid())->find();
		$this->assign('user', $user);
		return $this->fetch();
	}

	public function tpwdsetting()
	{
		if (userid()) {
			$tpwdsetting = Db::name('User')->where('id', userid())->value('tpwdsetting');
			exit($tpwdsetting);
		}
	}

	public function uptpwdsetting($paypassword, $tpwdsetting)
	{
		// 过滤非法字符----------------S
		if (checkstr($paypassword) || checkstr($tpwdsetting)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E

		if (!userid()) {
			$this->error(lang('请先登录！'));
		}

		if (!check($paypassword, 'password')) {
			$this->error(lang('密码格式为6~16位，不含特殊符号！'));
		}

		if (($tpwdsetting != 1) && ($tpwdsetting != 2) && ($tpwdsetting != 3)) {
			$this->error(lang('选项错误！') . $tpwdsetting);
		}

		$user_paypassword = Db::name('User')->where('id', userid())->value('paypassword');
		if (md5($paypassword) != $user_paypassword) {
			$this->error(lang('交易密码错误！'));
		}

		$rs = Db::name('User')->where('id', userid())->update(array('tpwdsetting' => $tpwdsetting));
		if ($rs) {
			$this->success(lang('成功！'));
		} else {
			$this->error(lang('失败！'));
		}
	}

	public function bank()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		$UserBankType = Db::name('UserBankType')->where('status', 1)->order('id desc')->select();
		$this->assign('UserBankType', $UserBankType);
		$truename = Db::name('User')->where('id', userid())->value('truename');
		$this->assign('truename', $truename);
		$UserBank = Db::name('UserBank')->where('userid', userid())->where('status', 1)->order('id desc')->select();
		$this->assign('UserBank', $UserBank);
		return $this->fetch();
	}

	public function upbank($name, $bank, $bankprov, $bankcity, $bankaddr, $bankcard, $paypassword)
	{
		// 过滤非法字符----------------S
		if (checkstr($name) || checkstr($bank) || checkstr($bankprov) || checkstr($bankcity) || checkstr($bankaddr) || checkstr($bankcard) || checkstr($paypassword)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E

		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		if (!check($name, 'a')) {
			$this->error(lang('备注名称格式错误！'));
		}
		if (!check($bank, 'a')) {
			$this->error(lang('开户银行格式错误！'));
		}
		if (!check($bankprov, 'c')) {
			$this->error(lang('开户省市格式错误！'));
		}
		if (!check($bankcity, 'c')) {
			$this->error(lang('开户省市格式错误！'));
		}
		if (!check($bankaddr, 'a')) {
			$this->error(lang('开户行地址格式错误！'));
		}
		if (!check($bankcard, 'd')) {
			$this->error(lang('请填写正确的银行卡号！'));
		}
		if (!preg_match('/^\d{13,}$/',$bankcard)) {
			$this->error(lang('请填写正确的银行卡号！'));
		}
		if (!Db::name('UserBankType')->where('title', $bank)->find()) {
			$this->error(lang('开户银行错误！'));
		}
		
		if (!check($paypassword, 'password')) {
			$this->error(lang('密码格式为6~16位，不含特殊符号！'));
		}		
		$user_paypassword = Db::name('User')->where('id', userid())->value('paypassword');
		if (md5($paypassword) != $user_paypassword) {
			$this->error(lang('交易密码错误！'));
		}

		$userBank = Db::name('UserBank')->where('userid', userid())->select();
		foreach ($userBank as $k => $v) {
			if ($v['name'] == $name) {
				$this->error(lang('请不要使用相同的备注名称！'));
			}
			if ($v['bankcard'] == $bankcard) {
				$this->error(lang('银行卡号已存在！'));
			}
		}

		if (1 <= count($userBank)) {
			$this->error(lang('每个用户最多只能添加1个地址！'));
		}

		if (Db::name('UserBank')->insert([
		    'userid' => userid(),
            'name' => $name,
            'bank' => $bank,
            'bankprov' => $bankprov,
            'bankcity' => $bankcity,
            'bankaddr' => $bankaddr,
            'bankcard' => $bankcard,
            'addtime' => time(),
            'status' => 1])) {
			$this->success(lang('银行添加成功！'));
		} else {
			$this->error(lang('银行添加失败！'));
		}
	}

	public function delbank($id, $paypassword)
	{
		// 过滤非法字符----------------S
		if (checkstr($id) || checkstr($paypassword)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E

		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		if (!check($paypassword, 'password')) {
			$this->error(lang('密码格式为6~16位，不含特殊符号！'));
		}

		if (!check($id, 'd')) {
			$this->error(lang('参数错误！'));
		}

		$user_paypassword = Db::name('User')->where('id', userid())->value('paypassword');

		if (md5($paypassword) != $user_paypassword) {
			$this->error(lang('交易密码错误！'));
		}

		if (!Db::name('UserBank')->where('userid', userid())->where('id', $id)->find()) {
			$this->error(lang('非法访问！'));
		}
		else if (Db::name('UserBank')->where('userid', userid())->where('id', $id)->delete()) {
			$this->success(lang('删除成功！'));
		}
		else {
			$this->error(lang('删除失败！'));
		}
	}

	public function qianbao($coin = NULL)
	{
		// 过滤非法字符----------------S
		if (checkstr($coin)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E

		if (!userid()) {
			$this->redirect(url('Login/index'));
		}
		
		//获取用户信息
		$user = Db::name('User')->where('id', userid())->find();
		$this->assign('user', $user);

		$Coin = Db::name('Coin')->where('status', 1)->where('type', 'neq', 'ptb')->where('name', 'neq', config('app.anchor_cny'))->select();

		if (!$coin) {
			$coin = $Coin[0]['name'];
		}

		$this->assign('xnb', $coin);
		
		foreach ($Coin as $k => $v) {
			$coin_list[$v['name']] = $v;
		}

		$this->assign('coin_list', $coin_list);
		$userQianbaoList = Db::name('UserQianbao')->where('userid', userid())->where('status', 1)->where('coinname', $coin)->order('id desc')->select();
		$this->assign('userQianbaoList', $userQianbaoList);
		return $this->fetch();
	}

	public function upqianbao($coin, $name=NULL, $addr, $paypassword)
	{
		// 过滤非法字符----------------S
		if (checkstr($coin) || checkstr($name) ||checkstr($addr) || checkstr($paypassword)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E

		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

/*		if (!check($name, 'a')) {
			$this->error(lang('备注名称格式错误！'));
		}*/
		if (!check($addr, 'dw')) {
			$this->error(lang('钱包地址格式错误！'));
		}
		if (!check($paypassword, 'password')) {
			$this->error(lang('密码格式为6~16位，不含特殊符号！'));
		}
		$user_paypassword = Db::name('User')->where('id', userid())->value('paypassword');
		if (md5($paypassword) != $user_paypassword) {
			$this->error(lang('交易密码错误！'));
		}
		if (!Db::name('Coin')->where('name', $coin)->find()) {
			$this->error(lang('品种错误！'));
		}

		$userQianbao = Db::name('UserQianbao')->where('userid', userid())->where('coinname', $coin)->select();
		foreach ($userQianbao as $k => $v) {
/*			if ($v['name'] == $name) {
				$this->error(lang('请不要使用相同的钱包备注！'));
			}*/
			if ($v['addr'] == $addr) {
				$this->error(lang('钱包地址已存在！'));
			}
		}

		if (3 <= count($userQianbao)) {
			$this->error(lang('每个人最多只能添加3个地址！'));
		}

		if (Db::name('UserQianbao')->insert([
		    'userid' => userid(),
            'name' => $name,
            'addr' => $addr,
            'coinname' => $coin,
            'addtime' => time(),
            'status' => 1])) {
			$this->success(lang('添加成功！'));
		} else {
			$this->error(lang('添加失败！'));
		}
	}

	public function delqianbao($id, $paypassword)
	{
		// 过滤非法字符----------------S
		if (checkstr($id) || checkstr($paypassword)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E

		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		if (!check($paypassword, 'password')) {
			$this->error(lang('密码格式为6~16位，不含特殊符号！'));
		}

		if (!check($id, 'd')) {
			$this->error(lang('参数错误！'));
		}

		$user_paypassword = Db::name('User')->where('id', userid())->value('paypassword');
		if (md5($paypassword) != $user_paypassword) {
			$this->error(lang('交易密码错误！'));
		}

		if (!Db::name('UserQianbao')->where('userid', userid())->where('id', $id)->find()) {
			$this->error(lang('非法访问！'));
		} else if (Db::name('UserQianbao')->where('userid', userid())->where('id', $id)->delete()) {
			$this->success(lang('删除成功！'));
		} else {
			$this->error(lang('删除失败！'));
		}
	}

	public function goods()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		$userGoodsList = Db::name('UserGoods')->where('userid', userid())->where('status', 1)->order('id desc')->select();

		foreach ($userGoodsList as $k => $v) {
			$userGoodsList[$k]['mobile'] = substr_replace($v['mobile'], '****', 3, 4);
			$userGoodsList[$k]['idcard'] = substr_replace($v['idcard'], '********', 6, 8);
		}

		$this->assign('userGoodsList', $userGoodsList);
		return $this->fetch();
	}

	public function upgoods($name, $truename, $idcard, $mobile, $addr, $paypassword)
	{
		// 过滤非法字符----------------S
		if (checkstr($name) || checkstr($truename) || checkstr($idcard) || checkstr($mobile) || checkstr($paypassword)) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E

		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		if (!check($name, 'a')) {
			$this->error('备注名称格式错误！');
		}
		if (!check($truename, 'truename')) {
			$this->error('联系姓名格式错误！');
		}
		if (!check($idcard, 'idcard')) {
			$this->error('身份证号格式错误！');
		}
		if (!check($mobile, 'mobile')) {
			$this->error('联系电话格式错误！');
		}
		if (!check($addr, 'a')) {
			$this->error('联系地址格式错误！');
		}

		$user_paypassword = Db::name('User')->where('id', userid())->value('paypassword');
		if (md5($paypassword) != $user_paypassword) {
			$this->error('交易密码错误！');
		}

		$userGoods = Db::name('UserGoods')->where('userid', userid())->select();
		foreach ($userGoods as $k => $v) {
			if ($v['name'] == $name) {
				$this->error('请不要使用相同的地址标识！');
			}
		}

		if (10 <= count($userGoods)) {
			$this->error('每个人最多只能添加10个地址！');
		}

		if (Db::name('UserGoods')->insert([
		    'userid' => userid(),
            'name' => $name,
            'addr' => $addr,
            'idcard' => $idcard,
            'truename' => $truename,
            'mobile' => $mobile,
            'addtime' => time(), 'status' => 1])) {
			$this->success('添加成功！');
		} else {
			$this->error('添加失败！');
		}
	}

	public function delgoods($id, $paypassword)
	{
		// 过滤非法字符----------------S
		if (checkstr($id) || checkstr($paypassword)) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E

		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		if (!check($paypassword, 'password')) {
			$this->error('密码格式为6~16位，不含特殊符号！');
		}

		if (!check($id, 'd')) {
			$this->error('参数错误！');
		}

		$user_paypassword = Db::name('User')->where('id', userid())->value('paypassword');
		if (md5($paypassword) != $user_paypassword) {
			$this->error('交易密码错误！');
		}

		if (!Db::name('UserGoods')->where('userid', userid())->where('id', $id)->find()) {
			$this->error('非法访问！');
		} else if (Db::name('UserGoods')->where('userid', userid())->where('id', $id)->delete()) {
			$this->success('删除成功！');
		} else {
			$this->error('删除失败！');
		}
	}

	public function log()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}
		
		//获取用户信息
		$user = Db::name('User')->where('id', userid())->find();
		$this->assign('user', $user);

		$where['status'] = array('egt', 0);
		$where['userid'] = userid();
		$Model = Db::name('UserLog');
		$show = $Model->where($where)->paginate(10);

		$list = $Model->where($where)->order('id desc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}
}
?>