<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;


/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events{

    public static $db = null;

    public static function onWorkerStart($businessWorker){
        $config = require_once 'config.php';
        self::$db = new Workerman\MySQL\Connection($config['host'], $config['port'], $config['username'], $config['passwd'], $config['dbname']);
    }

   /**
    * 当客户端发来消息时触发
    * @param int $client_id 连接id
    * @param mixed $message 具体消息
    */
   public static function onMessage($client_id, $message) {

       // debug
       //echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id session:".json_encode($_SESSION)." onMessage:".$message."\n";

       // 解析数据,  数组格式
       $message_data = json_decode($message, true);

       if(!$message_data) return ;

       // 根据类型执行不同的逻辑
       switch($message_data['type']) {

           //登录操作
           case 'login' :

                //房间不合法
                if(!isset($message_data['room_id'])) {
                   throw new Exception("房间号{$message_data['room_id']}未设置. 客户端ip:{$_SERVER['REMOTE_ADDR']} \$message:$message");
                }

                // session维护登录状态信息
                $room_id = $message_data['room_id'];
                $client_logo = $message_data['client_logo'];
                $client_name = htmlspecialchars($message_data['client_name']);

                $_SESSION['room_id'] = $room_id;
                $_SESSION['client_name'] = $client_name;
                $_SESSION['client_logo'] = $client_logo;

                //连接标识,  1代表大厅, 2代表棋桌
                $_SESSION['is_room'] = 1;

               //数据库保存连接
               $insert_id = self::$db->insert('client')->cols([
                   'client_id'    =>    $client_id,
                   'client_name'  =>    $client_name,
                   'client_logo'  =>    $client_logo,
               ])->query();

                //获取当前房间在线用户列表
                $count = Gateway::getClientCountByGroup($room_id) + 1;          //人数
                $clients_list = Gateway::getClientSessionsByGroup($room_id);    //列表
                foreach($clients_list as $tmp_client_id=>$item) {
                   $clients_list[$tmp_client_id] = $item['client_name'];
                }
                $clients_list[$client_id] = $client_name;


                //当前房间正在进行游戏的玩家
               $white = self::$db
                   ->select("client_id, client_logo, client_name, desk_id,  'white' as color")
                   ->from('client')
                   ->innerJoin('desk','desk.white_id = client.game_id')
                   ->where("room_id = :room_id AND game_id <> '' AND white_id <> ''")
                   ->bindValues(['room_id' => $room_id])
                   ->query();
               $black = self::$db
                   ->select("client_id, client_logo, client_name, desk_id,  'black' as color")
                   ->from('client')
                   ->innerJoin('desk','desk.black_id = client.game_id')
                   ->where("room_id = :room_id AND game_id <> '' AND black_id <> ''")
                   ->bindValues(['room_id' => $room_id])
                   ->query();
                $desk_status = array_merge($white, $black);

                //debug
                //var_dump($desk_status);


                //大厅广播 xxx 进入房间
                $message = [
                    'type'          =>      'login',
                    'client_id'     =>      $client_id,
                    'client_logo'   =>      $client_logo,
                    'client_name'   =>  $client_name,
                    'time'          =>  date('Y-m-d H:i:s'),
                    'count'         =>      $count,
                ];
               Gateway::sendToGroup($room_id, json_encode($message));


               Gateway::joinGroup($client_id, $room_id);
               //发送在线用户列表
               $message['client_list'] = $clients_list;
               $message['count'] = $count;
               //发送房间状态
               $message['desk_status'] = $desk_status;
               return Gateway::sendToCurrentClient(json_encode($message));

           //有玩家请求坐下
           case 'sit' :

               // 非法请求
               if(!isset($_SESSION['room_id'])) {
                   throw new \Exception("非法操作. 客户端ip:{$_SERVER['REMOTE_ADDR']}");
               }
               // 玩家空闲  1  玩家已加入游戏  2
               //玩家已经在游戏状态
               $desk = self::$db->select('game_id')->from('client')->where('client_id= :client_id')->bindValues(array('client_id'=> $client_id))->single();
               //var_dump($desk);
               if($desk){
                   $message = [
                       'type'=>'sit',
                       'status' =>  2,
                       'content'    =>  '您当前已经在'.$_SESSION['desk_id'].'号桌中游戏',
                   ];
                   return Gateway::sendToCurrentClient(json_encode($message));
               }

               $room_id = $_SESSION['room_id'];
               $client_name = $_SESSION['client_name'];
               $_SESSION['desk_id'] = $message_data['desk_id'];
               $_SESSION['color']   =    $message_data['color'];

               //分配座位, 通知客户端打开新页面
               Gateway::sendToCurrentClient(json_encode([
                   'type'       =>  'open',
                   'url'        =>  'client.php?client_id='.$client_id.'&room_id='.$room_id.'&color='.$message_data['color'].'&desk_id='.$message_data['desk_id'], //跳转地址
               ]));

               //更新房间桌子状态
               $message = [
                   'type'=>'sit',
                   'status' =>  1,
                   'client_id'=>$client_id,
                   'client_name' =>$client_name,
                   'color'  =>  $message_data['color'],
                   'desk_id'    =>  $message_data['desk_id'],
                   'client_logo'    =>  $_SESSION['client_logo'],
               ];
               return Gateway::sendToGroup($room_id ,json_encode($message));

           //聊天操作
           case 'say' :
               echo "发言";
               // 非法请求
               if(!isset($_SESSION['room_id'])) {
                   throw new \Exception("房间号\$_SESSION['room_id']未设置. 客户端ip:{$_SERVER['REMOTE_ADDR']}");
               }
               $room_id = $_SESSION['room_id'];
               $client_name = $_SESSION['client_name'];
               $new_message = array(
                   'type'=>'say',
                   'from_client_id'=>$client_id,
                   'from_client_name' =>$client_name,
                   //'to_client_id'=>'all',     //聊天对象
                   'content'=>nl2br(htmlspecialchars($message_data['content'])),
                   'time'=>date('Y-m-d H:i:s'),
                   'client_logo'    =>  $_SESSION['client_logo'],
               );
               Gateway::sendToGroup($room_id ,json_encode($new_message));
           break;

           //进入棋桌
           case 'entry' :

               /*******先检测用户是否合法********/

               //获取当前玩家信息
               $client = self::$db->select('client_name,client_logo')->from('client')->where('client_id= :client_id')->bindValues(array('client_id'=> $message_data['room_client_id']))->row();

               $color = $message_data['color'].'_id';
               $room_id = $message_data['room_id'];
               $desk_id = $message_data['desk_id'];
               //sessoion 记录玩家信息
               $_SESSION['color'] = $message_data['color'];
               $_SESSION['room_id'] = $room_id;
               $_SESSION['desk_id'] = $desk_id;
               $_SESSION['client_name'] = $client['client_name'];
               $_SESSION['client_logo'] = $client['client_logo'];
               $_SESSION['room_client_id'] = $message_data['room_client_id'];

               var_dump($_SESSION['color']);
               //加入两人游戏分组
               Gateway::joinGroup($client_id, 'room'.$room_id.'desk'.$desk_id);

               //游戏标识
               $_SESSION['is_game'] = 1;

               //记录game_id
               self::$db->query("INSERT INTO `desk` (room_id, desk_id, $color) VALUES('$room_id','$desk_id','$client_id') ON DUPLICATE KEY UPDATE `$color` = '$client_id'");

               $competitor = [];
               //查找对手
               $competitor = self::get_competitor($room_id, $message_data['color']);

               $res = [
                   'type'   =>  'self_entry',
                   'msg'   =>  '',
                   'data'  =>  [
                       'color'      =>  $color,
                       'client_id'  =>  $client_id,
                       'room_client_id'  =>  $message_data['room_client_id'],
                       'client_name' => $client['client_name'],
                       'client_logo' => $client['client_logo'],
                       'competitor'  => $competitor,
                   ],
               ];

               Gateway::sendToCurrentClient(json_encode($res));

               // 更新房间棋桌
               self::$db->update('client')->cols(array('game_id'))->where("client_id='{$message_data['room_client_id']}'")->bindValue('game_id', $client_id)->query();

               //通知对手玩家进入房间
               if($competitor){
                   $client['color'] = $_SESSION['color'];
                   $data = [
                       'type'      =>   'competitor_entry',
                       'data'  =>  [
                           'competitor'  => $client,
                       ],
                   ];
                   Gateway::sendToClient($competitor['game_id'], json_encode($data));
               }

           break;


           //玩家准备开始游戏
           case 'start' :

               //获取当前玩家信息
               //$client = self::$db->select('client_name,client_logo')->from('client')->where('client_id= :client_id')->bindValues(array('client_id'=> $message_data['room_client_id']))->row();

               //玩家准备
               self::$db->update('client')->cols(array('start'))->where("game_id='$client_id'")->bindValue('start', '1')->query();

               $competitor = self::get_competitor($_SESSION['room_id'] ,$_SESSION['color']);

               var_dump($competitor);


               //初始化各种数据.
               if($competitor && 1 == $competitor['start']){

                   //初始化棋盘   15 x 15
                   $chessboard = [];
                   for($i = 0; $i < 15; $i++){
                       $chessboard[$i] = [];
                       for($j = 0; $j < 15; $j++){
                           $chessboard[$i][$j] = 0;
                       }
                   }

                   $data = [
                       'type'    =>  'start',  //准备开始游戏
                       'data'  =>  [
                           'chessboard'  =>  $chessboard,
                           'msg'   =>  '双方玩家已就绪, 现在开始游戏...',
                           'competitor_id' =>  $competitor['game_id'], //对手
                       ],
                   ];
                   $move = $_SESSION['color'] == 'black' ? 1 : 0;
                   $other_move = $_SESSION['color'] == 'white' ? 1 : 0;

                   //var_dump($move);
                   $chessboard = json_encode($chessboard);
                   //设置当前玩家  先手  棋盘
                   self::$db->query("UPDATE `client` SET `move` = '$move',chessboard = '$chessboard'  WHERE game_id='$client_id'");

                   //echo "1  : UPDATE `client` SET `move` = '$move',chessboard = '$chessboard'  WHERE game_id='$client_id'";
                   //设置对手
                   self::$db->query("UPDATE `client` SET `move` = '$other_move',chessboard = '$chessboard'  WHERE game_id='{$competitor['game_id']}'");

                   //echo "2  : UPDATE `client` SET `move` = '$c',chessboard = '$chessboard'  WHERE game_id='$client_id'";

                   Gateway::sendToCurrentClient(json_encode($data));
                   $data['data']['competitor'] = [
                       'color'      =>  $_SESSION['color'],
                       'client_name' => $_SESSION['client_name'],
                       'client_logo' => $_SESSION['client_logo'],
                   ];
                   $data['data']['competitor_id'] = $client_id;
                   Gateway::sendToClient($competitor['game_id'], json_encode($data));
               }
           break;

           //游戏中...
           case 'play' :

               //对手信息
               $competitor =  self::$db->select('client_name, start, move, chessboard')->from('client')->where("game_id= :id AND game_id <> ''")->bindValues(array('id'=> $message_data['competitor']))->row();
               //当前玩家信息
               $self = self::$db->select('client_name, start, move, chessboard')->from('client')->where('game_id= :id')->bindValues(array('id'=> $client_id))->row();

               $id = $message_data['competitor'];
               if( 3 == $message_data['status'] && $competitor && 1 == $self['start'] && 1 == $competitor['start']){

                   if(0 == $self['move']){
                       $res = [
                           'type'    =>  'ban',  //不是自己下棋
                           'msg'   =>  '等待对手下棋',
                       ];
                       return Gateway::sendToCurrentClient(json_encode($res));
                   }

                   //当前棋局
                   $cur_chess_board = json_decode($competitor['chessboard']);

                   //当前坐标
                   $coordinate = explode('|', $message_data['data']);
                   if(!$coordinate) return ;
                   $x = $coordinate[0];
                   $y = $coordinate[1];

                   if($cur_chess_board[$x][$y] != 0) return ;

                   //更新棋局  1 黑棋 2 白棋
                   $cur_chess_board[$x][$y] = $_SESSION['color'] == 'black' ? 1 : 2;

                   $chess = json_encode($cur_chess_board);

                   //设置交换行棋顺序
                   self::$db->query("UPDATE `client` SET `move` = '0',`chessboard` = '$chess'  WHERE game_id='$client_id'");
                   self::$db->query("UPDATE `client` SET `move` = '1',`chessboard` = '$chess'  WHERE game_id='$id'");

                   $res = [
                       'type'    =>  'playing',  //正在开始游戏
                       'msg'   =>  '',
                       'data'  =>  [
                           'chessboard'  =>  $cur_chess_board,
                           'color'  =>    $_SESSION['color'],
                           'x'     =>    $x,
                           'y'     =>    $y,
                       ],
                   ];
                   Gateway::sendToClient($id, json_encode($res));
                   Gateway::sendToCurrentClient(json_encode($res));

                   //判断胜负
                   if(self::get_win($_SESSION['color'], $cur_chess_board)){
                       $info = [
                           'type'    =>  'finish',  //游戏结束
                           'data'       =>[
                               'msg'   =>  '五子连珠, 你获得了胜利',
                           ]
                       ];
                       //更新状态
                       self::$db->update('client')->cols(['start' => 0])->where("game_id='$client_id'")->query();
                       self::$db->update('client')->cols(['start' => 0])->where("game_id='$id'")->query();

                       Gateway::sendToCurrentClient(json_encode($info));

                       $info['data']['msg']  = '你输了哦.';
                       Gateway::sendToClient($id, json_encode($info));
                   }

               }else{
                   Gateway::sendToCurrentClient(json_encode([
                       'type'     =>      'ban',
                       'msg'        =>      '双方玩家还没有准备就绪哦',
                   ]));
               }
           break;

           //玩家认输
           case 'end' :
               //对手信息
               $self = self::$db->row("SELECT client_id, game_id, start FROM client WHERE game_id='$client_id'");
               $competitor = self::get_competitor($_SESSION['room_id'], $_SESSION['color']);

               if(isset($competitor) && 1 == $self['start'] && 1 == $competitor['start']){
                   $id = $competitor['game_id'];
                   $info = [
                       'type'    =>  'finish',  //游戏结束
                       'data'       =>[
                           'status' => 1,
                           'msg'   =>  '游戏结束, 请选择操作',
                       ]
                   ];

                   //更新状态
                   self::$db->update('client')->cols(['start' => 0])->where("game_id='$client_id'")->query();
                   self::$db->update('client')->cols(['start' => 0])->where("game_id='$id'")->query();

                   Gateway::sendToCurrentClient(json_encode($info));
                   if(isset($message_data['time']) && 'over' == $message_data['time']){
                       $info['data']['msg']  = '对方玩家超时.';
                   }else{
                       $info['data']['msg']  = '对方玩家向你认输.';
                   }
                   Gateway::sendToClient($id, json_encode($info));
               }else{
                   Gateway::sendToCurrentClient(json_encode([
                       'type'     =>      'ban',
                       'msg'        =>      '双方玩家还没有准备就绪哦',
                   ]));
               }
           break;

           //玩家离开房间,  发消息给对手
           case 'out' :
               // 非法请求
               if(!isset($_SESSION['room_id'])) {
                   throw new \Exception("非法操作. 客户端ip:{$_SERVER['REMOTE_ADDR']}");
               }

               var_dump($message_data);
               $id = $message_data['competitor'];
               Gateway::sendToClient($id, json_encode([
                   'type'   =>  'out',
               ]));
           break;

       }
   }
   
   /**
    * 当用户断开连接时触发
    * @param int $client_id 连接id
    */
   public static function onClose($client_id) {
       // debug
       //echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id onClose:''\n";

       //连接的用户信息
       $client = self::$db->row("SELECT client_id, game_id, start FROM client WHERE client_id = '$client_id' OR game_id='$client_id'");

       // 离开房间 or 玩家逃跑
       if(isset($_SESSION['is_game']))
       {
           //棋桌状态改变
           if('white' == $_SESSION['color']){
               self::$db->update('desk')->cols(['white_id' => ''])->where("white_id='$client_id'")->query();
           } else if('black' == $_SESSION['color']){
               self::$db->update('desk')->cols(['black_id' => ''])->where("black_id='$client_id'")->query();
           }

            //删除房间连接
           if($client['client_id'] == ''){
               self::$db->delete('client')->where("game_id='$client_id' AND client_id = ''")->query();
           }else{
               self::$db->update('client')->cols(['game_id' => '', 'move' => 0, 'start' => 0])->where("game_id='$client_id'")->query();
           }

           //给对手发送玩家离线信息
           $group = 'room'.$_SESSION['room_id'].'desk'.$_SESSION['desk_id'];
           $competitor = [];
           $competitor = self::get_competitor($_SESSION['room_id'] ,$_SESSION['color']);

           // 这里有问题,  不太确定连接是不是比实际少一个  0 为空闲状态 1 为游戏中状态
           if(1 == $client['start'] && Gateway::getClientCountByGroup($group) >= 1 && 1 == $competitor['start']){
               Gateway::sendToGroup($group, json_encode([
                   'type'   =>  'finish',
                   'data'   =>[
                       'status' => 2,
                       'msg'   =>  '对方逃跑, 确定继续等待其它玩家加入,  离开将关闭本页面?',
                   ]
               ]));
           }

           Gateway::sendToGroup($group, json_encode([
               'type'   =>  'out',
           ]));

           /*更新棋桌*/
           $message = [
               'type'               =>      'out',
               'client_logo'        =>      $_SESSION['client_logo'],
               'from_client_id'     =>      $client_id,
               'from_client_name'   =>      $_SESSION['client_name'],
               'desk_id'            =>  $_SESSION['desk_id'],
               'color'            =>  $_SESSION['color'],
           ];
           Gateway::sendToGroup($_SESSION['room_id'], json_encode($message));

       }else if(isset($_SESSION['is_room'])) {      //退出游戏大厅
           $room_id = $_SESSION['room_id'];

           //桌子的状态改变
           if('white' == $_SESSION['color'])
               self::$db->update('desk')->cols(['white_id' => ''])->where("white_id='$client_id'")->query();
           else if('black' == $_SESSION['color'])
               self::$db->update('desk')->cols(['black_id' => ''])->where("black_id='$client_id'")->query();

           if($client['game_id'] == ''){
               self::$db->delete('client')->where("client_id='$client_id' AND game_id = ''")->query();
           }else{
               self::$db->update('client')->cols(['client_id' => ''])->where("client_id='$client_id'")->query();
           }

           $message = [
               'type'               =>      'logout',
               'client_logo'        =>      $_SESSION['client_logo'],
               'from_client_id'     =>      $client_id,
               'from_client_name'   =>      $_SESSION['client_name'],
               'time'               =>      date('Y-m-d H:i:s'),
               'count'              =>      Gateway::getClientCountByGroup($room_id),
               'desk_id'            =>  isset($_SESSION['desk_id']) ? $_SESSION['desk_id'] : false,
               'color'            =>  isset($_SESSION['color']) ? $_SESSION['color'] : false,
           ];
           Gateway::sendToGroup($room_id, json_encode($message));
       }

   }



    //判断输赢
    public static function get_win($color, $chessboard){

        $flag = $color == 'black' ? 1 : 2;
        var_dump($flag);
        // 判断 x 方向
        for($y = 0; $y < 15; $y++){
            for($x = 0; $x < 11; $x++){
                if($flag == $chessboard[$x][$y] && $flag ==$chessboard[$x + 1][$y] && $flag == $chessboard[$x + 2][$y] && $flag == $chessboard[$x + 3][$y] && $flag == $chessboard[$x + 4][$y]){
                    return true;
                }
            }
        }

        // 判断  y方向
        for($y = 0; $y < 11; $y++){
            for($x = 0; $x < 15; $x++){
                if($flag == $chessboard[$x][$y] && $flag == $chessboard[$x][$y + 1] && $flag == $chessboard[$x][$y + 2] && $flag == $chessboard[$x][$y + 3] && $flag == $chessboard[$x][$y + 4]){
                    return true;
                }
            }
        }

        // 判断   \  方向
        for($y = 0; $y < 11; $y++){
            for($x = 0; $x < 11; $x++){
                if($flag == $chessboard[$x][$y] && $flag == $chessboard[$x + 1][$y + 1] && $flag == $chessboard[$x + 2][$y + 2] && $flag == $chessboard[$x + 3][$y + 3] && $flag == $chessboard[$x + 4][$y + 4]){
                    return true;
                }
            }
        }

        //判断 / 方向
        for($y = 0; $y < 11; $y++){
            for($x = 4; $x < 15; $x++){
                if($flag == $chessboard[$x][$y] && $flag == $chessboard[$x - 1][$y + 1] && $flag == $chessboard[$x - 2][$y + 2] && $flag == $chessboard[$x - 3][$y + 3] && $flag == $chessboard[$x - 4][$y + 4]){
                    return true;
                }
            }
        }
        return false;
    }


    //获取对手信息
    public static function get_competitor($room_id, $color){
        $competitor = [];
        if('white' == $color){
            $competitor = self::$db
                ->select("client_id, start, client_logo, client_name, game_id,  'black' as color")
                ->from('client')
                ->innerJoin('desk','desk.black_id = client.game_id')
                ->where("room_id = :room_id AND black_id <> ''")
                ->bindValues(['room_id' => $room_id])
                ->row();
        }else if('black' == $color){
            $competitor = self::$db
                ->select("client_id, start, client_logo, client_name, game_id,  'white' as color")
                ->from('client')
                ->innerJoin('desk','desk.white_id = client.game_id')
                ->where("room_id = :room_id AND white_id <> ''")
                ->bindValues(['room_id' => $room_id])
                ->row();
        }
        return $competitor;
    }

}
