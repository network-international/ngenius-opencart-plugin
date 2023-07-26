<?php

namespace Opencart\System\Library\Tools;

/**
 * Request class
 */
class Request
{
    /**
     *
     * @var object library class
     */
    private $library;
    public const REDIRECT_EXTENSION_LITERAL = "extension/ngenius/payment/ngenius";

    /**
     *
     * @param object $library
     */
    public function __construct($library)
    {
        $this->library = $library;
    }

    /**
     * Request array for token
     *
     * @return array
     */
    public function tokenRequest(): array
    {
        return [
            'method'  => 'POST',
            'uri'     => $this->library->getTokenRequestURL()
        ];
    }

    /**
     * Request array for sale
     *
     * @param array $order
     *
     * @return array
     */
    public function saleRequest(array $order): array
    {
        return $this->actionRequest($order, 'SALE');
    }

    /**
     * Request array for purchase
     *
     * @param array $order
     *
     * @return array
     */
    public function purchaseRequest(array $order): array
    {
        return $this->actionRequest($order, 'PURCHASE');
    }

    /**
     * Request array for authorize
     *
     * @param array $order
     *
     * @return array
     */
    public function authRequest(array $order)
    {
        return $this->actionRequest($order, 'AUTH');
    }

    /**
     * @param array $order
     * @param string $action
     *
     * @return array
     */
    private function actionRequest(array $order, string $action): array
    {
        $outletReferenceId = $this->library->getOutletReferenceId();

        return [
            'data'   => [
                'action'                 => $action,
                'amount'                 => [
                    'currencyCode' => $order['currency_code'],
                    'value'        => strval(round($order['total'] * 100)),
                ],
                'merchantAttributes'     => [
                    'redirectUrl'          => filter_var(
                        $this->library->url->link(self::REDIRECT_EXTENSION_LITERAL, '', true),
                        FILTER_SANITIZE_URL
                    ),
                    'skipConfirmationPage' => true,
                ],
                'outletId'               => $outletReferenceId,
                'billingAddress'         => [
                    'firstName'   => $order['firstname'],
                    'lastName'    => $order['lastname'],
                    'address1'    => !empty($order['shipping_address_1'])
                        ? $order['shipping_address_1'] : $order['payment_address_1'],
                    'city'        => !empty($order['shipping_city']) ? $order['shipping_city'] : $order['payment_city'],
                    'countryCode' => !empty($order['shipping_iso_code_2'])
                        ? $order['shipping_iso_code_2'] : $order['payment_iso_code_2']
                ],
                'merchantOrderReference' => $order['order_id'],
                'emailAddress'           => $order['email'],
            ],
            'method' => 'POST',
            'uri'    => $this->library->getOrderRequestUrl(),
        ];
    }

    /**
     * Fetch order details
     *
     * @param string $order_ref
     *
     * @return array
     */
    public function fetchOrder(string $order_ref): array
    {
        return [
            'data'   => [],
            'method' => 'GET',
            'uri'    => $this->library->getFetchRequestUrl($order_ref)
        ];
    }

    /**
     * voidOrder
     *
     * @param string $order_ref
     * @param string $payment_ref
     *
     * @return array
     */
    public function voidOrder(string $order_ref, string $payment_ref): array
    {
        return [
            'data'   => [],
            'method' => 'PUT',
            'uri'    => $this->library->getOrderVoidUrl($order_ref, $payment_ref),
        ];
    }

    /**
     * captureOrder
     *
     * @param array $order_item
     * @param float $amount
     *
     * @return array
     */
    public function captureOrder(array $order_item, float $amount): array
    {
        return [
            'data'   => [
                'amount' => [
                    'currencyCode' => $order_item['currency'],
                    'value'        => strval(round($amount * 100)),
                ],
            ],
            'method' => 'POST',
            'uri'    => $this->library->getOrderCaptureUrl($order_item['reference'], $order_item['payment_id']),
        ];
    }

    /**
     * refundOrder
     *
     * @param array $order_item
     * @param float $amount
     * @param type $capture_id
     * @param null $url
     * @return array
     */
    public function refundOrder(array $order_item, float $amount, $capture_id, $url = null): array
    {
        return [
            'data'   => [
                'amount' => [
                    'currencyCode' => $order_item['currency'],
                    'value'        => strval(round($amount * 100)),
                ],
            ],
            'method' => 'POST',
            'uri'    => $url ?? $this->library->getOrderRefundUrl(
                $order_item['reference'],
                $order_item['payment_id'],
                $capture_id,
            ),
        ];
    }

    public function refundPurchase(array $order_item, float $amount): array
    {
        return [
            'data'   => [
                'amount' => [
                    'currencyCode' => $order_item['currency'],
                    'value'        => strval(round($amount * 100)),
                ],
            ],
            'method' => 'POST',
            'uri'    => $order_item['uri'],
        ];
    }

    public function voidPurchase(array $order_item, float $amount, $capture_id): array
    {
        return [
            'data'   => [
                'amount' => [
                    'currencyCode' => $order_item['currency'],
                    'value'        => strval(round($amount * 100)),
                ],
            ],
            'method' => 'PUT',
            'uri'    => $this->library->getPurchaseRefundUrl(
                $order_item['reference'],
                $order_item['payment_id'],
                $capture_id
            ),
        ];
    }

    public function orderStatus(array $data): array
    {
        return [
            'method' => 'GET',
            'uri'    => $this->library->getOrderStatusUrl(
                $data['reference']
            ),
        ];
    }
}
