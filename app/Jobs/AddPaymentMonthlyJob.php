<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\Plan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddPaymentMonthlyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payment_id;

    public function __construct($payment_id)
    {
        $this->payment_id = $payment_id;
    }

    public function handle()
    {
        $payment = Payment::whereId($this->payment_id)->with('sponser')->first();
        // $plan = Plan::whereId($payment->plan_id)->first();
        $sponser = $payment->sponser??null;
        if($sponser && $payment->statis == 1){
            add_notification($sponser->id,9);
            // $payment_info = PaymentDetail::updateOrCreate([
            //     'payment_id' => $payment->id,
            // ],[
            //     'payment_id' => $payment->id,
            //     'price' => $plan->price,
            // ]);
    
            AddPaymentMonthlyJob::dispatch($payment->id)->delay(now()->addDays($payment->duration_day));
        }
    }
}
