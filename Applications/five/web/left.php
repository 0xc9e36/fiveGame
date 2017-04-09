<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>web在线五子棋</title>
    <style>
        body{
            background: #DFFFB1;
            font-family: 微软雅黑;
        }
        h4{
            text-align: center;
        }
        li{
            list-style-type: none;
        }
        a{
            text-decoration: none;
            color: #00a0e9;
        }
    </style>
</head>
<body>
        <h4>房间列表</h4>
        <ul>
            <?php for($i = 1; $i <= 100; $i++){  ?>
            <li><a href="room.php?room_id=<?= $i; ?>" target="_blank"><?= $i ?>号房间</a></li>
            <?php } ?>
        </ul>
</body>
</html>