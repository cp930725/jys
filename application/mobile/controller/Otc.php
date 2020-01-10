<?php


namespace app\mobile\controller;


use think\Db;
use think\Exception;

class Otc extends Mobile
{
    /*protected function _initialize(){
        parent::_initialize();
        $allow_action=array("index","movepay","mycz","myczQueren","ecpss");
        if(!in_array(ACTION_NAME,$allow_action)){
            $this->error("非法操作！");
        }
    }*/
    public function index()
    {
        return $this->fetch();
    }

    public function sell()
    {
        $coin = Db::name('coin')->where('status', 1)->field('name, title')->select();
        $this->assign('coin', $coin);
        return $this->fetch();
    }

    public function sellPost()
    {
        $data = $this->request->param();
        $money = Db::name('user_coin')->where('userid', userid())->value($data['coin']);
        /*if ($money < $data['money']) {
            return $this->error('余额不足,出售失败');
        }*/

        $data['type'] = 0;
        $data['create_at'] = time();
        $res = Db::name('otc')->insert($data);
        if ($res) {
            return $this->success('出售成功');
        } else {
            return $this->error('出售失败');
        }
    }

    public function getSell($page, $limit)
    {
        $list = Db::name('otc')->alias('o')
            ->join('user u', 'u.id = o.userid')
            ->field('u.username, o.*')
            ->where('o.type', 0)
            ->order('o.create_at desc')
            ->limit(($page-1) * $limit, $limit)
            ->select();

        echo json_encode([
            'code' => 0,
            'data' => $list,
            'msg' => '数据正常'
        ]);
        exit;
    }



    public function buy($id)
    {
        $data = Db::name('otc')->where('id',$id)->find();
        $this->assign('data', $data);
        return $this->fetch();
    }

    public function buyPost()
    {
        $data = $this->request->param();
        $data['oid'] = date('YmdHis').mt_rand(1000,9999);
        $data['bid'] = userid();
        $data['status'] = 0;
        $data['create_at'] = time();
        Db::startTrans();
        try {
            Db::name('order')->insert($data);
            Db::name('user')->where('id', $data['sid'])->inc($data['coin'].'d', $data['num'])->dec($data['coin'], $data['num'])->update();
            Db::name('user')->where('id', userid())->dec('cny', $data['money'])->inc('cnyd', $data['money'])->update();
            Db::commit();
            return $this->success('交易成功, 请等待出售方确认');
        } catch (Exception $e) {
            return $this->error('交易失败');
        }
    }

    public function getBuy()
    {
        
    }
}