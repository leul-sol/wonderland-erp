<?php

namespace App\Services;

use App\Models\EmployeeConsumptionPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EmployeeConsumptionService
{
    public function __construct(
        private readonly S2WorkforceClient $s2,
        private readonly OutboxService $outbox,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function open(array $data): EmployeeConsumptionPeriod
    {
        $start = Carbon::parse($data['period_start']);
        $end = Carbon::parse($data['period_end']);

        if ($start->gt($end)) {
            throw new InvalidArgumentException('period_start must be on or before period_end.');
        }

        return EmployeeConsumptionPeriod::query()->create([
            'employee_id' => (int) $data['employee_id'],
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'total_amount' => 0,
            'status' => 'open',
        ]);
    }

    public function close(EmployeeConsumptionPeriod $period): EmployeeConsumptionPeriod
    {
        if ($period->status !== 'open') {
            throw new InvalidArgumentException('Only open consumption periods can be closed.');
        }

        return DB::transaction(function () use ($period) {
            $this->syncTotalFromOrders($period);
            $period->refresh();

            $amount = round((float) $period->total_amount, 2);

            if ($amount > 0) {
                $this->s2->postDeduction((int) $period->employee_id, [
                    'deduction_type' => 'staff_meal',
                    'amount' => $amount,
                    'description' => 'Staff meal consumption period',
                    'source_reference' => 'CONSUMPTION-'.$period->id,
                ], 'consumption-'.$period->id);
            }

            $period->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);

            $this->outbox->enqueue(config('events.channels.employee_consumption_period_closed'), [
                'consumption_period_id' => $period->id,
                'employee_id' => $period->employee_id,
                'total_amount' => (string) $amount,
                'period_start' => $period->period_start?->toDateString(),
                'period_end' => $period->period_end?->toDateString(),
            ]);

            return $period->fresh();
        });
    }

    public function syncTotalFromOrders(EmployeeConsumptionPeriod $period): void
    {
        $total = (float) \App\Models\RestaurantOrder::query()
            ->where('employee_consumption_period_id', $period->id)
            ->where('status', 'finalized')
            ->sum('subtotal');

        $period->update(['total_amount' => round($total, 2)]);
    }
}
