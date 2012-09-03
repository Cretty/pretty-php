<?php
namespace net\shawn_huang\pretty\view;
use \net\shawn_huang\pretty as p;


class DebugView implements p\View {
	
	public function render(p\Action $aciton) {
		header('content-type:text/html;charset=utf8');
?><!DOCTYPE html>
<html>
	<head>
		<title>Pretty php debugger</title>
		<meta charset='utf-8' />
	</head>	
	<body>
	</body>
</html><?php
	}

}