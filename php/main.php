<?php
require 'interfaces.php';
require 'classes.php';
//Lets init products instances
// we're going for A, B, C, D, E, F, G, H, I, J, K, L, M
$prodA = new Product("A", 100);
$prodB = new Product("B", 200);
$prodD = new Product("D", 250);
$prodC = new Product("C", 300);
$prodE = new Product("E", 400);
$prodF = new Product("F", 500);
$prodG = new Product("G", 600);
$prodH = new Product("H", 700);
$prodI = new Product("I", 800);
$prodJ = new Product("J", 900);
$prodK = new Product("K", 1000);
$prodL = new Product("L", 1100);
$prodM = new Product("M", 1200);

//Let's init discounts
$dm = new DiscountManager(); //Discount Manager, not Dungeon Master

$dm->addSetRule()
->addProductToRule($prodA)
->addProductToRule($prodB)
->setDiscountAmount(10)
->finalizeDiscountRule();

$dm->addSetRule()
->addProductToRule($prodD)
->addProductToRule($prodE)
->setDiscountAmount(5)
->finalizeDiscountRule();

$dm->addSetRule()
->addProductToRule($prodE)
->addProductToRule($prodF)
->addProductToRule($prodG)
->setDiscountAmount(5)
->finalizeDiscountRule();

$dm->addSetRule()
->addProductToRule($prodA)
->addProductSetToRule($prodK)
->addProductSetToRule($prodL)
->addProductSetToRule($prodM)
->endProductSetToRule()
->setDiscountAmount(5)
->finalizeDiscountRule();

$dm->addQuantityRule()
->addQuantityToRule(3)
->addExceptionToRule($prodA)
->addExceptionToRule($prodC)
->setDiscountAmount(5)
->finalizeDiscountRule();

$dm->addQuantityRule()
->addQuantityToRule(4)
->addExceptionToRule($prodA)
->addExceptionToRule($prodC)
->setDiscountAmount(10)
->finalizeDiscountRule();

$dm->addQuantityRule()
->addQuantityToRule(5)
->addExceptionToRule($prodA)
->addExceptionToRule($prodC)
->setDiscountAmount(20)
->finalizeDiscountRule();

//let's then initialize cart and fill it with products

$cart = new Cart();

$cart->addProduct($prodA)
->addProduct($prodB)
->addProduct($prodB)
->addProduct($prodC)
->addProduct($prodK)
->addProduct($prodL)
->addProduct($prodA)
->addProduct($prodB)
->addProduct($prodB)
->addProduct($prodB);


//So, let's prepare The Calculator
$calculator = new Calculator();
$calculator->installDiscountManager($dm); //go go power^W rangers^W DM

$calculator->loadCart($cart);

$quote = $calculator->getQuote();

var_dump($cart);
var_dump($quote);


