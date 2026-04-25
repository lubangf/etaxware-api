<?php

/**
 * This file is part of the efris-webui system
 * The is the invoices model
 * @date: 15-54-2023
 * @file: creditmemos.php
 * @path: ./app/model/creditmemos.php
 * @author: francis lubanga <francis.lubanga@gmail.com>
 * @copyright  (C) FTS GROUP CONSULTING LIMITED - All Rights Reserved
 * @version    1.0.0
 */
class creditmemos extends \DB\SQL\Mapper
{

    public function __construct(\DB\SQL $db)
    {
        parent::__construct($db, 'tblcreditmemos');
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
    
    public function getByErpID($id)
    {
        $this->load(array(
            'TRIM(erpinvoiceid)=?',
            $id
        ));
        return $this->query;
    } 
    
    public function getByInvoiceID($id)
    {
        $this->load(array(
            'einvoiceid=?',
            $id
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