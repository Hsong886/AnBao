<?php

namespace GatewayWorker\Lib;

use Exception;
use PDO;
use PDOException;
 

class DbModel
{

    protected $filterFieldNotice = true;
    private $pk = ''; //主键
    private $querySql = '';  //执行的sql语句
    private $columns = '';  //表中所有字段
    private $logic = ' AND ';
    protected $sql = [
        'select' => '*',
        'from' => '',
        'where' => [],
        'order' => '',
        'limit' => '',
        'group' => '',
        'having' => '',
        'join' => [],
        'alias' => '',
    ];
    //protected $db;
    protected $table = '';
    protected $res;
    protected $asArray = false;
    protected $data = [];
    protected $tablepre = '';

    /**
     * pdo 实例
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * PDOStatement 实例
     *
     * @var \PDOStatement
     */
    protected $sQuery;

    /**
     * 数据库用户名密码等配置
     *
     * @var array
     */
    protected $settings = array();

    /**
     * 构造函数
     *
     * @param string $host
     * @param int    $port
     * @param string $user
     * @param string $password
     * @param string $db_name
     * @param string $charset
     */
    public function __construct($host, $port, $user, $password, $db_name, $tablepre = '', $charset = 'utf8mb4')
    {
        $this->settings = array(
            'host'     => $host,
            'port'     => $port,
            'user'     => $user,
            'password' => $password,
            'dbname'   => $db_name,
            'charset'  => $charset,
        );
        $this->tablepre = $tablepre;
        $this->connect();
    }

