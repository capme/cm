<?php

namespace App\Model;

use Jenssegers\Mongodb\Model as Eloquent;

class SalesOrder extends Eloquent {

	protected $collection = 'salesorders';
}