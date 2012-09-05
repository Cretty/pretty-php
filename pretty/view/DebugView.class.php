<?php
namespace net\shawn_huang\pretty\view;
use \net\shawn_huang\pretty as p;


class DebugView implements p\View {
    
    public $data;

    public function render(p\Action $aciton = null) {
        header('content-type:text/html;charset=utf8');
?><!DOCTYPE html>
<html>
    <head>
        <title>Pretty php debugger</title>
        <meta charset='utf-8' />
        <style type="text/css">
            table tr td {
                padding: 10px;
            }
        </style>
    </head>    
    <body>
        <table border='1' style="margin:auto; width: 90%">
            <thead>
                <tr>
                    <td colspan='2' align='center'>Error.</td>
                </tr>
            </thead>
            <tr>
                <td>request url:</td>
                <td><?php echo $_SERVER['PATH_INFO'];?></td>
            </tr>
            <tr>
                <td>file loading:</td>
                <td><?php foreach($this->data['files'] as $k => $v) { ?>
                    <p><?php echo $k?> : <?php echo ($v ? 'true': 'false');?></p> <?php } ?>
                </td>
            </tr>
            <tr>
                <td>class searching:</td>
                <td><?php foreach($this->data['class'] as $k => $v) { ?>
                   <p><?php echo $k?> : <?php echo ($v ? 'true': 'false');?></p> <?php }?>
                </td>
            </tr>
        </table>
    </body>
</html><?php
    }

}