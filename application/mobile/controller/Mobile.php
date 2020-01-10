<?php
namespace app\mobile\controller;

use think\Db;
use think\Controller;

class Mobile extends Controller
{
	protected function _initialize()
	{
		$allow_controller=array("Ajax","Api","Article","Chart","Finance","Index","Login","Pay","Queue","Trade","User","Issue",  "Morefind","Financing","Exchange");
		if(!in_array(CONTROLLER_NAME,$allow_controller)){
			$this->error("非法操作");
		}
		
		defined('APP_DEMO') || define('APP_DEMO', 0);

		if (!session('userId')) {
			session('userId', 0);
		} else if (CONTROLLER_NAME != 'Login') {
/*			$user = Model('user')->where('id = ' . session('userId'))->find();
			if (!$user['paypassword']) {
				$this->redirect('/Login/register2');
			}*/

			// if (!$user['truename']) {
			// 	redirect('/Login/register3');
			// }
			// if(!session('loginTime')){
			// 	redirect('/Login/loginout');
			// }else{
			// 	if(is_file("login/".session('userId').".txt")){
			// 		$xx=file_get_contents("login/".session('userId').".txt");
			// 	}else{
			// 		$xx='';
			// 	}
			// 	if((!empty($xx)&&$xx!=session('loginTime'))){
			// 		file_put_contents("login/".session('userId').".txt",'');
			// 		$this->redirect('/Login/loginout');
			// 	}
			// }
		}
	}

	public function __construct()
	{
		parent::__construct();
		
		if (userid()) {
			$userCoin_top = Db::name('UserCoin')->where(array('userid' => userid()))->find();
/*			$userCoin_top['cny'] = round($userCoin_top['cny'], 2);
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
			$config = Db::name('Config')->where(array('id' => 1))->find();
			cache('home_config', $config);
		}

		if (!session('web_close')) {
			if (!$config['web_close']) {
				exit($config['web_close_cause']);
			}
		}

		config($config);
/*		config('contact_qq', explode('|', C('contact_qq')));
		config('contact_qqun', explode('|', C('contact_qqun')));
		config('contact_bank', explode('|', C('contact_bank')));*/
		
		$coin = (config('app.develop') ? null : cache('home_coin'));
		if (!$coin) {
			$coin = Db::name('Coin')->where(array('status' => 1))->select();
			cache('home_coin', $coin);
		}

		$coinList = array();
		foreach ($coin as $k => $v) {
			$coinList['coin'][$v['name']] = $v;
			if ($v['name'] != 'cny') {
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
			$market = Db::name('Market')->where(array('status' => 1))->select();
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
			$tables = Db::query('show tables');
			$tableMap = array();

			foreach ($tables as $table) {
				$tableMap[reset($table)] = 1;
			}
			
			if (!isset($tableMap['tw_daohang'])) {
				Db::execute("\r\n" . '                    CREATE TABLE `tw_daohang` (' . "\r\n" . '                        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT \'自增id\',' . "\r\n" . '                        `name` VARCHAR(255) NOT NULL COMMENT \'名称\',' . "\r\n" . '                         `title` VARCHAR(255) NOT NULL COMMENT \'名称\',' . "\r\n" . '                        `url` VARCHAR(255) NOT NULL COMMENT \'url\',' . "\r\n" . '                        `sort` INT(11) UNSIGNED NOT NULL COMMENT \'排序\',' . "\r\n" . '                        `addtime` INT(11) UNSIGNED NOT NULL COMMENT \'添加时间\',' . "\r\n" . '                        `endtime` INT(11) UNSIGNED NOT NULL COMMENT \'编辑时间\',' . "\r\n" . '                        `status` TINYINT(4)  NOT NULL COMMENT \'状态\',' . "\r\n" . '                        PRIMARY KEY (`id`)' . "\r\n\r\n" . '                  )' . "\r\n" . 'COLLATE=\'gbk_chinese_ci\'' . "\r\n" . 'ENGINE=MyISAM' . "\r\n" . 'AUTO_INCREMENT=1' . "\r\n" . ';' . "\r\n\r\n\r\n\r\n" . 'INSERT INTO `tw_daohang` (`name`,`title`, `url`, `sort`, `status`) VALUES (\'finance\',\'财务中心\', \'Finance/index\', 1, 1);' . "\r\n" . 'INSERT INTO `tw_daohang` (`name`,`title`, `url`, `sort`, `status`) VALUES (\'user\',\'安全中心\', \'User/index\', 2, 1);' . "\r\n" . 'INSERT INTO `tw_daohang` (`name`, `title`,`url`, `sort`, `status`) VALUES (\'game\',\'应用中心\', \'Game/index\', 3, 1);' . "\r\n" . 'INSERT INTO `tw_daohang` (`name`, `title`,`url`, `sort`, `status`) VALUES (\'article\',\'帮助中心\', \'Article/index\', 4, 1);' . "\r\n\r\n\r\n" . '                ');
			}
	
			cache('daohang_aa', 1);
		}*/

		if (!cache('daohang')) {
			$this->daohang = Db::name('Daohang')->where(array('status' => 1))->order('sort asc')->select();
			cache('daohang', $this->daohang);
		} else {
			$this->daohang = cache('daohang');
		}

		$footerArticleType = (config('app.develop') ? null : cache('footer_indexArticleType'));
		if (!$footerArticleType) {
			$footerArticleType = Db::name('ArticleType')->where(array('status' => 1, 'footer' => 1, 'shang' => ''))->order('sort asc ,id desc')->limit(3)->select();
			cache('footer_indexArticleType', $footerArticleType);
		}

		$this->assign('footerArticleType', $footerArticleType);
		$footerArticle = (config('app.develop') ? null : cache('footer_indexArticle'));
		if (!$footerArticle) {
			for ($i = 0; $i < count($footerArticleType); $i++) {
				$footerArticle[$i['name']] = Db::name('ArticleType')->where('footer', 1)->where('status', 1)->order('id asc')->limit(4)->select();
			}
			cache('footer_indexArticle', $footerArticle);
		}
		$this->assign('footerArticle', $footerArticle);

		// 底部友情链接--------------------S
		$footerindexLink = (config('app.develop') ? null : cache('index_indexLink'));
		if (!$footerindexLink) {
			$footerindexLink = Db::name('Link')->where(array('status' => 1,'look_type'=>1))->order('sort asc ,id desc')->select();
		}

		$this->assign('footerindexLink', $footerindexLink);
		// 底部友情链接--------------------E
		
		// 官方公告 ----------------------S
		$news_list1 = Db::name('Article')->where(array('status'=>1))->order('sort,endtime desc')->limit(3)->select();
		$this->assign('notice_list', $news_list1);
		// 官方公告 ----------------------n

		// 交易币种列表--------------------S
		$data = array();
		foreach (config('market') as $k => $v) {
			$v['xnb'] = explode('_', $v['name'])[0];
			$v['rmb'] = explode('_', $v['name'])[1];
			$data[$k]['name'] = $v['name'];
			$data[$k]['img'] = $v['xnbimg'];
			$data[$k]['title'] = $v['title'];
		}
		$this->assign('market_ss', $data);
		// 交易币种列表--------------------E
		
		//注册协议
		//$this->assign('registerAgreement',((LANG_SET=='zh-cn')?'/Article/detail/id/54.html':'/Article/detail/id/150.html'));
		$this->assign('registerAgreement','/Support/index/articles/cid/7/id/18.html');
		
		// 踢出内容中的标签
		//$notice_info['content'] = strip_tags($notice_info['content']);
	}
}

?>