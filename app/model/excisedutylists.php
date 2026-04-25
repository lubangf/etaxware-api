<?php

/**
 * This file is part of the efris-webui system
 * The is the excise duty lists model
 * @date: 10-01-2026
 * @file: excisedutylists.php
 * @path: ./app/view/excisedutylists.php
 * @author: francis lubanga <francis.lubanga@gmail.com>
 * @version    1.0.0
 */
class excisedutylists extends \DB\SQL\Mapper
{

    public function __construct(\DB\SQL $db)
    {
        parent::__construct($db, 'tblexcisedutylist');
    }

    public function all()
    {
        $this->load();
        return $this->query;
    }

    public function getByID($id)
    {
        $this->load(array(
            'id=?',
            $id
        ));
        //return $this->query;
    }

    public function getActive($status)
    {
        $this->load(array(
            'status=?',
            $status
        ));
        return $this->query;
    }

    public function getByCode($code)
    {
        $this->load(array(
            'code=?',
            $code
        ));
        return $this->query;
    }
    
    public function add()
    {
        $this->copyFrom('POST');
        $this->save();
    }

    public function edit($id)
    {
        $this->load(array(
            'id=?',
            $id
        ));
        $this->copyFrom('POST');
        $this->update();
    }

    public function delete($id)
    {
        $this->load(array(
            'id=?',
            $id
        ));
        $this->erase();
    }
}

?>