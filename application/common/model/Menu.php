<?php
namespace app\common\model;

use think\Model;

class Menu extends Model
{
	protected $_validate = array(
		array('url', 'require', 'url必须填写')
		);

	public function getPath($id)
	{
		$path = array();
		$nav = $this->where('id=' . $id)->field('id,pid,title')->find();
		$path[] = $nav;

		if (0 < $nav['pid']) {
			$path = array_merge($this->getPath($nav['pid']), $path);
		}

		return $path;
	}
}

?>