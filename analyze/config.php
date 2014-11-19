<?php
define('DB_DNS', 'mysql:host=192.168.2.45;dbname=test');
define('DB_USER', 'test');
define('DB_PASS', 'test-045@');
define('DB_CHARSET', 'utf8');
define('DB_TABLE', 'activity_dimension2');
define('DB_TABLE_UID', 'haha');
define('IMG_PATH', '/home/liufang/webtest/analyze-view/analyze/img/');
define('IMG_TYPE', '.png');


require('mysql.php');
require('../jpgraph/src/jpgraph.php');
require('../jpgraph/src/jpgraph_line.php');
require('../jpgraph/src/jpgraph_bar.php');
