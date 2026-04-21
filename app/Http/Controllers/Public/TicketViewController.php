<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Inertia\Inertia;
use Inertia\Response;

class TicketViewController extends Controller
{
    public function show(string $token): Response
    {
        $ticket = Ticket::where('public_token', $token)
            ->with(['replies' => function ($query) {
                $query->orderBy('created_at')->with('user:id,name');
            }])
            ->firstOrFail();

        return Inertia::render('Public/TicketView', [
            'ticket' => new TicketResource($ticket),
        ]);
    }
}
