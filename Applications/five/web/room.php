<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>五子棋</title>
    <link rel="stylesheet" href="css/room.css" type="text/css" />
    <script src="js/jquery-2.1.4.min.js"></script>
    <script src="js/config.js"></script>
    <script src="js/chat.js"></script>
    <script type="text/javascript" >
        window.onload = function(){
            var ws,client_id, client_name, client_logo, client_list = {}, color, desk_status = {},  desk_id, room_id = <?php echo isset($_GET['room_id']) ? $_GET['room_id'] : 1?>;

            // 创建websocket
            ws = new WebSocket('ws://'+ip);

            // 当socket连接打开时
            ws.onopen = function () {
                if(!client_name) login();
                if(!client_logo) client_logo = images + getRandomNum(1, 9)+'.jpg';
                // 发送登录消息
                var login_data = '{"type":"login", "client_logo":"'+client_logo+'", "client_name":"'+client_name.replace(/"/g, '\\"')+'","room_id":"'+room_id+'"}';
                console.log("websocket握手成功，发送登录数据:"+login_data);
                ws.send(login_data);
            };
            // socket接收服务端消息
            ws.onmessage = function (e) {

                var data = eval('(' + e.data + ')');

                switch(data.type) {

                    //登录
                    case 'login' :
                        client_id = data['client_id'];
                        say(data['client_id'], data['client_name'],  data['client_name']+' 加入了房间', data['time'], data['client_logo']);

                        //刷新在线用户
                        if(data['client_list']){
                            client_list = data['client_list'];
                        }else{
                            //没有就加入进去
                            client_list[data['client_id']] = data['client_name'];
                        }
                        refresh_client_list(data['count']);
                        //初始化棋桌
                        desk_status = data['desk_status'];
                        //console.log(desk_status);
                        refresh_desk_status();
                        console.log("用户名:" + data['client_name']+"登录成功"+", 分配进程id:" + data['client_id']);
                    break;

                    //聊天
                    case 'say' :
                        say(data['from_client_id'], data['from_client_name'], data['content'], data['time'], data['client_logo']);
                        break;

                    //退出房间
                    case 'logout':
                        //刷新在线用户列表
                        say(data['from_client_id'], data['from_client_name'],  data['from_client_name']+' 退出了房间', data['time'], data['client_logo']);
                        delete client_list[data['from_client_id']];
                        refresh_client_list(data['count']);
                        //console.log(data['color']);
                    break;

                    //退出游戏
                    case 'end' :
                        console.log(data);
                        refresh_chess_desk(data['from_client_id'], data['desk_id'], data['color'], data['from_client_name'], data['client_logo'], false);
                    break;

                    //有玩家坐下
                    case 'sit' :
                            //玩家加入桌子
                        if(data['status'] == 1) {
                            //刷新棋桌状态
                            refresh_chess_desk(data['client_id'], data['desk_id'], data['color'], data['client_name'], data['client_logo'], true);
                        }else{
                            alert(data['content']);
                        }
                    break;
                    //打开新页面
                    case 'open' :
                        url = site + data['url'];
                        console.log(url);
                        window.open(url, '_blank');
                }
            }

            //发言
            function say(client_id, client_name, content, time, client_logo){
                var input = $(".input");
                input.before('<div class="chat-box"> <div class="player-info"> <img src="'+client_logo+'" class="player_icon"/>'+client_name+'<br />'+time+'</span> <div style="clear: both"></div> <p class="chat_style">'+content+'</p> </div> </div>');
            }

            //刷新在线用户
            function refresh_client_list(num){
                var list = $("#client_list");
                var count = $("#count");
                list.empty();
                count.empty();
                for(var id in client_list){
                    list.append('<li>'+client_list[id]+'</li>');
                }
                count.html('在线用户'+num+'人');
            }

            //进入时刷新房间
            function refresh_desk_status(){
                for(var i in desk_status){
                    refresh_chess_desk(desk_status[i].client_id, desk_status[i].desk_id, desk_status[i].color, desk_status[i].client_name, desk_status[i].client_logo, true);
                }
            }

            //更新棋桌状态,   type 玩家坐下 true,  玩家离开 false
            function refresh_chess_desk(client_id, desk_id, color, client_name, client_logo, type){
                var name = color == 'black' ? '.desk-right' : '.desk-left';
                var box = $('#'+desk_id).find(name);
                //玩家坐下, 刷新桌子logo
                if(type){
                    box.html('<div class="desk-on"></div>');
                    box.find('.desk-on').attr('client_id', client_id);
                    box.find('.desk-on').attr('client_name', client_name);
                    box.find('.desk-on').attr('title', client_name);
                    box.find('.desk-on').css('background-image','url('+client_logo+')');
                }else{
                    //玩家退出
                    box.html('<div class="desk-none"> </div>');
                }

            }

            //输入用户名
            function login(){
                client_name = prompt('输入你的名字：', '');
                if(!client_name || client_name=='null'){
                    client_name = '游客'+getRandomNum(1,1000);
                }
            }

            //随机数
            function getRandomNum(min, max) {//生成一个随机数从[min,max]
                return min + Math.round(Math.random() * (max - min));
            }

            //发言
            $("#speak").click(function(){
                content = $('.message').val();
                var data = '{"type":"say","content":"'+content.replace(/"/g, '\\"').replace(/\n/g,'\\n').replace(/\r/g, '\\r')+'"}'
                ws.send(data);
                $('.message').val('');
            });


            /*获取当前时间*/
            function CurentTime()
            {
                var now = new Date();
                var year = now.getFullYear();       //年
                var month = now.getMonth() + 1;     //月
                var day = now.getDate();            //日
                var hh = now.getHours();            //时
                var mm = now.getMinutes();          //分
                var ss = now.getSeconds();          //秒

                var clock = year + "-";
                if(month < 10)
                    clock += "0";
                clock += month + "-";
                if(day < 10)
                    clock += "0";
                clock += day + " ";

                if(hh < 10)
                    clock += "0";
                clock += hh + ":";

                if (mm < 10) clock += '0';
                clock += mm;

                if (ss < 10) clock += '0';
                clock += ss;
                return clock;
            }

            //加入房间
            $(".desk-left,.desk-middle,.desk-right").click(function(){
                var desk_on = $(this).children("div").attr('class');
                if(desk_on == 'desk-on' || desk_on == 'desk-board-on'){
                    alert('这个座位已经有人了');
                    return ;
                }
                var desk_id = $(this).parent().attr('id');
                var desk_color = $(this).attr('class');
                var color = 'black';
                if(desk_color == 'desk-left') color = 'white';
                var data = '{"type":"sit","desk_id":"'+desk_id+'","color":"'+color+'","client_logo":"'+client_logo+'"}'
                ws.send(data);
            });
        };
    </script>
</head>

<body>
<div class="main">
    <table>
        <?php for($i = 1; $i <= 25; $i++){  ?>
        <tr>
            <?php
                for($j = 1; $j <= 5; $j++){
                    $id = 5*($i - 1) + $j;
            ?>
            <td>
                <div class="desk" id="<?= $id ?>">
                    <span class="desk-left" >
                        <div class="desk-none"></div>
                    </span>
                    <span class="desk-middle">
                        <div class="desk-board-none"> </div>
                    </span>
                    <span class="desk-right">
                         <div class="desk-none"> </div>
                    </span>
                </div>
                <div class="desk-num">- <?= $id ?> -</div>
            </td>
            <?php } ?>
        </tr>
        <?php } ?>
    </table>
</div>
<div class="chat">
    <div class="chat-list">
        <div class="input">
            <hr />
            <textarea class="message"></textarea>
            <button id="speak">发言</button>
        </div>
    </div>
    <div class="player-list">
        <h4 align="center" id="count"></h4>
        <ul id="client_list"></ul>
    <div>
</div>

</body>

</html>
