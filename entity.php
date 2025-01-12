<?php

namespace ZDB ;

/**
 * Базовый  класс  для  бизнес-сущностей
 * Реализует паттерн  Active Directory
 * Предназначен  для   автоматизации стандартных  над  записями  в  БД
 */
abstract class Entity
{
    protected $fields = array();  //список  полей

    /**
     * Конструктор
     *
     * @param mixed $row массив инициализирующий некторые
     * или  все  поля объекта
     *
     */

    public function __construct($row = null) {
        $this->init();
        $this->setData($row);

    }

    /**
     * Инициализация полей  сущности
     *
     */
    protected function init() {
        $meta = $this->getMetadata();
        $this->{$meta['keyfield']} = 0;
    }

    /**
     * Возврашает  метаданные для   выборки  из  БД
     * Реализуется  конкретными  сущностями имплементирующими  класс  Entity
     * Метаданные  содержат  имя  таблицы, имя  ключевого  поля
     * а  также  имя  представления  если  такое  существует  в  БД
     * Например  array('table' => 'system_users','view' => 'system_users_view', 'keyfield' => 'user_id')
     *
     * Вместо  испоользования  метода   можно  импользоввать  аннтации  возде  определения  класса
     * анноации  именуются   аналогично  ключам  массива метаданных.
     */
    protected static function getMetadata() {
        $class = new \ReflectionClass(get_called_class());
        $doc = $class->getDocComment();
        preg_match_all('/@([a-z0-9_-]+)=([^\n]+)/is', $doc, $arr);
        if (is_array($arr)) {
            $reg_arr = array_combine($arr[1], $arr[2]);

            $table = trim($reg_arr['table']);
            $view = trim($reg_arr['view'] ?? '');
            $keyfield = trim($reg_arr['keyfield']);


            if (strlen($table) > 0 && strlen($keyfield) > 0) {
                $retarr = array();
                $retarr['table'] = $table;
                $retarr['keyfield'] = $keyfield;
                if (strlen($view) > 0) {
                    $retarr['view'] = $view;
                }

                return $retarr;
            }
        }


        throw new ZDBException('getMetadata должен  быть  перегружен');
    }

    /**
     * Возвращает  сущность  из  БД по  ключу
     * @param mixed $param
     * @param mixed $fields  уточнение  списка  возвращаемых  полей По  умолчанию ставится  *
     */
    public static function load($param, $fields='*') {
        if($param ==null) {
            return null;
        }
        if(is_string($param )) {
            if(strlen($param )==0) {
                return null;
            }
        }
        $row  = array();
        $class = get_called_class();

        $meta = $class::getMetadata();
        $table = isset($meta['view']) ? $meta['view'] : $meta['table'];
        $conn = DB::getConnect();
        if (is_numeric($param)) {
            $row = $conn->GetRow("select  {$fields}  from {$table}  where {$meta['keyfield']} = " . $param);
        } else  {
            $row = $conn->GetRow("select  {$fields}  from {$table}  where {$meta['keyfield']} = " . $conn->qstr( $param) );  
        }

        if (count($row) == 0) {
            return null;
        }
        $obj = new $class();

        $obj->setData($row);

        return $obj;
    }

    /**
     * Возвращает  количество  сущностй  в  БД  по  критерию
     *
     * @param mixed $where
     */
    public static function findCnt($where = "") {
        $class = get_called_class();
        $meta = $class::getMetadata();
        $conn = DB::getConnect();

        $table = isset($meta['view']) ? $meta['view'] : $meta['table'];
        $sql = "select coalesce(count({$meta['keyfield']}),0) as  cnt from " . $table;

        $cnst = static::getConstraint();
        if(strlen($cnst)  >0) {
            if(strlen($where)==0) {
                $where =  $cnst;
            } else {
                $where = "({$cnst}) and ({$where}) ";
            }
        }
        if (strlen($where) > 0) {
            $sql .= " where " . $where;
        }


        return $conn->getOne($sql);
    }




    public static function findBySql($sql) {

        $class = get_called_class();
        $meta = $class::getMetadata();
        //$table = isset($meta['view']) ? $meta['view'] : $meta['table'];
        $conn = DB::getConnect();
        $list = array();

        $rs = $conn->Execute($sql);

        foreach ($rs as $row) {
            $item = new $class();
            $item->setData($row);
            $list[$row[$meta['keyfield']]] = $item;
      //      $list[$row[$meta['keyfield']]]->afterLoad();
        }
        return $list;
    }

