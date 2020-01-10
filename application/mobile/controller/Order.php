<?php


namespace app\mobile\controller;


use think\Db;
use think\Exception;

class Order extends Mobile
{

    public function index()
    {
        if ($this->request->isPost()) {
            $list = Db::name('order')->where('sid', userid())->whereOr('bid', userid())->order('create_at desc')->paginate(10);
            echo json_encode([
                'code' => 0,
                'data' => $list,
                'msg' => '获取成功'
            ]);
            exit;
        }
        return $this->fetch();
    }

    public function orderInfo()
    {

    }

    public function passed($id)
    {
        $data = Db::name('order')->where('id', $id)->find();
        Db::startTrans();

        try {
            Db::name('user')->where('id', $data['sid'])->dec($data['coin'].'d', $data['num'])->inc('cny', $data['money']);
            Db::name('user')->where('id', $data['bid'])->inc($data['coin'], $data['num'])->dec('cnyd', $data['money']);
            Db::name('order')->where('id', $id)->update(['status' => 1]);
            Db::commit();
            return $this->success('交易成功');
        } catch (Exception $e) {
            Db::rollback();
            return $this->error('交易失败');
        }
    }

    public function refush($id)
    {
        $data = Db::name('order')->where('id', $id)->find();
        Db::startTrans();

        try {
            Db::name('user')->where('id', $data['sid'])->dec($data['coin'].'d', $data['num'])->inc($data['coin'], $data['num'])->update();
            Db::name('user')->where('id', $data('bid'))->inc('cny', $data['money'])->dec('cnyd', $data['money'])->update();
            Db::name('order')->where('id', $id)->update(['status' => 1]);
            Db::commit();
            return $this->success('取消成功');
        } catch (Exception $e) {
            Db::rollback();
            return $this->error('取消失败');
        }
    }
}