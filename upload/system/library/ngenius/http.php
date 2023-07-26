<?php

namespace Ngenius;

use Ngenius\Transfer;

/**
 * Http class
 */
class Http
{

    /**
     * Place request to gateway
     *
     * @param Transfer $transfer
     *
     * @return array|string
     */
    public function placeRequest(Transfer $transfer)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $transfer->getUri());
        curl_setopt($ch, CURLOPT_HTTPHEADER, $transfer->getHeaders());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        if ("POST" === $transfer->getMethod()) {
            curl_setopt($ch, CURLOPT_POST, 1);
            if ($transfer->getBody()) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $transfer->getBody());
            }
        } elseif ("PUT" === $transfer->getMethod()) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $transfer->getMethod());
            curl_setopt($ch, CURLOPT_POSTFIELDS, $transfer->getBody());
        }

        $server_output = curl_exec($ch);
        $error         = curl_error($ch);
        curl_close($ch);

        if (is_string($server_output)) {
            return $server_output;
        }

        return ['error' => $error];
    }
}
