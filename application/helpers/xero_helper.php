<?php

function xero_add_trackingcategories() {
    $file =  CURR_DIR.'application/logs/xero_full_request.log';
    $time = time();
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  TrackingCategories Add ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 
    if(DISABLE_XERO){
        return false;
    }
    $file =  CURR_DIR.'application/logs/xero_request.log';
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n========== TrackingCategories POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 

    $ci =& get_instance();
    $ci->load->library('xero'); 
    $apiInstance = $ci->xero->apiInstance;

    $result = $apiInstance->getTrackingCategories(XERO_ORGANISATION_ID);
    $category_add = 0 ;
    if (is_array($result->getTrackingCategories())) {
        foreach($result->getTrackingCategories() as $_category){
            if ($_category->getName() == 'Branch') {
                $category_add = 1 ;
            }
        }                        
    }
    if ($category_add == 0) {
        $trackingcategory = new XeroAPI\XeroPHP\Models\Accounting\TrackingCategory;
        $trackingcategory->setName('Branch');
        $apiResponse = $apiInstance->createTrackingCategory(XERO_ORGANISATION_ID,$trackingcategory); 
        if(isset($apiResponse->getTrackingCategories()[0]->getValidationErrors()[0]['message'])) {
           $file =  CURR_DIR.'application/logs/xero_error.log';
           ob_start();
           echo "<pre>";
           echo "=====".$time.'======';        
           echo "\n========== TrackingCategories Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
           print_r($apiResponse->getTrackingCategories());
           echo "===========================================================================";
           $res = ob_get_clean();
           file_put_contents($file, $res, FILE_APPEND); 
        }else{
           $file =  CURR_DIR.'application/logs/xero_response.log';
           ob_start();
           echo "<pre>";  
           echo "=====".$time.'======';      
           echo "\n========== TrackingCategories ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
           print_r($apiResponse->getTrackingCategories());
           echo "===========================================================================";
           $res = ob_get_clean();
           file_put_contents($file, $res, FILE_APPEND); 
        }
    }
    $center_list = get_data_from_table_list('center','','','');
    $trackingCategories = $apiInstance->getTrackingCategories(XERO_ORGANISATION_ID);
    if (is_array($trackingCategories->getTrackingCategories())) {
        foreach($trackingCategories->getTrackingCategories() as $_category){
            if ($_category->getName() == 'Branch') {
                if (is_array($_category->getOptions()) && count($_category->getOptions()) > 0 ) {
                    $option_array = array();
                    foreach($_category->getOptions() as $_category_name){
                        $option_array[] = $_category_name->getName();
                    }
                    if(is_array($center_list) && count($center_list) > 0){
                        foreach($center_list as $_value){
                            if ($_value['xero_tracking_option_id'] == '') {
                                if (!in_array($_value['center_code'],$option_array)) {
                                    $trackingCategoryId = $_category->getTrackingCategoryId();
                                    $option = new XeroAPI\XeroPHP\Models\Accounting\TrackingOption;
                                    $option->setName($_value['center_code']);
                                    $result = $apiInstance->createTrackingOptions(XERO_ORGANISATION_ID,$trackingCategoryId,$option); 
                                }
                            }else{
                                if (!in_array($_value['center_code'],$option_array)) {
                                    $trackingCategoryId = $_category->getTrackingCategoryId();
                                    $option = new XeroAPI\XeroPHP\Models\Accounting\TrackingOption;
                                    $option = new XeroAPI\XeroPHP\Models\Accounting\TrackingOption;
                                    $option->setName($_value['center_code']);
                                    $result = $apiInstance->updateTrackingOptions(XERO_ORGANISATION_ID,$trackingCategoryId,$_value['xero_tracking_option_id'],$option); 
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    if (is_array($trackingCategories->getTrackingCategories())) {
        foreach($trackingCategories->getTrackingCategories() as $_category){
            if ($_category->getName() == 'Branch') {
                if (is_array($_category->getOptions()) && count($_category->getOptions()) > 0 ) {
                    $option_array = array();
                    $option_id_arry = array();
                    foreach($_category->getOptions() as $_category_name){
                        $option_array[] = $_category_name->getName();
                        $option_id_arry[] = $_category_name->getTrackingOptionId();
                    }
                    if(is_array($option_array) && count($option_array) > 0){
                        foreach($option_array as $key => $_option){
                            update_center_tracking_option_id($_option,$option_id_arry[$key]);
                        }
                    }
                }
            }
        }
    }

}

function xero_add_account($detail){
  $time = time();
  $file =  CURR_DIR.'application/logs/xero_full_request.log';
  ob_start();
  echo "<pre>";  
  echo "=====".$time.'============';      
  echo "\n==========  Account Add POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
  print_r($detail);
  echo "===========================================================================";
  $res = ob_get_clean();
  file_put_contents($file, $res, FILE_APPEND); 

  if(DISABLE_XERO){
      return false;
  }

  $ci =& get_instance();
  $ci->load->library('xero'); 
  $apiInstance = $ci->xero->apiInstance;

  $where = array('id'=>'1');
  $where_2 = array('table_id'=>$detail['id'],'company_id'=>1);
  $xero_account = get_data_from_table('xero_account',$where_2);
  $where = array('id'=>'1');
  $account = new XeroAPI\XeroPHP\Models\Accounting\Account;
  $where = 'Code =="'.trim($detail['code']).'"';
  $accounts = $apiInstance->getAccounts(XERO_ORGANISATION_ID, null, $where); 
  if (empty($xero_account['xero_account_id']) && empty($accounts->getAccounts()[0]['account_id']))  {
      $file =  CURR_DIR.'application/logs/xero_request.log';
      ob_start();
      echo "<pre>";  
      echo "=====".$time.'============';      
      echo "\n==========  Account Add POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
      print_r($detail);
      echo "===========================================================================";
      $res = ob_get_clean();
      file_put_contents($file, $res, FILE_APPEND); 

      $account = new XeroAPI\XeroPHP\Models\Accounting\Account;
      $account->setCode(isset($detail['code']) ? $detail['code'] : '');
      $account->setName(isset($detail['name']) ? $detail['name'] : '');
      $account->setType(isset($detail['type']) ? $detail['type'] : '');
      $account->setTaxType(isset($detail['tex_code']) ?  $detail['tex_code'] : '');
      $account->setDescription(isset($detail['description']) ?  $detail['description'] : ''); 
      $account->setAddToWatchlist(($detail['dashboard'] == '1') ? true : false ); 
      $account->setEnablePaymentsToAccount(($detail['enable_payments'] == '1') ? true : false);
      $account->setShowInExpenseClaims(($detail['expense_claims'] == '1') ? true : false );
      $apiResponse  = $apiInstance->createAccount(XERO_ORGANISATION_ID,$account); 
      if (isset($apiResponse->getAccounts()[0]->getValidationErrors()[0]['message'])) {
        
         $file =  CURR_DIR.'application/logs/xero_error.log';
         ob_start();
         echo "<pre>"; 
         echo "=====".$time.'============';             
         echo "\n==========  Account Add Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
         print_r($apiResponse->getAccounts());
         echo "===========================================================================";
         $res = ob_get_clean();
         file_put_contents($file, $res, FILE_APPEND); 

         $udata = array();
         $udata['xero_account_code_responce'] = serialize($apiResponse->getAccounts());
         $udata['xero_log_id'] = $time;
         $wher_column_name = 'id';
         $table = 'account_code';
         grid_data_updates($udata,$table,$wher_column_name,$detail['id']);

      }else{

         $file =  CURR_DIR.'application/logs/xero_response.log';
         ob_start();
         echo "<pre>"; 
         echo "=====".$time.'============';                    
         echo "\n========== Add Account Response ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
         print_r($apiResponse->getAccounts());
         echo "===========================================================================";
         $res = ob_get_clean();
         file_put_contents($file, $res, FILE_APPEND);

         $data =  array();
         $data['xero_account_id'] = $apiResponse->getAccounts()[0]->getAccountId();
         $data['xero_account_code'] = $apiResponse->getAccounts()[0]->getCode();
         $data['table_id'] = $detail['id'];
         $data['company_id'] = 1;
         $table = 'xero_account';
         grid_add_data($data,$table);
         $udata = array();
         $table = 'account_code';
         $udata['push_in_xero'] = 1;
         $udata['xero_account_code_responce'] = serialize($apiResponse->getAccounts());
         $udata['xero_log_id'] = $time;
         $wher_column_name = 'id';
         grid_data_updates($udata,$table,$wher_column_name,$detail['id']);
      }
  }else{
     $file =  CURR_DIR.'application/logs/xero_error.log';
     ob_start();
     echo "<pre>"; 
     echo "=====".$time.'============';             
     echo "\n==========  Account Add Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
     echo 'Already Account Code Added';
     echo "===========================================================================";
     $res = ob_get_clean();
     file_put_contents($file, $res, FILE_APPEND); 
     $udata = array();
     $udata['xero_account_code_responce'] = 'Already Account Code Added';
     $udata['xero_log_id'] = $time;
     $table = 'account_code';
     $wher_column_name = 'id';
     grid_data_updates($udata,$table,$wher_column_name,$detail['id']);
  }
}

function xero_add_bank_account($detail){

  $file =  CURR_DIR.'application/logs/xero_full_request.log';
  $time = time();
  ob_start();
  echo "<pre>";  
  echo "=====".$time.'============';      
  echo "\n==========  Bank Account Add POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
  print_r($detail);
  echo "===========================================================================";
  $res = ob_get_clean();
  file_put_contents($file, $res, FILE_APPEND); 

  if(DISABLE_XERO){
      return false;
  }

  $ci =& get_instance();
  $ci->load->library('xero'); 
  $apiInstance = $ci->xero->apiInstance;

  $where = array('id'=>'1');
  $where_2 = array('table_id'=>$detail['id'],'company_id'=>1);
  $xero_account = get_data_from_table('xero_account',$where_2);
  $where = array('id'=>'1');
  $account = new XeroAPI\XeroPHP\Models\Accounting\Account;
  $where = 'Code =="'.trim($detail['code']).'"';
  $accounts = $apiInstance->getAccounts(XERO_ORGANISATION_ID, null, $where); 
  if (empty($xero_account['xero_account_id']) && empty($accounts->getAccounts()[0]['account_id']))  {
      $file =  CURR_DIR.'application/logs/xero_request.log';
      ob_start();
      echo "<pre>";  
      echo "=====".$time.'============';      
      echo "\n========== Bank Account Add POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
      print_r($detail);
      echo "===========================================================================";
      $res = ob_get_clean();
      file_put_contents($file, $res, FILE_APPEND); 


      $account = new XeroAPI\XeroPHP\Models\Accounting\Account;
      $account->setCode(isset($detail['code']) ? $detail['code'] : '');
      $account->setName(isset($detail['name']) ? $detail['name'] : '');
      $account->setType('BANK');
      $account->setTaxType('NONE');
      $account->setBankAccountNumber(isset($detail['bank_account_no']) ? $detail['bank_account_no'] : '');
      $apiResponse  = $apiInstance->createAccount(XERO_ORGANISATION_ID,$account); 
      if (isset($apiResponse->getAccounts()[0]->getValidationErrors()[0]['message'])) {
         $file =  CURR_DIR.'application/logs/xero_error.log';
         ob_start();
         echo "<pre>"; 
         echo "=====".$time.'============';             
         echo "\n==========  Account Add Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
         print_r($apiResponse->getAccounts());
         echo "===========================================================================";
         $res = ob_get_clean();
         file_put_contents($file, $res, FILE_APPEND); 
         $udata = array();
         $table = 'account_code';
         $udata['xero_account_code_responce'] = serialize($apiResponse->getAccounts());
         $udata['xero_log_id'] = $time;
         $wher_column_name = 'id';
         grid_data_updates($udata,$table,$wher_column_name,$detail['id']);
      }else{

         $file =  CURR_DIR.'application/logs/xero_response.log';
         ob_start();
         echo "<pre>"; 
         echo "=====".$time.'============';                    
         echo "\n========== Add Account Response ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
         print_r($apiResponse->getAccounts());
         echo "===========================================================================";
         $res = ob_get_clean();
         file_put_contents($file, $res, FILE_APPEND);

         $data =  array();
         $data['xero_account_id'] = $apiResponse->getAccounts()[0]->getAccountId();
         $data['xero_account_code'] = $apiResponse->getAccounts()[0]->getCode();
         $data['table_id'] = $detail['id'];
         $data['push_in_xero'] = 1;
         $data['company_id'] = 1;
         $table = 'xero_account';
         grid_add_data($data,$table);
         $udata = array();
         $table = 'account_code';
         $udata['push_in_xero'] = 1;
         $udata['xero_account_code_responce'] = serialize($apiResponse->getAccounts());
         $udata['xero_log_id'] = $time;
         $wher_column_name = 'id';
         grid_data_updates($udata,$table,$wher_column_name,$detail['id']);
      }
  }else{
     $file =  CURR_DIR.'application/logs/xero_error.log';
     ob_start();
     echo "<pre>"; 
     echo "=====".$time.'============';             
     echo "\n==========  Account Add Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
     echo 'Already Account Code Added';
     echo "===========================================================================";
     $res = ob_get_clean();
     file_put_contents($file, $res, FILE_APPEND); 

     $udata = array();
     $udata['xero_account_code_responce'] = 'Already Account Code Added';
     $udata['xero_log_id'] = time();
     $table = 'account_code';
     $wher_column_name = 'id';
     grid_data_updates($udata,$table,$wher_column_name,$detail['id']);
  }
}

function xero_update_account($detail){

    $file =  CURR_DIR.'application/logs/xero_full_request.log';
    $time = time();
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Account Update POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 

    if(DISABLE_XERO){
      return false;
    }

    $ci =& get_instance();
    $ci->load->library('xero'); 
    $apiInstance = $ci->xero->apiInstance;
    $message = '';
    $file =  CURR_DIR.'application/logs/xero_request.log';
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Account Update POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND);

    $v_where = array('table_id'=>$detail['id'],'company_id'=> 1);
    $validation_array = get_data_from_table('xero_account',$v_where);
    if (isset($validation_array) && !empty($validation_array)) {
        $account = new XeroAPI\XeroPHP\Models\Accounting\Account;
        $account->setCode(isset($detail['code']) ? $detail['code'] : '');
        $account->setName(isset($detail['name']) ? $detail['name'] : '');
        $account->setType(isset($detail['type']) ? $detail['type'] : '');
        $account->setTaxType(isset($detail['tex_code']) ? $detail['tex_code'] : '');
        $account->setDescription(isset($detail['description']) ? $detail['description'] : ''); 
        $account->setAddToWatchlist(($detail['dashboard'] == '1') ? true : false ); 
        $account->setEnablePaymentsToAccount(($detail['enable_payments'] == '1') ? true : false);
        $account->setShowInExpenseClaims(($detail['expense_claims'] == '1') ? true : false );
        $apiResponse = $apiInstance->updateAccount(XERO_ORGANISATION_ID,$detail['xero_id'],$account);
        if (isset($apiResponse->getAccounts()[0]->getValidationErrors()[0]['message'])) {
            $file =  CURR_DIR.'application/logs/xero_error.log';
            ob_start();
            echo "<pre>"; 
            echo "=====".$time.'============';             
            echo "\n==========  Account Update Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
            print_r($apiResponse->getAccounts());
            echo "===========================================================================";
            $res = ob_get_clean();
            file_put_contents($file, $res, FILE_APPEND);

            $udata = array();
            $table = 'account_code';
            $udata['xero_account_code_responce'] = serialize($apiResponse->getAccounts());
            $udata['xero_log_id'] = $time;
            $wher_column_name = 'id';
            grid_data_updates($udata,$table,$wher_column_name,$detail['id']); 

        }else{

            $file =  CURR_DIR.'application/logs/xero_response.log';
            ob_start();
            echo "<pre>"; 
            echo "=====".$time.'============';                    
            echo "\n========== Update  Account Response ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
            print_r($apiResponse->getAccounts());
            echo "===========================================================================";
            $res = ob_get_clean();
            file_put_contents($file, $res, FILE_APPEND); 

            $udata = array();
            $table = 'account_code';
            $udata['xero_log_id'] = $time;
            $udata['xero_account_code_responce'] = serialize($apiResponse->getAccounts());
            $wher_column_name = 'id';
            grid_data_updates($udata,$table,$wher_column_name,$detail['id']);
        }  
    }else{
        $account = new XeroAPI\XeroPHP\Models\Accounting\Account;
        $account->setCode(isset($detail['code']) ? $detail['code'] : '');
        $account->setName(isset($detail['name']) ? $detail['name'] : '');
        $account->setType(isset($detail['type']) ? $detail['type'] : '');
        $account->setTaxType(isset($detail['tex_code']) ?  $detail['tex_code'] : '');
        $account->setDescription(isset($detail['description']) ?  $detail['description'] : ''); 
        $account->setAddToWatchlist(($detail['dashboard'] == '1') ? true : false ); 
        $account->setEnablePaymentsToAccount(($detail['enable_payments'] == '1') ? true : false);
        $account->setShowInExpenseClaims(($detail['expense_claims'] == '1') ? true : false );
        $apiResponse  = $apiInstance->createAccount(XERO_ORGANISATION_ID,$account); 
        if (isset($apiResponse->getAccounts()[0]->getValidationErrors()[0]['message'])) {
            $file =  CURR_DIR.'application/logs/xero_error.log';
            ob_start();
            echo "<pre>"; 
            echo "=====".$time.'============';             
            echo "\n==========  Account Update Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
            print_r($apiResponse->getAccounts());
            echo "===========================================================================";
            $res = ob_get_clean();
            file_put_contents($file, $res, FILE_APPEND);
            $udata = array();
            $table = 'account_code';
            $udata['xero_log_id'] = $time;
            $udata['xero_account_code_responce'] = serialize($apiResponse->getAccounts());
            $wher_column_name = 'id';
            grid_data_updates($udata,$table,$wher_column_name,$detail['id']); 
        }else{

            $file =  CURR_DIR.'application/logs/xero_response.log';
            ob_start();
            echo "<pre>"; 
            echo "=====".$time.'============';                    
            echo "\n========== Update Account Response ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
            print_r($apiResponse->getAccounts());
            echo "===========================================================================";
            $res = ob_get_clean();
            file_put_contents($file, $res, FILE_APPEND); 

            $data =  array();
            $data['xero_account_id'] = $apiResponse->getAccounts()[0]->getAccountId();
            $data['xero_account_code'] = $apiResponse->getAccounts()[0]->getCode();
            $data['table_id'] = $detail['id'];
            $data['company_id'] = 1;
            $table = 'xero_account';
            grid_add_data($data,$table);
            $udata = array();
            $table = 'account_code';
            $udata['push_in_xero'] = 1;
            $udata['xero_log_id'] = $time;
            $udata['xero_account_code_responce'] = serialize($apiResponse->getAccounts());
            $wher_column_name = 'id';
            grid_data_updates($udata,$table,$wher_column_name,$detail['id']);
        }
    }
}

function xero_add_item($detail) {
    $file =  CURR_DIR.'application/logs/xero_full_request.log';
    $time = time();
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Item Add ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 

    if(DISABLE_XERO){
        return false;
    }
    $ci =& get_instance();
    $ci->load->library('xero'); 
    $apiInstance = $ci->xero->apiInstance;

    $file =  CURR_DIR.'application/logs/xero_request.log';
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Item Add POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 

    $where = array('type'=> $detail['type'],'company_id'=> '1','table_id'=>$detail['id']);
    $xero_course = get_data_from_table('xero_course',$where);
    if (empty($xero_course)) {
         $where_2 = 'Code=="'.$detail['item_code'].'"';
         $result2 = $apiInstance->getItems(XERO_ORGANISATION_ID, null, $where_2); 
         if (isset($result2->getItems()[0])) {

                $file =  CURR_DIR.'application/logs/xero_response.log';
                ob_start();
                echo "====".$time."=====";
                echo "<pre>";        
                echo "\n========== Item Add (Ex) ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
                print_r($result2->getItems());
                echo "===========================================================================";
                $res = ob_get_clean();
                file_put_contents($file, $res, FILE_APPEND);

               $data =  array();
               $data['xero_course_id'] = $result2->getItems()[0]->getItemId();
               $data['xero_item_code'] = $result2->getItems()[0]->getCode();
               $data['type'] = $detail['type'];
               $data['table_id'] = $detail['id'];
               $data['company_id'] = '1';
               $table = 'xero_course';
               grid_add_data($data,$table);
               if ($detail['type'] == 'course') {
                   $data = array();
                   $data['push_in_xero_item'] = 1;
                   $data['xero_course_responce'] = serialize($result2->getItems());
                   $data['xero_log_id'] = $time;
                   $table = 'course';
                   $wher_column_name = 'id';
                   grid_data_updates($data,$table,$wher_column_name,$detail['id']);
               }
         }else{
            $arr_items = []; 
            $item_1 = new XeroAPI\XeroPHP\Models\Accounting\Item;
            $item_1->setName(isset($detail['name']) ? substr($detail['name'], 0, 45) : '')
              ->setCode(isset($detail['item_code']) ? $detail['item_code'] : '')
              ->setDescription(isset($detail['description']) ? strip_tags($detail['description']) : '')
              ->setIsTrackedAsInventory(false);
            array_push($arr_items, $item_1);
            $items = new XeroAPI\XeroPHP\Models\Accounting\Items;
            $items->setItems($arr_items);
            $apiResponse = $apiInstance->createItems(XERO_ORGANISATION_ID,$items);

            if (isset($apiResponse->getItems()[0]->getValidationErrors()[0]['message'])) {
                $file =  CURR_DIR.'application/logs/xero_error.log';
                ob_start();
                echo "====".$time."=====";
                echo "<pre>";        
                echo "\n========== Item Add Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
                print_r($apiResponse->getItems());
                echo "===========================================================================";
                $res = ob_get_clean();
                file_put_contents($file, $res, FILE_APPEND); 

                $data = array();
                $data['xero_course_responce'] = serialize($apiResponse->getItems());
                $data['xero_log_id'] = $time;
                $table = 'course';
                $wher_column_name = 'id';
                grid_data_updates($data,$table,$wher_column_name,$detail['id']);

            }else{

                $file =  CURR_DIR.'application/logs/xero_response.log';
                ob_start();
                echo "====".$time."=====";
                echo "<pre>";        
                echo "\n========== Item Add  ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
                print_r($apiResponse->getItems());
                echo "===========================================================================";
                $res = ob_get_clean();
                file_put_contents($file, $res, FILE_APPEND);


               $data =  array();
               $data['xero_course_id'] = $apiResponse->getItems()[0]->getItemId();
               $data['xero_item_code'] = $apiResponse->getItems()[0]->getCode();
               $data['type'] = $detail['type'];
               $data['table_id'] = $detail['id'];
               $data['company_id'] = '1';
               $table = 'xero_course';
               grid_add_data($data,$table);
               if ($detail['type'] == 'course') {
                   $data = array();
                   $data['push_in_xero_item'] = 1;
                   $data['xero_course_responce'] = serialize($apiResponse->getItems());
                   $data['xero_log_id'] = $time;
                   $table = 'course';
                   $wher_column_name = 'id';
                   grid_data_updates($data,$table,$wher_column_name,$detail['id']);
               }
            }
         }
    }
}

function xero_update_item($detail){

    $file =  CURR_DIR.'application/logs/xero_full_request.log';
    $time = time();
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Item Edit ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 

    if(DISABLE_XERO){
        return false;
    }
    $ci =& get_instance();
    $ci->load->library('xero'); 
    $apiInstance = $ci->xero->apiInstance;
    $file =  CURR_DIR.'application/logs/xero_request.log';
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Item Edit POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 
              
    $where = array('id'=> '1' );
    $where_1 = array('company_id'=> '1','table_id'=> $detail['id'],'type'=>$detail['type']);
    $xero_course = get_data_from_table('xero_course',$where_1);
    $itemId = $xero_course['xero_course_id'];
    if (isset($xero_course['xero_course_id']) && $xero_course['xero_course_id'] != '') {
          $code = $detail['item_code'];
          $item = new XeroAPI\XeroPHP\Models\Accounting\Item;
          $item->setName($detail['name'], 0, 45)
               ->setCode($code);
          $apiResponse = $apiInstance->updateItem(XERO_ORGANISATION_ID,$itemId,$item); 
          $message = array();
          if (isset($apiResponse->getItems()[0]->getValidationErrors()[0]['message'])) {
              $file =  CURR_DIR.'application/logs/xero_error.log';
              ob_start();
              echo "====".$time."=====";
              echo "<pre>";        
              echo "\n========== Item Edit Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
              print_r($apiResponse->getItems());
              echo "===========================================================================";
              $res = ob_get_clean();
              file_put_contents($file, $res, FILE_APPEND); 
              $data =  array();
              $data['xero_course_responce'] = serialize($apiResponse->getItems());
              $data['xero_log_id'] = $time;
              $table = 'course';
              $wher_column_name = 'id';
              grid_data_updates($data,$table,$wher_column_name,$detail['id']);
          }else{
              $file =  CURR_DIR.'application/logs/xero_response.log';
              ob_start();
              echo "<pre>";
              echo "=====".$time.'=========';        
              echo "\n========== Item Edit ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
              print_r($apiResponse->getItems());
              echo "===========================================================================";
              $res = ob_get_clean();
              file_put_contents($file, $res, FILE_APPEND); 
              $data =  array();
              $where = array('table_id'=> $detail['id'],'type' => $detail['type']);
              $xero_course = get_data_from_table('xero_course',$where);
              $data['xero_course_id'] = $apiResponse->getItems()[0]->getItemId();
              $data['xero_item_code'] = $apiResponse->getItems()[0]->getCode();
              $data['type'] = $detail['type'];
              $data['table_id'] = $detail['id'];
              $data['company_id'] = 1 ;
              $table = 'xero_course';
              $wher_column_name = 'id';
              grid_data_updates($data,$table,$wher_column_name,$xero_course['id']);
              if ($detail['type'] == 'course') {
                 $data =  array();
                 $data['push_in_xero_item'] = 1;
                 $data['xero_course_responce'] = serialize($apiResponse->getItems());
                 $data['xero_log_id'] = $time;
                 $table = 'course';
                 $wher_column_name = 'id';
                 grid_data_updates($data,$table,$wher_column_name,$detail['id']);
              }
          }
    }
}

function xero_add_tax_rate($detail){
    $file =  CURR_DIR.'application/logs/xero_full_request.log';
    $time = time();
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Tax Rate Add ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 

    if(DISABLE_XERO){
        return false;
    }
    $ci =& get_instance();
    $ci->load->library('xero'); 
    $apiInstance = $ci->xero->apiInstance;
    $file =  CURR_DIR.'application/logs/xero_request.log';
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Tax Rate Add POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND);

    if (isset($detail['id']) && $detail['id'] != '') {
          $str = '';
          $center_name = $detail['center_name'];
          $tax_type_name = $detail['tax_type_name'];
          $taxcomponent = new XeroAPI\XeroPHP\Models\Accounting\TaxComponent;
          $taxcomponent->setName($tax_type_name.'-'.$center_name.'-'.$detail['id'])
                       ->setRate($detail['rate']);
          $arr_taxcomponent = [];
          array_push($arr_taxcomponent, $taxcomponent);
          $taxrate = new XeroAPI\XeroPHP\Models\Accounting\TaxRate;
          $taxrate->setName($tax_type_name.'-'.$center_name.'-'.$detail['id'])
                   ->setTaxType('INPUT')
                  ->setTaxComponents($arr_taxcomponent);
          $apiResponse = $apiInstance->createTaxRates(XERO_ORGANISATION_ID,$taxrate); 
          if ($apiResponse->getTaxRates()[0]->getName()) {
              $file =  CURR_DIR.'application/logs/xero_response.log';
              ob_start();
              echo "<pre>";
              echo "=====".$time.'=========';        
              echo "\n========== TaxRate Add ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
              print_r($apiResponse->getTaxRates());
              echo "===========================================================================";
              $res = ob_get_clean();
              file_put_contents($file, $res, FILE_APPEND); 

              $data =  array();
              $data['push_in_xero_tax_rate'] = 1;
              $data['xero_log_id'] = $time;
              $data['xero_tax_type'] = $apiResponse->getTaxRates()[0]->getTaxType();
              $data['xero_center_gst_responce'] = serialize($apiResponse->getTaxRates());
              $table = 'center_gst';
              $wher_column_name = 'id';
              grid_data_updates($data,$table,$wher_column_name,$detail['id']);
          }else{
              $file =  CURR_DIR.'application/logs/xero_error.log';
              ob_start();
              echo "====".$time."=====";
              echo "<pre>";        
              echo "\n========== TaxRate Add Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
              print_r($apiResponse->getTaxRates());
              echo "===========================================================================";
              $res = ob_get_clean();
              file_put_contents($file, $res, FILE_APPEND); 
              $data =  array();
              $data['xero_center_gst_responce'] = serialize($apiResponse->getTaxRates());
              $data['xero_log_id'] = $time;
              $table = 'center_gst';
              $wher_column_name = 'id';
              grid_data_updates($data,$table,$wher_column_name,$detail['id']);
          }
    }
}

function xero_edit_tax_rate($detail){
    $file =  CURR_DIR.'application/logs/xero_full_request.log';
    $time = time();
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Tax Rate Edit ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 

    if(DISABLE_XERO){
        return false;
    }
    $ci =& get_instance();
    $ci->load->library('xero'); 
    $apiInstance = $ci->xero->apiInstance;
    $file =  CURR_DIR.'application/logs/xero_request.log';
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Tax Rate Edit POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND);

    if (isset($detail['id']) && $detail['id'] != '') {
          $str = '';
          $center_name = $detail['center_name'];
          $tax_type_name = $detail['center_name'];
          $taxcomponent = new XeroAPI\XeroPHP\Models\Accounting\TaxComponent;
          $taxcomponent->setName($tax_type_name.'-'.$center_name.'-'.$detail['id'])
                       ->setRate($detail['rate']);
          $arr_taxcomponent = [];
          array_push($arr_taxcomponent, $taxcomponent);
          $taxrate = new XeroAPI\XeroPHP\Models\Accounting\TaxRate;
          $taxrate->setName($tax_type_name.'-'.$center_name.'-'.$detail['id'])
                  ->setTaxComponents($arr_taxcomponent);
          $apiResponse = $apiInstance->updateTaxRate(XERO_ORGANISATION_ID,$taxrate); 
          if ($apiResponse->getTaxRates()[0]->getName()) {
              $file =  CURR_DIR.'application/logs/xero_response.log';
              ob_start();
              echo "<pre>";
              echo "=====".$time.'=========';        
              echo "\n========== TaxRate Edit ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
              print_r($apiResponse->getTaxRates());
              echo "===========================================================================";
              $res = ob_get_clean();
              file_put_contents($file, $res, FILE_APPEND); 

              $data =  array();
              $data['push_in_xero_tax_rate'] = 1;
              $data['xero_log_id'] = $time;
              $data['xero_tax_type'] = $apiResponse->getTaxRates()[0]->getTaxType();
              $table = 'center_gst';
              $wher_column_name = 'id';
              grid_data_updates($data,$table,$wher_column_name,$detail['id']);
          }else{
              $file =  CURR_DIR.'application/logs/xero_error.log';
              ob_start();
              echo "====".$time."=====";
              echo "<pre>";        
              echo "\n========== TaxRate Edit Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
              print_r($apiResponse->getTaxRates());
              echo "===========================================================================";
              $res = ob_get_clean();
              file_put_contents($file, $res, FILE_APPEND); 

              $data =  array();
              $data['push_in_xero_tax_rate'] = 1;
              $data['xero_log_id'] = $time;
              $data['xero_tax_type'] = $apiResponse->getTaxRates()[0]->getTaxType();
              $data['xero_center_gst_responce'] = serialize($apiResponse->getTaxRates());
              $table = 'center_gst';
              $wher_column_name = 'id';
              grid_data_updates($data,$table,$wher_column_name,$detail['id']);
          }
    }
}

function xero_add_contact($detail) {
    $file =  CURR_DIR.'application/logs/xero_full_request.log';
    $time = time();
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Contact Add ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 

    if(DISABLE_XERO){
        return false;
    }
    $ci =& get_instance();
    $ci->load->library('xero'); 
    $apiInstance = $ci->xero->apiInstance;
    $message = '';
    $file =  CURR_DIR.'application/logs/xero_request.log';
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Contact Add POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND);

    $where_2 = 'Name=="'.$detail['contact_name'].'"';
    $result2 = $apiInstance->getContacts(XERO_ORGANISATION_ID, null, $where_2); 
        if (isset($result2->getContacts()[0])) {
           $file =  CURR_DIR.'application/logs/xero_response.log';
           ob_start();
           echo "<pre>";  
           echo "=====".$time.'======';      
           echo "\n========== Contact Add (Ex) ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
           print_r($result2->getContacts());
           echo "===========================================================================";
           $res = ob_get_clean();
           file_put_contents($file, $res, FILE_APPEND);
                 $data =  array();
                 $data['user_id'] = $detail['user_id'];
                 $data['table_id'] = isset($detail['id']) ? $detail['id'] : '';
                 $data['type'] = $detail['type'];
                 $data['company_id'] = 1 ;
                 $data['ContactID'] = $result2->getContacts()[0]->getContactId();
                 $table = 'xero_contact';
                 grid_add_data($data,$table);
                 if ($detail['type'] == 'student') {
                     $data =  array();
                     $data['push_in_xero'] = 1;
                     $data['xero_log_id'] = $time;
                     $data['xero_student_responce'] = serialize($result2->getContacts());
                     $table = 'student';
                     $wher_column_name = 'id';
                     grid_data_updates($data,$table,$wher_column_name,$detail['id']);
                 }
                  
        }else{
                $file =  CURR_DIR.'application/logs/xero_request.log';
                ob_start();
                echo "<pre>";  
                echo "=====".$time.'============';      
                echo "\n==========  Get Contact ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
                print_r($detail);
                $res = ob_get_clean();
                file_put_contents($file, $res, FILE_APPEND);

                $detail['phone']['PhoneType'] =  'MOBILE';
                if (isset($detail['contactnumber']) && $detail['contactnumber'] != '') {
                    $detail['phone']['PhoneNumber'] =  $detail['contactnumber'];
                }
                $detail['phone']['PhoneAreaCode'] =  '';
                $detail['phone']['PhoneCountryCode'] =  '';
                $arr_phones = [];
                array_push($arr_phones, $detail['phone']);
                $detail['address'][0]['AddressType'] =  'STREET';
                if (isset($detail['addressline1']) && $detail['addressline1'] != '') {
                    $detail['address'][0]['AddressLine1'] =  $detail['addressline1'];
                }
                if (isset($detail['addressline2']) && $detail['addressline2'] != '') {
                    $detail['address'][0]['AddressLine2'] =  $detail['addressline2'];
                }
                if (isset($detail['addressline3']) && $detail['addressline3'] != '') {
                    $detail['address'][0]['AddressLine3'] =  $detail['addressline3'];
                }
                if (isset($detail['city']) && $detail['city'] != '') {
                    $detail['address'][0]['city'] =  $detail['city'];
                }
                if (isset($detail['postalcode']) && $detail['postalcode'] != '') {
                    $detail['address'][0]['PostalCode'] =  $detail['postalcode'];
                }
                $detail['address'][1]['AddressType'] =  'POBOX';
                if (isset($detail['billing_address']) && $detail['billing_address']) {
                    $detail['address'][1]['AddressLine1'] =  $detail['billing_address'];
                }
                if (isset($detail['billing_address_2']) && $detail['billing_address_2']) {
                    $detail['address'][1]['AddressLine2'] =  $detail['billing_address_2'];
                }
                if (isset($detail['billing_address_3']) && $detail['billing_address_3']) {
                    $detail['address'][1]['AddressLine3'] =  $detail['billing_address_3'];
                }
                $arr_address = [];
                array_push($arr_address, $detail['address'][0],$detail['address'][1]);
                $contact = new XeroAPI\XeroPHP\Models\Accounting\Contact;
                $contact->setName(isset($detail['contact_name']) ? $detail['contact_name'] : '')
                    ->setFirstName(isset($detail['first_name']) ? $detail['first_name'] : '')
                    ->setLastName(isset($detail['last_name']) ? $detail['last_name'] : '')
                    ->setPhones($arr_phones)
                    ->setAddresses($arr_address)
                    ->setEmailAddress(isset($detail['email']) ? $detail['email'] : '');
                $arr_contacts = [];
                array_push($arr_contacts, $contact);
                $contacts = new XeroAPI\XeroPHP\Models\Accounting\Contacts;
                $contacts->setContacts($arr_contacts);
                $apiResponse = $apiInstance->createContacts(XERO_ORGANISATION_ID,$contacts);
                $id = $apiResponse->getContacts()[0]->getContactId();
                $message = array();
                if (isset($apiResponse->getContacts()[0]->getValidationErrors()[0]['message'])) {
                   $file =  CURR_DIR.'application/logs/xero_error.log';
                   ob_start();
                   echo "<pre>";  
                   echo "=====".$time.'======';      
                   echo "\n========== Contact Add Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
                   print_r($apiResponse->getContacts());
                   echo "===========================================================================";
                   $res = ob_get_clean();
                   file_put_contents($file, $res, FILE_APPEND); 

                   $data =  array();
                   $data['xero_student_responce'] = serialize($apiResponse->getContacts());
                   $data['xero_log_id'] = $time;
                   $table = 'student';
                   $wher_column_name = 'id';
                   grid_data_updates($data,$table,$wher_column_name,$detail['id']);

                }else{
                   $file =  CURR_DIR.'application/logs/xero_response.log';
                   ob_start();
                   echo "<pre>";  
                   echo "=====".$time.'======';      
                   echo "\n========== Contact Add  ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
                   print_r($apiResponse->getContacts());
                   echo "===========================================================================";
                   $res = ob_get_clean();
                   file_put_contents($file, $res, FILE_APPEND); 
                   $data =  array();
                   $data['user_id'] = $detail['id'];
                   $data['table_id'] = $detail['id'];
                   $data['type'] = $detail['type'];
                   $data['company_id'] = 1 ;
                   $data['ContactID'] = $apiResponse->getContacts()[0]->getContactId();
                   $table = 'xero_contact';
                   grid_add_data($data,$table);
                   if ($detail['type'] == 'student') {
                       $data =  array();
                       $data['push_in_xero'] = 1;
                       $data['xero_student_responce'] = serialize($apiResponse->getContacts());
                       $data['xero_log_id'] = $time;
                       $table = 'student';
                       $wher_column_name = 'id';
                       grid_data_updates($data,$table,$wher_column_name,$detail['id']);
                   }
                }
        }
}

function xero_update_contact($detail) {
    $file =  CURR_DIR.'application/logs/xero_full_request.log';
    $time = time();
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Contact Edit ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 
    if(DISABLE_XERO){
        return false;
    }
    $file =  CURR_DIR.'application/logs/xero_request.log';
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Update Contact POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 

    $ci =& get_instance();
    $ci->load->library('xero'); 
    $apiInstance = $ci->xero->apiInstance;
    $detail['phone']['PhoneType'] =  'MOBILE';
    if (isset($detail['contactnumber']) && $detail['contactnumber'] != '') {
        $detail['phone']['PhoneNumber'] =  $detail['contactnumber'];
    }
    $detail['phone']['PhoneAreaCode'] =  '';
    $detail['phone']['PhoneCountryCode'] =  '';
    $arr_phones = [];
    array_push($arr_phones, $detail['phone']);
    $detail['address'][0]['AddressType'] =  'STREET';
    if (isset($detail['addressline1']) && $detail['addressline1'] != '') {
        $detail['address'][0]['AddressLine1'] =  $detail['addressline1'];
    }
    if (isset($detail['addressline2']) && $detail['addressline2'] != '') {
        $detail['address'][0]['AddressLine2'] =  $detail['addressline2'];
    }
    if (isset($detail['addressline3']) && $detail['addressline3'] != '') {
        $detail['address'][0]['AddressLine3'] =  $detail['addressline3'];
    }
    if (isset($detail['city']) && $detail['city'] != '') {
        $detail['address'][0]['city'] =  $detail['city'];
    }
    if (isset($detail['postalcode']) && $detail['postalcode'] != '') {
        $detail['address'][0]['PostalCode'] =  $detail['postalcode'];
    }
    $detail['address'][1]['AddressType'] =  'POBOX';
    if (isset($detail['billing_address']) && $detail['billing_address']) {
        $detail['address'][1]['AddressLine1'] =  $detail['billing_address'];
    }
    if (isset($detail['billing_address_2']) && $detail['billing_address_2']) {
        $detail['address'][1]['AddressLine2'] =  $detail['billing_address_2'];
    }
    if (isset($detail['billing_address_3']) && $detail['billing_address_3']) {
        $detail['address'][1]['AddressLine3'] =  $detail['billing_address_3'];
    }
    $arr_address = [];
    array_push($arr_address, $detail['address'][0],$detail['address'][1]);
    $contact = new XeroAPI\XeroPHP\Models\Accounting\Contact;
    if (isset($detail['contactid']) && $detail['contactid'] != '') {
        $contact->setName(isset($detail['contact_name']) ? $detail['contact_name'] : '')
            ->setContactId(isset($detail['contactid']) ? $detail['contactid'] : '')
            ->setFirstName(isset($detail['first_name']) ? $detail['first_name'] : '')
            ->setLastName(isset($detail['last_name']) ? $detail['last_name'] : '')
            ->setPhones($arr_phones)
            ->setAddresses($arr_address)
            ->setEmailAddress(isset($detail['email']) ? $detail['email'] : '');
     }
    $arr_contacts = [];
    array_push($arr_contacts, $contact);
    $contacts = new XeroAPI\XeroPHP\Models\Accounting\Contacts;
    $contacts->setContacts($arr_contacts);
    $apiResponse = $apiInstance->updateOrCreateContacts(XERO_ORGANISATION_ID,$contacts);
    if (isset($apiResponse->getContacts()[0]->getValidationErrors()[0]['message'])) {
       $file =  CURR_DIR.'application/logs/xero_error.log';
       ob_start();
       echo "<pre>";
       echo "=====".$time.'======';        
       echo "\n========== Contact Edit Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
       print_r($apiResponse->getContacts());
       echo "===========================================================================";
       $res = ob_get_clean();
       file_put_contents($file, $res, FILE_APPEND); 
       $data =  array();
       $data['xero_student_responce'] = serialize($apiResponse->getContacts());
       $data['xero_log_id'] = $time;
       $table = 'student';
       $wher_column_name = 'id';
       grid_data_updates($data,$table,$wher_column_name,$detail['id']);
    }else{
       $file =  CURR_DIR.'application/logs/xero_response.log';
       ob_start();
       echo "<pre>";  
       echo "=====".$time.'======';      
       echo "\n========== Contact Add  ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
       print_r($apiResponse->getContacts());
       echo "===========================================================================";
       $res = ob_get_clean();
       file_put_contents($file, $res, FILE_APPEND); 
       if ($detail['type'] == 'student') {
           $data =  array();
           $data['push_in_xero'] = 1;
           $data['xero_student_responce'] = serialize($apiResponse->getContacts());
           $data['xero_log_id'] = $time;
           $table = 'student';
           $wher_column_name = 'id';
           grid_data_updates($data,$table,$wher_column_name,$detail['id']);
       }
    }
}

function xero_add_singleinvoice($detail) {

      $file =  CURR_DIR.'application/logs/xero_full_request.log';
      $time = time();
      ob_start();
      echo "<pre>";  
      echo "=====".$time.'============';      
      echo "\n==========  Invoice Add  ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
      print_r($detail);
      echo "===========================================================================";
      $res = ob_get_clean();
      file_put_contents($file, $res, FILE_APPEND); 

      if(DISABLE_XERO){
          return false;
      }
      $ci =& get_instance();
      $ci->load->library('xero');
      $apiInstance = $ci->xero->apiInstance;

      $file =  CURR_DIR.'application/logs/xero_request.log';
      ob_start();
      echo "<pre>";  
      echo "=====".$time.'============';      
      echo "\n==========  Invoice Add POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
      print_r($detail);
      echo "===========================================================================";
      $res = ob_get_clean();
      file_put_contents($file, $res, FILE_APPEND); 

      $contact = new XeroAPI\XeroPHP\Models\Accounting\Contact;

      $contact->setContactId($detail['ContactID']);

      $center_id = isset($detail['invoice_detail']['invoice_master']['center_id']) ? $detail['invoice_detail']['invoice_master']['center_id'] : '1';
      $account_code = get_data_from_table('center_xero_account_setting',array('center_id' => $center_id));
      $xero_account = get_data_from_table('xero_account',array('table_id' => $account_code['invoice_account']));


      if (empty($xero_account)) {
           $file =  CURR_DIR.'application/logs/xero_error.log';
            ob_start();
            echo "<pre>";  
            echo "=====".$time.'============';      
            echo "\n==========  Invoice Add POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
            echo "Xero Accounting Setting Missing Pleace Xero Setting Add in Center Profile";
            echo "===========================================================================";
            $res = ob_get_clean();
            file_put_contents($file, $res, FILE_APPEND); 
            $data =  array();
            $data['xero_log_id'] = $time;
            $data['xero_invoice_responce'] = 'Xero Accounting Setting Missing Pleace Xero Setting Add in Center Profile.';
            $table = 'invoice';
            $wher_column_name = 'id';
            grid_data_updates($data,$table,$wher_column_name,$detail['invoice_detail']['invoice_master']['id']);
            return false;
      }


      $newAcct = getAccount(XERO_ORGANISATION_ID,$apiInstance,$xero_account['xero_account_id']);
      $xero_account_code = $newAcct->getAccounts()[0]->getCode();
      $dis_xero_account =get_data_from_table('xero_account',array('table_id' =>$account_code['discount_account']));
      if (empty($dis_xero_account)) {
           $file =  CURR_DIR.'application/logs/xero_error.log';
            ob_start();
            echo "<pre>";  
            echo "=====".$time.'============';      
            echo "\n==========  Invoice Add POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
            echo "Xero Discount Accounting Setting Missing Pleace Xero Setting Add in Center Profile";
            echo "===========================================================================";
            $res = ob_get_clean();
            file_put_contents($file, $res, FILE_APPEND); 

            $data =  array();
            $data['xero_invoice_responce'] = 'Xero Accounting Setting Missing Pleace Xero Setting Add in Center Profile.';
            $data['xero_log_id'] = $time;
            $table = 'invoice';
            $wher_column_name = 'id';
            grid_data_updates($data,$table,$wher_column_name,$detail['invoice_detail']['invoice_master']['id']);

            return false;
      }
      $disnewAcct = getAccount(XERO_ORGANISATION_ID,$apiInstance,$dis_xero_account['xero_account_id']);
      $discount_account_code = $disnewAcct->getAccounts()[0]->getCode();

      $gst = isset($invoice['gst'])? $invoice['gst'] : '';
      $gst_count = isset($detail['invoice_detail']['fees_detail']) ?  count($detail['invoice_detail']['fees_detail']) : '0';
      $gst = isset($invoice['gst'])? $invoice['gst']/$gst_count : '';
      $lineitems = [];
      if (isset($detail['invoice_detail']['invoice_master']['balance_amount']) && $detail['invoice_detail']['invoice_master'] ['balance_amount'] > 0) {
        if (isset($detail['invoice_detail']['fees_detail']) && count($detail['invoice_detail']['fees_detail']) > 0) {
            foreach ($detail['invoice_detail']['fees_detail'] as $key => $value) {

                if(isset($value['draft_fees_type']) && $value['draft_fees_type'] == 'D'){
                  $description = '';
                  $description = str_replace('<b>','',$value['fees_name']);
                  $description = str_replace('</b>','',$description);
                  $description = str_replace('<br>',' ',$description);
                  $lineitem = new XeroAPI\XeroPHP\Models\Accounting\LineItem;
                  $lineitem->setDescription(isset($description) ? $description : '')
                          ->setItemCode(isset($xero_course['xero_item_code']) ? $xero_course['xero_item_code'] : '')
                          ->setQuantity(isset($value['unit'])? $value['unit'] : '')
                          ->setUnitAmount(isset($value['amount'])? $value['amount'] : '')
                          ->setDiscountAmount(isset($value['discount_amount']) ? $value['discount_amount'] : 0 );
                          if (isset($value['tax_amount']) && $value['tax_amount'] > 0 && isset($value['xero_tax_type'])) {
                              $lineitem->setTaxType($value['xero_tax_type']);
                          }else{
                              $lineitem->setTaxType('NONE');
                          }
                  $lineitem->setLineAmount(isset($value['amount']) ? $value['amount'] : '')
                          ->setAccountCode('4060/0000');
                  array_push($lineitems,$lineitem);

                }elseif($value['draft_fees_type'] == 'AutoTagCN'){
                    $description = '';
                    $description = str_replace('<b>','',$value['fees_name']);
                    $description = str_replace('</b>','',$description);
                    $description = str_replace('<br>',' ',$description);
                    $lineitem = new XeroAPI\XeroPHP\Models\Accounting\LineItem;
                    $lineitem->setDescription(isset($description) ? $description : '')
                              ->setItemCode(isset($xero_course['xero_item_code']) ? $xero_course['xero_item_code'] : '')
                              ->setQuantity(isset($value['unit'])? $value['unit'] : '')
                              ->setUnitAmount(isset($value['amount'])? - $value['amount'] : '');
                              if (isset($value['tax_amount']) && $value['tax_amount'] > 0 && isset($value['xero_tax_type'])) {
                                  $lineitem->setTaxType($value['xero_tax_type']);
                              }else{
                                  $lineitem->setTaxType('NONE');
                              }
                      $lineitem->setLineAmount(isset($value['amount']) ? - $value['amount'] : '')
                              ->setAccountCode(isset($xero_account_code) ? $xero_account_code : '200');
                      array_push($lineitems,$lineitem);
                }else{
                      $description = '';
                      $description = str_replace('<b>','',$value['fees_name']);
                      $description = str_replace('</b>','',$description);
                      $description = str_replace('<br>',' ',$description);
                      $lineitem = new XeroAPI\XeroPHP\Models\Accounting\LineItem;
                      $lineitem->setDescription(isset($description) ? $description : '')
                              ->setItemCode(isset($xero_course['xero_item_code']) ? $xero_course['xero_item_code'] : '')
                              ->setQuantity(isset($value['unit'])? $value['unit'] : '')
                              ->setUnitAmount(isset($value['amount'])? $value['amount'] : '')
                              ->setDiscountAmount(isset($value['discount_amount']) ? $value['discount_amount'] : 0 );
                              if (isset($value['tax_amount']) && $value['tax_amount'] > 0 && isset($value['xero_tax_type'])) {
                                  $lineitem->setTaxType($value['xero_tax_type']);
                              }else{
                                  $lineitem->setTaxType('NONE');
                              }
                      $lineitem->setLineAmount(isset($value['amount']) ? $value['amount'] : '')
                              ->setAccountCode(isset($xero_account_code) ? $xero_account_code : '200');
                      array_push($lineitems,$lineitem);

                }

            }
        }


        if(isset($detail['invoice_detail']['invoice_discounts']) && count($detail['invoice_detail']['invoice_discounts']) > 0) {
            foreach ($detail['invoice_detail']['invoice_discounts'] as $key => $value) {
                  $lineitem = new XeroAPI\XeroPHP\Models\Accounting\LineItem;
                  $lineitem->setDescription(isset($value['title']) ? $value['title'] : '')
                          ->setQuantity('1')
                          ->setUnitAmount(isset($value['discount_amount'])? - $value['discount_amount'] : '')
                          ->setTaxType('NONE')
                          ->setLineAmount(isset($value['discount_subtotal']) ? - $value['discount_subtotal'] : '')
                          ->setAccountCode(isset($discount_account_code) ? $discount_account_code : '');
                  array_push($lineitems,$lineitem);
            }
        }

        if(isset($detail['invoice_detail']['invoice_master']['round_amount']) && $detail['invoice_detail']['invoice_master']['round_amount'] > 0) {
          $lineitem = new XeroAPI\XeroPHP\Models\Accounting\LineItem;
          $lineitem->setDescription('Rounding Amount')
                  ->setQuantity('1')
                  ->setUnitAmount(-$detail['invoice_detail']['invoice_master']['round_amount'])
                  ->setTaxType('NONE')
                  ->setLineAmount(-$detail['invoice_detail']['invoice_master']['round_amount'])
                  ->setAccountCode('860');
          array_push($lineitems,$lineitem);
        }


        if(isset($detail['invoice_detail']['invoice_master']['status']) && $detail['invoice_detail']['invoice_master']['status'] == '1'){
            $invoice_no = isset($detail['invoice_detail']['invoice_master']['invoice_no']) ? $detail['invoice_detail']['invoice_master']['invoice_no'] : '' ;
            $invoice = new XeroAPI\XeroPHP\Models\Accounting\Invoice;
            $invoice->setDueDate((isset($detail['invoice_detail']['invoice_master']['due_date']) && $detail['invoice_detail']['invoice_master']['due_date'] != '0000-00-00') ? $detail['invoice_detail']['invoice_master']['due_date'] : date('Y-m-d'))
                ->setContact($contact)
                ->setInvoiceNumber(isset($invoice_no) ? $invoice_no : '')
                ->setLineItems($lineitems)
                ->setTotalTax($detail['invoice_detail']['invoice_master']['gst_amount'])
                ->setReference(isset($detail['invoice_detail']['invoice_master']['remark'])? $detail['invoice_detail']['invoice_master']['remark'] : '')
                ->setType(XeroAPI\XeroPHP\Models\Accounting\Invoice::TYPE_ACCREC)
                ->setStatus(XeroAPI\XeroPHP\Models\Accounting\Invoice::STATUS_AUTHORISED)
                ->setLineAmountTypes(\XeroAPI\XeroPHP\Models\Accounting\LineAmountTypes::INCLUSIVE);

        }else{
            $invoice_no = 'Draft - '.time().' -'.str_pad($detail['invoice_detail']['invoice_master']['id'],5, 0,STR_PAD_LEFT);
            $invoice = new XeroAPI\XeroPHP\Models\Accounting\Invoice;
            $invoice->setDueDate((isset($detail['invoice_detail']['invoice_master']['due_date']) && $detail['invoice_detail']['invoice_master']['due_date'] != '0000-00-00') ? $detail['invoice_detail']['invoice_master']['due_date'] : date('Y-m-d'))
                ->setContact($contact)
                ->setInvoiceNumber(isset($invoice_no) ? $invoice_no : '')
                ->setLineItems($lineitems)
                ->setTotalTax($detail['invoice_detail']['invoice_master']['gst_amount'])
                ->setReference(isset($detail['invoice_detail']['invoice_master']['remark'])? $detail['invoice_detail']['invoice_master']['remark'] : '')
                ->setType(XeroAPI\XeroPHP\Models\Accounting\Invoice::TYPE_ACCREC)
                ->setStatus(XeroAPI\XeroPHP\Models\Accounting\Invoice::STATUS_DRAFT)
                ->setLineAmountTypes(\XeroAPI\XeroPHP\Models\Accounting\LineAmountTypes::INCLUSIVE);

        }
        $apiResponse = $apiInstance->createInvoices(XERO_ORGANISATION_ID,$invoice);



        if (isset($apiResponse->getInvoices()[0]->getValidationErrors()[0]['message'])) {
           $file =  CURR_DIR.'application/logs/xero_error.log';
           ob_start();
           echo "<pre>";    
           echo "=====".$time."=====";    
           echo "\n========== Invoice Add Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START ================================\n";
           print_r($apiResponse->getInvoices());
           echo "===========================================================================";
           $res = ob_get_clean();
           file_put_contents($file, $res, FILE_APPEND); 
           $data =  array();
           $data['xero_invoice_responce'] = serialize($apiResponse->getInvoices());
           $data['xero_log_id'] = $time;
           $table = 'invoice';
           $wher_column_name = 'id';
           grid_data_updates($data,$table,$wher_column_name,$detail['invoice_detail']['invoice_master']['id']);
        }else{
           $file =  CURR_DIR.'application/logs/xero_response.log';
           ob_start();
           echo "<pre>";  
           echo "=====".$time.'======';      
           echo "\n========== Invoice Add  ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
           print_r($apiResponse->getInvoices());
           echo "===========================================================================";
           $res = ob_get_clean();
           file_put_contents($file, $res, FILE_APPEND); 

           $data =  array();
           $data['push_in_xero'] = 1;
           $data['xero_log_id'] = $time;
           $data['InvoiceID'] = $apiResponse->getInvoices()[0]->getInvoiceId();
           if(isset($detail['invoice_detail']['invoice_master']['status']) && $detail['invoice_detail']['invoice_master']['status'] == '1'){
                $data['push_in_confrim_invoice'] = 1;
           }
           $data['xero_invoice_responce'] = serialize($apiResponse->getInvoices());
           $table = 'invoice';
           $wher_column_name = 'id';
           grid_data_updates($data,$table,$wher_column_name,$detail['invoice_detail']['invoice_master']['id']);
        }
      }else{
         $file =  CURR_DIR.'application/logs/xero_error.log';
         ob_start();
         echo "<pre>";    
         echo "=====".$time."=====";    
         echo "\n========== Nagitive Invoice Amount ============== START ----- ".date("Y-m-d H:i:s")." ----- START ================================\n";
         echo "===========================================================================";
         $res = ob_get_clean();
         file_put_contents($file, $res, FILE_APPEND); 
          $data =  array();
          $data['xero_invoice_responce'] = 'Nagitive Invoice Amount';
          $data['xero_log_id'] = $time;
          $table = 'invoice';
          $wher_column_name = 'id';
          grid_data_updates($data,$table,$wher_column_name,$detail['invoice_detail']['invoice_master']['id']);
      }
}

function xero_update_singleinvoice($detail) {
    $file =  CURR_DIR.'application/logs/xero_full_request.log';
    $time = time();
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Invoice  Update  ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 

    if(DISABLE_XERO){
        return false;
    }
    $ci =& get_instance();
    $ci->load->library('xero');
    $apiInstance = $ci->xero->apiInstance;

    $file =  CURR_DIR.'application/logs/xero_request.log';
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Invoice  POST Update ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 


    if (isset($detail['invoice_detail']['invoice_master']['balance_amount']) && $detail['invoice_detail']['invoice_master']['balance_amount'] > 0) {
          $contact = new XeroAPI\XeroPHP\Models\Accounting\Contact;
          $contact->setContactId($detail['ContactID']);
          $lineitems = [];
          $center_id = isset($detail['invoice_detail']['invoice_master']['center_id']) ? $detail['invoice_detail']['invoice_master']['center_id'] : '1';
          $account_code = get_data_from_table('center_xero_account_setting',array('center_id' => $center_id));
          $xero_account = get_data_from_table('xero_account',array('table_id' => $account_code['invoice_account']));

          if (empty($xero_account)) {
             $file =  CURR_DIR.'application/logs/xero_error.log';
              ob_start();
              echo "<pre>";  
              echo "=====".$time.'============';      
              echo "\n==========  Invoice Add POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
              echo "Xero Accounting Setting Missing Pleace Xero Setting Add in Center Profile";
              echo "===========================================================================";
              $res = ob_get_clean();
              file_put_contents($file, $res, FILE_APPEND); 
            $data =  array();
            $data['xero_invoice_responce'] = 'Xero Accounting Setting Missing Pleace Xero Setting Add in Center Profile.';
            $data['xero_log_id'] = $time;
            $table = 'invoice';
            $wher_column_name = 'id';
            grid_data_updates($data,$table,$wher_column_name,$detail['invoice_detail']['invoice_master']['id']);
              return false;
          }

          $newAcct = getAccount(XERO_ORGANISATION_ID,$apiInstance,$xero_account['xero_account_id']);
          $xero_account_code = $newAcct->getAccounts()[0]->getCode();

          $dis_xero_account =get_data_from_table('xero_account',array('table_id' =>$account_code['discount_account']));

          if (empty($dis_xero_account)) {
             $file =  CURR_DIR.'application/logs/xero_error.log';
              ob_start();
              echo "<pre>";  
              echo "=====".$time.'============';      
              echo "\n==========  Invoice Add POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
              echo "Xero Discount Accounting Setting Missing Pleace Xero Setting Add in Center Profile";
              echo "===========================================================================";
              $res = ob_get_clean();
              file_put_contents($file, $res, FILE_APPEND); 
            $data =  array();
            $data['xero_invoice_responce'] = 'Xero Accounting Setting Missing Pleace Xero Setting Add in Center Profile.';
            $data['xero_log_id'] = $time;
            $table = 'invoice';
            $wher_column_name = 'id';
            grid_data_updates($data,$table,$wher_column_name,$detail['invoice_detail']['invoice_master']['id']);
              return false;
          }

          $disnewAcct = getAccount(XERO_ORGANISATION_ID,$apiInstance,$dis_xero_account['xero_account_id']);
          $discount_account_code = $disnewAcct->getAccounts()[0]->getCode();

              //////////// Xero Invoice Start /////////////////

          if(isset($detail['invoice_detail']['fees_detail']) && count($detail['invoice_detail']['fees_detail']) > 0) {
                foreach ($detail['invoice_detail']['fees_detail'] as $key => $value) {
                      $description = '';
                      $description = str_replace('<b>','',$value['fees_name']);
                      $description = str_replace('</b>','',$description);
                      $description = str_replace('<br>',' ',$description);
                      $lineitem = new XeroAPI\XeroPHP\Models\Accounting\LineItem;
                      $lineitem->setDescription(isset($description) ? $description : '')
                              ->setItemCode(isset($xero_course['xero_item_code']) ? $xero_course['xero_item_code'] : '')
                              ->setQuantity(isset($value['unit'])? $value['unit'] : '')
                              ->setUnitAmount(isset($value['sub_total'])? $value['sub_total'] : '');
                              if (isset($value['tax_amount']) && $value['tax_amount'] > 0 && isset($value['xero_tax_type'])) {
                                  $lineitem->setTaxType($value['xero_tax_type']);
                              }else{
                                  $lineitem->setTaxType('NONE');
                              }
                      $lineitem->setLineAmount(isset($value['sub_total']) ? $value['sub_total'] : '')
                              ->setAccountCode(isset($xero_account_code) ? $xero_account_code : '');
                      array_push($lineitems,$lineitem);
                }
            }


          if(isset($detail['invoice_detail']['invoice_discounts']) && count($detail['invoice_detail']['invoice_discounts']) > 0) {
                foreach ($detail['invoice_detail']['invoice_discounts'] as $key => $value) {
                      $lineitem = new XeroAPI\XeroPHP\Models\Accounting\LineItem;
                      $lineitem->setDescription(isset($value['title']) ? $value['title'] : '')
                              ->setQuantity('1')
                              ->setUnitAmount(isset($value['discount_amount'])? - $value['discount_amount'] : '')
                              ->setTaxType('NONE')
                              ->setLineAmount(isset($value['discount_subtotal']) ? - $value['discount_subtotal'] : '')
                              ->setAccountCode(isset($discount_account_code) ? $discount_account_code : '');
                      array_push($lineitems,$lineitem);
                }
            }

          $invoice_no = 'Draft - '.time().' -'.str_pad($detail['invoice_detail']['invoice_master']['id'],5, 0,STR_PAD_LEFT);
          $invoice = new XeroAPI\XeroPHP\Models\Accounting\Invoice;

          $invoice->setInvoiceID(isset($detail['InvoiceID']) ? $detail['InvoiceID'] : '')
                  ->setLineItems($lineitems)
                  ->setTotalTax($detail['invoice_detail']['invoice_master']['gst_amount'])
                  ->setInvoiceNumber(isset($invoice_no) ? $invoice_no : '')
                  ->setType(XeroAPI\XeroPHP\Models\Accounting\Invoice::TYPE_ACCREC)
                  ->setContact($contact)
                 // ->setDueDate(isset($detail['invoice_detail']['invoice_master']['due_date'])? $detail['invoice_detail']['invoice_master']['due_date'] : '')
                  ->setLineAmountTypes(\XeroAPI\XeroPHP\Models\Accounting\LineAmountTypes::EXCLUSIVE)
                  ->setReference(isset($detail['invoice_detail']['invoice_master']['remark']) ? $detail['invoice_detail']['invoice_master']['remark'] : '')
                  ->setStatus(XeroAPI\XeroPHP\Models\Accounting\Invoice::STATUS_DRAFT);
          $apiResponse = $apiInstance->updateInvoice(XERO_ORGANISATION_ID,$detail['InvoiceID'],$invoice); 
          if (isset($apiResponse->getInvoices()[0]->getValidationErrors()[0]['message'])) {
             $file =  CURR_DIR.'application/logs/xero_error.log';
             ob_start();
             echo "<pre>";
             echo "=====".$time."======";        
             echo "\n==========  Invoice Edit Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
             print_r($apiResponse->getInvoices());
             $res = ob_get_clean();
             file_put_contents($file, $res, FILE_APPEND);
             $data =  array();
             $data['xero_log_id'] = $time;
             $data['xero_invoice_responce'] = serialize($apiResponse->getInvoices());
             $table = 'invoice';
             $wher_column_name = 'id';
             grid_data_updates($data,$table,$wher_column_name,$detail['invoice_detail']['invoice_master']['id']);
          }else{
             
             $file =  CURR_DIR.'application/logs/xero_response.log';
             ob_start();
             echo "<pre>";
             echo "=====".$time."======";        
             echo "\n==========  Invoice Edit  ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
             print_r($apiResponse->getInvoices());
             $res = ob_get_clean();
             file_put_contents($file, $res, FILE_APPEND);

             $data =  array();
             $data['push_in_xero'] = 1;
             $data['xero_log_id'] = $time;
             $data['xero_invoice_responce'] = serialize($apiResponse->getInvoices());
             $table = 'invoice';
             $wher_column_name = 'id';
             grid_data_updates($data,$table,$wher_column_name,$detail['invoice_detail']['invoice_master']['id']);
          }
    }else{
        $file =  CURR_DIR.'application/logs/xero_error.log';
        ob_start();
        echo "<pre>";    
        echo "=====".$time."=====";    
        echo "\n========== Nagitive Invoice Amount ============== START ----- ".date("Y-m-d H:i:s")." ----- START ================================\n";
        print_r($value);
        echo "===========================================================================";
        $res = ob_get_clean();
        file_put_contents($file, $res, FILE_APPEND); 
        $data =  array();
        $data['push_in_xero'] = 1;
        $data['xero_log_id'] = $time;
        $data['xero_invoice_responce'] = 'Nagitive Invoice Amount Not Acceptable in xero';
        $table = 'invoice';
        $wher_column_name = 'id';
        grid_data_updates($data,$table,$wher_column_name,$detail['invoice_detail']['invoice_master']['id']);
    }
}

function xero_confrim_single_draft_invoice($detail) {
    $file =  CURR_DIR.'application/logs/xero_full_request.log';
    $time = time();
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Invoice  Confrim  ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 

    if(DISABLE_XERO){
        return false;
    }

    $ci =& get_instance();
    $ci->load->library('xero');
    $apiInstance = $ci->xero->apiInstance;

    $file =  CURR_DIR.'application/logs/xero_request.log';
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Invoice  Confrim Update ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 

    if (isset($detail['invoice_detail']['invoice_master']['balance_amount']) && $detail['invoice_detail']['invoice_master']['balance_amount'] > 0 && isset($detail['InvoiceID']) && !empty($detail['InvoiceID'])) {
          $contact = new XeroAPI\XeroPHP\Models\Accounting\Contact;
          $contact->setContactId($detail['ContactID']);
          $invoice = new XeroAPI\XeroPHP\Models\Accounting\Invoice;
          $invoice->setInvoiceID(isset($detail['InvoiceID']) ? $detail['InvoiceID'] : '')
                  ->setType(XeroAPI\XeroPHP\Models\Accounting\Invoice::TYPE_ACCREC)
                  ->setContact($contact)
                  ->setInvoiceNumber(isset($detail['invoice_no']) ? $detail['invoice_no'] : '')
                  ->setLineAmountTypes(\XeroAPI\XeroPHP\Models\Accounting\LineAmountTypes::INCLUSIVE)
                  ->setReference(isset($detail['invoice_detail']['invoice_master']['remark']) ? $detail['invoice_detail']['invoice_master']['remark'] : '')
                  ->setStatus(XeroAPI\XeroPHP\Models\Accounting\Invoice::STATUS_AUTHORISED);
          $apiResponse = $apiInstance->updateInvoice(XERO_ORGANISATION_ID,$detail['InvoiceID'],$invoice); 

          if (isset($apiResponse->getInvoices()[0]->getValidationErrors()[0]['message'])) {
             $file =  CURR_DIR.'application/logs/xero_error.log';
             ob_start();
             echo "<pre>";
             echo "=====".$time."======";        
             echo "\n==========  Invoice Edit Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
             print_r($apiResponse->getInvoices());
             $res = ob_get_clean();
             file_put_contents($file, $res, FILE_APPEND);
             
             $data =  array();
             $data['xero_confirm_invoice_responce'] = serialize($apiResponse->getInvoices());
             $data['xero_confrim_log_id'] = $time;
             $table = 'invoice';
             $wher_column_name = 'id';
             grid_data_updates($data,$table,$wher_column_name,$detail['invoice_detail']['invoice_master']['id']);
          }else{
             $file =  CURR_DIR.'application/logs/xero_response.log';
             ob_start();
             echo "<pre>";
             echo "=====".$time."======";        
             echo "\n==========  Invoice Edit  ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
             print_r($apiResponse->getInvoices());
             $res = ob_get_clean();
             file_put_contents($file, $res, FILE_APPEND);
             $data =  array();
             $data['push_in_confrim_invoice'] = 1;
             $data['xero_confirm_invoice_responce'] = serialize($apiResponse->getInvoices());
             $data['xero_confrim_log_id'] = $time;
             $table = 'invoice';
             $wher_column_name = 'id';
             grid_data_updates($data,$table,$wher_column_name,$detail['invoice_detail']['invoice_master']['id']);
          }
    }else{
         $file =  CURR_DIR.'application/logs/xero_error.log';
         ob_start();
         echo "<pre>";    
         echo "=====".$time."=====";    
         echo "\n========== Negative Invoice Amount OR Missing Peramiter ============== START ----- ".date("Y-m-d H:i:s")." ----- START ================================\n";
         echo "===========================================================================";
         $res = ob_get_clean();
         file_put_contents($file, $res, FILE_APPEND); 
         $data =  array();
         $data['xero_confirm_invoice_responce'] = 'Negative Invoice Amount OR Missing Peramiter in Xero';
         $data['xero_confrim_log_id'] = $time;
         $table = 'invoice';
         $wher_column_name = 'id';
         grid_data_updates($data,$table,$wher_column_name,$detail['invoice_detail']['invoice_master']['id']);
    }
}

function xero_voided_invoice($detail) {
    $file =  CURR_DIR.'application/logs/xero_full_request.log';
    $time = time();
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Invoice Void Invoice  ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND);
    if(DISABLE_XERO){
        return false;
    }

    $ci =& get_instance();
    $ci->load->library('xero');
    $apiInstance = $ci->xero->apiInstance;

    $file =  CURR_DIR.'application/logs/xero_request.log';
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Void Invoice POST  ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 
    $contact = new XeroAPI\XeroPHP\Models\Accounting\Contact;
    $contact->setContactId($detail['ContactID']);
    $invoice = new XeroAPI\XeroPHP\Models\Accounting\Invoice;

    if(isset($detail['invoice_detail']['invoice_master']['void_date']) && $detail['invoice_detail']['invoice_master']['void_date'] == '0000-00-00 00:00:00'){
        $invoice->setInvoiceID(isset($detail['InvoiceID']) ? $detail['InvoiceID'] : '')
                ->setContact($contact)
                ->setReference('DRAFT Invoice Deleted')
                ->setStatus(XeroAPI\XeroPHP\Models\Accounting\Invoice::STATUS_DELETED)
                ->setType(XeroAPI\XeroPHP\Models\Accounting\Invoice::TYPE_ACCREC);
    }else{
        $invoice->setInvoiceID(isset($detail['InvoiceID']) ? $detail['InvoiceID'] : '')
                ->setContact($contact)
                ->setReference(isset($detail['invoice_detail']['invoice_master']['void_reason']) ? $detail['invoice_detail']['invoice_master']['void_reason'] : '')
                ->setStatus(XeroAPI\XeroPHP\Models\Accounting\Invoice::STATUS_VOIDED)
                ->setType(XeroAPI\XeroPHP\Models\Accounting\Invoice::TYPE_ACCREC);
    }
    $apiResponse = $apiInstance->updateInvoice(XERO_ORGANISATION_ID,$detail['InvoiceID'],$invoice); 
    if (isset($apiResponse->getInvoices()[0]->getValidationErrors()[0]['message'])) {
        $file =  CURR_DIR.'application/logs/xero_error.log';
        ob_start();
        echo "<pre>"; 
        echo "=====".$time."=======";      
        echo "\n========== Void Invoice Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START ==================================\n";
        print_r($apiResponse->getInvoices());
        $res = ob_get_clean();
        file_put_contents($file, $res, FILE_APPEND);
        $data =  array();
        $data['xero_cancel_invoice_responce'] = serialize($apiResponse->getInvoices());
        $data['xero_cancel_log_id'] = $time;
        $table = 'invoice';
        $wher_column_name = 'id';
        grid_data_updates($data,$table,$wher_column_name,$detail['invoice_detail']['invoice_master']['id']);
    }else{
        $file =  CURR_DIR.'application/logs/xero_response.log';
        ob_start();
        echo "=====".$time."=======";      
        echo "<pre>";        
        echo "\n========== Void Invoice  ============== START ----- ".date("Y-m-d H:i:s")." ----- START ====================================\n";
        print_r($apiResponse->getInvoices());
        $res = ob_get_clean();
        file_put_contents($file, $res, FILE_APPEND);
        $data =  array();
        $data['push_in_cancel_xero'] = 1;
        $data['xero_cancel_invoice_responce'] = serialize($apiResponse->getInvoices());
        $data['xero_cancel_log_id'] = $time;
        $table = 'invoice';
        $wher_column_name = 'id';
        grid_data_updates($data,$table,$wher_column_name,$detail['invoice_detail']['invoice_master']['id']);
    }
}

function xero_voided_credit_notes($detail){
    $file =  CURR_DIR.'application/logs/xero_full_request.log';
    $time = time();
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Void CreditNote  ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND);

    if(DISABLE_XERO){
        return false;
    }
    $ci =& get_instance();
    $ci->load->library('xero');
    $apiInstance = $ci->xero->apiInstance;

    $file =  CURR_DIR.'application/logs/xero_request.log';
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Void CreditNote ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 

    $creditnote = new XeroAPI\XeroPHP\Models\Accounting\CreditNote;
    $creditnote->setCreditNoteID($detail['CreditNoteID'])
        ->setReference(isset($detail['Reference']) ? $detail['Reference'] : '')
        ->setStatus(XeroAPI\XeroPHP\Models\Accounting\Invoice::STATUS_VOIDED);
    $apiResponse = $apiInstance->updateCreditNote(XERO_ORGANISATION_ID,$detail['CreditNoteID'],$creditnote);
    if (isset($apiResponse->getCreditNotes()[0]->getValidationErrors()[0]['message'])) {
       $file =  CURR_DIR.'application/logs/xero_error.log';
       ob_start();
       echo "<pre>";  
       echo "=====".$time."=======";      
       echo "\n========== Void Credit Note Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
       print_r($apiResponse->getCreditNotes());
       $res = ob_get_clean();
       file_put_contents($file, $res, FILE_APPEND);
       $data =  array();
       $data['xero_credit_note_responce'] = serialize($apiResponse->getCreditNotes());
       $data['xero_log_id'] = $time;
       $table = 'payment_credit_notes';
       $wher_column_name = 'id';
       grid_data_updates($data,$table,$wher_column_name,$detail['id']);
    }else{
        $file =  CURR_DIR.'application/logs/xero_response.log';
        ob_start();
        echo "=====".$time."=======";      
        echo "<pre>";        
        echo "\n========== Void Credit Note  ============== START ----- ".date("Y-m-d H:i:s")." ----- START ====================================\n";
        print_r($apiResponse->getCreditNotes());
        $res = ob_get_clean();
        file_put_contents($file, $res, FILE_APPEND);
       $data =  array();
       $data['push_in_cancel_xero'] = 1;
       $data['xero_credit_note_responce'] = serialize($apiResponse->getCreditNotes());
       $data['xero_log_id'] = $time;
       $table = 'payment_credit_notes';
       $wher_column_name = 'id';
       grid_data_updates($data,$table,$wher_column_name,$detail['id']);
    }
}

function xero_add_credit_notes($detail){
    $file =  CURR_DIR.'application/logs/xero_full_request.log';
    $time = time();
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Add CreditNote  ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND);
    if(DISABLE_XERO){
        return false;
    }
    $ci =& get_instance();
    $ci->load->library('xero');
    $apiInstance = $ci->xero->apiInstance;
    $file =  CURR_DIR.'application/logs/xero_request.log';
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n========== CreditNote Add ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND);

    $center_id = isset($detail['center_id']) ? $detail['center_id'] : '1';
    $account_code = get_data_from_table('center_xero_account_setting',array('center_id' => $center_id));
    $xero_account = '';
    if($detail['type'] == 1){
        $xero_account = get_data_from_table('xero_account',array('table_id'=> $account_code['credit_account_1']),'','','');
    }else{
        $xero_account = get_data_from_table('xero_account',array('table_id'=> $account_code['credit_account_2']),'','','');
    }
    $newAcct = getAccount(XERO_ORGANISATION_ID,$apiInstance,$xero_account['xero_account_id']);
    $credit_account_code = $newAcct->getAccounts()[0]->getCode();


     if (empty($credit_account_code)) {
         $file =  CURR_DIR.'application/logs/xero_error.log';
          ob_start();
          echo "<pre>";  
          echo "=====".$time.'============';      
          echo "\n==========  Invoice Add POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
          echo "Xero Credit Accounting Setting Missing Pleace Xero Setting Add in Center Profile";
          echo "===========================================================================";
          $res = ob_get_clean();
          file_put_contents($file, $res, FILE_APPEND); 
        $data =  array();
        $data['xero_credit_note_responce'] = 'Xero Accounting Setting Missing Pleace Xero Setting Add in Center Profile.';
        $data['xero_log_id'] = $time;
        $table = 'payment_credit_notes';
        $wher_column_name = 'id';
        grid_data_updates($data,$table,$wher_column_name,$detail['id']);
          return false;
    }

    $lineitem = new XeroAPI\XeroPHP\Models\Accounting\LineItem;
    $lineitem->setDescription(isset($detail['description']) ? $detail['description'] : '')
             ->setQuantity('1')
             ->setTaxType('NONE')
             ->setAccountCode(isset($credit_account_code) ? $credit_account_code : "200" )
             ->setUnitAmount(isset($detail['credit_amount']) ? $detail['credit_amount'] : '');
    $lineitems = [];
    array_push($lineitems,$lineitem);
    $contact = new XeroAPI\XeroPHP\Models\Accounting\Contact;
    $contact->setContactId($detail['ContactID']);
    $creditnote = new XeroAPI\XeroPHP\Models\Accounting\CreditNote;
    $creditnote->setDate(isset($detail['date']) ? $detail['date'] : '')
        ->setContact($contact)
        ->setCreditNoteNumber(isset($detail['credit_note_no']) ? $detail['credit_note_no'] : '')
        ->setLineItems($lineitems)
        ->setStatus(XeroAPI\XeroPHP\Models\Accounting\CreditNote::STATUS_AUTHORISED)
        ->setType(XeroAPI\XeroPHP\Models\Accounting\CreditNote::TYPE_ACCRECCREDIT);
    $apiResponse = $apiInstance->createCreditNotes(XERO_ORGANISATION_ID,$creditnote);
    $message = array();
    if (isset($apiResponse->getCreditNotes()[0]->getValidationErrors()[0]['message'])) {
       $file =  CURR_DIR.'application/logs/xero_error.log';
       ob_start();
       echo "<pre>";
       echo "======".$time."======";        
       echo "\n========== Add Credit Note Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
       print_r($apiResponse->getCreditNotes());
       $res = ob_get_clean();
       file_put_contents($file, $res, FILE_APPEND);
       
       $data =  array();
       $data['xero_CreditNoteID'] =$apiResponse->getCreditNotes()[0]->getCreditNoteId();
       $data['xero_credit_note_responce'] = serialize($apiResponse->getCreditNotes());
       $data['xero_log_id'] = $time;
       $table = 'payment_credit_notes';
       $wher_column_name = 'id';
       grid_data_updates($data,$table,$wher_column_name,$detail['id']);

    }else{
       $file =  CURR_DIR.'application/logs/xero_response.log';
       ob_start();
       echo "<pre>";
       echo "======".$time."======";        
       echo "\n========== Add Credit Note ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
       print_r($apiResponse->getCreditNotes());
       $res = ob_get_clean();
       file_put_contents($file, $res, FILE_APPEND);

       $data =  array();
       $data['push_in_xero'] = 1;
       $data['xero_CreditNoteID'] =$apiResponse->getCreditNotes()[0]->getCreditNoteId();
       $data['xero_credit_note_responce'] = serialize($apiResponse->getCreditNotes());
       $data['xero_log_id'] = $time;
       $table = 'payment_credit_notes';
       $wher_column_name = 'id';
       grid_data_updates($data,$table,$wher_column_name,$detail['id']);
    }
}

