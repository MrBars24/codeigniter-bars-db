<?php
class MX_Model extends CI_Model{

    protected $tbl;
    protected $capsule = array();
    protected $guarded;
    protected $isUpdate = FALSE;
    protected $attributes;

    function __construct(){
        parent::__construct();
        $this->load->database();
        $this->attributes = new stdClass;

        if(isset($this->table)){
            $this->tbl = strtolower($this->table);
        }else{
            $this->tbl = strtolower(get_called_class());
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
    }

    static function __callStatic($name, $arguments){
        if(substr($name,0,6)=="findBy"){
            $field = strtolower(substr($name,6,strlen($name)));
            $class = get_called_class();
            $that = new $class();
        
            $fn = "appendDataToObjectBy".$field;
            $that->attributes = $that->$fn($arguments[0],$that);
        
            $that->setUpdate(TRUE);
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

    static function where($cond,$val){
        $class = get_called_class();
        $that = new $class();
        $that->callWhere($cond,$val);
        return $that;
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

    function save(){
        if(empty($this->attributes)) return FALSE;
        print_r($this->attributes);
        if($this->isUpdate){
            $this->db->where("id",$this->attributes->id);
            unset($this->attributes->id);
            $this->db->update($this->tbl,$this->attributes);
            $this->setUpdate(FALSE);
        }else{
            print_r($this->attributes);
            $this->db->insert($this->tbl,$this->attributes);
        }
    }

    function saveAll($obj){
        $this->db->insert_batch($this->tbl,$obj);
    }

    static function findById($id){
        $class = get_called_class();
        $that = new $class();
        
        $that->attributes = $that->appendDataToObjectById($id,$that);
        $that->setUpdate(TRUE);
        return $that;
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

    function findAllById(){

    }

    function count(){
        $q = $this->db->get($this->tbl);
        return $q->num_rows();
    }

    function delete(){
        $this->db->where("id",$this->id);
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

    //private
    private function callWhere($cond,$val=null){
        if(empty($val)){
            $this->db->where($cond);
        }else{
            $this->db->where($cond,$val);
        }
    }

    private function getAll(){
        $table = isset($this->table) ? $this->table : strtolower(get_called_class());
        $q = $this->db->get($table);
        if($q->num_rows() > 0){
            while($row = $q->unbuffered_row()){
                $class = get_called_class();
                $obj = new $class();
                $obj->attributes = $row;
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

}

class Collection{

    public $items;

    function __construct($items){
        $this->items = $items;
    }

    function fetch(){
        $data = [];
        foreach($this->items as $c){
            $data[] = $c->getAttributes();
        }

        return $data;
    }

    function fetchObjects(){
        return $this->items;
    }
}


?>
