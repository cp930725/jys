<?php
namespace app\common\model;

class Shop extends Model
{
	protected $keyS = 'Shop';

	public function check_coin()
	{
		$check_coin = (config('app.develop') ? null : cache('check_coin' . $this->keyS));

		if (!$check_coin) {
			$coin_list = config('coin');

			if (is_array($coin_list)) {
				foreach ($coin_list as $k => $v) {
					$coin_arr[$v['name']] = $v['title'];
				}

				unset($k);
				unset($v);
			}
			else {
				$coin_arr = null;
			}

			if ($coin_arr) {
				$Shop_Coin_DbFields = Db::name('ShopCoin')->getDbFields();

				foreach ($coin_arr as $k => $v) {
					if (!in_array($k, $Shop_Coin_DbFields)) {
						Db::execute('ALTER TABLE `tw_shop_coin` ADD COLUMN `' . $k . '` VARCHAR(50) NOT NULL  COMMENT \'' . $v . '\' AFTER `shopid`;');
					}
				}
			}

			cache('check_coin' . $this->keyS, 1);
		}
	}

	public function shop_type_list()
	{
		$shop_type_list = (config('app.develop') ? null : cache('shop_type_list' . $this->keyS));

		if (!$shop_type_list) {
			$list = Db::name('ShopType')->where('status', 1)->select();

			if ($list) {
				foreach ($list as $k => $v) {
					$shop_type_list[$v['name']] = $v['title'];
				}
			}
			else {
				$shop_type_list = null;
			}

			cache('shop_type_list' . $this->keyS, $shop_type_list);
		}

		return $shop_type_list;
	}

	public function getShopName($id = NULL)
	{
		if (empty($id)) {
			return null;
		}

		$shop_getShopName = (config('app.develop') ? null : cache('shop_getShopName' . $id . $this->keyS));

		if (!$shop_getShopName) {
			$shop_getShopName = Db::name('Shop')->where('id', $id)->value('name');
			cache('shop_getShopName' . $id . $this->keyS, $shop_getShopName);
		}

		return $shop_getShopName;
	}

	public function getShopId($name = NULL)
	{
		if (empty($name)) {
			return null;
		}

		$shop_getShopId = (config('app.develop') ? null : cache('shop_getShopId' . $this->keyS));

		if (!$shop_getShopId) {
			$shop_getShopId = Db::name('Shop')->where(array(
				'name' => array('like', '%' . $name . '%')
				))->value('id');
			cache('shop_getShopId' . $this->keyS, $shop_getShopId);
		}

		return $shop_getShopId;
	}

	public function tongbu()
	{
		$shop_tongbu = (config('app.develop') ? null : cache('shop_tongbu' . $this->keyS));

		if (!$shop_tongbu) {
			$shop_list = Db::name('Shop')->select();
			$shop_coin_list = Db::name('ShopCoin')->select();

			if (is_array($shop_coin_list)) {
				foreach ($shop_coin_list as $k => $v) {
					$shop_coin_arr[$v['shopid']] = $v['id'];
				}
			}

			if (is_array($shop_list)) {
				foreach ($shop_list as $k => $v) {
					$shop_list_arr[$v['id']] = $v;

					if (!$shop_coin_arr[$v['id']]) {
						Db::name('ShopCoin')->insert(['shopid' => $v['id']]);
					}
				}
			}

			if (is_array($shop_coin_list) && is_array($shop_list)) {
				foreach ($shop_coin_list as $k => $v) {
					$shop_coin_arr[$v['shopid']] = $v['id'];

					if (!$shop_list_arr[$v['shopid']]) {
						Db::name('ShopCoin')->where('id', $v['id'])->delete();
					}
				}
			}

			cache('shop_tongbu' . $this->keyS, 1);
		}
	}

	public function fangshi($shopid = NULL)
	{
		if (empty($shopid)) {
			return null;
		}

		$shop_fangshi = (config('app.develop') ? null : cache('shop_fangshi' . $this->keyS . $shopid));

		if (!$shop_fangshi) {
			$list = Db::name('ShopCoin')->where('shopid', $shopid)->find();

			foreach ($list as $k => $v) {
				if (($k != 'id') && ($k != 'shopid')) {
					if ($v) {
						if ($k == 'cny') {
							$shop_fangshi[$k] = 1;
						}
						else {
							$new_price = Model('Market')->get_new_price($k . '_cny');

							if ($new_price) {
								$shop_fangshi[$k] = $new_price;
							}
							else {
								$shop_fangshi[$k] = 1;
							}
						}
					}
				}
			}

			cache('shop_fangshi' . $this->keyS . $shopid, $shop_fangshi);
		}

		return $shop_fangshi;
	}

	public function get_goods($userid = NULL)
	{
		if (empty($userid)) {
			return null;
		}

		$shop_get_goods = (config('app.develop') ? null : cache('shop_get_goods' . $this->keyS . $userid));

		if (!$shop_get_goods) {
			$list = Db::name('UserGoods')->where('userid', $userid)->select();

			foreach ($list as $k => $v) {
				$shop_get_goods[$v['id']] = $v['name'];
			}

			cache('shop_get_goods' . $this->keyS . $userid, $shop_get_goods);
		}

		return $shop_get_goods;
	}

	public function setStatus($id = NULL, $type = NULL, $mobile = 'Shop')
	{
		if (empty($id)) {
			return null;
		}

		if (empty($type)) {
			return null;
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'repeal':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'delete':
			$data = array('status' => -1);
			break;

		case 'del':
			if (Db::name($mobile)->where($where)->delete()) {
				return true;
			}
			else {
				return null;
			}

			break;

		default:
			return null;
		}

		if (Db::name($mobile)->where($where)->update($data)) {
			return true;
		}
		else {
			return null;
		}
	}
}

?>