<?php
namespace app\mobile\controller;

class Index extends Mobile
{

	protected function _initialize()
	{
		parent::_initialize();
		$allow_action=array("index","indexold","article","coin_list","qq");
		if(!in_array(ACTION_NAME,$allow_action)){
			$this->error("非法操作！");
		}
	}

	public function index()
	{
		$this->redirect('Trade/tradelist');
		return $this->fetch();
	}
	
	// 首页界面
	public function indexold()
	{
		// 幻灯片信息------S
		$indexAdver = (config('app.develop') ? null : cache('mobile_index_indexAdver'));
		if (!$indexAdver) {
			$indexAdver = Db::name('Adver')->where(array('status' => 1,'look'=>1,'lang'=>LANG_SET))->order('sort asc')->select();
			cache('mobile_index_indexAdver', $indexAdver);
		}
		$this->assign('indexAdver', $indexAdver);

		// 获取最新公告信息------S
		$indexArticle = (config('app.develop') ? null : cache('mobile_index_indexArticle'));
		if (!$indexArticle) {
			$indexArticle = Db::name('Article')->where(array('type' =>array('like','notice_%'), 'status' => 1, 'index' => 1,'lang'=>LANG_SET))->order('id desc')->find();
			cache('mobile_index_indexArticleType', $indexArticle);
		}
		$this->assign('indexArticle', $indexArticle);

		// 获取最新公告信息------E
        $helpArticle = (config('app.develop') ? null : cache('mobile_index_helpArticle'));
        if (!$indexArticle || true) {
            $helpType = Db::name('ArticleType')->where(array('status' => 1, 'footer' => 1, 'shang' => array('like','help_%'),'lang'=>LANG_SET))->order('sort asc ,id desc')->select();
            foreach ($helpType as $k => $v) {
                $second_class= Db::name('ArticleType')->where(array('shang' => $v['name'], 'footer' => 1, 'status' => 1,'lang'=>LANG_SET))->order('id asc')->select();
                if(!empty($second_class)){
                    foreach($second_class as $val){
                        $article_list = Db::name('Article')->where(array('footer'=>1,'index'=>1,'status'=>1,'type'=>$val['name']))->limit(5)->select();
                        if(!empty($article_list)){
                            foreach($article_list as $kk=>$vv){
                                $footerArticle[$v['name']][]=$vv;
                            }
                        }
                    }
                } else {
                    $article_list = Db::name('Article')->where(array('footer'=>1,'index'=>1,'status'=>1,'type'=>$v['name']))->limit(5)->select();
                    if(!empty($article_list)){
                        foreach($article_list as $kk=>$vv){
                            $footerArticle[$v['name']][]=$vv;
                        }
                    }
                }
            }
            $helpArticle=$footerArticle;
            cache('mobile_index_helpArticle', $helpArticle);
        }
        $this->assign('helpArticle', $helpArticle);

		$this->display('index');
	}

	// 充提币链接
	public function article()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}
		return $this->fetch();
	}

	// 充提币链接
	public function coin_list()
	{
		if (!userid()) {
			$this->redirect(url('Login/index'));
		}
		return $this->fetch();
	}

	// 在线客服
	public function qq()
	{
		return $this->fetch();
	}
}