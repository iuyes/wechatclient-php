<?php
define('TOKEN','YOUR_TOKEN');
require_once 'WechatClient.php';
class TestWechatListener extends WechatListener{

	function onFirst($textRequest){ //用户第一次关注时
	}
	function onText(TextRequest $textRequest){ //用户发送文本内容时
		return new TextResponse($textRequest->fromUserName,$textRequest->toUserName,$textRequest->content);
	}
	function onLocation(LocationRequest $locationRequest){ //用户发送位置信息时
		return new TextResponse($locationRequest->fromUserName,$locationRequest->toUserName,$locationRequest->location_X.",".$locationRequest->location_Y);
	}
	function onImage(ImageRequest $imageRequest){ //用户发送图片信息时
		return new TextResponse($imageRequest->fromUserName,$imageRequest->toUserName,$imageRequest->picUrl);
	}
}

$wechatClient = new WechatClient();
$listener = new TestWechatListener();
$wechatClient->addListener($listener);
$wechatClient->start($GLOBALS["HTTP_RAW_POST_DATA"]);

