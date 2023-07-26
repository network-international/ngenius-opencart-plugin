<?php

namespace Opencart\Catalog\Controller\Extension\Ngenius\Payment;

use Opencart\System\Engine\Controller;
use Opencart\System\Library\Cart\Customer;
use Opencart\System\Library\Tools\Validate;
use Opencart\System\Library\Tools\Request;
use Ngenius\NgeniusCommon\NgeniusHTTPCommon;
use Ngenius\NgeniusCommon\NgeniusHTTPTransfer;

require_once DIR_EXTENSION . 'ngenius/system/library/vendor/autoload.php';
require_once DIR_EXTENSION . 'ngenius/system/library/tools/request.php';
require_once DIR_EXTENSION . 'ngenius/system/library/tools/validate.php';
require_once DIR_EXTENSION . 'ngenius/system/library/ngenius.php';

/**
 * ControllerExtensionPaymentNgenius class
 */
class Ngenius extends Controller
{
    /**
     * N-Genius states
     */
    public const NGENIUS_STARTED    = 'STARTED';
    public const NGENIUS_AUTHORISED = 'AUTHORISED';
    public const NGENIUS_CAPTURED   = 'CAPTURED';
    public const NGENIUS_PURCHASED  = 'PURCHASED';
    public const NGENIUS_FAILED     = 'FAILED';
    public const EXTENSION_LITERAL = "extension/ngenius/payment/ngenius";
    public const CHECKOUT_LITERAL = "checkout/order";
    public const CAPTURE_LITERAL = "cnp:capture";
    public const LINKS_LITERAL = "_links";
    public const EMBEDDED_LITERAL = "_embedded";

    private string $ngenius_state;
    private string $order_status;

    /**
     * Index
     * @return string
     */
    public function index(): string
    {
        if (isset($this->request->get['ref'])) {
            $this->redirect();
            return "";
        }
        $this->load->language(self::EXTENSION_LITERAL);

        $ngenius = new \Opencart\System\Library\Ngenius($this->registry);

        if ($ngenius->isComplete()) {
            $this->session->data['payment_initiated'] = true;
            $data['action'] = $this->url->link('extension/ngenius/payment/ngenius|process', '', true);
        } else {
            $data['error'] = 'Not configured properly!';
        }

        return $this->load->view(self::EXTENSION_LITERAL, $data);
    }

