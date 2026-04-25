<?php

/**
 * This file is part of the etaxware system
 * The is the suppliers model
 * @date: 24-05-2021
 * @file: suppliers.php
 * @path: ./app/view/suppliers.php
 * @author: francis lubanga <fl@digitalformulae.com>
 * @copyright  (C) Digital Formulae Limited - All Rights Reserved
 * @version    1.0.0
 */
class suppliers extends \DB\SQL\Mapper
{
    
    public function __construct(\DB\SQL $db)
    {
        parent::__construct($db, 'tblsuppliers');
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
    
    public function getByName($name)
    {
        $this->load(array(
            'legalname=?',
            $name
        ));
        return $this->query;
    }
    
    public function getByCode($code)
    {
        $this->load(array(
            'erpsuppliercode=?',
            $code
        ));
        return $this->query;
    }
    
    public function getBySupplierId($id)
    {
        $this->load(array(
            'erpsupplierid=?',
            $id
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