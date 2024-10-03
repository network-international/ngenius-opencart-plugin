<?php

namespace Opencart\System\Library\Tools;

use Ngenius\NgeniusCommon\Formatter\ValueFormatter;
use Ngenius\NgeniusCommon\Processor\ApiProcessor;

/**
 * Validate class
 */
class Validate
{
    public const LINKS_LITERAL    = "_links";
    public const EMBEDDED_LITERAL = "_embedded";
    public const CAPTURE_LITERAL  = "cnp:capture";
    public const REFUND_LITERAL   = "cnp:refund";


    /**
     * Validation for token
     *
     * @param string $response_enc
     *
     * @return string|array
     */
    public static function tokenValidate(string $response_enc): string|array
    {
        $response = json_decode($response_enc);

        return $response->access_token ?? ['error' => $response->errors[0]->message];
    }

    /**
     * Validation for sale and authorize
     *
     * @param string $response_enc
     *
     * @return bool|array|string
     */

    public static function paymentUrlValidate(string $response_enc): bool|array|string
    {
        if ($response_enc) {
            $response = json_decode($response_enc, true);
            if (isset($response['errors']) && is_array($response['errors'])) {
                return $response['errors'][0]['message'] ?? 'Failed';
            } elseif (isset($response[self::LINKS_LITERAL]['payment']['href'])) {
                $data              = [];
                $data['reference'] = $response['reference'] ?? '';
                $data['action']    = $response['action'] ?? '';
                $data['state']     = $response[self::EMBEDDED_LITERAL]['payment'][0]['state'] ?? '';

                return ['url' => $response[self::LINKS_LITERAL]['payment']['href'], 'data' => $data];
            }
        }

        return false;
    }

    /**
     * Validation for order details
     *
     * @param string $response_enc
     *
     * @return false|mixed|string
     */
    public static function orderValidate(string $response_enc)
    {
        if ($response_enc) {
            $response = json_decode($response_enc, true);
            if (isset($response['errors']) && is_array($response['errors'])) {
                return $response['errors'][0]['message'] ?? 'Failed';
            } else {
                return $response;
            }
        } else {
            return false;
        }
    }

    /**
     * Validation for void
     *
     * @param string $response_enc
     *
     * @return false|mixed|string|string[]
     */
    public static function voidValidate(string $response_enc): mixed
    {
        if ($response_enc) {
            $response = json_decode($response_enc, true);

            if (isset($response['errors']) && is_array($response['errors'])) {
                return $response['errors'][0]['message'] ?? 'Failed';
            } else {
                return [
                    'state' => $response['state'] ?? ''
                ];
            }
        } else {
            return false;
        }
    }

    /**
     * Validation for capture
     *
     * @param string $response_enc
     *
     * @return array|false|mixed|string
     */
    public static function captureValidate(string $response_enc): mixed
    {
        if (!$response_enc) {
            return false;
        }
        $response = json_decode($response_enc, true);
        if (isset($response['errors']) && is_array($response['errors'])) {
            return $response['errors'][0]['message'] ?? 'Failed';
        }

        $currencyCode = $response['amount']['currencyCode'];

        $amount = 0;
        if (isset($response[self::EMBEDDED_LITERAL][self::CAPTURE_LITERAL])
            && is_array($response[self::EMBEDDED_LITERAL][self::CAPTURE_LITERAL])
        ) {
            foreach ($response[self::EMBEDDED_LITERAL][self::CAPTURE_LITERAL] as $capture) {
                $last_transaction = end($response[self::EMBEDDED_LITERAL][self::CAPTURE_LITERAL]);
                if (isset($capture['state']) && ('SUCCESS' === $capture['state'])
                    && isset($capture['amount']['value'])
                ) {
                    $amount += $capture['amount']['value'];
                }
            }
        }

        $captured_amt = 0.00;
        if (isset($last_transaction['state']) && ('SUCCESS' === $last_transaction['state'])
            && isset($last_transaction['amount']['value'])
        ) {
            $captured_amt = ValueFormatter::intToFloatRepresentation($currencyCode, $last_transaction['amount']['value']);
        }

        $transaction_id = '';
        if (isset($last_transaction[self::LINKS_LITERAL]['self']['href'])) {
            $transaction_arr = explode('/', $last_transaction[self::LINKS_LITERAL]['self']['href']);
            $transaction_id  = end($transaction_arr);
        }
        $amount = ($amount > 0) ? ValueFormatter::intToFloatRepresentation($currencyCode, $amount) : 0;
        $state  = $response['state'] ?? '';

        return [
            'total_captured' => $amount,
            'captured_amt'   => $captured_amt,
            'state'          => $state,
            'transaction_id' => $transaction_id,
        ];
    }

    /**
     * refundValidate
     *
     * @param string $response_enc
     *
     * @return array|false|mixed|string
     */
    public static function refundValidate(string $response_enc): mixed
    {
        if (!$response_enc) {
            return false;
        }
        $response = json_decode($response_enc, true);
        if (isset($response['errors']) && is_array($response['errors'])) {
            return $response['errors'][0]['message'] ?? 'Failed';
        }

        $currencyCode = $response['amount']['currencyCode'];

        $capturedAmount = 0;
        if (isset($response['amount']['value'])) {
            $capturedAmount = ValueFormatter::intToFloatRepresentation($currencyCode, $response['amount']['value']);
        }

        $refunded_amt = 0;
        if (isset($response[self::EMBEDDED_LITERAL][self::REFUND_LITERAL])
            && is_array($response[self::EMBEDDED_LITERAL][self::REFUND_LITERAL])
        ) {
            $transaction_arr = end($response[self::EMBEDDED_LITERAL][self::REFUND_LITERAL]);
            foreach ($response[self::EMBEDDED_LITERAL][self::REFUND_LITERAL] as $refund) {
                if (isset($refund['state']) && ('SUCCESS' === $refund['state'] ||
                                                (isset($refund[self::LINKS_LITERAL]['cnp:china_union_pay_results'])
                                                 && $refund['state'] == 'REQUESTED')) && isset($refund['amount']['value'])
                ) {
                    $refunded_amt += $refund['amount']['value'];
                }
            }
        } else {
            $refunded_amt = $response['amount']['value'];
        }

        $refunded_amt = ValueFormatter::intToFloatRepresentation($currencyCode, $refunded_amt);

        if (isset($transaction_arr['state']) && ('SUCCESS' === $transaction_arr['state'] ||
                                                 (isset($transaction_arr[self::LINKS_LITERAL]['cnp:china_union_pay_results'])
                                                  && $transaction_arr['state'] == 'REQUESTED'))
            && isset($transaction_arr['amount']['value'])
        ) {
            $last_refunded_amt = ValueFormatter::intToFloatRepresentation($currencyCode, $transaction_arr['amount']['value']);
		} else {
            $last_refunded_amt = $refunded_amt;
        }

        if (isset($transaction_arr[self::LINKS_LITERAL]['self']['href'])) {
            $transaction_arr = explode('/', $transaction_arr[self::LINKS_LITERAL]['self']['href']);
            $transaction_id  = end($transaction_arr);
        } else {
            $transaction_id = $response['reference'] ?? '';
        }

        return [
            'captured_amt'   => $capturedAmount,
            'total_refunded' => $refunded_amt,
            'refunded_amt'   => $last_refunded_amt,
            'state'          => $response['state'] ?? '',
            'transaction_id' => $transaction_id,
        ];
    }
}