    /**
     * Process the payment request
     */
    public function process(): void
    {
        if (! isset($this->session->data['payment_initiated'])) {
            $this->response->redirect($this->url->link('checkout/checkout'));
        }
        unset($this->session->data['payment_initiated']);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->load->model(self::CHECKOUT_LITERAL);

            $ngenius = new \Opencart\System\Library\Ngenius($this->registry);

            $request  = new Request($ngenius);

            $httpTransfer = new NgeniusHTTPTransfer();

            $httpTransfer->setTokenHeaders($ngenius->getApiKey());
            $httpTransfer->setHttpVersion($ngenius->getHttpVersion());

            $httpTransfer->build($request->tokenRequest());

            $token = NgeniusHTTPCommon::placeRequest($httpTransfer);

            if (is_string($token)) {
                $token = Validate::tokenValidate($token);
            }
            $order = $this->model_checkout_order->getOrder($this->session->data['order_id']);
            if ($token && is_string($token) && $order) {
                $paymentAction = $ngenius->getPaymentAction();

                $requestArr = [];
                switch ($paymentAction) {
                    case 'sale':
                        $requestArr = $request->saleRequest($order);
                        break;
                    case 'authorize':
                        $requestArr = $request->authRequest($order);
                        break;
                    case 'purchase':
                        $requestArr = $request->purchaseRequest($order);
                        break;
                    default:
                        break;
                }

                $httpTransfer->build($requestArr);
                $httpTransfer->setPaymentHeaders($token);

                $payment = Validate::paymentUrlValidate(NgeniusHTTPCommon::placeRequest($httpTransfer));

                if (isset($payment['data']) && isset($payment['url'])) {
                    $this->order_status = $ngenius::NG_PENDING;

                    $data['order_id']  = $order['order_id'];
                    $data['currency']  = $order['currency_code'];
                    $data['amount']    = $order['total'];
                    $data['reference'] = $payment['data']['reference'];
                    $data['action']    = $payment['data']['action'];
                    $data['state']     = $payment['data']['state'];
                    $data['status']    = $this->order_status;

                    $this->load->model(self::EXTENSION_LITERAL);

                    $redirect_uri = $payment['url'];
                    $status_id    = $ngenius->getOrderStatusId($this, $data['status']);
                    $msg          = 'Customer redirected to payment gateway';

                    try {
                        $this->model_checkout_order->addHistory(
                            $this->session->data['order_id'],
                            $status_id,
                            $msg,
                            false
                        );
                    } catch (Exception $ex) {
                        echo '<p>Please check your SMTP configuration!</p>';
                        echo $ex->getMessage();
                        exit;
                    }

                    //If order confirm is disable in javascript
                    if (empty($this->model_extension_ngenius_payment_ngenius
                        ->getOrder($this->session->data['order_id'])
                        ->rows)
                    ) {
                        try {
                            $this->model_extension_ngenius_payment_ngenius->insertOrderInfo($data);
                        } catch (Exception $ex) {
                            $this->response->redirect($this->url->link('checkout/failure'));
                            exit;
                        }
                    }

                    $this->response->redirect($redirect_uri);
                } else {
                    echo "<h3><strong>Error: $payment</strong></h3>";
                    echo '<br>';
                    echo 'Use the browser back button to try again<br>';
                    echo 'If the problem persists please contact the store owner.';
                }
            } else {
                // No token
                $error = $token['error'] ?? 'Unknown';
                echo "<h3><strong>Error: $error</strong></h3>";
                echo '<br>';
                echo 'Use the browser back button to try again<br>';
                echo 'If the problem persists please contact the store owner.';
            }
        } else {
            $this->response->redirect($this->url->link('checkout/checkout'));
        }
    }

    /**
     * Redirection
     */
    public function redirect(): void
    {
        $redirect_url = $this->url->link('checkout');
        $order_ref    = $this->request->get['ref'];
        if (isset($order_ref)) {

            $ngenius = new \Opencart\System\Library\Ngenius($this->registry);

            $this->load->model(self::CHECKOUT_LITERAL);
            $this->load->model(self::EXTENSION_LITERAL);

            $request  = new Request($ngenius);

            $httpTransfer = new NgeniusHTTPTransfer();

            $httpTransfer->setTokenHeaders($ngenius->getApiKey());
            $httpTransfer->setHttpVersion($ngenius->getHttpVersion());

            $httpTransfer->build($request->tokenRequest());

            $token = NgeniusHTTPCommon::placeRequest($httpTransfer);

            if (is_string($token)) {
                $token = Validate::tokenValidate($token);
            }

            $order = $this->model_checkout_order->getOrder($this->session->data['order_id']);

            if ($token && $order) {

                $httpTransfer->setPaymentHeaders($token);

                $httpTransfer->build($request->fetchOrder($order_ref));

                $order_info = Validate::orderValidate(
                    NgeniusHTTPCommon::placeRequest($httpTransfer)
                );

                if (is_array($order_info)) {
                    $this->ngenius_state = $order_info[self::EMBEDDED_LITERAL]['payment'][0]['state'] ?? '';
                    if (isset($order_info[self::EMBEDDED_LITERAL]['payment'])
                        && is_array($order_info[self::EMBEDDED_LITERAL]['payment'])) {
                        $action         = $order_info['action'] ?? '';
                        $payment_result = $order_info[self::EMBEDDED_LITERAL]['payment'][0];
                        if ($payment_result['state'] === 'FAILED') {
                            $this->order_status = $ngenius::NG_DECLINED;
                        }
                        $redirect_url = $this->processOrder($order, $payment_result, $action);
                    }
                } else {
                    $data['error'] = $order_info;
                }
            } else {
                $data['error'] = 'Error! Order not found';
            }
            $this->response->redirect($redirect_url);
        }
    }

    /**
     * Process Order
     *
     * @param array $order
     * @param array $payment_result
     * @param string $action
     * @return bool|string
     */
    public function processOrder(array $order, array $payment_result, string $action): bool|string
    {
        $this->load->model(self::EXTENSION_LITERAL);
        $order_item = $this->model_extension_ngenius_payment_ngenius->getOrder($order['order_id']);

        $this->model_extension_ngenius_payment_ngenius->addTransaction(
            $order['customer_id'],
            "Order ID: #" . $order['order_id'],
            (int)($order['total'])*-1,
            $order['order_id']
        );

        $ngenius = new \Opencart\System\Library\Ngenius($this->registry);

        if ($order_item) {
            $this->order_status = $ngenius::NG_PROCESSING;
            $data_table         = [];
            $payment_id         = '';
            $capture_id         = '';
            $captured_amt       = 0;
            if (isset($payment_result['_id'])) {
                $payment_id_arr      = explode(':', $payment_result['_id']);
                $payment_id          = end($payment_id_arr);
                $this->ngenius_state = $payment_result['state'];
            }

            if (self::NGENIUS_FAILED !== $this->ngenius_state) {
                if (self::NGENIUS_STARTED !== $this->ngenius_state) {
                    switch ($action) {
                        case 'AUTH':
                            $this->orderAuthorize($order);
                            break;
                        case 'SALE':
                            $capture_id   = $this->orderSale($order, $payment_result);
                            $captured_amt = $order['total'];
                            break;
                        case 'PURCHASE':
                            $capture_id   = $this->orderPurchase($order, $payment_result);
                            $captured_amt = $order['total'];
                            break;
                        default:
                            break;
                    }
                    $data_table['status'] = $this->order_status;
                    $this->model_extension_ngenius_payment_ngenius->quantityReduce($order['order_id']);
                } else {
                    $data_table['status'] = $ngenius::NG_PENDING;
                }
                $redirect_url = $this->url->link('checkout/success');
            } else {
                $data_table['status'] = $ngenius::NG_FAILED;
                switch ($action) {
                    case 'SALE':
                        $this->orderSale($order, $payment_result);
                        break;
                    case 'PURCHASE':
                        $this->orderPurchase($order, $payment_result);
                        break;
                    default:
                        break;
                }
                $redirect_url = $this->url->link('checkout/failure');
            }
            $data_table['payment_id']   = $payment_id;
            $data_table['captured_amt'] = $captured_amt;
            $data_table['state']        = $this->ngenius_state;
            if (! empty($capture_id)) {
                $comment = json_encode(array('captureId' => $capture_id));
            } else {
                $comment = json_encode(array('AuthId' => $payment_id));
            }

            $this->model_extension_ngenius_payment_ngenius->addTransaction(
                $order['customer_id'],
                $comment,
                $order['total'],
                $order['order_id']
            );
            $this->model_extension_ngenius_payment_ngenius->updateTable($data_table, $order_item->row['nid']);

            return $redirect_url;
        }
        return false;
    }

    /**
     * Order Authorize.
     *
     * @param array $order
     *
     * @return void
     */
    public function orderAuthorize(array $order): void
    {

        $ngenius = new \Opencart\System\Library\Ngenius($this->registry);

        $this->load->model(self::CHECKOUT_LITERAL);
        if (self::NGENIUS_AUTHORISED === $this->ngenius_state) {
            $this->order_status = $ngenius::NG_AUTHORISED;
            $this->load->model(self::CHECKOUT_LITERAL);

            $message   = 'An amount: ' . $order['currency_code'] . number_format(
                    $order['total'],
                    2
                ) . ' has been authorised.';
            $status_id = $ngenius->getOrderStatusId($this, $ngenius::NG_AUTHORISED);

            $this->model_checkout_order->addHistory($order['order_id'], $status_id, $message, true);
        }
    }

    /**
     * Order Sale
     *
     * @param array $order
     * @param array $payment_result
     *
     * @return array|string|null
     */
    public function orderSale(array $order, array $payment_result): array|string|null
    {

        $ngenius = new \Opencart\System\Library\Ngenius($this->registry);

        if (self::NGENIUS_CAPTURED === $this->ngenius_state) {
            $this->order_status = $ngenius::NG_COMPLETE;
            $this->load->model(self::CHECKOUT_LITERAL);
            $transaction_id = '';
            if (isset($payment_result[self::EMBEDDED_LITERAL][self::CAPTURE_LITERAL][0])) {
                $last_transaction = $payment_result[self::EMBEDDED_LITERAL][self::CAPTURE_LITERAL][0];
                if (isset($last_transaction[self::LINKS_LITERAL]['self']['href'])) {
                    $transaction_arr = explode('/', $last_transaction[self::LINKS_LITERAL]['self']['href']);
                    $transaction_id  = end($transaction_arr);
                }
            }
            $message   = 'Captured Amount: ' . $order['currency_code'] . number_format(
                    $order['total'],
                    2
                ) . ' | Transaction ID: ' . $transaction_id;
            $status_id = $ngenius->getOrderStatusId($this, $ngenius::NG_COMPLETE);

            $this->model_checkout_order->addHistory($order['order_id'], $status_id, $message, true);

            return $transaction_id;
        } elseif ($payment_result['state'] === 'FAILED') {
            $this->order_status = $ngenius::NG_DECLINED;
            $this->load->model(self::CHECKOUT_LITERAL);
            $message   = $payment_result['authResponse']['resultMessage'] ?? 'Declined';
            $status_id = $ngenius->getOrderStatusId($this, $ngenius::NG_DECLINED);
            $this->model_checkout_order->addHistory($order['order_id'], $status_id, $message, true);
        }
        return null;
    }

    /**
     * Order Sale
     *
     * @param array $order
     * @param array $payment_result
     *
     * @return array|string|null
     */
    public function orderPurchase(array $order, array $payment_result): array|string|null
    {
        $ngenius = new \Opencart\System\Library\Ngenius($this->registry);

        if (self::NGENIUS_PURCHASED === $this->ngenius_state) {
            $this->order_status = $ngenius::NG_COMPLETE;
            $this->load->model(self::CHECKOUT_LITERAL);
            $transaction_id = '';
            if (isset($payment_result[self::EMBEDDED_LITERAL][self::CAPTURE_LITERAL][0])) {
                $last_transaction = $payment_result[self::EMBEDDED_LITERAL][self::CAPTURE_LITERAL][0];
                if (isset($last_transaction[self::LINKS_LITERAL]['self']['href'])) {
                    $transaction_arr = explode('/', $last_transaction[self::LINKS_LITERAL]['self']['href']);
                    $transaction_id  = end($transaction_arr);
                }
            }
            $message   = 'Captured Amount: ' . $order['currency_code'] . number_format(
                    $order['total'],
                    2
                ) . ' | Transaction ID: ' . $transaction_id;
            $status_id = $ngenius->getOrderStatusId($this, $ngenius::NG_COMPLETE);

            $this->model_checkout_order->addHistory($order['order_id'], $status_id, $message, true);

            return $transaction_id;
        } elseif ($payment_result['state'] === 'FAILED') {
            $this->order_status = $ngenius::NG_DECLINED;
            $this->load->model(self::CHECKOUT_LITERAL);
            $message   = $payment_result['authResponse']['resultMessage'] ?? 'Declined';
            $status_id = $ngenius->getOrderStatusId($this, $ngenius::NG_DECLINED);
            $this->model_checkout_order->addHistory($order['order_id'], $status_id, $message, true);
        }
        return null;
    }
}
