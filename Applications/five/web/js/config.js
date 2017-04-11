/**
 * Created by tan on 17-4-5.
 */

//全局配置文件

var site = "http://www.game.com/";      //网站根目录

var images = site + 'images/';          //图片路径

var ip = '127.0.0.1:8282';              //ip地址+端口

/************************配置房间信息*******************************/
var room = new Array();

var num = 1;
for(i = 0; i < 15; i++) {
    room[i] = new Object();
    room[i].name = (i <= 8 ?  '五子棋新手' : '五子棋高手') + eval(i + 1) + '区';
    room[i].children = new Array();
    for(j = 0; j < 10; j++) {
        room[i].children[j] = new Object();
        room[i].children[j].name = num + '号房间';
        room[i].children[j].href = site + 'room.php?room_id=' + num;
        num++;
    }
}

console.log(room);
