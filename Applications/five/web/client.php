<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title></title>
    <link rel="stylesheet" href="css/client.css" type="text/css" />
    <link href="layui/css/layui.css" rel="stylesheet" type="text/css"/>

    <script src="js/jquery-2.1.4.min.js"></script>
    <script src="js/config.js"></script>
    <script src="layui/lay/dest/layui.all.js"></script>
    <script src="js/client.js"></script>

</head>
<body>
<div class="main" >

    <span class="left">
        <div class="player">
            <div class="white">
                <div class="player-logo"></div>
                <div class="player-info">
                    <img class="chess-logo">
                    <span class="chess-info">
                        <ul>
                            <li>昵称 : <span class="client_name"></span></li>
                            <li>限时 : <span class="max_time"></li>
                            <li>倒计时: <span class="white_time"></span></li>
                            <li>比分 : </li>
                        </ul>
                    </span>
                </div>
            </div>
            <div class="black">
                <div class="player-logo"></div>
                <div class="player-info">
                    <img class="chess-logo">
                    <span class="chess-info">
                    <span class="chess-info">
                        <ul>
                            <li>昵称 : <span class="client_name"></span></li>
                            <li>限时 : <span class="max_time"></li>
                            <li>倒计时: <span class="black_time"></span></li>
                            <li>比分 : </li>
                        </ul>
                    </span>
                    </span>
                </div>
            </div>
        </div>
    </span>

    <span class="border">
        <span class="title">五子棋游戏(玩家对奕)</span>
        <canvas id="chessboard" width="600" height="600"></canvas>
        <div class="operation">
            <ul>
                <li><button class="layui-btn layui-btn-radius layui-btn-normal" id="retract">悔棋</button></li>
                <li><button class="layui-btn layui-btn-radius layui-btn-normal" id="start" click="on">准备</button></li>
                <li><button class="layui-btn layui-btn-radius layui-btn-normal" id="end">认输</button></li>
            </ul>
        </div>
    </span>

    <span class="right">
        <div class="me-info">
            <span class="room-title"><i class="layui-icon" style="font-size: 20px; color: #1E9FFF;">&#xe612;</i> 当前玩家</span>
            <div class="detail">
                <span class="detail-left"></span>
                <span class="detail-right">
                    <ul>
                        <li>昵称 : <span class="cur_name"></span></li>
                        <li>限时 : <span class="max_time"></span></li>
                        <li>倒计时: <span id="time"></span></li>
                        <li>比分 : </li>
                    </ul>
                </span>
            </div>
        </div>
        <div class="chess-chat">
            <center><b>游戏说明 : </b></center>
            <p style="text-indent: 2em">玩家首先进入房间, 双方准备后方可开始游戏.  游戏过程中注意不要超时 !</p>
        </div>
    </span>
</div>
<div style="clear: both"></div>

</body>
</html>

