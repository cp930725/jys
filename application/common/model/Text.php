<?php
namespace app\common\model;

use think\Model;
use think\Db;

class Text extends Model
{
	protected $keyS = 'Text';

	public function get_content($name = NULL)
	{
		if (empty($name)) {
			return null;
		}

		$get_content = (config('app.develop') ? null : cache('get_content' . $this->keyS . $name));

		if (!$get_content) {
			$this->check_field($name);
			$get_content = Db::name('Text')->where('name', $name)->where('status', 1)->value('content');
			cache('get_content' . $this->keyS . $name, $get_content);
		}

		return $get_content;
	}
	
	public function get_url($name = NULL)
	{
		if (empty($name)) {
			return null;
		}

		$get_url = (config('app.develop') ? null : cache("get_url" . $this->keyS . $name));

		if (!empty($get_content)) {
			$this->check_field($name);
			$get_url = Db::name("Text")->where(array("name" => $name, "status" => 1))->value("url");
			cache("get_url" . $this->keyS . $name, $get_url);
		}

		return $get_url;
	}


	public function check_field($name = NULL)
	{
		if (!Db::name('Text')->where('name', $name)->find()) {
			Db::name('Text')->insert(array('name' => $name, 'content' => '<span style="color:#0096E0;line-height:21px;background-color:#FFFFFF;"><span>请在后台修改此处内容</span></span><span style="color:#0096E0;line-height:21px;font-family:\'Microsoft Yahei\', \'Sim sun\', tahoma, \'Helvetica,Neue\', Helvetica, STHeiTi, Arial, sans-serif;background-color:#FFFFFF;">,<span style="color:#EE33EE;">详细信息</span></span>', 'status' => 1, 'addtime' => time()));
		}
	}
}

?>