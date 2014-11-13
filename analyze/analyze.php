<?php
require('config.php');


//获取x轴
function get_x($index, $uid, $index2, $num=0)
{
	$db = DB::getInstance();
	switch ($index2) {
		case 'issign':
    		$fields = array('issign');
			break;
		case 'isclicked':
    		$fields = array('isclicked');
			break;
		default:
			break;
	}
	$params = array(
		'where' => array(
			'uid='.$uid,
			'ver=0',
		),
		'order' => $index . ' DESC', 
	);
	if (!empty($num)) $params['limit'] = $num;
	//uid有多个
	if ( strpos($uid, ',') !== false ) {
		$params['where'] = array(
			'uid in (' . $uid . ')',
			'ver=0',
		);
	}
	$re = $db->select(array(DB_TABLE), $fields, $params);
	return $re;
}

function get_y($x, $num, $index2)
{
	$i = 0;
	foreach ($x as $st) {
	switch ($index2) {
		case 'issign':
			$param = $st->issign;
			break;
		case 'isclicked':
			$param = $st->isclicked;
			break;
		default:
			break;
	}
		if ($param == 1) $i++; 
	}
	$y = $i / $num;
	return $y;
}

//获取前面几个uid
function get_tops($num)
{
	$db = DB::getInstance();
	$fields = array('count(issign) as a, uid');
	$params = array(
		'where'=>array(
			'issign=1',
		),
		'group' => 'uid', 
		'order' => 'a desc', 
		'limit' => $num,
	);
	$re = $db->select(array(DB_TABLE), $fields, $params);
	return $re;
}

//获取图表
function get_charts($uid, $index, $index2, $actnum, $type='nomal')
{
	//刻度
	$xscale = floor($actnum/10);
	//error_log('scale=='.var_export($xscale,true).chr(10),3,'/tmp/lf.log');
	// y 轴数据，以数组形式赋值  
	$ydata = array();
//$start = gettimeofday(true);
	$x = get_x($index, $uid, $index2);
//$waste = gettimeofday(true) - $start;
//	error_log('time=='.var_export($waste,true).chr(10),3,'/tmp/lf.log');
	$t = array();
	$xdata = array();  
	$i = 0;
	while($i<=$actnum){
		if ($i==0) {
			$ydata[] = 0;
			//$t = array_slice($x, 0, $i+$xscale);
			//$ydata[] = get_y($t, $i, $index2);
			$xdata[] = 0;
		} else {
			$t = array_slice($x, $l, $xscale);
			$ydata[] = get_y($t, $xscale, $index2);
			$xdata[] = $i;
		}
		$l = $i;
		$i = $i+$xscale;
	}

	//y轴自适应
	$yMax = max($ydata);
	$y    = $yMax + 0.05;
  
	// 创建 Graph 类，350 为宽度，250 长度，auto：表示生成的缓存文件名是该文件的文件名+扩展名(.jpg .png .gif ……)  
	$graph = new Graph(500,250,"auto");  
  
	// 设置刻度类型，x轴直线刻度，y轴为直线刻度  
	$graph->SetScale("textlin",0,$y);  
	//设置x轴最小刻度
	$graph->xaxis->scale->ticks->Set($xscale);
	$graph->xaxis->SetTickLabels($xdata); 
	//设置y轴最小刻度
	$graph->yaxis->scale->ticks->Set(0.05);

	//设置x轴文字
	$graph->xaxis->title->Set('top actid');
  
	// 创建坐标类，将y轴数据注入  
	$lineplot=new BarPlot($ydata);  
  
	// y 轴连线设定为蓝色  
	$lineplot->SetColor("blue");
  
	// 坐标类注入图标类  
	$graph->Add($lineplot);

	if ( strpos($uid, ',') === false) {
		$filename = $index . '-' . $index2 . '-' . $uid;
	} else {
		$filename = $index . '-' . $index2;
	}
	$graph->title->Set($filename);
  
  $img = '/home/liufang/webtest/analyze_1/img/'.$filename.'.png';
	// 显示图 
	$graph->Stroke($img);
	return array('name'=>$filename, 'src'=>$img);
}
$index = array(
	'docsimv',
	'feature1',
	'feature2',
	'feature3',
	'feature4',
	'feature5',
	'tagrel',
	'arearel',
	'kvalue',
);
$imgs = array();
$index2 = array('issign', 'isclicked');
$uid = isset($_POST['uid']) ? $_POST['uid'] : 0;
$actnum = isset($_POST['actnum']) ? $_POST['actnum'] : 0;
$userNum = isset($_POST['userNum']) ? $_POST['userNum'] : 0;
if ( empty($uid) && empty($actnum) && $userNum) { //多个用户
	$uids = get_tops($userNum);
	$uid = '';
	foreach ($uids as $one) {
		$uid .= $one->uid . ',';
	}
	$uid = trim($uid, ',');
	$actnum = '600';
}
foreach ($index2 as $i2) {
	foreach ($index as $i) {
//$start = gettimeofday(true);
		$imgs[] = get_charts($uid, $i, $i2, $actnum);
//$waste = gettimeofday(true) - $start;
	//error_log('time=='.var_export($waste,true).chr(10),3,'/tmp/lf.log');
	}
}
echo json_encode($imgs);
