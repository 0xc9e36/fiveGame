/**
 * Created by tan on 17-3-30.
 */
window.onload = function(){
    //定义玩家信息, 定时器,  棋盘信息
    var room_client_id, room_id, desk_id, client_id, color, competitor_id;
    var timer, othertimer, t = time, o = time;
    var chessboard = document.getElementById('chessboard');
    var context = chessboard.getContext('2d');
    context.strokeStyle = "#BFBFBF";

    //绘制棋盘
    drawChessBoard();

    //建立websocket连接
    connect();

    //websocket连接
    function connect(){
        // 创建websocket
        ws = new WebSocket('ws://'+ip);

        // 当socket连接打开时
        ws.onopen = open;

        // socket接收服务端消息
        ws.onmessage = onmessage;

    }

    function open() {
        room_client_id = getQueryString("client_id");
        room_id = getQueryString("room_id");
        desk_id = getQueryString("desk_id");
        color = getQueryString("color");
        // 发送登录消息
        var data = '{"type":"entry", "color":"'+color+'" ,"room_client_id":"'+room_client_id+'", "room_id":"'+room_id+'", "desk_id" : "'+desk_id+'"}';
        //console.log("websocket握手成功，发送登录数据:"+data);
        ws.send(data);
    }

    function onmessage(e) {
        console.log(e);
        //转为json对象
        var obj = eval('(' + e.data + ')');
        var data = obj.data;
        console.log(obj.type);

        switch(obj.type) {

            //玩家进入房间
            case 'self_entry' :
                //右上角玩家具体信息设置
                setSelf(data.client_name, data.client_logo);

                //左侧当前玩家信息设置
                setPlayer(data.client_name, data.client_logo, color);
                //如果有对手设置对手信息
                if (data.competitor) {
                    var competitor = data.competitor;
                    //设置对手玩家信息
                    setPlayer(competitor.client_name, competitor.client_logo, competitor.color);
                }
            break;

            //对手进入房间
            case 'competitor_entry' :
                var competitor = data.competitor;
                //设置对手玩家信息
                setPlayer(competitor.client_name, competitor.client_logo, competitor.color);
            break;

            //对手离开房间
            case 'out':
                console.log(data);
                //清空对手玩家信息
                clearPLayer(color);
            break;

            //双方开始游戏
            case 'start' :

                //初始化棋盘
                context.beginPath();
                context.clearRect(0, 0, 600, 600);
                drawChessBoard();

                //初始化计时器
                clearInterval(timer);
                clearInterval(othertimer);
                $('.white').find('.white_time').html(get_time(time));
                $('.black').find('.black_time').html(get_time(time));
                $('#time').html(get_time(time));

                t = time;
                o = time;
                if('black' == color) {
                    timer = setInterval(refresh_time, 1000);//1000毫秒
                }else {
                    othertimer = setInterval(refresh_other_time, 1000);//1000毫秒
                }

                competitor_id = data.competitor_id;
                layer.msg(data.msg);
            break;

            //玩家落子
            case 'playing' :
                var coordinate_x = data.x;
                var coordinate_y = data.y;
                var black = data.color == 'black' ? true : false;

                //对手页面设置
                if(color != data.color){
                    timer = setInterval(refresh_time, 1000);//1000毫秒
                    clearInterval(othertimer);
                }else{
                    //清除自身计数器
                    clearInterval(timer);
                    //开始对手计时器
                    othertimer = setInterval(refresh_other_time,1000);//1000毫秒
                }

                chess(coordinate_x, coordinate_y, black);
            break;

            //禁止的操作
            case 'ban' :
                layer.msg(obj.msg);
            break;

            //玩家胜利
            case 'finish':
                clearInterval(timer);
                //对方逃跑则清空信息
                if(2 == data.status)
                    clearPLayer(color);

                var msg = data.msg;
                layer.open({
                    btn: ['再来一盘', '离开'],
                    title: '提示',
                    content: msg,
                    yes: function (index, layero) {
                        //var data = '{"type":"entry", "color":"'+color+'" ,"room_client_id":"'+room_client_id+'", "room_id":"'+room_id+'", "desk_id" : "'+desk_id+'"}';
                        var data = '{"type":"start"}';
                        //console.log(data);
                        ws.send(data);
                        layer.close(index);
                    },
                    btn2: function () {
                        var data = '{"type":"out", "competitor":"'+competitor_id+'"}';
                        //console.log(data);
                        ws.send(data);
                        //关闭连接
                        ws.close();
                        window.opener = null;
                        window.close()
                    },
                    'cancel': function () {
                        return false;
                    },
                    'end': function () {
                    },
                });
            break;

        }

    };


    //点击落子
    chessboard.onclick = function(e){
        var x = e.offsetX;
        var y = e.offsetY;
        var i = Math.floor(x / 40);
        var j = Math.floor(y / 40);
        var val = i + "|" + j;
        var send = '{"type":"play","status":"3","color":"'+color+'", "data":"'+val+'", "competitor":"'+competitor_id+'"}';
        console.log(send);
        ws.send(send);
        //停止计时
    }

    //绘制棋盘
    function drawChessBoard(){
        for(var i = 0; i < 15; i++){
            //先画竖线
            context.moveTo(20 + i * 40, 20);
            context.lineTo(20 + i * 40,575);
            context.stroke();
            //再画横线
            context.moveTo(20, 20 + i * 40);
            context.lineTo(575, 20 + i * 40);
            context.stroke();
        }
    }

    //绘制棋子
    function chess(i, j, black){
        var x = 20+i*40;
        var y = 20+j*40;
        context.beginPath();
        //棋子大小
        context.arc(x, y, 19, 0, 2 * Math.PI);
        context.closePath();
        //渐变填充
        var gradient  = context.createRadialGradient(x + 2, y - 2, 15, x + 2, y - 2, 0);
        if(black) {
            gradient.addColorStop(0, "#0A0A0A");
            gradient.addColorStop(1, "#636766");
        }else{
            gradient.addColorStop(0, "#D1D1D1");
            gradient.addColorStop(1, "#F9F9F9");
        }
        context.fillStyle = gradient;
        context.fill();

    }

    //设置右上角玩家信息
    function setSelf(client_name, client_logo){
        $('.cur_name').html(client_name);
        $('.detail-left').css('background-image', 'url(' + client_logo + ')');
        $('.max_time').html(get_time(time));
    }

    //设置玩家信息
    function setPlayer(client_name, client_logn, color){
        var curBox = color == 'white' ? $('.white') : $('.black');
        var src = color == 'white' ? '../images/white-chess.png' : '../images/black-chess.png';
        curBox.find('img').attr('src', src);
        curBox.find('.client_name').html(client_name);
        //console.log(color);
        curBox.find('.player-logo').css('background-image',"url('../images/person.gif')");
    }

    //清空对手信息
    function clearPLayer(color){
        var box = color == 'black' ? $('.white') : $('.black');
        console.log(color);
        //设置no-player
        box.find('.player-logo').css('background-image',"url('../images/no-player.gif')");
        box.find('.client_name').html('');
        box.find('.chess-logo').removeAttr('src');
    }

    //获取url参数
    function getQueryString(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
        var r = window.location.search.substr(1).match(reg);
        if (r != null) return unescape(r[2]); return null;
    }

    //设置定时器
    function setTime(color, white_time, black_time){

    }


    //自己定时器设置
    function refresh_time(){

        $('#time').html(get_time(t));

        if('black' == color){
            $('.black').find('.black_time').html(get_time(t));
        }else{
            $('.white').find('.white_time').html(get_time(t));
        }

        t--;
        if(t < 0){
            //倒计时结束, 自动认输
            clearInterval(timer);
            var data = '{"type":"end", "time":"over"}';
            ws.send(data);
        }
    }

    //对手定时器设置
    function refresh_other_time(){
        if('black' == color){
            $('.white').find('.white_time').html(get_time(o));
        }else{
            $('.black').find('.black_time').html(get_time(o));
        }

        o--;
        if(o < 0){
            //倒计时结束, 自动认输
            clearInterval(othertimer);
        }
    }

    function get_time(s){
        var minutes = parseInt(s / 60);
        var minutes = minutes < 10 ? '0' + minutes : minutes;
        var seconds = parseInt(s % 60);
        var seconds = seconds < 10 ? '0' + seconds : seconds;
        return minutes + '-' + seconds;
    }
    //悔棋操作
    $('#retract').click(function(){
        layer.msg('暂不支持');
    });

    //玩家准备
    $('#start').click(function(){
        //已经准备过了禁止点击
        if($(this).attr('click') == 'off') return false;
        var data = '{"type":"start"}';
        $(this).attr('class', 'layui-btn layui-btn-disabled');
        $(this).attr('click', 'off');
        console.log(data);
        ws.send(data);
    });


    //玩家认输
    $('#end').click(function(){
        layer.confirm('确定要向对方认输吗', {icon: 3, title:'提示'}, function(index){
            //do something
            var data = '{"type":"end"}';
            ws.send(data);
            layer.close(index);
        });
    });
}

