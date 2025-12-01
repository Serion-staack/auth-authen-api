<?php

namespace App\Console\Commands;

use App\Models\Refresh_token;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class DeleteExpiredToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired access and refresh tokens';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        PersonalAccessToken::whereNotNull('expires_at')->where('expires_at','<', $now)->delete();

        Refresh_token::where('expired_at','<',$now)->delete();

        $this->info('Expired tokens cleaned up successfully');
    }
}
