<?php

namespace Osiset\ShopifyApp\Http\Controllers;

use Illuminate\Routing\Controller;
use Osiset\ShopifyApp\Traits\ApiController as ApiControllerTrait;

/**
 * Authenticates with a JWT through auth.token Middleware
 */
class ApiController extends Controller
{
    use ApiControllerTrait;
}
