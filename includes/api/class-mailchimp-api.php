<?php

/**
 * Created by PhpStorm.
 *
 * User: kingpin
 * Email: ryan@mailchimp.com
 * Date: 11/4/15
 * Time: 3:35 PM
 */
class MailChimpApi
{
    protected $version = '3.0';
    protected $data_center = 'us2';
    protected $api_key = null;
    protected $auth_type = 'key';

    /**
     * MailChimpService constructor.
     * @param null $api_key
     */
    public function __construct($api_key = null)
    {
        if (!empty($api_key)) {
            $this->setApiKey($api_key);
        }
    }

    /**
     * @param $key
     * @return $this
     */
    public function setApiKey($key)
    {
        $parts = str_getcsv($key, '-');

        if (count($parts) == 2) {
            $this->data_center = $parts[1];
        }

        $this->api_key = $parts[0];

        return $this;
    }

    /**
     * @param $dc
     * @return $this
     */
    public function setDataCenter($dc)
    {
        $this->data_center = $dc;

        return $this;
    }

    /**
     * @param $version
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return bool
     */
    public function ping()
    {
        try {
            $this->get('/');
            return true;
        } catch (MailChimp_Error $e) {
            return false;
        }
    }

    /**
     * @return array
     */
    public function getProfile()
    {
        return $this->get('/');
    }

    /**
     * @return array|bool
     */
    public function getAuthorizedApps()
    {
        return $this->get('authorized-apps');
    }

    /**
     * @return array|bool
     */
    public function getAuthorizedAppDetails($id)
    {
        return $this->get("authorized-apps/$id");
    }

    /**
     * Returns an array of ['access_token' => '', 'viewer_token' => '']
     *
     * @param $client_id
     * @param $client_secret
     * @return array|bool
     */
    public function linkAuthorizedApp($client_id, $client_secret)
    {
        return $this->post('authorized-apps', array('client_id' => $client_id, 'client_secret' => $client_secret));
    }

    /**
     * @param $list_id
     * @param $email
     * @return array|bool
     */
    public function member($list_id, $email)
    {
        $hash = md5(strtolower($email));
        return $this->get("lists/$list_id/members/$hash", array());
    }

    /**
     * @param $list_id
     * @return array|bool
     */
    public function members($list_id)
    {
        return $this->get("lists/$list_id/members");
    }

    /**
     * @param $list_id
     * @param $email
     * @param bool $subscribed
     * @param array $merge_fields
     * @param array $list_interests
     * @return array|bool
     */
    public function subscribe($list_id, $email, $subscribed = true, $merge_fields = array(), $list_interests = array())
    {
        $data = array(
            'email_type' => 'html',
            'email_address' => $email,
            'status' => $subscribed === true ? 'subscribed' : 'pending',
            'merge_fields' => $merge_fields,
            'interests' => $list_interests,
        );

        if (empty($data['merge_fields'])) {
            unset($data['merge_fields']);
        }

        if (empty($data['interests'])) {
            unset($data['interests']);
        }

        return $this->post("lists/$list_id/members", $data);
    }

    /**
     * @param $list_id
     * @param $email
     * @param bool $subscribed
     * @param array $merge_fields
     * @param array $list_interests
     * @return array|bool
     */
    public function update($list_id, $email, $subscribed = true, $merge_fields = array(), $list_interests = array())
    {
        $hash = md5(strtolower($email));

        $data = array(
            'email_address' => $email,
            'status' => ($subscribed === null ? 'cleaned' : ($subscribed === true ? 'subscribed' : 'unsubscribed')),
            'merge_fields' => $merge_fields,
            'interests' => $list_interests,
        );

        if (empty($data['merge_fields'])) {
            unset($data['merge_fields']);
        }


        if (empty($data['interests'])) {
            unset($data['interests']);
        }

        return $this->patch("lists/$list_id/members/$hash", $data);
    }

    /**
     * @param $list_id
     * @param $email
     * @param bool $subscribed
     * @param array $merge_fields
     * @param array $list_interests
     * @return array|bool
     */
    public function updateOrCreate($list_id, $email, $subscribed = true, $merge_fields = array(), $list_interests = array())
    {
        $hash = md5(strtolower($email));

        $data = array(
            'email_address' => $email,
            'status' => ($subscribed === null ? 'cleaned' : ($subscribed === true ? 'subscribed' : 'unsubscribed')),
            'status_if_new' => $subscribed === true ? 'subscribed' : 'pending',
            'merge_fields' => $merge_fields,
            'interests' => $list_interests,
        );

        if (empty($data['merge_fields'])) {
            unset($data['merge_fields']);
        }

        if (empty($data['interests'])) {
            unset($data['interests']);
        }

        return $this->put("lists/$list_id/members/$hash", $data);
    }

