<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessScan;
use App\Models\Payment;
use App\Models\Scan;
use Illuminate\Http\Request;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;
use Stripe\Webhook;

class CheckoutController extends Controller
{
    private const TIER_PRICES = [
        'pro'  => ['amount' => 999,  'label' => 'Pro Scan'],
        'deep' => ['amount' => 2999, 'label' => 'Deep Scan'],
    ];

    public function create(Request $request)
    {
        $request->validate([
            'url'  => ['required', 'string', 'max:255'],
            'tier' => ['required', 'in:pro,deep'],
        ]);

        $tier = $request->input('tier');
        $url  = $this->normalizeUrl($request->input('url'));
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host || filter_var($host, FILTER_VALIDATE_IP)) {
            return back()->withErrors(['url' => 'Please enter a valid domain name.'])->withInput();
        }

        $host = strtolower($host);

        if (! preg_match('/^(?:[a-z0-9](?:[a-z0-9\-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $host)) {
            return back()->withErrors(['url' => 'Please enter a valid domain name (e.g. example.com).'])->withInput();
        }

        $resolved = @gethostbyname($host);
        if ($resolved === $host) {
            return back()->withErrors(['url' => "The domain \"{$host}\" does not appear to exist. Please check for typos."])->withInput();
        }

        $price = self::TIER_PRICES[$tier];

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = StripeSession::create([
            'payment_method_types' => ['card', 'ideal'],
            'mode'                 => 'payment',
            'customer_email'       => $request->user()->email,
            'line_items'           => [[
                'price_data' => [
                    'currency'     => 'eur',
                    'unit_amount'  => $price['amount'],
                    'product_data' => [
                        'name'        => "{$price['label']} — {$host}",
                        'description' => "Complete security scan of {$host}",
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'user_id' => $request->user()->id,
                'url'     => $url,
                'host'    => $host,
                'tier'    => $tier,
            ],
            'success_url' => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('home'),
        ]);

        // Store pending payment
        Payment::create([
            'user_id'            => $request->user()->id,
            'stripe_session_id'  => $session->id,
            'amount_cents'       => $price['amount'],
            'currency'           => 'eur',
            'status'             => 'pending',
            'tier'               => $tier,
            'domain'             => $host,
        ]);

        return redirect($session->url);
    }

    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()->route('home');
        }

        $payment = Payment::where('stripe_session_id', $sessionId)->first();

        if (! $payment) {
            return redirect()->route('home')->with('error', 'Payment not found.');
        }

        // If already processed (e.g. webhook was faster), redirect to scan
        if ($payment->scan_id) {
            return redirect()->route('scan.show', Scan::find($payment->scan_id));
        }

        // Verify payment with Stripe
        Stripe::setApiKey(config('services.stripe.secret'));
        $session = StripeSession::retrieve($sessionId);

        if ($session->payment_status !== 'paid') {
            return redirect()->route('home')->with('error', 'Payment has not been completed.');
        }

        // Create scan and start processing
        $scan = $this->createScanFromPayment($payment, $session);

        return redirect()->route('scan.show', $scan);
    }

    public function webhook(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Exception $e) {
            return response('Invalid signature', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $payment = Payment::where('stripe_session_id', $session->id)->first();

            if ($payment && ! $payment->scan_id) {
                $this->createScanFromPayment($payment, $session);
            }
        }

        return response('OK', 200);
    }

    private function createScanFromPayment(Payment $payment, $session): Scan
    {
        $metadata = $session->metadata ?? (object) [];

        $scan = Scan::create([
            'url'        => $metadata->url ?? "https://{$payment->domain}",
            'host'       => $payment->domain,
            'status'     => 'pending',
            'tier'       => $payment->tier,
            'user_id'    => $payment->user_id,
            'ip_address' => request()->ip(),
        ]);

        $payment->update([
            'scan_id'                  => $scan->id,
            'stripe_payment_intent_id' => $session->payment_intent ?? null,
            'status'                   => 'completed',
            'paid_at'                  => now(),
        ]);

        ProcessScan::dispatch($scan);

        return $scan;
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = 'https://' . $url;
        }
        return rtrim($url, '/');
    }
}
