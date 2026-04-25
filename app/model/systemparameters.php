<?php

/**
 * @name systemparameters
 * @desc This file is part of the efris-webui system. The is the systemparameters model
 * @date: 07-06-2020
 * @file: systemparameters.php
 * @path: ./app/view/systemparameters.php
 * @author: francis lubanga <francis.lubanga@gmail.com>
 * @copyright  (C) d'alytics - All Rights Reserved
 * @version    1.0.0
 */
class systemparameters extends \DB\SQL\Mapper
{

    public function __construct(\DB\SQL $db)
    {
        parent::__construct($db, 'tblsystemparameters');
    }

    public function all()
    {
        $this->load();
        return $this->query;
    }
    
    public function getByOwner($id)
    {
        $this->load(array(
            'insertedby=?',
            $id
        ));
        return $this->query;
    }

    public function getById($id)
    {
        $this->load(array(
            'id=?',
            $id
        ));
        //return $this->query;
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