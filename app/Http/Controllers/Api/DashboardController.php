<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Deal;
use App\Models\Entity;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $openStages = ['lead', 'contact', 'proposal', 'negotiation'];

        $entityCount = Entity::count();
        $peopleCount = Person::count();

        $openDealsCount     = Deal::whereIn('stage', $openStages)->count();
        $pipelineValue      = (float) Deal::whereIn('stage', $openStages)->sum('value');
        $wonDeals30d        = Deal::where('stage', 'won')
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();
        $wonValue30d        = (float) Deal::where('stage', 'won')
            ->where('updated_at', '>=', now()->subDays(30))
            ->sum('value');

        $dealsByStage = Deal::selectRaw('stage, COUNT(*) as count, COALESCE(SUM(value), 0) as total_value')
            ->groupBy('stage')
            ->get()
            ->map(fn ($d) => [
                'stage'       => $d->stage,
                'count'       => (int) $d->count,
                'total_value' => (float) $d->total_value,
            ]);

        $recentDeals = Deal::with('entity')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($d) => [
                'id'         => $d->id,
                'title'      => $d->title,
                'stage'      => $d->stage,
                'value'      => (float) $d->value,
                'entity'     => $d->entity
                    ? ['id' => $d->entity->id, 'name' => $d->entity->name]
                    : null,
                'created_at' => $d->created_at->toIso8601String(),
            ]);

        $upcomingEvents = CalendarEvent::where('start_at', '>=', now())
            ->orderBy('start_at')
            ->limit(5)
            ->get()
            ->map(fn ($e) => [
                'id'       => $e->id,
                'title'    => $e->title,
                'start_at' => $e->start_at->toIso8601String(),
                'all_day'  => $e->all_day,
            ]);

        return response()->json([
            'entities'        => $entityCount,
            'people'          => $peopleCount,
            'open_deals'      => $openDealsCount,
            'pipeline_value'  => $pipelineValue,
            'won_deals_30d'   => $wonDeals30d,
            'won_value_30d'   => $wonValue30d,
            'deals_by_stage'  => $dealsByStage,
            'recent_deals'    => $recentDeals,
            'upcoming_events' => $upcomingEvents,
        ]);
    }
}
