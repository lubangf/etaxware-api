<?php
use PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\SMTP;

/**
 * @name Api.php
 * @desc This file is part of the etaxware-api app. This is the API version 11
 * @date: 24-05-2024
 * @file: Api.php
 * @path: ./api/Tally/v11/Api.php
 * @author: francis lubanga <francis.lubanga@gmail.com>
 * @version    11.0.0
 */

Class Api{
    protected $module = NULL; //tblmodules
    protected $submodule = NULL; //tblsubmodules
    
    protected $f3;// store an instance of base
    protected $db;// store database connection here    
    protected $logger;    
    protected $appsettings;// store the setting details here
    protected $util;// store utilities here
    
    protected $data;//store the data/request from the client
    protected $response;
    protected $message;
    protected $code;
    protected $action;
    protected $xml;
    protected $params;
    protected $errormessage;
    protected $errorcode;
    protected $apikey; //store the API key sent by the client
    protected $version; //store the version sent by the client
        
    /*API user details. These are populated from a setting, are are using for more admin specific tasks, such as creating audit logs, sending email alerts.*/
    protected $userid;
    protected $username;
    protected $password;
    protected $permissions;
    
    /*Current user details*/
    protected $userpermissions;
    protected $userid_u;
    protected $username_u;
    protected $userbranch_u;

    /*Email Settings*/
    protected $recipientname;
    protected $recipientemail;
    protected $subject;
    protected $ccrecipientemail = 'francis.lubanga@gmail.com'; 
    protected $ccrecipientname = 'e-TW Developer';
    
    protected $vatRegistered; //Flag to indicate if the tax payer is registered for VAT or not.

    /**
     *	@name sendmail
     *  @desc Send Email
     *	@return NULL
     *	@param NULL
     **/
    function sendmail(){
        $operation = NULL; //tblevents
        $permission = 'SENDEMAIL'; //tblpermissions
        $event = NULL; //tblevents
        $eventnotification = NULL; //tbleventnotifications
        
        
        if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
            date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
        }
        
        $xml = simplexml_load_string($this->xml);
        
        $recipientname = trim($xml->RECIPIENTNAME);
        $recipientemail = trim($xml->RECIPIENTEMAIL);
        $subject = trim($xml->SUBJECT);
        $body = trim($xml->BODY);
        $attachments = array();//this is an array
        
        
        if ($recipientemail && $body) {
            try {
                //Create a new PHPMailer instance
                $mail = new PHPMailer;
                
                //Tell PHPMailer to use SMTP
                $mail->isSMTP();
                
                //Enable SMTP debugging
                // SMTP::DEBUG_OFF = off (for production use)
                // SMTP::DEBUG_CLIENT = client messages
                // SMTP::DEBUG_SERVER = client and server messages
                $mail->SMTPDebug = SMTP::DEBUG_OFF;
                
                //Set the hostname of the mail server
                $mail->Host = 'mail.privateemail.com';
                // use
                // $mail->Host = gethostbyname('smtp.gmail.com');
                // if your network does not support SMTP over IPv6
                
                //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
                $mail->Port = 587;
                
                //Set the encryption mechanism to use - STARTTLS or SMTPS
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                
                //Whether to use SMTP authentication
                $mail->SMTPAuth = true;
                
                //Username to use for SMTP authentication - use full email address for gmail
                $mail->Username = $this->appsettings['EMAILUSERNAME'];
                
                //Password to use for SMTP authentication
                $mail->Password = $this->appsettings['EMAILPASSWORD'];
                
                //Set who the message is to be sent from
                $mail->setFrom($this->appsettings['EMAILUSERNAME'], 'e-TW App');
                
                //Set an alternative reply-to address
                //$mail->addReplyTo('replyto@example.com', 'First Last');
                
                //Set who the message is to be sent to
                $mail->addAddress($recipientemail, $recipientname);
                $mail->AddCC($this->ccrecipientemail, $this->ccrecipientname);
                
                //Set the subject line
                $mail->Subject = $subject;
                
                //Read an HTML message body from an external file, convert referenced images to embedded,
                //convert HTML into a basic plain-text alternative body
                //$mail->msgHTML(file_get_contents('contents.html'), __DIR__);
                $mail->Body = $body;
                
                //Replace the plain text body with one created manually
                $mail->AltBody = 'This is a plain-text message body';
                
                //Attach an image file
                //$mail->addAttachment('../scripts/db/rematch.sql');
                foreach ($attachments as $obj) {
                    $mail->addAttachment($obj['path'] . $obj['name']);
                }
                
                //send the message, check for errors
                if (!$mail->send()) {
                    $this->logger->write("Api Controller : sendmail() : The operation to send an email was not successful. The error messages is " . $mail->ErrorInfo, 'r');
                    $this->code = '300';
                    $this->message = 'The operation to send an email was not successful';
                    print $this->code;
                } else {
                    $this->logger->write("Api : sendmail() : The operation to send an email was successful", 'r');
                    $this->code = '000';
                    $this->message = 'The operation to send an email was successful';
                    print $this->code;
                }
            } catch (Exception $e) {
                $this->logger->write("Api Controller : sendmail() : The operation to send an email was not successful. The error messages is " . $e->getMessage(), 'r');
                $this->code = '300';
                $this->message = 'The operation to send an email was not successful';
                print $this->code;
            }
        } else {
            $this->logger->write("Api : sendmail() : There was no email or body specified", 'r');
            $this->code = '500';
            $this->message = 'There was no email or body specified';
            print $this->code;
        }
    }
    
    /**
     *	@name index
     *  @desc used to test if the service is running
     *	@return string response
     *	@param NULL
     **/
    function index(){              
        $this->logger->write("Api : index() : The previous URL is " . $this->f3->get('SERVER.HTTP_REFERER'), 'r');
        $this->logger->write("Api : index() : The current URL is " . $this->f3->get('SERVER.REQUEST_URI'), 'r');
        
        if ($this->errorcode) {
            $this->message = $this->errormessage;
            $this->code = $this->errorcode;
            
            $body = $this->code . ' : ' . $this->message;
            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
        } else {
            $this->message = 'It Works!';
            $this->code = '000';
        }
        
        $this->response  =  "<ENVELOPE>";
        $this->response .=  "<HEADER>";
        $this->response .=  "<VERSION>1</VERSION>";
        $this->response .=  "<STATUS>1</STATUS>";
        $this->response .=  "</HEADER>";
        $this->response .=  "<BODY>";
        $this->response .=  "<DATA>";
        $this->response .=  "<RESPONSE>";
        $this->response .=  "<RETURNCODE>" . htmlspecialchars($this->code) . "</RETURNCODE>";
        $this->response .=  "<RETURNMESSAGE>" . htmlspecialchars($this->message) . "</RETURNMESSAGE>";
        $this->response .=  "</RESPONSE>";
        $this->response .=  "</DATA>";
        $this->response .=  "</BODY>";
        $this->response .=  "</ENVELOPE>";
        
        $len = strlen($this->response);
        header ("CONTENT-LENGTH:".$len);
        print $this->response;
        return;
    }
    
    /**
     *	@name testapi
     *  @desc used to test if the service is running
     *	@return string response
     *	@param NULL
     **/
    function testapi(){
        $this->logger->write("Api : testapi() : The previous URL is " . $this->f3->get('SERVER.HTTP_REFERER'), 'r');
        $this->logger->write("Api : testapi() : The current URL is " . $this->f3->get('SERVER.REQUEST_URI'), 'r');
        
        if ($this->errorcode) {
            $this->message = $this->errormessage;
            $this->code = $this->errorcode;
            
            $body = $this->code . ' : ' . $this->message;
            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
        } else {
            $this->message = 'It Works!';
            $this->code = '000';
        }
        
        
        /*$body = 'This is a test body';        
        $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);*/
        
        
        $this->response  =  "<ENVELOPE>";
        $this->response .=  "<HEADER>";
        $this->response .=  "<VERSION>1</VERSION>";
        $this->response .=  "<STATUS>1</STATUS>";
        $this->response .=  "</HEADER>";
        $this->response .=  "<BODY>";
        $this->response .=  "<DATA>";
        $this->response .=  "<RESPONSE>";
        $this->response .=  "<RETURNCODE>" . htmlspecialchars($this->code) . "</RETURNCODE>";
        $this->response .=  "<RETURNMESSAGE>" . htmlspecialchars($this->message) . "</RETURNMESSAGE>";
        $this->response .=  "</RESPONSE>";
        $this->response .=  "</DATA>";
        $this->response .=  "</BODY>";
        $this->response .=  "</ENVELOPE>";
        
        $len = strlen($this->response);
        header ("CONTENT-LENGTH:".$len);
        print $this->response;
        return;
    }
       
    /**
     *	@name validatetin
     *  @desc validate a TIN number
     *	@return string response
     *	@param NULL
     **/
    function validatetin(){
        $operation = NULL; //tblevents
        $permission = 'QUERYTAXPAYER'; //tblpermissions
        $event = NULL; //tblevents
        $eventnotification = NULL; //tbleventnotifications
        
        if ($this->errorcode) {
            $this->message = $this->errormessage;
            $this->code = $this->errorcode;
            
            $body = $this->code . ' : ' . $this->message;
            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
        } else {
            if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
                date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
            }
            
            $xml = simplexml_load_string($this->xml);
            
            $tin = trim($xml->TIN);

            $this->logger->write("Api : validatetin() : The userid is: " . $this->userid_u, 'r');
            
            $this->logger->write("Api : validatetin() : Checking permissions", 'r');
            if ($this->userpermissions[$permission]) {
                $data = $this->util->querytaxpayer($this->userid_u, $tin);//will return JSON.
                $data = json_decode($data, true);
                
                if (isset($data['taxpayer'])){
                    $tin = $data['taxpayer']['tin'];
                    $ninBrn = $data['taxpayer']['ninBrn'];
                    $legalName = $data['taxpayer']['legalName'];
                    $businessName = $data['taxpayer']['businessName'];
                    $contactNumber = $data['taxpayer']['contactNumber'];
                    $contactEmail = $data['taxpayer']['contactEmail'];
                    $address = $data['taxpayer']['address'];
                    $this->logger->write("Api : validatetin() : The operation to query the taxpayer was successful", 'r');
                    $this->message = "The operation to query the taxpayer was successful";
                    $this->code = '00';
                } elseif (isset($data['returnCode'])){
                    $this->logger->write("Api : validatetin() : The operation to query the taxpayer not successful. The error message is " . $data['returnMessage'], 'r');
                    $this->message = $data['returnMessage'];
                    $this->code = $data['returnCode'];
                    
                    $body = $this->code . ' : ' . $this->message;
                    $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                } else {
                    $this->logger->write("Api : validatetin() : The operation to query the taxpayer was not successful", 'r');
                    $this->message = "The operation to query the taxpayer was not successful";
                    $this->code = '99';
                    
                    $body = $this->code . ' : ' . $this->message;
                    $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                }
            } else {
                $this->logger->write("Api : validatetin() : The user is not allowed to perform this function", 'r');
                $this->message = "The user is not allowed to perform this function";
                $this->code = '0099';
            }
                                 
            
            
            $activity = 'VALIDATETIN: ' . $tin . ': ' . $this->message;
            $windowsuser = trim($xml->WINDOWSUSER);
            $ipaddress = trim($xml->IPADDRESS);
            $macaddress = trim($xml->MACADDRESS);
            $systemname = trim($xml->SYSTEMNAME);
            $this->util->createerpauditlog($this->userid_u, $activity, $windowsuser, $ipaddress, $macaddress, $systemname, $xml);
        }

        
        // prepare xml response
        $this->response  =  "<ENVELOPE>";
        $this->response .=  "<HEADER>";
        $this->response .=  "<VERSION>1</VERSION>";
        $this->response .=  "<STATUS>1</STATUS>";
        $this->response .=  "</HEADER>";
        $this->response .=  "<BODY>";
        $this->response .=  "<DATA>";
        $this->response .=  "<RESPONSE>";
        $this->response .=  "<RETURNCODE>" . htmlspecialchars($this->code) . "</RETURNCODE>";
        $this->response .=  "<RETURNMESSAGE>" . htmlspecialchars($this->message) . "</RETURNMESSAGE>";        
        $this->response .=  "<NINBRN>" . htmlspecialchars($ninBrn) . "</NINBRN>";
        $this->response .=  "<LEGALNAME>" . htmlspecialchars($legalName) . "</LEGALNAME>";
        $this->response .=  "<BUSINESSNAME>" . htmlspecialchars($businessName) . "</BUSINESSNAME>";
        $this->response .=  "<CONTACTNUMBER>" . htmlspecialchars($contactNumber) . "</CONTACTNUMBER>";
        $this->response .=  "<CONTACTEMAIL>" . htmlspecialchars($contactEmail) . "</CONTACTEMAIL>";
        $this->response .=  "<ADDRESS>" . htmlspecialchars($address) . "</ADDRESS>";        
        $this->response .=  "</RESPONSE>";
        $this->response .=  "</DATA>";
        $this->response .=  "</BODY>";
        $this->response .=  "</ENVELOPE>";
        
        $len = strlen($this->response);
        header ("CONTENT-LENGTH:".$len);
        print $this->response;
        return;
        
    }
    
    
    /**
     *	@name checktaxpayer
     *  @desc checks whether the taxpayer is tax exempt/Deemed
     *	@return string response
     *	@param NULL
     **/
    function checktaxpayer(){
        $operation = NULL; //tblevents
        $permission = 'QUERYTAXPAYER'; //tblpermissions
        $event = NULL; //tblevents
        $eventnotification = NULL; //tbleventnotifications
        
        if ($this->errorcode) {
            $this->message = $this->errormessage;
            $this->code = $this->errorcode;
            
            $body = $this->code . ' : ' . $this->message;
            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
        } else {
            if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
                date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
            }
            
            $xml = simplexml_load_string($this->xml);
            
            $tin = trim($xml->TIN);
            $commodity = $this->util->mapcommodity(trim($xml->COMMODITYCODE));
            $taxpayerType = NULL;
            $commodityCategoryTaxpayerType = NULL;
            
            $this->logger->write("Api : checktaxpayer() : The userid is: " . $this->userid_u, 'r');
            $this->logger->write("Api : checktaxpayer() : Checking permissions", 'r');
            if ($this->userpermissions[$permission]) {
                
                
                $data = $this->util->checktaxpayer($this->userid_u, $tin, $commodity);//will return JSON.
                //var_dump($data);
                $data = json_decode($data, true); //{"commodityCategory":[],"taxpayerType":"101"}
                
                if (isset($data['commodityCategory'])){
                    
                    foreach($data['commodityCategory'] as $elem){
                        
                        if ($elem['commodityCategoryCode'] == $commodity) {
                            $commodityCategoryTaxpayerType = $this->util->decodetaxpayertypecode($elem['commodityCategoryTaxpayerType']);
                            
                            $this->logger->write("Api : checktaxpayer() : The tax payer type for this commodity code is " . $commodityCategoryTaxpayerType, 'r');
                        }
                        
                    }
                    
                    if (isset($data['taxpayerType'])){
                        $taxpayerType = $this->util->decodetaxpayertypecode($data['taxpayerType']);
                    }
                    
                    $this->logger->write("Api : checktaxpayer() : The operation to check the taxpayer was successful", 'r');
                    $this->message = "The operation to query the taxpayer was successful";
                    $this->code = '00';
                } elseif (isset($data['returnCode'])){
                    $this->logger->write("Api : checktaxpayer() : The operation to check the taxpayer not successful. The error message is " . $data['returnMessage'], 'r');
                    $this->message = $data['returnMessage'];
                    $this->code = $data['returnCode'];
                    
                    $body = $this->code . ' : ' . $this->message;
                    $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                } else {
                    $this->logger->write("Api : checktaxpayer() : The operation to check the taxpayer was not successful", 'r');
                    $this->message = "The operation to check the taxpayer was not successful";
                    $this->code = '99';
                    
                    $body = $this->code . ' : ' . $this->message;
                    $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                }
            } else {
                $this->logger->write("Api : checktaxpayer() : The user is not allowed to perform this function", 'r');
                $this->message = "The user is not allowed to perform this function";
                $this->code = '0099';
            }
            
            
            
            $activity = 'CHECKTAXPAYER: ' . $tin . ': ' . $this->message;
            $windowsuser = trim($xml->WINDOWSUSER);
            $ipaddress = trim($xml->IPADDRESS);
            $macaddress = trim($xml->MACADDRESS);
            $systemname = trim($xml->SYSTEMNAME);
            $this->util->createerpauditlog($this->userid_u, $activity, $windowsuser, $ipaddress, $macaddress, $systemname, $xml);
        }
        
        
        // prepare xml response
        $this->response  =  "<ENVELOPE>";
        $this->response .=  "<HEADER>";
        $this->response .=  "<VERSION>1</VERSION>";
        $this->response .=  "<STATUS>1</STATUS>";
        $this->response .=  "</HEADER>";
        $this->response .=  "<BODY>";
        $this->response .=  "<DATA>";
        $this->response .=  "<RESPONSE>";
        $this->response .=  "<RETURNCODE>" . htmlspecialchars($this->code) . "</RETURNCODE>";
        $this->response .=  "<RETURNMESSAGE>" . htmlspecialchars($this->message) . "</RETURNMESSAGE>";
        $this->response .=  "<TAXPAYERTYPE>" . htmlspecialchars($taxpayerType) . "</TAXPAYERTYPE>";
        $this->response .=  "<COMMODITYTAXPAYERTYPE>" . htmlspecialchars($commodityCategoryTaxpayerType) . "</COMMODITYTAXPAYERTYPE>";
        $this->response .=  "</RESPONSE>";
        $this->response .=  "</DATA>";
        $this->response .=  "</BODY>";
        $this->response .=  "</ENVELOPE>";
        
        $len = strlen($this->response);
        header ("CONTENT-LENGTH:".$len);
        print $this->response;
        return;
        
    }
    
    /**
     *	@name currencyquery
     *  @desc query currencies
     *	@return string response
     *	@param NULL
     **/
    function currencyquery(){
        $operation = NULL; //tblevents
        $permission = 'FETCHCURRENCYRATES'; //tblpermissions
        $event = NULL; //tblevents
        $eventnotification = NULL; //tbleventnotifications
        
        if ($this->errorcode) {
            $this->message = $this->errormessage;
            $this->code = $this->errorcode;
            
            $body = $this->code . ' : ' . $this->message;
            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
        } else {
            if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
                date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
            }
            
            $xml = simplexml_load_string($this->xml);
            
            $this->logger->write("Api : currencyquery() : The userid is: " . $this->userid_u, 'r');
            $this->logger->write("Api : currencyquery() : Checking permissions", 'r');
            if ($this->userpermissions[$permission]) {
                $data = $this->util->fetchcurrencyrates($this->userid_u); // will return JSON.
                $data = json_decode($data, true);
                
                $currency = '';
                
                if (isset($data[0]['currency'])) {
                    foreach ($data as $elem) {
                        $currency = $currency .  "<" . $elem['currency'] . ">" . $elem['rate'] . "</" . $elem['currency'] . ">";
                    }
                    
                    $this->logger->write("Api : validatetin() : The operation to fetch the currencies was successful", 'r');
                    $this->message = "The operation to fetch the currencies was successful";
                    $this->code = '00';
                } elseif (isset($data['returnCode'])){
                    $this->logger->write("Api : currencyquery() : The operation to fetch the currencies not successful. The error message is " . $data['returnMessage'], 'r');
                    $this->message = $data['returnMessage'];
                    $this->code = $data['returnCode'];
                    
                    $body = $this->code . ' : ' . $this->message;
                    $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                } else {
                    $this->logger->write("Api : validatetin() : The operation to fetch the currencies was not successful", 'r');
                    $this->message = "The operation to fetch the currencies was not successful";
                    $this->code = '99';
                    
                    $body = $this->code . ' : ' . $this->message;
                    $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                }
            } else {
                $this->logger->write("Api : currencyquery() : The user is not allowed to perform this function", 'r');
                $this->message = "The user is not allowed to perform this function";
                $this->code = '0099';
            }
            
            
            
            
            $activity = 'CURRENCYQUERY: ' . ': ' . $this->message;
            $windowsuser = trim($xml->WINDOWSUSER);
            $ipaddress = trim($xml->IPADDRESS);
            $macaddress = trim($xml->MACADDRESS);
            $systemname = trim($xml->SYSTEMNAME);
            $this->util->createerpauditlog($this->userid_u, $activity, $windowsuser, $ipaddress, $macaddress, $systemname, $xml);
        }
        
        
        
        // prepare xml response
        $this->response  =  "<ENVELOPE>";
        $this->response .=  "<HEADER>";
        $this->response .=  "<VERSION>1</VERSION>";
        $this->response .=  "<STATUS>1</STATUS>";
        $this->response .=  "</HEADER>";
        $this->response .=  "<BODY>";
        $this->response .=  "<DATA>";
        $this->response .=  "<RESPONSE>";
        $this->response .=  "<RETURNCODE>" . htmlspecialchars($this->code) . "</RETURNCODE>";
        $this->response .=  "<RETURNMESSAGE>" . htmlspecialchars($this->message) . "</RETURNMESSAGE>";
        $this->response .=  $currency;
        $this->response .=  "</RESPONSE>";
        $this->response .=  "</DATA>";
        $this->response .=  "</BODY>";
        $this->response .=  "</ENVELOPE>";
        
        $len = strlen($this->response);
        header ("CONTENT-LENGTH:".$len);
        print $this->response;
        return;
        
    }
    
    /**
     *	@name stockin
     *  @desc add stock to a product
     *	@return string response
     *	@param NULL
     **/
    function stockin(){
        $operation = NULL; //tblevents
        $permission = 'STOCKIN'; //tblpermissions
        $event = NULL; //tblevents
        $eventnotification = NULL; //tbleventnotifications
        
        if ($this->errorcode) {
            $this->message = $this->errormessage;
            $this->code = $this->errorcode;
            
            $body = $this->code . ' : ' . $this->message;
            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
        } else {
            if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
                date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
            }
            
            $xml = simplexml_load_string($this->xml);
            
            $productcode = trim($xml->PRODUCTCODE);//KINOK-123
            $suppliername = trim($xml->SUPPLIERNAME);
            $suppliertin = trim($xml->SUPPLIERTIN);
            $stockintype = $this->util->mapstockincode(trim($xml->STOCKINTYPE));
            $productiondate = empty(trim($xml->PRODUCTIONDATE))? '' : date('Y-m-d', strtotime(trim($xml->PRODUCTIONDATE)));
            $batchno = trim($xml->BATCHNUMBER);
            $unitprice = trim($xml->UNITPRICE);
            $qty = trim($xml->QTY);
            
            $this->logger->write("Api : stockin() : The userid is: " . $this->userid_u, 'r');
            $this->logger->write("Api : stockin() : Checking permissions", 'r');
            if ($this->userpermissions[$permission]) {
                $branch = new branches($this->db);
                $branch->getByID($this->userbranch_u);
                
                
                $data = $this->util->stockin($this->userid_u, $branch->uraid, $productcode, $batchno, $qty, $suppliertin, $suppliername, $stockintype, $productiondate, $unitprice);//will return JSON.
                
                $data = json_decode($data, true);
                
                
                if(isset($data['returnCode'])){
                    $this->logger->write("Api : stockin() : The operation to increase stock was not successful. The error message is " . $data['returnMessage'], 'r');
                    $this->message = $data['returnMessage'];
                    $this->code = $data['returnCode'];
                    
                    $body = $this->code . ' : ' . $this->message;
                    $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                } else {
                    if ($data) {
                        foreach($data as $elem){
                            $this->message = $elem['returnMessage'];
                            $this->code = $elem['returnCode'];
                        }
                    } else {
                        $this->message = "The operation was successful";
                        $this->code = '00';
                        $this->util->logstockadjustment($this->userid_u, $productcode, $batchno, $qty, $suppliertin, $suppliername, $stockintype, $productiondate, $unitprice, trim($this->appsettings['STOCKINOPERATIONTYPE']), NULL, NULL, NULL, NULL, NULL, NULL);
                    }
                    
                }
            } else {
                $this->logger->write("Api : stockin() : The user is not allowed to perform this function", 'r');
                $this->message = "The user is not allowed to perform this function";
                $this->code = '0099';
            }
            
            
            
            $activity = 'STOKIN: ' . $productcode . ': ' . $this->message;
            $windowsuser = trim($xml->WINDOWSUSER);
            $ipaddress = trim($xml->IPADDRESS);
            $macaddress = trim($xml->MACADDRESS);
            $systemname = trim($xml->SYSTEMNAME);
            $this->util->createerpauditlog($this->userid_u, $activity, $windowsuser, $ipaddress, $macaddress, $systemname, $xml);
        }
        
        

        // prepare xml response
        $this->response  =  "<ENVELOPE>";
        $this->response .=  "<HEADER>";
        $this->response .=  "<VERSION>1</VERSION>";
        $this->response .=  "<STATUS>1</STATUS>";
        $this->response .=  "</HEADER>";
        $this->response .=  "<BODY>";
        $this->response .=  "<DATA>";
        $this->response .=  "<RESPONSE>";
        $this->response .=  "<RETURNCODE>" . htmlspecialchars($this->code) . "</RETURNCODE>";
        $this->response .=  "<RETURNMESSAGE>" . htmlspecialchars($this->message) . "</RETURNMESSAGE>";
        $this->response .=  "</RESPONSE>";
        $this->response .=  "</DATA>";
        $this->response .=  "</BODY>";
        $this->response .=  "</ENVELOPE>";
        
        $len = strlen($this->response);
        header ("CONTENT-LENGTH:".$len);
        print $this->response;
        return;
        
    }
    
    /**
     *	@name batchstockin
     *  @desc add stock to mulitple products. this is a work-around for manufacturers who produce in batches
     *	@return string response
     *	@param NULL
     **/
    function batchstockin(){
        $operation = NULL; //tblevents
        $permission = 'STOCKIN'; //tblpermissions
        $event = NULL; //tblevents
        $eventnotification = NULL; //tbleventnotifications
        
        if ($this->errorcode) {
            $this->message = $this->errormessage;
            $this->code = $this->errorcode;
            
            $body = $this->code . ' : ' . $this->message;
            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
        } else {
            if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
                date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
            }
            
            $xml = simplexml_load_string($this->xml);
            
            
            
            $stockintype = $this->util->mapstockincode(trim($xml->STOCKINTYPE));//Manufacture,Local Purchase, Import, etc.
            
            $vchtype = trim($xml->VOUCHERTYPE);
            $vchtypename = trim($xml->VOUCHERTYPENAME);
            $vchnumber = trim($xml->VOUCHERNUMBER);
            $vchref = trim($xml->VOUCHERREF);
            
            $this->logger->write("Api : batchstockin() : The stockin type is " . $stockintype, 'r');
            
            $this->logger->write("Api : batchstockin() : The userid is: " . $this->userid_u, 'r');
            $this->logger->write("Api : batchstockin() : Checking permissions", 'r');
            if ($this->userpermissions[$permission]) {
                $vch_check = new DB\SQL\Mapper($this->db, 'tblgoodsstockadjustment');
                $vch_check->load(array('TRIM(voucherNumber)=? AND TRIM(voucherRef)=? AND TRIM(voucherType)=? AND TRIM(voucherTypeName)=?', trim($xml->VOUCHERNUMBER), trim($xml->VOUCHERREF), trim($xml->VOUCHERTYPE), trim($xml->VOUCHERTYPENAME)));
                $this->logger->write($this->db->log(TRUE), 'r');
                
                if($vch_check->dry ()){
                    if (trim($stockintype) == '103') {//Manufacture
                        $productiondate = date('Y-m-d', strtotime(trim($xml->DATE)));//1-Apr-2020  => 2020-04-01  date('Y-m-d', strtotime($date))
                        $batchno = trim($xml->VOUCHERNUMBER);//1
                        $suppliername = '';
                        $suppliertin = '';
                    } else {
                        $productiondate = '';
                        $batchno = '';
                        $suppliername = trim($xml->SUPPLIERNAME);
                        $suppliertin = trim($xml->SUPPLIERTIN);
                    }
                    
                    $products = array();
                    
                    foreach ($xml->INVENTORIES->INVENTORY as $obj){
                        $products[] = array(
                            'productCode' => (trim($obj->PRODUCTCODE)),//8762753
                            'quantity' => $this->util->removeunitsfromqty(trim($obj->QTY)),//23 Pcs
                            'unitPrice' => $this->util->removeunitsfromrate(trim($obj->RATE))//25,000.00/Pcs
                        );
                    }
                    
                    
                    $branch = new branches($this->db);
                    $branch->getByID($this->userbranch_u);
                    
                    
                    $data = $this->util->batchstockin($this->userid_u, $branch->uraid, $products, $batchno, $suppliertin, $suppliername, $stockintype, $productiondate);//will return JSON.
                    
                    $data = json_decode($data, true);
                    
                    
                    
                    if(isset($data['returnCode'])){
                        $this->logger->write("Api : batchstockin() : The operation to increase stock was not successful. The error message is " . $data['returnMessage'], 'r');
                        $this->message = $data['returnMessage'];
                        $this->code = $data['returnCode'];
                        
                        $body = $this->code . ' : ' . $this->message;
                        $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                    } else {
                        if ($data) {
                            foreach($data as $elem){
                                $this->message = $elem['returnMessage'];
                                $this->code = $elem['returnCode'];
                            }
                        } else {
                            $this->message = "The operation was successful";
                            $this->code = '00';
                            $this->util->logstockadjustment($this->userid_u, NULL, $batchno, NULL, $suppliertin, $suppliername, $stockintype, $productiondate, NULL, trim($this->appsettings['STOCKINOPERATIONTYPE']), $vchtype, $vchtypename, $vchnumber, $vchref, NULL, NULL);
                        }
                        
                    }
                } else {
                    $this->message = 'The voucher/journal has already been uploaded into EFRIS';
                    $this->code = '99';
                }
            } else {
                $this->logger->write("Api : batchstockin() : The user is not allowed to perform this function", 'r');
                $this->message = "The user is not allowed to perform this function";
                $this->code = '0099';
            }
            
            
            
            $activity = 'BATCHSTOCKIN: ' . $vchnumber . ': ' . $vchref . ': ' . $this->message;
            $windowsuser = trim($xml->WINDOWSUSER);
            $ipaddress = trim($xml->IPADDRESS);
            $macaddress = trim($xml->MACADDRESS);
            $systemname = trim($xml->SYSTEMNAME);
            $this->util->createerpauditlog($this->userid_u, $activity, $windowsuser, $ipaddress, $macaddress, $systemname, $xml);
        }
        
        
        
        
        // prepare xml response
        $this->response  =  "<ENVELOPE>";
        $this->response .=  "<HEADER>";
        $this->response .=  "<VERSION>1</VERSION>";
        $this->response .=  "<STATUS>1</STATUS>";
        $this->response .=  "</HEADER>";
        $this->response .=  "<BODY>";
        $this->response .=  "<DATA>";
        $this->response .=  "<RESPONSE>";
        $this->response .=  "<RETURNCODE>" . htmlspecialchars($this->code) . "</RETURNCODE>";
        $this->response .=  "<RETURNMESSAGE>" . htmlspecialchars($this->message) . "</RETURNMESSAGE>";
        $this->response .=  "</RESPONSE>";
        $this->response .=  "</DATA>";
        $this->response .=  "</BODY>";
        $this->response .=  "</ENVELOPE>";
        
        $len = strlen($this->response);
        header ("CONTENT-LENGTH:".$len);
        print $this->response;
        return;
        
    }
    
    /**
     *	@name stockout
     *  @desc adjust stock of a product
     *	@return string response
     *	@param NULL
     **/
    function stockout(){
        $operation = NULL; //tblevents
        $permission = 'STOCKOUT'; //tblpermissions
        $event = NULL; //tblevents
        $eventnotification = NULL; //tbleventnotifications
        
        if ($this->errorcode) {
            $this->message = $this->errormessage;
            $this->code = $this->errorcode;
            
            $body = $this->code . ' : ' . $this->message;
            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
        } else {
            if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
                date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
            }
            
            $xml = simplexml_load_string($this->xml);
            
            $productcode = trim($xml->PRODUCTCODE);//KINOK-123
            $adjustmenttype = $this->util->mapstockadjustmentcode(trim($xml->ADJUSTMENTTYPE));
            $remarks = trim($xml->REMARKS);
            $batchno = trim($xml->BATCHNUMBER);
            $qty = trim($xml->QTY);
            
            $this->logger->write("Api : stockout() : The userid is: " . $this->userid_u, 'r');
            $this->logger->write("Api : stockout() : Checking permissions", 'r');
            if ($this->userpermissions[$permission]) {
                $branch = new branches($this->db);
                $branch->getByID($this->userbranch_u);
                
                
                $data = $this->util->stockout($this->userid_u, $branch->uraid, $productcode, $batchno, $qty, $adjustmenttype, $remarks);
                
                $data = json_decode($data, true);
                
                if(isset($data['returnCode'])){
                    $this->logger->write("Api : stockout() : The operation to decrease stock not successful. The error message is " . $data['returnMessage'], 'r');
                    $this->message = $data['returnMessage'];
                    $this->code = $data['returnCode'];
                    
                    $body = $this->code . ' : ' . $this->message;
                    $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                } else {
                    if ($data) {
                        foreach($data as $elem){
                            $this->message = $elem['returnMessage'];
                            $this->code = $elem['returnCode'];
                        }
                    } else {
                        $this->message = "The operation was successful";
                        $this->code = '00';
                        $this->util->logstockadjustment($this->userid_u, $productcode, $batchno, $qty, NULL, NULL, NULL, NULL, NULL, trim($this->appsettings['STOCKOUTOPERATIONTYPE']), NULL, NULL, NULL, NULL, $adjustmenttype, $remarks);
                    }
                    
                }
            } else {
                $this->logger->write("Api : stockout() : The user is not allowed to perform this function", 'r');
                $this->message = "The user is not allowed to perform this function";
                $this->code = '0099';
            }
            
            
            
            
            $activity = 'STOKOUT: ' . $productcode . ': ' . $this->message;
            $windowsuser = trim($xml->WINDOWSUSER);
            $ipaddress = trim($xml->IPADDRESS);
            $macaddress = trim($xml->MACADDRESS);
            $systemname = trim($xml->SYSTEMNAME);
            $this->util->createerpauditlog($this->userid_u, $activity, $windowsuser, $ipaddress, $macaddress, $systemname, $xml);
        }
        
        
        
        // prepare xml response
        $this->response  =  "<ENVELOPE>";
        $this->response .=  "<HEADER>";
        $this->response .=  "<VERSION>1</VERSION>";
        $this->response .=  "<STATUS>1</STATUS>";
        $this->response .=  "</HEADER>";
        $this->response .=  "<BODY>";
        $this->response .=  "<DATA>";
        $this->response .=  "<RESPONSE>";
        $this->response .=  "<RETURNCODE>" . htmlspecialchars($this->code) . "</RETURNCODE>";
        $this->response .=  "<RETURNMESSAGE>" . htmlspecialchars($this->message) . "</RETURNMESSAGE>";
        $this->response .=  "</RESPONSE>";
        $this->response .=  "</DATA>";
        $this->response .=  "</BODY>";
        $this->response .=  "</ENVELOPE>";
        
        $len = strlen($this->response);
        header ("CONTENT-LENGTH:".$len);
        print $this->response;
        return;
        
    }
    
    /**
     *	@name batchstockout
     *  @desc adjust stock of one or more product
     *	@return string response
     *	@param NULL
     **/
    function batchstockout(){
        $operation = NULL; //tblevents
        $permission = 'STOCKOUT'; //tblpermissions
        $event = NULL; //tblevents
        $eventnotification = NULL; //tbleventnotifications
        
        if ($this->errorcode) {
            $this->message = $this->errormessage;
            $this->code = $this->errorcode;
            
            $body = $this->code . ' : ' . $this->message;
            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
        } else {
            if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
                date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
            }
            
            $xml = simplexml_load_string($this->xml);
            
            
            $vchtype = trim($xml->VOUCHERTYPE);
            $vchtypename = trim($xml->VOUCHERTYPENAME);
            $vchnumber = trim($xml->VOUCHERNUMBER);
            $vchref = trim($xml->VOUCHERREF);
            
            $adjustmenttype = $this->util->mapstockadjustmentcode(trim($xml->ADJUSTMENTTYPE));
            $remarks = trim($xml->REMARKS);
            
            $this->logger->write("Api : batchstockin() : The userid is: " . $this->userid_u, 'r');
            $this->logger->write("Api : batchstockin() : Checking permissions", 'r');
            if ($this->userpermissions[$permission]) {
                $vch_check = new DB\SQL\Mapper($this->db, 'tblgoodsstockadjustment');
                $vch_check->load(array('TRIM(voucherNumber)=? AND TRIM(voucherRef)=? AND TRIM(voucherType)=? AND TRIM(voucherTypeName)=?', trim($xml->VOUCHERNUMBER), trim($xml->VOUCHERREF), trim($xml->VOUCHERTYPE), trim($xml->VOUCHERTYPENAME)));
                $this->logger->write($this->db->log(TRUE), 'r');
                
                if($vch_check->dry ()){
                    $products = array();
                    
                    foreach ($xml->INVENTORIES->INVENTORY as $obj){
                        $products[] = array(
                            'productCode' => (trim($obj->PRODUCTCODE)),//8762753
                            'quantity' => $this->util->removeunitsfromqty(trim($obj->QTY)),//23 Pcs
                            'unitPrice' => $this->util->removeunitsfromrate(trim($obj->RATE))//25,000.00/Pcs
                        );
                    }
                    
                    
                    $branch = new branches($this->db);
                    $branch->getByID($this->userbranch_u);
                    
                    
                    $data = $this->util->batchstockout($this->userid_u, $branch->uraid, $products, $adjustmenttype, $remarks);//will return JSON.
                    
                    $data = json_decode($data, true);
                    
                    
                    
                    if(isset($data['returnCode'])){
                        $this->logger->write("Api : batchstockout() : The operation to decrease stock was not successful. The error message is " . $data['returnMessage'], 'r');
                        $this->message = $data['returnMessage'];
                        $this->code = $data['returnCode'];
                        
                        $body = $this->code . ' : ' . $this->message;
                        $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                    } else {
                        if ($data) {
                            foreach($data as $elem){
                                $this->message = $elem['returnMessage'];
                                $this->code = $elem['returnCode'];
                            }
                        } else {
                            $this->message = "The operation was successful";
                            $this->code = '00';
                            $this->util->logstockadjustment($this->userid_u, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, trim($this->appsettings['STOCKOUTOPERATIONTYPE']), $vchtype, $vchtypename, $vchnumber, $vchref, $adjustmenttype, $remarks);
                        }
                        
                    }
                } else {
                    $this->message = 'The voucher has already been uploaded into EFRIS';
                    $this->code = '99';
                }
            } else {
                $this->logger->write("Api : batchstockout() : The user is not allowed to perform this function", 'r');
                $this->message = "The user is not allowed to perform this function";
                $this->code = '0099';
            }
            
            $activity = 'STOKOUT: ' . $vchnumber . ': ' . $vchref . ': ' . $this->message;
            $windowsuser = trim($xml->WINDOWSUSER);
            $ipaddress = trim($xml->IPADDRESS);
            $macaddress = trim($xml->MACADDRESS);
            $systemname = trim($xml->SYSTEMNAME);
            $this->util->createerpauditlog($this->userid_u, $activity, $windowsuser, $ipaddress, $macaddress, $systemname, $xml);
        }
        
        
        
        // prepare xml response
        $this->response  =  "<ENVELOPE>";
        $this->response .=  "<HEADER>";
        $this->response .=  "<VERSION>1</VERSION>";
        $this->response .=  "<STATUS>1</STATUS>";
        $this->response .=  "</HEADER>";
        $this->response .=  "<BODY>";
        $this->response .=  "<DATA>";
        $this->response .=  "<RESPONSE>";
        $this->response .=  "<RETURNCODE>" . htmlspecialchars($this->code) . "</RETURNCODE>";
        $this->response .=  "<RETURNMESSAGE>" . htmlspecialchars($this->message) . "</RETURNMESSAGE>";
        $this->response .=  "</RESPONSE>";
        $this->response .=  "</DATA>";
        $this->response .=  "</BODY>";
        $this->response .=  "</ENVELOPE>";
        
        $len = strlen($this->response);
        header ("CONTENT-LENGTH:".$len);
        print $this->response;
        return;
        
    }
    
    /**
     *	@name fetchproduct
     *  @desc fetch a product
     *	@return string response
     *	@param NULL
     **/
    function fetchproduct(){
        $operation = NULL; //tblevents
        $permission = 'FETCHPRODUCT'; //tblpermissions
        $event = NULL; //tblevents
        $eventnotification = NULL; //tbleventnotifications
        
        if ($this->errorcode) {
            $this->message = $this->errormessage;
            $this->code = $this->errorcode;
            
            $body = $this->code . ' : ' . $this->message;
            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
        } else {
            if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
                date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
            }
            
            $xml = simplexml_load_string($this->xml);
            
            
            
            
            $branch = new branches($this->db);
            $branch->getByID($this->userbranch_u);
                        
            
            $ProductCode = trim($xml->PRODUCTCODE);//KINOK-123
            $ErpQty = $this->util->removeunitsfromqty(trim($xml->ERPQTY));
            //$this->logger->write("Api : fetchproduct() : The ERP quantity is: " . $ErpQty, 'r');
            
            //Handles cases where the ERP Qty is not availed.
            $ErpQty = trim($ErpQty) == ''? '0' : $ErpQty;
            
            $this->logger->write("Api : fetchproduct() : The product code is " . $ProductCode, 'r');
            
            if(trim($ProductCode) !== '' || !empty(trim($ProductCode))) {
                
                $this->logger->write("Api : fetchproduct() : The userid is: " . $this->userid_u, 'r');
                $this->logger->write("Api : fetchproduct() : Checking permissions", 'r');
                if ($this->userpermissions[$permission]) {
                    $product = array(
                        'uraproductidentifier' => NULL,
                        'erpid' => NULL,
                        'erpcode' => trim($ProductCode),
                        'name' => NULL,
                        'code' => trim($ProductCode),
                        'measureunit' => NULL,
                        'unitprice' => 0,
                        'currency' => NULL,
                        'commoditycategorycode' => NULL,
                        'hasexcisetax' => NULL,
                        'description' => NULL,
                        'stockprewarning' => NULL,
                        'piecemeasureunit' => NULL,
                        'havepieceunit' => NULL,
                        'pieceunitprice' => NULL,
                        'packagescaledvalue' => NULL,
                        'piecescaledvalue' => NULL,
                        'excisedutycode' => NULL,
                        'uraquantity' => 0,
                        'erpquantity' => 0,
                        'purchaseprice' => 0,
                        'stockintype' => NULL,
                        'haveotherunit' => NULL,
                        'isexempt' => NULL,
                        'iszerorated' => NULL,
                        'taxrate' => NULL,
                        'statuscode' => NULL,
                        'source' => NULL,
                        'exclusion' => NULL
                    );
                    
                    $productid = '';
                    $uraquantity =  0;
                    $isexempt = '';
                    $iszerorated = '';
                    $taxrate = '';
                    $statuscode = '';
                    $source = '';
                    $exclusion = '';
                    
                    
                    //Fetch the details from EFRIS
                    $this->logger->write("Api : fetchproduct() : Fetching product " . $product['code'], 'r');
                    $n_data = $this->util->fetchproduct($this->userid_u, $product, $branch->uraid);//will return JSON.
                    //var_dump($data);
                    $n_data = json_decode($n_data, true);
                    
                    if(isset($n_data['records'])){
                        $this->logger->write("Api : fetchproduct() : The fetch returned some records", 'r');
                        if ($n_data['records']) {
                            foreach($n_data['records'] as $elem){
                                
                                try{
                                    $productid = $elem['id'];
                                    $uraquantity =  $elem['stock'];
                                    $isexempt = $elem['isExempt'];
                                    $iszerorated = $elem['isZeroRate'];
                                    $taxrate = $elem['taxRate'];
                                    $statuscode = $elem['statusCode'];
                                    $source = $elem['source'];
                                    $exclusion = $elem['exclusion'];
                                    
                                    $product['name'] = $elem['goodsName'];
                                    $product['measureunit'] = $elem['measureUnit'];
                                    $product['unitprice'] = $elem['unitPrice'];
                                    $product['currency'] = $elem['currency'];
                                    $product['commoditycategorycode'] = $elem['commodityCategoryCode'];
                                    $product['hasexcisetax'] = $elem['haveExciseTax'];
                                    $product['stockprewarning'] = $elem['stockPrewarning'];
                                    $product['havepieceunit'] = $elem['havePieceUnit'];
                                    $product['haveotherunit'] = $elem['haveOtherUnit'];
                                    
                                    $product['isexempt'] = $elem['isExempt'];
                                    $product['iszerorated'] = $elem['isZeroRate'];
                                    $product['taxrate'] = $elem['taxRate'];
                                    $product['statuscode'] = $elem['statusCode'];
                                    $product['source'] = $elem['source'];
                                    $product['exclusion'] = $elem['exclusion'];
                                    
                                } catch (Exception $e) {
                                    $this->logger->write("Api : fetchproduct() : The operation to fetch the product encountered an error. The error message is " . $e->getMessage(), 'r');
                                }
                            }
                            
                            //Add/update the product to eTW
                            $product['uraproductidentifier'] = $productid;
                            $product['uraquantity'] = $uraquantity;
                            $product['erpquantity'] = $ErpQty;
                            
                            $this->logger->write("Api : fetchproduct() : The ERP quantity is: " . $product['erpquantity'], 'r');
                            
                            $pdct = new products($this->db);
                            $pdct->getByErpCode(trim($product['code']));
                            
                            if ($pdct->dry()) {
                                $this->logger->write("Api : fetchproduct() : The product " . $product['code'] . " does not exist on eTW", 'r');
                                
                                $product['description'] = "This product was created by the TALLY api";
                                $pdct_status = $this->util->createproduct($product, $this->userid_u);
                                
                            } else {
                                $this->logger->write("Api : fetchproduct() : The product " . $product['code'] . " exists on eTW", 'r');
                                
                                $product['description'] = "This product was updated by the TALLY api";
                                $pdct_status = $this->util->updateproduct($product, $this->userid_u);
                            }
                            
                            
                            if ($pdct_status) {
                                $this->logger->write("Api : fetchproduct() : The product " . $product['code'] . " was created/updated on eTW successfully", 'r');
                            } else {
                                $this->logger->write("Api : fetchproduct() : The product " . $product['code'] . " was NOT created/updated on eTW", 'r');
                            }
                            
                            /**
                             * Over-ride the stock quantity in case the query was made from a branch
                             */
                            
                            $q_data = $this->util->stockquery($this->userid_u, $branch->uraid, $ProductCode); //will return JSON
                            $q_data = json_decode($q_data, true);
                            
                            if (isset($q_data['stock'])){
                                $uraquantity = $q_data['stock'];
                                //$stockPrewarning = $data['stockPrewarning'];
                                $this->logger->write("Api : fetchproduct() : The operation to query branch specific stock was successful", 'r');
                            } elseif (isset($q_data['returnCode'])){
                                $this->logger->write("Api : fetchproduct() : The operation to query branch specific stock not successful. The error message is " . $q_data['returnMessage'], 'r');
                                
                                $body = $q_data['returnCode'] . ' : ' . $q_data['returnMessage'];
                                $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                            } else {
                                $this->logger->write("Api : fetchproduct() : The operation to query branch specific stock was not successful", 'r');
                                
                                $body = '99' . ' : ' . 'The operation to query the taxpayer was not successful';
                                $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                            }
                            
                            $this->logger->write("Api : fetchproduct() : The product " . $product['code'] . " was fetched successfully", 'r');
                            $this->message = 'The operation was successful';
                            $this->code = '000';
                            
                        } else {
                            $this->logger->write("Api : fetchproduct() : The fetch returned 0 records", 'r');
                            $this->logger->write("Api : fetchproduct() : The fetch operation for product " . $product['code'] . " returned 0 records", 'r');
                            $this->message = 'The operation to fetch the product didnt return anything. The TCS is offline OR you have not yet uploaded this product';
                            $this->code = '999';
                        }
                        
                        /*$this->logger->write("Api : fetchproduct() : The product " . $product['code'] . " was fetched successfully", 'r');
                         $this->message = 'The operation was successful';
                         $this->code = '000';*/
                        
                    } else {
                        
                        foreach($n_data as $elem){
                            if(isset($elem['returnCode'])){
                                $this->message = $elem['returnMessage'];
                                $this->code = $elem['returnCode'];
                                $this->logger->write("Api : fetchproduct() : The operation to fetch the product was not successful. The error message is " . $elem['returnMessage'], 'r');
                                
                                $body = $this->code . ' : ' . $this->message;
                                $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                            } else {
                                $this->logger->write("Api : fetchproduct() : The operation to fetch the product didnt return anything. The TCS is offline OR you have not yet uploaded this product", 'r');
                                $this->message = "The operation to fetch the product didnt return anything. The TCS is offline OR you have not yet uploaded this product";
                                $this->code = "999";
                                
                                $body = $this->code . ' : ' . $this->message;
                                $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                            }
                        }
                        
                        
                    }
                } else {
                    $this->logger->write("Api : fetchproduct() : The user is not allowed to perform this function", 'r');
                    $this->message = "The user is not allowed to perform this function";
                    $this->code = '0099';
                }
                
                
                
                
                $activity = 'FETCHPRODUCT: ' . $ProductCode . ': ' . $this->message;
                $windowsuser = trim($xml->WINDOWSUSER);
                $ipaddress = trim($xml->IPADDRESS);
                $macaddress = trim($xml->MACADDRESS);
                $systemname = trim($xml->SYSTEMNAME);
                $this->util->createerpauditlog($this->userid_u, $activity, $windowsuser, $ipaddress, $macaddress, $systemname, $xml);
            } else {
                $this->logger->write("Api : fetchproduct() : The product code is empty", 'r');
                $this->message = 'The product/service code is empty. Please configure.';
                $this->code = '99';
            }   
        }
        
        
        
        // prepare xml response
        $this->response  =  "<ENVELOPE>";
        $this->response .=  "<HEADER>";
        $this->response .=  "<VERSION>1</VERSION>";
        $this->response .=  "<STATUS>1</STATUS>";
        $this->response .=  "</HEADER>";
        $this->response .=  "<BODY>";
        $this->response .=  "<DATA>";
        $this->response .=  "<RESPONSE>";
        $this->response .=  "<RETURNCODE>" . htmlspecialchars($this->code) . "</RETURNCODE>";
        $this->response .=  "<RETURNMESSAGE>" . htmlspecialchars($this->message) . "</RETURNMESSAGE>";
        $this->response .=  "<PRODUCTID>" . htmlspecialchars($productid) . "</PRODUCTID>";
        $this->response .=  "<ISTAXEXEMPT>". htmlspecialchars($this->util->decodechoicecode($isexempt)) ."</ISTAXEXEMPT>";
        $this->response .=  "<ISZERORATED>". htmlspecialchars($this->util->decodechoicecode($iszerorated)) ."</ISZERORATED>";
        $this->response .=  "<TAXRATE>". htmlspecialchars($taxrate) ."</TAXRATE>";
        $this->response .=  "<STATUS>". htmlspecialchars($this->util->decodeproductstatuscode($statuscode)) ."</STATUS>";
        $this->response .=  "<SOURCE>". htmlspecialchars($this->util->decodeproductsourcecode($source)) ."</SOURCE>";
        $this->response .=  "<EXCLUSION>". htmlspecialchars($this->util->decodeproductexclusioncode($exclusion)) ."</EXCLUSION>";
        $this->response .=  "<URAQTY>" . htmlspecialchars($uraquantity) . "</URAQTY>";
        $this->response .=  "</RESPONSE>";
        $this->response .=  "</DATA>";
        $this->response .=  "</BODY>";
        $this->response .=  "</ENVELOPE>";
        
        $len = strlen($this->response);
        header ("CONTENT-LENGTH:".$len);
        print $this->response;
        return;
        
    }
    
    
    /**
     *	@name uploadproduct
     *  @desc upload product
     *	@return string response
     *	@param NULL
     **/
    function uploadproduct(){
        $operation = NULL; //tblevents
        $permission = 'UPLOADPRODUCT'; //tblpermissions
        $event = NULL; //tblevents
        $eventnotification = NULL; //tbleventnotifications
        
        if ($this->errorcode) {
            $this->message = $this->errormessage;
            $this->code = $this->errorcode;
            
            $body = $this->code . ' : ' . $this->message;
            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
        } else {
            if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
                date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
            }
            
            
            /**
             *
             * Steps to follow when uploading to EFRIS
             * 0. Grab the xml body from the ERP
             * 1. Check permissions of the api key
             * 2. Populate the mandatory fields
             * 3. Call the EFRIS interface
             * 4. Receive feedback from EFRIS
             * 5. If it is a success, populate eTW product tables
             * 6. Transmit the same to the ERP & make necessary updates
             */
            
            $xml = simplexml_load_string($this->xml);
            $ProductName = $xml->ITEMNAME;//Nails 12-Inch
            $ProductId = $xml->ITEMID;//
            $ProductMeasureUnits = $xml->MEASUREUNITS;//Kgs, Pcs, etc.
            /*
             * Date: 2021-01-10
             * Author: Francis Lubanga
             * Description: Pause the sending of alternative measure units. They have a dependency on piece units
             */
            //$ProductAltMeasureUnits = $xml->ALTMEASUREUNITS;//Kgs, Pcs, etc.
            $ProductAltMeasureUnits = NULL;
            $ProductCommodityCode = $xml->COMMODITYCODE;//Corrugated steel sheet
            $ProductCode = $xml->PRODUCTCODE;//KINOK-123
            $ProductCurrency = $xml->CURRENCY;//UGX
            $ProductHasExciseDuty = $xml->HASEXCISEDUTYFLAG;//No,Yes
            $ProductExciseDutyCode = $xml->EXCISEDUTYCODE;
            $ProductHavePieceUnits = $xml->HAVEPIECEUNITSFLAG;//No,Yes
            $ProductPieceUnitsMeasureUnit = $xml->PIECEUNITSMEASUREUNIT;//Kgs, Pcs, etc.
            $ProductPieceUnitPrice = $xml->PIECEUNITPRICE;
            $ProductPackageScaleValue = $xml->PACKAGESCALEVALUE;
            $ProductPieceScaleValue = $xml->PIECESCALEVALUE;
            $ProductStockPrewarning = $xml->STOCKPREWARNING;
            
            $this->logger->write("Api : uploadproduct() : The commodity code is " . $ProductCommodityCode, 'r');
            $this->logger->write("Api : uploadproduct() : The product code is " . $ProductCode, 'r');
            
            
            $this->logger->write("Api : uploadproduct() : The userid is: " . $this->userid_u, 'r');
            $this->logger->write("Api : uploadproduct() : Checking permissions", 'r');
            if ($this->userpermissions[$permission]) {
                
                
                $altunits = array();
                
                $product = array(
                    'uraproductidentifier' => NULL,
                    'erpid' => trim($ProductId),
                    'erpcode' => trim($ProductCode),
                    'name' => trim($ProductName),
                    'code' => trim($ProductCode),
                    'measureunit' => $this->util->mapmeasureunit($ProductMeasureUnits),
                    'unitprice' => 0,
                    'currency' => $this->util->mapcurrency($ProductCurrency),
                    'commoditycategorycode' => $this->util->mapcommodity($ProductCommodityCode),
                    'hasexcisetax' => strtoupper(trim($ProductHasExciseDuty)) == 'NO'? '102' : '101',
                    'description' => NULL,
                    'stockprewarning' => (int)$ProductStockPrewarning,
                    'piecemeasureunit' => $this->util->mapmeasureunit($ProductPieceUnitsMeasureUnit),
                    'havepieceunit' => strtoupper(trim($ProductHavePieceUnits)) == 'NO'? '102' : '101',
                    'pieceunitprice' => empty(trim($ProductPieceUnitPrice))? '' : (float)trim($ProductPieceUnitPrice),
                    'packagescaledvalue' => empty(trim($ProductPackageScaleValue))? '' : (int)trim($ProductPackageScaleValue),
                    'piecescaledvalue' => empty(trim($ProductPieceScaleValue))? '' : (int)trim($ProductPieceScaleValue),
                    'excisedutycode' => trim($ProductExciseDutyCode),
                    'uraquantity' => 0,
                    'erpquantity' => 0,
                    'purchaseprice' => 0,
                    'stockintype' => NULL,
                    'haveotherunit' => empty(trim($ProductAltMeasureUnits))? '102' : '101',
                    'isexempt' => NULL,
                    'iszerorated' => NULL,
                    'taxrate' => NULL,
                    'statuscode' => NULL,
                    'source' => NULL,
                    'exclusion' => NULL
                );
                
                if (!empty(trim($ProductAltMeasureUnits))) {
                    $this->logger->write("Api : uploadproduct() : The product " . $product['code'] . " has an alternative measure unit", 'r');
                    $altunits[] = array(
                        'otherunit' => $this->util->mapmeasureunit($ProductAltMeasureUnits),
                        'otherPrice' => 1,
                        'otherscaled' => 1,
                        'packagescaled' => 1
                    );
                } else {
                    $this->logger->write("Api : uploadproduct() : The product " . $product['code'] . " does not have an alternative measure unit", 'r');
                }
                
                $branch = new branches($this->db);
                $branch->getByID($this->userbranch_u);
                
                $productid = '';
                $isexempt = '';
                $iszerorated = '';
                $taxrate = '';
                $statuscode = '';
                $source = '';
                $exclusion = '';
                
                $this->logger->write("Api : uploadproduct() : Uploading product " . $product['code'], 'r');
                $data = $this->util->uploadproduct($this->userid_u, $product, $altunits);//will return JSON.
                
                $data = json_decode($data, true);
                //var_dump($data);
                
                if (empty($data)) {
                    //Fetch the details from EFRIS
                    $this->logger->write("Api : uploadproduct() : Fetching product " . $product['code'], 'r');
                    $n_data = $this->util->fetchproduct($this->userid_u, $product, $branch->uraid);//will return JSON.
                    //var_dump($data);
                    $n_data = json_decode($n_data, true);
                    
                    if(isset($n_data['records'])){
                        $this->logger->write("Api : uploadproduct() : The fetch returned some records", 'r');
                        if ($n_data['records']) {
                            foreach($n_data['records'] as $elem){
                                
                                try{
                                    $productid = $elem['id'];
                                    $isexempt = $elem['isExempt'];
                                    $iszerorated = $elem['isZeroRate'];
                                    $taxrate = $elem['taxRate'];
                                    $statuscode = $elem['statusCode'];
                                    $source = $elem['source'];
                                    $exclusion = $elem['exclusion'];
                                    
                                    $product['uraproductidentifier'] = $elem['id'];
                                    $product['uraquantity'] = $elem['stock'];
                                    $product['name'] = $elem['goodsName'];
                                    $product['measureunit'] = $elem['measureUnit'];
                                    $product['unitprice'] = $elem['unitPrice'];
                                    $product['currency'] = $elem['currency'];
                                    $product['commoditycategorycode'] = $elem['commodityCategoryCode'];
                                    $product['hasexcisetax'] = $elem['haveExciseTax'];
                                    $product['stockprewarning'] = $elem['stockPrewarning'];
                                    $product['havepieceunit'] = $elem['havePieceUnit'];
                                    $product['haveotherunit'] = $elem['haveOtherUnit'];
                                    
                                    $product['isexempt'] = $elem['isExempt'];
                                    $product['iszerorated'] = $elem['isZeroRate'];
                                    $product['taxrate'] = $elem['taxRate'];
                                    $product['statuscode'] = $elem['statusCode'];
                                    $product['source'] = $elem['source'];
                                    $product['exclusion'] = $elem['exclusion'];
                                    
                                    $this->logger->write("Api : uploadproduct() : Product Code - " . $product['code'] . " - was fetched & attributes updated successfully by " . $this->username, 'r');
                                } catch (Exception $e) {
                                    $this->logger->write("Api : uploadproduct() : The operation to fetch the product was not successful. The error message is " . $e->getMessage(), 'r');
                                }
                            }
                            
                        } else {
                            $this->logger->write("Api : uploadproduct() : The operation to fetch the product " . $product['code'] . " returned 0 records", 'r');
                        }
                        
                        $this->logger->write("Api : uploadproduct() : The product " . $product['code'] . " was uploaded/updated successfully", 'r');
                        $this->message = "The product was uploaded/updated successfully";
                        $this->code = '00';
                        
                        //Add/update the product to eTW
                        $pdct = new products($this->db);
                        $pdct->getByErpCode(trim($product['code']));
                        
                        if ($pdct->dry()) {
                            $this->logger->write("Api : uploadproduct() : The product " . $product['code'] . " does not exist on eTW", 'r');
                            
                            $product['description'] = "This product was created by the TALLY api";
                            $pdct_status = $this->util->createproduct($product, $this->userid_u);
                            
                        } else {
                            $this->logger->write("Api : uploadproduct() : The product " . $product['code'] . " exists on eTW", 'r');
                            
                            $product['description'] = "This product was updated by the TALLY api";
                            $pdct_status = $this->util->updateproduct($product, $this->userid_u);
                        }
                        
                        
                        if ($pdct_status) {
                            $this->logger->write("Api : uploadproduct() : The product " . $product['code'] . " was created/updated on eTW successfully", 'r');
                        } else {
                            $this->logger->write("Api : uploadproduct() : The product " . $product['code'] . " was NOT created/updated on eTW", 'r');
                        }
                    } else {
                        $this->logger->write("Api : uploadproduct() : The product " . $product['code'] . " was not uploaded successfully", 'r');
                        //var_dump($n_data);
                        $this->message = $n_data['returnMessage'];
                        $this->code = $n_data['returnCode'];
                        
                        $body = $this->code . ' : ' . $this->message;
                        $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                    }
                    
                } else {
                    $this->logger->write("Api : uploadproduct() : The product " . $product['code'] . " was not uploaded successfully", 'r');
                    
                    foreach($data as $elem){
                        if(isset($elem['returnCode'])){
                            $this->message = $elem['returnMessage'];
                            $this->code = $elem['returnCode'];
                            
                            /**
                             * Handle response code 602: Goods Code already exists && the product is not on the eTW database
                             * 1. Check if the product is on eTW
                             * 2. If the product is on eTW, then ignore
                             * 3. If the product is NOT on eTW, call the create product API
                             */
                            
                            if (trim($elem['returnCode']) == '602') {
                                $pdct = new products($this->db);
                                $pdct->getByErpCode(trim($product['code']));
                                
                                if ($pdct->dry()) {
                                    $this->logger->write("Api : uploadproduct() : The product " . $product['code'] . " does not exist on eTW", 'r');
                                    
                                    $pdct_status = $this->util->createproduct($product, $this->userid_u);
                                    
                                    if ($pdct_status) {
                                        $this->logger->write("Api : uploadproduct() : The product " . $product['code'] . " was created on eTW successfully", 'r');
                                    } else {
                                        $this->logger->write("Api : uploadproduct() : The product " . $product['code'] . " was NOT created on eTW", 'r');
                                    }
                                } else {
                                    ;
                                }
                            }
                        }
                    }
                }
            } else {
                $this->logger->write("Api : uploadproduct() : The user is not allowed to perform this function", 'r');
                $this->message = "The user is not allowed to perform this function";
                $this->code = '0099';
            }
            
            
            
            
            
            //$productid = '0091862';
            
            
            $activity = 'UPLOADPRODUCT: ' . $ProductCode . ': ' . $this->message;
            $windowsuser = trim($xml->WINDOWSUSER);
            $ipaddress = trim($xml->IPADDRESS);
            $macaddress = trim($xml->MACADDRESS);
            $systemname = trim($xml->SYSTEMNAME);
            $this->util->createerpauditlog($this->userid_u, $activity, $windowsuser, $ipaddress, $macaddress, $systemname, $xml);
        }
        
        
        
        // prepare xml response
        $this->response  =  "<ENVELOPE>";
        $this->response .=  "<HEADER>";
        $this->response .=  "<VERSION>1</VERSION>";
        $this->response .=  "<STATUS>1</STATUS>";
        $this->response .=  "</HEADER>";
        $this->response .=  "<BODY>";
        $this->response .=  "<DATA>";
        $this->response .=  "<RESPONSE>";
        $this->response .=  "<RETURNCODE>" . htmlspecialchars($this->code) . "</RETURNCODE>";
        $this->response .=  "<RETURNMESSAGE>" . htmlspecialchars($this->message) . "</RETURNMESSAGE>";
        $this->response .=  "<ISTAXEXEMPT>". htmlspecialchars($this->util->decodechoicecode($isexempt)) ."</ISTAXEXEMPT>";
        $this->response .=  "<ISZERORATED>". htmlspecialchars($this->util->decodechoicecode($iszerorated)) ."</ISZERORATED>";
        $this->response .=  "<TAXRATE>". htmlspecialchars($taxrate) ."</TAXRATE>";
        $this->response .=  "<STATUS>". htmlspecialchars($this->util->decodeproductstatuscode($statuscode)) ."</STATUS>";
        $this->response .=  "<SOURCE>". htmlspecialchars($this->util->decodeproductsourcecode($source)) ."</SOURCE>";
        $this->response .=  "<EXCLUSION>". htmlspecialchars($this->util->decodeproductexclusioncode($exclusion)) ."</EXCLUSION>";
        $this->response .=  "<PRODID>". htmlspecialchars($productid) ."</PRODID>";
        $this->response .=  "</RESPONSE>";
        $this->response .=  "</DATA>";
        $this->response .=  "</BODY>";
        $this->response .=  "</ENVELOPE>";

        $len = strlen($this->response);
        header ("CONTENT-LENGTH:".$len);
        print $this->response;
        return;
    }
 
 
    /**
     *	@name stocktransfer
     *  @desc validate a TIN number
     *	@return string response
     *	@param NULL
     **/
    function stocktransfer(){
        $operation = NULL; //tblevents
        $permission = 'TRANSFERPRODUCTSTOCK'; //tblpermissions
        $event = NULL; //tblevents
        $eventnotification = NULL; //tbleventnotifications
        
        if ($this->errorcode) {
            $this->message = $this->errormessage;
            $this->code = $this->errorcode;
            
            $body = $this->code . ' : ' . $this->message;
            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
        } else {
            if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
                date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
            }
            
            $xml = simplexml_load_string($this->xml);
            
            $ProductCode = trim($xml->PRODUCTCODE);//KINOK-123
            $sourcebranch = trim($xml->SOURCEBRANCH);
            $destbranch = trim($xml->DESTBRANCH);
            $qty = trim($xml->QTY);
            $remarks = trim($xml->REMARKS);
            
            $this->logger->write("Api : stocktransfer() : The userid is: " . $this->userid_u, 'r');
            
            $this->logger->write("Api : stocktransfer() : Checking permissions", 'r');
            if ($this->userpermissions[$permission]) {
                
                $pdct = new products($this->db);
                $pdct->getByErpCode(trim($ProductCode));
                
                $product = array(
                    'uraproductidentifier' => $pdct['uraproductidentifier'],
                    'name' => $pdct['name'],
                    'code' => trim($ProductCode),
                    'measureunit' => $pdct['measureunit']
                );
                
                $data = $this->util->transferproductstock($this->userid_u, $product, $sourcebranch, $destbranch, $qty, $remarks);//will return JSON.
                $data = json_decode($data, true);
                
                if(isset($data['returnCode'])){
                    $this->logger->write("Api : stocktransfer() : The operation to transfer stock was not successful. The error message is " . $data['returnMessage'], 'r');
                    $this->message = $data['returnMessage'];
                    $this->code = $data['returnCode'];
                    
                    $body = $this->code . ' : ' . $this->message;
                    $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                } else {
                    if ($data) {
                        foreach($data as $elem){
                            $this->message = $elem['returnMessage'];
                            $this->code = $elem['returnCode'];
                        }
                    } else {
                        $this->message = "The operation was successful";
                        $this->code = '00';
                        $this->util->logstocktransfer($this->userid_u, $ProductCode, $qty, NULL, NULL, NULL, NULL, $remarks, $sourcebranch, $destbranch, '101', $pdct['uraproductidentifier']);
                    }
                    
                }
                
            } else {
                $this->logger->write("Api : stocktransfer() : The user is not allowed to perform this function", 'r');
                $this->message = "The user is not allowed to perform this function";
                $this->code = '0099';
            }
                      
            $activity = 'TRANSFERPRODUCTSTOCK: ' . $ProductCode . ': ' . $this->message;
            $windowsuser = trim($xml->WINDOWSUSER);
            $ipaddress = trim($xml->IPADDRESS);
            $macaddress = trim($xml->MACADDRESS);
            $systemname = trim($xml->SYSTEMNAME);
            $this->util->createerpauditlog($this->userid_u, $activity, $windowsuser, $ipaddress, $macaddress, $systemname, $xml);
        }
        
        
        // prepare xml response
        $this->response  =  "<ENVELOPE>";
        $this->response .=  "<HEADER>";
        $this->response .=  "<VERSION>1</VERSION>";
        $this->response .=  "<STATUS>1</STATUS>";
        $this->response .=  "</HEADER>";
        $this->response .=  "<BODY>";
        $this->response .=  "<DATA>";
        $this->response .=  "<RESPONSE>";
        $this->response .=  "<RETURNCODE>" . htmlspecialchars($this->code) . "</RETURNCODE>";
        $this->response .=  "<RETURNMESSAGE>" . htmlspecialchars($this->message) . "</RETURNMESSAGE>";
        $this->response .=  "</RESPONSE>";
        $this->response .=  "</DATA>";
        $this->response .=  "</BODY>";
        $this->response .=  "</ENVELOPE>";
        
        $len = strlen($this->response);
        header ("CONTENT-LENGTH:".$len);
        print $this->response;
        return;
        
    }
    
    
    /**
     *	@name uploadinvoice
     *  @desc upload invoice
     *	@return string response
     *	@param NULL
     **/
    function uploadinvoice(){
        $operation = NULL; //tblevents
        $permission = 'UPLOADINVOICE'; //tblpermissions
        $event = NULL; //tblevents
        $eventnotification = NULL; //tbleventnotifications
        
        if ($this->errorcode) {
            $this->message = $this->errormessage;
            $this->code = $this->errorcode;
            
            $body = $this->code . ' : ' . $this->message;
            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
        } else {
            $invoiceid = '';
            $invoicenumber = '';
            $issueddate = '';
            $fdn = '';
            $qr = '';
            
            if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
                date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
            }
            
            
            /**
             *
             * Steps to follow when uploading to EFRIS
             * 0. Grab the xml body from the ERP
             * 1. Generate a groupid for the following;
             * 1.1 Goods
             * 1.2 Taxes
             * 1.3 Payments
             * 2. Create the following arrays
             * 2.1 buyer
             * 2.2 invoicedetails
             * 2.3 goods
             * 2.4 payments
             * 2.5 taxes
             * 3. Send the items in [1] to the uploadinvoice API
             */
            
            $xml = simplexml_load_string($this->xml);
            
            $tcsdetails = new tcsdetails($this->db);
            $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
            
            $companydetails = new organisations($this->db);
            $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
            
            $devicedetails = new devices($this->db);
            $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
            
            $vchtype = trim($xml->VOUCHERTYPE);
            $vchtypename = trim($xml->VOUCHERTYPENAME);
            $vchnumber = trim($xml->VOUCHERNUMBER);
            $vchref = trim($xml->VOUCHERREF);
            
            $this->logger->write("Api : uploadinvoice() : The userid is: " . $this->userid_u, 'r');
            $this->logger->write("Api : uploadinvoice() : Checking permissions", 'r');
            if ($this->userpermissions[$permission]) {
                $branch = new branches($this->db);
                $branch->getByID($this->userbranch_u);
                
                
                
                /**
                 * Check if Inv is already issued
                 *
                 */
                
                
                
                $inv_check = new DB\SQL\Mapper($this->db, 'tblinvoices');
                $inv_check->load(array('TRIM(erpinvoiceid)=? AND TRIM(vouchertype)=? AND TRIM(vouchertypename)=?', $vchnumber, $vchtype, $vchtypename));
                $this->logger->write($this->db->log(TRUE), 'r');
                
                if($inv_check->dry ()){
                    $this->logger->write("Api : uploadinvoice() : The invoice does not exist on eTW. Proceed and upload", 'r');
                    $this->logger->write("Api : uploadinvoice() : The voucher narration is: " . trim($xml->VOUCHERNARRATION), 'r');
                    
                    $remarks = '';
                    
                    if (isset($xml->REASONS->REASON)) {
                        foreach ($xml->REASONS->REASON as $obj){
                            $remarks = trim($obj->REMARKS);
                        }
                    }
                    
                    
                    $this->logger->write("Api : uploadinvoice() : The voucher remarks is: " . $remarks, 'r');
                    
                    $invoicedetails = array(
                        'gooddetailgroupid' => NULL,
                        'taxdetailgroupid' => NULL,
                        'paymentdetailgroupid' => NULL,
                        'erpinvoiceid' => trim($xml->VOUCHERNUMBER),
                        'erpinvoiceno' => trim($xml->VOUCHERREF),
                        'antifakecode' => NULL,
                        'deviceno' => trim($devicedetails->deviceno),
                        'issueddate' => NULL,
                        'issuedtime' => NULL,
                        'operator' => trim($xml->ERPUSER),
                        'currency' => $this->util->getcurrency(trim($xml->CURRENCY)),
                        'oriinvoiceid' => NULL,
                        'invoicetype' => "1",
                        'invoicekind' => ($this->vatRegistered == 'Y')? "1" : "2",
                        'datasource' => $this->appsettings['WESERVICEDS'],
                        'invoiceindustrycode' => $this->util->mapindustrycode(trim($xml->INDUSTRYCODE)),
                        'einvoiceid' => NULL,
                        'einvoicenumber' => NULL,
                        'einvoicedatamatrixcode' => NULL,
                        'isbatch' => '0',
                        'netamount' => NULL,
                        'taxamount' => NULL,
                        'grossamount' => NULL,
                        'origrossamount' => NULL,
                        'itemcount' => NULL,
                        'modecode' => '1',
                        'modename' => NULL,
                        'remarks' => $remarks,
                        'buyerid' => NULL,
                        'sellerid' => $this->appsettings['SELLER_RECORD_ID'],
                        'issueddatepdf' => NULL,
                        'grossamountword' => NULL,
                        'isinvalid' => 0,
                        'isrefund' => 0,
                        'vchtype' => trim($xml->VOUCHERTYPE),
                        'vchtypename' => trim($xml->VOUCHERTYPENAME)
                    );
                    
                    $buyer = array(
                        'tin' => trim($xml->BUYERTIN),
                        'ninbrn' => trim($xml->BUYERNINBRN),
                        'PassportNum' => trim($xml->BUYERPASSPORTNUM),
                        'legalname' => trim($xml->BUYERLEGALNAME),
                        'businessname' => trim($xml->BUSINESSNAME),
                        'address' => trim($xml->BUYERADDRESS),
                        'mobilephone' => trim($xml->MOBILEPHONE),
                        'linephone' => trim($xml->BUYERLINEPHONE),
                        'emailaddress' => trim($xml->BUYEREMAIL),
                        'placeofbusiness' => trim($xml->BUYERPLACEOFBUSI),
                        'type' => $this->util->mapbuyertypecode(trim($xml->BUYERTYPE)),
                        'citizineship' => trim($xml->BUYERCITIZENSHIP),
                        'sector' => trim($xml->BUYERSECTOR),
                        'referenceno' => trim($xml->VOUCHERNUMBER) == ''? trim($xml->BUYERREFERENCENO) : trim($xml->VOUCHERREF) . trim($xml->VOUCHERNUMBER),
                        'datasource' => $this->appsettings['WESERVICEDS']
                    );
                    
                    $this->logger->write("Api : uploadinvoice() : The BUYERTYPE are " . trim($xml->BUYERTYPE), 'r');
                    //$this->logger->write("Api : uploadinvoice() : The INVENTORIES are " . trim($xml->INVENTORIES), 'r');
                    
                    $goods = array();
                    $taxes = array();
                    $payments = array();
                    
                    /**
                     * Author: francis.lubanga@gmail.com
                     * Date: 2023-02-28
                     * Description: Resolve EFRIS error code 2821 : buyerDetails-->buyerTin: you don't have permission to issue deemed invoices!
                     *
                     *
                     * 1. Take the deemed flag from the payload from the client
                     */
                    //$deemedflag = 'NO';
                    $deemedflag = trim($xml->DEEMEDFLAG) == ''? 'NO' : strtoupper(trim($xml->DEEMEDFLAG));
                                        
                    //$vatapplicationlevel = trim($xml->VATAPPLICATIONLEVEL);//Ledger
                    $pricevatinclusive = trim($xml->PRICEVATINCLUSIVE);//No
                    //$defaultvatrate = trim($xml->DEFAULTVATRATE);//18
                    $buyertype = $this->util->mapbuyertypecode(trim($xml->BUYERTYPE));
                    $industrycode = $this->util->mapindustrycode(trim($xml->INDUSTRYCODE));
                    
                    $netamount = 0;
                    $taxamount = 0;
                    $grossamount = 0;
                    $itemcount = 0;
                    
                    /**
                     * Author: francis.lubanga@gmail.com
                     * Date: 2024-05-20
                     * Description: Handle fees/taxes which have no rates, and are passed as invoice line items
                     */
                    $mapped_fees = array();
                    
                    if(trim($this->appsettings['CHECK_FEE_MAP_FLAG']) == '1'){
                        $this->logger->write("Utilities : uploadinvoice() : The check fees mapping is on", 'r');
                        
                        $feesmapping = new feesmapping($this->db);
                        $feesmappings = $feesmapping->all();
                        
                        foreach ($feesmappings as $f_obj) {
                            $this->logger->write("Utilities : uploadinvoice() : The fee code is " . $f_obj['feecode'], 'r');
                            $this->logger->write("Utilities : uploadinvoice() : The product code is " . $f_obj['productcode'], 'r');
                            
                            $mapped_fees[] = array(
                                'id' => empty($f_obj['id'])? '' : $f_obj['id'],
                                'feecode' => empty($f_obj['feecode'])? '' : $f_obj['feecode'],
                                'productcode' => empty($f_obj['productcode'])? '' : $f_obj['productcode'],
                                'amount' => 0
                            );
                        }
                    }
                    
                    if (isset($xml->INVENTORIES->INVENTORY)) {
                        $this->logger->write("Api : uploadinvoice() : This is an inventory/goods invoice", 'r');
                        
                        foreach ($xml->INVENTORIES->INVENTORY as $obj){
                            $this->logger->write("Api : uploadinvoice() : The STOCKITEMNAME is " . $obj->STOCKITEMNAME, 'r');
                            $this->logger->write("Api : uploadinvoice() : The RATE is " . $obj->RATE, 'r');
                            
                            $ii = 0;
                            $product_skip_flag = 0;
                            
                            foreach ($mapped_fees as $m_obj) {
                                if(trim($m_obj['productcode']) == trim($obj->PRODUCTCODE)){
                                    $this->logger->write("Api : uploadinvoice() : The product " . $obj->PRODUCTCODE . " is mapped to a tax/fee " . $m_obj['feecode'], 'r');
                                    
                                    $f_qty = $this->util->removeunitsfromqty(trim($obj->BILLEDQTY));
                                    $f_unit = $this->util->removeunitsfromrate(trim($obj->RATE));
                                    
                                    $mapped_fees[$ii]['amount'] = ($f_qty * $f_unit); # Might be problematic. Consider "foreach ($mapped_fees as &$m_obj) {"
                                    $product_skip_flag = 1;
                                    break;
                                }
                                
                                $ii = $ii + 1;
                            }
                            
                            if ($product_skip_flag == 1){
                                continue;
                            }
                            
                            
                            /**
                             * 1. Get the following variables
                             * 1.1 Deemed Flag (Yes, No)
                             * 1.2 Industry (General, Export, Import, Imported Service)
                             * 1.3 Buyer Type (B2G/B2B, B2C, Foreigner)
                             * 2. Choose the tax rates based on the follwowing criteria
                             * 2.1 If Deemed Flag = No & Industry = General & Buyer Type = B2C or B2G/B2B then use the standard rate
                             * */
                            
                            
                            $this->logger->write("Api : uploadproduct() : The PRICE INCLUSIVE FLAG is " . strtoupper(trim($pricevatinclusive)), 'r');
                            
                            /********************************START CALCULATIONS HERE******************************************/
                            
                            
                            /**
                             * Date: 2025-05-24
                             * Author: Francis Lubanga <francis.lubanga@gmail.com>
                             * Description: Hard code the tax rate for product code MZG001. This is a fix for THL
                             * 
                             */
                            if (trim($obj->PRODUCTCODE) == 'MZG001'){
                                $taxid = '3';
                            } else {
                                $taxid = $this->util->getinvoicetaxrate_v2($industrycode, $buyertype, trim($obj->PRODUCTCODE), trim($xml->BUYERTIN), $this->appsettings['OVERRIDE_TAXRATE_FLAG'], $this->appsettings['TAXPAYER_CHECK_FLAG']);
                            }
                            
                            $this->logger->write("Api : uploadinvoice() : The computed TAXID is " . $taxid, 'r');
                            
                            if (!$taxid) {
                                $taxid = $this->appsettings['STANDARDTAXRATE'];
                            }
                            
  
                            /**
                             * Author: francis.lubanga@gmail.com
                             * Date: 2023-02-28
                             * Description: Resolve EFRIS error code 2821 : buyerDetails-->buyerTin: you don't have permission to issue deemed invoices!
                             *
                             *
                             * 1. If the deemed flag received from the ERP is YES, then override the taxid by setting it to the deemed tax rate
                             */
                            
                            /*if ($taxid == $this->appsettings['DEEMEDTAXRATE']) {
                                $deemedflag = 'YES';
                            } else {
                                $deemedflag = 'NO';
                            }*/
                            
                            $this->logger->write("Api : uploadinvoice() : The DEEMED FLAG is " . $deemedflag, 'r');
                            
                            if ($deemedflag == 'YES') {
                                $taxid = $this->appsettings['DEEMEDTAXRATE'];
                            } 
                            
                            $this->logger->write("Api : uploadinvoice() : The final TAXID is " . $taxid, 'r');
                            
                            $tr = new taxrates($this->db);
                            $tr->getByID($taxid);
                            $taxcode = $tr->code;
                            $taxname = $tr->name;
                            $taxcategory = $tr->category;
                            $taxdisplaycategory = $tr->displayCategoryCode;
                            $taxdescription = $tr->description;
                            $rate = $tr->rate? $tr->rate : 0;
                            $qty = $this->util->removeunitsfromqty(trim($obj->BILLEDQTY));
                            $unit = $this->util->removeunitsfromrate(trim($obj->RATE));
                            $discountpct = empty(trim($obj->DISCOUNT))? 0 : (float)trim($obj->DISCOUNT);
                            
                            if (strtoupper(trim($pricevatinclusive)) == 'YES') {
                                //Use the figures as they come from the ERP
                                $total = ($qty * $unit);//??
                                
                                $discount = ($discountpct/100) * $total;
                                
                                /**
                                 * Modification Date: 2021-01-26
                                 * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                                 * */
                                //$gross = $total - $discount;
                                $gross = $total;
                                
                                $discount = (-1) * $discount;
                                
                                $tax = ($gross/($rate + 1)) * $rate; //??
                                
                                $net = $gross - $tax;
                            } elseif (strtoupper(trim($pricevatinclusive)) == 'NO') {
                                //Manually calculate figures
                                $this->logger->write("Api : uploadinvoice() : Manually calculating tax", 'r');
                                
                                if ($rate > 0) {
                                    $unit = $unit * ($rate + 1);
                                }
                                
                                $total = ($qty * $unit);//??
                                
                                $discount = ($discountpct/100) * $total;
                                
                                /**
                                 * Modification Date: 2021-01-26
                                 * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                                 * */
                                //$gross = $total - $discount;
                                $gross = $total;
                                
                                $discount = (-1) * $discount;
                                
                                $tax = ($gross/($rate + 1)) * $rate; //??
                                
                                $net = $gross - $tax;
                            }
                            
                            /********************************END CALCULATIONS HERE******************************************/
                            
                            /**
                             * Over-ride tax, if the tax payer is not VAT registered
                             */
                            if ($this->vatRegistered == 'N') {
                                $tax = 0;
                                $taxcategory = NULL;
                                $taxcode = NULL;
                            }
                            
                            
                            
                            $netamount = $netamount + $net;
                            $taxamount = $taxamount + $tax;
                            
                            $grossamount = $grossamount + $gross;
                            $itemcount = $itemcount + 1;
                            
                            /**
                             * Replace the INV currency with the product currency
                             */
                            
                            $invoicedetails['currency'] = empty(trim($obj->STOCKITEMCURRENCY))? $this->util->getcurrency(trim($this->appsettings['ERPBASECURRENCY'])) : $this->util->getcurrency(trim($obj->STOCKITEMCURRENCY));
                            
                            $goods[] = array(
                                'groupid' => NULL,
                                'item' => trim($obj->STOCKITEMNAME),
                                'itemcode' => trim($obj->PRODUCTCODE),
                                'qty' => $qty,
                                'unitofmeasure' => $this->util->mapmeasureunit(trim($obj->BUOM)),
                                'unitprice' => $unit,
                                'total' => $total,
                                'taxid' => $taxid,
                                'taxrate' => $rate,
                                'tax' => $tax,
                                'discounttotal' => $discount,
                                'discounttaxrate' => $rate,
                                'discountpercentage' => $discountpct,
                                'ordernumber' => NULL,
                                'discountflag' => empty(trim($obj->DISCOUNT))? '2' : '1',
                                'deemedflag' => (strtoupper(trim($deemedflag)) == 'NO'? '2' : '1'),
                                'exciseflag' => NULL,
                                'categoryid' => NULL,
                                'categoryname' => NULL,
                                'goodscategoryid' => $this->util->mapcommodity(trim($obj->COMMODITYCATEGORYCODE)),
                                'goodscategoryname' => trim($obj->COMMODITYCATEGORYCODE),
                                'exciserate' => NULL,
                                'exciserule' => NULL,
                                'excisetax' => NULL,
                                'pack' => NULL,
                                'stick' => NULL,
                                'exciseunit' => NULL,
                                'excisecurrency' => NULL,
                                'exciseratename' => NULL,
                                'taxdisplaycategory' => $taxdisplaycategory,
                                'taxcategory' => $taxcategory,
                                'taxcategoryCode' => $taxcode,
                                'unitofmeasurename' => trim($obj->BUOM),
                                'vatProjectId' => trim($xml->PROJECTID),
                                'vatProjectName' => trim($xml->PROJECTNAME)
                            );
                            
                            $this->logger->write("Api : uploadinvoice() : The TAXCODE is " . $taxcode, 'r');
                            
                            
                            if ($this->vatRegistered == 'Y') {
                                $taxes[] = array(
                                    'discountflag' => empty(trim($obj->DISCOUNT))? '2' : '1',
                                    'discounttotal' => $discount,
                                    'discounttaxrate' => $rate,
                                    'discountpercentage' => $discountpct,
                                    'd_netamount' => NULL,
                                    'd_taxamount' => NULL,
                                    'd_grossamount' => NULL,
                                    'groupid' => NULL,
                                    'goodid' => NULL,
                                    'taxdisplaycategory' => $taxdisplaycategory,
                                    'taxcategory' => $taxcategory,
                                    'taxcategoryCode' => $taxcode,
                                    'netamount' => $net,
                                    'taxrate' => $rate,
                                    'taxamount' => $tax,
                                    'grossamount' => $gross,
                                    'exciseunit' => NULL,
                                    'excisecurrency' => NULL,
                                    'taxratename' => $taxname,
                                    'taxdescription' => $taxdescription
                                );
                            }
                            
                        }
                        
                        
                        /*$payments[] = array(
                         'groupid' => NULL,
                         'paymentmode' => NULL,
                         'paymentmodename' => NULL,
                         'paymentamount' => 0,
                         'ordernumber' => NULL
                         );*/
                    }
                    
                    if (isset($xml->SERVICES->SERVICE)) {
                        $this->logger->write("Api : uploadinvoice() : This is an service invoice", 'r');
                        
                        foreach ($xml->SERVICES->SERVICE as $obj){
                            $this->logger->write("Api : uploadinvoice() : The STOCKITEMNAME is " . $obj->STOCKITEMNAME, 'r');
                            $this->logger->write("Api : uploadinvoice() : The RATE is " . $obj->RATE, 'r');
                            
                            $ii = 0;
                            $product_skip_flag = 0;
                            
                            foreach ($mapped_fees as $m_obj) {
                                if(trim($m_obj['productcode']) == trim($obj->PRODUCTCODE)){
                                    $this->logger->write("Api : uploadinvoice() : The product " . $obj->PRODUCTCODE . " is mapped to a tax/fee " . $m_obj['feecode'], 'r');
                                    
                                    $f_qty = $this->util->removeunitsfromqty(trim($obj->BILLEDQTY));
                                    $f_unit = $this->util->removeunitsfromrate(trim($obj->RATE));
                                    
                                    $mapped_fees[$ii]['amount'] = ($f_qty * $f_unit); # Might be problematic. Consider "foreach ($mapped_fees as &$m_obj) {"
                                    $product_skip_flag = 1;
                                    break;
                                }
                                
                                $ii = $ii + 1;
                            }
                            
                            if ($product_skip_flag == 1){
                                continue;
                            }
                            
                            /**
                             * 1. Get the following variables
                             * 1.1 Deemed Flag (Yes, No)
                             * 1.2 Industry (General, Export, Import, Imported Service)
                             * 1.3 Buyer Type (B2G/B2B, B2C, Foreigner)
                             * 2. Choose the tax rates based on the follwowing criteria
                             * 2.1 If Deemed Flag = No & Industry = General & Buyer Type = B2C or B2G/B2B then use the standard rate
                             * */
                            
                            
                            $this->logger->write("Api : uploadproduct() : The PRICE INCLUSIVE FLAG is " . strtoupper(trim($pricevatinclusive)), 'r');
                            
                            /********************************START CALCULATIONS HERE******************************************/
                            $taxid = $this->util->getinvoicetaxrate_v2($industrycode, $buyertype, trim($obj->PRODUCTCODE), trim($xml->BUYERTIN), $this->appsettings['OVERRIDE_TAXRATE_FLAG'], $this->appsettings['TAXPAYER_CHECK_FLAG']);
                            $this->logger->write("Api : uploadinvoice() : The computed TAXID is " . $taxid, 'r');
                            
                            if (!$taxid) {
                                $taxid = $this->appsettings['STANDARDTAXRATE'];
                            }
                            
                            
                            /**
                             * Author: francis.lubanga@gmail.com
                             * Date: 2023-02-28
                             * Description: Resolve EFRIS error code 2821 : buyerDetails-->buyerTin: you don't have permission to issue deemed invoices!
                             *
                             *
                             * 1. If the deemed flag received from the ERP is YES, then override the taxid by setting it to the deemed tax rate
                             */
                            
                            /*if ($taxid == $this->appsettings['DEEMEDTAXRATE']) {
                             $deemedflag = 'YES';
                             } else {
                             $deemedflag = 'NO';
                             }*/
                            
                            $this->logger->write("Api : uploadinvoice() : The DEEMED FLAG is " . $deemedflag, 'r');
                            
                            if ($deemedflag == 'YES') {
                                $taxid = $this->appsettings['DEEMEDTAXRATE'];
                            } 
                            
                            $this->logger->write("Api : uploadinvoice() : The final TAXID is " . $taxid, 'r');
                            
                            $tr = new taxrates($this->db);
                            $tr->getByID($taxid);
                            $taxcode = $tr->code;
                            $taxname = $tr->name;
                            $taxcategory = $tr->category;
                            $taxdescription = $tr->description;
                            $rate = $tr->rate? $tr->rate : 0;
                            $qty = $this->util->removeunitsfromqty(trim($obj->BILLEDQTY));
                            $unit = $this->util->removecommasfromamount(trim($obj->RATE));
                            $discountpct = empty(trim($obj->DISCOUNT))? 0 : (float)trim($obj->DISCOUNT);
                            
                            if (strtoupper(trim($pricevatinclusive)) == 'YES') {
                                //Use the figures are they come from the ERP
                                $total = ($qty * $unit);//??
                                
                                $discount = ($discountpct/100) * $total;
                                
                                /**
                                 * Modification Date: 2021-01-26
                                 * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                                 * */
                                //$gross = $total - $discount;
                                $gross = $total;
                                
                                $discount = (-1) * $discount;
                                
                                $tax = ($gross/($rate + 1)) * $rate; //??
                                
                                $net = $gross - $tax;
                            } elseif (strtoupper(trim($pricevatinclusive)) == 'NO') {
                                //Manually calculate figures
                                $this->logger->write("Api : uploadinvoice() : Manually calculating tax", 'r');
                                
                                if ($rate > 0) {
                                    $unit = $unit * ($rate + 1);
                                }
                                
                                $total = ($qty * $unit);//??
                                
                                $discount = ($discountpct/100) * $total;
                                
                                /**
                                 * Modification Date: 2021-01-26
                                 * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                                 * */
                                //$gross = $total - $discount;
                                $gross = $total;
                                
                                $discount = (-1) * $discount;
                                
                                $tax = ($gross/($rate + 1)) * $rate; //??
                                
                                $net = $gross - $tax;
                            }
                            
                            /********************************END CALCULATIONS HERE******************************************/
                            
                            /**
                             * Over-ride tax, if the tax payer is not VAT registered
                             */
                            if ($this->vatRegistered == 'N') {
                                $tax = 0;
                                $taxcategory = NULL;
                                $taxcode = NULL;
                            }
                            
                            $netamount = $netamount + $net;
                            $taxamount = $taxamount + $tax;
                            
                            $grossamount = $grossamount + $gross;
                            $itemcount = $itemcount + 1;
                            
                            /**
                             * Replace the INV currency with the product currency
                             */
                            $invoicedetails['currency'] = empty(trim($obj->STOCKITEMCURRENCY))? $this->util->getcurrency(trim($this->appsettings['ERPBASECURRENCY'])) : $this->util->getcurrency(trim($obj->STOCKITEMCURRENCY));
                            
                            $goods[] = array(
                                'groupid' => NULL,
                                'item' => trim($obj->STOCKITEMNAME),
                                'itemcode' => trim($obj->PRODUCTCODE),
                                'qty' => $qty,
                                'unitofmeasure' => $this->util->mapmeasureunit(trim($obj->BUOM)),
                                'unitprice' => $unit,
                                'total' => $total,
                                'taxid' => $taxid,
                                'taxrate' => $rate,
                                'tax' => $tax,
                                'discounttotal' => $discount,
                                'discounttaxrate' => $rate,
                                'discountpercentage' => $discountpct,
                                'ordernumber' => NULL,
                                'discountflag' => empty(trim($obj->DISCOUNT))? '2' : '1',
                                'deemedflag' => (strtoupper(trim($deemedflag)) == 'NO'? '2' : '1'),
                                'exciseflag' => NULL,
                                'categoryid' => NULL,
                                'categoryname' => NULL,
                                'goodscategoryid' => $this->util->mapcommodity(trim($obj->COMMODITYCATEGORYCODE)),
                                'goodscategoryname' => trim($obj->COMMODITYCATEGORYCODE),
                                'exciserate' => NULL,
                                'exciserule' => NULL,
                                'excisetax' => NULL,
                                'pack' => NULL,
                                'stick' => NULL,
                                'exciseunit' => NULL,
                                'excisecurrency' => NULL,
                                'exciseratename' => NULL,
                                'taxdisplaycategory' => $taxdisplaycategory,
                                'taxcategory' => $taxcategory,
                                'taxcategoryCode' => $taxcode,
                                'unitofmeasurename' => trim($obj->BUOM),
                                'vatProjectId' => trim($xml->PROJECTID),
                                'vatProjectName' => trim($xml->PROJECTNAME)
                            );
                            
                            if ($this->vatRegistered == 'Y'){
                                $taxes[] = array(
                                    'discountflag' => empty(trim($obj->DISCOUNT))? '2' : '1',
                                    'discounttotal' => $discount,
                                    'discounttaxrate' => $rate,
                                    'discountpercentage' => $discountpct,
                                    'd_netamount' => NULL,
                                    'd_taxamount' => NULL,
                                    'd_grossamount' => NULL,
                                    'groupid' => NULL,
                                    'goodid' => NULL,
                                    'taxdisplaycategory' => $taxdisplaycategory,
                                    'taxcategory' => $taxcategory,
                                    'taxcategoryCode' => $taxcode,
                                    'netamount' => $net,
                                    'taxrate' => $rate,
                                    'taxamount' => $tax,
                                    'grossamount' => $gross,
                                    'exciseunit' => NULL,
                                    'excisecurrency' => NULL,
                                    'taxratename' => $taxname,
                                    'taxdescription' => $taxdescription
                                );
                            }
                            
                            
                        }
                        
                        
                        /*$payments[] = array(
                         'groupid' => NULL,
                         'paymentmode' => NULL,
                         'paymentmodename' => NULL,
                         'paymentamount' => 0,
                         'ordernumber' => NULL
                         );*/
                    }
                    
                    foreach ($mapped_fees as $m_obj) {
                        $this->logger->write("Api : uploadinvoice() : Adding fees to the tax array", 'r');
                        
                        $tr = new taxrates($this->db);
                        $tr->getByCode($m_obj['feecode']);
                        $taxcode = $tr->code;
                        $taxname = $tr->name;
                        $taxcategory = $tr->category;
                        $taxdescription = $tr->description;
                        $taxdisplaycategory = $tr->displayCategoryCode;
                        
                        $taxes[] = array(
                            'discountflag' => '1',
                            'discounttotal' => NULL,
                            'discounttaxrate' => NULL,
                            'discountpercentage' => NULL,
                            'd_netamount' => NULL,
                            'd_taxamount' => NULL,
                            'd_grossamount' => NULL,
                            'groupid' => NULL,
                            'goodid' => NULL,
                            'taxdisplaycategory' => $taxdisplaycategory,
                            'taxcategory' => $taxcategory,
                            'taxcategoryCode' => $taxcode,
                            'netamount' => 0,
                            'taxrate' => $m_obj['amount'],
                            'taxamount' => $m_obj['amount'],
                            'grossamount' => $m_obj['amount'],
                            'exciseunit' => NULL,
                            'excisecurrency' => NULL,
                            'taxratename' => $taxname,
                            'taxdescription' => $taxdescription
                        );
                    }
                    
                    
                    $this->logger->write("Api : uploadinvoice() : The GOODS count: " . sizeof($goods), 'r');
                    $this->logger->write("Api : uploadinvoice() : The TAX count: " . sizeof($taxes), 'r');
                    $this->logger->write("Api : uploadinvoice() : The PAYMENTS count: " . sizeof($payments), 'r');
                    
                    $data = $this->util->uploadinvoice($this->userid_u, $branch->uraid, $buyer, $invoicedetails, $goods, $payments, $taxes);
                    $data = json_decode($data, true);
                    
                    if (isset($data['returnCode'])){
                        $this->logger->write("Api : uploadinvoice() : The operation to upload the invoice not successful. The error message is " . $data['returnMessage'], 'r');
                        //$this->util->createinappnotification(NULL, NULL, NULL, self::$module, self::$submodule, $operation, $event, $eventnotification, NULL, $this->f3->get('SESSION.id'), "The operation to upload the invoice by " . $this->f3->get('SESSION.username') . " was not successful");
                        $this->message = $data['returnMessage'];
                        $this->code = $data['returnCode'];
                        
                        
                        /**
                         * If the invoice passed the duplicate check due to an error or incomplete parameters, but EFRIS determines that it is a duplicate invoice,
                         * We insert it into the database?
                         */
                        
                        
                        $body = $this->code . ' : ' . $this->message;
                        $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                    } else {
                        if (isset($data['basicInformation'])){
                            $antifakeCode = $data['basicInformation']['antifakeCode']; //32966911991799104051
                            $invoiceId = $data['basicInformation']['invoiceId']; //3257429764295992735
                            $invoiceNo = $data['basicInformation']['invoiceNo']; //3120012276043
                            
                            $issuedDate = $data['basicInformation']['issuedDate']; //18/09/2020 17:14:12
                            $issuedDate = str_replace('/', '-', $issuedDate);//Replace / with -
                            $issuedDate = date("Y-m-d H:i:s", strtotime($issuedDate));
                            
                            $issuedTime = $data['basicInformation']['issuedDate']; //18/09/2020 17:14:12
                            $issuedTime = str_replace('/', '-', $issuedTime);//Replace / with -
                            $issuedTime = date("Y-m-d H:i:s", strtotime($issuedTime));
                            
                            $issuedDatePdf = $data['basicInformation']['issuedDatePdf']; //318/09/2020 17:14:12
                            $issuedDatePdf = str_replace('/', '-', $issuedDatePdf);//Replace / with -
                            $issuedDatePdf = date("Y-m-d H:i:s", strtotime($issuedDatePdf));
                            
                            $oriInvoiceId = $data['basicInformation']['oriInvoiceId'];//1
                            $isInvalid = $data['basicInformation']['isInvalid'];//1
                            $isRefund = $data['basicInformation']['isRefund'];//1
                            $currencyRate = $data['basicInformation']['currencyRate'];
                            
                            $invoiceid = $invoiceId;
                            $invoicenumber = $invoiceNo;
                            $issueddate = $issuedDate;
                            $fdn = $antifakeCode; 
                        }
                        
                        if (isset($data['summary'])){
                            $grossAmount = $data['summary']['grossAmount']; //832000
                            $itemCount = $data['summary']['itemCount']; //1
                            $netAmount = $data['summary']['netAmount']; //705084.75
                            $qrCode = $data['summary']['qrCode']; //020000001149IC1200122760430004F588000000C1A8450A20A021D534A1000121462A1000094968~MM INTERGRATED STEEL MILLS (UGANDA) LIMITED~Ediomu & Company~Kiboko Galv Corr. Sheet 36G
                            $taxAmount = $data['summary']['taxAmount'];//126915.25
                            $modeCode = $data['summary']['modeCode'];//0
                            
                            $mode = new modes($this->db);
                            $mode->getByCode($modeCode);
                            $modeName = $mode->name;//online
                            
                            $f = new \NumberFormatter("en", NumberFormatter::SPELLOUT);
                            $grossAmountWords = $f->format($grossAmount);//two million,
                            
                            $qr = $qrCode;
                            
                            
                        }
                        
                        $invoicedetails['einvoicedatamatrixcode'] = $qrCode;
                        $invoicedetails['grossamountword'] = $grossAmountWords;
                        $invoicedetails['modename'] = $modeName;
                        $invoicedetails['modecode'] = $modeCode;
                        $invoicedetails['taxamount'] = $taxAmount;
                        $invoicedetails['netamount'] = $netAmount;
                        $invoicedetails['itemcount'] = $itemCount;
                        $invoicedetails['grossamount'] = $grossAmount;
                        $invoicedetails['antifakecode'] = $antifakeCode;
                        $invoicedetails['issueddate'] = $issuedDate;
                        $invoicedetails['einvoicenumber'] = $invoiceNo;
                        $invoicedetails['einvoiceid'] = $invoiceId;
                        $invoicedetails['isinvalid'] = $isRefund;
                        $invoicedetails['isrefund'] = $isInvalid;
                        $invoicedetails['oriinvoiceid'] = $oriInvoiceId;
                        $invoicedetails['issueddatepdf'] = $issuedDatePdf;
                        $invoicedetails['issuedtime'] = $issuedTime;
                        $invoicedetails['origrossamount'] = '0';
                        $invoicedetails['currencyRate'] = $currencyRate;
                        
                        $inv_status = $this->util->createinvoice($invoicedetails, $goods, $taxes, $buyer, $this->userid_u);
                        
                        if ($inv_status) {
                            $this->logger->write("Api : uploadinvoice() : The invoice was created on eTW successfully", 'r');
                        } else {
                            $this->logger->write("Api : uploadinvoice() : The invoice was NOT created on eTW", 'r');
                        }
                        
                        $this->message = 'The operation to upload the invoice was successful';
                        $this->code = '000';
                    }
                } else {
                    $this->message = 'The invoice has already been uploaded into EFRIS';
                    $this->code = '99';
                    
                    $invoiceid = $inv_check->einvoiceid;
                    $invoicenumber = $inv_check->einvoicenumber;
                    $issueddate = $inv_check->issuedtime;
                    $fdn = $inv_check->antifakecode;
                    $qr = $inv_check->einvoicedatamatrixcode;
                }
            } else {
                $this->logger->write("Api : uploadinvoice() : The user is not allowed to perform this function", 'r');
                $this->message = "The user is not allowed to perform this function";
                $this->code = '0099';
            }
            
            
            
            
            
            $activity = 'UPLOADINVOICE: ' . $vchnumber . ': ' . $vchref . ': ' . $this->message;
            $windowsuser = trim($xml->WINDOWSUSER);
            $ipaddress = trim($xml->IPADDRESS);
            $macaddress = trim($xml->MACADDRESS);
            $systemname = trim($xml->SYSTEMNAME);
            $this->util->createerpauditlog($this->userid_u, $activity, $windowsuser, $ipaddress, $macaddress, $systemname, $xml);
        }
        
        
        
        // prepare xml response
        $this->response  =  "<ENVELOPE>";
        $this->response .=  "<HEADER>";
        $this->response .=  "<VERSION>1</VERSION>";
        $this->response .=  "<STATUS>1</STATUS>";
        $this->response .=  "</HEADER>";
        $this->response .=  "<BODY>";
        $this->response .=  "<DATA>";
        $this->response .=  "<RESPONSE>";
        $this->response .=  "<RETURNCODE>" . htmlspecialchars($this->code) . "</RETURNCODE>";
        $this->response .=  "<RETURNMESSAGE>" . htmlspecialchars($this->message) . "</RETURNMESSAGE>";
        $this->response .=  "<INVID>". htmlspecialchars($invoiceid) ."</INVID>";
        $this->response .=  "<INVNO>". htmlspecialchars($invoicenumber) ."</INVNO>";
        $this->response .=  "<ISSUEDT>". htmlspecialchars($issueddate) ."</ISSUEDT>";
        $this->response .=  "<FDN>". htmlspecialchars($fdn) ."</FDN>";
        $this->response .=  "<QRCODE>". htmlspecialchars($qr) ."</QRCODE>";
        $this->response .=  "</RESPONSE>";
        $this->response .=  "</DATA>";
        $this->response .=  "</BODY>";
        $this->response .=  "</ENVELOPE>";
        
        $len = strlen($this->response);
        header ("CONTENT-LENGTH:".$len);
        print $this->response;
        return;
    }
    
    
    /**
     *	@name uploadcreditnote
     *  @desc upload creditnote
     *	@return string response
     *	@param NULL
     **/
    function uploadcreditnote(){
        $operation = NULL; //tblevents
        $permission = 'UPLOADCREDITNOTE'; //tblpermissions
        $event = NULL; //tblevents
        $eventnotification = NULL; //tbleventnotifications
        
        if ($this->errorcode) {
            $this->message = $this->errormessage;
            $this->code = $this->errorcode;
            
            $body = $this->code . ' : ' . $this->message;
            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
        } else {
            $creditnoteid = '';
            $creditnotenumber = '';
            $issueddate = '';
            $fdn = '';
            $qr = '';
            $ref = '';
            
            if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
                date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
            }
            
            
            /**
             *
             * Steps to follow when uploading to EFRIS
             * 0. Grab the xml body from the ERP
             * 1. Generate a groupid for the following;
             * 1.1 Goods
             * 1.2 Taxes
             * 1.3 Payments
             * 2. Create the following arrays
             * 2.1 buyer
             * 2.2 creditnotedetails
             * 2.3 goods
             * 2.4 payments
             * 2.5 taxes
             * 3. Send the items in [1] to the uploadcreditnote API
             */
            
            $xml = simplexml_load_string($this->xml);
            
            
            $tcsdetails = new tcsdetails($this->db);
            $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
            
            $companydetails = new organisations($this->db);
            $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
            
            $devicedetails = new devices($this->db);
            $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
            
            
            
            $vchtype = trim($xml->VOUCHERTYPE);
            $vchtypename = trim($xml->VOUCHERTYPENAME);
            $vchnumber = trim($xml->VOUCHERNUMBER);
            $vchref = trim($xml->VOUCHERREF);/*holds the original invoice #*/
            
            
            $windowsuser = trim($xml->WINDOWSUSER);
            $ipaddress = trim($xml->IPADDRESS);
            $macaddress = trim($xml->MACADDRESS);
            $systemname = trim($xml->SYSTEMNAME);
            
            $this->logger->write("Api : uploadcreditnote() : The userid is: " . $this->userid_u, 'r');
            $this->logger->write("Api : uploadcreditnote() : Checking permissions", 'r');
            if ($this->userpermissions[$permission]) {
                
                $this->logger->write("Api : uploadcreditnote() : The voucher type is: " . $vchtype, 'r');
                $this->logger->write("Api : uploadcreditnote() : The voucher type name is: " . $vchtypename, 'r');
                
                
                /**
                 * Author: francis.lubanga@gmail.com
                 * Date: 2023-05-15
                 * Description: Add control logic to handle credit notes & credit memo. These share the same base voucher type, but bahave differently (in EFRIS)
                 */
                
                if(strtoupper($vchtypename) == 'CREDIT MEMO'){
                    $branch = new branches($this->db);
                    $branch->getByID($this->userbranch_u);
                    
                    
                    
                    /**
                     * Check if C/M is already issued
                     *
                     */
                    
                    
                    
                    $inv_check = new DB\SQL\Mapper($this->db, 'tblcreditmemos');
                    $inv_check->load(array('TRIM(erpinvoiceid)=? AND TRIM(vouchertype)=? AND TRIM(vouchertypename)=?', $vchnumber, $vchtype, $vchtypename));
                    $this->logger->write($this->db->log(TRUE), 'r');
                    
                    if($inv_check->dry ()){
                        $this->logger->write("Api : uploadcreditnote() : The credit memo does not exist on eTW. Proceed and upload", 'r');
                        $this->logger->write("Api : uploadcreditnote() : The voucher narration is: " . trim($xml->VOUCHERNARRATION), 'r');
                        
                        $remarks = '';
                        
                        if (isset($xml->REASONS->REASON)) {
                            foreach ($xml->REASONS->REASON as $obj){
                                $remarks = trim($obj->REMARKS);
                            }
                        }
                        
                        
                        $this->logger->write("Api : uploadcreditnote() : The voucher remarks is: " . $remarks, 'r');
                        
                        $invoicedetails = array(
                            'gooddetailgroupid' => NULL,
                            'taxdetailgroupid' => NULL,
                            'paymentdetailgroupid' => NULL,
                            'erpinvoiceid' => trim($xml->VOUCHERNUMBER),
                            'erpinvoiceno' => trim($xml->VOUCHERREF),
                            'antifakecode' => NULL,
                            'deviceno' => trim($devicedetails->deviceno),
                            'issueddate' => NULL,
                            'issuedtime' => NULL,
                            'operator' => trim($xml->ERPUSER),
                            'currency' => $this->util->getcurrency(trim($xml->CURRENCY)),
                            'oriinvoiceid' => NULL,
                            'invoicetype' => "5",
                            'invoicekind' => ($this->vatRegistered == 'Y')? "1" : "2",
                            'datasource' => $this->appsettings['WESERVICEDS'],
                            'invoiceindustrycode' => $this->util->mapindustrycode(trim($xml->INDUSTRYCODE)),
                            'einvoiceid' => NULL,
                            'einvoicenumber' => NULL,
                            'einvoicedatamatrixcode' => NULL,
                            'isbatch' => '0',
                            'netamount' => NULL,
                            'taxamount' => NULL,
                            'grossamount' => NULL,
                            'origrossamount' => NULL,
                            'itemcount' => NULL,
                            'modecode' => '1',
                            'modename' => NULL,
                            'remarks' => $remarks,
                            'buyerid' => NULL,
                            'sellerid' => $this->appsettings['SELLER_RECORD_ID'],
                            'issueddatepdf' => NULL,
                            'grossamountword' => NULL,
                            'isinvalid' => 0,
                            'isrefund' => 0,
                            'vchtype' => trim($xml->VOUCHERTYPE),
                            'vchtypename' => trim($xml->VOUCHERTYPENAME)
                        );
                        
                        $buyer = array(
                            'tin' => trim($xml->BUYERTIN),
                            'ninbrn' => trim($xml->BUYERNINBRN),
                            'PassportNum' => trim($xml->BUYERPASSPORTNUM),
                            'legalname' => trim($xml->BUYERLEGALNAME),
                            'businessname' => trim($xml->BUSINESSNAME),
                            'address' => trim($xml->BUYERADDRESS),
                            'mobilephone' => trim($xml->MOBILEPHONE),
                            'linephone' => trim($xml->BUYERLINEPHONE),
                            'emailaddress' => trim($xml->BUYEREMAIL),
                            'placeofbusiness' => trim($xml->BUYERPLACEOFBUSI),
                            'type' => $this->util->mapbuyertypecode(trim($xml->BUYERTYPE)),
                            'citizineship' => trim($xml->BUYERCITIZENSHIP),
                            'sector' => trim($xml->BUYERSECTOR),
                            'referenceno' => trim($xml->VOUCHERNUMBER) == ''? trim($xml->BUYERREFERENCENO) : trim($xml->VOUCHERREF) . trim($xml->VOUCHERNUMBER),
                            'datasource' => $this->appsettings['WESERVICEDS']
                        );
                        
                        $this->logger->write("Api : uploadcreditnote() : The BUYERTYPE are " . trim($xml->BUYERTYPE), 'r');
                        //$this->logger->write("Api : uploadcreditnote() : The INVENTORIES are " . trim($xml->INVENTORIES), 'r');
                        
                        $goods = array();
                        $taxes = array();
                        $payments = array();
                        
                        /**
                         * Author: francis.lubanga@gmail.com
                         * Date: 2024-05-20
                         * Description: Handle fees/taxes which have no rates, and are passed as invoice line items
                         */
                        $mapped_fees = array();
                        
                        if(trim($this->appsettings['CHECK_FEE_MAP_FLAG']) == '1'){
                            $this->logger->write("Utilities : uploadinvoice() : The check fees mapping is on", 'r');
                            
                            $feesmapping = new feesmapping($this->db);
                            $feesmappings = $feesmapping->all();
                            
                            foreach ($feesmappings as $f_obj) {
                                $this->logger->write("Utilities : uploadinvoice() : The fee code is " . $f_obj['feecode'], 'r');
                                $this->logger->write("Utilities : uploadinvoice() : The product code is " . $f_obj['productcode'], 'r');
                                
                                $mapped_fees[] = array(
                                    'id' => empty($f_obj['id'])? '' : $f_obj['id'],
                                    'feecode' => empty($f_obj['feecode'])? '' : $f_obj['feecode'],
                                    'productcode' => empty($f_obj['productcode'])? '' : $f_obj['productcode'],
                                    'amount' => 0
                                );
                            }
                        }
                        
                        /**
                         * Author: francis.lubanga@gmail.com
                         * Date: 2023-02-28
                         * Description: Resolve EFRIS error code 2821 : buyerDetails-->buyerTin: you don't have permission to issue deemed invoices!
                         *
                         *
                         * 1. Take the deemed flag from the payload from the client
                         */
                        //$deemedflag = 'NO';
                        $deemedflag = trim($xml->DEEMEDFLAG) == ''? 'NO' : strtoupper(trim($xml->DEEMEDFLAG));
                        
                        //$vatapplicationlevel = trim($xml->VATAPPLICATIONLEVEL);//Ledger
                        $pricevatinclusive = trim($xml->PRICEVATINCLUSIVE);//No
                        //$defaultvatrate = trim($xml->DEFAULTVATRATE);//18
                        $buyertype = $this->util->mapbuyertypecode(trim($xml->BUYERTYPE));
                        $industrycode = $this->util->mapindustrycode(trim($xml->INDUSTRYCODE));
                        
                        $netamount = 0;
                        $taxamount = 0;
                        $grossamount = 0;
                        $itemcount = 0;
                        
                        if (isset($xml->INVENTORIES->INVENTORY)) {
                            $this->logger->write("Api : uploadcreditnote() : This is an inventory/goods invoice", 'r');
                            
                            foreach ($xml->INVENTORIES->INVENTORY as $obj){
                                $this->logger->write("Api : uploadcreditnote() : The STOCKITEMNAME is " . $obj->STOCKITEMNAME, 'r');
                                $this->logger->write("Api : uploadcreditnote() : The RATE is " . $obj->RATE, 'r');
                                
                                $ii = 0;
                                $product_skip_flag = 0;
                                
                                foreach ($mapped_fees as $m_obj) {
                                    if(trim($m_obj['productcode']) == trim($obj->PRODUCTCODE)){
                                        $this->logger->write("Api : uploadcreditnote() : The product " . $obj->PRODUCTCODE . " is mapped to a tax/fee " . $m_obj['feecode'], 'r');
                                        
                                        $f_qty = $this->util->removeunitsfromqty(trim($obj->BILLEDQTY));
                                        $f_unit = $this->util->removeunitsfromrate(trim($obj->RATE));
                                        
                                        $mapped_fees[$ii]['amount'] = ($f_qty * $f_unit); # Might be problematic. Consider "foreach ($mapped_fees as &$m_obj) {"
                                        $product_skip_flag = 1;
                                        break;
                                    }
                                    
                                    $ii = $ii + 1;
                                }
                                
                                if ($product_skip_flag == 1){
                                    continue;
                                }
                                
                                /**
                                 * 1. Get the following variables
                                 * 1.1 Deemed Flag (Yes, No)
                                 * 1.2 Industry (General, Export, Import, Imported Service)
                                 * 1.3 Buyer Type (B2G/B2B, B2C, Foreigner)
                                 * 2. Choose the tax rates based on the follwowing criteria
                                 * 2.1 If Deemed Flag = No & Industry = General & Buyer Type = B2C or B2G/B2B then use the standard rate
                                 * */
                                
                                
                                $this->logger->write("Api : uploadcreditnote() : The PRICE INCLUSIVE FLAG is " . strtoupper(trim($pricevatinclusive)), 'r');
                                
                                /********************************START CALCULATIONS HERE******************************************/
                                
                                $taxid = $this->util->getinvoicetaxrate_v2($industrycode, $buyertype, trim($obj->PRODUCTCODE), trim($xml->BUYERTIN), $this->appsettings['OVERRIDE_TAXRATE_FLAG'], $this->appsettings['TAXPAYER_CHECK_FLAG']);
                                $this->logger->write("Api : uploadcreditnote() : The computed TAXID is " . $taxid, 'r');
                                
                                if (!$taxid) {
                                    $taxid = $this->appsettings['STANDARDTAXRATE'];
                                }
                                
                                
                                /**
                                 * Author: francis.lubanga@gmail.com
                                 * Date: 2023-02-28
                                 * Description: Resolve EFRIS error code 2821 : buyerDetails-->buyerTin: you don't have permission to issue deemed invoices!
                                 *
                                 *
                                 * 1. If the deemed flag received from the ERP is YES, then override the taxid by setting it to the deemed tax rate
                                 */
                                
                                /*if ($taxid == $this->appsettings['DEEMEDTAXRATE']) {
                                 $deemedflag = 'YES';
                                 } else {
                                 $deemedflag = 'NO';
                                 }*/
                                
                                $this->logger->write("Api : uploadcreditnote() : The DEEMED FLAG is " . $deemedflag, 'r');
                                
                                if ($deemedflag == 'YES') {
                                    $taxid = $this->appsettings['DEEMEDTAXRATE'];
                                }
                                
                                $this->logger->write("Api : uploadcreditnote() : The final TAXID is " . $taxid, 'r');
                                
                                $tr = new taxrates($this->db);
                                $tr->getByID($taxid);
                                $taxcode = $tr->code;
                                $taxname = $tr->name;
                                $taxcategory = $tr->category;
                                $taxdisplaycategory = $tr->displayCategoryCode;
                                $taxdescription = $tr->description;
                                $rate = $tr->rate? $tr->rate : 0;
                                $qty = $this->util->removeunitsfromqty(trim($obj->BILLEDQTY));
                                $unit = $this->util->removeunitsfromrate(trim($obj->RATE));
                                $discountpct = empty(trim($obj->DISCOUNT))? 0 : (float)trim($obj->DISCOUNT);
                                
                                if (strtoupper(trim($pricevatinclusive)) == 'YES') {
                                    //Use the figures as they come from the ERP
                                    $total = ($qty * $unit);//??
                                    
                                    $discount = ($discountpct/100) * $total;
                                    
                                    /**
                                     * Modification Date: 2021-01-26
                                     * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                                     * */
                                    //$gross = $total - $discount;
                                    $gross = $total;
                                    
                                    $discount = (-1) * $discount;
                                    
                                    $tax = ($gross/($rate + 1)) * $rate; //??
                                    
                                    $net = $gross - $tax;
                                } elseif (strtoupper(trim($pricevatinclusive)) == 'NO') {
                                    //Manually calculate figures
                                    $this->logger->write("Api : uploadcreditnote() : Manually calculating tax", 'r');
                                    
                                    if ($rate > 0) {
                                        $unit = $unit * ($rate + 1);
                                    }
                                    
                                    $total = ($qty * $unit);//??
                                    
                                    $discount = ($discountpct/100) * $total;
                                    
                                    /**
                                     * Modification Date: 2021-01-26
                                     * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                                     * */
                                    //$gross = $total - $discount;
                                    $gross = $total;
                                    
                                    $discount = (-1) * $discount;
                                    
                                    $tax = ($gross/($rate + 1)) * $rate; //??
                                    
                                    $net = $gross - $tax;
                                }
                                
                                /********************************END CALCULATIONS HERE******************************************/
                                
                                /**
                                 * Over-ride tax, if the tax payer is not VAT registered
                                 */
                                if ($this->vatRegistered == 'N') {
                                    $tax = 0;
                                    $taxcategory = NULL;
                                    $taxcode = NULL;
                                }
                                
                                
                                
                                $netamount = $netamount + $net;
                                $taxamount = $taxamount + $tax;
                                
                                $grossamount = $grossamount + $gross;
                                $itemcount = $itemcount + 1;
                                
                                /**
                                 * Replace the INV currency with the product currency
                                 */
                                
                                $invoicedetails['currency'] = empty(trim($obj->STOCKITEMCURRENCY))? $this->util->getcurrency(trim($this->appsettings['ERPBASECURRENCY'])) : $this->util->getcurrency(trim($obj->STOCKITEMCURRENCY));
                                
                                $goods[] = array(
                                    'groupid' => NULL,
                                    'item' => trim($obj->STOCKITEMNAME),
                                    'itemcode' => trim($obj->PRODUCTCODE),
                                    'qty' => $qty,
                                    'unitofmeasure' => $this->util->mapmeasureunit(trim($obj->BUOM)),
                                    'unitprice' => $unit,
                                    'total' => $total,
                                    'taxid' => $taxid,
                                    'taxrate' => $rate,
                                    'tax' => $tax,
                                    'discounttotal' => $discount,
                                    'discounttaxrate' => $rate,
                                    'discountpercentage' => $discountpct,
                                    'ordernumber' => NULL,
                                    'discountflag' => empty(trim($obj->DISCOUNT))? '2' : '1',
                                    'deemedflag' => (strtoupper(trim($deemedflag)) == 'NO'? '2' : '1'),
                                    'exciseflag' => NULL,
                                    'categoryid' => NULL,
                                    'categoryname' => NULL,
                                    'goodscategoryid' => $this->util->mapcommodity(trim($obj->COMMODITYCATEGORYCODE)),
                                    'goodscategoryname' => trim($obj->COMMODITYCATEGORYCODE),
                                    'exciserate' => NULL,
                                    'exciserule' => NULL,
                                    'excisetax' => NULL,
                                    'pack' => NULL,
                                    'stick' => NULL,
                                    'exciseunit' => NULL,
                                    'excisecurrency' => NULL,
                                    'exciseratename' => NULL,
                                    'taxdisplaycategory' => $taxdisplaycategory,
                                    'taxcategory' => $taxcategory,
                                    'taxcategoryCode' => $taxcode,
                                    'unitofmeasurename' => trim($obj->BUOM),
                                    'vatProjectId' => trim($xml->PROJECTID),
                                    'vatProjectName' => trim($xml->PROJECTNAME)
                                );
                                
                                $this->logger->write("Api : uploadcreditnote() : The TAXCODE is " . $taxcode, 'r');
                                
                                
                                if ($this->vatRegistered == 'Y') {
                                    $taxes[] = array(
                                        'discountflag' => empty(trim($obj->DISCOUNT))? '2' : '1',
                                        'discounttotal' => $discount,
                                        'discounttaxrate' => $rate,
                                        'discountpercentage' => $discountpct,
                                        'd_netamount' => NULL,
                                        'd_taxamount' => NULL,
                                        'd_grossamount' => NULL,
                                        'groupid' => NULL,
                                        'goodid' => NULL,
                                        'taxdisplaycategory' => $taxdisplaycategory,
                                        'taxcategory' => $taxcategory,
                                        'taxcategoryCode' => $taxcode,
                                        'netamount' => $net,
                                        'taxrate' => $rate,
                                        'taxamount' => $tax,
                                        'grossamount' => $gross,
                                        'exciseunit' => NULL,
                                        'excisecurrency' => NULL,
                                        'taxratename' => $taxname,
                                        'taxdescription' => $taxdescription
                                    );
                                }
                                
                            }
                            
                            
                            /*$payments[] = array(
                             'groupid' => NULL,
                             'paymentmode' => NULL,
                             'paymentmodename' => NULL,
                             'paymentamount' => 0,
                             'ordernumber' => NULL
                             );*/
                        }
                        
                        if (isset($xml->SERVICES->SERVICE)) {
                            $this->logger->write("Api : uploadcreditnote() : This is an service invoice", 'r');
                            
                            foreach ($xml->SERVICES->SERVICE as $obj){
                                $this->logger->write("Api : uploadcreditnote() : The STOCKITEMNAME is " . $obj->STOCKITEMNAME, 'r');
                                $this->logger->write("Api : uploadcreditnote() : The RATE is " . $obj->RATE, 'r');
                                
                                $ii = 0;
                                $product_skip_flag = 0;
                                
                                foreach ($mapped_fees as $m_obj) {
                                    if(trim($m_obj['productcode']) == trim($obj->PRODUCTCODE)){
                                        $this->logger->write("Api : uploadcreditnote() : The product " . $obj->PRODUCTCODE . " is mapped to a tax/fee " . $m_obj['feecode'], 'r');
                                        
                                        $f_qty = $this->util->removeunitsfromqty(trim($obj->BILLEDQTY));
                                        $f_unit = $this->util->removeunitsfromrate(trim($obj->RATE));
                                        
                                        $mapped_fees[$ii]['amount'] = ($f_qty * $f_unit); # Might be problematic. Consider "foreach ($mapped_fees as &$m_obj) {"
                                        $product_skip_flag = 1;
                                        break;
                                    }
                                    
                                    $ii = $ii + 1;
                                }
                                
                                if ($product_skip_flag == 1){
                                    continue;
                                }
                                
                                /**
                                 * 1. Get the following variables
                                 * 1.1 Deemed Flag (Yes, No)
                                 * 1.2 Industry (General, Export, Import, Imported Service)
                                 * 1.3 Buyer Type (B2G/B2B, B2C, Foreigner)
                                 * 2. Choose the tax rates based on the follwowing criteria
                                 * 2.1 If Deemed Flag = No & Industry = General & Buyer Type = B2C or B2G/B2B then use the standard rate
                                 * */
                                
                                
                                $this->logger->write("Api : uploadcreditnote() : The PRICE INCLUSIVE FLAG is " . strtoupper(trim($pricevatinclusive)), 'r');
                                
                                /********************************START CALCULATIONS HERE******************************************/
                                $taxid = $this->util->getinvoicetaxrate_v2($industrycode, $buyertype, trim($obj->PRODUCTCODE), trim($xml->BUYERTIN), $this->appsettings['OVERRIDE_TAXRATE_FLAG'], $this->appsettings['TAXPAYER_CHECK_FLAG']);
                                $this->logger->write("Api : uploadcreditnote() : The computed TAXID is " . $taxid, 'r');
                                
                                if (!$taxid) {
                                    $taxid = $this->appsettings['STANDARDTAXRATE'];
                                }
                                
                                
                                /**
                                 * Author: francis.lubanga@gmail.com
                                 * Date: 2023-02-28
                                 * Description: Resolve EFRIS error code 2821 : buyerDetails-->buyerTin: you don't have permission to issue deemed invoices!
                                 *
                                 *
                                 * 1. If the deemed flag received from the ERP is YES, then override the taxid by setting it to the deemed tax rate
                                 */
                                
                                /*if ($taxid == $this->appsettings['DEEMEDTAXRATE']) {
                                 $deemedflag = 'YES';
                                 } else {
                                 $deemedflag = 'NO';
                                 }*/
                                
                                $this->logger->write("Api : uploadcreditnote() : The DEEMED FLAG is " . $deemedflag, 'r');
                                
                                if ($deemedflag == 'YES') {
                                    $taxid = $this->appsettings['DEEMEDTAXRATE'];
                                }
                                
                                $this->logger->write("Api : uploadcreditnote() : The final TAXID is " . $taxid, 'r');
                                
                                $tr = new taxrates($this->db);
                                $tr->getByID($taxid);
                                $taxcode = $tr->code;
                                $taxname = $tr->name;
                                $taxcategory = $tr->category;
                                $taxdescription = $tr->description;
                                $rate = $tr->rate? $tr->rate : 0;
                                $qty = $this->util->removeunitsfromqty(trim($obj->BILLEDQTY));
                                $unit = $this->util->removecommasfromamount(trim($obj->RATE));
                                $discountpct = empty(trim($obj->DISCOUNT))? 0 : (float)trim($obj->DISCOUNT);
                                
                                if (strtoupper(trim($pricevatinclusive)) == 'YES') {
                                    //Use the figures are they come from the ERP
                                    $total = ($qty * $unit);//??
                                    
                                    $discount = ($discountpct/100) * $total;
                                    
                                    /**
                                     * Modification Date: 2021-01-26
                                     * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                                     * */
                                    //$gross = $total - $discount;
                                    $gross = $total;
                                    
                                    $discount = (-1) * $discount;
                                    
                                    $tax = ($gross/($rate + 1)) * $rate; //??
                                    
                                    $net = $gross - $tax;
                                } elseif (strtoupper(trim($pricevatinclusive)) == 'NO') {
                                    //Manually calculate figures
                                    $this->logger->write("Api : uploadcreditnote() : Manually calculating tax", 'r');
                                    
                                    if ($rate > 0) {
                                        $unit = $unit * ($rate + 1);
                                    }
                                    
                                    $total = ($qty * $unit);//??
                                    
                                    $discount = ($discountpct/100) * $total;
                                    
                                    /**
                                     * Modification Date: 2021-01-26
                                     * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                                     * */
                                    //$gross = $total - $discount;
                                    $gross = $total;
                                    
                                    $discount = (-1) * $discount;
                                    
                                    $tax = ($gross/($rate + 1)) * $rate; //??
                                    
                                    $net = $gross - $tax;
                                }
                                
                                /********************************END CALCULATIONS HERE******************************************/
                                
                                /**
                                 * Over-ride tax, if the tax payer is not VAT registered
                                 */
                                if ($this->vatRegistered == 'N') {
                                    $tax = 0;
                                    $taxcategory = NULL;
                                    $taxcode = NULL;
                                }
                                
                                $netamount = $netamount + $net;
                                $taxamount = $taxamount + $tax;
                                
                                $grossamount = $grossamount + $gross;
                                $itemcount = $itemcount + 1;
                                
                                /**
                                 * Replace the INV currency with the product currency
                                 */
                                $invoicedetails['currency'] = empty(trim($obj->STOCKITEMCURRENCY))? $this->util->getcurrency(trim($this->appsettings['ERPBASECURRENCY'])) : $this->util->getcurrency(trim($obj->STOCKITEMCURRENCY));
                                
                                $goods[] = array(
                                    'groupid' => NULL,
                                    'item' => trim($obj->STOCKITEMNAME),
                                    'itemcode' => trim($obj->PRODUCTCODE),
                                    'qty' => $qty,
                                    'unitofmeasure' => $this->util->mapmeasureunit(trim($obj->BUOM)),
                                    'unitprice' => $unit,
                                    'total' => $total,
                                    'taxid' => $taxid,
                                    'taxrate' => $rate,
                                    'tax' => $tax,
                                    'discounttotal' => $discount,
                                    'discounttaxrate' => $rate,
                                    'discountpercentage' => $discountpct,
                                    'ordernumber' => NULL,
                                    'discountflag' => empty(trim($obj->DISCOUNT))? '2' : '1',
                                    'deemedflag' => (strtoupper(trim($deemedflag)) == 'NO'? '2' : '1'),
                                    'exciseflag' => NULL,
                                    'categoryid' => NULL,
                                    'categoryname' => NULL,
                                    'goodscategoryid' => $this->util->mapcommodity(trim($obj->COMMODITYCATEGORYCODE)),
                                    'goodscategoryname' => trim($obj->COMMODITYCATEGORYCODE),
                                    'exciserate' => NULL,
                                    'exciserule' => NULL,
                                    'excisetax' => NULL,
                                    'pack' => NULL,
                                    'stick' => NULL,
                                    'exciseunit' => NULL,
                                    'excisecurrency' => NULL,
                                    'exciseratename' => NULL,
                                    'taxdisplaycategory' => $taxdisplaycategory,
                                    'taxcategory' => $taxcategory,
                                    'taxcategoryCode' => $taxcode,
                                    'unitofmeasurename' => trim($obj->BUOM),
                                    'vatProjectId' => trim($xml->PROJECTID),
                                    'vatProjectName' => trim($xml->PROJECTNAME)
                                );
                                
                                if ($this->vatRegistered == 'Y'){
                                    $taxes[] = array(
                                        'discountflag' => empty(trim($obj->DISCOUNT))? '2' : '1',
                                        'discounttotal' => $discount,
                                        'discounttaxrate' => $rate,
                                        'discountpercentage' => $discountpct,
                                        'd_netamount' => NULL,
                                        'd_taxamount' => NULL,
                                        'd_grossamount' => NULL,
                                        'groupid' => NULL,
                                        'goodid' => NULL,
                                        'taxdisplaycategory' => $taxdisplaycategory,
                                        'taxcategory' => $taxcategory,
                                        'taxcategoryCode' => $taxcode,
                                        'netamount' => $net,
                                        'taxrate' => $rate,
                                        'taxamount' => $tax,
                                        'grossamount' => $gross,
                                        'exciseunit' => NULL,
                                        'excisecurrency' => NULL,
                                        'taxratename' => $taxname,
                                        'taxdescription' => $taxdescription
                                    );
                                }
                                
                                
                            }
                            
                            
                            /*$payments[] = array(
                             'groupid' => NULL,
                             'paymentmode' => NULL,
                             'paymentmodename' => NULL,
                             'paymentamount' => 0,
                             'ordernumber' => NULL
                             );*/
                        }
                        
                        foreach ($mapped_fees as $m_obj) {
                            $this->logger->write("Api : uploadcreditnote() : Adding fees to the tax array", 'r');
                            
                            $tr = new taxrates($this->db);
                            $tr->getByCode($m_obj['feecode']);
                            $taxcode = $tr->code;
                            $taxname = $tr->name;
                            $taxcategory = $tr->category;
                            $taxdescription = $tr->description;
                            $taxdisplaycategory = $tr->displayCategoryCode;
                            
                            $taxes[] = array(
                                'discountflag' => '1',
                                'discounttotal' => NULL,
                                'discounttaxrate' => NULL,
                                'discountpercentage' => NULL,
                                'd_netamount' => NULL,
                                'd_taxamount' => NULL,
                                'd_grossamount' => NULL,
                                'groupid' => NULL,
                                'goodid' => NULL,
                                'taxdisplaycategory' => $taxdisplaycategory,
                                'taxcategory' => $taxcategory,
                                'taxcategoryCode' => $taxcode,
                                'netamount' => 0,
                                'taxrate' => $m_obj['amount'],
                                'taxamount' => $m_obj['amount'],
                                'grossamount' => $m_obj['amount'],
                                'exciseunit' => NULL,
                                'excisecurrency' => NULL,
                                'taxratename' => $taxname,
                                'taxdescription' => $taxdescription
                            );
                        }
                        
                        $this->logger->write("Api : uploadcreditnote() : The GOODS count: " . sizeof($goods), 'r');
                        $this->logger->write("Api : uploadcreditnote() : The TAX count: " . sizeof($taxes), 'r');
                        $this->logger->write("Api : uploadcreditnote() : The PAYMENTS count: " . sizeof($payments), 'r');
                        
                        $data = $this->util->uploadinvoice($this->userid_u, $branch->uraid, $buyer, $invoicedetails, $goods, $payments, $taxes);
                        $data = json_decode($data, true);
                        
                        if (isset($data['returnCode'])){
                            $this->logger->write("Api : uploadcreditnote() : The operation to upload the credit memo not successful. The error message is " . $data['returnMessage'], 'r');
                            //$this->util->createinappnotification(NULL, NULL, NULL, self::$module, self::$submodule, $operation, $event, $eventnotification, NULL, $this->f3->get('SESSION.id'), "The operation to upload the invoice by " . $this->f3->get('SESSION.username') . " was not successful");
                            $this->message = $data['returnMessage'];
                            $this->code = $data['returnCode'];
                            
                            
                            /**
                             * If the invoice passed the duplicate check due to an error or incomplete parameters, but EFRIS determines that it is a duplicate invoice,
                             * We insert it into the database?
                             */
                            
                            
                            $body = $this->code . ' : ' . $this->message;
                            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                        } else {
                            if (isset($data['basicInformation'])){
                                $antifakeCode = $data['basicInformation']['antifakeCode']; //32966911991799104051
                                $invoiceId = $data['basicInformation']['invoiceId']; //3257429764295992735
                                $invoiceNo = $data['basicInformation']['invoiceNo']; //3120012276043
                                
                                $issuedDate = $data['basicInformation']['issuedDate']; //18/09/2020 17:14:12
                                $issuedDate = str_replace('/', '-', $issuedDate);//Replace / with -
                                $issuedDate = date("Y-m-d H:i:s", strtotime($issuedDate));
                                
                                $issuedTime = $data['basicInformation']['issuedDate']; //18/09/2020 17:14:12
                                $issuedTime = str_replace('/', '-', $issuedTime);//Replace / with -
                                $issuedTime = date("Y-m-d H:i:s", strtotime($issuedTime));
                                
                                $issuedDatePdf = $data['basicInformation']['issuedDatePdf']; //318/09/2020 17:14:12
                                $issuedDatePdf = str_replace('/', '-', $issuedDatePdf);//Replace / with -
                                $issuedDatePdf = date("Y-m-d H:i:s", strtotime($issuedDatePdf));
                                
                                $oriInvoiceId = $data['basicInformation']['oriInvoiceId'];//1
                                $isInvalid = $data['basicInformation']['isInvalid'];//1
                                $isRefund = $data['basicInformation']['isRefund'];//1
                                $currencyRate = $data['basicInformation']['currencyRate'];
                                
                                $creditnoteid = $invoiceId;
                                $creditnotenumber = $invoiceNo;
                                $issueddate = $issuedDate;
                                $fdn = $antifakeCode;
                            }
                            
                            if (isset($data['summary'])){
                                $grossAmount = $data['summary']['grossAmount']; //832000
                                $itemCount = $data['summary']['itemCount']; //1
                                $netAmount = $data['summary']['netAmount']; //705084.75
                                $qrCode = $data['summary']['qrCode']; //020000001149IC1200122760430004F588000000C1A8450A20A021D534A1000121462A1000094968~MM INTERGRATED STEEL MILLS (UGANDA) LIMITED~Ediomu & Company~Kiboko Galv Corr. Sheet 36G
                                $taxAmount = $data['summary']['taxAmount'];//126915.25
                                $modeCode = $data['summary']['modeCode'];//0
                                
                                $mode = new modes($this->db);
                                $mode->getByCode($modeCode);
                                $modeName = $mode->name;//online
                                
                                $f = new \NumberFormatter("en", NumberFormatter::SPELLOUT);
                                $grossAmountWords = $f->format($grossAmount);//two million,
                                
                                $qr = $qrCode;
                                
                                
                            }
                            
                            $invoicedetails['einvoicedatamatrixcode'] = $qrCode;
                            $invoicedetails['grossamountword'] = $grossAmountWords;
                            $invoicedetails['modename'] = $modeName;
                            $invoicedetails['modecode'] = $modeCode;
                            $invoicedetails['taxamount'] = $taxAmount;
                            $invoicedetails['netamount'] = $netAmount;
                            $invoicedetails['itemcount'] = $itemCount;
                            $invoicedetails['grossamount'] = $grossAmount;
                            $invoicedetails['antifakecode'] = $antifakeCode;
                            $invoicedetails['issueddate'] = $issuedDate;
                            $invoicedetails['einvoicenumber'] = $invoiceNo;
                            $invoicedetails['einvoiceid'] = $invoiceId;
                            $invoicedetails['isinvalid'] = $isRefund;
                            $invoicedetails['isrefund'] = $isInvalid;
                            $invoicedetails['oriinvoiceid'] = $oriInvoiceId;
                            $invoicedetails['issueddatepdf'] = $issuedDatePdf;
                            $invoicedetails['issuedtime'] = $issuedTime;
                            $invoicedetails['origrossamount'] = '0';
                            $invoicedetails['currencyRate'] = $currencyRate;
                            
                            $inv_status = $this->util->createcreditmemo($invoicedetails, $goods, $taxes, $buyer, $this->userid_u);
                            
                            if ($inv_status) {
                                $this->logger->write("Api : uploadcreditnote() : The credit memo was created on eTW successfully", 'r');
                            } else {
                                $this->logger->write("Api : uploadcreditnote() : The credit memo was NOT created on eTW", 'r');
                            }
                            
                            $this->message = 'The operation to upload the credit memo was successful';
                            $this->code = '000';
                        }
                    } else {
                        $this->message = 'The credit memo has already been uploaded into EFRIS';
                        $this->code = '99';
                        
                        $creditnoteid = $inv_check->einvoiceid;
                        $creditnotenumber = $inv_check->einvoicenumber;
                        $issueddate = $inv_check->issuedtime;
                        $fdn = $inv_check->antifakecode;
                        $qr = $inv_check->einvoicedatamatrixcode;
                    }
                    
                    $activity = 'UPLOADCREDITMEMO: ' . $vchnumber . ': ' . $vchref . ': ' . $this->message;
                    
                    $this->util->createerpauditlog($this->userid_u, $activity, $windowsuser, $ipaddress, $macaddress, $systemname, $xml);
                } elseif(strtoupper($vchtypename) == 'CREDIT NOTE'){
                    /**
                     * Check if C/N is already issued
                     *
                     */
                    
                    $inv_check = new DB\SQL\Mapper($this->db, 'tblcreditnotes');
                    $inv_check->load(array('TRIM(erpcreditnoteid)=? AND TRIM(vouchertype)=? AND TRIM(vouchertypename)=?', $vchnumber, $vchtype, $vchtypename));
                    $this->logger->write($this->db->log(TRUE), 'r');
                    
                    if($inv_check->dry ()){
                        $this->logger->write("Api : uploadcreditnote() : The creditnote does not exist on eTW. Proceed and upload", 'r');
                        
                        if(trim($vchref) !== '' || ! empty(trim($vchref))) {
                            $this->logger->write("Api : uploadcreditnote() : The associated original invoice was supplied", 'r');
                            
                            $orig_inv = new DB\SQL\Mapper($this->db, 'tblinvoices');
                            $orig_inv->load(array('TRIM(erpinvoiceid)=?', $vchref));
                            $this->logger->write($this->db->log(TRUE), 'r');
                            
                            if ($orig_inv->dry()) {
                                $this->logger->write("Api : uploadcreditnote() : There associated original invoice does not exist in the database", 'r');
                                $oriinvoiceid = NULL;
                                $oriinvoiceno = NULL;
                            } else {
                                $this->logger->write("Api : uploadcreditnote() : There is an associated original invoice", 'r');
                                $oriinvoiceid = $orig_inv->einvoiceid;
                                $oriinvoiceno = $orig_inv->einvoicenumber;
                                
                                /**
                                 * Author: francis.lubanga@gmail.com
                                 * Date: 2021-02-28
                                 * Description: Resolve EFRIS error code 2783: oriInvoiceNo: cannot be empty!
                                 *
                                 *
                                 * 1. Check if oriinvoiceid is empty
                                 * 2. If oriinvoiceid is NOT empty, then ignore
                                 * 3. If it is empty, then query EFRIS and retrieve it
                                 * 4. Update the eTW record of this invoice
                                 */
                                
                                if(trim($oriinvoiceid) == '' || empty(trim($oriinvoiceid))) {
                                    $this->logger->write("Api : uploadcreditnote() : The oriinvoiceid is empty", 'r');
                                    
                                    if(trim($oriinvoiceno) == '' || empty(trim($oriinvoiceno))) {
                                        $this->logger->write("Api : uploadcreditnote() : The oriinvoiceno is empty", 'r');
                                    } else {
                                        $this->logger->write("Api : uploadcreditnote() : The oriinvoiceno is NOT empty", 'r');
                                        $i_data = $this->util->downloadinvoice($this->userid_u, $oriinvoiceno);
                                        $i_data = json_decode($i_data, true);
                                        
                                        /*START OF INVOICE BLOCK*/
                                        if (isset($i_data['basicInformation'])){
                                            $TempInvoiceId = $i_data['basicInformation']['invoiceId']; //3257429764295992735
                                            $TempInvoiceNo = $i_data['basicInformation']['invoiceNo']; //3120012276043
                                            
                                            if (trim($TempInvoiceNo) == trim($oriinvoiceno)) {
                                                $oriinvoiceid = $TempInvoiceId;
                                            }
                                        }
                                        /*END INVOICE BLOCK*/
                                        
                                        try{
                                            $this->db->exec(array('UPDATE tblinvoices SET einvoiceid = "' . $oriinvoiceid . '", modifieddt = NOW(), modifiedby = ' . $this->userid . ' WHERE einvoicenumber = "' . $oriinvoiceno . '"'));
                                            $this->logger->write($this->db->log(TRUE), 'r');
                                        } catch (Exception $e) {
                                            $this->logger->write("Api : uploadcreditnote() : Failed to update the table tblinvoices. The error message is " . $e->getMessage(), 'r');
                                        }
                                    }
                                } else {
                                    $this->logger->write("Api : uploadcreditnote() : The oriinvoiceid is not empty", 'r');
                                }
                            }
                        } else {
                            $this->logger->write("Api : uploadcreditnote() : The associated original invoice was not supplied", 'r');
                            $oriinvoiceid = NULL;
                            $oriinvoiceno = NULL;
                        }
                        
                        
                        
                        $reasoncode = NULL;
                        $reason = NULL;
                        $remarks = '';
                        
                        foreach ($xml->REASONS->REASON as $obj){
                            $reasoncode = trim($obj->REASONCODE);
                            $reason = trim($obj->REASON);
                            $remarks = trim($obj->REMARKS);
                        }
                        
                        $creditnotedetails = array(
                            'gooddetailgroupid' => NULL,
                            'taxdetailgroupid' => NULL,
                            'paymentdetailgroupid' => NULL,
                            'erpcreditnoteid' => trim($xml->VOUCHERNUMBER),
                            'erpcreditnoteno' => NULL,
                            'erpinvoiceid' => trim($xml->VOUCHERREF),
                            'erpinvoiceno' => NULL,
                            'antifakecode' => NULL,
                            'deviceno' => trim($devicedetails->deviceno),
                            'issueddate' => date('Y-m-d'),
                            'issuedtime' => date('Y-m-d H:i:s'),
                            'operator' => trim($xml->ERPUSER),
                            'currency' => $this->util->getcurrency(trim($xml->CURRENCY)),
                            'oriinvoiceid' => $oriinvoiceid,
                            'oriinvoiceno' => $oriinvoiceno,
                            'invoicetype' => "1",
                            'invoicekind' => ($this->vatRegistered == 'Y')? "1" : "2",
                            'datasource' => $this->appsettings['WESERVICEDS'],
                            'invoiceindustrycode' => $this->util->mapindustrycode(trim($xml->INDUSTRYCODE)),
                            'einvoiceid' => NULL,
                            'einvoicenumber' => NULL,
                            'einvoicedatamatrixcode' => NULL,
                            'isbatch' => '0',
                            'netamount' => NULL,
                            'taxamount' => NULL,
                            'grossamount' => NULL,
                            'origrossamount' => NULL,
                            'itemcount' => NULL,
                            'modecode' => '1',/*default-online*/
                            'modename' => NULL,
                            'remarks' => $remarks,
                            'buyerid' => NULL,
                            'sellerid' => $this->appsettings['SELLER_RECORD_ID'],
                            'issueddatepdf' => date('Y-m-d H:i:s'),
                            'grossamountword' => NULL,
                            'isinvalid' => 0,
                            'isrefund' => 0,
                            'vchtype' => trim($xml->VOUCHERTYPE),
                            'vchtypename' => trim($xml->VOUCHERTYPENAME),
                            'reasoncode' => $this->util->mapreasoncode($reasoncode),
                            'reason' => $reason,
                            'referenceno' => NULL,
                            'approvestatus' => NULL,
                            'creditnoteapplicationid' => NULL,
                            'refundinvoiceno' => NULL,
                            'applicationtime' => date('Y-m-d H:i:s'),
                            'invoiceapplycategorycode' => '101' /*101-Credit Note*/
                        );
                        
                        $buyer = array(
                            'tin' => trim($xml->BUYERTIN),
                            'ninbrn' => trim($xml->BUYERNINBRN),
                            'PassportNum' => trim($xml->BUYERPASSPORTNUM),
                            'legalname' => trim($xml->BUYERLEGALNAME),
                            'businessname' => trim($xml->BUSINESSNAME),
                            'address' => trim($xml->BUYERADDRESS),
                            'mobilephone' => trim($xml->MOBILEPHONE),
                            'linephone' => trim($xml->BUYERLINEPHONE),
                            'emailaddress' => trim($xml->BUYEREMAIL),
                            'placeofbusiness' => trim($xml->BUYERPLACEOFBUSI),
                            'type' => $this->util->mapbuyertypecode(trim($xml->BUYERTYPE)),
                            'citizineship' => trim($xml->BUYERCITIZENSHIP),
                            'sector' => trim($xml->BUYERSECTOR),
                            'referenceno' => trim($xml->VOUCHERNUMBER) == ''? trim($xml->BUYERREFERENCENO) : trim($xml->VOUCHERREF) . trim($xml->VOUCHERNUMBER),
                            'datasource' => $this->appsettings['WESERVICEDS']
                        );
                        
                        $this->logger->write("Api : uploadcreditnote() : The BUYERTYPE are " . trim($xml->BUYERTYPE), 'r');
                        //$this->logger->write("Api : uploadcreditnote() : The INVENTORIES are " . trim($xml->INVENTORIES), 'r');
                        
                        $goods = array();
                        $taxes = array();
                        $payments = array();
                        
                        /**
                         * Author: francis.lubanga@gmail.com
                         * Date: 2023-02-28
                         * Description: Resolve EFRIS error code 2821 : buyerDetails-->buyerTin: you don't have permission to issue deemed invoices!
                         *
                         *
                         * 1. Take the deemed flag from the payload from the client
                         */
                        //$deemedflag = 'NO';
                        $deemedflag = trim($xml->DEEMEDFLAG) == ''? 'NO' : strtoupper(trim($xml->DEEMEDFLAG));
                        
                        //$vatapplicationlevel = trim($xml->VATAPPLICATIONLEVEL);//Ledger
                        $pricevatinclusive = trim($xml->PRICEVATINCLUSIVE);//No
                        //$defaultvatrate = trim($xml->DEFAULTVATRATE);//18
                        $buyertype = $this->util->mapbuyertypecode(trim($xml->BUYERTYPE));
                        $industrycode = $this->util->mapindustrycode(trim($xml->INDUSTRYCODE));
                        
                        $netamount = 0;
                        $taxamount = 0;
                        $grossamount = 0;
                        $itemcount = 0;
                        
                        if (isset($xml->INVENTORIES->INVENTORY)) {
                            $this->logger->write("Api : uploadcreditnote() : This is an inventory/goods creditnote", 'r');
                            
                            foreach ($xml->INVENTORIES->INVENTORY as $obj){
                                $this->logger->write("Api : uploadcreditnote() : The STOCKITEMNAME is " . $obj->STOCKITEMNAME, 'r');
                                $this->logger->write("Api : uploadcreditnote() : The RATE is " . $obj->RATE, 'r');
                                
                                /**
                                 * 1. Get the following variables
                                 * 1.1 Deemed Flag (Yes, No)
                                 * 1.2 Industry (General, Export, Import, Imported Service)
                                 * 1.3 Buyer Type (B2G/B2B, B2C, Foreigner)
                                 * 2. Choose the tax rates based on the follwowing criteria
                                 * 2.1 If Deemed Flag = No & Industry = General & Buyer Type = B2C or B2G/B2B then use the standard rate
                                 * */
                                
                                
                                $this->logger->write("Api : uploadcreditnote() : The PRICE INCLUSIVE FLAG is " . strtoupper(trim($pricevatinclusive)), 'r');
                                
                                /********************************START CALCULATIONS HERE******************************************/
                                $taxid = $this->util->getinvoicetaxrate_v2($industrycode, $buyertype, trim($obj->PRODUCTCODE), trim($xml->BUYERTIN), $this->appsettings['OVERRIDE_TAXRATE_FLAG'], $this->appsettings['TAXPAYER_CHECK_FLAG']);
                                $this->logger->write("Api : uploadcreditnote() : The computed TAXID is " . $taxid, 'r');
                                
                                if (!$taxid) {
                                    $taxid = $this->appsettings['STANDARDTAXRATE'];
                                }
                                
                                
                                /**
                                 * Author: francis.lubanga@gmail.com
                                 * Date: 2023-02-28
                                 * Description: Resolve EFRIS error code 2821 : buyerDetails-->buyerTin: you don't have permission to issue deemed invoices!
                                 *
                                 *
                                 * 1. If the deemed flag received from the ERP is YES, then override the taxid by setting it to the deemed tax rate
                                 */
                                
                                /*if ($taxid == $this->appsettings['DEEMEDTAXRATE']) {
                                 $deemedflag = 'YES';
                                 } else {
                                 $deemedflag = 'NO';
                                 }*/
                                
                                $this->logger->write("Api : uploadinvoice() : The DEEMED FLAG is " . $deemedflag, 'r');
                                
                                if ($deemedflag == 'YES') {
                                    $taxid = $this->appsettings['DEEMEDTAXRATE'];
                                }
                                
                                $this->logger->write("Api : uploadcreditnote() : The final TAXID is " . $taxid, 'r');
                                
                                $tr = new taxrates($this->db);
                                $tr->getByID($taxid);
                                $taxcode = $tr->code;
                                $taxname = $tr->name;
                                $taxcategory = $tr->category;
                                $taxdisplaycategory = $tr->displayCategoryCode;
                                $taxdescription = $tr->description;
                                $rate = $tr->rate? $tr->rate : 0;
                                $qty = $this->util->removeunitsfromqty(trim($obj->BILLEDQTY));
                                $unit = $this->util->removeunitsfromrate(trim($obj->RATE));
                                $discountpct = empty(trim($obj->DISCOUNT))? 0 : (float)trim($obj->DISCOUNT);
                                
                                if (strtoupper(trim($pricevatinclusive)) == 'YES') {
                                    //Use the figures are they come from the ERP
                                    $total = ($qty * $unit);//??
                                    
                                    $discount = ($discountpct/100) * $total;
                                    
                                    /**
                                     * Modification Date: 2021-01-26
                                     * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                                     * */
                                    //$gross = $total - $discount;
                                    $gross = $total;
                                    
                                    $discount = (-1) * $discount;
                                    
                                    $tax = ($gross/($rate + 1)) * $rate; //??
                                    
                                    $net = $gross - $tax;
                                } elseif (strtoupper(trim($pricevatinclusive)) == 'NO') {
                                    //Manually calculate figures
                                    $this->logger->write("Api : uploadcreditnote() : Manually calculating tax", 'r');
                                    
                                    if ($rate > 0) {
                                        $unit = $unit * ($rate + 1);
                                    }
                                    
                                    $total = ($qty * $unit);//??
                                    
                                    $discount = ($discountpct/100) * $total;
                                    
                                    /**
                                     * Modification Date: 2021-01-26
                                     * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                                     * */
                                    //$gross = $total - $discount;
                                    $gross = $total;
                                    
                                    $discount = (-1) * $discount;
                                    
                                    $tax = ($gross/($rate + 1)) * $rate; //??
                                    
                                    $net = $gross - $tax;
                                }
                                
                                /********************************END CALCULATIONS HERE******************************************/
                                
                                if ($this->vatRegistered == 'N') {
                                    $tax = 0;
                                    $taxcategory = NULL;
                                    $taxcode = NULL;
                                }
                                
                                $netamount = $netamount + $net;
                                $taxamount = $taxamount + $tax;
                                
                                $grossamount = $grossamount + $gross;
                                $itemcount = $itemcount + 1;
                                
                                /**
                                 * Replace the INV currency with the product currency
                                 */
                                $creditnotedetails['currency'] = empty(trim($obj->STOCKITEMCURRENCY))? $this->util->getcurrency(trim($this->appsettings['ERPBASECURRENCY'])) : $this->util->getcurrency(trim($obj->STOCKITEMCURRENCY));
                                
                                
                                /**
                                 * Author: francis.lubanga@gmail.com
                                 * Modification Date: 2021-02-28
                                 * Description: Resolving EFRIS error code 1427 - goodsDetails-->item:Must be the same as the original invoice!Collection index:0
                                 */
                                
                                /*Reset the order number*/
                                $ordernumber = NULL;
                                
                                try {
                                    $o_data = array ();
                                    $r = $this->db->exec(array('SELECT g.ordernumber "ordernumber" FROM tblgooddetails g JOIN tblinvoices i ON i.gooddetailgroupid = g.groupid AND i.einvoicenumber = "' . $oriinvoiceno . '" WHERE TRIM(g.itemcode) = "' . trim($obj->PRODUCTCODE) . '"'));
                                    $this->logger->write($this->db->log(TRUE), 'r');
                                    foreach ( $r as $set ) {
                                        $o_data [] = $set;
                                    }
                                    
                                    $ordernumber = $o_data[0]['ordernumber'];
                                    $this->logger->write("Api : uploadcreditnote() : The order number for product " . trim($obj->STOCKITEMNAME) . " is: " . $ordernumber, 'r');
                                } catch (Exception $e) {
                                    $this->logger->write("Api : uploadcreditnote() : The operation to retrieve the order number was not successful. The error messages is " . $e->getMessage(), 'r');
                                }
                                
                                $goods[] = array(
                                    'groupid' => NULL,
                                    'item' => trim($obj->STOCKITEMNAME),
                                    'itemcode' => trim($obj->PRODUCTCODE),
                                    'qty' => $qty,
                                    'unitofmeasure' => $this->util->mapmeasureunit(trim($obj->BUOM)),
                                    'unitprice' => $unit,
                                    'total' => $total,
                                    'taxid' => $taxid,
                                    'taxrate' => $rate,
                                    'tax' => $tax,
                                    'discounttotal' => $discount,
                                    'discounttaxrate' => $rate,
                                    'discountpercentage' => $discountpct,
                                    'ordernumber' => empty($ordernumber)? NULL : $ordernumber,
                                    'discountflag' => empty(trim($obj->DISCOUNT))? '2' : '1',
                                    'deemedflag' => (strtoupper(trim($deemedflag)) == 'NO'? '2' : '1'),
                                    'exciseflag' => NULL,
                                    'categoryid' => NULL,
                                    'categoryname' => NULL,
                                    'goodscategoryid' => $this->util->mapcommodity(trim($obj->COMMODITYCATEGORYCODE)),
                                    'goodscategoryname' => trim($obj->COMMODITYCATEGORYCODE),
                                    'exciserate' => NULL,
                                    'exciserule' => NULL,
                                    'excisetax' => NULL,
                                    'pack' => NULL,
                                    'stick' => NULL,
                                    'exciseunit' => NULL,
                                    'excisecurrency' => NULL,
                                    'exciseratename' => NULL,
                                    'taxdisplaycategory' => $taxdisplaycategory,
                                    'taxcategory' => $taxcategory,
                                    'taxcategoryCode' => $taxcode,
                                    'unitofmeasurename' => trim($obj->BUOM)
                                );
                                
                                if ($this->vatRegistered == 'Y') {
                                    $taxes[] = array(
                                        'discountflag' => empty(trim($obj->DISCOUNT))? '2' : '1',
                                        'discounttotal' => $discount,
                                        'discounttaxrate' => $rate,
                                        'discountpercentage' => $discountpct,
                                        'd_netamount' => NULL,
                                        'd_taxamount' => NULL,
                                        'd_grossamount' => NULL,
                                        'groupid' => NULL,
                                        'goodid' => NULL,
                                        'taxdisplaycategory' => $taxdisplaycategory,
                                        'taxcategory' => $taxcategory,
                                        'taxcategoryCode' => $taxcode,
                                        'netamount' => $net,
                                        'taxrate' => $rate,
                                        'taxamount' => $tax,
                                        'grossamount' => $gross,
                                        'exciseunit' => NULL,
                                        'excisecurrency' => NULL,
                                        'taxratename' => $taxname,
                                        'taxdescription' => $taxdescription
                                    );
                                }
                                
                                
                            }
                            
                            
                            /*$payments[] = array(
                             'groupid' => NULL,
                             'paymentmode' => NULL,
                             'paymentmodename' => NULL,
                             'paymentamount' => 0,
                             'ordernumber' => NULL
                             );*/
                        }
                        
                        if (isset($xml->SERVICES->SERVICE)) {
                            $this->logger->write("Api : uploadcreditnote() : This is an service creditnote", 'r');
                            
                            foreach ($xml->SERVICES->SERVICE as $obj){
                                $this->logger->write("Api : uploadcreditnote() : The STOCKITEMNAME is " . $obj->STOCKITEMNAME, 'r');
                                $this->logger->write("Api : uploadcreditnote() : The RATE is " . $obj->RATE, 'r');
                                
                                /**
                                 * 1. Get the following variables
                                 * 1.1 Deemed Flag (Yes, No)
                                 * 1.2 Industry (General, Export, Import, Imported Service)
                                 * 1.3 Buyer Type (B2G/B2B, B2C, Foreigner)
                                 * 2. Choose the tax rates based on the follwowing criteria
                                 * 2.1 If Deemed Flag = No & Industry = General & Buyer Type = B2C or B2G/B2B then use the standard rate
                                 * */
                                
                                
                                $this->logger->write("Api : uploadproduct() : The PRICE INCLUSIVE FLAG is " . strtoupper(trim($pricevatinclusive)), 'r');
                                
                                /********************************START CALCULATIONS HERE******************************************/
                                $taxid = $this->util->getinvoicetaxrate_v2($industrycode, $buyertype, trim($obj->PRODUCTCODE), trim($xml->BUYERTIN), $this->appsettings['OVERRIDE_TAXRATE_FLAG'], $this->appsettings['TAXPAYER_CHECK_FLAG']);
                                $this->logger->write("Api : uploadcreditnote() : The computed TAXID is " . $taxid, 'r');
                                
                                if (!$taxid) {
                                    $taxid = $this->appsettings['STANDARDTAXRATE'];
                                }
                                
                                
                                /**
                                 * Author: francis.lubanga@gmail.com
                                 * Date: 2023-02-28
                                 * Description: Resolve EFRIS error code 2821 : buyerDetails-->buyerTin: you don't have permission to issue deemed invoices!
                                 *
                                 *
                                 * 1. If the deemed flag received from the ERP is YES, then override the taxid by setting it to the deemed tax rate
                                 */
                                
                                /*if ($taxid == $this->appsettings['DEEMEDTAXRATE']) {
                                 $deemedflag = 'YES';
                                 } else {
                                 $deemedflag = 'NO';
                                 }*/
                                
                                $this->logger->write("Api : uploadinvoice() : The DEEMED FLAG is " . $deemedflag, 'r');
                                
                                if ($deemedflag == 'YES') {
                                    $taxid = $this->appsettings['DEEMEDTAXRATE'];
                                }
                                
                                $this->logger->write("Api : uploadcreditnote() : The final TAXID is " . $taxid, 'r');
                                
                                $tr = new taxrates($this->db);
                                $tr->getByID($taxid);
                                $taxcode = $tr->code;
                                $taxname = $tr->name;
                                $taxcategory = $tr->category;
                                $taxdescription = $tr->description;
                                $rate = $tr->rate? $tr->rate : 0;
                                $qty = $this->util->removeunitsfromqty(trim($obj->BILLEDQTY));
                                $unit = $this->util->removecommasfromamount(trim($obj->RATE));
                                $discountpct = empty(trim($obj->DISCOUNT))? 0 : (float)trim($obj->DISCOUNT);
                                
                                if (strtoupper(trim($pricevatinclusive)) == 'YES') {
                                    //Use the figures are they come from the ERP
                                    $total = ($qty * $unit);//??
                                    
                                    $discount = ($discountpct/100) * $total;
                                    
                                    /**
                                     * Modification Date: 2021-01-26
                                     * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                                     * */
                                    //$gross = $total - $discount;
                                    $gross = $total;
                                    
                                    $discount = (-1) * $discount;
                                    
                                    $tax = ($gross/($rate + 1)) * $rate; //??
                                    
                                    $net = $gross - $tax;
                                } elseif (strtoupper(trim($pricevatinclusive)) == 'NO') {
                                    //Manually calculate figures
                                    $this->logger->write("Api : uploadcreditnote() : Manually calculating tax", 'r');
                                    
                                    if ($rate > 0) {
                                        $unit = $unit * ($rate + 1);
                                    }
                                    
                                    $total = ($qty * $unit);//??
                                    
                                    $discount = ($discountpct/100) * $total;
                                    
                                    /**
                                     * Modification Date: 2021-01-26
                                     * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                                     * */
                                    //$gross = $total - $discount;
                                    $gross = $total;
                                    
                                    $discount = (-1) * $discount;
                                    
                                    $tax = ($gross/($rate + 1)) * $rate; //??
                                    
                                    $net = $gross - $tax;
                                }
                                
                                /********************************END CALCULATIONS HERE******************************************/
                                if ($this->vatRegistered == 'N') {
                                    $tax = 0;
                                    $taxcategory = NULL;
                                    $taxcode = NULL;
                                }
                                
                                $netamount = $netamount + $net;
                                $taxamount = $taxamount + $tax;
                                
                                $grossamount = $grossamount + $gross;
                                $itemcount = $itemcount + 1;
                                
                                /**
                                 * Replace the INV currency with the product currency
                                 */
                                $creditnotedetails['currency'] = empty(trim($obj->STOCKITEMCURRENCY))? $this->util->getcurrency(trim($this->appsettings['ERPBASECURRENCY'])) : $this->util->getcurrency(trim($obj->STOCKITEMCURRENCY));
                                
                                /**
                                 * Author: francis.lubanga@gmail.com
                                 * Modification Date: 2021-02-28
                                 * Description: Resolving EFRIS error code 1427 - goodsDetails-->item:Must be the same as the original invoice!Collection index:0
                                 */
                                
                                /*Reset the order number*/
                                $ordernumber = NULL;
                                
                                try {
                                    $o_data = array ();
                                    $r = $this->db->exec(array('SELECT g.ordernumber "ordernumber" FROM tblgooddetails g JOIN tblinvoices i ON i.gooddetailgroupid = g.groupid AND i.einvoicenumber = "' . $oriinvoiceno . '" WHERE TRIM(g.itemcode) = "' . trim($obj->PRODUCTCODE) . '"'));
                                    $this->logger->write($this->db->log(TRUE), 'r');
                                    foreach ( $r as $set ) {
                                        $o_data [] = $set;
                                    }
                                    
                                    $ordernumber = $o_data[0]['ordernumber'];
                                    $this->logger->write("Api : uploadcreditnote() : The order number for product " . trim($obj->STOCKITEMNAME) . " is: " . $ordernumber, 'r');
                                } catch (Exception $e) {
                                    $this->logger->write("Api : uploadcreditnote() : The operation to retrieve the order number was not successful. The error messages is " . $e->getMessage(), 'r');
                                }
                                
                                $goods[] = array(
                                    'groupid' => NULL,
                                    'item' => trim($obj->STOCKITEMNAME),
                                    'itemcode' => trim($obj->PRODUCTCODE),
                                    'qty' => $qty,
                                    'unitofmeasure' => $this->util->mapmeasureunit(trim($obj->BUOM)),
                                    'unitprice' => $unit,
                                    'total' => $total,
                                    'taxid' => $taxid,
                                    'taxrate' => $rate,
                                    'tax' => $tax,
                                    'discounttotal' => $discount,
                                    'discounttaxrate' => $rate,
                                    'discountpercentage' => $discountpct,
                                    'ordernumber' => empty($ordernumber)? NULL : $ordernumber,
                                    'discountflag' => empty(trim($obj->DISCOUNT))? '2' : '1',
                                    'deemedflag' => (strtoupper(trim($deemedflag)) == 'NO'? '2' : '1'),
                                    'exciseflag' => NULL,
                                    'categoryid' => NULL,
                                    'categoryname' => NULL,
                                    'goodscategoryid' => $this->util->mapcommodity(trim($obj->COMMODITYCATEGORYCODE)),
                                    'goodscategoryname' => trim($obj->COMMODITYCATEGORYCODE),
                                    'exciserate' => NULL,
                                    'exciserule' => NULL,
                                    'excisetax' => NULL,
                                    'pack' => NULL,
                                    'stick' => NULL,
                                    'exciseunit' => NULL,
                                    'excisecurrency' => NULL,
                                    'exciseratename' => NULL,
                                    'taxdisplaycategory' => $taxdisplaycategory,
                                    'taxcategory' => $taxcategory,
                                    'taxcategoryCode' => $taxcode,
                                    'unitofmeasurename' => trim($obj->BUOM)
                                );
                                
                                
                                
                                if ($this->vatRegistered == 'Y') {
                                    $taxes[] = array(
                                        'discountflag' => empty(trim($obj->DISCOUNT))? '2' : '1',
                                        'discounttotal' => $discount,
                                        'discounttaxrate' => $rate,
                                        'discountpercentage' => $discountpct,
                                        'd_netamount' => NULL,
                                        'd_taxamount' => NULL,
                                        'd_grossamount' => NULL,
                                        'groupid' => NULL,
                                        'goodid' => NULL,
                                        'taxdisplaycategory' => $taxdisplaycategory,
                                        'taxcategory' => $taxcategory,
                                        'taxcategoryCode' => $taxcode,
                                        'netamount' => $net,
                                        'taxrate' => $rate,
                                        'taxamount' => $tax,
                                        'grossamount' => $gross,
                                        'exciseunit' => NULL,
                                        'excisecurrency' => NULL,
                                        'taxratename' => $taxname,
                                        'taxdescription' => $taxdescription
                                    );
                                }
                            }
                            
                            
                            /*$payments[] = array(
                             'groupid' => NULL,
                             'paymentmode' => NULL,
                             'paymentmodename' => NULL,
                             'paymentamount' => 0,
                             'ordernumber' => NULL
                             );*/
                        }
                        
                        
                        $this->logger->write("Api : uploadcreditnote() : The GOODS count: " . sizeof($goods), 'r');
                        $this->logger->write("Api : uploadcreditnote() : The TAX count: " . sizeof($taxes), 'r');
                        $this->logger->write("Api : uploadcreditnote() : The PAYMENTS count: " . sizeof($payments), 'r');
                        
                        $data = $this->util->uploadcreditnote($this->userid_u, $buyer, $creditnotedetails, $goods, $payments, $taxes);
                        $data = json_decode($data, true);
                        
                        if (isset($data['returnCode'])){
                            $this->logger->write("Api : uploadcreditnote() : The operation to upload the credit note not successful. The error message is " . $data['returnMessage'], 'r');
                            $this->message = $data['returnMessage'];
                            $this->code = $data['returnCode'];
                            
                            /**
                             * If the credit note passed the duplicate check due to an error or incomplete parameters, but EFRIS determines that it is a duplicate invoice,
                             * We insert it into the database?
                             */
                            
                            $body = $this->code . ' : ' . $this->message;
                            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                        } else {//{"referenceNo":"21PL010175571"}
                            
                            if (isset($data['referenceNo'])){
                                $ref = $data['referenceNo']; //21PL010073993
                                $creditnotedetails['referenceno'] = $data['referenceNo'];
                            }
                            
                            $inv_status = $this->util->createcreditnote($creditnotedetails, $goods, $taxes, $buyer, $this->userid_u);
                            
                            if ($inv_status) {
                                $this->logger->write("Api : uploadcreditnote() : The credit note was created on eTW successfully", 'r');
                            } else {
                                $this->logger->write("Api : uploadcreditnote() : The credit note was NOT created on eTW", 'r');
                            }
                            
                            $this->message = 'The operation to upload the credit note was successful';
                            $this->code = '000';
                        }
                    } else {
                        $this->logger->write("Api : uploadcreditnote() : The credit note has already been uploaded into EFRIS", 'r');
                        $this->message = 'The credit note has already been uploaded into EFRIS';
                        $this->code = '99';
                        
                        /*START OF EFRIS C/N QUERY*/
                        //Fetch details of the newly uploaded credit note
                        $n_data = $this->util->downloadcreditnote($this->userid_u, $inv_check->referenceno);//will return JSON.
                        //var_dump($data);
                        
                        $n_data = json_decode($n_data, true);
                        //var_dump($n_data);
                        
                        
                        if(isset($n_data['records'])){
                            $antifakeCode = '';
                            $invoiceNo = '';
                            $issuedDate = date("Y-m-d", strtotime($inv_check->applicationtime));
                            $issuedTime = date("Y-m-d H:i:s", strtotime($inv_check->applicationtime));
                            $issuedDatePdf = date("Y-m-d H:i:s", strtotime($inv_check->applicationtime));
                            $oriInvoiceId = '';
                            $isInvalid = '';
                            $isRefund = '';
                            $grossAmount = 0;
                            $itemCount = 0;
                            $netAmount = 0;
                            $qrCode = '';
                            $taxAmount = 0;
                            $modeCode = '';
                            $modeName = '';
                            $grossAmountWords = '';
                            $oriGrossAmount = 0;
                            $currencyRate = 1;
                            
                            foreach($n_data['records'] as $elem){
                                
                                $i_data = $this->util->downloadinvoice($this->userid_u, $elem['invoiceNo']);
                                $i_data = json_decode($i_data, true);
                                
                                /*START OF INVOICE BLOCK*/
                                if (isset($i_data['basicInformation'])){
                                    $antifakeCode = $i_data['basicInformation']['antifakeCode']; //32966911991799104051
                                    $invoiceId = $i_data['basicInformation']['invoiceId']; //3257429764295992735
                                    $invoiceNo = $i_data['basicInformation']['invoiceNo']; //3120012276043
                                    
                                    $issuedDate = $i_data['basicInformation']['issuedDate']; //18/09/2020 17:14:12
                                    $issuedDate = str_replace('/', '-', $issuedDate);//Replace / with -
                                    $issuedDate = date("Y-m-d H:i:s", strtotime($issuedDate));
                                    
                                    $issuedTime = $i_data['basicInformation']['issuedDate']; //18/09/2020 17:14:12
                                    $issuedTime = str_replace('/', '-', $issuedTime);//Replace / with -
                                    $issuedTime = date("Y-m-d H:i:s", strtotime($issuedTime));
                                    
                                    $issuedDatePdf = $i_data['basicInformation']['issuedDatePdf']; //318/09/2020 17:14:12
                                    $issuedDatePdf = str_replace('/', '-', $issuedDatePdf);//Replace / with -
                                    $issuedDatePdf = date("Y-m-d H:i:s", strtotime($issuedDatePdf));
                                    
                                    $oriInvoiceId = $i_data['basicInformation']['oriInvoiceId'];//1
                                    $isInvalid = $i_data['basicInformation']['isInvalid'];//1
                                    $isRefund = $i_data['basicInformation']['isRefund'];//1
                                    $currencyRate = $i_data['basicInformation']['currencyRate'];
                                    
                                    $invoiceid = $invoiceId;
                                    //$invoicenumber = $invoiceNo;
                                    $issueddate = $issuedDate;
                                    $fdn = $antifakeCode;
                                    
                                }
                                
                                if (isset($i_data['summary'])){
                                    $grossAmount = $i_data['summary']['grossAmount']; //832000
                                    $itemCount = $i_data['summary']['itemCount']; //1
                                    $netAmount = $i_data['summary']['netAmount']; //705084.75
                                    $qrCode = $i_data['summary']['qrCode']; //020000001149IC1200122760430004F588000000C1A8450A20A021D534A1000121462A1000094968~MM INTERGRATED STEEL MILLS (UGANDA) LIMITED~Ediomu & Company~Kiboko Galv Corr. Sheet 36G
                                    $taxAmount = $i_data['summary']['taxAmount'];//126915.25
                                    $modeCode = $i_data['summary']['modeCode'];//0
                                    $oriGrossAmount = $i_data['summary']['oriGrossAmount'];//19556.48
                                    
                                    $mode = new modes($this->db);
                                    $mode->getByCode($modeCode);
                                    $modeName = $mode->name;//online
                                    
                                    $f = new \NumberFormatter("en", NumberFormatter::SPELLOUT);
                                    $grossAmountWords = $f->format($grossAmount);//two million,
                                    
                                    $qr = $qrCode;
                                    
                                }
                                /*END OF INVOICE BLOCK*/
                                
                                $refundInvoiceNo = $elem['invoiceNo'];
                                $approveStatusCode = $elem['approveStatus'];
                                $applicationTime = $elem['applicationTime']; //28/09/2020 00:43:29
                                $referenceNo = $elem['referenceNo']; //21PL010073993
                                $oriInvoiceNo = $elem['oriInvoiceNo']; //120014732476
                                
                                $applicationTime = str_replace('/', '-', $applicationTime);//Replace / with -
                                $applicationTime = date("Y-m-d H:i:s", strtotime($applicationTime));
                                
                                $grossAmount = $elem['grossAmount'];
                                $totalAmount = $elem['totalAmount'];
                                //$refundIssuedDate = $elem['refundIssuedDate'];
                                $refundIssuedDate = $applicationTime;//28-09-2020 00:43:29
                                $appId = $elem['id'];
                                
                                try{
                                    $this->db->exec(array('UPDATE tblcreditnotes SET antifakecode = "' . $antifakeCode .
                                        '", einvoiceid = "' . $invoiceId .
                                        '", einvoicenumber = "' . $invoiceNo .
                                        '", issueddate = "' . $issuedDate .
                                        '", issuedtime = "' . $issuedTime .
                                        '", issueddatepdf = "' . $issuedDatePdf .
                                        '", oriinvoiceid = "' . $oriInvoiceId .
                                        '", isinvalid = "' . $isInvalid .
                                        '", isrefund = "' . $isRefund .
                                        '", grossamount = ' . $grossAmount .
                                        ', itemcount = ' . $itemCount .
                                        ', netamount = ' . $netAmount .
                                        ', einvoicedatamatrixcode = "' . $qrCode .
                                        '", taxamount = ' . $taxAmount .
                                        ', modecode = "' . $modeCode .
                                        '", modename = "' . $modeName .
                                        '", grossamountword = "' . $grossAmountWords .
                                        '", origrossamount = ' . $oriGrossAmount .
                                        ', refundinvoiceno = "' . $refundInvoiceNo .
                                        '", approvestatus = "' . $approveStatusCode .
                                        '", grossamount = "' . $grossAmount .
                                        '", totalamount = "' . $totalAmount .
                                        '", issueddate = "' . $refundIssuedDate .
                                        '", issuedtime = "' . $refundIssuedDate .
                                        '", creditnoteapplicationid = "' . $appId .
                                        '", applicationtime = "' . $applicationTime .
                                        '", currencyRate = ' . $currencyRate .
                                        ', modifieddt = NOW(), modifiedby = ' . $this->userid .
                                        ' WHERE referenceno = "' . $inv_check->referenceno . '"'));
                                    
                                    $this->logger->write($this->db->log(TRUE), 'r');
                                } catch (Exception $e) {
                                    $this->logger->write("Api : uploadcreditnote() : Failed to update the table tblcreditnotes. The error message is " . $e->getMessage(), 'r');
                                }
                                
                                if ($referenceNo = $inv_check->referenceno) {
                                    $invoicenumber = $oriInvoiceNo;
                                    $ref = $referenceNo;
                                    //$appstatus = $this->util->decodeapprovestatus($approveStatusCode);
                                }
                                
                            }
                            
                            //$this->message = 'The credit note was retrived successfully';
                            //$this->code = '000';
                            
                        } elseif (isset($n_data['returnCode'])){
                            $this->logger->write("Api : uploadcreditnote() : The operation to download the credit note not successful. The error message is " . $n_data['returnMessage'], 'r');
                            //$this->message = $n_data['returnMessage'];
                            //$this->code = $n_data['returnCode'];
                        } else {
                            $this->logger->write("Api : uploadcreditnote() : The operation to download the credit note not successful.", 'r');
                            //$this->message = 'The operation was not successful';
                            //$this->code = '1005';
                        }
                        /*END OF EFRIS C/N QUERY*/
                        
                        $creditnoteid = empty($inv_check->einvoiceid)? $invoiceid : $inv_check->einvoiceid;
                        $creditnotenumber = empty($inv_check->oriinvoiceno)? $invoicenumber : $inv_check->oriinvoiceno;
                        $issueddate = empty($inv_check->issuedtime)? $issuedTime : $inv_check->issuedtime;
                        $fdn = empty($inv_check->antifakecode)? $antifakeCode : $inv_check->antifakecode;
                        $qr = empty($inv_check->einvoicedatamatrixcode)? $qrCode : $inv_check->einvoicedatamatrixcode;
                        $ref = empty($inv_check->referenceno)? $ref : $inv_check->referenceno;
                    }
                    
                    $activity = 'UPLOADCREDITNOTE: ' . $vchnumber . ': ' . $vchref . ': ' . $this->message;
                    /*$windowsuser = trim($xml->WINDOWSUSER);
                     $ipaddress = trim($xml->IPADDRESS);
                     $macaddress = trim($xml->MACADDRESS);
                     $systemname = trim($xml->SYSTEMNAME);*/
                    $this->util->createerpauditlog($this->userid_u, $activity, $windowsuser, $ipaddress, $macaddress, $systemname, $xml);
                } else {
                    $this->logger->write("Api : uploadcreditnote() : unexpected voucher type name. Please name your voucher type as per standard", 'r');
                    $this->message = "Unexpected voucher type name. Please name your voucher type as per standard";
                    $this->code = '8452';
                    
                    $activity = 'UPLOADCREDITNOTE: ' . $vchnumber . ': ' . $vchref . ': ' . $this->message;
                    $this->util->createerpauditlog($this->userid_u, $activity, $windowsuser, $ipaddress, $macaddress, $systemname, $xml);
                    
                    $body = $this->code . ' : ' . $this->message;
                    $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);                   
                }                                
            } else {
                $this->logger->write("Api : uploadcreditnote() : The user is not allowed to perform this function", 'r');
                $this->message = "The user is not allowed to perform this function";
                $this->code = '0099';
            }
        }
               
        
        // prepare xml response
        $this->response  =  "<ENVELOPE>";
        $this->response .=  "<HEADER>";
        $this->response .=  "<VERSION>1</VERSION>";
        $this->response .=  "<STATUS>1</STATUS>";
        $this->response .=  "</HEADER>";
        $this->response .=  "<BODY>";
        $this->response .=  "<DATA>";
        $this->response .=  "<RESPONSE>";
        $this->response .=  "<RETURNCODE>" . htmlspecialchars($this->code) . "</RETURNCODE>";
        $this->response .=  "<RETURNMESSAGE>" . htmlspecialchars($this->message) . "</RETURNMESSAGE>";
        $this->response .=  "<INVID>". htmlspecialchars($creditnoteid) ."</INVID>";
        $this->response .=  "<INVNO>". htmlspecialchars($creditnotenumber) ."</INVNO>";
        $this->response .=  "<ISSUEDT>". htmlspecialchars($issueddate) ."</ISSUEDT>";
        $this->response .=  "<FDN>". htmlspecialchars($fdn) ."</FDN>";
        $this->response .=  "<QRCODE>". htmlspecialchars($qr) ."</QRCODE>";
        $this->response .=  "<REFERENCE>". htmlspecialchars($ref) ."</REFERENCE>";
        $this->response .=  "</RESPONSE>";
        $this->response .=  "</DATA>";
        $this->response .=  "</BODY>";
        $this->response .=  "</ENVELOPE>";
        
        $len = strlen($this->response);
        header ("CONTENT-LENGTH:".$len);
        print $this->response;
        return;
    }
    
    
    /**
     *	@name uploaddebitnote
     *  @desc upload debitnote
     *	@return string response
     *	@param NULL
     **/
    function uploaddebitnote(){
        $operation = NULL; //tblevents
        $permission = 'UPLOADDEBITNOTE'; //tblpermissions
        $event = NULL; //tblevents
        $eventnotification = NULL; //tbleventnotifications
        
        if ($this->errorcode) {
            $this->message = $this->errormessage;
            $this->code = $this->errorcode;
            
            $body = $this->code . ' : ' . $this->message;
            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
        } else {
            $debitnoteid = '';
            $debitnotenumber = '';
            $issueddate = '';
            $fdn = '';
            $qr = '';
            
            if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
                date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
            }
            
            
            /**
             *
             * Steps to follow when uploading to EFRIS
             * 0. Grab the xml body from the ERP
             * 1. Generate a groupid for the following;
             * 1.1 Goods
             * 1.2 Taxes
             * 1.3 Payments
             * 2. Create the following arrays
             * 2.1 buyer
             * 2.2 debitnotedetails
             * 2.3 goods
             * 2.4 payments
             * 2.5 taxes
             * 3. Send the items in [1] to the uploaddebitnote API
             */
            
            $xml = simplexml_load_string($this->xml);
            
            $tcsdetails = new tcsdetails($this->db);
            $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
            
            $companydetails = new organisations($this->db);
            $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
            
            $devicedetails = new devices($this->db);
            $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
            
            $vchtype = trim($xml->VOUCHERTYPE);
            $vchtypename = trim($xml->VOUCHERTYPENAME);
            $vchnumber = trim($xml->VOUCHERNUMBER);
            $vchref = trim($xml->VOUCHERREF);
            
            $this->logger->write("Api : uploaddebitnote() : The userid is: " . $this->userid_u, 'r');
            $this->logger->write("Api : uploaddebitnote() : Checking permissions", 'r');
            if ($this->userpermissions[$permission]) {
                $branch = new branches($this->db);
                $branch->getByID($this->userbranch_u);
                
                
                /**
                 * Check if D/N is already issued
                 *
                 */

                $inv_check = new DB\SQL\Mapper($this->db, 'tbldebitnotes');
                $inv_check->load(array('TRIM(erpdebitnoteid)=? AND TRIM(vouchertype)=? AND TRIM(vouchertypename)=?', $vchnumber, $vchtype, $vchtypename));
                $this->logger->write($this->db->log(TRUE), 'r');
                
                if($inv_check->dry ()){
                    $this->logger->write("Api : uploaddebitnote() : The debitnote does not exist on eTW. Proceed and upload", 'r');
                    
                    
                    if(trim($vchref) !== '' || ! empty(trim($vchref))) {
                        $this->logger->write("Api : uploaddebitnote() : The associated original invoice was supplied", 'r');
                        
                        $orig_inv = new DB\SQL\Mapper($this->db, 'tblinvoices');
                        $orig_inv->load(array('TRIM(erpinvoiceid)=?', $vchref));
                        $this->logger->write($this->db->log(TRUE), 'r');
                        
                        if ($orig_inv->dry()) {
                            $this->logger->write("Api : uploaddebitnote() : There is no associated original invoice in the database", 'r');
                            $oriinvoiceid = NULL;
                            $oriinvoiceno = NULL;
                        } else {
                            $oriinvoiceid = $orig_inv->einvoiceid;
                            $oriinvoiceno = $orig_inv->einvoicenumber;
                            
                            /**
                             * Author: francis.lubanga@gmail.com
                             * Date: 2021-02-28
                             * Description: Resolve EFRIS error code 2783: oriInvoiceNo: cannot be empty!
                             *
                             *
                             * 1. Check if oriinvoiceid is empty
                             * 2. If oriinvoiceid is NOT empty, then ignore
                             * 3. If it is empty, then query EFRIS and retrieve it
                             * 4. Update the eTW record for this invoice
                             */
                            
                            if(trim($oriinvoiceid) == '' || empty(trim($oriinvoiceid))) {
                                $this->logger->write("Api : uploaddebitnote() : The oriinvoiceid is empty", 'r');
                                
                                if(trim($oriinvoiceno) == '' || empty(trim($oriinvoiceno))) {
                                    $this->logger->write("Api : uploaddebitnote() : The oriinvoiceno is empty", 'r');
                                } else {
                                    $this->logger->write("Api : uploaddebitnote() : The oriinvoiceno is NOT empty", 'r');
                                    $i_data = $this->util->downloadinvoice($this->userid, $oriinvoiceno);
                                    $i_data = json_decode($i_data, true);
                                    
                                    /*START OF INVOICE BLOCK*/
                                    if (isset($i_data['basicInformation'])){
                                        $TempInvoiceId = $i_data['basicInformation']['invoiceId']; //3257429764295992735
                                        $TempInvoiceNo = $i_data['basicInformation']['invoiceNo']; //3120012276043
                                        
                                        if (trim($TempInvoiceNo) == trim($oriinvoiceno)) {
                                            $oriinvoiceid = $TempInvoiceId;
                                        }
                                    }
                                    /*END INVOICE BLOCK*/
                                    
                                    try{
                                        $this->db->exec(array('UPDATE tblinvoices SET einvoiceid = "' . $oriinvoiceid . '", modifieddt = NOW(), modifiedby = ' . $this->userid . ' WHERE einvoicenumber = "' . $oriinvoiceno . '"'));
                                        $this->logger->write($this->db->log(TRUE), 'r');
                                    } catch (Exception $e) {
                                        $this->logger->write("Api : uploaddebitnote() : Failed to update the table tblinvoices. The error message is " . $e->getMessage(), 'r');
                                    }
                                }
                            } else {
                                $this->logger->write("Api : uploaddebitnote() : The oriinvoiceid is not empty", 'r');
                            }
                        }
                    } else {
                        $this->logger->write("Api : uploaddebitnote() : The associated original invoice was not supplied", 'r');
                        $oriinvoiceid = NULL;
                        $oriinvoiceno = NULL;
                    }
                    
                    
                    
                    $reasoncode = NULL;
                    $reason = NULL;
                    $remarks = '';
                    
                    foreach ($xml->REASONS->REASON as $obj){
                        $reasoncode = trim($obj->REASONCODE);
                        $reason = trim($obj->REASON);
                        $remarks = trim($obj->REMARKS);
                    }
                    
                    $debitnotedetails = array(
                        'gooddetailgroupid' => NULL,
                        'taxdetailgroupid' => NULL,
                        'paymentdetailgroupid' => NULL,
                        'erpdebitnoteid' => trim($xml->VOUCHERNUMBER),
                        'erpdebitnoteno' => NULL,
                        'erpinvoiceid' => trim($xml->VOUCHERREF),
                        'erpinvoiceno' => NULL,
                        'antifakecode' => NULL,
                        'deviceno' => trim($devicedetails->deviceno),
                        'issueddate' => NULL,
                        'issuedtime' => NULL,
                        'operator' => trim($xml->ERPUSER),
                        'currency' => $this->util->getcurrency(trim($xml->CURRENCY)),
                        'oriinvoiceid' => $oriinvoiceid,
                        'oriinvoiceno' => $oriinvoiceno,
                        'invoicetype' => "4", /*4-Debit Note*/
                        'invoicekind' => ($this->vatRegistered == 'Y')? "1" : "2", /*1-Invoice*/
                        'datasource' => $this->appsettings['WESERVICEDS'],
                        'invoiceindustrycode' => $this->util->mapindustrycode(trim($xml->INDUSTRYCODE)),
                        'einvoiceid' => NULL,
                        'einvoicenumber' => NULL,
                        'einvoicedatamatrixcode' => NULL,
                        'isbatch' => '0',
                        'netamount' => NULL,
                        'taxamount' => NULL,
                        'grossamount' => NULL,
                        'origrossamount' => NULL,
                        'itemcount' => NULL,
                        'modecode' => '1',
                        'modename' => NULL,
                        'remarks' => $remarks,
                        'buyerid' => NULL,
                        'sellerid' => $this->appsettings['SELLER_RECORD_ID'],
                        'issueddatepdf' => NULL,
                        'grossamountword' => NULL,
                        'isinvalid' => 0,
                        'isrefund' => 0,
                        'vchtype' => trim($xml->VOUCHERTYPE),
                        'vchtypename' => trim($xml->VOUCHERTYPENAME),
                        'reasoncode' => $this->util->mapreasoncode($reasoncode),
                        'reason' => $reason,
                    );
                    
                    $buyer = array(
                        'tin' => trim($xml->BUYERTIN),
                        'ninbrn' => trim($xml->BUYERNINBRN),
                        'PassportNum' => trim($xml->BUYERPASSPORTNUM),
                        'legalname' => trim($xml->BUYERLEGALNAME),
                        'businessname' => trim($xml->BUSINESSNAME),
                        'address' => trim($xml->BUYERADDRESS),
                        'mobilephone' => trim($xml->MOBILEPHONE),
                        'linephone' => trim($xml->BUYERLINEPHONE),
                        'emailaddress' => trim($xml->BUYEREMAIL),
                        'placeofbusiness' => trim($xml->BUYERPLACEOFBUSI),
                        'type' => $this->util->mapbuyertypecode(trim($xml->BUYERTYPE)),
                        'citizineship' => trim($xml->BUYERCITIZENSHIP),
                        'sector' => trim($xml->BUYERSECTOR),
                        'referenceno' => trim($xml->VOUCHERNUMBER) == ''? trim($xml->BUYERREFERENCENO) : trim($xml->VOUCHERREF) . trim($xml->VOUCHERNUMBER),
                        'datasource' => $this->appsettings['WESERVICEDS']
                    );
                    
                    $this->logger->write("Api : uploadproduct() : The BUYERTYPE are " . trim($xml->BUYERTYPE), 'r');
                    //$this->logger->write("Api : uploadproduct() : The INVENTORIES are " . trim($xml->INVENTORIES), 'r');
                    
                    $goods = array();
                    $taxes = array();
                    $payments = array();
                    
                    $deemedflag = 'NO';
                    
                    //$vatapplicationlevel = trim($xml->VATAPPLICATIONLEVEL);//Ledger
                    $pricevatinclusive = trim($xml->PRICEVATINCLUSIVE);//No
                    //$defaultvatrate = trim($xml->DEFAULTVATRATE);//18
                    $buyertype = $this->util->mapbuyertypecode(trim($xml->BUYERTYPE));
                    $industrycode = $this->util->mapindustrycode(trim($xml->INDUSTRYCODE));
                    
                    $netamount = 0;
                    $taxamount = 0;
                    $grossamount = 0;
                    $itemcount = 0;
                    
                    /**
                     * Author: francis.lubanga@gmail.com
                     * Date: 2024-05-20
                     * Description: Handle fees/taxes which have no rates, and are passed as invoice line items
                     */
                    $mapped_fees = array();
                    
                    if(trim($this->appsettings['CHECK_FEE_MAP_FLAG']) == '1'){
                        $this->logger->write("Utilities : uploadinvoice() : The check fees mapping is on", 'r');
                        
                        $feesmapping = new feesmapping($this->db);
                        $feesmappings = $feesmapping->all();
                        
                        foreach ($feesmappings as $f_obj) {
                            $this->logger->write("Utilities : uploadinvoice() : The fee code is " . $f_obj['feecode'], 'r');
                            $this->logger->write("Utilities : uploadinvoice() : The product code is " . $f_obj['productcode'], 'r');
                            
                            $mapped_fees[] = array(
                                'id' => empty($f_obj['id'])? '' : $f_obj['id'],
                                'feecode' => empty($f_obj['feecode'])? '' : $f_obj['feecode'],
                                'productcode' => empty($f_obj['productcode'])? '' : $f_obj['productcode'],
                                'amount' => 0
                            );
                        }
                    }
                    
                    if (isset($xml->INVENTORIES->INVENTORY)) {
                        $this->logger->write("Api : uploaddebitnote() : This is an inventory/goods debitnote", 'r');
                        
                        foreach ($xml->INVENTORIES->INVENTORY as $obj){
                            $this->logger->write("Api : uploaddebitnote() : The STOCKITEMNAME is " . $obj->STOCKITEMNAME, 'r');
                            $this->logger->write("Api : uploaddebitnote() : The RATE is " . $obj->RATE, 'r');
                            
                            $ii = 0;
                            $product_skip_flag = 0;
                            
                            foreach ($mapped_fees as $m_obj) {
                                if(trim($m_obj['productcode']) == trim($obj->PRODUCTCODE)){
                                    $this->logger->write("Api : uploaddebitnote() : The product " . $obj->PRODUCTCODE . " is mapped to a tax/fee " . $m_obj['feecode'], 'r');
                                    
                                    $f_qty = $this->util->removeunitsfromqty(trim($obj->BILLEDQTY));
                                    $f_unit = $this->util->removeunitsfromrate(trim($obj->RATE));
                                    
                                    $mapped_fees[$ii]['amount'] = ($f_qty * $f_unit); # Might be problematic. Consider "foreach ($mapped_fees as &$m_obj) {"
                                    $product_skip_flag = 1;
                                    break;
                                }
                                
                                $ii = $ii + 1;
                            }
                            
                            if ($product_skip_flag == 1){
                                continue;
                            }
                            
                            /**
                             * 1. Get the following variables
                             * 1.1 Deemed Flag (Yes, No)
                             * 1.2 Industry (General, Export, Import, Imported Service)
                             * 1.3 Buyer Type (B2G/B2B, B2C, Foreigner)
                             * 2. Choose the tax rates based on the follwowing criteria
                             * 2.1 If Deemed Flag = No & Industry = General & Buyer Type = B2C or B2G/B2B then use the standard rate
                             * */
                            
                            
                            $this->logger->write("Api : uploaddebitnote() : The PRICE INCLUSIVE FLAG is " . strtoupper(trim($pricevatinclusive)), 'r');
                            
                            /********************************START CALCULATIONS HERE******************************************/
                            $taxid = $this->util->getinvoicetaxrate_v2($industrycode, $buyertype, trim($obj->PRODUCTCODE), trim($xml->BUYERTIN), $this->appsettings['OVERRIDE_TAXRATE_FLAG'], $this->appsettings['TAXPAYER_CHECK_FLAG']);
                            $this->logger->write("Api : uploaddebitnote() : The computed TAXID is " . $taxid, 'r');
                            
                            if (!$taxid) {
                                $taxid = $this->appsettings['STANDARDTAXRATE'];
                            }
                            
                            
                            if ($taxid == $this->appsettings['DEEMEDTAXRATE']) {
                                $deemedflag = 'YES';
                            } else {
                                $deemedflag = 'NO';
                            }
                            
                            $this->logger->write("Api : uploaddebitnote() : The final TAXID is " . $taxid, 'r');
                            
                            $tr = new taxrates($this->db);
                            $tr->getByID($taxid);
                            $taxcode = $tr->code;
                            $taxname = $tr->name;
                            $taxcategory = $tr->category;
                            $taxdisplaycategory = $tr->displayCategoryCode;
                            $taxdescription = $tr->description;
                            $rate = $tr->rate? $tr->rate : 0;
                            $qty = $this->util->removeunitsfromqty(trim($obj->BILLEDQTY));
                            $unit = $this->util->removeunitsfromrate(trim($obj->RATE));
                            $discountpct = empty(trim($obj->DISCOUNT))? 0 : (float)trim($obj->DISCOUNT);
                            
                            if (strtoupper(trim($pricevatinclusive)) == 'YES') {
                                //Use the figures are they come from the ERP
                                $total = ($qty * $unit);//??
                                
                                $discount = ($discountpct/100) * $total;
                                
                                /**
                                 * Modification Date: 2021-01-26
                                 * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                                 * */
                                //$gross = $total - $discount;
                                $gross = $total;
                                
                                $discount = (-1) * $discount;
                                
                                $tax = ($gross/($rate + 1)) * $rate; //??
                                
                                $net = $gross - $tax;
                            } elseif (strtoupper(trim($pricevatinclusive)) == 'NO') {
                                //Manually calculate figures
                                $this->logger->write("Api : uploaddebitnote() : Manually calculating tax", 'r');
                                
                                if ($rate > 0) {
                                    $unit = $unit * ($rate + 1);
                                }
                                
                                $total = ($qty * $unit);//??
                                
                                $discount = ($discountpct/100) * $total;
                                
                                /**
                                 * Modification Date: 2021-01-26
                                 * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                                 * */
                                //$gross = $total - $discount;
                                $gross = $total;
                                
                                $discount = (-1) * $discount;
                                
                                $tax = ($gross/($rate + 1)) * $rate; //??
                                
                                $net = $gross - $tax;
                            }
                            
                            /********************************END CALCULATIONS HERE******************************************/
                            
                            if ($this->vatRegistered == 'N') {
                                $tax = 0;
                                $taxcategory = NULL;
                                $taxcode = NULL;
                            }
                            
                            $netamount = $netamount + $net;
                            $taxamount = $taxamount + $tax;
                            
                            $grossamount = $grossamount + $gross;
                            $itemcount = $itemcount + 1;
                            
                            /**
                             * Replace the INV currency with the product currency
                             */
                            $debitnotedetails['currency'] = empty(trim($obj->STOCKITEMCURRENCY))? $this->util->getcurrency(trim($this->appsettings['ERPBASECURRENCY'])) : $this->util->getcurrency(trim($obj->STOCKITEMCURRENCY));
                            
                            $goods[] = array(
                                'groupid' => NULL,
                                'item' => trim($obj->STOCKITEMNAME),
                                'itemcode' => trim($obj->PRODUCTCODE),
                                'qty' => $qty,
                                'unitofmeasure' => $this->util->mapmeasureunit(trim($obj->BUOM)),
                                'unitprice' => $unit,
                                'total' => $total,
                                'taxid' => $taxid,
                                'taxrate' => $rate,
                                'tax' => $tax,
                                'discounttotal' => $discount,
                                'discounttaxrate' => $rate,
                                'discountpercentage' => $discountpct,
                                'ordernumber' => NULL,
                                'discountflag' => empty(trim($obj->DISCOUNT))? '2' : '1',
                                'deemedflag' => (strtoupper(trim($deemedflag)) == 'NO'? '2' : '1'),
                                'exciseflag' => NULL,
                                'categoryid' => NULL,
                                'categoryname' => NULL,
                                'goodscategoryid' => $this->util->mapcommodity(trim($obj->COMMODITYCATEGORYCODE)),
                                'goodscategoryname' => trim($obj->COMMODITYCATEGORYCODE),
                                'exciserate' => NULL,
                                'exciserule' => NULL,
                                'excisetax' => NULL,
                                'pack' => NULL,
                                'stick' => NULL,
                                'exciseunit' => NULL,
                                'excisecurrency' => NULL,
                                'exciseratename' => NULL,
                                'taxdisplaycategory' => $taxdisplaycategory,
                                'taxcategory' => $taxcategory,
                                'taxcategoryCode' => $taxcode,
                                'unitofmeasurename' => trim($obj->BUOM),
                                'vatProjectId' => trim($xml->PROJECTID),
                                'vatProjectName' => trim($xml->PROJECTNAME)
                            );
                            
                            
                            
                            if ($this->vatRegistered == 'Y') {
                                $taxes[] = array(
                                    'discountflag' => empty(trim($obj->DISCOUNT))? '2' : '1',
                                    'discounttotal' => $discount,
                                    'discounttaxrate' => $rate,
                                    'discountpercentage' => $discountpct,
                                    'd_netamount' => NULL,
                                    'd_taxamount' => NULL,
                                    'd_grossamount' => NULL,
                                    'groupid' => NULL,
                                    'goodid' => NULL,
                                    'taxdisplaycategory' => $taxdisplaycategory,
                                    'taxcategory' => $taxcategory,
                                    'taxcategoryCode' => $taxcode,
                                    'netamount' => $net,
                                    'taxrate' => $rate,
                                    'taxamount' => $tax,
                                    'grossamount' => $gross,
                                    'exciseunit' => NULL,
                                    'excisecurrency' => NULL,
                                    'taxratename' => $taxname,
                                    'taxdescription' => $taxdescription
                                );
                            }
                        }
                        
                        
                        /*$payments[] = array(
                         'groupid' => NULL,
                         'paymentmode' => NULL,
                         'paymentmodename' => NULL,
                         'paymentamount' => 0,
                         'ordernumber' => NULL
                         );*/
                    }
                    
                    if (isset($xml->SERVICES->SERVICE)) {
                        $this->logger->write("Api : uploaddebitnote() : This is an service debitnote", 'r');
                        
                        foreach ($xml->SERVICES->SERVICE as $obj){
                            $this->logger->write("Api : uploaddebitnote() : The STOCKITEMNAME is " . $obj->STOCKITEMNAME, 'r');
                            $this->logger->write("Api : uploaddebitnote() : The RATE is " . $obj->RATE, 'r');
                            
                            $ii = 0;
                            $product_skip_flag = 0;
                            
                            foreach ($mapped_fees as $m_obj) {
                                if(trim($m_obj['productcode']) == trim($obj->PRODUCTCODE)){
                                    $this->logger->write("Api : uploaddebitnote() : The product " . $obj->PRODUCTCODE . " is mapped to a tax/fee " . $m_obj['feecode'], 'r');
                                    
                                    $f_qty = $this->util->removeunitsfromqty(trim($obj->BILLEDQTY));
                                    $f_unit = $this->util->removeunitsfromrate(trim($obj->RATE));
                                    
                                    $mapped_fees[$ii]['amount'] = ($f_qty * $f_unit); # Might be problematic. Consider "foreach ($mapped_fees as &$m_obj) {"
                                    $product_skip_flag = 1;
                                    break;
                                }
                                
                                $ii = $ii + 1;
                            }
                            
                            if ($product_skip_flag == 1){
                                continue;
                            }
                            
                            /**
                             * 1. Get the following variables
                             * 1.1 Deemed Flag (Yes, No)
                             * 1.2 Industry (General, Export, Import, Imported Service)
                             * 1.3 Buyer Type (B2G/B2B, B2C, Foreigner)
                             * 2. Choose the tax rates based on the follwowing criteria
                             * 2.1 If Deemed Flag = No & Industry = General & Buyer Type = B2C or B2G/B2B then use the standard rate
                             * */
                            
                            
                            $this->logger->write("Api : uploaddebitnote() : The PRICE INCLUSIVE FLAG is " . strtoupper(trim($pricevatinclusive)), 'r');
                            
                            /********************************START CALCULATIONS HERE******************************************/
                            $taxid = $this->util->getinvoicetaxrate_v2($industrycode, $buyertype, trim($obj->PRODUCTCODE), trim($xml->BUYERTIN), $this->appsettings['OVERRIDE_TAXRATE_FLAG'], $this->appsettings['TAXPAYER_CHECK_FLAG']);
                            $this->logger->write("Api : uploaddebitnote() : The computed TAXID is " . $taxid, 'r');
                            
                            if (!$taxid) {
                                $taxid = $this->appsettings['STANDARDTAXRATE'];
                            }
                            
                            
                            if ($taxid == $this->appsettings['DEEMEDTAXRATE']) {
                                $deemedflag = 'YES';
                            } else {
                                $deemedflag = 'NO';
                            }
                            
                            $this->logger->write("Api : uploaddebitnote() : The final TAXID is " . $taxid, 'r');
                            
                            $tr = new taxrates($this->db);
                            $tr->getByID($taxid);
                            $taxcode = $tr->code;
                            $taxname = $tr->name;
                            $taxcategory = $tr->category;
                            $taxdescription = $tr->description;
                            $rate = $tr->rate? $tr->rate : 0;
                            $qty = $this->util->removeunitsfromqty(trim($obj->BILLEDQTY));
                            $unit = $this->util->removecommasfromamount(trim($obj->RATE));
                            $discountpct = empty(trim($obj->DISCOUNT))? 0 : (float)trim($obj->DISCOUNT);
                            
                            if (strtoupper(trim($pricevatinclusive)) == 'YES') {
                                //Use the figures are they come from the ERP
                                $total = ($qty * $unit);//??
                                
                                $discount = ($discountpct/100) * $total;
                                
                                /**
                                 * Modification Date: 2021-01-26
                                 * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                                 * */
                                //$gross = $total - $discount;
                                $gross = $total;
                                
                                $discount = (-1) * $discount;
                                
                                $tax = ($gross/($rate + 1)) * $rate; //??
                                
                                $net = $gross - $tax;
                            } elseif (strtoupper(trim($pricevatinclusive)) == 'NO') {
                                //Manually calculate figures
                                $this->logger->write("Api : uploaddebitnote() : Manually calculating tax", 'r');
                                
                                if ($rate > 0) {
                                    $unit = $unit * ($rate + 1);
                                }
                                
                                $total = ($qty * $unit);//??
                                
                                $discount = ($discountpct/100) * $total;
                                
                                /**
                                 * Modification Date: 2021-01-26
                                 * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                                 * */
                                //$gross = $total - $discount;
                                $gross = $total;
                                
                                $discount = (-1) * $discount;
                                
                                $tax = ($gross/($rate + 1)) * $rate; //??
                                
                                $net = $gross - $tax;
                            }
                            
                            /********************************END CALCULATIONS HERE******************************************/
                            
                            if ($this->vatRegistered == 'N') {
                                $tax = 0;
                                $taxcategory = NULL;
                                $taxcode = NULL;
                            }
                            
                            $netamount = $netamount + $net;
                            $taxamount = $taxamount + $tax;
                            
                            $grossamount = $grossamount + $gross;
                            $itemcount = $itemcount + 1;
                            
                            /**
                             * Replace the INV currency with the product currency
                             */
                            $debitnotedetails['currency'] = empty(trim($obj->STOCKITEMCURRENCY))? $this->util->getcurrency(trim($this->appsettings['ERPBASECURRENCY'])) : $this->util->getcurrency(trim($obj->STOCKITEMCURRENCY));
                            
                            $goods[] = array(
                                'groupid' => NULL,
                                'item' => trim($obj->STOCKITEMNAME),
                                'itemcode' => trim($obj->PRODUCTCODE),
                                'qty' => $qty,
                                'unitofmeasure' => $this->util->mapmeasureunit(trim($obj->BUOM)),
                                'unitprice' => $unit,
                                'total' => $total,
                                'taxid' => $taxid,
                                'taxrate' => $rate,
                                'tax' => $tax,
                                'discounttotal' => $discount,
                                'discounttaxrate' => $rate,
                                'discountpercentage' => $discountpct,
                                'ordernumber' => NULL,
                                'discountflag' => empty(trim($obj->DISCOUNT))? '2' : '1',
                                'deemedflag' => (strtoupper(trim($deemedflag)) == 'NO'? '2' : '1'),
                                'exciseflag' => NULL,
                                'categoryid' => NULL,
                                'categoryname' => NULL,
                                'goodscategoryid' => $this->util->mapcommodity(trim($obj->COMMODITYCATEGORYCODE)),
                                'goodscategoryname' => trim($obj->COMMODITYCATEGORYCODE),
                                'exciserate' => NULL,
                                'exciserule' => NULL,
                                'excisetax' => NULL,
                                'pack' => NULL,
                                'stick' => NULL,
                                'exciseunit' => NULL,
                                'excisecurrency' => NULL,
                                'exciseratename' => NULL,
                                'taxdisplaycategory' => $taxdisplaycategory,
                                'taxcategory' => $taxcategory,
                                'taxcategoryCode' => $taxcode,
                                'unitofmeasurename' => trim($obj->BUOM),
                                'vatProjectId' => trim($xml->PROJECTID),
                                'vatProjectName' => trim($xml->PROJECTNAME)
                            );
                            
                            
                            
                            if ($this->vatRegistered == 'Y') {
                                $taxes[] = array(
                                    'discountflag' => empty(trim($obj->DISCOUNT))? '2' : '1',
                                    'discounttotal' => $discount,
                                    'discounttaxrate' => $rate,
                                    'discountpercentage' => $discountpct,
                                    'd_netamount' => NULL,
                                    'd_taxamount' => NULL,
                                    'd_grossamount' => NULL,
                                    'groupid' => NULL,
                                    'goodid' => NULL,
                                    'taxdisplaycategory' => $taxdisplaycategory,
                                    'taxcategory' => $taxcategory,
                                    'taxcategoryCode' => $taxcode,
                                    'netamount' => $net,
                                    'taxrate' => $rate,
                                    'taxamount' => $tax,
                                    'grossamount' => $gross,
                                    'exciseunit' => NULL,
                                    'excisecurrency' => NULL,
                                    'taxratename' => $taxname,
                                    'taxdescription' => $taxdescription
                                );
                            }
                        }
                        
                        
                        /*$payments[] = array(
                         'groupid' => NULL,
                         'paymentmode' => NULL,
                         'paymentmodename' => NULL,
                         'paymentamount' => 0,
                         'ordernumber' => NULL
                         );*/
                    }
                    
                    foreach ($mapped_fees as $m_obj) {
                        $this->logger->write("Api : uploaddebitnote() : Adding fees to the tax array", 'r');
                        
                        $tr = new taxrates($this->db);
                        $tr->getByCode($m_obj['feecode']);
                        $taxcode = $tr->code;
                        $taxname = $tr->name;
                        $taxcategory = $tr->category;
                        $taxdescription = $tr->description;
                        $taxdisplaycategory = $tr->displayCategoryCode;
                        
                        $taxes[] = array(
                            'discountflag' => '1',
                            'discounttotal' => NULL,
                            'discounttaxrate' => NULL,
                            'discountpercentage' => NULL,
                            'd_netamount' => NULL,
                            'd_taxamount' => NULL,
                            'd_grossamount' => NULL,
                            'groupid' => NULL,
                            'goodid' => NULL,
                            'taxdisplaycategory' => $taxdisplaycategory,
                            'taxcategory' => $taxcategory,
                            'taxcategoryCode' => $taxcode,
                            'netamount' => 0,
                            'taxrate' => $m_obj['amount'],
                            'taxamount' => $m_obj['amount'],
                            'grossamount' => $m_obj['amount'],
                            'exciseunit' => NULL,
                            'excisecurrency' => NULL,
                            'taxratename' => $taxname,
                            'taxdescription' => $taxdescription
                        );
                    }
                    
                    $this->logger->write("Api : uploaddebitnote() : The GOODS count: " . sizeof($goods), 'r');
                    $this->logger->write("Api : uploaddebitnote() : The TAX count: " . sizeof($taxes), 'r');
                    $this->logger->write("Api : uploaddebitnote() : The PAYMENTS count: " . sizeof($payments), 'r');
                    
                    $data = $this->util->uploaddebitnote($this->userid_u, $branch->uraid, $buyer, $debitnotedetails, $goods, $payments, $taxes);
                    $data = json_decode($data, true);
                    
                    if (isset($data['returnCode'])){
                        $this->logger->write("Api : uploaddebitnote() : The operation to upload the debitnote not successful. The error message is " . $data['returnMessage'], 'r');
                        //$this->util->createinappnotification(NULL, NULL, NULL, self::$module, self::$submodule, $operation, $event, $eventnotification, NULL, $this->f3->get('SESSION.id'), "The operation to upload the debitnote by " . $this->f3->get('SESSION.username') . " was not successful");
                        $this->message = $data['returnMessage'];
                        $this->code = $data['returnCode'];
                        
                        /**
                         * If the debit note passed the duplicate check due to an error or incomplete parameters, but EFRIS determines that it is a duplicate invoice,
                         * We insert it into the database?
                         */
                        
                        $body = $this->code . ' : ' . $this->message;
                        $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                    } else {
                        if (isset($data['basicInformation'])){
                            $antifakeCode = $data['basicInformation']['antifakeCode']; //32966911991799104051
                            $debitnoteId = $data['basicInformation']['invoiceId']; //3257429764295992735
                            $debitnoteNo = $data['basicInformation']['invoiceNo']; //3120012276043
                            
                            $issuedDate = $data['basicInformation']['issuedDate']; //18/09/2020 17:14:12
                            $issuedDate = str_replace('/', '-', $issuedDate);//Replace / with -
                            $issuedDate = date("Y-m-d H:i:s", strtotime($issuedDate));
                            
                            $issuedTime = $data['basicInformation']['issuedDate']; //18/09/2020 17:14:12
                            $issuedTime = str_replace('/', '-', $issuedTime);//Replace / with -
                            $issuedTime = date("Y-m-d H:i:s", strtotime($issuedTime));
                            
                            $issuedDatePdf = $data['basicInformation']['issuedDatePdf']; //318/09/2020 17:14:12
                            $issuedDatePdf = str_replace('/', '-', $issuedDatePdf);//Replace / with -
                            $issuedDatePdf = date("Y-m-d H:i:s", strtotime($issuedDatePdf));
                            
                            $oriInvoiceId = $data['basicInformation']['oriInvoiceId'];//1
                            $isInvalid = $data['basicInformation']['isInvalid'];//1
                            $isRefund = $data['basicInformation']['isRefund'];//1
                            $currencyRate = $data['basicInformation']['currencyRate'];
                            
                            $debitnoteid = $debitnoteId;
                            $debitnotenumber = $debitnoteNo;
                            $issueddate = $issuedDate;
                            $fdn = $antifakeCode;
                            
                            
                            
                            
                        }
                        
                        if (isset($data['summary'])){
                            $grossAmount = $data['summary']['grossAmount']; //832000
                            $itemCount = $data['summary']['itemCount']; //1
                            $netAmount = $data['summary']['netAmount']; //705084.75
                            $qrCode = $data['summary']['qrCode']; //020000001149IC1200122760430004F588000000C1A8450A20A021D534A1000121462A1000094968~MM INTERGRATED STEEL MILLS (UGANDA) LIMITED~Ediomu & Company~Kiboko Galv Corr. Sheet 36G
                            $taxAmount = $data['summary']['taxAmount'];//126915.25
                            $modeCode = $data['summary']['modeCode'];//0
                            
                            $mode = new modes($this->db);
                            $mode->getByCode($modeCode);
                            $modeName = $mode->name;//online
                            
                            $f = new \NumberFormatter("en", NumberFormatter::SPELLOUT);
                            $grossAmountWords = $f->format($grossAmount);//two million,
                            
                            $qr = $qrCode;
                            
                            
                        }
                        
                        $debitnotedetails['einvoicedatamatrixcode'] = $qrCode;
                        $debitnotedetails['grossamountword'] = $grossAmountWords;
                        $debitnotedetails['modename'] = $modeName;
                        $debitnotedetails['modecode'] = $modeCode;
                        $debitnotedetails['taxamount'] = $taxAmount;
                        $debitnotedetails['netamount'] = $netAmount;
                        $debitnotedetails['itemcount'] = $itemCount;
                        $debitnotedetails['grossamount'] = $grossAmount;
                        $debitnotedetails['antifakecode'] = $antifakeCode;
                        $debitnotedetails['issueddate'] = $issuedDate;
                        $debitnotedetails['einvoicenumber'] = $debitnoteNo;
                        $debitnotedetails['edebitnoteid'] = $debitnoteId;
                        $debitnotedetails['isinvalid'] = $isRefund;
                        $debitnotedetails['isrefund'] = $isInvalid;
                        $debitnotedetails['oriinvoiceid'] = $oriInvoiceId;
                        $debitnotedetails['issueddatepdf'] = $issuedDatePdf;
                        $debitnotedetails['issuedtime'] = $issuedTime;
                        $debitnotedetails['origrossamount'] = '0';
                        $debitnotedetails['currencyRate'] = $currencyRate;
                        
                        $inv_status = $this->util->createdebitnote($debitnotedetails, $goods, $taxes, $buyer, $this->userid_u);
                        
                        if ($inv_status) {
                            $this->logger->write("Api : uploadproduct() : The debit note was created on eTW successfully", 'r');
                        } else {
                            $this->logger->write("Api : uploadproduct() : The debit note was NOT created on eTW", 'r');
                        }
                        
                        $this->message = 'The operation to upload the debit note was successful';
                        $this->code = '000';
                    }
                } else {
                    $this->message = 'The debit note has already been uploaded into EFRIS';
                    $this->code = '99';
                    
                    $debitnoteid = $inv_check->einvoiceid;
                    $debitnotenumber = $inv_check->einvoicenumber;
                    $issueddate = $inv_check->issuedtime;
                    $fdn = $inv_check->antifakecode;
                    $qr = $inv_check->einvoicedatamatrixcode;
                }
            } else {
                $this->logger->write("Api : uploaddebitnote() : The user is not allowed to perform this function", 'r');
                $this->message = "The user is not allowed to perform this function";
                $this->code = '0099';
            }
            
            
            
            
            
            $activity = 'UPLOADDEBITNOTE: ' . $vchnumber . ': ' . $vchref . ': ' . $this->message;
            $windowsuser = trim($xml->WINDOWSUSER);
            $ipaddress = trim($xml->IPADDRESS);
            $macaddress = trim($xml->MACADDRESS);
            $systemname = trim($xml->SYSTEMNAME);
            $this->util->createerpauditlog($this->userid_u, $activity, $windowsuser, $ipaddress, $macaddress, $systemname, $xml);
        }
        
        
        
        // prepare xml response
        $this->response  =  "<ENVELOPE>";
        $this->response .=  "<HEADER>";
        $this->response .=  "<VERSION>1</VERSION>";
        $this->response .=  "<STATUS>1</STATUS>";
        $this->response .=  "</HEADER>";
        $this->response .=  "<BODY>";
        $this->response .=  "<DATA>";
        $this->response .=  "<RESPONSE>";
        $this->response .=  "<RETURNCODE>" . htmlspecialchars($this->code) . "</RETURNCODE>";
        $this->response .=  "<RETURNMESSAGE>" . htmlspecialchars($this->message) . "</RETURNMESSAGE>";
        $this->response .=  "<INVID>". htmlspecialchars($debitnoteid) ."</INVID>";
        $this->response .=  "<INVNO>". htmlspecialchars($debitnotenumber) ."</INVNO>";
        $this->response .=  "<ISSUEDT>". htmlspecialchars($issueddate) ."</ISSUEDT>";
        $this->response .=  "<FDN>". htmlspecialchars($fdn) ."</FDN>";
        $this->response .=  "<QRCODE>". htmlspecialchars($qr) ."</QRCODE>";
        $this->response .=  "</RESPONSE>";
        $this->response .=  "</DATA>";
        $this->response .=  "</BODY>";
        $this->response .=  "</ENVELOPE>";
        
        $len = strlen($this->response);
        header ("CONTENT-LENGTH:".$len);
        print $this->response;
        return;
    }
    
    
    /**
     *	@name queryinvoice
     *  @desc query invoice
     *	@return string response
     *	@param NULL
     **/
    function queryinvoice(){
        $operation = NULL; //tblevents
        $permission = 'DOWNLOADINVOICE'; //tblpermissions
        $event = NULL; //tblevents
        $eventnotification = NULL; //tbleventnotifications
        
        if ($this->errorcode) {
            $this->message = $this->errormessage;
            $this->code = $this->errorcode;
            
            $body = $this->code . ' : ' . $this->message;
            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
        } else {
            $invoiceid = '';
            $invoicenumber = '';
            $issueddate = '';
            $fdn = '';
            $qr = '';
            $ref = '';
            $appstatus = '';
            $creditnotenumber = '';
            
            
            if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
                date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
            }
            
            
            $xml = simplexml_load_string($this->xml);
            $erpinvoiceid = trim($xml->VOUCHERNUMBER);
            $erpinvoiceno = trim($xml->VOUCHERREF);
            $vchtype = trim($xml->VOUCHERTYPE);
            $vchtypename = trim($xml->VOUCHERTYPENAME);
            
            $this->logger->write("Api : queryinvoice() : The userid is: " . $this->userid_u, 'r');
            $this->logger->write("Api : queryinvoice() : Checking permissions", 'r');
            if ($this->userpermissions[$permission]) {
                
                
                if ($vchtype == 'Credit Note') {
                    $this->logger->write("Api : queryinvoice() : The voucher type name is: " . $vchtypename, 'r');
                    
                    if(strtoupper($vchtypename) == 'CREDIT MEMO'){
                        $this->logger->write("Api : queryinvoice() : Querying credit memo started", 'r');
                        $inv_check = new DB\SQL\Mapper($this->db, 'tblcreditmemos');
                        $inv_check->load(array('TRIM(erpinvoiceid)=? AND TRIM(vouchertype)=? AND TRIM(vouchertypename)=?', $erpinvoiceid, $vchtype, $vchtypename));
                        
                        $this->logger->write($this->db->log(TRUE), 'r');
                        
                        $this->logger->write("Api : queryinvoice() : Proceeding to check the query results", 'r');
                        
                        if($inv_check->dry ()){
                            $this->logger->write("Api : queryinvoice() : The credit memo does not exist on eTW", 'r');
                            $this->message = 'The credit memo does not exist on EFRIS';
                            $this->code = '99';
                        } else {
                            $this->logger->write("Api : queryinvoice() : The credit memo was retrieved successfully", 'r');
                            $this->message = 'The credit memo was retrived successfully';
                            $this->code = '000';
                            
                            $invoiceid = $inv_check->einvoiceid;
                            $invoicenumber = $inv_check->einvoicenumber;
                            $issueddate = $inv_check->issuedtime;
                            $fdn = $inv_check->antifakecode;
                            $qr = $inv_check->einvoicedatamatrixcode;
                            
                            
                            $i_data = $this->util->downloadinvoice($this->userid_u, $invoicenumber);
                            $i_data = json_decode($i_data, true);
                            
                            $antifakeCode = '';
                            $invoiceNo = '';
                            $issuedDate = '';
                            $issuedDate = date("Y-m-d", strtotime($inv_check->issuedtime));
                            $issuedTime = date("Y-m-d H:i:s", strtotime($inv_check->issuedtime));
                            $issuedDatePdf = date("Y-m-d H:i:s", strtotime($inv_check->issuedtime));
                            $oriInvoiceId = '';
                            $oriInvoiceNo = '';
                            $isInvalid = '';
                            $isRefund = '';
                            $grossAmount = 0;
                            $itemCount = 0;
                            $netAmount = 0;
                            $qrCode = '';
                            $taxAmount = 0;
                            $modeCode = '';
                            $modeName = '';
                            $grossAmountWords = '';
                            $oriGrossAmount = 0;
                            $oriIssuedDate = '';
                            $currencyRate = 1;
                            
                            /*START OF INVOICE BLOCK*/
                            if (isset($i_data['basicInformation'])){
                                $antifakeCode = $i_data['basicInformation']['antifakeCode']; //32966911991799104051
                                $invoiceId = $i_data['basicInformation']['invoiceId']; //3257429764295992735
                                $invoiceNo = $i_data['basicInformation']['invoiceNo']; //3120012276043
                                
                                $issuedDate = $i_data['basicInformation']['issuedDate']; //18/09/2020 17:14:12
                                $issuedDate = str_replace('/', '-', $issuedDate);//Replace / with -
                                $issuedDate = date("Y-m-d H:i:s", strtotime($issuedDate));
                                
                                $issuedTime = $i_data['basicInformation']['issuedDate']; //18/09/2020 17:14:12
                                $issuedTime = str_replace('/', '-', $issuedTime);//Replace / with -
                                $issuedTime = date("Y-m-d H:i:s", strtotime($issuedTime));
                                
                                $issuedDatePdf = $i_data['basicInformation']['issuedDatePdf']; //318/09/2020 17:14:12
                                $issuedDatePdf = str_replace('/', '-', $issuedDatePdf);//Replace / with -
                                $issuedDatePdf = date("Y-m-d H:i:s", strtotime($issuedDatePdf));
                                
                                
                                $oriIssuedDate = $i_data['basicInformation']['oriIssuedDate']; //318/09/2020 17:14:12
                                $oriIssuedDate = str_replace('/', '-', $oriIssuedDate);//Replace / with -
                                $oriIssuedDate = date("Y-m-d H:i:s", strtotime($oriIssuedDate));
                                
                                $oriInvoiceId = $i_data['basicInformation']['oriInvoiceId'];//1
                                $oriInvoiceNo = $i_data['basicInformation']['oriInvoiceNo'];//1
                                $isInvalid = $i_data['basicInformation']['isInvalid'];//1
                                $isRefund = $i_data['basicInformation']['isRefund'];//1
                                $currencyRate = $i_data['basicInformation']['currencyRate'];
                                
                                $invoiceid = $invoiceId;
                                $issueddate = $issuedDate;
                                $fdn = $antifakeCode;
                                
                            }
                            
                            if (isset($i_data['summary'])){
                                $grossAmount = $i_data['summary']['grossAmount']; //832000
                                $itemCount = $i_data['summary']['itemCount']; //1
                                $netAmount = $i_data['summary']['netAmount']; //705084.75
                                $qrCode = $i_data['summary']['qrCode']; //020000001149IC1200122760430004F588000000C1A8450A20A021D534A1000121462A1000094968~MM INTERGRATED STEEL MILLS (UGANDA) LIMITED~Ediomu & Company~Kiboko Galv Corr. Sheet 36G
                                $taxAmount = $i_data['summary']['taxAmount'];//126915.25
                                $modeCode = $i_data['summary']['modeCode'];//0
                                $oriGrossAmount = $i_data['summary']['oriGrossAmount'];//19556.48
                                
                                $mode = new modes($this->db);
                                $mode->getByCode($modeCode);
                                $modeName = $mode->name;//online
                                
                                $f = new \NumberFormatter("en", NumberFormatter::SPELLOUT);
                                $grossAmountWords = $f->format($grossAmount);//two million,
                                
                                $qr = $qrCode;
                                
                            }
                            /*END INVOICE BLOCK*/
                            
                            if ($invoicenumber == $invoiceNo) {
                                try{
                                    $isInvalid = empty($isInvalid) || trim($isInvalid) == ''? 'NULL' : $isInvalid;
                                    $isRefund = empty($isRefund) || trim($isRefund) == ''? 'NULL' : $isRefund;
                                    $grossAmount = empty($grossAmount) || trim($grossAmount) == ''? 'NULL' : $grossAmount;
                                    $itemCount = empty($itemCount) || trim($itemCount) == ''? 'NULL' : $itemCount;
                                    $netAmount = empty($netAmount) || trim($netAmount) == ''? 'NULL' : $netAmount;
                                    $taxAmount = empty($taxAmount) || trim($taxAmount) == ''? 'NULL' : $taxAmount;
                                    
                                    $this->db->exec(array('UPDATE tblcreditmemos SET antifakecode = "' . $antifakeCode .
                                        '", einvoiceid = "' . $invoiceId .
                                        '", einvoicenumber = "' . $invoiceNo .
                                        '", issueddate = "' . $issuedDate .
                                        '", issuedtime = "' . $issuedTime .
                                        '", issueddatepdf = "' . $issuedDatePdf .
                                        '", isinvalid = ' . $isInvalid .
                                        ', isrefund = ' . $isRefund .
                                        ', grossamount = ' . $grossAmount .
                                        ', itemcount = ' . $itemCount .
                                        ', netamount = ' . $netAmount .
                                        ', einvoicedatamatrixcode = "' . $qrCode .
                                        '", taxamount = ' . $taxAmount .
                                        ', modecode = "' . $modeCode .
                                        '", modename = "' . $modeName .
                                        '", grossamountword = "' . $grossAmountWords .
                                        '", currencyRate = ' . $currencyRate .
                                        ', modifieddt = NOW(), modifiedby = ' . $this->userid .
                                        ' WHERE einvoicenumber = "' . $invoicenumber . '"'));
                                    
                                    $this->logger->write($this->db->log(TRUE), 'r');
                                } catch (Exception $e) {
                                    $this->logger->write("Api : queryinvoice() : Failed to update the table tblcreditmemos. The error message is " . $e->getMessage(), 'r');
                                }
                            }
                            
                        }
                    } elseif(strtoupper($vchtypename) == 'CREDIT NOTE'){
                        $inv_check = new DB\SQL\Mapper($this->db, 'tblcreditnotes');
                        $this->logger->write("Api : queryinvoice() : Querying credit note started", 'r');
                        $inv_check->load(array('TRIM(erpcreditnoteid)=? AND TRIM(vouchertype)=? AND TRIM(vouchertypename)=?', $erpinvoiceid, $vchtype, $vchtypename));
                        
                        $this->logger->write($this->db->log(TRUE), 'r');
                        
                        if($inv_check->dry ()){
                            $this->logger->write("Api : queryinvoice() : The credit note does not exist on eTW", 'r');
                            $this->message = 'The credit note does not exist on EFRIS';
                            $this->code = '99';
                        } else {
                            $this->logger->write("Api : queryinvoice() : The credit note was retrieved successfully", 'r');
                            
                            //Fetch details of the newly uploaded credit note
                            $n_data = $this->util->downloadcreditnote($this->userid_u, $inv_check->referenceno);//will return JSON.
                            //var_dump($data);
                            
                            $n_data = json_decode($n_data, true);
                            //var_dump($n_data);
                            
                            
                            if(isset($n_data['records'])){
                                $antifakeCode = '';
                                $invoiceNo = '';
                                $issuedDate = '';
                                $issuedDate = date("Y-m-d", strtotime($inv_check->applicationtime));
                                $issuedTime = date("Y-m-d H:i:s", strtotime($inv_check->applicationtime));
                                $issuedDatePdf = date("Y-m-d H:i:s", strtotime($inv_check->applicationtime));
                                $isInvalid = '';
                                $isRefund = '';
                                $grossAmount = 0;
                                $itemCount = 0;
                                $netAmount = 0;
                                $qrCode = '';
                                $taxAmount = 0;
                                $modeCode = '';
                                $modeName = '';
                                $grossAmountWords = '';
                                $oriGrossAmount = 0;
                                $currencyRate = 1;
                                
                                foreach($n_data['records'] as $elem){
                                    
                                    $i_data = $this->util->downloadinvoice($this->userid_u, $elem['invoiceNo']);
                                    $i_data = json_decode($i_data, true);
                                    
                                    /*START OF INVOICE BLOCK*/
                                    if (isset($i_data['basicInformation'])){
                                        $antifakeCode = $i_data['basicInformation']['antifakeCode']; //32966911991799104051
                                        $invoiceId = $i_data['basicInformation']['invoiceId']; //3257429764295992735
                                        $invoiceNo = $i_data['basicInformation']['invoiceNo']; //3120012276043
                                        
                                        $issuedDate = $i_data['basicInformation']['issuedDate']; //18/09/2020 17:14:12
                                        $issuedDate = str_replace('/', '-', $issuedDate);//Replace / with -
                                        $issuedDate = date("Y-m-d H:i:s", strtotime($issuedDate));
                                        
                                        $issuedTime = $i_data['basicInformation']['issuedDate']; //18/09/2020 17:14:12
                                        $issuedTime = str_replace('/', '-', $issuedTime);//Replace / with -
                                        $issuedTime = date("Y-m-d H:i:s", strtotime($issuedTime));
                                        
                                        $issuedDatePdf = $i_data['basicInformation']['issuedDatePdf']; //318/09/2020 17:14:12
                                        $issuedDatePdf = str_replace('/', '-', $issuedDatePdf);//Replace / with -
                                        $issuedDatePdf = date("Y-m-d H:i:s", strtotime($issuedDatePdf));
                                        
                                        $oriInvoiceId = $i_data['basicInformation']['oriInvoiceId'];//1
                                        $isInvalid = $i_data['basicInformation']['isInvalid'];//1
                                        $isRefund = $i_data['basicInformation']['isRefund'];//1
                                        
                                        $currencyRate = $i_data['basicInformation']['currencyRate'];
                                        
                                        $invoiceid = $invoiceId;
                                        //$invoicenumber = $invoiceNo;
                                        $issueddate = $issuedDate;
                                        $fdn = $antifakeCode;
                                        
                                    }
                                    
                                    if (isset($i_data['summary'])){
                                        $grossAmount = $i_data['summary']['grossAmount']; //832000
                                        $itemCount = $i_data['summary']['itemCount']; //1
                                        $netAmount = $i_data['summary']['netAmount']; //705084.75
                                        $qrCode = $i_data['summary']['qrCode']; //020000001149IC1200122760430004F588000000C1A8450A20A021D534A1000121462A1000094968~MM INTERGRATED STEEL MILLS (UGANDA) LIMITED~Ediomu & Company~Kiboko Galv Corr. Sheet 36G
                                        $taxAmount = $i_data['summary']['taxAmount'];//126915.25
                                        $modeCode = $i_data['summary']['modeCode'];//0
                                        $oriGrossAmount = $i_data['summary']['oriGrossAmount'];//19556.48
                                        
                                        $mode = new modes($this->db);
                                        $mode->getByCode($modeCode);
                                        $modeName = $mode->name;//online
                                        
                                        $f = new \NumberFormatter("en", NumberFormatter::SPELLOUT);
                                        $grossAmountWords = $f->format($grossAmount);//two million,
                                        
                                        $qr = $qrCode;
                                        
                                    }
                                    /*END OF INVOICE BLOCK*/
                                    
                                    $refundInvoiceNo = $elem['invoiceNo'];
                                    $approveStatusCode = $elem['approveStatus'];
                                    $applicationTime = $elem['applicationTime']; //28/09/2020 00:43:29
                                    $referenceNo = $elem['referenceNo']; //21PL010073993
                                    $oriInvoiceNo = $elem['oriInvoiceNo']; //120014732476
                                    
                                    $applicationTime = str_replace('/', '-', $applicationTime);//Replace / with -
                                    $applicationTime = date("Y-m-d H:i:s", strtotime($applicationTime));
                                    
                                    $grossAmount = $elem['grossAmount'];
                                    $totalAmount = $elem['totalAmount'];
                                    //$refundIssuedDate = $elem['refundIssuedDate'];
                                    $refundIssuedDate = $applicationTime;//28-09-2020 00:43:29
                                    $appId = $elem['id'];
                                    
                                    try{
                                        
                                        $isInvalid = empty($isInvalid) || trim($isInvalid) == ''? 'NULL' : $isInvalid;
                                        $isRefund = empty($isRefund) || trim($isRefund) == ''? 'NULL' : $isRefund;
                                        $grossAmount = empty($grossAmount) || trim($grossAmount) == ''? 'NULL' : $grossAmount;
                                        $itemCount = empty($itemCount) || trim($itemCount) == ''? 'NULL' : $itemCount;
                                        $netAmount = empty($netAmount) || trim($netAmount) == ''? 'NULL' : $netAmount;
                                        $taxAmount = empty($taxAmount) || trim($taxAmount) == ''? 'NULL' : $taxAmount;
                                        $oriGrossAmount = empty($oriGrossAmount) || trim($oriGrossAmount) == ''? 'NULL' : $oriGrossAmount;
                                        $totalAmount = empty($totalAmount) || trim($totalAmount) == ''? 'NULL' : $totalAmount;
                                        
                                        $this->db->exec(array('UPDATE tblcreditnotes SET antifakecode = "' . $antifakeCode .
                                            '", einvoiceid = "' . $invoiceId .
                                            '", einvoicenumber = "' . $invoiceNo .
                                            '", issueddate = "' . $issuedDate .
                                            '", issuedtime = "' . $issuedTime .
                                            '", issueddatepdf = "' . $issuedDatePdf .
                                            '", oriinvoiceid = "' . $oriInvoiceId .
                                            '", isinvalid = ' . $isInvalid .
                                            ', isrefund = ' . $isRefund .
                                            ', grossamount = ' . $grossAmount .
                                            ', itemcount = ' . $itemCount .
                                            ', netamount = ' . $netAmount .
                                            ', einvoicedatamatrixcode = "' . $qrCode .
                                            '", taxamount = ' . $taxAmount .
                                            ', modecode = "' . $modeCode .
                                            '", modename = "' . $modeName .
                                            '", grossamountword = "' . $grossAmountWords .
                                            '", origrossamount = ' . $oriGrossAmount .
                                            ', refundinvoiceno = "' . $refundInvoiceNo .
                                            '", approvestatus = "' . $approveStatusCode .
                                            '", totalamount = ' . $totalAmount .
                                            ', issueddate = "' . $refundIssuedDate .
                                            '", issuedtime = "' . $refundIssuedDate .
                                            '", creditnoteapplicationid = "' . $appId .
                                            '", applicationtime = "' . $applicationTime .
                                            '", currencyRate = ' . $currencyRate .
                                            ', modifieddt = NOW(), modifiedby = ' . $this->userid .
                                            ' WHERE referenceno = "' . $inv_check->referenceno . '"'));
                                        
                                        $this->logger->write($this->db->log(TRUE), 'r');
                                    } catch (Exception $e) {
                                        $this->logger->write("Api : queryinvoice() : Failed to update the table tblcreditnotes. The error message is " . $e->getMessage(), 'r');
                                    }
                                    
                                    if ($referenceNo = $inv_check->referenceno) {
                                        $invoicenumber = $oriInvoiceNo;
                                        $ref = $referenceNo;
                                        $appstatus = $this->util->decodeapprovestatus($approveStatusCode);
                                        $creditnotenumber = $refundInvoiceNo;
                                    }
                                    
                                }
                                
                                $this->message = 'The credit note was retrived successfully';
                                $this->code = '000';
                                
                            } elseif (isset($n_data['returnCode'])){
                                $this->logger->write("Api : queryinvoice() : The operation to download the credit note not successful. The error message is " . $n_data['returnMessage'], 'r');
                                $this->message = $n_data['returnMessage'];
                                $this->code = $n_data['returnCode'];
                            } else {
                                $this->logger->write("Api : queryinvoice() : The operation to download the credit note not successful.", 'r');
                                $this->message = 'The operation was not successful';
                                $this->code = '1005';
                            }
                        }
                    } else {
                        $this->logger->write("Api : queryinvoice() : unexpected voucher type name. Please name your voucher type as per standard", 'r');
                        $this->message = "Unexpected voucher type name. Please name your voucher type as per standard";
                        $this->code = '8452';
                    }                   
                } elseif ($vchtype == 'Debit Note'){
                    $inv_check = new DB\SQL\Mapper($this->db, 'tbldebitnotes');
                    $this->logger->write("Api : queryinvoice() : Querying debit note started", 'r');
                    
                    $inv_check = new DB\SQL\Mapper($this->db, 'tbldebitnotes');
                    $inv_check->load(array('TRIM(erpdebitnoteid)=? AND TRIM(vouchertype)=? AND TRIM(vouchertypename)=?', $erpinvoiceid, $vchtype, $vchtypename));
                    
                    $this->logger->write($this->db->log(TRUE), 'r');
                    
                    $this->logger->write("Api : queryinvoice() : Proceeding to check the query results", 'r');
                    
                    if($inv_check->dry ()){
                        $this->logger->write("Api : queryinvoice() : The debit note does not exist on eTW", 'r');
                        $this->message = 'The debit note does not exist on EFRIS';
                        $this->code = '99';
                    } else {
                        $this->logger->write("Api : queryinvoice() : The debit note was retrieved successfully", 'r');
                        $this->message = 'The debit note was retrived successfully';
                        $this->code = '000';
                        
                        $invoiceid = $inv_check->einvoiceid;
                        $invoicenumber = $inv_check->einvoicenumber;
                        $issueddate = $inv_check->issuedtime;
                        $fdn = $inv_check->antifakecode;
                        $qr = $inv_check->einvoicedatamatrixcode;
                        
                        $i_data = $this->util->downloadinvoice($this->userid_u, $invoicenumber);
                        $i_data = json_decode($i_data, true);
                        
                        $antifakeCode = '';
                        $invoiceNo = '';
                        $issuedDate = '';
                        $issuedDate = date("Y-m-d", strtotime($inv_check->applicationtime));
                        $issuedTime = date("Y-m-d H:i:s", strtotime($inv_check->applicationtime));
                        $issuedDatePdf = date("Y-m-d H:i:s", strtotime($inv_check->applicationtime));
                        $oriInvoiceId = '';
                        $oriInvoiceNo = '';
                        $isInvalid = '';
                        $isRefund = '';
                        $grossAmount = 0;
                        $itemCount = 0;
                        $netAmount = 0;
                        $qrCode = '';
                        $taxAmount = 0;
                        $modeCode = '';
                        $modeName = '';
                        $grossAmountWords = '';
                        $oriGrossAmount = 0;
                        $oriIssuedDate = '';
                        $currencyRate = 1;
                        
                        /*START OF INVOICE BLOCK*/
                        if (isset($i_data['basicInformation'])){
                            $antifakeCode = $i_data['basicInformation']['antifakeCode']; //32966911991799104051
                            $invoiceId = $i_data['basicInformation']['invoiceId']; //3257429764295992735
                            $invoiceNo = $i_data['basicInformation']['invoiceNo']; //3120012276043
                            
                            $issuedDate = $i_data['basicInformation']['issuedDate']; //18/09/2020 17:14:12
                            $issuedDate = str_replace('/', '-', $issuedDate);//Replace / with -
                            $issuedDate = date("Y-m-d H:i:s", strtotime($issuedDate));
                            
                            $issuedTime = $i_data['basicInformation']['issuedDate']; //18/09/2020 17:14:12
                            $issuedTime = str_replace('/', '-', $issuedTime);//Replace / with -
                            $issuedTime = date("Y-m-d H:i:s", strtotime($issuedTime));
                            
                            $issuedDatePdf = $i_data['basicInformation']['issuedDatePdf']; //318/09/2020 17:14:12
                            $issuedDatePdf = str_replace('/', '-', $issuedDatePdf);//Replace / with -
                            $issuedDatePdf = date("Y-m-d H:i:s", strtotime($issuedDatePdf));
                            
                            
                            $oriIssuedDate = $i_data['basicInformation']['oriIssuedDate']; //318/09/2020 17:14:12
                            $oriIssuedDate = str_replace('/', '-', $oriIssuedDate);//Replace / with -
                            $oriIssuedDate = date("Y-m-d H:i:s", strtotime($oriIssuedDate));
                            
                            $oriInvoiceId = $i_data['basicInformation']['oriInvoiceId'];//1
                            $oriInvoiceNo = $i_data['basicInformation']['oriInvoiceNo'];//1
                            $isInvalid = $i_data['basicInformation']['isInvalid'];//1
                            $isRefund = $i_data['basicInformation']['isRefund'];//1
                            
                            $currencyRate = $i_data['basicInformation']['currencyRate'];
                            
                            $invoiceid = $invoiceId;
                            $issueddate = $issuedDate;
                            $fdn = $antifakeCode;
                            
                        }
                        
                        if (isset($i_data['summary'])){
                            $grossAmount = $i_data['summary']['grossAmount']; //832000
                            $itemCount = $i_data['summary']['itemCount']; //1
                            $netAmount = $i_data['summary']['netAmount']; //705084.75
                            $qrCode = $i_data['summary']['qrCode']; //020000001149IC1200122760430004F588000000C1A8450A20A021D534A1000121462A1000094968~MM INTERGRATED STEEL MILLS (UGANDA) LIMITED~Ediomu & Company~Kiboko Galv Corr. Sheet 36G
                            $taxAmount = $i_data['summary']['taxAmount'];//126915.25
                            $modeCode = $i_data['summary']['modeCode'];//0
                            $oriGrossAmount = $i_data['summary']['oriGrossAmount'];//19556.48
                            
                            $mode = new modes($this->db);
                            $mode->getByCode($modeCode);
                            $modeName = $mode->name;//online
                            
                            $f = new \NumberFormatter("en", NumberFormatter::SPELLOUT);
                            $grossAmountWords = $f->format($grossAmount);//two million,
                            
                            $qr = $qrCode;
                            
                        }
                        /*END INVOICE BLOCK*/
                        
                        if ($invoicenumber == $invoiceNo) {
                            try{
                                
                                $isInvalid = empty($isInvalid) || trim($isInvalid) == ''? 'NULL' : $isInvalid;
                                $isRefund = empty($isRefund) || trim($isRefund) == ''? 'NULL' : $isRefund;
                                $grossAmount = empty($grossAmount) || trim($grossAmount) == ''? 'NULL' : $grossAmount;
                                $itemCount = empty($itemCount) || trim($itemCount) == ''? 'NULL' : $itemCount;
                                $netAmount = empty($netAmount) || trim($netAmount) == ''? 'NULL' : $netAmount;
                                $taxAmount = empty($taxAmount) || trim($taxAmount) == ''? 'NULL' : $taxAmount;
                                $oriGrossAmount = empty($oriGrossAmount) || trim($oriGrossAmount) == ''? 'NULL' : $oriGrossAmount;
                                
                                $this->db->exec(array('UPDATE tbldebitnotes SET oriinvoiceno = "' . $oriInvoiceNo . 
                                    '", antifakecode = "' . $antifakeCode . 
                                    '", einvoiceid = "' . $invoiceId . 
                                    '", einvoicenumber = "' . $invoiceNo . 
                                    '", issueddate = "' . $issuedDate . 
                                    '", issuedtime = "' . $issuedTime . 
                                    '", issueddatepdf = "' . $issuedDatePdf . 
                                    '", oriinvoiceid = "' . $oriInvoiceId . 
                                    '", isinvalid = ' . $isInvalid . 
                                    ', isrefund = ' . $isRefund . 
                                    ', grossamount = ' . $grossAmount . 
                                    ', itemcount = ' . $itemCount . 
                                    ', netamount = ' . $netAmount . 
                                    ', einvoicedatamatrixcode = "' . $qrCode . 
                                    '", taxamount = ' . $taxAmount . 
                                    ', modecode = "' . $modeCode . 
                                    '", modename = "' . $modeName . 
                                    '", grossamountword = "' . $grossAmountWords . 
                                    '", origrossamount = ' . $oriGrossAmount . 
                                    ', currencyRate = ' . $currencyRate .
                                    ', modifieddt = NOW(), modifiedby = ' . $this->userid . 
                                    ' WHERE einvoicenumber = "' . $invoicenumber . '"'));
                                
                                $this->logger->write($this->db->log(TRUE), 'r');
                            } catch (Exception $e) {
                                $this->logger->write("Api : queryinvoice() : Failed to update the table tbldebitnotes. The error message is " . $e->getMessage(), 'r');
                            }
                        }
                        
                        
                    }
                } elseif ($vchtype == 'Sales' || stripos($vchtype, 'Sales')){
                    $this->logger->write("Api : queryinvoice() : Querying invoice started", 'r');
                    $inv_check = new DB\SQL\Mapper($this->db, 'tblinvoices');
                    $inv_check->load(array('TRIM(erpinvoiceid)=? AND TRIM(vouchertype)=? AND TRIM(vouchertypename)=?', $erpinvoiceid, $vchtype, $vchtypename));
                    
                    $this->logger->write($this->db->log(TRUE), 'r');
                    
                    $this->logger->write("Api : queryinvoice() : Proceeding to check the query results", 'r');
                    
                    if($inv_check->dry ()){
                        $this->logger->write("Api : queryinvoice() : The invoice does not exist on eTW", 'r');
                        $this->message = 'The invoice does not exist on EFRIS';
                        $this->code = '99';
                    } else {
                        $this->logger->write("Api : queryinvoice() : The invoice was retrieved successfully", 'r');
                        $this->message = 'The invoice was retrived successfully';
                        $this->code = '000';
                        
                        $invoiceid = $inv_check->einvoiceid;
                        $invoicenumber = $inv_check->einvoicenumber;
                        $issueddate = $inv_check->issuedtime;
                        $fdn = $inv_check->antifakecode;
                        $qr = $inv_check->einvoicedatamatrixcode;
                        
                        
                        $i_data = $this->util->downloadinvoice($this->userid_u, $invoicenumber);
                        $i_data = json_decode($i_data, true);
                        
                        $antifakeCode = '';
                        $invoiceNo = '';
                        $issuedDate = '';
                        $issuedDate = date("Y-m-d", strtotime($inv_check->issuedtime));
                        $issuedTime = date("Y-m-d H:i:s", strtotime($inv_check->issuedtime));
                        $issuedDatePdf = date("Y-m-d H:i:s", strtotime($inv_check->issuedtime));
                        $oriInvoiceId = '';
                        $oriInvoiceNo = '';
                        $isInvalid = '';
                        $isRefund = '';
                        $grossAmount = 0;
                        $itemCount = 0;
                        $netAmount = 0;
                        $qrCode = '';
                        $taxAmount = 0;
                        $modeCode = '';
                        $modeName = '';
                        $grossAmountWords = '';
                        $oriGrossAmount = 0;
                        $oriIssuedDate = '';
                        $currencyRate = 1;
                        
                        /*START OF INVOICE BLOCK*/
                        if (isset($i_data['basicInformation'])){
                            $antifakeCode = $i_data['basicInformation']['antifakeCode']; //32966911991799104051
                            $invoiceId = $i_data['basicInformation']['invoiceId']; //3257429764295992735
                            $invoiceNo = $i_data['basicInformation']['invoiceNo']; //3120012276043
                            
                            $issuedDate = $i_data['basicInformation']['issuedDate']; //18/09/2020 17:14:12
                            $issuedDate = str_replace('/', '-', $issuedDate);//Replace / with -
                            $issuedDate = date("Y-m-d H:i:s", strtotime($issuedDate));
                            
                            $issuedTime = $i_data['basicInformation']['issuedDate']; //18/09/2020 17:14:12
                            $issuedTime = str_replace('/', '-', $issuedTime);//Replace / with -
                            $issuedTime = date("Y-m-d H:i:s", strtotime($issuedTime));
                            
                            $issuedDatePdf = $i_data['basicInformation']['issuedDatePdf']; //318/09/2020 17:14:12
                            $issuedDatePdf = str_replace('/', '-', $issuedDatePdf);//Replace / with -
                            $issuedDatePdf = date("Y-m-d H:i:s", strtotime($issuedDatePdf));
                            
                            
                            $oriIssuedDate = $i_data['basicInformation']['oriIssuedDate']; //318/09/2020 17:14:12
                            $oriIssuedDate = str_replace('/', '-', $oriIssuedDate);//Replace / with -
                            $oriIssuedDate = date("Y-m-d H:i:s", strtotime($oriIssuedDate));
                            
                            $oriInvoiceId = $i_data['basicInformation']['oriInvoiceId'];//1
                            $oriInvoiceNo = $i_data['basicInformation']['oriInvoiceNo'];//1
                            $isInvalid = $i_data['basicInformation']['isInvalid'];//1
                            $isRefund = $i_data['basicInformation']['isRefund'];//1
                            $currencyRate = $i_data['basicInformation']['currencyRate'];
                            
                            $invoiceid = $invoiceId;
                            $issueddate = $issuedDate;
                            $fdn = $antifakeCode;
                            
                        }
                        
                        if (isset($i_data['summary'])){
                            $grossAmount = $i_data['summary']['grossAmount']; //832000
                            $itemCount = $i_data['summary']['itemCount']; //1
                            $netAmount = $i_data['summary']['netAmount']; //705084.75
                            $qrCode = $i_data['summary']['qrCode']; //020000001149IC1200122760430004F588000000C1A8450A20A021D534A1000121462A1000094968~MM INTERGRATED STEEL MILLS (UGANDA) LIMITED~Ediomu & Company~Kiboko Galv Corr. Sheet 36G
                            $taxAmount = $i_data['summary']['taxAmount'];//126915.25
                            $modeCode = $i_data['summary']['modeCode'];//0
                            $oriGrossAmount = $i_data['summary']['oriGrossAmount'];//19556.48
                            
                            $mode = new modes($this->db);
                            $mode->getByCode($modeCode);
                            $modeName = $mode->name;//online
                            
                            $f = new \NumberFormatter("en", NumberFormatter::SPELLOUT);
                            $grossAmountWords = $f->format($grossAmount);//two million,
                            
                            $qr = $qrCode;
                            
                        }
                        /*END INVOICE BLOCK*/
                        
                        if ($invoicenumber == $invoiceNo) {
                            try{
                                $isInvalid = empty($isInvalid) || trim($isInvalid) == ''? 'NULL' : $isInvalid;
                                $isRefund = empty($isRefund) || trim($isRefund) == ''? 'NULL' : $isRefund;
                                $grossAmount = empty($grossAmount) || trim($grossAmount) == ''? 'NULL' : $grossAmount;
                                $itemCount = empty($itemCount) || trim($itemCount) == ''? 'NULL' : $itemCount;
                                $netAmount = empty($netAmount) || trim($netAmount) == ''? 'NULL' : $netAmount;
                                $taxAmount = empty($taxAmount) || trim($taxAmount) == ''? 'NULL' : $taxAmount;
                                
                                $this->db->exec(array('UPDATE tblinvoices SET antifakecode = "' . $antifakeCode . 
                                    '", einvoiceid = "' . $invoiceId . 
                                    '", einvoicenumber = "' . $invoiceNo . 
                                    '", issueddate = "' . $issuedDate . 
                                    '", issuedtime = "' . $issuedTime . 
                                    '", issueddatepdf = "' . $issuedDatePdf .
                                    '", isinvalid = ' . $isInvalid . 
                                    ', isrefund = ' . $isRefund . 
                                    ', grossamount = ' . $grossAmount . 
                                    ', itemcount = ' . $itemCount . 
                                    ', netamount = ' . $netAmount . 
                                    ', einvoicedatamatrixcode = "' . $qrCode . 
                                    '", taxamount = ' . $taxAmount . 
                                    ', modecode = "' . $modeCode . 
                                    '", modename = "' . $modeName . 
                                    '", grossamountword = "' . $grossAmountWords . 
                                    '", currencyRate = ' . $currencyRate .
                                    ', modifieddt = NOW(), modifiedby = ' . $this->userid . 
                                    ' WHERE einvoicenumber = "' . $invoicenumber . '"'));
                                
                                $this->logger->write($this->db->log(TRUE), 'r');
                            } catch (Exception $e) {
                                $this->logger->write("Api : queryinvoice() : Failed to update the table tblinvoices. The error message is " . $e->getMessage(), 'r');
                            }
                        }
                        
                    }
                } else {
                    $this->logger->write("Api : queryinvoice() : Unsupported voucher type.", 'r');
                    $this->message = "Unsupported voucher type.";
                    $this->code = '0099';
                }
            } else {
                $this->logger->write("Api : queryinvoice() : The user is not allowed to perform this function", 'r');
                $this->message = "The user is not allowed to perform this function";
                $this->code = '0099';
            }
                       
            
            
            
            
            
            $activity = 'QUERYINVOICE: ' . $erpinvoiceid . ': ' . $erpinvoiceno . ': ' . $this->message;
            $windowsuser = trim($xml->WINDOWSUSER);
            $ipaddress = trim($xml->IPADDRESS);
            $macaddress = trim($xml->MACADDRESS);
            $systemname = trim($xml->SYSTEMNAME);
            $this->util->createerpauditlog($this->userid_u, $activity, $windowsuser, $ipaddress, $macaddress, $systemname, $xml);
        }
        
        
        
        // prepare xml response
        $this->response  =  "<ENVELOPE>";
        $this->response .=  "<HEADER>";
        $this->response .=  "<VERSION>1</VERSION>";
        $this->response .=  "<STATUS>1</STATUS>";
        $this->response .=  "</HEADER>";
        $this->response .=  "<BODY>";
        $this->response .=  "<DATA>";
        $this->response .=  "<RESPONSE>";
        $this->response .=  "<RETURNCODE>" . htmlspecialchars($this->code) . "</RETURNCODE>";
        $this->response .=  "<RETURNMESSAGE>" . htmlspecialchars($this->message) . "</RETURNMESSAGE>";
        $this->response .=  "<INVID>". htmlspecialchars($invoiceid) ."</INVID>";
        $this->response .=  "<INVNO>". htmlspecialchars($invoicenumber) ."</INVNO>";
        $this->response .=  "<ISSUEDT>". htmlspecialchars($issueddate) ."</ISSUEDT>";
        $this->response .=  "<FDN>". htmlspecialchars($fdn) ."</FDN>";
        $this->response .=  "<QRCODE>". htmlspecialchars($qr) ."</QRCODE>";
        $this->response .=  "<REFERENCE>". htmlspecialchars($ref) ."</REFERENCE>";
        $this->response .=  "<APPROVESTATUS>". htmlspecialchars($appstatus) ."</APPROVESTATUS>";
        $this->response .=  "<CNNUMBER>". htmlspecialchars($creditnotenumber) ."</CNNUMBER>";
        $this->response .=  "</RESPONSE>";
        $this->response .=  "</DATA>";
        $this->response .=  "</BODY>";
        $this->response .=  "</ENVELOPE>";
        
        $len = strlen($this->response);
        header ("CONTENT-LENGTH:".$len);
        print $this->response;
        return;
    }
    

    /**
     *	@name voidcreditnote
     *  @desc void a creditnote
     *	@return string
     *	@param NULL
     **/
    function voidcreditnote(){
        $this->logger->write("Api : voidcreditnote() : The previous URL is " . $this->f3->get('SERVER.HTTP_REFERER'), 'r');
        $this->logger->write("Api : voidcreditnote() : The current URL is " . $this->f3->get('SERVER.REQUEST_URI'), 'r');
        $operation = NULL; //tblevents
        $permission = 'CANCELCREDITNOTE'; //tblpermissions
        $event = NULL; //tblevents
        $eventnotification = NULL; //tbleventnotifications
        
        if ($this->errorcode) {
            $this->message = $this->errormessage;
            $this->code = $this->errorcode;
            
            $body = $this->code . ' : ' . $this->message;
            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
        } else {
            $invoiceid = '';
            $invoicenumber = '';
            $issueddate = '';
            $fdn = '';
            $qr = '';
            $ref = '';
            $appstatus = '';
            
            
            
            if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
                date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
            }
            
            
            $xml = simplexml_load_string($this->xml);
            $erpinvoiceid = trim($xml->VOUCHERNUMBER);
            $erpinvoiceno = trim($xml->VOUCHERREF);
            $vchtype = trim($xml->VOUCHERTYPE);
            $vchtypename = trim($xml->VOUCHERTYPENAME);
            
            $this->logger->write("Api : voidcreditnote() : The userid is: " . $this->userid_u, 'r');
            $this->logger->write("Api : voidcreditnote() : Checking permissions", 'r');
            if ($this->userpermissions[$permission]) {
                
                
                $inv_check = new DB\SQL\Mapper($this->db, 'tblcreditnotes');
                $this->logger->write("Api : voidcreditnote() : Querying credit note started", 'r');
                $inv_check->load(array('TRIM(erpcreditnoteid)=? AND TRIM(vouchertype)=? AND TRIM(vouchertypename)=?', $erpinvoiceid, $vchtype, $vchtypename));
                
                $this->logger->write($this->db->log(TRUE), 'r');
                
                if($inv_check->dry ()){
                    $this->logger->write("Api : voidcreditnote() : The credit note does not exist on eTW", 'r');
                    $this->message = 'The credit note does not exist on EFRIS';
                    $this->code = '99';
                } else {
                    $this->logger->write("Api : voidcreditnote() : The credit note was retrieved successfully", 'r');
                    
                    //Submit the cancel request
                    $data = $this->util->voidcreditnote($this->userid_u, $inv_check->creditnoteapplicationid, $inv_check->oriinvoiceno, $inv_check->referenceno);
                    $data = json_decode($data, true);
                    /**
                     * Check the feedback for success or failure
                     */
                    if (isset($data['returnCode'])){
                        $this->logger->write("Api : queryinvoice() : The operation to download the credit note not successful. The error message is " . $data['returnMessage'], 'r');
                        $this->message = $data['returnMessage'];
                        $this->code = $data['returnCode'];
                        
                        $body = $this->code . ' : ' . $this->message;
                        $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                    } else {
                        //Fetch details of the newly uploaded credit note
                        $n_data = $this->util->downloadcreditnote($this->userid_u, $inv_check->referenceno);//will return JSON.
                        //var_dump($data);
                        
                        $n_data = json_decode($n_data, true);
                        //var_dump($n_data);
                        
                        
                        if(isset($n_data['records'])){
                            
                            /*if ($n_data['records']) {
                             ;
                             } else {
                             ;
                             }*/
                            
                            foreach($n_data['records'] as $elem){
                                $refundInvoiceNo = $elem['invoiceNo'];
                                $approveStatusCode = $elem['approveStatus'];
                                $applicationTime = $elem['applicationTime']; //28/09/2020 00:43:29
                                $referenceNo = $elem['referenceNo']; //21PL010073993
                                
                                $applicationTime = str_replace('/', '-', $applicationTime);//Replace / with -
                                $applicationTime = date("Y-m-d H:i:s", strtotime($applicationTime));
                                
                                $grossAmount = $elem['grossAmount'];
                                $totalAmount = $elem['totalAmount'];
                                //$refundIssuedDate = $elem['refundIssuedDate'];
                                $refundIssuedDate = $applicationTime;//28-09-2020 00:43:29
                                $appId = $elem['id'];
                                
                                try{
                                    $this->db->exec(array('UPDATE tblcreditnotes SET refundinvoiceno = "' . $refundInvoiceNo . '", approvestatus = "' . $approveStatusCode . '", grossamount = "' . $grossAmount . '", totalamount = "' . $totalAmount . '", issueddate = "' . $refundIssuedDate . '", issuedtime = "' . $refundIssuedDate . '", creditnoteapplicationid = "' . $appId . '", applicationtime = "' . $applicationTime . '", modifieddt = NOW(), modifiedby = ' . $this->userid . ' WHERE referenceno = "' . $inv_check->referenceno . '"'));
                                    $this->logger->write($this->db->log(TRUE), 'r');
                                } catch (Exception $e) {
                                    $this->logger->write("Api : voidcreditnote() : Failed to update the table tblcreditnotes. The error message is " . $e->getMessage(), 'r');
                                }
                                
                                if ($referenceNo = $inv_check->referenceno) {
                                    $invoiceid = $appId;
                                    $invoicenumber = $refundInvoiceNo;
                                    $ref = $referenceNo;
                                    $appstatus = $this->util->decodeapprovestatus($approveStatusCode);
                                }
                                
                            }
                            
                            $this->message = 'The operation was successful';
                            $this->code = '000';
                            
                        } elseif (isset($n_data['returnCode'])){
                            $this->logger->write("Api : voidcreditnote() : The operation to download the credit note not successful. The error message is " . $n_data['returnMessage'], 'r');
                            $this->message = $n_data['returnMessage'];
                            $this->code = $n_data['returnCode'];
                            
                            $body = $this->code . ' : ' . $this->message;
                            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                        } else {
                            $this->logger->write("Api : voidcreditnote() : The operation to download the credit note not successful.", 'r');
                            $this->message = 'The operation was not successful';
                            $this->code = '1005';
                            
                            $body = $this->code . ' : ' . $this->message;
                            $this->util->sendemailnotification($this->recipientname, $this->recipientemail, $this->subject, $body, NULL, $this->apikey, $this->version);
                        }
                    }
                    
                }
            } else {
                $this->logger->write("Api : voidcreditnote() : The user is not allowed to perform this function", 'r');
                $this->message = "The user is not allowed to perform this function";
                $this->code = '0099';
            }
                        
            
            $activity = 'VOIDCREDITNOTE: ' . $erpinvoiceid . ': ' . $erpinvoiceno . ': ' . $this->message;
            $windowsuser = trim($xml->WINDOWSUSER);
            $ipaddress = trim($xml->IPADDRESS);
            $macaddress = trim($xml->MACADDRESS);
            $systemname = trim($xml->SYSTEMNAME);
            $this->util->createerpauditlog($this->userid_u, $activity, $windowsuser, $ipaddress, $macaddress, $systemname, $xml);
        }
        
        
        $this->response  =  "<ENVELOPE>";
        $this->response .=  "<HEADER>";
        $this->response .=  "<VERSION>1</VERSION>";
        $this->response .=  "<STATUS>1</STATUS>";
        $this->response .=  "</HEADER>";
        $this->response .=  "<BODY>";
        $this->response .=  "<DATA>";
        $this->response .=  "<RESPONSE>";
        $this->response .=  "<RETURNCODE>" . htmlspecialchars($this->code) . "</RETURNCODE>";
        $this->response .=  "<RETURNMESSAGE>" . htmlspecialchars($this->message) . "</RETURNMESSAGE>";
        $this->response .=  "<REFERENCE>". htmlspecialchars($ref) ."</REFERENCE>";
        $this->response .=  "<APPROVESTATUS>". htmlspecialchars($appstatus) ."</APPROVESTATUS>";
        $this->response .=  "</RESPONSE>";
        $this->response .=  "</DATA>";
        $this->response .=  "</BODY>";
        $this->response .=  "</ENVELOPE>";
        
        $len = strlen($this->response);
        header ("CONTENT-LENGTH:".$len);
        print $this->response;
        return;
    }
    
    /**
     * @name beforeroute
     * @desc invoke before any session
     *
     * @return NULL
     * @param NULL
     *
     */
    function beforeroute(){
        $this->logger->write("Api : beforeroute() : Checking client details", 'r');
        $REMOTE_ADDR = $this->f3->get('SERVER.REMOTE_ADDR');
        $REMOTE_HOST = $this->f3->get('SERVER.REMOTE_HOST');
        $REMOTE_PORT = $this->f3->get('SERVER.REMOTE_PORT');
        $REMOTE_USER = $this->f3->get('SERVER.REMOTE_USER');
        $REDIRECT_REMOTE_USER = $this->f3->get('SERVER.REDIRECT_REMOTE_USER');
        $HTTP_X_FORWARDED_FOR = $this->f3->get('SERVER.HTTP_X_FORWARDED_FOR');
        
        $this->logger->write("Api : beforeroute() : The previous URL is " . $this->f3->get('SERVER.HTTP_REFERER'), 'r');
        $this->logger->write("Api : beforeroute() : The current URL is " . $this->f3->get('SERVER.REQUEST_URI'), 'r');
        
        $url_components = parse_url($this->f3->get('SERVER.REQUEST_URI'));
        parse_str($url_components['query'], $this->params);
        
        $API_KEY = trim($this->params['apikey']); 
        
        $this->logger->write("Api : beforeroute() : The apikey is: " . $API_KEY, 'r');
        
        $this->logger->write("Api : beforeroute() : REMOTE_ADDR = " . $REMOTE_ADDR, 'r');
        $this->logger->write("Api : beforeroute() : REMOTE_HOST = " . $REMOTE_HOST, 'r');
        $this->logger->write("Api : beforeroute() : REMOTE_PORT = " . $REMOTE_PORT, 'r');
        $this->logger->write("Api : beforeroute() : REMOTE_USER = " . $REMOTE_USER, 'r');
        $this->logger->write("Api : beforeroute() : REDIRECT_REMOTE_USER = " . $REDIRECT_REMOTE_USER, 'r');
        $this->logger->write("Api : beforeroute() : HTTP_X_FORWARDED_FOR = " . $HTTP_X_FORWARDED_FOR, 'r');   
        
        //Pick the XML content from the client
        $this->xml = file_get_contents('php://input');
        $this->logger->write("Api : beforeroute() : Raw body content" . $this->xml, 'r');
        
        //Replace special characters
        /*$this->xml = htmlspecialchars_decode($this->xml, ENT_XML1);
        $this->logger->write("Api : beforeroute() : The xml after replacing special xters" . $this->xml, 'r');*/
        
        $xml = simplexml_load_string($this->xml);
        
        if(empty($xml) && empty($API_KEY)){
            
            $this->message = 'No parameters were sent!';
            $this->code = '1000';
            
            $this->errorcode = '1000';
            $this->errormessage = 'No parameters were sent!';
            
            return;
        /*} elseif (!empty($API_KEY)) {
            //The API KEY was sent as part of a GET call
            $this->logger->write("Api : beforeroute() : The apikey was sent in the GET call", 'r');
            $this->logger->write("Api : beforeroute() : The apikey is: " . $API_KEY, 'r');*/
        } else {
            $this->logger->write("Api : beforeroute() : The apikey is: " . $xml->APIKEY, 'r');
            
            if ($xml->APIKEY) {
                $this->apikey = trim($xml->APIKEY);
            } else {
                $this->apikey = $API_KEY;
            }

            $this->logger->write("Api : beforeroute() : The new apikey is: " . $this->apikey, 'r');
            
            if(empty($this->apikey)){
                $this->message = 'No API Key was specified';
                $this->code = '1001';
                
                $this->errorcode = '1001';
                $this->errormessage = 'No API Key was specified';
                
                return;
            } else {
                $apikey_check = new DB\SQL\Mapper($this->db, 'tblapikeys');
                $apikey_check->load(array('apikey=? AND status=? AND expirydt > NOW()', $this->apikey, $this->appsettings['APIKEYENABLEDSTATUS']));
                $this->logger->write($this->db->log(TRUE), 'r');
                
                if($apikey_check->dry ()){
                    $this->logger->write("Api : beforeroute() : The api key does not exist or is inactive or expired", 'r');
                    $this->code = '1002';
                    $this->message = 'The api key does not exist or is inactive or expired. Please contact your system administrator!';
                    
                    $this->errorcode = '1002';
                    $this->errormessage = 'The api key does not exist or is inactive or expired. Please contact your system administrator!';
                    
                    return;
                } else {
                    $this->logger->write("Api : beforeroute() : Checking the version of the client", 'r');
                    $this->version = $xml->VERSION;
                    
                    if (trim($this->version) == $this->appsettings['APPVERSION']) {
                        $this->logger->write("Api : beforeroute() : The client version " . trim($this->version) . " and api version " . $this->appsettings['APPVERSION'] . " match", 'r');
                    } else {
                        $this->logger->write("Api : beforeroute() : The versions do not match", 'r');
                        $this->code = '1003';
                        $this->message = 'The plugin version does not match. Please contact your system administrator!';
                        
                        $this->errorcode = '1003';
                        $this->errormessage = 'The plugin version does not match. Please contact your system administrator!';
                        
                        return;
                    }
                    
                    $this->logger->write("Api : beforeroute() : Retrieving permissions of the api key", 'r');
                    
                    /**
                     * 1. Get the api key's permissions, both inherited & customised
                     * 2. Assign them to the permissions variable
                     */
                    $apikeypg = !empty($apikey_check->permissiongroup)? $apikey_check->permissiongroup : 'NULL';//user-specific permission
                    $this->logger->write("Api : beforeroute() : PERMISSION GROUP = " . $apikeypg, 'r');
                    
                    $data = array();
                    $pr = $this->db->exec(array('SELECT DISTINCT p.code, p.value FROM tblpermissiondetails p WHERE p.groupid IN (' . $apikeypg . ')'));
                    foreach ($pr as $obj) {
                        $data[$obj['code']] = $obj['value'];//insert a KEY/VALUE pair for each permission
                    }
                    
                    $this->permissions = $data;
                    
                    $this->logger->write("Api : beforeroute() : Retrieving permissions of the current user", 'r');
                    /**
                     * 1. Get the user's permissions, both inherited & customised
                     * 2. Assign them to the userpermissions variable
                     */
                    $user_u = new users($this->db);
                    $erpuser = trim($xml->ERPUSER);
                    $user_u->getByErpUserCode(strtoupper($erpuser), $this->appsettings['ACTIVEUSERSTATUSID']);
                    $this->logger->write($this->db->log(TRUE), 'r');
                    
                    $this->logger->write("Api : beforeroute() : The current user is: " . $erpuser, 'r');
                    $this->userid_u = $user_u->id;
                    $this->username_u = $user_u->username;
                    $this->userbranch_u = $user_u->branch;
                    
                    $userpg = !empty($user_u->permissiongroup)? $user_u->permissiongroup : 'NULL';//user-specific permission
                    $this->logger->write("Api : beforeroute() : PERMISSION GROUP = " . $userpg, 'r');
                    
                    $data = array();
                    $pr = $this->db->exec(array('SELECT DISTINCT p.code, p.value FROM tblpermissiondetails p WHERE p.groupid IN (' . $userpg . ')'));
                    foreach ($pr as $obj) {
                        $data[$obj['code']] = $obj['value'];//insert a KEY/VALUE pair for each permission
                    }
                    
                    $this->userpermissions = $data;
                    
                    
                    
                    $vat_check = new DB\SQL\Mapper($this->db, 'tbltaxtypes');
                    $vat_check->load(array('TRIM(code)=?', $this->appsettings['EFRIS_VAT_TAX_TYPE_CODE']));
                    
                    if ($vat_check->dry()) {
                        $this->logger->write("Api : beforeroute() : The tax payer is not VAT registered", 'r');
                        $this->vatRegistered = 'N';
                    } else {
                        $this->logger->write("Api : beforeroute() : The tax payer is VAT registered", 'r');
                        $this->vatRegistered = 'Y';
                    }
                                                            
                    //Clear the response
                    $this->message = NULL;
                    $this->code = NULL;
                    $this->response = NULL;
                    
                    $this->errorcode = NULL;
                    $this->errormessage = NULL;
                    
                    //update lastaccessdt for the API
                    try {
                        $this->db->exec(array("UPDATE tblapikeys SET lastaccessdt = '" . date('Y-m-d H:i:s') . "' WHERE apikey = '" . $this->apikey . "'"));
                    } catch (Exception $e) {
                        $this->logger->write("Api : beforeroute() : The operation to update the lastaccessdt for the Api was not successful. The error messages is " . $e->getMessage(), 'r');
                    }
                }
            }
        }
    }
    /**
     * @name beforeroute
     * @desc invoke after any session
     *
     * @return NULL
     * @param NULL
     *
     */
    function afterroute(){
        $this->logger->write("Api : afterroute() : Cleaning up", 'r');
        
        //Wipe the content
        $this->xml = NULL;
        $this->message = NULL;
        $this->code = NULL;
        $this->action = NULL;
        $this->response = NULL;
        $this->params = NULL;
        
        $this->errorcode = NULL;
        $this->errormessage = NULL;
        
        $this->apikey = NULL;
        $this->version = NULL;
        
        $this->permissions = NULL;
        $this->userpermissions = NULL;
    }
    
    
    /**
     *
     * @name __constructor
     * @desc Constructor for the Api class
     * @return NULL
     * @param NULL
     *
     */
    function __construct(){
        $f3 = Base::instance();
        $this->f3 = $f3;
        
        $db = new DB\SQL($f3->get('dbserver'), $f3->get('dbuser'), $f3->get('dbpwd'), array(
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
        ));
        
        $this->db = $db;
        
        $logger = new Log('api.log');
        $this->logger = $logger;
        
        $data = array();
        $setting = new settings($db);
        $settings = $setting->getNoneSensitive();
        
        foreach ($settings as $obj) {
            $data[$obj['code']] = $obj['value'];//insert a KEY/VALUE pair for each setting
        }
        
        $this->appsettings = $data;
        
        $this->userid = $this->appsettings['APIUSERID'];
        $user = new users($this->db);
        $user->getByID($this->userid);
        $this->username = $user->username;
        
        $this->recipientname = $this->appsettings['SYSTEMALERTSRECIPIENTNAME'];
        $this->recipientemail = $this->appsettings['SYSTEMALERTSRECIPIENTEMAIL'];
        
        $this->subject = 'e-TaxWare: System Error (' . $this->appsettings['APPDOMAIN'] . ')';
        
        $util = new Utilities();
        $this->util = $util;
        
    }
}
?>