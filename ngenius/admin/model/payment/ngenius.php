<?php

namespace Opencart\Admin\Model\Extension\Ngenius\Payment;

use Ngenius\NgeniusCommon\NgeniusHTTPCommon;
use Ngenius\NgeniusCommon\NgeniusHTTPTransfer;
use Opencart\System\Engine\Model;
use Opencart\System\Library\Ngenius as NgeniusTools;
use Opencart\System\Library\Tools\Request;
use Opencart\System\Library\Tools\Validate;

/**
 * ModelExtensionPaymentNgenius class
 */
class Ngenius extends Model
{
    public const UPDATE_LITERAL = "UPDATE ";
    private NgeniusTools $ngenius;

    /**
     * Table Installation
     */
    public function install(): void
    {
        $this->db->query(
            "
                CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ngenius_networkinternational` (
                    `nid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'n-genius Id',
                    `order_id` varchar(55) NOT NULL COMMENT 'Order Id',
                    `amount` decimal(12,4) UNSIGNED NOT NULL COMMENT 'Amount',
                    `currency` varchar(3) NOT NULL COMMENT 'Currency',
                    `reference` text NOT NULL COMMENT 'Reference',
                    `action` varchar(20) NOT NULL COMMENT 'Action',
                    `state` varchar(20) NOT NULL COMMENT 'State',
                    `status` varchar(50) NOT NULL COMMENT 'Status',
                    `payment_id` text NOT NULL COMMENT 'Payment Id',
                    `captured_amt` decimal(12,4) UNSIGNED NOT NULL COMMENT 'Captured Amount',
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                     COMMENT 'Created At',
                    PRIMARY KEY (`nid`),
                    UNIQUE KEY `NGENIUS_ONLINE_ORDER_ID` (`order_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='n-genius order table';"
        );

        $this->enablePermission();
    }

    /**
     * uninstall
     */
    public function uninstall()
    {
        // No uninstall actions
    }

    /**
     * Get order details from table
     *
     * @param int $orderId
     *
     * @return mixed
     */
    public function getOrder(int $orderId): mixed
    {
        $sql   = "SELECT * FROM " . DB_PREFIX . "ngenius_networkinternational" . " WHERE order_id = '" . $orderId . "'";
        $query = $this->db->query($sql);

        return $query->row;
    }

    /**
     * Get customer transaction details
     *
     * @param int $orderId
     *
     * @return mixed
     */
    public function getCustomerTransaction(int $orderId): mixed
    {
        $sql   = "SELECT * FROM " . DB_PREFIX . "customer_transaction"
                 . " WHERE order_id = '" . $orderId . "' ORDER BY customer_transaction_id DESC";
        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * Get the refunded transaction details
     *
     */
    public function getRefundedTransaction($orderId, $parentCaptureId)
    {
        $customerTransactions = $this->getCustomerTransaction($orderId);
        $refundAmount         = 0;
        foreach ($customerTransactions as $key => $transaction) {
            $jsonData = json_decode($transaction['description'], true);
            if ($jsonData) {
                foreach ($jsonData as $key => $value) {
                    if ($key === 'parentCaptureId' && $jsonData['parentCaptureId'] == $parentCaptureId) {
                        $refundAmount = $refundAmount + $transaction['amount'];
                    }
                }
            }
        }

        return $refundAmount;
    }

    /**
     * Update table
     *
     * @param array $data_table
     * @param int $order_id
     *
     * @return mixed
     */
    public function updateTable(array $data_table, int $order_id): mixed
    {
        $query = '';
        if (isset($data_table['captured_amt'])) {
            $query = ", captured_amt = '{$data_table['captured_amt']}'";
        }

        return $this->db->query(
            self::UPDATE_LITERAL . DB_PREFIX
            . "ngenius_networkinternational SET state = '{$data_table['state']}',
             status = '{$data_table['status']}' {$query}  WHERE order_id = '{$order_id}' "
        );
    }

    /**
     * Insert data to the table
     *
     * @param type $customer_id
     * @param type $description
     * @param type $amount
     * @param type $order_id
     */
    public function addTransaction($customer_id, $description = '', $amount = '', $order_id = 0): void
    {
        $this->db->query(
            "INSERT INTO " . DB_PREFIX . "customer_transaction SET customer_id = '"
            . (int)$customer_id . "', order_id = '" . (int)$order_id . "', description = '" . $this->db->escape(
                $description
            ) . "', amount = '" . (float)$amount . "', date_added = NOW()"
        );
    }

    /**
     * Get order statues Id
     */
    public function getNgeniusStatusId(string $status): int
    {
        $get_result = $this->db->query(
            "SELECT order_status_id FROM `" . DB_PREFIX . "order_status` WHERE name LIKE '%$status%'"
        );

        return (int)$get_result->row['order_status_id'];
    }

    /**
     * Add to Order History
     *
     */
    public function addOrderHistory($order_id, $order_status_id, $comment = '', $notify = false): void
    {
        $this->db->query(
            "UPDATE `" . DB_PREFIX . "order` SET order_status_id = '"
            . (int)$order_status_id . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'"
        );
        $this->db->query(
            "INSERT INTO " . DB_PREFIX . "order_history SET order_id = '"
            . (int)$order_id . "', order_status_id = '" . (int)$order_status_id . "', notify = '"
            . (int)$notify . "', comment = '" . $this->db->escape(
                $comment
            ) . "', date_added = NOW()"
        );
    }

    /**
     * Give permission to the added plugin files/folders
     */
    public function enablePermission(): void
    {
        $this->load->model('user/user_group');
        $this->model_user_user_group->addPermission($this->user->getId(), 'access', 'extension/report/ngenius');
        $this->model_user_user_group->addPermission($this->user->getId(), 'modify', 'extension/report/ngenius');
    }

    /**
     * Update to product table
     *
     */
    public function updateProduct($order_products): void
    {
        $this->load->model('sale/order');
        foreach ($order_products as $order_product) {
            $this->db->query(
                self::UPDATE_LITERAL . DB_PREFIX . "product SET quantity = (quantity + "
                . (int)$order_product['quantity'] . ") WHERE product_id = '"
                . (int)$order_product['product_id'] . "' AND subtract = '1'"
            );

            $order_options = $this->model_sale_order->getOptions(
                $order_product['order_id'],
                $order_product['order_product_id']
            );

            foreach ($order_options as $order_option) {
                $this->db->query(
                    self::UPDATE_LITERAL . DB_PREFIX . "product_option_value SET quantity = (quantity + "
                    . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '"
                    . (int)$order_option['product_option_value_id'] . "' AND subtract = '1'"
                );
            }
        }
    }

    /**
     * @param $ngeniusReference
     *
     * @return array|string|bool
     */
    public function getNGeniusOrder($ngeniusReference): array|string|bool
    {
        $this->ngenius = new NgeniusTools($this->registry);

        $request = new Request($this->ngenius);

        $httpTransfer = new NgeniusHTTPTransfer("");

        $httpTransfer->setTokenHeaders($this->ngenius->getApiKey());
        $httpTransfer->setHttpVersion($this->ngenius->getHttpVersion());

        $httpTransfer->build($request->tokenRequest());

        $token = NgeniusHTTPCommon::placeRequest($httpTransfer);

        if (is_string($token)) {
            $token = Validate::tokenValidate($token);

            $httpTransfer->setPaymentHeaders($token);

            $httpTransfer->build($request->fetchOrder($ngeniusReference));

            return Validate::orderValidate(
                NgeniusHTTPCommon::placeRequest($httpTransfer)
            );
        }

        return false;
    }
}
