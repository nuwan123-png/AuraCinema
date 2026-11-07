<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AdminBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = (string) config('auracinema.admin_user');
        $hash = (string) config('auracinema.admin_password_hash');

        if ($user === '' || $hash === '') {
            abort(503, 'Admin is not configured.');
        }

        if (config('auracinema.admin_require_https') && app()->isProduction() && ! $request->isSecure()) {
            abort(403, 'Admin requires HTTPS.');
        }

        $userOk = hash_equals($user, (string) $request->getUser());
        // Always run the hash check so timing does not reveal whether the username was right.
        $passOk = Hash::check((string) $request->getPassword(), $hash);

        if (! ($userOk && $passOk)) {
            return response('Authentication required.', 401, [
                'WWW-Authenticate' => 'Basic realm="AuraCinema Admin", charset="UTF-8"',
            ]);
        }

        // UI hint only (shows Edit / Add buttons in this browser). The routes stay protected by the check above.
        Cookie::queue('aura_admin', '1', 60 * 24 * 30);

        return $next($request);
    }
}