<?php 

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

class CustomThrottleRequests extends ThrottleRequests
{
    /**
     * Create a 'too many attempts' response.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function buildResponse($key, $maxAttempts)
    {
        $retryAfter = $this->limiter->availableIn($key);

        $response = response()->json([
            'message' => 'You have exceeded the number of allowed requests. Please try again after some time.',
            'retry_after' => $retryAfter
        ], 429);

        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts),
            $retryAfter
        );
    }

    /**
     * Handle a throttled request exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Exceptions\ThrottleRequestsException  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleRequestException($request, ThrottleRequestsException $exception)
    {
        $response = response()->json([
            'message' => 'Custom throttle message. Please wait a while before trying again.',
        ], $exception->getStatusCode());

        return $this->addHeaders(
            $response, $exception->getMaxAttempts(),
            $exception->getHeaders()['Retry-After']
        );
    }
}