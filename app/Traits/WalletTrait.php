<?php

namespace App\Traits;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

use DB;

trait WalletTrait
{

    /**
     * @param Request $request
     * @return $this|false|string
     */

    public function isDebited($amount, $userid = null)
    {
    
        

        try {
            DB::beginTransaction();
                $userWallet = User::query()->lockForUpdate()->findOrFail($userid == null ? $this->user->id : $userid);

                // \Log::info(print_r($userWallet, true));

                if (!is_numeric($amount)) {
                    return false;
                } else if ($amount <= 0) {
                    return false;
                } else if ($userWallet->wallet < $amount) {
                    return false;
                } else {
                    $userWallet->wallet  = $userWallet->wallet - $amount;
                    $userWallet->save();
                    DB::commit();
                    return true;
                }
            
                

           } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
          }
    }
    // public function isDebited($amount, $userid = null)
    // {
    //     $userWallet = User::lockForUpdate()->find($userid == null ? $this->user->id : $userid);
    //     if (!is_numeric($amount)) {
    //         return false;
    //     } else if ($amount <= 0) {
    //         return false;
    //     } else if ($userWallet->wallet < $amount) {
    //         return false;
    //     } else {
    //         $userWallet->wallet  = $userWallet->wallet - $amount;
    //         $userWallet->save();
    //         return true;
    //     }
    // }
    
    // public function isDebited($amount, $userid = null)
    // {
    //     $myUserId = $userid==null ? $this->user_id : $userid;  
        

    //     do {
    //         $theUser = User::whereId($myUserId)->first(); 
            
    //         if (!is_numeric($amount)) {
    //             return false;
    //         } else if ($amount <= 0) {
    //             return false;
    //         } else if ($theUser->wallet < $amount) {
    //             return false;
    //         }
    //         $updated = User::whereId($myUserId)->update(['wallet'=> $theUser->wallet - $amount]);
    //         if($updated) {
    //             return true;
    //         }
            
    //     }while(!$updated); 
        
   
    // }


    // public function isCredited($amount, $userid = null)
    // {
    //     $userWallet = User::lockForUpdate()->find($userid == null ? $this->user->id : $userid);
    //     // $userWallet->wallet  = $userWallet->wallet + $amount;
    //     // return $userWallet->save();


    //     if (!is_numeric($amount)) {
    //         return false;
    //     } else if ($amount <= 0) {
    //         return false;
    //     } else {
    //         $userWallet->wallet  = $userWallet->wallet + $amount;
    //         $userWallet->save();
    //         return true;
    //     }
    // }
    public function isCredited($amount, $userid = null)
    {
        

        try {
            DB::beginTransaction();
        $userWallet = User::query()->lockForUpdate()->findOrFail($userid == null ? $this->user->id : $userid);
        // $userWallet->wallet  = $userWallet->wallet + $amount;
        // return $userWallet->save();


        if (!is_numeric($amount)) {
            return false;
        } else if ($amount <= 0) {
            return false;
        } else {
            $userWallet->wallet  = $userWallet->wallet + $amount;
            $userWallet->save();
            DB::commit();
            return true;
        }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function adwardBonus($id, $bonus, $transactionAmount)
    {
        $amount = round(((float) $bonus / 100) * (float) $transactionAmount, 2);

        if ($amount <= 0) {
            return 0;
        }

        /*
         * This is the legacy percentage-reward path used by provider jobs.
         * Keep commission as the single destination and lock the user row so
         * simultaneous successful transactions cannot overwrite each other.
         */
        return DB::transaction(function () use ($id, $amount) {
            $userWallet = User::query()->lockForUpdate()->findOrFail($id);
            $userWallet->commission = round(
                (float) $userWallet->commission + $amount,
                2
            );
            $userWallet->save();

            return $amount;
        }, 3);
    }
    
    public function DecrementBucket($wallet_name,$user_id, $data_cost)
    {
        try {
            DB::beginTransaction();
            $userWallet = User::query()->lockForUpdate()->findOrFail($user_id == null ? $this->user->id : $user_id);


            if (!is_numeric($data_cost)) {
                return false;
            } else if ($data_cost <= 0) {
                return false;
            } else if ($userWallet->$wallet_name < $data_cost) {
                return false;
            } else {
                $userWallet->$wallet_name -= $data_cost;
                $userWallet->save();
                DB::commit();
                return true;


            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function IncrementBucket($wallet_name,$user_id, $data_cost)
    {
        try {
            DB::beginTransaction();
            $userWallet = User::query()->lockForUpdate()->findOrFail($user_id == null ? $this->user->id : $user_id);


            if (!is_numeric($data_cost)) {
                return false;
            } else if ($data_cost <= 0) {
                return false;
            } else {
                $userWallet->$wallet_name += $data_cost;
                $userWallet->save();
                DB::commit();
                return true;


            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
