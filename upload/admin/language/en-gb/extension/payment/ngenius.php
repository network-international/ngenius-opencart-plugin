<?php

// Heading
$_['heading_title'] = 'N-Genius Payment Gateway - 1.0.1';

// Text
$_['text_extension'] = 'Extensions';
$_['text_success']   = 'Success: You have modified Ngenius payment module!';
$_['text_edit']      = 'Edit';
$_['text_ngenius']   = '<img src="view/image/payment/ngenius.jpg" alt="n-genius" title="n-genius" style="border: 1px solid #EEEEEE;" />';

// Entry
$_['order_status'] = 'Status of new order';
$_['status']       = 'Status';
$_['sort_order']   = 'Sort Order';

$_['title']                      = 'Title';
$_['environment']                = 'Environment';
$_['uat']                        = 'Sandbox';
$_['live']                       = 'Live';
$_['text_uat']                   = 'uat';
$_['text_live']                  = 'live';
$_['tenant']                     = 'Tenant';
$_['network_international']      = 'Network International';
$_['text_network_international'] = 'networkinternational';
$_['authorize']                  = 'Authorize';
$_['sale']                       = 'Sale';
$_['purchase']                   = 'Purchase';
$_['text_authorize']             = 'authorize';
$_['text_sale']                  = 'sale';
$_['text_purchase']              = 'purchase';
$_['payment_action']             = 'Payment Action';
$_['outlet_ref']                 = 'Outlet Reference ID';
$_['api_key']                    = 'API Key';
$_['debug']                      = 'Debug Log';
$_['text_debug_yes']             = 'Yes';
$_['text_debug_no']              = 'No';
$_['text_title']                 = 'N-Genius Payment Gateway';
$_['uat_api_url_text']           = 'Sandbox API URL';
$_['uat_api_url_value']          = 'fggdfgfdgdfgfdgdfgfdgdfg';
$_['live_api_url_text']          = 'Live API URL';
$_['live_api_url_value']         = 'fggdfgfdgdfgfdgdfgfdgdfg';

// Multi-currency support
$_['add_outlet_ref']            = 'Add Additional Outlet Reference IDs (Optional)';
$_['add_outlet_button_text']    = 'Add Outlet Reference ID';
$_['remove_outlet_button_text'] = 'Delete Reference';

//column name
$_['column_order_id']                         = 'Order Id';
$_['column_amount']                           = 'Amount';
$_['column_currency']                         = 'Currency';
$_['column_reference']                        = 'Reference';
$_['column_action']                           = 'Action';
$_['column_state']                            = 'State';
$_['column_status']                           = 'Status';
$_['column_payment_id']                       = 'Payment Id';
$_['column_captured_amt']                     = 'Captured Amount';
$_['column_created_at']                       = 'Created Date';
$_['column_transactions']                     = 'Transactions';
$_['column_refund_history']                   = 'Refund History';
$_['column_customer_transaction_id']          = 'Id';
$_['column_customer_transaction_amount']      = 'Amount';
$_['column_customer_transaction_capture_id']  = 'Transaction Id';
$_['column_customer_transaction_refunded_id'] = 'Refunded Id';
$_['column_customer_transaction_action']      = 'Status / Actions';
$_['column_customer_transaction_date']        = 'Date';
$_['column_void']                             = 'Void';
$_['column_capture']                          = 'Capture';
$_['column_refund']                           = 'Refund';
$_['column_date']                             = 'Date';


// Button
$_['button_void']    = 'Void';
$_['button_capture'] = 'Capture';
$_['button_refund']  = 'Refund';
$_['button_filter']  = 'Filter';
$_['text_na']        = 'N/A';


$_['text_confirm_void']    = 'Are you sure you want to void this transaction?';
$_['text_confirm_capture'] = 'Are you sure you want to capture';
$_['text_confirm_refund']  = 'Are you sure you want to refund';

//cron text
$_['text_cron_heading']     = 'N-GENIUS CRON TASK';
$_['text_cron_description'] = 'Please add the below cron job in your cron module or server.<br/>
                               This cron will run the Query API to retrieve the status of incomplete requests from N-Genius and update the order status in Opencart.<br/>
                               It is recommended to run this cron every 60 minutes.';
