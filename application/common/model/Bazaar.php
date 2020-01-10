<?php
namespace app\common\model;

class Bazaar extends Model
{
	protected $keyS = 'Bazaar';

	public function get_market_mr()
	{
		$get_market_mr = Db::name('BazaarConfig')->where('default', 1)->value('market');

		if (!$get_market_mr) {
			$get_market_mr = Db::name('BazaarConfig')->where('status', 1)->order('id asc')->value('market');
		}

		return $get_market_mr;
	}

	public function get_market_list()
	{
		$get_market_list = Db::name('BazaarConfig')->where('status', 1)->order('sort asc')->select();

		foreach ($get_market_list as $k => $v) {
			$get_market_list_data[$v['market']] = Model('Market')->get_title($v['market']);
		}

		return $get_market_list_data;
	}
}

?>