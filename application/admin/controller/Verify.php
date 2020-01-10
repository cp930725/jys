<?php
namespace app\admin\controller;

use think\Controller;

class Verify extends Controller
{
	public function code()
	{
		$config['useNoise'] = true;
		$config['length'] = 4;
		$config['codeSet'] = '1234567890';
		$verify = new Verify($config);
		$verify->entry('.cn');
	}

	public function mobile()
	{
		if ($this->request->ispost()) {
			if (check($_POST['mobile'], 'mobile')) {
				$mobile = $_POST['mobile'];
			}
			else {
				$this->error('手机号码格式错误!');
			}

			if (empty($_POST['type'])) {
				$this->error('短信模版名称错误!');
			}

			$Configmobile = Model('ConfigMobile')->where('id', $_POST['type'])->find();

			if ($Configmobile) {
				$code = rand(111111, 999999);
				session('mobilecode', $code);
				$content = str_replace('[url]', $code, $Configmobile['content']);
			}
			else {
				$this->error('短信模版错误!');
			}

			config('MOBILE_URL', $_POST['mobile_url']);
			config('MOBILE_USER', $_POST['mobile_user']);
			config('MOBILE_PASS', $_POST['mobile_pass']);
		}

		if (0 < smssend($mobile, $content)) {
			$this->success('短信发送成功!');
		}
		else {
			$this->error('短信发送失败!');
		}
	}

	public function email()
	{
		if ($this->request->ispost()) {
			if (check($_POST['email'], 'email')) {
				$email = $_POST['email'];
			}
			else {
				$this->error('邮件格式错误!');
			}

			if (empty($_POST['type'])) {
				$this->error('邮件模版名称错误!');
			}

			$Configemail = Model('ConfigEmail')->where('id', $_POST['type'])->find();

			if ($Configemail) {
				$code = rand(111111, 999999);
				session('emailcode', $code);
				$content = str_replace('[url]', $code, $Configemail['content']);
				$title = $Configemail['title'];
			}
			else {
				$this->error('邮件模版错误!');
			}

			config('SMTP_HOST', $_POST['smtp_host']);
			config('SMTP_PORT', $_POST['smtp_port']);
			config('SMTP_USER', $_POST['smtp_user']);
			config('SMTP_PASS', $_POST['smtp_pass']);
			config('SMTP_NAME', $_POST['smtp_name']);
			config('SMTP_EMAIL', $_POST['smtp_email']);
		}

		if (send_email($email, $title, $content)) {
			$this->success('邮件发送成功!');
		}
		else {
			$this->error('邮件发送失败!');
		}
	}
}

?>