    /**
     * @param MailChimp_CreateListSubmission $submission
     * @return array|bool
     */
    public function createList(MailChimp_CreateListSubmission $submission)
    {
        return $this->post('lists', $submission->getSubmission());
    }

    /**
     * @param bool $as_list
     * @param int $count
     * @return array|mixed
     */
    public function getLists($as_list = false, $count = 50)
    {
        $result = $this->get('lists', array('count' => $count));

        if ($as_list) {
            $lists = array();
            if ($result) {
                $result = (object)$result;
                if (isset($result->lists) && is_array($result->lists)) {
                    foreach ($result->lists as $list) {
                        $list = (object)$list;
                        $lists[$list->id] = $list->name;
                    }
                }
            }

            return $lists;
        }

        return $result;
    }

    /**
     * @param $id
     * @return bool
     */
    public function hasList($id)
    {
        try {
            return (bool) $this->getList($id);
        } catch (\Exception $e) {
            return false;
        }
    }
    /**
     * @param $id
     * @return mixed
     */
    public function getList($id)
    {
        return $this->get('lists/' . $id);
    }

    /**
     * @param $id
     * @return array|bool
     */
    public function deleteList($id)
    {
        return $this->delete('lists/'.$id);
    }

    /**
     * @return array|mixed
     */
    public function getListsWithMergeFields()
    {
        $lists = $this->getLists(true);
        foreach ($lists as $id => $name) {
            $lists[$id] = $this->mergeFields($id, 50);
        }

        return $lists;
    }

    /**
     * @param $list_id
     * @param int $count
     * @return array|bool
     */
    public function mergeFields($list_id, $count = 10)
    {
        $result = $this->get("lists/$list_id/merge-fields", array('count' => $count,));

        return $result;
    }

    /**
     * @param $list_id
     * @return array|bool
     */
    public function getInterestGroups($list_id)
    {
        if (empty($list_id)) {
            return array();
        }
        $result = $this->get("lists/$list_id/interest-categories");

        return $result;
    }

    /**
     * @param $list_id
     * @param $group_id
     * @return array|bool
     */
    public function getInterestGroupOptions($list_id, $group_id)
    {
        if (empty($list_id) || empty($group_id)) {
            return array();
        }
        $result = $this->get("lists/$list_id/interest-categories/$group_id/interests");

        return $result;
    }

    /**
     * @param $store_id
     * @param int $page
     * @param int $count
     * @param DateTime|null $since
     * @param null $campaign_id
     * @return array|bool
     */
    public function orders($store_id, $page = 1, $count = 10, \DateTime $since = null, $campaign_id = null)
    {
        $result = $this->get('ecommerce/stores/'.$store_id.'/orders', array(
            'start' => $page,
            'count' => $count,
            'offset' => ($page * $count),
            'since' => $since ? $since->format('Y-m-d H:i:s') : null,
            'cid' => $campaign_id,
        ));

        return $result;
    }

    /**
     * @param $store_id
     * @return Store|bool
     */
    public function getStore($store_id)
    {
        try {
            $data = $this->get("ecommerce/stores/$store_id");
            if (!isset($data['id']) || !isset($data['name'])) {
                return false;
            }
            return (new MailChimp_Store)->fromArray($data);
        } catch (MailChimp_Error $e) {
            return false;
        }
    }

    /**
     * @return array|bool
     */
    public function stores()
    {
        try {
            $data = $this->get("ecommerce/stores");

            if (!isset($data['stores']) || empty($data['stores'])) {
                return array();
            }

            $response = array();

            foreach ($data['stores'] as $store_data) {
                $response[] = (new MailChimp_Store)->fromArray($store_data);
            }

            return $response;
        } catch (MailChimp_Error $e) {
            return false;
        }
    }

    /**
     * @param MailChimp_Store $store
     * @return MailChimp_Store
     * @throws MailChimp_Error
     */
    public function addStore(MailChimp_Store $store)
    {
        $this->validateStoreSubmission($store);
        $data = $this->post("ecommerce/stores", $store->toArray());
        return (new MailChimp_Store)->fromArray($data);
    }

