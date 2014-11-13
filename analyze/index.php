<?php
require('config.php');

function get_uids()
{
	$db = DB::getInstance();
	$fields = array('count(issign) as a, uid');
	$params = array(
		'where'=>array(
			'issign=1',
		),
		'group' => 'uid', 
		'order' => 'a desc', 
	);
	$re = $db->select(array(DB_TABLE), $fields, $params);
	return $re;
}
$uids = get_uids();
?>
<html>
	<head>
		<title>数据分析</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="shortcut icon" href="favicon.ico" />
		<link rel="stylesheet" type="text/css" href="static/css/index.css" />
	</head>
	<body>
	<p id="back-to-top"><a href="#top"><span></span>返回顶部</a></p>
	<span id="load">数据加载中请稍后。。。</span>
	<span id="load-over">数据加载完毕。。。</span>
	<div class='left'>
	前: <select name='actnum' id='actnum'>
		<?php  
			for ($i=0;$i<601;$i=$i+5) {
				if ($i == 100) {
		?>
				<option value ="<?php echo $i;?>" selected><?php echo $i;?></option>
		<?php } else {?>
				<option value ="<?php echo $i;?>"><?php echo $i;?></option>
		<?php	}}?>
			</select>个活动
		<div id = 'btn'>
	<?php  foreach ($uids as $uid) {?>
		<input value =<?php echo $uid->uid;?> type="button" name = 'uid' class="button"/><br/>
	<?php } ?>
		</div>
	</div>
	<div class='top'>
	TOP:
		<select name='button_top' class="button_top">
		<?php  
			for ($l=0;$l<601;$l=$l+5) {
		?>
				<option value ="<?php echo $l;?>"><?php echo $l;?></option>
		<?php } ?>
		</select>
	</div>
	<div class="im">
	</div>
		<script src="static/js/jquery.js"></script>
		<script src="static/js/index.js"></script>
	</body>
</html>
