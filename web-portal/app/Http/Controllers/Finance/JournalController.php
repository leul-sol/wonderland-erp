<?php

namespace App\Http\Controllers\Finance;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Concerns\LoadsGatewayDataInParallel;
use App\Http\Controllers\Controller;
use App\Services\Api\S4FinanceClient;
use App\Services\Auth\PortalAuthService;
use App\Support\JournalApprovalSteps;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class JournalController extends Controller
{
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;
    use LoadsGatewayDataInParallel;

    public function __construct(
        private readonly S4FinanceClient $s4,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        $sourceModule = (string) $request->input('source_module', 'all');
        $allowedSources = ['all', 'manual', 's2', 's3', 's4'];
        if (! in_array($sourceModule, $allowedSources, true)) {
            $sourceModule = 'all';
        }

        return Inertia::render('Finance/Journals/Index', [
            'sourceModule' => $sourceModule,
            'canCreate' => $this->auth->hasAnyPermission(['S4.finance.journal_entries.create']),
            'defaultEntryDate' => now()->toDateString(),
            'pageLoad' => $this->deferPageLoad(function () use ($sourceModule) {
                $journalQuery = ['per_page' => 50];
                if ($sourceModule !== 'all') {
                    $journalQuery['source_module'] = $sourceModule;
                }

                $results = $this->fetchGatewayInParallel($this->s4, [
                    'journalEntries' => ['path' => '/s4/api/v1/journal-entries', 'query' => $journalQuery],
                    'accounts' => ['path' => '/s4/api/v1/accounts', 'query' => []],
                ]);
                $response = $this->requireParallelResult($results, 'journalEntries');
                $accounts = $results['accounts'] ?? ['data' => []];

                $entries = $response['data'] ?? [];
                if (isset($entries['data']) && is_array($entries['data'])) {
                    $entries = $entries['data'];
                }

                return [
                    'journalEntries' => is_array($entries) ? $entries : [],
                    'accounts' => $accounts['data'] ?? [],
                ];
            }),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('finance.journals.index', ['open' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'entry_date' => ['nullable', 'date'],
            'source_reference' => ['nullable', 'string', 'max:100'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_code' => ['required', 'string', 'max:20'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
        ]);

        $lines = collect($data['lines'])->map(fn (array $line) => [
            'account_code' => $line['account_code'],
            'debit' => (float) ($line['debit'] ?? 0),
            'credit' => (float) ($line['credit'] ?? 0),
            'description' => $line['description'] ?? null,
        ])->values()->all();

        try {
            $response = $this->s4->createJournalEntry([
                'description' => $data['description'],
                'entry_date' => $data['entry_date'] ?? now()->toDateString(),
                'source_module' => 'manual',
                'source_reference' => $data['source_reference'] ?? null,
                'lines' => $lines,
            ], (string) Str::uuid());
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        $entryId = (int) ($response['data']['id'] ?? 0);

        if ($entryId <= 0) {
            return back()->with('error', 'Journal entry was not created.');
        }

        return redirect()
            ->route('finance.journals.show', $entryId)
            ->with('success', 'Manual journal saved as draft.');
    }

    public function show(int $journalEntry): Response|RedirectResponse
    {
        try {
            $response = $this->s4->journalEntry($journalEntry);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'finance.journals.index');
        }

        $entry = $response['data'] ?? [];
        $requiresGm = JournalApprovalSteps::requiresGm($entry);
        $status = (string) ($entry['status'] ?? '');

        return Inertia::render('Finance/Journals/Show', [
            'journalEntry' => $entry,
            'approvalSteps' => JournalApprovalSteps::steps($requiresGm),
            'approvalCurrentStep' => JournalApprovalSteps::currentStepKey($entry),
            'gmThreshold' => JournalApprovalSteps::GM_THRESHOLD,
            'canApproveFinance' => $status === 'draft' && $this->auth->hasAnyPermission(['S4.finance.journal_entries.approve']),
            'canApproveGm' => $status === 'approved'
                && $requiresGm
                && empty($entry['second_approved_by'])
                && $this->auth->hasAnyPermission(['S4.finance.journal_entries.approve']),
            'canDelete' => $status === 'draft' && $this->auth->hasAnyPermission(['S4.finance.journal_entries.create']),
            'canReverse' => $status === 'posted' && $this->auth->hasAnyPermission(['S4.finance.journal_entries.reverse']),
        ]);
    }

    public function reverse(Request $request, int $journalEntry): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->s4->reverseJournalEntry($journalEntry, array_filter($data));
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('finance.journals.show', $journalEntry)
            ->with('success', 'Journal entry reversed.');
    }

    public function approve(int $journalEntry): RedirectResponse
    {
        try {
            $this->s4->approveJournalEntry($journalEntry);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Journal approval recorded.');
    }

    public function destroy(int $journalEntry): RedirectResponse
    {
        try {
            $this->s4->deleteJournalEntry($journalEntry);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('finance.journals.index')
            ->with('success', 'Draft journal deleted.');
    }
}
