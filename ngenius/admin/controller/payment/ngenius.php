<?php

/**
 * ControllerExtensionPaymentNgenius class
 */

namespace Opencart\Admin\Controller\Extension\Ngenius\Payment;

use Ngenius\NgeniusCommon\NgeniusHTTPCommon;
use Ngenius\NgeniusCommon\NgeniusHTTPTransfer;
use Opencart\System\Engine\Controller;
use Opencart\System\Library\Tools\Request;
use Opencart\System\Library\Tools\Validate;

require_once DIR_EXTENSION . 'ngenius/system/library/vendor/autoload.php';
require_once DIR_EXTENSION . 'ngenius/system/library/ngenius.php';
require_once DIR_EXTENSION . 'ngenius/system/library/tools/request.php';
require_once DIR_EXTENSION . 'ngenius/system/library/tools/validate.php';

class Ngenius extends Controller
{
    /**
     *
     * @var array
     */
    private array $error = array();
    public const EXTENSION_DIR = "extension/ngenius/payment/ngenius";
    public const USER_TOKEN = "user_token=";
    public const PAYMENT_TYPE = "&type=payment";
    public const AMOUNT_LITERAL = "An amount ";

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

        $data['payment_environment'] = $this->config->get('payment_ngenius_environment');

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

        $data['payment_actions'] = $this->config->get('payment_ngenius_payment_action');


