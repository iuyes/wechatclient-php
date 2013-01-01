<?php
define('TOKEN','YOUR_TOKEN');
require_once 'WechatClient.php';
class TestWechatListener extends WechatListener{

	function onFirst($textRequest){
	}
	function onText(TextRequest $textRequest){
		return new TextResponse($textRequest->fromUserName,$textRequest->toUserName,$textRequest->content);
	}
	function onLocation(LocationRequest $locationRequest){
		return new TextResponse($locationRequest->fromUserName,$locationRequest->toUserName,$locationRequest->location_X.",".$locationRequest->location_Y);
	}
	function onImage(ImageRequest $imageRequest){
		return new TextResponse($imageRequest->fromUserName,$imageRequest->toUserName,$imageRequest->picUrl);
	}
}

$wechatClient = new WechatClient();
$listener = new TestWechatListener();
$wechatClient->addListener($listener);
$wechatClient->start($GLOBALS["HTTP_RAW_POST_DATA"]);

