<?php
class MX_Model extends CI_Model{

    protected $tbl;
    protected $capsule = array();
    protected $guarded;
    protected $isUpdate = FALSE;
    protected $attributes;
    protected $original;
    protected $condition;
    protected $conditionRaw;

    function __construct(){
        parent::__construct();
        $this->load->database();
        $this->attributes = new stdClass;
        $this->original = new stdClass;

        if(isset($this->table)){
            $this->tbl = strtolower($this->table);
        }else{
            $pieces = preg_split('/(?=[A-Z])/', get_called_class(), -1, PREG_SPLIT_NO_EMPTY);
            $class = (count($pieces) > 1) ? implode("_",$pieces) : get_called_class();
            $this->tbl = strtolower($class);
        }
    }

    function __set($name,$val){
        $allowed = array('collection','loader');
        if(in_array($name,$allowed)){
            $this->$name = $val;
            return;
        }

        if($this->isUpdate && !isset($this->attributes->$name)) return;
        $this->attributes->$name = $val;
    }

    public function __get($key)
	{
        if(!empty($this->attributes)){
            $keys = array_keys(get_object_vars($this->attributes));
            if(in_array($key,$keys)) return $this->attributes->$key;
        }

        if(method_exists($this,$key)){
            return $this->$key();
        }

        return get_instance()->$key;
	}

    function __call($name,$args){
        if(substr($name,0,20)=="appendDataToObjectBy"){
            $obj = $args[1];
            $field = strtolower(substr($name,20,strlen($name)));
            $this->db->where($field,$args[0]);
            $q = $this->db->get($this->tbl);
            if($q->num_rows() > 0){
                return $q->unbuffered_row();
            }

            return [];
        }

        if($name == "where"){
            $this->callWhere($args[0],$args[1]);
            $this->setCondition($args[0],$args[1]);
            return $this;
        }
    }

    static function __callStatic($name, $arguments){
        if(substr($name,0,6)=="findBy"){
            $field = strtolower(substr($name,6,strlen($name)));
            $class = get_called_class();
            $that = new $class();
        
            $fn = "appendDataToObjectBy".$field;
            $that->attributes = $that->$fn($arguments[0],$that);
            $that->original = $that->attributes;
        
            $that->setUpdate(TRUE);
            return $that;
        }

        if(substr($name,0,9)=="findAllBy"){
            $field = strtolower(substr($name,9,strlen($name)));
            $class = get_called_class();
            $that = new $class();

            $items = $that->getAllBy($field,$arguments[0]);
            $collection = new Collection($items);
            return $collection;
        }

        if($name == "where"){
            $class = get_called_class();
            $that = new $class();
            $that->callWhere($arguments[0],$arguments[1]);
            $that->setCondition($arguments[0],$arguments[1]);
            return $that;
        }
    }

    static function all(){
        $class = get_called_class();
        $that = new $class();
        $items = $that->getAll();
        $collection = new Collection($items);
        return $collection;
    }

    static function whereRaw($sql){
        $class = get_called_class();
        $that = new $class();
        $that->callWhere($sql);
        return $that;
    }

    function fetch(){
        $items = $this->getAll();
        $collection = new Collection($items);
        return $collection;
    }

    function fetchOne(){
        $this->attributes = $this->getOne();
        $this->original = $this->attributes;
        return $this;
    }

    function save(){
        if(empty($this->attributes)) return FALSE;
        if($this->isUpdate){
            $this->db->where("id",$this->attributes->id);
            unset($this->attributes->id);
            $this->db->update($this->tbl,$this->attributes);
            $this->setUpdate(FALSE);
        }else{
            $this->db->insert($this->tbl,$this->attributes);
        }
    }

    function update($param){
        $this->db->where($this->condition);

        if(count($this->conditionRaw) > 0){
            foreach($this->conditionRaw as $c){
                $this->db->where($c);
            }
        }

        $this->db->update($this->tbl,$param);
    }

    static function saveAll($obj){
        $class = get_called_class();
        $o = new $class();
        $o->insertAll($obj);
    }

    function insertAll($obj){
        $this->db->insert_batch($this->tbl,$obj);
    }

    static function findById($id){
        $class = get_called_class();
        $that = new $class();
        
        $that->attributes = $that->appendDataToObjectById($id,$that);
        $that->original = $that->attributes;
        $that->setUpdate(TRUE);
        return $that;
    }

    static function findAllById($id){
        $class = get_called_class();
        $that = new $class();
        $items = $that->getAllBy("id",$id);
        $collection = new Collection($items);
        return $collection;
    }

    function existsById($id){
        $this->db->where("id",$id);
        $q = $this->db->get($this->tbl);
        if($q->num_rows() > 0){
            return TRUE;
        }
        
        return FALSE;
    }

