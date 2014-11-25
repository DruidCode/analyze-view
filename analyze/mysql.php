<?php
/**
 * 使用了单例模式
 * 连接mysql
 * 使用方法: $db = DB::getInstance();
 *
 */
class DB {
    //pdo对象
    private $_pdo = null;
    //用于存放实例化的对象
    private static $_instance = null;

    //公共静态方法获取实例化的对象
    public static function getInstance() {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    //私有克隆 禁止克隆
    private function __clone() {}

    //私有构造
    private function __construct() {
        try {
            $this->_pdo = new PDO(DB_DNS, DB_USER, DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES '.DB_CHARSET));
            $this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }

    //新增
    public function add($_tables, Array $_addData) {
        $_addFields = array();
        $_addValues = array();
        foreach ($_addData as $_key=>$_value) {
            $_addFields[] = $_key;
            $_addValues[] = $_value;
        }
        $_addFields = implode(',', $_addFields);
        $_addValues = implode("','", $_addValues);
        $_sql = "INSERT INTO $_tables[0] ($_addFields) VALUES ('$_addValues')";
        return $this->execute($_sql)->rowCount();
    }

    //修改
    public function update($_tables, Array $_param, Array $_updateData) {
        $_where = $_setData = '';
        foreach ($_param as $_key=>$_value) {
            $_where .= $_value.' AND ';
        }
        $_where = 'WHERE '.substr($_where, 0, -4);
        foreach ($_updateData as $_key=>$_value) {
            if (is_array($_value)) {
                $_setData .= "$_key=$_value[0],";
            } else {
                $_setData .= "$_key='$_value',";
            }
        }
        $_setData = substr($_setData, 0, -1);
        $_sql = "UPDATE $_tables[0] SET $_setData $_where";
        return $this->execute($_sql)->rowCount();
    }

    //验证一条数据
    public function isOne($_tables, Array $_param) {
        $_where = '';
        foreach ($_param as $_key=>$_value) {
            $_where .=$_value.' AND ';
        }
        $_where = 'WHERE '.substr($_where, 0, -4);
        $_sql = "SELECT id FROM $_tables[0] $_where LIMIT 1";
        return $this->execute($_sql)->rowCount();
    }

    //删除
    public function delete($_tables, Array $_param) {
        $_where = '';
        foreach ($_param as $_key=>$_value) {
            $_where .= $_value.' AND ';
        }
        $_where = 'WHERE '.substr($_where, 0, -4);
        $_sql = "DELETE FROM $_tables[0] $_where LIMIT 1";
        return $this->execute($_sql)->rowCount();
    }

    //查询
    public function select($_tables, Array $_fileld, Array $_param = array()) {
        $_limit = $_order = $_where = $_like = $_other = '';
        if (is_array($_param) && !empty($_param)) {
            $_limit = isset($_param['limit']) ? 'LIMIT '.$_param['limit'] : '';
            $_order = isset($_param['order']) ? 'ORDER BY '.$_param['order'] : '';
            $_group = isset($_param['group']) ? 'GROUP BY '.$_param['group'] : '';
            if (isset($_param['where'])) {
                foreach ($_param['where'] as $_key=>$_value) {
                    $_where .= $_value.' AND ';
                }
                $_where = 'WHERE '.substr($_where, 0, -4);
            }
            if (isset($_param['like'])) {
                foreach ($_param['like'] as $_key=>$_value) {
                    $_like = "WHERE $_key LIKE '%$_value%'";
                }
            }
			if (isset($_param['other'])) {
				$_other = $_param['other'];
			}
        }
        $_selectFields = implode(',', $_fileld);
        $_table = isset($_tables[1]) ? $_tables[0].','.$_tables[1] : $_tables[0];
        $_sql = "SELECT $_selectFields FROM $_table $_other $_where $_like $_group $_order $_limit";
		//error_log('sql===='.var_export($_sql,true).chr(10),3,'/tmp/lf.log');
        $_stmt = $this->execute($_sql);
        $_result = array();
        while (!!$_objs = $_stmt->fetchObject()) {
            $_result[] = $_objs;
        }
        //return Tool::setHtmlString($_result);
        return $_result;
    }

    //总记录
    public function total($_tables, Array $_param = array()) {
        $_where = '';
        if (isset($_param['where'])) {
            foreach ($_param['where'] as $_key=>$_value) {
                $_where .= $_value.' AND ';
            }
            $_where = 'WHERE '.substr($_where, 0, -4);
        }
        $_sql = "SELECT COUNT(*) as count FROM $_tables[0] $_where";
        $_stmt = $this->execute($_sql);
        return $_stmt->fetchObject()->count;
    }

    //得到下一个ID
    public function nextId($_tables) {
        $_sql = "SHOW TABLE STATUS LIKE '$_tables[0]'";
        $_stmt = $this->execute($_sql);
        return $_stmt->fetchObject()->Auto_increment;
    }


    //执行SQL
    private function execute($_sql) {
        try {
            $_stmt = $this->_pdo->prepare($_sql);
            $_stmt->execute();
        } catch (PDOException  $e) {
            exit('SQL语句：'.$_sql.'<br />错误信息：'.$e->getMessage());
        }
        return $_stmt;
    }
}
