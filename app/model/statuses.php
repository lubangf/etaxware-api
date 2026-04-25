<?php

/**
 * This file is part of the efris-webui system
 * The is the statuses model
 * @date: 08-04-2019
 * @file: statuses.php
 * @path: ./app/view/statuses.php
 * @author: francis lubanga <francis.lubanga@gmail.com>
 * @copyright  (C) d'alytics - All Rights Reserved
 * @version    1.0.0
 */
class statuses extends \DB\SQL\Mapper
{

    public function __construct(\DB\SQL $db)
    {
        parent::__construct($db, 'tblstatuses');
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
    
    public function getByGroupID($groupid)
    {
        $this->load(array(
            'groupid=?',
            $groupid
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