    /**
     * Возвращает  массив ключ/имя  из  БД  по  критерию
     * Может  использоватся  для заполнения выпадающих списков
     *
     * @param string $fieldname Имя  поля представляющее поле сущности. Можно использовать  конкатенацию  полей.
     * @param mixed $where Условие  для предиката where
     * @param mixed $orderbyfield
     * @param mixed $orderbydir
     * @param mixed $count
     * @param mixed $offset
     */
    public static function findArray($fieldname, $where = '', $orderbyfield = null, $count = -1, $offset = -1) {

        $class = get_called_class();
        $meta = $class::getMetadata();
        $table = isset($meta['view']) ? $meta['view'] : $meta['table'];
        $conn = DB::getConnect();

        $sql = "select {$meta['keyfield']} ,{$fieldname} as _field_ from " . $table;


        $cnst = static::getConstraint();
        if(strlen($cnst ?? '')  >0) {
            if(strlen($where ?? '')==0) {
                $where =  $cnst;
            } else {
                $where = "({$cnst}) and ({$where}) ";

            }
        }

        if (strlen(trim($where ?? '')) > 0) {
            $sql .= " where " . $where;
        }
        $orderbyfield = trim($orderbyfield ?? '') ;
        if(trim($orderbyfield)=='asc') {
            $orderbyfield='';
        }
        if(trim($orderbyfield)=='desc') {
            $orderbyfield='';
        }
        if (strlen($orderbyfield) > 0) {
            $sql .= " order by " . $orderbyfield;
        }

        if ($offset >= 0 or $count >= 0) {
            $rs = $conn->SelectLimit($sql, $count, $offset);
        } else {
            $rs = $conn->Execute($sql);
        }
        $list = array();
        foreach ($rs as $row) {

            $list[$row[$meta['keyfield']]] = $row['_field_'];

        }
        return $list;
    }



    /**
     * Возвращает  массив  сущностей  из  БД  по  критерию
     *
     * @param mixed $where Условие  для предиката where
     * @param mixed $orderbyfield
     * @param mixed $orderbydir
     * @param mixed $count
     * @param mixed $offset
     * @param mixed $fields  уточнение  списка  возвращаемых  полей По  умолчанию ставится  *
     * @return массив 
     */
    public static function find($where = '', $orderbyfield = null, $count = -1, $offset = -1, $fields='') : array {
        $list = [];
        
        foreach( self::findYield($where , $orderbyfield , $count , $offset , $fields) as $k=>$v ){
           $list[$k]=$v ;            
        }
        
        return $list;
    }       

    /**
     * Возвращает  итерируемый набор сущностей  из  БД  по  критерию
     *
     * @param mixed $where Условие  для предиката where
     * @param mixed $orderbyfield
     * @param mixed $orderbydir
     * @param mixed $count
     * @param mixed $offset
     * @param mixed $fields  уточнение  списка  возвращаемых  полей По  умолчанию ставится  *
  
     */
    public static function findYield($where = '', $orderbyfield = null, $count = -1, $offset = -1, $fields='')    {
        if(strlen($fields)==0) {
            $fields ="*";
        }

        $class = get_called_class();
        $meta = $class::getMetadata();
        $table = isset($meta['view']) ? $meta['view'] : $meta['table'];
        $conn = DB::getConnect();
        $list = array();
        $sql = "select {$fields} from " . $table;

        $cnst = static::getConstraint();
        if(strlen($cnst ?? '')  >0) {
            if(strlen($where ?? '')==0) {
                $where =  $cnst;
            } else {
                $where = "({$cnst}) and ({$where}) ";

            }
        }

        if (strlen(trim($where ?? '')) > 0) {
            $sql .= " where " . $where;
        }
        $orderbyfield = trim($orderbyfield ?? '') ;
        if(trim($orderbyfield ?? '')=='asc') {
            $orderbyfield='';
        }
        if(trim($orderbyfield ?? '')=='desc') {
            $orderbyfield='';
        }
        if (strlen($orderbyfield ?? '') > 0) {
            $sql .= " order by " . $orderbyfield;
        }

        if ($offset >= 0 or $count >= 0) {
            $rs = $conn->SelectLimit($sql, $count, $offset);
        } else {
            $rs = $conn->Execute($sql);
        }

        foreach ($rs as $row) {
            $item = new $class();
            $item->setData($row);

            yield $row[$meta['keyfield']]  => $item;

        }
        
    }

  

    /**
     * Возвращает  одно скалярное  значение  из одной строки
     * @param mixed $field  возвращаемое  поле  или  выражение
     * @param mixed $where
     */
    public static function getOne($field, $where = "") {
        $class = get_called_class();
        $meta = $class::getMetadata();
        $conn = DB::getConnect();

        $table = isset($meta['view']) ? $meta['view'] : $meta['table'];
        $sql = "select {$field} from " . $table;

        $cnst = static::getConstraint();
        if(strlen($cnst)  >0) {
            if(strlen($where)==0) {
                $where =  $cnst;
            } else {
                $where = "({$cnst}) and ({$where}) ";

            }
        }

        if (strlen($where) > 0) {
            $sql .= " where " . $where;
        }


        return $conn->getOne($sql);
    }
    /**
     * Возвращает  первую  строку  из набора
     * @param mixed $where
     * @param mixed $orderbyfield
     * @param mixed $unique  если  true должна быть  только одна запись
     */
    public static function getFirst($where = "", $orderbyfield = null, $fields='') {
        $list = self::find($where, $orderbyfield, 1, -1, $fields);

        if (count($list) == 0) {
            return null;
        }

        return array_pop($list);


    }

