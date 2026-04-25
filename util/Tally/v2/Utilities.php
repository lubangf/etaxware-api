<?php
/**
 * @name Utilities.php
 * @desc This file is part of the etaxware-api app.
 * @date: 01-02-2026
 * @file: Utilities.php
 * @path: ./util/Tally/v2/Utilities.php
 * @author: francis lubanga <francis.lubanga@gmail.com>
 * @version    2.0.0
 */

Class Utilities{
    protected $f3;// store an instance of base
    protected $db;// store database connection here
    protected $logger;
    protected $appsettings;// store the setting details here  
    
    protected $username;
    protected $userid;
    protected $branch;
    
    protected $vatRegistered;
    protected $emailUrl;
    
    
    /**
     * @name sendemailnotification
     * @desc send an email notification
     * @return $status boolean
     */
    function sendemailnotification($recipientname, $recipientemail, $subject, $body, $attachments, $apikey, $version){
        $status = true;
        $response  =  "";
        $request  =  "";
        $web = \Web::instance();
        $url = 'http://127.0.0.1:8080/etaxware-api/sendmail';//api endpoint
        $header = array('Content-Type: application/xml');
        
        $recipientname = trim($recipientname);
        $recipientemail = trim($recipientemail);
        $subject = trim($subject);
        $body = trim($body);
        
        
        $request  =  '<?xml version="1.0" encoding="UTF-8"?>';
        $request .=  '<REQUEST>';
        $request .=  '<VERSION>' . htmlspecialchars($version) . '</VERSION>';
        $request .=  '<ERPUSER></ERPUSER>';
        $request .=  '<WINDOWSUSER></WINDOWSUSER>';
        $request .=  '<IPADDRESS></IPADDRESS>';
        $request .=  '<MACADDRESS></MACADDRESS>';
        $request .=  '<SYSTEMNAME></SYSTEMNAME>';
        $request .=  '<APIKEY>' . htmlspecialchars($apikey) . '</APIKEY>';
        $request .=  '<RECIPIENTNAME>' . htmlspecialchars($recipientname) . '</RECIPIENTNAME>';
        $request .=  '<RECIPIENTEMAIL>' . htmlspecialchars($recipientemail) . '</RECIPIENTEMAIL>';
        $request .=  '<SUBJECT>' . htmlspecialchars($subject) . '</SUBJECT>';
        $request .=  '<BODY>' . htmlspecialchars($body) . '</BODY>';
        $request .=  '<ATTACHMENTS>' . htmlspecialchars($attachments) . '</ATTACHMENTS>';
        $request .=  '</REQUEST>';
        
        $options = array(
            'method'  => 'POST',
            'content' => $request,
            'header' => $header
        );
        
        $this->logger->write("Utilities : sendemailnotification() : The request is " . $request, 'r');
        
        $response = $web->request($url, $options);
        
        
        $this->logger->write("Utilities : sendemailnotification() : The response is " . $response['body'], 'r');
        
        
        if (trim($response['body']) == '000') {
            $status = true;
        } else {
            $status = false;
        }
        
        $this->logger->write("Utilities : sendemailnotification() : The final status is " . $status, 'r');
        return $status;
    }
    
    
    /**
     * @name sendemailnotification_v2
     * @desc send an email notification. This version communicates with the sendemail endpoint using JSON
     * @return $status boolean
     */
    function sendemailnotification_v2($recipientname, $recipientemail, $subject, $body, $attachments, $apikey, $version){
        $status = true;
        $response  =  "";
        $request  =  "";
        $web = \Web::instance();
        
        if(trim($this->emailUrl)){
            $url = $this->emailUrl;
        } else {
            $url = 'http://127.0.0.1:8080/etaxware-api/sendmail';//api endpoint
        }
        
        $header = array('Content-Type: application/json');
        
        $recipientname = trim($recipientname);
        $recipientemail = trim($recipientemail);
        $subject = trim($subject);
        $body = trim($body);
        
        // prepare json response
        $request = array(
            "VERSION" => $version,
            "ERPUSER" => "",
            "WINDOWSUSER" => "",
            "IPADDRESS" => "",
            "MACADDRESS" => "",
            "SYSTEMNAME" => "",
            "APIKEY" => $apikey,
            "RECIPIENTNAME" => $recipientname,
            "RECIPIENTEMAIL" => $recipientemail,
            "SUBJECT" => $subject,
            "BODY" => $body,
            "ATTACHMENTS" => array()
        );
        
        
        $request = json_encode($request);
        $this->logger->write("Utilities : sendemailnotification_v2() : The request is " . $request, 'r');
        
        $options = array(
            'method'  => 'POST',
            'content' => $request,
            'header' => $header
        );
        
        $response = $web->request($url, $options);

        $this->logger->write("Utilities : sendemailnotification_v2() : The response is " . $response['body'], 'r');
        $j_response = json_decode($response['body'], TRUE);
        
        $this->logger->write("Utilities : sendemailnotification_v2() : The responseCode is: " . $j_response['response']['responseCode'], 'r');
        $this->logger->write("Utilities : sendemailnotification_v2() : The responseMessage is: " . $j_response['response']['responseMessage'], 'r');
        
        if (trim($j_response['response']['responseCode']) == '000') {
            $status = true;
        } else {
            $status = false;
        }
        
        return $status;
    }
    

    /**
     * @name xss_cleaner
     * @desc Cross Site Script  & Code Injection Sanitization
     * @return string
     * @param $input_str string
     *
     */
    function xss_cleaner($input_str) {
        $return_str = str_replace( array('<',';','|','&','>',"'",'"',')','('), array('&lt;','&#58;','&#124;','&#38;','&gt;','&apos;','&#x22;','&#x29;','&#x28;'), $input_str );
        $return_str = str_ireplace( '%3Cscript', '', $return_str );
        return $return_str;
    }
    
    /**
     * @name getinvoicetaxrate
     * @desc Get the tax rate to be used by a good/service item on a  invoice, credit note, debit note
     * @return $taxid int
     * @param $industrycode string, $buyertype string, $deemedflag, $productcode string
     *
     */
    function getinvoicetaxrate($industrycode, $buyertype, $deemedflag, $productcode) {
        //Ensure all params are not EMPTY/NULL
        if(trim($industrycode) == '') {
            $this->logger->write("Utilities : getinvoicetaxrate() : The industrycode is empty", 'r');
            return NULL;
        } elseif (trim($deemedflag) == ''){
            $this->logger->write("Utilities : getinvoicetaxrate() : The deemedflag is empty", 'r');
            return NULL;
        } elseif (trim($productcode) == ''){
            $this->logger->write("Utilities : getinvoicetaxrate() : The productcode is empty", 'r');
            return NULL;
        } elseif (trim($buyertype) == ''){
            $this->logger->write("Utilities : getinvoicetaxrate() : The buyertype is empty", 'r');
            return NULL;
        } 
        
        $this->logger->write("Utilities : getinvoicetaxrate() : industrycode = " . $industrycode, 'r');
        $this->logger->write("Utilities : getinvoicetaxrate() : buyertype = " . $buyertype, 'r');
        $this->logger->write("Utilities : getinvoicetaxrate() : deemedflag = " . $deemedflag, 'r');
        $this->logger->write("Utilities : getinvoicetaxrate() : productcode = " . $productcode, 'r');
        
        $taxid = NULL;
        
        $pdct = new products($this->db);
        $pdct->getByErpCode($productcode);
        
        $isexempt = $pdct->isexempt;
        $iszerorated = $pdct->iszerorated;
        $taxrate = $pdct->taxrate;
        $statuscode = $pdct->statuscode;
        $source = $pdct->source;
        $exclusion = $pdct->exclusion;
        
        
        
        $this->logger->write("Utilities : getinvoicetaxrate() : isexempt = " . $isexempt, 'r');
        $this->logger->write("Utilities : getinvoicetaxrate() : iszerorated = " . $iszerorated, 'r');
        $this->logger->write("Utilities : getinvoicetaxrate() : taxrate = " . $taxrate, 'r');
        $this->logger->write("Utilities : getinvoicetaxrate() : statuscode = " . $statuscode, 'r');
        $this->logger->write("Utilities : getinvoicetaxrate() : source = " . $source, 'r');
        $this->logger->write("Utilities : getinvoicetaxrate() : exclusion = " . $exclusion, 'r');
        
        
        if (trim($industrycode) == '102') {
            //Export
            $taxid = $this->appsettings['ZEROTAXRATE'];
            $this->logger->write("Utilities : getinvoicetaxrate() : Zero rate tax", 'r');
        } else {
            //Non-Export
            
            /**
             * If DEEMED flag is YES, use the standard DEEMED tax rate.
             */
            if (strtoupper(trim($deemedflag)) == 'YES') {
                $taxid = $this->appsettings['DEEMEDTAXRATE'];
                $this->logger->write("Utilities : getinvoicetaxrate() : Deemed rate tax", 'r');
            } else {
                //Non-DEEMED
                
                
                if (trim($isexempt) == '101') {
                    //Exempt
                    $taxid = $this->appsettings['EXPEMPTTAXRATE'];
                    $this->logger->write("Utilities : getinvoicetaxrate() : Exempt rate tax", 'r');
                } elseif (trim($iszerorated) == '101'){
                    //ZERORATED
                    $taxid = $this->appsettings['ZEROTAXRATE'];
                    $this->logger->write("Utilities : getinvoicetaxrate() : Zero rate tax", 'r');
                } else {
                    $taxid = $this->appsettings['STANDARDTAXRATE'];
                    $this->logger->write("Utilities : getinvoicetaxrate() : Standard rate tax", 'r');
                }
            }
        }
        
        
        return $taxid;
    }
    
    /**
     * @name getinvoicetaxrate_v2
     * @desc Get the tax rate to be used by a good/service item on a  invoice, credit note, debit note
     * @return $taxid int
     * @param $industrycode string, $buyertype string, $productcode string, $tin string, $overrideflag string, $taxpayercheckflag string
     *
     */
    function getinvoicetaxrate_v2($industrycode, $buyertype, $productcode, $tin, $overrideflag, $taxpayercheckflag) {
               
        //Ensure all params are not EMPTY/NULL
        if(trim($industrycode) == '') {
            $this->logger->write("Utilities : getinvoicetaxrate_v2() : The industrycode is empty", 'r');
            return NULL;
        } elseif (trim($productcode) == ''){
            $this->logger->write("Utilities : getinvoicetaxrate_v2() : The productcode is empty", 'r');
            return NULL;
        } elseif (trim($buyertype) == ''){
            $this->logger->write("Utilities : getinvoicetaxrate_v2() : The buyertype is empty", 'r');
            return NULL;
        }
        
        $this->logger->write("Utilities : getinvoicetaxrate_v2() : industrycode = " . $industrycode, 'r');
        $this->logger->write("Utilities : getinvoicetaxrate_v2() : buyertype = " . $buyertype, 'r');
        $this->logger->write("Utilities : getinvoicetaxrate_v2() : productcode = " . $productcode, 'r');
        
        /**
         * 1. If the OVERRIDE_TAXRATE_FLAG is set to 1, then check if the ProductCode is part of the list in tblproductoverridelist
         * 2. If the ProductCode exists in the list, then set $existsinlist to TRUE
         * 2(a). If the $existsinlist is TRUE, set the rate to STANDARD
         * 2(b). If the $existsinlist is FALSE, call getinvoicetaxrate($industrycode, $buyertype, $deemedflag, $productcode)
         * 3. Has the TIN been supplied?
         * 4(a). If TIN is not supplied, then do the following;
         * 4(a)(i). If the $existsinlist is TRUE, set the rate to STANDARD
         * 4(a)(ii). If the $existsinlist is FALSE, then do the following;
         * - Call getinvoicetaxrate($industrycode, $buyertype, $deemedflag, $productcode)
         * 4(b). If TIN is supplied, then do the following;
         * 4(b)(i). If the $taxpayercheckflag is set to FALSE then do the following;
         * - Call getinvoicetaxrate($industrycode, $buyertype, $deemedflag, $productcode)
         * 4(b)(ii). If the $taxpayercheckflag is set to TRUE then do the following;
         * 4(b)(ii)(1). Check URA for the tax status of the tax payer using the TIN and Product
         * 4(b)(ii)(2). Set the tax as per the result from the check
         */
        $pdct = new products($this->db);
        $pdct->getByErpCode($productcode);
        
        $taxid = NULL;
        $existsinlist = FALSE;
        $taxpayerType = NULL;
        $commodityCategoryTaxpayerType = NULL;
        
        $list_check = new DB\SQL\Mapper($this->db, 'tblproductoverridelist');
        $list_check->load(array('TRIM(code)=?', $productcode));
        
        if (!$list_check->dry()) {
            $this->logger->write("Utilities : getinvoicetaxrate_v2() : The product exists in the override list", 'r');
            $existsinlist = TRUE;
        } 
        
        if ($overrideflag == '1') {
            
            $this->logger->write("Api : getinvoicetaxrate_v2() : The override flag is set to Yes", 'r');
            
            if($existsinlist){
                $taxid = $this->appsettings['STANDARDTAXRATE'];
            } else {
                $taxid = $this->getinvoicetaxrate($industrycode, $buyertype, 'NO', $productcode);
            }
            
        } else {
            $this->logger->write("Api : getinvoicetaxrate_v2() : The override flag is set to No", 'r');
            
            if ($tin) {
                
                if ($taxpayercheckflag == '1') {
                    $this->logger->write("Api : getinvoicetaxrate_v2() : The taxpayer check flag is set to Yes", 'r');
                    
                    $data = $this->checktaxpayer($this->userid, $tin, $pdct['commoditycategorycode']);//will return JSON.
                    //var_dump($data);
                    $data = json_decode($data, true); //{"commodityCategory":[],"taxpayerType":"101"}
                    
                    /*
                        101	Normal taxpayer
                        102	Exempt taxpayer
                        103	Deemed taxpayer
                     */
                    if (isset($data['commodityCategory'])){
                        
                        foreach($data['commodityCategory'] as $elem){
                            
                            if ($elem['commodityCategoryCode'] == $pdct['commoditycategorycode']) {
                                $commodityCategoryTaxpayerType = $elem['commodityCategoryTaxpayerType'];
                                
                                $this->logger->write("Api : getinvoicetaxrate_v2() : The tax payer type for this commodity code is " . $commodityCategoryTaxpayerType, 'r');
                            }
                            
                        }
                        
                        if (isset($data['taxpayerType'])){
                            $taxpayerType = $data['taxpayerType'];
                            $this->logger->write("Api : getinvoicetaxrate_v2() : The general tax payer type is " . $taxpayerType, 'r');
                        }
                        
                        if ($commodityCategoryTaxpayerType == '101') { //STANDARD
                            $taxid = $this->appsettings['STANDARDTAXRATE'];
                        } elseif ($commodityCategoryTaxpayerType == '102') { //EXEMPT
                            $taxid = $this->appsettings['EXPEMPTTAXRATE'];
                        } elseif ($commodityCategoryTaxpayerType == '103') { //DEEMED
                            $taxid = $this->appsettings['DEEMEDTAXRATE'];
                        } else {
                            $taxid = $this->appsettings['STANDARDTAXRATE'];
                        }
                        
                    } elseif (isset($data['returnCode'])){
                        $taxid = $this->getinvoicetaxrate($industrycode, $buyertype, 'NO', $productcode);
                    } else {
                        $taxid = $this->getinvoicetaxrate($industrycode, $buyertype, 'NO', $productcode);
                    }
                    
                } else {
                    $this->logger->write("Api : getinvoicetaxrate_v2() : The taxpayer chec flag is set to No", 'r');
                    $taxid = $this->getinvoicetaxrate($industrycode, $buyertype, 'NO', $productcode);
                }
                
            } else {
                $this->logger->write("Api : getinvoicetaxrate_v2() : The TIN is not supplied", 'r');
                $taxid = $this->getinvoicetaxrate($industrycode, $buyertype, 'NO', $productcode);
            }
            
        }

        return $taxid;
    }
    
    /**
     * @name truncatenumber
     * @desc Truncate a number by the specified decimal places
     * @return $value float
     * @param $no string, $dec int
     *
     */
    function truncatenumber($no, $dec=NULL) {
        /**
         * 00. If the input parameter $dec is NULL, then default it to 2
         * 1. Trim the number
         * 2. Remove any commas, if present
         * 3. Check for presence of a decimal point (.)
         * 4. If there is no decimal point (.), do the following
         * 4.1 Append a decimal point (.)
         * 4.2 Append 0's equivalent to the value of the $dec input parameter, e.g. if $dec = 2, then append 2 Zeros (00)
         * 5. If there is a decimal point (.), do the following
         * 6. Get the position of the decimal point
         * 7. Count how many digits/characters are after the decimal point
         * 8. If the digits after the decimal points are less that the value of $dec, then append 0's equivalent to the difference between $dec and the # of digits
         * 9. If the digits after the more then the value of $dec, drop off the excess digits
         *
         */
        
        $this->logger->write("Utilities : truncatenumber() : The input no is " . $no, 'r');
        
        $value = '0.00';
        //$no = '8900000.2';
        //$dec = 2;
        $cntr = 0;
        $int_part = '';
        $dec_part = '';
        
        //00. If the input parameter $dec is NULL, then default it to 2
        if (is_null($dec)) {
            $dec = 2;
        }
        
        //1. Trim the number
        //2. Remove any commas, if present
        $value = str_replace(array(','), '' , trim($no));
        $len = strlen($value);
        
        $pos = stripos($value, '.');//3. Check for presence of a decimal point (.)
        
        if ($pos) {
            //4. If there is a decimal point (.)
            $int_part = substr($value, 0, $pos);
            $dec_part = substr($value, $pos + 1, $len);
            
            //7. Count how many digits/characters are after the decimal point
            $len_dec_part = strlen($dec_part);
            
            
            if ((int)$len_dec_part < (int)$dec) {
                //8. If the digits after the decimal points are less that the value of $dec, then append 0's equivalent to the difference between $dec and the # of digits
                $cntr = (int)$dec - (int)$len_dec_part;
                while ($cntr > 0) {
                    $dec_part = $dec_part . '0';
                    $cntr = $cntr - 1;
                }
            } else {
                //9. If the digits after the more then the value of $dec, drop off the excess digits
                $dec_part = substr($dec_part, 0, $dec);
            }
            
            $value = $int_part . '.' . $dec_part;
        } else {
            //5. If there is no decimal point (.)
            //5.1 Append a decimal point (.)
            $value = $value . '.';
            
            //5.2 Append 0's equivalent to the value of the $dec input parameter, e.g. if $dec = 2, then append 2 Zeros (00)
            $cntr = (int)$dec;
            while ($cntr > 0) {
                $value = $value . '0';
                $cntr = $cntr - 1;
            }
        }
        
        $this->logger->write("Utilities : truncatenumber() : The output no is " . $value, 'r');
        return $value;
    }
    
    /**
     * @name logstockadjustment
     * @desc Create a record of the stock adjustment on eTW
     * @return NULL
     * @param $userid int, $productcode string, $batchno string, $qty int, $suppliertin string, $suppliername string, $stockintype int, $productiondate date, $unitprice float, $operationtype int, $vchtype string, $vchtypename string, $vchnumber string, $vchref string, $adjustmenttype int, $remarks string
     *
     */
    function logstockadjustment($userid, $productcode, $batchno, $qty, $suppliertin, $suppliername, $stockintype, $productiondate, $unitprice, $operationtype, $vchtype, $vchtypename, $vchnumber, $vchref, $adjustmenttype, $remarks) {
        $operationtype = empty($operationtype) || is_null($operationtype)? 'NULL' : $operationtype;
        $adjustmenttype = empty($adjustmenttype) || is_null($adjustmenttype)? 'NULL' : $adjustmenttype;
        $stockintype = empty($stockintype) || is_null($stockintype)? 'NULL' : $stockintype;
        $qty = empty($qty) || is_null($qty)? 'NULL' : $qty;
        $unitprice = empty($unitprice) || is_null($unitprice)? 'NULL' : $unitprice;
        $productiondate = empty($productiondate) || is_null($productiondate)? date('Y-m-d') : $productiondate;
        
        $sql = 'INSERT INTO tblgoodsstockadjustment
                            (operationType,
                             supplierTin,
                             supplierName,
                             adjustType,
                             remarks,
                             stockInDate,
                             stockInType,
                             productionBatchNo,
                             productionDate,
                             quantity,
                             unitPrice,
                             ProductCode,
                             voucherType,
                             voucherTypeName,
                             voucherNumber,
                             voucherRef,
                             inserteddt,
                             insertedby,
                             modifieddt,
                             modifiedby)
                            VALUES ('
            . $operationtype . ', "'
                . addslashes($suppliertin) . '", "'
                    . addslashes($suppliername) . '", '
                        . $adjustmenttype . ', "'
                            . addslashes($remarks) . '", "'
                                . date('Y-m-d') . '", '
                                    . $stockintype . ', "'
                                        . addslashes($batchno) . '", "'
                                            . $productiondate . '", '
                                                . $qty . ', '
                                                    . $unitprice . ', "'
                                                        . addslashes($productcode) . '", "'
                                                            . addslashes($vchtype) . '", "'
                                                                . addslashes($vchtypename) . '", "'
                                                                    . addslashes($vchnumber) . '", "'
                                                                        . addslashes($vchref) . '", "'
                                                                            . date('Y-m-d H:i:s') . '", '
                                                                                . $userid . ', "'
                                                                                    . date('Y-m-d H:i:s') . '", '
                                                                                        . $userid . ')';
        
        $this->logger->write("Utilities : logstockadjustment() : The SQL is " . $sql, 'r');
        
        try{
            $this->db->exec(array($sql));
            $this->logger->write("Utilities : logstockadjustment() : The stock adjustment record has been added", 'r');
        } catch (Exception $e) {
            $this->logger->write("Utilities : logstockadjustment() : Failed to insert the stock adjustment record. The error message is " . $e->getMessage(), 'r');
        }  
    }
    
    /**
     * @name createinvoice
     * @desc create an createinvoice in eTW
     * @return bool
     * @param $invoicedetails array, $goods array, $taxes array, $buyer array
     *
     */
    function createinvoice($invoicedetails, $goods, $taxes, $buyer, $userid){
        /**
         * 0. Insert a new invoice and retrieve its id
         * 1. Create a param group for goods, taxes, and payments
         * 2. Modify the following arrays
         * 2.1 goods
         * 2.2 payments
         * 2.3 payments
         * 2.4 buyers
         * 3. Insert into the respective tables
         */
        
        
        try{
            
            $netamount = empty($invoicedetails['netamount'])? '0.00' : $invoicedetails['netamount'];
            $taxamount = empty($invoicedetails['taxamount'])? '0.00' : $invoicedetails['taxamount'];
            $grossamount = empty($invoicedetails['grossamount'])? '0.00' : $invoicedetails['grossamount'];
            $itemcount = empty($invoicedetails['itemcount'])? '0' : $invoicedetails['itemcount'];
            $userid = empty($userid) || trim($userid) == ''? $this->userid : $userid;
            $currencyRate = empty($invoicedetails['currencyRate'])? '1' : $invoicedetails['currencyRate'];
            
            $sql = 'INSERT INTO tblinvoices
                                    (erpinvoiceid,
                                    erpinvoiceno,
                                    antifakecode,
                                    deviceno,
                                    issueddate,
                                    issuedtime,
                                    operator,
                                    currency,
                                    oriinvoiceid,
                                    invoicetype,
                                    invoicekind,
                                    datasource,
                                    invoiceindustrycode,
                                    einvoiceid,
                                    einvoicenumber,
                                    einvoicedatamatrixcode,
                                    isbatch,
                                    netamount,
                                    taxamount,
                                    grossamount,
                                    origrossamount,
                                    itemcount,
                                    modecode,
                                    modename,
                                    remarks,
                                    buyerid,
                                    sellerid,
                                    issueddatepdf,
                                    grossamountword,
                                    isinvalid,
                                    isrefund,
                                    vouchertype,
                                    vouchertypename,
                                    currencyRate,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ("'
                . addslashes($invoicedetails['erpinvoiceid']) . '", "'
                    . addslashes($invoicedetails['erpinvoiceno']) . '", "'
                        . addslashes($invoicedetails['antifakecode']) . '", "'
                            . addslashes($invoicedetails['deviceno']) . '", "'
                                . $invoicedetails['issueddate'] . '", "'
                                        . $invoicedetails['issuedtime'] . '", "'
                                            . addslashes($invoicedetails['operator']) . '", "'
                                                . $invoicedetails['currency'] . '", "'
                                                    . $invoicedetails['oriinvoiceid'] . '", '
                                                        . $invoicedetails['invoicetype'] . ', '
                                                            . $invoicedetails['invoicekind'] . ', '
                                                                . $invoicedetails['datasource'] . ', '
                                                                    . $invoicedetails['invoiceindustrycode'] . ', "'
                                                                        . addslashes($invoicedetails['einvoiceid']) . '", "'
                                                                            . addslashes($invoicedetails['einvoicenumber']) . '", "'
                                                                                . addslashes($invoicedetails['einvoicedatamatrixcode']) . '", "'
                                                                                    . $invoicedetails['isbatch'] . '", '
                                                                                        . $netamount . ', '
                                                                                            . $taxamount . ', '
                                                                                                . $grossamount . ', '
                                                                                                    . $invoicedetails['origrossamount'] . ', '
                                                                                                        . $itemcount . ', "'
                                                                                                            . $invoicedetails['modecode'] . '", "'
                                                                                                                . $invoicedetails['modename'] . '", "'
                                                                                                                    . addslashes($invoicedetails['remarks']) . '", '
                                                                                                                        . 'NULL, '
                                                                                                                            . $invoicedetails['sellerid'] . ', "'
                                                                                                                                . $invoicedetails['issueddatepdf'] . '", "'
                                                                                                                                    . $invoicedetails['grossamountword'] . '", '
                                                                                                                                        . $invoicedetails['isinvalid'] . ', '
                                                                                                                                            . $invoicedetails['isrefund'] . ', "'
                                                                                                                                                . addslashes($invoicedetails['vchtype']) . '", "'
                                                                                                                                                    . addslashes($invoicedetails['vchtypename']) . '", '
                                                                                                                                                        . $currencyRate . ', "'
                                                                                                                                                        . date('Y-m-d H:i:s') . '", '
                                                                                                                                                            . $userid . ', "'
                                                                                                                                                                . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                    . $userid . ')';
            
            $this->logger->write("Utilities : createinvoice() : The SQL is " . $sql, 'r');
            $this->db->exec(array($sql));
            $this->logger->write("Utilities : createinvoice() : The invoice has been added", 'r');
            
            
            $this->logger->write("Utilities : createinvoice() : The FDN is " . $invoicedetails['antifakecode'], 'r');
            
            $data = array();
            $r = $this->db->exec(array(
                'SELECT id "id" FROM tblinvoices WHERE TRIM(antifakecode) = \'' . $invoicedetails['antifakecode'] . '\''
            ));
            
            foreach ($r as $obj) {
                $data[] = $obj;
            }
            
            $id = $data[0]['id'];
            
            try {
                $paramgroupdescription = "This is an autogenerated group id for the invoice id " . $id;
                
                $this->db->exec(array('INSERT INTO tblgooddetailgroups (owner, entitytype, description, inserteddt, insertedby, modifieddt, modifiedby)
                                            VALUES(' . $id . ', ' . $this->appsettings['INVOICEENTITYTYPE'] . ', "' . $paramgroupdescription . '", NOW(), ' . $userid . ', NOW(), ' . $userid . ')'));
                
                try {
                    $pg = array ();
                    $r = $this->db->exec(array('SELECT MAX(id) "id" FROM tblgooddetailgroups WHERE owner = ' . $id . ' AND entitytype = ' . $this->appsettings['INVOICEENTITYTYPE'] . ' AND insertedby = ' . $userid));
                    
                    foreach ( $r as $obj ) {
                        $pg [] = $obj;
                    }
                    
                    $gooddetailgroupid = $pg[0]['id'];
                    $this->db->exec(array('UPDATE tblinvoices SET gooddetailgroupid = ' . $gooddetailgroupid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                    
                    /*Insert Goods*/
                    
                    $i = 0;
                    foreach ($goods as $obj) {

                        
                        $obj['item'] = empty($obj['item'])? '' : $obj['item'];
                        $obj['itemcode'] = empty($obj['itemcode'])? '' : $obj['itemcode'];
                        $obj['qty'] = empty($obj['qty'])? 'NULL' : $obj['qty'];
                        $obj['unitofmeasure'] = empty($obj['unitofmeasure'])? '' : $obj['unitofmeasure'];
                        $obj['unitprice'] = empty($obj['unitprice'])? 'NULL' : $obj['unitprice'];
                        $obj['total'] = empty($obj['total'])? 'NULL' : $obj['total'];
                        $obj['taxrate'] = empty($obj['taxrate'])? 'NULL' : $obj['taxrate'];
                        $obj['tax'] = empty($obj['tax'])? '0.00' : $obj['tax'];
                        $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? 'NULL' : $obj['discounttotal'];
                        $obj['discounttaxrate'] = (empty($obj['discounttaxrate']) || $obj['discounttaxrate'] == '0.00')? 'NULL' : $obj['discounttaxrate'];
                        $obj['discountflag'] = empty($obj['discountflag'])? '2' : $obj['discountflag'];
                        $obj['deemedflag'] = empty($obj['deemedflag'])? '2' : $obj['deemedflag'];
                        $obj['exciseflag'] = empty($obj['exciseflag'])? '2' : $obj['exciseflag'];
                        $obj['categoryid'] = empty($obj['categoryid'])? 'NULL' : $obj['categoryid'];
                        $obj['categoryname'] = empty($obj['categoryname'])? '' : $obj['categoryname'];
                        $obj['goodscategoryid'] = empty($obj['goodscategoryid'])? 'NULL' : $obj['goodscategoryid'];
                        $obj['goodscategoryname'] = empty($obj['goodscategoryname'])? '' : $obj['goodscategoryname'];
                        $obj['exciserate'] = empty($obj['exciserate'])? '' : $obj['exciserate'];
                        $obj['exciserule'] = empty($obj['exciserule'])? 'NULL' : $obj['exciserule'];
                        $obj['excisetax'] = empty($obj['excisetax'])? 'NULL' : $obj['excisetax'];
                        $obj['pack'] = empty($obj['pack'])? 'NULL' : $obj['pack'];
                        $obj['stick'] = empty($obj['stick'])? 'NULL' : $obj['stick'];
                        $obj['exciseunit'] = empty($obj['exciseunit'])? 'NULL' : $obj['exciseunit'];
                        $obj['excisecurrency'] = empty($obj['excisecurrency'])? '' : $obj['excisecurrency'];
                        $obj['exciseratename'] = empty($obj['exciseratename'])? '' : $obj['exciseratename'];
                        $obj['vatProjectId'] = empty($obj['vatProjectId'])? '' : $obj['vatProjectId'];
                        $obj['vatProjectName'] = empty($obj['exciseratename'])? '' : $obj['vatProjectName'];
                        
                        $sql = 'INSERT INTO tblgooddetails (
                                    groupid,
                                    item,
                                    itemcode,
                                    qty,
                                    unitofmeasure,
                                    unitprice,
                                    total,
                                    taxid,
                                    taxrate,
                                    tax,
                                    discounttotal,
                                    discounttaxrate,
                                    discountpercentage,
                                    ordernumber,
                                    discountflag,
                                    deemedflag,
                                    exciseflag,
                                    categoryid,
                                    categoryname,
                                    goodscategoryid,
                                    goodscategoryname,
                                    exciserate,
                                    exciserule,
                                    excisetax,
                                    pack,
                                    stick,
                                    exciseunit,
                                    excisecurrency,
                                    exciseratename,
                                    taxcategory,
                                    unitofmeasurename,
                                    projectId,
                                    projectName,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ('
                            . $gooddetailgroupid . ', "'
                                . addslashes($obj['item']) . '", "'
                                    . addslashes($obj['itemcode']) . '", '
                                        . $obj['qty'] . ', "'
                                            . $obj['unitofmeasure'] . '", '
                                                . $obj['unitprice'] . ', '
                                                    . $obj['total'] . ', '
                                                        . $obj['taxid'] . ', '
                                                            . $obj['taxrate'] . ', '
                                                                . $obj['tax'] . ', '
                                                                    . $obj['discounttotal'] . ', '
                                                                        . $obj['discounttaxrate'] . ', '
                                                                            . $obj['discountpercentage'] . ', '
                                                                                . $i . ', '
                                                                                    . $obj['discountflag'] . ', '
                                                                                        . $obj['deemedflag'] . ', '
                                                                                            . $obj['exciseflag'] . ', '
                                                                                                . $obj['categoryid'] . ', "'
                                                                                                    . addslashes($obj['categoryname']) . '", '
                                                                                                        . $obj['goodscategoryid'] . ', "'
                                                                                                            . addslashes($obj['goodscategoryname']) . '", "'
                                                                                                                . $obj['exciserate'] . '", '
                                                                                                                    . $obj['exciserule'] . ', '
                                                                                                                        . $obj['excisetax'] . ', '
                                                                                                                            . $obj['pack'] . ', '
                                                                                                                                . $obj['stick'] . ', '
                                                                                                                                    . $obj['exciseunit'] . ', "'
                                                                                                                                        . $obj['excisecurrency'] . '", "'
                                                                                                                                            . $obj['exciseratename'] . '", "'
                                                                                                                                                . $obj['taxdisplaycategory'] . '", "'
                                                                                                                                                    . $obj['unitofmeasurename'] . '", "'
                                                                                                                                                    . $obj['vatProjectId'] . '", "'
                                                                                                                                                    . $obj['vatProjectName'] . '", "'
                                                                                                                                                        . date('Y-m-d H:i:s') . '", '
                                                                                                                                                            . $userid . ', "'
                                                                                                                                                                . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                    . $userid . ')';
                                                                                                    
                        $this->logger->write("Utilities : createinvoice() : The SQL is " . $sql, 'r');
                        $this->db->exec(array($sql));
                        
                        $i = $i + 1;
                    }
                    
                } catch (Exception $e) {
                    $this->logger->write("Utilities : createinvoice() : Failed to select and insert into table tblgooddetailgroups. The error message is " . $e->getMessage(), 'r');
                }
            } catch (Exception $e) {
                $this->logger->write("Utilities : createinvoice() : Failed to insert into the table tblgooddetailgroups. The error message is " . $e->getMessage(), 'r');
            }
            
            
            try {
                $paramgroupdescription = "This is an autogenerated group id for the invoice id " . $id;
                
                $this->db->exec(array('INSERT INTO tblpaymentdetailgroups (owner, entitytype, description, inserteddt, insertedby, modifieddt, modifiedby)
                                            VALUES(' . $id . ', ' . $this->appsettings['INVOICEENTITYTYPE'] . ', "' . $paramgroupdescription . '", NOW(), ' . $userid . ', NOW(), ' . $userid . ')'));
                
                try {
                    $pg = array ();
                    $r = $this->db->exec(array('SELECT MAX(id) "id" FROM tblpaymentdetailgroups WHERE owner = ' . $id . ' AND entitytype = ' . $this->appsettings['INVOICEENTITYTYPE'] . ' AND insertedby = ' . $userid));
                    
                    foreach ( $r as $obj ) {
                        $pg [] = $obj;
                    }
                    
                    $paymentdetailgroupid = $pg[0]['id'];
                    $this->db->exec(array('UPDATE tblinvoices SET paymentdetailgroupid = ' . $paymentdetailgroupid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                } catch (Exception $e) {
                    $this->logger->write("Utilities : createinvoice() : Failed to select from table tblpaymentdetailgroups. The error message is " . $e->getMessage(), 'r');
                }
            } catch (Exception $e) {
                $this->logger->write("Utilities : createinvoice() : Failed to insert into the table tblpaymentdetailgroups. The error message is " . $e->getMessage(), 'r');
            }
            
            
            try {
                $paramgroupdescription = "This is an autogenerated group id for the invoice id " . $id;
                
                $this->db->exec(array('INSERT INTO tbltaxdetailgroups (owner, entitytype, description, inserteddt, insertedby, modifieddt, modifiedby)
                                            VALUES(' . $id . ', ' . $this->appsettings['INVOICEENTITYTYPE'] . ', "' . $paramgroupdescription . '", NOW(), ' . $userid . ', NOW(), ' . $userid . ')'));
                
                try {
                    $pg = array ();
                    $r = $this->db->exec(array('SELECT MAX(id) "id" FROM tbltaxdetailgroups WHERE owner = ' . $id . ' AND entitytype = ' . $this->appsettings['INVOICEENTITYTYPE'] . ' AND insertedby = ' . $userid));
                    
                    foreach ( $r as $obj ) {
                        $pg [] = $obj;
                    }
                    
                    $taxdetailgroupid = $pg[0]['id'];
                    $this->db->exec(array('UPDATE tblinvoices SET taxdetailgroupid = ' . $taxdetailgroupid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                    
                    //Get details of goods inserted
                    $t_goods = $this->db->exec(array('SELECT * FROM tblgooddetails g WHERE g.groupid = ' . $gooddetailgroupid . ' ORDER BY id ASC'));
                    
                    //Insert Taxes
                    $j = 0;
                    foreach ($taxes as $obj) {
                        /**
                         * Modification Date: 2021-01-26
                         * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                         * */
                        if (trim($obj['discountflag']) == '1') {
                            $obj['d_grossamount'] = $obj['discounttotal'];/*Should be a negative #*/
                            $obj['d_taxamount'] = ($obj['d_grossamount']/($obj['discounttaxrate'] + 1)) * $obj['discounttaxrate'];
                            $obj['d_netamount'] = $obj['d_grossamount'] - $obj['d_taxamount'];
                            
                            $this->logger->write("Utilities : createinvoice() : Calculating taxes. The d_grossamount is " . $obj['d_grossamount'], 'r');
                            $this->logger->write("Utilities : createinvoice() : Calculating taxes. The d_taxamount is " . $obj['d_taxamount'], 'r');
                            $this->logger->write("Utilities : createinvoice() : Calculating taxes. The d_netamount is " . $obj['d_netamount'], 'r');
                        }
                        
                        if (strtoupper(trim($obj['taxcategory'])) == 'DEEMED') {
                            $obj['netamount'] = floor(($obj['netamount'] + $obj['d_netamount'])*100)/100;
                            $obj['taxamount'] = floor(($obj['taxamount'] + $obj['d_taxamount'])*100)/100;
                            //$obj['grossamount'] = floor($obj['grossamount']*100)/100;
                            $obj['grossamount'] = $obj['netamount'] + $obj['taxamount'];
                            
                            $obj['netamount'] = number_format($obj['netamount'], 2, '.', '');
                            $obj['taxamount'] = number_format($obj['taxamount'], 2, '.', '');
                            $obj['grossamount'] = number_format($obj['grossamount'], 2, '.', '');                         
                        } else {
                            $obj['netamount'] = round(($obj['netamount'] + $obj['d_netamount']), 2);
                            $obj['taxamount'] = round(($obj['taxamount'] + $obj['d_taxamount']), 2);
                            //$obj['grossamount'] = round($obj['grossamount'], 2);
                            $obj['grossamount'] = $obj['netamount'] + $obj['taxamount'];                            
                        }
                        
                        $obj['taxcategory'] = empty($obj['taxcategory'])? '' : $obj['taxcategory'];
                        $obj['netamount'] = empty($obj['netamount'])? 'NULL' : $obj['netamount'];
                        $obj['taxrate'] = empty($obj['taxrate'])? '' : $obj['taxrate'];
                        $obj['taxamount'] = empty($obj['taxamount'])? '0.00' : $obj['taxamount'];
                        $obj['grossamount'] = empty($obj['grossamount'])? 'NULL' : $obj['grossamount'];
                        $obj['exciseunit'] = empty($obj['exciseunit'])? '' : $obj['exciseunit'];
                        $obj['excisecurrency'] = empty($obj['excisecurrency'])? '' : $obj['excisecurrency'];
                        $obj['taxratename'] = empty($obj['taxratename'])? '' : $obj['taxratename'];
                        
                        //$obj['goodid'] = empty($obj['goodid'])? 'NULL' : $obj['goodid'];
                        $obj['goodid'] = $t_goods[$j]['id'];
                        
                        $sql = 'INSERT INTO tbltaxdetails (
                                    groupid,
                                    goodid,
                                    taxcategory,
                                    netamount,
                                    taxrate,
                                    taxamount,
                                    grossamount,
                                    exciseunit,
                                    excisecurrency,
                                    taxratename,
                                    taxdescription,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ('
                            . $taxdetailgroupid . ', '
                                . $obj['goodid'] . ', "'
                                    . addslashes($obj['taxcategory']) . '", '
                                        . $obj['netamount'] . ', '
                                            . $obj['taxrate'] . ', '
                                                . $obj['taxamount'] . ', '
                                                    . $obj['grossamount'] . ', "'
                                                        . $obj['exciseunit'] . '", "'
                                                            . $obj['excisecurrency'] . '", "'
                                                                . $obj['taxratename'] . '", "'
                                                                    . $obj['taxdescription'] . '", "'
                                                                        . date('Y-m-d H:i:s') . '", '
                                                                            . $userid . ', "'
                                                                                . date('Y-m-d H:i:s') . '", '
                                                                                    . $userid . ')';
                        
                        $this->logger->write("Utilities : createinvoice() : The SQL is " . $sql, 'r');
                        $this->db->exec(array($sql));
                        $j = $j + 1;
                        
                    }
                } catch (Exception $e) {
                    $this->logger->write("Utilities : createinvoice() : Failed to select from table tbltaxdetailgroups. The error message is " . $e->getMessage(), 'r');
                }
            } catch (Exception $e) {
                $this->logger->write("Utilities : createinvoice() : Failed to insert into the table tbltaxdetailgroups. The error message is " . $e->getMessage(), 'r');
            }
            
            
            if (trim($buyer['referenceno']) !== '' || !empty(trim($buyer['referenceno']))) {
                try{
                    
                    $sql = 'INSERT INTO tblbuyers (
                                    tin,
                                    ninbrn,
                                    PassportNum,
                                    legalname,
                                    businessname,
                                    address,
                                    mobilephone,
                                    linephone,
                                    emailaddress,
                                    placeofbusiness,
                                    type,
                                    citizineship,
                                    sector,
                                    referenceno,
                                    datasource,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ("'
                        . addslashes($buyer['tin']) . '", "'
                            . addslashes($buyer['ninbrn']) . '", "'
                                . addslashes($buyer['PassportNum']) . '", "'
                                    . addslashes($buyer['legalname']) . '", "'
                                        . addslashes($buyer['businessname']) . '", "'
                                            . addslashes($buyer['address']) . '", "'
                                                . addslashes($buyer['mobilephone']) . '", "'
                                                    . addslashes($buyer['linephone']) . '", "'
                                                        . addslashes($buyer['emailaddress']) . '", "'
                                                            . addslashes($buyer['placeofbusiness']) . '", "'
                                                                . $buyer['type'] . '", "'
                                                                    . addslashes($buyer['citizineship']) . '", "'
                                                                        . addslashes($buyer['sector']) . '", "'
                                                                            . addslashes($buyer['referenceno']) . '", "'
                                                                                . $buyer['datasource'] . '", "'
                                                                                    . date('Y-m-d H:i:s') . '", '
                                                                                        . $userid . ', "'
                                                                                            . date('Y-m-d H:i:s') . '", '
                                                                                                . $userid . ')';
                                                                                                
                                                                                                $this->logger->write("Utilities : createinvoice() : The SQL is " . $sql, 'r');
                                                                                                $this->db->exec(array($sql));
                                                                                                
                                                                                                try {
                                                                                                    $by = array ();
                                                                                                    $r = $this->db->exec(array('SELECT id "id" FROM tblbuyers WHERE referenceno = "' . $buyer['referenceno'] . '" AND insertedby = ' . $userid));
                                                                                                    
                                                                                                    foreach ( $r as $obj ) {
                                                                                                        $by [] = $obj;
                                                                                                    }
                                                                                                    
                                                                                                    $buyerid = $by[0]['id'];
                                                                                                    $this->db->exec(array('UPDATE tblinvoices SET buyerid = ' . $buyerid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                                                                                                } catch (Exception $e) {
                                                                                                    $this->logger->write("Utilities : createinvoice() : Failed to select and update table tblinvoices. The error message is " . $e->getMessage(), 'r');
                                                                                                }
                } catch (Exception $e) {
                    $this->logger->write("Utilities : createinvoice() : The operation to create a buyer was not successful. The error message is " . $e->getMessage(), 'r');
                }
            }
            
            return true;
        } catch (Exception $e) {
            $this->logger->write("Utilities : createinvoice() : The operation to create the invoice was not successful. The error message is " . $e->getMessage(), 'r');
            return false;
        }
    }
    
    /**
     * @name createcreditmemo
     * @desc create a credit memo in eTW
     * @return bool
     * @param $invoicedetails array, $goods array, $taxes array, $buyer array
     *
     */
    function createcreditmemo($invoicedetails, $goods, $taxes, $buyer, $userid){
        /**
         * 0. Insert a new invoice and retrieve its id
         * 1. Create a param group for goods, taxes, and payments
         * 2. Modify the following arrays
         * 2.1 goods
         * 2.2 payments
         * 2.3 payments
         * 2.4 buyers
         * 3. Insert into the respective tables
         */
        
        
        try{
            
            $netamount = empty($invoicedetails['netamount'])? '0.00' : $invoicedetails['netamount'];
            $taxamount = empty($invoicedetails['taxamount'])? '0.00' : $invoicedetails['taxamount'];
            $grossamount = empty($invoicedetails['grossamount'])? '0.00' : $invoicedetails['grossamount'];
            $itemcount = empty($invoicedetails['itemcount'])? '0' : $invoicedetails['itemcount'];
            $userid = empty($userid) || trim($userid) == ''? $this->userid : $userid;
            $currencyRate = empty($invoicedetails['currencyRate'])? '1' : $invoicedetails['currencyRate'];
            
            $sql = 'INSERT INTO tblcreditmemos
                                    (erpinvoiceid,
                                    erpinvoiceno,
                                    antifakecode,
                                    deviceno,
                                    issueddate,
                                    issuedtime,
                                    operator,
                                    currency,
                                    oriinvoiceid,
                                    invoicetype,
                                    invoicekind,
                                    datasource,
                                    invoiceindustrycode,
                                    einvoiceid,
                                    einvoicenumber,
                                    einvoicedatamatrixcode,
                                    isbatch,
                                    netamount,
                                    taxamount,
                                    grossamount,
                                    origrossamount,
                                    itemcount,
                                    modecode,
                                    modename,
                                    remarks,
                                    buyerid,
                                    sellerid,
                                    issueddatepdf,
                                    grossamountword,
                                    isinvalid,
                                    isrefund,
                                    vouchertype,
                                    vouchertypename,
                                    currencyRate,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ("'
                . addslashes($invoicedetails['erpinvoiceid']) . '", "'
                    . addslashes($invoicedetails['erpinvoiceno']) . '", "'
                        . addslashes($invoicedetails['antifakecode']) . '", "'
                            . addslashes($invoicedetails['deviceno']) . '", "'
                                . $invoicedetails['issueddate'] . '", "'
                                    . $invoicedetails['issuedtime'] . '", "'
                                        . addslashes($invoicedetails['operator']) . '", "'
                                            . $invoicedetails['currency'] . '", "'
                                                . $invoicedetails['oriinvoiceid'] . '", '
                                                    . $invoicedetails['invoicetype'] . ', '
                                                        . $invoicedetails['invoicekind'] . ', '
                                                            . $invoicedetails['datasource'] . ', '
                                                                . $invoicedetails['invoiceindustrycode'] . ', "'
                                                                    . addslashes($invoicedetails['einvoiceid']) . '", "'
                                                                        . addslashes($invoicedetails['einvoicenumber']) . '", "'
                                                                            . addslashes($invoicedetails['einvoicedatamatrixcode']) . '", "'
                                                                                . $invoicedetails['isbatch'] . '", '
                                                                                    . $netamount . ', '
                                                                                        . $taxamount . ', '
                                                                                            . $grossamount . ', '
                                                                                                . $invoicedetails['origrossamount'] . ', '
                                                                                                    . $itemcount . ', "'
                                                                                                        . $invoicedetails['modecode'] . '", "'
                                                                                                            . $invoicedetails['modename'] . '", "'
                                                                                                                . addslashes($invoicedetails['remarks']) . '", '
                                                                                                                    . 'NULL, '
                                                                                                                        . $invoicedetails['sellerid'] . ', "'
                                                                                                                            . $invoicedetails['issueddatepdf'] . '", "'
                                                                                                                                . $invoicedetails['grossamountword'] . '", '
                                                                                                                                    . $invoicedetails['isinvalid'] . ', '
                                                                                                                                        . $invoicedetails['isrefund'] . ', "'
                                                                                                                                            . addslashes($invoicedetails['vchtype']) . '", "'
                                                                                                                                                . addslashes($invoicedetails['vchtypename']) . '", '
                                                                                                                                                    . $currencyRate . ', "'
                                                                                                                                                        . date('Y-m-d H:i:s') . '", '
                                                                                                                                                            . $userid . ', "'
                                                                                                                                                                . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                    . $userid . ')';
                                                                                                                                                                    
                                                                                                                                                                    $this->logger->write("Utilities : createcreditmemo() : The SQL is " . $sql, 'r');
                                                                                                                                                                    $this->db->exec(array($sql));
                                                                                                                                                                    $this->logger->write("Utilities : createcreditmemo() : The invoice has been added", 'r');
                                                                                                                                                                    
                                                                                                                                                                    
                                                                                                                                                                    $this->logger->write("Utilities : createcreditmemo() : The FDN is " . $invoicedetails['antifakecode'], 'r');
                                                                                                                                                                    
                                                                                                                                                                    $data = array();
                                                                                                                                                                    $r = $this->db->exec(array(
                                                                                                                                                                        'SELECT id "id" FROM tblcreditmemos WHERE TRIM(antifakecode) = \'' . $invoicedetails['antifakecode'] . '\''
                                                                                                                                                                    ));
                                                                                                                                                                    
                                                                                                                                                                    foreach ($r as $obj) {
                                                                                                                                                                        $data[] = $obj;
                                                                                                                                                                    }
                                                                                                                                                                    
                                                                                                                                                                    $id = $data[0]['id'];
                                                                                                                                                                    
                                                                                                                                                                    try {
                                                                                                                                                                        $paramgroupdescription = "This is an autogenerated group id for the invoice id " . $id;
                                                                                                                                                                        
                                                                                                                                                                        $this->db->exec(array('INSERT INTO tblgooddetailgroups (owner, entitytype, description, inserteddt, insertedby, modifieddt, modifiedby)
                                            VALUES(' . $id . ', ' . $this->appsettings['CREDITMEMOENTITYTYPE'] . ', "' . $paramgroupdescription . '", NOW(), ' . $userid . ', NOW(), ' . $userid . ')'));
                                                                                                                                                                        
                                                                                                                                                                        try {
                                                                                                                                                                            $pg = array ();
                                                                                                                                                                            $r = $this->db->exec(array('SELECT MAX(id) "id" FROM tblgooddetailgroups WHERE owner = ' . $id . ' AND entitytype = ' . $this->appsettings['CREDITMEMOENTITYTYPE'] . ' AND insertedby = ' . $userid));
                                                                                                                                                                            
                                                                                                                                                                            foreach ( $r as $obj ) {
                                                                                                                                                                                $pg [] = $obj;
                                                                                                                                                                            }
                                                                                                                                                                            
                                                                                                                                                                            $gooddetailgroupid = $pg[0]['id'];
                                                                                                                                                                            $this->db->exec(array('UPDATE tblcreditmemos SET gooddetailgroupid = ' . $gooddetailgroupid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                                                                                                                                                                            
                                                                                                                                                                            /*Insert Goods*/
                                                                                                                                                                            
                                                                                                                                                                            $i = 0;
                                                                                                                                                                            foreach ($goods as $obj) {
                                                                                                                                                                                
                                                                                                                                                                                
                                                                                                                                                                                $obj['item'] = empty($obj['item'])? '' : $obj['item'];
                                                                                                                                                                                $obj['itemcode'] = empty($obj['itemcode'])? '' : $obj['itemcode'];
                                                                                                                                                                                $obj['qty'] = empty($obj['qty'])? 'NULL' : $obj['qty'];
                                                                                                                                                                                $obj['unitofmeasure'] = empty($obj['unitofmeasure'])? '' : $obj['unitofmeasure'];
                                                                                                                                                                                $obj['unitprice'] = empty($obj['unitprice'])? 'NULL' : $obj['unitprice'];
                                                                                                                                                                                $obj['total'] = empty($obj['total'])? 'NULL' : $obj['total'];
                                                                                                                                                                                $obj['taxrate'] = empty($obj['taxrate'])? 'NULL' : $obj['taxrate'];
                                                                                                                                                                                $obj['tax'] = empty($obj['tax'])? '0.00' : $obj['tax'];
                                                                                                                                                                                $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? 'NULL' : $obj['discounttotal'];
                                                                                                                                                                                $obj['discounttaxrate'] = (empty($obj['discounttaxrate']) || $obj['discounttaxrate'] == '0.00')? 'NULL' : $obj['discounttaxrate'];
                                                                                                                                                                                $obj['discountflag'] = empty($obj['discountflag'])? '2' : $obj['discountflag'];
                                                                                                                                                                                $obj['deemedflag'] = empty($obj['deemedflag'])? '2' : $obj['deemedflag'];
                                                                                                                                                                                $obj['exciseflag'] = empty($obj['exciseflag'])? '2' : $obj['exciseflag'];
                                                                                                                                                                                $obj['categoryid'] = empty($obj['categoryid'])? 'NULL' : $obj['categoryid'];
                                                                                                                                                                                $obj['categoryname'] = empty($obj['categoryname'])? '' : $obj['categoryname'];
                                                                                                                                                                                $obj['goodscategoryid'] = empty($obj['goodscategoryid'])? 'NULL' : $obj['goodscategoryid'];
                                                                                                                                                                                $obj['goodscategoryname'] = empty($obj['goodscategoryname'])? '' : $obj['goodscategoryname'];
                                                                                                                                                                                $obj['exciserate'] = empty($obj['exciserate'])? '' : $obj['exciserate'];
                                                                                                                                                                                $obj['exciserule'] = empty($obj['exciserule'])? 'NULL' : $obj['exciserule'];
                                                                                                                                                                                $obj['excisetax'] = empty($obj['excisetax'])? 'NULL' : $obj['excisetax'];
                                                                                                                                                                                $obj['pack'] = empty($obj['pack'])? 'NULL' : $obj['pack'];
                                                                                                                                                                                $obj['stick'] = empty($obj['stick'])? 'NULL' : $obj['stick'];
                                                                                                                                                                                $obj['exciseunit'] = empty($obj['exciseunit'])? 'NULL' : $obj['exciseunit'];
                                                                                                                                                                                $obj['excisecurrency'] = empty($obj['excisecurrency'])? '' : $obj['excisecurrency'];
                                                                                                                                                                                $obj['exciseratename'] = empty($obj['exciseratename'])? '' : $obj['exciseratename'];
                                                                                                                                                                                
                                                                                                                                                                                $sql = 'INSERT INTO tblgooddetails (
                                    groupid,
                                    item,
                                    itemcode,
                                    qty,
                                    unitofmeasure,
                                    unitprice,
                                    total,
                                    taxid,
                                    taxrate,
                                    tax,
                                    discounttotal,
                                    discounttaxrate,
                                    discountpercentage,
                                    ordernumber,
                                    discountflag,
                                    deemedflag,
                                    exciseflag,
                                    categoryid,
                                    categoryname,
                                    goodscategoryid,
                                    goodscategoryname,
                                    exciserate,
                                    exciserule,
                                    excisetax,
                                    pack,
                                    stick,
                                    exciseunit,
                                    excisecurrency,
                                    exciseratename,
                                    taxcategory,
                                    unitofmeasurename,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ('
                                                                                                                                                                                    . $gooddetailgroupid . ', "'
                                                                                                                                                                                        . addslashes($obj['item']) . '", "'
                                                                                                                                                                                            . addslashes($obj['itemcode']) . '", '
                                                                                                                                                                                                . $obj['qty'] . ', "'
                                                                                                                                                                                                    . $obj['unitofmeasure'] . '", '
                                                                                                                                                                                                        . $obj['unitprice'] . ', '
                                                                                                                                                                                                            . $obj['total'] . ', '
                                                                                                                                                                                                                . $obj['taxid'] . ', '
                                                                                                                                                                                                                    . $obj['taxrate'] . ', '
                                                                                                                                                                                                                        . $obj['tax'] . ', '
                                                                                                                                                                                                                            . $obj['discounttotal'] . ', '
                                                                                                                                                                                                                                . $obj['discounttaxrate'] . ', '
                                                                                                                                                                                                                                    . $obj['discountpercentage'] . ', '
                                                                                                                                                                                                                                        . $i . ', '
                                                                                                                                                                                                                                            . $obj['discountflag'] . ', '
                                                                                                                                                                                                                                                . $obj['deemedflag'] . ', '
                                                                                                                                                                                                                                                    . $obj['exciseflag'] . ', '
                                                                                                                                                                                                                                                        . $obj['categoryid'] . ', "'
                                                                                                                                                                                                                                                            . addslashes($obj['categoryname']) . '", '
                                                                                                                                                                                                                                                                . $obj['goodscategoryid'] . ', "'
                                                                                                                                                                                                                                                                    . addslashes($obj['goodscategoryname']) . '", "'
                                                                                                                                                                                                                                                                        . $obj['exciserate'] . '", '
                                                                                                                                                                                                                                                                            . $obj['exciserule'] . ', '
                                                                                                                                                                                                                                                                                . $obj['excisetax'] . ', '
                                                                                                                                                                                                                                                                                    . $obj['pack'] . ', '
                                                                                                                                                                                                                                                                                        . $obj['stick'] . ', '
                                                                                                                                                                                                                                                                                            . $obj['exciseunit'] . ', "'
                                                                                                                                                                                                                                                                                                . $obj['excisecurrency'] . '", "'
                                                                                                                                                                                                                                                                                                    . $obj['exciseratename'] . '", "'
                                                                                                                                                                                                                                                                                                        . $obj['taxdisplaycategory'] . '", "'
                                                                                                                                                                                                                                                                                                            . $obj['unitofmeasurename'] . '", "'
                                                                                                                                                                                                                                                                                                                . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                                                                                                    . $userid . ', "'
                                                                                                                                                                                                                                                                                                                        . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                                                                                                            . $userid . ')';
                                                                                                                                                                                                                                                                                                                            
                                                                                                                                                                                                                                                                                                                            $this->logger->write("Utilities : createcreditmemo() : The SQL is " . $sql, 'r');
                                                                                                                                                                                                                                                                                                                            $this->db->exec(array($sql));
                                                                                                                                                                                                                                                                                                                            
                                                                                                                                                                                                                                                                                                                            $i = $i + 1;
                                                                                                                                                                            }
                                                                                                                                                                            
                                                                                                                                                                        } catch (Exception $e) {
                                                                                                                                                                            $this->logger->write("Utilities : createcreditmemo() : Failed to select and insert into table tblgooddetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                        }
                                                                                                                                                                    } catch (Exception $e) {
                                                                                                                                                                        $this->logger->write("Utilities : createcreditmemo() : Failed to insert into the table tblgooddetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                    }
                                                                                                                                                                    
                                                                                                                                                                    
                                                                                                                                                                    try {
                                                                                                                                                                        $paramgroupdescription = "This is an autogenerated group id for the invoice id " . $id;
                                                                                                                                                                        
                                                                                                                                                                        $this->db->exec(array('INSERT INTO tblpaymentdetailgroups (owner, entitytype, description, inserteddt, insertedby, modifieddt, modifiedby)
                                            VALUES(' . $id . ', ' . $this->appsettings['CREDITMEMOENTITYTYPE'] . ', "' . $paramgroupdescription . '", NOW(), ' . $userid . ', NOW(), ' . $userid . ')'));
                                                                                                                                                                        
                                                                                                                                                                        try {
                                                                                                                                                                            $pg = array ();
                                                                                                                                                                            $r = $this->db->exec(array('SELECT MAX(id) "id" FROM tblpaymentdetailgroups WHERE owner = ' . $id . ' AND entitytype = ' . $this->appsettings['CREDITMEMOENTITYTYPE'] . ' AND insertedby = ' . $userid));
                                                                                                                                                                            
                                                                                                                                                                            foreach ( $r as $obj ) {
                                                                                                                                                                                $pg [] = $obj;
                                                                                                                                                                            }
                                                                                                                                                                            
                                                                                                                                                                            $paymentdetailgroupid = $pg[0]['id'];
                                                                                                                                                                            $this->db->exec(array('UPDATE tblcreditmemos SET paymentdetailgroupid = ' . $paymentdetailgroupid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                                                                                                                                                                        } catch (Exception $e) {
                                                                                                                                                                            $this->logger->write("Utilities : createcreditmemo() : Failed to select from table tblpaymentdetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                        }
                                                                                                                                                                    } catch (Exception $e) {
                                                                                                                                                                        $this->logger->write("Utilities : createcreditmemo() : Failed to insert into the table tblpaymentdetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                    }
                                                                                                                                                                    
                                                                                                                                                                    
                                                                                                                                                                    try {
                                                                                                                                                                        $paramgroupdescription = "This is an autogenerated group id for the invoice id " . $id;
                                                                                                                                                                        
                                                                                                                                                                        $this->db->exec(array('INSERT INTO tbltaxdetailgroups (owner, entitytype, description, inserteddt, insertedby, modifieddt, modifiedby)
                                            VALUES(' . $id . ', ' . $this->appsettings['CREDITMEMOENTITYTYPE'] . ', "' . $paramgroupdescription . '", NOW(), ' . $userid . ', NOW(), ' . $userid . ')'));
                                                                                                                                                                        
                                                                                                                                                                        try {
                                                                                                                                                                            $pg = array ();
                                                                                                                                                                            $r = $this->db->exec(array('SELECT MAX(id) "id" FROM tbltaxdetailgroups WHERE owner = ' . $id . ' AND entitytype = ' . $this->appsettings['CREDITMEMOENTITYTYPE'] . ' AND insertedby = ' . $userid));
                                                                                                                                                                            
                                                                                                                                                                            foreach ( $r as $obj ) {
                                                                                                                                                                                $pg [] = $obj;
                                                                                                                                                                            }
                                                                                                                                                                            
                                                                                                                                                                            $taxdetailgroupid = $pg[0]['id'];
                                                                                                                                                                            $this->db->exec(array('UPDATE tblcreditmemos SET taxdetailgroupid = ' . $taxdetailgroupid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                                                                                                                                                                            
                                                                                                                                                                            //Get details of goods inserted
                                                                                                                                                                            $t_goods = $this->db->exec(array('SELECT * FROM tblgooddetails g WHERE g.groupid = ' . $gooddetailgroupid . ' ORDER BY id ASC'));
                                                                                                                                                                            
                                                                                                                                                                            //Insert Taxes
                                                                                                                                                                            $j = 0;
                                                                                                                                                                            foreach ($taxes as $obj) {
                                                                                                                                                                                /**
                                                                                                                                                                                 * Modification Date: 2021-01-26
                                                                                                                                                                                 * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                                                                                                                                                                                 * */
                                                                                                                                                                                if (trim($obj['discountflag']) == '1') {
                                                                                                                                                                                    $obj['d_grossamount'] = $obj['discounttotal'];/*Should be a negative #*/
                                                                                                                                                                                    $obj['d_taxamount'] = ($obj['d_grossamount']/($obj['discounttaxrate'] + 1)) * $obj['discounttaxrate'];
                                                                                                                                                                                    $obj['d_netamount'] = $obj['d_grossamount'] - $obj['d_taxamount'];
                                                                                                                                                                                    
                                                                                                                                                                                    $this->logger->write("Utilities : createcreditmemo() : Calculating taxes. The d_grossamount is " . $obj['d_grossamount'], 'r');
                                                                                                                                                                                    $this->logger->write("Utilities : createcreditmemo() : Calculating taxes. The d_taxamount is " . $obj['d_taxamount'], 'r');
                                                                                                                                                                                    $this->logger->write("Utilities : createcreditmemo() : Calculating taxes. The d_netamount is " . $obj['d_netamount'], 'r');
                                                                                                                                                                                }
                                                                                                                                                                                
                                                                                                                                                                                if (strtoupper(trim($obj['taxcategory'])) == 'DEEMED') {
                                                                                                                                                                                    $obj['netamount'] = floor(($obj['netamount'] + $obj['d_netamount'])*100)/100;
                                                                                                                                                                                    $obj['taxamount'] = floor(($obj['taxamount'] + $obj['d_taxamount'])*100)/100;
                                                                                                                                                                                    //$obj['grossamount'] = floor($obj['grossamount']*100)/100;
                                                                                                                                                                                    $obj['grossamount'] = $obj['netamount'] + $obj['taxamount'];
                                                                                                                                                                                    
                                                                                                                                                                                    $obj['netamount'] = number_format($obj['netamount'], 2, '.', '');
                                                                                                                                                                                    $obj['taxamount'] = number_format($obj['taxamount'], 2, '.', '');
                                                                                                                                                                                    $obj['grossamount'] = number_format($obj['grossamount'], 2, '.', '');
                                                                                                                                                                                } else {
                                                                                                                                                                                    $obj['netamount'] = round(($obj['netamount'] + $obj['d_netamount']), 2);
                                                                                                                                                                                    $obj['taxamount'] = round(($obj['taxamount'] + $obj['d_taxamount']), 2);
                                                                                                                                                                                    //$obj['grossamount'] = round($obj['grossamount'], 2);
                                                                                                                                                                                    $obj['grossamount'] = $obj['netamount'] + $obj['taxamount'];
                                                                                                                                                                                }
                                                                                                                                                                                
                                                                                                                                                                                $obj['taxcategory'] = empty($obj['taxcategory'])? '' : $obj['taxcategory'];
                                                                                                                                                                                $obj['netamount'] = empty($obj['netamount'])? 'NULL' : $obj['netamount'];
                                                                                                                                                                                $obj['taxrate'] = empty($obj['taxrate'])? '' : $obj['taxrate'];
                                                                                                                                                                                $obj['taxamount'] = empty($obj['taxamount'])? '0.00' : $obj['taxamount'];
                                                                                                                                                                                $obj['grossamount'] = empty($obj['grossamount'])? 'NULL' : $obj['grossamount'];
                                                                                                                                                                                $obj['exciseunit'] = empty($obj['exciseunit'])? '' : $obj['exciseunit'];
                                                                                                                                                                                $obj['excisecurrency'] = empty($obj['excisecurrency'])? '' : $obj['excisecurrency'];
                                                                                                                                                                                $obj['taxratename'] = empty($obj['taxratename'])? '' : $obj['taxratename'];
                                                                                                                                                                                
                                                                                                                                                                                //$obj['goodid'] = empty($obj['goodid'])? 'NULL' : $obj['goodid'];
                                                                                                                                                                                $obj['goodid'] = $t_goods[$j]['id'];
                                                                                                                                                                                
                                                                                                                                                                                $sql = 'INSERT INTO tbltaxdetails (
                                    groupid,
                                    goodid,
                                    taxcategory,
                                    netamount,
                                    taxrate,
                                    taxamount,
                                    grossamount,
                                    exciseunit,
                                    excisecurrency,
                                    taxratename,
                                    taxdescription,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ('
                                                                                                                                                                                    . $taxdetailgroupid . ', '
                                                                                                                                                                                        . $obj['goodid'] . ', "'
                                                                                                                                                                                            . addslashes($obj['taxcategory']) . '", '
                                                                                                                                                                                                . $obj['netamount'] . ', '
                                                                                                                                                                                                    . $obj['taxrate'] . ', '
                                                                                                                                                                                                        . $obj['taxamount'] . ', '
                                                                                                                                                                                                            . $obj['grossamount'] . ', "'
                                                                                                                                                                                                                . $obj['exciseunit'] . '", "'
                                                                                                                                                                                                                    . $obj['excisecurrency'] . '", "'
                                                                                                                                                                                                                        . $obj['taxratename'] . '", "'
                                                                                                                                                                                                                            . $obj['taxdescription'] . '", "'
                                                                                                                                                                                                                                . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                    . $userid . ', "'
                                                                                                                                                                                                                                        . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                            . $userid . ')';
                                                                                                                                                                                                                                            
                                                                                                                                                                                                                                            $this->logger->write("Utilities : createcreditmemo() : The SQL is " . $sql, 'r');
                                                                                                                                                                                                                                            $this->db->exec(array($sql));
                                                                                                                                                                                                                                            $j = $j + 1;
                                                                                                                                                                                                                                            
                                                                                                                                                                            }
                                                                                                                                                                        } catch (Exception $e) {
                                                                                                                                                                            $this->logger->write("Utilities : createcreditmemo() : Failed to select from table tbltaxdetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                        }
                                                                                                                                                                    } catch (Exception $e) {
                                                                                                                                                                        $this->logger->write("Utilities : createcreditmemo() : Failed to insert into the table tbltaxdetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                    }
                                                                                                                                                                    
                                                                                                                                                                    
                                                                                                                                                                    if (trim($buyer['referenceno']) !== '' || !empty(trim($buyer['referenceno']))) {
                                                                                                                                                                        try{
                                                                                                                                                                            
                                                                                                                                                                            $sql = 'INSERT INTO tblbuyers (
                                    tin,
                                    ninbrn,
                                    PassportNum,
                                    legalname,
                                    businessname,
                                    address,
                                    mobilephone,
                                    linephone,
                                    emailaddress,
                                    placeofbusiness,
                                    type,
                                    citizineship,
                                    sector,
                                    referenceno,
                                    datasource,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ("'
                                                                                                                                                                                . addslashes($buyer['tin']) . '", "'
                                                                                                                                                                                    . addslashes($buyer['ninbrn']) . '", "'
                                                                                                                                                                                        . addslashes($buyer['PassportNum']) . '", "'
                                                                                                                                                                                            . addslashes($buyer['legalname']) . '", "'
                                                                                                                                                                                                . addslashes($buyer['businessname']) . '", "'
                                                                                                                                                                                                    . addslashes($buyer['address']) . '", "'
                                                                                                                                                                                                        . addslashes($buyer['mobilephone']) . '", "'
                                                                                                                                                                                                            . addslashes($buyer['linephone']) . '", "'
                                                                                                                                                                                                                . addslashes($buyer['emailaddress']) . '", "'
                                                                                                                                                                                                                    . addslashes($buyer['placeofbusiness']) . '", "'
                                                                                                                                                                                                                        . $buyer['type'] . '", "'
                                                                                                                                                                                                                            . addslashes($buyer['citizineship']) . '", "'
                                                                                                                                                                                                                                . addslashes($buyer['sector']) . '", "'
                                                                                                                                                                                                                                    . addslashes($buyer['referenceno']) . '", "'
                                                                                                                                                                                                                                        . $buyer['datasource'] . '", "'
                                                                                                                                                                                                                                            . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                                . $userid . ', "'
                                                                                                                                                                                                                                                    . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                                        . $userid . ')';
                                                                                                                                                                                                                                                        
                                                                                                                                                                                                                                                        $this->logger->write("Utilities : createcreditmemo() : The SQL is " . $sql, 'r');
                                                                                                                                                                                                                                                        $this->db->exec(array($sql));
                                                                                                                                                                                                                                                        
                                                                                                                                                                                                                                                        try {
                                                                                                                                                                                                                                                            $by = array ();
                                                                                                                                                                                                                                                            $r = $this->db->exec(array('SELECT id "id" FROM tblbuyers WHERE referenceno = "' . $buyer['referenceno'] . '" AND insertedby = ' . $userid));
                                                                                                                                                                                                                                                            
                                                                                                                                                                                                                                                            foreach ( $r as $obj ) {
                                                                                                                                                                                                                                                                $by [] = $obj;
                                                                                                                                                                                                                                                            }
                                                                                                                                                                                                                                                            
                                                                                                                                                                                                                                                            $buyerid = $by[0]['id'];
                                                                                                                                                                                                                                                            $this->db->exec(array('UPDATE tblinvoices SET buyerid = ' . $buyerid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                                                                                                                                                                                                                                                        } catch (Exception $e) {
                                                                                                                                                                                                                                                            $this->logger->write("Utilities : createcreditmemo() : Failed to select and update table tblinvoices. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                                                                                                        }
                                                                                                                                                                        } catch (Exception $e) {
                                                                                                                                                                            $this->logger->write("Utilities : createcreditmemo() : The operation to create a buyer was not successful. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                        }
                                                                                                                                                                    }
                                                                                                                                                                    
                                                                                                                                                                    return true;
        } catch (Exception $e) {
            $this->logger->write("Utilities : createcreditmemo() : The operation to create the invoice was not successful. The error message is " . $e->getMessage(), 'r');
            return false;
        }
    }
    
    /**
     * @name createdebitnote
     * @desc create an createdebitnote in eTW
     * @return bool
     * @param $debitnotedetails array, $goods array, $taxes array, $buyer array
     *
     */
    function createdebitnote($debitnotedetails, $goods, $taxes, $buyer, $userid){
        /**
         * 0. Insert a new invoice and retrieve its id
         * 1. Create a param group for goods, taxes, and payments
         * 2. Modify the following arrays
         * 2.1 goods
         * 2.2 payments
         * 2.3 payments
         * 2.4 buyers
         * 3. Insert into the respective tables
         */
        
        
        try{
            
            $netamount = empty($debitnotedetails['netamount'])? '0.00' : $debitnotedetails['netamount'];
            $taxamount = empty($debitnotedetails['taxamount'])? '0.00' : $debitnotedetails['taxamount'];
            $grossamount = empty($debitnotedetails['grossamount'])? '0.00' : $debitnotedetails['grossamount'];
            $itemcount = empty($debitnotedetails['itemcount'])? '0' : $debitnotedetails['itemcount'];
            $userid = empty($userid) || trim($userid) == ''? $this->userid : $userid;
            $currencyRate = empty($debitnotedetails['currencyRate'])? '1' : $debitnotedetails['currencyRate'];
            
            $sql = 'INSERT INTO tbldebitnotes
                                    (erpinvoiceid,
                                    erpinvoiceno, erpdebitnoteid, erpdebitnoteno,
                                    antifakecode,
                                    deviceno,
                                    issueddate,
                                    issuedtime,
                                    operator,
                                    currency,
                                    oriinvoiceid,
                                    invoicetype,
                                    invoicekind,
                                    datasource,
                                    invoiceindustrycode,
                                    einvoiceid,
                                    einvoicenumber,
                                    einvoicedatamatrixcode,
                                    isbatch,
                                    netamount,
                                    taxamount,
                                    grossamount,
                                    origrossamount,
                                    itemcount,
                                    modecode,
                                    modename,
                                    remarks,
                                    buyerid,
                                    sellerid,
                                    issueddatepdf,
                                    grossamountword,
                                    isinvalid,
                                    isrefund,
                                    vouchertype,
                                    vouchertypename, currencyRate,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ("'
                . addslashes($debitnotedetails['erpinvoiceid']) . '", "'
                    . addslashes($debitnotedetails['erpinvoiceno']) . '", "'
                    . addslashes($debitnotedetails['erpdebitnoteid']) . '", "'
                    . addslashes($debitnotedetails['erpdebitnoteno']) . '", "'
                        . addslashes($debitnotedetails['antifakecode']) . '", "'
                            . addslashes($debitnotedetails['deviceno']) . '", "'
                                . $debitnotedetails['issueddate'] . '", "'
                                    . $debitnotedetails['issuedtime'] . '", "'
                                        . addslashes($debitnotedetails['operator']) . '", "'
                                            . $debitnotedetails['currency'] . '", "'
                                                . $debitnotedetails['oriinvoiceid'] . '", '
                                                    . $debitnotedetails['invoicetype'] . ', '
                                                        . $debitnotedetails['invoicekind'] . ', '
                                                            . $debitnotedetails['datasource'] . ', '
                                                                . $debitnotedetails['invoiceindustrycode'] . ', "'
                                                                    . addslashes($debitnotedetails['einvoiceid']) . '", "'
                                                                        . addslashes($debitnotedetails['einvoicenumber']) . '", "'
                                                                            . addslashes($debitnotedetails['einvoicedatamatrixcode']) . '", "'
                                                                                . $debitnotedetails['isbatch'] . '", '
                                                                                    . $netamount . ', '
                                                                                        . $taxamount . ', '
                                                                                            . $grossamount . ', '
                                                                                                . $debitnotedetails['origrossamount'] . ', '
                                                                                                    . $itemcount . ', "'
                                                                                                        . $debitnotedetails['modecode'] . '", "'
                                                                                                            . $debitnotedetails['modename'] . '", "'
                                                                                                                . addslashes($debitnotedetails['remarks']) . '", '
                                                                                                                    . 'NULL, '
                                                                                                                        . $debitnotedetails['sellerid'] . ', "'
                                                                                                                            . $debitnotedetails['issueddatepdf'] . '", "'
                                                                                                                                . $debitnotedetails['grossamountword'] . '", '
                                                                                                                                    . $debitnotedetails['isinvalid'] . ', '
                                                                                                                                        . $debitnotedetails['isrefund'] . ', "'
                                                                                                                                            . addslashes($debitnotedetails['vchtype']) . '", "'
                                                                                                                                                . addslashes($debitnotedetails['vchtypename']) . '", '
                                                                                                                                                    . $currencyRate . ', "'
                                                                                                                                                    . date('Y-m-d H:i:s') . '", '
                                                                                                                                                        . $userid . ', "'
                                                                                                                                                            . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                . $userid . ')';
                                                                                                                                                                
                                                                                                                                                                $this->logger->write("Utilities : createdebitnote() : The SQL is " . $sql, 'r');
                                                                                                                                                                $this->db->exec(array($sql));
                                                                                                                                                                $this->logger->write("Utilities : createdebitnote() : The invoice has been added", 'r');
                                                                                                                                                                
                                                                                                                                                                
                                                                                                                                                                $this->logger->write("Utilities : createdebitnote() : The FDN is " . $debitnotedetails['antifakecode'], 'r');
                                                                                                                                                                
                                                                                                                                                                $data = array();
                                                                                                                                                                $r = $this->db->exec(array(
                                                                                                                                                                    'SELECT id "id" FROM tbldebitnotes WHERE TRIM(antifakecode) = \'' . $debitnotedetails['antifakecode'] , '\''
                                                                                                                                                                ));
                                                                                                                                                                
                                                                                                                                                                foreach ($r as $obj) {
                                                                                                                                                                    $data[] = $obj;
                                                                                                                                                                }
                                                                                                                                                                
                                                                                                                                                                $id = $data[0]['id'];
                                                                                                                                                                
                                                                                                                                                                try {
                                                                                                                                                                    $paramgroupdescription = "This is an autogenerated group id for the debitnote id " . $id;
                                                                                                                                                                    
                                                                                                                                                                    $this->db->exec(array('INSERT INTO tblgooddetailgroups (owner, entitytype, description, inserteddt, insertedby, modifieddt, modifiedby)
                                            VALUES(' . $id . ', ' . $this->appsettings['DEBITNOTEENTITYTYPE'] . ', "' . $paramgroupdescription . '", NOW(), ' . $userid . ', NOW(), ' . $userid . ')'));
                                                                                                                                                                    
                                                                                                                                                                    try {
                                                                                                                                                                        $pg = array ();
                                                                                                                                                                        $r = $this->db->exec(array('SELECT MAX(id) "id" FROM tblgooddetailgroups WHERE owner = ' . $id . ' AND entitytype = ' . $this->appsettings['DEBITNOTEENTITYTYPE'] . ' AND insertedby = ' . $userid));
                                                                                                                                                                        
                                                                                                                                                                        foreach ( $r as $obj ) {
                                                                                                                                                                            $pg [] = $obj;
                                                                                                                                                                        }
                                                                                                                                                                        
                                                                                                                                                                        $gooddetailgroupid = $pg[0]['id'];
                                                                                                                                                                        $this->db->exec(array('UPDATE tbldebitnotes SET gooddetailgroupid = ' . $gooddetailgroupid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                                                                                                                                                                        
                                                                                                                                                                        /*Insert Goods*/
                                                                                                                                                                        
                                                                                                                                                                        $i = 0;
                                                                                                                                                                        foreach ($goods as $obj) {
                                                                                                                                                                            
                                                                                                                                                                            
                                                                                                                                                                            $obj['item'] = empty($obj['item'])? '' : $obj['item'];
                                                                                                                                                                            $obj['itemcode'] = empty($obj['itemcode'])? '' : $obj['itemcode'];
                                                                                                                                                                            $obj['qty'] = empty($obj['qty'])? 'NULL' : $obj['qty'];
                                                                                                                                                                            $obj['unitofmeasure'] = empty($obj['unitofmeasure'])? '' : $obj['unitofmeasure'];
                                                                                                                                                                            $obj['unitprice'] = empty($obj['unitprice'])? 'NULL' : $obj['unitprice'];
                                                                                                                                                                            $obj['total'] = empty($obj['total'])? 'NULL' : $obj['total'];
                                                                                                                                                                            $obj['taxrate'] = empty($obj['taxrate'])? 'NULL' : $obj['taxrate'];
                                                                                                                                                                            $obj['tax'] = empty($obj['tax'])? '0.00' : $obj['tax'];
                                                                                                                                                                            $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? 'NULL' : $obj['discounttotal'];
                                                                                                                                                                            $obj['discounttaxrate'] = (empty($obj['discounttaxrate']) || $obj['discounttaxrate'] == '0.00')? 'NULL' : $obj['discounttaxrate'];
                                                                                                                                                                            $obj['discountflag'] = empty($obj['discountflag'])? '2' : $obj['discountflag'];
                                                                                                                                                                            $obj['deemedflag'] = empty($obj['deemedflag'])? '2' : $obj['deemedflag'];
                                                                                                                                                                            $obj['exciseflag'] = empty($obj['exciseflag'])? '2' : $obj['exciseflag'];
                                                                                                                                                                            $obj['categoryid'] = empty($obj['categoryid'])? 'NULL' : $obj['categoryid'];
                                                                                                                                                                            $obj['categoryname'] = empty($obj['categoryname'])? '' : $obj['categoryname'];
                                                                                                                                                                            $obj['goodscategoryid'] = empty($obj['goodscategoryid'])? 'NULL' : $obj['goodscategoryid'];
                                                                                                                                                                            $obj['goodscategoryname'] = empty($obj['goodscategoryname'])? '' : $obj['goodscategoryname'];
                                                                                                                                                                            $obj['exciserate'] = empty($obj['exciserate'])? '' : $obj['exciserate'];
                                                                                                                                                                            $obj['exciserule'] = empty($obj['exciserule'])? 'NULL' : $obj['exciserule'];
                                                                                                                                                                            $obj['excisetax'] = empty($obj['excisetax'])? 'NULL' : $obj['excisetax'];
                                                                                                                                                                            $obj['pack'] = empty($obj['pack'])? 'NULL' : $obj['pack'];
                                                                                                                                                                            $obj['stick'] = empty($obj['stick'])? 'NULL' : $obj['stick'];
                                                                                                                                                                            $obj['exciseunit'] = empty($obj['exciseunit'])? 'NULL' : $obj['exciseunit'];
                                                                                                                                                                            $obj['excisecurrency'] = empty($obj['excisecurrency'])? '' : $obj['excisecurrency'];
                                                                                                                                                                            $obj['exciseratename'] = empty($obj['exciseratename'])? '' : $obj['exciseratename'];
                                                                                                                                                                            $obj['vatProjectId'] = empty($obj['vatProjectId'])? '' : $obj['vatProjectId'];
                                                                                                                                                                            $obj['vatProjectName'] = empty($obj['exciseratename'])? '' : $obj['vatProjectName'];
                                                                                                                                                                            
                                                                                                                                                                            $sql = 'INSERT INTO tblgooddetails (
                                    groupid,
                                    item,
                                    itemcode,
                                    qty,
                                    unitofmeasure,
                                    unitprice,
                                    total,
                                    taxid,
                                    taxrate,
                                    tax,
                                    discounttotal,
                                    discounttaxrate,
                                    discountpercentage,
                                    ordernumber,
                                    discountflag,
                                    deemedflag,
                                    exciseflag,
                                    categoryid,
                                    categoryname,
                                    goodscategoryid,
                                    goodscategoryname,
                                    exciserate,
                                    exciserule,
                                    excisetax,
                                    pack,
                                    stick,
                                    exciseunit,
                                    excisecurrency,
                                    exciseratename,
                                    taxcategory,
                                    unitofmeasurename,
                                    projectId,
                                    projectName,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ('
                                                                                                                                                                                . $gooddetailgroupid . ', "'
                                                                                                                                                                                    . addslashes($obj['item']) . '", "'
                                                                                                                                                                                        . addslashes($obj['itemcode']) . '", '
                                                                                                                                                                                            . $obj['qty'] . ', "'
                                                                                                                                                                                                . $obj['unitofmeasure'] . '", '
                                                                                                                                                                                                    . $obj['unitprice'] . ', '
                                                                                                                                                                                                        . $obj['total'] . ', '
                                                                                                                                                                                                            . $obj['taxid'] . ', '
                                                                                                                                                                                                                . $obj['taxrate'] . ', '
                                                                                                                                                                                                                    . $obj['tax'] . ', '
                                                                                                                                                                                                                        . $obj['discounttotal'] . ', '
                                                                                                                                                                                                                            . $obj['discounttaxrate'] . ', '
                                                                                                                                                                                                                                . $obj['discountpercentage'] . ', '
                                                                                                                                                                                                                                    . $i . ', '
                                                                                                                                                                                                                                        . $obj['discountflag'] . ', '
                                                                                                                                                                                                                                            . $obj['deemedflag'] . ', '
                                                                                                                                                                                                                                                . $obj['exciseflag'] . ', '
                                                                                                                                                                                                                                                    . $obj['categoryid'] . ', "'
                                                                                                                                                                                                                                                        . addslashes($obj['categoryname']) . '", '
                                                                                                                                                                                                                                                            . $obj['goodscategoryid'] . ', "'
                                                                                                                                                                                                                                                                . addslashes($obj['goodscategoryname']) . '", "'
                                                                                                                                                                                                                                                                    . $obj['exciserate'] . '", '
                                                                                                                                                                                                                                                                        . $obj['exciserule'] . ', '
                                                                                                                                                                                                                                                                            . $obj['excisetax'] . ', '
                                                                                                                                                                                                                                                                                . $obj['pack'] . ', '
                                                                                                                                                                                                                                                                                    . $obj['stick'] . ', '
                                                                                                                                                                                                                                                                                        . $obj['exciseunit'] . ', "'
                                                                                                                                                                                                                                                                                            . $obj['excisecurrency'] . '", "'
                                                                                                                                                                                                                                                                                                . $obj['exciseratename'] . '", "'
                                                                                                                                                                                                                                                                                                    . $obj['taxdisplaycategory'] . '", "'
                                                                                                                                                                                                                                                                                                        . $obj['unitofmeasurename'] . '", "'
                                                                                                                                                                                                                                                                                                        . $obj['vatProjectId'] . '", "'
                                                                                                                                                                                                                                                                                                            . $obj['vatProjectName'] . '", "'
                                                                                                                                                                                                                                                                                                            . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                                                                                                . $userid . ', "'
                                                                                                                                                                                                                                                                                                                    . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                                                                                                        . $userid . ')';
                                                                                                                                                                                                                                                                                                                        
                                                                                                                                                                                                                                                                                                                        $this->logger->write("Utilities : createdebitnote() : The SQL is " . $sql, 'r');
                                                                                                                                                                                                                                                                                                                        $this->db->exec(array($sql));
                                                                                                                                                                                                                                                                                                                        
                                                                                                                                                                                                                                                                                                                        $i = $i + 1;
                                                                                                                                                                        }
                                                                                                                                                                        
                                                                                                                                                                    } catch (Exception $e) {
                                                                                                                                                                        $this->logger->write("Utilities : createdebitnote() : Failed to select and insert into table tblgooddetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                    }
                                                                                                                                                                } catch (Exception $e) {
                                                                                                                                                                    $this->logger->write("Utilities : createdebitnote() : Failed to insert into the table tblgooddetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                }
                                                                                                                                                                
                                                                                                                                                                
                                                                                                                                                                try {
                                                                                                                                                                    $paramgroupdescription = "This is an autogenerated group id for the debitnote id " . $id;
                                                                                                                                                                    
                                                                                                                                                                    $this->db->exec(array('INSERT INTO tblpaymentdetailgroups (owner, entitytype, description, inserteddt, insertedby, modifieddt, modifiedby)
                                            VALUES(' . $id . ', ' . $this->appsettings['DEBITNOTEENTITYTYPE'] . ', "' . $paramgroupdescription . '", NOW(), ' . $userid . ', NOW(), ' . $userid . ')'));
                                                                                                                                                                    
                                                                                                                                                                    try {
                                                                                                                                                                        $pg = array ();
                                                                                                                                                                        $r = $this->db->exec(array('SELECT MAX(id) "id" FROM tblpaymentdetailgroups WHERE owner = ' . $id . ' AND entitytype = ' . $this->appsettings['DEBITNOTEENTITYTYPE'] . ' AND insertedby = ' . $userid));
                                                                                                                                                                        
                                                                                                                                                                        foreach ( $r as $obj ) {
                                                                                                                                                                            $pg [] = $obj;
                                                                                                                                                                        }
                                                                                                                                                                        
                                                                                                                                                                        $paymentdetailgroupid = $pg[0]['id'];
                                                                                                                                                                        $this->db->exec(array('UPDATE tbldebitnotes SET paymentdetailgroupid = ' . $paymentdetailgroupid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                                                                                                                                                                    } catch (Exception $e) {
                                                                                                                                                                        $this->logger->write("Utilities : createdebitnote() : Failed to select from table tblpaymentdetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                    }
                                                                                                                                                                } catch (Exception $e) {
                                                                                                                                                                    $this->logger->write("Utilities : createdebitnote() : Failed to insert into the table tblpaymentdetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                }
                                                                                                                                                                
                                                                                                                                                                
                                                                                                                                                                try {
                                                                                                                                                                    $paramgroupdescription = "This is an autogenerated group id for the debitnote id " . $id;
                                                                                                                                                                    
                                                                                                                                                                    $this->db->exec(array('INSERT INTO tbltaxdetailgroups (owner, entitytype, description, inserteddt, insertedby, modifieddt, modifiedby)
                                            VALUES(' . $id . ', ' . $this->appsettings['DEBITNOTEENTITYTYPE'] . ', "' . $paramgroupdescription . '", NOW(), ' . $userid . ', NOW(), ' . $userid . ')'));
                                                                                                                                                                    
                                                                                                                                                                    try {
                                                                                                                                                                        $pg = array ();
                                                                                                                                                                        $r = $this->db->exec(array('SELECT MAX(id) "id" FROM tbltaxdetailgroups WHERE owner = ' . $id . ' AND entitytype = ' . $this->appsettings['DEBITNOTEENTITYTYPE'] . ' AND insertedby = ' . $userid));
                                                                                                                                                                        
                                                                                                                                                                        foreach ( $r as $obj ) {
                                                                                                                                                                            $pg [] = $obj;
                                                                                                                                                                        }
                                                                                                                                                                        
                                                                                                                                                                        $taxdetailgroupid = $pg[0]['id'];
                                                                                                                                                                        $this->db->exec(array('UPDATE tbldebitnotes SET taxdetailgroupid = ' . $taxdetailgroupid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                                                                                                                                                                        
                                                                                                                                                                        //Get details of goods inserted
                                                                                                                                                                        $t_goods = $this->db->exec(array('SELECT * FROM tblgooddetails g WHERE g.groupid = ' . $gooddetailgroupid . ' ORDER BY id ASC'));
                                                                                                                                                                        
                                                                                                                                                                        //Insert Taxes
                                                                                                                                                                        $j = 0;
                                                                                                                                                                        foreach ($taxes as $obj) {
                                                                                                                                                                            /**
                                                                                                                                                                             * Modification Date: 2021-01-26
                                                                                                                                                                             * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                                                                                                                                                                             * */
                                                                                                                                                                            if (trim($obj['discountflag']) == '1') {
                                                                                                                                                                                $obj['d_grossamount'] = $obj['discounttotal'];/*Should be a negative #*/
                                                                                                                                                                                $obj['d_taxamount'] = ($obj['d_grossamount']/($obj['discounttaxrate'] + 1)) * $obj['discounttaxrate'];
                                                                                                                                                                                $obj['d_netamount'] = $obj['d_grossamount'] - $obj['d_taxamount'];
                                                                                                                                                                                
                                                                                                                                                                                $this->logger->write("Utilities : createdebitnote() : Calculating taxes. The d_grossamount is " . $obj['d_grossamount'], 'r');
                                                                                                                                                                                $this->logger->write("Utilities : createdebitnote() : Calculating taxes. The d_taxamount is " . $obj['d_taxamount'], 'r');
                                                                                                                                                                                $this->logger->write("Utilities : createdebitnote() : Calculating taxes. The d_netamount is " . $obj['d_netamount'], 'r');
                                                                                                                                                                            }
                                                                                                                                                                            
                                                                                                                                                                            if (strtoupper(trim($obj['taxcategory'])) == 'DEEMED') {
                                                                                                                                                                                $obj['netamount'] = floor(($obj['netamount'] + $obj['d_netamount'])*100)/100;
                                                                                                                                                                                $obj['taxamount'] = floor(($obj['taxamount'] + $obj['d_taxamount'])*100)/100;
                                                                                                                                                                                //$obj['grossamount'] = floor($obj['grossamount']*100)/100;
                                                                                                                                                                                $obj['grossamount'] = $obj['netamount'] + $obj['taxamount'];
                                                                                                                                                                                
                                                                                                                                                                                $obj['netamount'] = number_format($obj['netamount'], 2, '.', '');
                                                                                                                                                                                $obj['taxamount'] = number_format($obj['taxamount'], 2, '.', '');
                                                                                                                                                                                $obj['grossamount'] = number_format($obj['grossamount'], 2, '.', '');
                                                                                                                                                                            } else {
                                                                                                                                                                                $obj['netamount'] = round(($obj['netamount'] + $obj['d_netamount']), 2);
                                                                                                                                                                                $obj['taxamount'] = round(($obj['taxamount'] + $obj['d_taxamount']), 2);
                                                                                                                                                                                //$obj['grossamount'] = round($obj['grossamount'], 2);
                                                                                                                                                                                $obj['grossamount'] = $obj['netamount'] + $obj['taxamount'];
                                                                                                                                                                            }
                                                                                                                                                                            
                                                                                                                                                                            
                                                                                                                                                                            $obj['taxcategory'] = empty($obj['taxcategory'])? '' : $obj['taxcategory'];
                                                                                                                                                                            $obj['netamount'] = empty($obj['netamount'])? 'NULL' : $obj['netamount'];
                                                                                                                                                                            $obj['taxrate'] = empty($obj['taxrate'])? '' : $obj['taxrate'];
                                                                                                                                                                            $obj['taxamount'] = empty($obj['taxamount'])? '0.00' : $obj['taxamount'];
                                                                                                                                                                            $obj['grossamount'] = empty($obj['grossamount'])? 'NULL' : $obj['grossamount'];
                                                                                                                                                                            $obj['exciseunit'] = empty($obj['exciseunit'])? '' : $obj['exciseunit'];
                                                                                                                                                                            $obj['excisecurrency'] = empty($obj['excisecurrency'])? '' : $obj['excisecurrency'];
                                                                                                                                                                            $obj['taxratename'] = empty($obj['taxratename'])? '' : $obj['taxratename'];
                                                                                                                                                                            
                                                                                                                                                                            //$obj['goodid'] = empty($obj['goodid'])? 'NULL' : $obj['goodid'];
                                                                                                                                                                            $obj['goodid'] = $t_goods[$j]['id'];
                                                                                                                                                                            
                                                                                                                                                                            $sql = 'INSERT INTO tbltaxdetails (
                                    groupid,
                                    goodid,
                                    taxcategory,
                                    netamount,
                                    taxrate,
                                    taxamount,
                                    grossamount,
                                    exciseunit,
                                    excisecurrency,
                                    taxratename,
                                    taxdescription,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ('
                                                                                                                                                                                . $taxdetailgroupid . ', '
                                                                                                                                                                                    . $obj['goodid'] . ', "'
                                                                                                                                                                                        . addslashes($obj['taxcategory']) . '", '
                                                                                                                                                                                            . $obj['netamount'] . ', '
                                                                                                                                                                                                . $obj['taxrate'] . ', '
                                                                                                                                                                                                    . $obj['taxamount'] . ', '
                                                                                                                                                                                                        . $obj['grossamount'] . ', "'
                                                                                                                                                                                                            . $obj['exciseunit'] . '", "'
                                                                                                                                                                                                                . $obj['excisecurrency'] . '", "'
                                                                                                                                                                                                                    . $obj['taxratename'] . '", "'
                                                                                                                                                                                                                        . $obj['taxdescription'] . '", "'
                                                                                                                                                                                                                            . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                . $userid . ', "'
                                                                                                                                                                                                                                    . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                        . $userid . ')';
                                                                                                                                                                                                                                        
                                                                                                                                                                                                                                        $this->logger->write("Utilities : createdebitnote() : The SQL is " . $sql, 'r');
                                                                                                                                                                                                                                        $this->db->exec(array($sql));
                                                                                                                                                                                                                                        $j = $j + 1;
                                                                                                                                                                                                                                        
                                                                                                                                                                        }
                                                                                                                                                                    } catch (Exception $e) {
                                                                                                                                                                        $this->logger->write("Utilities : createdebitnote() : Failed to select from table tbltaxdetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                    }
                                                                                                                                                                } catch (Exception $e) {
                                                                                                                                                                    $this->logger->write("Utilities : createdebitnote() : Failed to insert into the table tbltaxdetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                }
                                                                                                                                                                
                                                                                                                                                                if (trim($buyer['referenceno']) !== '' || !empty(trim($buyer['referenceno']))) {
                                                                                                                                                                    try{
                                                                                                                                                                        
                                                                                                                                                                        $sql = 'INSERT INTO tblbuyers (
                                    tin,
                                    ninbrn,
                                    PassportNum,
                                    legalname,
                                    businessname,
                                    address,
                                    mobilephone,
                                    linephone,
                                    emailaddress,
                                    placeofbusiness,
                                    type,
                                    citizineship,
                                    sector,
                                    referenceno,
                                    datasource,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ("'
                                                                                                                                                                            . addslashes($buyer['tin']) . '", "'
                                                                                                                                                                                . addslashes($buyer['ninbrn']) . '", "'
                                                                                                                                                                                    . addslashes($buyer['PassportNum']) . '", "'
                                                                                                                                                                                        . addslashes($buyer['legalname']) . '", "'
                                                                                                                                                                                            . addslashes($buyer['businessname']) . '", "'
                                                                                                                                                                                                . addslashes($buyer['address']) . '", "'
                                                                                                                                                                                                    . addslashes($buyer['mobilephone']) . '", "'
                                                                                                                                                                                                        . addslashes($buyer['linephone']) . '", "'
                                                                                                                                                                                                            . addslashes($buyer['emailaddress']) . '", "'
                                                                                                                                                                                                                . addslashes($buyer['placeofbusiness']) . '", "'
                                                                                                                                                                                                                    . $buyer['type'] . '", "'
                                                                                                                                                                                                                        . addslashes($buyer['citizineship']) . '", "'
                                                                                                                                                                                                                            . addslashes($buyer['sector']) . '", "'
                                                                                                                                                                                                                                . addslashes($buyer['referenceno']) . '", "'
                                                                                                                                                                                                                                    . $buyer['datasource'] . '", "'
                                                                                                                                                                                                                                        . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                            . $userid . ', "'
                                                                                                                                                                                                                                                . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                                    . $userid . ')';
                                                                                                                                                                                                                                                    
                                                                                                                                                                                                                                                    $this->logger->write("Utilities : createdebitnote() : The SQL is " . $sql, 'r');
                                                                                                                                                                                                                                                    $this->db->exec(array($sql));
                                                                                                                                                                                                                                                    
                                                                                                                                                                                                                                                    try {
                                                                                                                                                                                                                                                        $by = array ();
                                                                                                                                                                                                                                                        $r = $this->db->exec(array('SELECT id "id" FROM tblbuyers WHERE referenceno = "' . $buyer['referenceno'] . '" AND insertedby = ' . $userid));
                                                                                                                                                                                                                                                        
                                                                                                                                                                                                                                                        foreach ( $r as $obj ) {
                                                                                                                                                                                                                                                            $by [] = $obj;
                                                                                                                                                                                                                                                        }
                                                                                                                                                                                                                                                        
                                                                                                                                                                                                                                                        $buyerid = $by[0]['id'];
                                                                                                                                                                                                                                                        $this->db->exec(array('UPDATE tbldebitnotes SET buyerid = ' . $buyerid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                                                                                                                                                                                                                                                    } catch (Exception $e) {
                                                                                                                                                                                                                                                        $this->logger->write("Utilities : createdebitnote() : Failed to select and update table tblinvoices. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                                                                                                    }
                                                                                                                                                                    } catch (Exception $e) {
                                                                                                                                                                        $this->logger->write("Utilities : createdebitnote() : The operation to create a buyer was not successful. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                    }
                                                                                                                                                                }
                                                                                                                                                                
                                                                                                                                                                
                                                                                                                                                                
                                                                                                                                                                return true;
        } catch (Exception $e) {
            $this->logger->write("Utilities : createdebitnote() : The operation to create the invoice was not successful. The error message is " . $e->getMessage(), 'r');
            return false;
        }
    }
    
    
    /**
     * @name createcreditnote
     * @desc create an createcreditnote in eTW
     * @return bool
     * @param $creditnotedetails array, $goods array, $taxes array, $buyer array
     *
     */
    function createcreditnote($creditnotedetails, $goods, $taxes, $buyer, $userid){
        /**
         * 0. Insert a new credit note and retrieve its id
         * 1. Create a param group for goods, taxes, and payments
         * 2. Modify the following arrays
         * 2.1 goods
         * 2.2 payments
         * 2.3 payments
         * 2.4 buyers
         * 3. Insert into the respective tables
         */
        
        
        try{
            
            $netamount = empty($creditnotedetails['netamount'])? '0.00' : $creditnotedetails['netamount'];
            $taxamount = empty($creditnotedetails['taxamount'])? '0.00' : $creditnotedetails['taxamount'];
            $grossamount = empty($creditnotedetails['grossamount'])? '0.00' : $creditnotedetails['grossamount'];
            $itemcount = empty($creditnotedetails['itemcount'])? '0' : $creditnotedetails['itemcount'];
            $userid = empty($userid) || trim($userid) == ''? $this->userid : $userid;
            
            $creditnotedetails['origrossamount'] = empty($creditnotedetails['origrossamount'])? '0' : $creditnotedetails['origrossamount'];
            
            $sql = 'INSERT INTO tblcreditnotes
                                    (erpinvoiceid,
                                    erpinvoiceno, erpcreditnoteid, erpcreditnoteno,
                                    antifakecode,
                                    deviceno,
                                    issueddate,
                                    issuedtime,
                                    operator,
                                    currency,
                                    oriinvoiceid,
                                    invoicetype,
                                    invoicekind,
                                    datasource,
                                    invoiceindustrycode,
                                    einvoiceid,
                                    einvoicenumber,
                                    einvoicedatamatrixcode,
                                    isbatch,
                                    netamount,
                                    taxamount,
                                    grossamount,
                                    origrossamount,
                                    itemcount,
                                    modecode,
                                    modename,
                                    remarks,
                                    buyerid,
                                    sellerid,
                                    issueddatepdf,
                                    grossamountword,
                                    isinvalid,
                                    isrefund,
                                    vouchertype,
                                    vouchertypename,
                                    oriinvoiceno,
                                    reasoncode,
                                    reason,
                                    referenceno,
                                    invoiceapplycategorycode,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ("'
                . addslashes($creditnotedetails['erpinvoiceid']) . '", "'
                    . addslashes($creditnotedetails['erpinvoiceno']) . '", "'
                    . addslashes($creditnotedetails['erpcreditnoteid']) . '", "'
                    . addslashes($creditnotedetails['erpcreditnoteno']) . '", "'
                        . addslashes($creditnotedetails['antifakecode']) . '", "'
                            . addslashes($creditnotedetails['deviceno']) . '", "'
                                . $creditnotedetails['issueddate'] . '", "'
                                    . $creditnotedetails['issuedtime'] . '", "'
                                        . addslashes($creditnotedetails['operator']) . '", "'
                                            . $creditnotedetails['currency'] . '", "'
                                                . $creditnotedetails['oriinvoiceid'] . '", '
                                                    . $creditnotedetails['invoicetype'] . ', '
                                                        . $creditnotedetails['invoicekind'] . ', '
                                                            . $creditnotedetails['datasource'] . ', '
                                                                . $creditnotedetails['invoiceindustrycode'] . ', "'
                                                                    . addslashes($creditnotedetails['einvoiceid']) . '", "'
                                                                        . addslashes($creditnotedetails['einvoicenumber']) . '", "'
                                                                            . addslashes($creditnotedetails['einvoicedatamatrixcode']) . '", "'
                                                                                . $creditnotedetails['isbatch'] . '", '
                                                                                    . $netamount . ', '
                                                                                        . $taxamount . ', '
                                                                                            . $grossamount . ', '
                                                                                                . $creditnotedetails['origrossamount'] . ', '
                                                                                                    . $itemcount . ', "'
                                                                                                        . $creditnotedetails['modecode'] . '", "'
                                                                                                            . $creditnotedetails['modename'] . '", "'
                                                                                                                . addslashes($creditnotedetails['remarks']) . '", '
                                                                                                                    . 'NULL, '
                                                                                                                        . $creditnotedetails['sellerid'] . ', "'
                                                                                                                            . $creditnotedetails['issueddatepdf'] . '", "'
                                                                                                                                . $creditnotedetails['grossamountword'] . '", '
                                                                                                                                    . $creditnotedetails['isinvalid'] . ', '
                                                                                                                                        . $creditnotedetails['isrefund'] . ', "'
                                                                                                                                            . addslashes($creditnotedetails['vchtype']) . '", "'
                                                                                                                                                . addslashes($creditnotedetails['vchtypename']) . '", "'
                                                                                                                                                . addslashes($creditnotedetails['oriinvoiceno']) . '", "'
                                                                                                                                                . addslashes($creditnotedetails['reasoncode']) . '", "'
                                                                                                                                                . addslashes($creditnotedetails['reason']) . '", "'
                                                                                                                                                . addslashes($creditnotedetails['referenceno']) . '", "'
                                                                                                                                                . addslashes($creditnotedetails['invoiceapplycategorycode']) . '", "'
                                                                                                                                                    . date('Y-m-d H:i:s') . '", '
                                                                                                                                                        . $userid . ', "'
                                                                                                                                                            . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                . $userid . ')';
                                                                                                                                                                
                                                                                                                                                                $this->logger->write("Utilities : createcreditnote() : The SQL is " . $sql, 'r');
                                                                                                                                                                $this->db->exec(array($sql));
                                                                                                                                                                $this->logger->write("Utilities : createcreditnote() : The invoice has been added", 'r');
                                                                                                                                                                
                                                                                                                                                                
                                                                                                                                                                $this->logger->write("Utilities : createcreditnote() : The referenceno is " . $creditnotedetails['referenceno'], 'r');
                                                                                                                                                                
                                                                                                                                                                $data = array();
                                                                                                                                                                $r = $this->db->exec(array(
                                                                                                                                                                    'SELECT id "id" FROM tblcreditnotes WHERE TRIM(referenceno) = \'' . $creditnotedetails['referenceno'] . '\''
                                                                                                                                                                ));
                                                                                                                                                                
                                                                                                                                                                foreach ($r as $obj) {
                                                                                                                                                                    $data[] = $obj;
                                                                                                                                                                }
                                                                                                                                                                
                                                                                                                                                                $id = $data[0]['id'];
                                                                                                                                                                
                                                                                                                                                                try {
                                                                                                                                                                    $paramgroupdescription = "This is an autogenerated group id for the creditnote id " . $id;
                                                                                                                                                                    
                                                                                                                                                                    $this->db->exec(array('INSERT INTO tblgooddetailgroups (owner, entitytype, description, inserteddt, insertedby, modifieddt, modifiedby)
                                            VALUES(' . $id . ', ' . $this->appsettings['CREDITNOTEENTITYTYPE'] . ', "' . $paramgroupdescription . '", NOW(), ' . $userid . ', NOW(), ' . $userid . ')'));
                                                                                                                                                                    
                                                                                                                                                                    try {
                                                                                                                                                                        $pg = array ();
                                                                                                                                                                        $r = $this->db->exec(array('SELECT MAX(id) "id" FROM tblgooddetailgroups WHERE owner = ' . $id . ' AND entitytype = ' . $this->appsettings['CREDITNOTEENTITYTYPE'] . ' AND insertedby = ' . $userid));
                                                                                                                                                                        
                                                                                                                                                                        foreach ( $r as $obj ) {
                                                                                                                                                                            $pg [] = $obj;
                                                                                                                                                                        }
                                                                                                                                                                        
                                                                                                                                                                        $gooddetailgroupid = $pg[0]['id'];
                                                                                                                                                                        $this->db->exec(array('UPDATE tblcreditnotes SET gooddetailgroupid = ' . $gooddetailgroupid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                                                                                                                                                                        
                                                                                                                                                                        /*Insert Goods*/
                                                                                                                                                                        
                                                                                                                                                                        $i = 0;
                                                                                                                                                                        foreach ($goods as $obj) {
                                                                                                                                                                            
                                                                                                                                                                            
                                                                                                                                                                            $obj['item'] = empty($obj['item'])? '' : $obj['item'];
                                                                                                                                                                            $obj['itemcode'] = empty($obj['itemcode'])? '' : $obj['itemcode'];
                                                                                                                                                                            $obj['qty'] = empty($obj['qty'])? 'NULL' : $obj['qty'];
                                                                                                                                                                            $obj['unitofmeasure'] = empty($obj['unitofmeasure'])? '' : $obj['unitofmeasure'];
                                                                                                                                                                            $obj['unitprice'] = empty($obj['unitprice'])? 'NULL' : $obj['unitprice'];
                                                                                                                                                                            $obj['total'] = empty($obj['total'])? 'NULL' : $obj['total'];
                                                                                                                                                                            $obj['taxrate'] = empty($obj['taxrate'])? 'NULL' : $obj['taxrate'];
                                                                                                                                                                            $obj['tax'] = empty($obj['tax'])? '0.00' : $obj['tax'];
                                                                                                                                                                            $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? 'NULL' : $obj['discounttotal'];
                                                                                                                                                                            $obj['discounttaxrate'] = (empty($obj['discounttaxrate']) || $obj['discounttaxrate'] == '0.00')? 'NULL' : $obj['discounttaxrate'];
                                                                                                                                                                            $obj['discountflag'] = empty($obj['discountflag'])? '2' : $obj['discountflag'];
                                                                                                                                                                            $obj['deemedflag'] = empty($obj['deemedflag'])? '2' : $obj['deemedflag'];
                                                                                                                                                                            $obj['exciseflag'] = empty($obj['exciseflag'])? '2' : $obj['exciseflag'];
                                                                                                                                                                            $obj['categoryid'] = empty($obj['categoryid'])? 'NULL' : $obj['categoryid'];
                                                                                                                                                                            $obj['categoryname'] = empty($obj['categoryname'])? '' : $obj['categoryname'];
                                                                                                                                                                            $obj['goodscategoryid'] = empty($obj['goodscategoryid'])? 'NULL' : $obj['goodscategoryid'];
                                                                                                                                                                            $obj['goodscategoryname'] = empty($obj['goodscategoryname'])? '' : $obj['goodscategoryname'];
                                                                                                                                                                            $obj['exciserate'] = empty($obj['exciserate'])? '' : $obj['exciserate'];
                                                                                                                                                                            $obj['exciserule'] = empty($obj['exciserule'])? 'NULL' : $obj['exciserule'];
                                                                                                                                                                            $obj['excisetax'] = empty($obj['excisetax'])? 'NULL' : $obj['excisetax'];
                                                                                                                                                                            $obj['pack'] = empty($obj['pack'])? 'NULL' : $obj['pack'];
                                                                                                                                                                            $obj['stick'] = empty($obj['stick'])? 'NULL' : $obj['stick'];
                                                                                                                                                                            $obj['exciseunit'] = empty($obj['exciseunit'])? 'NULL' : $obj['exciseunit'];
                                                                                                                                                                            $obj['excisecurrency'] = empty($obj['excisecurrency'])? '' : $obj['excisecurrency'];
                                                                                                                                                                            $obj['exciseratename'] = empty($obj['exciseratename'])? '' : $obj['exciseratename'];
                                                                                                                                                                            $obj['ordernumber'] = empty($obj['ordernumber'])? $i : $obj['ordernumber'];
                                                                                                                                                                            $obj['vatProjectId'] = empty($obj['vatProjectId'])? '' : $obj['vatProjectId'];
                                                                                                                                                                            $obj['vatProjectName'] = empty($obj['exciseratename'])? '' : $obj['vatProjectName'];
                                                                                                                                                                            
                                                                                                                                                                            $sql = 'INSERT INTO tblgooddetails (
                                    groupid,
                                    item,
                                    itemcode,
                                    qty,
                                    unitofmeasure,
                                    unitprice,
                                    total,
                                    taxid,
                                    taxrate,
                                    tax,
                                    discounttotal,
                                    discounttaxrate,
                                    discountpercentage,
                                    ordernumber,
                                    discountflag,
                                    deemedflag,
                                    exciseflag,
                                    categoryid,
                                    categoryname,
                                    goodscategoryid,
                                    goodscategoryname,
                                    exciserate,
                                    exciserule,
                                    excisetax,
                                    pack,
                                    stick,
                                    exciseunit,
                                    excisecurrency,
                                    exciseratename,
                                    taxcategory,
                                    unitofmeasurename,
                                    projectId,
                                    projectName,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ('
                                                                                                                                                                                . $gooddetailgroupid . ', "'
                                                                                                                                                                                    . addslashes($obj['item']) . '", "'
                                                                                                                                                                                        . addslashes($obj['itemcode']) . '", '
                                                                                                                                                                                            . $obj['qty'] . ', "'
                                                                                                                                                                                                . $obj['unitofmeasure'] . '", '
                                                                                                                                                                                                    . $obj['unitprice'] . ', '
                                                                                                                                                                                                        . $obj['total'] . ', '
                                                                                                                                                                                                            . $obj['taxid'] . ', '
                                                                                                                                                                                                                . $obj['taxrate'] . ', '
                                                                                                                                                                                                                    . $obj['tax'] . ', '
                                                                                                                                                                                                                        . $obj['discounttotal'] . ', '
                                                                                                                                                                                                                            . $obj['discounttaxrate'] . ', '
                                                                                                                                                                                                                                . $obj['discountpercentage'] . ', '
                                                                                                                                                                                                                                    . $obj['ordernumber'] . ', '
                                                                                                                                                                                                                                        . $obj['discountflag'] . ', '
                                                                                                                                                                                                                                            . $obj['deemedflag'] . ', '
                                                                                                                                                                                                                                                . $obj['exciseflag'] . ', '
                                                                                                                                                                                                                                                    . $obj['categoryid'] . ', "'
                                                                                                                                                                                                                                                        . addslashes($obj['categoryname']) . '", '
                                                                                                                                                                                                                                                            . $obj['goodscategoryid'] . ', "'
                                                                                                                                                                                                                                                                . addslashes($obj['goodscategoryname']) . '", "'
                                                                                                                                                                                                                                                                    . $obj['exciserate'] . '", '
                                                                                                                                                                                                                                                                        . $obj['exciserule'] . ', '
                                                                                                                                                                                                                                                                            . $obj['excisetax'] . ', '
                                                                                                                                                                                                                                                                                . $obj['pack'] . ', '
                                                                                                                                                                                                                                                                                    . $obj['stick'] . ', '
                                                                                                                                                                                                                                                                                        . $obj['exciseunit'] . ', "'
                                                                                                                                                                                                                                                                                            . $obj['excisecurrency'] . '", "'
                                                                                                                                                                                                                                                                                                . $obj['exciseratename'] . '", "'
                                                                                                                                                                                                                                                                                                    . $obj['taxdisplaycategory'] . '", "'
                                                                                                                                                                                                                                                                                                        . $obj['unitofmeasurename'] . '", "'
                                                                                                                                                                                                                                                                                                        . $obj['vatProjectId'] . '", "'
                                                                                                                                                                                                                                                                                                            . $obj['vatProjectName'] . '", "'
                                                                                                                                                                                                                                                                                                            . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                                                                                                . $userid . ', "'
                                                                                                                                                                                                                                                                                                                    . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                                                                                                        . $userid . ')';
                                                                                                                                                                                                                                                                                                                        
                                                                                                                                                                                                                                                                                                                        $this->logger->write("Utilities : createcreditnote() : The SQL is " . $sql, 'r');
                                                                                                                                                                                                                                                                                                                        $this->db->exec(array($sql));
                                                                                                                                                                                                                                                                                                                        
                                                                                                                                                                                                                                                                                                                        $i = $i + 1;
                                                                                                                                                                        }
                                                                                                                                                                        
                                                                                                                                                                    } catch (Exception $e) {
                                                                                                                                                                        $this->logger->write("Utilities : createcreditnote() : Failed to select and insert into table tblgooddetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                    }
                                                                                                                                                                } catch (Exception $e) {
                                                                                                                                                                    $this->logger->write("Utilities : createcreditnote() : Failed to insert into the table tblgooddetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                }
                                                                                                                                                                
                                                                                                                                                                
                                                                                                                                                                try {
                                                                                                                                                                    $paramgroupdescription = "This is an autogenerated group id for the creditnote id " . $id;
                                                                                                                                                                    
                                                                                                                                                                    $this->db->exec(array('INSERT INTO tblpaymentdetailgroups (owner, entitytype, description, inserteddt, insertedby, modifieddt, modifiedby)
                                            VALUES(' . $id . ', ' . $this->appsettings['CREDITNOTEENTITYTYPE'] . ', "' . $paramgroupdescription . '", NOW(), ' . $userid . ', NOW(), ' . $userid . ')'));
                                                                                                                                                                    
                                                                                                                                                                    try {
                                                                                                                                                                        $pg = array ();
                                                                                                                                                                        $r = $this->db->exec(array('SELECT MAX(id) "id" FROM tblpaymentdetailgroups WHERE owner = ' . $id . ' AND entitytype = ' . $this->appsettings['CREDITNOTEENTITYTYPE'] . ' AND insertedby = ' . $userid));
                                                                                                                                                                        
                                                                                                                                                                        foreach ( $r as $obj ) {
                                                                                                                                                                            $pg [] = $obj;
                                                                                                                                                                        }
                                                                                                                                                                        
                                                                                                                                                                        $paymentdetailgroupid = $pg[0]['id'];
                                                                                                                                                                        $this->db->exec(array('UPDATE tblcreditnotes SET paymentdetailgroupid = ' . $paymentdetailgroupid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                                                                                                                                                                    } catch (Exception $e) {
                                                                                                                                                                        $this->logger->write("Utilities : createcreditnote() : Failed to select from table tblpaymentdetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                    }
                                                                                                                                                                } catch (Exception $e) {
                                                                                                                                                                    $this->logger->write("Utilities : createcreditnote() : Failed to insert into the table tblpaymentdetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                }
                                                                                                                                                                
                                                                                                                                                                
                                                                                                                                                                try {
                                                                                                                                                                    $paramgroupdescription = "This is an autogenerated group id for the creditnote id " . $id;
                                                                                                                                                                    
                                                                                                                                                                    $this->db->exec(array('INSERT INTO tbltaxdetailgroups (owner, entitytype, description, inserteddt, insertedby, modifieddt, modifiedby)
                                            VALUES(' . $id . ', ' . $this->appsettings['CREDITNOTEENTITYTYPE'] . ', "' . $paramgroupdescription . '", NOW(), ' . $userid . ', NOW(), ' . $userid . ')'));
                                                                                                                                                                    
                                                                                                                                                                    try {
                                                                                                                                                                        $pg = array ();
                                                                                                                                                                        $r = $this->db->exec(array('SELECT MAX(id) "id" FROM tbltaxdetailgroups WHERE owner = ' . $id . ' AND entitytype = ' . $this->appsettings['CREDITNOTEENTITYTYPE'] . ' AND insertedby = ' . $userid));
                                                                                                                                                                        
                                                                                                                                                                        foreach ( $r as $obj ) {
                                                                                                                                                                            $pg [] = $obj;
                                                                                                                                                                        }
                                                                                                                                                                        
                                                                                                                                                                        $taxdetailgroupid = $pg[0]['id'];
                                                                                                                                                                        $this->db->exec(array('UPDATE tblcreditnotes SET taxdetailgroupid = ' . $taxdetailgroupid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                                                                                                                                                                        
                                                                                                                                                                        //Get details of goods inserted
                                                                                                                                                                        $t_goods = $this->db->exec(array('SELECT * FROM tblgooddetails g WHERE g.groupid = ' . $gooddetailgroupid . ' ORDER BY id ASC'));
                                                                                                                                                                        
                                                                                                                                                                        //Insert Taxes
                                                                                                                                                                        $j = 0;
                                                                                                                                                                        foreach ($taxes as $obj) {
                                                                                                                                                                            /**
                                                                                                                                                                             * Modification Date: 2021-01-26
                                                                                                                                                                             * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                                                                                                                                                                             * */
                                                                                                                                                                            if (trim($obj['discountflag']) == '1') {
                                                                                                                                                                                $obj['d_grossamount'] = $obj['discounttotal'];/*Should be a negative #*/
                                                                                                                                                                                $obj['d_taxamount'] = ($obj['d_grossamount']/($obj['discounttaxrate'] + 1)) * $obj['discounttaxrate'];
                                                                                                                                                                                $obj['d_netamount'] = $obj['d_grossamount'] - $obj['d_taxamount'];
                                                                                                                                                                                
                                                                                                                                                                                $this->logger->write("Utilities : createcreditnote() : Calculating taxes. The d_grossamount is " . $obj['d_grossamount'], 'r');
                                                                                                                                                                                $this->logger->write("Utilities : createcreditnote() : Calculating taxes. The d_taxamount is " . $obj['d_taxamount'], 'r');
                                                                                                                                                                                $this->logger->write("Utilities : createcreditnote() : Calculating taxes. The d_netamount is " . $obj['d_netamount'], 'r');
                                                                                                                                                                            }
                                                                                                                                                                            
                                                                                                                                                                            if (strtoupper(trim($obj['taxcategory'])) == 'DEEMED') {
                                                                                                                                                                                $obj['netamount'] = floor(($obj['netamount'] + $obj['d_netamount'])*100)/100;
                                                                                                                                                                                $obj['taxamount'] = floor(($obj['taxamount'] + $obj['d_taxamount'])*100)/100;
                                                                                                                                                                                //$obj['grossamount'] = floor($obj['grossamount']*100)/100;
                                                                                                                                                                                $obj['grossamount'] = $obj['netamount'] + $obj['taxamount'];
                                                                                                                                                                                
                                                                                                                                                                                $obj['netamount'] = number_format($obj['netamount'], 2, '.', '');
                                                                                                                                                                                $obj['taxamount'] = number_format($obj['taxamount'], 2, '.', '');
                                                                                                                                                                                $obj['grossamount'] = number_format($obj['grossamount'], 2, '.', '');
                                                                                                                                                                            } else {
                                                                                                                                                                                $obj['netamount'] = round(($obj['netamount'] + $obj['d_netamount']), 2);
                                                                                                                                                                                $obj['taxamount'] = round(($obj['taxamount'] + $obj['d_taxamount']), 2);
                                                                                                                                                                                //$obj['grossamount'] = round($obj['grossamount'], 2);
                                                                                                                                                                                $obj['grossamount'] = $obj['netamount'] + $obj['taxamount'];
                                                                                                                                                                            }
                                                                                                                                                                            
                                                                                                                                                                            $obj['taxcategory'] = empty($obj['taxcategory'])? '' : $obj['taxcategory'];
                                                                                                                                                                            $obj['netamount'] = empty($obj['netamount'])? 'NULL' : $obj['netamount'];
                                                                                                                                                                            $obj['taxrate'] = empty($obj['taxrate'])? '' : $obj['taxrate'];
                                                                                                                                                                            $obj['taxamount'] = empty($obj['taxamount'])? '0.00' : $obj['taxamount'];
                                                                                                                                                                            $obj['grossamount'] = empty($obj['grossamount'])? 'NULL' : $obj['grossamount'];
                                                                                                                                                                            $obj['exciseunit'] = empty($obj['exciseunit'])? '' : $obj['exciseunit'];
                                                                                                                                                                            $obj['excisecurrency'] = empty($obj['excisecurrency'])? '' : $obj['excisecurrency'];
                                                                                                                                                                            $obj['taxratename'] = empty($obj['taxratename'])? '' : $obj['taxratename'];
                                                                                                                                                                            
                                                                                                                                                                            //$obj['goodid'] = empty($obj['goodid'])? 'NULL' : $obj['goodid'];
                                                                                                                                                                            $obj['goodid'] = $t_goods[$j]['id'];
                                                                                                                                                                            
                                                                                                                                                                            $sql = 'INSERT INTO tbltaxdetails (
                                    groupid,
                                    goodid,
                                    taxcategory,
                                    netamount,
                                    taxrate,
                                    taxamount,
                                    grossamount,
                                    exciseunit,
                                    excisecurrency,
                                    taxratename,
                                    taxdescription,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ('
                                                                                                                                                                                . $taxdetailgroupid . ', '
                                                                                                                                                                                    . $obj['goodid'] . ', "'
                                                                                                                                                                                        . addslashes($obj['taxcategory']) . '", '
                                                                                                                                                                                            . $obj['netamount'] . ', '
                                                                                                                                                                                                . $obj['taxrate'] . ', '
                                                                                                                                                                                                    . $obj['taxamount'] . ', '
                                                                                                                                                                                                        . $obj['grossamount'] . ', "'
                                                                                                                                                                                                            . $obj['exciseunit'] . '", "'
                                                                                                                                                                                                                . $obj['excisecurrency'] . '", "'
                                                                                                                                                                                                                    . $obj['taxratename'] . '", "'
                                                                                                                                                                                                                        . $obj['taxdescription'] . '", "'
                                                                                                                                                                                                                            . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                . $userid . ', "'
                                                                                                                                                                                                                                    . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                        . $userid . ')';
                                                                                                                                                                                                                                        
                                                                                                                                                                                                                                        $this->logger->write("Utilities : createcreditnote() : The SQL is " . $sql, 'r');
                                                                                                                                                                                                                                        $this->db->exec(array($sql));
                                                                                                                                                                                                                                        $j = $j + 1;
                                                                                                                                                                                                                                        
                                                                                                                                                                        }
                                                                                                                                                                    } catch (Exception $e) {
                                                                                                                                                                        $this->logger->write("Utilities : createcreditnote() : Failed to select from table tbltaxdetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                    }
                                                                                                                                                                } catch (Exception $e) {
                                                                                                                                                                    $this->logger->write("Utilities : createcreditnote() : Failed to insert into the table tbltaxdetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                }
                                                                                                                                                                
                                                                                                                                                                if (trim($buyer['referenceno']) !== '' || !empty(trim($buyer['referenceno']))) {
                                                                                                                                                                    try{
                                                                                                                                                                        
                                                                                                                                                                        $sql = 'INSERT INTO tblbuyers (
                                    tin,
                                    ninbrn,
                                    PassportNum,
                                    legalname,
                                    businessname,
                                    address,
                                    mobilephone,
                                    linephone,
                                    emailaddress,
                                    placeofbusiness,
                                    type,
                                    citizineship,
                                    sector,
                                    referenceno,
                                    datasource,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ("'
                                                                                                                                                                            . addslashes($buyer['tin']) . '", "'
                                                                                                                                                                                . addslashes($buyer['ninbrn']) . '", "'
                                                                                                                                                                                    . addslashes($buyer['PassportNum']) . '", "'
                                                                                                                                                                                        . addslashes($buyer['legalname']) . '", "'
                                                                                                                                                                                            . addslashes($buyer['businessname']) . '", "'
                                                                                                                                                                                                . addslashes($buyer['address']) . '", "'
                                                                                                                                                                                                    . addslashes($buyer['mobilephone']) . '", "'
                                                                                                                                                                                                        . addslashes($buyer['linephone']) . '", "'
                                                                                                                                                                                                            . addslashes($buyer['emailaddress']) . '", "'
                                                                                                                                                                                                                . addslashes($buyer['placeofbusiness']) . '", "'
                                                                                                                                                                                                                    . $buyer['type'] . '", "'
                                                                                                                                                                                                                        . addslashes($buyer['citizineship']) . '", "'
                                                                                                                                                                                                                            . addslashes($buyer['sector']) . '", "'
                                                                                                                                                                                                                                . addslashes($buyer['referenceno']) . '", "'
                                                                                                                                                                                                                                    . $buyer['datasource'] . '", "'
                                                                                                                                                                                                                                        . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                            . $userid . ', "'
                                                                                                                                                                                                                                                . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                                    . $userid . ')';
                                                                                                                                                                                                                                                    
                                                                                                                                                                                                                                                    $this->logger->write("Utilities : createcreditnote() : The SQL is " . $sql, 'r');
                                                                                                                                                                                                                                                    $this->db->exec(array($sql));
                                                                                                                                                                                                                                                    
                                                                                                                                                                                                                                                    try {
                                                                                                                                                                                                                                                        $by = array ();
                                                                                                                                                                                                                                                        $r = $this->db->exec(array('SELECT id "id" FROM tblbuyers WHERE referenceno = "' . $buyer['referenceno'] . '" AND insertedby = ' . $userid));
                                                                                                                                                                                                                                                        
                                                                                                                                                                                                                                                        foreach ( $r as $obj ) {
                                                                                                                                                                                                                                                            $by [] = $obj;
                                                                                                                                                                                                                                                        }
                                                                                                                                                                                                                                                        
                                                                                                                                                                                                                                                        $buyerid = $by[0]['id'];
                                                                                                                                                                                                                                                        $this->db->exec(array('UPDATE tblcreditnotes SET buyerid = ' . $buyerid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                                                                                                                                                                                                                                                    } catch (Exception $e) {
                                                                                                                                                                                                                                                        $this->logger->write("Utilities : createcreditnote() : Failed to select and update table tblcreditnotes. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                                                                                                    }
                                                                                                                                                                    } catch (Exception $e) {
                                                                                                                                                                        $this->logger->write("Utilities : createcreditnote() : The operation to create a buyer was not successful. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                    }
                                                                                                                                                                }
                                                                                                                                                                
                                                                                                                                                                
                                                                                                                                                                
                                                                                                                                                                return true;
        } catch (Exception $e) {
            $this->logger->write("Utilities : createcreditnote() : The operation to create the credit note was not successful. The error message is " . $e->getMessage(), 'r');
            return false;
        }
    }
 
    /**
     * @name createimportedservice
     * @desc create an imported service invoice in eTW
     * @return bool
     * @param $invoicedetails array, $goods array, $taxes array, $buyer array, $importedseller array
     *
     */
    function createimportedservice($invoicedetails, $goods, $taxes, $buyer, $userid, $importedseller){
        /**
         * 0. Insert a new imported service invoice and retrieve its id
         * 1. Create a param group for goods, taxes, and payments
         * 2. Modify the following arrays
         * 2.1 goods
         * 2.2 payments
         * 2.3 payments
         * 2.4 buyers
         * 3. Insert into the respective tables
         */
        
        
        try{
            
            $netamount = empty($invoicedetails['netamount'])? '0.00' : $invoicedetails['netamount'];
            $taxamount = empty($invoicedetails['taxamount'])? '0.00' : $invoicedetails['taxamount'];
            $grossamount = empty($invoicedetails['grossamount'])? '0.00' : $invoicedetails['grossamount'];
            $itemcount = empty($invoicedetails['itemcount'])? '0' : $invoicedetails['itemcount'];
            $userid = empty($userid) || trim($userid) == ''? $this->userid : $userid;
            $currencyRate = empty($invoicedetails['currencyRate'])? '1' : $invoicedetails['currencyRate'];
            
            $sql = 'INSERT INTO tblimportedservices
                                    (erpinvoiceid,
                                    erpinvoiceno,
                                    antifakecode,
                                    deviceno,
                                    issueddate,
                                    issuedtime,
                                    operator,
                                    currency,
                                    oriinvoiceid,
                                    invoicetype,
                                    invoicekind,
                                    datasource,
                                    invoiceindustrycode,
                                    einvoiceid,
                                    einvoicenumber,
                                    einvoicedatamatrixcode,
                                    isbatch,
                                    netamount,
                                    taxamount,
                                    grossamount,
                                    origrossamount,
                                    itemcount,
                                    modecode,
                                    modename,
                                    remarks,
                                    buyerid,
                                    sellerid,
                                    issueddatepdf,
                                    grossamountword,
                                    isinvalid,
                                    isrefund,
                                    vouchertype,
                                    vouchertypename,
                                    currencyRate,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ("'
                . addslashes($invoicedetails['erpinvoiceid']) . '", "'
                    . addslashes($invoicedetails['erpinvoiceno']) . '", "'
                        . addslashes($invoicedetails['antifakecode']) . '", "'
                            . addslashes($invoicedetails['deviceno']) . '", "'
                                . $invoicedetails['issueddate'] . '", "'
                                    . $invoicedetails['issuedtime'] . '", "'
                                        . addslashes($invoicedetails['operator']) . '", "'
                                            . $invoicedetails['currency'] . '", "'
                                                . $invoicedetails['oriinvoiceid'] . '", '
                                                    . $invoicedetails['invoicetype'] . ', '
                                                        . $invoicedetails['invoicekind'] . ', '
                                                            . $invoicedetails['datasource'] . ', '
                                                                . $invoicedetails['invoiceindustrycode'] . ', "'
                                                                    . addslashes($invoicedetails['einvoiceid']) . '", "'
                                                                        . addslashes($invoicedetails['einvoicenumber']) . '", "'
                                                                            . addslashes($invoicedetails['einvoicedatamatrixcode']) . '", "'
                                                                                . $invoicedetails['isbatch'] . '", '
                                                                                    . $netamount . ', '
                                                                                        . $taxamount . ', '
                                                                                            . $grossamount . ', '
                                                                                                . $invoicedetails['origrossamount'] . ', '
                                                                                                    . $itemcount . ', "'
                                                                                                        . $invoicedetails['modecode'] . '", "'
                                                                                                            . $invoicedetails['modename'] . '", "'
                                                                                                                . addslashes($invoicedetails['remarks']) . '", '
                                                                                                                    . 'NULL, '
                                                                                                                        . $invoicedetails['sellerid'] . ', "'
                                                                                                                            . $invoicedetails['issueddatepdf'] . '", "'
                                                                                                                                . $invoicedetails['grossamountword'] . '", '
                                                                                                                                    . $invoicedetails['isinvalid'] . ', '
                                                                                                                                        . $invoicedetails['isrefund'] . ', "'
                                                                                                                                            . addslashes($invoicedetails['vchtype']) . '", "'
                                                                                                                                                . addslashes($invoicedetails['vchtypename']) . '", '
                                                                                                                                                    . $currencyRate . ', "'
                                                                                                                                                        . date('Y-m-d H:i:s') . '", '
                                                                                                                                                            . $userid . ', "'
                                                                                                                                                                . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                    . $userid . ')';
                                                                                                                                                                    
                                                                                                                                                                    $this->logger->write("Utilities : createimportedservice() : The SQL is " . $sql, 'r');
                                                                                                                                                                    $this->db->exec(array($sql));
                                                                                                                                                                    $this->logger->write("Utilities : createimportedservice() : The invoice has been added", 'r');
                                                                                                                                                                    
                                                                                                                                                                    
                                                                                                                                                                    $this->logger->write("Utilities : createimportedservice() : The FDN is " . $invoicedetails['antifakecode'], 'r');
                                                                                                                                                                    
                                                                                                                                                                    $data = array();
                                                                                                                                                                    $r = $this->db->exec(array(
                                                                                                                                                                        'SELECT id "id" FROM tblimportedservices WHERE TRIM(antifakecode) = \'' . $invoicedetails['antifakecode'] . '\''
                                                                                                                                                                    ));
                                                                                                                                                                    
                                                                                                                                                                    foreach ($r as $obj) {
                                                                                                                                                                        $data[] = $obj;
                                                                                                                                                                    }
                                                                                                                                                                    
                                                                                                                                                                    $id = $data[0]['id'];
                                                                                                                                                                    
                                                                                                                                                                    try {
                                                                                                                                                                        $paramgroupdescription = "This is an autogenerated group id for the invoice id " . $id;
                                                                                                                                                                        
                                                                                                                                                                        $this->db->exec(array('INSERT INTO tblgooddetailgroups (owner, entitytype, description, inserteddt, insertedby, modifieddt, modifiedby)
                                            VALUES(' . $id . ', ' . $this->appsettings['INVOICEENTITYTYPE'] . ', "' . $paramgroupdescription . '", NOW(), ' . $userid . ', NOW(), ' . $userid . ')'));
                                                                                                                                                                        
                                                                                                                                                                        try {
                                                                                                                                                                            $pg = array ();
                                                                                                                                                                            $r = $this->db->exec(array('SELECT MAX(id) "id" FROM tblgooddetailgroups WHERE owner = ' . $id . ' AND entitytype = ' . $this->appsettings['INVOICEENTITYTYPE'] . ' AND insertedby = ' . $userid));
                                                                                                                                                                            
                                                                                                                                                                            foreach ( $r as $obj ) {
                                                                                                                                                                                $pg [] = $obj;
                                                                                                                                                                            }
                                                                                                                                                                            
                                                                                                                                                                            $gooddetailgroupid = $pg[0]['id'];
                                                                                                                                                                            $this->db->exec(array('UPDATE tblimportedservices SET gooddetailgroupid = ' . $gooddetailgroupid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                                                                                                                                                                            
                                                                                                                                                                            /*Insert Goods*/
                                                                                                                                                                            
                                                                                                                                                                            $i = 0;
                                                                                                                                                                            foreach ($goods as $obj) {
                                                                                                                                                                                
                                                                                                                                                                                
                                                                                                                                                                                $obj['item'] = empty($obj['item'])? '' : $obj['item'];
                                                                                                                                                                                $obj['itemcode'] = empty($obj['itemcode'])? '' : $obj['itemcode'];
                                                                                                                                                                                $obj['qty'] = empty($obj['qty'])? 'NULL' : $obj['qty'];
                                                                                                                                                                                $obj['unitofmeasure'] = empty($obj['unitofmeasure'])? '' : $obj['unitofmeasure'];
                                                                                                                                                                                $obj['unitprice'] = empty($obj['unitprice'])? 'NULL' : $obj['unitprice'];
                                                                                                                                                                                $obj['total'] = empty($obj['total'])? 'NULL' : $obj['total'];
                                                                                                                                                                                $obj['taxrate'] = empty($obj['taxrate'])? 'NULL' : $obj['taxrate'];
                                                                                                                                                                                $obj['tax'] = empty($obj['tax'])? '0.00' : $obj['tax'];
                                                                                                                                                                                $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? 'NULL' : $obj['discounttotal'];
                                                                                                                                                                                $obj['discounttaxrate'] = (empty($obj['discounttaxrate']) || $obj['discounttaxrate'] == '0.00')? 'NULL' : $obj['discounttaxrate'];
                                                                                                                                                                                $obj['discountflag'] = empty($obj['discountflag'])? '2' : $obj['discountflag'];
                                                                                                                                                                                $obj['deemedflag'] = empty($obj['deemedflag'])? '2' : $obj['deemedflag'];
                                                                                                                                                                                $obj['exciseflag'] = empty($obj['exciseflag'])? '2' : $obj['exciseflag'];
                                                                                                                                                                                $obj['categoryid'] = empty($obj['categoryid'])? 'NULL' : $obj['categoryid'];
                                                                                                                                                                                $obj['categoryname'] = empty($obj['categoryname'])? '' : $obj['categoryname'];
                                                                                                                                                                                $obj['goodscategoryid'] = empty($obj['goodscategoryid'])? 'NULL' : $obj['goodscategoryid'];
                                                                                                                                                                                $obj['goodscategoryname'] = empty($obj['goodscategoryname'])? '' : $obj['goodscategoryname'];
                                                                                                                                                                                $obj['exciserate'] = empty($obj['exciserate'])? '' : $obj['exciserate'];
                                                                                                                                                                                $obj['exciserule'] = empty($obj['exciserule'])? 'NULL' : $obj['exciserule'];
                                                                                                                                                                                $obj['excisetax'] = empty($obj['excisetax'])? 'NULL' : $obj['excisetax'];
                                                                                                                                                                                $obj['pack'] = empty($obj['pack'])? 'NULL' : $obj['pack'];
                                                                                                                                                                                $obj['stick'] = empty($obj['stick'])? 'NULL' : $obj['stick'];
                                                                                                                                                                                $obj['exciseunit'] = empty($obj['exciseunit'])? 'NULL' : $obj['exciseunit'];
                                                                                                                                                                                $obj['excisecurrency'] = empty($obj['excisecurrency'])? '' : $obj['excisecurrency'];
                                                                                                                                                                                $obj['exciseratename'] = empty($obj['exciseratename'])? '' : $obj['exciseratename'];
                                                                                                                                                                                
                                                                                                                                                                                $sql = 'INSERT INTO tblgooddetails (
                                    groupid,
                                    item,
                                    itemcode,
                                    qty,
                                    unitofmeasure,
                                    unitprice,
                                    total,
                                    taxid,
                                    taxrate,
                                    tax,
                                    discounttotal,
                                    discounttaxrate,
                                    discountpercentage,
                                    ordernumber,
                                    discountflag,
                                    deemedflag,
                                    exciseflag,
                                    categoryid,
                                    categoryname,
                                    goodscategoryid,
                                    goodscategoryname,
                                    exciserate,
                                    exciserule,
                                    excisetax,
                                    pack,
                                    stick,
                                    exciseunit,
                                    excisecurrency,
                                    exciseratename,
                                    taxcategory,
                                    unitofmeasurename,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ('
                                                                                                                                                                                    . $gooddetailgroupid . ', "'
                                                                                                                                                                                        . addslashes($obj['item']) . '", "'
                                                                                                                                                                                            . addslashes($obj['itemcode']) . '", '
                                                                                                                                                                                                . $obj['qty'] . ', "'
                                                                                                                                                                                                    . $obj['unitofmeasure'] . '", '
                                                                                                                                                                                                        . $obj['unitprice'] . ', '
                                                                                                                                                                                                            . $obj['total'] . ', '
                                                                                                                                                                                                                . $obj['taxid'] . ', '
                                                                                                                                                                                                                    . $obj['taxrate'] . ', '
                                                                                                                                                                                                                        . $obj['tax'] . ', '
                                                                                                                                                                                                                            . $obj['discounttotal'] . ', '
                                                                                                                                                                                                                                . $obj['discounttaxrate'] . ', '
                                                                                                                                                                                                                                    . $obj['discountpercentage'] . ', '
                                                                                                                                                                                                                                        . $i . ', '
                                                                                                                                                                                                                                            . $obj['discountflag'] . ', '
                                                                                                                                                                                                                                                . $obj['deemedflag'] . ', '
                                                                                                                                                                                                                                                    . $obj['exciseflag'] . ', '
                                                                                                                                                                                                                                                        . $obj['categoryid'] . ', "'
                                                                                                                                                                                                                                                            . addslashes($obj['categoryname']) . '", '
                                                                                                                                                                                                                                                                . $obj['goodscategoryid'] . ', "'
                                                                                                                                                                                                                                                                    . addslashes($obj['goodscategoryname']) . '", "'
                                                                                                                                                                                                                                                                        . $obj['exciserate'] . '", '
                                                                                                                                                                                                                                                                            . $obj['exciserule'] . ', '
                                                                                                                                                                                                                                                                                . $obj['excisetax'] . ', '
                                                                                                                                                                                                                                                                                    . $obj['pack'] . ', '
                                                                                                                                                                                                                                                                                        . $obj['stick'] . ', '
                                                                                                                                                                                                                                                                                            . $obj['exciseunit'] . ', "'
                                                                                                                                                                                                                                                                                                . $obj['excisecurrency'] . '", "'
                                                                                                                                                                                                                                                                                                    . $obj['exciseratename'] . '", "'
                                                                                                                                                                                                                                                                                                        . $obj['taxdisplaycategory'] . '", "'
                                                                                                                                                                                                                                                                                                            . $obj['unitofmeasurename'] . '", "'
                                                                                                                                                                                                                                                                                                                . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                                                                                                    . $userid . ', "'
                                                                                                                                                                                                                                                                                                                        . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                                                                                                            . $userid . ')';
                                                                                                                                                                                                                                                                                                                            
                                                                                                                                                                                                                                                                                                                            $this->logger->write("Utilities : createimportedservice() : The SQL is " . $sql, 'r');
                                                                                                                                                                                                                                                                                                                            $this->db->exec(array($sql));
                                                                                                                                                                                                                                                                                                                            
                                                                                                                                                                                                                                                                                                                            $i = $i + 1;
                                                                                                                                                                            }
                                                                                                                                                                            
                                                                                                                                                                        } catch (Exception $e) {
                                                                                                                                                                            $this->logger->write("Utilities : createimportedservice() : Failed to select and insert into table tblgooddetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                        }
                                                                                                                                                                    } catch (Exception $e) {
                                                                                                                                                                        $this->logger->write("Utilities : createimportedservice() : Failed to insert into the table tblgooddetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                    }
                                                                                                                                                                    
                                                                                                                                                                    
                                                                                                                                                                    try {
                                                                                                                                                                        $paramgroupdescription = "This is an autogenerated group id for the invoice id " . $id;
                                                                                                                                                                        
                                                                                                                                                                        $this->db->exec(array('INSERT INTO tblpaymentdetailgroups (owner, entitytype, description, inserteddt, insertedby, modifieddt, modifiedby)
                                            VALUES(' . $id . ', ' . $this->appsettings['INVOICEENTITYTYPE'] . ', "' . $paramgroupdescription . '", NOW(), ' . $userid . ', NOW(), ' . $userid . ')'));
                                                                                                                                                                        
                                                                                                                                                                        try {
                                                                                                                                                                            $pg = array ();
                                                                                                                                                                            $r = $this->db->exec(array('SELECT MAX(id) "id" FROM tblpaymentdetailgroups WHERE owner = ' . $id . ' AND entitytype = ' . $this->appsettings['INVOICEENTITYTYPE'] . ' AND insertedby = ' . $userid));
                                                                                                                                                                            
                                                                                                                                                                            foreach ( $r as $obj ) {
                                                                                                                                                                                $pg [] = $obj;
                                                                                                                                                                            }
                                                                                                                                                                            
                                                                                                                                                                            $paymentdetailgroupid = $pg[0]['id'];
                                                                                                                                                                            $this->db->exec(array('UPDATE tblimportedservices SET paymentdetailgroupid = ' . $paymentdetailgroupid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                                                                                                                                                                        } catch (Exception $e) {
                                                                                                                                                                            $this->logger->write("Utilities : createimportedservice() : Failed to select from table tblpaymentdetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                        }
                                                                                                                                                                    } catch (Exception $e) {
                                                                                                                                                                        $this->logger->write("Utilities : createimportedservice() : Failed to insert into the table tblpaymentdetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                    }
                                                                                                                                                                    
                                                                                                                                                                    
                                                                                                                                                                    try {
                                                                                                                                                                        $paramgroupdescription = "This is an autogenerated group id for the invoice id " . $id;
                                                                                                                                                                        
                                                                                                                                                                        $this->db->exec(array('INSERT INTO tbltaxdetailgroups (owner, entitytype, description, inserteddt, insertedby, modifieddt, modifiedby)
                                            VALUES(' . $id . ', ' . $this->appsettings['INVOICEENTITYTYPE'] . ', "' . $paramgroupdescription . '", NOW(), ' . $userid . ', NOW(), ' . $userid . ')'));
                                                                                                                                                                        
                                                                                                                                                                        try {
                                                                                                                                                                            $pg = array ();
                                                                                                                                                                            $r = $this->db->exec(array('SELECT MAX(id) "id" FROM tbltaxdetailgroups WHERE owner = ' . $id . ' AND entitytype = ' . $this->appsettings['INVOICEENTITYTYPE'] . ' AND insertedby = ' . $userid));
                                                                                                                                                                            
                                                                                                                                                                            foreach ( $r as $obj ) {
                                                                                                                                                                                $pg [] = $obj;
                                                                                                                                                                            }
                                                                                                                                                                            
                                                                                                                                                                            $taxdetailgroupid = $pg[0]['id'];
                                                                                                                                                                            $this->db->exec(array('UPDATE tblimportedservices SET taxdetailgroupid = ' . $taxdetailgroupid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                                                                                                                                                                            
                                                                                                                                                                            //Get details of goods inserted
                                                                                                                                                                            $t_goods = $this->db->exec(array('SELECT * FROM tblgooddetails g WHERE g.groupid = ' . $gooddetailgroupid . ' ORDER BY id ASC'));
                                                                                                                                                                            
                                                                                                                                                                            //Insert Taxes
                                                                                                                                                                            $j = 0;
                                                                                                                                                                            foreach ($taxes as $obj) {
                                                                                                                                                                                /**
                                                                                                                                                                                 * Modification Date: 2021-01-26
                                                                                                                                                                                 * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
                                                                                                                                                                                 * */
                                                                                                                                                                                if (trim($obj['discountflag']) == '1') {
                                                                                                                                                                                    $obj['d_grossamount'] = $obj['discounttotal'];/*Should be a negative #*/
                                                                                                                                                                                    $obj['d_taxamount'] = ($obj['d_grossamount']/($obj['discounttaxrate'] + 1)) * $obj['discounttaxrate'];
                                                                                                                                                                                    $obj['d_netamount'] = $obj['d_grossamount'] - $obj['d_taxamount'];
                                                                                                                                                                                    
                                                                                                                                                                                    $this->logger->write("Utilities : createimportedservice() : Calculating taxes. The d_grossamount is " . $obj['d_grossamount'], 'r');
                                                                                                                                                                                    $this->logger->write("Utilities : createimportedservice() : Calculating taxes. The d_taxamount is " . $obj['d_taxamount'], 'r');
                                                                                                                                                                                    $this->logger->write("Utilities : createimportedservice() : Calculating taxes. The d_netamount is " . $obj['d_netamount'], 'r');
                                                                                                                                                                                }
                                                                                                                                                                                
                                                                                                                                                                                if (strtoupper(trim($obj['taxcategory'])) == 'DEEMED') {
                                                                                                                                                                                    $obj['netamount'] = floor(($obj['netamount'] + $obj['d_netamount'])*100)/100;
                                                                                                                                                                                    $obj['taxamount'] = floor(($obj['taxamount'] + $obj['d_taxamount'])*100)/100;
                                                                                                                                                                                    //$obj['grossamount'] = floor($obj['grossamount']*100)/100;
                                                                                                                                                                                    $obj['grossamount'] = $obj['netamount'] + $obj['taxamount'];
                                                                                                                                                                                    
                                                                                                                                                                                    $obj['netamount'] = number_format($obj['netamount'], 2, '.', '');
                                                                                                                                                                                    $obj['taxamount'] = number_format($obj['taxamount'], 2, '.', '');
                                                                                                                                                                                    $obj['grossamount'] = number_format($obj['grossamount'], 2, '.', '');
                                                                                                                                                                                } else {
                                                                                                                                                                                    $obj['netamount'] = round(($obj['netamount'] + $obj['d_netamount']), 2);
                                                                                                                                                                                    $obj['taxamount'] = round(($obj['taxamount'] + $obj['d_taxamount']), 2);
                                                                                                                                                                                    //$obj['grossamount'] = round($obj['grossamount'], 2);
                                                                                                                                                                                    $obj['grossamount'] = $obj['netamount'] + $obj['taxamount'];
                                                                                                                                                                                }
                                                                                                                                                                                
                                                                                                                                                                                $obj['taxcategory'] = empty($obj['taxcategory'])? '' : $obj['taxcategory'];
                                                                                                                                                                                $obj['netamount'] = empty($obj['netamount'])? 'NULL' : $obj['netamount'];
                                                                                                                                                                                $obj['taxrate'] = empty($obj['taxrate'])? '' : $obj['taxrate'];
                                                                                                                                                                                $obj['taxamount'] = empty($obj['taxamount'])? '0.00' : $obj['taxamount'];
                                                                                                                                                                                $obj['grossamount'] = empty($obj['grossamount'])? 'NULL' : $obj['grossamount'];
                                                                                                                                                                                $obj['exciseunit'] = empty($obj['exciseunit'])? '' : $obj['exciseunit'];
                                                                                                                                                                                $obj['excisecurrency'] = empty($obj['excisecurrency'])? '' : $obj['excisecurrency'];
                                                                                                                                                                                $obj['taxratename'] = empty($obj['taxratename'])? '' : $obj['taxratename'];
                                                                                                                                                                                
                                                                                                                                                                                //$obj['goodid'] = empty($obj['goodid'])? 'NULL' : $obj['goodid'];
                                                                                                                                                                                $obj['goodid'] = $t_goods[$j]['id'];
                                                                                                                                                                                
                                                                                                                                                                                $sql = 'INSERT INTO tbltaxdetails (
                                    groupid,
                                    goodid,
                                    taxcategory,
                                    netamount,
                                    taxrate,
                                    taxamount,
                                    grossamount,
                                    exciseunit,
                                    excisecurrency,
                                    taxratename,
                                    taxdescription,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ('
                                                                                                                                                                                    . $taxdetailgroupid . ', '
                                                                                                                                                                                        . $obj['goodid'] . ', "'
                                                                                                                                                                                            . addslashes($obj['taxcategory']) . '", '
                                                                                                                                                                                                . $obj['netamount'] . ', '
                                                                                                                                                                                                    . $obj['taxrate'] . ', '
                                                                                                                                                                                                        . $obj['taxamount'] . ', '
                                                                                                                                                                                                            . $obj['grossamount'] . ', "'
                                                                                                                                                                                                                . $obj['exciseunit'] . '", "'
                                                                                                                                                                                                                    . $obj['excisecurrency'] . '", "'
                                                                                                                                                                                                                        . $obj['taxratename'] . '", "'
                                                                                                                                                                                                                            . $obj['taxdescription'] . '", "'
                                                                                                                                                                                                                                . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                    . $userid . ', "'
                                                                                                                                                                                                                                        . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                            . $userid . ')';
                                                                                                                                                                                                                                            
                                                                                                                                                                                                                                            $this->logger->write("Utilities : createimportedservice() : The SQL is " . $sql, 'r');
                                                                                                                                                                                                                                            $this->db->exec(array($sql));
                                                                                                                                                                                                                                            $j = $j + 1;
                                                                                                                                                                                                                                            
                                                                                                                                                                            }
                                                                                                                                                                        } catch (Exception $e) {
                                                                                                                                                                            $this->logger->write("Utilities : createimportedservice() : Failed to select from table tbltaxdetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                        }
                                                                                                                                                                    } catch (Exception $e) {
                                                                                                                                                                        $this->logger->write("Utilities : createimportedservice() : Failed to insert into the table tbltaxdetailgroups. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                    }
                                                                                                                                                                    
                                                                                                                                                                    
                                                                                                                                                                    if (trim($buyer['referenceno']) !== '' || !empty(trim($buyer['referenceno']))) {
                                                                                                                                                                        try{
                                                                                                                                                                            
                                                                                                                                                                            $sql = 'INSERT INTO tblbuyers (
                                    tin,
                                    ninbrn,
                                    PassportNum,
                                    legalname,
                                    businessname,
                                    address,
                                    mobilephone,
                                    linephone,
                                    emailaddress,
                                    placeofbusiness,
                                    type,
                                    citizineship,
                                    sector,
                                    referenceno,
                                    datasource,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ("'
                                                                                                                                                                                . addslashes($buyer['tin']) . '", "'
                                                                                                                                                                                    . addslashes($buyer['ninbrn']) . '", "'
                                                                                                                                                                                        . addslashes($buyer['PassportNum']) . '", "'
                                                                                                                                                                                            . addslashes($buyer['legalname']) . '", "'
                                                                                                                                                                                                . addslashes($buyer['businessname']) . '", "'
                                                                                                                                                                                                    . addslashes($buyer['address']) . '", "'
                                                                                                                                                                                                        . addslashes($buyer['mobilephone']) . '", "'
                                                                                                                                                                                                            . addslashes($buyer['linephone']) . '", "'
                                                                                                                                                                                                                . addslashes($buyer['emailaddress']) . '", "'
                                                                                                                                                                                                                    . addslashes($buyer['placeofbusiness']) . '", "'
                                                                                                                                                                                                                        . $buyer['type'] . '", "'
                                                                                                                                                                                                                            . addslashes($buyer['citizineship']) . '", "'
                                                                                                                                                                                                                                . addslashes($buyer['sector']) . '", "'
                                                                                                                                                                                                                                    . addslashes($buyer['referenceno']) . '", "'
                                                                                                                                                                                                                                        . $buyer['datasource'] . '", "'
                                                                                                                                                                                                                                            . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                                . $userid . ', "'
                                                                                                                                                                                                                                                    . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                                        . $userid . ')';
                                                                                                                                                                                                                                                        
                                                                                                                                                                                                                                                        $this->logger->write("Utilities : createimportedservice() : The SQL is " . $sql, 'r');
                                                                                                                                                                                                                                                        $this->db->exec(array($sql));
                                                                                                                                                                                                                                                        
                                                                                                                                                                                                                                                        try {
                                                                                                                                                                                                                                                            $by = array ();
                                                                                                                                                                                                                                                            $r = $this->db->exec(array('SELECT id "id" FROM tblbuyers WHERE referenceno = "' . $buyer['referenceno'] . '" AND insertedby = ' . $userid));
                                                                                                                                                                                                                                                            
                                                                                                                                                                                                                                                            foreach ( $r as $obj ) {
                                                                                                                                                                                                                                                                $by [] = $obj;
                                                                                                                                                                                                                                                            }
                                                                                                                                                                                                                                                            
                                                                                                                                                                                                                                                            $buyerid = $by[0]['id'];
                                                                                                                                                                                                                                                            $this->db->exec(array('UPDATE tblimportedservices SET buyerid = ' . $buyerid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                                                                                                                                                                                                                                                        } catch (Exception $e) {
                                                                                                                                                                                                                                                            $this->logger->write("Utilities : createimportedservice() : Failed to select and update table tblimportedservices. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                                                                                                        }
                                                                                                                                                                        } catch (Exception $e) {
                                                                                                                                                                            $this->logger->write("Utilities : createimportedservice() : The operation to create a buyer was not successful. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                        }
                                                                                                                                                                    }
                                                                                                                                                                    
                                                                                                                                                                    if (trim($importedseller['referenceno']) !== '' || !empty(trim($importedseller['referenceno']))) {
                                                                                                                                                                        try{
                                                                                                                                                                            
                                                                                                                                                                            $sql = 'INSERT INTO tblimportedsellers (
                                    tin,
                                    ninbrn,
                                    PassportNum,
                                    legalname,
                                    businessname,
                                    address,
                                    mobilephone,
                                    linephone,
                                    emailaddress,
                                    placeofbusiness,
                                    type,
                                    citizineship,
                                    sector,
                                    referenceno,
                                    datasource,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ("'
                                                                                                                                                                                . addslashes($importedseller['tin']) . '", "'
                                                                                                                                                                                    . addslashes($importedseller['ninbrn']) . '", "'
                                                                                                                                                                                        . addslashes($importedseller['PassportNum']) . '", "'
                                                                                                                                                                                            . addslashes($importedseller['legalname']) . '", "'
                                                                                                                                                                                                . addslashes($importedseller['businessname']) . '", "'
                                                                                                                                                                                                    . addslashes($importedseller['address']) . '", "'
                                                                                                                                                                                                        . addslashes($importedseller['mobilephone']) . '", "'
                                                                                                                                                                                                            . addslashes($importedseller['linephone']) . '", "'
                                                                                                                                                                                                                . addslashes($importedseller['emailaddress']) . '", "'
                                                                                                                                                                                                                    . addslashes($importedseller['placeofbusiness']) . '", "'
                                                                                                                                                                                                                        . $importedseller['type'] . '", "'
                                                                                                                                                                                                                            . addslashes($importedseller['citizineship']) . '", "'
                                                                                                                                                                                                                                . addslashes($importedseller['sector']) . '", "'
                                                                                                                                                                                                                                    . addslashes($importedseller['referenceno']) . '", "'
                                                                                                                                                                                                                                        . $importedseller['datasource'] . '", "'
                                                                                                                                                                                                                                            . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                                . $userid . ', "'
                                                                                                                                                                                                                                                    . date('Y-m-d H:i:s') . '", '
                                                                                                                                                                                                                                                        . $userid . ')';
                                                                                                                                                                                                                                                        
                                                                                                                                                                                                                                                        $this->logger->write("Utilities : createimportedservice() : The SQL is " . $sql, 'r');
                                                                                                                                                                                                                                                        $this->db->exec(array($sql));
                                                                                                                                                                                                                                                        
                                                                                                                                                                                                                                                        try {
                                                                                                                                                                                                                                                            $by = array ();
                                                                                                                                                                                                                                                            $r = $this->db->exec(array('SELECT id "id" FROM tblimportedsellers WHERE referenceno = "' . $importedseller['referenceno'] . '" AND insertedby = ' . $userid));
                                                                                                                                                                                                                                                            
                                                                                                                                                                                                                                                            foreach ( $r as $obj ) {
                                                                                                                                                                                                                                                                $by [] = $obj;
                                                                                                                                                                                                                                                            }
                                                                                                                                                                                                                                                            
                                                                                                                                                                                                                                                            $importedsellerid = $by[0]['id'];
                                                                                                                                                                                                                                                            $this->db->exec(array('UPDATE tblimportedservices SET importedsellerid = ' . $importedsellerid . ', modifieddt = NOW(), modifiedby = ' . $userid . ' WHERE id = ' . $id));
                                                                                                                                                                                                                                                        } catch (Exception $e) {
                                                                                                                                                                                                                                                            $this->logger->write("Utilities : createimportedservice() : Failed to select and update table tblimportedservices. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                                                                                                        }
                                                                                                                                                                        } catch (Exception $e) {
                                                                                                                                                                            $this->logger->write("Utilities : createimportedservice() : The operation to create a supplier was not successful. The error message is " . $e->getMessage(), 'r');
                                                                                                                                                                        }
                                                                                                                                                                    }
                                                                                                                                                                    
                                                                                                                                                                    return true;
        } catch (Exception $e) {
            $this->logger->write("Utilities : createimportedservice() : The operation to create the imported service invoice was not successful. The error message is " . $e->getMessage(), 'r');
            return false;
        }
    }
    
    
    /**
     * @name createcustomer
     * @desc create a customer
     * @return bool
     * @param $customer array
     *
     */
    function createcustomer($customer, $userid){
        
        if (!isset($customer)) {
            $this->logger->write("Utilities : createcustomer() : The customer object is not set", 'r');
            return false;
        }
        
        if (!isset($customer['legalname'])) {
            $this->logger->write("Utilities : createcustomer() : The customer name is not set", 'r');
            return false;
        } else {
            if ($customer['legalname'] == null) {
                $this->logger->write("Utilities : createcustomer() : The customer name is not set", 'r');
                return false;
            }
        }
        
        try{
            $customer['erpcustomerid'] = (trim($customer['erpcustomerid']) == ''? '' : $customer['erpcustomerid']);
            $customer['erpcustomercode'] = (trim($customer['erpcustomercode']) == ''? '' : $customer['erpcustomercode']);
            $customer['tin'] = (trim($customer['tin']) == ''? '' : $customer['tin']);
            $customer['ninbrn'] = (trim($customer['ninbrn']) == ''? '' : $customer['ninbrn']);
            $customer['PassportNum'] = (trim($customer['PassportNum']) == ''? '' : $customer['PassportNum']);
            $customer['legalname'] = (trim($customer['legalname']) == ''? '' : $customer['legalname']);
            $customer['address'] = (trim($customer['address']) == ''? '' : $customer['address']);
            $customer['mobilephone'] = (trim($customer['mobilephone']) == ''? '' : $customer['mobilephone']);
            $customer['linephone'] = (trim($customer['linephone']) == ''? '' : $customer['linephone']);
            
            $customer['emailaddress'] = (trim($customer['emailaddress']) == ''? '' : $customer['emailaddress']);
            $customer['placeofbusiness'] = (trim($customer['placeofbusiness']) == ''? '' : $customer['placeofbusiness']);
            $customer['type'] = (trim($customer['type']) == ''? 'NULL' : $customer['type']);
            $customer['citizineship'] = (trim($customer['citizineship']) == ''? '' : $customer['citizineship']);
            $customer['countryCode'] = (trim($customer['countryCode']) == ''? '' : $customer['countryCode']);
            $customer['sector'] = (trim($customer['sector']) == ''? '' : $customer['sector']);
            $customer['sectorCode'] = (trim($customer['sectorCode']) == ''? '' : $customer['sectorCode']);
            $customer['datasource'] = (trim($customer['datasource']) == ''? '' : $customer['datasource']);
            $customer['status'] = (trim($customer['status']) == ''? 'NULL' : $customer['status']);
            
            $userid = empty($userid) || trim($userid) == ''? $this->userid : $userid;
            
            
            $sql = 'INSERT INTO tblcustomers (
                                    erpcustomerid,
                                    erpcustomercode,
                                    tin,
                                    ninbrn,
                                    PassportNum,
                                    legalname,
                                    businessname,
                                    address,
                                    mobilephone,
                                    linephone,
                                    emailaddress,
                                    placeofbusiness,
                                    type,
                                    citizineship,
                                    countryCode,
                                    sector,
                                    sectorCode,
                                    datasource,
                                    status,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ("'
                . addslashes($customer['erpcustomerid']) . '", "'
                    . addslashes($customer['erpcustomercode']) . '", "'
                        . addslashes($customer['tin']) . '", "'
                            . addslashes($customer['ninbrn']) . '", "'
                                . addslashes($customer['PassportNum']) . '", "'
                                    . addslashes($customer['legalname']) . '", "'
                                        . addslashes($customer['businessname']) . '", "'
                                            . addslashes($customer['address']) . '", "'
                                                . addslashes($customer['mobilephone']) . '", "'
                                                    . addslashes($customer['linephone']) . '", "'
                                                        . addslashes($customer['emailaddress']) . '", "'
                                                            . addslashes($customer['placeofbusiness']) . '", '
                                                                . $customer['type'] . ', "'
                                                                    . addslashes($customer['citizineship']) . '", "'
                                                                        . addslashes($customer['countryCode']) . '", "'
                                                                            . addslashes($customer['sector']) . '", "'
                                                                                . addslashes($customer['sectorCode']) . '", "'
                                                                                    . addslashes($customer['datasource']) . '", '
                                                                                        . $customer['status'] . ', "'
                                                                                            . date('Y-m-d H:i:s') . '", '
                                                                                                . $userid . ', "'
                                                                                                    . date('Y-m-d H:i:s') . '", '
                                                                                                        . $userid . ')';
                                                                                                        
                                                                                                        $this->logger->write("Utilities : createcustomer() : The SQL is " . $sql, 'r');
                                                                                                        $this->db->exec(array($sql));
                                                                                                        
                                                                                                        return true;
                                                                                                        
        } catch (Exception $e) {
            $this->logger->write("Utilities : createcustomer() : The operation to create the customer was not successful. The error message is " . $e->getMessage(), 'r');
            return false;
        }
        
    }
    
    /**
     * @name updatecustomer
     * @desc update a customer
     * @return bool
     * @param $customer array
     *
     */
    function updatecustomer($customer, $userid){
        if (!isset($customer)) {
            $this->logger->write("Utilities : updatecustomer() : The customer object is not set", 'r');
            return false;
        }
        
        if (!isset($customer['legalname'])) {
            $this->logger->write("Utilities : updatecustomer() : The customer name is not set", 'r');
            return false;
        } else {
            if ($customer['legalname'] == null) {
                $this->logger->write("Utilities : updatecustomer() : The customer name is not set", 'r');
                return false;
            }
        }
        
        if (!isset($customer['id'])) {
            $this->logger->write("Utilities : updatecustomer() : The customer id is not set", 'r');
            return false;
        } else {
            if ($customer['id'] == null) {
                $this->logger->write("Utilities : updatecustomer() : The customer id is not set", 'r');
                return false;
            }
        }
        
        $cust = new customers($this->db);
        $cust->getByID($customer['id']);
        
        try{
            $customer['erpcustomerid'] = (trim($customer['erpcustomerid']) == ''? $cust->erpcustomerid : $customer['erpcustomerid']);
            $customer['erpcustomercode'] = (trim($customer['erpcustomercode']) == ''? $cust->erpcustomercode : $customer['erpcustomercode']);
            $customer['tin'] = (trim($customer['tin']) == ''? $cust->tin : $customer['tin']);
            $customer['ninbrn'] = (trim($customer['ninbrn']) == ''? $cust->ninbrn : $customer['ninbrn']);
            $customer['PassportNum'] = (trim($customer['PassportNum']) == ''? $cust->PassportNum : $customer['PassportNum']);
            $customer['legalname'] = (trim($customer['legalname']) == ''? $cust->legalname : $customer['legalname']);
            $customer['address'] = (trim($customer['address']) == ''? $cust->address : $customer['address']);
            $customer['mobilephone'] = (trim($customer['mobilephone']) == ''? $cust->mobilephone : $customer['mobilephone']);
            $customer['linephone'] = (trim($customer['linephone']) == ''? $cust->linephone : $customer['linephone']);
            
            $customer['emailaddress'] = (trim($customer['emailaddress']) == ''? $cust->emailaddress : $customer['emailaddress']);
            $customer['placeofbusiness'] = (trim($customer['placeofbusiness']) == ''? $cust->placeofbusiness : $customer['placeofbusiness']);
            
            $customer['type'] = (trim($customer['type']) == ''? $cust->type : $customer['type']);
            $customer['type'] = (trim($customer['type']) == ''? 'NULL' : $customer['type']);
            
            $customer['citizineship'] = (trim($customer['citizineship']) == ''? $cust->citizineship : $customer['citizineship']);
            $customer['countryCode'] = (trim($customer['countryCode']) == ''? $cust->countryCode : $customer['countryCode']);
            $customer['sector'] = (trim($customer['sector']) == ''? $cust->sector : $customer['sector']);
            $customer['sectorCode'] = (trim($customer['sectorCode']) == ''? $cust->sectorCode : $customer['sectorCode']);
            $customer['datasource'] = (trim($customer['datasource']) == ''? $cust->datasource : $customer['datasource']);
            
            $customer['status'] = (trim($customer['status']) == ''? $cust->status : $customer['status']);
            $customer['status'] = (trim($customer['status']) == ''? 'NULL' : $customer['status']);
            
            $userid = empty($userid) || trim($userid) == ''? $this->userid : $userid;
            
            $sql = 'UPDATE tblcustomers SET
                                    erpcustomerid = "' . addslashes($customer['erpcustomerid']) . '",
                                    erpcustomercode = "' . addslashes($customer['erpcustomercode']) . '",
                                    tin = "' . addslashes($customer['tin']) . '",
                                    ninbrn = "' . addslashes($customer['ninbrn']) . '",
                                    PassportNum = "' . addslashes($customer['PassportNum']) . '",
                                    legalname = "' . addslashes($customer['legalname']) . '",
                                    businessname = "' . addslashes($customer['businessname']) . '",
                                    address = "' . addslashes($customer['address']) . '",
                                    mobilephone = "' . addslashes($customer['mobilephone']) . '",
                                    linephone = "' . addslashes($customer['linephone']) . '",
                                    emailaddress = "' . addslashes($customer['emailaddress']) . '",
                                    placeofbusiness = "' . addslashes($customer['placeofbusiness']) . '",
                                    type = ' . $customer['type'] . ',
                                    citizineship = "' . addslashes($customer['citizineship']) . '",
                                    countryCode = "' . addslashes($customer['countryCode']) . '",
                                    sector = "' . addslashes($customer['sector']) . '",
                                    sectorCode = "' . addslashes($customer['sectorCode']) . '",
                                    datasource = "' . addslashes($customer['datasource']) . '",
                                    status = ' . $customer['status'] . ',
                                    modifieddt = "' .  date('Y-m-d H:i:s') . '",
                                    modifiedby = ' . $userid  . '
                                    WHERE id = ' . $customer['id'];
            
            $this->logger->write("Utilities : updatecustomer() : The SQL is " . $sql, 'r');
            $this->db->exec(array($sql));
            return true;
        } catch (Exception $e) {
            $this->logger->write("Utilities : updatecustomer() : The operation to update the customer was not successful. The error message is " . $e->getMessage(), 'r');
            return false;
        }
        
    }
    
    /**
     * @name createsupplier
     * @desc create a supplier
     * @return bool
     * @param $supplier array
     *
     */
    function createsupplier($supplier, $userid){
        
        if (!isset($supplier)) {
            $this->logger->write("Utilities : createsupplier() : The supplier object is not set", 'r');
            return false;
        }
        
        if (!isset($supplier['legalname'])) {
            $this->logger->write("Utilities : createsupplier() : The supplier name is not set", 'r');
            return false;
        } else {
            if ($supplier['legalname'] == null) {
                $this->logger->write("Utilities : createsupplier() : The supplier name is not set", 'r');
                return false;
            }
        }
        
        try{
            $supplier['erpsupplierid'] = (trim($supplier['erpsupplierid']) == ''? '' : $supplier['erpsupplierid']);
            $supplier['erpsuppliercode'] = (trim($supplier['erpsuppliercode']) == ''? '' : $supplier['erpsuppliercode']);
            $supplier['tin'] = (trim($supplier['tin']) == ''? '' : $supplier['tin']);
            $supplier['ninbrn'] = (trim($supplier['ninbrn']) == ''? '' : $supplier['ninbrn']);
            $supplier['PassportNum'] = (trim($supplier['PassportNum']) == ''? '' : $supplier['PassportNum']);
            $supplier['legalname'] = (trim($supplier['legalname']) == ''? '' : $supplier['legalname']);
            $supplier['address'] = (trim($supplier['address']) == ''? '' : $supplier['address']);
            $supplier['mobilephone'] = (trim($supplier['mobilephone']) == ''? '' : $supplier['mobilephone']);
            $supplier['linephone'] = (trim($supplier['linephone']) == ''? '' : $supplier['linephone']);
            
            $supplier['emailaddress'] = (trim($supplier['emailaddress']) == ''? '' : $supplier['emailaddress']);
            $supplier['placeofbusiness'] = (trim($supplier['placeofbusiness']) == ''? '' : $supplier['placeofbusiness']);
            $supplier['type'] = (trim($supplier['type']) == ''? 'NULL' : $supplier['type']);
            $supplier['citizineship'] = (trim($supplier['citizineship']) == ''? '' : $supplier['citizineship']);
            $supplier['countryCode'] = (trim($supplier['countryCode']) == ''? '' : $supplier['countryCode']);
            $supplier['sector'] = (trim($supplier['sector']) == ''? '' : $supplier['sector']);
            $supplier['sectorCode'] = (trim($supplier['sectorCode']) == ''? '' : $supplier['sectorCode']);
            $supplier['datasource'] = (trim($supplier['datasource']) == ''? '' : $supplier['datasource']);
            $supplier['status'] = (trim($supplier['status']) == ''? 'NULL' : $supplier['status']);
            
            $userid = empty($userid) || trim($userid) == ''? $this->userid : $userid;
            
            
            $sql = 'INSERT INTO tblsuppliers (
                                    erpsupplierid,
                                    erpsuppliercode,
                                    tin,
                                    ninbrn,
                                    PassportNum,
                                    legalname,
                                    businessname,
                                    address,
                                    mobilephone,
                                    linephone,
                                    emailaddress,
                                    placeofbusiness,
                                    type,
                                    citizineship,
                                    countryCode,
                                    sector,
                                    sectorCode,
                                    datasource,
                                    status,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ("'
                . addslashes($supplier['erpsupplierid']) . '", "'
                    . addslashes($supplier['erpsuppliercode']) . '", "'
                        . addslashes($supplier['tin']) . '", "'
                            . addslashes($supplier['ninbrn']) . '", "'
                                . addslashes($supplier['PassportNum']) . '", "'
                                    . addslashes($supplier['legalname']) . '", "'
                                        . addslashes($supplier['businessname']) . '", "'
                                            . addslashes($supplier['address']) . '", "'
                                                . addslashes($supplier['mobilephone']) . '", "'
                                                    . addslashes($supplier['linephone']) . '", "'
                                                        . addslashes($supplier['emailaddress']) . '", "'
                                                            . addslashes($supplier['placeofbusiness']) . '", '
                                                                . $supplier['type'] . ', "'
                                                                    . addslashes($supplier['citizineship']) . '", "'
                                                                        . addslashes($supplier['countryCode']) . '", "'
                                                                            . addslashes($supplier['sector']) . '", "'
                                                                                . addslashes($supplier['sectorCode']) . '", "'
                                                                                    . addslashes($supplier['datasource']) . '", '
                                                                                        . $supplier['status'] . ', "'
                                                                                            . date('Y-m-d H:i:s') . '", '
                                                                                                . $userid . ', "'
                                                                                                    . date('Y-m-d H:i:s') . '", '
                                                                                                        . $userid . ')';
                                                                                                        
                                                                                                        $this->logger->write("Utilities : createsupplier() : The SQL is " . $sql, 'r');
                                                                                                        $this->db->exec(array($sql));
                                                                                                        
                                                                                                        return true;
                                                                                                        
        } catch (Exception $e) {
            $this->logger->write("Utilities : createsupplier() : The operation to create the supplier was not successful. The error message is " . $e->getMessage(), 'r');
            return false;
        }
        
    }
    
    /**
     * @name updatesupplier
     * @desc update a supplier
     * @return bool
     * @param $supplier array
     *
     */
    function updatesupplier($supplier, $userid){
        if (!isset($supplier)) {
            $this->logger->write("Utilities : updatesupplier() : The supplier object is not set", 'r');
            return false;
        }
        
        if (!isset($supplier['legalname'])) {
            $this->logger->write("Utilities : updatesupplier() : The supplier name is not set", 'r');
            return false;
        } else {
            if ($supplier['legalname'] == null) {
                $this->logger->write("Utilities : updatesupplier() : The supplier name is not set", 'r');
                return false;
            }
        }
        
        if (!isset($supplier['id'])) {
            $this->logger->write("Utilities : updatesupplier() : The supplier id is not set", 'r');
            return false;
        } else {
            if ($supplier['id'] == null) {
                $this->logger->write("Utilities : updatesupplier() : The supplier id is not set", 'r');
                return false;
            }
        }
        
        $cust = new suppliers($this->db);
        $cust->getByID($supplier['id']);
        
        try{
            $supplier['erpsupplierid'] = (trim($supplier['erpsupplierid']) == ''? $cust->erpsupplierid : $supplier['erpsupplierid']);
            $supplier['erpsuppliercode'] = (trim($supplier['erpsuppliercode']) == ''? $cust->erpsuppliercode : $supplier['erpsuppliercode']);
            $supplier['tin'] = (trim($supplier['tin']) == ''? $cust->tin : $supplier['tin']);
            $supplier['ninbrn'] = (trim($supplier['ninbrn']) == ''? $cust->ninbrn : $supplier['ninbrn']);
            $supplier['PassportNum'] = (trim($supplier['PassportNum']) == ''? $cust->PassportNum : $supplier['PassportNum']);
            $supplier['legalname'] = (trim($supplier['legalname']) == ''? $cust->legalname : $supplier['legalname']);
            $supplier['address'] = (trim($supplier['address']) == ''? $cust->address : $supplier['address']);
            $supplier['mobilephone'] = (trim($supplier['mobilephone']) == ''? $cust->mobilephone : $supplier['mobilephone']);
            $supplier['linephone'] = (trim($supplier['linephone']) == ''? $cust->linephone : $supplier['linephone']);
            
            $supplier['emailaddress'] = (trim($supplier['emailaddress']) == ''? $cust->emailaddress : $supplier['emailaddress']);
            $supplier['placeofbusiness'] = (trim($supplier['placeofbusiness']) == ''? $cust->placeofbusiness : $supplier['placeofbusiness']);
            
            $supplier['type'] = (trim($supplier['type']) == ''? $cust->type : $supplier['type']);
            $supplier['type'] = (trim($supplier['type']) == ''? 'NULL' : $supplier['type']);
            
            $supplier['citizineship'] = (trim($supplier['citizineship']) == ''? $cust->citizineship : $supplier['citizineship']);
            $supplier['countryCode'] = (trim($supplier['countryCode']) == ''? $cust->countryCode : $supplier['countryCode']);
            $supplier['sector'] = (trim($supplier['sector']) == ''? $cust->sector : $supplier['sector']);
            $supplier['sectorCode'] = (trim($supplier['sectorCode']) == ''? $cust->sectorCode : $supplier['sectorCode']);
            $supplier['datasource'] = (trim($supplier['datasource']) == ''? $cust->datasource : $supplier['datasource']);
            
            $supplier['status'] = (trim($supplier['status']) == ''? $cust->status : $supplier['status']);
            $supplier['status'] = (trim($supplier['status']) == ''? 'NULL' : $supplier['status']);
            
            $userid = empty($userid) || trim($userid) == ''? $this->userid : $userid;
            
            $sql = 'UPDATE tblsuppliers SET
                                    erpsupplierid = "' . addslashes($supplier['erpsupplierid']) . '",
                                    erpsuppliercode = "' . addslashes($supplier['erpsuppliercode']) . '",
                                    tin = "' . addslashes($supplier['tin']) . '",
                                    ninbrn = "' . addslashes($supplier['ninbrn']) . '",
                                    PassportNum = "' . addslashes($supplier['PassportNum']) . '",
                                    legalname = "' . addslashes($supplier['legalname']) . '",
                                    businessname = "' . addslashes($supplier['businessname']) . '",
                                    address = "' . addslashes($supplier['address']) . '",
                                    mobilephone = "' . addslashes($supplier['mobilephone']) . '",
                                    linephone = "' . addslashes($supplier['linephone']) . '",
                                    emailaddress = "' . addslashes($supplier['emailaddress']) . '",
                                    placeofbusiness = "' . addslashes($supplier['placeofbusiness']) . '",
                                    type = ' . $supplier['type'] . ',
                                    citizineship = "' . addslashes($supplier['citizineship']) . '",
                                    countryCode = "' . addslashes($supplier['countryCode']) . '",
                                    sector = "' . addslashes($supplier['sector']) . '",
                                    sectorCode = "' . addslashes($supplier['sectorCode']) . '",
                                    datasource = "' . addslashes($supplier['datasource']) . '",
                                    status = ' . $supplier['status'] . ',
                                    modifieddt = "' .  date('Y-m-d H:i:s') . '",
                                    modifiedby = ' . $userid  . '
                                    WHERE id = ' . $supplier['id'];
            
            $this->logger->write("Utilities : updatesupplier() : The SQL is " . $sql, 'r');
            $this->db->exec(array($sql));
            return true;
        } catch (Exception $e) {
            $this->logger->write("Utilities : updatesupplier() : The operation to update the supplier was not successful. The error message is " . $e->getMessage(), 'r');
            return false;
        }
        
    }
    
    /**
     * @name mapmeasureunit
     * @desc return the standard code for a measure unit
     * @return string
     * @param $no string
     *
     */
    function mapmeasureunit($unit){
        /**
         * 1. Cleanup the units
         * 2. Search the measure unit table for the equivalent
         */
        $this->logger->write("Utilities : mapmeasureunit() : The raw unit is " . $unit, 'r');
        $value = '';
        
        $unit = strtoupper(trim($unit));
        
        $unit_check = new DB\SQL\Mapper($this->db, 'tblrateunits');
        $unit_check->load(array('UPPER(erpcode)=?', $unit));
        $this->logger->write($this->db->log(TRUE), 'r');
        
        if($unit_check->dry ()){
            $this->logger->write("Utilities : mapmeasureunit() : The measure unit does not exist or is not mapped", 'r');           
        } else {
            $value = $unit_check->code;
        }
        
        $this->logger->write("Utilities : mapmeasureunit() : The rate code is " . $value, 'r');
        return $value;
    }
    
    
    /**
     * @name maphscode
     * @desc return the standard code for an HS code
     * @return string
     * @param $no string
     *
     */
    function maphscode($code){
        /**
         * 1. Cleanup the code
         * 2. Search the HS code table for the equivalent
         */
        $this->logger->write("Utilities : maphscode() : The raw code is " . $code, 'r');
        $value = '';
        
        $code = strtoupper(trim($code));
        
        $code_check = new DB\SQL\Mapper($this->db, 'tblhscodes');
        $code_check->load(array('UPPER(erpcode)=?', $code));
        $this->logger->write($this->db->log(TRUE), 'r');
        
        if($code_check->dry ()){
            $this->logger->write("Utilities : maphscode() : The HS code does not exist or is not mapped", 'r');
        } else {
            $value = $code_check->code;
        }
        
        $this->logger->write("Utilities : maphscode() : The rate code is " . $value, 'r');
        return $value;
    }
    
    /**
     * @name mapindustrycode
     * @desc return the standard code for an industry
     * @return string
     * @param $code string
     *
     */
    function mapindustrycode($code){
        /**
         * 1. Cleanup the industry code
         * 2. Search the industry table for the equivalent
         */
        $this->logger->write("Utilities : mapindustrycode() : The raw industry is " . $code, 'r');
        $value = '';
        
        $code = strtoupper(trim($code));
        
        $code_check = new DB\SQL\Mapper($this->db, 'tblindustries');
        $code_check->load(array('UPPER(erpcode)=?', $code));
        $this->logger->write($this->db->log(TRUE), 'r');
        
        if($code_check->dry ()){
            $this->logger->write("Utilities : mapindustrycode() : The industry code does not exist or is not mapped", 'r');
        } else {
            $value = $code_check->code;
        }
        
        $this->logger->write("Utilities : mapindustrycode() : The industry code is " . $value, 'r');
        return $value;
    }
    
    /**
     * @name mapreasoncode
     * @desc return the standard code for a credit/debit note reason
     * @return string
     * @param $code string
     *
     */
    function mapreasoncode($code){
        /**
         * 1. Cleanup the reason code
         * 2. Search the reason table for the equivalent
         */
        $this->logger->write("Utilities : mapreasoncode() : The raw reason is " . $code, 'r');
        $value = '';
        
        $code = strtoupper(trim($code));
        
        $code_check = new DB\SQL\Mapper($this->db, 'tblcdnotereasoncodes');
        $code_check->load(array('UPPER(erpcode)=?', $code));
        $this->logger->write($this->db->log(TRUE), 'r');
        
        if($code_check->dry ()){
            $this->logger->write("Utilities : mapreasoncode() : The reason code does not exist or is not mapped in the credit note reasons", 'r');
            
            $d_code_check = new DB\SQL\Mapper($this->db, 'tbldebitnotereasoncodes');
            $d_code_check->load(array('UPPER(erpcode)=?', $code));
            $this->logger->write($this->db->log(TRUE), 'r');
            
            if($d_code_check->dry ()){
                $this->logger->write("Utilities : mapreasoncode() : The reason code does not exist or is not mapped in the debit note reasons", 'r');
            } else {
                $value = $d_code_check->code;
            }
            
        } else {
            $value = $code_check->code;
        }
        
        $this->logger->write("Utilities : mapreasoncode() : The reason code is " . $value, 'r');
        return $value;
    }
    
    /**
     * @name decodechoicecode
     * @desc return the name/definition of a choice code
     * @return string
     * @param $code string
     *
     */
    function decodechoicecode($code){
        /**
         * 1. Cleanup the choice code
         * 2. Search the choice table for the equivalent
         */
        $this->logger->write("Utilities : decodechoicecode() : The raw choice is " . $code, 'r');
        $name = '';
        
        $code = trim($code);
        
        $code_check = new DB\SQL\Mapper($this->db, 'tblchoices');
        $code_check->load(array('TRIM(code)=?', $code));
        $this->logger->write($this->db->log(TRUE), 'r');
        
        if($code_check->dry ()){
            $this->logger->write("Utilities : decodechoicecode() : The choice code does not exist or is not mapped", 'r');          
        } else {
            $name = $code_check->name;
        }
        
        $this->logger->write("Utilities : decodechoicecode() : The choice name is " . $name, 'r');
        return $name;
    }
    
    
    /**
     * @name decodetaxpayertypecode
     * @desc return the name/definition of a tax payer type code
     * @return string
     * @param $code string
     *
     */
    function decodetaxpayertypecode($code){
        /**
         * 1. Cleanup the choice code
         * 2. Search the choice table for the equivalent
         */
        $this->logger->write("Utilities : decodetaxpayertypecode() : The raw tax payer type code is " . $code, 'r');
        $name = '';
        
        $code = trim($code);
        
        $code_check = new DB\SQL\Mapper($this->db, 'tbltaxpayertypes');
        $code_check->load(array('TRIM(code)=?', $code));
        $this->logger->write($this->db->log(TRUE), 'r');
        
        if($code_check->dry ()){
            $this->logger->write("Utilities : decodetaxpayertypecode() : The tax payer type code does not exist or is not mapped", 'r');
        } else {
            $name = $code_check->name;
        }
        
        $this->logger->write("Utilities : decodetaxpayertypecode() : The tax payer type name is " . $name, 'r');
        return $name;
    }
    
    /**
     * @name decodeproductexclusioncode
     * @desc return the name/definition of a productexclusion code
     * @return string
     * @param $code string
     *
     */
    function decodeproductexclusioncode($code){
        /**
         * 1. Cleanup the productexclusion code
         * 2. Search the productexclusion table for the equivalent
         */
        $this->logger->write("Utilities : decodeproductexclusioncode() : The raw productexclusion is " . $code, 'r');
        $name = '';
        
        $code = trim($code);
        
        $code_check = new DB\SQL\Mapper($this->db, 'tblproductexclusioncodes');
        $code_check->load(array('TRIM(code)=?', $code));
        $this->logger->write($this->db->log(TRUE), 'r');
        
        if($code_check->dry ()){
            $this->logger->write("Utilities : decodeproductexclusioncode() : The productexclusion code does not exist or is not mapped", 'r');
        } else {
            $name = $code_check->name;
        }
        
        $this->logger->write("Utilities : decodeproductexclusioncode() : The productexclusion name is " . $name, 'r');
        return $name;
    }
    
    /**
     * @name decodeproductsourcecode
     * @desc return the name/definition of a productsource code
     * @return string
     * @param $code string
     *
     */
    function decodeproductsourcecode($code){
        /**
         * 1. Cleanup the productsource code
         * 2. Search the productsource table for the equivalent
         */
        $this->logger->write("Utilities : decodeproductsourcecode() : The raw productsource is " . $code, 'r');
        $name = '';
        
        $code = trim($code);
        
        $code_check = new DB\SQL\Mapper($this->db, 'tblproductsourcecodes');
        $code_check->load(array('TRIM(code)=?', $code));
        $this->logger->write($this->db->log(TRUE), 'r');
        
        if($code_check->dry ()){
            $this->logger->write("Utilities : decodeproductsourcecode() : The productsource code does not exist or is not mapped", 'r');
        } else {
            $name = $code_check->name;
        }
        
        $this->logger->write("Utilities : decodeproductsourcecode() : The productsource name is " . $name, 'r');
        return $name;
    }
    
    /**
     * @name decodeproductstatuscode
     * @desc return the name/definition of a productstatus code
     * @return string
     * @param $code string
     *
     */
    function decodeproductstatuscode($code){
        /**
         * 1. Cleanup the productstatus code
         * 2. Search the productstatus table for the equivalent
         */
        $this->logger->write("Utilities : decodeproductstatuscode() : The raw productstatus is " . $code, 'r');
        $name = '';
        
        $code = trim($code);
        
        $code_check = new DB\SQL\Mapper($this->db, 'tblproductstatuscodes');
        $code_check->load(array('TRIM(code)=?', $code));
        $this->logger->write($this->db->log(TRUE), 'r');
        
        if($code_check->dry ()){
            $this->logger->write("Utilities : decodeproductstatuscode() : The productstatus code does not exist or is not mapped", 'r');
        } else {
            $name = $code_check->name;
        }
        
        $this->logger->write("Utilities : decodeproductstatuscode() : The productstatus name is " . $name, 'r');
        return $name;
    }
    
    /**
     * @name mapstockincode
     * @desc return the standard code for stockin types
     * @return string
     * @param $code string
     *
     */
    function mapstockincode($code){
        /**
         * 1. Cleanup the industry code
         * 2. Search the industry table for the equivalent
         */
        $this->logger->write("Utilities : mapstockincode() : The raw stockin is " . $code, 'r');
        $value = '';
        
        $code = strtoupper(trim($code));
        
        $code_check = new DB\SQL\Mapper($this->db, 'tblstockintypes');
        $code_check->load(array('UPPER(erpcode)=?', $code));
        $this->logger->write($this->db->log(TRUE), 'r');
        
        if($code_check->dry ()){
            $this->logger->write("Utilities : mapstockincode() : The stockin code does not exist or is not mapped", 'r');
        } else {
            $value = $code_check->code;
        }
        
        $this->logger->write("Utilities : mapstockincode() : The stockin code is " . $value, 'r');
        return $value;
    }
    
    /**
     * @name mapstockadjustmentcode
     * @desc return the standard code for adjustment types
     * @return string
     * @param $code string
     *
     */
    function mapstockadjustmentcode($code){
        /**
         * 1. Cleanup the industry code
         * 2. Search the industry table for the equivalent
         */
        $this->logger->write("Utilities : mapstockadjustmentcode() : The raw adjustment is " . $code, 'r');
        $value = '';
        
        $code = strtoupper(trim($code));
        
        $code_check = new DB\SQL\Mapper($this->db, 'tblstockadjustmenttypes');
        $code_check->load(array('UPPER(erpcode)=?', $code));
        $this->logger->write($this->db->log(TRUE), 'r');
        
        if($code_check->dry ()){
            $this->logger->write("Utilities : mapstockadjustmentcode() : The adjustment code does not exist or is not mapped", 'r');
        } else {
            $value = $code_check->code;
        }
        
        $this->logger->write("Utilities : mapstockadjustmentcode() : The adjustment code is " . $value, 'r');
        return $value;
    }
    
    /**
     * @name mapbuyertypecode
     * @desc return the standard code for a buyer type
     * @return string
     * @param $code string
     *
     */
    function mapbuyertypecode($code){
        /**
         * 1. Cleanup the industry code
         * 2. Search the industry table for the equivalent
         */
        $this->logger->write("Utilities : mapbuyertypecode() : The raw buyer type is " . $code, 'r');
        $value = '';
        
        $code = strtoupper(trim($code));
        
        $code_check = new DB\SQL\Mapper($this->db, 'tblbuyertypes');
        $code_check->load(array('UPPER(erpcode)=?', $code));
        $this->logger->write($this->db->log(TRUE), 'r');
        
        if($code_check->dry ()){
            $this->logger->write("Utilities : mapbuyertypecode() : The buyer type code does not exist or is not mapped", 'r');
        } else {
            $value = $code_check->code;
        }
        
        $this->logger->write("Utilities : mapbuyertypecode() : The buyer type code is " . $value, 'r');
        return $value;
    }
    
    
    /**
     * @name mapdeliverytermcode
     * @desc return the standard code for a delivery term code
     * @return string
     * @param $code string
     *
     */
    function mapdeliverytermcode($code){
        /**
         * 1. Cleanup the industry code
         * 2. Search the industry table for the equivalent
         */
        $this->logger->write("Utilities : mapdeliverytermcode() : The raw delivery term code is " . $code, 'r');
        $value = '';
        
        $code = strtoupper(trim($code));
        
        $code_check = new DB\SQL\Mapper($this->db, 'tbldeliverytermscodes');
        $code_check->load(array('UPPER(erpcode)=?', $code));
        $this->logger->write($this->db->log(TRUE), 'r');
        
        if($code_check->dry ()){
            $this->logger->write("Utilities : mapdeliverytermcode() : The delivery term code does not exist or is not mapped", 'r');
        } else {
            $value = $code_check->code;
        }
        
        $this->logger->write("Utilities : mapdeliverytermcode() : The delivery term code is " . $value, 'r');
        return $value;
    }
    
    /**
     * @name removecommas
     * @desc return an amount without commas
     * @return float
     * @param $amount string
     *
     */
    function removecommas($amount){

        $this->logger->write("Utilities : removecommas() : The raw amount is " . $amount, 'r');
        $value = $amount;//2,80,000.00
        
        $value = str_replace(array(','), '' , $value);//280000.00
                
        $this->logger->write("Utilities : removecommas() : The final amount is " . $value, 'r');
        return $value;
    }
    
    /**
     * @name removeunitsfromrate
     * @desc return a rate without measure of units
     * @return float
     * @param $amount string
     *
     */
    function removeunitsfromrate($amount){
        
        $this->logger->write("Utilities : removeunitsfromrate() : The raw amount is " . $amount, 'r');
        $value = $amount;//56,000.00/Pcs  25,000.00/Pcs
        
        $pos = stripos($value, '/');
        //$o_len = strlen($value);
        //$len = 0;
        $start = 0;
        
        $value = substr($value, $start, $pos);
        
        $value = str_replace(array(',', '(', ')'), '' , $value);//56000.00
        
        $this->logger->write("Utilities : removeunitsfromrate() : The final amount is " . $value, 'r');
        return $value;
    }
    
    
    /**
     * @name removecommasfromamount
     * @desc return an amount without commas
     * @return float
     * @param $amount string
     *
     */
    function removecommasfromamount($amount){
        
        $this->logger->write("Utilities : removecommasfromamount() : The raw amount is " . $amount, 'r');
        $value = $amount;//56,000.00$
        
        $value = preg_replace('/[^0-9.]/', '', $value);//Remove non-digit characters, except periods
        //$value = str_replace(array(',', '(', ')'), '' , $value);//56000.00
        
        $this->logger->write("Utilities : removecommasfromamount() : The final amount is " . $value, 'r');
        return $value;
    }
    
    /**
     * @name removeunitsfromrate
     * @desc return qty without measure of units
     * @return float
     * @param $amount string
     *
     */
    function removeunitsfromqty($amount){
        
        $this->logger->write("Utilities : removeunitsfromqty() : The raw amount is " . $amount, 'r');
        $value = $amount;//5 Pcs
        
        $pos = stripos($value, ' ');
        //$o_len = strlen($value);
        //$len = 0;
        $start = 0;
        
        $value = substr($value, $start, $pos);
        
        $value = str_replace(array(',', '(', ')'), '' , $value);//5.00
        
        $this->logger->write("Utilities : removeunitsfromqty() : The final amount is " . $value, 'r');
        return $value;
    }
     
    /**
     * @name createproduct
     * @desc create a product in eTW
     * @return bool
     * @param $product array
     *
     */
    function createproduct($product, $userid){
        /**
         * 1. Pick the fields from the product array
         * 2. Convert the following fields
         * 2.1  measureUnit (getmeasureunit)
         * 2.2  currency (getcurrency)
         * 2.3  commodityCategoryId (getcommodity)
         * 2.4  haveExciseTax (getchoice)
         * 2.5  havePieceUnit (getchoice)
         * 3. Insert into the table tblproductdetails
         */
        
        try{
            $product['piecemeasureunit'] = (trim($product['piecemeasureunit']) == ''? 'NULL' : $product['piecemeasureunit']);
            $product['pieceunitprice'] = (trim($product['pieceunitprice']) == ''? 'NULL' : $product['pieceunitprice']);
            $product['packagescaledvalue'] = (trim($product['packagescaledvalue']) == ''? 'NULL' : $product['packagescaledvalue']);
            $product['piecescaledvalue'] = (trim($product['piecescaledvalue']) == ''? 'NULL' : $product['piecescaledvalue']);
            $product['excisedutylist'] = (trim($product['excisedutylist']) == ''? 'NULL' : $product['excisedutylist']);
            $product['uraquantity'] = (trim($product['uraquantity']) == ''? 'NULL' : $product['uraquantity']);
            $product['erpquantity'] = (trim($product['erpquantity']) == ''? 'NULL' : $product['erpquantity']);
            $product['purchaseprice'] = (trim($product['purchaseprice']) == ''? 'NULL' : $product['purchaseprice']);
            $product['haveotherunit'] = (trim($product['haveotherunit']) == ''? 'NULL' : $product['haveotherunit']);
            
            $product['isexempt'] = (trim($product['isexempt']) == ''? 'NULL' : $product['isexempt']);
            $product['iszerorated'] = (trim($product['iszerorated']) == ''? 'NULL' : $product['iszerorated']);
            $product['source'] = (trim($product['source']) == ''? 'NULL' : $product['source']);
            $product['exclusion'] = (trim($product['exclusion']) == ''? 'NULL' : $product['exclusion']);
            $product['statuscode'] = (trim($product['statuscode']) == ''? 'NULL' : $product['statuscode']);
            $product['taxrate'] = (trim($product['taxrate']) == ''? 'NULL' : $product['taxrate']);
            $product['serviceMark'] = (trim($product['serviceMark']) == ''? 'NULL' : $product['serviceMark']);
            
            $product['goodsTypeCode'] = (trim($product['goodsTypeCode']) == ''? 'NULL' : $product['goodsTypeCode']);
            $product['hsCode'] = (trim($product['hsCode']) == ''? 'NULL' : $product['hsCode']);
            $product['customsmeasureunit'] = (trim($product['customsmeasureunit']) == ''? 'NULL' : $product['customsmeasureunit']);
            $product['customsunitprice'] = (trim($product['customsunitprice']) == ''? 'NULL' : $product['customsunitprice']);
            $product['packagescaledvaluecustoms'] = (trim($product['packagescaledvaluecustoms']) == ''? 'NULL' : $product['packagescaledvaluecustoms']);
            $product['customsscaledvalue'] = (trim($product['customsscaledvalue']) == ''? 'NULL' : $product['customsscaledvalue']);
            $product['weight'] = (trim($product['weight']) == ''? 'NULL' : $product['weight']);
            
            $userid = empty($userid) || trim($userid) == ''? $this->userid : $userid;
            
            $sql = 'INSERT INTO tblproductdetails (
                                    uraproductidentifier,
                                    erpid,
                                    erpcode,
                                    name,
                                    code,
                                    measureunit,
                                    unitprice,
                                    currency,
                                    commoditycategorycode,
                                    hasexcisetax,
                                    description,
                                    stockprewarning,
                                    piecemeasureunit,
                                    havepieceunit,
                                    pieceunitprice,
                                    packagescaledvalue,
                                    piecescaledvalue,
                                    excisedutylist,
                                    uraquantity,
                                    erpquantity,
                                    purchaseprice,
                                    stockintype,
                                    isexempt,
                                    iszerorated,
                                    source,
                                    exclusion,
                                    statuscode,
                                    taxrate,
                                    haveotherunit,
                                    serviceMark,
                                    goodsTypeCode,
                                    hsCode,
                                    customsmeasureunit,
                                    customsunitprice,
                                    packagescaledvaluecustoms,
                                    customsscaledvalue,
                                    weight,
                                    inserteddt,
                                    insertedby,
                                    modifieddt,
                                    modifiedby)
                                    VALUES ("'
                . addslashes($product['uraproductidentifier']) . '", "'
                    . addslashes($product['erpid']) . '", "'
                        . addslashes($product['erpcode']) . '", "'
                            . addslashes($product['name']) . '", "'
                                . addslashes($product['code']) . '", "'
                                    . addslashes($product['measureunit']) . '", '
                                        . $product['unitprice'] . ', '
                                            . $product['currency'] . ', "'
                                                . addslashes($product['commoditycategorycode']) . '", '
                                                    . $product['hasexcisetax'] . ', "'
                                                        . addslashes($product['description']) . '", '
                                                            . $product['stockprewarning'] . ', "'
                                                                . $product['piecemeasureunit'] . '", '
                                                                    . $product['havepieceunit'] . ', '
                                                                        . $product['pieceunitprice'] . ', '
                                                                            . $product['packagescaledvalue'] . ', '
                                                                                . $product['piecescaledvalue'] . ', '
                                                                                    . $product['excisedutylist'] . ', '
                                                                                        . $product['uraquantity'] . ', '
                                                                                            . $product['erpquantity'] . ', '
                                                                                                . $product['purchaseprice'] . ', "'
                                                                                                    . addslashes($product['stockintype']) . '", '
                                                                                                        . $product['isexempt'] . ', '
                                                                                                            . $product['iszerorated'] . ', '
                                                                                                                . $product['source'] . ', '
                                                                                                                    . $product['exclusion'] . ', '
                                                                                                                        . $product['statuscode'] . ', '
                                                                                                                            . $product['taxrate'] . ', '
                                                                                                                                . $product['haveotherunit'] . ', '
                                                                                                                                    . $product['serviceMark'] . ', '
                                                                                                                                    . $product['goodsTypeCode'] . ', "'
                                                                                                                                    . $product['hsCode'] . '", "'
                                                                                                                                    . $product['customsmeasureunit'] . '", '
                                                                                                                                    . $product['customsunitprice'] . ', '
                                                                                                                                    . $product['packagescaledvaluecustoms'] . ', '
                                                                                                                                    . $product['customsscaledvalue'] . ', '
                                                                                                                                    . $product['weight'] . ', "'
                                                                                                                                    . date('Y-m-d H:i:s') . '", '
                                                                                                                                        . $userid . ', "'
                                                                                                                                            . date('Y-m-d H:i:s') . '", '
                                                                                                                                                . $userid . ')';
            
            $this->logger->write("Utilities : createproduct() : The SQL is " . $sql, 'r');
            $this->db->exec(array($sql));
            
            return true;
        } catch (Exception $e) {
            $this->logger->write("Utilities : createproduct() : The operation to create the product was not successful. The error message is " . $e->getMessage(), 'r');
            return false;
        }
        
    }
    
    /**
     * @name updateproduct
     * @desc update a product in eTW
     * @return bool
     * @param $product array
     *
     */
    function updateproduct($product, $userid){
        /**
         * 1. Pick the fields from the product array
         * 2. Convert the following fields
         * 2.1  measureUnit (getmeasureunit)
         * 2.2  currency (getcurrency)
         * 2.3  commodityCategoryId (getcommodity)
         * 2.4  haveExciseTax (getchoice)
         * 2.5  havePieceUnit (getchoice)
         * 3. Update the table tblproductdetails
         */
        
        try{
            $product['piecemeasureunit'] = (trim($product['piecemeasureunit']) == ''? 'NULL' : $product['piecemeasureunit']);
            $product['pieceunitprice'] = (trim($product['pieceunitprice']) == ''? 'NULL' : $product['pieceunitprice']);
            $product['packagescaledvalue'] = (trim($product['packagescaledvalue']) == ''? 'NULL' : $product['packagescaledvalue']);
            $product['piecescaledvalue'] = (trim($product['piecescaledvalue']) == ''? 'NULL' : $product['piecescaledvalue']);
            $product['excisedutylist'] = (trim($product['excisedutylist']) == ''? 'NULL' : $product['excisedutylist']);
            $product['uraquantity'] = (trim($product['uraquantity']) == ''? 'NULL' : $product['uraquantity']);
            $product['erpquantity'] = (trim($product['erpquantity']) == ''? 'NULL' : $product['erpquantity']);
            $product['purchaseprice'] = (trim($product['purchaseprice']) == ''? 'NULL' : $product['purchaseprice']);
            $product['haveotherunit'] = (trim($product['haveotherunit']) == ''? 'NULL' : $product['haveotherunit']);
            
            $product['isexempt'] = (trim($product['isexempt']) == ''? 'NULL' : $product['isexempt']);
            $product['iszerorated'] = (trim($product['iszerorated']) == ''? 'NULL' : $product['iszerorated']);
            $product['source'] = (trim($product['source']) == ''? 'NULL' : $product['source']);
            $product['exclusion'] = (trim($product['exclusion']) == ''? 'NULL' : $product['exclusion']);
            $product['statuscode'] = (trim($product['statuscode']) == ''? 'NULL' : $product['statuscode']);
            $product['taxrate'] = (trim($product['taxrate']) == ''? 'NULL' : $product['taxrate']);
            $product['serviceMark'] = (trim($product['serviceMark']) == ''? 'NULL' : $product['serviceMark']);
            
            $product['goodsTypeCode'] = (trim($product['goodsTypeCode']) == ''? 'NULL' : $product['goodsTypeCode']);
            $product['hsCode'] = (trim($product['hsCode']) == ''? 'NULL' : $product['hsCode']);
            $product['customsmeasureunit'] = (trim($product['customsmeasureunit']) == ''? 'NULL' : $product['customsmeasureunit']);
            $product['customsunitprice'] = (trim($product['customsunitprice']) == ''? 'NULL' : $product['customsunitprice']);
            $product['packagescaledvaluecustoms'] = (trim($product['packagescaledvaluecustoms']) == ''? 'NULL' : $product['packagescaledvaluecustoms']);
            $product['customsscaledvalue'] = (trim($product['customsscaledvalue']) == ''? 'NULL' : $product['customsscaledvalue']);
            $product['weight'] = (trim($product['weight']) == ''? 'NULL' : $product['weight']);
            
            $userid = empty($userid) || trim($userid) == ''? $this->userid : $userid;
            
            $sql = 'UPDATE tblproductdetails SET 
                                    uraquantity = ' . $product['uraquantity'] . ', 
                                    uraproductidentifier = "' . addslashes($product['uraproductidentifier']) . '", 
                                    erpid = "' . addslashes($product['erpid']) . '",
                                    erpcode = "' . addslashes($product['erpcode']) . '",
                                    name = "' . addslashes($product['name']) . '",
                                    measureunit = "' . addslashes($product['measureunit']) . '",
                                    unitprice = ' . $product['unitprice'] . ',
                                    currency = ' . $product['currency'] . ',
                                    commoditycategorycode = "' . addslashes($product['commoditycategorycode']) . '",
                                    hasexcisetax = ' . $product['hasexcisetax'] . ',
                                    description = "' . addslashes($product['description']) . '",
                                    stockprewarning = ' . $product['stockprewarning'] . ',
                                    piecemeasureunit = "' . $product['piecemeasureunit'] . '",
                                    havepieceunit = ' . $product['havepieceunit'] . ',
                                    pieceunitprice = ' . $product['pieceunitprice'] . ',
                                    packagescaledvalue = ' . $product['packagescaledvalue'] . ',
                                    piecescaledvalue = ' . $product['piecescaledvalue'] . ',
                                    excisedutylist = ' . $product['excisedutylist'] . ',
                                    purchaseprice = ' . $product['purchaseprice'] . ',
                                    isexempt = ' . $product['isexempt'] . ',
                                    serviceMark = ' . $product['serviceMark'] . ',
                                    iszerorated = ' . $product['iszerorated'] . ',
                                    source = ' . $product['source'] . ',
                                    exclusion = ' . $product['exclusion'] . ',
                                    statuscode = ' . $product['statuscode'] . ',

                                    taxrate = ' . $product['taxrate'] . ',
                                    erpquantity = ' . $product['erpquantity'] . ',
                                    haveotherunit = ' . $product['haveotherunit'] . ',
                                    goodsTypeCode = ' . $product['goodsTypeCode'] . ',
                                    hsCode = "' . $product['hsCode'] . '",
                                    customsmeasureunit = "' . $product['customsmeasureunit'] . '",
                                    customsunitprice = ' . $product['customsunitprice'] . ',
                                    packagescaledvaluecustoms = ' . $product['packagescaledvaluecustoms'] . ',
                                    customsscaledvalue = ' . $product['customsscaledvalue'] . ',
                                    weight = ' . $product['weight'] . ',
                                    modifieddt = "' .  date('Y-m-d H:i:s') . '", 
                                    modifiedby = ' . $userid  . ' 
                                    WHERE TRIM(code) = "' . addslashes($product['code']) . '"';
            
            $this->logger->write("Utilities : createproduct() : The SQL is " . $sql, 'r');
            $this->db->exec(array($sql));
            return true;
        } catch (Exception $e) {
            $this->logger->write("Utilities : updateproduct() : The operation to update the product was not successful. The error message is " . $e->getMessage(), 'r');
            return false;
        }
        
    }
    
    /**
     * @name mapcurrency
     * @desc return the standard code for a currency
     * @return string
     * @param $no string
     *
     */
    function mapcurrency($code){
        /**
         * 1. Cleanup the currency
         * 2. Search the currency table for the equivalent
         */
        $this->logger->write("Utilities : mapcurrency() : The raw currency is " . $code, 'r');
        $value = '';
        
        $code = strtoupper(trim($code));
        
        $code_check = new DB\SQL\Mapper($this->db, 'tblcurrencies');
        $code_check->load(array('UPPER(erpcode)=?', $code));
        $this->logger->write($this->db->log(TRUE), 'r');
        
        if($code_check->dry ()){
            $this->logger->write("Utilities : mapcurrency() : The currency does not exist or is not mapped", 'r');
        } else {
            $value = $code_check->code;
        }
        
        $this->logger->write("Utilities : mapcurrency() : The currency code is " . $value, 'r');
        return $value;
    }
    
    /**
     * @name getcurrency
     * @desc return the standard name for a currency
     * @return string
     * @param $no string
     *
     */
    function getcurrency($code){
        /**
         * 1. Cleanup the currency
         * 2. Search the currency table for the equivalent
         */
        $this->logger->write("Utilities : getcurrency() : The raw currency is " . $code, 'r');
        $value = '';
        
        $code = strtoupper(trim($code));
        
        $code_check = new DB\SQL\Mapper($this->db, 'tblcurrencies');
        $code_check->load(array('UPPER(erpcode)=?', $code));
        $this->logger->write($this->db->log(TRUE), 'r');
        
        if($code_check->dry ()){
            $this->logger->write("Utilities : getcurrency() : The currency does not exist or is not mapped", 'r');
        } else {
            $value = $code_check->name;
        }
        
        $this->logger->write("Utilities : getcurrency() : The currency code is " . $value, 'r');
        return $value;
    }
    
    /**
     * @name getcurrency
     * @desc return the standard name for a currency
     * @return string
     * @param $no string
     *
     */
    function decodeapprovestatus($code){
        /**
         * 1. Cleanup the status
         * 2. Search the approve status table for the equivalent
         */
        $this->logger->write("Utilities : decodeapprovestatus() : The raw status is " . $code, 'r');
        $value = '';
        
        $code = strtoupper(trim($code));
        
        $code_check = new DB\SQL\Mapper($this->db, 'tblcdnoteapprovestatuses');
        $code_check->load(array('UPPER(code)=?', $code));
        $this->logger->write($this->db->log(TRUE), 'r');
        
        if($code_check->dry ()){
            $this->logger->write("Utilities : decodeapprovestatus() : The status does not exist or is not mapped", 'r');
        } else {
            $value = $code_check->name;
        }
        
        $this->logger->write("Utilities : decodeapprovestatus() : The status name is " . $value, 'r');
        return $value;
    }
    
    /**
     * @name mapcommodity
     * @desc return the commodity code for a product
     * @return string
     * @param $no string
     *
     */
    function mapcommodity($code){
        /**
         * 1. Cleanup the commodity
         * 2. Search the commodity table for the equivalent
         */
        $this->logger->write("Utilities : mapcommodity() : The raw commodity is " . $code, 'r');
        $value = '';
        
        $code = strtoupper(trim($code));
        
        $code_check = new DB\SQL\Mapper($this->db, 'tblcommoditycategories');
        $code_check->load(array('UPPER(commodityname)=?', $code));
        $this->logger->write($this->db->log(TRUE), 'r');
        
        if($code_check->dry ()){
            $this->logger->write("Utilities : mapcommodity() : The commodity does not exist or is not mapped", 'r');
        } else {
            $value = $code_check->commoditycode;
        }
        
        $this->logger->write("Utilities : mapcommodity() : The commodity code is " . $value, 'r');
        return $value;
    }
    
    /**
     * @name mapcommodity_v2
     * @desc return the commodity code for a product
     * @return string
     * @param $no string
     *
     */
    function mapcommodity_v2($code){
        /**
         * 1. Cleanup the commodity
         * 2. Search the commodity table for the equivalent
         */
        $this->logger->write("Utilities : mapcommodity_v2() : The raw commodity is " . $code, 'r');
        $value = '';
        
        $code = strtoupper(trim($code));
        
        $code_check = new DB\SQL\Mapper($this->db, 'tblcommoditycategories');
        $code_check->load(array('UPPER(erpcode)=?', $code));
        $this->logger->write($this->db->log(TRUE), 'r');
        
        if($code_check->dry ()){
            $this->logger->write("Utilities : mapcommodity_v2() : The commodity does not exist or is not mapped", 'r');
        } else {
            $value = $code_check->commoditycode;
        }
        
        $this->logger->write("Utilities : mapcommodity_v2() : The commodity code is " . $value, 'r');
        return $value;
    }
    
    /**
     * @name mapcommodity_v3
     * @desc return the commodity code for a product
     * @return string
     * @param $no string
     *
     */
    function mapcommodity_v3($code){
        /**
         * 1. Cleanup the commodity
         * 2. Search the commodity table for the equivalent
         */
        $this->logger->write("Utilities : mapcommodity_v2() : The raw commodity is " . $code, 'r');
        $value = '';
        
        $code = strtoupper(trim($code));
        
        $code_check = new DB\SQL\Mapper($this->db, 'tblcommoditycategories');
        $code_check->load(array('UPPER(commodityname) LIKE ?', '%' . $code . '%'));
        $this->logger->write($this->db->log(TRUE), 'r');
        
        if($code_check->dry ()){
            $this->logger->write("Utilities : mapcommodity_v2() : The commodity does not exist or is not mapped", 'r');
        } else {
            $value = $code_check->commoditycode;
        }
        
        $this->logger->write("Utilities : mapcommodity_v2() : The commodity code is " . $value, 'r');
        return $value;
    }
      
    /**
     * @name createauditlog
     * @desc Log user activity
     * @return NULL
     * @param $activity string, $userid int 
     *
     */
    function createauditlog($userid, $activity){
        //sanitize the activity text
        $values = $userid . ", '" . addslashes($activity) . "', " . "NOW(), " . $userid . ", NOW(), " . $userid;
        
        
        $sql = 'INSERT INTO tblauditlogs (userid, description, inserteddt, insertedby, modifieddt, modifiedby)
                    VALUES (' . $values . ')';
                
        try {
            $this->db->exec(array($sql));
            ////$this->logger->write("Utilities : createauditlog() : Query was executed successfully", 'r');
        } catch (Exception $e) {
            $this->logger->write("Utilities : createauditlog() : Error " . $e->getMessage(), 'r');
        } 
	}
	
	/**
	 * @name createerpauditlog
	 * @desc Log ERP user activity
	 * @return NULL
	 * @param $userid int, $activity string, $windowsuser string, $ipaddress string, $macaddress string, $systemname string, $payload string, $voucherNumber string, $voucherRef string, $productCode string, $responseCode string, $responseMessage string
	 */
	function createerpauditlog($userid, $activity, $windowsuser, $ipaddress, $macaddress, $systemname, $payload, $voucherNumber=NULL, $voucherRef=NULL, $productCode=NULL, $responseCode=NULL, $responseMessage=NULL){
	    $userid = empty($userid) || trim($userid) == ''? $this->userid : $userid;
	    
	    //sanitize the activity text
	    $values = "'" . addslashes($activity) . "', '" . addslashes($windowsuser) . "', '" . addslashes($ipaddress) . "', '" . addslashes($macaddress) . "', '" . addslashes($systemname) . "', '" . addslashes($payload) . "', '" . addslashes($voucherNumber) . "', '" . addslashes($voucherRef) . "', '" . addslashes($productCode) . "', '" . addslashes($responseCode) . "', '" . addslashes($responseMessage) . "', NOW(), " . $userid . ", NOW(), " . $userid;
	    
	    $sql = 'INSERT INTO tblerpauditlogs (description, windowsuser, ipaddress, macaddress, systemname, payload, voucherNumber, voucherRef, productCode, responseCode, responseMessage, inserteddt, insertedby, modifieddt, modifiedby)
                    VALUES (' . $values . ')';
	    
	    try {
	        $this->db->exec(array($sql));
	        ////$this->logger->write("Utilities : createerpauditlog() : Query was executed successfully", 'r');
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : createerpauditlog() : Error " . $e->getMessage(), 'r');
	    }
	}
	
	/**
	 * Retrieve settings from the table tblsettings;
	 *
	 * @return array
	 * @param $userid int
	 * @param $groupid int
	 *
	 */
	function getsettings($userid=NULL, $groupid=NULL){
	    //$this->logger->write("Utilities : getsettings() : Processing settings", 'r');
	    $sql = '';
	    
	    $data = array ();
	    
	    if (is_null($groupid)) {
	        $sql = 'SELECT s.id "ID",
                    s.code "Code",
                    s.value "Value",
                    s.description "Description",
                    s.inserteddt "Inserted Date",
                    s.modifieddt "Modified Date",
                    NULL "Actions"
                FROM tblsettings s';
	    }else {
	        $sql = 'SELECT s.id "ID",
                    s.code "Code",
                    s.value "Value",
                    s.description "Description",
                    s.inserteddt "Inserted Date",
                    s.modifieddt "Modified Date",
                    NULL "Actions"
                FROM tblsettings s
                WHERE s. groupid in (' . $groupid . ')';
	    }

	    try {
	        $dtls = $this->db->exec($sql);
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : getsettings() : Error " . $e->getMessage(), 'r');
	        return null;
	    }
	        
	    foreach ( $dtls as $obj ) {
	        $data [] = $obj;
	    }
	    
	    return $data;
	}
	
	
	/**
	 * @name fetchcurrencyrates
	 * @desc Fetch currency rates from EFRIS
	 * @return JSON-encoded object
	 * @param $userid int
	 *
	 */
	function fetchcurrencyrates($userid){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : fetchcurrencyrates() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : fetchcurrencyrates() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : fetchcurrencyrates() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : fetchcurrencyrates() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    try {
	        $this->logger->write("Utilities : fetchcurrencyrates() : Fetching currency rates started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T126';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        
	        $data = array(
	            'data' => array(
	                'content' => '',
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : uploadproduct() : The response content is: " . $content, 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : fetchcurrencyrates() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	        } else {
	            $this->logger->write("Utilities : fetchcurrencyrates() : The API call was not successful. The return code is: " . $returninfo['returnCode'], 'r');
	        }
	        
	        if ($dataDesc['zipCode'] == '1') {
	            $this->logger->write("Utilities : fetchcurrencyrates() : The response is zipped", 'r');
	            return gzdecode(base64_decode($content));
	        } else {
	            $this->logger->write("Utilities : fetchcurrencyrates() : The response is NOT zipped", 'r');
	            return base64_decode($content);
	        }
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : fetchcurrencyrates() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}
	
	
	/**
	 * @name fetchbranches
	 * @desc Fetch branches from EFRIS
	 * @return JSON-encoded object
	 * @param $userid int
	 *
	 */
	function fetchbranches($userid){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : fetchbranches() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : fetchbranches() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : fetchbranches() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : fetchbranches() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    try {
	        $this->logger->write("Utilities : fetchbranches() : Fetching branches started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T138';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        
	        $data = array(
	            'data' => array(
	                'content' => '',
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : uploadproduct() : The response content is: " . $content, 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : fetchbranches() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	        } else {
	            $this->logger->write("Utilities : fetchbranches() : The API call was not successful. The return code is: " . $returninfo['returnCode'], 'r');
	        }
	        
	        if ($dataDesc['zipCode'] == '1') {
	            $this->logger->write("Utilities : fetchbranches() : The response is zipped", 'r');
	            return gzdecode(base64_decode($content));
	        } else {
	            $this->logger->write("Utilities : fetchbranches() : The response is NOT zipped", 'r');
	            return base64_decode($content);
	        }
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : fetchbranches() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}
	
	/**
	 * @name uploadproduct
	 * @desc Upload a product to EFRIS
	 * @return JSON-encoded object
	 * @param $userid int, $product array, $altunits array
	 *
	 */
	function uploadproduct($userid, $product, $altunits){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : uploadproduct() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : uploadproduct() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : uploadproduct() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : uploadproduct() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    $this->logger->write("Utilities : uploadproduct() : The user id is " . $userid, 'r');
	    $this->logger->write("Utilities : uploadproduct() : The product code is " . $product['code'], 'r');
	    
	    try {
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T130';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        
            //Check if the product is already uploaded
	        $pdct = new products($this->db);
	        $pdct->getByErpCode(trim($product['code']));
	        
	        if ($pdct->dry()) {
	            $this->logger->write("Utilities : uploadproduct() : The product " . $product['code'] . " is new. Uploading started", 'r');
	            $operationType = '101'; /*add goods(default)*/
	        } else {
	            $this->logger->write("Utilities : uploadproduct() : The product " . $product['code'] . " is already uploaded. Updating started", 'r');
	            $operationType = '102'; /*modify product*/
	        }
	        
	        $otherunits = array();
	        
	        try{	            
	            foreach ($altunits as $obj) {
	                $otherunits[] = array(
	                    'otherUnit' => empty($obj['otherunit'])? '' : $obj['otherunit'],
	                    'otherPrice' => empty($obj['otherPrice'])? '' : $obj['otherPrice'],
	                    'otherScaled' => empty($obj['otherscaled'])? '' : $obj['otherscaled'],
	                    'packageScaled' => empty($obj['packagescaled'])? '' : $obj['packagescaled']
	                );
	            }
	        } catch (Exception $e) {
	            $this->logger->write("Utilities Controller : uploadproduct() : The operation to process the alternative units was not successful. The error messages is " . $e->getMessage(), 'r');
	        }
	        
	        $products = array(
	            array(
	                'operationType' => $operationType,
	                'goodsName' => $product['name'],
	                'goodsCode' => $product['code'],
	                'measureUnit' => $product['measureunit'],
	                'unitPrice' => empty($product['unitprice'])? '1' : $product['unitprice'],
	                'currency' => $product['currency'],
	                'commodityCategoryId' => $product['commoditycategorycode'],
	                'haveExciseTax' => $product['hasexcisetax'],
	                'description' => empty($product['description'])? '' : $product['description'],
	                'stockPrewarning' => $product['stockprewarning'],
	                'pieceMeasureUnit' => $product['piecemeasureunit'],
	                'havePieceUnit' => $product['havepieceunit'],
	                'pieceUnitPrice' => empty($product['pieceunitprice'])? '' : $product['pieceunitprice'],
	                'packageScaledValue' => empty($product['packagescaledvalue'])? '' : round($product['packagescaledvalue']),
	                'pieceScaledValue' => empty($product['piecescaledvalue'])? '' : round($product['piecescaledvalue']),
	                'exciseDutyCode' => $product['excisedutylist'],
	                'haveOtherUnit' => empty($product['haveotherunit'])? '' : $product['haveotherunit'],
	                'goodsTypeCode' => empty($product['goodsTypeCode'])? '101' : $product['goodsTypeCode'],
	                /**
	                 * Date: 2025-12-04
	                 * Author: Francis Lubanga <francis.lubanga@gmail.com>
	                 * Desc: Temporarily suspend the sending of the commodityGoodsExtendEntity to EFRIS
	                 */
	                /*'commodityGoodsExtendEntity' => array(
	                    'customsMeasureUnit' => empty($product['customsmeasureunit'])? '' : $product['customsmeasureunit'],
	                    'customsUnitPrice' => empty($product['customsunitprice'])? '' : $product['customsunitprice'],
	                    'packageScaledValueCustoms' => empty($product['packagescaledvaluecustoms'])? '' : round($product['packagescaledvaluecustoms']),
	                    'customsScaledValue' => empty($product['customsscaledvalue'])? '' : round($product['customsscaledvalue'])
	                ),*/
	                'goodsOtherUnits' => $otherunits
	            )
	        );
	        
	        $products = json_encode($products); //JSON-ifiy
	        $products = base64_encode($products); //base64 encode
	        $this->logger->write("Utilities : uploadproduct() : The encoded product is " . $products, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $products,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        
	        
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : uploadproduct() : The response content is: " . $content, 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : uploadproduct() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	        } else {
	            $this->logger->write("Utilities : uploadproduct() : The API call was not successful. The return code is: " . $returninfo['returnCode'], 'r');
	        }
	        
	        if ($dataDesc['zipCode'] == '1') {
	            $this->logger->write("Utilities : uploadproduct() : The response is zipped", 'r');
	            return gzdecode(base64_decode($content));
	        } else {
	            $this->logger->write("Utilities : uploadproduct() : The response is NOT zipped", 'r');
	            return base64_decode($content);
	        }
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : uploadproduct() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}
	
	/**
	 * @name fetchproduct
	 * @desc Fetch a product from EFRIS
	 * @return JSON-encoded object
	 * @param $userid int, $product array, $urabranchid string
	 *
	 */
	function fetchproduct($userid, $product, $urabranchid){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : fetchproduct() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : fetchproduct() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : fetchproduct() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : fetchproduct() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    $this->logger->write("Utilities : fetchproduct() : The user id is " . $userid, 'r');
	    
	    try {
	        $this->logger->write("Utilities : fetchproduct() : Fetching product started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T127';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        
	        //$product = new products($this->db);
	        //$product->getByID($id);
	        
	        $product = array(
	            'goodsName' => '',
	            'goodsCode' => $product['code'],
	            'commodityCategoryName' => '',
	            'pageNo' => '1',
	            'pageSize' => '10',
	            'branchId' => $urabranchid
	        );
	        
	        $product = json_encode($product); //JSON-ifiy
	        $product = base64_encode($product); //base64 encode
	        $this->logger->write("Utilities : fetchproduct() : The encoded product is " . $product, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $product,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : fetchproduct() : The response content is: " . $content, 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : fetchproduct() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	            if ($dataDesc['zipCode'] == '1') {
	                $this->logger->write("Utilities : fetchproduct() : The response is zipped", 'r');
	                $content = gzdecode(base64_decode($content));
	            } else {
	                $this->logger->write("Utilities : fetchproduct() : The response is NOT zipped", 'r');
	                $content = base64_decode($content);
	            }
	        } else {
	            $this->logger->write("Utilities : fetchproduct() : The API call was not successful. The return code is: " . $returninfo['returnCode'] . ' - ' . $returninfo['returnMessage'], 'r');
	            $content = json_encode(array('returnCode' => $returninfo['returnCode'], 'returnMessage' => $returninfo['returnMessage']));
	        }
	        
	        return $content;
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : fetchproduct() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}
	
	
	/**
	 * @name stockin
	 * @desc Upload stock to EFRIS
	 * @return JSON-encoded object
	 * @param $userid int, $branchuraid string, $productcode string, $batchno string, $qty int, $suppliertin string, $suppliername string, $stockintype string, $productiondate DateTime, $unitprice float
	 *
	 */
	function stockin($userid, $branchuraid, $productcode, $batchno, $qty, $suppliertin, $suppliername, $stockintype, $productiondate, $unitprice){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : stockin() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : stockin() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : stockin() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : stockin() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    $this->logger->write("Utilities : stockin() : The user id is " . $userid, 'r');
	    $this->logger->write("Utilities : stockin() : The product code is " . $productcode, 'r');
	    
	    try {
	        $this->logger->write("Utilities : stockin() : Uploading stock started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T131';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        
	        $product = new products($this->db);
	        //$product->getByID($id);
	        $product->getByErpCode(trim($productcode));
	        
	        if ($product->dry()) {
	            $this->logger->write("Utilities : stockin() : The product " . $productcode . " does not exist", 'r');
	            return json_encode(array('returnCode' => '658', 'returnMessage' => 'goodsCode does not exist!'));
	        } 
	        
	        $stock = array(
	            'goodsStockIn' => array(
	                'operationType' => trim($this->appsettings['STOCKINOPERATIONTYPE']),
	                'supplierTin' => $suppliertin,
	                'supplierName' => $suppliername == 'N/A'? '' : $suppliername,
	                'adjustType' => '',
	                'remarks' => '',
	                'stockInDate' => date('Y-m-d'),
	                'stockInType' => $stockintype,
	                'productionBatchNo' => empty($batchno)? '' : $batchno,
	                'productionDate' => empty($productiondate)? '' : $productiondate,
	                'branchId' => empty($branchuraid)? $devicedetails->branchId : $branchuraid,
	                'invoiceNo' => '',
	                'isCheckBatchNo' => '0'
	            ),
	            'goodsStockInItem' => array(
	                array(
	                    'commodityGoodsId' => empty($product->uraproductidentifier)? '' : $product->uraproductidentifier,
	                    'quantity' => strval($qty),
	                    'unitPrice' => $unitprice,
	                    'goodsCode' => empty($product->code)? '' : $product->code,
	                    'measureUnit' => empty($product->measureunit)? '' : $product->measureunit
	                )
	            )
	        );
	        
	        $stock = json_encode($stock); //JSON-ifiy
	        $stock = base64_encode($stock); //base64 encode
	        $this->logger->write("Utilities : stockin() : The encoded stock is " . $stock, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $stock,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : stockin() : The response content is: " . $content, 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : stockin() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');	  
	            
	            if ($dataDesc['zipCode'] == '1') {
	                $this->logger->write("Utilities : stockin() : The response is zipped", 'r');
	                $content = gzdecode(base64_decode($content));
	            } else {
	                $this->logger->write("Utilities : stockin() : The response is NOT zipped", 'r');
	                $content = base64_decode($content);
	            }
	        } else {
	            $this->logger->write("Utilities : stockin() : The API call was not successful. The return code is: " . $returninfo['returnCode'] . ' - ' . $returninfo['returnMessage'], 'r');
	            
	            if (trim($content) == '' || empty($content)) {
	                $content = json_encode(array('returnCode' => $returninfo['returnCode'], 'returnMessage' => $returninfo['returnMessage']));
	            } else {
	                /**
	                 * Modification Date: 2022-06-13
	                 * Modified By: Francis Lubanga
	                 * Description: Resolving issue of sending the generic PARTIAL ERROR message
	                 * */
	                
	                if ($dataDesc['zipCode'] == '1') {
	                    $this->logger->write("Utilities : stockin() : The response is zipped", 'r');
	                    $content = gzdecode(base64_decode($content));
	                } else {
	                    $this->logger->write("Utilities : stockin() : The response is NOT zipped", 'r');
	                    $content = base64_decode($content);
	                }
	            }
	        }

	        return $content;
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : stockin() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}
	
	/**
	 * @name stockquery
	 * @desc Query stock from EFRIS
	 * @return JSON-encoded object
	 * @param $userid int, $branchuraid string, $productcode string
	 *
	 */
	function stockquery($userid, $branchuraid, $productcode){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : stockquery() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : stockquery() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : stockquery() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : stockquery() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    $this->logger->write("Utilities : stockquery() : The user id is " . $userid, 'r');
	    $this->logger->write("Utilities : stockquery() : The product code is " . $productcode, 'r');
	    
	    try {
	        $this->logger->write("Utilities : stockquery() : Querying stock started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T128';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        
	        $product = new products($this->db);
	        //$product->getByID($id);
	        $product->getByErpCode(trim($productcode));
	        
	        $product = array(
	            'id' => $product->uraproductidentifier,
	            'branchId' => empty($branchuraid)? $devicedetails->branchId : $branchuraid,
	            
	        );
	        
	        $product = json_encode($product); //JSON-ifiy
	        $product = base64_encode($product); //base64 encode
	        $this->logger->write("Utilities : stockquery() : The encoded stock is " . $product, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $product,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : stockquery() : The response content is: " . $content, 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : stockquery() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	            if ($dataDesc['zipCode'] == '1') {
	                $this->logger->write("Utilities : stockquery() : The response is zipped", 'r');
	                $content = gzdecode(base64_decode($content));
	            } else {
	                $this->logger->write("Utilities : stockquery() : The response is NOT zipped", 'r');
	                $content = base64_decode($content);
	            }
	        } else {
	            $this->logger->write("Utilities : stockquery() : The API call was not successful. The return code is: " . $returninfo['returnCode'] . ' - ' . $returninfo['returnMessage'], 'r');
	            $content = json_encode(array('returnCode' => $returninfo['returnCode'], 'returnMessage' => $returninfo['returnMessage']));
	        }
	        
	        return $content;
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : stockquery() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}
	
	/**
	 * @name batchstockin
	 * @desc Upload stock in a batch to EFRIS
	 * @return JSON-encoded object
	 * @param $userid int, $branchuraid string, $product array, $batchno string, $suppliertin string, $suppliername string, $stockintype string, $productiondate DateTime
	 *
	 */
	function batchstockin($userid, $branchuraid, $products, $batchno, $suppliertin, $suppliername, $stockintype, $productiondate){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : batchstockin() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : batchstockin() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : batchstockin() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : batchstockin() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    $this->logger->write("Utilities : batchstockin() : The user id is " . $userid, 'r');
	    
	    try {
	        $this->logger->write("Utilities : batchstockin() : Uploading stock started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T131';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        
	        $product = new products($this->db);
	        //$product->getByID($id);
	        //$product->getByErpCode(trim($productcode));
	        
	        $t_stock = array();
	        
	        foreach ($products as $obj) {
	            $product->getByErpCode(trim($obj['productCode']));
	            
	            $t_stock[] = array(
	                'commodityGoodsId' => empty($product->uraproductidentifier)? '' : $product->uraproductidentifier,
	                'quantity' => empty($obj['quantity'])? '' : $obj['quantity'],
	                'unitPrice' => empty($obj['unitPrice'])? '' : $obj['unitPrice'],
	                'goodsCode' => empty($product->code)? '' : $product->code,
	                'measureUnit' => empty($product->measureunit)? '' : $product->measureunit
	            );
	            
	        }
	        
	        $stock = array(
	            'goodsStockIn' => array(
	                'operationType' => trim($this->appsettings['STOCKINOPERATIONTYPE']),
	                'supplierTin' => $suppliertin,
	                'supplierName' => $suppliername == 'N/A'? '' : $suppliername,
	                'adjustType' => '',
	                'remarks' => '',
	                'stockInDate' => date('Y-m-d'),
	                'stockInType' => $stockintype,
	                'productionBatchNo' => empty($batchno)? '' : $batchno,
	                'productionDate' => empty($productiondate)? '' : date('Y-m-d', strtotime($productiondate)),
	                'branchId' => empty($branchuraid)? $devicedetails->branchId : $branchuraid,
	                'invoiceNo' => '',
	                'isCheckBatchNo' => '0'
	            ),
	            'goodsStockInItem' => $t_stock
	        );
	        
	        $stock = json_encode($stock); //JSON-ifiy
	        $stock = base64_encode($stock); //base64 encode
	        $this->logger->write("Utilities : batchstockin() : The encoded stock is " . $stock, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $stock,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : batchstockin() : The response content is: " . $content, 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : batchstockin() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	            if ($dataDesc['zipCode'] == '1') {
	                $this->logger->write("Utilities : batchstockin() : The response is zipped", 'r');
	                $content = gzdecode(base64_decode($content));
	            } else {
	                $this->logger->write("Utilities : batchstockin() : The response is NOT zipped", 'r');
	                $content = base64_decode($content);
	            }
	        } else {
	            $this->logger->write("Utilities : batchstockin() : The API call was not successful. The return code is: " . $returninfo['returnCode'] . ' - ' . $returninfo['returnMessage'], 'r');
	            
	            if (trim($content) == '' || empty($content)) {
	                $content = json_encode(array('returnCode' => $returninfo['returnCode'], 'returnMessage' => $returninfo['returnMessage']));
	            } else {
	                /**
	                 * Modification Date: 2022-06-13
	                 * Modified By: Francis Lubanga
	                 * Description: Resolving issue of sending the generic PARTIAL ERROR message
	                 * */
	                
	                if ($dataDesc['zipCode'] == '1') {
	                    $this->logger->write("Utilities : batchstockin() : The response is zipped", 'r');
	                    $content = gzdecode(base64_decode($content));
	                } else {
	                    $this->logger->write("Utilities : batchstockin() : The response is NOT zipped", 'r');
	                    $content = base64_decode($content);
	                }
	            }
	        }
	        
	        return $content;
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : stockin() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}
	
	
	/**
	 * @name stockout
	 * @desc Reduce stock from EFRIS
	 * @return JSON-encoded object
	 * @param $userid int, $branchuraid string, $productcode string, $batchno string, $qty int, $adjustmenttype string, $remarks string
	 *
	 */
	function stockout($userid, $branchuraid, $productcode, $batchno, $qty, $adjustmenttype, $remarks){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : stockout() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : stockout() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : stockout() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : stockout() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    $this->logger->write("Utilities : stockout() : The user id is " . $userid, 'r');
	    $this->logger->write("Utilities : stockout() : The product id is " . $productcode, 'r');
	    
	    try {
	        $this->logger->write("Utilities : stockout() : Stocking out started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T131';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        
	        $product = new products($this->db);
	        //$product->getByID($id);
	        $product->getByErpCode(trim($productcode));
	        
	        if ($product->dry()) {
	            $this->logger->write("Utilities : stockout() : The product " . $productcode . " does not exist", 'r');
	            return json_encode(array('returnCode' => '658', 'returnMessage' => 'goodsCode does not exist!'));
	        } 
	        
	        $stock = array(
	            'goodsStockIn' => array(
	                'operationType' => trim($this->appsettings['STOCKOUTOPERATIONTYPE']),
	                'supplierTin' => '',
	                'supplierName' => '',
	                'adjustType' => $adjustmenttype,
	                'remarks' => $remarks,
	                'stockInDate' => '',
	                /**
	                 *Date: 2020-11-18
	                 *Authour: Francis Lubanga
	                 *Description: Fixing error code 2177 
	                 * 
	                 */
	                //'stockInType' => empty($product->stockintype)? '' : $product->stockintype,
	                'stockInType' => '',
	                'productionBatchNo' => '',
	                'productionDate' => '',
	                'branchId' => empty($branchuraid)? $devicedetails->branchId : $branchuraid,
	                'invoiceNo' => '',
	                'isCheckBatchNo' => '0'
	            ),
	            'goodsStockInItem' => array(
	                array(
	                    'commodityGoodsId' => empty($product->uraproductidentifier)? '' : $product->uraproductidentifier,
	                    'quantity' => strval($qty),
	                    'unitPrice' => (empty($product->purchaseprice) || $product->purchaseprice == '0.00')? '1' : $product->purchaseprice,
	                    'goodsCode' => empty($product->code)? '' : $product->code,
	                    'measureUnit' => empty($product->measureunit)? '' : $product->measureunit
	                )
	            )
	        );
	        
	        $stock = json_encode($stock); //JSON-ifiy
	        $stock = base64_encode($stock); //base64 encode
	        $this->logger->write("Utilities : stockout() : The encoded stock is " . $stock, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $stock,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : stockout() : The response content is: " . $content, 'r');
	        //$this->logger->write("Utilities : stockout() : The return info is: " . $returninfo['returnCode'], 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : stockout() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');

	            if ($dataDesc['zipCode'] == '1') {
	                $this->logger->write("Utilities : stockout() : The response is zipped", 'r');
	                $content = gzdecode(base64_decode($content));
	            } else {
	                $this->logger->write("Utilities : stockout() : The response is NOT zipped", 'r');
	                $content = base64_decode($content);
	            }
	        } else {
	            $this->logger->write("Utilities : stockout() : The API call was not successful. The return code is: " . $returninfo['returnCode'] . ' - ' . $returninfo['returnMessage'], 'r');
	            
	            if (trim($content) == '' || empty($content)) {
	                $content = json_encode(array('returnCode' => $returninfo['returnCode'], 'returnMessage' => $returninfo['returnMessage']));
	            } else {
	                /**
	                 * Modification Date: 2022-06-13
	                 * Modified By: Francis Lubanga
	                 * Description: Resolving issue of sending the generic PARTIAL ERROR message
	                 * */
	                
	                if ($dataDesc['zipCode'] == '1') {
	                    $this->logger->write("Utilities : stockout() : The response is zipped", 'r');
	                    $content = gzdecode(base64_decode($content));
	                } else {
	                    $this->logger->write("Utilities : stockout() : The response is NOT zipped", 'r');
	                    $content = base64_decode($content);
	                }
	            }
	            
	        }
	        
	        
	        
	        return $content;
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : stockout() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}
	
	/**
	 * @name batchstockout
	 * @desc Reduce stock from EFRIS
	 * @return JSON-encoded object
	 * @param $userid int, $branchuraid string, $products string, $adjustmenttype string, $remarks string
	 *
	 */
	function batchstockout($userid, $branchuraid, $products, $adjustmenttype, $remarks){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : batchstockout() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : batchstockout() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : batchstockout() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : batchstockout() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    $this->logger->write("Utilities : batchstockout() : The user id is " . $userid, 'r');
	    
	    try {
	        $this->logger->write("Utilities : batchstockout() : Stocking out started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T131';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        
	        $product = new products($this->db);
	        
	        $t_stock = array();
	        
	        foreach ($products as $obj) {
	            $product->getByErpCode(trim($obj['productCode']));
	            
	            $t_stock[] = array(
	                'commodityGoodsId' => empty($product->uraproductidentifier)? '' : $product->uraproductidentifier,
	                'quantity' => empty($obj['quantity'])? '' : $obj['quantity'],
	                'unitPrice' => empty($obj['unitPrice'])? '' : $obj['unitPrice'],
	                'goodsCode' => empty($product->code)? '' : $product->code,
	                'measureUnit' => empty($product->measureunit)? '' : $product->measureunit
	            );
	            
	        }
	        
	        $stock = array(
	            'goodsStockIn' => array(
	                'operationType' => trim($this->appsettings['STOCKOUTOPERATIONTYPE']),
	                'supplierTin' => '',
	                'supplierName' => '',
	                'adjustType' => $adjustmenttype,
	                'remarks' => $remarks,
	                'stockInDate' => '',
	                /**
	                 *Date: 2020-11-18
	                 *Authour: Francis Lubanga
	                 *Description: Fixing error code 2177
	                 *
	                 */
	                //'stockInType' => empty($product->stockintype)? '' : $product->stockintype,
	                'stockInType' => '',
	                'productionBatchNo' => '',
	                'productionDate' => '',
	                'branchId' => empty($branchuraid)? $devicedetails->branchId : $branchuraid,
	                'invoiceNo' => '',
	                'isCheckBatchNo' => '0'
	            ),
	            'goodsStockInItem' => $t_stock
	        );
	        
	        $stock = json_encode($stock); //JSON-ifiy
	        $stock = base64_encode($stock); //base64 encode
	        $this->logger->write("Utilities : batchstockout() : The encoded stock is " . $stock, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $stock,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : batchstockout() : The response content is: " . $content, 'r');
	        //$this->logger->write("Utilities : batchstockout() : The return info is: " . $returninfo['returnCode'], 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : batchstockout() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	            if ($dataDesc['zipCode'] == '1') {
	                $this->logger->write("Utilities : batchstockout() : The response is zipped", 'r');
	                $content = gzdecode(base64_decode($content));
	            } else {
	                $this->logger->write("Utilities : batchstockout() : The response is NOT zipped", 'r');
	                $content = base64_decode($content);
	            }
	        } else {
	            $this->logger->write("Utilities : batchstockout() : The API call was not successful. The return code is: " . $returninfo['returnCode'] . ' - ' . $returninfo['returnMessage'], 'r');
	            
	            if (trim($content) == '' || empty($content)) {
	                $content = json_encode(array('returnCode' => $returninfo['returnCode'], 'returnMessage' => $returninfo['returnMessage']));
	            } else {
	                /**
	                 * Modification Date: 2022-06-14
	                 * Modified By: Francis Lubanga
	                 * Description: Resolving issue of sending the generic PARTIAL ERROR message
	                 * */
	                
	                if ($dataDesc['zipCode'] == '1') {
	                    $this->logger->write("Utilities : batchstockout() : The response is zipped", 'r');
	                    $content = gzdecode(base64_decode($content));
	                } else {
	                    $this->logger->write("Utilities : batchstockout() : The response is NOT zipped", 'r');
	                    $content = base64_decode($content);
	                }
	            }
	        }
	        
	        return $content;
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : batchstockout() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}

	/**
	 * @name transferproductstock
	 * @desc Transfer stock of a product from EFRIS
	 * @return JSON-encoded object
	 * @param $userid int, $product array, $sourcebranch string, $destinationbranch string, $qty float, $remarks string
	 *
	 */
	function transferproductstock($userid, $product, $sourcebranch, $destinationbranch, $qty=0, $remarks=''){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : transferproductstock() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : transferproductstock() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : transferproductstock() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : transferproductstock() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    $this->logger->write("Utilities : transferproductstock() : The user id is " . $userid, 'r');
	    
	    if (trim($sourcebranch) == '' || empty($sourcebranch)) {
	        return json_encode(array('returnCode' => '999', 'returnMessage' => 'Unknown Error'));
	        $this->logger->write("Utilities : transferproductstock() : The source branch is empty.", 'r');
	    }
	    
	    if (trim($destinationbranch) == '' || empty($destinationbranch)) {
	        return json_encode(array('returnCode' => '999', 'returnMessage' => 'Unknown Error'));
	        $this->logger->write("Utilities : transferproductstock() : The destination branch is empty.", 'r');
	    }
	    
	    try {
	        $this->logger->write("Utilities : transferproductstock() : Transfer of product stock started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T139';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        
	        $user = new users($this->db);
	        $user->getByID($userid);
	        $branch = new branches($this->db);
	        $branch->getByID($user->branch);
	        	        
	        $productId = $product['uraproductidentifier'];
	        $productCode = $product['code'];
	        
	        $transferdetails[] = array(
	            'commodityGoodsId' => $productId,
	            'goodsCode' => $productCode,
	            'measureUnit' => $product['measureunit'],
	            'quantity' => strval($qty),
	            'remarks' => $remarks
	        );
	        
	        $product = array(
	            'goodsStockTransfer' => array(
	                'sourceBranchId' => $sourcebranch,
	                'destinationBranchId' => $destinationbranch,
	                'transferTypeCode' => '101',
	                'remarks' => '',
	            ),
	            'goodsStockTransferItem' => $transferdetails
	        );
	        
	        $product = json_encode($product); //JSON-ifiy
	        $product = base64_encode($product); //base64 encode
	        $this->logger->write("Utilities : transferproductstock() : The encoded product is " . $product, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $product,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : transferproductstock() : The response content is: " . $content, 'r');
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : transferproductstock() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	            if ($dataDesc['zipCode'] == '1') {
	                $this->logger->write("Utilities : transferproductstock() : The response is zipped", 'r');
	                $content = gzdecode(base64_decode($content));
	            } else {
	                $this->logger->write("Utilities : transferproductstock() : The response is NOT zipped", 'r');
	                $content = base64_decode($content);
	            }
	            
	            //self::logstocktransfer($userid, $productCode, $qty, NULL, NULL, NULL, NULL, $remarks, $sourcebranch, $destinationbranch, '101', $productId);
	        } else {
	            $this->logger->write("Utilities : transferproductstock() : The API call was not successful. The return code is: " . $returninfo['returnCode'] . ' - ' . $returninfo['returnMessage'], 'r');
	            
	            if (trim($content) == '' || empty($content)) {
	                $content = json_encode(array('returnCode' => $returninfo['returnCode'], 'returnMessage' => $returninfo['returnMessage']));
	            } else {
	                /**
	                 * Modification Date: 2022-06-14
	                 * Modified By: Francis Lubanga
	                 * Description: Resolving issue of sending the generic PARTIAL ERROR message
	                 * */
	                
	                if ($dataDesc['zipCode'] == '1') {
	                    $this->logger->write("Utilities : transferproductstock() : The response is zipped", 'r');
	                    $content = gzdecode(base64_decode($content));
	                } else {
	                    $this->logger->write("Utilities : transferproductstock() : The response is NOT zipped", 'r');
	                    $content = base64_decode($content);
	                }
	            }
	        }
	        
	        return $content;
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : transferproductstock() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}
	
	/**
	 * @name uploadinvoice
	 * @desc Upload an invoice to EFRIS
	 * @return JSON-encoded object
	 * @param $userid int, $branchuraid string, $buyer array, $invoicedetails array, $goods array, $payments array, $taxes array
	 *
	 */
	function uploadinvoice($userid, $branchuraid, $buyer, $invoicedetails, $goods, $payments, $taxes){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : uploadinvoice() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : uploadinvoice() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : uploadinvoice() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : uploadinvoice() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    $this->logger->write("Utilities : uploadinvoice() : The user id is " . $userid, 'r');	    
	    
	    try {
	        $this->logger->write("Utilities : uploadinvoice() : Uploading invoice started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T109';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        
	        //$invoice = $invoicedetails;
	        
	        $org = new organisations($this->db);
	        $org->getBySeller($this->appsettings['SELLER_RECORD_ID']);
	        
	        $t_goods = array();
	        $t_taxes = array();
	        $t_payments = array();
	        $t_summary = array();
	        $t_airlinegoods = array();
	        
	        $netamount = 0;
	        $taxamount = 0;
	        $grossamount = 0;
	        $itemcount = 0;	
	        
	        try{
	            //$temp = $goods;
	            $this->logger->write("Utilities : uploadinvoice() : The GOOD is " . $goods[0]['item'], 'r');
	            
	            $i = 0;
	            foreach ($goods as $obj) {
	                $this->logger->write("Utilities : uploadinvoice() : The unit price is " . round($obj['unitprice'], 8), 'r');
	                $this->logger->write("Utilities : uploadinvoice() : The total is " . floor($obj['total']*100)/100, 'r');
	                $this->logger->write("Utilities : uploadinvoice() : The tax is " . floor($obj['tax']*100)/100, 'r');
	                
	                if ($obj['deemedflag'] == '1') {
	                    $obj['item'] = $obj['item'] . " (Deemed)";
	                    
	                    //Truncate
	                    //$obj['unitprice'] = round($obj['unitprice'], 8);
	                    $obj['total'] = floor($obj['total']*100)/100;
	                    $obj['tax'] = floor($obj['tax']*100)/100;
	                    $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? NULL : floor($obj['discounttotal']*100)/100;
	                    
	                    //Ensure 2 decimal places
	                    /*$obj['unitprice'] = $this->truncatenumber($obj['unitprice'], 8);
	                    $obj['total'] = $this->truncatenumber($obj['total'], 2);
	                    $obj['tax'] = $this->truncatenumber($obj['tax'], 2);
	                    $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? NULL : $this->truncatenumber($obj['discounttotal'], 2);*/
	                    
	                } else {
	                    //Round off
	                    //$obj['unitprice'] = round($obj['unitprice'], 8);
	                    $obj['total'] = round($obj['total'], 2);
	                    $obj['tax'] = round($obj['tax'], 2);
	                    $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? NULL : round($obj['discounttotal'], 2);
	                }
	                
	                //Ensure the right decimal places
	                $obj['qty'] = $this->truncatenumber($obj['qty'], 8);
	                $obj['unitprice'] = $this->truncatenumber($obj['unitprice'], 8);
	                $obj['total'] = $this->truncatenumber($obj['total'], 2);
	                $obj['tax'] = $this->truncatenumber($obj['tax'], 2);
	                $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? NULL : $this->truncatenumber($obj['discounttotal'], 2);
	                
	                $t_goods[] = array(
	                    'item' => empty($obj['item'])? '' : $obj['item'],
	                    'itemCode' => empty($obj['itemcode'])? '' : $obj['itemcode'],
	                    'qty' => empty($obj['qty'])? '' : strval($obj['qty']),
	                    'unitOfMeasure' => empty($obj['unitofmeasure'])? '' : $obj['unitofmeasure'],
	                    'unitPrice' => empty($obj['unitprice'])? '' : strval($obj['unitprice']),
	                    'total' => empty($obj['total'])? '' : strval($obj['total']),
	                    'taxRate' => trim($obj['taxcategory']) == 'Exempt'? '-' : (empty($obj['taxrate'])? '' : number_format($obj['taxrate'], 2, '.', '')),
	                    'tax' => empty($obj['tax'])? '' : strval($obj['tax']),
	                    'discountTotal' => (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? '' : strval($obj['discounttotal']),
	                    /**
	                     * Modification Date: 2023-05-16
	                     * Modified By: Francis Lubanga
	                     * Description: 1214 - goodsDetails-->discountTaxRate:If 'discountFlag' is '2', 'discountTaxRate' must be empty!Collection index:0
	                     * */
	                    'discountTaxRate' => (empty($obj['discounttaxrate']) || $obj['discounttaxrate'] == '0.00' || empty($obj['discountflag']) || trim($obj['discountflag']) == '2')? '' : $obj['discounttaxrate'],
	                    'orderNumber' => strval($i),
	                    'discountFlag' => empty($obj['discountflag'])? '2' : $obj['discountflag'],
	                    'deemedFlag' => empty($obj['deemedflag'])? '2' : $obj['deemedflag'],
	                    'exciseFlag' => empty($obj['exciseflag'])? '2' : $obj['exciseflag'],
	                    'categoryId' => empty($obj['categoryid'])? '' : $obj['categoryid'],
	                    'categoryName' => empty($obj['categoryname'])? '' : $obj['categoryname'],
	                    'goodsCategoryId' => empty($obj['goodscategoryid'])? '' : $obj['goodscategoryid'],
	                    'goodsCategoryName' => empty($obj['goodscategoryname'])? '' : $obj['goodscategoryname'],
	                    'exciseRate' => empty($obj['exciserate'])? '' : $obj['exciserate'],
	                    'exciseRule' => empty($obj['exciserule'])? '' : $obj['exciserule'],
	                    'exciseTax' => empty($obj['excisetax'])? '' : $obj['excisetax'],
	                    'pack' => empty($obj['pack'])? '' : $obj['pack'],
	                    'stick' => empty($obj['stick'])? '' : $obj['stick'],
	                    'exciseUnit' => empty($obj['exciseunit'])? '' : $obj['exciseunit'],
	                    'exciseCurrency' => empty($obj['excisecurrency'])? '' : $obj['excisecurrency'],
	                    'exciseRateName' => empty($obj['exciseratename'])? '' : $obj['exciseratename'],
	                    /**
	                     * Modification Date: 2025-05-30
	                     * Modified By: Francis Lubanga
	                     * Description: Resolving error code 2857 - goodsDetails-->taxRate:If 'vatApplicableFlag' is '0', 'taxRate' must be '0'!Collection index:1 when sending VAT OUT OF SCOPE items
	                     * */
	                    'vatApplicableFlag' => (empty($obj['goodscategoryid']) || $obj['goodscategoryid'] !== '96010102')? '1' : '0',
	                    //'vatApplicableFlag' => empty($obj['vatApplicableFlag'])? '' : $obj['vatApplicableFlag'],
	                    'deemedExemptCode' => empty($obj['deemedExemptCode'])? '' : $obj['deemedExemptCode'],
	                    'vatProjectId' => empty($obj['vatProjectId'])? '' : $obj['vatProjectId'],
	                    'vatProjectName' => empty($obj['vatProjectName'])? '' : $obj['vatProjectName'],
	                    'hsCode' => empty($obj['hsCode'])? '' : $obj['hsCode'],
	                    'hsName' => empty($obj['hsName'])? '' : $obj['hsName'],
	                    'totalWeight' => empty($obj['totalWeight'])? '' : round($obj['totalWeight'], 4),
	                    'pieceQty' => empty($obj['pieceQty'])? '' : $obj['pieceQty'],
	                    'pieceMeasureUnit' => empty($obj['pieceMeasureUnit'])? '' : $obj['pieceMeasureUnit']
	                );
	                
	                $i = $i + 1;
	                
	                //If there is a discount, add a discount line below the item
	                if ($obj['discounttotal'] < 0) {
	                    $t_goods[] = array(
	                        'item' => empty($obj['item'])? '' : $obj['item'] . " (Discount)",
	                        'itemCode' => empty($obj['itemcode'])? '' : $obj['itemcode'],
	                        'qty' => '',
	                        'unitOfMeasure' => '',
	                        'unitPrice' => '',
	                        'total' => empty($obj['discounttotal'])? '' : strval($obj['discounttotal']),
	                        'taxRate' => trim($obj['taxcategory']) == 'Exempt'? '-' : (empty($obj['taxrate'])? '' : $obj['taxrate']),
	                        /**
	                         * Modification Date: 2020-11-15
	                         * Modified By: Francis Lubanga
	                         * Description: Resolving error code 1200 - goodsDetails-->tax:cannot be empty!Collection index:1
	                         * Modification Date: 2021-01-26
	                         * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
	                         * */
	                        //'tax' => '',
	                        'tax' => strval(number_format((($obj['discounttotal']/($obj['taxrate'] + 1)) * $obj['taxrate']), 2, '.', '')),
	                        'discountTotal' => '',
	                        'discountTaxRate' => empty($obj['discounttaxrate'])? '' : $obj['discounttaxrate'],
	                        'orderNumber' => strval($i),
	                        'discountFlag' => '0',
	                        'deemedFlag' => empty($obj['deemedflag'])? '2' : $obj['deemedflag'],
	                        'exciseFlag' => empty($obj['exciseflag'])? '2' : $obj['exciseflag'],
	                        'categoryId' => empty($obj['categoryid'])? '' : $obj['categoryid'],
	                        'categoryName' => empty($obj['categoryname'])? '' : $obj['categoryname'],
	                        'goodsCategoryId' => empty($obj['goodscategoryid'])? '' : $obj['goodscategoryid'],
	                        'goodsCategoryName' => empty($obj['goodscategoryname'])? '' : $obj['goodscategoryname'],
	                        'exciseRate' => empty($obj['exciserate'])? '' : $obj['exciserate'],
	                        'exciseRule' => empty($obj['exciserule'])? '' : $obj['exciserule'],
	                        'exciseTax' => empty($obj['excisetax'])? '' : $obj['excisetax'],
	                        'pack' => empty($obj['pack'])? '' : $obj['pack'],
	                        'stick' => empty($obj['stick'])? '' : $obj['stick'],
	                        'exciseUnit' => empty($obj['exciseunit'])? '' : $obj['exciseunit'],
	                        'exciseCurrency' => empty($obj['excisecurrency'])? '' : $obj['excisecurrency'],
	                        'exciseRateName' => empty($obj['exciseratename'])? '' : $obj['exciseratename'],
	                        'vatApplicableFlag' => empty($obj['vatApplicableFlag'])? '' : $obj['vatApplicableFlag'],
	                        'deemedExemptCode' => empty($obj['deemedExemptCode'])? '' : $obj['deemedExemptCode'],
	                        'vatProjectId' => empty($obj['vatProjectId'])? '' : $obj['vatProjectId'],
	                        'vatProjectName' => empty($obj['vatProjectName'])? '' : $obj['vatProjectName'],
	                        'hsCode' => empty($obj['hsCode'])? '' : $obj['hsCode'],
	                        'hsName' => empty($obj['hsName'])? '' : $obj['hsName'],
	                        'totalWeight' => empty($obj['totalWeight'])? '' : round($obj['totalWeight'], 4),
	                        'pieceQty' => empty($obj['pieceQty'])? '' : $obj['pieceQty'],
	                        'pieceMeasureUnit' => empty($obj['pieceMeasureUnit'])? '' : $obj['pieceMeasureUnit']
	                    );
	                    
	                    $i = $i + 1;
	                }
	                
	                $netamount = $netamount + $obj['total'];
	                $taxamount = $taxamount + $obj['tax'];
	                
	                $grossamount = $grossamount + $obj['total'];
	                $itemcount = $itemcount + 1;
	            }
	        } catch (Exception $e) {
	            $this->logger->write("Utilities Controller : uploadinvoice() : The operation to retrive the good details was not successful. The error messages is " . $e->getMessage(), 'r');
	        }
	        
	        //var_dump($goods);
	        $deemedflag = 'N';
	        
	        
	        try{
	            //$temp = $taxes;
	            
	            if ($this->vatRegistered == 'Y') {
	                
	                //RESET THE SUMMARIES
	                $netamount = 0;
	                $taxamount = 0;
	                $grossamount = 0;
	                $itemcount = 0;
	                
	                foreach ($taxes as $obj) {
	                    
	                    /**
	                     * Modification Date: 2021-01-26
	                     * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
	                     * */
	                    if (trim($obj['discountflag']) == '1') {
	                        $obj['d_grossamount'] = $obj['discounttotal'];/*Should be a negative #*/
	                        $obj['d_taxamount'] = ($obj['d_grossamount']/($obj['discounttaxrate'] + 1)) * $obj['discounttaxrate'];
	                        $obj['d_netamount'] = $obj['d_grossamount'] - $obj['d_taxamount'];
	                        
	                        $this->logger->write("Utilities : uploadinvoice() : Calculating taxes. The d_grossamount is " . $obj['d_grossamount'], 'r');
	                        $this->logger->write("Utilities : uploadinvoice() : Calculating taxes. The d_taxamount is " . $obj['d_taxamount'], 'r');
	                        $this->logger->write("Utilities : uploadinvoice() : Calculating taxes. The d_netamount is " . $obj['d_netamount'], 'r');
	                    }
	                    
	                    
	                    if (strtoupper(trim($obj['taxcategory'])) == 'DEEMED') {
	                        $obj['netamount'] = floor(($obj['netamount'] + $obj['d_netamount'])*100)/100;
	                        $obj['taxamount'] = floor(($obj['taxamount'] + $obj['d_taxamount'])*100)/100;
	                        //$obj['grossamount'] = floor($obj['grossamount']*100)/100;
	                        $obj['grossamount'] = $obj['netamount'] + $obj['taxamount'];
	                        
	                        $obj['netamount'] = number_format($obj['netamount'], 2, '.', '');
	                        $obj['taxamount'] = number_format($obj['taxamount'], 2, '.', '');
	                        $obj['grossamount'] = number_format($obj['grossamount'], 2, '.', '');
	                        
	                        $deemedflag = 'Y';
	                    } else {
	                        $obj['netamount'] = round(($obj['netamount'] + $obj['d_netamount']), 2);
	                        $obj['taxamount'] = round(($obj['taxamount'] + $obj['d_taxamount']), 2);
	                        //$obj['grossamount'] = round($obj['grossamount'], 2);
	                        $obj['grossamount'] = $obj['netamount'] + $obj['taxamount'];
	                        
	                        $deemedflag = 'N';
	                    }
	                    
	                    $this->logger->write("Utilities : uploadinvoice() : Calculating taxes. The deemedflag is " . $deemedflag, 'r');
	                    
	                    $t_taxes[] = array(
	                        'taxCategoryCode' => empty($obj['taxcategoryCode'])? '' : $obj['taxcategoryCode'],
	                        'taxCategory' => empty($obj['taxcategory'])? '' : $obj['taxcategory'],
	                        'netAmount' => empty($obj['netamount'])? '0' : strval($obj['netamount']),
	                        'taxRate' => trim($obj['taxcategory']) == 'Exempt'? '-' : (empty($obj['taxrate'])? '0' : number_format($obj['taxrate'], 2, '.', '')),
	                        'taxAmount' => empty($obj['taxamount'])? '0' : strval($obj['taxamount']),
	                        'grossAmount' => empty($obj['grossamount'])? '0' : strval($obj['grossamount']),
	                        'exciseUnit' => empty($obj['exciseunit'])? '' : $obj['exciseunit'],
	                        'exciseCurrency' => empty($obj['excisecurrency'])? '' : $obj['excisecurrency'],
	                        'taxRateName' => empty($obj['taxratename'])? '' : $obj['taxratename']
	                    );
	                    
	                    $netamount = $netamount + $obj['netamount'];
	                    $taxamount = $taxamount + $obj['taxamount'];
	                    
	                    $grossamount = $grossamount + $obj['grossamount'];
	                    $itemcount = $itemcount + 1;
	                }
	            } 
	            
	            
	        } catch (Exception $e) {
	            $this->logger->write("Utilities Controller : uploadinvoice() : The operation to retrive the tax details was not successful. The error messages is " . $e->getMessage(), 'r');
	        }
	        
	        $t_summary[] = array(
	            'netamount' => strtoupper(trim($deemedflag)) == 'N'? round($netamount, 2) : number_format((floor($netamount*100)/100), 2, '.', ''),
	            'taxamount' => strtoupper(trim($deemedflag)) == 'N'? round($taxamount, 2) : 0,
	            'grossamount' => strtoupper(trim($deemedflag)) == 'N'? round($grossamount, 2) : number_format((floor($netamount*100)/100), 2, '.', ''),
	            'itemcount' => sizeof($goods)
	        );
	        
	        try{
	            //$temp = $taxes;
	            
	            foreach ($payments as $obj) {
	                $t_payments[] = array(
	                    'paymentMode' => empty($obj['paymentmode'])? '' : $obj['paymentmode'],
	                    'paymentAmount' => empty($obj['paymentamount'])? '' : strval(round($obj['paymentamount'], 2)),
	                    'orderNumber' => empty($obj['ordernumber'])? '' : $obj['ordernumber']
	                );
	            }
	        } catch (Exception $e) {
	            $this->logger->write("Utilities Controller : uploadinvoice() : The operation to retrive the payment details was not successful. The error messages is " . $e->getMessage(), 'r');
	        }
	        
	        
	        //return json_encode(array('returnCode' => '999', 'returnMessage' => 'Unknown Error'));
	        
	        if(trim($invoicedetails['invoiceindustrycode']) == '104'){
	            $this->logger->write("Utilities : uploadinvoice() : This is an Imported Services invoice. Proceed and swap the seller with the buyer.", 'r');
	            
	            $invoice_u = array(
	                'sellerDetails' => array(
	                    'tin' => empty($org->tin)? '' : $org->tin,
	                    'ninBrn' => empty($org->ninbrn)? '' : addslashes($org->ninbrn),
	                    'legalName' => empty($org->legalname)? '' : addslashes($org->legalname),
	                    'businessName' => empty($org->businessname)? '' : addslashes($org->businessname),
	                    'address' => empty($org->address)? '' : addslashes($org->address),
	                    'mobilePhone' => empty($org->mobilephone)? '' : $org->mobilephone,
	                    'linePhone' => empty($org->linephone)? '' : $org->linephone,
	                    'emailAddress' => empty($org->emailaddress)? '' : addslashes($org->emailaddress),
	                    'placeOfBusiness' => empty($org->placeofbusiness)? '' : addslashes($org->placeofbusiness),
	                    'referenceNo' => empty($invoicedetails['erpinvoiceid'])? '' : $invoicedetails['erpinvoiceid'],
	                    'branchId' => empty($branchuraid)? $devicedetails->branchId : $branchuraid,
	                    'isCheckReferenceNo' => '0'
	                ),
	                'basicInformation' => array(
	                    'invoiceNo' => empty($invoicedetails['erpinvoiceid'])? '' : $invoicedetails['erpinvoiceid'],
	                    'antifakeCode' => empty($invoicedetails['antifakecode'])? '' : $invoicedetails['antifakecode'],
	                    'deviceNo' => $invoicedetails['deviceno'],
	                    'issuedDate' => date('Y-m-d H:i:s'),
	                    'operator' => $invoicedetails['operator'],
	                    'currency' => $invoicedetails['currency'],
	                    'oriInvoiceId' => empty($invoicedetails['oriinvoiceid'])? '' : $invoicedetails['oriinvoiceid'],
	                    'invoiceType' => empty($invoicedetails['invoicetype'])? '' : strval($invoicedetails['invoicetype']),
	                    'invoiceKind' => empty($invoicedetails['invoicekind'])? '' : strval($invoicedetails['invoicekind']),
	                    'dataSource' => empty($invoicedetails['datasource'])? '' : strval($invoicedetails['datasource']),
	                    'invoiceIndustryCode' => empty($invoicedetails['invoiceindustrycode'])? '' : strval($invoicedetails['invoiceindustrycode']),
	                    'isBatch' => empty($invoicedetails['isbatch'])? '' : $invoicedetails['isbatch']
	                ),
	                'buyerDetails' => array(
	                    'buyerTin' => empty($org->tin)? '' : $org->tin,
	                    'buyerNinBrn' => empty($org->ninbrn)? '' : addslashes($org->ninbrn),
	                    'buyerPassportNum' => '',
	                    'buyerLegalName' => empty($org->legalname)? '' : addslashes($org->legalname),
	                    'buyerBusinessName' => empty($org->businessname)? '' : addslashes($org->businessname),
	                    'buyerAddress' => empty($org->address)? '' : addslashes($org->address),
	                    'buyerEmail' => empty($org->emailaddress)? '' : addslashes($org->emailaddress),
	                    'buyerMobilePhone' => empty($org->mobilephone)? '' : $org->mobilephone,
	                    'buyerLinePhone' => empty($org->linephone)? '' : $org->linephone,
	                    'buyerPlaceOfBusi' => empty($org->placeofbusiness)? '' : addslashes($org->placeofbusiness),
	                    'buyerType' => strval($this->appsettings['B2BCODE']),
	                    'buyerCitizenship' => '',
	                    'buyerSector' => '',
	                    'buyerReferenceNo' => '',
	                    'nonResidentFlag' => '0',
	                    'deliveryTermsCode' => ''
	                ),
	                'goodsDetails' => $t_goods,
	                'taxDetails' => $t_taxes,
	                'summary' => array(
	                    'netAmount' => empty($t_summary[0]['netamount'])? '0' : strval($t_summary[0]['netamount']),
	                    'taxAmount' => empty($t_summary[0]['taxamount'])? '0' : strval($t_summary[0]['taxamount']),
	                    'grossAmount' => empty($t_summary[0]['grossamount'])? '0' : strval($t_summary[0]['grossamount']),
	                    'itemCount' => empty($t_summary[0]['itemcount'])? '0' : strval($t_summary[0]['itemcount']),
	                    'modeCode' => empty($invoicedetails['modecode'])? '' : $invoicedetails['modecode'],
	                    'remarks' => empty($invoicedetails['remarks'])? '' : $invoicedetails['remarks'],
	                    'qrCode' => ''
	                ),
	                'payWay' => $t_payments,
	                'extend' => array(
	                    /**
	                     * Author: francis.lubanga@gmail.com
	                     * Date: 2023-05-16
	                     * Description: Resolve EFRIS error code 1404 - reasonCode:Invalid field value!
	                     */
	                    'reason' => trim($invoicedetails['invoicetype']) == '5'? 'Credit Memo' : '',
	                    'reasonCode' => trim($invoicedetails['invoicetype']) == '5'? '103' : ''
	                ),
	                'importServicesSeller' => array(
	                    'importBusinessName' => empty($buyer['businessname'])? $buyer['legalname'] : $buyer['businessname'],
	                    'importEmailAddress' => empty($buyer['emailaddress'])? '' : $buyer['emailaddress'],
	                    'importContactNumber' => empty($buyer['mobilephone'])? $buyer['linephone'] : $buyer['mobilephone'],
	                    'importAddress' => empty($buyer['address'])? $buyer['placeofbusiness'] : $buyer['address'],
	                    'importInvoiceDate' => date('Y-m-d'),
	                    'importAttachmentName' => '',
	                    'importAttachmentContent' => ''
	                ),
	                'airlineGoodsDetails' => $t_airlinegoods,
	                'buyerExtend' => array(
	                    'propertyType' => '',
	                    'district' => '',
	                    'municipalityCounty' => '',
	                    'divisionSubcounty' => '',
	                    'town' => '',
	                    'cellVillage' => '',
	                    'effectiveRegistrationDate' => '',
	                    'meterStatus' => ''
	                ),
	            );
	        } else {
    	        $invoice_u = array(
    	            'sellerDetails' => array(
    	                'tin' => empty($org->tin)? '' : $org->tin,
    	                'ninBrn' => empty($org->ninbrn)? '' : addslashes($org->ninbrn),
    	                'legalName' => empty($org->legalname)? '' : addslashes($org->legalname),
    	                'businessName' => empty($org->businessname)? '' : addslashes($org->businessname),
    	                'address' => empty($org->address)? '' : addslashes($org->address),
    	                'mobilePhone' => empty($org->mobilephone)? '' : $org->mobilephone,
    	                'linePhone' => empty($org->linephone)? '' : $org->linephone,
    	                'emailAddress' => empty($org->emailaddress)? '' : addslashes($org->emailaddress),
    	                'placeOfBusiness' => empty($org->placeofbusiness)? '' : addslashes($org->placeofbusiness),
    	                'referenceNo' => empty($invoicedetails['erpinvoiceid'])? '' : $invoicedetails['erpinvoiceid'],
    	                'branchId' => empty($branchuraid)? $devicedetails->branchId : $branchuraid,
    	                'isCheckReferenceNo' => '0'
    	            ),
    	            'basicInformation' => array(
    	                'invoiceNo' => empty($invoicedetails['erpinvoiceid'])? '' : $invoicedetails['erpinvoiceid'],
    	                'antifakeCode' => empty($invoicedetails['antifakecode'])? '' : $invoicedetails['antifakecode'],
    	                'deviceNo' => $invoicedetails['deviceno'],
    	                'issuedDate' => date('Y-m-d H:i:s'),
    	                'operator' => $invoicedetails['operator'],
    	                'currency' => $invoicedetails['currency'],
    	                'oriInvoiceId' => empty($invoicedetails['oriinvoiceid'])? '' : $invoicedetails['oriinvoiceid'],
    	                'invoiceType' => empty($invoicedetails['invoicetype'])? '' : strval($invoicedetails['invoicetype']),
    	                'invoiceKind' => empty($invoicedetails['invoicekind'])? '' : strval($invoicedetails['invoicekind']),
    	                'dataSource' => empty($invoicedetails['datasource'])? '' : strval($invoicedetails['datasource']),
    	                'invoiceIndustryCode' => empty($invoicedetails['invoiceindustrycode'])? '' : strval($invoicedetails['invoiceindustrycode']),
    	                'isBatch' => empty($invoicedetails['isbatch'])? '' : $invoicedetails['isbatch']
    	            ),
    	            'buyerDetails' => array(
    	                'buyerTin' => empty($buyer['tin'])? '' : $buyer['tin'],
    	                'buyerNinBrn' => empty($buyer['ninbrn'])? '' : $buyer['ninbrn'],
    	                'buyerPassportNum' => empty($buyer['PassportNum'])? '' : $buyer['PassportNum'],
    	                'buyerLegalName' => empty($buyer['legalname'])? '' : $buyer['legalname'],
    	                'buyerBusinessName' => empty($buyer['businessname'])? '' : $buyer['businessname'],
    	                'buyerAddress' => empty($buyer['address'])? '' : $buyer['address'],
    	                'buyerEmail' => empty($buyer['emailaddress'])? '' : $buyer['emailaddress'],
    	                'buyerMobilePhone' => empty($buyer['mobilephone'])? '' : $buyer['mobilephone'],
    	                'buyerLinePhone' => empty($buyer['linephone'])? '' : $buyer['linephone'],
    	                'buyerPlaceOfBusi' => empty($buyer['placeofbusiness'])? '' : $buyer['placeofbusiness'],
    	                'buyerType' => strval($buyer['type']),
    	                'buyerCitizenship' => empty($buyer['citizineship'])? '' : $buyer['citizineship'],
    	                'buyerSector' => empty($buyer['sector'])? '' : $buyer['sector'],
    	                'buyerReferenceNo' => '',
    	                'nonResidentFlag' => empty($buyer['nonResidentFlag'])? '0' : $buyer['nonResidentFlag'],
    	                'deliveryTermsCode' => $buyer['deliveryTermsCode']
    	            ),
    	            'goodsDetails' => $t_goods,
    	            'taxDetails' => $t_taxes,
    	            'summary' => array(
    	                'netAmount' => empty($t_summary[0]['netamount'])? '0' : strval($t_summary[0]['netamount']),
    	                'taxAmount' => empty($t_summary[0]['taxamount'])? '0' : strval($t_summary[0]['taxamount']),
    	                'grossAmount' => empty($t_summary[0]['grossamount'])? '0' : strval($t_summary[0]['grossamount']),
    	                'itemCount' => empty($t_summary[0]['itemcount'])? '0' : strval($t_summary[0]['itemcount']),
    	                'modeCode' => empty($invoicedetails['modecode'])? '' : $invoicedetails['modecode'],
    	                'remarks' => empty($invoicedetails['remarks'])? '' : $invoicedetails['remarks'],
    	                'qrCode' => ''
    	            ),
    	            'payWay' => $t_payments,
    	            'extend' => array(
        	            /**
        	             * Author: francis.lubanga@gmail.com
        	             * Date: 2023-05-16
        	             * Description: Resolve EFRIS error code 1404 - reasonCode:Invalid field value!
        	             */
    	                'reason' => trim($invoicedetails['invoicetype']) == '5'? 'Credit Memo' : '',
    	                'reasonCode' => trim($invoicedetails['invoicetype']) == '5'? '103' : ''
    	            ),
    	            'importServicesSeller' => array(
    	                'importBusinessName' => '',
    	                'importEmailAddress' => '',
    	                'importContactNumber' => '',
    	                'importAddress' => '',
    	                'importInvoiceDate' => '',
    	                'importAttachmentName' => '',
    	                'importAttachmentContent' => ''
    	            ),
    	            'airlineGoodsDetails' => $t_airlinegoods,
    	            'buyerExtend' => array(
    	                'propertyType' => '',
    	                'district' => '',
    	                'municipalityCounty' => '',
    	                'divisionSubcounty' => '',
    	                'town' => '',
    	                'cellVillage' => '',
    	                'effectiveRegistrationDate' => '',
    	                'meterStatus' => ''
    	            ),
    	        );
	        }
	        
	        //print_r($invoice_u);
	        
	        $invoice_u = json_encode($invoice_u); //JSON-ifiy
	        $invoice_u = base64_encode($invoice_u); //base64 encode
	        $this->logger->write("Utilities : uploadinvoice() : The encoded invoice is " . $invoice_u, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $invoice_u,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        
	        //var_dump($j_response);
	        //$this->logger->write("Utilities : uploadinvoice() : The response is: " . $j_response, 'r');
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : uploadinvoice() : The response content is: " . $content, 'r');
	        
	        /**
	         * We need to find out if the content is zipped. 
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : uploadinvoice() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	            if ($dataDesc['zipCode'] == '1') {
	                $this->logger->write("Utilities : uploadinvoice() : The response is zipped", 'r');
	                $content = gzdecode(base64_decode($content));
	            } else {
	                $this->logger->write("Utilities : uploadinvoice() : The response is NOT zipped", 'r');
	                $content = base64_decode($content);
	            }
	        } else {
	            $this->logger->write("Utilities : uploadinvoice() : The API call was not successful. The return code is: " . $returninfo['returnCode'] . ' - ' . $returninfo['returnMessage'], 'r');
	            $content = json_encode(array('returnCode' => $returninfo['returnCode'], 'returnMessage' => $returninfo['returnMessage']));
	        }
	        
	        return $content;
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : uploadinvoice() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}
	
	/**
	 * @name uploaddebitnote
	 * @desc Upload an invoice to EFRIS
	 * @return JSON-encoded object
	 * @param $userid int, $branchuraid string, $buyer array, $debitnotedetails array, $goods array, $payments array, $taxes array
	 *
	 */
	function uploaddebitnote($userid, $branchuraid, $buyer, $debitnotedetails, $goods, $payments, $taxes){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : uploaddebitnote() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : uploaddebitnote() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : uploaddebitnote() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : uploaddebitnote() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    $this->logger->write("Utilities : uploaddebitnote() : The user id is " . $userid, 'r');
	    
	    try {
	        $this->logger->write("Utilities : uploaddebitnote() : Uploading invoice started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T109';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        
	        //$invoice = $debitnotedetails;
	        
	        $org = new organisations($this->db);
	        $org->getBySeller($this->appsettings['SELLER_RECORD_ID']);
	        
	        $t_goods = array();
	        $t_taxes = array();
	        $t_payments = array();
	        $t_summary = array();
	        
	        $netamount = 0;
	        $taxamount = 0;
	        $grossamount = 0;
	        $itemcount = 0;
	        
	        try{
	            //$temp = $goods;
	            $this->logger->write("Utilities : uploaddebitnote() : The GOOD is " . $goods[0]['item'], 'r');
	            
	            $i = 0;
	            foreach ($goods as $obj) {
	                $this->logger->write("Utilities : uploaddebitnote() : The unit price is " . round($obj['unitprice'], 8), 'r');
	                $this->logger->write("Utilities : uploaddebitnote() : The total is " . floor($obj['total']*100)/100, 'r');
	                $this->logger->write("Utilities : uploaddebitnote() : The tax is " . floor($obj['tax']*100)/100, 'r');
	                
	                if ($obj['deemedflag'] == '1') {
	                    $obj['item'] = $obj['item'] . " (Deemed)";
	                    
	                    //Truncate
	                    //$obj['unitprice'] = round($obj['unitprice'], 8);
	                    $obj['total'] = floor($obj['total']*100)/100;
	                    $obj['tax'] = floor($obj['tax']*100)/100;
	                    $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? NULL : floor($obj['discounttotal']*100)/100;
	                    
	                    //Ensure 2 decimal places
	                    /*$obj['unitprice'] = $this->truncatenumber($obj['unitprice'], 8);
	                     $obj['total'] = $this->truncatenumber($obj['total'], 2);
	                     $obj['tax'] = $this->truncatenumber($obj['tax'], 2);
	                     $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? NULL : $this->truncatenumber($obj['discounttotal'], 2);*/
	                    
	                } else {
	                    //Round off
	                    //$obj['unitprice'] = round($obj['unitprice'], 8);
	                    $obj['total'] = round($obj['total'], 2);
	                    $obj['tax'] = round($obj['tax'], 2);
	                    $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? NULL : round($obj['discounttotal'], 2);
	                }
	                
	                //Ensure the right decimal places
	                $obj['qty'] = $this->truncatenumber($obj['qty'], 8);
	                $obj['unitprice'] = $this->truncatenumber($obj['unitprice'], 8);
	                $obj['total'] = $this->truncatenumber($obj['total'], 2);
	                $obj['tax'] = $this->truncatenumber($obj['tax'], 2);
	                $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? NULL : $this->truncatenumber($obj['discounttotal'], 2);
	                
	                
	                $t_goods[] = array(
	                    'item' => empty($obj['item'])? '' : $obj['item'],
	                    'itemCode' => empty($obj['itemcode'])? '' : $obj['itemcode'],
	                    'qty' => empty($obj['qty'])? '' : strval($obj['qty']),
	                    'unitOfMeasure' => empty($obj['unitofmeasure'])? '' : $obj['unitofmeasure'],
	                    'unitPrice' => empty($obj['unitprice'])? '' : strval($obj['unitprice']),
	                    'total' => empty($obj['total'])? '' : strval($obj['total']),
	                    'taxRate' => trim($obj['taxcategory']) == 'Exempt'? '-' : (empty($obj['taxrate'])? '' : number_format($obj['taxrate'], 2, '.', '')),
	                    'tax' => empty($obj['tax'])? '' : strval($obj['tax']),
	                    'discountTotal' => (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? '' : strval($obj['discounttotal']),
	                    'discountTaxRate' => (empty($obj['discounttaxrate']) || $obj['discounttaxrate'] == '0.00')? '' : $obj['discounttaxrate'],
	                    'orderNumber' => strval($i),
	                    'discountFlag' => empty($obj['discountflag'])? '2' : $obj['discountflag'],
	                    'deemedFlag' => empty($obj['deemedflag'])? '2' : $obj['deemedflag'],
	                    'exciseFlag' => empty($obj['exciseflag'])? '2' : $obj['exciseflag'],
	                    'categoryId' => empty($obj['categoryid'])? '' : $obj['categoryid'],
	                    'categoryName' => empty($obj['categoryname'])? '' : $obj['categoryname'],
	                    'goodsCategoryId' => empty($obj['goodscategoryid'])? '' : $obj['goodscategoryid'],
	                    'goodsCategoryName' => empty($obj['goodscategoryname'])? '' : $obj['goodscategoryname'],
	                    'exciseRate' => empty($obj['exciserate'])? '' : $obj['exciserate'],
	                    'exciseRule' => empty($obj['exciserule'])? '' : $obj['exciserule'],
	                    'exciseTax' => empty($obj['excisetax'])? '' : $obj['excisetax'],
	                    'pack' => empty($obj['pack'])? '' : $obj['pack'],
	                    'stick' => empty($obj['stick'])? '' : $obj['stick'],
	                    'exciseUnit' => empty($obj['exciseunit'])? '' : $obj['exciseunit'],
	                    'exciseCurrency' => empty($obj['excisecurrency'])? '' : $obj['excisecurrency'],
	                    'exciseRateName' => empty($obj['exciseratename'])? '' : $obj['exciseratename'],
	                    'vatProjectId' => empty($obj['vatProjectId'])? '' : $obj['vatProjectId'],
	                    'vatProjectName' => empty($obj['vatProjectName'])? '' : $obj['vatProjectName']
	                );
	                
	                $i = $i + 1;
	                
	                //If there is a discount, add a discount line below the item
	                if ($obj['discounttotal'] < 0) {
	                    $t_goods[] = array(
	                        'item' => empty($obj['item'])? '' : $obj['item'] . " (Discount)",
	                        'itemCode' => empty($obj['itemcode'])? '' : $obj['itemcode'],
	                        'qty' => '',
	                        'unitOfMeasure' => '',
	                        'unitPrice' => '',
	                        'total' => empty($obj['discounttotal'])? '' : strval($obj['discounttotal']),
	                        'taxRate' => trim($obj['taxcategory']) == 'Exempt'? '-' : (empty($obj['taxrate'])? '' : $obj['taxrate']),
	                        /**
	                         * Modification Date: 2020-11-15
	                         * Modified By: Francis Lubanga
	                         * Description: Resolving error code 1200 - goodsDetails-->tax:cannot be empty!Collection index:1
	                         * Modification Date: 2021-01-26
	                         * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
	                         * */
	                        //'tax' => '',
	                        'tax' => strval(number_format((($obj['discounttotal']/($obj['taxrate'] + 1)) * $obj['taxrate']), 2, '.', '')),
	                        'discountTotal' => '',
	                        'discountTaxRate' => empty($obj['discounttaxrate'])? '' : $obj['discounttaxrate'],
	                        'orderNumber' => strval($i),
	                        'discountFlag' => '0',
	                        'deemedFlag' => empty($obj['deemedflag'])? '2' : $obj['deemedflag'],
	                        'exciseFlag' => empty($obj['exciseflag'])? '2' : $obj['exciseflag'],
	                        'categoryId' => empty($obj['categoryid'])? '' : $obj['categoryid'],
	                        'categoryName' => empty($obj['categoryname'])? '' : $obj['categoryname'],
	                        'goodsCategoryId' => empty($obj['goodscategoryid'])? '' : $obj['goodscategoryid'],
	                        'goodsCategoryName' => empty($obj['goodscategoryname'])? '' : $obj['goodscategoryname'],
	                        'exciseRate' => empty($obj['exciserate'])? '' : $obj['exciserate'],
	                        'exciseRule' => empty($obj['exciserule'])? '' : $obj['exciserule'],
	                        'exciseTax' => empty($obj['excisetax'])? '' : $obj['excisetax'],
	                        'pack' => empty($obj['pack'])? '' : $obj['pack'],
	                        'stick' => empty($obj['stick'])? '' : $obj['stick'],
	                        'exciseUnit' => empty($obj['exciseunit'])? '' : $obj['exciseunit'],
	                        'exciseCurrency' => empty($obj['excisecurrency'])? '' : $obj['excisecurrency'],
	                        'exciseRateName' => empty($obj['exciseratename'])? '' : $obj['exciseratename'],
	                        'vatProjectId' => empty($obj['vatProjectId'])? '' : $obj['vatProjectId'],
	                        'vatProjectName' => empty($obj['vatProjectName'])? '' : $obj['vatProjectName']
	                    );
	                    
	                    $i = $i + 1;
	                }
	                
	                $netamount = $netamount + $obj['total'];
	                $taxamount = $taxamount + $obj['tax'];
	                
	                $grossamount = $grossamount + $obj['total'];
	                $itemcount = $itemcount + 1;
	            }
	        } catch (Exception $e) {
	            $this->logger->write("Utilities Controller : uploaddebitnote() : The operation to retrive the good details was not successful. The error messages is " . $e->getMessage(), 'r');
	        }
	        
	        //var_dump($goods);
	        $deemedflag = 'N';
	        
	        
	        try{
	            //$temp = $taxes;
	            if ($this->vatRegistered == 'Y') {
	                
	                //RESET THE SUMMARIES
	                $netamount = 0;
	                $taxamount = 0;
	                $grossamount = 0;
	                $itemcount = 0;
	                
	                foreach ($taxes as $obj) {
	                    /**
	                     * Modification Date: 2021-01-26
	                     * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
	                     * */
	                    if (trim($obj['discountflag']) == '1') {
	                        $obj['d_grossamount'] = $obj['discounttotal'];/*Should be a negative #*/
	                        $obj['d_taxamount'] = ($obj['d_grossamount']/($obj['discounttaxrate'] + 1)) * $obj['discounttaxrate'];
	                        $obj['d_netamount'] = $obj['d_grossamount'] - $obj['d_taxamount'];
	                        
	                        $this->logger->write("Utilities : uploadinvoice() : Calculating taxes. The d_grossamount is " . $obj['d_grossamount'], 'r');
	                        $this->logger->write("Utilities : uploadinvoice() : Calculating taxes. The d_taxamount is " . $obj['d_taxamount'], 'r');
	                        $this->logger->write("Utilities : uploadinvoice() : Calculating taxes. The d_netamount is " . $obj['d_netamount'], 'r');
	                    }
	                    
	                    if (strtoupper(trim($obj['taxcategory'])) == 'DEEMED') {
	                        $obj['netamount'] = floor($obj['netamount']*100)/100;
	                        $obj['taxamount'] = floor($obj['taxamount']*100)/100;
	                        //$obj['grossamount'] = floor($obj['grossamount']*100)/100;
	                        $obj['grossamount'] = $obj['netamount'] + $obj['taxamount'];
	                        
	                        $obj['netamount'] = number_format($obj['netamount'], 2, '.', '');
	                        $obj['taxamount'] = number_format($obj['taxamount'], 2, '.', '');
	                        $obj['grossamount'] = number_format($obj['grossamount'], 2, '.', '');
	                        
	                        $deemedflag = 'Y';
	                    } else {
	                        $obj['netamount'] = round($obj['netamount'], 2);
	                        $obj['taxamount'] = round($obj['taxamount'], 2);
	                        //$obj['grossamount'] = round($obj['grossamount'], 2);
	                        $obj['grossamount'] = $obj['netamount'] + $obj['taxamount'];
	                        
	                        $deemedflag = 'N';
	                    }
	                    
	                    $t_taxes[] = array(
	                        'taxCategoryCode' => empty($obj['taxcategoryCode'])? '' : $obj['taxcategoryCode'],
	                        'taxCategory' => empty($obj['taxcategory'])? '' : $obj['taxcategory'],
	                        'netAmount' => empty($obj['netamount'])? '0' : strval($obj['netamount']),
	                        'taxRate' => trim($obj['taxcategory']) == 'Exempt'? '-' : (empty($obj['taxrate'])? '0' : number_format($obj['taxrate'], 2, '.', '')),
	                        'taxAmount' => empty($obj['taxamount'])? '0' : strval($obj['taxamount']),
	                        'grossAmount' => empty($obj['grossamount'])? '0' : strval($obj['grossamount']),
	                        'exciseUnit' => empty($obj['exciseunit'])? '' : $obj['exciseunit'],
	                        'exciseCurrency' => empty($obj['excisecurrency'])? '' : $obj['excisecurrency'],
	                        'taxRateName' => empty($obj['taxratename'])? '' : $obj['taxratename']
	                    );
	                    
	                    $netamount = $netamount + $obj['netamount'];
	                    $taxamount = $taxamount + $obj['taxamount'];
	                    
	                    $grossamount = $grossamount + $obj['grossamount'];
	                    $itemcount = $itemcount + 1;
	                }
	            }
	            
	            
	        } catch (Exception $e) {
	            $this->logger->write("Utilities Controller : uploaddebitnote() : The operation to retrive the tax details was not successful. The error messages is " . $e->getMessage(), 'r');
	        }
	        
	        $t_summary[] = array(
	            'netamount' => strtoupper(trim($deemedflag)) == 'N'? round($netamount, 2) : number_format((floor($netamount*100)/100), 2, '.', ''),
	            'taxamount' => strtoupper(trim($deemedflag)) == 'N'? round($taxamount, 2) : 0,
	            'grossamount' => strtoupper(trim($deemedflag)) == 'N'? round($grossamount, 2) : number_format((floor($netamount*100)/100), 2, '.', ''),
	            'itemcount' => $itemcount
	        );
	        
	        try{
	            //$temp = $taxes;
	            
	            foreach ($payments as $obj) {
	                $t_payments[] = array(
	                    'paymentMode' => empty($obj['paymentmode'])? '' : $obj['paymentmode'],
	                    'paymentAmount' => empty($obj['paymentamount'])? '' : strval(round($obj['paymentamount'], 2)),
	                    'orderNumber' => empty($obj['ordernumber'])? '' : $obj['ordernumber']
	                );
	            }
	        } catch (Exception $e) {
	            $this->logger->write("Utilities Controller : uploaddebitnote() : The operation to retrive the payment details was not successful. The error messages is " . $e->getMessage(), 'r');
	        }
	        
	        
	        //return json_encode(array('returnCode' => '999', 'returnMessage' => 'Unknown Error'));
	        
	        $invoice_u = array(
	            'sellerDetails' => array(
	                'tin' => empty($org->tin)? '' : $org->tin,
	                'ninBrn' => empty($org->ninbrn)? '' : addslashes($org->ninbrn),
	                'legalName' => empty($org->legalname)? '' : addslashes($org->legalname),
	                'businessName' => empty($org->businessname)? '' : addslashes($org->businessname),
	                'address' => empty($org->address)? '' : addslashes($org->address),
	                'mobilePhone' => empty($org->mobilephone)? '' : $org->mobilephone,
	                'linePhone' => empty($org->linephone)? '' : $org->linephone,
	                'emailAddress' => empty($org->emailaddress)? '' : addslashes($org->emailaddress),
	                'placeOfBusiness' => empty($org->placeofbusiness)? '' : addslashes($org->placeofbusiness),
	                'referenceNo' => empty($debitnotedetails['erpinvoiceid'])? '' : $debitnotedetails['erpinvoiceid'],
	                'branchId' => empty($branchuraid)? $devicedetails->branchId : $branchuraid,
	                'isCheckReferenceNo' => '0'
	            ),
	            'basicInformation' => array(
	                'invoiceNo' => empty($debitnotedetails['erpinvoiceid'])? '' : $debitnotedetails['erpinvoiceid'],
	                'antifakeCode' => empty($debitnotedetails['antifakecode'])? '' : $debitnotedetails['antifakecode'],
	                'deviceNo' => $debitnotedetails['deviceno'],
	                'issuedDate' => date('Y-m-d H:i:s'),
	                'operator' => $debitnotedetails['operator'],
	                'currency' => $debitnotedetails['currency'],
	                'oriInvoiceId' => empty($debitnotedetails['oriinvoiceid'])? '' : $debitnotedetails['oriinvoiceid'],
	                'invoiceType' => empty($debitnotedetails['invoicetype'])? '' : strval($debitnotedetails['invoicetype']),
	                'invoiceKind' => empty($debitnotedetails['invoicekind'])? '' : strval($debitnotedetails['invoicekind']),
	                'dataSource' => empty($debitnotedetails['datasource'])? '' : strval($debitnotedetails['datasource']),
	                'invoiceIndustryCode' => empty($debitnotedetails['invoiceindustrycode'])? '' : strval($debitnotedetails['invoiceindustrycode']),
	                'isBatch' => empty($debitnotedetails['isbatch'])? '' : $debitnotedetails['isbatch']
	            ),
	            'buyerDetails' => array(
	                'buyerTin' => empty($buyer['tin'])? '' : $buyer['tin'],
	                'buyerNinBrn' => empty($buyer['ninbrn'])? '' : $buyer['ninbrn'],
	                'buyerPassportNum' => empty($buyer['PassportNum'])? '' : $buyer['PassportNum'],
	                'buyerLegalName' => empty($buyer['legalname'])? '' : $buyer['legalname'],
	                'buyerBusinessName' => empty($buyer['businessname'])? '' : $buyer['businessname'],
	                'buyerAddress' => empty($buyer['address'])? '' : $buyer['address'],
	                'buyerEmail' => empty($buyer['emailaddress'])? '' : $buyer['emailaddress'],
	                'buyerMobilePhone' => empty($buyer['mobilephone'])? '' : $buyer['mobilephone'],
	                'buyerLinePhone' => empty($buyer['linephone'])? '' : $buyer['linephone'],
	                'buyerPlaceOfBusi' => empty($buyer['placeofbusiness'])? '' : $buyer['placeofbusiness'],
	                'buyerType' => strval($buyer['type']),
	                'buyerCitizenship' => empty($buyer['citizineship'])? '' : $buyer['citizineship'],
	                'buyerSector' => empty($buyer['sector'])? '' : $buyer['sector'],
	                'buyerReferenceNo' => ''
	            ),
	            'goodsDetails' => $t_goods,
	            'taxDetails' => $t_taxes,
	            'summary' => array(
	                'netAmount' => empty($t_summary[0]['netamount'])? '0' : strval($t_summary[0]['netamount']),
	                'taxAmount' => empty($t_summary[0]['taxamount'])? '0' : strval($t_summary[0]['taxamount']),
	                'grossAmount' => empty($t_summary[0]['grossamount'])? '0' : strval($t_summary[0]['grossamount']),
	                'itemCount' => empty($t_summary[0]['itemcount'])? '0' : strval($t_summary[0]['itemcount']),
	                'modeCode' => empty($debitnotedetails['modecode'])? '' : $debitnotedetails['modecode'],
	                'remarks' => empty($debitnotedetails['remarks'])? '' : $debitnotedetails['remarks'],
	                'qrCode' => ''
	            ),
	            'payWay' => $t_payments,
	            'extend' => array(
	                'reason' => empty($debitnotedetails['reason'])? '' : $debitnotedetails['reason'],
	                'reasonCode' => empty($debitnotedetails['reasoncode'])? '' : strval($debitnotedetails['reasoncode'])
	            ),
	            'importServicesSeller' => array(
	                'importBusinessName' => '',
	                'importEmailAddress' => '',
	                'importContactNumber' => '',
	                'importAddres' => '',
	                'importInvoiceDate' => '',
	                'importAttachmentName' => '',
	                'importAttachmentContent' => ''
	            )
	        );
	        
	        //print_r($invoice_u);
	        
	        $invoice_u = json_encode($invoice_u); //JSON-ifiy
	        $invoice_u = base64_encode($invoice_u); //base64 encode
	        $this->logger->write("Utilities : uploaddebitnote() : The encoded invoice is " . $invoice_u, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $invoice_u,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : uploaddebitnote() : The response content is: " . $content, 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : uploaddebitnote() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	            if ($dataDesc['zipCode'] == '1') {
	                $this->logger->write("Utilities : uploaddebitnote() : The response is zipped", 'r');
	                $content = gzdecode(base64_decode($content));
	            } else {
	                $this->logger->write("Utilities : uploaddebitnote() : The response is NOT zipped", 'r');
	                $content = base64_decode($content);
	            }
	        } else {
	            $this->logger->write("Utilities : uploaddebitnote() : The API call was not successful. The return code is: " . $returninfo['returnCode'] . ' - ' . $returninfo['returnMessage'], 'r');
	            $content = json_encode(array('returnCode' => $returninfo['returnCode'], 'returnMessage' => $returninfo['returnMessage']));
	        }
	        
	        return $content;
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : uploaddebitnote() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}
	
	/**
	 * @name uploadcreditnote
	 * @desc Upload an creditnote to EFRIS
	 * @return JSON-encoded object
	 * @param $userid int, $buyer array, $creditnotedetails array, $goods array, $payments array, $taxes array
	 *
	 */
	function uploadcreditnote($userid, $buyer, $creditnotedetails, $goods, $payments, $taxes){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : uploadcreditnote() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : uploadcreditnote() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : uploadcreditnote() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : uploadcreditnote() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    $this->logger->write("Utilities : uploadcreditnote() : The user id is " . $userid, 'r');
	    
	    try {
	        $this->logger->write("Utilities : uploadcreditnote() : Uploading creditnote started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T110';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        
	        //$creditnote = $creditnotedetails;
	        
	        $org = new organisations($this->db);
	        $org->getBySeller($this->appsettings['SELLER_RECORD_ID']);
	        
	        $t_goods = array();
	        $t_taxes = array();
	        $t_payments = array();
	        $t_summary = array();
	        
	        $netamount = 0;
	        $taxamount = 0;
	        $grossamount = 0;
	        $itemcount = 0;
	        
	        try{
	            //$temp = $goods;
	            $this->logger->write("Utilities : uploadcreditnote() : The GOOD is " . $goods[0]['item'], 'r');
	            
	            $i = 0;
	            foreach ($goods as $obj) {
	                $this->logger->write("Utilities : uploadcreditnote() : The unit price is " . round($obj['unitprice'], 8), 'r');
	                $this->logger->write("Utilities : uploadcreditnote() : The total is " . floor($obj['total']*100)/100, 'r');
	                $this->logger->write("Utilities : uploadcreditnote() : The tax is " . floor($obj['tax']*100)/100, 'r');
	                
	                if ($obj['deemedflag'] == '1') {
	                    $obj['item'] = $obj['item'] . " (Deemed)";
	                    
	                    //Truncate
	                    //$obj['unitprice'] = round($obj['unitprice'], 8);
	                    $obj['total'] = floor($obj['total']*100)/100;
	                    $obj['tax'] = floor($obj['tax']*100)/100;
	                    $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? NULL : floor($obj['discounttotal']*100)/100;
	                    
	                    //Ensure 2 decimal places
	                    /*$obj['unitprice'] = $this->truncatenumber($obj['unitprice'], 8);
	                     $obj['total'] = $this->truncatenumber($obj['total'], 2);
	                     $obj['tax'] = $this->truncatenumber($obj['tax'], 2);
	                     $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? NULL : $this->truncatenumber($obj['discounttotal'], 2);*/
	                    
	                } else {
	                    //Round off
	                    //$obj['unitprice'] = round($obj['unitprice'], 8);
	                    $obj['total'] = round($obj['total'], 2);
	                    $obj['tax'] = round($obj['tax'], 2);
	                    $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? NULL : round($obj['discounttotal'], 2);
	                }
	                
	                //Ensure the right decimal places
	                $obj['qty'] = $this->truncatenumber($obj['qty'], 8);
	                $obj['unitprice'] = $this->truncatenumber($obj['unitprice'], 8);
	                $obj['total'] = $this->truncatenumber($obj['total'], 2);
	                $obj['tax'] = $this->truncatenumber($obj['tax'], 2);
	                $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? NULL : $this->truncatenumber($obj['discounttotal'], 2);
	                
	                
	                $t_goods[] = array(
	                    'item' => empty($obj['item'])? '' : $obj['item'],
	                    'itemCode' => empty($obj['itemcode'])? '' : $obj['itemcode'],
	                    'qty' => empty($obj['qty'])? '' : '-' . strval($obj['qty']),
	                    'unitOfMeasure' => empty($obj['unitofmeasure'])? '' : $obj['unitofmeasure'],
	                    'unitPrice' => empty($obj['unitprice'])? '' : strval($obj['unitprice']),
	                    'total' => empty($obj['total'])? '' : '-' . strval($obj['total']),
	                    'taxRate' => trim($obj['taxcategory']) == 'Exempt'? '-' : (empty($obj['taxrate'])? '' : number_format($obj['taxrate'], 2, '.', '')),
	                    'tax' => empty($obj['tax'])? '' : '-' . strval($obj['tax']),
	                    'discountTotal' => (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? '' : strval($obj['discounttotal']),
	                    'discountTaxRate' => (empty($obj['discounttaxrate']) || $obj['discounttaxrate'] == '0.00')? '' : $obj['discounttaxrate'],
	                    'orderNumber' => empty($obj['ordernumber'])? strval($i) : strval($obj['ordernumber']),
	                    'discountFlag' => empty($obj['discountflag'])? '2' : $obj['discountflag'],
	                    'deemedFlag' => empty($obj['deemedflag'])? '2' : $obj['deemedflag'],
	                    'exciseFlag' => empty($obj['exciseflag'])? '2' : $obj['exciseflag'],
	                    'categoryId' => empty($obj['categoryid'])? '' : $obj['categoryid'],
	                    'categoryName' => empty($obj['categoryname'])? '' : $obj['categoryname'],
	                    'goodsCategoryId' => empty($obj['goodscategoryid'])? '' : $obj['goodscategoryid'],
	                    'goodsCategoryName' => empty($obj['goodscategoryname'])? '' : $obj['goodscategoryname'],
	                    'exciseRate' => empty($obj['exciserate'])? '' : $obj['exciserate'],
	                    'exciseRule' => empty($obj['exciserule'])? '' : $obj['exciserule'],
	                    'exciseTax' => empty($obj['excisetax'])? '' : $obj['excisetax'],
	                    'pack' => empty($obj['pack'])? '' : $obj['pack'],
	                    'stick' => empty($obj['stick'])? '' : $obj['stick'],
	                    'exciseUnit' => empty($obj['exciseunit'])? '' : $obj['exciseunit'],
	                    'exciseCurrency' => empty($obj['excisecurrency'])? '' : $obj['excisecurrency'],
	                    'exciseRateName' => empty($obj['exciseratename'])? '' : $obj['exciseratename'],
	                    'vatProjectId' => empty($obj['vatProjectId'])? '' : $obj['vatProjectId'],
	                    'vatProjectName' => empty($obj['vatProjectName'])? '' : $obj['vatProjectName']
	                );
	                
	                $i = $i + 1;
	                
	                $netamount = $netamount + $obj['total'];
	                $taxamount = $taxamount + $obj['tax'];
	                
	                $grossamount = $grossamount + $obj['total'];
	                $itemcount = $itemcount + 1;
	            }
	        } catch (Exception $e) {
	            $this->logger->write("Utilities Controller : uploadcreditnote() : The operation to retrive the good details was not successful. The error messages is " . $e->getMessage(), 'r');
	        }
	        
	        //var_dump($goods);
	        $deemedflag = 'N';
	        
	        
	        try{
	            //$temp = $taxes;
	            if ($this->vatRegistered == 'Y') {
	                
	                //RESET THE SUMMARIES
	                $netamount = 0;
	                $taxamount = 0;
	                $grossamount = 0;
	                $itemcount = 0;
	                
	                foreach ($taxes as $obj) {
	                    /**
	                     * Modification Date: 2021-01-26
	                     * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
	                     * */
	                    if (trim($obj['discountflag']) == '1') {
	                        $obj['d_grossamount'] = $obj['discounttotal'];/*Should be a negative #*/
	                        $obj['d_taxamount'] = ($obj['d_grossamount']/($obj['discounttaxrate'] + 1)) * $obj['discounttaxrate'];
	                        $obj['d_netamount'] = $obj['d_grossamount'] - $obj['d_taxamount'];
	                        
	                        $this->logger->write("Utilities : uploadinvoice() : Calculating taxes. The d_grossamount is " . $obj['d_grossamount'], 'r');
	                        $this->logger->write("Utilities : uploadinvoice() : Calculating taxes. The d_taxamount is " . $obj['d_taxamount'], 'r');
	                        $this->logger->write("Utilities : uploadinvoice() : Calculating taxes. The d_netamount is " . $obj['d_netamount'], 'r');
	                    }
	                    
	                    if (strtoupper(trim($obj['taxcategory'])) == 'DEEMED') {
	                        $obj['netamount'] = floor($obj['netamount']*100)/100;
	                        $obj['taxamount'] = floor($obj['taxamount']*100)/100;
	                        //$obj['grossamount'] = floor($obj['grossamount']*100)/100;
	                        $obj['grossamount'] = $obj['netamount'] + $obj['taxamount'];
	                        
	                        $obj['netamount'] = number_format($obj['netamount'], 2, '.', '');
	                        $obj['taxamount'] = number_format($obj['taxamount'], 2, '.', '');
	                        $obj['grossamount'] = number_format($obj['grossamount'], 2, '.', '');
	                        
	                        $deemedflag = 'Y';
	                    } else {
	                        $obj['netamount'] = round($obj['netamount'], 2);
	                        $obj['taxamount'] = round($obj['taxamount'], 2);
	                        //$obj['grossamount'] = round($obj['grossamount'], 2);
	                        $obj['grossamount'] = $obj['netamount'] + $obj['taxamount'];
	                        
	                        $deemedflag = 'N';
	                    }
	                    
	                    $t_taxes[] = array(
	                        'taxCategoryCode' => empty($obj['taxcategoryCode'])? '' : $obj['taxcategoryCode'],
	                        'taxCategory' => empty($obj['taxcategory'])? '' : $obj['taxcategory'],
	                        'netAmount' => empty($obj['netamount'])? '0' : '-' . strval($obj['netamount']),
	                        'taxRate' => trim($obj['taxcategory']) == 'Exempt'? '-' : (empty($obj['taxrate'])? '0' : number_format($obj['taxrate'], 2, '.', '')),
	                        'taxAmount' => empty($obj['taxamount'])? '0' : '-' . strval($obj['taxamount']),
	                        'grossAmount' => empty($obj['grossamount'])? '0' : '-' . strval($obj['grossamount']),
	                        'exciseUnit' => empty($obj['exciseunit'])? '' : $obj['exciseunit'],
	                        'exciseCurrency' => empty($obj['excisecurrency'])? '' : $obj['excisecurrency'],
	                        'taxRateName' => empty($obj['taxratename'])? '' : $obj['taxratename']
	                    );
	                    
	                    $netamount = $netamount + $obj['netamount'];
	                    $taxamount = $taxamount + $obj['taxamount'];
	                    
	                    $grossamount = $grossamount + $obj['grossamount'];
	                    $itemcount = $itemcount + 1;
	                }
	            }
	            
	            
	        } catch (Exception $e) {
	            $this->logger->write("Utilities Controller : uploadcreditnote() : The operation to retrive the tax details was not successful. The error messages is " . $e->getMessage(), 'r');
	        }
	        
	        $t_summary[] = array(
	            'netamount' => strtoupper(trim($deemedflag)) == 'N'? round($netamount, 2) : number_format((floor($netamount*100)/100), 2, '.', ''),
	            'taxamount' => strtoupper(trim($deemedflag)) == 'N'? round($taxamount, 2) : 0,
	            'grossamount' => strtoupper(trim($deemedflag)) == 'N'? round($grossamount, 2) : number_format((floor($netamount*100)/100), 2, '.', ''),
	            'itemcount' => sizeof($goods)
	        );
	        
	        try{
	            //$temp = $taxes;
	            
	            foreach ($payments as $obj) {
	                $t_payments[] = array(
	                    'paymentMode' => empty($obj['paymentmode'])? '' : $obj['paymentmode'],
	                    'paymentAmount' => empty($obj['paymentamount'])? '' : strval(round($obj['paymentamount'], 2)),
	                    'orderNumber' => empty($obj['ordernumber'])? '' : $obj['ordernumber']
	                );
	            }
	        } catch (Exception $e) {
	            $this->logger->write("Utilities Controller : uploadcreditnote() : The operation to retrive the payment details was not successful. The error messages is " . $e->getMessage(), 'r');
	        }
	        
	        
	        //return json_encode(array('returnCode' => '999', 'returnMessage' => 'Unknown Error'));
	        $creditnote_u = array(
	            'currency' => $creditnotedetails['currency'],
	            'source' => empty($creditnotedetails['datasource'])? '' : strval($creditnotedetails['datasource']),
	            'oriInvoiceId' => $creditnotedetails['oriinvoiceid'],
	            'oriInvoiceNo' => $creditnotedetails['oriinvoiceno'],
	            'reasonCode' => $creditnotedetails['reasoncode'],
	            'reason' => empty($creditnotedetails['reason'])? '' : $creditnotedetails['reason'],
	            'applicationTime' => empty($creditnotedetails['applicationtime'])? date('Y-m-d H:i:s') : $creditnotedetails['applicationtime'],
	            'invoiceApplyCategoryCode' => strval($creditnotedetails['invoiceapplycategorycode']),
	            'contactName' => empty($buyer['legalname'])? '' : $buyer['legalname'],
	            'contactMobileNum' => empty($buyer['mobilephone'])? '' : $buyer['mobilephone'],
	            'contactEmail' => empty($buyer['emailaddress'])? '' : $buyer['emailaddress'],
	            'sellersReferenceNo' => empty($creditnotedetails['erpinvoiceid'])? '' : $creditnotedetails['erpinvoiceid'],
	            'goodsDetails' => $t_goods,
	            'taxDetails' => $t_taxes,
	            'summary' => array(
	                'netAmount' => empty($t_summary[0]['netamount'])? '0' : '-' . strval($t_summary[0]['netamount']),
	                'taxAmount' => empty($t_summary[0]['taxamount'])? '0' : '-' . strval($t_summary[0]['taxamount']),
	                'grossAmount' => empty($t_summary[0]['grossamount'])? '0' : '-' . strval($t_summary[0]['grossamount']),
	                'itemCount' => empty($t_summary[0]['itemcount'])? '0' : strval($t_summary[0]['itemcount']),
	                'modeCode' => empty($creditnotedetails['modecode'])? '' : $creditnotedetails['modecode'],
	                'remarks' => empty($creditnotedetails['remarks'])? '' : $creditnotedetails['remarks'],
	                'qrCode' => ''
	            ),
	            'payWay' => $t_payments
	        );

	        
	        //print_r($creditnote_u);
	        
	        $creditnote_u = json_encode($creditnote_u); //JSON-ifiy
	        $creditnote_u = base64_encode($creditnote_u); //base64 encode
	        $this->logger->write("Utilities : uploadcreditnote() : The encoded creditnote is " . $creditnote_u, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $creditnote_u,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : uploadcreditnote() : The response content is: " . $content, 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : uploadcreditnote() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	            if ($dataDesc['zipCode'] == '1') {
	                $this->logger->write("Utilities : uploadcreditnote() : The response is zipped", 'r');
	                $content = gzdecode(base64_decode($content));
	            } else {
	                $this->logger->write("Utilities : uploadcreditnote() : The response is NOT zipped", 'r');
	                $content = base64_decode($content);
	            }
	        } else {
	            $this->logger->write("Utilities : uploadcreditnote() : The API call was not successful. The return code is: " . $returninfo['returnCode'] . ' - ' . $returninfo['returnMessage'], 'r');
	            $content = json_encode(array('returnCode' => $returninfo['returnCode'], 'returnMessage' => $returninfo['returnMessage']));
	        }
	        
	        return $content;
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : uploadcreditnote() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}

	
	/**
	 * @name downloadcreditnote
	 * @desc download a credit note from EFRIS
	 * @return JSON-encoded object
	 * @param $userid int, $reference string
	 *
	 */
	function downloadcreditnote($userid, $reference){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : downloadcreditnote() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : downloadcreditnote() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : downloadcreditnote() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : downloadcreditnote() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    $this->logger->write("Utilities : downloadcreditnote() : The user id is " . $userid, 'r');
	    
	    if (trim($reference) == '' || empty($reference)) {
	        return json_encode(array('returnCode' => '999', 'returnMessage' => 'Unknown Error'));
	        $this->logger->write("Utilities : downloadcreditnote() : The reference is empty.", 'r');
	    }
	    
	    try {
	        $this->logger->write("Utilities : downloadcreditnote() : Downloading credit note started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T111';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
 
	        $creditdebitnote_u = array(
	            'referenceNo' => $reference,
	            'oriInvoiceNo' => '',
	            'invoiceNo' => '',
	            'combineKeywords' => '',
	            'approveStatus' => '',
	            'queryType' => '1',
	            'invoiceApplyCategoryCode' => '101', /*101-Credit Note*/
	            'startDate' => '',
	            'endDate' => '',
	            'pageNo' => '1',
	            'pageSize' => '10',
	        );
	        
	        //print_r($creditdebitnote_u);
	        
	        $creditdebitnote_u = json_encode($creditdebitnote_u); //JSON-ifiy
	        $creditdebitnote_u = base64_encode($creditdebitnote_u); //base64 encode
	        $this->logger->write("Utilities : downloadcreditnote() : The encoded invoice is " . $creditdebitnote_u, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $creditdebitnote_u,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : downloadcreditnote() : The response content is: " . $content, 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : downloadcreditnote() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	            if ($dataDesc['zipCode'] == '1') {
	                $this->logger->write("Utilities : downloadcreditnote() : The response is zipped", 'r');
	                $content = gzdecode(base64_decode($content));
	            } else {
	                $this->logger->write("Utilities : downloadcreditnote() : The response is NOT zipped", 'r');
	                $content = base64_decode($content);
	            }
	        } else {
	            $this->logger->write("Utilities : downloadcreditnote() : The API call was not successful. The return code is: " . $returninfo['returnCode'] . ' - ' . $returninfo['returnMessage'], 'r');
	            $content = json_encode(array('returnCode' => $returninfo['returnCode'], 'returnMessage' => $returninfo['returnMessage']));
	        }
	        
	        return $content;
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : downloadcreditnote() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}
	
	/**
	 * @name downloadcreditnoteappldetails
	 * @desc download credit note application details from EFRIS
	 * @return JSON-encoded object
	 * @param $userid int, $id string
	 *
	 */
	function downloadcreditnoteappldetails($userid, $id){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : downloadcreditnoteappldetails() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : downloadcreditnoteappldetails() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : downloadcreditnoteappldetails() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : downloadcreditnoteappldetails() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    $this->logger->write("Utilities : downloadcreditnoteappldetails() : The user id is " . $userid, 'r');
	    
	    if (trim($id) == '' || empty($id)) {
	        return json_encode(array('returnCode' => '999', 'returnMessage' => 'Unknown Error'));
	        $this->logger->write("Utilities : downloadcreditnoteappldetails() : The application id is empty.", 'r');
	    }
	    
	    try {
	        $this->logger->write("Utilities : downloadcreditnoteappldetails() : Downloading credit note details started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T112';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        

	        $creditnote_u = array(
	            'id' => $id
	        );
	        
	        //print_r($creditnote_u);
	        
	        $creditnote_u = json_encode($creditnote_u); //JSON-ifiy
	        $creditnote_u = base64_encode($creditnote_u); //base64 encode
	        $this->logger->write("Utilities : downloadcreditnoteappldetails() : The encoded invoice is " . $creditnote_u, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $creditnote_u,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : downloadcreditnoteappldetails() : The response content is: " . $content, 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : downloadcreditnoteappldetails() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	            if ($dataDesc['zipCode'] == '1') {
	                $this->logger->write("Utilities : downloadcreditnoteappldetails() : The response is zipped", 'r');
	                $content = gzdecode(base64_decode($content));
	            } else {
	                $this->logger->write("Utilities : downloadcreditnoteappldetails() : The response is NOT zipped", 'r');
	                $content = base64_decode($content);
	            }
	        } else {
	            $this->logger->write("Utilities : downloadcreditnoteappldetails() : The API call was not successful. The return code is: " . $returninfo['returnCode'] . ' - ' . $returninfo['returnMessage'], 'r');
	            $content = json_encode(array('returnCode' => $returninfo['returnCode'], 'returnMessage' => $returninfo['returnMessage']));
	        }
	        
	        return $content;
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : downloadcreditnoteappldetails() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}
	
	/**
	 * @name downloadinvoice
	 * @desc download an invoice, credit note or debit note from EFRIS
	 * @return JSON-encoded object
	 * @param $userid int, $invoiceno string
	 *
	 */
	function downloadinvoice($userid, $invoiceno){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : downloadinvoice() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : downloadinvoice() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : downloadinvoice() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : downloadinvoice() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if (trim($invoiceno) == '' || empty($invoiceno)) {
	        return json_encode(array('returnCode' => '999', 'returnMessage' => 'Unknown Error'));
	        $this->logger->write("Utilities : downloadinvoice() : The invoice is empty.", 'r');
	    }
	    
	    
	    try {
	        $this->logger->write("Utilities : downloadinvoice() : Downloading credit note started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T108';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        
  
	        $invoice_u = array(
	            'invoiceNo' => $invoiceno
	        );
	        
	        //print_r($invoice_u);
	        
	        $invoice_u = json_encode($invoice_u); //JSON-ifiy
	        $invoice_u = base64_encode($invoice_u); //base64 encode
	        $this->logger->write("Utilities : downloadinvoice() : The encoded invoice is " . $invoice_u, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $invoice_u,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : downloadinvoice() : The response content is: " . $content, 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : downloadinvoice() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	            if ($dataDesc['zipCode'] == '1') {
	                $this->logger->write("Utilities : downloadinvoice() : The response is zipped", 'r');
	                $content = gzdecode(base64_decode($content));
	            } else {
	                $this->logger->write("Utilities : downloadinvoice() : The response is NOT zipped", 'r');
	                $content = base64_decode($content);
	            }
	        } else {
	            $this->logger->write("Utilities : downloadinvoice() : The API call was not successful. The return code is: " . $returninfo['returnCode'] . ' - ' . $returninfo['returnMessage'], 'r');
	            $content = json_encode(array('returnCode' => $returninfo['returnCode'], 'returnMessage' => $returninfo['returnMessage']));
	        }
	        
	        return $content;
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : downloadinvoice() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}
	
	/**
	 * @name downloaddebitnote
	 * @desc download a credit/debit note from EFRIS
	 * @return JSON-encoded object
	 * @param $userid int, $debitnoteid int
	 *
	 */
	function downloaddebitnote($userid, $id){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : downloaddebitnote() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : downloaddebitnote() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : downloaddebitnote() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : downloaddebitnote() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    $this->logger->write("Utilities : downloaddebitnote() : The user id is " . $userid, 'r');
	    $this->logger->write("Utilities : downloaddebitnote() : The debitnote id is " . $id, 'r');
	    
	    if (trim($id) == '' || empty($id)) {
	        return json_encode(array('returnCode' => '999', 'returnMessage' => 'Unknown Error'));
	        $this->logger->write("Utilities : downloaddebitnote() : The debitnote id is empty.", 'r');
	    }
	    
	    try {
	        $this->logger->write("Utilities : downloaddebitnote() : Downloading debitnote started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T106';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        
	        $debitnote = new debitnotes($this->db);
	        $debitnote->getByID($id);
	        
	        
	        $debitnote_u = array(
	            'oriInvoiceNo' => $debitnote->oriinvoiceno,
	            'invoiceNo' => empty($debitnote->debitnoteno)? '' : $debitnote->debitnoteno,
	            'deviceNo' => '',
	            'buyerTin' => '',
	            'buyerNinBrn' => '',
	            'buyerLegalName' => '',
	            'combineKeywords' => '',
	            'invoiceType' => strval($debitnote->invoicetype),
	            'invoiceKind' => strval($debitnote->invoicekind),
	            'isInvalid' => '0',
	            'isRefund' => '',
	            'startDate' => '',
	            'endDate' => '',
	            'pageNo' => '1',
	            'pageSize' => '10',
	            'referenceNo' => ''
	        );
	        
	        //print_r($debitnote_u);
	        
	        $debitnote_u = json_encode($debitnote_u); //JSON-ifiy
	        $debitnote_u = base64_encode($debitnote_u); //base64 encode
	        $this->logger->write("Utilities : downloaddebitnote() : The encoded invoice is " . $debitnote_u, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $debitnote_u,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : downloaddebitnote() : The response content is: " . $content, 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : downloaddebitnote() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	            if ($dataDesc['zipCode'] == '1') {
	                $this->logger->write("Utilities : downloaddebitnote() : The response is zipped", 'r');
	                $content = gzdecode(base64_decode($content));
	            } else {
	                $this->logger->write("Utilities : downloaddebitnote() : The response is NOT zipped", 'r');
	                $content = base64_decode($content);
	            }
	        } else {
	            $this->logger->write("Utilities : downloaddebitnote() : The API call was not successful. The return code is: " . $returninfo['returnCode'] . ' - ' . $returninfo['returnMessage'], 'r');
	            $content = json_encode(array('returnCode' => $returninfo['returnCode'], 'returnMessage' => $returninfo['returnMessage']));
	        }
	        
	        return $content;
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : downloaddebitnote() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}
	
	/**
	 * @name cancelcreditnote
	 * @desc Cancel Credit Note, initiate Cancel of Debit Note Application
	 * @return JSON-encoded object
	 * @param $userid int, $oriInvoiceId string, $invoiceNo string, $reason string, $reasonCode string, $invoiceApplyCategoryCode string
	 *
	 */
	function cancelcreditnote($userid, $oriInvoiceId, $invoiceNo, $reason, $reasonCode, $invoiceApplyCategoryCode){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : cancelcreditnote() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : cancelcreditnote() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : cancelcreditnote() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : cancelcreditnote() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    $this->logger->write("Utilities : cancelcreditnote() : The user id is " . $userid, 'r');
	    $this->logger->write("Utilities : cancelcreditnote() : The credit note number is " . $invoiceNo, 'r');
	    
	    if (trim($invoiceNo) == '' || empty($invoiceNo)) {
	        return json_encode(array('returnCode' => '999', 'returnMessage' => 'Unknown Error'));
	        $this->logger->write("Utilities : cancelcreditnote() : The invoice No is empty.", 'r');
	    }
	    
	    try {
	        $this->logger->write("Utilities : cancelcreditnote() : Cancellation of the credit note started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T120';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        

	        $creditdebitnote_u = array(
	            'oriInvoiceId' => strval($oriInvoiceId),
	            'invoiceNo' => strval($invoiceNo),
	            'reason' => strval($reason),
	            'reasonCode' => strval($reasonCode),
	            'invoiceApplyCategoryCode' => strval($invoiceApplyCategoryCode)
	        );
	        
	        
	        //print_r($creditdebitnote_u);
	        
	        $creditdebitnote_u = json_encode($creditdebitnote_u); //JSON-ifiy
	        $creditdebitnote_u = base64_encode($creditdebitnote_u); //base64 encode
	        $this->logger->write("Utilities : cancelcreditnote() : The encoded invoice is " . $creditdebitnote_u, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $creditdebitnote_u,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : cancelcreditnote() : The response content is: " . $content, 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : cancelcreditnote() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	            if ($dataDesc['zipCode'] == '1') {
	                $this->logger->write("Utilities : cancelcreditnote() : The response is zipped", 'r');
	                $content = gzdecode(base64_decode($content));
	            } else {
	                $this->logger->write("Utilities : cancelcreditnote() : The response is NOT zipped", 'r');
	                $content = base64_decode($content);
	            }
	        } else {
	            $this->logger->write("Utilities : cancelcreditnote() : The API call was not successful. The return code is: " . $returninfo['returnCode'] . ' - ' . $returninfo['returnMessage'], 'r');
	            $content = json_encode(array('returnCode' => $returninfo['returnCode'], 'returnMessage' => $returninfo['returnMessage']));
	        }
	        
	        return $content;
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : cancelcreditnote() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}
	
	
	/**
	 * @name voidcreditnote
	 * @desc void a credit/debit note from EFRIS
	 * @return JSON-encoded object
	 * @param $userid int, $applicationid string, $invoiceno string, $referenceno string
	 *
	 */
	function voidcreditnote($userid, $applicationid, $invoiceno, $referenceno){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : voidcreditnote() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : voidcreditnote() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : voidcreditnote() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : voidcreditnote() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    $this->logger->write("Utilities : voidcreditnote() : The user id is " . $userid, 'r');
	    $this->logger->write("Utilities : voidcreditnote() : The credit note invoice number is " . $invoiceno, 'r');
	    
	    if (trim($invoiceno) == '' || empty($invoiceno)) {
	        return json_encode(array('returnCode' => '999', 'returnMessage' => 'Unknown Error'));
	        $this->logger->write("Utilities : voidcreditnote() : The creditdebitnote id is empty.", 'r');
	    }
	    
	    try {
	        $this->logger->write("Utilities : voidcreditnote() : Uploading creditdebitnote started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T120';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        
	        
	        $creditdebitnote_u = array(
	            'businessKey' => strval($applicationid),
	            'referenceNo' => strval($referenceno)
	        );
	        
	        
	        //print_r($creditdebitnote_u);
	        
	        $creditdebitnote_u = json_encode($creditdebitnote_u); //JSON-ifiy
	        $creditdebitnote_u = base64_encode($creditdebitnote_u); //base64 encode
	        $this->logger->write("Utilities : voidcreditnote() : The encoded invoice is " . $creditdebitnote_u, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $creditdebitnote_u,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : voidcreditnote() : The response content is: " . $content, 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : voidcreditnote() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	            if ($dataDesc['zipCode'] == '1') {
	                $this->logger->write("Utilities : voidcreditnote() : The response is zipped", 'r');
	                $content = gzdecode(base64_decode($content));
	            } else {
	                $this->logger->write("Utilities : voidcreditnote() : The response is NOT zipped", 'r');
	                $content = base64_decode($content);
	            }
	        } else {
	            $this->logger->write("Utilities : voidcreditnote() : The API call was not successful. The return code is: " . $returninfo['returnCode'] . ' - ' . $returninfo['returnMessage'], 'r');
	            $content = json_encode(array('returnCode' => $returninfo['returnCode'], 'returnMessage' => $returninfo['returnMessage']));
	        }
	        
	        return $content;
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : voidcreditnote() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}
	
	

	
	/**
	 * @name querytaxpayer
	 * @desc query a tax payer by TIN from EFRIS
	 * @return JSON-encoded object
	 * @param $userid int, $tin string
	 *
	 */
	function querytaxpayer($userid, $tin){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : querytaxpayer() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : querytaxpayer() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : querytaxpayer() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : querytaxpayer() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    $this->logger->write("Utilities : querytaxpayer() : The user id is " . $userid, 'r');
	    $this->logger->write("Utilities : querytaxpayer() : The TIN is " . $tin, 'r');
	    
	    if (trim($tin) == '' || empty($tin)) {
	        return json_encode(array('returnCode' => '999', 'returnMessage' => 'Unknown Error'));
	        $this->logger->write("Utilities : querytaxpayer() : The TIN is empty.", 'r');
	    }
	    
	    try {
	        $this->logger->write("Utilities : querytaxpayer() : Downloading taxpayer started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T119';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        
	        
	        $taxpayer_u = array(
	            'tin' => strval($tin),
	            'ninBrn' => ''
	        );
	        
	        //print_r($taxpayer_u);
	        
	        $taxpayer_u = json_encode($taxpayer_u); //JSON-ifiy
	        $taxpayer_u = base64_encode($taxpayer_u); //base64 encode
	        $this->logger->write("Utilities : querytaxpayer() : The encoded data is " . $taxpayer_u, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $taxpayer_u,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : querytaxpayer() : The response content is: " . $content, 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : querytaxpayer() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	            if ($dataDesc['zipCode'] == '1') {
	                $this->logger->write("Utilities : querytaxpayer() : The response is zipped", 'r');
	                $content = gzdecode(base64_decode($content));
	            } else {
	                $this->logger->write("Utilities : querytaxpayer() : The response is NOT zipped", 'r');
	                $content = base64_decode($content);
	            }
	        } else {
	            $this->logger->write("Utilities : querytaxpayer() : The API call was not successful. The return code is: " . $returninfo['returnCode'] . ' - ' . $returninfo['returnMessage'], 'r');
	            $content = json_encode(array('returnCode' => $returninfo['returnCode'], 'returnMessage' => $returninfo['returnMessage']));
	        }
	        
	        return $content;
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : querytaxpayer() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}
	
	
	/**
	 * @name checktaxpayer
	 * @desc checks whether the taxpayer is tax exempt/Deemed
	 * @return JSON-encoded object
	 * @param $userid int, $tin string
	 *
	 */
	function checktaxpayer($userid, $tin, $commodity){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : checktaxpayer() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : checktaxpayer() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : checktaxpayer() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : checktaxpayer() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    $this->logger->write("Utilities : checktaxpayer() : The user id is " . $userid, 'r');
	    $this->logger->write("Utilities : checktaxpayer() : The TIN is " . $tin, 'r');
	    
	    if (trim($tin) == '' || empty($tin)) {
	        return json_encode(array('returnCode' => '999', 'returnMessage' => 'Unknown Error'));
	        $this->logger->write("Utilities : checktaxpayer() : The TIN is empty.", 'r');
	    }
	    
	    try {
	        $this->logger->write("Utilities : checktaxpayer() : Checking the taxpayer started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T137';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        
	        
	        $taxpayer_u = array(
	            'tin' => strval($tin),
	            'commodityCategoryCode' => strval($commodity)
	        );
	        
	        //print_r($taxpayer_u);
	        
	        $taxpayer_u = json_encode($taxpayer_u); //JSON-ifiy
	        $taxpayer_u = base64_encode($taxpayer_u); //base64 encode
	        $this->logger->write("Utilities : checktaxpayer() : The encoded data is " . $taxpayer_u, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $taxpayer_u,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : checktaxpayer() : The response content is: " . $content, 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : checktaxpayer() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	            if ($dataDesc['zipCode'] == '1') {
	                $this->logger->write("Utilities : checktaxpayer() : The response is zipped", 'r');
	                $content = gzdecode(base64_decode($content));
	            } else {
	                $this->logger->write("Utilities : checktaxpayer() : The response is NOT zipped", 'r');
	                $content = base64_decode($content);
	            }
	        } else {
	            $this->logger->write("Utilities : checktaxpayer() : The API call was not successful. The return code is: " . $returninfo['returnCode'] . ' - ' . $returninfo['returnMessage'], 'r');
	            $content = json_encode(array('returnCode' => $returninfo['returnCode'], 'returnMessage' => $returninfo['returnMessage']));
	        }
	        
	        return $content;
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : checktaxpayer() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}

	
	/**
	 * @name logstocktransfer
	 * @desc Create a record of the stock transfer on eTW
	 * @return NULL
	 * @param $userid int, $productcode string, $qty float, $vchtype string, $vchtypename string, $vchnumber string, $vchref string, $remarks string, $sourceBranchId string, $destinationBranchId string, $transferTypeCode string, $commodityGoodsId string
	 *
	 */
	function logstocktransfer($userid, $productcode, $qty, $vchtype, $vchtypename, $vchnumber, $vchref, $remarks, $sourceBranchId, $destinationBranchId, $transferTypeCode, $commodityGoodsId) {
	    $transferTypeCode = empty($transferTypeCode) || is_null($transferTypeCode)? 'NULL' : $transferTypeCode;
	    $qty = empty($qty) || is_null($qty)? 'NULL' : $qty;
	    
	    
	    $sql = 'INSERT INTO tblgoodsstocktransfer
                            (sourceBranchId,
                             destinationBranchId,
                             transferTypeCode,
                             remarks,
                             commodityGoodsId,
                             quantity,
                             ProductCode,
                             voucherType,
                             voucherTypeName,
                             voucherNumber,
                             voucherRef,
                             inserteddt,
                             insertedby,
                             modifieddt,
                             modifiedby)
                            VALUES ("'
	        . addslashes($sourceBranchId) . '", "'
	            . addslashes($destinationBranchId) . '", '
	                . $transferTypeCode . ', "'
	                    . addslashes($remarks) . '", "'
	                        . addslashes($commodityGoodsId) . '", '
	                            . $qty . ', "'
	                                . addslashes($productcode) . '", "'
	                                    . addslashes($vchtype) . '", "'
	                                        . addslashes($vchtypename) . '", "'
	                                            . addslashes($vchnumber) . '", "'
	                                                . addslashes($vchref) . '", "'
	                                                    . date('Y-m-d H:i:s') . '", '
	                                                        . $userid . ', "'
	                                                            . date('Y-m-d H:i:s') . '", '
	                                                                . $userid . ')';
	                                                                
	                                                                $this->logger->write("Utilities : logstocktransfer() : The SQL is " . $sql, 'r');
	                                                                
	                                                                try{
	                                                                    $this->db->exec(array($sql));
	                                                                    $this->logger->write("Utilities : logstocktransfer() : The stock transfer record has been added", 'r');
	                                                                } catch (Exception $e) {
	                                                                    $this->logger->write("Utilities : logstocktransfer() : Failed to insert the stock transfer record. The error message is " . $e->getMessage(), 'r');
	                                                                }
	}
	
	
	/**
	 * @name invoiceinquiry
	 * @desc Query all invoice information（Invoice/receipt, credit note, debit note, cancel credit note, cancel debit note）
	 * @return JSON-encoded object
	 * @param $userid int, $pageNo string, $pageSize string, $queryType string, $invoiceKind string, $referenceNo string, $deviceNo string, $buyerTin string, $buyerNinBrn string, $buyerLegalName string, $startDate string, $endDate string, $branchName string, $dataSource string
	 *
	 */
	function invoiceinquiry($userid, $pageNo='1', $pageSize='10', $queryType='1', $invoiceKind='1', $referenceNo='', $deviceNo='', $buyerTin='', $buyerNinBrn='', $buyerLegalName='', $startDate='', $endDate='', $branchName='', $dataSource=''){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : invoiceinquiry() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : invoiceinquiry() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : invoiceinquiry() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : invoiceinquiry() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    $this->logger->write("Utilities : invoiceinquiry() : The user id is " . $userid, 'r');
	    
	    
	    try {
	        $this->logger->write("Utilities : invoiceinquiry() : Invoice inquiry started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T106';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        
	        
	        $invoice = array(
	            'oriInvoiceNo' => '',
	            'invoiceNo' => '',
	            'deviceNo' => $deviceNo,
	            'buyerTin' => $buyerTin,
	            'buyerNinBrn' => $buyerNinBrn,
	            'buyerLegalName' => $buyerLegalName,
	            'combineKeywords' => '',
	            'invoiceType' => '1,2,4,5',
	            'invoiceKind' => $invoiceKind,
	            'isInvalid' => '',
	            'isRefund' => '',
	            'startDate' => $startDate,
	            'endDate' => $endDate,
	            'pageNo' => $pageNo,
	            'pageSize' => $pageSize,
	            'referenceNo' => $referenceNo,
	            'branchName' => $branchName,
	            'queryType' => $queryType,
	            'dataSource' => $dataSource
	            
	        );
	        
	        //print_r($debitnote_u);
	        
	        $invoice = json_encode($invoice); //JSON-ifiy
	        $invoice = base64_encode($invoice); //base64 encode
	        $this->logger->write("Utilities : invoiceinquiry() : The encoded invoice is " . $invoice, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $invoice,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        
	        //var_dump($j_response);
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : invoiceinquiry() : The response content is: " . $content, 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : invoiceinquiry() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	            if ($dataDesc['zipCode'] == '1') {
	                $this->logger->write("Utilities : invoiceinquiry() : The response is zipped", 'r');
	                $content = gzdecode(base64_decode($content));
	            } else {
	                $this->logger->write("Utilities : invoiceinquiry() : The response is NOT zipped", 'r');
	                $content = base64_decode($content);
	            }
	        } else {
	            $this->logger->write("Utilities : invoiceinquiry() : The API call was not successful. The return code is: " . $returninfo['returnCode'] . ' - ' . $returninfo['returnMessage'], 'r');
	            $content = json_encode(array('returnCode' => $returninfo['returnCode'], 'returnMessage' => $returninfo['returnMessage']));
	        }
	        
	        return $content;
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : invoiceinquiry() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}

	
	/**
	 * @name uploadimportedservice
	 * @desc Upload an imported service invoice to EFRIS
	 * @return JSON-encoded object
	 * @param $userid int, $branchuraid string, $importedseller array, $invoicedetails array, $goods array, $payments array, $taxes array
	 *
	 */
	function uploadimportedservice($userid, $branchuraid, $importedseller, $invoicedetails, $goods, $payments, $taxes){
	    $web = \Web::instance();
	    $url = $this->appsettings['EFRIS_ENDPOINT'];//api endpoint
	    $content = json_encode(new stdClass);// create an empty JSON
	    //date_default_timezone_set('UTC');//set timezone to UTC
	    $this->logger->write("Utilities : uploadimportedservice() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : uploadimportedservice() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    if(trim(date_default_timezone_get() !== trim($this->appsettings['EFRIS_TIMEZONE']))){
	        date_default_timezone_set($this->appsettings['EFRIS_TIMEZONE']);
	    }
	    
	    $this->logger->write("Utilities : uploadimportedservice() : The current time is " . date('Y-m-d H:i:s'), 'r');
	    $this->logger->write("Utilities : uploadimportedservice() : The current timezone is " . date_default_timezone_get(), 'r');
	    
	    $this->logger->write("Utilities : uploadimportedservice() : The user id is " . $userid, 'r');
	    
	    try {
	        $this->logger->write("Utilities : uploadimportedservice() : Uploading invoice started", 'r');
	        $header = array('Content-Type: application/json');
	        $interfaceCode = 'T109';
	        $tcsdetails = new tcsdetails($this->db);
	        $tcsdetails->getByID($this->appsettings['EFRIS_TCS_RECORD_ID']);
	        $this->logger->write($this->db->log(TRUE), 'r');
	        
	        $companydetails = new organisations($this->db);
	        $companydetails->getByID($this->appsettings['SELLER_RECORD_ID']);
	        
	        $devicedetails = new devices($this->db);
	        $devicedetails->getByID($this->appsettings['EFRIS_DEVICE_RECORD_ID']);
	        
	        //$invoice = $invoicedetails;
	        
	        $org = new organisations($this->db);
	        $org->getBySeller($this->appsettings['SELLER_RECORD_ID']);
	        
	        $t_goods = array();
	        $t_taxes = array();
	        $t_payments = array();
	        $t_summary = array();
	        
	        $netamount = 0;
	        $taxamount = 0;
	        $grossamount = 0;
	        $itemcount = 0;
	        
	        try{
	            //$temp = $goods;
	            $this->logger->write("Utilities : uploadimportedservice() : The GOOD is " . $goods[0]['item'], 'r');
	            
	            $i = 0;
	            foreach ($goods as $obj) {
	                $this->logger->write("Utilities : uploadimportedservice() : The unit price is " . round($obj['unitprice'], 8), 'r');
	                $this->logger->write("Utilities : uploadimportedservice() : The total is " . floor($obj['total']*100)/100, 'r');
	                $this->logger->write("Utilities : uploadimportedservice() : The tax is " . floor($obj['tax']*100)/100, 'r');
	                
	                if ($obj['deemedflag'] == '1') {
	                    $obj['item'] = $obj['item'] . " (Deemed)";
	                    
	                    //Truncate
	                    //$obj['unitprice'] = round($obj['unitprice'], 8);
	                    $obj['total'] = floor($obj['total']*100)/100;
	                    $obj['tax'] = floor($obj['tax']*100)/100;
	                    $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? NULL : floor($obj['discounttotal']*100)/100;
	                    
	                    //Ensure 2 decimal places
	                    /*$obj['unitprice'] = $this->truncatenumber($obj['unitprice'], 8);
	                     $obj['total'] = $this->truncatenumber($obj['total'], 2);
	                     $obj['tax'] = $this->truncatenumber($obj['tax'], 2);
	                     $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? NULL : $this->truncatenumber($obj['discounttotal'], 2);*/
	                    
	                } else {
	                    //Round off
	                    //$obj['unitprice'] = round($obj['unitprice'], 8);
	                    $obj['total'] = round($obj['total'], 2);
	                    $obj['tax'] = round($obj['tax'], 2);
	                    $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? NULL : round($obj['discounttotal'], 2);
	                }
	                
	                //Ensure the right decimal places
	                $obj['qty'] = $this->truncatenumber($obj['qty'], 8);
	                $obj['unitprice'] = $this->truncatenumber($obj['unitprice'], 8);
	                $obj['total'] = $this->truncatenumber($obj['total'], 2);
	                $obj['tax'] = $this->truncatenumber($obj['tax'], 2);
	                $obj['discounttotal'] = (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? NULL : $this->truncatenumber($obj['discounttotal'], 2);
	                
	                $t_goods[] = array(
	                    'item' => empty($obj['item'])? '' : $obj['item'],
	                    'itemCode' => empty($obj['itemcode'])? '' : $obj['itemcode'],
	                    'qty' => empty($obj['qty'])? '' : strval($obj['qty']),
	                    'unitOfMeasure' => empty($obj['unitofmeasure'])? '' : $obj['unitofmeasure'],
	                    'unitPrice' => empty($obj['unitprice'])? '' : strval($obj['unitprice']),
	                    'total' => empty($obj['total'])? '' : strval($obj['total']),
	                    'taxRate' => trim($obj['taxcategory']) == 'Exempt'? '-' : (empty($obj['taxrate'])? '' : number_format($obj['taxrate'], 2, '.', '')),
	                    'tax' => empty($obj['tax'])? '' : strval($obj['tax']),
	                    'discountTotal' => (empty($obj['discounttotal']) || $obj['discounttotal'] == '0.00')? '' : strval($obj['discounttotal']),
	                    /**
	                     * Modification Date: 2023-05-16
	                     * Modified By: Francis Lubanga
	                     * Description: 1214 - goodsDetails-->discountTaxRate:If 'discountFlag' is '2', 'discountTaxRate' must be empty!Collection index:0
	                     * */
	                    'discountTaxRate' => (empty($obj['discounttaxrate']) || $obj['discounttaxrate'] == '0.00' || empty($obj['discountflag']) || trim($obj['discountflag']) == '2')? '' : $obj['discounttaxrate'],
	                    'orderNumber' => strval($i),
	                    'discountFlag' => empty($obj['discountflag'])? '2' : $obj['discountflag'],
	                    'deemedFlag' => empty($obj['deemedflag'])? '2' : $obj['deemedflag'],
	                    'exciseFlag' => empty($obj['exciseflag'])? '2' : $obj['exciseflag'],
	                    'categoryId' => empty($obj['categoryid'])? '' : $obj['categoryid'],
	                    'categoryName' => empty($obj['categoryname'])? '' : $obj['categoryname'],
	                    'goodsCategoryId' => empty($obj['goodscategoryid'])? '' : $obj['goodscategoryid'],
	                    'goodsCategoryName' => empty($obj['goodscategoryname'])? '' : $obj['goodscategoryname'],
	                    'exciseRate' => empty($obj['exciserate'])? '' : $obj['exciserate'],
	                    'exciseRule' => empty($obj['exciserule'])? '' : $obj['exciserule'],
	                    'exciseTax' => empty($obj['excisetax'])? '' : $obj['excisetax'],
	                    'pack' => empty($obj['pack'])? '' : $obj['pack'],
	                    'stick' => empty($obj['stick'])? '' : $obj['stick'],
	                    'exciseUnit' => empty($obj['exciseunit'])? '' : $obj['exciseunit'],
	                    'exciseCurrency' => empty($obj['excisecurrency'])? '' : $obj['excisecurrency'],
	                    'exciseRateName' => empty($obj['exciseratename'])? '' : $obj['exciseratename']
	                );
	                
	                $i = $i + 1;
	                
	                //If there is a discount, add a discount line below the item
	                if ($obj['discounttotal'] < 0) {
	                    $t_goods[] = array(
	                        'item' => empty($obj['item'])? '' : $obj['item'] . " (Discount)",
	                        'itemCode' => empty($obj['itemcode'])? '' : $obj['itemcode'],
	                        'qty' => '',
	                        'unitOfMeasure' => '',
	                        'unitPrice' => '',
	                        'total' => empty($obj['discounttotal'])? '' : strval($obj['discounttotal']),
	                        'taxRate' => trim($obj['taxcategory']) == 'Exempt'? '-' : (empty($obj['taxrate'])? '' : $obj['taxrate']),
	                        /**
	                         * Modification Date: 2020-11-15
	                         * Modified By: Francis Lubanga
	                         * Description: Resolving error code 1200 - goodsDetails-->tax:cannot be empty!Collection index:1
	                         * Modification Date: 2021-01-26
	                         * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
	                         * */
	                        //'tax' => '',
	                        'tax' => strval(number_format((($obj['discounttotal']/($obj['taxrate'] + 1)) * $obj['taxrate']), 2, '.', '')),
	                        'discountTotal' => '',
	                        'discountTaxRate' => empty($obj['discounttaxrate'])? '' : $obj['discounttaxrate'],
	                        'orderNumber' => strval($i),
	                        'discountFlag' => '0',
	                        'deemedFlag' => empty($obj['deemedflag'])? '2' : $obj['deemedflag'],
	                        'exciseFlag' => empty($obj['exciseflag'])? '2' : $obj['exciseflag'],
	                        'categoryId' => empty($obj['categoryid'])? '' : $obj['categoryid'],
	                        'categoryName' => empty($obj['categoryname'])? '' : $obj['categoryname'],
	                        'goodsCategoryId' => empty($obj['goodscategoryid'])? '' : $obj['goodscategoryid'],
	                        'goodsCategoryName' => empty($obj['goodscategoryname'])? '' : $obj['goodscategoryname'],
	                        'exciseRate' => empty($obj['exciserate'])? '' : $obj['exciserate'],
	                        'exciseRule' => empty($obj['exciserule'])? '' : $obj['exciserule'],
	                        'exciseTax' => empty($obj['excisetax'])? '' : $obj['excisetax'],
	                        'pack' => empty($obj['pack'])? '' : $obj['pack'],
	                        'stick' => empty($obj['stick'])? '' : $obj['stick'],
	                        'exciseUnit' => empty($obj['exciseunit'])? '' : $obj['exciseunit'],
	                        'exciseCurrency' => empty($obj['excisecurrency'])? '' : $obj['excisecurrency'],
	                        'exciseRateName' => empty($obj['exciseratename'])? '' : $obj['exciseratename']
	                    );
	                    
	                    $i = $i + 1;
	                }
	                
	                $netamount = $netamount + $obj['total'];
	                $taxamount = $taxamount + $obj['tax'];
	                
	                $grossamount = $grossamount + $obj['total'];
	                $itemcount = $itemcount + 1;
	            }
	        } catch (Exception $e) {
	            $this->logger->write("Utilities Controller : uploadimportedservice() : The operation to retrive the good details was not successful. The error messages is " . $e->getMessage(), 'r');
	        }
	        
	        //var_dump($goods);
	        $deemedflag = 'N';
	        
	        
	        try{
	            //$temp = $taxes;
	            
	            if ($this->vatRegistered == 'Y') {
	                
	                //RESET THE SUMMARIES
	                $netamount = 0;
	                $taxamount = 0;
	                $grossamount = 0;
	                $itemcount = 0;
	                
	                foreach ($taxes as $obj) {
	                    
	                    /**
	                     * Modification Date: 2021-01-26
	                     * Description: Resolving error code 2776 - goodsDetails-->tax: Tax calculation error!Collection index:0
	                     * */
	                    if (trim($obj['discountflag']) == '1') {
	                        $obj['d_grossamount'] = $obj['discounttotal'];/*Should be a negative #*/
	                        $obj['d_taxamount'] = ($obj['d_grossamount']/($obj['discounttaxrate'] + 1)) * $obj['discounttaxrate'];
	                        $obj['d_netamount'] = $obj['d_grossamount'] - $obj['d_taxamount'];
	                        
	                        $this->logger->write("Utilities : uploadimportedservice() : Calculating taxes. The d_grossamount is " . $obj['d_grossamount'], 'r');
	                        $this->logger->write("Utilities : uploadimportedservice() : Calculating taxes. The d_taxamount is " . $obj['d_taxamount'], 'r');
	                        $this->logger->write("Utilities : uploadimportedservice() : Calculating taxes. The d_netamount is " . $obj['d_netamount'], 'r');
	                    }
	                    
	                    
	                    if (strtoupper(trim($obj['taxcategory'])) == 'DEEMED') {
	                        $obj['netamount'] = floor(($obj['netamount'] + $obj['d_netamount'])*100)/100;
	                        $obj['taxamount'] = floor(($obj['taxamount'] + $obj['d_taxamount'])*100)/100;
	                        //$obj['grossamount'] = floor($obj['grossamount']*100)/100;
	                        $obj['grossamount'] = $obj['netamount'] + $obj['taxamount'];
	                        
	                        $obj['netamount'] = number_format($obj['netamount'], 2, '.', '');
	                        $obj['taxamount'] = number_format($obj['taxamount'], 2, '.', '');
	                        $obj['grossamount'] = number_format($obj['grossamount'], 2, '.', '');
	                        
	                        $deemedflag = 'Y';
	                    } else {
	                        $obj['netamount'] = round(($obj['netamount'] + $obj['d_netamount']), 2);
	                        $obj['taxamount'] = round(($obj['taxamount'] + $obj['d_taxamount']), 2);
	                        //$obj['grossamount'] = round($obj['grossamount'], 2);
	                        $obj['grossamount'] = $obj['netamount'] + $obj['taxamount'];
	                        
	                        $deemedflag = 'N';
	                    }
	                    
	                    $this->logger->write("Utilities : uploadimportedservice() : Calculating taxes. The deemedflag is " . $deemedflag, 'r');
	                    
	                    $t_taxes[] = array(
	                        'taxCategoryCode' => empty($obj['taxcategoryCode'])? '' : $obj['taxcategoryCode'],
	                        'taxCategory' => empty($obj['taxcategory'])? '' : $obj['taxcategory'],
	                        'netAmount' => empty($obj['netamount'])? '0' : strval($obj['netamount']),
	                        'taxRate' => trim($obj['taxcategory']) == 'Exempt'? '-' : (empty($obj['taxrate'])? '0' : number_format($obj['taxrate'], 2, '.', '')),
	                        'taxAmount' => empty($obj['taxamount'])? '0' : strval($obj['taxamount']),
	                        'grossAmount' => empty($obj['grossamount'])? '0' : strval($obj['grossamount']),
	                        'exciseUnit' => empty($obj['exciseunit'])? '' : $obj['exciseunit'],
	                        'exciseCurrency' => empty($obj['excisecurrency'])? '' : $obj['excisecurrency'],
	                        'taxRateName' => empty($obj['taxratename'])? '' : $obj['taxratename']
	                    );
	                    
	                    $netamount = $netamount + $obj['netamount'];
	                    $taxamount = $taxamount + $obj['taxamount'];
	                    
	                    $grossamount = $grossamount + $obj['grossamount'];
	                    $itemcount = $itemcount + 1;
	                }
	            }
	            
	            
	        } catch (Exception $e) {
	            $this->logger->write("Utilities Controller : uploadimportedservice() : The operation to retrive the tax details was not successful. The error messages is " . $e->getMessage(), 'r');
	        }
	        
	        $t_summary[] = array(
	            'netamount' => strtoupper(trim($deemedflag)) == 'N'? round($netamount, 2) : number_format((floor($netamount*100)/100), 2, '.', ''),
	            'taxamount' => strtoupper(trim($deemedflag)) == 'N'? round($taxamount, 2) : 0,
	            'grossamount' => strtoupper(trim($deemedflag)) == 'N'? round($grossamount, 2) : number_format((floor($netamount*100)/100), 2, '.', ''),
	            'itemcount' => $itemcount
	        );
	        
	        try{
	            //$temp = $taxes;
	            
	            foreach ($payments as $obj) {
	                $t_payments[] = array(
	                    'paymentMode' => empty($obj['paymentmode'])? '' : $obj['paymentmode'],
	                    'paymentAmount' => empty($obj['paymentamount'])? '' : strval(round($obj['paymentamount'], 2)),
	                    'orderNumber' => empty($obj['ordernumber'])? '' : $obj['ordernumber']
	                );
	            }
	        } catch (Exception $e) {
	            $this->logger->write("Utilities Controller : uploadimportedservice() : The operation to retrive the payment details was not successful. The error messages is " . $e->getMessage(), 'r');
	        }
	        
	        
	        //return json_encode(array('returnCode' => '999', 'returnMessage' => 'Unknown Error'));
	        
	        $invoice_u = array(
	            'sellerDetails' => array(
	                'tin' => empty($org->tin)? '' : $org->tin,
	                'ninBrn' => empty($org->ninbrn)? '' : addslashes($org->ninbrn),
	                'legalName' => empty($org->legalname)? '' : addslashes($org->legalname),
	                'businessName' => empty($org->businessname)? '' : addslashes($org->businessname),
	                'address' => empty($org->address)? '' : addslashes($org->address),
	                'mobilePhone' => empty($org->mobilephone)? '' : $org->mobilephone,
	                'linePhone' => empty($org->linephone)? '' : $org->linephone,
	                'emailAddress' => empty($org->emailaddress)? '' : addslashes($org->emailaddress),
	                'placeOfBusiness' => empty($org->placeofbusiness)? '' : addslashes($org->placeofbusiness),
	                'referenceNo' => empty($invoicedetails['erpinvoiceid'])? '' : $invoicedetails['erpinvoiceid'],
	                'branchId' => empty($branchuraid)? $devicedetails->branchId : $branchuraid,
	                'isCheckReferenceNo' => '0'
	            ),
	            'basicInformation' => array(
	                'invoiceNo' => empty($invoicedetails['erpinvoiceid'])? '' : $invoicedetails['erpinvoiceid'],
	                'antifakeCode' => empty($invoicedetails['antifakecode'])? '' : $invoicedetails['antifakecode'],
	                'deviceNo' => $invoicedetails['deviceno'],
	                'issuedDate' => date('Y-m-d H:i:s'),
	                'operator' => $invoicedetails['operator'],
	                'currency' => $invoicedetails['currency'],
	                'oriInvoiceId' => empty($invoicedetails['oriinvoiceid'])? '' : $invoicedetails['oriinvoiceid'],
	                'invoiceType' => empty($invoicedetails['invoicetype'])? '' : strval($invoicedetails['invoicetype']),
	                'invoiceKind' => empty($invoicedetails['invoicekind'])? '' : strval($invoicedetails['invoicekind']),
	                'dataSource' => empty($invoicedetails['datasource'])? '' : strval($invoicedetails['datasource']),
	                'invoiceIndustryCode' => empty($invoicedetails['invoiceindustrycode'])? '' : strval($invoicedetails['invoiceindustrycode']),
	                'isBatch' => empty($invoicedetails['isbatch'])? '' : $invoicedetails['isbatch']
	            ),
	            'buyerDetails' => array(
	                'buyerTin' => empty($org->tin)? '' : $org->tin,
	                'buyerNinBrn' => empty($org->ninbrn)? '' : addslashes($org->ninbrn),
	                'buyerPassportNum' => '',
	                'buyerLegalName' => empty($org->legalname)? '' : addslashes($org->legalname),
	                'buyerBusinessName' => empty($org->businessname)? '' : addslashes($org->businessname),
	                'buyerAddress' => empty($org->address)? '' : addslashes($org->address),
	                'buyerEmail' => empty($org->emailaddress)? '' : addslashes($org->emailaddress),
	                'buyerMobilePhone' => empty($org->mobilephone)? '' : $org->mobilephone,
	                'buyerLinePhone' => empty($org->linephone)? '' : $org->linephone,
	                'buyerPlaceOfBusi' => empty($org->placeofbusiness)? '' : addslashes($org->placeofbusiness),
	                'buyerType' => strval($this->appsettings['B2BCODE']),
	                'buyerCitizenship' => '',
	                'buyerSector' => '',
	                'buyerReferenceNo' => ''
	            ),
	            'goodsDetails' => $t_goods,
	            'taxDetails' => $t_taxes,
	            'summary' => array(
	                'netAmount' => empty($t_summary[0]['netamount'])? '0' : strval($t_summary[0]['netamount']),
	                'taxAmount' => empty($t_summary[0]['taxamount'])? '0' : strval($t_summary[0]['taxamount']),
	                'grossAmount' => empty($t_summary[0]['grossamount'])? '0' : strval($t_summary[0]['grossamount']),
	                'itemCount' => empty($t_summary[0]['itemcount'])? '0' : strval($t_summary[0]['itemcount']),
	                'modeCode' => empty($invoicedetails['modecode'])? '' : $invoicedetails['modecode'],
	                'remarks' => empty($invoicedetails['remarks'])? '' : $invoicedetails['remarks'],
	                'qrCode' => ''
	            ),
	            'payWay' => $t_payments,
	            'extend' => array(
	                /**
	                 * Author: francis.lubanga@gmail.com
	                 * Date: 2023-05-16
	                 * Description: Resolve EFRIS error code 1404 - reasonCode:Invalid field value!
	                 */
	                'reason' => trim($invoicedetails['invoicetype']) == '5'? 'Credit Memo' : '',
	                'reasonCode' => trim($invoicedetails['invoicetype']) == '5'? '103' : ''
	            ),
	            'importServicesSeller' => array(
	                'importBusinessName' => empty($importedseller['businessname'])? $importedseller['legalname'] : $importedseller['businessname'],
	                'importEmailAddress' => empty($importedseller['emailaddress'])? '' : $importedseller['emailaddress'],
	                'importContactNumber' => empty($importedseller['mobilephone'])? $importedseller['linephone'] : $importedseller['mobilephone'],
	                'importAddress' => empty($importedseller['address'])? $importedseller['placeofbusiness'] : $importedseller['address'],
	                'importInvoiceDate' => date('Y-m-d'),
	                'importAttachmentName' => '',
	                'importAttachmentContent' => ''
	            )
	        );
	        
	        //print_r($invoice_u);
	        
	        $invoice_u = json_encode($invoice_u); //JSON-ifiy
	        $invoice_u = base64_encode($invoice_u); //base64 encode
	        $this->logger->write("Utilities : uploadimportedservice() : The encoded invoice is " . $invoice_u, 'r');
	        
	        $data = array(
	            'data' => array(
	                'content' => $invoice_u,
	                'signature' => '',
	                'dataDescription' => array(
	                    'codeType' => '0',
	                    'encryptCode' => '2',
	                    'zipCode' => '0'
	                )
	            ),
	            'globalInfo' => array(
	                'appId' => $tcsdetails->appid,
	                'version' => $tcsdetails->version,
	                'dataExchangeId' => $tcsdetails->dataexchangeid,
	                'interfaceCode' => $interfaceCode,
	                'requestCode' => $tcsdetails->requestcode,
	                'requestTime' => date('Y-m-d H:i:s'),
	                'responseCode' => $tcsdetails->resposecode,
	                'userName' => $tcsdetails->username,
	                'deviceMAC' => $devicedetails->devicemac,
	                'deviceNo' => $devicedetails->deviceno,
	                'tin' => $companydetails->tin,
	                'taxpayerID' => $companydetails->taxpayerid,
	                'longitude' => $companydetails->longitude,
	                'latitude' => $companydetails->latitude,
	                'extendField' => array(
	                    'responseDateFormat' => $tcsdetails->responsedataformat,
	                    'responseTimeFormat' => $tcsdetails->responsetimeformat,
	                )
	            ),
	            'returnStateInfo' => array(
	                'returnCode' => '',
	                'returnMessage' => '',
	            )
	        );
	        
	        $data = json_encode($data);
	        
	        $this->logger->write("Utilities : uploadimportedservice() : The request payload is: " . $data, 'r');
	        
	        $options = array(
	            'method'  => 'POST',
	            'content' => $data,
	            'header' => $header
	        );
	        
	        $response = $web->request($url, $options);
	        $j_response = json_decode($response['body'], true);
	        
	        //var_dump($j_response);
	        //$this->logger->write("Utilities : uploadimportedservice() : The response is: " . print_r($j_response), 'r');
	        
	        $returninfo = $j_response['returnStateInfo'];
	        $content = $j_response['data']['content'];
	        $this->logger->write("Utilities : uploadimportedservice() : The response content is: " . $content, 'r');
	        
	        /**
	         * We need to find out if the content is zipped.
	         */
	        
	        $dataDesc = $j_response['data']['dataDescription'];
	        
	        //var_dump($returninfo);
	        //var_dump($content);
	        
	        if ($returninfo['returnCode'] == '00') {
	            $this->logger->write("Utilities : uploadimportedservice() : The API call was successful. The return code is: " . $returninfo['returnCode'], 'r');
	            if ($dataDesc['zipCode'] == '1') {
	                $this->logger->write("Utilities : uploadimportedservice() : The response is zipped", 'r');
	                $content = gzdecode(base64_decode($content));
	            } else {
	                $this->logger->write("Utilities : uploadimportedservice() : The response is NOT zipped", 'r');
	                $content = base64_decode($content);
	            }
	        } else {
	            $this->logger->write("Utilities : uploadimportedservice() : The API call was not successful. The return code is: " . $returninfo['returnCode'] . ' - ' . $returninfo['returnMessage'], 'r');
	            $content = json_encode(array('returnCode' => $returninfo['returnCode'], 'returnMessage' => $returninfo['returnMessage']));
	        }
	        
	        return $content;
	    } catch (Exception $e) {
	        $this->logger->write("Utilities : uploadimportedservice() : Error " . $e->getMessage(), 'r');
	        return $content;
	    }
	}
	
	
	
	/**
	 *
	 * @name __constructor
	 * @desc Constructor for the Utilities class
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
	    $logger = new Log('util.log');
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
	    $this->branch = $user->branch;
	    
	    $this->emailUrl = $this->appsettings['SYSTEMEMAILENDPOINT'];
	    
	    
	    $vat_check = new DB\SQL\Mapper($this->db, 'tbltaxtypes');
	    $vat_check->load(array('TRIM(code)=?', $this->appsettings['EFRIS_VAT_TAX_TYPE_CODE']));
	    
	    if ($vat_check->dry()) {
	        $this->logger->write("Utilities : __construct() : The tax payer is not VAT registered", 'r');
	        $this->vatRegistered = 'N';
	    } else {
	        $this->logger->write("Utilities : __construct() : The tax payer is VAT registered", 'r');
	        $this->vatRegistered = 'Y';
	    }
	}
}
?>