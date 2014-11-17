<?php
ini_set('max_execution_time', '50');
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
	$fields[] = 'uid';
	$params = array(
		'where' => array(
			'uid='.$uid,
			'ver=0',
		),
		'order' => $index . ' DESC', 
	);
	if (!empty($num)) $params['limit'] = $num;
	//uid有多个
	if ( is_array($uid) ) {
		$uid = implode(',', $uid);
		$params['where'] = array(
			'uid in (' . $uid . ')',
			'ver=0',
		);
	}
	$re = $db->select(array(DB_TABLE), $fields, $params);
	return $re;
}

function get_y($x, $num, $index2, $type)
{
	$i = 0;
	foreach ($x as $st) {
	if ( $type == 'single') {
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
	} else if ( $type == 'multiple') {
		$param = $st;
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

//获取活动
function get_act($type, $uid, $index, $index2)
{
	if ($type == 'single') {
		$x = get_x($index, $uid, $index2); //获取活动
	} else if ( $type == 'multiple' ) {
		$x = get_x($index, $uid, $index2); //获取活动
		$new = array();
		foreach ($x as $stat) {
			if ( $index2 == 'issign') {
				$new[$stat->uid][] = $stat->issign;
			} else if ($index2 == 'isclicked') {
				$new[$stat->uid][] = $stat->isclicked;
			}
		}
	}
	if (!isset($new)) {
		$new = array();
	}
	return array('x'=>$x, 'new'=>$new);
}

//处理数据
function handle_data($type, $uid, $index, $index2, $xscale, $actnum, $x)
{
	// y 轴数据，以数组形式赋值
	$ydata = array();
	$t = array();
	$xdata = array();
	$i = 0;
	$uidNum = count($uid);
	$new = $x['new'];
	$x = $x['x'];
	while($i<=$actnum){
		if ($i==0) {
			$ydata[] = 0;
			$xdata[] = 0;
		} else {
			if ($type == 'single') {
				$t = array_slice($x, $l, $xscale);
				$ydata[] = get_y($t, $xscale, $index2, $type); //得到比例
			} else if ($type == 'multiple') {
				foreach ($new as $uids) {
					$t = array_slice($uids, $l, $xscale);
					$y[] = get_y($t, $xscale, $index2, $type); //得到比例
				}
				$sum = array_sum($y);
				$ydata[] = $sum / $uidNum;
				unset($y);
			}
			$xdata[] = $i;
		}
		$l = $i;
		$i = $i+$xscale;
	}
	return array('y'=>$ydata, 'x'=>$xdata);
}

//获取图表
function get_charts($uid, $index, $index2, $actnum, $type)
{
	//刻度
	$xscale = floor($actnum/10);
	$x = get_act($type, $uid, $index, $index2);
	$datas = handle_data($type, $uid, $index, $index2, $xscale, $actnum, $x);
	$ydata = $datas['y'];
	$xdata = $datas['x'];
//	error_log('datas==='.var_export($datas,true).chr(10),3,'/tmp/lf.log');

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

	if ( !is_array($uid) ) {
		$filename = $index . '-' . $index2 . '-' . $uid;
	} else {
		$filename = $index . '-' . $index2;
	}
	$graph->title->Set($filename);
  
  $img = IMG_PATH.$filename.IMG_TYPE;
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
$type = 'single';
if ( empty($uid) && $userNum) { //多个用户
	$uids = get_tops($userNum);
	$uid = array();
	foreach ($uids as $one) {
		$uid[] =  $one->uid;
	}
	$type = 'multiple';
}
foreach ($index2 as $i2) {
	foreach ($index as $i) {
		$imgs[] = get_charts($uid, $i, $i2, $actnum, $type);
	}
}
echo json_encode($imgs);
