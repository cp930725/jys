<?php
/* 短信发送内容配置 */
namespace app\home\controller;

class Verify extends \think\Controller
{
	protected function _initialize()
	{
		$allow_action = array("code","code2","real","real1","sendSms","mytx","paypass","pass","mibao","mobilebd","findpwd","findpaypwd","myzc","tests","findpwd_test");
		if(!in_array($this->request->action(),$allow_action)){
			$this->error("非法操作！");
		}
	}
	
/*	public function tests()
	{
		$config = Db::name('Config')->where('id', 1)->find();
		echo '短信签名 '.$config['smsqm']."<br>";
		echo '网站名称 '.$config['web_name'];
	}*/
	
	public function code()
	{
		$config['useNoise'] = true; // 关闭验证码杂点
		$config['length'] = 4; // 验证码位数
		$config['codeSet'] = '123456789';
		ob_clean();
		$verify = new \think\Verify($config);
		$verify->entry(1);
	}

	public function sendSms($phone)
	{
		if (empty($phone)) {
			return $this->error('手机号不能为空');
		}
		$preg = '/^\+[\d]+?/';
		if (preg_match($preg, $phone)) {
			$phone = str_replace('+', '00', $phone);
			$appid = '8aaf07086e0115bb016e5d8b79e832f7';
			$tempId = 484941;
		} else {
			$appid = '8a216da86b652116016b685f6095012a';
			$tempId = 484940;
		}
		$tel_code = rand(100000,999999);
		session('tel_code', $tel_code);
		$data = [$tel_code, '10分钟'];
		$res = sendTelCode($phone, $data, $tempId, $appid);
		if (empty($res)) {
			return $this->success('发送成功');
		}
	}

	public function mytx()
	{
		$config = Db::name('Config')->where('id', 1)->find();
		if (!userid()) {
			$this->error(lang('请先登录'));
		}
		
		$mobile = Db::name('User')->where('id', userid())->value('mobile');
		if (!$mobile) {
			$this->error(lang('你的手机没有认证'));
		}

		$code = rand(111111, 999999);
		session('mytx_verify', $code);
		session('chkmobile',$mobile);
		$intnum = Db::name('User')->where('id', userid())->value('qz');
		// $content="您正在进行申请提现操作，您的验证码是:". $code;
		switch ($intnum) {
			case '1': //英语
				$content = "You are requesting a cash withdrawal. Your verification code is:". $code;
				break;
			case '33': //法语
				$content = "Vous demandez un retrait en espèces Votre code de vérification est:". $code;
				break;
				case '49': //德语
				$content = "Sie fordern eine Barabhebung an. Ihr Bestätigungscode lautet:". $code;
				break;
				case '81': //日语
				$content = "現金引き出しをリクエストしています。確認コード:". $code;
				break;
				case '7': //俄语
				$content = "Вы запрашиваете снятие наличных. Ваш код подтверждения:". $code;
				break;
				case '82': //韩语
				$content = "현금 인출 요청. 확인 코드:". $code;
				break;
				case '86': //中文
				$content = "您正在进行申请提现操作，您的验证码是:". $code;
				break;
			default:
				$content = "You are requesting a cash withdrawal. Your verification code is:". $code;
				break;
		}
		$sign = "【".$config['smsqm']."】";
		$mobile = $intnum.$mobile;
		$fh = sendsmsint($mobile, $content,$sign);
		if ($fh) {
			$this->success(lang('短信验证码已发送到你的手机，请查收'));
		} else {
			$this->error(lang('短信验证码发送失败，请重新点击发送'));
		}
		// $content = "【".$config['smsqm']."】您正在进行申请提现操作，您的验证码是:" . $code;

/*		if (smssend($mobile, $content)) {
			$this->success(lang('短信验证码已发送到你的手机，请查收'));
		} else {
			$this->error(lang('短信验证码发送失败，请重新点击发送'));
		}*/
	}

