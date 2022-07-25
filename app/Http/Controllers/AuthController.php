<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use \App\Models\User;
use \App\Models\Transactions;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use DB;
class AuthController extends Controller
{

    public function register(Request $request){
        $email = $request->input('email');
        $username = $request->input('username');
        $userid = $request->input('userid');
        //return $email;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response(["msg"=>"Invalid email address"]);
        }else{
            $rules = array(
                "email"=>"required",
                "name"=>"required",
                "lastname"=>"required",
                "password"=>"required|min:6",
                "username"=>"required|min:6",
                "userid"=>"required",
                "walletBalance"=>"required",
                "userCountry"=>"required",
                "userCurrency"=>"required",
                "telNumber"=>"required",
            );
            $validator = Validator::make($request->all(),$rules);

            if ($validator->fails()) {
                return $validator->errors();
            }else{       
            // //create new user
                $check_username = User::where('username', '=', $username)->count()>0;
                $check_email = User::where('email', '=',$email)->count()>0;
                $check_id = User::where('userid', '=',$userid)->count()>0;
                if ($check_username || $check_email || $check_id) {
                    return response(["msg"=>"user already exist"]);
                }else{
                    $user =  User::create
                    ([
                    'email'=>$request->input('email'),
                    'name'=>$request->input('name'),
                    'lastname'=>$request->input('lastname'),
                    'password'=>Hash::make($request->input('password')),
                    'username'=>$request->input('username'),
                    'userid'=>$request->input('userid'),
                    'walletBalance'=>$request->input('walletBalance'),
                    'userCountry'=>$request->input('userCountry'),
                    'userCurrency'=>$request->input('userCurrency'),
                    'telNumber'=>$request->input('telNumber'),
                    'isVerified'=>"0",
                    'accountStatus'=>"1",
                    'isPrivate'=>"0"
                    ]);

                    return response(["msg"=>"success"]);

                }


                
            }
        }
    }

    public function login(Request $request)
    {
        $rules = array("username"=>"required", "password"=>"required");

        $validator = Validator::make($request->all(),$rules);

        if ($validator->fails()) {
            return $validator->errors();
        }else {
            $username = $request->input('username');
            $password = $request->input('password');
            if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                //means loggin in with username
                $account_status = User::where('email', $username)->pluck('accountStatus')->first();
                //return $account_status;
                if ($account_status == 2) {
                    return response(["msg"=>"Account locked"]);
                }else
                {
                    if(!Auth::attempt($request->only('username', 'password')))
                    {
                        //means login invalid
                        return response(["message"=>"Incorrect credentials"]);
                    }
                    else
                    {
                        $user = Auth::user();
                        $token = $user->createToken('token')->plainTextToken;
                        //save token inside cookie of user browser
                        $cookie = cookie('jwt',$token, 60 *12);//half day for each new cookie generated
                        //return response(["msg"=>"success"])->withCookie($cookie);
                        return response(["msg"=>"success"]);

                    }
                }

            }else{
                //means loggin in with username
                $account_status = User::where('username', $username)->pluck('accountStatus')->first();
                //return $account_status;
                if ($account_status == 2) {
                    //restricted
                    return response(["msg"=>"Account locked"]);
                }else
                {
                    if(!Auth::attempt($request->only('username', 'password')))
                    {
                        //means login invalid
                        return response(["message"=>"Incorrect credentials"]);
                    }
                    else
                    {
                        $user = Auth::user();
                        $token = $user->createToken('token')->plainTextToken;
                        //save token inside cookie of user browser
                        $cookie = cookie('jwt',$token, 60 *12);//half day for each new cookie generated
                        return response(["msg"=>"success"])->withCookie($cookie);

                    }
                }
            }
        }

    }

    public function user()
    {
            return Auth::user();
    }

    public function logout(Request $request){
        $cookie = Cookie::forget('jwt');

        return response(["msg"=>"success"])->withCookie($cookie);
    }

    public function change_password(Request $request)
    {
        $rules = array("old_password"=>"required", "password"=>"required|min:6");

        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()) {
            return $validator->errors();
        }else{
            $user = $request->user();
            if (Hash::check($request->old_password, $user->password)) {
                $user->update(["password"=>Hash::make($request->password)]);
                return response(["msg"=>"success"]);

            }else{
                return response(["msg"=>"Incorrect password"]);
            }

        }

    }


    public function update_address(Request $request)
    {
        $rules = array( "user_address"=>"required|min:10");

        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()) {
            return $validator->errors();
        }else{
            $user = $request->user();
            if($user->update(["userAddress"=>$request->user_address])){
                return response(["msg"=>"success"]);
            }else{
                return response(["msg"=>"failed, try later"]);
            }
        }

    }


    public function international_transfer(Request $request)
    {
        $sender = $request->input("from");
        $reciever = $request->input("send_to");
        $initial_amount = floatval($request->input("initial_amount"));
        $charges = floatval($request->input("charging_amount"));
        $charged_amount = $request->input("charged_amount");
        $new_amount = floatval($request->input("new_amount"));
        $rules = array(
            "from" => "required",
            "send_to" => "required",
            "initial_amount" => "required",
            "charged_amount" => "required",
            "charging_amount" => "required",
            "new_amount" => "required",
            "description" => "required",
            "transfer_type" => "required"
        );
        $validator = Validator::make($request->all(), $rules);
        if ( $validator->fails() ) {
            return $validator->errors();
        }else {
            $account_status = User::where('username', $sender)->pluck('accountStatus')->first();
            if ( $account_status == 2 ) {
                return response(["msg"=>"Access blocked"]);
            }
            elseif ( $account_status == 0)
            {
                return response(["msg"=>"Restricted"]);
            }elseif ( $initial_amount < 1 )
            {
                return response(["msg"=>"Invalid amount"]);
            }
            else
            {
                $sender_balance = User::where('username', $sender)->pluck('walletBalance')->first();
                $sender_balance_to_float = floatval($sender_balance);

                $reciever_balance = User::where('username', $reciever)->pluck('walletBalance')->first();
                $reciever_balance_to_float = floatval($reciever_balance);

                if (!$reciever_balance)
                {
                    return response(["msg"=>"user not found"]);
                }else
                {
                    if ($sender_balance_to_float >= $charged_amount) 
                    {
                        $new_sender_balance = round($sender_balance_to_float - $charged_amount, 2);
                        $new_receiver_balance = round($reciever_balance_to_float + $new_amount, 2);
                        
                        $user = $request->user();
                        $debit_sender = DB::update('update users set walletBalance =? where username = ?', [$new_sender_balance, $sender]);
                        $credit_receiver = DB::update('update users set walletBalance =? where username = ?', [$new_receiver_balance, $reciever]);

                        $send_fund = Transactions::create([
                            "from"=>$sender,
                            "send_to"=>$reciever,
                            "sending_amount"=>$charged_amount,
                            "recieving_amount"=> round($new_amount, 2),
                            "charges"=>$charges,
                            "reciever_balance"=>$new_receiver_balance,
                            "sender_balance"=>$new_sender_balance,
                            "description"=>$request->input('description'),
                            "transfer_type" => $request->input("transfer_type"),
                            "status"=>"completed",
                        ]);
                        
                        if ($debit_sender && $credit_receiver && $send_fund) {
                            return response(["msg"=>"success"]);
                        }else{
                            return response(["msg"=>"failed"]);
                        }
                    }else
                    {
                        return response(["msg"=>"insufficient funds"]);
                    }
                }
            }
        }
    }


    //Local transfer
    public function local_transfer(Request $request)
    {
        $sender = $request->input("from");
        $reciever = $request->input("send_to");
        $amount = $request->input("amount");
        $amount_to_float = floatval($amount);
        $charges = floatval($request->input("charged_amount"));
        $charged_amount = ($charges + $amount_to_float);
        $rules = array(
            "from" => "required",
            "send_to" => "required",
            "amount" => "required",
            "charged_amount" => "required",
            "description" => "required",
            "transfer_type" => "required"
        );
        $validator = Validator::make($request->all(), $rules);
        if ( $validator->fails() ) {
            return $validator->errors();
        }else {
            $account_status = User::where('username', $sender)->pluck('accountStatus')->first();
            if ( $account_status == 2 ) {
                return response(["msg"=>"Access blocked"]);
            }
            elseif ( $account_status == 0)
            {
                return response(["msg"=>"Restricted"]);
            }
            elseif($amount < 1 )
            {
                return response(["msg"=>"Invalid amount"]);
            }else
            { 
                $sender_balance = User::where('username', $sender)->pluck('walletBalance')->first();
                $sender_balance_to_float = floatval($sender_balance);

                $reciever_balance = User::where('username', $reciever)->pluck('walletBalance')->first();
                $reciever_balance_to_float = floatval($reciever_balance);

                if (!$reciever_balance)
                {
                    return response(["msg"=>"user not found"]);
                }else
                {
                    if ($sender_balance_to_float >= $charged_amount) 
                    {
                        $new_sender_balance = round($sender_balance_to_float - $charged_amount, 2);
                        $new_receiver_balance = round($reciever_balance_to_float + $amount_to_float, 2);
                        
                        $user = $request->user();
                        $debit_sender = DB::update('update users set walletBalance =? where username = ?', [$new_sender_balance, $sender]);
                        $credit_receiver = DB::update('update users set walletBalance =? where username = ?', [$new_receiver_balance, $reciever]);

                            $send_fund = Transactions::create([
                                "from"=>$sender,
                                "send_to"=>$reciever,
                                "sending_amount"=>$charged_amount,
                                "recieving_amount"=>$amount_to_float,
                                "charges"=>$charges,
                                "reciever_balance"=>$new_receiver_balance,
                                "sender_balance"=>$new_sender_balance,
                                "description"=>$request->input('description'),
                                "transfer_type" => $request->input("transfer_type"),
                                "status"=>"completed",
                            ]);
                        
                        if ($debit_sender && $credit_receiver && $send_fund) {
                            return response(["msg"=>"success"]);
                        }else{
                            return response(["msg"=>"failed"]);
                        }
                    }else
                    {
                        return response(["msg"=>"insufficient funds"]);
                    }
                }
            }
        }
    }


    public function search_user(Request $request)
    {   
        $transfer_type;
        $rules = array(
            "sender"=>"required",
            "reciever"=>"required"
        );
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $validator->errors();
        }

        $sender = $request->input('sender');
        $reciever = $request->input('reciever');

        //getting all values from sender table

        $get_sender_currency = User::where("username",$sender)->pluck("userCurrency")->first();        

        $receiver_data = User::where("username",$reciever)->get(["userCurrency","name","lastname","isPrivate"]);
        $receiver_currency = $receiver_data[0]->userCurrency;

        if ( $get_sender_currency == $receiver_currency ) {
            $transfer_type = "local";
        }else {
            $transfer_type = "international";
        }

        if (($receiver_data[0]->isPrivate) == 0) {
            //public user
            return response([
                "msg"=>"success",
                "fullname"=>$receiver_data[0]->name ." ". $receiver_data[0]->lastname,
                "userCurrency"=>$receiver_data[0]->userCurrency,
                "transfer_type"=>$transfer_type,
                "isPrivate" => $receiver_data[0]->isPrivate
            ]);
        }else{
            //private user
            return response([
                "msg"=>"success",
                "username"=>$receiver_data[0]->username,
                "userCurrency"=>$receiver_data[0]->userCurrency,
                "transfer_type"=>$transfer_type,
                "isPrivate" => $receiver_data[0]->isPrivate

            ]);
        }
    } 

    // For local tramsfers only
    public function transfer_charges(Request $request)
    {

    //This function executes on keyUp amount input by the user initiating the transfer  
        $rules = array(
            "amount"=>"required",
            "currency"=>"required"
        );
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()) {
            return $validator->errors();
        }else {
            $amount = round(floatval($request->input('amount')), 2);
            $currency = $request->input("currency");
            $charges;

            // Local tranfer
            if ( $currency == "NGN" ) {
                if ( $amount < 1000 ) {
                    return response(["msg"=>"Minmum is 1000"]);
                }
                elseif ( ($amount>1000) && ($amount<=10000) ) {
                    $charges = 10;
                }
                elseif ( ($amount>10000) && ($amount<=50000) ) {
                    $charges = 50;
                }
                elseif ( ($amount>50000) && ($amount<=75000) ) {
                    $charges = 80;
                }
                elseif ( ($amount>75000) && ($amount<=150000) ) {
                    $charges = 120;
                }
                elseif ( ($amount>150000) && ($amount<=500000) ) {
                    $charges = 200;
                }
                elseif ( $amount>150000) {
                    return response(["msg"=>"Limit is #150000"]);
                }
                $charged_amount = round(floatval($charges), 2);
                return response([
                    "msg"=>"You will be charged $charged_amount",
                    "charged_amount"=>$charged_amount,
                    "transfer_type"=>"local"
                ]);
            }elseif ( $currency == "USD" ) {
                if ( $amount < 10 ) {
                    return response(["msg"=>"Minmum is $10"]);
                }
                elseif ( ($amount>10) && ($amount<=100) ) {
                    $charges = 0.5;
                }
                elseif ( ($amount>100) && ($amount<=500) ) {
                    $charges = 0.8;
                }
                elseif ( ($amount>500) && ($amount<=750) ) {
                    $charges = 1;
                }
                elseif ( ($amount>750) && ($amount<=1000) ) {
                    $charges = 1.5;
                }
                elseif ( ($amount>1000) && ($amount<=5000) ) {
                    $charges = 1.8;
                }elseif ( ($amount>5000) ) {
                    return response(["msg"=>"Limit is $5000"]);                        
                }

                $charged_amount = round(floatval($charges), 2);
                return response([
                    "msg"=>"You will be charged $charged_amount",
                    "charged_amount"=>$charged_amount,
                    "transfer_type"=>"local"
                ]);
            }elseif ( $currency == "EUR" ) {
                if ( $amount < 10 ) {
                    return response(["msg"=>"Minmum is £10"]);
                }
                elseif ( ($amount>10) && ($amount<=100) ) {
                    $charges = 0.5;
                }
                elseif ( ($amount>100) && ($amount<=500) ) {
                    $charges = 0.8;
                }
                elseif ( ($amount>500) && ($amount<=750) ) {
                    $charges = 1;
                }
                elseif ( ($amount>750) && ($amount<=1000) ) {
                    $charges = 1.5;
                }
                elseif ( ($amount>1000) && ($amount<=5000) ) {
                    $charges = 1.8;
                }elseif ( ($amount>5000) ) {
                    return response(["msg"=>"Limit is £5000"]);                        
                }

                $charged_amount = round(floatval($charges), 2);
                return response([
                    "msg"=>"You will be charged $charged_amount",
                    "charged_amount"=>$charged_amount,
                    "transfer_type"=>"local"
                ]);
            }
            
        }
    }


    public function exchange_rates(Request $request)
    {
        $sender_currency = $request->input("sender_currency");
        $reciever_currency = $request->input("reciever_currency");
        $amount = floatval($request->input("amount"));
        $default_rate = floatval($request->input("exchange_rate"));
        $rules = array(
            "amount" => "required",
            "sender_currency" => "required",
            "reciever_currency" => "required",
            "exchange_rate" => "required",
            "isPrivate" => "required"
        );
        $validator = Validator::make($request->all(),$rules);
        if ($validator->fails()) {
            return $validator->errors();
        }else {
            $charging_amount = (1 * $amount) / 100;
            $charging_rate = (9.5 * $default_rate) /100;
            $new_rate = $default_rate - $charging_rate;
            $new_amount = $amount - $charging_amount;

            $charged_amount = $amount + $charging_amount;

            $exchanged_amount = round($amount * $new_rate, 2);
            return $charged_amount;
            if ( $request->input("isPrivate") == 0 ) {
                // Public user
                return response([
                    "msg" => "success",
                    "initial_amount" => $amount,
                    "charging_amount" => $charging_amount,
                    "charged_amount" => $charged_amount,
                    "transfer_type" => "international",
                    "sender_currency" => $request->input("sender_currency"),
                    "reciever_currency" => $request->input("reciever_currency"),
                    "new_amount" => $new_amount
                ]);
            }else {
                // Private user
            }
        }
    }
}
