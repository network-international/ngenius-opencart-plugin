<?php

namespace Ngenius;

/**
 * Transfer class.
 */
class Transfer
{

    /**
     * @var array
     */
    private $headers = array();

    /**
     * @var array
     */
    private $body = array();

    /**
     * @var api curl uri
     */
    private $uri = '';

    /**
     * @var method
     */
    private $method;

    /**
     * Builds gateway transfer object
     *
     * @param array $request
     *
     * @return Transfer
     */
    public function create(array $request, string $token)
    {
        if (is_array($request)) {
            return $this->setBody(json_encode($request['data'] ?? []))
                        ->setMethod($request['method'])
                        ->setHeaders(
                            array(
                                'Authorization: Bearer ' . $token,
                                'Content-Type: application/vnd.ni-payment.v2+json',
                                'Accept: application/vnd.ni-payment.v2+json',
                            )
                        )
                        ->setUri($request['uri']);
        }
    }

    /**
     * Builds gateway transfer object
     *
     * @param array $request
     *
     * @return Transfer
     */
    public function forToken(array $request)
    {
        if (is_array($request)) {
            return $this->setBody($request['data'])
                        ->setMethod($request['method'])
                        ->setHeaders($request['headers'])
                        ->setUri($request['uri']);
        }
    }

    /**
     * Set header for transfer object
     *
     * @param array $headers
     *
     * @return Transfer
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Set body for transfer object
     *
     * @param array $body
     *
     * @return Transfer
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Set method for transfer object
     *
     * @param array $method
     *
     * @return Transfer
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Set uri for transfer object
     *
     * @param array $uri
     *
     * @return Transfer
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * Retrieve method from transfer object
     *
     * @return string
     */
    public function getMethod()
    {
        return (string)$this->method;
    }

    /**
     * Retrieve header from transfer object
     *
     * @return Transfer
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Retrieve body from transfer object
     *
     * @return Transfer
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Retrieve uri from transfer object
     *
     * @return string
     */
    public function getUri()
    {
        return (string)$this->uri;
    }
}