	public function paypass()
	{
		$config = Db::name('Config')->where('id', 1)->find();
		if (!userid()) {
			$this->error(lang('请先登录'));
		}

		$mobile = Db::name('User')->where('id', userid())->value('mobile');
		if (!$mobile) {
			$this->error(lang('你的手机没有认证'));
		}

		$code = rand(111111, 999999);
		session('chkmobile',$mobile);
		session('paypass_verify', $code);
		$intnum = Db::name('User')->where('id', userid())->value('qz');
		// $content="您正在进行修改交易密码操作，您的验证码是:". $code;
		switch ($intnum) {
			case '1': //英语
				$content = "You are in the process of modifying the transaction password. Your verification code is:". $code;
				break;
			case '33': //法语
				$content = "Vous êtes en train de modifier le mot de passe de la transaction Votre code de vérification est:". $code;
				break;
				case '49': //德语
				$content = "Sie sind dabei, das Transaktionskennwort zu ändern. Ihr Bestätigungscode lautet:". $code;
				break;
				case '81': //日语
				$content = "あなたは、トランザクションのパスワードを変更するための継続的な操作を持って、確認コードは次のようになります:". $code;
				break;
				case '7': //俄语
				$content = "Вы в процессе изменения пароля транзакции. Ваш код подтверждения:". $code;
				break;
				case '82': //韩语
				$content = "거래 비밀번호를 수정하는 중입니다. 인증 코드는 다음과 같습니다:". $code;
				break;
				case '86': //中文
				$content = "您正在进行修改交易密码操作，您的验证码是:". $code;
				break;
			default:
				$content = "You are in the process of modifying the transaction password. Your verification code is:". $code;
				break;
		}
		$sign = "【".$config['smsqm']."】";
		$mobile = $intnum.$mobile;
		$fh = sendsmsint($mobile, $content,$sign);
		if ($fh) {
			$this->success(lang('短信验证码已发送到你的手机，请查收'));
		} else {
			$this->error(lang('短信验证码发送失败，请重新点击发送'));
		}
		// $content = "【".$config['smsqm']."】您正在进行修改交易密码操作，您的验证码是:" . $code;

/*		if (smssend($mobile, $content)) {
			$this->success(lang('短信验证码已发送到你的手机，请查收'));
		} else {
			$this->error(lang('短信验证码发送失败，请重新点击发送'));
		}*/
	}
	
	// 会员中心 - 修改登录密码：短信
	public function pass()
	{
		$config = Db::name('Config')->where('id', 1)->find();
		if (!userid()) {
			$this->error(lang('请先登录'));
		}

		$mobile = Db::name('User')->where('id', userid())->value('mobile');
		if (!$mobile) {
			$this->error(lang('你的手机没有认证'));
		}

		$code = rand(111111, 999999);
		session('chkmobile',$mobile);
		session('pass_verify', md5($code.'mima'));
		$intnum = Db::name('User')->where('id', userid())->value('qz');
		// $content="您正在进行修改登录密码操作，您的验证码是:". $code;
		switch ($intnum) {
			case '1': //英语
				$content = "You are in the process of modifying the Login password. Your verification code is:". $code;
				break;
			case '33': //法语
				$content = "Vous êtes en train de modifier le mot de passe de la Login Votre code de vérification est:". $code;
				break;
				case '49': //德语
				$content = "Sie sind dabei, das Loginskennwort zu ändern. Ihr Bestätigungscode lautet:". $code;
				break;
				case '81': //日语
				$content = "あなたは、トランザクションのパスワードを変更するための継続的な操作を持って、確認コードは次のようになります:". $code;
				break;
				case '7': //俄语
				$content = "Вы в процессе изменения пароля транзакции. Ваш код подтверждения:". $code;
				break;
				case '82': //韩语
				$content = "거래 비밀번호를 수정하는 중입니다. 인증 코드는 다음과 같습니다:". $code;
				break;
				case '86': //中文
				$content = "您正在进行修改登录密码操作，您的验证码是:". $code;
				break;
			default:
				$content = "You are in the process of modifying the Login password. Your verification code is:". $code;
				break;
		}
		$sign = "【".$config['smsqm']."】";
		$mobile = $intnum.$mobile;
		$fh = sendsmsint($mobile, $content,$sign);
		if ($fh) {
			$this->success(lang('短信验证码已发送到你的手机，请查收'));
		} else {
			$this->error(lang('短信验证码发送失败，请重新点击发送'));
		}
		// $content = "【".$config['smsqm']."】您正在进行修改登录密码操作，您的验证码是:" . $code;

/*		if (smssend($mobile, $content)) {
			$this->success(lang('短信验证码已发送到你的手机，请查收'));
		} else {
			$this->error(lang('短信验证码发送失败，请重新点击发送'));
		}*/
	}

