<?php

namespace Osiset\ShopifyApp\Traits;

use Illuminate\Contracts\View\View as ViewView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Osiset\ShopifyApp\Actions\AuthenticateShop;
use Osiset\ShopifyApp\Exceptions\MissingAuthUrlException;
use Osiset\ShopifyApp\Exceptions\SignatureVerificationException;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use function Osiset\ShopifyApp\getShopifyConfig;
use function Osiset\ShopifyApp\parseQueryString;

/**
 * Responsible for authenticating the shop.
 */
trait AuthController
{
    /**
     * Installing/authenticating a shop.
     *
     * @return ViewView|RedirectResponse
     */
    public function authenticate(Request $request, AuthenticateShop $authShop)
    {
        // Get the shop domain
        if (getShopifyConfig('turbo_enabled') && $request->user()) {
            // If the user clicked on any link before load Turbo and receiving the token
            $shopDomain = $request->user()->getDomain();
            $request['shop'] = $shopDomain->toNative();
        } else {
            $shopDomain = ShopDomain::fromNative($request->get('shop'));
        }

        // Run the action
        [$result, $status] = $authShop($request);

        if ($status === null) {
            // Show exception, something is wrong
            throw new SignatureVerificationException('Invalid HMAC verification');
        } elseif ($status === false) {
            if (! $result['url']) {
                throw new MissingAuthUrlException('Missing auth url');
            }

            return View::make(
                'shopify-app::auth.fullpage_redirect',
                [
                    'authUrl'    => $result['url'],
                    'shopDomain' => $shopDomain->toNative(),
                ]
            );
        } else {
            // Go to home route
            return Redirect::route(
                getShopifyConfig('route_names.home'),
                ['shop' => $shopDomain->toNative()]
            );
        }
    }

    /**
     * Get session token for a shop.
     *
     * @return ViewView
     */
    public function token(Request $request)
    {
        $shopDomain = ShopDomain::getFromRequest($request);

        $target = $request->query('target');
        $query = parse_url($target, PHP_URL_QUERY);

        if ($query) {
            // remove "token" from the target's query string
            $params = parseQueryString($query);
            unset($params['token']);

            $cleanTarget = trim(explode('?', $target)[0] . '?' . http_build_query($params), '?');
        } else {
            $cleanTarget = $target;
        }

        return View::make(
            'shopify-app::auth.token',
            [
                'shopDomain' => $shopDomain,
                'target'     => $cleanTarget,
            ]
        );
    }
}
