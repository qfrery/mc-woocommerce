<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@mailchimp.com
 * Date: 7/14/16
 * Time: 10:57 AM
 */
class MailChimp_WooCommerce_Process_Products extends MailChimp_WooCommerce_Abtstract_Sync
{
    /**
     * @var string
     */
    protected $action = 'mailchimp_woocommerce_process_products';

    /**
     * @return string
     */
    public function getResourceType()
    {
        return 'products';
    }

    /**
     * @param MailChimp_Product $item
     *
     * @return mixed
     */
    protected function iterate($item) {

        if ($item instanceof MailChimp_Product) {

            // need to run the delete option on this before submitting because the API does not support PATCH yet.
            $this->mailchimp()->deleteStoreProduct($this->store_id, $item->getId());

            // add the product.
            try {
                $response = $this->mailchimp()->addStoreProduct($this->store_id, $item);
                mailchimp_log('sync.products.success', 'Added', array('api_response' => $response));
            } catch (MailChimp_Error $e) {
                mailchimp_log('sync.products.error', 'MailChimp_Error :: iterate :: '.$e->getMessage());
            } catch (MailChimp_ServerError $e) {
                mailchimp_log('sync.products.error', 'MailChimp_ServerError :: iterate :: '.$e->getMessage());
            }
        }

        return false;
    }

    /**
     * Called after all the products have been iterated and processed into MailChimp
     */
    protected function complete()
    {
        mailchimp_log('sync.products.completed', 'Done with the product sync :: queuing up the orders next!');

        // add a timestamp for the product sync completion
        $this->setResourceCompleteTime();

        // since the products are all good, let's sync up the orders now.
        wp_queue(new MailChimp_WooCommerce_Process_Orders());
    }
}
