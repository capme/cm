<?php

namespace App\Model;

use Jenssegers\Mongodb\Model as Eloquent;

class Product extends Eloquent {

    protected $collection = 'channelproducts';
}