	public function mibao() {
		$config = Db::name('Config')->where('id', 1)->find();
		if (!userid()) {
			$this->error(lang('请先登录'));
		}

		$mobile = Db::name('User')->where('id', userid())->value('mobile');
		if (!$mobile) {
			$this->error(lang('你的手机没有认证'));
		}

		$code = rand(111111, 999999);
		session('chkmobile',$mobile);
		session('mibao_verify', $code);
		$intnum = Db::name('User')->where('id', userid())->value('qz');
		// $content="您正在进行修改密保问题操作，您的验证是:". $code;
		switch ($intnum) {
			case '1': //英语
				$content = "You are modifying the security question, your verification is:". $code;
				break;
			case '33': //法语
				$content = "Vous êtes de modifier la question de sécurité de fonctionnement, on vérifie:". $code;
				break;
				case '49': //德语
				$content = "Sie ändern ihre überprüfung der sicherheits - Frage IST:". $code;
				break;
				case '81': //日语
				$content = "君の中に修正を行う操作秘密保障問題、あなたの検証:". $code;
				break;
				case '7': //俄语
				$content = "Вы в настоящее время  пересмотреть  вопрос безопасности  операции,  вашей проверки  является:". $code;
				break;
				case '82': //韩语
				$content = "밀보 문제를 수정하고 있습니다. 검증이 있습니다:". $code;
				break;
				case '86': //中文
				$content = "您正在进行修改密保问题操作，您的验证是:". $code;
				break;
			default:
				$content = "You are modifying the security question, your verification is:". $code;
				break;
		}
		$sign = "【".$config['smsqm']."】";
		$mobile = $intnum.$mobile;
		$fh = sendsmsint($mobile, $content,$sign);
		if ($fh) {
			$this->success(lang('短信验证码已发送到你的手机，请查收'));
		} else {
			$this->error(lang('短信验证码发送失败，请重新点击发送'));
		}
		// $content = "【".$config['smsqm']."】您正在进行修改密保问题操作，您的验证是:" . $code;

		// if (smssend($mobile, $content)) {
		// 	$this->success(lang('短信验证码已发送到你的手机，请查收'));
		// }
		// else {
		// 	$this->error(lang('短信验证码发送失败，请重新点击发送'));
		// }
	}

	public function mobilebd($mobile, $verify) {
		$config = Db::name('Config')->where('id', 1)->find();
		
		// 过滤非法字符----------------S
		if (checkstr($mobile) || checkstr($verify)) {
			$this->error(lang('您输入的信息有误！'));
		}
		// 过滤非法字符----------------E

		if (!userid()) {
			$this->error(lang('请先登录'));
		}
		
		if (!check_verify(strtoupper($verify))) {
			$this->error(lang('图形验证码错误!'));
		}
		if (!check($mobile, 'mobile')) {
			$this->error(lang('手机号码格式错误！'));
		}
		if (Db::name('User')->where('mobile', $mobile)->find()) {
			$this->error(lang('手机号码已存在！'));
		}

		$code = rand(111111, 999999);
		session('chkmobile',$mobile);
		session('mobilebd_verify', $code);
		$content = "【".$config['smsqm']."】您正在进行手机绑定操作，您的验证码是:" . $code;
		if (smssend($mobile, $content)) {
			$this->success(lang('短信验证码已发送到你的手机，请查收'));
		} else {
			$this->error(lang('短信验证码发送失败，请重新点击发送'));
		}
	}
	
