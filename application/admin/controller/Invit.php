<?php
namespace app\admin\controller;

use think\Db;

class Invit extends Admin
{
	private $Model;

	public function __construct()
	{
		parent::__construct();
		$this->Title = '推广记录';
	}

	public function index($name = NULL, $userr = NULL, $status = NULL)
	{
		$where = array();
		
		/* 用户名--条件 */
		if ($name) {
			if ($userr == "") {
				$where['userid'] = Db::name('User')->where('username', $name)->value('id');
			} else {
				$where['invit'] = Db::name('User')->where('username', $name)->value('id');
			}
		}
		// 默认状态
		if ($status == 0) {
			$where['status'] = 0;
		}
		/* 状态--条件 */
		if ($status != '99') {
			if ($status) {
				$where['status'] = $status;
			}
		}
		
		// 统计
		$tongji['ydz'] = Db::name('Invit')->where('status', 1)->sum('fee') * 1;
		$tongji['wdz'] = Db::name('Invit')->where('status', 0)->sum('fee') * 1;
		$tongji['heji'] = Db::name('Invit')->sum('fee') * 1;
		$this->assign('tongji', $tongji);
		

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
	
	public function mining($name = NULL, $status = NULL)
	{
		$where = array();
		
		/* 用户名--条件 */
		if ($name) {
			$where['userid'] = Db::name('User')->where('username', $name)->value('id');
		}
		// 默认状态
		if ($status == 0) {
			$where['status'] = 0;
		}
		/* 状态--条件 */
		if ($status != '99') {
			if ($status) {
				$where['status'] = $status;
			}
		}
		
		// 统计
		$tongji['ydz'] = Db::name('mining')->where('status', 1)->sum('fee') * 1;
		$tongji['wdz'] = Db::name('mining')->where('status', 0)->sum('fee') * 1;
		$tongji['heji'] = Db::name('mining')->sum('fee') * 1;
		$this->assign('tongji', $tongji);
		

		$show = Db::name('mining')->where($where)->paginate(10);

		$list = Db::name('mining')->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = Db::name('User')->where('id', $v['userid'])->value('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}
	
	
	public function recharge($name = NULL, $status = NULL)
	{
		echo '开发中';die();
		$where = array();
		
		/* 用户名--条件 */
		if ($name) {
			$where['userid'] = Db::name('User')->where('username', $name)->value('id');
		}
		// 默认状态
		if ($status == 0) {
			$where['status'] = 0;
		}
		/* 状态--条件 */
		if ($status != '99') {
			if ($status) {
				$where['status'] = $status;
			}
		}
		
		// 统计
		$tongji['ydz'] = Db::name('mining_recharge')->where('status', 1)->sum('sd_num') * 1;
		$tongji['wdz'] = Db::name('mining_recharge')->where('status', 0)->sum('sd_num') * 1;
		$tongji['heji'] = Db::name('mining_recharge')->sum('sd_num') * 1;
		$this->assign('tongji', $tongji);
		

		$count = Db::name('mining_recharge')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = Db::name('mining_recharge')->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = Db::name('User')->where('id', $v['userid'])->value('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}
}
?>