    /**
     * Удаление  сущности
     * возвращает  строку с ошибкой  если   удаление неудачно  или  не  разрешено
     * @param mixed $id Уникальный ключ
     */
    public static function delete($id) {
        $class = get_called_class();
        $meta = $class::getMetadata();

        if ($id>0) {

            $obj = $class::load($id);
        }

        if ($obj instanceof Entity) {
            $allowdelete = $obj->beforeDelete();
            if (strlen($allowdelete)>0) {

                return $allowdelete;
            }

            $sql = "delete from {$meta['table']}  where {$meta['keyfield']} = " . $id;
            $conn = DB::getConnect();
            $conn->Execute($sql);
            $obj->afterDelete();
            return "";
        }


    }

    /**
     * Вызывается перед  удалением  сущности
     * если  возвращает  строку с ошибкой удаление отменяется
     *
     */
    protected function beforeDelete() { 
        return "";
    }


    /**
    * Вызывается после  удаления
    *
    */
    protected function afterDelete() {

    }




    /**
     * Обработка строки  перед вставкой   в  запрос
     * после  обработки  строка  не  требует кавычек
     * @param mixed $str
     */
    public static function qstr($str) {
        $conn = DB::getConnect();
        return $conn->qstr($str);
    }

    /**
     * Добавление  слешей  в строку
     *
     * @param mixed $str
     */
    public static function escape($str) {
        $conn = DB::getConnect();
        return mysqli_real_escape_string($conn->_connectionID, $str);
    }

    /**
     * Форматирование  даты в   сответствии  с  SQL  диалектом
     *
     * @param mixed $dt Timestamp
     */
    public static function dbdate($dt) {
        $conn = DB::getConnect();
        return $conn->DBDate($dt);
    }

    /**
     * Возвращает  значение  поля
     *
     * @param mixed $name
     * @return mixed
     */
    final public function __get($name) {
        return $this->fields[$name] ?? null;
    }

    /**
     * Устанавливает  значение поля
     *
     * @param mixed $name
     * @param mixed $value
     */
    final public function __set($name, $value) {
        $this->fields[$name] = $value;
    }

    /**
     * Возвращает поля сущности  в  виде  ассоциативного  массива
     *
     */
    final public function getData() {
        return $this->fields;
    }
    /**
     * записывает данные  в сущность
     *
     */
    final public function setData($row) {
        if (is_array($row)) {
            $this->fields = array_merge($this->fields, $row);
            $this->afterLoad();            
        }

    }

    /**
     * Возвращает значение  уникального  ключа  сущности
     *
     */
    final public function getKeyValue() {
        $meta = $this->getMetadata();
        return $this->fields[$meta['keyfield']];
    }

    /**
     * Сохраняет  сущность  в  БД
     * Если  сущность новая создает запись
     *
     */
    public function save() {

        if ($this->beforeSave() === false) {
            return;
        };
        $conn = DB::getConnect();
        $meta = $this->getMetadata();
        $flist=$this->fields ;
        unset($flist[$meta['keyfield']]);//убираем  ключевое  поле с  запроса
        if (($this->fields[$meta['keyfield']]?? 0) > 0) {

            $conn->AutoExecute($meta['table'], $flist, "UPDATE", "{$meta['keyfield']} = " . $this->fields[$meta['keyfield']]);
            $this->afterSave(true);
        } else {
            $conn->AutoExecute($meta['table'], $flist, "INSERT");
            $this->fields[$meta['keyfield']] = $conn->Insert_ID();
            $this->afterSave(false);
        }
    }

    /**
     * Вызывается  перед сохранением  сущности
     * Если  возвращает  false  сохранение  отменяется
     *
     */
    protected function beforeSave() {
        return true;
    }

    /**
     * Вызывается  после  сохранения сущности
     *
     * @param mixed $update - true  если обновление
     */
    protected function afterSave($update) {

    }

    /**
     * Вызывается   после  загрузки  сущности  из  БД
     *
     */
    protected function afterLoad() {

    }

    /**
    * возвращает  ограничение на выборку  на  уровне  бизнес-сущности.
    * например  если  в  системе  нужно ограничить возвращаемый  набор для всех  выборок
    * перегружается в  класе  сущности и возвращает инструкцию для where
    */
    protected static function getConstraint() {
        return '';
    }


}

class ZDBException extends \Error
{
}
