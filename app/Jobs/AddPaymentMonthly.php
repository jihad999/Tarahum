<?php

namespace App\Jobs;

use App\Models\Constant;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\Plan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AddPaymentMonthly implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payment_id;

    public function __construct($payment_id)
    {
        $this->payment_id = $payment_id;
    }

    public function handle()
    {
        $payment = Payment::whereId($this->payment_id)->first();
        $plan = Plan::whereId($payment->plan_id)->first();
        $payment_info = PaymentDetail::updateOrCreate([
            'payment_id' => $payment->id,
        ],[
            'payment_id' => $payment->id,
            'price' => $plan->price,
        ]);

        AddPaymentMonthly::dispatch($payment->id)->delay(now()->addMonths(30));
    }
}
