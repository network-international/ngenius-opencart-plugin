<?php

use Ngenius\Http;
use Ngenius\Transfer;
use Ngenius\Validate;

/**
 * ControllerExtensionPaymentNgenius class
 */
class ControllerExtensionPaymentNgenius extends Controller
{

    /**
     * N-Genius states
     */
    const NGENIUS_STARTED    = 'STARTED';
    const NGENIUS_AUTHORISED = 'AUTHORISED';
    const NGENIUS_CAPTURED   = 'CAPTURED';
    const NGENIUS_PURCHASED  = 'PURCHASED';
    const NGENIUS_FAILED     = 'FAILED';
    const EXTENSION_LITERAL = "extension/payment/ngenius";
    const CHECKOUT_LITERAL = "checkout/order";
    const CAPTURE_LITERAL = "cnp:capture";
    const LINKS_LITERAL = "_links";
    const EMBEDDED_LITERAL = "_embedded";

    /**
     *
     * @var state,history,status
     */
    private $ngenius_state;
    private $order_status;


    /**
     * Index
     * @return array
     */
    public function index()
    {
        $this->load->language(self::EXTENSION_LITERAL);
        $this->load->library('ngenius');

        if ($this->ngenius->isComplete()) {
            $this->session->data['payment_initiated'] = true;
            $data['action']                           = $this->url->link('extension/payment/ngenius/process', '', true);
        } else {
            $data['error'] = 'Not configured properly!';
        }

        return $this->load->view(self::EXTENSION_LITERAL, $data);
    }

