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
                    <img src="../images/white-chess.png" class="chess-logo">
                    <span class="chess-info">
                        <ul>
                            <li>昵称 : <span class="client_name"></span></li>
                            <li>限时 : 15-00</li>
                            <li>倒计时: 15-00</li>
                            <li>比分 : 15-00</li>
                        </ul>
                    </span>
                </div>
            </div>
            <div class="black">
                <div class="player-logo"></div>
                <div class="player-info">
                    <img src="../images/black-chess.png" class="chess-logo">
                    <span class="chess-info">
                    <span class="chess-info">
                        <ul>
                            <li>昵称 : <span class="client_name"></span></li>
                            <li>限时 : 15-00</li>
                            <li>倒计时: 15-00</li>
                            <li>比分 : 15-00</li>
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
    </span>

    <span class="right">
        <div class="me-info">
            <span class="room-title"><i class="layui-icon" style="font-size: 20px; color: #1E9FFF;">&#xe612;</i> 当前玩家</span>
            <div class="detail">
                <span class="detail-left"></span>
                <span class="detail-right">
                    <ul>
                        <li>昵称 : <span class="cur_name"></span></li>
                        <li>限时 : 15-00</li>
                        <li>倒计时: 15-00</li>
                        <li>比分 : 15-00</li>
                    </ul>
                </span>
            </div>
        </div>
        <div class="chess-chat">玩家聊天信息~ 即将实现</div>
    </span>
</div>

</body>
</html>