    function findAll(){
        
    }

    function count(){
        $q = $this->db->get($this->tbl);
        return $q->num_rows();
    }

    function delete(){
        if(empty($this->id)){
            $this->db->where($this->condition);
            if(count($this->conditionRaw) > 0){
                foreach($this->conditionRaw as $c){
                    $this->db->where($c);
                }
            }
            $this->db->delete($this->tbl);
        }else{
            $this->db->where("id",$this->id);
            $this->db->delete($this->tbl);
        }
    }

    function remove(){
        $this->db->where($this->condition);
        if(count($this->conditionRaw) > 0){
            foreach($this->conditionRaw as $c){
                $this->db->where($c);
            }
        }
        $this->db->delete($this->tbl);
    }

    function deleteAll(){
        $this->db->delete($this->tbl);
    }

    function appendDataToObjectById($id,$obj){
        $this->db->where("id",$id);
        $q = $this->db->get($this->tbl);
        if($q->num_rows() > 0){
            $row = $q->unbuffered_row();
            return $row;
        }
    }

    //relationship
    function hasOne($cls,$f=null){
        require_once(APPPATH.'models'.DIRECTORY_SEPARATOR.$cls.'.php');
        $foreign = strtolower(get_called_class().'_id');
        if(!empty($f)){
            $foreign = $f;
        }
        $model = new $cls();
        return $model->where("id",$this->$foreign)->fetchOne();
    }

    function belongsTo($cls,$f=null){
        require_once(APPPATH.'models'.DIRECTORY_SEPARATOR.$cls.'.php');
        $foreign = strtolower($cls.'_id');
        if(!empty($f)){
            $foreign = $f;
        }
        $model = new $cls();
        return $model->where($foreign,$this->id)->fetchOne();
    }

    //private
    private function callWhere($cond,$val=null){
        if(empty($val)){
            $this->db->where($cond);
        }else{
            $this->db->where($cond,$val);
        }
    }

    private function getAll(){
        $table = isset($this->table) ? $this->table : $this->tbl;
        $q = $this->db->get($table);
        if($q->num_rows() > 0){
            while($row = $q->unbuffered_row()){
                $class = get_called_class();
                $obj = new $class();
                $obj->attributes = $row;
                $obj->original = $obj->attributes;
                $data[] = $obj;
            }
            return $data;
            //return $q->result();
        }

        return [];
    }

    private function getOne(){
        $table = isset($this->table) ? $this->table : $this->tbl;
        $q = $this->db->get($table);
        if($q->num_rows() > 0){
            $row = $q->unbuffered_row();
            return $row;
        }

        return [];
    }

    private function getAllBy($field,$value){
        $table = isset($this->table) ? $this->table : strtolower(get_called_class());
        $this->db->where($field,$value);
        $q = $this->db->get($table);
        if($q->num_rows() > 0){
            while($row = $q->unbuffered_row()){
                $class = get_called_class();
                $obj = new $class();
                $obj->setCondition($field,$value);
                $obj->attributes = $row;
                $obj->original = $obj->attributes;
                $data[] = $obj;
            }
            return $data;
            //return $q->result();
        }

        return [];
    }

    private function setPublicProperties(){
        $reflection = new ReflectionObject($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $obj = array();
        foreach($properties as $property){
            if($property->getName() == 'id') continue;
            $this->db->set($property->getName(),$property->getValue($this)); 
        }
    }

    private function getPublicProperties(){
        $reflection = new ReflectionObject($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $obj = array();
        foreach($properties as $property){
            $obj[$property->getName()] = $property->getValue($this);
        }

        return $obj;
    }

    public function setUpdate($status){
        $this->isUpdate = $status;
    }

    public function getAttributes(){
        return $this->attributes;
    }

    public function setCondition($condition,$val){
        $this->condition[$condition] = $val;
    }

    public function setConditionRaw($condition){
        $this->setConditionRaw[] = array($condition);
    }

}

class Collection{

    public $items = array();

    function __construct($items = null){
        if(!empty($items)){
            $this->items = $items;
        }
    }

    function fetch(){
        $data = [];
        foreach($this->items as $c){
            $data[] = $c->getAttributes();
        }

        return $data;
    }

    function save(){
        $data = [];
        foreach($this->items as $c){
            $data[] = $c->getAttributes();
        }

        $this->items[0]->saveAll($data);
    }

    function update($param){
        $this->items[0]->update($param);
    }

    function delete(){
        $this->items[0]->remove();
    }


    function fetchObjects(){
        return $this->items;
    }

    function addItem($item){
        array_push($this->items,$item);
    }
}


?>