function getBankAccount($xeroTenantId,$apiInstance){
   if(DISABLE_XERO){
        return false;
    }
    $where = 'Status=="' . \XeroAPI\XeroPHP\Models\Accounting\Account::STATUS_ACTIVE .'" AND Type=="' .  \XeroAPI\XeroPHP\Models\Accounting\Account::BANK_ACCOUNT_TYPE_BANK . '"';
    $result = $apiInstance->getAccounts($xeroTenantId, null, $where); 
    return $result;
}

function getAccount($xeroTenantId,$apiInstance,$AccountID){
    if(DISABLE_XERO){
        return false;
    }
    $result = $apiInstance->getAccount($xeroTenantId,$AccountID); 
    return $result;
}

function getOrganisation($xeroTenantId,$apiInstance){
   if(DISABLE_XERO){
        return false;
    }
    $result = $apiInstance->getOrganisations($xeroTenantId);  
    return  $result->getOrganisations()[0]->getName();
}

function xero_voided_payment($detail) {
    $file =  CURR_DIR.'application/logs/xero_full_request.log';
    $time = time();
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Void Payment  ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND);
    if(DISABLE_XERO){
        return false;
    }
    $ci =& get_instance();
    $ci->load->library('xero');
    $apiInstance = $ci->xero->apiInstance;

    $file =  CURR_DIR.'application/logs/xero_request.log';
    $time = time();
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n========== Void Payment ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 

    $PaymentID = $detail['PaymentID'];
    $payment = new XeroAPI\XeroPHP\Models\Accounting\Payment;
    $payment->setStatus(XeroAPI\XeroPHP\Models\Accounting\Payment::STATUS_DELETED);
    $apiResponse = $apiInstance->deletePayment(XERO_ORGANISATION_ID,$PaymentID,$payment);
    if (isset($apiResponse->getPayments()[0]->getValidationErrors()[0]['message'])) {
          $file =  CURR_DIR.'application/logs/xero_error.log';
          ob_start();
          echo "<pre>";        
          echo "=====".$time."=======";
          echo "\n========== Void Payment Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
          print_r($apiResponse->getPayments());
          $res = ob_get_clean();
          file_put_contents($file, $res, FILE_APPEND);
          $data =  array();
          $data['payment_cancel_xero_responce'] = serialize($apiResponse->getPayments());
          $data['xero_cancel_log_id'] = $time;
          $table = 'payment';
          $wher_column_name = 'id';
          grid_data_updates($data,$table,$wher_column_name,$detail['id']);
    }else{
          $file =  CURR_DIR.'application/logs/xero_response.log';
          ob_start();
          echo "<pre>";        
          echo "=====".$time."=======";
          echo "\n========== Void Payment  ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
          print_r($apiResponse->getPayments());
          $res = ob_get_clean();
          file_put_contents($file, $res, FILE_APPEND);
          $data =  array();
          $data['push_in_cencel_xero'] = 1;
          $data['payment_cancel_xero_responce'] = serialize($apiResponse->getPayments());
          $data['xero_cancel_log_id'] = $time;
          $table = 'payment';
          $wher_column_name = 'id';
          grid_data_updates($data,$table,$wher_column_name,$detail['id']);
    }
}

