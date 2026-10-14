<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use App\Models\FamilyPack;
use App\Notifications\FamilyPackReminderNotification;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('family-packs:send-reminders', function () {
    $sent = 0;

    FamilyPack::with('user')
        ->where('status', 'active')
        ->whereNull('reminder_sent_at')
        ->whereNotNull('next_reminder_at')
        ->where('next_reminder_at', '<=', now())
        ->chunkById(100, function ($packs) use (&$sent) {
            foreach ($packs as $pack) {
                if (! $pack->user?->email) {
                    continue;
                }

                try {
                    $pack->user->notify(new FamilyPackReminderNotification($pack));
                    $pack->update(['reminder_sent_at' => now()]);
                    $sent++;
                } catch (\Throwable $exception) {
                    Log::error('Family pack reminder could not be sent.', [
                        'family_pack_id' => $pack->id,
                        'user_id' => $pack->user_id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        });

    $this->info("Sent {$sent} family pack reminder(s).");
})->purpose('Send family pack reorder reminders due before the next monthly purchase.');

Artisan::command('family-packs:send-test-email {email=dhruvparekh605@gmail.com}', function (string $email) {
    $this->info("Preparing realistic ICMR-NIN monthly ration test reminder for: {$email}");

    // Fetch latest existing pack or build realistic sample
    $pack = FamilyPack::with('user')->where('status', 'active')->latest()->first();

    if (! $pack) {
        $pack = new FamilyPack();
        $pack->id = 1;
        $pack->user_id = 1;
        $pack->created_at = now()->subDays(27);
        $pack->next_reminder_at = now();
        $pack->next_purchase_at = now()->addDays(3);
        $pack->monthly_total = 4115.00;
        $pack->recommendations = [
            [
                'product_id' => 1,
                'name' => 'Organic Sharbati Wheat Atta',
                'package_size' => '5 kg',
                'qty' => 4,
                'price' => 280,
                'label' => 'Cereals & Millets · Stone Ground',
            ],
            [
                'product_id' => 2,
                'name' => 'Traditional Brown / Basmati Rice',
                'package_size' => '5 kg',
                'qty' => 2,
                'price' => 350,
                'label' => 'Cereals · Unpolished',
            ],
            [
                'product_id' => 3,
                'name' => 'Desi Toor Dal (Unpolished)',
                'package_size' => '1 kg',
                'qty' => 3,
                'price' => 160,
                'label' => 'Pulses & Legumes · High Protein',
            ],
            [
                'product_id' => 4,
                'name' => 'Organic Green Moong Dal',
                'package_size' => '1 kg',
                'qty' => 2,
                'price' => 170,
                'label' => 'Pulses · Easy Digestibility',
            ],
            [
                'product_id' => 5,
                'name' => 'Wood-Pressed Groundnut Oil',
                'package_size' => '1 L',
                'qty' => 2,
                'price' => 280,
                'label' => 'Cold-Pressed Heart-Healthy Oil',
            ],
            [
                'product_id' => 6,
                'name' => 'Pure A2 Gir Cow Vedic Bilona Ghee',
                'package_size' => '500 ml',
                'qty' => 1,
                'price' => 750,
                'label' => 'A2 Cultured Ghee · Vitality',
            ],
            [
                'product_id' => 7,
                'name' => 'Organic Desi Jaggery / Shakkar',
                'package_size' => '1 kg',
                'qty' => 1,
                'price' => 110,
                'label' => 'Natural Sweetener · Mineral Rich',
            ],
            [
                'product_id' => 8,
                'name' => 'Natural Himalayan Pink Rock Salt',
                'package_size' => '1 kg',
                'qty' => 1,
                'price' => 55,
                'label' => 'Low Sodium · Trace Minerals',
            ],
        ];
    }

    $notifiable = new \App\Models\User([
        'name'  => 'Dhruv Parekh',
        'email' => $email,
    ]);

    try {
        $notifiable->notify(new FamilyPackReminderNotification($pack));

        $this->info("✓ Test email triggered successfully to {$email}!");
        $this->line("Subject: 🌾 Prakruti Monthly Ration Refill: Your Family Nutrition Basket is Ready to Reorder");
        $this->line("Estimated Monthly Total: Rs. " . number_format((float) $pack->monthly_total, 2));
    } catch (\Throwable $e) {
        $this->error("Failed to send test email: " . $e->getMessage());
    }
})->purpose('Send a test monthly family pack reminder email to the specified address.');

Schedule::command('family-packs:send-reminders')->dailyAt('09:00');
Schedule::command('prakruti:quarantine-expired-stock')->dailyAt('00:05');
Schedule::command('prakruti:check-reorder-levels')->hourly();
