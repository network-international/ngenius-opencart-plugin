<?php

namespace Opencart\Catalog\Controller\Extension\Ngenius\Payment;

use Ngenius\NgeniusCommon\Formatter\ValueFormatter;
use NumberFormatter;
use Opencart\System\Engine\Controller;
use Opencart\System\Library\Cart\Customer;
use Opencart\System\Library\Tools\Validate;
use Opencart\System\Library\Tools\Request;
use Ngenius\NgeniusCommon\NgeniusHTTPCommon;
use Ngenius\NgeniusCommon\NgeniusHTTPTransfer;
use Opencart\System\Library\Ngenius as NGeniusTools;

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
    public const EXTENSION_LITERAL = "extension/ngenius/payment/ngenius";
    public const CHECKOUT_LITERAL  = "checkout/order";
    public const EMBEDDED_LITERAL  = "_embedded";

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
            $data['action']                           = $this->url->link(
                'extension/ngenius/payment/ngenius|process',
                '',
                true
            );
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
        if (!isset($this->session->data['payment_initiated'])) {
            $this->response->redirect($this->url->link('checkout/checkout'));
        }
        unset($this->session->data['payment_initiated']);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->load->model(self::CHECKOUT_LITERAL);

            $ngenius = new \Opencart\System\Library\Ngenius($this->registry);

            $request = new Request($ngenius);

            $httpTransfer = new NgeniusHTTPTransfer("");

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

                    if (!empty(
                    $this->model_extension_ngenius_payment_ngenius
                        ->getOrder($this->session->data['order_id'])
                        ->rows
                    )
                    ) {
                        $this->model_extension_ngenius_payment_ngenius->removeOrderInfo($data['order_id']);
                    }

                    try {
                        $this->model_extension_ngenius_payment_ngenius->insertOrderInfo($data);
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
    public function redirect(): void
    {
        if ($this->config->get('payment_ngenius_debug_cron')) {
            $data['error'] = '<div>This is a cron debugging test, the order is still in pending.<br><br><a href="/">Back</a></div>';
            $this->response->setOutput($this->load->view(self::EXTENSION_LITERAL, $data));

            return;
        }

        $redirect_url = $this->url->link('checkout');
        $order_ref    = $this->request->get['ref'];
        if (isset($order_ref)) {
            $this->load->model(self::EXTENSION_LITERAL);

            $redirectData = $this->model_extension_ngenius_payment_ngenius->processRedirect($order_ref);

            if (!isset($redirectData['error'])) {
                $redirect_url = $redirectData['redirect_url'];
            }
        }
        $this->response->redirect($redirect_url);
    }

    /**
     * @return void
     */
    public function cronTask(): void
    {
        $this->load->model(self::EXTENSION_LITERAL);
        $orders = $this->model_extension_ngenius_payment_ngenius->fetchOrder(
            "status = '" . NGeniusTools::NG_PENDING . "' AND DATE_ADD(created_at, interval 60 MINUTE) < NOW()
            AND (payment_id ='' OR payment_id ='null');"
        );

        $this->model_extension_ngenius_payment_ngenius->processCron($orders);
    }
}
