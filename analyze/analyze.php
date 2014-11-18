<?php
ini_set('max_execution_time', '50');
ini_set('memory_limit', '500M');
require('config.php');


//获取x轴
function get_x($index, $uid, $index2, $userNum, $num=0)
{
	$db = DB::getInstance();
	/*
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
	*/
	$fields = $index2;
	$tmp = 'uid';
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
		$tmp = 'a.uid';
		$uid = implode(',', $uid);
		$params = array(
			'other' => ' as a INNER JOIN (SELECT uid FROM haha LIMIT '.$userNum.') as b ON a.uid = b.uid AND ver=0  ORDER BY '.$index.' DESC',
		);
	}
	$fields[] = $tmp;
	$start = gettimeofday(true);
	$re = $db->select(array(DB_TABLE), $fields, $params);
	$time = gettimeofday(true) - $start;
	error_log('sql time==='.var_export($time,true).chr(10),3,'/tmp/lf.log');
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
	/*
	$fields = array('count(issign) as a, uid');
	$params = array(
		'where'=>array(
			'issign=1',
		),
		'group' => 'uid', 
		'order' => 'a desc', 
		'limit' => $num,
	);
	*/
	$fields = array('uid');
	$params = array(
		'limit' => $num,
	);
	$re = $db->select(array(DB_TABLE), $fields, $params);
	return $re;
}

//获取活动
function get_act($type, $uid, $index, $index2, $userNum)
{
	if ($type == 'single') {
		$x = get_x($index, $uid, $index2, $userNum); //获取活动
	} else if ( $type == 'multiple' ) {
		$x = get_x($index, $uid, $index2, $userNum); //获取活动
		$new = array();
		//$start = gettimeofday(true);
		foreach ($x as $stat) {
			$new['issign'][$stat->uid][] = $stat->issign;
			$new['isclicked'][$stat->uid][] = $stat->isclicked;
		}
		//$time = gettimeofday(true)-$start;
	//error_log('foreach一次时间==='.var_export($time,true).chr(10),3,'/tmp/lf.log');
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
	//$new = $x['new'][$index2];
	$xd = $x['x'];
	while($i<=$actnum){
		if ($i==0) {
			$ydata[] = 0;
			$xdata[] = 0;
		} else {
			if ($type == 'single') {
				$t = array_slice($xd, $l, $xscale);
				$ydata[] = get_y($t, $xscale, $index2, $type); //得到比例
			} else if ($type == 'multiple') {
				foreach ($x['new'][$index2] as $uids) {
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
//index2 array('issign', 'isclicked')
function get_charts($uid, $index, $index2, $actnum, $type, $userNum)
{
	//刻度
	$xscale = floor($actnum/10);
	$start = gettimeofday(true);
	$x = get_act($type, $uid, $index, $index2, $userNum);
	$time = gettimeofday(true) - $start;
	error_log('获取活动一次时间==='.var_export($time,true).chr(10),3,'/tmp/lf.log');
	$filenames = array();
	$imgs = array();
	foreach ($index2 as $i2) {
	$datas = handle_data($type, $uid, $index, $i2, $xscale, $actnum, $x);
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
		$filename = $index . '-' . $i2 . '-' . $uid;
	} else {
		$filename = $index . '-' . $i2;
	}
	$graph->title->Set($filename);
  
  $img = IMG_PATH.$filename.IMG_TYPE;
	$filenames[] = $filename;
	$imgs[] = $img;
	// 显示图 
	$graph->Stroke($img);
	}
	return array('name'=>$filenames);
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
$start = gettimeofday(true);
foreach ($index as $i) {
	$imgs[] = get_charts($uid, $i, $index2, $actnum, $type, $userNum);
}
$time = gettimeofday(true) - $start;
	error_log('all time==='.var_export($time,true).chr(10),3,'/tmp/lf.log');
echo json_encode($imgs);
