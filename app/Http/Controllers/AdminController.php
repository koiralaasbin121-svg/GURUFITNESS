<?php

namespace App\Http\Controllers;

use App\Models\UserInfo;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\News;
use App\Models\Event;
use App\Models\Notice;
use App\Models\PlansAndPrice;


class AdminController extends Controller
{
    protected $guard = 'admin';
    //
    public function AdminLogin()
    {
        return view('adminAuth.admin-login');
    }

    public function AdminDashboard()
    {
        //get customers, admins, and subscriptions count
//count number of entries in UserInfo
        $userAccounts = UserInfo::all()->count();
        $admins = Admin::all()->count();
        $subscriptions = UserSubscription::where('payment_status', 'success')->count();
        $totalAmount = UserSubscription::where('payment_status', 'success')->sum('payment_amount');
        $news = News::all()->count();
        $events = Event::all()->count();
        $notices = Notice::all()->count();
        $plans = PlansAndPrice::all()->count();


        return view(
            'adminAuth.home.dashboard',
            [
                'userAccounts' => $userAccounts,
                'admins' => $admins,
                'subscriptions' => $subscriptions,
                'totalAmount' => $totalAmount,
                'news' => $news,
                'events' => $events,
                'notices' => $notices,
                'plans' => $plans
            ]
        );
    }
    public function AdminProfile()
    {
        return view('adminAuth.home.my-profile');
    }
    public function AdminNewsEvents()
    {
        return view(
            'adminAuth.websiteContent.news-and-events',
            [
                'news' => News::all(),
                'events' => Event::all()
            ]
        );
    }
    public function createNews(Request $request)
    {

        $inputData = $request->validate([
            'news_title' => 'required',
            'news_body' => 'required',
            'news_image' => 'required|file',
        ], [
            'news_title.required' => 'Empty fields',
            'news_body.required' => 'Empty fields',
            'news_image.required' => 'Empty fields',

        ]);

        $path = $request->file('news_image')->store('public/images/NewsImages');
        $pathWithoutPublic = str_replace('public/', '', $path);
        $news = new News;
        $news->news_title = $inputData['news_title'];
        $news->news_body = $inputData['news_body'];
        $news->news_image = 'storage/' . $pathWithoutPublic;
        $news->save();

        return redirect()->back()->with('success', 'News added successfully.');
    }
    public function createEvent(Request $request)
    {
        $inputData = $request->validate([
            'events_title' => 'required',
            'events_body' => 'required',
        ], [
            'events_title.required' => 'Empty fields',
            'events_body.required' => 'Empty fields',
        ]);

        $event = new Event;
        $event->events_title = $inputData['events_title'];
        $event->events_body = $inputData['events_body'];
        $event->save();
        return redirect()->back()->with('success', 'Event added successfully.');
    }

    public function deleteNewsEvents(Request $request)
    {
        $ids = $request->input('ids');
        News::whereIn('id', $ids)->delete();
        Event::whereIn('id', $ids)->delete();
        return response()->json(['message' => 'Selected news and events deleted'], 200);

    }
    public function AdminNotices()
    {
        return view(
            'adminAuth.websiteContent.notice'
            ,
            [
                'notices' => Notice::all()
            ]

        );
    }
    public function createNotice(Request $request)
    {
        $inputData = $request->validate([
            'notice_title' => 'required'
        ], [
            'notice_title.required' => 'Empty field',
        ]);
        $notice = new Notice;
        $notice->notice_title = $inputData['notice_title'];
        $notice->save();
        return redirect()->back()->with('success', 'Notice added successfully.');
    }

    public function deleteNotice(Request $request)
    {
        $ids = $request->input('ids');
        Notice::whereIn('id', $ids)->delete();
        return response()->json(['message' => 'Selected news and events deleted'], 200);
    }

    public function AdminPlansPrices()
    {
        return view(
            'adminAuth.websiteContent.plans-and-prices',
            ['plans' => PlansAndPrice::all()]
        );
    }

