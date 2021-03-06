<?php
namespace Home\Controller;

class ShopController extends HomeController
{
	protected function _initialize(){
		parent::_initialize();
		$allow_action=array("index","view","log","address","shopaddr","buyShop","shouhuo","setaddress","goods","upgoods","delgoods");
		if(!in_array($this->request->action(),$allow_action)){
			$this->error("非法操作！");
		}
	}
	
	public function index($name = NULL, $type = NULL, $deal = NULL, $addtime = NULL, $price = NULL, $ls = 20)
	{


		// 过滤非法字符----------------S

		if (checkstr($name) || checkstr($type) || checkstr($deal) || checkstr($addtime) || checkstr($price) || checkstr($ls)) {
			$this->error('您输入的信息有误！');
		}

		// 过滤非法字符----------------E



		if (cache('shop_login')) {
			if (!userid()) {
				$this->redirect(url('Login/index'));
			}
		}

		if (authgame('shop') != 1) {
			$this->redirect('/');
		}

		$this->assign('prompt_text', Model('Text')->get_content('game_shop'));

		if ($name && check($name, 'a')) {
			$where['name'] = array('like', '%' . trim($name) . '%');
		}

		$shop_type_list = Db::name('Shop')->shop_type_list();

		if ($type && $shop_type_list[$type]) {
			$where['type'] = trim($type);
		}

		$this->assign('shop_type_list', $shop_type_list);

		if (empty($deal)) {
		}

		if ($deal) {
			$deal_arr = explode('_', $deal);

			if (($deal_arr[1] == 'asc') || ($deal_arr[1] == 'desc')) {
				$order['deal'] = $deal_arr[1];
			}
			else {
				$order['deal'] = 'desc';
			}
		}

		if (empty($addtime)) {
		}

		if ($addtime) {
			$addtime_arr = explode('_', $addtime);

			if (($addtime_arr[1] == 'asc') || ($addtime_arr[1] == 'desc')) {
				$order['addtime'] = $addtime_arr[1];
			}
			else {
				$order['addtime'] = 'desc';
			}
		}

		if (empty($price)) {
		}

		if ($price) {
			$price_arr = explode('_', $price);

			if (($price_arr[1] == 'asc') || ($price_arr[1] == 'desc')) {
				$order['price'] = $price_arr[1];
			}
			else {
				$order['price'] = 'desc';
			}
		}

		$this->assign('name', $name);
		$this->assign('type', $type);
		$this->assign('deal', $deal);
		$this->assign('addtime', $addtime);
		$this->assign('price', $price);
		$where['status'] = 1;
		$shop = Db::name('Shop');
		$count = $shop->where($where)->count();
		$Page = new \Think\Page($count, $ls);
		$Page->parameter .= 'name=' . $name . '&type=' . $type . '&deal=' . $deal . '&addtime=' . $addtime . '&price=' . $price . '&';
		$show = $Page->show();
		$list = $shop->where($where)->order($order)->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function view($id)
	{

		// 过滤非法字符----------------S

		if (checkstr($id)) {
			$this->error('您输入的信息有误！');
		}

		// 过滤非法字符----------------E

		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		$this->assign('prompt_text', Model('Text')->get_content('game_shop_view'));

		if (!check($id, 'd')) {
			$this->error('参数错误！');
		}

		$Shop = Db::name('Shop')->where('id', $id)->find();

		if (!$Shop) {
			$this->error('商品错误！');
		}
		else {
			$this->assign('data', $Shop);
			$shop_coin_list = Db::name('Shop')->fangshi($Shop['id']);

			foreach ($shop_coin_list as $k => $v) {
				$coin_list[$k]['name'] = Db::name('Coin')->get_title($k);
				$coin_list[$k]['price'] = Num($v);
			}

			$this->assign('coin_list', $coin_list);
		}

		$goods_list = Db::name('Shop')->get_goods(userid());
		$this->assign('goods_list', $goods_list);
		return $this->fetch();
	}

	public function log($ls = 15)
	{

		// 过滤非法字符----------------S

		if (checkstr($ls)) {
			$this->error('您输入的信息有误！');
		}

		// 过滤非法字符----------------E

		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		$this->assign('prompt_text', Model('Text')->get_content('game_shop_log'));
		$where['status'] = array('egt', 0);
		$where['userid'] = userid();
		$ShopLog = Db::name('ShopLog');
		$count = $ShopLog->where($where)->count();
		$Page = new \Think\Page($count, $ls);
		$show = $Page->show();
		$list = $ShopLog->where($where)->order('id desc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function address()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		$ShopAddr = Db::name('ShopAddr')->where('userid', userid())->find();
		$this->assign('ShopAddr', $ShopAddr);
		return $this->fetch();
	}

	public function shopaddr()
	{
		exit();

		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		return $this->fetch();
	}

	public function buyShop($id, $num, $paypassword, $type, $goods)
	{

		// 过滤非法字符----------------S

		if (checkstr($id) || checkstr($num) || checkstr($paypassword) || checkstr($type) || checkstr($goods)) {
			$this->error('您输入的信息有误！');
		}

		// 过滤非法字符----------------E


		if (!userid()) {
			$this->error('请先登录！');
		}

		if (!check($id, 'd')) {
			$this->error('参数错误！');
		}

		if (!check($num, 'd')) {
			$this->error('购买数量格式错误！');
		}

		if (!check($goods, 'd')) {
			$this->error('收货地址格式错误！');
		}

		if (!check($paypassword, 'password')) {
			$this->error('交易密码格式错误！');
		}

		if (!check($type, 'w')) {
			$this->error('付款方式格式错误！');
		}

		$User = Db::name('User')->where('id', userid())->find();

		if (!$User['paypassword']) {
			$this->error('交易密码非法！');
		}

		if (md5($paypassword) != $User['paypassword']) {
			$this->error('交易密码错误！');
		}

		$Shop = Db::name('Shop')->where('id', $id)->find();

		if (!$Shop) {
			$this->error('商品错误！');
		}

		$my_goods = Db::name('UserGoods')->where('id', $goods)->find();

		if (!$my_goods) {
			$this->error('收货地址错误！');
		}

		if ($my_goods['userid'] != userid()) {
			$this->error('收货地址非法！');
		}

		if (!$Shop['status']) {
			$this->error('当前商品没有上架！');
		}

		if ($Shop['num'] <= $Shop['deal']) {
			$this->error('当前商品已经卖完！');
		}

		$shop_min = 1;
		$shop_max = 100000000;

		if ($num < $shop_min) {
			$this->error('购买数量超过系统最小限制！');
		}

		if ($shop_max < $num) {
			$this->error('购买数量超过系统最大限制！');
		}

		if (($Shop['num'] - $Shop['deal']) < $num) {
			$this->error('购买数量超过当前剩余量！');
		}

		if ($type != 'cny') {
			$coin_price = Db::name('Market')->get_new_price($type . '_cny');

			if (!$coin_price) {
				$this->error('当前币种价格错误！');
			}
		}
		else {
			$coin_price = 1;
		}

		$mum = round($Shop['price'] * $num, 8);

		if (!$mum) {
			$this->error('购买总额错误');
		}

		$xuyao = round($mum / $coin_price, 8);

		if (!$xuyao) {
			$this->error('付款总额错误');
		}

		$usercoin = Db::name('UserCoin')->where('userid', userid())->value($type);

		if ($usercoin < $xuyao) {
			$this->error('可用' . config('coin')[$type]['title'] . '余额不足,总共需要支付' . $xuyao . ' ' . config('coin')[$type]['title']);
		}

		$mo = db();
		Db::execute('set autocommit=0');
		Db::execute('lock tables tw_user_coin write,tw_shop write,tw_shop_log write');
		$rs = [];
		$rs[] = Db::table('tw_user_coin')->where('userid', userid())->setDec($type, $xuyao);
		$rs[] = Db::table('tw_shop')->where('id', $Shop['id'])->update(array(
			'deal' => array('exp', 'deal+' . $num),
			'num'  => array('exp', 'num-' . $num)
			));

		if ($Shop['num'] - $num <= 0) {
			$rs[] = Db::table('tw_shop')->where('id', $Shop['id'])->update(array('status' => 0));
		}

		$rs[] = Db::table('tw_shop_log')->insert(array('userid' => userid(), 'shopid' => $Shop['id'], 'price' => $Shop['price'], 'coinname' => $type, 'xuyao' => $xuyao, 'num' => $num, 'mum' => $mum, 'addr' => $my_goods['truename'] . '|' . $my_goods['mobile'] . '|' . $my_goods['addr'], 'addtime' => time(), 'status' => 0));

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

	public function shouhuo($id = NULL)
	{

		// 过滤非法字符----------------S

		if (checkstr($id)) {
			$this->error('您输入的信息有误！');
		}

		// 过滤非法字符----------------E

		if (!check($id, 'd')) {
			$this->error('参数错误！');
		}

		$shoplog = Db::name('ShopLog')->where('id', $id)->find();

		if (!$shoplog) {
			$this->error('操作失败1！');
		}

		if ($shoplog['userid'] != userid()) {
			$this->error('非法操作！');
		}

		$rs = Db::name('ShopLog')->where('id', $id)->update(array('status' => 1));

		if ($rs) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function setaddress($truename, $mobile, $name)
	{


		// 过滤非法字符----------------S

		if (checkstr($truename) || checkstr($mobile) || checkstr($name)) {
			$this->error('您输入的信息有误！');
		}

		// 过滤非法字符----------------E

		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		if (!check($truename, 'truename')) {
			$this->error('收货人姓名格式错误');
		}

		if (!check($mobile, 'mobile')) {
			$this->error('收货人电话格式错误');
		}

		if (!check($name, 'a')) {
			$this->error('收货地址格式错误');
		}

		$ShopAddr = Db::name('ShopAddr')->where('userid', userid())->find();

		if ($ShopAddr) {
			$rs = Db::name('ShopAddr')->where('userid', userid())->update(array('truename' => $truename, 'mobile' => $mobile, 'name' => $name));
		}
		else {
			$rs = Db::name('ShopAddr')->insert(array('userid' => userid(), 'truename' => $truename, 'mobile' => $mobile, 'name' => $name));
		}

		if ($rs) {
			$this->success('提交成功');
		}
		else {
			$this->error('提交失败');
		}
	}

	public function goods()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}

		$this->assign('prompt_text', Model('Text')->get_content('game_shop_goods'));
		$userGoodsList = Db::name('UserGoods')->where('userid', userid(), 'status' => 1)->order('id desc')->select();

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

		if (Db::name('UserGoods')->insert(array('userid' => userid(), 'name' => $name, 'addr' => $addr, 'idcard' => $idcard, 'truename' => $truename, 'mobile' => $mobile, 'addtime' => time(), 'status' => 1))) {
			$this->success('添加成功！');
		}
		else {
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
			$this->error('交易密码格式错误！');
		}

		if (!check($id, 'd')) {
			$this->error('参数错误！');
		}

		$user_paypassword = Db::name('User')->where('id', userid())->value('paypassword');

		if (md5($paypassword) != $user_paypassword) {
			$this->error('交易密码错误！');
		}

		if (!Db::name('UserGoods')->where('userid', userid(), 'id' => $id)->find()) {
			$this->error('非法访问！');
		}
		else if (Db::name('UserGoods')->where('userid', userid(), 'id' => $id)->delete()) {
			$this->success('删除成功！');
		}
		else {
			$this->error('删除失败！');
		}
	}
}

?>