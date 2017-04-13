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
        <li class="layui-nav-item"><a href="https://github.com/tw1996/fiveGame" target="_blank">源码</a></li>
    </ul>
    <hr>
    &nbsp;&nbsp;<a name="default">你好, 欢迎来到五子棋.</a>  <a href="#" class="layui-btn layui-btn-small layui-btn-radius layui-btn-danger">快速开始</a>
    <hr>
    <div class="main">
            <ul id="demo" class="layui-list"></ul>
            <span class="layui-readme">
                    <div class="layui-content-title">游戏说明</div>
                    <p>无禁手玩法：黑先白后，谁先连五谁胜</p>
                    <p>禁手玩法：黑先行棋，黑棋只能走冲四活三胜，黑双活三禁手 双冲四禁手 四三三禁手 四四三禁手 六连长连禁手；白后手,白棋无任何禁手，还可以抓黑棋的禁手点取胜</p>
                    <p>   职业规则玩法：三手交换五手两打，黑棋有禁手，意思是下到第三手棋执白方有权选择交换下黑棋或者继续行棋，下到第五手时执黑方
                        给出两个打点让执白方选择去掉一个打点下剩下的打点。五子棋 第一子下天元 第三手确定一个开局，正规开局26种 直指13种 斜指13种，有些开局即便在职业规则下
                         已经必胜了，或者必败了，还有一些平衡局 黑优局 白优局
                    职业规则的交换就限制了开局方（一开s始执黑方）开必胜或着开黑优会被另一方交换掉，所以职业规则下 大家基本都选择平衡局来行棋
                    </p>
            </span>
    </div>
    <hr>
</body>
</html>


