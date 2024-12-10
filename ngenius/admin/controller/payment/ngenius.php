<?php

/**
 * ControllerExtensionPaymentNgenius class
 */

namespace Opencart\Admin\Controller\Extension\Ngenius\Payment;

use Ngenius\NgeniusCommon\Formatter\ValueFormatter;
use Ngenius\NgeniusCommon\NgeniusHTTPCommon;
use Ngenius\NgeniusCommon\NgeniusHTTPTransfer;
use Ngenius\NgeniusCommon\Processor\ApiProcessor;
use Ngenius\NgeniusCommon\Processor\RefundProcessor;
use NumberFormatter;
use Opencart\System\Engine\Controller;
use Opencart\System\Library\Tools\Request;
use Opencart\System\Library\Tools\Validate;
use Opencart\Catalog\Model\Extension\Ngenius\Payment\Ngenius as NgeniusOrder;

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
    public const EXTENSION_DIR  = "extension/ngenius/payment/ngenius";
    public const USER_TOKEN     = "user_token=";
    public const PAYMENT_TYPE   = "&type=payment";
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

        $parsedUrl = parse_url(HTTP_SERVER);

        $data['cron_url'] = $parsedUrl['scheme'] . '://' . $parsedUrl['host']
                            . '/index.php?route=extension/ngenius/payment/ngenius|cronTask';

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

        if (isset($this->request->post['payment_ngenius_debug_cron'])) {
            $data['payment_ngenius_debug_cron'] = $this->request->post['payment_ngenius_debug_cron'];
        } else {
            $data['payment_ngenius_debug_cron'] = $this->config->get('payment_ngenius_debug_cron');
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

        ValueFormatter::formatCurrencyDecimals($data['currency'], $data['amount']);

        ValueFormatter::formatCurrencyDecimals($data['currency'], $data['captured_amt']);

        if ($data['action'] === 'SALE' && $data['state'] === 'PURCHASED') {
            $getNgeniusOrder = $this->model_extension_ngenius_payment_ngenius->getNGeniusOrder($data['reference']);
            $apiProcessor    = new ApiProcessor($getNgeniusOrder);
            $captureId       = $apiProcessor->getTransactionId();
            if (!empty($captureId)) {
                $dataArray = [
                    'captured_amt' => $data['captured_amt'],
                    'state'        => $apiProcessor->getState(),
                    'status'       => $data['status'],
                ];
                $this->model_extension_ngenius_payment_ngenius->updateTable($dataArray, (int)$data['order_id']);

                $comment = json_encode(array('captureId' => $captureId));

                $this->model_extension_ngenius_payment_ngenius->addTransaction(
                    $customerTransaction[0]['customer_id'],
                    $comment,
                    (float)$dataArray['captured_amt'],
                    $customerTransaction[0]['order_id']
                );

                $customerTransaction = $this->model_extension_ngenius_payment_ngenius->getCustomerTransaction(
                    $this->request->get['order_id']
                );
            }
        }

        foreach ($customerTransaction as $key => $transaction) {
            $jsonData = json_decode($transaction['description'], true);
            if ($jsonData) {
                foreach ($jsonData as $key => $value) {
                    ValueFormatter::formatCurrencyDecimals($data['currency'], $transaction['amount']);

                    $transaction['amount'] = $data['currency'] . $transaction['amount'];
                    $transaction['id']     = $value;
                    switch ($key) {
                        case 'captureId':
                            $transaction['message'] = 'Transaction: Captured';
                            if (
                                $data['state'] === 'CAPTURED'
                                || $data["state"] === 'PARTIALLY_REFUNDED'
                                || $data["state"] === 'PURCHASED'
                            ) {
                                $transaction['refund_button'] = true;
                            }
                            break;
                        case 'refundedId':
                            $transaction['message'] = 'Transaction: Refunded';
                            break;
                        case 'AuthId':
                            $transaction['message'] = 'Transaction: Authorised';
                            if (($data["state"] === 'PARTIALLY_REFUNDED'
                                 || $data["state"] === 'PURCHASED') && $data["action"] !== 'SALE'
                                && $data["action"] !== 'AUTH'
                            ) {
                                $transaction['refund_button'] = true;
                            }
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

        $data['max_refund_amount']  = $data['captured_amt'];
        $data['max_capture_amount'] = $data['amount'];
        $data['amount']             = $data['currency'] . $data['amount'];
        $data['captured_amt']       = $data['currency'] . $data['captured_amt'];
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
        $json    = [];

        $request = new Request($ngenius);

        $httpTransfer = new NgeniusHTTPTransfer("");

        $httpTransfer->setHttpVersion($ngenius->getHttpVersion());
        $httpTransfer->setTokenHeaders($ngenius->getApiKey());

        $tokenRequestData = $request->tokenRequest();
        $httpTransfer->build($tokenRequestData);

        $token = NgeniusHTTPCommon::placeRequest($httpTransfer);

        if (is_string($token)) {
            $token = Validate::tokenValidate($token);
        } else {
            $json['error'] = 'Could not retrieve token.';
        }
        $data  = $this->model_extension_ngenius_payment_ngenius->getOrder($this->request->post['order_id']);
        $order = $this->model_sale_order->getOrder($this->request->post['order_id']);

        $amount = null;

        if (isset($this->request->post['amount'])) {
            $amount = (float)str_replace(',', '', $this->request->post['amount']);
        }

        if (!empty($token) && is_string($token)) {
            $httpTransfer->setPaymentHeaders($token);
            if ($this->request->post['type'] == 'void') {
                $json = $this->voidOrder($request, $httpTransfer, $data, $order, $ngenius);
            } elseif ($this->request->post['type'] == 'capture' && $amount) {
                $json = $this->captureOrder($request, $httpTransfer, $data, $amount, $order, $ngenius);
            } elseif (($data['action'] === 'SALE' || $data['action'] === 'AUTH') && $this->request->post['type'] == 'refund'
                      && $amount && $this->request->post['capture_id']) {
                $json = $this->saleRefund($request, $httpTransfer, $data, $amount, $order, $ngenius);
            } elseif ($data['action'] === 'PURCHASE' && $this->request->post['type'] == 'refund'
                      && $amount && $this->request->post['capture_id']) {
                $json = $this->purchaseRefund($request, $httpTransfer, $data, $amount, $order, $ngenius);
            } else {
                $json['error'] = "Your action could not be performed";
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function getOrderStatus($data, $httpTransfer, $request): \stdClass
    {
        $orderRequestData = $request->orderStatus($data);
        $httpTransfer->build($orderRequestData);

        $orderStatus = NgeniusHTTPCommon::placeRequest($httpTransfer);

        return json_decode($orderStatus);
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

    /**
     * @param $request
     * @param $httpTransfer
     * @param $data
     * @param $order
     * @param $ngenius
     *
     * @return array
     */
    protected function voidOrder($request, $httpTransfer, $data, $order, $ngenius): array
    {
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

        return $json;
    }

    /**
     * @param $request
     * @param $httpTransfer
     * @param $data
     * @param $amount
     * @param $order
     * @param $ngenius
     *
     * @return array
     */
    protected function captureOrder($request, $httpTransfer, $data, $amount, $order, $ngenius): array
    {
        $captureRequestData = $request->captureOrder($data, $amount);
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

            $this->model_extension_ngenius_payment_ngenius->addTransaction(
                $order['customer_id'],
                "Order ID: #" . $order['order_id'] . " pre-capture",
                (float)($order['total']) * -1,
                $order['order_id']
            );

            $captureIdArr['captureId'] = $orderInfo['transaction_id'];
            $this->model_extension_ngenius_payment_ngenius->addTransaction(
                $order['customer_id'],
                json_encode($captureIdArr),
                (float)$order['total'],
                $order['order_id']
            );

            $total = $orderInfo['captured_amt'];

            ValueFormatter::formatCurrencyDecimals($order['currency_code'] , $total);

            $json['success'] = self::AMOUNT_LITERAL . $order['currency_code']
                               . $total . ' captured successfully.';

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

        return $json;
    }

    /**
     * @param $request
     * @param $httpTransfer
     * @param $data
     * @param $amount
     * @param $order
     * @param $ngenius
     *
     * @return array
     */
    protected function saleRefund($request, $httpTransfer, $data, $amount, $order, $ngenius): array
    {
        $orderStatus = $this->getOrderStatus($data, $httpTransfer, $request);

        $payment = $orderStatus->_embedded->payment[0];

        $refundUrl = RefundProcessor::extractUrl($payment);

        $refundRequestData = $request->refundOrder(
            $data,
            $amount,
            $this->request->post['capture_id'],
            $refundUrl
        );

        $httpTransfer->build($refundRequestData);

        $orderInfo = Validate::refundValidate(
            NgeniusHTTPCommon::placeRequest($httpTransfer)
        );

        if (is_array($orderInfo)) {
            $data_table['state'] = $orderInfo['state'];
            if ($orderInfo['total_refunded'] === $orderInfo['captured_amt']) {
                if ($orderInfo['state'] === 'PARTIALLY_REFUNDED') {
                    $data_table['state'] = 'REFUNDED';
                }

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
            ValueFormatter::formatCurrencyDecimals($order['currency_code'], $orderInfo['refunded_amt']);

            $this->model_extension_ngenius_payment_ngenius->addTransaction(
                $order['customer_id'],
                json_encode($captureIdArr),
                $orderInfo['refunded_amt'],
                $order['order_id']
            );

            $json['success'] = self::AMOUNT_LITERAL . $order['currency_code'] . $orderInfo['refunded_amt'] . ' refunded successfully.';

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

        return $json;
    }

    /**
     * @param $request
     * @param $httpTransfer
     * @param $data
     * @param $amount
     * @param $order
     * @param $ngenius
     *
     * @return array
     */
    protected function purchaseRefund($request, $httpTransfer, $data, $amount, $order, $ngenius): array
    {
        // Get the ngenius order detail
        $orderRequestData = $request->orderStatus($data);
        $httpTransfer->build($orderRequestData);

        $orderStatus = NgeniusHTTPCommon::placeRequest($httpTransfer);

        $orderStatus = json_decode($orderStatus);

        $payment = $orderStatus->_embedded->payment[0];

        $refundLink = RefundProcessor::extractUrl($payment);

        if (!str_contains($refundLink, 'cancel')) {
            $data['uri']       = $refundLink;
            $refundRequestData = $request->refundPurchase($data, $amount);
        } else {
            $refundRequestData = $request->voidPurchase(
                $data,
                $amount,
                $this->request->post['capture_id']
            );
        }

        $httpTransfer->build($refundRequestData);

        $orderInfo = Validate::refundValidate(
            NgeniusHTTPCommon::placeRequest($httpTransfer)
        );

        if (is_array($orderInfo)) {
            $data_table['state'] = $orderInfo['state'];
            if ($orderInfo['total_refunded'] === $orderInfo['captured_amt']) {
                if ($orderInfo['state'] === 'PARTIALLY_REFUNDED') {
                    $data_table['state'] = 'REFUNDED';
                }

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
            ValueFormatter::formatCurrencyDecimals($order['currency_code'], $orderInfo['refunded_amt']);
            $json['success'] = self::AMOUNT_LITERAL . $order['currency_code'] . $orderInfo['refunded_amt'] . ' refunded successfully.';

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

        return $json;
    }
}
