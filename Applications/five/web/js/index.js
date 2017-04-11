/**
 * Created by tan on 17-4-10.
 */
window.onload = function () {
    ;!function(){
        //当使用了 layui.all.js，无需再执行layui.use()方法
        var from = layui.form()
            ,layer = layui.layer;

        //welcome();
        room_list()


    }();

    //欢迎信息
    function welcome(){
        layer.open({
            title: '欢迎'
            ,content: '即时五子棋, 祝你愉快~!'
        });
    }

    //房间列表
    function room_list(){
        layui.tree({
            elem: '#demo' //传入元素选择器
            ,nodes: room
            ,target: '_blank'
            ,
        });
    }
}