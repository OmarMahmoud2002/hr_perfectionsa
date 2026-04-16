<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        if ($e instanceof TokenMismatchException && ! $request->expectsJson()) {
            if ($request->isMethod('post') && $request->routeIs('login')) {
                return redirect()
                    ->route('login')
                    ->withInput($request->except(['password', 'password_confirmation']))
                    ->with('error', 'انتهت الجلسة، تم تحديث الصفحة تلقائيا. برجاء إدخال كلمة المرور مرة أخرى.');
            }

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', 'انتهت الجلسة، برجاء إعادة المحاولة.');
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = (int) $e->getStatusCode();

            if ($status === 403) {
                return response()->view('errors.403', [], 403);
            }

            if ($status === 404) {
                return response()->view('errors.404', [], 404);
            }
        }

        return parent::render($request, $e);
    }
}
