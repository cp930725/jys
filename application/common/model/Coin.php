<?php
namespace app\common\model;

class Coin extends Model
{
	public function check_install() {}

	public function check_uninstall() {}

	public function check_server() {}

	public function check_authorization() {}

	public function check_database() {}

	public function check_update() {}

	public function check_file() {}

	public function get_all_name_list()
	{
		$list = Db::name('Coin')->where(array())->order('sort asc')->select();

		if (is_array($list)) {
			foreach ($list as $k => $v) {
				$get_all_name_list[$v['name']] = $v['title'];
			}
		} else {
			$get_all_name_list = null;
		}

		return $get_all_name_list;
	}

	public function get_all_xnb_list()
	{
		$list = Db::name('Coin')->where()->order('sort asc')->select();
		if (is_array($list)) {
			foreach ($list as $k => $v) {
				if ($v['type'] != 'rmb') {
					$get_all_xnb_list[$v['name']] = $v['title'];
				}
			}
		} else {
			$get_all_xnb_list = null;
		}

		return $get_all_xnb_list;
	}

	public function get_title($name = NULL)
	{
		if (empty($name)) {
			return null;
		}

		$get_title = Db::name('Coin')->where('name', $name)->value('title');
		return $get_title;
	}

	public function get_img($name = NULL)
	{
		if (empty($name)) {
			return null;
		}

		$get_img = Db::name('Coin')->where('name', $name)->value('img');
		return $get_img;
	}

	public function get_sum_coin($name = NULL, $userid = NULL)
	{
		if (empty($name)) {
			return null;
		}

		if ($userid) {
			$a = Db::name('UserCoin')->where('userid', $userid)->sum($name);
			$b = Db::name('UserCoin')->where('userid', $userid)->sum($name . 'd');
		} else {
			$a = Db::name('UserCoin')->sum($name);
			$b = Db::name('UserCoin')->sum($name . 'd');
		}

		$c = $a + $b;
		return $c;
	}
}
?>