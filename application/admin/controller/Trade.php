<?php
namespace app\admin\controller;

use  think\Db;

class Trade extends Admin
{
	protected function _initialize(){
		parent::_initialize();
		$allow_action=array("index","chexiao","log","chat","chatStatus","comment","commentStatus","market","marketEdit","marketStatus","invit","tradeclear");
		if(!in_array($this->request->action(),$allow_action)){
			$this->error("页面不存在！");
		}
	}

	public function index($field = NULL, $name = NULL, $market = NULL, $status = NULL, $bs_type = NULL, $starttime = NULL, $endtime = NULL)
	{
		$where = array();
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where('username', $name)->value('id');
			} else {
				$where[$field] = $name;
			}
		}
		
		$where['userid'] = array('gt',0);
		if ($market) {
			$where['market'] = $market;
		}
		if ($status) {
			$where['status'] = $status - 1;
		}

		// 交易类型
		if ($bs_type) {
			$where['type'] = $bs_type;
		}

		// 时间--条件
		$time_type = 'addtime';
		if (!empty($starttime) && empty($endtime)) {
			$starttime = strtotime($starttime);
			$where[$time_type] = array('EGT',$starttime);

		}else if(empty($starttime) && !empty($endtime)){
			$endtime = strtotime($endtime);
			$where[$time_type] = array('ELT',$endtime);

		}else if(!empty($starttime) && !empty($endtime)){
			$starttime = strtotime($starttime);
			$endtime = strtotime($endtime);
			$where[$time_type] =  array(array('EGT',$starttime),array('ELT',$endtime));

		}

		$list_new = Db::name('Trade')->where($where)->order('id desc')->select();

		$num_zong = 0;
		$num_cj_zong = 0;
		$money_zong = 0;

		foreach ($list_new as $k => $v) {
			$num_zong += $v['num'];
			$num_cj_zong += $v['deal'];
			$money_zong += $v['mum'];
		}

		$show = Db::name('Trade')->where($where)->paginate(10);

		$list = Db::name('Trade')->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = Db::name('User')->where('id', $v['userid'])->value('username');
		}
		$datas = array();
		$datas['num_zong'] = $num_zong;
		$datas['num_cj_zong'] = $num_cj_zong;
		$datas['money_zong'] = $money_zong;

		$this->assign('datas', $datas);
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function chexiao($id = NULL)
	{
		$rs = Model('Trade')->chexiao($id);

		if ($rs[0]) {
			$this->success($rs[1]);
		}
		else {
			$this->error($rs[1]);
		}
	}

	public function log($field = NULL, $name = NULL, $market = NULL, $bs_type = NULL, $starttime = NULL, $endtime = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where('username', $name)->value('id');
			}
			else if ($field == 'peername') {
				$where['peerid'] = Db::name('User')->where('username', $name)->value('id');
			}
			else {
				$where[$field] = $name;
			}
		}

		if ($market) {
			$where['market'] = $market;
		}




		// 交易类型
		if ($bs_type) {
			$where['type'] = $bs_type;
		}


		// 时间--条件
		$where['userid|peerid'] = array('gt',0);
		$time_type = 'addtime';

		if (!empty($starttime) && empty($endtime)) {
			$starttime = strtotime($starttime);
			$where[$time_type] = array('EGT',$starttime);

		}else if(empty($starttime) && !empty($endtime)){
			$endtime = strtotime($endtime);
			$where[$time_type] = array('ELT',$endtime);

		}else if(!empty($starttime) && !empty($endtime)){
			$starttime = strtotime($starttime);
			$endtime = strtotime($endtime);
			$where[$time_type] =  array(array('EGT',$starttime),array('ELT',$endtime));

		}


		$list_new = Db::name('TradeLog')->where($where)->select();

		$num_zong = 0;
		$money_zong = 0;
		$fee_sell_zong=0;

		foreach ($list_new as $k => $v) {
			$num_zong += $v['num'];
			$money_zong += $v['mum'];
			$fee_sell_zong += $v['fee_sell'];

		}



		$show = Db::name('TradeLog')->where($where)->paginate(10);

		$list = Db::name('TradeLog')->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = Db::name('User')->where('id', $v['userid'])->value('username');
			$list[$k]['username']=$list[$k]['username']?$list[$k]['username']:'系统';
			$list[$k]['peername'] = Db::name('User')->where('id', $v['peerid'])->value('username');
			$list[$k]['peername'] = $list[$k]['peername']?$list[$k]['peername']:'系统';

		}

		$datas = array();
		$datas['num_zong'] = $num_zong;
		$datas['money_zong'] = $money_zong;
		$datas['fee_sell_zong'] = $fee_sell_zong;

		$this->assign('datas', $datas);
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function chat($field = NULL, $name = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where('username', $name)->value('id');
			}
			else {
				$where[$field] = $name;
			}
		}

		$show = Db::name('Chat')->where($where)->paginate(10);

		$list = Db::name('Chat')->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = Db::name('User')->where('id', $v['userid'])->value('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function chatStatus($id = NULL, $type = NULL, $mobile = 'Chat')
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
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
				$this->success('操作成功！');
			}
			else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (Db::name($mobile)->where($where)->update($data)) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function comment($field = NULL, $name = NULL, $coinname = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where('username', $name)->value('id');
			}
			else {
				$where[$field] = $name;
			}
		}

		if ($coinname) {
			$where['coinname'] = $coinname;
		}

		$count = Db::name('CoinComment')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = Db::name('CoinComment')->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = Db::name('User')->where('id', $v['userid'])->value('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function commentStatus($id = NULL, $type = NULL, $mobile = 'CoinComment')
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
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
				$this->success('操作成功！');
			}
			else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (Db::name($mobile)->where($where)->update($data)) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function market($field = NULL, $name = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where('username', $name)->value('id');
			}
			else {
				$where[$field] = $name;
			}
		}

		$show = Db::name('Market')->where($where)->paginate(10);

		$list = Db::name('Market')->where($where)->order('id desc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function marketEdit($id = NULL)
	{
		$getCoreConfig = getCoreConfig();
		if(!$getCoreConfig){
			$this->error('核心配置有误');
		}
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = array();
			}
			else {
				$this->data = Db::name('Market')->where('id', $id)->find();
			}
			$time_arr = array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23');
			$time_minute = array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','38','39','40','41','42','43','44','45','46','47','48','49','50','51','52','53','54','55','56','57','58','59');
			$this->assign('time_arr', $time_arr);
			$this->assign('time_minute', $time_minute);
			$this->assign('getCoreConfig',$getCoreConfig['indexcat']);
			return $this->fetch();
		}
		else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$round = array(0, 1, 2, 3, 4, 5, 6);

			if (!in_array($_POST['round'], $round)) {
				$this->error('小数位数格式错误！');
			}

			if ($_POST['id']) {
				$rs = Db::name('Market')->update($_POST);
			}
			else {
				$_POST['name'] = $_POST['sellname'] . '_' . $_POST['buyname'];
				unset($_POST['buyname']);
				unset($_POST['sellname']);

				if (Db::name('Market')->where('name', $_POST['name'])->find()) {
					$this->error('市场存在！');
				}

				$rs = Db::name('Market')->insert($_POST);
			}

			if ($rs) {
				$this->success('操作成功！');
			}
			else {
				$this->error('操作失败！');
			}
		}
	}

	public function marketStatus($id = NULL, $type = NULL, $mobile = 'Market')
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
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
				$this->success('操作成功！');
			}
			else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (Db::name($mobile)->where($where)->update($data)) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function invit($field = NULL, $name = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where('username', $name)->value('id');
			}
			else {
				$where[$field] = $name;
			}
		}

		$show = Db::name('Invit')->where($where)->paginate(10);

		$list = Db::name('Invit')->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = Db::name('User')->where('id', $v['userid'])->value('username');
			$list[$k]['invit'] = Db::name('User')->where('id', $v['invit'])->value('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}
	
	public function tradeclear($type=NULL,$id=NULL)
	{
		if(!$id){
			$this->error('请选择交易市场!');
		}
		if(!$type){
			$this->error('请选择清理类型!');
		}
		$market= Db::name('Market')->where('id', $id)->find();
		if($type==1){
			$allclear=Db::name('Trade')->where(array('market'=>$market['name'],'userid'=>0))->delete();
		}
		if($type==2){
			if(!$market['sdhigh'] or !$market['sdlow']){
				$this->error('该市场未设置刷单最高价或最低价,无法部分清理');
			}
			$map['market']=$market['name'];
			$map['userid']=0;
			$map['price']=array('notbetween',array($market['sdhigh'],$market['sdlow']));
			$allclear=Db::name('Trade')->where($map)->delete();
		}
		if($allclear){
			$this->success('清理成功,一共'.$allclear.'条刷单记录');
		}else{
			$this->error('清理失败!');
		}
	}

}

?>
