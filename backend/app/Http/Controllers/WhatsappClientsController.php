<?php

namespace App\Http\Controllers;

use App\Models\WhatsappClient;
use App\Models\WhatsappClients;
use Illuminate\Http\Request;

class WhatsappClientsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show($company_id)
    {
        // WhatsappClient::truncate();

        $clients = WhatsappClients::where('company_id', $company_id)->first();
        return response()->json($clients);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {



        // $validated = $request->validate([
        //     'company_id' => 'required|exists:companies,id',
        //     'accounts' => 'required|array',
        // ]);

        // Check if a record exists for the given company_id
        $whatsappClient = WhatsappClients::where('company_id', $request->company_id)->first();

        if ($whatsappClient) {
            // Update existing record
            $whatsappClient->update(['accounts' => $request->accounts]);
        } else {
            // Create new record
            $whatsappClient = WhatsappClients::create(['company_id' => $request->company_id, 'accounts' => $request->accounts]);
        }

        return response()->json($whatsappClient, 200);
    }

    // public function delete(Request $request)
    // {
    //     if ($request->filled("client_id")) {
    //         $clientId = $request->client_id;
    //     }
    // }
}
