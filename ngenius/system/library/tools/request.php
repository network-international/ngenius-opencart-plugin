<?php

namespace Opencart\System\Library\Tools;

use Ngenius\NgeniusCommon\Formatter\ValueFormatter;
use Ngenius\NgeniusCommon\NgeniusUtilities;

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
            'method' => 'POST',
            'uri'    => $this->library->getTokenRequestURL()
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

        $currencyCode = $order['currency_code'];
        $amount       = ValueFormatter::floatToIntRepresentation($currencyCode, $order['total']);
        $countryCode  = !empty($order['shipping_iso_code_2'])
            ? $order['shipping_iso_code_2'] : $order['payment_iso_code_2'];
        $utilities    = new NgeniusUtilities();

        return [
            'data'   => [
                'action'                 => $action,
                'amount'                 => [
                    'currencyCode' => $currencyCode,
                    'value'        => $amount,
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
                    'address2'    => !empty($order['shipping_address_2'])
                        ? $order['shipping_address_2'] : $order['payment_address_2'],
                    'city'        => !empty($order['shipping_city']) ? $order['shipping_city'] : $order['payment_city'],
                    'stateCode'   => !empty($order['shipping_zone']) ? $order['shipping_zone'] : $order['shipping_zone'],
                    'postalCode'  => !empty($order['shipping_postcode']) ? $order['shipping_postcode'] : $order['shipping_postcode'],
                    'countryCode' => $countryCode
                ],
                'merchantOrderReference' => $order['order_id'],
                'emailAddress'           => $order['email'],
                'phoneNumber'            => [
                    'countryCode' => (!empty($countryCode) ? $utilities->getCountryTelephonePrefix($countryCode) : ''),
                    'subscriber'  => $order['telephone'],
                ],
                'merchantDefinedData'    => [
                    'pluginName'    => 'opencart',
                    'pluginVersion' => $this->getPluginVersion(),
                ]
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
        $currencyCode = $order_item['currency'];
        $amount       = ValueFormatter::floatToIntRepresentation($currencyCode, $amount);

        return [
            'data'   => [
                'amount'              => [
                    'currencyCode' => $currencyCode,
                    'value'        => $amount,
                ],
                'merchantDefinedData' => [
                    'pluginName'    => 'opencart',
                    'pluginVersion' => $this->getPluginVersion(),
                ]
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
     *
     * @return array
     */
    public function refundOrder(array $order_item, float $amount, $capture_id, $url = null): array
    {
        $currencyCode = $order_item['currency'];
        $amount       = ValueFormatter::floatToIntRepresentation($currencyCode, $amount);

        return [
            'data'   => [
                'amount'              => [
                    'currencyCode' => $currencyCode,
                    'value'        => $amount,
                ],
                'merchantDefinedData' => [
                    'pluginName'    => 'opencart',
                    'pluginVersion' => $this->getPluginVersion(),
                ]
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
        $currencyCode = $order_item['currency'];
        $amount       = ValueFormatter::floatToIntRepresentation($currencyCode, $amount);

        return [
            'data'   => [
                'amount'              => [
                    'currencyCode' => $currencyCode,
                    'value'        => $amount,
                ],
                'merchantDefinedData' => [
                    'pluginName'    => 'opencart',
                    'pluginVersion' => $this->getPluginVersion(),
                ]
            ],
            'method' => 'POST',
            'uri'    => $order_item['uri'],
        ];
    }

    public function voidPurchase(array $order_item, float $amount, $capture_id): array
    {
        $currencyCode = $order_item['currency'];
        $amount       = ValueFormatter::floatToIntRepresentation($currencyCode, $amount);

        return [
            'data'   => [
                'amount'              => [
                    'currencyCode' => $currencyCode,
                    'value'        => $amount,
                ],
                'merchantDefinedData' => [
                    'pluginName'    => 'opencart',
                    'pluginVersion' => $this->getPluginVersion(),
                ]
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

    public function getPluginVersion()
    {
        $extensionPath   = 'extension/ngenius/';
        $installJsonPath = $extensionPath . 'install.json';

        if (file_exists($installJsonPath)) {
            $installJsonContents = file_get_contents($installJsonPath);
            $installData         = json_decode($installJsonContents, true);

            if (isset($installData['version'])) {
                $extensionVersion = $installData['version'];

                return $extensionVersion;
            } else {
                return 'Version information not found in opencart plugin';
            }
        } else {
            return 'install.json not found in opencart plugin';
        }
    }
}
