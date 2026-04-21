<?php

namespace App\Services\SupportPortal;

use App\Models\SupportPortal\TicketTimeline;

class TimelineService
{
    public static function add(
        int $ticketId,
        string $eventType,
        string $title,
        ?string $description = null,
        ?int $performedBy = null,
        ?array $metaData = null
    ) {
        return TicketTimeline::create([
            'ticket_id'   => $ticketId,
            'event_type'  => $eventType,
            'title'       => $title,
            'description' => $description,
            'performed_by'=> $performedBy,
            'meta_data'   => $metaData,
        ]);
    }
}