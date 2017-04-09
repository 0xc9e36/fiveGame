/**
 * Created by tan on 17-3-30.
 */
window.onload = function(){

    var room_client_id, room_id, desk_id, client_id, color, competitor_id;
    ws = new WebSocket('ws://'+ip);
    ws.onopen = function () {
        room_client_id = getQueryString("client_id");
        room_id = getQueryString("room_id");
        desk_id = getQueryString("desk_id");
        color = getQueryString("color");
        // 发送登录消息
        var data = '{"type":"start", "color":"'+color+'" ,"room_client_id":"'+room_client_id+'", "room_id":"'+room_id+'", "desk_id" : "'+desk_id+'"}';
        console.log("websocket握手成功，发送登录数据:"+data);
        ws.send(data);
    }
    var chessboard = document.getElementById('chessboard');
    var context = chessboard.getContext('2d');
    context.strokeStyle = "#BFBFBF";

    //棋盘
    drawChessBoard();

    //点击落子
    chessboard.onclick = function(e){
        var x = e.offsetX;
        var y = e.offsetY;
        var i = Math.floor(x / 40);
        var j = Math.floor(y / 40);
        var val = i + "|" + j;
        var send = '{"type":"play","status":"2","color":"'+color+'", "data":"'+val+'", "competitor":"'+competitor_id+'"}';
        console.log(send);
        ws.send(send);
    }
    ws.onmessage = function (e) {
        //转为json对象
        var obj = eval('(' + e.data + ')');
        var data = obj.data;
        console.log(data);
        if (obj.status == 0) {//初始化名字
            $('h3').html('当前玩家 : '+data.client_name);
            //当前玩家信息设置
            setPlayer(data.client_name, data.client_logo, data.color);
            console.log(data.competitor);
            if(data.competitor){
                var competitor = data.competitor;
                //设置对手玩家信息
                setPlayer(competitor.client_name, competitor.client_logo, competitor.color);
            }
            //console.log(data);
        }

        if(obj.status == 1){

            context.beginPath();
            context.clearRect(0,0,600,600);
            //绘制棋子
            drawChessBoard();

            competitor_id = data.competitor_id;
            if(data.competitor){
                var competitor = data.competitor;
                //设置对手玩家信息
                setPlayer(competitor.client_name, competitor.client_logo, competitor.color);
            }
            alert(data.msg);
        }

        if (obj.status == 2) {//
            var coordinate_x = data.x;
            var coordinate_y = data.y;
            var black = data.color == 'black' ? true : false;
            chess(coordinate_x, coordinate_y, black);
        }

        if (obj.status == 3) {//
            var msg = data.msg;
            var r = confirm(msg + ',是否重新开始 ?');
            if (r) {
                competitor_id = null;
                var data = '{"type":"start", "color":"'+color+'" ,"room_client_id":"'+room_client_id+'", "room_id":"'+room_id+'", "desk_id" : "'+desk_id+'"}';
                console.log(data);
                ws.send(data);
                return;
            } else {
                //关闭连接
                ws.close();
                return;
            }
        }

        if (obj.status == 4) {//
            var box = color == 'white' ? $('.player2') : $('.player1');
            console.log(color);
            box.find('p').empty();
            var msg = data.msg;
            var r = confirm(msg);
            if (r) {
                competitor_id = null;
                var data = '{"type":"start", "color":"'+color+'" ,"room_client_id":"'+room_client_id+'", "room_id":"'+room_id+'", "desk_id" : "'+desk_id+'"}';
                console.log(data);
                ws.send(data);
                return;
            } else {
                //关闭连接
                ws.close();
                window.opener = null;
                window.open('', '_self');
                window.close()
            }
        }


        if(obj.status == 5){
            alert(obj.msg);
        }
    };

    //棋盘
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

    function setPlayer(client_name, client_logn, color){
        var box = color == 'white' ? $('.player1') : $('.player2');
        box.find('p').html('<image src="'+client_logn+'">'+client_name);
    }

    //获取url参数
    function getQueryString(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
        var r = window.location.search.substr(1).match(reg);
        if (r != null) return unescape(r[2]); return null;
    }
}

