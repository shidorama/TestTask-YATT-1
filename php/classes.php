<?php

class Product implements Productable {
	protected $type;
	protected $cost;
	protected $discounted; //NOTE: should consider neccessity

	public function __construct($type, $cost) {
		$this->cost = (int) $cost;
		$this->type = $type;
		$this->discounted = FALSE;
	}

	public function getType() {
		return $this->type;
	}

	public function getPrice() {
		return $this->cost;
	}

	public function discount($precentage) {
		$discount = abs((int) $precentage); //We're not running almost any checks, so we're better check for sanity
		if ($discount < 100)
			$this->cost = $this->cost - round($this->cost*$discount/100, 2);
		$this->discounted = TRUE;
	}

	public function isDiscounted() {
		return $this->discounted;
	}
}

class Cart implements Cartable {
	protected $position;
	protected $cartContents;


	public function __construct() {
		$this->position = 0;
	}

	public function current () {
		return $this->cartContents[$this->position];
	}
	public function key () {
		return $this->position;
	}
	public function next () {
		++$this->position;
	}
	public function rewind () {
		$this->position = 0;
	}
	public function valid(){
		return isset($this->cartContents[$this->position]);
	}

	public function getCartContentsById($id) {
		if (isset($this->cartContents[$id]))
			return $this->cartContents[$id];
		return FALSE;
	}

	public function addProduct(Productable $product) {
		$this->cartContents[] = clone $product;
		return $this;
	}

	public function extractProduct(Productable $product) {
		foreach($this->cartContents as $key => $ooProducts) {
			if($product != $ooProducts)
				continue;
			unset($this->cartContents[$key]);
			$this->cartContents = array_values($this->cartContents);
			return TRUE;
		}
		return FALSE;
	}
}

class Calculator implements Calculatable {
	protected $cart;
	protected $discountManager;

	public function loadCart(Cartable $cart) {
		$this->cart = $cart;
		return $this;
	}

	public function installDiscountManager(DiscountManagereable $discManager) {
		$this->discountManager = $discManager;
		return $this;
	}

	public function getQuote() {
		if (!($this->discountManager instanceof DiscountManagereable))
			return FALSE; //it's maybe better to throw exceptions, but, well it's less hassle to just ret FALSE, so I'll assume that it'll do
			if (!($this->cart instanceof Cartable))
				return FALSE;
			$this->discountManager->processCart($this->cart);
			$quote = 0;
			foreach ($this->cart as $product)
				$quote += $product->getPrice();
			return $quote;
	}
}

class DiscountManager implements DiscountManagereable {
	protected $ruleset = array();
	protected $currentRule = array();
	protected $productSet;
	protected $cart;

	public function addQuantityRule() {
		$this->currentRule = array(
				'type' => 'qty',
				'products' => array(),
				'exceptions' => array(),
		);
		return $this;
	}

	public function addSetRule() {
		$this->currentRule = array(
				"type" => "set",
				'products' => array(),
				'exceptions' => array(),
		);
		return $this;
	}

	public function addProductToRule(Productable $product) {
		if($this->checkProductDouble($product))
			$this->currentRule['products'][] = $product;
		return $this;
	}

	public function addQuantityToRule($qty) {
		$this->currentRule['qty'] = abs($qty);
		return $this;
	}

	public function addExceptionToRule(Productable $product) {
		if($this->checkProductDoubleExcept($product))
			$this->currentRule['exceptions'][] = $product;
		return $this;
	}

	public function setDiscountAmount($discount) {
		$this->currentRule['discount'] = 0;
		if ($discount <= 100)
			$this->currentRule['discount'] = $discount;
		return $this;
	}

	public function finalizeDiscountRule() {
		$this->ruleset[] = $this->currentRule;
		$this->currentRule = NULL;
		return TRUE;
	}

	public function addProductSetToRule(Productable $product) {
		$this->productSet[] = $product;
		return $this;
	}

	public function endProductSetToRule() {
		$this->currentRule['products']['set'][] = $this->productSet;
		$this->productSet = array();
		return $this;
	}

