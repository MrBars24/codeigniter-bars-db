<?php
class Home extends CI_Controller{

    function __construct(){
        parent::__construct();
        $this->load->model('notes');
    }

    function index(){
        //CREATE
        // $note = new Notes;
        // $note->note = 'rannis';
        // $note->des = 'hmm';
        // $note->status = 'open';
        // $note->save();
        
        //COUNT
        //$data = $this->notes->count();
        //print_r($data);
        
        //MULTIPLE SAVE
        // $arr = array(
        //     array("note"=>"test 1","des"=>"des1","status"=>"close"),
        //     array("note"=>"test 2","des"=>"des2","status"=>"close"),
        //     array("note"=>"test 3","des"=>"des3","status"=>"open")
        // );
        
        // $caps = array();
        // $notes = new Notes();
        // foreach($arr as $a){
        //     $note = new stdClass;
        //     $note->note = $a['note'];
        //     $note->des = $a['des'];
        //     $note->status = $a['status'];
        //     $caps[] = $note;
        // }
        // $notes->saveAll($caps);

        //UPDATE BY ID
        // $note = Notes::findById(1);
        // $note->des = "desdesdes";
        // $note->save();
        
        //UPDATE BY CUSTOM FIELD
        //$data = Notes::findByStatus("close");
        //$data->status = 'last';
        // $data->save();

        // $data = Notes::findAllByStatus('close');
        // print_r($data);
        // $data->delete();
        
        
        //$data = Notes::all();
        // print_r($data->fetch());
        // print_r($note);
        
        $where = Notes::whereRaw('id<4')->fetch();
        print_r($where);

    }

}