    /**
     * 创建 PDO 实例
     */
    protected function connect()
    {
        $dsn       = 'mysql:dbname=' . $this->settings["dbname"] . ';host=' .
            $this->settings["host"] . ';port=' . $this->settings['port'];
        $this->pdo = new PDO($dsn, $this->settings["user"], $this->settings["password"],
            array(
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . (!empty($this->settings['charset']) ?
                        $this->settings['charset'] : 'utf8')
            ));
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    /**
     * 关闭连接
     */
    public function closeConnection()
    {
        $this->pdo = null;
    }

    /**
     * 查询时记录是否转换为关联数组
     */
    public function asArray($flag = true)
    {
        $this->asArray = $flag;
        return $this;
    }

    /**
     * 执行sql语句
     * $sql参数string----执行的sql语句
     * 返回PDOStatement对象
     */
    public function query($sql)
    {
//        if (Config::get('debug')) {
//            Log::set('sql: ' . $sql);
//        }
        $this->querySql = $sql;
        try {
            $this->res = $this->pdo->query($sql);
        } catch (PDOException $e) {
            $this->res = false;
        } catch (Exception $e) {
            $this->res = FALSE;
        }
        //$this->res = $this->pdo->query($sql);
//        if ($this->pdo->errorCode() != '00000' && Config::get('debug')) {
//            $e = $this->pdo->errorInfo();
//            //Log::set('sqlError: ' . $e[2], 1);
//        }
        $this->refactor();
        return $this->res;
    }

    /**
     * 指定查询字段(并可以为字段取别名)
     * $select参数string,array----字段列表
     * $flag参数是boolean型决定$select是否为排除的字段
     */
    public function select($select = "*", $flag = false)
    {
        if ($select == '*') {
            $this->sql['select'] = $select;
            return $this;
        }
        if ($flag) {
            //字段排除法
            $columns = $this->columns or $this->getFields();
        }
        if (is_string($select)) {
            $select = explode(',', $select);
        }
        $temp = [];
        foreach ($select as $key => $v) {
            $field = $this->handleField($v);
            if ($flag) {
                $index = array_search($v, $columns);
                if ($index !== false) {
                    unset($columns[$index]);
                }
            } else {
                if (!is_numeric($key)) {
                    $temp [] = $field . ' AS ' . $key;
                } else {
                    $temp [] = $field;
                }
            }
        }
        if ($flag) {
            $temp = $columns;
        }
        $this->sql['select'] = join(',', $temp);
        return $this;
    }

    /**
     * 更新记录
     * 条件没有不作处理
     * 返回所更新的行数
     */
    public function update($data = array())
    {
        if (empty($this->sql['from'])) {
            $this->from($this->table);
        }
        if (!empty($data)) {
            $this->data($data);
        }
        if (empty($this->data)) {
            return false;
        }
        if (isset($this->data[$this->pk])) {
            $tj[$this->pk] = $this->data[$this->pk];
            unset($this->data[$this->pk]);
            $this->where($tj);
        }
        $sql = 'UPDATE ' . str_replace('FROM', ' ', $this->sql['from']) . ' SET ' . $this->parseData();
        $where = $this->checkWhere();
        if (empty($where))
            return false;
        $sql .= $where;
        $this->query($sql);
        if (!$this->res) {
            return false;
        }
        return $this->res->rowCount();
    }

    /**
     * 新增记录
     * 返回所添加记录对应的主键值
     */
    public function insert($data = array())
    {
        if (empty($this->sql['from'])) {
            $this->from($this->table);
        }
        if (!empty($data)) {
            $this->data($data);
        }
        if (empty($this->data)) {
            return false;
        }
        $sql = 'INSERT INTO ' . str_replace('FROM', ' ', $this->sql['from']) . ' SET ' . $this->parseData();
        $this->query($sql);
        if (!$this->res) {
            return false;
        }
        return $this->pdo->lastInsertId();
    }

    /**
     * 添加多条记录
     * @param type $data
     * @return
     */
    public function insertAll($data = array())
    {
        if (count($data, 1) == count($data)) {
            //\core\Debug::errorHandler(8, 'insertAll method first argument need array ', __FILE__, __LINE__);
            return false;
        }
        $field = [];
        $values = [];
        foreach ($data as $v) {
            if (!$field) {
                $field = array_keys($v);
            }
            $s = '(';
            foreach ($v as $val) {
                $s.=(is_numeric($val) ? $val : $this->quote($val) ) . ',';
            }
            $s = rtrim($s, ',');
            $s.=')';
            $values[] = $s;
        }
        $values = join(',', $values);
        $field = join(',', $field);
        $sql = 'INSERT INTO `' . $this->tablepre . $this->table . '`(' . $field . ')';
        $sql .= ' VALUES' . $values;
        $this->querySql = $sql;
        $res = $this->query($sql);
        if ($res !== false) {
            return $res->rowCount();
        }
        return false;
    }

    /*
     * type  修改指定地段
     *
     * $data = ['keys' => ['uid', 'fuid' , 'hid'] ,  'typeone' => 'type' , 'arr' => array([1, 2, 4, 3], [2, 3, 5, 3])];  //条件
     *
     * type = 字段名
     * condition = 条件
     * */
    public function UpdateAll ($data = array())
    {
        if (empty($data)) {
            return false;
        }

        if (!is_array($data)) {
            return false;
        }

        $condition = $data['keys'];
        if (count($condition) != 1) {
            $list = [];
            foreach ($data['keys'] as $k => $v) {
                foreach ($data['arr'] as $j => $p) {
                    $list[$k][] = $v.' = '.$p[$k];
                }
            }

            $ab = [];
            foreach ($list as $k => $v) {
                foreach ($v as $j => $p) {
                    $ab[$j][] = $p;
                }

            }
            foreach ($ab as $k => $v) {
                $arr[] = ' WHEN '.implode(' AND ' , $v).' THEN ' . $data['arr'][$k][count($data['keys'])];
            }


            $when = implode(' ' , $arr) . ' END ';
            if (empty($condition)) {
                return false;
            } else {

                foreach ($condition as $k => $v) {
                    $key = array_search($v , $data['keys']);

                    foreach ($data['arr'] as $j => $p) {
                        $vs[$v][] = $p[$key];
                    }

                }

                if (is_array($vs)) {
                    $ff = [];

                    $num = count($condition);
                    $ii = 0;
                    foreach ($vs as $i => $o) {
                        $ii++;
                        $jj = $num != $ii ? ' AND ' : '';
                        $ff[] = $i . ' IN ' . "(" . implode(',' , $o) . ") " . $jj;
                    }
                    $ff = implode('' , $ff);
                }
            }

            $type = $data['typeone'];
            $sql = "UPDATE " . $this->tablepre . $this->table ." SET $type = case"  .$when. " WHERE $ff";
        } else {
            $sql = '';
            $keys = $data['keys']['0'];
            foreach ($data['arr'] as $k => $v) {
                $uids[] = $v[0];
            }
            $t = $condition[0];
            $uid = "where $t in (" .implode(',' , $uids).')';
            foreach ($data['typeone'] as $k => $v)  {


                $sql .= $v . " = case $keys ";

                foreach ($data['arr'] as $i => $o) {
                    $sql .= " WHEN $uids[$i] THEN " . $data['arr'][$i][$k+1];
                }
                if ($k == (count($data['typeone']) - 1)) {
                    $sql .= ' END';
                } else {
                    $sql .= ' END,';
                }
            }
            $sql = "UPDATE " . $this->tablepre . $this->table ." SET $sql $uid";
        }

        $res = $this->query($sql);
        if ($res !== false) {
            return $res->rowCount();
        }
        return false;
    }




    /**
     * 新增或更新记录
     *
     */
    public function keyUpdate($data = array())
    {
        if (empty($this->sql['from'])) {
            $this->from($this->table);
        }
        if (empty($this->data)) {
            return false;
        }
        if (empty($data)) {
            return false;
        }

        $t = [];
        foreach ($data as $k => $v) {
            $icon = '=';
            if (is_array($v)) {
                $icon = $v[0];
                $v = $v[1];
            }
            if (!is_numeric($v)) {
                continue;
            }
            if ($icon == '=') {
                $t[$k] = "`{$k}`=" . $v;
            }
            else {
                $t[$k] = "`{$k}`=`{$k}`{$icon}" . $v;
            }
        }
        if (empty($t)) {
            return false;
        }

        $data = implode(' , ', $t);

        $sql = 'INSERT INTO ' . str_replace('FROM', ' ', $this->sql['from']);
        $sql .= ' SET ' . $this->parseData();
        $sql .= ' ON DUPLICATE KEY UPDATE ' . $data;
        $this->query($sql);
        if (!$this->res) {
            return false;
        }
        return true;
    }

    /**
     * 处理带前缀表名
     * $form参数string---除前缀以外的表名
     */
    public function from($form = '')
    {
        $this->sql['from'] = 'FROM `' . $this->tablepre . $form . '`';
        return $this;
    }

    /**
     * 设置表名
     */
    public function table($dbname)
    {
        if ($this->table !== $dbname) {
            $this->table = $dbname;
            $this->getFields();
        }
        return $this;
    }

    /**
     * 构造sql语句条件
     * $where参数为string,array指定条件元素
     */
    public function where($where = '')
    {
        if (!empty($where)) {
            if (is_string($where)) {
                $this->sql['where'][] = $where;
            } else {
                $this->sql['where'][] = $this->parseWhere($where);
            }
        }
        return $this;
    }

    private function parseWhere($where, $index = '')
    {
        if (isset($where['logic'])) {
            $logic = $where['logic'];
            unset($where['logic']);
        } else {
            $logic = 'and';
        }
        $logic = ' ' . strtoupper($logic) . ' ';
        $temp = '';
        foreach ($where as $key => $v) {
            if (is_array($v) && is_array($v[0]) && count($v, 1) != count($v)) {
                $temp.=($temp ? $logic : '') . '(' . $this->parseWhere($v, $key) . ')';
            } else {
                $v1 = [];
                $v1[] = $index ? $index : $key;
                if (!is_array($v)) {
                    $v = array('=', $v);
                }
                $arr = array_merge($v1, $v);
                $temp .= ($temp ? $logic : '') . $this->getCondition($arr);
            }
        }
        return $temp;
    }

    /**
     * 处理条件元素
     * $where参数array
     * 返回条件字符串
     */
    private function getCondition($where = array())
    {
        $count = count($where);
        $whereStr = $value = '';
        if ($count == 3) {
            $whereStr = $this->handleField($where[0]) . ' ' . $where[1] . ' ';
            $where[1] = strtoupper($where[1]);
            switch ($where[1]) {
                case '=':
                case '!=':
                case '>=':
                case '<=':
                case '>':
                case '<':
                    $value = is_numeric($where[2]) ? $where[2] : $this->pdo->quote($where[2]);
                    break;
                case 'BETWEEN':
                case 'NOT BETWEEN':
                    $v1 = is_numeric($where[2][0]) ? $where[2][0] : $this->pdo->quote($where[2][0]);
                    $v2 = is_numeric($where[2][1]) ? $where[2][1] : $this->pdo->quote($where[2][1]);
                    $value = $v1 . ' AND ' . $v2;
                    break;
                case 'IN':
                case 'NOT IN':
                    foreach ($where[2] as &$w) {
                        $w = is_numeric($w) ? $w : $this->pdo->quote($w);
                    }
                    $value = ' (' . implode(',', $where[2]) . ')';
                    unset($w);
                    break;
                case 'LIKE':
                case 'NOT LIKE':
                    $value = is_numeric($where[2]) ? $where[2] : $this->pdo->quote($where[2]);
                    break;
                default:
                    throw new Exception("type empty");
                //Log::set('sqlError: The first element array is wrong(' . $where[1] . ')', 1);
            }
        }
        return $whereStr . $value;
    }

    /**
     * 绑定更新与添加的数据
     * $data参数array
     */
    public function data($data = array())
    {
        if (empty($data)) {
            return false;
        }
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                if (!in_array($k, $this->columns, true)) {
                    unset($data[$k]);
                    if ($this->filterFieldNotice) {
                        //\core\Debug::errorHandler(8, $k . ' field name does not exist', __FILE__, __LINE__);
                    }
                }
            }
            $this->data = $data;
        }
        return $this;
    }

    /**
     * 查询排序
     * $orderby参数string
     */
    public function order($orderby = '')
    {
        if (empty($orderby)) {
            return $this;
        }
        $this->sql['order'] = 'ORDER BY ' . $orderby;
        return $this;
    }

    /**
     * 限定行数
     * $limit参数string,array
     */
    public function limit($limit = 10)
    {
        if (empty($limit)){
            return $this;
        }
        if (is_array($limit)) {
            $limit = (int) $limit[0] . ',' . (int) $limit[1];
            ;
        }
        $this->sql['limit'] = 'LIMIT ' . $limit;
        return $this;
    }

    /**
     * 查询单条记录，或者某个字段的值
     * $flag参数boolean当查询一个字段时是否返回具体的值
     * 返回一维数组或者一个对象或者标量
     */
    public function one($flag = false)
    {
        if (empty($this->sql['from'])) {
            $this->from($this->table);
        }
        $this->limit(1);
        $where = $this->checkWhere();
        $sql = 'SELECT ' . $this->sql['select'];
        $sql .= ' ' . $this->sql['from'];
        $sql .= $where;
        $sql .= $this->sql['group'] . $this->sql['having'];
        $sql .= ' ' . $this->sql['order'];
        $sql .= ' ' . $this->sql['limit'];
        $this->query($sql);
        if (!$this->res) {
            return false;
        }
        if ($this->asArray) {
            $this->res->setFetchMode(\PDO::FETCH_ASSOC);
        } else {
            $this->res->setFetchMode(\PDO::FETCH_OBJ);
        }
        $temp = $result = $this->res->fetch();
        if (empty($result))
            return $result;
        $this->asArray() && settype($temp, 'array');
        if ($flag && count($temp) == 1) {
            return current($result);
        }
        return $result;
    }

    /**
     * 查询多条记录
     * $key参数string指定要作为数组的下标的字段名
     * 返回二维数组或者一维数组
     */
    public function all($key = '')
    {
        if (empty($this->sql['from'])) {
            $this->from($this->table);
        }
        $sql = 'SELECT ' . $this->sql['select'];
        $where = $this->checkWhere();
        $sql .= ' ' . $this->sql['from'];
        $sql .= $this->sql['alias'];
        if (!empty($this->sql['join'])) {
            $sql .= join('', $this->sql['join']);
        }
        $sql .= $where;

        $sql .= $this->sql['group'] . $this->checkWhere('having');

        $sql .= ' ' . $this->sql['order'];
        $sql .= ' ' . $this->sql['limit'];
        $this->query($sql);
        if (!$this->res) {
            return false;
        }
        if ($this->asArray || !empty($key)) {
            $this->res->setFetchMode(\PDO::FETCH_ASSOC);
        } else {
            $this->res->setFetchMode(\PDO::FETCH_OBJ);
        }
        $results = $this->res->fetchAll();
        if (empty($results)) {
            return $results;
        }
        $firstRow = $results[0];
        setType($firstRow, 'array');
        $fileNum = count($firstRow);
        if (empty($key) && $fileNum > 1) {
            return $results;
        }
        $temp = [];
        foreach ($results as $row) {
            setType($row, 'array');
            if (isset($row[$key])) {
                $index = $row[$key];
            }
            if ($fileNum == 2) {
                $row = end($row);
                $temp[$index] = $row;
            } else if ($fileNum == 1) {
                $temp[] = current($row);
            } else {
                $temp[$index] = $row;
            }
        }
        return $temp;
    }

    /**
     * 计数
     * $field参数字符串，列名
     * 返回数值型
     */
    public function count($field = '*')
    {
        return $this->aggregate('COUNT', $field);
    }

    /**
     * 求和
     * $field参数字符串，列名
     * 返回数值型
     */
    public function sum($field)
    {
        return $this->aggregate('SUM', $field);
    }

    /**
     * 求平均值
     * $field参数字符串，列名
     * 返回数值型
     */
    public function avg($field)
    {
        return $this->aggregate('AVG', $field);
    }

    /**
     * 求最大值
     * $field参数字符串，列名
     * 返回数值型
     */
    public function max($field)
    {
        return $this->aggregate('MAX', $field);
    }

    /**
     * 求最小值
     * $field参数字符串，列名
     * 返回数值型
     */
    public function min($field)
    {
        return $this->aggregate('MIN', $field);
    }

    /**
     * 聚合函数处理
     * $fn参数string指定聚合函数名SUM,COUNT,AVG,MAX,MIN
     * $field参数string指定列名
     * 返回数值型
     */
    private function aggregate($fn, $field)
    {
        if (empty($this->sql['from'])) {
            $this->from($this->table);
        }
        $sql = 'SELECT ' . $fn . '(' . $field . ') as field ' . $this->sql['from'];
        $where = $this->checkWhere();
        $sql .= $where;
        $this->query($sql);
        if (!$this->res) {
            return false;
        }
        $this->res->setFetchMode(\PDO::FETCH_OBJ);
        $result = $this->res->fetch();
        return $result->field;
    }

    /**
     * 删除记录,可以根据主键(支持单一主键)值来删除
     * $id可以是单个值，也可以是数组
     * 条件为空则不作处理
     * 返回删除的行数(数值型)
     */
    public function delete($id = '')
    {


        if (empty($this->sql['from'])) {
            $this->from($this->table);
        }
        $sql = 'DELETE ' . $this->sql['from'];

        if (!empty($id)) {
            if (is_array($id)) {
                $where[$this->pk] = array('IN', $id);
            } else {
                $where[$this->pk] = $id;
            }
            $this->where($where);
        }
        $where = $this->checkWhere();

        if (empty($where))
            return false;
        $sql .= $where;
        $sql .= $this->sql['limit'];
        $this->query($sql);
        if (!$this->res) {
            return false;
        }
        return $this->res->rowCount();
    }

    /**
     * 拼接where
     * 返回整个条件字符串
     */
    private function checkWhere($key = 'where')
    {
        if (!isset($this->sql[$key]['logic'])) {
            if (!is_array($this->sql[$key])) {
                $this->sql[$key] = [];
            }
            $this->sql[$key]['logic'] = 'and';
        }
        $logic = ' ' . strtoupper($this->sql[$key]['logic']) . ' ';
        unset($this->sql[$key]['logic']);
        $where = '';
        foreach ($this->sql[$key] as $val) {
            $where.=($where ? $logic : '') . $val;
        }
        if (!empty($where)) {
            $where = ($key == 'where' ? ' WHERE ' : ' HAVING ') . $where;
        }
        return $where;
    }

    /**
     * 是逻辑与还是逻辑或
     */
    function logic($logic = 'AND')
    {
        $logic = strtoupper($logic);
        $this->logic = ' ' . $logic . ' ';
        return $this;
    }

    /**
     * 字段值自增
     * $data参数为array,指定列名
     * 返回所影响的行数
     */
    public function increment($data = [])
    {
        return $this->crement($data);
    }

    /**
     * 字段值自减
     * $data参数为array,指定列名
     * 返回所影响的行数
     */
    public function decrement($data = [])
    {
        return $this->crement($data, '-');
    }

    private function crement($data = [], $icon = '+')
    {
        if (empty($this->sql['from'])) {
            $this->from($this->table);
        }
        if (empty($data)) {
            return false;
        }
        if (isset($data[$this->pk])) {
            $tj[$this->pk] = $data[$this->pk];
            unset($data[$this->pk]);
            $this->where($tj);
        }
        $where = $this->checkWhere();
        if (empty($where))
            return false;
        $t = [];
        foreach ($data as $k => $v) {
            if (!is_numeric($v)) {
                continue;
            }
            $t[$k] = "`{$k}`=`{$k}`{$icon}" . $v;
        }

        if (empty($t)) {
            return false;
        }

        $data = implode(' , ', $t);
        $sql = 'UPDATE ' . str_replace('FROM', ' ', $this->sql['from']) . ' SET ' . $data;
        $sql .= $where;
        $this->query($sql);
        if (!$this->res) {
            return false;
        }
        return $this->res->rowCount();
    }

    /**
     * sql过滤
     * $str参数string，要处理的sql内容
     * 返回string
     */
    function quote($str = '')
    {
        if (empty($str)) {
            return '';
        }
        return $this->pdo->quote($str);
    }

    /**
     * 分组group by
     * $field参数为string,指定分组字段
     */
    function group($field = '')
    {
        if (!empty($field)) {
            $this->sql['group'] = ' GROUP BY ' . $field;
        }
        return $this;
    }

    /**
     * 分组过滤having
     * $where参数string,array构造条件
     */
    function having($where = '')
    {
        if (!empty($where)) {
            if (!is_array($this->sql['having'])) {
                $this->sql['having'] = [];
            }
            if (is_string($where)) {
                $this->sql['having'][] = $where;
            } else {
                $this->sql['having'][] = $this->parseWhere($where);
            }
        }
        return $this;
    }

    /**
     * 获取最近执行的拼凑sql语句
     */
    function getQuerySql()
    {
        return $this->querySql;
    }

    /**
     * 表连接(左,右,内连接)
     * $join参数为string
     */
    function join($join = '')
    {
        if (!empty($join)) {
            $this->sql['join'][] = ' ' . $join . ' ';
        }
        return $this;
    }

    /**
     * 表取别名
     * $name为string指定别名
     */
    function alias($name)
    {
        $this->sql['alias'] = ' AS ' . $name;
        return $this;
    }

    /**
     * 为字段名添加反引号
     * username转换为`username`,admin.username转换为admin.`username`
     * username as account转换为`username` as account
     * 返回string
     */
    private function handleField($field)
    {
        $tableAlias = '';
        if (strpos($field, '.') > 0) {
            list($tableAlias, $field) = explode('.', $field);
            $tableAlias .= '.';
        }
        $field = preg_split('/\s+/', $field);
        if (!preg_match('/\(.*?\)/', $field[0])) {
            $field[0] = '`' . $field[0] . '`';
        }
        $field = join(' ', $field);
        return $tableAlias . $field;
    }

    /**
     * 获取表中所有字段，及表主键
     * 返回一维数组
     */
    public function getFields()
    {
        $tableName = $this->tablepre . $this->table;
        $sql = 'SELECT * FROM ' . $tableName . ' LIMIT 0';
        $rs = $this->query($sql);
        if ($flag = empty($this->pk)) {
            $pk = '';
        }
        for ($i = 0; $i < $rs->columnCount(); $i++) {
            $col = $rs->getColumnMeta($i);
            if ($flag && isset($col['flags'][1]) && $col['flags'][1] == 'primary_key') {
                $pk .= $col['name'] . '-';
            }
            $columns[] = $col['name'];
        }
        if (!empty($pk)) {
            $this->pk = rtrim($pk, '-');
        }
        $this->columns = $columns;
        return $columns;
    }

    /**
     * 指行原始sql语句,适合执行复杂查询的sql语句
     * 有数据返回二维数组，没有则返回空数组
     */
    public function execute($sql)
    {
        $result = $this->query($sql);
        $lists = array();
        while (($temp = $result->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $lists[] = $temp;
        }
        return $lists;
    }

    /**
     * 获取表中主键
     */
    public function getPk()
    {
        if (empty($this->columns)) {
            $this->getFields();
        }
        return $this->pk;
    }

    /**
     * 重置$this->sql中元素
     */
    private function refactor()
    {
        $this->sql['where'] = [];
        $this->sql['group'] = '';
        $this->sql['limit'] = '';
        $this->sql['order'] = '';
        $this->sql['having'] = '';
        $this->sql['join'] = [];
        $this->sql['alias'] = '';
        $this->sql['select'] = '*';
        $this->sql['from'] = '';
    }

    private function parseData()
    {
        $t = [];
        foreach ($this->data as $k => $v) {
            $v = is_numeric($v) ? $v : $this->pdo->quote($v);
            $t[$k] = "`{$k}`=" . $v;
        }
        if (empty($t)) {
            return '';
        }
        return join(',', $t);
    }

    /**
     * 开始事务
     */
    public function beginTrans()
    {
        try {
            $this->pdo->beginTransaction();
        } catch (PDOException $e) {
            // 服务端断开时重连一次
            if ($e->errorInfo[1] == 2006 || $e->errorInfo[1] == 2013) {
                $this->pdo->beginTransaction();
            } else {
                throw $e;
            }
        }
    }

    /**
     * 提交事务
     */
    public function commitTrans()
    {
        $this->pdo->commit();
    }

    /**
     * 事务回滚
     */
    public function rollBackTrans()
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

}
