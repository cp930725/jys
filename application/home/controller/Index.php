<?php
namespace app\home\controller;

use think\Db;

class Index extends Home
{
	protected function _initialize()
	{
		parent::_initialize();
		$allow_action = array("index","more");
		if (!in_array($this->request->action(),$allow_action)) {
			$this->error("非法操作！");
		}
	}

	public function index()
	{
		$getCoreConfig = getCoreConfig();
		if (!$getCoreConfig) {
			$this->error('核心配置有误');
		}
		$this->assign('jiaoyiqu', $getCoreConfig['indexcat']);
		
		// 轮播图
		$banner = Db::name('Adver')->where('look', 0)->where('status', 1)->field('name,subhead,img,onlinetime')->order('id desc')->select();
		$this->assign('banner', $banner);
		
		return $this->fetch();
	}

    public function more(){
        return $this->fetch();
    }
}
?>