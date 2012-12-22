<?php

class TestWechatListener extends WechatListener{

	function onFirst($textRequest){
	}
	function onText($textRequest){
		return new TextResponse($textRequest->fromUserName,$textRequest->toUserName,$textRequest->content);
	}
	function onLocation($locationRequest){
	}
	function onImage($imageRequest){
	}
	function checkSignature(){
		return true;
	}

}

$wechatClient = new WechatClient();
$listener = new TestWechatListener();
$wechatClient->addListener($listener);
$wechatClient->start($GLOBALS["HTTP_RAW_POST_DATA"]);

