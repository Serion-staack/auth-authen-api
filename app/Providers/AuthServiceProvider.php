<?php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            $frontend = rtrim(config('app.frontend_url') ?: env('APP_FRONTEND_URL'), '/');
            $reactUrl = $frontend . '/verify-email?url=' . urlencode($url);

            return (new MailMessage)
                ->subject('Verify Your Email Address')
                ->line('Click the button below to verify your email address.')
                ->action('Verify Email', $reactUrl)
                ->line('If you did not create an account, no further action is required.');
        });
    }
}
