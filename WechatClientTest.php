<?php
require_once 'WechatClient.php';


class TextWechatListener extends WechatListener{

	function onText($textRequest){
		return new TextResponse($textRequest->fromUserName,$textRequest->toUserName,$textRequest->content);
	}

}

class NewsWechatListener extends WechatListener{

	function onText($textRequest){
		$items = [];
		for($i=0;$i<1;$i++){
			$item = new NewsItem();
			$item->title = "item$i";
			$item->description = "item$i desc";
			$item->picUrl = "http://google.com/$i";
			$item->url = "http://github.com/$i";
			$items[] = $item;
		}
		return new NewsResponse($textRequest->fromUserName,$textRequest->toUserName,$items);
	}

}

class WechatClientTest extends PHPUnit_Framework_TestCase{
	public $target = null;
	public function __construct(){
		$this->target = new WechatClient();
	}
	public function testTextResponse(){
		$listener = new TextWechatListener();
		$this->target->addListener($listener);
		$first = "
			 <xml>
			 <ToUserName><![CDATA[1]]></ToUserName>
			 <FromUserName><![CDATA[2]]></FromUserName>
			 <CreateTime>1348831860</CreateTime>
			 <MsgType><![CDATA[text]]></MsgType>
			 <Content><![CDATA[this is a test]]></Content>
			 </xml>
		";
		$xml = simplexml_load_string($first, 'SimpleXMLElement', LIBXML_NOCDATA);
		$tr = new TextRequest();
		$request = $tr->parse($xml);
		$response = $this->target->start($first,false);
		$this->assertEquals((string)$response->toUserName,(string)$request->fromUserName);
	}

	public function testNewsResponse(){
		$listener = new NewsWechatListener();
		$this->target->addListener($listener);
		$first = "
			 <xml>
			 <ToUserName><![CDATA[1]]></ToUserName>
			 <FromUserName><![CDATA[2]]></FromUserName>
			 <CreateTime>1348831860</CreateTime>
			 <MsgType><![CDATA[text]]></MsgType>
			 <Content><![CDATA[this is a test]]></Content>
			 </xml>
		";
		$xml = simplexml_load_string($first, 'SimpleXMLElement', LIBXML_NOCDATA);
		$request = $this->target->parseText($xml);
		$response = $this->target->start($first,false);
		$this->assertEquals((string)$response->toUserName,(string)$request->fromUserName);
	}
}