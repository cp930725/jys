<?php
return array(
	'TMPL_PARSE_STRING' => array('__UPLOAD__' => __ROOT__ . '/Upload', '__PUBLIC__' => __ROOT__ . '/Public', '__IMG__' => __ROOT__ . '/Public/' . $this->request->module() . '/images', '__CSS__' => __ROOT__ . '/Public/' . $this->request->module() . '/css', '__JS__' => __ROOT__ . '/Public/' . $this->request->module() . '/js'),
);