<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use App\Mail\VerifyEmail;
use Symfony\Component\Mailer\Exception\TransportException;
use App\Http\Controllers\API\BaseController as BaseController;

use DB;

class RegisterController extends  BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $submittedEmail = strtolower(trim((string) $request->input('email')));
        $request->merge([
            'email' => self::normalizeEmail($submittedEmail),
            'phone' => preg_replace('/\s+/', '', (string) $request->input('phone')),
        ]);

        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:100',
            'lastname' => 'required|string|max:100',
            'phone' => ['required', 'regex:/^0[789][01]\d{8}$/', 'unique:users'],
            'email' => [
                'required',
                'email:rfc',
                'unique:users',
            ],
            'password' => 'required|string|min:8',
            'c_password' => 'required|same:password',
            'referral_code' => 'nullable|string|max:500',
            'ref' => 'nullable|string|max:500',
            'referral_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            $message = $validator->errors()->first();

            return $this->sendError($message, $message);
        }

        if (self::gmailAliasAlreadyExists($request->email)) {
            return $this->sendError(
                'The email has already been taken.',
                'The email has already been taken.'
            );
        }

        $emailDomain = strtolower((string) substr(strrchr($request->email, '@'), 1));
        if (in_array($emailDomain, config('referrals.blocked_email_domains', []), true)) {
            return $this->sendError(
                'Disposable email addresses are not allowed.',
                'Please register with a permanent email address you can verify.'
            );
        }

        $referralCode = $request->input(
            'referral_code',
            $request->input('ref', $request->input('referral_id', ''))
        );
        $referralCode = trim((string) $referralCode);

        // Accept a direct code, the ?ref= value used by invite links, or a
        // complete referral URL pasted into the optional registration field.
        if (filter_var($referralCode, FILTER_VALIDATE_URL)) {
            parse_str((string) parse_url($referralCode, PHP_URL_QUERY), $query);
            $referralCode = trim((string) (
                $query['ref'] ??
                $query['referral_code'] ??
                $query['referral_id'] ??
                ''
            ));
        }

        if ($referralCode === '' || $referralCode === '0') {
            $referralId = 0;
        } elseif (!ctype_digit($referralCode) || (int) $referralCode <= 0) {
            return $this->sendError(
                'The referral code is invalid.',
                'The referral code is invalid.'
            );
        } else {
            $referralId = (int) $referralCode;
        }

        if ($referralId > 0 && !User::where('id', $referralId)->exists()) {
            return $this->sendError(
                'The referral code could not be found.',
                'The referral code could not be found.'
            );
        }

        $newUser = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => password_hash($request->password, PASSWORD_DEFAULT),
            'phone' => $request->phone,
            'hear_about_us' => $request->hear_about_us,
            'referral_id' => $referralId,
        ]);

        $user = User::find($newUser->id);
        // Do not issue an API token until ownership of the email is verified.
        $success['token'] = null;
        $success['firstname'] = $user->firstname;
        $success['lastname'] = $user->lastname;
        $success['email'] = $user->email;
        $success['phone'] = $user->phone;
        $success['wallet'] = $user->wallet;
        $success['role'] = $user->role;
        $success['id'] = $user->id;
        $success['userlevel'] = $user->userlevel;
        $success['status'] = $user->status;
        $success['hear_about_us'] = $user->hear_about_us;
        $success['commission'] = $user->commission;
        $success['reserved_acct'] = $user->reserved_acct;
        $success['bank_name'] = $user->bank_name;
        $success['account_number'] = $user->account_number;
        $success['referral_id'] = $user->referral_id;
        $success['created_at'] = $user->created_at;
        $success['updated_at'] = $user->updated_at;
        Mail::send(new VerifyEmail($user));

        return $this->sendResponse($success, 'User registered successfully.');
    }

    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {

        //check email
        $submittedEmail = strtolower(trim((string) $request->input('email')));
        $request->merge(['email' => self::normalizeEmail($submittedEmail)]);
        $user = User::where('email', $request->email)
            ->orWhere('email', $submittedEmail)
            ->first();
        if(is_null($user)){
           return $this->sendError('Invalid Email/Password', 'Invalid Email/Password');
        }

        if ($user->status == 1) {

            return $this->sendError('Banned.', ['error' => 'User is Banned']);
        }
        else {
            // $user = User::where([
            //     'email' => $request->email,
            //     'password' => $hash
            // ])->first();
            // Auth::attempt([
            //     'email' => $request->email,
            //     'password' => $request->password
            // ])
            $remember_me = $request->remember_me;
            //dd($remember_me);
            \Log::info($remember_me);

            if (Auth::attempt(['email' => $user->email, 'password' => $request->input('password')], $remember_me)){
                // Auth::login($user);
                //\Log::info("User Details");
                
                $rem = User::where("email", $user->email)->first();
                //\Log::info(print_r($rem,true));

                // Each login receives its own Passport token. Do not revoke the
                // user's other tokens: they may belong to another browser,
                // mobile device, API integration, or the WhatsApp bot.
                $tokenName = trim((string) $request->input('device_name', 'swiftlinkng'));
                $tokenName = $tokenName !== '' ? substr($tokenName, 0, 100) : 'swiftlinkng';
                $success['token'] = $user->createToken($tokenName)->accessToken;
                $success['firstname'] =  $user->firstname;
                $success['lastname'] =  $user->lastname;
                $success['email'] =  $user->email;
                $success['email_verified'] = ! is_null($user->email_verified_at);
                $success['email_verified_at'] = $user->email_verified_at
                    ? $user->email_verified_at->toIso8601String()
                    : null;
                $success['phone'] =  $user->phone;
                $success['remember_token'] =  $rem->remember_token;

                $success['wallet'] =  $user->wallet;
                
                $success['bvn'] =  $user->bvn;
                $success['nin'] =  $user->nin;
                $success['webhook_url'] =  $user->webhook_url;
                $success['role'] = $user->role;
                $success['id'] =  $user->id;
                $success['userlevel'] =  $user->userlevel;
                $success['status'] = $user->status;
                $success['hear_about_us'] =  $user->hear_about_us;
                $success['commission'] =  $user->commission;
                $success['reserved_acct'] =  $user->reserved_acct;
                $success['wema_reserved_acct'] = $user->wema_reserved_acct;
                $success['moniepoint_reserved_acct'] = $user->moniepoint_reserved_acct;
                $success['providus_reserved_acct'] = $user->providus_reserved_acct;
                $success['gtbank_reserved_acct'] = $user->gtbank_reserved_acct;
                $success['rehoboth_reserved_acct'] = $user->rehoboth_reserved_acct;
                $success['palmpay_reserved_acct'] = $user->palmpay_reserved_acct;
                $success['opay_reserved_acct'] = $user->opay_wallet_number ? json_encode([
                    'bankCode' => 'OPAY', 'bankName' => 'OPay',
                    'accountNumber' => $user->opay_wallet_number,
                    'accountName' => $user->opay_wallet_name,
                    'refId' => $user->opay_wallet_ref_id,
                    'accountType' => $user->opay_wallet_account_type,
                    'status' => $user->opay_wallet_status,
                ]) : null;
                $success['providus_account'] =  $user->providus_account;
                $success['userToken'] =  $user->userToken;
                $success['deviceToken'] =  $user->deviceToken;
                $success['bank_name'] =  $user->bank_name;
                $success['account_number'] =  $user->account_number;
                $success['referral_id'] =  $user->referral_id;
                $success['created_at'] =  $user->created_at;
                $success['updated_at'] =  $user->updated_at;

                
  
                return $this->sendResponse($success, 'User login successfully.');
            } else {
                return $this->sendError('Invalid Email/Password', 'Invalid Email/Password');
            }
        }
    }

    private static function normalizeEmail($email)
    {
        $email = strtolower(trim((string) $email));
        if (strpos($email, '@') === false) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        if (in_array($domain, ['gmail.com', 'googlemail.com'], true)) {
            $local = explode('+', $local, 2)[0];
            $local = str_replace('.', '', $local);
            $domain = 'gmail.com';
        }

        return $local.'@'.$domain;
    }

    private static function gmailAliasAlreadyExists($email)
    {
        [$local, $domain] = array_pad(explode('@', strtolower((string) $email), 2), 2, '');
        if ($domain !== 'gmail.com') {
            return false;
        }

        $canonicalLocal = str_replace('.', '', explode('+', $local, 2)[0]);

        return User::whereRaw("LOWER(SUBSTRING_INDEX(email, '@', -1)) IN (?, ?)", [
                'gmail.com',
                'googlemail.com',
            ])
            ->whereRaw(
                "REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(LOWER(email), '@', 1), '+', 1), '.', '') = ?",
                [$canonicalLocal]
            )
            ->exists();
    }

    public function resendVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc'],
        ]);

        if ($validator->fails()) {
            return $this->sendError('Please enter a valid email address.', 'Please enter a valid email address.');
        }

        $submittedEmail = strtolower(trim((string) $request->email));
        $normalizedEmail = self::normalizeEmail($submittedEmail);
        $user = User::where('email', $normalizedEmail)
            ->orWhere('email', $submittedEmail)
            ->first();

        if ($user && is_null($user->email_verified_at)) {
            Mail::send(new VerifyEmail($user));
        }

        // Do not reveal whether an email address is registered.
        return $this->sendResponse(
            [],
            'If this email is registered and unverified, a fresh verification link has been sent.'
        );
    }






    public function logout(Request $request)
    {
        // Logout must affect only the token used for this request. Revoking or
        // deleting every token would disconnect the user's other devices and
        // server integrations, including the WhatsApp bot.
        $currentToken = $request->user()->token();
        if ($currentToken) {
            $currentToken->revoke();
        }

        $success['message'] = 'Logged out';
        return $this->sendResponse($success, 'Logged out successfully.');
    }

    public function reset_password(Request $request)
    {
        $input = $request->all();
        $rules = array(
            'email' => "required|email",
        );
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return $this->sendError(array("status" => 400, "message" => $validator->errors()->first(), "data" => array()));
        } else {
            try {

                $user = User::where('email', $request->input('email'))->first();
                //$credentials = $request->validate(['email' => 'required|email']);
                $credentials = ['email' => $user->email];
                $response = Password::broker()->sendResetLink($credentials);
                switch ($response) {
                    case Password::RESET_LINK_SENT:
                        return  $this->sendResponse(array("status" => 200, "message" => trans($response), "data" => array()), 'Password reset link has been sent');
                    case Password::INVALID_USER:
                        return  $this->sendError(array("status" => 400, "message" => trans($response), "data" => array()), 'Invalid User.');
                }
            } catch (TransportException $ex) {
                return $this->sendError(array("status" => 400, "message" => $ex->getMessage(), "data" => []));
            } catch (Exception $ex) {
                return $this->sendError(array("status" => 400, "message" => $ex->getMessage(), "data" => []));
            }
        }
        // return \Response::json($arr);
        //array("status" => 200, "message" => trans($response), "data" => array());
        //return $this->sendResponse($arr, 'User updated successfully.');
    }
}