function xero_make_payment($detail){
    $file =  CURR_DIR.'application/logs/xero_full_request.log';
    $time = time();
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Mack Payment  ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND);

   if(DISABLE_XERO){
        return false;
    }
    $ci =& get_instance();
    $ci->load->library('xero'); 
    $apiInstance = $ci->xero->apiInstance;

    $center_id = isset($detail['center_id']) ? $detail['center_id'] : '1';
    $account_code = get_data_from_table('center_xero_account_setting',array('center_id' => $center_id));
    $xero_account = get_data_from_table('xero_account',array('table_id'=> $account_code['bank_account']));


    if (empty($xero_account)) {
         $file =  CURR_DIR.'application/logs/xero_error.log';
          ob_start();
          echo "<pre>";  
          echo "=====".$time.'============';      
          echo "\n==========  Make payment POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
          echo "Xero Bank  Accounting Setting Missing Pleace Xero Setting Add in Center Profile";
          echo "===========================================================================";
          $res = ob_get_clean();
          file_put_contents($file, $res, FILE_APPEND); 
          $data =  array();
          $data['payment_xero_responce'] = 'Xero Accounting Setting Missing Pleace Xero Setting Add in Center Profile.';
          $data['xero_log_id'] = $time;
          $table = 'payment_invoice';
          $wher_column_name = 'id';
          grid_data_updates($data,$table,$wher_column_name,$detail['center_id']);
          return false;
      }


    $newAcct = getAccount(XERO_ORGANISATION_ID,$apiInstance,$xero_account['xero_account_id']);
    $bank_account_id = $newAcct->getAccounts()[0]->getAccountId();

    if (isset($bank_account_id) && !empty($bank_account_id) && isset($detail['Amount']) && $detail['Amount'] > 0 && isset($detail['InvoiceID']) && !empty($detail['InvoiceID'])) {
        $file =  CURR_DIR.'application/logs/xero_request.log';
        ob_start();
        echo "<pre>";  
        echo "=====".$time.'============';      
        echo "\n========== Mack Payment ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
        print_r($detail);
        $res = ob_get_clean();
        file_put_contents($file, $res, FILE_APPEND); 
        $Amount = $detail['Amount'];
        $date = $detail['Date'];
        $invoiceId = $detail['InvoiceID'];

        $invoice = new XeroAPI\XeroPHP\Models\Accounting\Invoice;
        $invoice->setInvoiceID($invoiceId);
        $bankaccount = new XeroAPI\XeroPHP\Models\Accounting\Account;
        $bankaccount->setAccountID($bank_account_id);
        $payment = new XeroAPI\XeroPHP\Models\Accounting\Payment;
        $payment->setInvoice($invoice)
            ->setAccount($bankaccount)
            ->setAmount($Amount)
            ->setReference(isset($detail['payment_ref']) ? $detail['payment_ref'] : '')
            ->setStatus(XeroAPI\XeroPHP\Models\Accounting\Payment::STATUS_AUTHORISED)
            ->setDate($date);
        $apiResponse = $apiInstance->createPayment(XERO_ORGANISATION_ID,$payment);
        if (isset($apiResponse->getPayments()[0]->getValidationErrors()[0]['message'])) {
            $file =  CURR_DIR.'application/logs/xero_error.log';
            ob_start();
            echo "<pre>";
            echo "====".$time."=====";        
            echo "\n==========  Mack Payment Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
            print_r($apiResponse->getPayments());
            $res = ob_get_clean();
            file_put_contents($file, $res, FILE_APPEND);
            
            $data =  array();
            $data['payment_xero_responce'] = serialize($apiResponse->getPayments());
            $data['xero_log_id'] = $time;
            $table = 'payment_invoice';
            $wher_column_name = 'id';
            grid_data_updates($data,$table,$wher_column_name,$detail['id']);
        }else{
            $file =  CURR_DIR.'application/logs/xero_response.log';
            ob_start();
            echo "<pre>";  
            echo "=====".$time.'============';      
            echo "\n==========  Mack Payment   ============== START ----- ".date("Y-m-d H:i:s")." ----- START =================================\n";
            print_r($apiResponse->getPayments());
            $res = ob_get_clean();
            file_put_contents($file, $res, FILE_APPEND); 

            $data =  array();
            $data['push_in_xero'] = 1;
            $data['PaymentID'] = $apiResponse->getPayments()[0]->getPaymentID();
            $data['payment_xero_responce'] = serialize($apiResponse->getPayments());
            $data['xero_log_id'] = $time;
            $table = 'payment_invoice';
            $wher_column_name = 'id';
            grid_data_updates($data,$table,$wher_column_name,$detail['id']);
        }
    }else{
          $file =  CURR_DIR.'application/logs/xero_error.log';
          ob_start();
          echo "<pre>";  
          echo "=====".$time."======";      
          echo "\n========== Make Payment Missing Peramiter ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
          print_r($detail);
          $res = ob_get_clean();
          file_put_contents($file, $res, FILE_APPEND);
          $data =  array();
          $data['payment_xero_responce'] = 'Make Payment Missing Peramiter';
          $data['xero_log_id'] = $time;
          $table = 'payment_invoice';
          $wher_column_name = 'id';
          grid_data_updates($data,$table,$wher_column_name,$detail['id']);
    }
}

