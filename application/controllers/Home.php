<?php
class Home extends CI_Controller{

    function __construct(){
        parent::__construct();
        $this->load->model('ingredient');
        $this->load->model('unitofmeasure');
    }
    
    function index(){  
        $uom = UnitOfMeasure::findById(2)->ingredient;
        print_r($uom);
    }

    function onetoone(){
        $u = Ingredient::findById(1)->measurement;
        print_r($u);
    }

    function create(){
        //CREATE
        $note = new Notes;
        $note->note = 'MrBars';
        $note->des = 'Mstr Bars';
        $note->status = 'open';
        $note->save();
    }

    function massCreate(){
        $data = array(
            array(
                'note' => 'Mass',
                'des' => 'mass desc',
                'status' => 'massopen'
            ),
            array(
                'note' => 'Mass2',
                'des' => 'mass desc2',
                'status' => 'massopen'
            ),
            array(
                'note' => 'Mass3',
                'des' => 'mass desc3',
                'status' => 'massopen'
            )
        );
        Notes::saveAll($data);
    }

    function collectionCreate(){
        $collection = new Collection();

        $note = new Notes;
        $note->note = 'collection';
        $note->des = 'collection des';
        $note->status = 'collectionopen';
        $collection->addItem($note);

        $note2 = new Notes;
        $note2->note = 'collection2';
        $note2->des = 'collection des2';
        $note2->status = 'collectionopen';
        $collection->addItem($note2);

        $collection->save();
    }

    function update(){
        $note = Notes::findById(1);
        $note->note = 'MrBarsUpdate';
        $note->des = 'Mstr Bars Update';
        $note->status = 'close';
        $note->save();
    }

    function massUpdate(){
        $data = Notes::findAllByStatus("open")->update(['status'=>'mass']);
    }

    function massWhere(){
        $data = Notes::where("status","open")->where("note","MrBars")->update(['status'=>'mass']);
        //$data = Notes::where("status","open")->update(['status'=>'mass']);
    }

    function customUpdate(){
        //UPDATE BY CUSTOM FIELD
        $data = Notes::findByStatus("close");
        $data->status = 'last';
        $data->save();
    }

    function retriveOne(){
        $note = Notes::findById(1);
        echo $note->note;
        echo $note->des;
        echo $note->status;
    }

    function retriveAll(){
        $note = Notes::all();
        print_r($note->fetch());
    }

    function retriveAllId(){
        $note = Notes::findAllById(1);
        print_r($note->fetch());
    }

    function retriveAllStatus(){
        $note = Notes::findAllByStatus('open');
        print_r($note->fetch());
    }

    function deleteOne(){
        $data = Notes::findById(4);
        $data->delete();
    }

    function deleteMany(){
        $data = Notes::findAllByStatus('open')->delete();
    }

    function massDelete(){
        $data = Notes::where("status","massopen")->where("note","MrBars")->delete();
        //$data = Notes::where("status","open")->delete();
    }

    function count(){
        //COUNT
        $data = $this->notes->count();
        print_r($data);
    }

}