<?php

/**
 * ModelExtensionPaymentNgenius class
 */
class ModelExtensionPaymentNgenius extends Model
{

    const UPDATE_LITERAL = "UPDATE ";

    /**
     *
     * @return array
     */
    public function getMethod()
    {
        $this->load->language('extension/payment/ngenius');

        if ($this->config->get('payment_ngenius_title') == "") {
            $title = $this->language->get('text_title');
        } else {
            $title = $this->config->get('payment_ngenius_title');
        }
        return array(
            'code'       => 'ngenius',
            'title'      => $title,
            'terms'      => '',
            'sort_order' => $this->config->get('payment_ngenius_sort_order')
        );
    }

    /**
     * Insert data to the ngenius table
     *
     * @param type $data
     *
     * @return last inserted id
     */
    public function insertOrderInfo(array $data)
    {
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "ngenius_networkinternational` SET order_id = {$data['order_id']}, amount = {$data['amount']}, currency = '{$data['currency']}', reference = '{$data['reference']}', action = '{$data['action']}', state = '{$data['state']}', status = '{$data['status']}' "
        );

        return $this->db->getLastId();
    }

    /**
     * Get order statues Id
     * @return array
     */
    public function getNgeniusStatusId(string $status)
    {
        $get_result = $this->db->query(
            "SELECT order_status_id FROM `" . DB_PREFIX . "order_status` WHERE name LIKE '%$status%'"
        );

        return $get_result->row['order_status_id'];
    }

    /**
     * Get all Order id
     *
     * @param int $order_id
     *
     * @return reult set
     */
    public function getOrder(int $order_id)
    {
        return $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "ngenius_networkinternational` WHERE order_id = {$order_id}"
        );
    }

    /**
     * Update the table
     *
     * @param array $data
     * @param int $nid
     *
     * @return result set
     */
    public function updateTable(array $data, int $nid)
    {
        return $this->db->query(
            self::UPDATE_LITERAL . DB_PREFIX . "ngenius_networkinternational SET state = '{$data['state']}', status = '{$data['status']}', payment_id = '{$data['payment_id']}', captured_amt = '{$data['captured_amt']}' WHERE nid = '{$nid}' "
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
    public function addTransaction($customer_id, $description = '', $amount = '', $order_id = 0)
    {
        $this->db->query(
            "INSERT INTO " . DB_PREFIX . "customer_transaction SET customer_id = '" . (int)$customer_id . "', order_id = '" . (int)$order_id . "', description = '" . $this->db->escape(
                $description
            ) . "', amount = '" . (float)$amount . "', date_added = NOW()"
        );
    }

    /**
     * Fetch data table
     *
     * @param string $where
     *
     * @return array
     */
    public function fetchOrder(string $where)
    {
        $get_result = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ngenius_networkinternational` WHERE $where");

        return $get_result->rows;
    }

    public function quantityReduce($order_id)
    {
        $this->load->model('checkout/order');
        $order_products = $this->model_checkout_order->getOrderProducts($order_id);

        foreach ($order_products as $order_product) {
            $this->db->query(
                self::UPDATE_LITERAL . DB_PREFIX . "product SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'"
            );

            $order_options = $this->model_checkout_order->getOrderOptions(
                $order_id,
                $order_product['order_product_id']
            );

            foreach ($order_options as $order_option) {
                $this->db->query(
                    self::UPDATE_LITERAL . DB_PREFIX . "product_option_value SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$order_option['product_option_value_id'] . "' AND subtract = '1'"
                );
            }
        }
    }

}
