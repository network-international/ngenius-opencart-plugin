<?php

class ControllerExtensionReportNgenius extends Controller
{


    const REPORT_EXTENSION_LITERAL = "extension/report/ngenius";
    const TOKEN_LITERAL = "user_token=";

    public function index()
    {
        $this->load->language(self::REPORT_EXTENSION_LITERAL);

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model(self::REPORT_EXTENSION_LITERAL);
        $this->getList();
    }

    protected function getList()
    {
        $filter_amount       = '';
        $filter_order_id     = '';
        $filter_reference    = '';
        $filter_action       = '';
        $filter_state        = '';
        $filter_status       = '';
        $filter_payment_id   = '';
        $filter_captured_amt = '';
        // order Id

        if (isset($this->request->get['filter_order_id'])) {
            $filter_order_id = $this->request->get['filter_order_id'];
        }

        // amount
        if (isset($this->request->get['filter_amount'])) {
            $filter_amount = $this->request->get['filter_amount'];
        }

        // reference
        if (isset($this->request->get['filter_reference'])) {
            $filter_reference = $this->request->get['filter_reference'];
        }

        //action
        if (isset($this->request->get['filter_action'])) {
            $filter_action = $this->request->get['filter_action'];
        }

        //state
        if (isset($this->request->get['filter_state'])) {
            $filter_state = $this->request->get['filter_state'];
        }

        //status
        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        }

        //payment id
        if (isset($this->request->get['filter_payment_id'])) {
            $filter_payment_id = $this->request->get['filter_payment_id'];
        }

        //captured_amt
        if (isset($this->request->get['filter_captured_amt'])) {
            $filter_captured_amt = $this->request->get['filter_captured_amt'];
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'o.order_id';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_order_id'])) {
            $url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
        }

        if (isset($this->request->get['filter_amount'])) {
            $url .= '&filter_amount=' . $this->request->get['filter_amount'];
        }

        if (isset($this->request->get['filter_reference'])) {
            $url .= '&filter_reference=' . $this->request->get['filter_reference'];
        }

        if (isset($this->request->get['filter_action'])) {
            $url .= '&filter_action=' . $this->request->get['filter_action'];
        }

        if (isset($this->request->get['filter_state'])) {
            $url .= '&filter_state=' . $this->request->get['filter_state'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }


        if (isset($this->request->get['filter_payment_id'])) {
            $url .= '&filter_payment_id=' . $this->request->get['filter_payment_id'];
        }


        if (isset($this->request->get['filter_captured_amt'])) {
            $url .= '&filter_captured_amt=' . $this->request->get['filter_captured_amt'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', self::TOKEN_LITERAL . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link(
                self::REPORT_EXTENSION_LITERAL,
                self::TOKEN_LITERAL . $this->session->data['user_token'] . $url,
                true
            )
        );
        $data['orders']        = array();

        $filter_data = array(
            'filter_order_id'     => $filter_order_id,
            'filter_reference'    => $filter_reference,
            'filter_amount'       => $filter_amount,
            'filter_action'       => $filter_action,
            'filter_state'        => $filter_state,
            'filter_status'       => $filter_status,
            'filter_payment_id'   => $filter_payment_id,
            'filter_captured_amt' => $filter_captured_amt,
            'sort'                => $sort,
            'order'               => $order,
            'start'               => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'               => $this->config->get('config_limit_admin')
        );

        $order_total = $this->model_extension_report_ngenius->getTotalOrders($filter_data);
        $results     = $this->model_extension_report_ngenius->getOrders($filter_data);

        foreach ($results as $result) {
            $data['orders'][] = array(
                'nid'         => $result['nid'],
                'order_id'    => $result['order_id'],
                'amount'      => $result['currency'] . number_format($result['amount'], 2),
                'currency'    => $result['currency'],
                'reference'   => $result['reference'],
                'action'      => $result['action'],
                'status'      => $result['status'],
                'state'       => $result['state'],
                'created_at'  => $result['created_at'],
                'id_payment'  => $result['payment_id'],
                'capture_amt' => $result['currency'] . number_format($result['captured_amt'], 2),
            );
        }

        $data['user_token'] = $this->session->data['user_token'];

        $pagination        = new Pagination();
        $pagination->total = $order_total;
        $pagination->page  = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link(
            self::REPORT_EXTENSION_LITERAL,
            self::TOKEN_LITERAL . $this->session->data['user_token'] . '&page={page}',
            true
        );

        $data['pagination'] = $pagination->render();
        $data['results']    = sprintf(
            $this->language->get('text_pagination'),
            ($order_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0,
            ((($page - 1) * $this->config->get(
                        'config_limit_admin'
                    )) > ($order_total - $this->config->get(
                        'config_limit_admin'
                    ))) ? $order_total : ((($page - 1) * $this->config->get(
                        'config_limit_admin'
                    )) + $this->config->get('config_limit_admin')),
            $order_total,
            ceil($order_total / $this->config->get('config_limit_admin'))
        );

        $data['filter_order_id']     = $filter_order_id;
        $data['filter_amount']       = $filter_amount;
        $data['filter_reference']    = $filter_reference;
        $data['filter_action']       = $filter_action;
        $data['filter_state']        = $filter_state;
        $data['filter_status']       = $filter_status;
        $data['filter_payment_id']   = $filter_payment_id;
        $data['filter_captured_amt'] = $filter_captured_amt;

        $data['sort']  = $sort;
        $data['order'] = $order;

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_extension_report_ngenius->getNgeniusOrderSatuts();

        $data['actions'] = array('SALE' => 'SALE', 'AUTH' => 'AUTH');


        // API login
        $data['catalog'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;

        // API login
        $this->load->model('user/api');

        $api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));

        if ($api_info && $this->user->hasPermission('modify', 'sale/order')) {
            $session = new Session($this->config->get('session_engine'), $this->registry);

            $session->start();

            $this->model_user_api->deleteApiSessionBySessonId($session->getId());

            $this->model_user_api->addApiSession(
                $api_info['api_id'],
                $session->getId(),
                $this->request->server['REMOTE_ADDR']
            );

            $session->data['api_id'] = $api_info['api_id'];

            $data['api_token'] = $session->getId();
        } else {
            $data['api_token'] = '';
        }

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/report/ngenius_order_list', $data));
    }
}
