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
		if($this->isValidRequest()){ //验证微信接口
			echo $_GET['echostr'];
			return;
		}
		if($this->checkSignature()){
			if (!empty($postStr)) {
				$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
				if($postObj){
					$msgType = $postObj->MsgType;
					$response = null;
					$reqclass = ucfirst($msgType).'Request';
					if(!class_exists($reqclass)) return;
					$request = new $reqclass();
					$listener = "on".ucfirst($msgType);
					if(method_exists($this->listener,$listener))
						$response = $this->listener->$listener($request->parse($postObj));
					if($response) {
						if($print) echo (string)$response;
						else return $response;
					}
				}
			}
		}
	}

	/**
	 * 检查签名信息
	 */
	public function checkSignature(){
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];

		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}

	private function isValidRequest(){
		return isset($_GET['echostr']);
	}

	public function addListener($listener){
		$this->listener = $listener;
	}
}

abstract class WechatRequest{
	public $toUserName,$fromUserName,$createTime,$msgType,$msgId;
	public abstract function parse($xml);
}

/**
 * 文本消息
 */
class TextRequest extends WechatRequest{
	public $msgType = 'text';
	public $content;
	public function parse($xml){
		$this->fromUserName = $xml->FromUserName[0];
		$this->toUserName = $xml->ToUserName[0];
		$this->createTime = $xml->CreateTime;
		$this->msgType = $xml->MsgType;
		$this->content = $xml->Content;
		$this->msgId = $xml->MsgId;
		return $this;
	}
}

/**
 * 图片消息
 */
class ImageRequest extends WechatRequest{
	public $msgType = 'image';
	public $picUrl;
	public function parse($xml){
		$this->fromUserName = $xml->FromUserName[0];
		$this->toUserName = $xml->ToUserName[0];
		$this->createTime = $xml->CreateTime;
		$this->msgType = $xml->MsgType;
		$this->picUrl = $xml->PicUrl;
		$this->msgId = $xml->MsgId;
		return $this;
	}
}

/**
 * 语音消息
 */
class VoiceRequest extends WechatRequest{
	public $msgType = 'voice';
	public $mediaId,$format,$recognition;
	public function parse($xml){
		$this->fromUserName = $xml->FromUserName[0];
		$this->toUserName = $xml->ToUserName[0];
		$this->createTime = $xml->CreateTime;
		$this->msgType = $xml->MsgType;
		$this->mediaId = $xml->MediaId;
		$this->format = $xml->Format;
		if($xml->Recognition){//语音识别
			$this->recognition = $xml->Recognition;
		}
		$this->msgId = $xml->MsgId;
		return $this;
	}
}

/**
 * 视频消息
 */
class VideoRequest extends WechatRequest{
	public $msgType = 'video';
	public $mediaId,$thumbMediaId;
	public function parse($xml){
		$this->fromUserName = $xml->FromUserName[0];
		$this->toUserName = $xml->ToUserName[0];
		$this->createTime = $xml->CreateTime;
		$this->msgType = $xml->MsgType;
		$this->mediaId = $xml->MediaId;
		$this->thumbMediaId = $xml->ThumbMediaId;
		$this->msgId = $xml->MsgId;
		return $this;
	}
}

/**
 * 地理位置消息
 */
class LocationRequest extends WechatRequest{
	public $msgType = 'location';
	public $location_X,$location_Y,$scale,$label;
	public function parse($xml){
		$this->fromUserName = $xml->FromUserName[0];
		$this->toUserName = $xml->ToUserName[0];
		$this->createTime = $xml->CreateTime;
		$this->msgType = $xml->MsgType;
		$this->location_X = $xml->Location_X;
		$this->location_Y = $xml->Location_Y;
		$this->label = $xml->Label;
		$this->scale = $xml->Scale;
		$this->msgId = $xml->MsgId;
		return $this;
	}
}

/**
 * 链接消息
 */
class LinkRequest extends WechatRequest{
	public $msgType = 'link';
	public $title,$description,$url;
	public function parse($xml){
		$this->fromUserName = $xml->FromUserName[0];
		$this->toUserName = $xml->ToUserName[0];
		$this->createTime = $xml->CreateTime;
		$this->msgType = $xml->MsgType;
		$this->title = $xml->Title;
		$this->description = $xml->Description;
		$this->url = $xml->Url;
		$this->msgId = $xml->MsgId;
		return $this;
	}
}

