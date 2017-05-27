<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>web即时五子棋</title>
    <link href="layui/css/layui.css" rel="stylesheet" type="text/css"/>
    <link href="css/index.css" rel="stylesheet" type="text/css"/>
    <script src="js/jquery-2.1.4.min.js"></script>
    <script src="layui/lay/dest/layui.all.js"></script>
    <script src="js/index.js"></script>
    <script src="js/config.js"></script>
</head>
<body>
    <ul class="layui-nav" lay-filter="">
        <li class="layui-nav-item layui-this"><a href="">首页</a></li>
        <li class="layui-nav-item "><a href="https://github.com/tw1996/fiveGame" target="_blank">源码</a></li>
    </ul>
    <hr>
    &nbsp;&nbsp;<a name="default">你好, 欢迎来到五子棋.</a>
    <hr>
    <div class="main">
            <ul id="demo" class="room-list"></ul>
            <span class="game-introduction">
                    <div class="game-introduction-title">说明</div>
                    <ul>
                        <li>基于websocket长连接,前端使用layui框架, 后端使用Workerman + MYSQL数据库</li>
                        <li>支持弹幕聊天, 房间大厅, 在线游戏等功能, 单机可同时打开两个网页进行测试,  注意下棋窗口弹出时有可能会被浏览器拦截</li>
                        <li>暂时没有实现人机对战.</li>
                    </ul>
            </span>
    </div>
    <hr>
</body>
</html>


