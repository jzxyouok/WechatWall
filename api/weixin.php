<?php
/**
 * 微信公众平台 PHP SDK 示例文件
 *
 * @author NetPuter <netputer@gmail.com>
 */
  require('wechat.php');
  //默认回复

  /**
   * 微信公众平台演示类
   */
  class MyWechat extends Wechat {
	
    /**
     * 用户关注时触发，回复「欢迎关注」
     *
     * @return void
     */
    protected  function welcome() {
        $xuanzezu = $this->xuanzezu;
        $weixin_name = $this->weixin_name;
        global $plugsc;
        $plugs=$plugsc->select('switch =1');
        $items = array(
			new NewsResponseItem("欢迎关注{$weixin_name}"),
			new NewsResponseItem('【上墙】发送【'.$xuanzezu[0].'任意内容】即可上墙'),
			new NewsResponseItem('【图片】直接发送图片即可上墙'),
		  );

     foreach($plugs as $plugin)  
         {
             $decripton=str_replace("~~[keyword]~~", $plugin['keyword'], $plugin['description']);
            array_push($items,new NewsResponseItem($decripton));
            }
        
		  return $items;
		}
	 /**
     * 验证码回复
     *
     * @return void
     */
	protected function VERYF($weixin_name,$veryfs) 
	{ 
	    $conf = $this->sqlselect('weibo_config','1','','id');
         include('../config.php');
		 if(empty($AppID)){$AppID = "wxf5f5d8dbcb1d7f2f";}
		 $state = Web_ROOT.",".$this->getRequest('fromusername').",".$AppID.",".strrev($AppSecret);
	$from=$this->getRequest('fromusername');
      $items = array(
        new NewsResponseItem("欢迎使用{$weixin_name}","点击获取您的信息，进行开场签到\n",Web_ROOT."/files/images/qiandao.jpg","https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxc2686f865fcf8f02&redirect_uri=http://kirisamenana.com/wall100/oauth2.php?userid=$from&response_type=code&scope=snsapi_userinfo&state=1"));
	    Wechat::$noendtail=0;
		return $items;  
	} 
    /**
     * 用户关注时触发，回复
     *
     * @return void
     */
    protected function onSubscribe() {
        $weixin_name = $this->weixin_name;

		$from = $this->getRequest('fromusername');
		        $check = $this->sqlselect('flag',$from,"row");
		$this->check=$check;

			$reply = $this->resetflag($weixin_name,$from);
		    if(Wechat::$noendtail){
            	$reply = $this->endtail($reply);
		    }
      $this->responseNews($reply);
    }

    /**
     * 用户已关注时,扫描带参数二维码时触发，回复二维码的EventKey (测试帐号似乎不能触发)
     *
     * @return void
     */
    protected function onScan() {
      $this->responseText('二维码的EventKey：' . $this->getRequest('EventKey'));
    }

    /**
     * 用户取消关注时触发
     *
     * @return void
     */
    protected function onUnsubscribe() {
       //$this->deleteflag($this->getRequest('fromusername'));
      // 「悄悄的我走了，正如我悄悄的来；我挥一挥衣袖，不带走一片云彩。」
    }
    /**
     * 收到图片消息时触发，回复由收到的图片组成的图文消息
     *
     * @return void
     */
    protected function onImage() {
        $xuanzezu = $this->xuanzezu;
        $weixin_name = $this->weixin_name;
		$picurl = makeimg($this->getRequest('picurl'),$xuanzezu[31]);
		$this->request['picurl']=$picurl;
		$from = $this->getRequest('fromusername');
		$time = $this->getRequest('createtime');
        $check = $this->sqlselect('flag',$from,"row");
        if (!$check[1]) {
				$reply = $this->resetflag($weixin_name,$from);
        }
        
		if($check[2] == '2'){
				$reply =$this->replaypic($from,$time,$picurl);
				
			}else{
			global $plugsc;
            $plugs=$plugsc->find("switch=1 and status='".$check[2]."'");
		        if(!empty($plugs)){
		            
                    $classname=$plugs['name']."_plug";
                    $replyc=new $classname($xuanzezu,$this->check,$this->request);
                    $method=$plugs['name'].'_replay';
                    $reply=$replyc->$method();
		        }else{
    			$q = $this->sqlselect('flag',$from);
    			$veryfs=$q['verify'];
    				$reply=$this->VERYF($weixin_name,$veryfs);
				
		        }
				}
		    if($xuanzezu[18] && Wechat::$noendtail){
		        $end = new NewsResponseItem('回复【退出】退出本活动系统','');
		        array_push($reply,$end);
		    }
		    if(Wechat::$noendtail){
	$reply = $this->endtail($reply);
		    }
      $this->responseNews($reply);
    }

    /**
     * 收到文本消息时触发，回复收到的文本消息内容
     *
     * @return void
     */
    protected function onText() {
        $xuanzezu = $this->xuanzezu;
        $weixin_name = $this->weixin_name;
		
		$content = $this->getRequest('content');
		$from = $this->getRequest('fromusername');
		$time = $this->getRequest('createtime');
		$content = $this->biaoqing($content);
		
        $check = $this->sqlselect('flag',$from,"row");
		$this->check=$check;
        //if($xuanzezu[18]){
            if($content == '退出'){
		        $this->deleteflag($from);
				$reply = array(new NewsResponseItem('您已成功退出本活动系统。',''));
				$check[2] = '-10';
			}
       // }
		if($content == '重置'){
				$reply = $this->resetflag($weixin_name,$from);
				$check[2] = '-10';
			}
		if($content == '查询'){
		        $this->findcj($check);
			}
        if (!$check[1]) {
				$reply = $this->resetflag($weixin_name,$from);
        }
			//判断用户是否没有录入信息
		if ($check[2] == '1') {
			$veryfs='验证';
                $reply = $this->VERYF($weixin_name,$veryfs);
		}
		elseif($check[2] == '2'){
				$reply =$this->replay($from,$time,$content);
			
			}else{
            global $plugsc;
            $plugs=$plugsc->find("switch=1 and status='".$check[2]."'");
		        if(!empty($plugs)){
		            
                    $classname=$plugs['name']."_plug";
                    $replyc=new $classname($xuanzezu,$this->check,$this->request);
                    $method=$plugs['name'].'_replay';
                    $reply=$replyc->$method();
		        }
			}

		    if($xuanzezu[18] && Wechat::$noendtail){
		        $end = new NewsResponseItem('回复【退出】退出本活动系统','');
		        array_push($reply,$end);
		    }
			if($check[2] != '88' && Wechat::$noendtail){
			$reply = $this->endtail($reply);
			}
      $this->responseNews($reply);
    }
	 /**
     * 信息录入
     *
     * @return void
     */
	protected function monigetinfo($from,$time,$content,$veryfs) 
	{ 
		include('function.php');
		$isluru =  isluru($from,$time,$content);
		if($isluru == 1)
		{
		$reply = array(
			new NewsResponseItem("获取失败"),
			new NewsResponseItem("抱歉，服务器繁忙，您的信息获取失败，请重新回复【{$veryfs}】！"),
		 );
		}else{
		$sql_check = "UPDATE  `weixin_flag` SET `flag` = '2'  WHERE  `openid` =  '{$from}';";
		mysql_query($sql_check);
		//该地址为1.php上传后的地址
		$reply = $this->welcome();
		}
		return $reply;
	} 
	 /**
     * 信息录入之后的回复函数
     *
     * @return void
     */
	protected function replay($from,$time,$content) 
	{ 
        $xuanzezu = $this->xuanzezu;
        $weixin_name = $this->weixin_name;
        
		if($content == '帮助'){
				$reply = $this->welcome();
				$check[2] = '-10';
				return $reply;
			}
            /*话题判断函数开始*/
            function startsWith($haystack, $needle, $case = false)
            {
                if ($case) {
                    return strcmp(substr($haystack, 0, strlen($needle)), $needle) === 0;
                }
                return strcasecmp(substr($haystack, 0, strlen($needle)), $needle) === 0;
            }
            /*话题判断函数结束*/
            /*屏蔽词判断*/
            function blackword($content,$xuanzezu)
            {   
                if (!empty($xuanzezu[26])) {
                   $blackarr= explode(",",$xuanzezu[26]);
                   
                   foreach ($blackarr as $v) {
                       if(strstr($content,$v)){
                            return 1;
                       }
                   }
                   return 0;
                }
            }
            global $plugsc;
            $plugs=$plugsc->find("switch=1 and needword = 1 and keyword='$content'");
            if(!empty($plugs)){
                $classname=$plugs['name']."_plug";
                $replyc=new $classname($xuanzezu,$this->check,$this->request);
                $method=$plugs['name'].'_replay';
                $reply=$replyc->$method();
            }else{
				if(startsWith($content, $xuanzezu[0])){
					if(blackword($content,$xuanzezu)){
					  $this->responseText('对不起，您发送的内容中有屏蔽的词语，上墙失败，请重新编辑后发送！');
					}
                    $q = $this->sqlselect('flag',$from);
				if($this->check[4] != ''){
						$reply = $this->writeinwall($content,$time,$q);
					}else{
						$reply = $this->unkownerror();
						}
				}else{
					$reply = $this->welcome();
					}
			}
			return $reply;
	} 
	 /**
     * 写入wall函数
     *
     * @return void
     */
	protected function writeinwall($content,$time,$q,$type='') 
	{ 
        $xuanzezu = $this->xuanzezu;
        $weixin_name = $this->weixin_name;
		include('function.php');
		
		//时间间隔判断
		if (($time-$q['datetime'])<$xuanzezu[11]){
			$reply = array(
					new NewsResponseItem("休息一下"),
					new NewsResponseItem("您发送的太快了，墙上还有位置慢慢来~"),
					 );
			return $reply;
		}
			 //以下为写入wall
		if($type == 'pic'){
                $sql = "INSERT INTO `weixin_wall` (`id`,`messageid`,`fakeid`,`num`,`content`,`nickname`,`avatar`,`ret`,`fromtype`,`image`,`datetime`) VALUES (NULL,'0','{$q['fakeid']} ','-1','此消息为图片！','{$q['nickname']}','{$q['avatar']}','0','weixin','{$content}','{$time}')";
			}else{
		$content=bin2hex($content);
				$sql = "INSERT INTO `weixin_wall` (`id`,`messageid`,`fakeid`,`num`,`content`,`nickname`,`avatar`,`ret`,`fromtype`,`datetime`) VALUES (NULL,'0','{$q['fakeid']}','-1','{$content}','{$q['nickname']}','{$q['avatar']}','0','weixin','{$time}')";
		}
                    mysql_query($sql);
					$q['nickname']=pack('H*',$q['nickname']);
					$usernickname = new NewsResponseItem("亲爱的【{$q['nickname']}】");
				  if($type == 'pic'){
					  $usernickname = new NewsResponseItem("亲爱的【{$q['nickname']}】","这是图片消息",$content,$content);
					  }
					  
					   if ($xuanzezu[9] == 0) {
										$cid = mysql_query('select id from `weixin_wall` WHERE id =(select max(id) from `weixin_wall`)');
										$row = mysql_fetch_array($cid);
										$maxid = $row['id'];
										   doshenhe($maxid);
									$isauto = new NewsResponseItem("您的消息已经成功发送，请多关注墙上内容！");
									}else{
									$isauto = new NewsResponseItem("您的消息已经成功发送，等待管理员审核后即可上墙，请多关注墙上内容！");
									}
						$reply = array(
							
						 );
						 array_unshift($reply,$usernickname,$isauto);
		return $reply;
	} 
    /**
     * 收到地理位置消息时触发，回复收到的地理位置
     *
     * @return void
     */
    protected function onLocation() {
      //$num = 1 / 0;
      // 故意触发错误，用于演示调试功能

      $this->responseText();
    }

    /**
     * 收到链接消息时触发，回复收到的链接地址
     *
     * @return void
     */
    protected function onLink() {
      $this->responseText('收到了链接：' . $this->getRequest('url'));
    }

    /**
     * 收到语音消息时触发，回复语音识别结果(需要开通语音识别功能)
     *
     * @return void
     */
    protected function onVoice() {
      $this->responseText('请发送文字信息进行上墙');
    }

    /**
     * 收到自定义菜单消息时触发，回复菜单的EventKey
     *
     * @return void
     */
    protected function onClick() {
        $eventkey=$this->getRequest('eventkey');
        $this->request['content']=$eventkey;
          $xuanzezu=$this->xuanzezu;
        $sql_check = "UPDATE  `weixin_flag` SET `flag` = '2'  WHERE  `flag` >  2;";
        mysql_query($sql_check);
        global $plugsc;
            $plugs=$plugsc->find("switch=1 and needword = 1 and keyword='$eventkey'");
            if(!empty($plugs) || $eventkey=='帮助' ||$eventkey=='上墙'){
                $this->onText();
            }else{
                    if($xuanzezu[18]){
                    exit($this->sendxml($GLOBALS['HTTP_RAW_POST_DATA'],$xuanzezu[20],$xuanzezu[21]));
                    }
            }
      $this->responseNews($reply);
    }
    /**
     * 收到未知类型消息时触发，回复收到的消息类型
     *
     * @return void
     */
    protected function onUnknown() {
      $this->responseText('收到了未知类型消息：' . $this->getRequest('msgtype'));
    }


  }
  
function makeimg($picurl,$webpath){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $picurl);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1521.3 Safari/537.36");
		$output = curl_exec($ch);
      curl_close($ch);
    if(!empty($_SERVER['HTTP_APPNAME'])){
        $weburl=saeup($output);
    }else{
        $weburl=commenup($output,$webpath);
    }
    return $weburl;
}
function commenup($write,$webpath){
		$filename ="pic_".time().".jpg";//要生成的图片名字
		$file = fopen("../img/pic/".$filename,"w");//打开文件准备写入
		fwrite($file,$write);//写入
		fclose($file);//关闭
		$imgurl =$webpath."/img/pic/".$filename;
		return $imgurl;
}
function saeup($write){
		$filename ="pic_".time().".jpg";//要生成的图片名字
		//以下为sae的sotrage
		$domain = 'img';
		$s = new SaeStorage();
		$imgurl=$s->write($domain, $filename ,$write);
		return $imgurl;
}
  $wechat = new MyWechat('dianplu','weixin', false );
  $wechat->run();
