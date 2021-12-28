<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DonationController extends Controller
{
  public function __construct()
  {
      \Midtrans\Config::$serverKey = config('services.midtrans.serverKey');
      \Midtrans\Config::$isProduction = config('services.midtrans.isProduction');
      \Midtrans\Config::$isSanitized = config('services.midtrans.isSanitized');
      \Midtrans\Config::$is3ds = config('services.midtrans.is3ds');
  }

  public function index()
  {
    return view('donation');
  }

  public function store(Request $request)
  {
    // dd('oik');
    DB::transaction(function () use ($request) {
        $donation = Donation::create([
          'donor_name' => $request->donor_name,
          'donor_email' => $request->donor_email,
          'donation_type' => $request->donation_type,
          'amount' => floatval($request->amount),
          'note' => $request->note
        ]);

        $payload = [
          'transaction_details' => [
            'order_id' => "SANDBOX-" . Str::random(10),
            'gross_amount' => $request->amount
          ],
          'customer_details' => [
            'first_name' => $request->donor_name,
            'email' => $request->donor_email,
          ],
          'item_details' => [
            [
              'id' => $request->donation_type,
              'price' => $request->amount,
              'quantity' => 1,
              'name' => ucwords(str_replace('-', ' ', $request->donation_type))
            ]
          ]
        ];

        $snapToken = \Midtrans\Snap::getSnapToken($payload);
        $donation->snap_token = $snapToken;
        $donation->save();

        $this->response['snap_token'] = $snapToken;
    });

    return response()->json($this->response);
  }
}
