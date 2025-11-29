<?php

namespace Mkhodroo\UserRoles\Controllers;

use App\Models\MethodsModel;
// use Auth;
use App\CustomClasses\Access;
use App\Http\Controllers\Controller;
use App\Http\Controllers\RandomStringController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mkhodroo\UserRoles\Controllers\GetRoleController;
use Mkhodroo\UserRoles\Models\Method;
use Mkhodroo\UserRoles\Models\User;

class UserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */


    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    // protected $redirectTo = '/admin';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth']);

    }

    public function index(Request $request, $id)
    {
        Access::check('user_show_all');
        if($id == 'all'):
            $search = $request->input('search');

            $users = User::with('role')
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('display_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhereHas('role', function ($roleQuery) use ($search) {
                                $roleQuery->where('name', 'like', "%{$search}%");
                            });
                    });
                })
                ->paginate(15)
                ->withQueryString();

            return view('URPackageView::user.all')->with([
                'users' => $users,
                'search' => $search
            ]);
        else:

            return view('URPackageView::user.edit')->with([
                'user' => User::find($id),
                'roles' => GetRoleController::getAll()
            ]);
        endif;
    }

    public function AccessReg(Request $request, $id)
    {
        Access::check('user_show_all');
        $methods = MethodsModel::get()->toArray();

        foreach($methods as $method)
        {
            $name = $method['name'];
            if(isset($request->$name)):
                Access::set($id,$method['name']);

            else:
                Access::unset($id,$method['name']);
            endif;
        }
        return redirect()->back();
    }

    public function ChangePass(Request $request, $id)
    {
        User::where('id', $id)->update([ 'password' => Hash::make($request->pass) ]);
        return redirect()->back();
    }

    function changePMUsername(Request $r, $id) {
        User::where('id', $id)->update(['pm_username' => $r->pm_username]);
        return redirect()->back();
    }

    public function ChangeIp(Request $r, $user_id)
    {
        User::where('id',$user_id)->update([ 'valid_ip' => $r->valid_ip ]);
        return redirect()->back();
    }

    public function changeShowInReport(Request $r, $id){
        if(isset($r->showInReport))
            $showInReport = true;
        else
            $showInReport = false;
        User:: where('id', $id)->update([ 'showInReport' => $showInReport ]);
        return redirect()->back();
    }

    public static function create_api_token(User $user)
    {
        $token = RandomStringController::Generate(32);
        $user->api_token = $token;
        $user->save();
        return $user;
    }

}
