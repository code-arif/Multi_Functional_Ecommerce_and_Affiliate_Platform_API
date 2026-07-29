<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Modules\Core\Traits\ApiResponse;

abstract class BaseController extends Controller
{
    use ApiResponse;
}
