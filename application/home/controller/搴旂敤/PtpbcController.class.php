<?php
/* 应用 - OTC场外交易 */
namespace Home\Controller;

class PtpbcController extends HomeController
{
    protected function _initialize() {
        parent::_initialize();
        $allow_action = ["index","index2","push","apply","buy","buylist","buyinfo",'buy_action','uppush'];
        if (!userid()) {
            redirect(url('Login/index'));
        }
        if (!in_array($this->request->action(), $allow_action)) {
            $this->error("非法操作！");
        }
        if(input('market'))S('market',I('market'));
        if(!cache('market'))S('market','USDT');
        $this->assign('market',S('market'));
    }

    public function index()
	{
        // $lists=DB::name('Ptpbc')->where(array('market'=>S('market'),'lang'=>config('app.default_lang'))->select();
        $lists=DB::name('Ptpbc')->where(array('market'=>S('market'))->select();
        foreach ($lists as $k=>$v){
            $lists[$k]['quota']=str_replace(',','-',$v['quota']);
        }
        $data = ['merchants' => $lists,];
        $this->assign('coin',S('market'));
        $this->assign('data', $data);
        return $this->fetch();
    }
	
	public function index2() 
	{
        $lists=DB::name('Ptpbc')->where(array('market'=>S('market'))->select();
        // $lists=DB::name('Ptpbc')->where(array('market'=>S('market'),'lang'=>config('app.default_lang'))->select();
        foreach ($lists as $k=>$v){
            $lists[$k]['quota']=str_replace(',','-',$v['quota']);
        }
        $data = ['merchants' => $lists,];
        $this->assign('data', $data);
        return $this->fetch();
    }
	
    public function push()
	{
        $coin = 'btc';
        $Coins = Db::name('Coin')->where('name', $coin)->find();
        $myzc_min = ($Coins['zc_min'] ? abs($Coins['zc_min']) : 1);
        $myzc_max = ($Coins['zc_max'] ? abs($Coins['zc_max']) : 10000000);
        $this->assign('myzc_min', $myzc_min);
        $this->assign('myzc_max', $myzc_max);
        $this->assign('xnb', $coin);
        $Coins = Db::name('Coin')->where(array(
            'status' => 1,
            'name'   => array('in', array('btc','usdt'))
            )->select();

        foreach ($Coins as $k => $v) {
            $coin_list[$v['name']] = $v;
        }
        $btc=C('btc');
        $usdt=C('usdt');
        $this->assign('btc',$btc);
        $this->assign('usdt',$usdt);
        $this->assign('coin_list', $coin_list);
        $user_coin = Db::name('UserCoin')->where('userid', userid())->find();
        $user_coin[$coin] = round($user_coin[$coin], 6);
        $user_coin[$coin] = sprintf("%.4f", $user_coin[$coin]);
        $this->assign('user_coin', $user_coin);
        return $this->fetch();
    }
	
    public function uppush($coin,$type,$min,$max,$bz,$gj,$yj,$num,$price,$paypassword)
	{
        if (!userid()) {
            $this->error(lang('您没有登录请先登录！'));
        }
        $user = Db::name('user')->where('status', userid())->find();
        if (md5($paypassword) != $user['paypassword']) {
            $this->error(lang('交易密码错误！'));
        }
        // $this->error($coin);
        $num = abs($num);
        $min=abs($min);
        $max=abs($max);
        if($coin=='btc'){
            $map['unit']='CNY/BTC';
        }
         if($coin=='usdt'){
            $map['unit']='CNY/USDT';
        }
        $map['lang']='zh-cn';
        $map['nation']=$gj;
        $map['market']=$coin;
        $map['price']=$price;
        $map['type']=$type;
        $map['quota']=$min.','.$max;
        $map['addtime']=time();
        $map['merchant']=$user['username'];
        $ccc=DB::name('ptpbc')->insert($map);
        if( $ccc){
             $this->success(lang('发布成功'));
        }else{
              $this->error(lang('发布失败'));
        }

    }
	
    public function apply()
	{
        $merchant = [];
        $this->assign('merchant', $merchant);
        return $this->fetch();
    }
	
    public function buy()
	{
        $id=I('get.mid',0);
        if(!$id)$this->error('参数错误');
        $info=DB::name('Ptpbc')->where('status', $id)->find();
        $info['quota']=explode(',',$info['quota']);
        $payments=explode(',',$info['payments']);
        $receives=explode(',',$info['receives']);
        $pay_list=array();
        foreach ($payments as $k=>$v){
            $pay_list[]=array('payment'=>$payments[$k],'receive'=>$receives[$k]);
        }
        $info['pay_list']=$pay_list;
        $this->assign('data',$info);
        return $this->fetch();
    }
	
    public function buy_action()
	{
        $id=I('post.id',0);
        if(!$id)$this->error('参数错误');
        $info=DB::name('Ptpbc')->where('status', $id)->find();
        $pay=explode(':',I('post.payment'));
        $data=array(
            'userid'=>userid(),
            'market'=>$info['market'],
            'price'=>$info['price']*intval(input('post.num')),
            'unit'=>$info['unit'],
            'num'=>I('post.num'),
            'merchant'=>$info['merchant'],
            'payment'=>$pay[0],
            'receive'=>$pay[1],
            'addtime'=>time(),
        );
        $res=DB::name('PtpbcLog')->insert($data);
        if($res){
            $this->redirect('buyinfo',array('id'=>$res));
        }else{
            $this->error('购买失败！');
        }
    }
	
    public function buylist()
	{
        return $this->fetch();
    }
	
    public function buyinfo()
	{
        $id=I('get.id');
        if(input('post.status')==1 && input('post.id')){
            $res=DB::name('PtpbcLog')->where('status', input('post.id'))->update(array('status'=>1));
        }
        $info=DB::name('PtpbcLog')->where('status', $id)->find();
        $this->assign('data',$info);
        return $this->fetch();
    }
}