function xero_make_multi_invoice_payment($detail){
    $file =  CURR_DIR.'application/logs/xero_full_request.log';
    $time = time();
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  Mack Payment  ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND);

   if(DISABLE_XERO){
        return false;
    }
    $ci =& get_instance();
    $ci->load->library('xero'); 
    $apiInstance = $ci->xero->apiInstance;

    $center_id = isset($detail['center_id']) ? $detail['center_id'] : '1';
    $account_code = get_data_from_table('center_xero_account_setting',array('center_id' => $center_id));
    $xero_account = get_data_from_table('xero_account',array('table_id'=> $account_code['bank_account']));


    if (empty($xero_account)) {
         $file =  CURR_DIR.'application/logs/xero_error.log';
          ob_start();
          echo "<pre>";  
          echo "=====".$time.'============';      
          echo "\n==========  Make payment POST ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
          echo "Xero Bank  Accounting Setting Missing Pleace Xero Setting Add in Center Profile";
          echo "===========================================================================";
          $res = ob_get_clean();
          file_put_contents($file, $res, FILE_APPEND); 
          $data =  array();
          $data['payment_xero_responce'] = 'Xero Accounting Setting Missing Pleace Xero Setting Add in Center Profile.';
          $data['xero_log_id'] = $time;
          $table = 'payment';
          $wher_column_name = 'id';
          grid_data_updates($data,$table,$wher_column_name,$detail['payment_id']);
          return false;
      }


    $newAcct = getAccount(XERO_ORGANISATION_ID,$apiInstance,$xero_account['xero_account_id']);
    $bank_account_id = $newAcct->getAccounts()[0]->getAccountId();

    if (isset($bank_account_id) && !empty($bank_account_id)) {
        $file =  CURR_DIR.'application/logs/xero_request.log';
        ob_start();
        echo "<pre>";  
        echo "=====".$time.'============';      
        echo "\n========== Mack Payment ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
        print_r($detail);
        $res = ob_get_clean();
        file_put_contents($file, $res, FILE_APPEND); 
       
        $bankaccount = new XeroAPI\XeroPHP\Models\Accounting\Account;
        $bankaccount->setAccountID($bank_account_id);

        /*echo "<pre>";
        print_r($detail);
        exit;*/


        $arr_payments = [];
        if(isset($detail['payment_list']) && count($detail['payment_list']) > 0) {
            foreach ($detail['payment_list'] as $key => $value) {
                $invoice = new XeroAPI\XeroPHP\Models\Accounting\Invoice;
                $invoice->setInvoiceID($value['invoice']);
                $payment_1 = new XeroAPI\XeroPHP\Models\Accounting\Payment;
                $payment_1->setInvoice($invoice)
                    ->setAccount($bankaccount)
                    ->setAmount($value['amount'])
                    ->setReference(isset($detail['payment_ref']) ? $detail['payment_ref'] : '')
                    ->setStatus(XeroAPI\XeroPHP\Models\Accounting\Payment::STATUS_AUTHORISED)
                    ->setDate(isset($detail['date']) ? $detail['date'] : '');
                array_push($arr_payments,$payment_1);
            }
        }
        $payments = new XeroAPI\XeroPHP\Models\Accounting\Payments;
        $payments->setPayments($arr_payments);
        $apiResponse = $apiInstance->createPayment(XERO_ORGANISATION_ID,$payments);

        if (isset($apiResponse->getPayments()[0]->getValidationErrors()[0]['message'])) {
            $file =  CURR_DIR.'application/logs/xero_error.log';
            ob_start();
            echo "<pre>";
            echo "====".$time."=====";        
            echo "\n==========  Mack Payment Error ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
            print_r($apiResponse->getPayments());
            $res = ob_get_clean();
            file_put_contents($file, $res, FILE_APPEND);
            
            $data =  array();
            $data['payment_xero_responce'] = serialize($apiResponse->getPayments());
            $data['xero_log_id'] = $time;
            $table = 'payment';
            $wher_column_name = 'id';
            grid_data_updates($data,$table,$wher_column_name,$detail['payment_id']);
        }else{
            $file =  CURR_DIR.'application/logs/xero_response.log';
            ob_start();
            echo "<pre>";  
            echo "=====".$time.'============';      
            echo "\n==========  Mack Payment   ============== START ----- ".date("Y-m-d H:i:s")." ----- START =================================\n";
            print_r($apiResponse->getPayments());
            $res = ob_get_clean();
            file_put_contents($file, $res, FILE_APPEND); 

            $data =  array();
            $data['push_in_xero'] = 1;
            $data['PaymentID'] = $apiResponse->getPayments()[0]->getPaymentID();
            $data['payment_xero_responce'] = serialize($apiResponse->getPayments());
            $data['xero_log_id'] = $time;
            $table = 'payment';
            $wher_column_name = 'id';
            grid_data_updates($data,$table,$wher_column_name,$detail['payment_id']);
        }
    }else{
          $file =  CURR_DIR.'application/logs/xero_error.log';
          ob_start();
          echo "<pre>";  
          echo "=====".$time."======";      
          echo "\n========== Make Payment Missing Peramiter ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
          print_r($detail);
          $res = ob_get_clean();
          file_put_contents($file, $res, FILE_APPEND);
          $data =  array();
          $data['payment_xero_responce'] = 'Make Payment Missing Peramiter';
          $data['xero_log_id'] = $time;
          $table = 'payment';
          $wher_column_name = 'id';
          grid_data_updates($data,$table,$wher_column_name,$detail['payment_id']);
    }
}

