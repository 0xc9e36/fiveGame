<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>web即时五子棋</title>
    <link href="layui/css/layui.css" rel="stylesheet" type="text/css"/>
    <link rel="stylesheet" href="css/room.css" type="text/css" />
    <script src="js/jquery-2.1.4.min.js"></script>
    <script src="js/config.js"></script>
    <script src="layui/lay/dest/layui.all.js"></script>
    <script src="js/room.js"></script>
</head>
<body>
    <div id="whole">
        <div class="layui-bg-cyan"><span id="room_id"></span> &nbsp;&nbsp; <span id="player"></span></div>

        <div class="main">
            <table align="center">
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
                        <div class="desk-num">- <?= $id ?>桌 -</div>
                    </td>
                    <?php } ?>
                </tr>
                <?php } ?>
            </table>
        </div>
        <div class="chat">

            <div class="player-list">
                <h4 align="center"><i class="layui-icon" style="font-size: 40px; color:black;">&#xe613;</i><p id="count"></p></h4>
                <ul id="client_list"></ul>
            </div>

            <div class="chat-list">
                <div class="input">
                    <hr />
                    <button class="layui-btn" id="speak">发送弹幕</button>
                </div>
            </div>
        </div>

    </div>
        <script type="text/javascript" >
            window.onload = function(){
                var ws,client_id, client_name, client_logo, client_list = {}, color, desk_status = {},  desk_id, room_id = getQueryString('room_id');
                //定义用户名, 头像, 建立websocket连接
                layer.ready(function(){
                    layer.prompt({
                        formType: 0,
                        value: '',
                        btn : ['确定', '随机'],
                        title: '先输入一个游戏昵称吧',
                        'cancel' :function () {     //禁止关闭
                            return false;
                        },
                        'end' :function () {
                            if(!client_name || client_name==''){
                                client_name = '游客'+getRandomNum(1,1000);
                            }
                            if(!client_logo) client_logo = images + getRandomNum(1, 9)+'.jpg';
                            //关闭弹出执行websocket连接
                            connect();
                            $('#room_id').html("当前房间 : " + room_id);
                        },
                    }, function(value, index, elem){
                        client_name = value;
                        layer.close(index);
                    });
                });


                //websocket连接
                function connect(){
                    // 创建websocket
                    ws = new WebSocket('ws://'+ip);

                    // 当socket连接打开时
                    ws.onopen = open;

                    // socket接收服务端消息
                    ws.onmessage = onmessage;

                }

                //获取url地址栏参数
                function getQueryString(name) {
                    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
                    var r = window.location.search.substr(1).match(reg);
                    if (r != null) return unescape(r[2]); return null;
                }

                //打开连接
                function open(){
                    // 发送登录消息
                    var login_data = '{"type":"login", "client_logo":"'+client_logo+'", "client_name":"'+client_name.replace(/"/g, '\\"')+'","room_id":"'+room_id+'"}';
                    console.log("websocket握手成功，发送登录数据:"+login_data);
                    ws.send(login_data);
                }

                //接收消息
                function onmessage(e) {
                    var data = eval('(' + e.data + ')');

                    switch (data.type) {

                        //登录
                        case 'login' :
                            client_id = data['client_id'];
                            client_name = data['client_name'];

                            //顶部玩家在线Logo
                            $('#player').html('<i class="layui-icon" style="font-size: 20px; color: yellow;">&#xe610;  '+client_name+'</i>' );

                            say(data['client_id'], data['client_name'], data['client_name'] + ' 加入了房间', data['time'], data['client_logo']);

                            //刷新在线用户
                            if (data['client_list']) {
                                client_list = data['client_list'];
                            } else {
                                //没有就加入进去
                                client_list[data['client_id']] = data['client_name'];
                            }
                            refresh_client_list(data['count']);

                            //初始化棋桌
                            desk_status = data['desk_status'];

                            //debug
                            //console.log(desk_status);

                            //刷新桌子
                            refresh_desk_status();

                            console.log("用户名:" + data['client_name'] + "登录成功" + ", 分配进程id:" + data['client_id']);
                            break;


                        //聊天
                        case 'say' :
                            say(data['from_client_id'], data['from_client_name'], data['content'], data['time'], data['client_logo']);
                            break;

                        //退出房间
                        case 'logout':
                            //刷新在线用户列表
                            say(data['from_client_id'], data['from_client_name'], data['from_client_name'] + ' 退出了房间', data['time'], data['client_logo']);
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
                            if (data['status'] == 1) {
                                //刷新棋桌状态
                                refresh_chess_desk(data['client_id'], data['desk_id'], data['color'], data['client_name'], data['client_logo'], true);
                            } else {
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
                    //发送弹幕
                    play(content);
                }

                //弹幕
                function play(content){
                    var $value=content;
                    var $p=$("<p></p>");
                    $p.addClass("danmu");
                    $p.text($value);
                    var _top=Math.floor(getRandomNum(50, 700));
                    var _rgb="rgb(" + Math.floor(Math.random()*255)+"," + Math.floor(Math.random()*255)+"," + Math.floor(Math.random()*255)+")";
                    $p.css({"top":_top,"font-size":'20px',"color":_rgb});
                    $s = $("#whole").append($p);
                    console.log($s);
                    var _timer=Math.ceil(Math.random()*4000)+4000;
                    $p.stop().animate({"left":"-500px"},_timer,function(){
                        $(this).remove();
                    });
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


                //随机数
                function getRandomNum(min, max) {//生成一个随机数从[min,max]
                    return min + Math.round(Math.random() * (max - min));
                }

                //发言
                $("#speak").click(function(){
                    var content;
                    layer.prompt({
                        formType: 2,
                        value: '',
                        title: '输入要发送的消息吧~',
                        area: ['400px', '150px'], //自定义文本域宽高
                    }, function(value, index, elem){
                        var data = '{"type":"say","content":"'+value.replace(/"/g, '\\"').replace(/\n/g,'\\n').replace(/\r/g, '\\r')+'"}'
                        ws.send(data);
                        layer.close(index);
                    });

                    //content = $('.message').val();

                    //$('.message').val('');
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
</body>
</html>
