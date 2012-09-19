<?php
namespace net\shawn_huang\pretty\view;
use \net\shawn_huang\pretty as p;


class DebugView implements p\View {
    
    private $data = array(
        'request.path' => '',
        'file' => array(),
        'class' => array()
    );
    public function render(p\Action $action) {
        header('http/1.1 404 not found.');
        header('content-type:text/html;charset=utf8');
        $this->data = $action->getData();

?><!DOCTYPE html>
<html>
    <head>
        <title>Pretty php debugger</title>
        <meta charset='utf-8' />
        <style type="text/css">
            table tr td {
                padding: 10px;
            }
            td b {
                color: red;
            }
            td i {
                color: green;
                font-style: normal;
                font-weight: bold;
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
                <td><?php echo $this->data['request.path']; ?></td>
            </tr>
            <tr>
                <td>file loading:</td>
                <td>
                    <?php $this->output('file'); ?>
                </td>
            </tr>
            <tr>
                <td>class searching:</td>
                <td>
                    <?php $this->output('class'); ?>
                </td>
            </tr>
        </table>
    </body>
</html><?php
    }

    private function output($type) {
        foreach($this->data[$type] as $k => $v) {
            echo '<p>' . $k . ' : ' . ($v ? '<i>OK</i>' : '<b>not found</b>') . '</p>';
        }
    }

}