<?php
/**
 * @name settings.php
 * @desc This file is part of the etaxware-api app. This is the settings model
 * @date: 29-09-2020
 * @file: settings.php
 * @path: ./app/model/settings.php
 * @author: francis lubanga <francis.lubanga@gmail.com>
 * @copyright  (C) d'alytics - All Rights Reserved
 * @version    1.0.0
 */

class settings extends \DB\SQL\Mapper
{
    
    public function __construct(\DB\SQL $db)
    {
        parent::__construct($db, 'tblsettings');
    }
    
    public function all()
    {
        $this->load();
        return $this->query;
    }
    
    public function getNoneSensitive()
    {
        $this->load(array(
            'sensitivityflag=?',
            0
        ));
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
            'code=?',
            $code
        ));
        return $this->query;
    }
    
    public function getByGroupCode($code)
    {
        $this->load(array(
            'groupcode=?',
            $code
        ));
        return $this->query;
    }
    
    public function getByGroupId($id)
    {
        $this->load(array(
            'groupid=?',
            $id
        ));
        return $this->query;
    }
}
?>