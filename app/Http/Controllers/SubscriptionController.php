<?php

namespace App\Http\Controllers;

use App\Models\PlansAndPrice;
use Illuminate\Http\Request;
use App\Models\UserInfo;

class SubscriptionController extends Controller
{
    //
    public function mySubscription()
    {
        // Get the currently logged-in user
        $user = auth()->user();

        // Retrieve the user's associated UserInfo
        $userInfo = $user->userInfo;

        // Get the user's subscriptions
        $subscriptions = $userInfo->subscriptions;

        $successSubscriptions = $subscriptions->where('payment_status', 'success');

        return view(
            'subscription.mySubscription',
            [
                'subscriptions' => $successSubscriptions,
                'userInfo' => $userInfo
            ]
        );
    }
    public function subscribePlan(Request $request)
    {
        $email = auth()->user()->email;
        $loggedinUser = UserInfo::where('email', $email)->first();

        // Check if user information is incomplete
        if ($loggedinUser !== null && ($loggedinUser->address == null || $loggedinUser->city == null || $loggedinUser->country == null || $loggedinUser->phone == null)) {
            return view(
                'users.userProfile',
                [
                    'user' => $loggedinUser
                ]
            )->with('warning', 'Please complete your profile information before subscribing to a plan.');
        }

        $userInfo = UserInfo::where('id', auth()->user()->id)->first();



        $plan_id = $request->plan_id;
        $selected_plan = PlansAndPrice::find($plan_id);
        $plan_price = $selected_plan->plan_prices;
        $plan_title = $selected_plan->plan_title;
        $discount = $selected_plan->discount_and_offer;
        $total_price = $plan_price - ($plan_price * $discount / 100);

        $passedPlan = [
            'plan_id' => $plan_id,
            'plan_price' => $plan_price,
            'plan_title' => $plan_title,
            'discount' => $discount,
            'total_price' => $total_price,
            'user_id' => $userInfo->id,

        ];

        return view(
            'subscription.subscribePlan',
            [
                'passedPlan' => $passedPlan
            ]
        );
    }


}
