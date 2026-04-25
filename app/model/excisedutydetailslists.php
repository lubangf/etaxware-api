<?php

/**
 * This file is part of the efris-webui system
 * The is the excise duty details lists model
 * @date: 10-01-2026
 * @file: excisedutydetailslists.php
 * @path: ./app/model/excisedutydetailslists.php
 * @author: francis lubanga <francis.lubanga@gmail.com>
 * @version    1.0.0
 */
class excisedutydetailslists extends \DB\SQL\Mapper
{

    public function __construct(\DB\SQL $db)
    {
        parent::__construct($db, 'tblexcisedutydetailslist');
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

    public function getByCode($code)
    {
        $this->load(array(
            'code=?',
            $code
        ));
        return $this->query;
    }

    public function getByExciseDutyId($id)
    {
        $this->load(array(
            'exciseDutyId=?',
            $id
        ));
        return $this->query;
    }

    public function getActive($status)
    {
        $this->load(array(
            'status=?',
            $status
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