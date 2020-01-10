<?php
namespace app\common\model;

use think\Model;

class User extends Model
{
	public function check_install()
	{
		$this->check_authorization();
		$this->check_database();
		$this->check_update();
	}

	public function check_uninstall()
	{
	}

	public function check_server()
	{
	}

	public function check_authorization()
	{
	}

	public function check_database()
	{
	}

	public function check_update()
	{
		$check_update_user = (config('app.develop') ? null : cache('check_update_user'));

		if (!$check_update_user) {
			$User_DbFields = Db::name('User')->getDbFields();

			if (!in_array('alipay', $User_DbFields)) {
				Db::execute('ALTER TABLE `tw_user` ADD COLUMN `alipay` VARCHAR(200) NULL  COMMENT \'支付宝\' AFTER `status`;');
			}

			if (!in_array('email', $User_DbFields)) {
				Db::execute('ALTER TABLE `tw_user` ADD COLUMN `email` VARCHAR(200) NULL  COMMENT \'邮箱\' AFTER `status`;');
			}

			cache('check_update_user', 1);
		}
	}

	public function get_userid($username = NULL)
	{
		if (empty($username)) {
			return null;
		}

		$get_userid_user = (config('app.develop') ? null : cache('get_userid_user' . $username));

		if (!$get_userid_user) {
			$get_userid_user = Db::name('User')->where('username', $username)->value('id');
			cache('get_userid_user' . $username, $get_userid_user);
		}

		return $get_userid_user;
	}

	public function get_username($id = NULL)
	{
		if (empty($id)) {
			return null;
		}

		$user = (config('app.develop') ? null : cache('get_username' . $id));

		if (!$user) {
			$user = Db::name('User')->where('id', $id)->value('username');
			cache('get_username' . $id, $user);
		}

		return $user;
	}
}

?>