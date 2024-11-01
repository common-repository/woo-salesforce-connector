<?php


class WP_Synchronized_Process_Product extends WP_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 'synchronized_process_product';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */

	public function log( $message ) {
		error_log( $message );
	}
	protected function task( $item ) {
		
		$item = ($item == '')?'A':$item;
		$a = new WooSfRest();
		
		// $this->log($item .' : processed successfully'.get_option('products_processed'));

		$result =  call_user_func(array($a,'wooSfExportProductData'),$item);

		
		return ($result)?$result:false;
	}
	
	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		$this->log('Done');
		delete_metadata('post',0, 'product_Synced','',true);

		parent::complete();
	}

}