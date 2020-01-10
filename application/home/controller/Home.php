<?php
namespace app\home\controller;

use think\Controller;
use think\Db;
use think\db\Where;

class Home extends Controller
{
	protected function _initialize()
	{
		$allow_controller=array("Index","Ajax","Api","Article","Finance","Login","Queue","Trade","Backstage","Excharge","Pay","User","Chart","Ptpbc","News","Reward", "Game","Financing","Issue","Vote");

		if(!in_array($this->request->controller(),$allow_controller)){
			$this->error("非法操作");
		}

		defined('APP_DEMO') || define('APP_DEMO', 0);

		// 链接审查（检查是否需要登录，检查是否开放访问）
		$data_url = (config('app.develop') ? null : cache('closeUrl'));

		if (!$data_url) {
			//$closeUrl = Db::name('daohang')->where('status=1')->field('url')->select();
			$closeUrl = Db::name('daohang')->where('url', $_SERVER['REQUEST_URI'])->where('status', 1)->find();
			cache('closeUrl', $closeUrl);

			if (cache('closeUrl')['get_login'] == 1) {
				$this->error(lang('需要登录后浏览!'), url('Login/index'));exit;
			}
			if (cache('closeUrl')['access'] == 1) {
				$this->error(lang('禁止访问！'), url('/'));exit;
			}
		}

/*
		// 旧的方法废弃，上面是新的
        $closeUrl = cache('closeUrl');
        if (empty($closeUrl)) {
			$list = Db::name('daohang')->field('url')->where('status=1 and get_login=1')->select();
            foreach($list as $v)
            {
                if ('' != $v['url']) {
                    $closeUrl[] = $v['url'];
                }
            }
            cache('closeUrl', $closeUrl);
        } else {
            if (!userid()) {
                foreach ($closeUrl as $v) {
                    if(mb_strripos($_SERVER['REQUEST_URI'], $v) !== false) {
                        //echo $_SERVER['REQUEST_URI'].$v.'<br>';
						$this->error(lang('请先登录，继续浏览。'), url('Login/index'));
                    } else {
                        break;
                    }
                }
            }
        }
		*/

		if (!session('userId')) {
			session('userId', 0);
		} else if ($this->request->controller() != 'Login' ) {
/*			$user = Db::name('user')->where('id = ' . session('userId')->find();
			if (!$user['paypassword']) {
				//未设置交易密码
				$this->redirect('/Login/register1');
			}*/
		}
	}