function xero_make_payment_credit_note($detail){

    $file =  CURR_DIR.'application/logs/xero_full_request.log';
    $time = time();
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n==========  CreditNote  Payment (Refund) ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND);

    if(DISABLE_XERO){
        return false;
    }
    $ci =& get_instance();
    $ci->load->library('xero'); 
    $apiInstance = $ci->xero->apiInstance;

    $file =  CURR_DIR.'application/logs/xero_request.log';
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n========== Mack Payment CreditNote ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 
    $Amount = $detail['Amount'];
    $date = $detail['Date'];
    $creditNoteID = $detail['creditNoteID'];
    $Reference = $detail['description'];
    $xero_account = '';
    $account_code = get_data_from_table('center_xero_account_setting',array('center_id' => $detail['center_id']));
    if($detail['type'] == 'refund'){
        $xero_account = get_data_from_table('xero_account',array('table_id'=> $account_code['credit_account_refund']));
    }else{
        $xero_account = get_data_from_table('xero_account',array('table_id'=> $account_code['credit_account_forfeit']));
    }
    $newAcct = getAccount(XERO_ORGANISATION_ID,$apiInstance,$xero_account['xero_account_id']);
    $bank_account_id = $newAcct->getAccounts()[0]->getAccountId();

    $creditnote = new XeroAPI\XeroPHP\Models\Accounting\CreditNote;
    $creditnote->setCreditNoteId($creditNoteID);
    $bankaccount = new XeroAPI\XeroPHP\Models\Accounting\Account;
    $bankaccount->setAccountID($bank_account_id);
    $payment = new XeroAPI\XeroPHP\Models\Accounting\Payment;
    $payment->setCreditNote($creditnote)
        ->setAccount($bankaccount)
        ->setReference($Reference)
        ->setAmount($Amount)
        ->setDate($date);
    $apiResponse = $apiInstance->createPayment(XERO_ORGANISATION_ID,$payment);
    if (isset($apiResponse->getPayments()[0]->getValidationErrors()[0]['message'])) {
        $file =  CURR_DIR.'application/logs/xero_error.log';
        ob_start();
        echo "<pre>";  
        echo "=====".$time."======";      
        echo "\n========== Mack Payment CreditNote  ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
        print_r($apiResponse->getPayments());
        $res = ob_get_clean();
        file_put_contents($file, $res, FILE_APPEND);

        $data =  array();
        $data['xero_responce'] = serialize($apiResponse->getPayments());
        $data['xero_log_id'] = $time;
        $table = 'student_refund';
        $wher_column_name = 'id';
        grid_data_updates($data,$table,$wher_column_name,$detail['id']);

    }else{

        $file =  CURR_DIR.'application/logs/xero_response.log';
        ob_start();
        echo "<pre>";  
        echo "=====".$time.'============';      
        echo "\n========== Mack Payment CreditNote  ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
        print_r($apiResponse->getPayments());
        $res = ob_get_clean();
        file_put_contents($file, $res, FILE_APPEND); 

        $data =  array();
        $data['push_in_xero'] = 1;
        $data['xero_log_id'] = $time;
        $data['xero_responce'] = serialize($apiResponse->getPayments());
        $data['xero_refund_id'] = $apiResponse->getPayments()[0]->getPaymentID();
        $table = 'student_refund';
        $wher_column_name = 'id';
        grid_data_updates($data,$table,$wher_column_name,$detail['id']);
    }
}

