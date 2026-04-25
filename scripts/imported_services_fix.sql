-- etaxware.tblimportedservices definition

CREATE TABLE `tblimportedservices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `gooddetailgroupid` int DEFAULT NULL,
  `taxdetailgroupid` int DEFAULT NULL,
  `paymentdetailgroupid` int DEFAULT NULL,
  `erpinvoiceid` varchar(100) DEFAULT NULL COMMENT 'invoice identifier from the erp',
  `erpinvoiceno` varchar(100) DEFAULT NULL COMMENT 'invoice number from the erp',
  `antifakecode` varchar(20) DEFAULT NULL COMMENT 'Digital signature(',
  `deviceno` varchar(20) DEFAULT NULL COMMENT 'Device Number',
  `issueddate` date DEFAULT NULL COMMENT 'yyyy-MM-dd HH24:mm:ss',
  `issuedtime` datetime DEFAULT NULL,
  `operator` varchar(100) DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `oriinvoiceid` varchar(20) DEFAULT NULL COMMENT 'When the credit is opened, it is the original invoice number. When the ticket is opened, it is empty.',
  `invoicetype` int DEFAULT NULL COMMENT '1-invoice, 2-credit, 3-temporary, 4-debit',
  `invoicekind` int DEFAULT NULL COMMENT '1-invoice, 2-receipt',
  `datasource` int DEFAULT NULL COMMENT '101-efd, 102-cs, 103-webService api, 104-BS',
  `invoiceindustrycode` int DEFAULT NULL COMMENT '101-general industry, 102-export, 103-import',
  `einvoiceid` varchar(20) DEFAULT NULL,
  `einvoicenumber` varchar(20) DEFAULT NULL,
  `einvoicedatamatrixcode` varchar(500) DEFAULT NULL,
  `isbatch` varchar(1) DEFAULT '0' COMMENT 'Not required, the value is 0 or 1, if it is empty, the default is 0. 0-not a batch summary invoice, 1-batch summary invoice',
  `netamount` decimal(20,8) DEFAULT NULL COMMENT 'Tax Receipt total net amount',
  `taxamount` decimal(20,8) DEFAULT NULL COMMENT 'Tax Receipt total tax amount',
  `grossamount` decimal(20,8) DEFAULT NULL COMMENT 'Tax Receipt total gross amount',
  `origrossamount` decimal(20,8) DEFAULT NULL COMMENT 'Tax Receipt total gross amount',
  `itemcount` int DEFAULT '0' COMMENT 'Purchase item lines',
  `modecode` varchar(100) DEFAULT '1' COMMENT 'Issuing receipt mode (1:Online or 0:Offline)',
  `modename` varchar(200) DEFAULT NULL,
  `remarks` varchar(220) DEFAULT NULL,
  `buyerid` int DEFAULT NULL,
  `sellerid` int DEFAULT NULL,
  `issueddatepdf` datetime DEFAULT NULL,
  `grossamountword` varchar(1000) DEFAULT NULL,
  `isinvalid` int DEFAULT NULL,
  `isrefund` int DEFAULT NULL,
  `vouchertype` varchar(100) DEFAULT NULL,
  `vouchertypename` varchar(100) DEFAULT NULL,
  `currencyRate` decimal(20,8) DEFAULT '0.00000000',
  `branchCode` varchar(45) DEFAULT NULL,
  `branchId` varchar(45) DEFAULT NULL,
  `disabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'This field is used to control whether to UI displays the item as enabled or disabled',
  `SyncToken` int DEFAULT NULL COMMENT 'Each response from QBO contains this field. This field is read-only. You need to use value from this field for performing an update. It''s used for avoiding a situation when you try to update not latest version of object.',
  `docTypeCode` varchar(45) DEFAULT '10',
  `inserteddt` datetime NOT NULL,
  `insertedby` int DEFAULT NULL,
  `modifieddt` datetime DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



INSERT INTO etaxware.tblentitytypes (code,name,description,inserteddt,insertedby,modifieddt,modifiedby)
	VALUES ('IMPORTEDSERVICE','Imported Service','Imported Service','2024-01-05 11:42:00',1000,'2024-01-05 11:42:00',1000);


INSERT INTO etaxware.tblpermissions (code,name,description,inserteddt,insertedby,modifieddt,modifiedby)
	VALUES ('VIEWIMPORTEDSERVICES','View Imported Services','View Imported Services','2024-01-05 10:11:00',1000,'2024-01-05 10:11:00',1000);

INSERT INTO etaxware.tblpermissions (code,name,description,inserteddt,insertedby,modifieddt,modifiedby)
	VALUES ('CREATEIMPORTEDSERVICES','Create Imported Services','Create Imported Services','2024-01-05 10:11:00',1000,'2024-01-05 10:11:00',1000);
	
INSERT INTO etaxware.tblpermissions (code,name,description,inserteddt,insertedby,modifieddt,modifiedby)
	VALUES ('EDITIMPORTEDSERVICES','Edit Imported Services','Edit Imported Services','2024-01-05 10:11:00',1000,'2024-01-05 10:11:00',1000);
	
INSERT INTO etaxware.tblpermissions (code,name,description,inserteddt,insertedby,modifieddt,modifiedby)
	VALUES ('FETCHIMPORTEDSERVICES','Fetch Imported Services','Fetch Imported Services','2024-01-05 10:11:00',1000,'2024-01-05 10:11:00',1000);
	
INSERT INTO etaxware.tblpermissions (code,name,description,inserteddt,insertedby,modifieddt,modifiedby)
	VALUES ('UPLOADIMPORTEDSERVICES','Upload Imported Services','Upload Imported Services','2024-01-05 10:11:00',1000,'2024-01-05 10:11:00',1000);
	
INSERT INTO etaxware.tblpermissions (code,name,description,inserteddt,insertedby,modifieddt,modifiedby)
	VALUES ('PRINTIMPORTEDSERVICES','Print Imported Services','Print Imported Services','2024-01-05 10:11:00',1000,'2024-01-05 10:11:00',1000);
	
INSERT INTO etaxware.tblpermissions (code,name,description,inserteddt,insertedby,modifieddt,modifiedby)
	VALUES ('DOWNLOADIMPORTEDSERVICES','Download Imported Services','Download Imported Services','2024-01-05 10:11:00',1000,'2024-01-05 10:11:00',1000);
	
INSERT INTO etaxware.tblpermissions (code,name,description,inserteddt,insertedby,modifieddt,modifiedby)
	VALUES ('SYNCIMPORTEDSERVICES','Sync Imported Services','Sync Imported Services','2024-01-05 10:11:00',1000,'2024-01-05 10:11:00',1000);
	

--  Auto-generated SQL script #202401281649
INSERT INTO etaxware.tblsettings (groupid,groupcode,code,name,value,disabled,sensitivityflag,inserteddt,insertedby,modifieddt,modifiedby)
	VALUES (3,'APP','IMPORTEDSERVICEENTITYTYPE','entity type for imported services','1034',0,0,'2024-01-05 11:42:00',1000,'2024-01-05 11:42:00',1000);