        $data['http_versions'] = $this->config->get('payment_ngenius_http_version');

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

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view(self::EXTENSION_DIR, $data));
    }

    /**
     * Install Function for inserting Ngenius NetworkInternational Table
     */
    public function install(): void
    {
        $this->load->model('localisation/order_status');

        $ngenius = new \Opencart\System\Library\Ngenius($this->registry);

        $results                     = $this->model_localisation_order_status->getOrderStatuses();
        $data_result["order_status"] = $ngenius->ngeniusOrderStatus();

        foreach ($data_result["order_status"] as $key => $val) {
            $data["order_status"] = $val;
            if (!in_array($data["order_status"][1]['name'], array_column($results, 'name'))) {
                $this->model_localisation_order_status->addOrderStatus($data);
            }
        }
        $this->load->model(self::EXTENSION_DIR);
        $this->model_extension_ngenius_payment_ngenius->install();
    }

    /**
     * Uninstall the Ngenius NetworkInternational Table
     */
    public function uninstall(): void
    {
        $this->load->model(self::EXTENSION_DIR);
        $this->model_extension_ngenius_payment_ngenius->uninstall();
    }

    /**
     * Adding ngenius tab
     * @return string
     */
    public function order(): string
    {
        $this->load->language(self::EXTENSION_DIR);

        $data['user_token'] = $this->session->data['user_token'];
        $data['order_id']   = $this->request->get['order_id'];

        return $this->load->view('extension/ngenius/payment/ngenius_order', $data);
    }

    /**
     * Get transaction details
     */
    public function getTransaction(): void
    {
        $this->load->language(self::EXTENSION_DIR);
        $this->load->model(self::EXTENSION_DIR);

        $ngenius = new \Opencart\System\Library\Ngenius($this->registry);

        $data = $this->model_extension_ngenius_payment_ngenius->getOrder($this->request->get['order_id']);

        $data['customer_transaction'] = array();
        $customerTransaction          = $this->model_extension_ngenius_payment_ngenius->getCustomerTransaction(
            $this->request->get['order_id']
        );

        foreach ($customerTransaction as $key => $transaction) {
            if ($data['action'] === 'PURCHASE' && $data['state'] === 'PURCHASED') {
                $transaction['refund_button'] = true;
            }
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
                                    $ngenius::NG_AUTHORISED,
                                    $ngenius::NG_AUTH_REV
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

        if (($data['action'] === 'PURCHASE'
                && ($data['state'] === 'PURCHASED' || $data["state"] === "PARTIALLY_REFUNDED"))
            || ($data['action'] === 'SALE'
                && ($data['state'] === 'CAPTURED' || $data["state"] === "PARTIALLY_REFUNDED"))
        ) {
            $data["transactions"][sizeof($customerTransaction)-1]['refund_button'] = true;
        }

        $data['max_refund_amount']  = number_format($data['captured_amt'], 2);
        $data['max_capture_amount'] = number_format($data['amount'], 2);
        $data['amount']             = $data['currency'] . number_format($data['amount'], 2);
        $data['captured_amt']       = $data['currency'] . number_format($data['captured_amt'], 2);
        $data['is_authorised']      = $ngenius::NG_AUTHORISED === $data['status'];
        $data['user_token']         = $this->session->data['user_token'];
        $this->response->setOutput($this->load->view('extension/ngenius/payment/ngenius_order_ajax', $data));
    }

    /**
     * Transaction command: void,capture and refund
     */
    public function transactionCommand()
    {
        $this->load->language(self::EXTENSION_DIR);
        $this->load->model(self::EXTENSION_DIR);
        $this->load->model('sale/order');

        $ngenius = new \Opencart\System\Library\Ngenius($this->registry);
        $json = [];

        $request  = new Request($ngenius);

        $httpTransfer = new NgeniusHTTPTransfer();

        $httpTransfer->setHttpVersion($ngenius->getHttpVersion());
        $httpTransfer->setTokenHeaders($ngenius->getApiKey());

        $tokenRequestData = $request->tokenRequest();
        $httpTransfer->build($tokenRequestData);

        $token = NgeniusHTTPCommon::placeRequest($httpTransfer);

        if (is_string($token)) {
            $token = Validate::tokenValidate($token);
        } else {
            $json['error'] = $token['error'];
        }
        $data_table = [];
        $data       = $this->model_extension_ngenius_payment_ngenius->getOrder($this->request->post['order_id']);
        $order      = $this->model_sale_order->getOrder($this->request->post['order_id']);

        if (!empty($token) && is_string($token)) {

            $httpTransfer->setPaymentHeaders($token);
            if ($this->request->post['type'] == 'void') {

                $voidRequestData = $request->voidOrder($data['reference'], $data['payment_id']);
                $httpTransfer->build($voidRequestData);

                $orderInfo = Validate::voidValidate(
                    NgeniusHTTPCommon::placeRequest($httpTransfer)
                );

                if (is_array($orderInfo)) {
                    if ('REVERSED' === $orderInfo['state']) {
                        $data_table['state']  = $orderInfo['state'];
                        $data_table['status'] = $ngenius::NG_AUTH_REV;

                        $this->model_extension_ngenius_payment_ngenius->updateTable($data_table, $order['order_id']);
                        $captureIdArr['voidId'] = 'NA';
                        $this->model_extension_ngenius_payment_ngenius->addTransaction(
                            $order['customer_id'],
                            json_encode($captureIdArr),
                            '',
                            $order['order_id']
                        );

                        //Add to history table
                        $order_status_id = $this->model_extension_ngenius_payment_ngenius->getNgeniusStatusId(
                            $data_table['status']
                        );
                        $json['success'] = 'The void transaction processed successfully.';
                        $this->model_extension_ngenius_payment_ngenius->addOrderHistory(
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

                $captureRequestData = $request->captureOrder($data, $this->request->post['amount']);
                $httpTransfer->build($captureRequestData);

                $orderInfo = Validate::captureValidate(
                    NgeniusHTTPCommon::placeRequest($httpTransfer)
                );

                if (is_array($orderInfo)) {
                    $data_table['state'] = $orderInfo['state'];
                    if ('PARTIALLY_CAPTURED' === $orderInfo['state']) {
                        $data_table['status'] = $ngenius::NG_P_CAPTURED;
                    } else {
                        $data_table['status'] = $ngenius::NG_F_CAPTURED;
                    }
                    $data_table['captured_amt'] = $orderInfo['total_captured'];
                    $this->model_extension_ngenius_payment_ngenius->updateTable($data_table, $order['order_id']);

                    $captureIdArr['captureId'] = $orderInfo['transaction_id'];
                    $this->model_extension_ngenius_payment_ngenius->addTransaction(
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
                    $order_status_id = $this->model_extension_ngenius_payment_ngenius->getNgeniusStatusId(
                        $data_table['status']
                    );
                    $this->model_extension_ngenius_payment_ngenius->addOrderHistory(
                        $order['order_id'],
                        $order_status_id,
                        $json['success'],
                        false
                    );
                } else {
                    $json['error'] = $orderInfo;
                }
            } elseif ($data['action'] === 'SALE' && $this->request->post['type'] == 'refund'
                && $this->request->post['amount'] && $this->request->post['capture_id']) {

                $refundRequestData = $request->refundOrder(
                    $data,
                    $this->request->post['amount'],
                    $this->request->post['capture_id']
                );
                $httpTransfer->build($refundRequestData);

                $orderInfo = Validate::refundValidate(
                    NgeniusHTTPCommon::placeRequest($httpTransfer)
                );

                if (is_array($orderInfo)) {
                    $data_table['state'] = $orderInfo['state'];
                    if ($orderInfo['total_refunded'] === $orderInfo['captured_amt']) {
                        $data_table['status'] = $ngenius::NG_F_REFUNDED;

                        //Reverse quantity
                        $order_products = $this->model_sale_order->getProducts($order['order_id']);
                        $this->model_extension_ngenius_payment_ngenius->updateProduct($order_products);
                    } else {
                        $data_table['status'] = $ngenius::NG_P_REFUNDED;
                    }

                    $data_table['captured_amt'] = $orderInfo['captured_amt'] - $orderInfo['total_refunded'];
                    $this->model_extension_ngenius_payment_ngenius->updateTable($data_table, $order['order_id']);

                    $captureIdArr['refundedId'] = $orderInfo['transaction_id'];
                    $this->model_extension_ngenius_payment_ngenius->addTransaction(
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
                    $order_status_id = $this->model_extension_ngenius_payment_ngenius->getNgeniusStatusId(
                        $data_table['status']
                    );
                    $this->model_extension_ngenius_payment_ngenius->addOrderHistory(
                        $order['order_id'],
                        $order_status_id,
                        $json['success'],
                        false
                    );
                } else {
                    $json['error'] = $orderInfo;
                }
            } elseif ($data['action'] === 'PURCHASE' && $this->request->post['type'] == 'refund'
                && $this->request->post['amount'] && $this->request->post['capture_id']) {
                // Get the ngenius order detail

                $orderRequestData = $request->orderStatus($data);
                $httpTransfer->build($orderRequestData);

                $orderStatus = NgeniusHTTPCommon::placeRequest($httpTransfer);

                $orderStatus = json_decode($orderStatus, true);
                if ($orderStatus['_embedded']['payment'][0]["paymentMethod"]["name"] === "CHINA_UNION_PAY") {
                    $json['error'] = "This order is non-refundable";
                } elseif (isset($orderStatus['_embedded']['payment'][0]['_links']['cnp:refund'])) {
                    $refundLink = $orderStatus['_embedded']['payment'][0]['_links']['cnp:refund']['href'];
                    // If we have a refund link then the amount has been captured and can be refunded as normal
                    $data['uri'] = $refundLink;

                    $refundRequestData = $request->refundPurchase($data, $this->request->post['amount']);
                    $httpTransfer->build($refundRequestData);

                    $orderInfo = Validate::refundValidate(
                        NgeniusHTTPCommon::placeRequest($httpTransfer)
                    );
                } else {

                    $refundRequestData = $request->voidPurchase(
                        $data,
                        $this->request->post['amount'],
                        $this->request->post['capture_id']
                    );
                    $httpTransfer->build($refundRequestData);

                    $orderInfo = Validate::refundValidate(
                        NgeniusHTTPCommon::placeRequest($httpTransfer)
                    );
                }
                if (is_array($orderInfo)) {
                    $data_table['state'] = $orderInfo['state'];
                    if ($orderInfo['total_refunded'] === $orderInfo['captured_amt']) {
                        $data_table['status'] = $ngenius::NG_F_REFUNDED;

                        //Reverse quantity
                        $order_products = $this->model_sale_order->getProducts($order['order_id']);
                        $this->model_extension_ngenius_payment_ngenius->updateProduct($order_products);
                    } else {
                        $data_table['status'] = $ngenius::NG_P_REFUNDED;
                    }

                    $data_table['captured_amt'] = $orderInfo['captured_amt'] - $orderInfo['total_refunded'];
                    $this->model_extension_ngenius_payment_ngenius->updateTable($data_table, $order['order_id']);

                    $captureIdArr['refundedId'] = $orderInfo['transaction_id'];
                    $this->model_extension_ngenius_payment_ngenius->addTransaction(
                        $order['customer_id'],
                        json_encode($captureIdArr),
                        $orderInfo['refunded_amt'],
                        $order['order_id']
                    );
                    $json['success'] = self::AMOUNT_LITERAL . $order['currency_code'] . number_format(
                        $orderInfo['refunded_amt'],
                        2
                    ) . ' refunded successfully.';

                    //Add to history table
                    $order_status_id = $this->model_extension_ngenius_payment_ngenius->getNgeniusStatusId(
                        $data_table['status']
                    );
                    $this->model_extension_ngenius_payment_ngenius->addOrderHistory(
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
     * @return bool|array
     */
    protected function validate(): bool|array
    {
        if (!$this->user->hasPermission('modify', self::EXTENSION_DIR)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

}
