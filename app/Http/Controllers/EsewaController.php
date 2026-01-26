<?php

namespace App\Http\Controllers;

use App\Models\UserSubscription;
use Illuminate\Http\Request;

require '../vendor/autoload.php';

use Cixware\Esewa\Client;
use Cixware\Esewa\Config;

class EsewaController extends Controller
{
    //
    public function esewaPayment(Request $request)
    {
        $inputData = $request->all();
        $user_id = $inputData['user_id'];
        $plan_id = $inputData['plan_id'];
        $payment_method = $inputData['payment_method'];
        $subscriptionMonth = $inputData['subscription_month'];

        $plan_price = $inputData['payment_amount'];
        $payment_id = uniqid();
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime($start_date . ' + ' . $subscriptionMonth . ' months'));
        $total_amount = $plan_price * $subscriptionMonth;

        UserSubscription::create([
            'user_id' => $user_id,
            'plan_id' => $plan_id,
            'payment_method' => $payment_method,
            'payment_amount' => $total_amount,
            'payment_id' => $payment_id,
            'payment_status' => 'pending',
            'start_date' => $start_date,
            'end_date' => $end_date,
        ]);

        // Set success and failure callback URLs.
        $successUrl = url('/user/subscription/esewa/payment-success');
        $failureUrl = url('/user/subscription/esewa/payment-failure');

        // Config for development.
        $config = new Config($successUrl, $failureUrl);

        // Initialize eSewa client.
        $esewa = new Client($config);
        $esewa->process($payment_id, $total_amount, 0, 0, 0);


    }
    public function esewaSuccess()
    {
        $payment_id = $_GET['oid'];
        $userSubscription = UserSubscription::where('payment_id', $payment_id)->first();
        $userSubscription->payment_status = 'success';
        $userSubscription->save();
        return view(
            'subscription.paymentSuccess',
            [
                'payment_details' => $userSubscription
            ]
        );

    }
    public function esewaFailure()
    {
        $payment_id = $_GET['pid'];
        $userSubscription = UserSubscription::where('payment_id', $payment_id)->first();

        return view(
            'subscription.paymentFailure',

        );


    }
}