    /**
     * @param MailChimp_Store $store
     * @return MailChimp_Store
     * @throws MailChimp_Error
     */
    public function updateStore(MailChimp_Store $store)
    {
        $this->validateStoreSubmission($store);
        $data = $this->patch("ecommerce/stores/{$store->getId()}", $store->toArray());
        return (new MailChimp_Store)->fromArray($data);
    }

    /**
     * @param $store_id
     * @return bool
     */
    public function deleteStore($store_id)
    {
        try {
            $this->delete("ecommerce/stores/$store_id");
            return true;
        } catch (MailChimp_Error $e) {
            return false;
        }
    }

    /**
     * @param $store_id
     * @param string $customer_id
     * @return MailChimp_Customer|bool
     */
    public function getCustomer($store_id, $customer_id)
    {
        try {
            $data = $this->get("ecommerce/stores/$store_id/customers/$customer_id");
            return (new MailChimp_Customer)->fromArray($data);
        } catch (MailChimp_Error $e) {
            return false;
        }
    }

    /**
     * @param MailChimp_Customer $store
     * @return MailChimp_Customer
     * @throws MailChimp_Error
     */
    public function addCustomer(MailChimp_Customer $store)
    {
        $this->validateStoreSubmission($store);
        $data = $this->post("ecommerce/stores", $store->toArray());
        return (new MailChimp_Customer)->fromArray($data);
    }

    /**
     * @param $store_id
     * @param int $page
     * @param int $count
     * @return array|bool
     */
    public function carts($store_id, $page = 1, $count = 10)
    {
        $result = $this->get('ecommerce/stores/'.$store_id.'/carts', array(
            'start' => $page,
            'count' => $count,
            'offset' => ($page * $count),
        ));

        return $result;
    }

    /**
     * @param $store_id
     * @param MailChimp_Cart $cart
     * @return bool|MailChimp_Cart
     */
    public function addCart($store_id, MailChimp_Cart $cart)
    {
        try {
            $data = $this->post("/ecommerce/stores/$store_id/carts", $cart->toArray());
            return (new MailChimp_Cart)->fromArray($data);
        } catch (MailChimp_Error $e) {
            error_log('MailChimpApi::addCart - '.$e->getMessage());
            return false;
        }
    }

    /**
     * @param $store_id
     * @param MailChimp_Cart $cart
     * @return bool|MailChimp_Cart
     */
    public function updateCart($store_id, MailChimp_Cart $cart)
    {
        try {
            $data = $this->patch("/ecommerce/stores/$store_id/carts/{$cart->getId()}", $cart->toArrayForUpdate());
            return (new MailChimp_Cart)->fromArray($data);
        } catch (MailChimp_Error $e) {
            error_log('MailChimpApi::updateCart - '.$e->getMessage());
            return false;
        }
    }

    /**
     * @param $store_id
     * @param $id
     * @return bool|MailChimp_Cart
     */
    public function getCart($store_id, $id)
    {
        try {
            $data = $this->get("/ecommerce/stores/$store_id/carts/$id");
            return (new MailChimp_Cart)->fromArray($data);
        } catch (MailChimp_Error $e) {
            return false;
        }
    }

    /**
     * @param $store_id
     * @param $id
     * @return bool
     */
    public function deleteCartByID($store_id, $id)
    {
        try {
            $this->delete("/ecommerce/stores/$store_id/carts/$id");
            return true;
        } catch (MailChimp_Error $e) {
            return false;
        }
    }

    /**
     * @param $store_id
     * @param MailChimp_Customer $customer
     * @return MailChimp_Customer|bool
     * @throws MailChimp_Error
     */
    public function updateCustomer($store_id, MailChimp_Customer $customer)
    {
        $this->validateStoreSubmission($customer);
        $data = $this->patch("ecommerce/stores/$store_id/customers/{$customer->getId()}", $customer->toArray());
        return (new MailChimp_Customer)->fromArray($data);
    }

    /**
     * @param $store_id
     * @param $customer_id
     * @return bool
     */
    public function deleteCustomer($store_id, $customer_id)
    {
        try {
            $this->delete("ecommerce/stores/$store_id/customers/$customer_id");
            return true;
        } catch (MailChimp_Error $e) {
            return false;
        }
    }

