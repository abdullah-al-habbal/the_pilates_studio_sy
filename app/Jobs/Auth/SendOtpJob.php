<?php
// filePath: app/Jobs/Auth/SendOtpJob.php
declare(strict_types=1);

namespace App\Jobs\Auth;

use App\Mail\Auth\OtpMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        private readonly User $user,
        private readonly string $otp,
    ) {}

    public function handle(): void
    {
        Mail::to($this->user->email)->send(new OtpMail($this->user, $this->otp));
    }
}