    /**
     * Process the payment request
     */
    public function process()
    {
        if ( ! isset($this->session->data['payment_initiated'])) {
            $this->response->redirect($this->url->link('checkout/checkout'));
        }
        unset($this->session->data['payment_initiated']);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->load->model(self::CHECKOUT_LITERAL);
            $this->load->library('ngenius');
            $request  = new \Ngenius\Request($this->ngenius);
            $transfer = new \Ngenius\Transfer();
            $http     = new \Ngenius\Http();
            $validate = new \Ngenius\Validate();

            $token = $http->placeRequest($transfer->forToken($request->tokenRequest()));
            if (is_string($token)) {
                $token = $validate->tokenValidate($token);
            }
            $order = $this->model_checkout_order->getOrder($this->session->data['order_id']);
            if ($token && is_string($token) && $order) {
                $paymentAction = $this->ngenius->getPaymentAction();

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
                $payment = $validate->paymentUrlValidate($http->placeRequest($transfer->create($requestArr, $token)));

                if (isset($payment['data']) && isset($payment['url'])) {
                    $this->order_status = $this->ngenius::NG_PENDING;

                    $data['order_id']  = $order['order_id'];
                    $data['currency']  = $order['currency_code'];
                    $data['amount']    = $order['total'];
                    $data['reference'] = $payment['data']['reference'];
                    $data['action']    = $payment['data']['action'];
                    $data['state']     = $payment['data']['state'];
                    $data['status']    = $this->order_status;

                    $this->load->model(self::EXTENSION_LITERAL);

                    $redirect_uri = $payment['url'];
                    $status_id    = $this->ngenius->getOrderStatusId($this, $data['status']);
                    $msg          = 'Customer redirected to payment gateway';


                    try {
                        $this->model_checkout_order->addOrderHistory(
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
                    try {
                        $this->model_extension_payment_ngenius->insertOrderInfo($data);
                    } catch (Exception $ex) {
                        $this->response->redirect($this->url->link('checkout/failure'));
                        exit;
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
    public function redirect()
    {
        $redirect_url = $this->url->link('checkout');
        $order_ref    = $this->request->get['ref'];
        if (isset($order_ref)) {
            $this->load->library('ngenius');
            $this->load->model(self::CHECKOUT_LITERAL);
            $this->load->model(self::EXTENSION_LITERAL);

            $request  = new \Ngenius\Request($this->ngenius);
            $transfer = new \Ngenius\Transfer();
            $http     = new \Ngenius\Http();
            $validate = new \Ngenius\Validate();

            $token = $validate->tokenValidate($http->placeRequest($transfer->forToken($request->tokenRequest())));
            $order = $this->model_checkout_order->getOrder($this->session->data['order_id']);

            if ($token && $order) {
                $order_info = $validate->orderValidate(
                    $http->placeRequest($transfer->create($request->fetchOrder($order_ref), $token))
                );

                if (is_array($order_info)) {
                    $this->ngenius_state = isset($order_info[self::EMBEDDED_LITERAL]['payment'][0]['state']) ? $order_info[self::EMBEDDED_LITERAL]['payment'][0]['state'] : '';
                    if (isset($order_info[self::EMBEDDED_LITERAL]['payment']) && is_array($order_info[self::EMBEDDED_LITERAL]['payment'])) {
                        $action         = isset($order_info['action']) ? $order_info['action'] : '';
                        $payment_result = $order_info[self::EMBEDDED_LITERAL]['payment'][0];
                        if ($payment_result['state'] === 'FAILED') {
                            $this->order_status = $this->ngenius::NG_DECLINED;
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
     *
     * @return $this|null
     */
    public function processOrder(array $order, array $payment_result, string $action)
    {
        $this->load->model(self::EXTENSION_LITERAL);
        $order_item = $this->model_extension_payment_ngenius->getOrder($order['order_id']);

        if ($order_item) {
            $this->order_status = $this->ngenius::NG_PROCESSING;
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
                    $this->model_extension_payment_ngenius->quantityReduce($order['order_id']);
                } else {
                    $data_table['status'] = $this->ngenius::NG_PENDING;
                }
                $redirect_url = $this->url->link('checkout/success');
            } else {
                $data_table['status'] = $this->ngenius::NG_FAILED;
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
            if ( ! empty($capture_id)) {
                $comment = json_encode(array('captureId' => $capture_id));
            } else {
                $comment = json_encode(array('AuthId' => $payment_id));
            }

            $this->model_extension_payment_ngenius->addTransaction(
                $order['customer_id'],
                $comment,
                $order['total'],
                $order['order_id']
            );
            $this->model_extension_payment_ngenius->updateTable($data_table, $order_item->row['nid']);

            return $redirect_url;
        }
    }

    /**
     * Order Authorize.
     *
     * @param array $order
     *
     * @return null
     */
    public function orderAuthorize(array $order)
    {
        $this->load->model(self::CHECKOUT_LITERAL);
        if (self::NGENIUS_AUTHORISED === $this->ngenius_state) {
            $this->order_status = $this->ngenius::NG_AUTHORISED;
            $this->load->model(self::CHECKOUT_LITERAL);

            $message   = 'An amount: ' . $order['currency_code'] . number_format(
                    $order['total'],
                    2
                ) . ' has been authorised.';
            $status_id = $this->ngenius->getOrderStatusId($this, $this->ngenius::NG_AUTHORISED);

            $this->model_checkout_order->addOrderHistory($order['order_id'], $status_id, $message, true);
        }
    }

    /**
     * Order Sale
     *
     * @param array $order
     * @param array $payment_result
     *
     * @return null|array
     */
    public function orderSale(array $order, array $payment_result)
    {
        if (self::NGENIUS_CAPTURED === $this->ngenius_state) {
            $this->order_status = $this->ngenius::NG_COMPLETE;
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
            $status_id = $this->ngenius->getOrderStatusId($this, $this->ngenius::NG_COMPLETE);

            $this->model_checkout_order->addOrderHistory($order['order_id'], $status_id, $message, true);

            return $transaction_id;
        } elseif ($payment_result['state'] === 'FAILED') {
            $this->order_status = $this->ngenius::NG_DECLINED;
            $this->load->model(self::CHECKOUT_LITERAL);
            $message   = $payment_result['authResponse']['resultMessage'] ?? 'Declined';
            $status_id = $this->ngenius->getOrderStatusId($this, $this->ngenius::NG_DECLINED);
            $this->model_checkout_order->addOrderHistory($order['order_id'], $status_id, $message, true);
        }
    }

    /**
     * Order Sale
     *
     * @param array $order
     * @param array $payment_result
     *
     * @return null|array
     */
    public function orderPurchase(array $order, array $payment_result)
    {
        if (self::NGENIUS_PURCHASED === $this->ngenius_state) {
            $this->order_status = $this->ngenius::NG_COMPLETE;
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
            $status_id = $this->ngenius->getOrderStatusId($this, $this->ngenius::NG_COMPLETE);

            $this->model_checkout_order->addOrderHistory($order['order_id'], $status_id, $message, true);

            return $transaction_id;
        } elseif ($payment_result['state'] === 'FAILED') {
            $this->order_status = $this->ngenius::NG_DECLINED;
            $this->load->model(self::CHECKOUT_LITERAL);
            $message   = $payment_result['authResponse']['resultMessage'] ?? 'Declined';
            $status_id = $this->ngenius->getOrderStatusId($this, $this->ngenius::NG_DECLINED);
            $this->model_checkout_order->addOrderHistory($order['order_id'], $status_id, $message, true);
        }
    }

    /**
     * Cron setup
     */
    public function cron()
    {
        $this->load->library('ngenius');
        $this->load->model(self::CHECKOUT_LITERAL);
        $this->load->model(self::EXTENSION_LITERAL);

        $order_items = $this->model_extension_payment_ngenius->fetchOrder(
            'state = "' . self::NGENIUS_STARTED . '" AND status = "' . $this->ngenius::NG_PENDING . '" AND payment_id="" AND DATE_ADD(created_at, INTERVAL 60 MINUTE) < NOW()'
        );

        $log = [];
        if (is_array($order_items)) {
            foreach ($order_items as $order_item) {
                $request  = new \Ngenius\Request($this->ngenius);
                $transfer = new \Ngenius\Transfer();
                $http     = new \Ngenius\Http();
                $validate = new \Ngenius\Validate();

                $token  = $validate->tokenValidate($http->placeRequest($transfer->forToken($request->tokenRequest())));
                $order  = $this->model_checkout_order->getOrder($order_item['order_id']);
                $result = $validate->orderValidate(
                    $http->placeRequest($transfer->create($request->fetchOrder($order_item['reference']), $token))
                );

                if (is_array($result)) {
                    $payment_result = $result[self::EMBEDDED_LITERAL]['payment'][0];
                    $action         = isset($result['action']) ? $result['action'] : '';
                    $this->processOrder($order, $payment_result, $action);
                    $log[] = 'Cron Updated: #' . $order['order_id'];
                }
            }
            $this->ngenius->debug(json_encode($log));
        }
    }

}
