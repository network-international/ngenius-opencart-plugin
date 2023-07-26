<?php

/**
 * ControllerExtensionPaymentNgenius class
 */
class ControllerExtensionPaymentNgenius extends Controller
{

    /**
     *
     * @var array
     */
    private $error = array();
    const EXTENSION_DIR = "extension/payment/ngenius";
    const USER_TOKEN = "user_token=";
    const PAYMENT_TYPE = "&type=payment";
    const AMOUNT_LITERAL = "An amount ";

    /**
     * Index function
     */
    public function index()
    {
        $this->load->language(self::EXTENSION_DIR);

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');
        $this->load->model(self::EXTENSION_DIR);

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_ngenius', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect(
                $this->url->link(
                    self::EXTENSION_DIR,
                    self::USER_TOKEN . $this->session->data['user_token'] . self::PAYMENT_TYPE,
                    true
                )
            );
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs']   = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', self::USER_TOKEN . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link(
                'marketplace/extension',
                self::USER_TOKEN . $this->session->data['user_token'] . self::PAYMENT_TYPE,
                true
            )
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link(
                self::EXTENSION_DIR,
                self::USER_TOKEN . $this->session->data['user_token'],
                true
            )
        );

        $data['action'] = $this->url->link(
            self::EXTENSION_DIR,
            self::USER_TOKEN . $this->session->data['user_token'],
            true
        );
        $data['cancel'] = $this->url->link(
            'marketplace/extension',
            self::USER_TOKEN . $this->session->data['user_token'] . self::PAYMENT_TYPE,
            true
        );

        if (isset($this->request->post['payment_ngenius_title'])) {
            $data['payment_ngenius_title'] = $this->request->post['payment_ngenius_title'];
        } else {
            $data['payment_ngenius_title'] = $this->config->get('payment_ngenius_title');
        }

        if (isset($this->request->post['payment_ngenius_environment'])) {
            $data['payment_ngenius_environment'] = $this->request->post['payment_ngenius_environment'];
        } else {
            $data['payment_ngenius_environment'] = $this->config->get('payment_ngenius_environment');
        }

        $data['payment_environment'] = $this->model_setting_setting->getSettingValue('payment_ngenius_environment', 0);

        if (isset($this->request->post['payment_ngenius_tenant'])) {
            $data['payment_ngenius_tenant'] = $this->request->post['payment_ngenius_tenant'];
        } else {
            $data['payment_ngenius_tenant'] = $this->config->get('payment_ngenius_tenant');
        }

        if (isset($this->request->post['payment_ngenius_payment_action'])) {
            $data['payment_ngenius_payment_action'] = $this->request->post['payment_ngenius_payment_action'];
        } else {
            $data['payment_ngenius_payment_action'] = $this->config->get('payment_ngenius_payment_action');
        }

        $data['payment_actions'] = $this->model_setting_setting->getSettingValue('payment_ngenius_payment_action', 0);

        if (isset($this->request->post['payment_ngenius_outlet_ref'])) {
            $data['payment_ngenius_outlet_ref'] = $this->request->post['payment_ngenius_outlet_ref'];
        } else {
            $data['payment_ngenius_outlet_ref'] = $this->config->get('payment_ngenius_outlet_ref');
        }
        if (isset($this->request->post['payment_ngenius_api_key'])) {
            $data['payment_ngenius_api_key'] = $this->request->post['payment_ngenius_api_key'];
        } else {
            $data['payment_ngenius_api_key'] = $this->config->get('payment_ngenius_api_key');
        }

        if (isset($this->request->post['payment_ngenius_uat_api_url'])) {
            $data['payment_ngenius_uat_api_url'] = $this->request->post['payment_ngenius_uat_api_url'];
        } else {
            $data['payment_ngenius_uat_api_url'] = $this->config->get('payment_ngenius_uat_api_url');
        }

        if (isset($this->request->post['payment_ngenius_live_api_url'])) {
            $data['payment_ngenius_live_api_url'] = $this->request->post['payment_ngenius_live_api_url'];
        } else {
            $data['payment_ngenius_live_api_url'] = $this->config->get('payment_ngenius_live_api_url');
        }

        if (isset($this->request->post['payment_ngenius_order_status_id'])) {
            $data['payment_ngenius_order_status_id'] = $this->request->post['payment_ngenius_order_status_id'];
        } else {
            $data['payment_ngenius_order_status_id'] = $this->config->get('payment_ngenius_order_status_id');
        }

        $data['order_statuses'] = array(['name' => 'N-Genius Pending', 'value' => 'ngenius_pending']);

        if (isset($this->request->post['payment_ngenius_status'])) {
            $data['payment_ngenius_status'] = $this->request->post['payment_ngenius_status'];
        } else {
            $data['payment_ngenius_status'] = $this->config->get('payment_ngenius_status');
        }

        if (isset($this->request->post['payment_ngenius_extra_currency'])) {
            $data['payment_ngenius_extra_currency'] = $this->request->post['payment_ngenius_extra_currency'];
        } else {
            $data['payment_ngenius_extra_currency'] = $this->config->get('payment_ngenius_extra_currency');
        }

        if (isset($this->request->post['payment_ngenius_extra_outlet'])) {
            $data['payment_ngenius_extra_outlet'] = $this->request->post['payment_ngenius_extra_outlet'];
        } else {
            $data['payment_ngenius_extra_outlet'] = $this->config->get('payment_ngenius_extra_outlet');
        }

        if (isset($this->request->post['payment_ngenius_debug'])) {
            $data['payment_ngenius_debug'] = $this->request->post['payment_ngenius_debug'];
        } else {
            $data['payment_ngenius_debug'] = $this->config->get('payment_ngenius_debug');
        }

        if (isset($this->request->post['payment_ngenius_sort_order'])) {
            $data['payment_ngenius_sort_order'] = $this->request->post['payment_ngenius_sort_order'];
        } else {
            $data['payment_ngenius_sort_order'] = $this->config->get('payment_ngenius_sort_order');
        }

        $url              = new Url(HTTP_CATALOG, $this->config->get('config_secure') ? HTTP_CATALOG : HTTPS_CATALOG);
        $data['cron_url'] = $url->link('extension/payment/ngenius/cron');

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view(self::EXTENSION_DIR, $data));
    }

    /**
     * Install Function for inserting Ngenius NetworkInternational Table
     */
    public function install()
    {
        $this->load->model('localisation/order_status');
        $this->load->library('ngenius');

        $results                     = $this->model_localisation_order_status->getOrderStatuses();
        $data_result["order_status"] = $this->ngenius->ngeniusOrderStatus();

        foreach ($data_result["order_status"] as $key => $val) {
            $data["order_status"] = $val;
            if (!in_array($data["order_status"][1]['name'], array_column($results, 'name'))) {
                $this->model_localisation_order_status->addOrderStatus($data);
            }
        }
        $this->load->model(self::EXTENSION_DIR);
        $this->model_extension_payment_ngenius->install();
    }

    /**
     * Uninstall the Ngenius NetworkInternational Table
     */
    public function uninstall()
    {
        $this->load->model(self::EXTENSION_DIR);
        $this->model_extension_payment_ngenius->uninstall();
    }

    /**
     * Adding ngenius tab
     * @return array
     */
    public function order()
    {
        $this->load->language(self::EXTENSION_DIR);

        $data['user_token'] = $this->session->data['user_token'];
        $data['order_id']   = $this->request->get['order_id'];

        return $this->load->view('extension/payment/ngenius_order', $data);
    }

    /**
     * Get transaction details
     */
    public function getTransaction()
    {
        $this->load->language(self::EXTENSION_DIR);
        $this->load->model(self::EXTENSION_DIR);
        $this->load->library('ngenius');

        $data = $this->model_extension_payment_ngenius->getOrder($this->request->get['order_id']);

        $data['customer_transaction'] = array();
        $customerTransaction          = $this->model_extension_payment_ngenius->getCustomerTransaction(
            $this->request->get['order_id']
        );

        foreach ($customerTransaction as $key => $transaction) {
            $jsonData = json_decode($transaction['description'], true);
            if ($jsonData) {
                foreach ($jsonData as $key => $value) {
                    $transaction['amount'] = $data['currency'] . number_format($transaction['amount'], 2);
                    $transaction['id']     = $value;
                    switch ($key) {
                        case 'captureId':
                            if ($data['captured_amt'] > 0 && !in_array(
                                    $data['status'],
                                    array(
                                        $this->ngenius::NG_AUTHORISED,
                                        $this->ngenius::NG_AUTH_REV
                                    )
                                )) {
                                $transaction['refund_button'] = true;
                            }
                            $transaction['message'] = 'Transaction: Captured';
                            break;
                        case 'refundedId':
                            $transaction['message'] = 'Transaction: Refunded';
                            break;
                        case 'AuthId':
                            $transaction['message'] = 'Transaction: Authorised';
                            break;
                        case 'voidId':
                            $transaction['message'] = 'Transaction: Auth Reversed';
                            break;
                        default:
                            break;
                    }
                    $data['transactions'][] = $transaction;
                }
            }
        }

        if (($data['action'] === 'PURCHASE' && ($data['state'] === 'PURCHASED' || $data["state"] === "PARTIALLY_REFUNDED"))
            || ($data['action'] === 'SALE' && ($data['state'] === 'CAPTURED' || $data["state"] === "PARTIALLY_REFUNDED"))) {
            $data["transactions"][sizeof($customerTransaction)-1]['refund_button'] = true;
        }

        $data['max_refund_amount']  = number_format($data['captured_amt'], 2);
        $data['max_capture_amount'] = number_format($data['amount'], 2);
        $data['amount']             = $data['currency'] . number_format($data['amount'], 2);
        $data['captured_amt']       = $data['currency'] . number_format($data['captured_amt'], 2);
        $data['is_authorised']      = ($this->ngenius::NG_AUTHORISED === $data['status']) ? true : false;
        $data['user_token']         = $this->session->data['user_token'];
        $this->response->setOutput($this->load->view('extension/payment/ngenius_order_ajax', $data));
    }

    /**
     * Transaction command: void,capture and refund
     */
    public function transactionCommand()
    {
        $this->load->language(self::EXTENSION_DIR);
        $this->load->model(self::EXTENSION_DIR);
        $this->load->model('sale/order');
        $this->load->library('ngenius');
        $json = [];

        $request  = new \Ngenius\Request($this->ngenius);
        $transfer = new \Ngenius\Transfer();
        $http     = new \Ngenius\Http();
        $validate = new \Ngenius\Validate();

        $token = $http->placeRequest($transfer->forToken($request->tokenRequest()));
        if (is_string($token)) {
            $token = $validate->tokenValidate($token);
        } else {
            $json['error'] = $token['error'];
        }
        $data_table = [];
        $data       = $this->model_extension_payment_ngenius->getOrder($this->request->post['order_id']);
        $order      = $this->model_sale_order->getOrder($this->request->post['order_id']);

        if (!empty($token) && is_string($token)) {
            if ($this->request->post['type'] == 'void') {
                $orderInfo = $validate->voidValidate(
                    $http->placeRequest(
                        $transfer->create($request->voidOrder($data['reference'], $data['payment_id']), $token)
                    )
                );

            if (is_array($orderInfo)) {
                if ('REVERSED' === $orderInfo['state']) {
                    $data_table['state']  = $orderInfo['state'];
                    $data_table['status'] = $this->ngenius::NG_AUTH_REV;

                        $this->model_extension_payment_ngenius->updateTable($data_table, $order['order_id']);
                        $captureIdArr['voidId'] = 'NA';
                        $this->model_extension_payment_ngenius->addTransaction(
                            $order['customer_id'],
                            json_encode($captureIdArr),
                            '',
                            $order['order_id']
                        );

                        //Add to history table
                        $order_status_id = $this->model_extension_payment_ngenius->getNgeniusStatusId(
                            $data_table['status']
                        );
                        $json['success'] = 'The void transaction processed successfully.';
                        $this->model_extension_payment_ngenius->addOrderHistory(
                            $order['order_id'],
                            $order_status_id,
                            $json['success'],
                            false
                        );
                    }
                } else {
                    $json['error'] = $orderInfo;
                }
            } elseif ($this->request->post['type'] == 'capture' && $this->request->post['amount']) {
                $orderInfo = $validate->captureValidate(
                    $http->placeRequest(
                        $transfer->create($request->captureOrder($data, $this->request->post['amount']), $token)
                    )
                );

                if (is_array($orderInfo)) {
                    $data_table['state'] = $orderInfo['state'];
                    if ('PARTIALLY_CAPTURED' === $orderInfo['state']) {
                        $data_table['status'] = $this->ngenius::NG_P_CAPTURED;
                    } else {
                        $data_table['status'] = $this->ngenius::NG_F_CAPTURED;
                    }
                    $data_table['captured_amt'] = $orderInfo['total_captured'];
                    $this->model_extension_payment_ngenius->updateTable($data_table, $order['order_id']);

                    $captureIdArr['captureId'] = $orderInfo['transaction_id'];
                    $this->model_extension_payment_ngenius->addTransaction(
                        $order['customer_id'],
                        json_encode($captureIdArr),
                        $orderInfo['captured_amt'],
                        $order['order_id']
                    );
                    $json['success'] = self::AMOUNT_LITERAL . $order['currency_code'] . number_format(
                            $orderInfo['captured_amt'],
                            2
                        ) . ' captured successfully.';

                    //Add to history table
                    $order_status_id = $this->model_extension_payment_ngenius->getNgeniusStatusId(
                        $data_table['status']
                    );
                    $this->model_extension_payment_ngenius->addOrderHistory(
                        $order['order_id'],
                        $order_status_id,
                        $json['success'],
                        false
                    );
                } else {
                    $json['error'] = $orderInfo;
                }
            } elseif ($data['action'] === 'SALE' && $this->request->post['type'] == 'refund' && $this->request->post['amount'] && $this->request->post['capture_id']) {

                $refundUrl = null;
                if ($data["payment_id"] === $this->request->post['capture_id']) {
                    $cupPayment = $validate->orderValidate(
                        $http->placeRequest($transfer->create($request->fetchOrder($data['reference']), $token))
                    );
                    if(isset($cupPayment["_embedded"]["payment"][0]["_embedded"]["cnp:capture"][0]["_links"]["cnp:refund"]["href"])) {
                        $refundUrl = $cupPayment["_embedded"]["payment"][0]["_embedded"]["cnp:capture"][0]["_links"]["cnp:refund"]["href"];
                    }
                }
                $orderInfo = $validate->refundValidate(
                    $http->placeRequest(
                        $transfer->create(
                            $request->refundOrder(
                                $data,
                                $this->request->post['amount'],
                                $this->request->post['capture_id'],
                                $refundUrl
                            ),
                            $token
                        )
                    )
                );
                if (is_array($orderInfo)) {
                    $data_table['state'] = $orderInfo['state'];
                    if ($orderInfo['total_refunded'] === $orderInfo['captured_amt']) {
                        $data_table['status'] = $this->ngenius::NG_F_REFUNDED;

                        //Reverse quantity
                        $order_products = $this->model_sale_order->getOrderProducts($order['order_id']);
                        $this->model_extension_payment_ngenius->updateProduct($order_products);
                    } else {
                        $data_table['status'] = $this->ngenius::NG_P_REFUNDED;
                    }

                    $data_table['captured_amt'] = $orderInfo['captured_amt'] - $orderInfo['total_refunded'];
                    $this->model_extension_payment_ngenius->updateTable($data_table, $order['order_id']);

                    $captureIdArr['refundedId'] = $orderInfo['transaction_id'];
                    $this->model_extension_payment_ngenius->addTransaction(
                        $order['customer_id'],
                        json_encode($captureIdArr),
                        $this->request->post['amount'],
                        $order['order_id']
                    );
                    $json['success'] = self::AMOUNT_LITERAL . $order['currency_code'] . number_format(
                            $orderInfo['refunded_amt'],
                            2
                        ) . ' refunded successfully.';

                    //Add to history table
                    $order_status_id = $this->model_extension_payment_ngenius->getNgeniusStatusId(
                        $data_table['status']
                    );
                    $this->model_extension_payment_ngenius->addOrderHistory(
                        $order['order_id'],
                        $order_status_id,
                        $json['success'],
                        false
                    );
                } else {
                    $json['error'] = $orderInfo;
                }
            } elseif ($data['action'] === 'PURCHASE' && $this->request->post['type'] == 'refund' && $this->request->post['amount'] && $this->request->post['capture_id']) {
                // Get the ngenius order detail
                $orderStatus = $http->placeRequest(
                    $transfer->create(
                        $request->orderStatus($data),
                        $token
                    )
                );
                $orderStatus = json_decode($orderStatus, true);
                if ($orderStatus['_embedded']['payment'][0]["paymentMethod"]["name"] === "CHINA_UNION_PAY") {
                    $json['error'] = "This order is non-refundable";
                } elseif (isset($orderStatus['_embedded']['payment'][0]['_links']['cnp:refund'])) {
                    $refundLink = $orderStatus['_embedded']['payment'][0]['_links']['cnp:refund']['href'];
                    // If we have a refund link then the amount has been captured and can be refunded as normal
                    $data['uri'] = $refundLink;
                    $orderInfo   = $validate->refundValidate(
                        $http->placeRequest(
                            $transfer->create(
                                $request->refundPurchase(
                                    $data,
                                    $this->request->post['amount']
                                ),
                                $token
                            )
                        )
                    );
                } elseif (isset($orderStatus['_embedded']['cnp:capture'][0]['_links']['cnp:refund'])) {
                    $refundLink = $orderStatus['_embedded']['cnp:capture'][0]['_links']['cnp:refund']['href'];
                    // If we have a refund link then the amount has been captured and can be refunded as normal
                    $data['uri'] = $refundLink;
                    $orderInfo   = $validate->refundValidate(
                        $http->placeRequest(
                            $transfer->create(
                                $request->refundPurchase(
                                    $data,
                                    $this->request->post['amount']
                                ),
                                $token
                            )
                        )
                    );
                } else {
                    $orderInfo = $validate->refundValidate(
                        $http->placeRequest(
                            $transfer->create(
                                $request->voidPurchase(
                                    $data,
                                    $this->request->post['amount'],
                                    $this->request->post['capture_id']
                                ),
                                $token
                            )
                        )
                    );
                }
                if (is_array($orderInfo) && !isset($json['error'])) {
                    $data_table['state'] = $orderInfo['state'];
                    if ($orderInfo['total_refunded'] === $orderInfo['captured_amt']) {
                        $data_table['status'] = $this->ngenius::NG_F_REFUNDED;

                        //Reverse quantity
                        $order_products = $this->model_sale_order->getOrderProducts($order['order_id']);
                        $this->model_extension_payment_ngenius->updateProduct($order_products);
                    } else {
                        $data_table['status'] = $this->ngenius::NG_P_REFUNDED;
                    }

                    $data_table['captured_amt'] = $orderInfo['captured_amt'] - $orderInfo['total_refunded'];
                    $this->model_extension_payment_ngenius->updateTable($data_table, $order['order_id']);

                    $captureIdArr['refundedId'] = $orderInfo['transaction_id'];
                    $this->model_extension_payment_ngenius->addTransaction(
                        $order['customer_id'],
                        json_encode($captureIdArr),
                        $this->request->post['amount'],
                        $order['order_id']
                    );
                    $json['success'] = self::AMOUNT_LITERAL . $order['currency_code'] . number_format(
                            $orderInfo['refunded_amt'],
                            2
                        ) . ' refunded successfully.';

                    //Add to history table
                    $order_status_id = $this->model_extension_payment_ngenius->getNgeniusStatusId(
                        $data_table['status']
                    );
                    $this->model_extension_payment_ngenius->addOrderHistory(
                        $order['order_id'],
                        $order_status_id,
                        $json['success'],
                        false
                    );
                } else {
                    $json['error'] = $orderInfo;
                }
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Validation
     * @return array
     */
    protected function validate()
    {
        if (!$this->user->hasPermission('modify', self::EXTENSION_DIR)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

}
