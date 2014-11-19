<?php
ini_set('max_execution_time', '50');
ini_set('memory_limit', '500M');
require('config.php');


//获取活动
function get_act($uid, $index, $index2, $userNum)
{
	$db = DB::getInstance();
	$params = array(
		'where' => array(
			'uid='.$uid,
		),
		'order' => $index . ' DESC', 
	);
	if (!empty($num)) $params['limit'] = $userNum;
	$fields = array('uid', 'issign', 'isclicked');
	//uid有多个
	if ( is_array($uid) ) {
		$new = array();
		foreach ($uid as $ui) {
			$params = array(
				'where'=>array(
					'uid='.$ui,
				),
				'order' => $index . ' DESC',
			);
			$re = $db->select(array(DB_TABLE), $fields, $params);
			$new[$ui] = $re;
		}
        return $new;
	} else {
		$re = $db->select(array(DB_TABLE), $fields, $params);
		return $re;
	}
}

function get_y($x, $type)
{
	$i = 0;
	$l = 0;
	foreach ($x as $st) {
		if ( $type == 'single') {
			if ($st->issign == 1) $i++;
			if ($st->isclicked == 1) $l++;
		} else if ( $type == 'multiple') {
			foreach ($st as $s) {
				if ($s->issign == 1) $i++;
				if ($s->isclicked == 1) $l++;
			}
		}
	}
	$is_acts_num = array();
	$is_acts_num['issign'] = $i;
	$is_acts_num['isclicked'] = $l;
	return $is_acts_num;
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
		'order' => 'id', 
		'limit' => $num,
	);
	$re = $db->select(array(DB_TABLE_UID), $fields, $params);
	return $re;
}

//处理数据
//$actnum  前几个活动
//$userNum TOP个用户
function handle_data($type, $uid, $index, $xscale, $actnum, $x, $userNum)
{
	// y 轴数据，以数组形式赋值
	$ydata = array();
	$t = array();
	$xdata = array();
	$i = 0;
	if ($type == 'multiple') $x = array_slice($x, 0, $userNum);  //取出top个用户数据
	while($i<=$actnum){
		if ($i==0) {
			$ydata['isclicked'][] = 0;
			$ydata['issign'][] = 0;
			$xdata[] = 0;
		} else {
			if ($type == 'single') {
				$t = array_slice($x, $l, $xscale);
				$is_acts_num  = get_y($t, $type); //得到已报名或已点击
				$all_acts_num = $xscale; //总活动数
			} else if ($type == 'multiple') {
				$all_acts = array(); //这一段所有活动
				foreach ($x as $userid=>$acts) {
					$all_acts[] = array_slice($acts, $l, $xscale);
				}
				$is_acts_num = get_y($all_acts, $type); //已报名或已点击活动
				$all_acts_num = count($all_acts, 1); //总活动数
			}
			$ydata['isclicked'][] = $is_acts_num['isclicked'] / $all_acts_num;
			$ydata['issign'][] = $is_acts_num['issign'] / $all_acts_num;
			$xdata[] = $i;
		}
		$l = $i;
		$i = $i+$xscale;
	}
	return array(
		'isclicked' => $ydata['isclicked'],
		'issign' => $ydata['issign'],
		'x' => $xdata,
	);
}

//获取图表
//index2 array('issign', 'isclicked')
function get_charts($uid, $index, $index2, $actnum, $type, $userNum)
{
	//刻度
	$xscale = floor($actnum/10);
	$filenames = array();
	$imgs = array();

	$x = get_act($uid, $index, $index2, $userNum);
	$datas = handle_data($type, $uid, $index, $xscale, $actnum, $x, $userNum);
	foreach ($index2 as $i2) {
	$ydata = $datas[$i2];
	$xdata = $datas['x'];

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
//9个指标
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
$actnum = isset($_POST['actnum']) ? $_POST['actnum'] : 0; //前几个活动
$userNum = isset($_POST['userNum']) ? $_POST['userNum'] : 0; //top几个用户
$type = 'single';
if ( empty($uid) && $userNum) { //多个用户
	$uids = get_tops($userNum);
	$uid = array();
	foreach ($uids as $one) {
		$uid[] =  $one->uid;
	}
	$type = 'multiple';
}
foreach ($index as $i) {
	$imgs[] = get_charts($uid, $i, $index2, $actnum, $type, $userNum);
}
echo json_encode($imgs);