function xero_allocating_credit_notes($detail,$creditNoteID){
  $file =  CURR_DIR.'application/logs/xero_full_request.log';
  $time = time();
  ob_start();
  echo "<pre>";  
  echo "=====".$time.'============';      
  echo "\n========== Allocating CreditNote Payment ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
  print_r($detail);
  echo "===========================================================================";
  $res = ob_get_clean();
  file_put_contents($file, $res, FILE_APPEND);

   if(DISABLE_XERO){
        return false;
    }
    $ci =& get_instance();
    $ci->load->library('xero'); 
    $apiInstance = $ci->xero->apiInstance;
    $file =  CURR_DIR.'application/logs/xero_request.log';
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n========== Allocation CN ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    print_r($creditNoteID);
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND); 

    $creditnoteId = $creditNoteID;
    $creditnote = $apiInstance->getCreditNote(XERO_ORGANISATION_ID,$creditnoteId); 
    $invoice = new XeroAPI\XeroPHP\Models\Accounting\Invoice;
    $invoice->setInvoiceID($detail['InvoiceID']);
    $allocation = new XeroAPI\XeroPHP\Models\Accounting\Allocation;
     $allocation->setInvoice($invoice)
      ->setAmount($detail['Amount']);
    $apiResponse = $apiInstance->createCreditNoteAllocation(XERO_ORGANISATION_ID,$creditnoteId,$allocation);
    if (isset($apiResponse)) {
        $file =  CURR_DIR.'application/logs/xero_error.log';
        ob_start();
        echo "<pre>";  
        echo "=====".$time."======";      
        echo "\n========== Allocation CN  ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
        print_r($apiResponse->getAllocations());
        $res = ob_get_clean();
        file_put_contents($file, $res, FILE_APPEND);
        $data =  array();
        $data['xero_log_id'] = $time;
        $data['xero_responce'] = serialize($apiResponse->getAllocations());
        $table = 'invoice_auto_tag_credit_note';
       
        $wher_column_name = 'id';
        grid_data_updates($data,$table,$wher_column_name,$detail['id']);
    }else{
        $file =  CURR_DIR.'application/logs/xero_response.log';
        ob_start();
        echo "<pre>";  
        echo "=====".$time.'============';      
        echo "\n========== Allocation CN ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
        print_r($apiResponse->getAllocations());
        $res = ob_get_clean();
        file_put_contents($file, $res, FILE_APPEND); 
        $data =  array();
        $data['push_in_xero'] = 1;
        $data['xero_log_id'] = $time;
        $data['xero_allocation_id'] = serialize($apiResponse->getAllocations());
        $table = 'invoice_auto_tag_credit_note';
        $wher_column_name = 'id';
        grid_data_updates($data,$table,$wher_column_name,$detail['id']);
    }
}