	// 找回密码：短信
	public function findpwd() {
		$config = Db::name('Config')->where('id', 1)->find();
		if ($this->request->ispost()) {
			$input = input('post.');
			foreach ($input as $k => $v) {
				// 过滤非法字符----------------S
				if (checkstr($v)) {
					$this->error(lang('您输入的信息有误！'));
				}
				// 过滤非法字符----------------E
			}

			if (!check_verify(strtoupper($input['verify']),"1")) {
				$this->error(lang('图形验证码错误!'));
			}
/*			if (!check($input['mobile'], 'mobile')) {
				$this->error(lang('手机号码格式错误！'));
			}*/

			$user = Db::name('User')->where('username', $input['mobile'])->find();
			if (!$user) {
				$this->error(lang('用户不存在！'));
			}
			if ($user['mobile'] != $input['mobile']) {
				$this->error(lang('手机号码错误！'));
			}
			$mobile = $user['mobile'];
			$code = rand(111111, 999999);
			session('findpwd_verify', md5($code.'mima'));
			session('chkmobile',$input['mobile']);
			$intnum = $user['qz'];
			// $content="您正在进行找回登录密码操作，您的验证码是:". $code;
			switch ($intnum) {
				case '1'://英语
					$content = "You are trying to retrieve the login password operation, and your verification code is:". $code;
					break;
				case '33'://法语
					$content = "Vous êtes de récupérer le mot de passe de votre opération, le Code de vérification est:". $code;
					break;
					case '49'://德语
					$content = "Ihr login - passwort laufenden Betrieb wieder ihre kartenprüfnummer IST:". $code;
					break;
					case '81'://日语
					$content = "ログインパスワードを取り戻す操作を行っております:". $code;
					break;
					case '7'://俄语
					$content = "Вы  в настоящее время  найти  пароль  операции,  ваш  код проверки  является:". $code;
					break;
					case '82'://韩语
					$content = "로그인 비밀번호 찾기:". $code;
					break;
					case '86'://中文
					$content = "您正在进行找回登录密码操作，您的验证码是:". $code;
					break;
				default:
					$content = "You are trying to retrieve the login password operation, and your verification code is:". $code;
					break;
			}
			$sign = "【".$config['smsqm']."】";
			$mobile = $intnum.$mobile;
			$fh = sendsmsint($mobile, $content,$sign);
			if ($fh) {
				$this->success(lang('短信验证码已发送到你的手机，请查收'));
			} else {
				$this->error(lang('短信验证码发送失败，请重新点击发送'));
			}
			// $content = "【".$config['smsqm']."】您正在进行找回登录密码操作，您的验证码是:" . $code;
			// if (smssend($user['mobile'], $content)) {
			// 	$this->success(lang('短信验证码已发送到你的手机，请查收'));
			// }
			// else {
			// 	$this->error(lang('短信验证码发送失败，请重新点击发送'));
			// }
		}
	}
	
	// 找回密码：短信（ Test调试 ）
	public function findpwd_test($mm=NULL)
	{
/*		// 过滤非法字符----------------S
		if (checkstr($mm)) {
			echo '您输入的信息有误！';
		}
		// 过滤非法字符----------------E
		
		$code = rand(111111, 999999);
		session('findpwd_verify', md5($code.'mima'));
		session('chkmobile',$mm);
		
		echo '验证码: '.$code.'<br>';
		echo '手机号: '.$mm;*/
	}
	