	/**
	 * This method checks if product is already in this rule.
	 * @param Productable $product
	 * @return boolean
	 */
	protected function checkProductDouble(Productable $product) {
		foreach($this->currentRule['products'] as $productInRule) {
			if ($productInRule == $product)
				return FALSE;
		}
		return TRUE;
	}

	/**
	 * Similar to previous one, but this function checks for doubles in exception part of the rule.
	 * @param Productable $product
	 * @return boolean
	 */
	protected function checkProductDoubleExcept(Productable $product) {
		foreach($this->currentRule['exceptions'] as $productInRule) {
			if ($productInRule == $product)
				return FALSE;
		}
		return TRUE;
	}

	public function processCart(Cartable $cart) {
		//So fore each rule we're going through undiscounted cart items
		//and if any given set or quantity matches we're applying discount to the products
		$this->cart = $cart;
		foreach ($this->ruleset as $rule) {
			switch ($rule['type']) {
				case 'set':
					$this->processSetRule($rule);
					break;
				case 'qty':
					$this->processQtyRule($rule);
					break;
			}
		}
	}

	/**
	 * Method that can interpret and process set rule
	 * @param unknown $rule
	 * @return boolean
	 */
	protected function processSetRule($rule) {
		if (sizeof($rule['products']) < 1)
			return FALSE;
		$productset = array();
		foreach ($rule['products'] as $setProduct) {
			if (is_array($setProduct)) {
				if(!$productset[] = $this->openProductSetInSetRule($setProduct))
					return FALSE;
			} else {
				if(!$productset[] = $this->findUnusedProductByType($setProduct))
					return FALSE;
			}
		}
		foreach($productset as $product) {
			$product->discount($rule['discount']);
		}
	}

	/**
	 * If a rule has product set instead of one product inside of one
	 * of rule clauses we need to open array and then process it
	 * @param unknown $rule
	 * @return boolean|Productable
	 */
	protected function openProductSetInSetRule($rule) {
		if (sizeof($rule) < 1)
			return FALSE;
		foreach ($rule as $productSet) {
			if (sizeof($productSet) < 1)
				continue;
			foreach ($productSet as $product) {
				$rightProduct = $this->findUnusedProductByType($product);
				if ($rightProduct instanceof Productable)
					return $rightProduct;
			}
				
		}
		return FALSE;

	}

	/**
	 * This method processes quantity rules
	 * @param unknown $rule
	 * @return boolean
	 */
	protected function processQtyRule($rule) {
		if ($rule['qty'] < 1)
			return TRUE;
		$productSet = array();
		for ($i = 0; $i < $rule['qty']; $i++) {
			$product = $this->findAnyUnusedProducts($rule['exceptions']);
			if(!$product)
				return TRUE;
			$productSet[] = $product;
		}
		foreach($productSet as $product) {
			$product->discount($rule['discount']);
		}
		return TRUE;

	}

	/**
	 * This method tries to find undiscounted products inside cart
	 * that must be of certain types
	 * @param Productable $product
	 * @return unknown|boolean
	 */
	protected function findUnusedProductByType(Productable $product) {
		foreach($this->cart as $cartProduct) {
			if(!$cartProduct->isDiscounted()
					&& $cartProduct == $product)
						return $cartProduct;
		}
		return FALSE;
	}

	/**
	 * This method is for finding undiscounted products of any type
	 * @param unknown $exceptions
	 * @return unknown|boolean
	 */
	protected function findAnyUnusedProducts($exceptions) {
		foreach($this->cart as $cartProduct) {
			if (!$cartProduct->isDiscounted()
					&& $this->isNotException($cartProduct, $exceptions))
						return $cartProduct;
		}
		return FALSE;
	}

	/**
	 * This method is trying to figure out whether current product is exception in current discount rule or not
	 * @param Productable $cartProduct
	 * @param array $exceptions
	 * @return boolean
	 */
	protected function isNotException(Productable $cartProduct, array $exceptions) {
		if(sizeof($exceptions) < 0)
			return TRUE;
		foreach ($exceptions as $exceptionProduct) {
			if($exceptionProduct == $cartProduct)
				return FALSE;
		}
		return TRUE;
	}

}