function xero_delete_account($detail){
    $file =  CURR_DIR.'application/logs/xero_full_request.log';
    $time = time();
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n========== Delete Account ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    echo "===========================================================================";
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND);
    if(DISABLE_XERO){
        return false;
    }
    $ci =& get_instance();
    $ci->load->library('xero'); 
    $apiInstance = $ci->xero->apiInstance;

    $file =  CURR_DIR.'application/logs/xero_request.log';
    ob_start();
    echo "<pre>";  
    echo "=====".$time.'============';      
    echo "\n========== Account Delete ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
    print_r($detail);
    print_r($creditNoteID);
    $res = ob_get_clean();
    file_put_contents($file, $res, FILE_APPEND);
    $accountId = $detail['accountId'];
    $system_account_id = $detail['id'];
    $result = $apiInstance->deleteAccount(XERO_ORGANISATION_ID,$accountId);
    if ($result->getAccounts()[0]->getName()) {
        $file =  CURR_DIR.'application/logs/xero_response.log';
        ob_start();
        echo "<pre>";  
        echo "=====".$time.'============';      
        echo "\n========== Account Delete ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
        print_r($result->getAccounts());
        $res = ob_get_clean();
        file_put_contents($file, $res, FILE_APPEND);

      $where_1 = array('table_id' => $system_account_id,'company_id' => 1);
      $where_2 = array('id' => $system_account_id);
      delete('xero_account',$where_1);
      delete('account_code',$where_2);
    }else{
        $file =  CURR_DIR.'application/logs/xero_error.log';
        ob_start();
        echo "<pre>";  
        echo "=====".$time.'============';      
        echo "\n========== Account Delete ============== START ----- ".date("Y-m-d H:i:s")." ----- START =======================================\n";
        print_r($result->getAccounts());
        $res = ob_get_clean();
        file_put_contents($file, $res, FILE_APPEND);
    }
}

