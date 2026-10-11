<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SmsRecipientList;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SmsRecipientListController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['data' => SmsRecipientList::where('user_id', $request->user()->id)
            ->latest()->get()]);
    }

    public function store(Request $request)
    {
        $request->merge(['name' => is_string($request->name) ? trim($request->name) : $request->name]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('sms_recipient_lists')->where('user_id', $request->user()->id)],
            'recipients' => ['required', 'array', 'min:1', 'max:1000'],
            'recipients.*' => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
        ]);
        // Treat Nigerian local/international spellings as the same recipient.
        $numbers = [];
        foreach ($data['recipients'] as $number) {
            $key = ltrim($number, '+');
            if (preg_match('/^0[0-9]{10}$/', $key)) $key = '234' . substr($key, 1);
            if (!isset($numbers[$key])) $numbers[$key] = $number;
        }
        $list = new SmsRecipientList(['name' => $data['name'], 'recipients' => array_values($numbers)]);
        $list->user_id = $request->user()->id;
        $list->save();
        return response()->json(['data' => $list], 201);
    }

    public function destroy(Request $request, $id)
    {
        SmsRecipientList::where('user_id', $request->user()->id)->findOrFail($id)->delete();
        return response()->noContent();
    }
}