    /**
     * @param $store_id
     * @param MailChimp_Order $order
     * @return MailChimp_Order|bool
     * @throws MailChimp_Error
     */
    public function addStoreOrder($store_id, MailChimp_Order $order)
    {
        $this->validateStoreSubmission($order);
        $data = $this->post("ecommerce/stores/$store_id/orders", $order->toArray());
        return (new MailChimp_Order)->fromArray($data);
    }

    /**
     * @param $store_id
     * @param MailChimp_Order $order
     * @return MailChimp_Order|bool
     * @throws MailChimp_Error
     */
    public function updateStoreOrder($store_id, MailChimp_Order $order)
    {
        $this->validateStoreSubmission($order);
        $id = $order->getId();
        $data = $this->patch("ecommerce/stores/$store_id/orders/$id", $order->toArray());
        return (new MailChimp_Order)->fromArray($data);
    }

    /**
     * @param $store_id
     * @param $order_id
     * @return MailChimp_Order|bool
     */
    public function getStoreOrder($store_id, $order_id)
    {
        try {
            $data = $this->get("ecommerce/stores/$store_id/orders/$order_id");
            return (new MailChimp_Order)->fromArray($data);
        } catch (MailChimp_Error $e) {
            return false;
        }
    }

    /**
     * @param $store_id
     * @param $order_id
     * @return bool
     */
    public function deleteStoreOrder($store_id, $order_id)
    {
        try {
            $this->delete("ecommerce/stores/$store_id/orders/$order_id");
            return true;
        } catch (MailChimp_Error $e) {
            return false;
        }
    }

    /**
     * @param $store_id
     * @param $product_id
     * @return MailChimp_Product|bool
     */
    public function getStoreProduct($store_id, $product_id)
    {
        try {
            $data = $this->get("ecommerce/stores/$store_id/products/$product_id");
            return (new MailChimp_Product)->fromArray($data);
        } catch (MailChimp_Error $e) {
            return false;
        }
    }

    /**
     * @param $store_id
     * @param int $page
     * @param int $count
     * @return array|bool
     */
    public function products($store_id, $page = 1, $count = 10)
    {
        $result = $this->get('ecommerce/stores/'.$store_id.'/products', array(
            'start' => $page,
            'count' => $count,
            'offset' => ($page * $count),
        ));

        return $result;
    }

    /**
     * @param $store_id
     * @param MailChimp_Product $product
     * @return MailChimp_Product|bool
     * @throws MailChimp_Error
     */
    public function addStoreProduct($store_id, MailChimp_Product $product)
    {
        $this->validateStoreSubmission($product);

        $data = $this->post("ecommerce/stores/$store_id/products", $product->toArray());

        return (new MailChimp_Product)->fromArray($data);
    }

    /**
     * @param $store_id
     * @param $product_id
     * @return bool
     */
    public function deleteStoreProduct($store_id, $product_id)
    {
        try {
            $this->delete("ecommerce/stores/$store_id/products/$product_id");
            return true;
        } catch (MailChimp_Error $e) {
            return false;
        }
    }

    /**
     * @param MailChimp_Store|MailChimp_Order|MailChimp_Product|MailChimp_Customer $target
     * @return bool
     * @throws MailChimp_Error
     */
    protected function validateStoreSubmission($target)
    {
        return true;
    }

    /**
     * @param $url
     * @param null $params
     * @return array|bool
     * @throws MailChimp_Error
     */
    protected function delete($url, $params = null)
    {
        $curl = curl_init();

        curl_setopt_array($curl, $this->applyCurlOptions('DELETE', $url, $params));

        return $this->processCurlResponse($curl);
    }

    /**
     * @param $url
     * @param null $params
     * @return array|bool
     * @throws MailChimp_Error
     */
    protected function get($url, $params = null)
    {
        $curl = curl_init();

        curl_setopt_array($curl, $this->applyCurlOptions('GET', $url, $params));

        return $this->processCurlResponse($curl);
    }

    /**
     * @param $url
     * @param $body
     * @return array|bool
     * @throws MailChimp_Error
     */
    protected function patch($url, $body)
    {
        $curl = curl_init();

        $options = $this->applyCurlOptions('PATCH', $url, array());
        $options[CURLOPT_POSTFIELDS] = json_encode($body);

        curl_setopt_array($curl, $options);

        return $this->processCurlResponse($curl);
    }