	public function __construct()
	{
		parent::__construct();
		
		if (userid()) {
			$userCoin_top = Db::name('user_coin')->where('userid', userid())->find();
/*			 $userCoin_top['cny'] = round($userCoin_top['cny'], 2);
			 $userCoin_top['cnyd'] = round($userCoin_top['cnyd'], 2);*/
			$userCoin_top[config('app.anchor_cny')] = sprintf("%.2f", $userCoin_top[config('app.anchor_cny')]);
			$userCoin_top[config('app.anchor_cny').'d'] = sprintf("%.2f", $userCoin_top[config('app.anchor_cny').'d']);
			$this->assign('userCoin_top', $userCoin_top);
		}

		if (isset($_GET['invit'])) {
			session('invit', $_GET['invit']);
		}

		$config = (config('app.develop') ? null : cache('home_config'));
		if (!$config) {
			$config = Db::name('Config')->where('id', 1)->find();
			cache('home_config', $config);
		}
		
		// 检查是否关闭站点
		if (!session('web_close')) {
			if (!$config['web_close']) {
				$conf = [];
				
				if(config('app.default_lang') == "zh-cn"){
					$conf['langs_close_cause'] = $config['web_close_cause'];
				} else {
					$conf['langs_close_cause'] = $config['web_close_cause_en'];
				}
				
				$this->assign('conf', $conf);
				$this->display('Index/maintain');
				exit;
			}
		}

		config($config);
/*		config('contact_qq', explode('|', config('contact_qq')));
		config('contact_qqun', explode('|', config('contact_qqun')));
		config('contact_bank', explode('|', config('contact_bank')));*/
		
		$coin = (config('app.develop') ? null : cache('home_coin'));
		if (!$coin) {
			$coin = Db::name('coin')->where('status', 1)->select();
			cache('home_coin', $coin);
		}
		$coinList = [];
		foreach ($coin as $k => $v) {
			$coinList['coin'][$v['name']] = $v;
			if ($v['name'] != config('app.anchor_cny')) {
				$coinList['coin_list'][$v['name']] = $v;
			}
			if ($v['type'] == 'rmb') {
				$coinList['rmb_list'][$v['name']] = $v;
			} else {
				$coinList['xnb_list'][$v['name']] = $v;
			}
			if ($v['type'] == 'rgb') {
				$coinList['rgb_list'][$v['name']] = $v;
			}
			if ($v['type'] == 'qbb') {
				$coinList['qbb_list'][$v['name']] = $v;
			}
		}
		config('coin', $coinList['coin']);

		$market = (config('app.develop') ? null : cache('home_market'));
		if (!$market) {
			$market = Db::name('Market')->where('status', 1)->order('sort asc')->select();
			cache('home_market', $market);
		}
		foreach ($market as $k => $v) {
			$v['new_price'] = round($v['new_price'], $v['round']);
			$v['buy_price'] = round($v['buy_price'], $v['round']);
			$v['sell_price'] = round($v['sell_price'], $v['round']);
			$v['min_price'] = round($v['min_price'], $v['round']);
			$v['max_price'] = round($v['max_price'], $v['round']);
			$v['xnb'] = explode('_', $v['name'])[0];
			$v['rmb'] = explode('_', $v['name'])[1];
			$v['xnbimg'] = config('coin')[$v['xnb']]['img'];
			$v['rmbimg'] = config('coin')[$v['rmb']]['img'];
			$v['volume'] = $v['volume'] * 1;
			$v['change'] = $v['change'] * 1;
			$v['title'] = config('coin')[$v['xnb']]['title'] . '(' . strtoupper($v['xnb']) . '/' . strtoupper($v['rmb']) . ')';

			$v['title_n'] = config('coin')[$v['xnb']]['title'];
			$v['title_ns'] = '(' . strtoupper($v['xnb']) . '/' . strtoupper($v['rmb']) . ')';
			$v['title_nsm'] = strtoupper($v['xnb']);

			$marketList['market'][$v['name']] = $v;
		}
		config('market', $marketList['market']);

		$C = config();
		foreach ($C as $k => $v) {
			$C[strtolower($k)] = $v;
		}

        $this->assign('C', $C);
		
/*		if (!cache('daohang_aa')) {
			$tables = db()->query('show tables');
			$tableMap = [];

			foreach ($tables as $table) {
				$tableMap[reset($table)] = 1;
			}

			if (!isset($tableMap['tw_daohang'])) {
				Db::name()->execute("\r\n" . 'CREATE TABLE `tw_daohang` (' . "\r\n" . ' `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT \'自增id\',' . "\r\n" . ' `name` VARCHAR(255) NOT NULL COMMENT \'名称\',' . "\r\n" . ' `title` VARCHAR(255) NOT NULL COMMENT \'名称\',' . "\r\n" . ' `url` VARCHAR(255) NOT NULL COMMENT \'url\',' . "\r\n" . ' `sort` INT(11) UNSIGNED NOT NULL COMMENT \'排序\',' . "\r\n" . ' `addtime` INT(11) UNSIGNED NOT NULL COMMENT \'添加时间\',' . "\r\n" . ' `endtime` INT(11) UNSIGNED NOT NULL COMMENT \'编辑时间\',' . "\r\n" . ' `status` TINYINT(4)  NOT NULL COMMENT \'状态\',' . "\r\n" . ' PRIMARY KEY (`id`)' . "\r\n\r\n" . ' )' . "\r\n" . 'COLLATE=\'gbk_chinese_ci\'' . "\r\n" . 'ENGINE=MyISAM' . "\r\n" . 'AUTO_INCREMENT=1' . "\r\n" . ';' . "\r\n\r\n\r\n\r\n" . 'INSERT INTO `tw_daohang` (`name`,`title`, `url`, `sort`, `status`) VALUES (\'finance\',\'财务中心\', \'Finance/index\', 1, 1);' . "\r\n" . 'INSERT INTO `tw_daohang` (`name`,`title`, `url`, `sort`, `status`) VALUES (\'user\',\'安全中心\', \'User/index\', 2, 1);' . "\r\n" . 'INSERT INTO `tw_daohang` (`name`, `title`,`url`, `sort`, `status`) VALUES (\'game\',\'应用中心\', \'Game/index\', 3, 1);' . "\r\n" . 'INSERT INTO `tw_daohang` (`name`, `title`,`url`, `sort`, `status`) VALUES (\'article\',\'帮助中心\', \'Article/index\', 4, 1);' . "\r\n\r\n\r\n" . ' ');
			}
			cache('daohang_aa', 1);
		}*/
		
		// 顶部导航--------------------S
		if (!cache('daohang_'.config('app.default_lang'))) {
			$this->daohang = Db::name('Daohang')->where('status', 1)->where('lang', config('app.default_lang'))->order('sort asc')->select();
			cache('daohang_'.config('app.default_lang'), $this->daohang);
		} else {
			$this->daohang = cache('daohang_'.config('app.default_lang'));
//			cache('daohang_'.config('app.default_lang'), null);
		}
		$this->assign('daohang', $this->daohang);
		// 顶部导航--------------------E
		
		// 页脚导航--------------------S
		if (!cache('footer_'.config('app.default_lang'))) {
			$this->footer = Db::name('footer')->where('status', 1)->where('lang', config('app.default_lang'))->order('sort asc')->select();
			cache('footer_'.config('app.default_lang'), $this->footer);
		} else {
			$this->footer = cache('footer_'.config('app.default_lang'));
		}
		$this->assign('footer', $this->footer);
		// 页脚导航--------------------E
		
		$footerArticleType = (config('app.develop') ? null : cache('footer_indexArticleType'));
		if (!$footerArticleType || true) {
			$footerArticleType = Db::name('ArticleType')->where('status', 1)->where('footer', 1)->order('sort asc ,id desc')->limit(5)->select();
			cache('footer_indexArticleType', $footerArticleType);
		}

		$this->assign('footerArticleType', $footerArticleType);
		$footerArticle = (config('app.develop') ? null : cache('footer_indexArticle'));
		if (!$footerArticle) {
			foreach ($footerArticleType as $k => $v) {
				 $second_class = Db::name('ArticleType')->where('footer', 1)->where('status', 1)->order('id asc')->select();
				 if (!empty($second_class)) {
					 foreach ($second_class as $val){
						 $article_list = Db::name('Article')->where('footer', 1)->where('index', 1)->where('status', 1)->where('type', $val['title'])->limit(5)->select();
						 if (!empty($article_list)) {
							 foreach ($article_list as $kk=>$vv) {
								 $footerArticle[$v['name']][] = $vv;
							 }
						 }
					 }
				 } else {
					 $article_list = Db::name('Article')->where('footer', 1)->where('index', 1)->where('status', 1)->where('type', $v['name'])->limit(5)->select();
					 if (!empty($article_list)) {
						 foreach ($article_list as $kk=>$vv) {
							 $footerArticle[$v['name']][] = $vv;
						 }
					 }
				 }
			}
			cache('footer_indexArticle', $footerArticle);
		}
		$this->assign('footerArticle', $footerArticle);
		
		// 底部友情链接--------------------S
		$footerindexLink = (config('app.develop') ? null : cache('index_indexLink'));
		if (!$footerindexLink) {
			$footerindexLink = Db::name('Link')->where('status', 1)->where('look_type', 1)->order('sort asc ,id desc')->select();
		}
		$this->assign('footerindexLink', $footerindexLink);
		// 底部友情链接--------------------E

		// 官方公告 ----------------------S
		$news_list1 = Db::name('Article')->where('status', 1)->order('sort,endtime desc')->limit(3)->select();
		$this->assign('notice_list', $news_list1);
		// 官方公告 ----------------------n

		// 交易币种列表--------------------S
		$data = [];
		if (!empty(cache('market'))) {
            foreach (cache('market') as $k => $v) {
                $v['xnb'] = explode('_', $v['name'])[0];
                $v['rmb'] = explode('_', $v['name'])[1];
                $data[$k]['name'] = $v['name'];
                $data[$k]['img'] = $v['xnbimg'];
                $data[$k]['title'] = $v['title'];
            }
        }

		$this->assign('market_ss', $data);
		// 交易币种列表--------------------E

		//注册协议
		//$this->assign('registerAgreement',((config('app.default_lang')=='zh-cn')?'/Article/detail/id/54.html':'/Article/detail/id/150.html'));
		$this->assign('registerAgreement','/Support/index/articles/cid/7/id/18.html');
		
		// 踢出内容中的标签
		//$notice_info['content'] = strip_tags($notice_info['content']);
	}
}
?>