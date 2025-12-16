<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Http\Controllers\WhatsappController;
use App\Models\WhatsappClients;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Log::channel("jobs")->info("WhatsappJob started", ['job_data' => $this->data]);

        $companyId = $this->data['company_id'] ?? null;
        $number = $this->data['number'] ?? null;
        $message = $this->data['message'] ?? null;

        if (!$companyId || !$number || !$message) {
            Log::channel("jobs")->warning("WhatsappJob missing required fields", $this->data);
            return;
        }

        $clientId = null;

        $companyData = WhatsappClients::where('company_id', $companyId)->first();

        if ($companyData && $companyData->accounts) {
            $accounts = $companyData->accounts;

            if (!empty($accounts)) {
                $clientId = $accounts[0]['clientId'];
            } else {
                $clientId = null; // or handle as needed
            }
        } else {
            $clientId = null; // or handle as needed
        }

        if ($clientId) {

            $payload = [
                'clientId'  => $clientId, //"client_id_online_xtremeguard_{$companyId}",
                'recipient' => $number,
                'text'      => $message,
            ];

            try {
                Log::channel("jobs")->info("Sending WhatsApp message", ['payload' => $payload]);

                $response = Http::withoutVerifying()
                    ->timeout(30)
                    ->post('https://wa.mytime2cloud.com/send-message', $payload);

                if ($response->successful()) {
                    Log::channel("jobs")->info("WhatsApp message sent successfully", [
                        'company_id' => $companyId,
                        'response' => $response->body()
                    ]);
                } else {
                    Log::channel("jobs")->error("WhatsApp API failed", [
                        'status' => $response->status(),
                        'response' => $response->body(),
                        'payload' => $payload,
                    ]);
                }
            } catch (\Exception $e) {
                Log::channel("jobs")->error("Exception in WhatsappJob", [
                    'error' => $e->getMessage(),
                    'data' => $this->data,
                ]);
            }

            Log::channel("jobs")->info("WhatsappJob completed", ['company_id' => $companyId]);
        }
    }
}
