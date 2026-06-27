<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FiscalPeriodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS4Auth;
use Tests\TestCase;

class Phase3ReportsTest extends TestCase
{
    use MocksS4Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.internal_key_current' => 'test-service-key',
            'services.s2_url' => 'http://s2.test',
            'services.s3_url' => 'http://s3.test',
        ]);

        $this->seed([
            AccountSeeder::class,
            FiscalPeriodSeeder::class,
        ]);

        Http::fake([
            'http://s2.test/api/v1/employees' => Http::response([
                'data' => [
                    ['id' => 1, 'full_name' => 'Abebe Kebede', 'status' => 'active'],
                ],
            ]),
            'http://s2.test/api/v1/payroll-runs' => Http::response([
                'data' => [
                    [
                        'id' => 10,
                        'run_number' => 'PR-2026-06',
                        'status' => 'approved',
                        'period_start' => '2026-06-01',
                        'period_end' => '2026-06-30',
                        'lines' => [
                            [
                                'employee_id' => 1,
                                'employee_name' => 'Abebe Kebede',
                                'employee_pension' => '700.00',
                                'employer_pension' => '1100.00',
                                'gross_salary' => '10000.00',
                                'net_pay' => '8500.00',
                            ],
                        ],
                    ],
                ],
            ]),
            'http://s2.test/api/v1/leave-requests' => Http::response([
                'data' => [
                    ['id' => 1, 'leave_type' => 'annual', 'status' => 'approved', 'days_requested' => 5],
                    ['id' => 2, 'leave_type' => 'annual', 'status' => 'pending', 'days_requested' => 2],
                ],
            ]),
            'http://s2.test/api/v1/overtime-records' => Http::response([
                'data' => [
                    ['id' => 1, 'employee_id' => 1, 'hours' => '4.00', 'status' => 'approved'],
                ],
            ]),
            'http://s2.test/api/v1/offboarding-records' => Http::response([
                'data' => [
                    ['id' => 1, 'employee_id' => 2, 'employee_name' => 'Sara Tesfaye', 'clearance_status' => 'pending'],
                ],
            ]),
            'http://s2.test/api/v1/employees/1/disciplinary-records' => Http::response([
                'data' => [
                    ['action_type' => 'warning', 'effective_date' => '2026-05-01', 'reason' => 'Late arrival'],
                ],
            ]),
            'http://s3.test/api/v1/folios/5/invoice' => Http::response([
                'data' => [
                    'folio_number' => 'F-0005',
                    'guest_full_name' => 'Guest One',
                    'guest_phone' => '+251911000000',
                    'guest_email' => 'guest@example.com',
                    'room_number' => '101',
                    'check_in_date' => '2026-06-01',
                    'check_out_date' => '2026-06-03',
                    'total_charges' => '1500.00',
                    'total_payments' => '500.00',
                    'outstanding_balance' => '1000.00',
                    'currency' => 'ETB',
                    'lines' => [['description' => 'Room', 'amount' => '1500.00']],
                    'issued_at' => now()->toIso8601String(),
                ],
            ]),
            'http://s3.test/api/v1/folios*' => Http::response([
                'data' => [
                    'data' => [
                        [
                            'id' => 5,
                            'reservation_id' => 12,
                            'status' => 'open',
                            'total_charges' => '1500.00',
                            'total_payments' => '500.00',
                            'balance' => '1000.00',
                            'currency' => 'ETB',
                        ],
                    ],
                ],
            ]),
            'http://s3.test/api/v1/employee-consumption-periods' => Http::response([
                'data' => [
                    ['id' => 1, 'status' => 'open', 'employee_id' => 1],
                ],
            ]),
            'http://s3.test/api/v1/items' => Http::response([
                'data' => [
                    ['id' => 3, 'sku' => 'FLOUR-01', 'name' => 'Flour'],
                ],
            ]),
            'http://s3.test/api/v1/items/3/movements*' => Http::response([
                'data' => [
                    'data' => [
                        ['movement_type' => 'issue', 'quantity' => '2.00', 'created_at' => now()->toIso8601String()],
                    ],
                ],
            ]),
            'http://s3.test/api/v1/supplier-payments' => Http::response([
                'data' => [
                    ['id' => 1, 'supplier_name' => 'Fresh Farms', 'amount' => '500.00', 'payment_date' => '2026-06-01'],
                ],
            ]),
            'http://s3.test/api/v1/orders' => Http::response([
                'data' => [
                    ['id' => 9, 'status' => 'finalized', 'customer_type' => 'event', 'subtotal' => '1200.00', 'total_amount' => '1356.00'],
                ],
            ]),
            'http://s2.test/api/v1/employees/1/guarantors' => Http::response([
                'data' => [
                    ['id' => 3, 'full_name' => 'Guarantor One', 'letter_path' => 'guarantors/guarantor-3.pdf'],
                ],
            ]),
            'http://s2.test/api/v1/employees/1/payslip/10' => Http::response('%PDF-1.4 fake', 200, ['Content-Type' => 'application/pdf']),
        ]);
    }

    public function test_payroll_pension_report_aggregates_contributions(): void
    {
        $response = $this->getJson('/api/v1/reports/workforce/payroll-pension', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.report', 'payroll_pension')
            ->assertJsonPath('data.total_employee_pension', '700.00')
            ->assertJsonPath('data.total_employer_pension', '1100.00');
    }

    public function test_payroll_overtime_report_lists_records(): void
    {
        $response = $this->getJson('/api/v1/bi/reports/payroll_overtime', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.report', 'payroll_overtime')
            ->assertJsonPath('data.record_count', 1)
            ->assertJsonPath('data.total_hours', '4.00');
    }

    public function test_leave_utilisation_groups_by_leave_type(): void
    {
        $response = $this->getJson('/api/v1/reports/workforce/leave-utilisation', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.report', 'leave_utilisation')
            ->assertJsonPath('data.lines.0.leave_type', 'annual')
            ->assertJsonPath('data.lines.0.approved_days', '5.0');
    }

    public function test_folio_outstanding_uses_s3_folio_balances(): void
    {
        $response = $this->getJson('/api/v1/reports/hotel/folio-outstanding', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.report', 'folio_outstanding')
            ->assertJsonPath('data.outstanding_count', 1)
            ->assertJsonPath('data.total_outstanding', '1000.00');
    }

    public function test_guest_folio_invoice_includes_mandatory_fields(): void
    {
        $response = $this->getJson('/api/v1/reports/hotel/guest-folio-invoice', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.report', 'guest_folio_invoice')
            ->assertJsonPath('data.invoice_count', 1)
            ->assertJsonPath('data.lines.0.folio_number', 'F-0005')
            ->assertJsonPath('data.lines.0.guest_full_name', 'Guest One')
            ->assertJsonPath('data.lines.0.outstanding_balance', '1000.00');
    }

    public function test_bi_export_csv_for_payroll_overtime(): void
    {
        $response = $this->postJson('/api/v1/bi/exports', [
            'report' => 'payroll_overtime',
            'format' => 'csv',
        ], $this->authHeaders());

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', strtolower((string) $response->headers->get('content-disposition')));
    }

    public function test_payroll_payslip_report_lists_downloadable_rows(): void
    {
        $response = $this->getJson('/api/v1/reports/workforce/payslip', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.report', 'payroll_payslip')
            ->assertJsonPath('data.payslip_count', 1)
            ->assertJsonPath('data.lines.0.pdf_route', '/reports/workforce/payslip/1/10');
    }

    public function test_payslip_pdf_export_proxies_s2_document(): void
    {
        $response = $this->postJson('/api/v1/bi/exports', [
            'report' => 'payroll_payslip',
            'format' => 'pdf',
            'employee_id' => 1,
            'payroll_run_id' => 10,
        ], $this->authHeaders());

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_supplier_payment_history_reads_s3_payments(): void
    {
        $response = $this->getJson('/api/v1/reports/hospitality/supplier-payment-history', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.report', 'supplier_payment_history')
            ->assertJsonPath('data.payment_count', 1)
            ->assertJsonPath('data.total_paid', '500.00');
    }

    public function test_event_fb_billing_filters_event_orders(): void
    {
        $response = $this->getJson('/api/v1/bi/reports/event_fb_billing', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.report', 'event_fb_billing')
            ->assertJsonPath('data.order_count', 1)
            ->assertJsonPath('data.total_billed', '1356.00');
    }

    public function test_guarantor_letter_report_lists_pdf_routes(): void
    {
        $response = $this->getJson('/api/v1/reports/hr/guarantor-letter', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.report', 'hr_guarantor_letter')
            ->assertJsonPath('data.letter_count', 1)
            ->assertJsonPath('data.lines.0.pdf_route', '/reports/hr/guarantor-letter/1/3');
    }

    public function test_posted_journal_entry_cannot_be_updated(): void
    {
        $this->postJson('/api/v1/journal-entries', [
            'description' => 'Immutable test',
            'source_module' => 's3',
            'source_reference' => 'IMM-1',
            'entry_date' => now()->toDateString(),
            'lines' => [
                ['account_code' => '1100', 'debit' => 100, 'credit' => 0],
                ['account_code' => '4001', 'debit' => 0, 'credit' => 100],
            ],
        ], [
            'X-Service-Key' => 'test-service-key',
            'Idempotency-Key' => 'immutable-journal-1',
        ])->assertCreated();

        $entry = JournalEntry::query()->where('source_reference', 'IMM-1')->firstOrFail();

        $this->expectException(\RuntimeException::class);
        $entry->update(['description' => 'Changed']);
    }
}
