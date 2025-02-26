<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class DonationController extends Controller
{
    private $client;
    private $baseUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->baseUrl = env('PAYPAL_MODE', 'sandbox') === 'sandbox'
            ? 'https://api.sandbox.paypal.com'
            : 'https://api.paypal.com';
    }

    /**
     * Show the donation form.
     */
    public function showDonationForm()
    {
        return view('donate');
    }

    /**
     * Handle the donation payment.
     */
    public function processDonation(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $amount = $request->input('amount');

        // Get an access token
        $accessToken = $this->getAccessToken();

        // Create an order
        $response = $this->client->post("{$this->baseUrl}/v2/checkout/orders", [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$accessToken}",
            ],
            'json' => [
                "intent" => "CAPTURE",
                "purchase_units" => [[
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => number_format($amount, 2, '.', '')
                    ],
                    "description" => "Donation"
                ]],
                "application_context" => [
                    "cancel_url" => route('donation.cancel'),
                    "return_url" => route('donation.success')
                ]
            ]
        ]);

        $order = json_decode($response->getBody(), true);

        // Redirect the user to the approval URL
        foreach ($order['links'] as $link) {
            if ($link['rel'] === 'approve') {
                return redirect($link['href']);
            }
        }

        return redirect()->back()->with('error', 'Payment could not be processed. Please try again.');
    }

    /**
     * Handle successful payment.
     */
    public function donationSuccess(Request $request)
    {
        $orderId = $request->query('token');

        if (!$orderId) {
            return redirect()->route('donation.cancel')->with('error', 'Order ID is missing.');
        }

        // Capture the order
        $accessToken = $this->getAccessToken();

        try {
            $response = $this->client->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture", [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$accessToken}",
                ]
            ]);

            $result = json_decode($response->getBody(), true);

            if ($result['status'] === 'COMPLETED') {
                return redirect()->route('donation.form')->with('success', 'Thank you for your donation!');
            } else {
                return redirect()->route('donation.cancel')->with('error', 'Payment execution failed.');
            }
        } catch (\Exception $ex) {
            Log::error('PayPal Error: ' . $ex->getMessage());
            return redirect()->route('donation.cancel')->with('error', 'Payment execution failed.');
        }
    }

    /**
     * Handle cancelled payment.
     */
    public function donationCancel()
    {
        return redirect()->route('donation.form')->with('error', 'Donation was cancelled.');
    }

    /**
     * Get an access token from PayPal.
     */
    private function getAccessToken()
    {
        $response = $this->client->post("{$this->baseUrl}/v1/oauth2/token", [
            'auth' => [env('PAYPAL_CLIENT_ID'), env('PAYPAL_SECRET')],
            'form_params' => [
                'grant_type' => 'client_credentials'
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['access_token'];
    }
}