	// 找回交易密码：短信
	public function findpaypwd()
	{
		$config = Db::name('Config')->where('id', 1)->find();
		$input = input('post.');

		foreach ($input as $k => $v) {
			// 过滤非法字符----------------S
			if (checkstr($v)) {
				$this->error(lang('您输入的信息有误！'));
			}
			// 过滤非法字符----------------E
		}

		if (!check_verify(strtoupper($input['verify']),"1")) {
			$this->error(lang('图形验证码错误!'));
		}
/*		if (!check($input['username'], 'mobile')) {
			$this->error(lang('用户名格式错误！'));
		}
		if (!check($input['mobile'], 'mobile')) {
			$this->error(lang('手机号码格式错误！'));
		}*/

		$user = Db::name('User')->where('id', userid())->find();
		if (!$user) {
			$this->error(lang('用户名不存在！'));
		}
		if ($user['mobile'] != $input['mobile']) {
			$this->error(lang('手机号码错误！'));
		}
		
		$mobile = $user['mobile'];
		$code = rand(111111, 999999);
		session('chkmobile',$input['mobile']);
		session('findpaypwd_verify', $code);
		$intnum = Db::name('User')->where('id', userid())->value('qz');
		// $content="您正在进行找回交易密码操作，您的验证码是:". $code;
		switch ($intnum) {
			case '1'://英语
				$content = "You are in the process of modifying the transaction password. Your verification code is:". $code;
				break;
			case '33'://法语
				$content = "Vous êtes en train de modifier le mot de passe de la transaction Votre code de vérification est:". $code;
				break;
				case '49'://德语
				$content = "Sie sind dabei, das Transaktionskennwort zu ändern. Ihr Bestätigungscode lautet:". $code;
				break;
				case '81'://日语
				$content = "あなたは、トランザクションのパスワードを変更するための継続的な操作を持って、確認コードは次のようになります:". $code;
				break;
				case '7'://俄语
				$content = "Вы в процессе изменения пароля транзакции. Ваш код подтверждения:". $code;
				break;
				case '82'://韩语
				$content = "거래 비밀번호를 수정하는 중입니다. 인증 코드는 다음과 같습니다:". $code;
				break;
				case '86'://中文
				$content = "您正在进行找回交易密码操作，您的验证码是:". $code;
				break;
			default:
				$content = "You are in the process of modifying the transaction password. Your verification code is:". $code;
				break;
		}
		$sign = "【".$config['smsqm']."】";
		$mobile = $intnum.$mobile;
		$fh = sendsmsint($mobile, $content,$sign);
		if ($fh) {
			$this->success(lang('短信验证码已发送到你的手机，请查收'));
		} else {
			$this->error(lang('短信验证码发送失败，请重新点击发送'));
		}
		// $content ="【".$config['smsqm']."】您正在进行找回交易密码操作，您的验证码是:" . $code;

/*		if (smssend($input['mobile'], $content)) {
			$this->success(lang('短信验证码已发送到你的手机，请查收'));
		} else {
			$this->error(lang('短信验证码发送失败，请重新点击发送'));
		}*/
	}

	public function myzc()
	{
		$config = Db::name('Config')->where('id', 1)->find();
		if (!userid()) {
			$this->error(lang('您没有登录请先登录!'));
		}

		$mobile = Db::name('User')->where('id', userid())->value('mobile');
		if (!$mobile) {
			$this->error(lang('你的手机没有认证'));
		}

		$code = rand(111111, 999999);
		session('myzc_verify', $code);
		session('chkmobile',$mobile);
		$intnum = Db::name('User')->where('id', userid())->value('qz');
		// $content="您正在进行申请转出操作，您的验证码是:". $code;
		switch ($intnum) {
			case '1'://英语
				$content = "You are in the process of applying for a transfer. Your verification code is:". $code;
				break;
			case '33'://法语
				$content = "Vous êtes en train de faire une demande de transfert Votre code de vérification est:". $code;
				break;
				case '49'://德语
				$content = "Sie sind dabei, sich um eine Überweisung zu bewerben. Ihr Bestätigungscode lautet:". $code;
				break;
				case '81'://日语
				$content = "譲渡申請中です。確認コード:". $code;
				break;
				case '7'://俄语
				$content = "Вы в процессе подачи заявки на перевод. Ваш код подтверждения:". $code;
				break;
				case '82'://韩语
				$content = "이전을 신청하는 중입니다. 인증 코드는 다음과 같습니다:". $code;
				break;
				case '86'://中文
				$content = "您正在进行申请转出操作，您的验证码是:". $code;
				break;
			default:
				$content = "You are in the process of applying for a transfer. Your verification code is:". $code;
				break;
		}
		$sign = "【".$config['smsqm']."】";
		$mobile = $intnum.$mobile;
		$fh = sendsmsint($mobile, $content,$sign);
		if ($fh) {
			$this->success(lang('短信验证码已发送到你的手机，请查收'));
		} else {
			$this->error(lang('短信验证码发送失败，请重新点击发送'));
		}
		// $content = "【".$config['smsqm']."】您正在进行申请转出操作，您的验证码是:" . $code;

/*		if (smssend($mobile, $content)) {
			$this->success(lang('短信验证码已发送到你的手机，请查收'));
		} else {
			$this->error(lang('短信验证码发送失败，请重新点击发送'));
		}*/
	}
}
?>