<?php
namespace app\mobile\controller;

class ArticleController extends MobileController
{
	protected function _initialize(){
		parent::_initialize();
		$allow_action=array("about","about_detail","help_list","notice","notice_detail","news","index","detail");
		if(!in_array(ACTION_NAME,$allow_action)){
			$this->error("非法操作！");
		}
	}

	public function index($id = NULL)
	{


		// 过滤非法字符----------------S

		if (checkstr($id)) {
			$this->error('您输入的信息有误！');
		}

		// 过滤非法字符----------------E


		if (empty($id)) {
			$this->redirect(url('Article/detail'));
		}

		if (!check($id, 'd')) {
			$this->redirect(url('Article/detail'));
		}

		$Articletype = Db::name('ArticleType')->where(array('id' => $id))->find();
		$ArticleTypeList = Db::name('ArticleType')->where(array('status' => 1, 'index' => 1, 'shang' => $Articletype['shang']))->order('sort asc ,id asc')->select();
		$Articleaa = Db::name('Article')->where(array('id' => $ArticleTypeList[0]['id']))->find();
		$this->assign('shang', $Articletype);

		foreach ($ArticleTypeList as $k => $v) {
			$ArticleTypeLista[$v['name']] = $v;
		}

		$this->assign('ArticleTypeList', $ArticleTypeLista);
		$this->assign('data', $Articleaa);
		$where = array('type' => $Articletype['name']);
		$Model = Db::name('Article');
		$count = $Model->where($where)->count();
		$Page = new \Think\Page($count, 10);
		$show = $Page->show();
		$list = $Model->where($where)->order('sort asc ,id desc')->limit(0, 10)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function news($id = NULL)
	{
		// 过滤非法字符----------------S

		if (checkstr($id)) {
			$this->error('您输入的信息有误！');
		}

		// 过滤非法字符----------------E
		// 热门新闻id固定了19，如需变动在改id
		$id = 20;

		$Articletype = Db::name('ArticleType')->where(array('id' => $id))->find();
		$where = array('type' => $Articletype['name']);
		$Model = Db::name('Article');
		$count = $Model->where($where)->count();

		$Page = new \Think\Page1($count, 10);
		$show = $Page->show();
		$list = $Model->where($where)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			// 踢出内容里的html标签
			$list[$k]['content'] = strip_tags($v['content']);
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function about($id = NULL)
	{

		// 过滤非法字符----------------S

		if (checkstr($id)) {
			$this->error('您输入的信息有误！');
		}

		// 过滤非法字符----------------E

		// 关于平台shang字段固定了aboutus
		$shang = 'aboutus';

		$list = Db::name('ArticleType')->where(array('shang' => $shang))->select();

		foreach ($list as $k => $v) {
			// 踢出内容里的html标签
			$list[$k]['content'] = strip_tags($v['content']);
		}

		$this->assign('list', $list);
		return $this->fetch();
	}


	public function about_detail($id = NULL)
	{

		// 过滤非法字符----------------S

		if (checkstr($id)) {
			$this->error('您输入的信息有误！');
		}

		// 过滤非法字符----------------E

		if (empty($id)) {
			$id = 1;
		}

		if (!check($id, 'd')) {
			$id = 1;
		}

		$data = Db::name('ArticleType')->where(array('id' => $id))->find();
		$this->assign('data', $data);
		return $this->fetch();
	}

	/*
	*****帮助中心
	*/

	public function help_list()
	{

		$where = array();
		$where['status'] = 1;

		$list = Db::name('ArticleType')->where($where)->select();

		$list_f = array();
		$list_z = array();
		$list_shang = array();

		foreach ($list as $k => $v) {
			if($v['name'] == 'notice' || !$v['shang']){
				continue;
			}

			$list_z[] = $v;

			$list_shang[] = $v['shang'];
		}

		$list_shang = array_unique($list_shang);

		foreach ($list as $k => $v) {

			if($v['shang'] || $v['name'] == 'notice'){
				continue;
			}

			if(in_array($v['name'], $list_shang)){
				$list[$k]['is_shang'] = 1;
			}else{
				$list[$k]['is_shang'] = 0;
			}

			$list_f[] = $list[$k];
		}

		// echo '<pre>';
		// var_dump($list);
		// echo '</pre>';die();
		
		$this->assign('list', $list_f);
		$this->assign('list_z', $list_z);
		return $this->fetch();
	}

	/*
	*
	*****官方公告
	*
	*/

	public function notice($name = NULL)
	{
		// 过滤非法字符----------------S

		if (checkstr($name)) {
			$this->error('您输入的信息有误！');
		}

		// 过滤非法字符----------------E

		if(!$name){
			$this->error('暂无该信息1');
		}

		$Article_first_info = Db::name('ArticleType')->where(array('name' => $name))->find();

		if(!$Article_first_info){
			$this->error('暂无该信息2');
		}

		$this->assign('Article_first_info', $Article_first_info);

		$Model = Db::name('Article');
		$wheres = array();
		$wheres['status'] = 1;
		$wheres['type'] = $Article_first_info['name'];
		$count = $Model->where($wheres)->count();

		$Page = new \Think\Page1($count, 10);
		$show = $Page->show();
		$list = $Model->where($wheres)->order('id desc')->limit(0, 10)->select();

		foreach ($list as $k => $v) {
			// 踢出内容里的html标签
			$list[$k]['content'] = strip_tags($v['content']);
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function notice_detail($id = NULL){
		// 过滤非法字符----------------S
		if (checkstr($id)) {
			$this->error('您输入的信息有误！');
		}
		// 过滤非法字符----------------E
		if (empty($id)) {
			$id = 1;
		}
		if (!check($id, 'd')) {
			$id = 1;
		}
		$data = Db::name('Article')->where(array('id' => $id))->find();
		$data_s = Db::name('ArticleType')->where(array('name' => $data['type']))->find();
		$this->assign('data', $data);
		$this->assign('data_s', $data_s);
		return $this->fetch();
	}

	public function detail($id = NULL){
        // 过滤非法字符----------------S
        if (checkstr($id)) {
            $this->error('您输入的信息有误！');
        }
        // 过滤非法字符----------------E
        if (empty($id)) {
            $id = 1;
        }
        if (!check($id, 'd')) {
            $id = 1;
        }
        $data = Db::name('Article')->where(array('id' => $id))->find();
        $data_s = Db::name('ArticleType')->where(array('name' => $data['type']))->find();
        $this->assign('data', $data);
        $this->assign('data_s', $data_s);
        return $this->fetch();
	}
}
?>