<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebhookTestController extends Controller
{
    public function receive(Request $request)
    {
        $signature = $request->header('X-Nova-Signature');
        $secret = config('services.webhooks.secret');

        $expectedSignature = 'sha256=' . hash_hmac(
            'sha256',
            $request->getContent(),
            $secret
        );

        if (!$secret || !$signature || !hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Webhook received and signature verified',
            'event' => $request->header('X-Nova-Event'),
            'delivery_id' => $request->header('X-Nova-Delivery'),
            'payload' => $request->json()->all(),
        ]);
    }
}