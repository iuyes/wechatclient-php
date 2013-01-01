<?php
/**
 * A simple Wechat API wrapper
 * @author ZhangV
 * @copyright Copyright (c) 2012
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
class WechatClient{
	public $listener;
	public function start($postStr,$print = true){
		if($this->isValidRequest($postStr)){
			echo $_GET['echostr'];
			return;
		}
		if($this->listener->checkSignature()){
			if (!empty($postStr)) {
				$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
				if($postObj){
					$msgType = $postObj->MsgType;
					$response = null;

					if($msgType == 'text') {
						$textRequest = $this->parseText($postObj);
						if($textRequest->content == 'Hello2BizUser') $response = $this->listener->onFirst($textRequest);
						else $response = $this->listener->onText($textRequest);
					}
					if($msgType == 'location') $response = $this->listener->onLocation($this->parseLocation($postObj));
					if($msgType == 'image') $response = $this->listener->onImage($this->parseImage($postObj));
					if($response) {
						if($print) echo (string)$response;
						else return $response;
					}
				}
			}
		}
	}

	private function isValidRequest(){
		return isset($_GET['echostr']);
	}

	public function addListener($listener){
		$this->listener = $listener;
	}

	public function parseText($xml){
		$request = new TextRequest();
		$request->fromUserName = $xml->FromUserName[0];
		$request->toUserName = $xml->ToUserName[0];
		$request->createTime = $xml->CreateTime;
		$request->msgType = $xml->MsgType;
		$request->content = $xml->Content;
		return $request;
	}
	public function parseLocation($xml){
		$request = new LocationRequest();
		$request->fromUserName = $xml->FromUserName[0];
		$request->toUserName = $xml->ToUserName[0];
		$request->createTime = $xml->CreateTime;
		$request->msgType = $xml->MsgType;
		$request->location_X = $xml->Location_X;
		$request->location_Y = $xml->Location_Y;
		$request->label = $xml->Label;
		$request->scale = $xml->Scale;
		return $request;
	}
	public function parseImage($xml){
		$request = new ImageRequest();
		$request->fromUserName = $xml->FromUserName[0];
		$request->toUserName = $xml->ToUserName[0];
		$request->createTime = $xml->CreateTime;
		$request->msgType = $xml->MsgType;
		$request->picUrl = $xml->PicUrl;
		return $request;
	}
}

class WechatRequest{
	public $toUserName,$fromUserName,$createTime,$msgType;
}
class TextRequest extends WechatRequest{
	public $msgType = 'text';
	public $content;
}

class LocationRequest extends WechatRequest{
	public $msgType = 'location';
	public $location_X,$location_Y,$scale,$label;
}
class ImageRequest extends WechatRequest{
	public $msgType = 'image';
	public $picUrl;
}

class WechatResponse {
	public $toUserName,$fromUserName,$createTime;
}
class TextResponse extends WechatResponse{
	private $template = "
		<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[text]]></MsgType>
			<Content><![CDATA[%s]]></Content>
			<FuncFlag>0<FuncFlag>
		</xml>";
	public $content;
	public function __construct($toUserName,$fromUserName,$content){
		$this->toUserName = $toUserName;
		$this->fromUserName = $fromUserName;
		$this->createTime = time();
		$this->content = $content;
	}
	public function __toString(){
		$responseStr = sprintf($this->template,$this->toUserName,$this->fromUserName,$this->createTime,$this->content);
		return $responseStr;
	}
}

class NewsResponse extends WechatResponse{
	private $template = "
		<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[news]]></MsgType>
			<Content><![CDATA[]]></Content>
			%s
			<FuncFlag>0<FuncFlag>
		</xml>";
	private $itemTemplate = "
		 <item>
		 <Title><![CDATA[%s]]></Title>
		 <Description><![CDATA[%s]]></Description>
		 <PicUrl><![CDATA[%s]]></PicUrl>
		 <Url><![CDATA[%s]]></Url>
		 </item>
	";
	private $items = array();
	public function __construct($toUserName,$fromUserName,$items){
		$this->toUserName = $toUserName;
		$this->fromUserName = $fromUserName;
		$this->createTime = time();
		$this->items = $items;
	}
	public function __toString(){
		$itemCount = count($this->items);
		$str = "<ArticleCount>$itemCount</ArticleCount><Articles>";
		foreach($this->items as $item){
			$itemStr = sprintf($this->itemTemplate,$item->title,$item->description,$item->picUrl,$item->url);
			$str .= $itemStr;
		}
		$str .= "</Articles>";
		$responseStr = sprintf($this->template,$this->toUserName,$this->fromUserName,$this->createTime,$str);
		return $responseStr;
	}
}

class NewsItem {
	public $title,$description,$picUrl,$url;
	public function __construction($title,$description,$picUrl,$url){
		$this->title = $title;
		$this->description = $description;
		$this->picUrl = $picUrl;
		$this->url = $url;
	}
}

abstract class WechatListener{

	protected function checkSignature(){
		$nonce = $_GET['nonce'];
		$timestamp = $_GET['timestamp'];
		$signature = $_GET['signature'];
		$sha1 = sha1($nonce.$timestamp.$this->token);
		return $sha1 == $signature;
	}
	protected function onFirst(TextRequest $textRequest){
		return new TextResponse($textRequest->fromUserName,$textRequest->toUserName,time(),'Hi');
	}
	abstract function onText(TextRequest $textRequest);
	abstract function onLocation(LocationRequest $locationRequest);
	abstract function onImage(ImageRequest $imageRequest);
}