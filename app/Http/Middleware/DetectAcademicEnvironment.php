<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectAcademicEnvironment
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Check if the domain is calculator.calories365.xyz
        $isAcademic = str_contains($request->getHost(), 'calculator.calories365.xyz');
        // Bind to the application container
        app()->bind('academic', function() use ($isAcademic) {
            return $isAcademic;
        });

        // Store in config as well for convenience
        config(['app.academic' => $isAcademic]);

        return $next($request);
    }
}