    public function createPlan(Request $request)
    {

        $inputData = $request->validate([
            'plan_title' => 'required',
            'plan_prices' => 'required',
            'plan_features' => 'required',
        ], [
            'plan_title.required' => 'Empty fields',
            'plan_prices.required' => 'Empty fields',
            'plan_features.required' => 'Empty fields',
        ]);

        $plan = new PlansAndPrice;
        $plan->plan_title = $inputData['plan_title'];
        $plan->plan_prices = $inputData['plan_prices'];
        $plan->plan_features = $inputData['plan_features'];
        $plan->save();
        return redirect()->back()->with('success', 'Plan added successfully.');
    }
    public function deletePlan(Request $request)
    {
        $ids = $request->input('ids');
        PlansAndPrice::whereIn('id', $ids)->delete();
        return response()->json(['message' => 'Selected Plan deleted successfully'], 200);
    }
    public function updatePlan(Request $request)
    {
        $inputData = $request->validate([
            'plan_title' => 'required',
            'plan_prices' => 'required',
            'plan_features' => 'required',
        ], [
            'plan_title.required' => 'Empty fields',
            'plan_prices.required' => 'Empty fields',
            'plan_features.required' => 'Empty fields',
        ]);
        $plan = PlansAndPrice::find($request['plan_id']);
        $plan->update($inputData);
        return redirect()->back()->with('success', 'Plan updated successfully.');
    }

    public function AdminAboutUs()
    {
        return view('adminAuth.websiteContent.about-us');
    }
    public function AdminUserAccounts()
    {
        $user = UserInfo::all();
        return view(
            'adminAuth.customerManage.user-accounts',
            [
                'users' => $user
            ]
        );
    }
    public function AdminSubscriptions()
    {

        $subscriptions = UserSubscription::where('payment_status', 'success')->get();
        return view(
            'adminAuth.customerManage.subscriptions',
            [
                'subscriptions' => $subscriptions

            ]
        );
    }
    public function AdminUsers()
    {
        return view('adminAuth.adminManage.users', [
            'admins' => Admin::latest()->filter(request(['search']))->paginate(10),
        ]);
    }

    public function AdminLoginUser(Request $request)
    {
        $this->validate(
            $request,
            [
                'username' => 'required',
                'password' => 'required',
            ],
            [
                'username.required' => 'Empty fields',
                'password.required' => 'Empty fields',
            ]
        );

        $credentials = $request->only('username', 'password');

        if (Auth::guard('admin')->attempt($credentials)) {

            return redirect()->intended('/admin/home');
        }

        // Authentication failed, redirect back with an error message
        return back()->withErrors(['username' => 'Invalid credentials']);
    }

    public function createAdminUser(Request $request)
    {
        // Validation rules for the form fields

        $inputData = $request->validate(
            [
                'fullName' => 'required',
                'username' => ['required', Rule::unique('admins', 'username')],
                'password' => ['required', 'confirmed', 'min:6'],
                'created_by' => 'required',
                'role' => 'required'

            ],
            [
                'fullName.required' => 'Empty fields',
                'username.required' => 'Empty fields',
                'password.required' => 'Empty fields',
                'role.required' => 'Empty fields',
                'password.confirmed' => 'Password didn\'t match',
                'username.unique' => 'Username already in use',
            ],
        );

        // Create a new admin user
        $admin = new Admin;
        $admin->fullName = $inputData['fullName'];
        $admin->username = $inputData['username'];
        $admin->password = bcrypt($inputData['password']);
        $admin->created_by = $inputData['created_by'];
        $admin->role = $inputData['role'];
        $admin->save();

        // Redirect back with a success message
        return redirect()->back()->with('success', 'Admin user created successfully.');
    }



    public function AdminLogout(Request $request)
    {
        Auth::guard('admin')->logout();
        return redirect('/admin/login');
    }
    public function deleteAdminUser(Request $request)
    {
        // Get the ids from the request
        $ids = $request->input('ids');

        // Get the currently logged-in admin
        $loggedInAdmin = Auth::guard('admin')->user();

        // Check if the logged-in admin is a super admin
        if ($loggedInAdmin->role === 'Super-Admin') {
            // Super admin can delete any user except themselves
            $ids = array_diff($ids, [$loggedInAdmin->id]);
            Admin::whereIn('id', $ids)->delete();
            return response()->json(['message' => 'Selected user/s deleted except unprivileged'], 200);
        } elseif ($loggedInAdmin->role === 'Admin') {
            // Normal admin can delete other normal admins, not super-admins or themselves
            $ids = array_diff($ids, [$loggedInAdmin->id]);
            $superAdminIds = Admin::whereIn('id', $ids)->where('role', 'Super-Admin')->pluck('id')->toArray();
            Admin::whereNotIn('id', $superAdminIds)->whereIn('id', $ids)->delete();
            return response()->json(['message' => 'Selected user/s deleted except unprivileged'], 200);
        } else {
            // Other roles are not allowed to delete
            return response()->json(['message' => 'You do not have permission to delete users.'], 403);
        }
    }



}
