layui.define(['jquery', 'layer'], function (exports) {
    var $ = layui.$;
    var layer = layui.layer;
    //插件名称
    var mod_name = "drawer";
    //外部接口
    var obj = {
        render: (i) => render(i),
    };

    //添加样式
    $("body").append(`<style type="text/css">
    .layui-layer.layui-layer-drawer {
        bottom: 0px;
        top: 0px;
        border: none!important;
        box-shadow: 1px 1px 50px rgba(0,0,0,.3)!important;
        overflow: auto
    }
    .layui-layer.layui-layer-drawer>.layui-layer-content,.layui-layer .layui-layer-drawer>.layui-layer-content>iframe {
        height:100%!important
    }
    .layui-layer.layui-layer-drawer>.layui-layer-title+.layui-layer-content {
        top: 0px;
        left: 0;
        right: 0;
        bottom: 0;
        height: auto!important
    }

    .layui-anim-rl {
        -webkit-animation-name: layui-rl;
        animation-name: layui-rl
    }
    @-webkit-keyframes layui-rl {
        from {
            -webkit-transform: translate3d(100%,0,0)
        }
        to {
            -webkit-transform: translate3d(0,0,0)
        }
    }    
    @keyframes layui-rl {
        from {
            transform: translate3d(100%,0,0)
        }
        to {
            transform: translate3d(0,0,0)
        }
    } 

    .layui-anim-lr{  
        -webkit-animation-name: layui-lr;
        animation-name: layui-lr;
    }
    @-webkit-keyframes layui-lr {
        from {
            -webkit-transform: translate3d(-300px,0,0);
            opacity: 1
        }    
        to {
            -webkit-transform: translate3d(0,0,0);
            opacity: 1
        }
    }    
    @keyframes layui-lr {
        from {
            transform: translate3d(-300px,0,0)
        }
        to {
            transform: translate3d(0,0,0)
        }
    }

    .layui-anim-down {
        -webkit-animation-name: layui-down;
        animation-name: layui-down
    }
    @-webkit-keyframes layui-down {
        from {
            -webkit-transform: translate3d(0, -300px, 0);
            opacity: .3
        }
        to {
            -webkit-transform: translate3d(0, 0, 0);
            opacity: 1
        }
    }
    @keyframes layui-down {
        from {
            transform: translate3d(0, -300px, 0);
            opacity: .3
        }
        to {
            transform: translate3d(0, 0, 0);
            opacity: 1
        }
    }
    </style>`);

    var render = function (e) {
        if (e.offset == "r"){
            e.skin = 'layui-anim layui-anim-rl layui-layer-drawer';
            if (!e.area)
                e.area = e.width ? [e.width, '100%'] : ['300px', '100%'];
        }
        else if (e.offset == "l"){
            e.skin = 'layui-anim layui-anim-lr layui-layer-drawer';
            if (!e.area)
                e.area = e.width ? [e.width, '100%'] : ['300px', '100%'];
        }
        else if(e.offset == "t"){
            e.skin = 'layui-anim layui-anim-down';
            if (!e.area)
                e.area = e.height ? ['100%', e.height] : ['100%', '300px'];
        }
        else if(e.offset == "b"){
            e.skin = 'layui-anim layui-anim-up';
            if (!e.area)
                e.area = e.height ? ['100%', e.height] : ['100%', '300px'];
        }

        var success = e.success;
        e.success = function (layero, index) {
            if (e.top != undefined)
                $(layero).css({ top: e.top });
            if (e.bottom != undefined)
                $(layero).css({ bottom: e.bottom });
            success && success();
        }
        var end = e.end;
        e.end = function () {
            layer.closeAll("tips");
            end && end();
        };

        layer.open($.extend({
            type: 1,
            id: 'layer-drawer',
            anim: -1,
            shade: 0.1,
            area : "336px",
            btnAlign: 'c',
            offset: 'r',
            skin: '', 
            content: '空',
            title: false,
            move: false,
            closeBtn: false, 
            isOutAnim: false, 
            shadeClose: true, 
        }, e));
    };

    exports(mod_name, obj);
});