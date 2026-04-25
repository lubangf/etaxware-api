<?php

/**
 * This file is part of the efris-webui system
 * The is the commodity categories model
 * @date: 08-04-2019
 * @file: commoditycategories.php
 * @path: ./app/view/commoditycategories.php
 * @author: francis lubanga <francis.lubanga@gmail.com>
 * @copyright  (C) d'alytics - All Rights Reserved
 * @version    1.0.0
 */
class commoditycategories extends \DB\SQL\Mapper
{

    public function __construct(\DB\SQL $db)
    {
        parent::__construct($db, 'tblcommoditycategories');
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
        return $this->query;
    }
    
    public function getByCode($code)
    {
        $this->load(array(
            'commoditycode=?',
            $code
        ));
        return $this->query;
    }
    
    public function getByName($name)
    {
        $this->load(array(
            'UPPER(commodityname) LIKE ?',
            "%" . addslashes(strtoupper($name)) . "%"
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