/**
 * 事件推送消息
 */
class EventRequest extends WechatRequest{
	public $msgType = 'event';
	public $event,$eventKey;
	public function parse($xml){
		$this->fromUserName = $xml->FromUserName[0];
		$this->toUserName = $xml->ToUserName[0];
		$this->createTime = $xml->CreateTime;
		$this->msgType = $xml->MsgType;
		$this->event = $xml->Event;
		$this->eventKey = $xml->EventKey;
		return $this;
	}
}


class WechatResponse {
	public $toUserName,$fromUserName,$createTime;
}

/**
 * 回复文本消息
 */
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
/**
 * 回复图片消息
 */
class ImageResponse extends WechatResponse{
	private $template = "
		<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[image]]></MsgType>
			<Image>
				<MediaId><![CDATA[%s]]></MediaId>
			</Image>
		</xml>";
	public $mediaId;
	public function __construct($toUserName,$fromUserName,$mediaId){
		$this->toUserName = $toUserName;
		$this->fromUserName = $fromUserName;
		$this->createTime = time();
		$this->mediaId = $mediaId;
	}
	public function __toString(){
		$responseStr = sprintf($this->template,$this->toUserName,$this->fromUserName,$this->createTime,$this->$mediaId);
		return $responseStr;
	}
}
/**
 * 回复语音消息
 */
class VoiceResponse extends WechatResponse{
	private $template = "
		<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[voice]]></MsgType>
			<Voice>
				<MediaId><![CDATA[%s]]></MediaId>
			</Voice>
		</xml>";
	public $mediaId;
	public function __construct($toUserName,$fromUserName,$mediaId){
		$this->toUserName = $toUserName;
		$this->fromUserName = $fromUserName;
		$this->createTime = time();
		$this->mediaId = $mediaId;
	}
	public function __toString(){
		$responseStr = sprintf($this->template,$this->toUserName,$this->fromUserName,$this->createTime,$this->$mediaId);
		return $responseStr;
	}
}
/**
 * 回复视频消息
 */
class VideoResponse extends WechatResponse{
	private $template = "
		<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[video]]></MsgType>
			<Video>
				<MediaId><![CDATA[%s]]></MediaId>
				<ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
			</Video>
		</xml>";
	public $mediaId,$thumbMediaId;
	public function __construct($toUserName,$fromUserName,$mediaId,$thumbMediaId){
		$this->toUserName = $toUserName;
		$this->fromUserName = $fromUserName;
		$this->createTime = time();
		$this->mediaId = $mediaId;
		$this->thumbMediaId = $thumbMediaId;
	}
	public function __toString(){
		$responseStr = sprintf($this->template,$this->toUserName,$this->fromUserName,$this->createTime,$this->$mediaId,$this->$thumbMediaId);
		return $responseStr;
	}
}

/**
 * 回复音乐消息
 */
class MusicResponse extends WechatResponse{
	private $template = "
		<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[music]]></MsgType>
			<Music>
				<Title><![CDATA[%s]]></Title>
				<Description><![CDATA[%s]]></Description>
				<MusicUrl><![CDATA[%s]]></MusicUrl>
				<HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
				<ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
			</Music>
		</xml>";
	public $title,$description,$musicUrl,$hqMusicUrl,$thumbMediaId;
	public function __construct($toUserName,$fromUserName,$title,$description,$musicUrl,$hqMusicUrl,$thumbMediaId){
		$this->toUserName = $toUserName;
		$this->fromUserName = $fromUserName;
		$this->createTime = time();
		$this->title = $title;
		$this->description = $description;
		$this->musicUrl = $musicUrl;
		$this->hqMusicUrl = $hqMusicUrl;
		$this->thumbMediaId = $thumbMediaId;
	}
	public function __toString(){
		$responseStr = sprintf($this->template,$this->toUserName,$this->fromUserName,$this->createTime,$this->title,$this->description,$this->musicUrl,$this->hqMusicUrl,$this->thumbMediaId);
		return $responseStr;
	}
}
/**
 * 回复图文消息
 */
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
	function onText(TextRequest $textRequest){}
	function onLocation(LocationRequest $locationRequest){}
	function onImage(ImageRequest $imageRequest){}
	function onLink(LinkRequest $linkRequest){}
	function onEvent(EventRequest $eventRequest){}
	function onVoice(VoiceRequest $eventRequest){}
}