function xero_get_account($detail){
    if(DISABLE_XERO){
        return false;
    }
    $ci =& get_instance();
    $ci->load->library('xero'); 
    $apiInstance = $ci->xero->apiInstance; 
    $result = $apiInstance->getAccounts(XERO_ORGANISATION_ID);  
    if ($result->getAccounts()) {
      foreach ($result->getAccounts() as $key => $value) {
          $where = array('xero_account_id'=> $value->getAccountId(),'xero_account_code'=> $value->getCode());
          $xero_account = get_data_from_table('xero_account',$where); 
          if (empty($xero_account)  &&  (($value->getType() != 'BANK' && !empty($value->getCode())) || (!empty($value->getType() == 'BANK' && !empty($value->getName())) ) )) {
              $data = array();
              $data['code'] = !empty($value->getCode()) ? $value->getCode() : '';
              $data['name'] = !empty($value->getName()) ? $value->getName() : '';
              $data['description'] = !empty($value->getDescription()) ? $value->getDescription() : '';
              $data['type'] = !empty($value->getType()) ? $value->getType() : '';
              $data['tex_code'] = !empty($value->getTaxType()) ? $value->getTaxType() : '';
              $data['dashboard'] = !empty($value->getAddToWatchlist()) ? $value->getAddToWatchlist() : '';
              $data['expense_claims'] = !empty($value->getShowInExpenseClaims()) ? $value->getShowInExpenseClaims() : '';
              $data['enable_payments'] = !empty($value->getEnablePaymentsToAccount()) ? $value->getEnablePaymentsToAccount() : '';
              $data['created_date'] = date('Y-m-d H:s:i a');
              $data['status'] = 1 ;
              $account_code_id =  grid_add_data($data,'account_code');

              $data = array();
              $data['xero_account_id'] = $value->getAccountId();
              $data['xero_account_code'] = $value->getCode();
              $data['table_id'] = $account_code_id;
              $data['company_id'] = 1;
              $data['defult_xero'] = 1;
              $data['push_in_xero'] = 1;
              $account_code_id =  grid_add_data($data,'xero_account');
          }
      }
    }
}


?>