    /**
     * @param $url
     * @param $body
     * @return array|bool
     * @throws MailChimp_Error
     */
    protected function post($url, $body)
    {
        $curl = curl_init();

        $options = $this->applyCurlOptions('POST', $url, array());
        $options[CURLOPT_POSTFIELDS] = json_encode($body);

        curl_setopt_array($curl, $options);

        return $this->processCurlResponse($curl);
    }

    /**
     * @param $url
     * @param $body
     * @return array|bool
     * @throws MailChimp_Error
     */
    protected function put($url, $body)
    {
        $curl = curl_init();

        $options = $this->applyCurlOptions('PUT', $url, array());
        $options[CURLOPT_POSTFIELDS] = json_encode($body);

        curl_setopt_array($curl, $options);

        return $this->processCurlResponse($curl);
    }

    /**
     * @param string $extra
     * @param null|array $params
     * @return string
     */
    protected function url($extra = '', $params = null)
    {
        $url = "https://{$this->data_center}.api.mailchimp.com/{$this->version}/";

        if (!empty($extra)) {
            $url .= $extra;
        }

        if (!empty($params)) {
            $url .= '?'.(is_array($params) ? http_build_query($params) : $params);
        }

        return $url;
    }

    /**
     * @param $method
     * @param $url
     * @param array $params
     * @return array
     */
    protected function applyCurlOptions($method, $url, $params = array())
    {
        return array(
            CURLOPT_USERPWD => "mailchimp:{$this->api_key}",
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_URL => $this->url($url, $params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HEADER => false,
            //CURLOPT_HEADER => ['content-type: application/json', 'allow: application/json',]
        );
    }

    /**
     * @param $curl
     * @return mixed
     * @throws MailChimp_Error
     * @throws MailChimp_ServerError
     */
    protected function processCurlResponse($curl)
    {
        $response = curl_exec($curl);
        $err = curl_error($curl);

        $info = curl_getinfo($curl);

        curl_close($curl);

        if ($err) {
            throw new MailChimp_Error('CURL error :: '.$err, '500');
        }

        /*
        $response = explode("\r\n\r\nHTTP/", $response, 2);    //to deal with "HTTP/1.1 100 Continue\r\n\r\nHTTP/1.1 200 OK...\r\n\r\n..." header
        $response = (count($response) > 1 ? 'HTTP/' : '').array_pop($response);

        list($str_resp_headers, $response) = explode("\r\n\r\n", $response, 2);

        $str_resp_headers = explode("\r\n", $str_resp_headers);
        array_shift($str_resp_headers);    //get rid of "HTTP/1.1 200 OK"
        $resp_headers = array();

        foreach ($str_resp_headers as $k => $v) {
            $v = explode(': ', $v, 2);
            $resp_headers[$v[0]] = $v[1];
        }
        */

        $data = json_decode($response, true);

        if (empty($info) || ($info['http_code'] >= 200 && $info['http_code'] <= 400)) {
            if (is_array($data)) {
                $this->checkForErrors($data);
            }
            return $data;
        }

        if ($info['http_code'] >= 400 && $info['http_code'] <= 500) {
            error_log('MailChimpApi::processCurlResponse - '.$data['title'] .' :: '.$data['detail']);
            throw new MailChimp_Error($data['title'] .' :: '.$data['detail'], $data['status']);
        }

        if ($info['http_code'] >= 500) {
            error_log('MailChimpApi::processCurlResponse - '.$data['detail']);
            throw new MailChimp_ServerError($data['detail'], $data['status']);
        }

        return null;
    }

    /**
     * @param array $data
     * @return bool
     * @throws MailChimp_Error
     */
    protected function checkForErrors(array $data)
    {
        // if we have an array of error data push it into a message
        if (isset($data['errors'])) {
            $message = '';
            foreach ($data['errors'] as $error) {
                $message .= '<p>'.$error['field'].': '.$error['message'].'</p>';
            }
            error_log('MailChimpApi::checkForErrors - '.$message);
            throw new MailChimp_Error($message, $data['status']);
        }

        // make sure the response is correct from the data in the response array
        if (isset($data['status']) && $data['status'] >= 400) {
            error_log('MailChimpApi::checkForErrors - '.$data['detail']);
            throw new MailChimp_Error($data['detail'], $data['status']);
        }

        return false;
    }
}