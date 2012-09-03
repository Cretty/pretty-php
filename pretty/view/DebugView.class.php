<?php
namespace net\shawn_huang\pretty\view;
use \net\shawn_huang\pretty as p;


class DebugView implements p\View {
	
	public function render(p\Action $aciton = null) {
		header('content-type:text/html;charset=utf8');
?><!DOCTYPE html>
<html>
	<head>
		<title>Pretty php debugger</title>
		<meta charset='utf-8' />
	</head>	
	<body>
		<div>Error.</div>
		<?=print_r($_SERVER, 1)?>
	</body>
</html><?php
	}

}